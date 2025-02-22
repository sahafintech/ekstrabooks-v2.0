<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\SubscriptionPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BusinessSettingSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function index(Request $request)
	{
		$user = auth()->user();
		$user_type = $user->user_type;
		$data = array();

		if ($user_type == 'admin') {
			$data['total_user'] = User::where('user_type', 'user')->count();
			$data['total_owner'] = User::where('user_type', 'user')
				->whereHas('package', function ($query) {
					$query->where('membership_type', '!=', null);
				})
				->count();

			$data['trial_users'] = User::where('user_type', 'user')
				->whereHas('package', function ($query) {
					$query->where('membership_type', 'trial');
				})
				->count();

			$data['expired_users'] = User::where('user_type', 'user')
				->whereHas('package', function ($query) {
					$query->where('membership_type', '!=', null)
						->whereDate('valid_to', '<', now());
				})
				->count();

			$data['recentPayments'] = SubscriptionPayment::select('subscription_payments.*')
				->with('user', 'package', 'created_by')
				->orderBy("subscription_payments.id", "desc")
				->limit(10)
				->get()
				->map(function ($payment) {
					$payment->amount = decimalPlace($payment->amount, currency_symbol());
					$payment->status = $payment->status == 1 ? 'Completed' : 'Pending';
					return $payment;
				});

			$data['newUsers'] = User::where('user_type', 'user')
				->whereHas('package', function ($query) {
					$query->where('membership_type', '!=', null)
						->whereDate('valid_to', '>', now());
				})
				->orderBy("users.id", "desc")
				->limit(10)
				->get();

			return Inertia::render('Backend/Admin/Dashboard-Admin', compact('data'));
		} else if ($user_type == 'user') {

			$currency = Currency::all();
			$businessSettings = BusinessSetting::where('business_id', request()->activeBusiness->id)->get();

			if ($currency->count() <= 0 && $businessSettings->count() <= 0) {
				// Instantiate the seeder with arguments
				$cseeder = new CurrencySeeder(auth()->user()->id, request()->activeBusiness->id);

				// Run the seeder
				$cseeder->run();

				// Instantiate the seeder with arguments
				$bseeder = new BusinessSettingSeeder(request()->activeBusiness->id);

				// Run the seeder
				$bseeder->run();
			}

			$topCustomersCredit = Invoice::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, customer_id')
				->where('is_recurring', 0)
				->where('status', '!=', 0)
				->limit(4)
				->with('customer')
				->groupBy('customer_id');


			$topCustomersJoined = Receipt::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, customer_id')
				->orderBy('total_amount', 'desc')
				->limit(3)
				->whereHas('customer')
				->with('customer')
				->groupBy('customer_id')
				->union($topCustomersCredit)
				->get()
				->groupBy('customer_id');

			$topCustomers = array();

			foreach ($topCustomersJoined as $key => $topCustomer) {
				$customer = $topCustomer->first()->customer;
				$topCustomers[$key] = $customer;
				$topCustomers[$key]['total_amount'] = $topCustomer->sum('total_amount');
				$topCustomers[$key]['total_invoice'] = $topCustomer->sum('total_invoice');
			}

			$data['topCustomers'] = $topCustomers;

			// income and expense widgets

			$data['range'] = $request->range;
			$data['custom'] = $request->custom;

			$start_date = Carbon::parse(explode('to', $request->custom)[0] ?? '')->format('Y-m-d');
			$end_date = Carbon::parse(explode('to', $request->custom)[1] ?? '')->format('Y-m-d');

			$income_dr_amount = Transaction::whereHas('account', function ($query) {
				$query->where('account_type', 'Sales')
					->orWhere('account_type', 'Other Income');
			})
				->selectRaw("SUM(transactions.base_currency_amount) as total")
				->where('dr_cr', 'dr')
				->when($request->range, function ($query) use ($request) {
					$query->where('trans_date', '>=', now()->subDays($request->range));
				})
				->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$income_cr_amount = Transaction::whereHas('account', function ($query) {
				$query->where('account_type', 'Sales')
					->orWhere('account_type', 'Other Income');
			})
				->selectRaw("SUM(transactions.base_currency_amount) as total")
				->where('dr_cr', 'cr')
				->when($request->range, function ($query) use ($request) {
					$query->where('trans_date', '>=', now()->subDays($request->range));
				})
				->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$data['current_month_income'] = $income_cr_amount->total - $income_dr_amount->total;


			$expense_dr_amount = Transaction::whereHas('account', function ($query) {
				$query->where('account_type', 'Other Expenses')
					->orWhere('account_type', 'Direct Expenses')
					->orWhere('account_type', 'Cost Of Sale')
					->where(function ($query) {
						$query->where('business_id', '=', request()->activeBusiness->id)
							->orwhere('business_id', '=', null);
					});
			})
				->selectRaw("SUM(transactions.base_currency_amount) as total")
				->where('dr_cr', 'dr')
				->when($request->range, function ($query) use ($request) {
					$query->where('trans_date', '>=', now()->subDays($request->range));
				})
				->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$expense_cr_amount = Transaction::whereHas('account', function ($query) {
				$query->where('account_type', 'Other Expenses')
					->orWhere('account_type', 'Direct Expenses')
					->orWhere('account_type', 'Cost Of Sale')
					->where(function ($query) {
						$query->where('business_id', '=', request()->activeBusiness->id)
							->orwhere('business_id', '=', null);
					});
			})
				->selectRaw("SUM(transactions.base_currency_amount) as total")
				->where('dr_cr', 'cr')
				->when($request->range, function ($query) use ($request) {
					$query->where('trans_date', '>=', now()->subDays($request->range));
				})
				->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$data['current_month_expense'] = $expense_dr_amount->total - $expense_cr_amount->total;

			// income vs expense graph data

			$months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
			$expenses = array();
			$incomes = array();

			foreach ($months as $m) {
				$expense_dr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Other Expenses')
						->orWhere('account_type', 'Direct Expenses')
						->orWhere('account_type', 'Cost Of Sale')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'dr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$expense_cr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Other Expenses')
						->orWhere('account_type', 'Direct Expenses')
						->orWhere('account_type', 'Cost Of Sale')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'cr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$expense = $expense_dr_amount->total - $expense_cr_amount->total;
				array_push($expenses, number_format($expense, 2, '.', ''));
			}

			foreach ($months as $m) {
				$income_dr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Sales')
						->orWhere('account_type', 'Other Income')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'dr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$income_cr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Sales')
						->orWhere('account_type', 'Other Income')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'cr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$income = $income_cr_amount->total - $income_dr_amount->total;
				array_push($incomes, number_format($income, 2, '.', ''));
			}

			$data['sales_overview'] = array('expenses' => $expenses, 'incomes' => $incomes);

			// recent invoices
			$data['recentCreditInvoices'] = Invoice::where('is_recurring', 0)
				->where('is_deffered', 0)
				->with('customer')
				->limit(4)
				->get();

			$data['recentCashInvoices'] = Receipt::with('customer')
				->orderBy('id', 'desc')
				->limit(3)
				->get();


			// sales value graph data
			$receivables = array();
			$payables = array();

			$allReceivables = Transaction::whereHas('account', function ($query) {
				$query->where('account_name', '=', 'Accounts Receivable');
			})
				->select(
					DB::raw('SUM(CASE WHEN dr_cr = "dr" THEN base_currency_amount ELSE 0 END) AS total_dr'),
					DB::raw('SUM(CASE WHEN dr_cr = "cr" THEN base_currency_amount ELSE 0 END) AS total_cr'),
					DB::raw('MONTH(trans_date) as month')
				)
				->groupBy('month')
				->whereYear('trans_date', date('Y'))
				->get();

			foreach ($months as $m) {
				$receivable = $allReceivables->where('month', $m)->first();
				if ($receivable) {
					array_push($receivables, number_format($receivable->total_dr - $receivable->total_cr, 2, '.', ''));
				} else {
					array_push($receivables, 0);
				}
			}

			$allPayables = Transaction::whereHas('account', function ($query) {
				$query->where('account_name', '=', 'Accounts Payable');
			})
				->select(
					DB::raw('SUM(CASE WHEN dr_cr = "dr" THEN base_currency_amount ELSE 0 END) AS total_dr'),
					DB::raw('SUM(CASE WHEN dr_cr = "cr" THEN base_currency_amount ELSE 0 END) AS total_cr'),
					DB::raw('MONTH(trans_date) as month')
				)
				->groupBy('month')
				->whereYear('trans_date', date('Y'))
				->get();

			foreach ($months as $m) {
				$payable = $allPayables->where('month', $m)->first();
				if ($payable) {
					array_push($payables, number_format($payable->total_cr - $payable->total_dr, 2, '.', ''));
				} else {
					array_push($payables, 0);
				}
			}


			$data['receivables_payables'] = array('receivables' => $receivables, 'payables' => $payables);

			// cashflow graph data
			$months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
			$cashflow_expenses = array();
			$cashflow_incomes = array();
			$ending_balance = array();
			$opening_balance = array();
			$old_ending_balance = 0;

			foreach ($months as $index => $m) {
				$openingBalance = Transaction::with(['account' => function ($query) {
					$query->where('account_type', '=', 'Bank')
						->orWhere('account_type', '=', 'Cash')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orWhere('business_id', '=', null);
						});
				}])
					->where('dr_cr', 'dr')
					->where('ref_type', 'open')
					->whereMonth("trans_date", $m)
					->whereYear('trans_date', date('Y'))
					->get();

				$endingBalance = Account::where(function ($query) {
					$query->where('account_type', '=', 'Bank')
						->orWhere('account_type', '=', 'Cash');
				})->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				})
					->whereHas('transactions', function ($query) use ($m) {
						$query->where('dr_cr', 'dr')
							->whereMonth("trans_date", $m)
							->whereYear('trans_date', date('Y'));
					})
					->with('transactions')
					->withSum(['transactions as dr_amount' => function ($query) use ($m) {
						$query->where('dr_cr', 'dr')
							->whereMonth("trans_date", $m)
							->whereYear('trans_date', date('Y'));
					}], 'base_currency_amount')
					->get();

				array_push($opening_balance, $openingBalance->sum('base_currency_amount'));

				if ($endingBalance->sum('dr_amount') > 0) {
					array_push($ending_balance, number_format($endingBalance->sum('dr_amount') + $old_ending_balance, 2, '.', ''));
				} else {
					array_push($ending_balance, number_format($endingBalance->sum('dr_amount'), 2, '.', ''));
				}

				$old_ending_balance = $endingBalance->sum('dr_amount');
			}

			foreach ($months as $m) {
				$income_dr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Sales')
						->orWhere('account_type', 'Other Income')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'dr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$income_cr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Sales')
						->orWhere('account_type', 'Other Income')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'cr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$income = $income_cr_amount->total - $income_dr_amount->total;
				array_push($cashflow_incomes, number_format($income, 2, '.', ''));
			}

			foreach ($months as $m) {
				$expense_dr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Other Expenses')
						->orWhere('account_type', 'Direct Expenses')
						->orWhere('account_type', 'Cost Of Sale')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'dr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$expense_cr_amount = Transaction::whereHas('account', function ($query) {
					$query->where('account_type', 'Other Expenses')
						->orWhere('account_type', 'Direct Expenses')
						->orWhere('account_type', 'Cost Of Sale')
						->where(function ($query) {
							$query->where('business_id', '=', request()->activeBusiness->id)
								->orwhere('business_id', '=', null);
						});
				})
					->selectRaw("SUM(transactions.base_currency_amount) as total")
					->where('dr_cr', 'cr')
					->whereMonth('trans_date', $m)
					->whereYear('trans_date', date('Y'))
					->first();

				$expense = $expense_dr_amount->total - $expense_cr_amount->total;
				array_push($cashflow_expenses, number_format($expense, 2, '.', ''));
			}

			$data['cashflow'] = array('cashflow_incomes' => $cashflow_incomes, 'cashflow_expenses' => $cashflow_expenses, 'ending_balance' => $ending_balance);

			// accounts receivable and payables widget

			$AccountsReceivable = Account::with('transactions')
				->where('account_name', '=', 'Accounts Receivable')
				->withSum(['transactions as dr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'dr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays($request->range));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'cr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays($request->range));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->first();

			$data['invoice'] = Invoice::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, sum(paid) as total_paid')
				->where('is_recurring', 0)
				->where('status', '!=', 0)
				->first();

			$AccountsPayable = Account::with('transactions')
				->where('account_name', '=', 'Accounts Payable')
				->withSum(['transactions as dr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'dr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays($request->range));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'cr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays($request->range));
						})
						->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->first();

			$data['purchase'] = Purchase::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, sum(paid) as total_paid')
				->where('status', '!=', 0)
				->first();

			$data['accounts'] = get_account_details();

			$data['AccountsReceivable'] = $AccountsReceivable?->dr_amount - $AccountsReceivable?->cr_amount;
			$data['AccountsPayable'] = $AccountsPayable?->cr_amount - $AccountsPayable?->dr_amount;

			$data['transactions'] = Transaction::whereHas('account', function ($query) {
				$query->where(function ($query) {
					$query->where('account_type', 'Cash')
						->orWhere('account_type', 'Bank');
				})->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				});
			})
				->orderBy('trans_date', 'desc')
				->limit(10)->get();

			if (request('isOwner') == true) {
				return view("backend.user.dashboard-user", $data);
			}
			return view("backend.user.dashboard-staff", $data);
		}
	}

	public function income_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function expense_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function accounts_receivable_amount_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function accounts_payable_amount_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function cashflow_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function top_customers_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function sales_overview_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function sales_value_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function recent_transaction_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function recent_invoices_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function receivables_vs_payables_widget()
	{
		// Use for Permission Only
		return redirect()->route('dashboard.index');
	}

	public function json_income_by_category()
	{
		$transactions = Transaction::selectRaw('transaction_category_id, ref_id, ref_type, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
			->with('category')
			->where('dr_cr', 'cr')
			->whereRaw('YEAR(trans_date) = ?', date('Y'))
			->groupBy('transaction_category_id', 'ref_type')
			->get();
		$category = array();
		$colors = array();
		$amounts = array();

		foreach ($transactions as $transaction) {
			array_push($category, $transaction->category->name);
			array_push($colors, $transaction->category->color);
			array_push($amounts, (float) $transaction->amount);
		}

		echo json_encode(array('amounts' => $amounts, 'category' => $category, 'colors' => $colors));
	}

	public function json_expense_by_category()
	{
		$transactions = Transaction::selectRaw('transaction_category_id, ref_id, ref_type, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
			->with('category')
			->where('dr_cr', 'dr')
			->whereRaw('YEAR(trans_date) = ?', date('Y'))
			->groupBy('transaction_category_id', 'ref_type')
			->get();

		$category = array();
		$colors = array();
		$amounts = array();

		foreach ($transactions as $transaction) {
			array_push($category, $transaction->category->name);
			array_push($colors, $transaction->category->color);
			array_push($amounts, (float) $transaction->amount);
		}

		echo json_encode(array('amounts' => $amounts, 'category' => $category, 'colors' => $colors));
	}

	public function json_cashflow()
	{
		$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
		$transactions = Transaction::selectRaw('MONTH(trans_date) as td, dr_cr, type, ROUND(IFNULL(SUM((transactions.amount/currency_rate) * 1),0),2) as amount')
			->whereRaw('YEAR(trans_date) = ?', date('Y'))
			->groupBy('td', 'type')
			->get();

		$deposit = array();
		$withdraw = array();

		foreach ($transactions as $transaction) {
			if ($transaction->type == 'income') {
				$deposit[$transaction->td] = $transaction->amount;
			} else if ($transaction->type == 'expense') {
				$withdraw[$transaction->td] = $transaction->amount;
			}
		}

		echo json_encode(array('month' => $months, 'deposit' => $deposit, 'withdraw' => $withdraw));
	}

	public function json_package_wise_subscription()
	{
		if (auth()->user()->user_type != 'admin') {
			return null;
		}
		$users = User::selectRaw('package_id, COUNT(id) as subscribed')
			->with('package')
			->where('user_type', 'user')
			->where('package_id', '!=', null)
			->groupBy('package_id')
			->get();

		$package = array();
		$colors = array();
		$subscribed = array();

		$flatColors = [
			"#1abc9c", "#2ecc71", "#3498db", "#9b59b6", "#34495e",
			"#16a085", "#27ae60", "#2980b9", "#8e44ad", "#2c3e50",
			"#f1c40f", "#e67e22", "#e74c3c", "#ecf0f1", "#95a5a6",
			"#f39c12", "#d35400", "#c0392b", "#bdc3c7", "#7f8c8d"
		];

		foreach ($users as $user) {
			array_push($package, $user->package->name . ' (' . ucwords($user->package->package_type) . ')');
			//array_push($colors, sprintf('#%06X', mt_rand(0, 0xFFFFFF)));
			$index = array_rand($flatColors, 1);
			array_push($colors, $flatColors[$index]);
			unset($flatColors[$index]);
			array_push($subscribed, (float) $user->subscribed);
		}

		echo json_encode(array('package' => $package, 'subscribed' => $subscribed, 'colors' => $colors));
	}

	public function json_yearly_reveneu()
	{
		$months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
		$subscriptionPayments = SubscriptionPayment::selectRaw('MONTH(created_at) as td, SUM(subscription_payments.amount) as amount')
			->whereRaw('YEAR(created_at) = ?', date('Y'))
			->groupBy('td')
			->get();

		$transactions = array();

		foreach ($subscriptionPayments as $subscriptionPayment) {
			$transactions[$subscriptionPayment->td] = $subscriptionPayment->amount;
		}

		echo json_encode(array('month' => $months, 'transactions' => $transactions));
	}
}
