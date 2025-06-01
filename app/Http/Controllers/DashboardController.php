<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\SubscriptionPayment;
use App\Models\Transaction;
use App\Models\User;
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

			$data['range'] = !intval($request->range) ? 'all' : $request->range;
			$data['custom'] = $request->custom;

			$start_date = $request->custom[0] ?? "";
			$end_date = $request->custom[1] ?? "";


			$income_dr_amount = Transaction::whereHas('account', function ($query) {
				$query->where('account_type', 'Sales')
					->orWhere('account_type', 'Other Income');
			})
				->selectRaw("SUM(transactions.base_currency_amount) as total")
				->where('dr_cr', 'dr')
				->when($request->range, function ($query) use ($request) {
					$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
				})
				->when($request->custom, function ($query) use ($start_date, $end_date) {
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
					$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
				})
				->when($request->custom, function ($query) use ($start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$data['current_month_income'] = floatval($income_cr_amount->total) - floatval($income_dr_amount->total);


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
					$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
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
					$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
				})
				->when($request->custom, function ($query) use ($request, $start_date, $end_date) {
					$query->whereDate('trans_date', '>=', $start_date)
						->whereDate('trans_date', '<=', $end_date);
				})
				->first();

			$data['current_month_expense'] = floatval($expense_dr_amount->total) - floatval($expense_cr_amount->total);

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

				$expense = floatval($expense_dr_amount->total) - floatval($expense_cr_amount->total);
				array_push($expenses, number_format($expense, 2, '.', ''));

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

				$income = floatval($income_cr_amount->total) - floatval($income_dr_amount->total);
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

			// Get products with their relationships and stock data
			$products = Product::with(['category', 'invoice_items.invoice'])
				->where('business_id', $request->activeBusiness->id)
				->get();

			// Get top selling products
			$top_selling_products = $products
				->map(function ($product) use ($start_date, $end_date) {
					$quantity_sold = $product->invoice_items
						->filter(function ($item) use ($start_date, $end_date) {
							$invoice_date = $item->invoice->invoice_date;
							return $invoice_date >= $start_date && $invoice_date <= $end_date;
						})
						->sum('quantity');

					return [
						'id' => $product->id,
						'name' => $product->name,
						'invoice_items_sum_quantity' => $quantity_sold,
						'stock' => $product->stock,
						'minimum_stock' => $product->minimum_stock ?? 10,
						'status' => $product->stock <= 0 ? 'Out of Stock' : ($product->stock <= ($product->minimum_stock ?? 10) ? 'Low Stock' : 'In Stock')
					];
				})
				->sortByDesc('invoice_items_sum_quantity')
				->take(5)
				->values()
				->toArray();

			// Get low stock products
			$low_stock_products = $products
				->filter(function ($product) {
					return $product->stock <= ($product->minimum_stock ?? 10);
				})
				->map(function ($product) {
					return [
						'id' => $product->id,
						'name' => $product->name,
						'stock' => $product->stock,
						'minimum_stock' => $product->minimum_stock ?? 10,
						'status' => $product->stock <= 0 ? 'Out of Stock' : 'Low Stock'
					];
				})
				->take(5)
				->values()
				->toArray();

			// Get category-wise stock data
			$category_stock = $products
				->groupBy(function ($product) {
					return $product->category ? $product->category->name : 'Uncategorized';
				})
				->map(function ($products, $category_name) use ($start_date, $end_date) {
					$total_sold = $products->sum(function ($product) use ($start_date, $end_date) {
						return $product->invoice_items
							->filter(function ($item) use ($start_date, $end_date) {
								$invoice_date = $item->invoice->invoice_date;
								return $invoice_date >= $start_date && $invoice_date <= $end_date;
							})
							->sum('quantity');
					});

					return [
						'name' => $category_name,
						'value' => $total_sold
					];
				})
				->sortByDesc('value')
				->take(10)
				->values()
				->toArray();

			$data['top_selling_products'] = $top_selling_products;
			$data['low_stock_products'] = $low_stock_products;
			$data['category_stock'] = $category_stock;

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
					array_push($receivables, number_format(floatval($receivable->total_dr) - floatval($receivable->total_cr), 2, '.', ''));
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
					array_push($payables, number_format(floatval($payable->total_cr) - floatval($payable->total_dr), 2, '.', ''));
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
						->orWhere('account_type', '=', 'Cash');
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
					array_push($ending_balance, number_format(floatval($endingBalance->sum('dr_amount')) + floatval($old_ending_balance), 2, '.', ''));
				} else {
					array_push($ending_balance, number_format(floatval($endingBalance->sum('dr_amount')), 2, '.', ''));
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

				$income = floatval($income_cr_amount->total) - floatval($income_dr_amount->total);
				array_push($cashflow_incomes, number_format($income, 2, '.', ''));

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

				$expense = floatval($expense_dr_amount->total) - floatval($expense_cr_amount->total);
				array_push($cashflow_expenses, number_format($expense, 2, '.', ''));
			}

			$data['cashflow'] = array('cashflow_incomes' => $cashflow_incomes, 'cashflow_expenses' => $cashflow_expenses, 'ending_balance' => $ending_balance);

			// accounts receivable and payables widget

			$AccountsReceivable = Account::with('transactions')
				->where('account_name', '=', 'Accounts Receivable')
				->withSum(['transactions as dr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'dr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'cr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
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
							$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->withSum(['transactions as cr_amount' => function ($query) use ($request, $start_date, $end_date) {
					$query->where('dr_cr', 'cr')
						->when($request->range, function ($query) use ($request) {
							$query->where('trans_date', '>=', now()->subDays(intval($request->range)));
						})
						->when($request->custom, function ($query) use ($start_date, $end_date) {
							$query->whereDate('trans_date', '>=', $start_date)
								->whereDate('trans_date', '<=', $end_date);
						});
				}], 'base_currency_amount')
				->first();

			$data['purchase'] = Purchase::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, sum(paid) as total_paid')
				->where('status', '!=', 0)
				->first();

			$data['accounts'] = get_account_details();

			$data['AccountsReceivable'] = floatval($AccountsReceivable?->dr_amount) - floatval($AccountsReceivable?->cr_amount);
			$data['AccountsPayable'] = floatval($AccountsPayable?->cr_amount) - floatval($AccountsPayable?->dr_amount);

			$data['recentTransactions'] = Transaction::whereHas('account', function ($query) {
				$query->where(function ($query) {
					$query->where('account_type', 'Cash')
						->orWhere('account_type', 'Bank');
				})->where(function ($query) {
					$query->where('business_id', '=', request()->activeBusiness->id)
						->orWhere('business_id', '=', null);
				});
			})
				->with('account')
				->orderBy('trans_date', 'desc')
				->limit(10)->get();

			if (request('isOwner') == true) {
				return Inertia::render("Backend/User/Dashboard-User", $data);
			}

			return Inertia::render("Backend/User/Dashboard-Staff", $data);
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
			"#1abc9c",
			"#2ecc71",
			"#3498db",
			"#9b59b6",
			"#34495e",
			"#16a085",
			"#27ae60",
			"#2980b9",
			"#8e44ad",
			"#2c3e50",
			"#f1c40f",
			"#e67e22",
			"#e74c3c",
			"#ecf0f1",
			"#95a5a6",
			"#f39c12",
			"#d35400",
			"#c0392b",
			"#bdc3c7",
			"#7f8c8d"
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

	public function inventory()
	{
		$range = request()->get('range', '30');
		$custom = request()->get('custom');
		$dashboard_type = 'inventory';

		// Get date range
		if ($range == 'all') {
			// Get the date of the first transaction
			$first_transaction = DB::table('invoices')
				->select('invoice_date')
				->union(DB::table('purchases')->select('purchase_date'))
				->orderBy('invoice_date', 'asc')
				->first();

			$start_date = $first_transaction ? $first_transaction->invoice_date : date('Y-m-d');
		} else {
			$start_date = $range == 'custom' ? $custom[0] : date('Y-m-d', strtotime("-$range days"));
		}
		$end_date = $range == 'custom' ? $custom[1] : date('Y-m-d');

		// Initialize stock movements with default values
		$stock_movements = [
			'sales' => 0,
			'purchases' => 0,
			'sales_returns' => 0,
			'purchase_returns' => 0
		];

		// Get products with stock management enabled
		$products = Product::where('stock_management', 1)
			->where('status', 1)
			->withSum(['invoice_items' => function ($query) use ($start_date, $end_date) {
				$query->whereHas('invoice', function ($q) use ($start_date, $end_date) {
					$q->whereBetween('invoice_date', [$start_date, $end_date]);
				});
			}], 'quantity')
			->withSum(['purchase_items' => function ($query) use ($start_date, $end_date) {
				$query->whereHas('purchase', function ($q) use ($start_date, $end_date) {
					$q->whereBetween('purchase_date', [$start_date, $end_date]);
				});
			}], 'quantity')
			->withSum(['sales_return_items' => function ($query) use ($start_date, $end_date) {
				$query->whereHas('sales_return', function ($q) use ($start_date, $end_date) {
					$q->whereBetween('return_date', [$start_date, $end_date]);
				});
			}], 'quantity')
			->withSum(['purchase_return_items' => function ($query) use ($start_date, $end_date) {
				$query->whereHas('purchase_return', function ($q) use ($start_date, $end_date) {
					$q->whereBetween('return_date', [$start_date, $end_date]);
				});
			}], 'quantity')
			->get();

		// Initialize variables with default values
		$date_aggregation = [
			'type' => 'daily',
			'format' => 'd M Y'
		];
		$total_products = 0;
		$low_stock_products = 0;
		$out_of_stock_products = 0;
		$inventory_value = 0;
		$stock_in = 0;
		$stock_out = 0;
		$closing_stock = 0;
		$stock_turnover_rate = 0;
		$daily_stock_movements = [];
		$category_stock = collect();
		$top_selling_products = collect();
		$most_purchased_products = collect();

		if ($products->isNotEmpty()) {
			// Calculate inventory statistics
			$total_products = $products->count();
			$low_stock_products = $products->where('stock', '<=', 10)->count();
			$out_of_stock_products = $products->where('stock', '<=', 0)->count();

			// Calculate total inventory value
			$inventory_value = $products->sum(function ($product) {
				return $product->stock * $product->purchase_cost;
			});

			// Calculate Stock In and Stock Out
			$stock_movements['sales'] = $products->sum('invoice_items_sum_quantity') ?? 0;
			$stock_movements['purchases'] = $products->sum('purchase_items_sum_quantity') ?? 0;
			$stock_movements['sales_returns'] = $products->sum('sales_return_items_sum_quantity') ?? 0;
			$stock_movements['purchase_returns'] = $products->sum('purchase_return_items_sum_quantity') ?? 0;

			$stock_in = $stock_movements['purchases'] + $stock_movements['sales_returns'];
			$stock_out = $stock_movements['sales'] + $stock_movements['purchase_returns'];

			// Calculate Closing Stock
			$closing_stock = $products->sum('stock');

			// Calculate Average Inventory
			$average_inventory = $closing_stock;

			// Calculate Stock Turnover Rate
			$stock_turnover_rate = $average_inventory > 0 ? ($stock_out / $average_inventory) * 100 : 0;

			// Get category-wise stock data
			$category_stock = $products->groupBy('category_id')
				->map(function ($items) {
					return [
						'name' => $items->first()->category->name ?? 'Uncategorized',
						'value' => $items->sum('invoice_items_sum_quantity') ?? 0
					];
				})
				->sortByDesc('value')
				->values()
				->take(10);

			// Get top selling products
			$top_selling_products = $products->sortByDesc('invoice_items_sum_quantity')
				->take(5)
				->map(function ($product) {
					return [
						'id' => $product->id,
						'name' => $product->name,
						'stock' => $product->stock,
						'minimum_stock' => $product->minimum_stock ?? 10,
						'status' => $product->stock <= 0 ? 'Out of Stock' : ($product->stock <= 10 ? 'Low Stock' : 'In Stock')
					];
				})
				->values()
				->toArray();

			// Get most purchased products
			$most_purchased_products = $products->sortByDesc('purchase_items_sum_quantity')->take(5);

			// Determine aggregation period based on date range
			$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
			$group_by = 'DATE(date)';
			$date_format = 'Y-m-d';

			if ($days_diff > 365) {
				$group_by = "DATE_FORMAT(date, '%Y-%m')";
				$date_format = 'Y-m';
			} elseif ($days_diff > 30) {
				$group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
				$date_format = 'Y-m-d';
				$aggregate_weekly = true;
			}

			// Optimize daily stock movements query using a single query with dynamic grouping
			$movements = DB::select(
				"
                SELECT {$group_by} as date, movement_type, SUM(quantity) as total_quantity
                FROM (
                    -- Purchases (in)
                    SELECT purchase_date as date, 'in' as movement_type, purchase_items.quantity
                    FROM purchase_items
                    JOIN purchases ON purchases.id = purchase_items.purchase_id
                    JOIN products ON products.id = purchase_items.product_id
                    WHERE products.stock_management = 1
                    AND products.status = 1
                    AND purchases.purchase_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    -- Sales Returns (in)
                    SELECT return_date as date, 'in' as movement_type, sales_return_items.quantity
                    FROM sales_return_items
                    JOIN sales_returns ON sales_returns.id = sales_return_items.sales_return_id
                    JOIN products ON products.id = sales_return_items.product_id
                    WHERE products.stock_management = 1
                    AND products.status = 1
                    AND sales_returns.return_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    -- Sales (out)
                    SELECT invoice_date as date, 'out' as movement_type, invoice_items.quantity
                    FROM invoice_items
                    JOIN invoices ON invoices.id = invoice_items.invoice_id
                    JOIN products ON products.id = invoice_items.product_id
                    WHERE products.stock_management = 1
                    AND products.status = 1
                    AND invoices.invoice_date BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    -- Purchase Returns (out)
                    SELECT return_date as date, 'out' as movement_type, purchase_return_items.quantity
                    FROM purchase_return_items
                    JOIN purchase_returns ON purchase_returns.id = purchase_return_items.purchase_return_id
                    JOIN products ON products.id = purchase_return_items.product_id
                    WHERE products.stock_management = 1
                    AND products.status = 1
                    AND purchase_returns.return_date BETWEEN ? AND ?
                ) as combined_movements
                GROUP BY date, movement_type
                ORDER BY date",
				[$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
			);

			// Initialize and fill daily movements array
			$daily_stock_movements = [];

			// Only create entries for dates that have movements
			foreach ($movements as $movement) {
				$date = $movement->date;
				if (!isset($daily_stock_movements[$date])) {
					$daily_stock_movements[$date] = ['in' => 0, 'out' => 0];
				}
				if ($movement->movement_type === 'in') {
					$daily_stock_movements[$date]['in'] += $movement->total_quantity;
				} else {
					$daily_stock_movements[$date]['out'] += $movement->total_quantity;
				}
			}

			// If weekly aggregation is needed (for 30-60 days range)
			if (isset($aggregate_weekly) && $aggregate_weekly) {
				$weekly_movements = [];
				foreach ($daily_stock_movements as $date => $movements) {
					$week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
					if (!isset($weekly_movements[$week_start])) {
						$weekly_movements[$week_start] = ['in' => 0, 'out' => 0];
					}
					$weekly_movements[$week_start]['in'] += $movements['in'];
					$weekly_movements[$week_start]['out'] += $movements['out'];
				}
				$daily_stock_movements = $weekly_movements;
			}

			// Sort by date
			ksort($daily_stock_movements);

			// Add aggregation info to be used by the view
			$date_aggregation = [
				'type' => $days_diff > 365 ? 'monthly' : ($days_diff > 30 ? 'weekly' : 'daily'),
				'format' => $date_format
			];
		}

		return Inertia::render("Backend/User/Dashboard-Inventory", compact(
			'range',
			'custom',
			'dashboard_type',
			'total_products',
			'low_stock_products',
			'out_of_stock_products',
			'inventory_value',
			'top_selling_products',
			'most_purchased_products',
			'stock_movements',
			'stock_in',
			'stock_out',
			'closing_stock',
			'stock_turnover_rate',
			'daily_stock_movements',
			'category_stock',
			'products',
			'date_aggregation',
		));
	}
}
