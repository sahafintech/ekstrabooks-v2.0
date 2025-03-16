<?php

namespace App\Http\Controllers\User;

use App\Exports\BalanceSheetExport;
use App\Exports\GenJournalExport;
use App\Exports\IncomeStatementExport;
use App\Exports\InventoryDetailsExport;
use App\Exports\InventorySummaryExport;
use App\Exports\LedgerExport;
use App\Exports\ReceivablesExport;
use App\Exports\SalesByProductExport;
use App\Exports\TrialBalanceExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\InventoryAdjustment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {}

	public function account_balances(Request $request)
	{
		$page_title = _lang('Account Balances');
		$assets = ['datatable'];
		$accounts = get_account_details();
		return view('backend.user.reports.account_balances', compact('accounts', 'assets', 'page_title'));
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function account_statement(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Account Statement');
			return view('backend.user.reports.account_statement', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = $request->date1;
			$date2 = $request->date2;
			$account_id = isset($request->account_id) ? $request->account_id : '';

			$account = Account::find($account_id);
			$business_id = request()->activeBusiness->id;

			if (!$account) {
				return back()->with('error', _lang('Account not found'));
			}

			DB::select("SELECT ((SELECT IFNULL(SUM(amount),0) FROM transactions WHERE dr_cr = 'cr' AND business_id = $business_id AND account_id = $account->id AND created_at < '$date1') - (SELECT IFNULL(SUM(amount),0) FROM transactions WHERE dr_cr = 'dr' AND business_id = $business_id AND account_id = $account->id AND created_at < '$date1')) into @openingBalance");

			$data['report_data'] = DB::select("SELECT '$date1' trans_date,'Opening Balance' as category, 'Opening Balance' as description, 0 as 'debit', 0 as 'credit', @openingBalance as 'balance'
            UNION ALL
            SELECT date(trans_date), category, description, debit, credit, @openingBalance := @openingBalance + (credit - debit) as balance FROM
            (SELECT date(transactions.trans_date) as trans_date, transaction_categories.name as category, transactions.description, IF(transactions.dr_cr='dr',transactions.amount,0) as debit, IF(transactions.dr_cr='cr',transactions.amount,0) as credit FROM `transactions` JOIN accounts ON account_id=accounts.id
            LEFT JOIN transaction_categories ON transaction_categories.id=transactions.transaction_category_id WHERE accounts.id = $account->id AND accounts.business_id = $business_id AND date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2')
            as all_transaction");

			$data['date1'] = $request->date1;
			$data['date2'] = $request->date2;
			$data['account_id'] = $request->account_id;
			$data['account'] = $account;
			$data['page_title'] = _lang('Account Statement');
			return view('backend.user.reports.account_statement', $data);
		}
	}

	public function profit_and_loss(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Profit & Loss Report');
			return view('backend.user.reports.profit_and_loss', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = $request->date1;
			$date2 = $request->date2;
			$report_type = $request->report_type;

			if ($report_type == 'paid_unpaid') {
				$invoices = Invoice::with(['items.product.income_category'])
					->whereRaw("date(invoice_date) >= '$date1' AND date(invoice_date) <= '$date2'")
					->where('invoices.status', '!=', 0)
					->where('is_recurring', 0)
					->get();

				$data['invoices'] = array();
				$data['sales_discount'] = 0;
				foreach ($invoices as $invoice) {
					foreach ($invoice->items as $invoice_item) {
						if (isset($data['invoices'][$invoice_item->product->income_category_id])) {
							$existingAmount = $data['invoices'][$invoice_item->product->income_category_id]['amount'];
							$data['invoices'][$invoice_item->product->income_category_id] = ['category' => $invoice_item->product->income_category->name, 'amount' => $existingAmount + $invoice_item->sub_total];
						} else {
							$data['invoices'][$invoice_item->product->income_category_id] = ['category' => $invoice_item->product->income_category->name, 'amount' => $invoice_item->sub_total];
						}
					}
					$data['sales_discount'] += $invoice->discount;
				}

				$data['othersIncome'] = Transaction::with('category')
					->selectRaw('transaction_category_id, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
					->where('dr_cr', 'cr')
					->where('ref_id', null)
					//->where('transaction_category_id', '!=', null)
					->whereRaw("date(trans_date) >= '$date1' AND date(trans_date) <= '$date2'")
					->groupBy('transaction_category_id')
					->get();

				$purchases = Purchase::with(['items.product.expense_category'])
					->whereRaw("date(purchase_date) >= '$date1' AND date(purchase_date) <= '$date2'")
					->get();

				$data['purchases'] = array();
				$data['purchase_discount'] = 0;
				foreach ($purchases as $purchase) {
					foreach ($purchase->items as $purchase_item) {
						if (isset($data['purchases'][$purchase_item->product->expense_category_id])) {
							$existingAmount = $data['purchases'][$purchase_item->product->expense_category_id]['amount'];
							$data['purchases'][$purchase_item->product->expense_category_id] = ['category' => $purchase_item->product->expense_category->name, 'amount' => $existingAmount + $purchase_item->sub_total];
						} else {
							$data['purchases'][$purchase_item->product->expense_category_id] = ['category' => $purchase_item->product->expense_category->name, 'amount' => $purchase_item->sub_total];
						}
					}
					$data['purchase_discount'] += $purchase->discount;
				}

				$data['othersExpense'] = Transaction::with('category')
					->selectRaw('transaction_category_id, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
					->where('dr_cr', 'dr')
					->where('ref_id', null)
					//->where('transaction_category_id', '!=', null)
					->whereRaw("date(trans_date) >= '$date1' AND date(trans_date) <= '$date2'")
					->groupBy('transaction_category_id')
					->get();
			}

			if ($report_type == 'paid') {
				$invoices = Invoice::with(['items.product.income_category'])
					->whereRaw("date(invoice_date) >= '$date1' AND date(invoice_date) <= '$date2'")
					->where('invoices.paid', '>', 0)
					->where('is_recurring', 0)
					->get();

				$data['invoices'] = array();
				$data['sales_discount'] = 0;
				foreach ($invoices as $invoice) {
					$percentage = (100 / $invoice->grand_total) * $invoice->paid;

					foreach ($invoice->items as $invoice_item) {
						if (isset($data['invoices'][$invoice_item->product->income_category_id])) {
							$existingAmount = $data['invoices'][$invoice_item->product->income_category_id]['amount'];
							$data['invoices'][$invoice_item->product->income_category_id] = ['category' => $invoice_item->product->income_category->name, 'amount' => $existingAmount + (($percentage / 100) * $invoice_item->sub_total)];
						} else {
							$data['invoices'][$invoice_item->product->income_category_id] = ['category' => $invoice_item->product->income_category->name, 'amount' => ($percentage / 100) * $invoice_item->sub_total];
						}
					}
					$data['sales_discount'] += ($percentage / 100) * $invoice->discount;
				}

				$data['othersIncome'] = Transaction::with('category')
					->selectRaw('transaction_category_id, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
					->where('dr_cr', 'cr')
					->where('ref_id', null)
					//->where('transaction_category_id', '!=', null)
					->whereRaw("date(trans_date) >= '$date1' AND date(trans_date) <= '$date2'")
					->groupBy('transaction_category_id')
					->get();

				$purchases = Purchase::with(['items.product.expense_category'])
					->whereRaw("date(purchase_date) >= '$date1' AND date(purchase_date) <= '$date2'")
					->where('purchases.paid', '>', 0)
					->get();

				$data['purchases'] = array();
				$data['purchase_discount'] = 0;
				foreach ($purchases as $purchase) {
					$percentage = (100 / $purchase->grand_total) * $purchase->paid;
					foreach ($purchase->items as $purchase_item) {
						if (isset($data['purchases'][$purchase_item->product->expense_category_id])) {
							$existingAmount = $data['purchases'][$purchase_item->product->expense_category_id]['amount'];
							$data['purchases'][$purchase_item->product->expense_category_id] = ['category' => $purchase_item->product->expense_category->name, 'amount' => $existingAmount + (($percentage / 100) * $purchase_item->sub_total)];
						} else {
							$data['purchases'][$purchase_item->product->expense_category_id] = ['category' => $purchase_item->product->expense_category->name, 'amount' => ($percentage / 100) * $purchase_item->sub_total];
						}
					}
					$data['purchase_discount'] += ($percentage / 100) * $purchase->discount;
				}

				$data['othersExpense'] = Transaction::with('category')
					->selectRaw('transaction_category_id, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
					->where('dr_cr', 'dr')
					->where('ref_id', null)
					//->where('transaction_category_id', '!=', null)
					->whereRaw("date(trans_date) >= '$date1' AND date(trans_date) <= '$date2'")
					->groupBy('transaction_category_id')
					->get();
			}

			$data['date1'] = $request->date1;
			$data['date2'] = $request->date2;
			$data['report_type'] = $request->report_type;
			$data['report_data'] = true;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Profit & Loss Report');
			return view('backend.user.reports.profit_and_loss', $data);
		}
	}

	public function transactions_report(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Transactions Report');
			return view('backend.user.reports.transactions_report', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = $request->date1;
			$date2 = $request->date2;
			$account_id = isset($request->account_id) ? $request->account_id : '';
			$transaction_type = isset($request->transaction_type) ? $request->transaction_type : '';

			$data['report_data'] = Transaction::select('transactions.*')
				->with(['account', 'category'])
				->when($transaction_type, function ($query, $transaction_type) {
					return $query->where('type', $transaction_type);
				})
				->when($account_id, function ($query, $account_id) {
					return $query->whereHas('account', function ($query) use ($account_id) {
						return $query->where('account_id', $account_id);
					});
				})
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->orderBy('transactions.trans_date', 'desc')
				->get();

			$data['date1'] = $request->date1;
			$data['date2'] = $request->date2;
			$data['status'] = $request->status;
			$data['account_id'] = $request->account_id;
			$data['transaction_type'] = $request->transaction_type;
			$data['page_title'] = _lang('Transactions Report');
			return view('backend.user.reports.transactions_report', $data);
		}
	}

	public function income_by_customer(Request $request)
	{
		if ($request->isMethod('get')) {

			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			$data['report_data'] = array();

			$credit_invoices = Invoice::with('customer')
				->selectRaw('customer_id, SUM(grand_total) as total_income, sum(paid) as total_paid')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
				->where('is_recurring', 0)
				->where('status', '!=', 0)
				->groupBy('customer_id')
				->get();

			$cash_invoices = Receipt::with('customer')
				->selectRaw('customer_id, SUM(grand_total) as total_income, SUM(grand_total) as total_paid')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(receipts.receipt_date) >= '$date1' AND date(receipts.receipt_date) <= '$date2'")
				->groupBy('customer_id')
				->get();

			foreach ($credit_invoices as $invoice) {
				$data['report_data'][$invoice->customer_id] = [
					'customer_id' => $invoice->customer_id,
					'customer_name' => $invoice->customer->name,
					'total_income' => $invoice->total_income,
					'total_paid' => $invoice->total_paid,
					'total_due' => $invoice->total_income - $invoice->total_paid,
				];
			}

			foreach ($cash_invoices as $invoice) {
				if (isset($data['report_data'][$invoice->customer_id])) {
					$data['report_data'][$invoice->customer_id]['total_income'] += $invoice->total_income;
					$data['report_data'][$invoice->customer_id]['total_paid'] += $invoice->total_paid;
					$data['report_data'][$invoice->customer_id]['total_due'] = $data['report_data'][$invoice->customer_id]['total_income'] - $data['report_data'][$invoice->customer_id]['total_paid'];
				} else {
					$data['report_data'][$invoice->customer_id] = [
						'customer_id' => $invoice->customer_id,
						'customer_name' => $invoice->customer->name,
						'total_income' => $invoice->total_income,
						'total_paid' => $invoice->total_paid,
						'total_due' => $invoice->total_income - $invoice->total_paid,
					];
				}
			}

			$grand_total_income = 0;
			$grand_total_paid = 0;
			$grand_total_due = 0;

			foreach ($data['report_data'] as $key => $value) {
				$grand_total_income += $value['total_income'];
				$grand_total_paid += $value['total_paid'];
				$grand_total_due += $value['total_due'];
			}

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['customer_id'] = $request->customer_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Income By Customers');
			$data['grand_total_income'] = $grand_total_income;
			$data['grand_total_paid'] = $grand_total_paid;
			$data['grand_total_due'] = $grand_total_due;

			return view('backend.user.reports.income_by_customer', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			if ($request->customer_id == 'all') {
				$customer_id = '';
			}

			$data['report_data'] = array();

			$credit_invoices = Invoice::with('customer')
				->selectRaw('customer_id, SUM(grand_total) as total_income, sum(paid) as total_paid')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
				->where('is_recurring', 0)
				->where('status', '!=', 0)
				->groupBy('customer_id')
				->get();

			$cash_invoices = Receipt::with('customer')
				->selectRaw('customer_id, SUM(grand_total) as total_income, SUM(grand_total) as total_paid')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(receipts.receipt_date) >= '$date1' AND date(receipts.receipt_date) <= '$date2'")
				->groupBy('customer_id')
				->get();

			foreach ($credit_invoices as $invoice) {
				$data['report_data'][$invoice->customer_id] = [
					'customer_id' => $invoice->customer_id,
					'customer_name' => $invoice->customer->name,
					'total_income' => $invoice->total_income,
					'total_paid' => $invoice->total_paid,
					'total_due' => $invoice->total_income - $invoice->total_paid,
				];
			}

			foreach ($cash_invoices as $invoice) {
				if (isset($data['report_data'][$invoice->customer_id])) {
					$data['report_data'][$invoice->customer_id]['total_income'] += $invoice->total_income;
					$data['report_data'][$invoice->customer_id]['total_paid'] += $invoice->total_paid;
					$data['report_data'][$invoice->customer_id]['total_due'] = $data['report_data'][$invoice->customer_id]['total_income'] - $data['report_data'][$invoice->customer_id]['total_paid'];
				} else {
					$data['report_data'][$invoice->customer_id] = [
						'customer_id' => $invoice->customer_id,
						'customer_name' => $invoice->customer->name,
						'total_income' => $invoice->total_income,
						'total_paid' => $invoice->total_paid,
						'total_due' => $invoice->total_income - $invoice->total_paid,
					];
				}
			}

			$grand_total_income = 0;
			$grand_total_paid = 0;
			$grand_total_due = 0;

			foreach ($data['report_data'] as $key => $value) {
				$grand_total_income += $value['total_income'];
				$grand_total_paid += $value['total_paid'];
				$grand_total_due += $value['total_due'];
			}

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['customer_id'] = $request->customer_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Income By Customers');
			$data['grand_total_income'] = $grand_total_income;
			$data['grand_total_paid'] = $grand_total_paid;
			$data['grand_total_due'] = $grand_total_due;

			return view('backend.user.reports.income_by_customer', $data);
		}
	}

	public function receivables(Request $request)
	{
		if ($request->isMethod('get')) {

			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['customer_id' => $customer_id]);

			$data['report_data'] = Invoice::with('customer')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
				->where('is_recurring', 0)
				->get();

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['customer_id'] = $request->customer_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Receivables');

			return view('backend.user.reports.receivables', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['customer_id' => $customer_id]);

			$data['report_data'] = Invoice::with('customer')
				->when($customer_id, function ($query, $customer_id) {
					return $query->where('customer_id', $customer_id);
				})
				->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
				->where('is_recurring', 0)
				->get();

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['customer_id'] = $request->customer_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Receivables');
			return view('backend.user.reports.receivables', $data);
		}
	}

	public function payables(Request $request)
	{
		if ($request->isMethod('get')) {

			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();
			$vendor_id = isset($request->vendor_id) ? $request->vendor_id : '';

			$data['report_data'] = Purchase::with('vendor')
				->when($vendor_id, function ($query, $vendor_id) {
					return $query->where('vendor_id', $vendor_id);
				})
				->whereRaw("date(purchases.purchase_date) >= '$date1' AND date(purchases.purchase_date) <= '$date2'")
				->get();

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['vendor_id'] = $request->vendor_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Paybles');

			return view('backend.user.reports.payables', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');
			$vendor_id = isset($request->vendor_id) ? $request->vendor_id : '';

			$data['report_data'] = Purchase::with('vendor')
				->when($vendor_id, function ($query, $vendor_id) {
					return $query->where('vendor_id', $vendor_id);
				})
				->whereRaw("date(purchases.purchase_date) >= '$date1' AND date(purchases.purchase_date) <= '$date2'")
				->get();

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['customer_id'] = $request->customer_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payables');
			return view('backend.user.reports.payables', $data);
		}
	}

	public function ledger(Request $request)
	{
		if ($request->isMethod('get')) {
			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data'] = Account::with(['transactions' => function ($query) use ($date1, $date2) {
				$query->whereDate('trans_date', '>=', $date1)
					->whereDate('trans_date', '<=', $date2)
					->orderBy('trans_date', 'desc');
			}])
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2)
						->orderBy('trans_date', 'desc');
				})
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['page_title'] = _lang('Ledger');

			return view('backend.user.reports.ledger', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data'] = Account::with(['transactions' => function ($query) use ($date1, $date2) {
				$query->whereDate('trans_date', '>=', $date1)
					->whereDate('trans_date', '<=', $date2)
					->orderBy('trans_date', 'desc');
			}])
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2)
						->orderBy('trans_date', 'desc');
				})
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['page_title'] = _lang('Ledger');
			return view('backend.user.reports.ledger', $data);
		}
	}

	public function journal(Request $request)
	{
		if ($request->isMethod('get')) {
			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$normal_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
				->get();

			$get_payment_income_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
				->get(); // Fetch the transactions as a collection

			$payment_income_transactions = $get_payment_income_transactions->groupBy(function ($transaction) {
				// Check if the ref_id contains a comma
				if (strpos($transaction->ref_id, ',') !== false) {
					// Extract the second part of ref_id
					return explode(',', $transaction->ref_id)[1];
				}
				// Otherwise, return the ref_id as is
				return $transaction->ref_id;
			});

			$payslip_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->where('ref_type', 'payslip')
				->get()
				->groupBy('trans_date');

			$data['report_data'] = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['page_title'] = _lang('Journal');

			return view('backend.user.reports.gen_journal', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$normal_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->whereNotIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'payslip', 'd invoice income', 'd invoice tax'])
				->get();

			$get_payment_income_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->whereIn('ref_type', ['invoice payment', 'bill payment', 'd invoice payment', 'd invoice income', 'd invoice tax'])
				->get(); // Fetch the transactions as a collection

			$payment_income_transactions = $get_payment_income_transactions->groupBy(function ($transaction) {
				// Check if the ref_id contains a comma
				if (strpos($transaction->ref_id, ',') !== false) {
					// Extract the second part of ref_id
					return explode(',', $transaction->ref_id)[1];
				}
				// Otherwise, return the ref_id as is
				return $transaction->ref_id;
			});

			$payslip_transactions = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'")
				->where('ref_type', 'payslip')
				->get()
				->groupBy('trans_date');

			$data['report_data'] = $this->combine($payment_income_transactions, $payslip_transactions, $normal_transactions);

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['page_title'] = _lang('Journal');
			return view('backend.user.reports.gen_journal', $data);
		}
	}

	function combine($payment_income_transactions, $payslip_transactions, $normal_transactions)
	{
		// Step 1: Aggregate payment transactions
		$aggregated_payment_income_transactions = $payment_income_transactions->map(function ($group, $key) {
			$totalAmount = $group->sum('base_currency_amount');
			$totalTransAmount = $group->sum('transaction_amount');
			$trans_date  = $group[0]->trans_date;
			$dr_cr  = $group[0]->dr_cr;
			$ref_type = $group[0]->ref_type;
			$ref_id = $group[0]->ref_id;
			$count = $group->count();
			if ($ref_type == 'invoice payment' || $ref_type == 'd invoice payment') {
				$description = $count . ' Invoices Payment';
			} else if ($ref_type == 'bill payment') {
				$description = $count . ' Bills Payment';
			} else if ($ref_type == 'd invoice income') {
				$description = 'Deffered Earnings Income From Invoice #' . Invoice::find($ref_id)->invoice_number;
			} else if ($ref_type == 'd invoice tax') {
				$description = 'Deffered Tax From Invoice #' . Invoice::find($ref_id)?->invoice_number;
			}
			return (object) [
				'trans_date' => $trans_date,
				'description' => "{$description}",
				'base_currency_amount' => $totalAmount,
				'transaction_currency' => $group[0]->transaction_currency,
				'transaction_amount' => $totalTransAmount,
				'currency_rate' => $group[0]->currency_rate,
				'dr_cr' => $dr_cr,
				'ref_type' => $ref_type,
				'ref_id' => $ref_id,
				'payee_name' => $group[0]->payee_name,
				'group_key' => $key, // Optional: Keep the group key for reference if needed
				'account' => (object) [
					'id' => $group[0]->account->id,
					'account_name' => $group[0]->account->account_name,
					'account_number' => $group[0]->account->account_number,
				],
			];
		})->values(); // Flatten the results into a list

		// Step 5: Aggregate payslip transactions
		$aggregated_payslip_transactions = $payslip_transactions->map(function ($group, $key) {
			$totalAmount = $group->sum('base_currency_amount');
			$totalTransAmount = $group->sum('transaction_amount');
			$trans_date  = $group[0]->trans_date;
			$dr_cr  = $group[0]->dr_cr;
			$ref_type = $group[0]->ref_type;
			$ref_id = $group[0]->ref_id;
			$count = $group->count();
			return (object) [
				'trans_date' => $trans_date,
				'description' => "{$count}, Staffs Salary",
				'base_currency_amount' => $totalAmount,
				'transaction_currency' => $group[0]->transaction_currency,
				'transaction_amount' => $totalTransAmount,
				'currency_rate' => $group[0]->currency_rate,
				'dr_cr' => $dr_cr,
				'ref_type' => $ref_type,
				'ref_id' => $ref_id,
				'payee_name' => $group[0]->payee_name,
				'group_key' => $key, // Optional: Keep the group key for reference if needed
				'account' => (object) [
					'id' => $group[0]->account->id,
					'account_name' => $group[0]->account->account_name,
					'account_number' => $group[0]->account->account_number,
				],
			];
		})->values(); // Flatten the results into a list

		// Step 6: Combine all aggregated transactions
		$combined_transactions = collect()
			->merge($aggregated_payment_income_transactions)
			->merge($aggregated_payslip_transactions);

		// Step 7: Include normal transactions as is
		$normal_transactions_summary = $normal_transactions->map(function ($transaction) {
			return (object) [
				'trans_date' => $transaction->trans_date,
				'description' => $transaction->description,
				'base_currency_amount' => $transaction->base_currency_amount,
				'transaction_currency' => $transaction->transaction_currency,
				'transaction_amount' => $transaction->transaction_amount,
				'currency_rate' => $transaction->currency_rate,
				'ref_type' => $transaction->ref_type,
				'ref_id' => $transaction->ref_id,
				'payee_name' => $transaction->payee_name,
				'currency' => $transaction->transaction_currency,
				'dr_cr' => $transaction->dr_cr,
				'account' => (object) [
					'id' => $transaction->account->id,
					'account_name' => $transaction->account->account_name,
					'account_number' => $transaction->account->account_number,
				],
			];
		});

		// Step 8: Combine everything into a single collection
		$final_collection = $combined_transactions->merge($normal_transactions_summary);

		// Output or use the $final_collection
		return $final_collection;
	}

	public function trial_balance(Request $request)
	{
		if ($request->isMethod('get')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data']['fixed_asset'] = Account::where(function ($query) {
				$query->where('account_type', 'Fixed Asset');
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
			})->where(function ($query) {
				$query->where('business_id', '=', request()->activeBusiness->id)
					->orWhere('business_id', '=', null);
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$total_debit = 0;
			$total_credit = 0;

			foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
				$total_debit += $fixed_asset->dr_amount;
				$total_credit += $fixed_asset->cr_amount;
			}

			foreach ($data['report_data']['current_asset'] as $current_asset) {
				$total_debit += $current_asset->dr_amount;
				$total_credit += $current_asset->cr_amount;
			}

			foreach ($data['report_data']['current_liability'] as $current_liability) {
				$total_debit += $current_liability->dr_amount;
				$total_credit += $current_liability->cr_amount;
			}

			foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
				$total_debit += $long_term_liability->dr_amount;
				$total_credit += $long_term_liability->cr_amount;
			}

			foreach ($data['report_data']['equity'] as $equity) {
				$total_debit += $equity->dr_amount;
				$total_credit += $equity->cr_amount;
			}

			foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
				$total_debit += $sales_and_income->dr_amount;
				$total_credit += $sales_and_income->cr_amount;
			}

			foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
				$total_debit += $cost_of_sale->dr_amount;
				$total_credit += $cost_of_sale->cr_amount;
			}

			foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
				$total_debit += $direct_expenses->dr_amount;
				$total_credit += $direct_expenses->cr_amount;
			}

			foreach ($data['report_data']['other_expenses'] as $other_expenses) {
				$total_debit += $other_expenses->dr_amount;
				$total_credit += $other_expenses->cr_amount;
			}

			$data['total_debit'] = $total_debit;
			$data['total_credit'] = $total_credit;

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['page_title'] = _lang('Trial Balance');

			return view('backend.user.reports.trial_balance', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data']['fixed_asset'] = Account::where(function ($query) {
				$query->where('account_type', 'Fixed Asset');
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
			})->where(function ($query) {
				$query->where('business_id', '=', request()->activeBusiness->id)
					->orWhere('business_id', '=', null);
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$total_debit = 0;
			$total_credit = 0;

			foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
				$total_debit += $fixed_asset->dr_amount;
				$total_credit += $fixed_asset->cr_amount;
			}

			foreach ($data['report_data']['current_asset'] as $current_asset) {
				$total_debit += $current_asset->dr_amount;
				$total_credit += $current_asset->cr_amount;
			}

			foreach ($data['report_data']['current_liability'] as $current_liability) {
				$total_debit += $current_liability->dr_amount;
				$total_credit += $current_liability->cr_amount;
			}

			foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
				$total_debit += $long_term_liability->dr_amount;
				$total_credit += $long_term_liability->cr_amount;
			}

			foreach ($data['report_data']['equity'] as $equity) {
				$total_debit += $equity->dr_amount;
				$total_credit += $equity->cr_amount;
			}

			foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
				$total_debit += $sales_and_income->dr_amount;
				$total_credit += $sales_and_income->cr_amount;
			}

			foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
				$total_debit += $cost_of_sale->dr_amount;
				$total_credit += $cost_of_sale->cr_amount;
			}

			foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
				$total_debit += $direct_expenses->dr_amount;
				$total_credit += $direct_expenses->cr_amount;
			}

			foreach ($data['report_data']['other_expenses'] as $other_expenses) {
				$total_debit += $other_expenses->dr_amount;
				$total_credit += $other_expenses->cr_amount;
			}

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['total_debit'] = $total_debit;
			$data['total_credit'] = $total_credit;
			$data['page_title'] = _lang('Trial Balance');
			return view('backend.user.reports.trial_balance', $data);
		}
	}

	public function balance_sheet(Request $request)
	{
		if ($request->isMethod('get')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date2 = Carbon::now();

			session(['end_date' => $date2]);

			$data['report_data']['fixed_asset'] = Account::where(function ($query) {
				$query->where('account_type', 'Fixed Asset');
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
			})->where(function ($query) {
				$query->where('business_id', '=', request()->activeBusiness->id)
					->orWhere('business_id', '=', null);
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$total_debit_asset = 0;
			$total_credit_asset = 0;

			$total_debit_liability = 0;
			$total_credit_liability = 0;

			$total_debit_equity = 0;
			$total_credit_equity = 0;

			$total_debit_sales_and_income = 0;
			$total_credit_sales_and_income = 0;

			$total_debit_cost_of_sale = 0;
			$total_credit_cost_of_sale = 0;

			$total_debit_direct_expenses = 0;
			$total_credit_direct_expenses = 0;

			$total_debit_other_expenses = 0;
			$total_credit_other_expenses = 0;

			foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
				$total_debit_sales_and_income += $sales_and_income->dr_amount;
				$total_credit_sales_and_income += $sales_and_income->cr_amount;
			}

			foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
				$total_debit_cost_of_sale += $cost_of_sale->dr_amount;
				$total_credit_cost_of_sale += $cost_of_sale->cr_amount;
			}

			foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
				$total_debit_direct_expenses += $direct_expenses->dr_amount;
				$total_credit_direct_expenses += $direct_expenses->cr_amount;
			}

			foreach ($data['report_data']['other_expenses'] as $other_expenses) {
				$total_debit_other_expenses += $other_expenses->dr_amount;
				$total_credit_other_expenses += $other_expenses->cr_amount;
			}

			$income_statement = (($data['report_data']['sales_and_income']->sum('cr_amount') - $data['report_data']['sales_and_income']->sum('dr_amount')) - ($data['report_data']['cost_of_sale']->sum('dr_amount') - $data['report_data']['cost_of_sale']->sum('cr_amount'))) - ((($data['report_data']['direct_expenses']->sum('dr_amount') - $data['report_data']['direct_expenses']->sum('cr_amount'))) + (($data['report_data']['other_expenses']->sum('dr_amount') - $data['report_data']['other_expenses']->sum('cr_amount'))));

			foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
				$total_debit_asset += $fixed_asset->dr_amount;
				$total_credit_asset += $fixed_asset->cr_amount;
			}

			foreach ($data['report_data']['current_asset'] as $current_asset) {
				$total_debit_asset += $current_asset->dr_amount;
				$total_credit_asset += $current_asset->cr_amount;
			}

			foreach ($data['report_data']['current_liability'] as $current_liability) {
				$total_debit_liability += $current_liability->dr_amount;
				$total_credit_liability += $current_liability->cr_amount;
			}

			foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
				$total_debit_liability += $long_term_liability->dr_amount;
				$total_credit_liability += $long_term_liability->cr_amount;
			}

			foreach ($data['report_data']['equity'] as $equity) {
				$total_debit_equity += $equity->dr_amount;
				$total_credit_equity += $equity->cr_amount;
			}

			$data['report_data']['equity'][] = (object) [
				'account_code' => '',
				'account_name' => 'Profit & Loss',
				'dr_amount' => $total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses,
				'cr_amount' => $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses,
			];

			$data['date2'] = $date2;
			$data['total_debit_asset'] = $total_debit_asset;
			$data['total_credit_asset'] = $total_credit_asset;
			$data['total_debit_liability'] = $total_debit_liability;
			$data['total_credit_liability'] = $total_credit_liability;
			$data['total_debit_equity'] = $total_debit_equity;
			$data['total_credit_equity'] = $total_credit_equity;
			$data['income_statement'] = $income_statement;
			$data['page_title'] = _lang('Balance Sheet');

			return view('backend.user.reports.balance_sheet', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data']['fixed_asset'] = Account::where(function ($query) {
				$query->where('account_type', 'Fixed Asset');
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
			})->where(function ($query) {
				$query->where('business_id', '=', request()->activeBusiness->id)
					->orWhere('business_id', '=', null);
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
			})
				->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->whereHas('transactions', function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date2) {
					$query->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$total_debit_asset = 0;
			$total_credit_asset = 0;

			$total_debit_liability = 0;
			$total_credit_liability = 0;

			$total_debit_equity = 0;
			$total_credit_equity = 0;

			$total_debit_sales_and_income = 0;
			$total_credit_sales_and_income = 0;

			$total_debit_cost_of_sale = 0;
			$total_credit_cost_of_sale = 0;

			$total_debit_direct_expenses = 0;
			$total_credit_direct_expenses = 0;

			$total_debit_other_expenses = 0;
			$total_credit_other_expenses = 0;

			foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
				$total_debit_sales_and_income += $sales_and_income->dr_amount;
				$total_credit_sales_and_income += $sales_and_income->cr_amount;
			}

			foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
				$total_debit_cost_of_sale += $cost_of_sale->dr_amount;
				$total_credit_cost_of_sale += $cost_of_sale->cr_amount;
			}

			foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
				$total_debit_direct_expenses += $direct_expenses->dr_amount;
				$total_credit_direct_expenses += $direct_expenses->cr_amount;
			}

			foreach ($data['report_data']['other_expenses'] as $other_expenses) {
				$total_debit_other_expenses += $other_expenses->dr_amount;
				$total_credit_other_expenses += $other_expenses->cr_amount;
			}

			$income_statement = $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses - ($total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses);

			foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
				$total_debit_asset += $fixed_asset->dr_amount;
				$total_credit_asset += $fixed_asset->cr_amount;
			}

			foreach ($data['report_data']['current_asset'] as $current_asset) {
				$total_debit_asset += $current_asset->dr_amount;
				$total_credit_asset += $current_asset->cr_amount;
			}

			foreach ($data['report_data']['current_liability'] as $current_liability) {
				$total_debit_liability += $current_liability->dr_amount;
				$total_credit_liability += $current_liability->cr_amount;
			}

			foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
				$total_debit_liability += $long_term_liability->dr_amount;
				$total_credit_liability += $long_term_liability->cr_amount;
			}

			foreach ($data['report_data']['equity'] as $equity) {
				$total_debit_equity += $equity->dr_amount;
				$total_credit_equity += $equity->cr_amount;
			}

			$data['report_data']['equity'][] = (object) [
				'account_code' => '',
				'account_name' => 'Profit & Loss',
				'dr_amount' => $total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses,
				'cr_amount' => $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses,
			];

			$data['date1'] = Carbon::parse($date1);
			$data['date2'] = Carbon::parse($date2);
			$data['total_debit_asset'] = $total_debit_asset;
			$data['total_credit_asset'] = $total_credit_asset;
			$data['total_debit_liability'] = $total_debit_liability;
			$data['total_credit_liability'] = $total_credit_liability;
			$data['total_debit_equity'] = $total_debit_equity;
			$data['total_credit_equity'] = $total_credit_equity;
			$data['income_statement'] = $income_statement;
			$data['page_title'] = _lang('Balance Sheet');

			return view('backend.user.reports.balance_sheet', $data);
		}
	}

	public function ledger_export()
	{
		return Excel::download(new LedgerExport(session('start_date'), session('end_date')), 'ledger ' . now()->format('d m Y') . '.xlsx');
	}

	public function gen_journal_export()
	{
		return Excel::download(new GenJournalExport(session('start_date'), session('end_date')), 'journal ' . now()->format('d m Y') . '.xlsx');
	}

	public function income_statement_export()
	{
		return Excel::download(new IncomeStatementExport(session('start_date'), session('end_date')), 'income_statement ' . now()->format('d m Y') . '.xlsx');
	}

	public function receivables_export()
	{
		return Excel::download(new ReceivablesExport(session('start_date'), session('end_date'), session('customer_id')), 'receivables ' . now()->format('d m Y') . '.xlsx');
	}

	public function trial_balance_export()
	{
		return Excel::download(new TrialBalanceExport(session('start_date'), session('end_date')), 'trial_balance ' . now()->format('d m Y') . '.xlsx');
	}

	public function balance_sheet_export()
	{
		return Excel::download(new BalanceSheetExport(session('end_date')), 'balance_sheet ' . now()->format('d m Y') . '.xlsx');
	}

	public function sales_by_product_export()
	{
		return Excel::download(new SalesByProductExport(session('start_date'), session('end_date'), session('category')), 'sales by product ' . now()->format('d m Y') . '.xlsx');
	}

	public function inventory_details_export()
	{
		return Excel::download(new InventoryDetailsExport(session('start_date'), session('end_date'), session('sub_category')), 'inventory details ' . now()->format('d m Y') . '.xlsx');
	}

	public function inventory_summary_export()
	{
		return Excel::download(new InventorySummaryExport(session('start_date'), session('end_date')), 'inventory summary ' . now()->format('d m Y') . '.xlsx');
	}

	public function income_statement(Request $request)
	{
		if ($request->isMethod('get')) {
			$data = array();

			$date1 = $date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['end_date' => $date2]);

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['date1'] = $date1;
			$data['date2'] = $date2;
			$data['page_title'] = _lang('Income Statement');

			return view('backend.user.reports.income_statement', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$data['report_data']['sales_and_income'] = Account::where(function ($query) {
				$query->where('account_type', 'Other Income')
					->orWhere('account_type', 'Sales');
			})
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
				->whereHas('transactions', function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				})
				->with(['transactions' => function ($query) use ($date1, $date2) {
					$query->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}])
				->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'dr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
					$query->where('dr_cr', 'cr')
						->whereDate('trans_date', '>=', $date1)
						->whereDate('trans_date', '<=', $date2);
				}], 'base_currency_amount')
				->get();

			$data['date1'] = Carbon::parse($date1);
			$data['date2'] = Carbon::parse($date2);
			$data['page_title'] = _lang('Income Statement');

			return view('backend.user.reports.income_statement', $data);
		}
	}

	public function purchase_by_vendor(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Purchase By Vendors');
			return view('backend.user.reports.purchase_by_vendor', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = $request->date1;
			$date2 = $request->date2;
			$vendor_id = isset($request->vendor_id) ? $request->vendor_id : '';

			$data['report_data'] = Purchase::with('vendor')
				->selectRaw('vendor_id, SUM(grand_total) as total_income, sum(paid) as total_paid')
				->when($vendor_id, function ($query, $vendor_id) {
					return $query->where('vendor_id', $vendor_id);
				})
				->whereRaw("date(purchase_date) >= '$date1' AND date(purchase_date) <= '$date2'")
				->groupBy('vendor_id')
				->get();

			$data['date1'] = $request->date1;
			$data['date2'] = $request->date2;
			$data['vendor_id'] = $request->vendor_id;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Purchase By Vendors');
			return view('backend.user.reports.purchase_by_vendor', $data);
		}
	}

	public function attendance_report(Request $request)
	{
		if (package()->payroll_module != 1) {
			if (!$request->ajax()) {
				return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
			} else {
				return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
			}
		}

		if ($request->isMethod('get')) {
			$page_title = _lang('Attendance Report');
			return view('backend.user.reports.attendance_report', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$month = $request->month;
			$year = $request->year;

			$data['calendar'] = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$attendance_list = Attendance::select('attendance.*')
				->whereMonth('date', $month)
				->whereYear('date', $year)
				->orderBy('date', 'asc')
				->orderBy('employee_id', 'asc')
				->get();

			$holidays = Holiday::whereMonth('date', $month)
				->whereYear('date', $year)
				->orderBy('date', 'ASC')
				->pluck('date')
				->toArray();

			$data['employees'] = Employee::active()
				->orderBy('employees.id', 'asc')
				->get();

			$weekends = json_decode(get_business_option('weekends', '[]', business_id()));
			$report_data = [];

			for ($day = 1; $day <= $data['calendar']; $day++) {
				$date = date('Y-m-d', strtotime("$year-$month-$day"));
				$status = ['A', 'P', 'L', 'W', 'H'];

				foreach ($attendance_list as $attendance) {
					if (in_array($date, $holidays)) {
						$report_data[$attendance->employee_id][$day] = $status[4]; // Holiday
					} else {
						if ($date == $attendance->getRawOriginal('date')) {
							$report_data[$attendance->employee_id][$day] = $status[$attendance->status];
						} else {
							if (in_array(date('l', strtotime($date)), $weekends)) {
								$report_data[$attendance->employee_id][$day] = $status[3];
							}
						}
					}
				}
			}

			$data['month'] = $request->month;
			$data['year'] = $request->year;
			$data['page_title'] = _lang('Attendance Report');
			$data['report_data'] = $report_data;
			$data['attendance_list'] = $attendance_list;
			return view('backend.user.reports.attendance_report', $data);
		}
	}

	public function payroll_report(Request $request)
	{
		if (package()->payroll_module != 1) {
			if (!$request->ajax()) {
				return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
			} else {
				return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
			}
		}

		if ($request->isMethod('get')) {
			$page_title = _lang('Payroll Report');
			return view('backend.user.reports.payroll_report', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$month = $request->month;
			$year = $request->year;

			$data['report_data'] = Payroll::with('staff')
				->select('payslips.*')
				->where('month', $month)
				->where('year', $year)
				->get();

			$data['month'] = $request->month;
			$data['year'] = $request->year;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payroll Report');
			return view('backend.user.reports.payroll_report', $data);
		}
	}

	public function payroll_summary(Request $request)
	{
		if (package()->payroll_module != 1) {
			if (!$request->ajax()) {
				return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
			} else {
				return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
			}
		}

		if ($request->isMethod('get')) {

			$data = array();
			$month = now()->month;
			$year = now()->year;

			$current_salary = Payroll::where('month', $month)
				->where('year', $year)
				->sum('current_salary');

			$total_allowance = Payroll::where('month', $month)
				->where('year', $year)
				->sum('total_allowance');

			$total_deduction = Payroll::where('month', $month)
				->where('year', $year)
				->sum('total_deduction');

			$net_salary = Payroll::where('month', $month)
				->where('year', $year)
				->sum('net_salary');

			$total_tax = Payroll::where('month', $month)
				->where('year', $year)
				->sum('tax_amount');

			$data['current_salary'] = $current_salary;
			$data['total_allowance'] = $total_allowance;
			$data['total_deduction'] = $total_deduction;
			$data['net_salary'] = $net_salary;
			$data['total_tax'] = $total_tax;

			$data['month'] = $month;
			$data['year'] = $year;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payroll Summary');

			return view('backend.user.reports.payroll_summary', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$month = $request->month;
			$year = $request->year;

			$current_salary = Payroll::where('month', $month)
				->where('year', $year)
				->sum('current_salary');

			$total_allowance = Payroll::where('month', $month)
				->where('year', $year)
				->sum('total_allowance');

			$total_deduction = Payroll::where('month', $month)
				->where('year', $year)
				->sum('total_deduction');

			$net_salary = Payroll::where('month', $month)
				->where('year', $year)
				->sum('net_salary');

			$total_tax = Payroll::where('month', $month)
				->where('year', $year)
				->sum('tax_amount');

			$data['current_salary'] = $current_salary;
			$data['total_allowance'] = $total_allowance;
			$data['total_deduction'] = $total_deduction;
			$data['net_salary'] = $net_salary;
			$data['total_tax'] = $total_tax;

			$data['month'] = $request->month;
			$data['year'] = $request->year;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payroll Summary');
			return view('backend.user.reports.payroll_summary', $data);
		}
	}

	public function payroll_cost(Request $request)
	{

		if (package()->payroll_module != 1) {
			if (!$request->ajax()) {
				return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
			} else {
				return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
			}
		}

		if ($request->isMethod('get')) {
			$data = array();
			$month = now()->month;
			$year = now()->year;

			$data['report_data'] = Payroll::with('staff')
				->select('payslips.*')
				->where('month', $month)
				->where('year', $year)
				->get();

			$data['total_netsalary'] = $data['report_data']->sum('net_salary');
			$data['total_basicsalary'] = $data['report_data']->sum('current_salary');
			$data['total_allowance'] = $data['report_data']->sum('total_allowance');
			$data['total_deduction'] = $data['report_data']->sum('total_deduction');
			$data['total_tax'] = $data['report_data']->sum('tax_amount');
			$data['total_advance'] = $data['report_data']->sum('advance');

			$data['month'] = now()->month;;
			$data['year'] = now()->year;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payroll Cost');
			return view('backend.user.reports.payroll_cost', $data);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$month = $request->month;
			$year = $request->year;

			$data['report_data'] = Payroll::with('staff')
				->select('payslips.*')
				->where('month', $month)
				->where('year', $year)
				->get();

			$data['total_netsalary'] = $data['report_data']->sum('net_salary');
			$data['total_basicsalary'] = $data['report_data']->sum('current_salary');
			$data['total_allowance'] = $data['report_data']->sum('total_allowance');
			$data['total_deduction'] = $data['report_data']->sum('total_deduction');
			$data['total_tax'] = $data['report_data']->sum('tax_amount');
			$data['total_advance'] = $data['report_data']->sum('advance');

			$data['month'] = $request->month;
			$data['year'] = $request->year;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Payroll Cost');
			return view('backend.user.reports.payroll_cost', $data);
		}
	}

	public function tax_report(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Tax Report');
			return view('backend.user.reports.tax_report', compact('page_title'));
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');
			$report_type = $request->report_type;
			$business_id = request()->activeBusiness->id;

			if ($report_type == 'paid_unpaid') {

				$data['sales_taxes'] = DB::select("SELECT taxes.id, taxes.name, taxes.rate, taxes.tax_number, SUM(invoice_items.sub_total) as sales_amount,
                SUM(invoice_item_taxes.amount) as sales_tax FROM invoice_items LEFT JOIN invoice_item_taxes ON invoice_items.id=invoice_item_taxes.invoice_item_id
                AND invoice_items.business_id = $business_id JOIN invoices ON invoices.id=invoice_items.invoice_id AND invoices.status != 0 AND invoices.is_recurring = 0
                AND invoices.invoice_date >= '$date1' AND invoices.invoice_date <= '$date2' RIGHT JOIN taxes ON taxes.id=invoice_item_taxes.tax_id WHERE taxes.business_id = $business_id GROUP BY taxes.id");

				$data['purchase_taxes'] = DB::select("SELECT taxes.id, taxes.name, taxes.rate, taxes.tax_number, SUM(purchase_items.sub_total) as purchase_amount,
                SUM(purchase_item_taxes.amount) as purchase_tax FROM purchase_items LEFT JOIN purchase_item_taxes ON purchase_items.id=purchase_item_taxes.purchase_item_id
                AND purchase_items.business_id = $business_id JOIN purchases ON purchases.id=purchase_items.purchase_id AND purchases.purchase_date >= '$date1' AND purchases.purchase_date <= '$date2'
                RIGHT JOIN taxes ON taxes.id=purchase_item_taxes.tax_id WHERE taxes.business_id = $business_id GROUP BY taxes.id");
			}

			if ($report_type == 'paid') {
				$data['sales_taxes'] = DB::select("SELECT taxes.id, taxes.name, taxes.rate, taxes.tax_number, SUM((((100 / invoices.grand_total) * invoices.paid) / 100) * invoice_items.sub_total) as sales_amount,
                SUM((((100 / invoices.grand_total) * invoices.paid) / 100) * invoice_item_taxes.amount) as sales_tax FROM invoice_items LEFT JOIN invoice_item_taxes ON invoice_items.id=invoice_item_taxes.invoice_item_id
                AND invoice_items.business_id = $business_id JOIN invoices ON invoices.id=invoice_items.invoice_id AND invoices.status != 0 AND invoices.is_recurring = 0
                AND invoices.paid > 0 AND invoices.invoice_date >= '$date1' AND invoices.invoice_date <= '$date2' RIGHT JOIN taxes ON taxes.id=invoice_item_taxes.tax_id WHERE taxes.business_id = $business_id GROUP BY taxes.id");

				$data['purchase_taxes'] = DB::select("SELECT taxes.id, taxes.name, taxes.rate, taxes.tax_number, SUM((((100 / purchases.grand_total) * purchases.paid) / 100) * purchase_items.sub_total) as purchase_amount,
                SUM((((100 / purchases.grand_total) * purchases.paid) / 100) * purchase_item_taxes.amount) as purchase_tax FROM purchase_items LEFT JOIN purchase_item_taxes ON purchase_items.id=purchase_item_taxes.purchase_item_id
                AND purchase_items.business_id = $business_id JOIN purchases ON purchases.id=purchase_items.purchase_id AND purchases.paid > 0 AND purchases.purchase_date >= '$date1' AND purchases.purchase_date <= '$date2'
                RIGHT JOIN taxes ON taxes.id=purchase_item_taxes.tax_id WHERE taxes.business_id = $business_id GROUP BY taxes.id");
			}

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);
			$data['report_type'] = $request->report_type;
			$data['currency'] = request()->activeBusiness->currency;
			$data['page_title'] = _lang('Tax Report');
			$data['report_data'] = true;
			return view('backend.user.reports.tax_report', $data);
		}
	}

	public function inventory_details(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Inventory Details Report');
			$data = array();

			$sub_category = 'all';
			$main_category = 'all';

			$date1 = Carbon::now()->subDays(30)->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['sub_category' => $sub_category]);
			session(['main_category' => $main_category]);

			$products = Product::select(
				'products.*',
				'sub_categories.id as category_id',
				'sub_categories.name as category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name',
				'main_categories.id as main_category_id',
				'main_categories.name as main_category_name'
			)
				->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
				->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
				->leftJoin('main_categories', 'main_categories.id', '=', 'sub_categories.main_category_id')
				->addSelect([
					'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('invoice', function ($query) use ($date1, $date2) {
							$query->whereDate('invoice_date', '>=', $date1)
								->whereDate('invoice_date', '<=', $date2);
						}),
					'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('receipt', function ($query) use ($date1, $date2) {
							$query->whereDate('receipt_date', '>=', $date1)
								->whereDate('receipt_date', '<=', $date2);
						}),
					'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('purchase', function ($query) use ($date1, $date2) {
							$query->whereDate('purchase_date', '>=', $date1)
								->whereDate('purchase_date', '<=', $date2);
						}),
					'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'adds')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
					'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'deducts')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
				])
				->get()
				->map(function ($product) {
					// Calculate the total sold by summing invoices and receipts
					$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

					// Calculate total stock cost
					$product->total_stock_cost = $product->stock * $product->purchase_cost;

					return $product;
				});

			// Step 2: Group products by category
			$groupedByCategory = $products->groupBy(function ($product) {
				return $product->category_id . '|' . $product->category_name;
			})->map(function ($categoryGroup, $categoryKey) {
				// Extract category ID and name
				[$categoryId, $categoryName] = explode('|', $categoryKey);

				// Step 3: Within each category, group by brand and calculate metrics
				$brands = $categoryGroup->groupBy(function ($product) {
					return $product->brand_id . '|' . $product->brand_name;
				})->map(function ($brandGroup, $brandKey) {
					// Extract brand ID and name
					[$brandId, $brandName] = explode('|', $brandKey);

					// Calculate total sold for the brand
					$brandTotalSold = $brandGroup->sum('total_sold');

					// Calculate total stock cost for the brand
					$brandTotalStockCost = $brandGroup->sum('total_stock_cost');

					return [
						'brand_id' => $brandId,
						'brand_name' => $brandName,
						'total_sold' => $brandTotalSold,
						'total_stock_cost' => $brandTotalStockCost,
						'products' => $brandGroup->sortByDesc('total_sold')->values(),
					];
				});

				// Step 4: Sort brands within the category by total_sold descending
				$sortedBrands = $brands->sortByDesc('total_sold')->values();

				// Calculate total sold and stock cost for the category
				$categoryTotalSold = $categoryGroup->sum('total_sold');
				$categoryTotalStockCost = $categoryGroup->sum('total_stock_cost');

				return [
					'category_id' => $categoryId,
					'category_name' => $categoryName,
					'total_sold' => $categoryTotalSold,
					'total_stock_cost' => $categoryTotalStockCost,
					'brands' => $sortedBrands,
				];
			});

			// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
			$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

			// Assign to your data array
			$data['products'] = $sortedCategories;

			$data['page_title'] = $page_title;

			return view('backend.user.reports.inventory_details', $data);
		} else {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$sub_category = $request->sub_category;
			$main_category = $request->main_category;

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['sub_category' => $sub_category]);
			session(['main_category' => $main_category]);

			$query = Product::select(
				'products.*',
				'sub_categories.id as category_id',
				'sub_categories.name as category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name',
				'main_categories.id as main_category_id',
				'main_categories.name as main_category_name'
			)
				->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
				->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
				->leftJoin('main_categories', 'main_categories.id', '=', 'sub_categories.main_category_id');

			if ($main_category !== 'all') {
				$query->where('main_categories.id', $main_category);
			}

			if ($sub_category !== 'all') {
				$query->where('sub_categories.id', $sub_category);
			}

			$products = $query->addSelect([
				'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
					->whereColumn('product_id', 'products.id')
					->whereIn('product_id', Product::pluck('id')) // Ensures all products are included
					->whereHas('invoice', function ($query) use ($date1, $date2) {
						$query->whereBetween('invoice_date', [$date1, $date2]);
					}, 'or'), // OR condition ensures products without invoices are not excluded

				'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
					->whereColumn('product_id', 'products.id')
					->whereIn('product_id', Product::pluck('id'))
					->whereHas('receipt', function ($query) use ($date1, $date2) {
						$query->whereBetween('receipt_date', [$date1, $date2]);
					}, 'or'),

				'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
					->whereColumn('product_id', 'products.id')
					->whereIn('product_id', Product::pluck('id'))
					->whereHas('purchase', function ($query) use ($date1, $date2) {
						$query->whereBetween('purchase_date', [$date1, $date2]);
					}, 'or'),

				'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
					->whereColumn('product_id', 'products.id')
					->where('adjustment_type', 'adds')
					->whereDate('adjustment_date', '>=', $date1)
					->whereDate('adjustment_date', '<=', $date2),

				'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
					->whereColumn('product_id', 'products.id')
					->where('adjustment_type', 'deducts')
					->whereDate('adjustment_date', '>=', $date1)
					->whereDate('adjustment_date', '<=', $date2),
			])->get()
				->map(function ($product) {
					// Calculate the total sold by summing invoices and receipts
					$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

					// Calculate total stock cost
					$product->total_stock_cost = $product->stock * $product->purchase_cost;

					return $product;
				});

			// Step 2: Group products by category
			$groupedByCategory = $products->groupBy(function ($product) {
				return $product->category_id . '|' . $product->category_name;
			})->map(function ($categoryGroup, $categoryKey) {
				// Extract category ID and name
				[$categoryId, $categoryName] = explode('|', $categoryKey);

				// Step 3: Within each category, group by brand and calculate metrics
				$brands = $categoryGroup->groupBy(function ($product) {
					return $product->brand_id . '|' . $product->brand_name;
				})->map(function ($brandGroup, $brandKey) {
					// Extract brand ID and name
					[$brandId, $brandName] = explode('|', $brandKey);

					// Calculate total sold for the brand
					$brandTotalSold = $brandGroup->sum('total_sold');

					// Calculate total stock cost for the brand
					$brandTotalStockCost = $brandGroup->sum('total_stock_cost');

					return [
						'brand_id' => $brandId,
						'brand_name' => $brandName,
						'total_sold' => $brandTotalSold,
						'total_stock_cost' => $brandTotalStockCost,
						'products' => $brandGroup->sortByDesc('total_sold')->values(),
					];
				});

				// Step 4: Sort brands within the category by total_sold descending
				$sortedBrands = $brands->sortByDesc('total_sold')->values();

				// Calculate total sold and stock cost for the category
				$categoryTotalSold = $categoryGroup->sum('total_sold');
				$categoryTotalStockCost = $categoryGroup->sum('total_stock_cost');

				return [
					'category_id' => $categoryId,
					'category_name' => $categoryName,
					'total_sold' => $categoryTotalSold,
					'total_stock_cost' => $categoryTotalStockCost,
					'brands' => $sortedBrands,
				];
			});

			// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
			$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

			// Assign to your data array
			$data['products'] = $sortedCategories;

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);

			$data['page_title'] = _lang('Inventory Details Report');
			$data['sub_category'] = $sub_category;
			$data['main_category'] = $main_category;

			return view('backend.user.reports.inventory_details', $data);
		}
	}

	public function inventory_summary(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Inventory Summary Report');
			$data = array();

			$date1 = Carbon::now()->subDays(30)->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$products = Product::select(
				'products.*',
				'sub_categories.id as sub_category_id',
				'sub_categories.name as sub_category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name'
			)
				->leftjoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
				->leftjoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
				->addSelect([
					'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('invoice', function ($query) use ($date1, $date2) {
							$query->whereDate('invoice_date', '>=', $date1)
								->whereDate('invoice_date', '<=', $date2);
						}),
					'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('receipt', function ($query) use ($date1, $date2) {
							$query->whereDate('receipt_date', '>=', $date1)
								->whereDate('receipt_date', '<=', $date2);
						}),
					'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('purchase', function ($query) use ($date1, $date2) {
							$query->whereDate('purchase_date', '>=', $date1)
								->whereDate('purchase_date', '<=', $date2);
						}),
					'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'adds')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
					'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'deducts')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
				])
				->get()
				->map(function ($product) {
					// Calculate the total sold by summing invoices and receipts
					$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

					// Calculate total stock cost
					$product->total_stock_cost = $product->stock * $product->purchase_cost;

					return $product;
				});

			// Step 2: Group products by category
			$groupedByCategory = $products->groupBy(function ($product) {
				return $product->sub_category_id . '|' . $product->sub_category_name;
			})->map(function ($categoryGroup, $categoryKey) {
				// Extract category ID and name
				[$categoryId, $categoryName] = explode('|', $categoryKey);

				// Step 3: Within each category, group by brand and calculate metrics
				$brands = $categoryGroup->groupBy(function ($product) {
					return $product->brand_id . '|' . $product->brand_name;
				})->map(function ($brandGroup, $brandKey) {
					// Extract brand ID and name
					[$brandId, $brandName] = explode('|', $brandKey);

					// Calculate total sold for the brand
					$brandTotalSold = $brandGroup->sum('total_sold');

					// Calculate total stock cost for the brand
					$brandTotalStockCost = $brandGroup->sum('total_stock_cost');

					return [
						'brand_id' => $brandId,
						'brand_name' => $brandName,
						'total_sold' => $brandTotalSold,
						'total_stock_cost' => $brandTotalStockCost,
						'products' => $brandGroup->sortByDesc('total_sold')->values(),
					];
				});

				// Step 4: Sort brands within the category by total_sold descending
				$sortedBrands = $brands->sortByDesc('total_sold')->values();

				// Calculate total sold and stock cost for the category
				$categoryTotalSold = $categoryGroup->sum('total_sold');
				$categoryTotalStockCost = $categoryGroup->sum('total_stock_cost');

				return [
					'category_id' => $categoryId,
					'category_name' => $categoryName,
					'total_sold' => $categoryTotalSold,
					'total_stock_cost' => $categoryTotalStockCost,
					'brands' => $sortedBrands,
				];
			});

			// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
			$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

			// Assign to your data array
			$data['products'] = $sortedCategories;

			$data['page_title'] = $page_title;

			return view('backend.user.reports.inventory_summary', $data);
		} else {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$category = $request->category;

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['category' => $category]);

			$products = Product::select(
				'products.*',
				'sub_categories.id as sub_category_id',
				'sub_categories.name as sub_category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name'
			)
				->leftjoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
				->leftjoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
				->addSelect([
					'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('invoice', function ($query) use ($date1, $date2) {
							$query->whereDate('invoice_date', '>=', $date1)
								->whereDate('invoice_date', '<=', $date2);
						}),
					'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('receipt', function ($query) use ($date1, $date2) {
							$query->whereDate('receipt_date', '>=', $date1)
								->whereDate('receipt_date', '<=', $date2);
						}),
					'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->whereHas('purchase', function ($query) use ($date1, $date2) {
							$query->whereDate('purchase_date', '>=', $date1)
								->whereDate('purchase_date', '<=', $date2);
						}),
					'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'adds')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
					'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
						->whereColumn('product_id', 'products.id')
						->where('adjustment_type', 'deducts')
						->whereDate('adjustment_date', '>=', $date1)
						->whereDate('adjustment_date', '<=', $date2),
				])
				->get()
				->map(function ($product) {
					// Calculate the total sold by summing invoices and receipts
					$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

					// Calculate total stock cost
					$product->total_stock_cost = $product->stock * $product->purchase_cost;

					return $product;
				});

			// Step 2: Group products by category
			$groupedByCategory = $products->groupBy(function ($product) {
				return $product->sub_category_id . '|' . $product->sub_category_name;
			})->map(function ($categoryGroup, $categoryKey) {
				// Extract category ID and name
				[$categoryId, $categoryName] = explode('|', $categoryKey);

				// Step 3: Within each category, group by brand and calculate metrics
				$brands = $categoryGroup->groupBy(function ($product) {
					return $product->brand_id . '|' . $product->brand_name;
				})->map(function ($brandGroup, $brandKey) {
					// Extract brand ID and name
					[$brandId, $brandName] = explode('|', $brandKey);

					// Calculate total sold for the brand
					$brandTotalSold = $brandGroup->sum('total_sold');

					// Calculate total stock cost for the brand
					$brandTotalStockCost = $brandGroup->sum('total_stock_cost');

					return [
						'brand_id' => $brandId,
						'brand_name' => $brandName,
						'total_sold' => $brandTotalSold,
						'total_stock_cost' => $brandTotalStockCost,
						'products' => $brandGroup->sortByDesc('total_sold')->values(),
					];
				});

				// Step 4: Sort brands within the category by total_sold descending
				$sortedBrands = $brands->sortByDesc('total_sold')->values();

				// Calculate total sold and stock cost for the category
				$categoryTotalSold = $categoryGroup->sum('total_sold');
				$categoryTotalStockCost = $categoryGroup->sum('total_stock_cost');

				return [
					'category_id' => $categoryId,
					'category_name' => $categoryName,
					'total_sold' => $categoryTotalSold,
					'total_stock_cost' => $categoryTotalStockCost,
					'brands' => $sortedBrands,
				];
			});

			// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
			$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

			// Assign to your data array
			$data['products'] = $sortedCategories;

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);

			$data['page_title'] = _lang('Inventory Details Report');
			$data['category'] = $category;

			return view('backend.user.reports.inventory_summary', $data);
		}
	}

	public function sales_by_product(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Inventory Stock');
			$data = array();

			$category = 'all';

			$date1 = Carbon::now()->subDays(30)->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['category' => $category]);

			// Step 1: Retrieve products with necessary joins and calculated fields
			$products = Product::select(
				'products.*',
				'categories.id as category_id',
				'categories.name as category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name'
			)
				->join('categories', 'categories.id', '=', 'products.category_id')
				->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
				->addSelect([
					'total_sold_invoices' => InvoiceItem::selectRaw('SUM(quantity)')
						->whereColumn('product_id', 'products.id')
						->whereHas('invoice', function ($query) use ($date1, $date2) {
							$query->whereDate('invoice_date', '>=', $date1)
								->whereDate('invoice_date', '<=', $date2);
						}),

					'total_sold_receipts' => ReceiptItem::selectRaw('SUM(quantity)')
						->whereColumn('product_id', 'products.id')
						->whereHas('receipt', function ($query) use ($date1, $date2) {
							$query->whereDate('receipt_date', '>=', $date1)
								->whereDate('receipt_date', '<=', $date2);
						}),

					'sub_total_invoices' => InvoiceItem::selectRaw('SUM(sub_total)')
						->whereColumn('product_id', 'products.id')
						->whereHas('invoice', function ($query) use ($date1, $date2) {
							$query->whereDate('invoice_date', '>=', $date1)
								->whereDate('invoice_date', '<=', $date2);
						}),

					'sub_total_receipts' => ReceiptItem::selectRaw('SUM(sub_total)')
						->whereColumn('product_id', 'products.id')
						->whereHas('receipt', function ($query) use ($date1, $date2) {
							$query->whereDate('receipt_date', '>=', $date1)
								->whereDate('receipt_date', '<=', $date2);
						}),
				])
				->get()
				->map(function ($product) {
					// Calculate the total sold by summing invoices and receipts
					$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

					// Calculate total sales
					$product->total_sales = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);

					// Calculate average price
					$total_sub_total = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);
					$product->average_price = $product->total_sold > 0 ? $total_sub_total / $product->total_sold : 0;

					// Calculate gross profit
					$product->gross_profit = ($product->average_price - $product->purchase_cost) * $product->total_sold;

					// Calculate profit margin
					// Corrected formula: (Total Sales - Total Cost) / Total Sales * 100
					$total_cost = $product->purchase_cost * $product->total_sold;
					$product->profit_margin = $product->total_sales > 0
						? (($product->total_sales - $total_cost) / $product->total_sales) * 100
						: 0;

					return $product;
				});

			// Step 2: Group products by category
			$groupedByCategory = $products->groupBy(function ($product) {
				return $product->category_id . '|' . $product->category_name;
			})->map(function ($categoryGroup, $categoryKey) {
				// Extract category ID and name
				[$categoryId, $categoryName] = explode('|', $categoryKey);

				// Step 3: Within each category, group by brand and calculate metrics
				$brands = $categoryGroup->groupBy(function ($product) {
					return $product->brand_id . '|' . $product->brand_name;
				})->map(function ($brandGroup, $brandKey) {
					// Extract brand ID and name
					[$brandId, $brandName] = explode('|', $brandKey);

					// Calculate total sold for the brand
					$brandTotalSold = $brandGroup->sum('total_sold');

					// Calculate total sales for the brand
					$brandTotalSales = $brandGroup->sum('total_sales');

					// Calculate gross profit for the brand
					$brandGrossProfit = $brandGroup->sum('gross_profit');

					// Calculate average price for the brand
					$brandAveragePrice = $brandTotalSold > 0 ? $brandTotalSales / $brandTotalSold : 0;

					// Calculate total cost for the brand correctly
					$brandTotalCost = $brandGroup->sum(function ($product) {
						return $product->purchase_cost * $product->total_sold;
					});

					// Calculate profit margin for the brand
					$brandProfitMargin = $brandTotalSales > 0
						? (($brandTotalSales - $brandTotalCost) / $brandTotalSales) * 100
						: 0;

					return [
						'brand_id' => $brandId,
						'brand_name' => $brandName,
						'total_sold' => $brandTotalSold,
						'total_sales' => $brandTotalSales,
						'gross_profit' => $brandGrossProfit,
						'average_price' => $brandAveragePrice,
						'profit_margin' => $brandProfitMargin,
						'products' => $brandGroup->sortByDesc('total_sold')->values(),
					];
				});

				// Step 4: Sort brands within the category by total_sold descending
				$sortedBrands = $brands->sortByDesc('total_sold')->values();

				// Calculate total sold and sales for the category
				$categoryTotalSold = $categoryGroup->sum('total_sold');
				$categoryTotalSales = $categoryGroup->sum('total_sales');
				$categoryGrossProfit = $categoryGroup->sum('gross_profit');
				$categoryAveragePrice = $categoryTotalSold > 0 ? $categoryTotalSales / $categoryTotalSold : 0;

				// Calculate total cost for the category correctly
				$categoryTotalCost = $categoryGroup->sum(function ($product) {
					return $product->purchase_cost * $product->total_sold;
				});

				// Calculate profit margin for the category
				$categoryProfitMargin = $categoryTotalSales > 0
					? (($categoryTotalSales - $categoryTotalCost) / $categoryTotalSales) * 100
					: 0;

				return [
					'category_id' => $categoryId,
					'category_name' => $categoryName,
					'total_sold' => $categoryTotalSold,
					'total_sales' => $categoryTotalSales,
					'gross_profit' => $categoryGrossProfit,
					'average_price' => $categoryAveragePrice,
					'profit_margin' => $categoryProfitMargin,
					'brands' => $sortedBrands,
				];
			});

			// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
			$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

			// Assign to your data array
			$data['products'] = $sortedCategories;

			$data['page_title'] = $page_title;

			return view('backend.user.reports.sales_by_product', $data);
		} else {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$data = array();
			$category = $request->category;

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['category' => $category]);

			if ($category == 'all') {
				// Step 1: Retrieve products with necessary joins and calculated fields
				$products = Product::select(
					'products.*',
					'categories.id as category_id',
					'categories.name as category_name',
					'product_brands.id as brand_id',
					'product_brands.name as brand_name'
				)
					->join('categories', 'categories.id', '=', 'products.category_id')
					->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
					->addSelect([
						'total_sold_invoices' => InvoiceItem::selectRaw('SUM(quantity)')
							->whereColumn('product_id', 'products.id')
							->whereHas('invoice', function ($query) use ($date1, $date2) {
								$query->whereDate('invoice_date', '>=', $date1)
									->whereDate('invoice_date', '<=', $date2);
							}),

						'total_sold_receipts' => ReceiptItem::selectRaw('SUM(quantity)')
							->whereColumn('product_id', 'products.id')
							->whereHas('receipt', function ($query) use ($date1, $date2) {
								$query->whereDate('receipt_date', '>=', $date1)
									->whereDate('receipt_date', '<=', $date2);
							}),

						'sub_total_invoices' => InvoiceItem::selectRaw('SUM(sub_total)')
							->whereColumn('product_id', 'products.id')
							->whereHas('invoice', function ($query) use ($date1, $date2) {
								$query->whereDate('invoice_date', '>=', $date1)
									->whereDate('invoice_date', '<=', $date2);
							}),

						'sub_total_receipts' => ReceiptItem::selectRaw('SUM(sub_total)')
							->whereColumn('product_id', 'products.id')
							->whereHas('receipt', function ($query) use ($date1, $date2) {
								$query->whereDate('receipt_date', '>=', $date1)
									->whereDate('receipt_date', '<=', $date2);
							}),
					])
					->get()
					->map(function ($product) {
						// Calculate the total sold by summing invoices and receipts
						$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

						// Calculate total sales
						$product->total_sales = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);

						// Calculate average price
						$total_sub_total = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);
						$product->average_price = $product->total_sold > 0 ? $total_sub_total / $product->total_sold : 0;

						// Calculate gross profit
						$product->gross_profit = ($product->average_price - $product->purchase_cost) * $product->total_sold;

						// Calculate profit margin
						// Corrected formula: (Total Sales - Total Cost) / Total Sales * 100
						$total_cost = $product->purchase_cost * $product->total_sold;
						$product->profit_margin = $product->total_sales > 0
							? (($product->total_sales - $total_cost) / $product->total_sales) * 100
							: 0;

						return $product;
					});

				// Step 2: Group products by category
				$groupedByCategory = $products->groupBy(function ($product) {
					return $product->category_id . '|' . $product->category_name;
				})->map(function ($categoryGroup, $categoryKey) {
					// Extract category ID and name
					[$categoryId, $categoryName] = explode('|', $categoryKey);

					// Step 3: Within each category, group by brand and calculate metrics
					$brands = $categoryGroup->groupBy(function ($product) {
						return $product->brand_id . '|' . $product->brand_name;
					})->map(function ($brandGroup, $brandKey) {
						// Extract brand ID and name
						[$brandId, $brandName] = explode('|', $brandKey);

						// Calculate total sold for the brand
						$brandTotalSold = $brandGroup->sum('total_sold');

						// Calculate total sales for the brand
						$brandTotalSales = $brandGroup->sum('total_sales');

						// Calculate gross profit for the brand
						$brandGrossProfit = $brandGroup->sum('gross_profit');

						// Calculate average price for the brand
						$brandAveragePrice = $brandTotalSold > 0 ? $brandTotalSales / $brandTotalSold : 0;

						// Calculate total cost for the brand correctly
						$brandTotalCost = $brandGroup->sum(function ($product) {
							return $product->purchase_cost * $product->total_sold;
						});

						// Calculate profit margin for the brand
						$brandProfitMargin = $brandTotalSales > 0
							? (($brandTotalSales - $brandTotalCost) / $brandTotalSales) * 100
							: 0;

						return [
							'brand_id' => $brandId,
							'brand_name' => $brandName,
							'total_sold' => $brandTotalSold,
							'total_sales' => $brandTotalSales,
							'gross_profit' => $brandGrossProfit,
							'average_price' => $brandAveragePrice,
							'profit_margin' => $brandProfitMargin,
							'products' => $brandGroup->sortByDesc('total_sold')->values(),
						];
					});

					// Step 4: Sort brands within the category by total_sold descending
					$sortedBrands = $brands->sortByDesc('total_sold')->values();

					// Calculate total sold and sales for the category
					$categoryTotalSold = $categoryGroup->sum('total_sold');
					$categoryTotalSales = $categoryGroup->sum('total_sales');
					$categoryGrossProfit = $categoryGroup->sum('gross_profit');
					$categoryAveragePrice = $categoryTotalSold > 0 ? $categoryTotalSales / $categoryTotalSold : 0;

					// Calculate total cost for the category correctly
					$categoryTotalCost = $categoryGroup->sum(function ($product) {
						return $product->purchase_cost * $product->total_sold;
					});

					// Calculate profit margin for the category
					$categoryProfitMargin = $categoryTotalSales > 0
						? (($categoryTotalSales - $categoryTotalCost) / $categoryTotalSales) * 100
						: 0;

					return [
						'category_id' => $categoryId,
						'category_name' => $categoryName,
						'total_sold' => $categoryTotalSold,
						'total_sales' => $categoryTotalSales,
						'gross_profit' => $categoryGrossProfit,
						'average_price' => $categoryAveragePrice,
						'profit_margin' => $categoryProfitMargin,
						'brands' => $sortedBrands,
					];
				});

				// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
				$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

				// Assign to your data array
				$data['products'] = $sortedCategories;
			} else {
				// Step 1: Retrieve products with necessary joins and calculated fields
				$products = Product::select(
					'products.*',
					'categories.id as category_id',
					'categories.name as category_name',
					'product_brands.id as brand_id',
					'product_brands.name as brand_name'
				)
					->join('categories', 'categories.id', '=', 'products.category_id')
					->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
					->addSelect([
						'total_sold_invoices' => InvoiceItem::selectRaw('SUM(quantity)')
							->whereColumn('product_id', 'products.id')
							->whereHas('invoice', function ($query) use ($date1, $date2) {
								$query->whereDate('invoice_date', '>=', $date1)
									->whereDate('invoice_date', '<=', $date2);
							}),

						'total_sold_receipts' => ReceiptItem::selectRaw('SUM(quantity)')
							->whereColumn('product_id', 'products.id')
							->whereHas('receipt', function ($query) use ($date1, $date2) {
								$query->whereDate('receipt_date', '>=', $date1)
									->whereDate('receipt_date', '<=', $date2);
							}),

						'sub_total_invoices' => InvoiceItem::selectRaw('SUM(sub_total)')
							->whereColumn('product_id', 'products.id')
							->whereHas('invoice', function ($query) use ($date1, $date2) {
								$query->whereDate('invoice_date', '>=', $date1)
									->whereDate('invoice_date', '<=', $date2);
							}),

						'sub_total_receipts' => ReceiptItem::selectRaw('SUM(sub_total)')
							->whereColumn('product_id', 'products.id')
							->whereHas('receipt', function ($query) use ($date1, $date2) {
								$query->whereDate('receipt_date', '>=', $date1)
									->whereDate('receipt_date', '<=', $date2);
							}),
					])
					->where('categories.name', $category)
					->get()
					->map(function ($product) {
						// Calculate the total sold by summing invoices and receipts
						$product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

						// Calculate total sales
						$product->total_sales = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);

						// Calculate average price
						$total_sub_total = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);
						$product->average_price = $product->total_sold > 0 ? $total_sub_total / $product->total_sold : 0;

						// Calculate gross profit
						$product->gross_profit = ($product->average_price - $product->purchase_cost) * $product->total_sold;

						// Calculate profit margin
						// Corrected formula: (Total Sales - Total Cost) / Total Sales * 100
						$total_cost = $product->purchase_cost * $product->total_sold;
						$product->profit_margin = $product->total_sales > 0
							? (($product->total_sales - $total_cost) / $product->total_sales) * 100
							: 0;

						return $product;
					});

				// Step 2: Group products by category
				$groupedByCategory = $products->groupBy(function ($product) {
					return $product->category_id . '|' . $product->category_name;
				})->map(function ($categoryGroup, $categoryKey) {
					// Extract category ID and name
					[$categoryId, $categoryName] = explode('|', $categoryKey);

					// Step 3: Within each category, group by brand and calculate metrics
					$brands = $categoryGroup->groupBy(function ($product) {
						return $product->brand_id . '|' . $product->brand_name;
					})->map(function ($brandGroup, $brandKey) {
						// Extract brand ID and name
						[$brandId, $brandName] = explode('|', $brandKey);

						// Calculate total sold for the brand
						$brandTotalSold = $brandGroup->sum('total_sold');

						// Calculate total sales for the brand
						$brandTotalSales = $brandGroup->sum('total_sales');

						// Calculate gross profit for the brand
						$brandGrossProfit = $brandGroup->sum('gross_profit');

						// Calculate average price for the brand
						$brandAveragePrice = $brandTotalSold > 0 ? $brandTotalSales / $brandTotalSold : 0;

						// Calculate total cost for the brand correctly
						$brandTotalCost = $brandGroup->sum(function ($product) {
							return $product->purchase_cost * $product->total_sold;
						});

						// Calculate profit margin for the brand
						$brandProfitMargin = $brandTotalSales > 0
							? (($brandTotalSales - $brandTotalCost) / $brandTotalSales) * 100
							: 0;

						return [
							'brand_id' => $brandId,
							'brand_name' => $brandName,
							'total_sold' => $brandTotalSold,
							'total_sales' => $brandTotalSales,
							'gross_profit' => $brandGrossProfit,
							'average_price' => $brandAveragePrice,
							'profit_margin' => $brandProfitMargin,
							'products' => $brandGroup->sortByDesc('total_sold')->values(),
						];
					});

					// Step 4: Sort brands within the category by total_sold descending
					$sortedBrands = $brands->sortByDesc('total_sold')->values();

					// Calculate total sold and sales for the category
					$categoryTotalSold = $categoryGroup->sum('total_sold');
					$categoryTotalSales = $categoryGroup->sum('total_sales');
					$categoryGrossProfit = $categoryGroup->sum('gross_profit');
					$categoryAveragePrice = $categoryTotalSold > 0 ? $categoryTotalSales / $categoryTotalSold : 0;

					// Calculate total cost for the category correctly
					$categoryTotalCost = $categoryGroup->sum(function ($product) {
						return $product->purchase_cost * $product->total_sold;
					});

					// Calculate profit margin for the category
					$categoryProfitMargin = $categoryTotalSales > 0
						? (($categoryTotalSales - $categoryTotalCost) / $categoryTotalSales) * 100
						: 0;

					return [
						'category_id' => $categoryId,
						'category_name' => $categoryName,
						'total_sold' => $categoryTotalSold,
						'total_sales' => $categoryTotalSales,
						'gross_profit' => $categoryGrossProfit,
						'average_price' => $categoryAveragePrice,
						'profit_margin' => $categoryProfitMargin,
						'brands' => $sortedBrands,
					];
				});

				// Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
				$sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

				// Assign to your data array
				$data['products'] = $sortedCategories;
			}

			$data['date1'] = Carbon::parse($request->date1);
			$data['date2'] = Carbon::parse($request->date2);

			$data['page_title'] = _lang('Inventory Stock');
			$data['category'] = $category;

			return view('backend.user.reports.sales_by_product', $data);
		}
	}
}
