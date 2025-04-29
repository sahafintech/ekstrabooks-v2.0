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
use App\Models\AccountType;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\InventoryAdjustment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MainCategory;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SubCategory;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
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
		// Get search term
		$search = $request->search ?? '';

		// Set default pagination
		$per_page = $request->per_page ?? 10;

		if ($request->isMethod('get')) {
			$date1 = Carbon::now()->startOfMonth()->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			// Return Inertia view with initial date values
			return $this->generate_customer_income_report($date1, $date2, $customer_id, $search, $per_page);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');
			$customer_id = isset($request->customer_id) ? $request->customer_id : '';

			if ($request->customer_id == 'all') {
				$customer_id = '';
			}

			// Return Inertia view with filter date values
			return $this->generate_customer_income_report($date1, $date2, $customer_id, $search, $per_page);
		}
	}

	private function generate_customer_income_report($date1, $date2, $customer_id, $search, $per_page)
	{
		// Get customers list for dropdown
		$customers = Customer::select('id', 'name', 'mobile', 'email')
			->orderBy('name')
			->get();
		// Get credit invoices data
		$credit_invoices_query = Invoice::with('customer')
			->selectRaw('customer_id, SUM(grand_total) as total_income, sum(paid) as total_paid')
			->when($customer_id, function ($query, $customer_id) {
				return $query->where('customer_id', $customer_id);
			})
			->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
			->where('is_recurring', 0)
			->where('status', '!=', 0)
			->groupBy('customer_id');

		// Get cash invoices data
		$cash_invoices_query = Receipt::with('customer')
			->selectRaw('customer_id, SUM(grand_total) as total_income, SUM(grand_total) as total_paid')
			->when($customer_id, function ($query, $customer_id) {
				return $query->where('customer_id', $customer_id);
			})
			->whereRaw("date(receipts.receipt_date) >= '$date1' AND date(receipts.receipt_date) <= '$date2'")
			->groupBy('customer_id');

		// Get the data from both queries
		$credit_invoices = $credit_invoices_query->get();
		$cash_invoices = $cash_invoices_query->get();

		// Process data for the report
		$report_data = [];

		// Process credit invoices
		foreach ($credit_invoices as $invoice) {
			$report_data[$invoice->customer_id] = [
				'id' => $invoice->customer_id,
				'customer_id' => $invoice->customer_id,
				'customer_name' => $invoice->customer->name,
				'total_income' => $invoice->total_income,
				'total_paid' => $invoice->total_paid,
				'total_due' => $invoice->total_income - $invoice->total_paid,
			];
		}

		// Process cash invoices
		foreach ($cash_invoices as $invoice) {
			if (isset($report_data[$invoice->customer_id])) {
				$report_data[$invoice->customer_id]['total_income'] += $invoice->total_income;
				$report_data[$invoice->customer_id]['total_paid'] += $invoice->total_paid;
				$report_data[$invoice->customer_id]['total_due'] = $report_data[$invoice->customer_id]['total_income'] - $report_data[$invoice->customer_id]['total_paid'];
			} else {
				$report_data[$invoice->customer_id] = [
					'id' => $invoice->customer_id,
					'customer_id' => $invoice->customer_id,
					'customer_name' => $invoice->customer->name,
					'total_income' => $invoice->total_income,
					'total_paid' => $invoice->total_paid,
					'total_due' => $invoice->total_income - $invoice->total_paid,
				];
			}
		}

		// Calculate totals
		$grand_total_income = 0;
		$grand_total_paid = 0;
		$grand_total_due = 0;

		// Convert to array and apply search filter
		$data_array = array_values($report_data);

		// Apply search filter if provided
		if (!empty($search)) {
			$data_array = array_filter($data_array, function ($item) use ($search) {
				return stripos($item['customer_name'], $search) !== false;
			});
		}

		// Calculate grand totals
		foreach ($data_array as $item) {
			$grand_total_income += $item['total_income'];
			$grand_total_paid += $item['total_paid'];
			$grand_total_due += $item['total_due'];
		}

		// Get currency information
		$currency = request()->activeBusiness->currency;

		// Create paginator from filtered array
		$page = request('page', 1);
		$total = count($data_array);
		$paginator = new \Illuminate\Pagination\LengthAwarePaginator(
			array_slice($data_array, ($page - 1) * $per_page, $per_page),
			$total,
			$per_page,
			$page,
			['path' => request()->url(), 'query' => request()->query()]
		);

		// Get business information for the header
		$business_name = request()->activeBusiness->name;

		// Return Inertia render with paginated data
		return Inertia::render('Backend/User/Reports/IncomeByCustomer', [
			'report_data' => $paginator->items(),
			'date1' => $date1,
			'date2' => $date2,
			'business_name' => $business_name,
			'currency' => $currency,
			'customer_id' => $customer_id,
			'customers' => $customers,
			'grand_total_income' => $grand_total_income,
			'grand_total_paid' => $grand_total_paid,
			'grand_total_due' => $grand_total_due,
			'meta' => [
				'current_page' => $paginator->currentPage(),
				'from' => $paginator->firstItem(),
				'last_page' => $paginator->lastPage(),
				'links' => $paginator->links()->toHtml(),
				'path' => $paginator->path(),
				'per_page' => $paginator->perPage(),
				'to' => $paginator->lastItem(),
				'total' => $paginator->total(),
			],
			'filters' => [
				'search' => $search,
			],
		]);
	}

	public function receivables(Request $request)
	{
		// Get request parameters
		$date1 = $request->date1 ?? Carbon::now()->startOfMonth()->format('Y-m-d');
		$date2 = $request->date2 ?? Carbon::now()->format('Y-m-d');
		$customer_id = $request->customer_id ?? '';
		$search = $request->search ?? '';
		$per_page = $request->per_page ?? 10;

		// Session storage (keep for backwards compatibility)
		session(['start_date' => $date1]);
		session(['end_date' => $date2]);
		session(['customer_id' => $customer_id]);

		return $this->generate_receivables_report($date1, $date2, $customer_id, $search, $per_page);
	}

	private function generate_receivables_report($date1, $date2, $customer_id, $search, $per_page)
	{
		// Get customers list for dropdown
		$customers = Customer::select('id', 'name', 'mobile', 'email')
			->orderBy('name')
			->get();

		// Get receivables data
		$invoices = Invoice::with('customer', 'client')
			->when($customer_id, function ($query, $customer_id) {
				return $query->where('customer_id', $customer_id);
			})
			->whereRaw("date(invoices.invoice_date) >= '$date1' AND date(invoices.invoice_date) <= '$date2'")
			->get();

		// Prepare data array for report
		$data_array = [];

		foreach ($invoices as $invoice) {
			$due_amount = $invoice->grand_total - $invoice->paid;

			// Skip invoices that are fully paid
			if ($due_amount <= 0) {
				continue;
			}

			$data_array[] = [
				'id' => $invoice->id,
				'invoice_number' => $invoice->invoice_number,
				'customer_id' => $invoice->customer_id,
				'customer_name' => $invoice->customer->name,
				'client_name' => $invoice->client->name,
				'invoice_date' => $invoice->invoice_date,
				'due_date' => $invoice->due_date,
				'grand_total' => $invoice->grand_total,
				'paid_amount' => $invoice->paid,
				'due_amount' => $due_amount,
				'status' => $invoice->status,
				'payment_status' => $invoice->payment_status,
			];
		}

		// Apply search filter if provided
		if (!empty($search)) {
			$data_array = array_filter($data_array, function ($item) use ($search) {
				return stripos($item['customer_name'], $search) !== false ||
					stripos($item['invoice_number'], $search) !== false ||
					stripos($item['client_name'], $search) !== false;
			});
			// Re-index array after filtering
			$data_array = array_values($data_array);
		}

		// Calculate total receivables
		$total_grand = 0;
		$total_paid = 0;
		$total_due = 0;

		foreach ($data_array as $item) {
			$total_grand += $item['grand_total'];
			$total_paid += $item['paid_amount'];
			$total_due += $item['due_amount'];
		}

		// Get currency information
		$currency = request()->activeBusiness->currency;

		// Create paginator from array
		$page = request('page', 1);
		$total = count($data_array);
		$paginator = new \Illuminate\Pagination\LengthAwarePaginator(
			array_slice($data_array, ($page - 1) * $per_page, $per_page),
			$total,
			$per_page,
			$page,
			['path' => request()->url(), 'query' => request()->query()]
		);

		// Get business information
		$business_name = request()->activeBusiness->name;

		return Inertia::render('Backend/User/Reports/Receivables', [
			'report_data' => $paginator->items(),
			'customers' => $customers,
			'currency' => $currency,
			'grand_total' => $total_grand,
			'paid_amount' => $total_paid,
			'due_amount' => $total_due,
			'business_name' => $business_name,
			'date1' => $date1,
			'date2' => $date2,
			'customer_id' => $customer_id,
			'pagination' => [
				'current_page' => $paginator->currentPage(),
				'last_page' => $paginator->lastPage(),
				'from' => $paginator->firstItem(),
				'path' => $paginator->path(),
				'per_page' => $paginator->perPage(),
				'to' => $paginator->lastItem(),
				'total' => $paginator->total(),
			],
			'filters' => [
				'search' => $search,
			],
		]);
	}

	public function payables(Request $request)
	{
		// Get request parameters
		$date1 = $request->date1 ?? Carbon::now()->startOfMonth()->format('Y-m-d');
		$date2 = $request->date2 ?? Carbon::now()->format('Y-m-d');
		$vendor_id = $request->vendor_id ?? '';
		$search = $request->search ?? '';
		$per_page = $request->per_page ?? 10;

		// Session storage (keep for backwards compatibility)
		session(['start_date' => $date1]);
		session(['end_date' => $date2]);
		session(['vendor_id' => $vendor_id]);

		return $this->generate_payables_report($date1, $date2, $vendor_id, $search, $per_page);
	}

	public function generate_payables_report($date1, $date2, $vendor_id, $search, $per_page)
	{
		// Get vendors list for dropdown
		$vendors = Vendor::select('id', 'name', 'mobile', 'email')
			->orderBy('name')
			->get();

		// Get receivables data
		$purchases = Purchase::with('vendor')
			->when($vendor_id, function ($query, $vendor_id) {
				return $query->where('vendor_id', $vendor_id);
			})
			->whereRaw("date(purchases.purchase_date) >= '$date1' AND date(purchases.purchase_date) <= '$date2'")
			->get();

		// Prepare data array for report
		$data_array = [];

		foreach ($purchases as $purchase) {
			$due_amount = $purchase->grand_total - $purchase->paid;

			// Skip purchases that are fully paid
			if ($due_amount <= 0) {
				continue;
			}

			$data_array[] = [
				'id' => $purchase->id,
				'purchase_number' => $purchase->bill_no,
				'vendor_id' => $purchase->vendor_id,
				'vendor_name' => $purchase->vendor->name,
				'purchase_date' => $purchase->purchase_date,
				'due_date' => $purchase->due_date,
				'grand_total' => $purchase->grand_total,
				'paid_amount' => $purchase->paid,
				'due_amount' => $due_amount,
				'status' => $purchase->status,
			];
		}

		// Apply search filter if provided
		if (!empty($search)) {
			$data_array = array_filter($data_array, function ($item) use ($search) {
				return stripos($item['vendor_name'], $search) !== false ||
					stripos($item['purchase_number'], $search) !== false;
			});
			// Re-index array after filtering
			$data_array = array_values($data_array);
		}

		// Calculate total receivables
		$total_grand = 0;
		$total_paid = 0;
		$total_due = 0;

		foreach ($data_array as $item) {
			$total_grand += $item['grand_total'];
			$total_paid += $item['paid_amount'];
			$total_due += $item['due_amount'];
		}

		// Get currency information
		$currency = request()->activeBusiness->currency;

		// Create paginator from array
		$page = request('page', 1);
		$total = count($data_array);
		$paginator = new LengthAwarePaginator(
			array_slice($data_array, ($page - 1) * $per_page, $per_page),
			$total,
			$per_page,
			$page,
			['path' => request()->url(), 'query' => request()->query()]
		);

		// Get business information
		$business_name = request()->activeBusiness->name;

		return Inertia::render('Backend/User/Reports/Payables', [
			'report_data' => $paginator->items(),
			'vendors' => $vendors,
			'currency' => $currency,
			'grand_total' => $total_grand,
			'paid_amount' => $total_paid,
			'due_amount' => $total_due,
			'business_name' => $business_name,
			'date1' => $date1,
			'date2' => $date2,
			'vendor_id' => $vendor_id,
			'pagination' => [
				'current_page' => $paginator->currentPage(),
				'last_page' => $paginator->lastPage(),
				'from' => $paginator->firstItem(),
				'path' => $paginator->path(),
				'per_page' => $paginator->perPage(),
				'to' => $paginator->lastItem(),
				'total' => $paginator->total(),
			],
			'filters' => [
				'search' => $search,
			],
		]);
	}

	public function ledger(Request $request)
	{
		// Get request parameters
		$date1 = $request->date1 ?? Carbon::now()->startOfMonth()->format('Y-m-d');
		$date2 = $request->date2 ?? Carbon::now()->format('Y-m-d');
		$search = $request->search ?? '';
		$per_page = $request->per_page ?? 10;

		// Session storage (keep for backwards compatibility)
		session(['start_date' => $date1]);
		session(['end_date' => $date2]);

		return $this->generate_ledger_report($date1, $date2, $search, $per_page);
	}

	private function generate_ledger_report($date1, $date2, $search, $per_page)
	{
		// Query accounts with transactions in the date range
		$accounts_query = Account::with(['transactions' => function ($query) use ($date1, $date2) {
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
			}], 'base_currency_amount');

		// Get accounts
		$accounts = $accounts_query->get();

		// Process data array - flat list of accounts
		$data_array = [];
		$grand_total_debit = 0;
		$grand_total_credit = 0;

		// Process each account
		foreach ($accounts as $account) {
			// Skip accounts with no transactions
			if ($account->transactions->isEmpty()) {
				continue;
			}

			// Calculate account balance
			$debit_amount = $account->dr_amount ?? 0;
			$credit_amount = $account->cr_amount ?? 0;
			$balance = 0;

			// Calculate balance based on dr_cr
			if ($account->dr_cr == 'dr') {
				$balance = $debit_amount - $credit_amount;
			} else {
				$balance = $credit_amount - $debit_amount;
			}

			// Create account data array
			$account_data = [
				'id' => $account->id,
				'account_name' => $account->account_name,
				'account_number' => $account->account_code,
				'debit_amount' => $debit_amount,
				'credit_amount' => $credit_amount,
				'balance' => $balance,
				'dr_cr' => $account->dr_cr,
				'transactions' => []
			];

			// Add transactions to account data
			foreach ($account->transactions as $transaction) {
				$account_data['transactions'][] = [
					'id' => $transaction->id,
					'trans_date' => $transaction->trans_date,
					'description' => $transaction->description,
					'transaction_amount' => $transaction->transaction_amount,
					'base_currency_amount' => $transaction->base_currency_amount,
					'ref_type' => $transaction->ref_type,
					'payee_name' => $transaction->payee_name,
					'transaction_currency' => $transaction->transaction_currency,
					'currency_rate' => $transaction->currency_rate,
					'dr_cr' => $transaction->dr_cr,
				];
			}

			// Add account to data array
			$data_array[] = $account_data;

			// Update grand totals
			$grand_total_debit += $debit_amount;
			$grand_total_credit += $credit_amount;
		}

		// Apply search filter if provided
		if (!empty($search)) {
			$data_array = array_filter($data_array, function ($account) use ($search) {
				return stripos($account['account_name'], $search) !== false ||
					stripos($account['account_number'], $search) !== false;
			});

			// Re-index the array to ensure consistent numeric keys.
			$data_array = array_values($data_array);
		}

		// Get currency information
		$currency = request()->activeBusiness->currency;
		$currency_symbol = currency_symbol($currency);

		// Format currency amounts
		foreach ($data_array as &$account) {
			$account['debit_amount_formatted'] = formatAmount($account['debit_amount'], $currency_symbol);
			$account['credit_amount_formatted'] = formatAmount($account['credit_amount'], $currency_symbol);
			$account['balance_formatted'] = formatAmount($account['balance'], $currency_symbol);

			foreach ($account['transactions'] as &$transaction) {
				$transaction['transaction_amount_formatted'] = formatAmount($transaction['transaction_amount'], $currency_symbol);
				$transaction['base_currency_amount_formatted'] = formatAmount($transaction['base_currency_amount'], $currency_symbol);
			}
		}

		// Create paginator from filtered array
		$page = request('page', 1);
		$total = count($data_array);
		$paginator = new LengthAwarePaginator(
			array_slice($data_array, ($page - 1) * $per_page, $per_page),
			$total,
			$per_page,
			$page,
			['path' => request()->url(), 'query' => request()->query()]
		);

		// Format grand total amounts
		$grand_total_debit_formatted = formatAmount($grand_total_debit, $currency_symbol);
		$grand_total_credit_formatted = formatAmount($grand_total_credit, $currency_symbol);
		$grand_total_balance_formatted = formatAmount($grand_total_debit - $grand_total_credit, $currency_symbol);

		// Get business information
		$business_name = request()->activeBusiness->name;

		// Return Inertia render with data
		return Inertia::render('Backend/User/Reports/Ledger', [
			'report_data' => $paginator->items(),
			'currency' => $currency,
			'grand_total_debit' => $grand_total_debit_formatted,
			'grand_total_credit' => $grand_total_credit_formatted,
			'grand_total_balance' => $grand_total_balance_formatted,
			'business_name' => $business_name,
			'date1' => $date1,
			'date2' => $date2,
			'pagination' => [
				'current_page' => $paginator->currentPage(),
				'last_page' => $paginator->lastPage(),
				'from' => $paginator->firstItem(),
				'path' => $paginator->path(),
				'per_page' => $paginator->perPage(),
				'to' => $paginator->lastItem(),
				'total' => $paginator->total(),
			],
			'filters' => [
				'search' => $search,
			],
		]);
	}

	public function journal(Request $request)
	{
		if ($request->isMethod('get')) {
			$date1 = Carbon::now()->startOfMonth()->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$per_page = $request->get('per_page', 10);
			$search = $request->get('search', '');

			$query = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'");

			// Apply search if provided
			if (!empty($search)) {
				$query->where(function ($q) use ($search) {
					$q->where('account.account_name', 'like', "%{$search}%")
						->orWhere('payee_name', 'like', "%{$search}%");
				});
			}

			$transactions = $query->paginate($per_page);

			$base_currency = $request->activeBusiness->currency;
			$business_name = $request->activeBusiness->name;

			return Inertia::render('Backend/User/Reports/GeneralJournal', [
				'transactions' => $transactions->items(),
				'date1' => $date1,
				'date2' => $date2,
				'base_currency' => $base_currency,
				'business_name' => $business_name,
				'meta' => [
					'current_page' => $transactions->currentPage(),
					'from' => $transactions->firstItem(),
					'last_page' => $transactions->lastPage(),
					'per_page' => $per_page,
					'to' => $transactions->lastItem(),
					'total' => $transactions->total(),
					'links' => $transactions->linkCollection(),
					'path' => $transactions->path(),
				],
				'filters' => [
					'search' => $search,
				],
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$per_page = $request->get('per_page', 10);
			$search = $request->get('search', '');

			$query = Transaction::with('account')
				->whereRaw("date(transactions.trans_date) >= '$date1' AND date(transactions.trans_date) <= '$date2'");

			// Apply search if provided
			if (!empty($search)) {
				$query->where(function ($q) use ($search) {
					$q->where('account.account_name', 'like', "%{$search}%")
						->orWhere('payee_name', 'like', "%{$search}%");
				});
			}

			$transactions = $query->paginate($per_page);

			$base_currency = $request->activeBusiness->currency;
			$business_name = $request->activeBusiness->name;

			return Inertia::render('Backend/User/Reports/GeneralJournal', [
				'transactions' => $transactions->items(),
				'date1' => $date1,
				'date2' => $date2,
				'business_name' => $business_name,
				'base_currency' => $base_currency,
				'meta' => [
					'current_page' => $transactions->currentPage(),
					'from' => $transactions->firstItem(),
					'last_page' => $transactions->lastPage(),
					'per_page' => $per_page,
					'to' => $transactions->lastItem(),
					'total' => $transactions->total(),
					'links' => $transactions->linkCollection(),
					'path' => $transactions->path(),
				],
				'filters' => [
					'search' => $search,
				],
			]);
		}
	}

	public function trial_balance(Request $request)
	{
		if ($request->isMethod('get')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
			$date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$report_data['fixed_asset'] = Account::where(function ($query) {
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

			$report_data['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
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

			$report_data['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
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

			$report_data['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
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

			$report_data['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
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

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			foreach ($report_data['fixed_asset'] as $fixed_asset) {
				$total_debit += $fixed_asset->dr_amount;
				$total_credit += $fixed_asset->cr_amount;
			}

			foreach ($report_data['current_asset'] as $current_asset) {
				$total_debit += $current_asset->dr_amount;
				$total_credit += $current_asset->cr_amount;
			}

			foreach ($report_data['current_liability'] as $current_liability) {
				$total_debit += $current_liability->dr_amount;
				$total_credit += $current_liability->cr_amount;
			}

			foreach ($report_data['long_term_liability'] as $long_term_liability) {
				$total_debit += $long_term_liability->dr_amount;
				$total_credit += $long_term_liability->cr_amount;
			}

			foreach ($report_data['equity'] as $equity) {
				$total_debit += $equity->dr_amount;
				$total_credit += $equity->cr_amount;
			}

			foreach ($report_data['sales_and_income'] as $sales_and_income) {
				$total_debit += $sales_and_income->dr_amount;
				$total_credit += $sales_and_income->cr_amount;
			}

			foreach ($report_data['cost_of_sale'] as $cost_of_sale) {
				$total_debit += $cost_of_sale->dr_amount;
				$total_credit += $cost_of_sale->cr_amount;
			}

			foreach ($report_data['direct_expenses'] as $direct_expenses) {
				$total_debit += $direct_expenses->dr_amount;
				$total_credit += $direct_expenses->cr_amount;
			}

			foreach ($report_data['other_expenses'] as $other_expenses) {
				$total_debit += $other_expenses->dr_amount;
				$total_credit += $other_expenses->cr_amount;
			}

			$report_data['total_debit'] = $total_debit;
			$report_data['total_credit'] = $total_credit;

			$base_currency = $request->activeBusiness->currency;
			$business_name = $request->activeBusiness->name;

			return Inertia::render('Backend/User/Reports/TrialBalance', [
				'report_data' => $report_data,
				'base_currency' => $base_currency,
				'business_name' => $business_name,
				'date1' => $date1,
				'date2' => $date2,
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$report_data['fixed_asset'] = Account::where(function ($query) {
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

			$report_data['current_asset'] = Account::where(function ($query) {
				$query->where('account_type', '=', 'Bank')
					->orWhere('account_type', '=', 'Cash')
					->orWhere('account_type', '=', 'Other Current Asset');
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

			$report_data['current_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Current Liability');
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

			$report_data['long_term_liability'] = Account::where(function ($query) {
				$query->where('account_type', 'Long Term Liability');
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

			$report_data['equity'] = Account::where(function ($query) {
				$query->where('account_type', 'Equity');
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

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			foreach ($report_data['fixed_asset'] as $fixed_asset) {
				$total_debit += $fixed_asset->dr_amount;
				$total_credit += $fixed_asset->cr_amount;
			}

			foreach ($report_data['current_asset'] as $current_asset) {
				$total_debit += $current_asset->dr_amount;
				$total_credit += $current_asset->cr_amount;
			}

			foreach ($report_data['current_liability'] as $current_liability) {
				$total_debit += $current_liability->dr_amount;
				$total_credit += $current_liability->cr_amount;
			}

			foreach ($report_data['long_term_liability'] as $long_term_liability) {
				$total_debit += $long_term_liability->dr_amount;
				$total_credit += $long_term_liability->cr_amount;
			}

			foreach ($report_data['equity'] as $equity) {
				$total_debit += $equity->dr_amount;
				$total_credit += $equity->cr_amount;
			}

			foreach ($report_data['sales_and_income'] as $sales_and_income) {
				$total_debit += $sales_and_income->dr_amount;
				$total_credit += $sales_and_income->cr_amount;
			}

			foreach ($report_data['cost_of_sale'] as $cost_of_sale) {
				$total_debit += $cost_of_sale->dr_amount;
				$total_credit += $cost_of_sale->cr_amount;
			}

			foreach ($report_data['direct_expenses'] as $direct_expenses) {
				$total_debit += $direct_expenses->dr_amount;
				$total_credit += $direct_expenses->cr_amount;
			}

			foreach ($report_data['other_expenses'] as $other_expenses) {
				$total_debit += $other_expenses->dr_amount;
				$total_credit += $other_expenses->cr_amount;
			}

			$date1 = Carbon::parse($request->date1);
			$date2 = Carbon::parse($request->date2);
			$report_data['total_debit'] = $total_debit;
			$report_data['total_credit'] = $total_credit;
			$base_currency = $request->activeBusiness->currency;
			$business_name = $request->activeBusiness->name;
			return Inertia::render('Backend/User/Reports/TrialBalance', [
				'report_data' => $report_data,
				'date1' => $date1,
				'date2' => $date2,
				'base_currency' => $base_currency,
				'business_name' => $business_name,
			]);
		}
	}

	public function balance_sheet(Request $request)
	{
		if ($request->isMethod('get')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
			$date2 = Carbon::now();

			session(['end_date' => $date2]);

			$report_data['fixed_asset'] = Account::where(function ($query) {
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

			$report_data['current_asset'] = Account::where(function ($query) {
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

			$report_data['current_liability'] = Account::where(function ($query) {
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

			$report_data['long_term_liability'] = Account::where(function ($query) {
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

			$report_data['equity'] = Account::where(function ($query) {
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

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			foreach ($report_data['sales_and_income'] as $sales_and_income) {
				$total_debit_sales_and_income += $sales_and_income->dr_amount;
				$total_credit_sales_and_income += $sales_and_income->cr_amount;
			}

			foreach ($report_data['cost_of_sale'] as $cost_of_sale) {
				$total_debit_cost_of_sale += $cost_of_sale->dr_amount;
				$total_credit_cost_of_sale += $cost_of_sale->cr_amount;
			}

			foreach ($report_data['direct_expenses'] as $direct_expenses) {
				$total_debit_direct_expenses += $direct_expenses->dr_amount;
				$total_credit_direct_expenses += $direct_expenses->cr_amount;
			}

			foreach ($report_data['other_expenses'] as $other_expenses) {
				$total_debit_other_expenses += $other_expenses->dr_amount;
				$total_credit_other_expenses += $other_expenses->cr_amount;
			}

			$income_statement = (($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount'))) - ((($report_data['direct_expenses']->sum('dr_amount') - $report_data['direct_expenses']->sum('cr_amount'))) + (($report_data['other_expenses']->sum('dr_amount') - $report_data['other_expenses']->sum('cr_amount'))));

			foreach ($report_data['fixed_asset'] as $fixed_asset) {
				$total_debit_asset += $fixed_asset->dr_amount;
				$total_credit_asset += $fixed_asset->cr_amount;
			}

			foreach ($report_data['current_asset'] as $current_asset) {
				$total_debit_asset += $current_asset->dr_amount;
				$total_credit_asset += $current_asset->cr_amount;
			}

			foreach ($report_data['current_liability'] as $current_liability) {
				$total_debit_liability += $current_liability->dr_amount;
				$total_credit_liability += $current_liability->cr_amount;
			}

			foreach ($report_data['long_term_liability'] as $long_term_liability) {
				$total_debit_liability += $long_term_liability->dr_amount;
				$total_credit_liability += $long_term_liability->cr_amount;
			}

			foreach ($report_data['equity'] as $equity) {
				$total_debit_equity += $equity->dr_amount;
				$total_credit_equity += $equity->cr_amount;
			}

			$report_data['equity'][] = (object) [
				'account_code' => '',
				'account_name' => 'Profit & Loss',
				'dr_amount' => $total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses,
				'cr_amount' => $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses,
			];

			$report_data['total_debit_asset'] = $total_debit_asset;
			$report_data['total_credit_asset'] = $total_credit_asset;
			$report_data['total_debit_liability'] = $total_debit_liability;
			$report_data['total_credit_liability'] = $total_credit_liability;
			$report_data['total_debit_equity'] = $total_debit_equity;
			$report_data['total_credit_equity'] = $total_credit_equity;
			$report_data['income_statement'] = $income_statement;

			return Inertia::render('Backend/User/Reports/BalanceSheet', [
				'report_data' => $report_data,
				'date2' => $date2,
				'base_currency' => $request->activeBusiness->currency,
				'business_name' => $request->activeBusiness->name
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['end_date' => $date2]);

			$report_data['fixed_asset'] = Account::where(function ($query) {
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

			$report_data['current_asset'] = Account::where(function ($query) {
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

			$report_data['current_liability'] = Account::where(function ($query) {
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

			$report_data['long_term_liability'] = Account::where(function ($query) {
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

			$report_data['equity'] = Account::where(function ($query) {
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

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			foreach ($report_data['sales_and_income'] as $sales_and_income) {
				$total_debit_sales_and_income += $sales_and_income->dr_amount;
				$total_credit_sales_and_income += $sales_and_income->cr_amount;
			}

			foreach ($report_data['cost_of_sale'] as $cost_of_sale) {
				$total_debit_cost_of_sale += $cost_of_sale->dr_amount;
				$total_credit_cost_of_sale += $cost_of_sale->cr_amount;
			}

			foreach ($report_data['direct_expenses'] as $direct_expenses) {
				$total_debit_direct_expenses += $direct_expenses->dr_amount;
				$total_credit_direct_expenses += $direct_expenses->cr_amount;
			}

			foreach ($report_data['other_expenses'] as $other_expenses) {
				$total_debit_other_expenses += $other_expenses->dr_amount;
				$total_credit_other_expenses += $other_expenses->cr_amount;
			}

			$income_statement = $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses - ($total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses);

			foreach ($report_data['fixed_asset'] as $fixed_asset) {
				$total_debit_asset += $fixed_asset->dr_amount;
				$total_credit_asset += $fixed_asset->cr_amount;
			}

			foreach ($report_data['current_asset'] as $current_asset) {
				$total_debit_asset += $current_asset->dr_amount;
				$total_credit_asset += $current_asset->cr_amount;
			}

			foreach ($report_data['current_liability'] as $current_liability) {
				$total_debit_liability += $current_liability->dr_amount;
				$total_credit_liability += $current_liability->cr_amount;
			}

			foreach ($report_data['long_term_liability'] as $long_term_liability) {
				$total_debit_liability += $long_term_liability->dr_amount;
				$total_credit_liability += $long_term_liability->cr_amount;
			}

			foreach ($report_data['equity'] as $equity) {
				$total_debit_equity += $equity->dr_amount;
				$total_credit_equity += $equity->cr_amount;
			}

			$report_data['equity'][] = (object) [
				'account_code' => '',
				'account_name' => 'Profit & Loss',
				'dr_amount' => $total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses,
				'cr_amount' => $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses,
			];

			$date2 = Carbon::parse($date2);
			$report_data['total_debit_asset'] = $total_debit_asset;
			$report_data['total_credit_asset'] = $total_credit_asset;
			$report_data['total_debit_liability'] = $total_debit_liability;
			$report_data['total_credit_liability'] = $total_credit_liability;
			$report_data['total_debit_equity'] = $total_debit_equity;
			$report_data['total_credit_equity'] = $total_credit_equity;
			$report_data['income_statement'] = $income_statement;

			return Inertia::render('Backend/User/Reports/BalanceSheet', [
				'report_data' => $report_data,
				'date2' => $date2,
				'base_currency' => $request->activeBusiness->base_currency,
				'business_name' => $request->activeBusiness->name,
			]);
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
			$report_data = array();

			$date1 = $date1 = Carbon::now()->startOfMonth();
			$date2 = Carbon::now();

			session(['end_date' => $date2]);

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			return Inertia::render('Backend/User/Reports/IncomeStatement', [
				'report_data' => $report_data,
				'date1' => $date1,
				'date2' => $date2,
				'base_currency' => $request->activeBusiness->currency,
				'business_name' => $request->activeBusiness->name,
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);

			$report_data['sales_and_income'] = Account::where(function ($query) {
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

			$report_data['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
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

			$report_data['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
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

			$report_data['other_expenses'] = Account::where('account_type', 'Other Expenses')
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

			$date1 = Carbon::parse($date1);
			$date2 = Carbon::parse($date2);

			return Inertia::render('Backend/User/Reports/IncomeStatement', [
				'report_data' => $report_data,
				'date1' => $date1,
				'date2' => $date2,
				'base_currency' => $request->activeBusiness->currency,
				'business_name' => $request->activeBusiness->name,
			]);
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

			$report_data = array();
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

			$report_data['current_salary'] = $current_salary;
			$report_data['total_allowance'] = $total_allowance;
			$report_data['total_deduction'] = $total_deduction;
			$report_data['net_salary'] = $net_salary;
			$report_data['total_tax'] = $total_tax;

			$currency = request()->activeBusiness->currency;

			return Inertia::render('Backend/User/Reports/PayrollSummary', [
				'report_data' => $report_data,
				'month' => $month,
				'year' => $year,
				'currency' => $currency,
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
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

			$report_data['current_salary'] = $current_salary;
			$report_data['total_allowance'] = $total_allowance;
			$report_data['total_deduction'] = $total_deduction;
			$report_data['net_salary'] = $net_salary;
			$report_data['total_tax'] = $total_tax;

			$month = $request->month;
			$year = $request->year;
			$currency = request()->activeBusiness->currency;

			return Inertia::render('Backend/User/Reports/PayrollSummary', [
				'report_data' => $report_data,
				'month' => $month,
				'year' => $year,
				'currency' => $currency,
			]);
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
			$report_data = array();
			$month = now()->month;
			$year = now()->year;

			$report_data['payroll'] = Payroll::with('staff')
				->select('payslips.*')
				->where('month', $month)
				->where('year', $year)
				->get();

			$report_data['total_netsalary'] = $report_data['payroll']->sum('net_salary');
			$report_data['total_basicsalary'] = $report_data['payroll']->sum('current_salary');
			$report_data['total_allowance'] = $report_data['payroll']->sum('total_allowance');
			$report_data['total_deduction'] = $report_data['payroll']->sum('total_deduction');
			$report_data['total_tax'] = $report_data['payroll']->sum('tax_amount');
			$report_data['total_advance'] = $report_data['payroll']->sum('advance');

			$month = now()->month;;
			$year = now()->year;
			$currency = request()->activeBusiness->currency;
			return Inertia::render('Backend/User/Reports/PayrollCost', [
				'report_data' => $report_data,
				'month' => $month,
				'year' => $year,
				'currency' => $currency,
			]);
		} else if ($request->isMethod('post')) {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$report_data = array();
			$month = $request->month;
			$year = $request->year;

			$report_data['payroll'] = Payroll::with('staff')
				->select('payslips.*')
				->where('month', $month)
				->where('year', $year)
				->get();

			$report_data['total_netsalary'] = $report_data['payroll']->sum('net_salary');
			$report_data['total_basicsalary'] = $report_data['payroll']->sum('current_salary');
			$report_data['total_allowance'] = $report_data['payroll']->sum('total_allowance');
			$report_data['total_deduction'] = $report_data['payroll']->sum('total_deduction');
			$report_data['total_tax'] = $report_data['payroll']->sum('tax_amount');
			$report_data['total_advance'] = $report_data['payroll']->sum('advance');

			$month = $request->month;
			$year = $request->year;
			$currency = request()->activeBusiness->currency;

			return Inertia::render('Backend/User/Reports/PayrollCost', [
				'report_data' => $report_data,
				'month' => $month,
				'year' => $year,
				'currency' => $currency,
			]);
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
			$categories = array();

			$sub_category = 'all';
			$main_category = 'all';

			$date1 = Carbon::now()->startOfMonth()->format('Y-m-d');
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
			$categories = $sortedCategories;

			return Inertia::render('Backend/User/Reports/InventoryDetails', [
				'categories' => $categories,
				'date1' => $date1,
				'date2' => $date2,
				'sub_category' => $sub_category,
				'main_category' => $main_category,
				'business_name' => $request->activeBusiness->name,
				'subCategories' => SubCategory::all(),
				'mainCategories' => mainCategory::all(),
			]);
		} else {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$categories = array();
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
			$categories = $sortedCategories;

			return Inertia::render('Backend/User/Reports/InventoryDetails', [
				'categories' => $categories,
				'date1' => $date1,
				'date2' => $date2,
				'sub_category' => $sub_category,
				'main_category' => $main_category,
				'business_name' => $request->activeBusiness->name,
				'subCategories' => SubCategory::all(),
				'mainCategories' => MainCategory::all(),
			]);
		}
	}

	public function inventory_summary(Request $request)
	{
		if ($request->isMethod('get')) {
			$categories = array();

			$date1 = Carbon::now()->startOfMonth()->format('Y-m-d');
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
			$categories = $sortedCategories;

			return Inertia::render('Backend/User/Reports/InventorySummary', [
				'categories' => $categories,
				'date1' => $date1,
				'date2' => $date2,
				'business_name' => $request->activeBusiness->name,
			]);
		} else {
			@ini_set('max_execution_time', 0);
			@set_time_limit(0);

			$categories = array();
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
			$categories = $sortedCategories;

			return Inertia::render('Backend/User/Reports/InventorySummary', [
				'categories' => $categories,
				'date1' => $date1,
				'date2' => $date2,
				'business_name' => $request->activeBusiness->name,
			]);
		}
	}

	public function sales_by_product(Request $request)
	{
		if ($request->isMethod('get')) {
			$page_title = _lang('Inventory Stock');
			$data = array();

			$sub_category = 'all';
			$main_category = 'all';

			$date1 = Carbon::now()->subDays(30)->format('Y-m-d');
			$date2 = Carbon::now()->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['sub_category' => $sub_category]);
			session(['main_category' => $main_category]);

			// Step 1: Retrieve products with necessary joins and calculated fields
			$products = Product::select(
				'products.*',
				'main_categories.id as main_category_id',
				'main_categories.name as main_category_name',
				'sub_categories.id as category_id',
				'sub_categories.name as category_name',
				'product_brands.id as brand_id',
				'product_brands.name as brand_name'
			)
				->leftjoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
				->leftJoin('main_categories', 'main_categories.id', '=', 'sub_categories.main_category_id')
				->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
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
			$sub_category = $request->sub_category;

			$date1 = Carbon::parse($request->date1)->format('Y-m-d');
			$date2 = Carbon::parse($request->date2)->format('Y-m-d');

			session(['start_date' => $date1]);
			session(['end_date' => $date2]);
			session(['sub_category' => $sub_category]);

			if ($sub_category == 'all') {
				// Step 1: Retrieve products with necessary joins and calculated fields
				$products = Product::select(
					'products.*',
					'sub_categories.id as category_id',
					'sub_categories.name as category_name',
					'product_brands.id as brand_id',
					'product_brands.name as brand_name'
				)
					->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
					->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
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
					'sub_categories.id as sub_category_id',
					'sub_categories.name as sub_category_name',
					'product_brands.id as brand_id',
					'product_brands.name as brand_name'
				)
					->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
					->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
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
					->where('sub_categories.name', $sub_category)
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
			$data['sub_category'] = $sub_category;

			return view('backend.user.reports.sales_by_product', $data);
		}
	}
}
