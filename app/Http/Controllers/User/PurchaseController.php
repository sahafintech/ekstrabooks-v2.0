<?php

namespace App\Http\Controllers\User;

use App\Exports\BillInvoiceExport;
use App\Http\Controllers\Controller;
use App\Imports\BillInvoiceImport;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BillPayment;
use App\Models\BusinessSetting;
use App\Models\CostCode;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\PurchasePayment;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Notifications\SendBillInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class PurchaseController extends Controller
{

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$search = $request->get('search', '');
		$perPage = $request->get('per_page', 50);
		$sorting = $request->get('sorting', []);
		$sortColumn = $sorting['column'] ?? 'id';
		$sortDirection = $sorting['direction'] ?? 'desc';
		$vendorId = $request->get('vendor_id', '');
		$dateRange = $request->get('date_range', '');
		$approvalStatus = $request->get('approval_status', '');
		$status = $request->get('status', '');

		$query = Purchase::query()
			->where('cash', 0);

		// Handle sorting
		if ($sortColumn === 'vendor.name') {
			$query->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
				->orderBy('vendors.name', $sortDirection)
				->select('purchases.*');
		} else {
			$query->orderBy('purchases.' . $sortColumn, $sortDirection);
		}

		// Apply filters
		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('purchases.bill_no', 'like', "%$search%")
					->orWhere('purchases.title', 'like', "%$search%")
					->orWhere('purchases.purchase_date', 'like', "%$search%")
					->orWhere('purchases.due_date', 'like', "%$search%")
					->orWhere('purchases.grand_total', 'like', "%$search%")
					->orWhere('purchases.paid', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		if ($vendorId) {
			$query->where('vendor_id', $vendorId);
		}

		if ($dateRange) {
			$query->whereDate('purchase_date', '>=', Carbon::parse($dateRange[0])->format('Y-m-d'))
				->whereDate('purchase_date', '<=', Carbon::parse($dateRange[1])->format('Y-m-d'));
		}

		if ($approvalStatus) {
			$query->where('approval_status', $approvalStatus);
		}

		if ($status) {
			$query->where('status', $status);
		}

		// Get summary data before pagination
		$summaryQuery = clone $query;
		$summary = [
			'total_bills' => $summaryQuery->count(),
			'total_amount' => $summaryQuery->sum('grand_total'),
			'total_paid' => $summaryQuery->sum('paid'),
		];

		$bills = $query->with('vendor')->paginate($perPage)->withQueryString();
		$vendors = Vendor::all();

		return Inertia::render('Backend/User/Bill/List', [
			'bills' => $bills->items(),
			'meta' => [
				'current_page' => $bills->currentPage(),
				'per_page' => $bills->perPage(),
				'last_page' => $bills->lastPage(),
				'total' => $bills->total(),
			],
			'filters' => [
				'search' => $search,
				'vendor_id' => $vendorId,
				'date_range' => $dateRange,
				'approval_status' => $approvalStatus,
				'status' => $status,
				'sorting' => $sorting,
			],
			'vendors' => $vendors,
			'summary' => $summary,
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		$vendors = Vendor::where('business_id', $request->activeBusiness->id)
			->orderBy('id', 'desc')
			->get();
		$products = Product::where('business_id', $request->activeBusiness->id)
			->orderBy('id', 'desc')
			->get();
		$currencies = Currency::orderBy('id', 'desc')
			->get();
		$taxes = Tax::orderBy('id', 'desc')
			->get();
		$accounts = Account::orderBy('id', 'desc')
			->get();
		$purchase_title = get_business_option('purchase_title', 'Bill Invoice');
		$inventory = Account::where('account_name', 'Inventory')->first();
		$projects = Project::orderBy('id', 'desc')
			->with('tasks')
			->get();
		$cost_codes = CostCode::orderBy('id', 'desc')
			->get();

		return Inertia::render('Backend/User/Bill/Create', [
			'vendors' => $vendors,
			'products' => $products,
			'currencies' => $currencies,
			'taxes' => $taxes,
			'accounts' => $accounts,
			'purchase_title' => $purchase_title,
			'inventory' => $inventory,
			'base_currency' => get_business_option('currency'),
			'projects' => $projects,
			'cost_codes' => $cost_codes,
			'construction_module' => package()->construction_module,
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'vendor_id' => 'required',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'due_date' => 'required|after_or_equal:purchase_date',
			'product_name' => 'required',
			'currency'  =>  'required',
		], [
			'product_id.required' => _lang('You must add at least one item'),
		]);

		if ($validator->fails()) {
			return redirect()->route('bill_invoices.create')
				->withErrors($validator)
				->withInput();
		}

		// if quantity is less than 1 or null then return with error
		if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
			return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
		}

		// if unit cost is less than 0 or null then return with error
		if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
			return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
		}

		$default_accounts = ['Accounts Payable', 'Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

		// if these accounts are not exists then create it
		foreach ($default_accounts as $account) {
			if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
				$account_obj = new Account();
				if ($account == 'Accounts Payable') {
					$account_obj->account_code = '2100';
				} elseif ($account == 'Purchase Tax Payable') {
					$account_obj->account_code = '2201';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->account_code = '6003';
				} elseif ($account == 'Inventory') {
					$account_obj->account_code = '1000';
				}
				$account_obj->account_name = $account;
				if ($account == 'Accounts Payable') {
					$account_obj->account_type = 'Current Liability';
				} elseif ($account == 'Purchase Tax Payable') {
					$account_obj->account_type = 'Current Liability';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->account_type = 'Cost Of Sale';
				} elseif ($account == 'Inventory') {
					$account_obj->account_type = 'Other Current Asset';
				}
				if ($account == 'Accounts Payable') {
					$account_obj->dr_cr   = 'cr';
				} elseif ($account == 'Purchase Tax Payable') {
					$account_obj->dr_cr   = 'dr';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->dr_cr   = 'cr';
				} elseif ($account == 'Inventory') {
					$account_obj->dr_cr   = 'dr';
				}
				$account_obj->business_id = $request->activeBusiness->id;
				$account_obj->user_id     = $request->activeBusiness->user->id;
				$account_obj->opening_date   = now()->format('Y-m-d');
				$account_obj->save();
			}
		}

		$month = Carbon::parse($request->purchase_date)->format('F');
		$year = Carbon::parse($request->purchase_date)->format('Y');
		$today = now()->format('d');

		// financial year
		$financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
		$end_month = explode(',', $financial_year)[1];
		$start_day = BusinessSetting::where('name', 'start_day')->first()->value;
		$end_day = $start_day + 5;

		// if login as this user dont check the financial year
		if (false) {
			if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
				return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
			}
		}

		DB::beginTransaction();

		$summary = $this->calculateTotal($request);

		$purchase = new Purchase();
		$purchase->vendor_id = $request->input('vendor_id');
		$purchase->title = $request->input('title');
		$purchase->bill_no = get_business_option('purchase_number');
		$purchase->po_so_number = $request->input('po_so_number');
		$purchase->purchase_date = Carbon::parse($request->input('purchase_date'))->format('Y-m-d');
		$purchase->due_date = Carbon::parse($request->input('due_date'))->format('Y-m-d');
		$purchase->sub_total = $summary['subTotal'];
		$purchase->grand_total = $summary['grandTotal'];
		$purchase->converted_total = $request->input('converted_total');
		$purchase->exchange_rate   = $request->input('exchange_rate');
		$purchase->currency   = $request->input('currency');
		$purchase->paid = 0;
		$purchase->discount = $summary['discountAmount'];
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->withholding_tax = $request->input('withholding_tax') ?? 0;
		$purchase->footer = $request->input('footer');
		if (has_permission('bill_invoices.approve') || request()->isOwner) {
			$purchase->approval_status = 1;
		} else {
			$purchase->approval_status = 0;
		}
		$purchase->created_by = auth()->user()->id;
		if (has_permission('bill_invoices.approve') || request()->isOwner) {
			$purchase->approved_by = auth()->user()->id;
		} else {
			$purchase->approved_by = null;
		}
		$purchase->short_code = rand(100000, 9999999) . uniqid();

		$purchase->save();


		// if attachments then upload
		if (isset($request->attachments)) {
			if ($request->attachments != null) {
				for ($i = 0; $i < count($request->attachments); $i++) {
					$theFile = $request->file("attachments.$i.file");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]['file_name'];
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'bill invoice';
					$attachment->ref_id = $purchase->id;
					$attachment->save();
				}
			}
		}

		$currentTime = Carbon::now();

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseItem([
				'purchase_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => isset($request->description[$i]) ? $request->description[$i] : null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
				'project_id' => isset($request->project_id[$i]) ? $request->project_id[$i] : null,
				'project_task_id' => isset($request->project_task_id[$i]) ? $request->project_task_id[$i] : null,
				'cost_code_id' => isset($request->cost_code_id[$i]) ? $request->cost_code_id[$i] : null,
			]));

			if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
				$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
				if ($projectBudget) {
					$projectBudget->actual_budget_quantity += $purchaseItem->quantity;
					$projectBudget->actual_budget_amount += $purchaseItem->sub_total;
					$projectBudget->save();
				}
			}

			if (isset($request->taxes)) {
				foreach ($request->taxes as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseItemTax([
						'purchase_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));

					if (has_permission('bill_invoices.approve') || request()->isOwner) {
						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						} else {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'dr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					} else {
						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new PendingTransaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						} else {
							$transaction              = new PendingTransaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'dr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('bill_invoices.approve') || request()->isOwner) {

				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = Account::find($request->input('account_id')[$i])->dr_cr;
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				} else {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				}
			} else {
				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new PendingTransaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = Account::find($request->input('account_id')[$i])->dr_cr;
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				} else {
					$transaction              = new PendingTransaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				}
			}

			// update stock
			if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
				$purchaseItem->product->stock = $purchaseItem->product->stock + $request->quantity[$i];
				$purchaseItem->product->save();
			}
		}

		//Increment Bill Number
		BusinessSetting::where('name', 'purchase_number')->increment('value');

		DB::commit();

		if (has_permission('bill_invoices.approve') || request()->isOwner) {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->project_task_id = $purchase->items->first()->project_task_id;
				$transaction->cost_code_id = $purchase->items->first()->cost_code_id;
				$transaction->save();
			} else {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchaseItem->first()->project_id;
				$transaction->save();
			}

			if ($request->input('discount_value') > 0) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id  = get_account('Purchase Discount Allowed')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate           = $purchase->exchange_rate;
				$transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
				$transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
				$transaction->ref_id      = $purchase->id;
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}
		} else {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			} else {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}

			if ($request->input('discount_value') > 0) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id  = get_account('Purchase Discount Allowed')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate           = $purchase->exchange_rate;
				$transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
				$transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
				$transaction->ref_id      = $purchase->id;
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}
		}

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Bill Invoice Updated' . ' ' . $purchase->bill_no;
		$audit->save();


		return redirect()->route('bill_invoices.show', $purchase->id)->with('success', _lang('Updated Successfully'));
	}

	/** Duplicate Invoice */
	public function duplicate($id)
	{
		DB::beginTransaction();
		$purchase = Purchase::find($id);
		$newPurchase = $purchase->replicate();
		$newPurchase->status = 0;
		$newPurchase->paid = 0;
		$newPurchase->short_code = rand(100000, 9999999) . uniqid();
		$newPurchase->save();

		foreach ($purchase->items as $purchaseItem) {
			$newPurchaseItem = $purchaseItem->replicate();
			$newPurchaseItem->purchase_id = $newPurchase->id;
			$newPurchaseItem->save();

			foreach ($purchaseItem->taxes as $PurchaseItemTax) {
				$newPurchaseItemTax = $PurchaseItemTax->replicate();
				$newPurchaseItemTax->purchase_id = $newPurchase->id;
				$newPurchaseItemTax->purchase_item_id = $newPurchaseItem->id;
				$newPurchaseItemTax->save();
			}

			//Update Stock
			$product = $purchaseItem->product;
			if ($product->type == 'product' && $product->stock_management == 1) {
				$product->stock = $product->stock + $newPurchaseItem->quantity;
				$product->save();
			}
		}

		DB::commit();

		return redirect()->route('bill_invoices.edit', $newPurchase->id);
	}

	public function pay_bill(Request $request)
	{
		if ($request->isMethod('get')) {
			return view('backend.user.purchase.modal.add-payment');
		} else if ($request->isMethod('post')) {
			$validator = Validator::make($request->all(), [
				'trans_date' => 'required',
				'account_id' => 'required',
				'method' => 'required',
				'amount' => 'required|numeric',
				'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
			]);

			if ($validator->fails()) {
				if ($request->ajax()) {
					return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
				}
			}

			$attachment = '';
			if ($request->hasfile('attachment')) {
				$file = $request->file('attachment');
				$attachment = rand() . time() . $file->getClientOriginalName();
				$file->move(public_path() . "/uploads/media/", $attachment);
			}

			$currentTime = Carbon::now();

			for ($i = 0; $i < count($request->invoices); $i++) {
				DB::beginTransaction();

				$purchase = Purchase::find($request->invoices[$i]);
				$account = Account::find($request->account_id);

				$refAmount = convert_currency($account->currency, $request->activeBusiness->currency, $request->amount[$request->invoices[$i]]);

				if ($refAmount > ($purchase->grand_total - $purchase->paid)) {
					return response()->json(['result' => 'error', 'message' => _lang('Amount must be equal or less than due amount')]);
				}

				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id  = $request->account_id;
				$transaction->transaction_method      = $request->method;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
				$transaction->transaction_currency    = $purchase->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
				$transaction->ref_amount  = $refAmount;
				$transaction->reference   = $request->reference;
				$transaction->description = 'Bill Invoice Payment' . ' #' . $request->invoices[$i];
				$transaction->attachment  = $attachment;
				$transaction->ref_id      = $request->invoices[$i];
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->vendor_id = $request->vendor_id;

				$transaction->save();

				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'dr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]);
				$transaction->transaction_currency    = $purchase->currency;
				$transaction->currency_rate           = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->amount[$request->invoices[$i]]));
				$transaction->reference   = $request->reference;
				$transaction->description = 'Bill Invoice Payment' . ' #' . $request->invoices[$i];
				$transaction->attachment  = $attachment;
				$transaction->ref_id      = $request->invoices[$i];
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;

				$transaction->save();

				$purchase->paid = $purchase->paid + $transaction->ref_amount;
				$purchase->status = 1; //Partially Paid
				if ($purchase->paid >= $purchase->grand_total) {
					$purchase->status = 2; //Paid
				}
				$purchase->save();

				DB::commit();
			}

			return redirect('user/bill_invoices/pay_bill');
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		$purchase = Purchase::find($id);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Bill Deleted' . ' ' . $purchase->bill_no;
		$audit->save();

		// descrease stock
		foreach ($purchase->items as $purchaseItem) {
			$product = $purchaseItem->product;
			if ($product->type == 'product' && $product->stock_management == 1) {
				$product->stock = $product->stock - $purchaseItem->quantity;
				$product->save();
			}

			if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
				$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
				if ($projectBudget) {
					$projectBudget->actual_budget_quantity -= $purchaseItem->quantity;
					$projectBudget->actual_budget_amount -= $purchaseItem->sub_total;
					$projectBudget->save();
				}
			}
		}
		// delete transactions
		$transactions = Transaction::where('ref_id', $purchase->id)
			->where(function ($query) {
				$query->where('ref_type', 'bill invoice')
					->orWhere('ref_type', 'bill invoice tax');
			})
			->get();

		foreach ($transactions as $transaction) {
			$transaction->delete();
		}

		$purchase_payments = PurchasePayment::where('purchase_id', $purchase->id)->get();

		foreach ($purchase_payments as $purchase_payment) {
			$bill_payment = BillPayment::find($purchase_payment->payment_id);
			if ($bill_payment) {
				$bill_payment->amount = $bill_payment->amount - $purchase_payment->amount;
				$bill_payment->save();

				if ($bill_payment->amount == 0) {
					$bill_payment->delete();
				}

				// delete transactions
				$transactions = Transaction::where('ref_id', $purchase->id . ',' . $bill_payment->id)->where('ref_type', 'bill payment')->get();
				foreach ($transactions as $transaction) {
					$transaction->delete();
				}
			}
			$purchase_payment->delete();

			if ($bill_payment->purchases == null) {
				$bill_payment->delete();
			}
		}

		// delete attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')->get();
		foreach ($attachments as $attachment) {
			$filePath = public_path($attachment->path);
			if (file_exists($filePath)) {
				unlink($filePath);
			}
			$attachment->delete();
		}

		$purchase->delete();
		return redirect()->route('bill_invoices.index')->with('success', _lang('Deleted Successfully'));
	}

	public function bulk_destroy(Request $request)
	{
		foreach ($request->ids as $id) {
			$purchase = Purchase::find($id);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Bill Deleted' . ' ' . $purchase->bill_no;
			$audit->save();

			// descrease stock
			foreach ($purchase->items as $purchaseItem) {
				$product = $purchaseItem->product;
				if ($product->type == 'product' && $product->stock_management == 1) {
					$product->stock = $product->stock - $purchaseItem->quantity;
					$product->save();
				}

				if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
					$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
					if ($projectBudget) {
						$projectBudget->actual_budget_quantity -= $purchaseItem->quantity;
						$projectBudget->actual_budget_amount -= $purchaseItem->sub_total;
						$projectBudget->save();
					}
				}
			}
			// delete transactions
			$transactions = Transaction::where('ref_id', $purchase->id)
				->where(function ($query) {
					$query->where('ref_type', 'bill invoice')
						->orWhere('ref_type', 'bill invoice tax');
				})
				->get();

			foreach ($transactions as $transaction) {
				$transaction->delete();
			}

			$purchase_payments = PurchasePayment::where('purchase_id', $purchase->id)->get();

			foreach ($purchase_payments as $purchase_payment) {
				$bill_payment = BillPayment::find($purchase_payment->payment_id);
				if ($bill_payment) {
					$bill_payment->amount = $bill_payment->amount - $purchase_payment->amount;
					$bill_payment->save();

					if ($bill_payment->amount == 0) {
						$bill_payment->delete();
					}

					// delete transactions
					$transactions = Transaction::where('ref_id', $purchase->id . ',' . $bill_payment->id)->where('ref_type', 'bill payment')->get();
					foreach ($transactions as $transaction) {
						$transaction->delete();
					}
				}
				$purchase_payment->delete();

				if ($bill_payment->purchases == null) {
					$bill_payment->delete();
				}
			}

			// delete attachments
			$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')->get();
			foreach ($attachments as $attachment) {
				$filePath = public_path($attachment->path);
				if (file_exists($filePath)) {
					unlink($filePath);
				}
				$attachment->delete();
			}

			$purchase->delete();
		}

		return redirect()->route('bill_invoices.index')->with('success', _lang('Deleted Successfully'));
	}

	private function calculateTotal(Request $request)
	{
		$subTotal = 0;
		$taxAmount = 0;
		$discountAmount = 0;
		$grandTotal = 0;

		for ($i = 0; $i < count($request->account_id); $i++) {
			//Calculate Sub Total
			$line_qnt = $request->quantity[$i];
			$line_unit_cost = $request->unit_cost[$i];
			$line_total = ($line_qnt * $line_unit_cost);

			//Show Sub Total
			$subTotal = ($subTotal + $line_total);

			//Calculate Taxes
			if (isset($request->taxes)) {
				for ($j = 0; $j < count($request->taxes); $j++) {
					$taxId = $request->taxes[$j];
					$tax = Tax::find($taxId);
					$product_tax = ($line_total / 100) * $tax->rate;
					$taxAmount += $product_tax;
				}
			}

			//Calculate Discount
			if ($request->discount_type == '0') {
				$discountAmount = ($subTotal / 100) * $request->discount_value ?? 0;
			} else if ($request->discount_type == '1') {
				$discountAmount = $request->discount_value ?? 0;
			}
		}

		//Calculate Grand Total
		$grandTotal = ($subTotal + $taxAmount) - $discountAmount;

		return array(
			'subTotal' => $subTotal / $request->exchange_rate,
			'taxAmount' => $taxAmount / $request->exchange_rate,
			'discountAmount' => $discountAmount / $request->exchange_rate,
			'grandTotal' => $grandTotal / $request->exchange_rate,
		);
	}

	public function get_bills(Request $request)
	{
		return Vendor::where('id', $request->id)
			->with(['purchases' => function ($query) {
				$query->where('status', 0)->orWhere('status', 1)
					->where('approval_status', 1)
					->where('cash', 0)
					->where('order', 0);
			}])
			->first();
	}

	public function show($id)
	{
		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);
		$attachments = Attachment::where('ref_type', 'bill invoice')->where('ref_id', $id)->get();
		$email_templates = EmailTemplate::whereIn('slug', ['NEW_BILL_CREATED'])
			->where('email_status', 1)
			->get();

		return Inertia::render('Backend/User/Bill/View', [
			'bill' => $bill,
			'attachments' => $attachments,
			'email_templates' => $email_templates,
		]);
	}

	public function show_public_bill_invoice($short_code)
	{
		$bill   = Purchase::withoutGlobalScopes()->with(['vendor', 'business', 'items', 'taxes'])
			->where('short_code', $short_code)
			->first();

		$request = request();
		// add activeBusiness object to request
		$request->merge(['activeBusiness' => $bill->business]);

		return Inertia::render('Backend/User/Bill/PublicView', [
			'bill' => $bill,
		]);
	}

	public function edit($id)
	{
		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);

		if (!has_permission('bill_invoices.approve') && !request()->isOwner && $bill->approval_status == 1) {
			return back()->with('error', _lang('Permission denied'));
		}

		$theAttachments = Attachment::where('ref_id', $id)->where('ref_type', 'bill invoice')->get();
		$accounts = Account::all();
		$currencies = Currency::all();
		$vendors = Vendor::all();
		$products = Product::all();
		$taxes = Tax::all();
		$inventory = Account::where('account_name', 'Inventory')->first();
		$taxIds = $bill->taxes
			->pluck('tax_id')
			->map(fn($id) => (string) $id)
			->toArray();
		$projects = Project::orderBy('id', 'desc')
			->with('tasks')
			->get();
		$cost_codes = CostCode::orderBy('id', 'desc')
			->get();

		return Inertia::render('Backend/User/Bill/Edit', [
			'bill' => $bill,
			'theAttachments' => $theAttachments,
			'accounts' => $accounts,
			'currencies' => $currencies,
			'vendors' => $vendors,
			'products' => $products,
			'taxes' => $taxes,
			'inventory' => $inventory,
			'taxIds' => $taxIds,
			'projects' => $projects,
			'cost_codes' => $cost_codes,
			'construction_module' => package()->construction_module,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$validator = Validator::make($request->all(), [
			'vendor_id' => 'required',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'due_date' => 'required|after_or_equal:purchase_date',
			'product_name' => 'required',
			'currency'  =>  'required',
		], [
			'product_id.required' => _lang('You must add at least one item'),
		]);

		if ($validator->fails()) {
			return redirect()->route('bill_invoices.edit', $id)
				->withErrors($validator)
				->withInput();
		}

		// if quantity is less than 1 or null then return with error
		if (in_array(null, $request->quantity) || in_array('', $request->quantity) || in_array(0, $request->quantity)) {
			return redirect()->back()->withInput()->with('error', _lang('Quantity is required'));
		}

		// if unit cost is less than 0 or null then return with error
		if (in_array(null, $request->unit_cost) || in_array('', $request->unit_cost)) {
			return redirect()->back()->withInput()->with('error', _lang('Unit Cost is required'));
		}

		$default_accounts = ['Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

		// if these accounts are not exists then create it
		foreach ($default_accounts as $account) {
			if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
				$account_obj = new Account();
				if ($account == 'Purchase Tax Payable') {
					$account_obj->account_code = '2201';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->account_code = '6003';
				} elseif ($account == 'Inventory') {
					$account_obj->account_code = '1000';
				}
				$account_obj->account_name = $account;
				if ($account == 'Purchase Tax Payable') {
					$account_obj->account_type = 'Current Liability';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->account_type = 'Cost Of Sale';
				} elseif ($account == 'Inventory') {
					$account_obj->account_type = 'Other Current Asset';
				}
				if ($account == 'Purchase Tax Payable') {
					$account_obj->dr_cr   = 'dr';
				} elseif ($account == 'Purchase Discount Allowed') {
					$account_obj->dr_cr   = 'cr';
				} elseif ($account == 'Inventory') {
					$account_obj->dr_cr   = 'dr';
				}
				$account_obj->business_id = $request->activeBusiness->id;
				$account_obj->user_id     = $request->activeBusiness->user->id;
				$account_obj->opening_date   = now()->format('Y-m-d');
				$account_obj->save();
			}
		}

		$month = Carbon::parse($request->purchase_date)->format('F');
		$year = Carbon::parse($request->purchase_date)->format('Y');
		$today = now()->format('d');

		// financial year
		$financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
		$end_month = explode(',', $financial_year)[1];
		$start_day = BusinessSetting::where('name', 'start_day')->first()->value;
		$end_day = $start_day + 5;

		// if login as this user dont check the financial year
		if (false) {
			if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
				return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
			}
		}

		DB::beginTransaction();

		$summary = $this->calculateTotal($request);

		$purchase = Purchase::where('id', $id)
			->first();
		$purchase->vendor_id = $request->input('vendor_id') ?? null;
		$purchase->title = $request->input('title');
		$purchase->po_so_number = $request->input('po_so_number');
		if ($purchase->bill_no == null) {
			$purchase->bill_no = get_business_option('purchase_number');
		}
		$purchase->purchase_date = Carbon::parse($request->input('purchase_date'))->format('Y-m-d');
		$purchase->due_date = Carbon::parse($request->input('purchase_date'))->format('Y-m-d');
		$purchase->sub_total = $summary['subTotal'];
		$purchase->grand_total = $summary['grandTotal'];
		$purchase->converted_total = $request->input('converted_total');
		$purchase->exchange_rate   = $request->input('exchange_rate');
		$purchase->currency   = $request->input('currency');
		$purchase->discount = $summary['discountAmount'];
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->withholding_tax = $request->input('withholding_tax') ?? 0;
		$purchase->benificiary = $request->input('benificiary');
		$purchase->footer = $request->input('footer');
		$purchase->save();

		// delete old attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')->get(); // Get attachments from the database

		if (isset($request->attachments)) {
			$incomingFiles = collect($request->attachments)->pluck('file')->toArray();

			foreach ($attachments as $attachment) {
				if (!in_array($attachment->path, $incomingFiles)) {
					$filePath = public_path($attachment->path);
					if (file_exists($filePath)) {
						unlink($filePath); // Delete the file
					}
					$attachment->delete(); // Delete the database record
				}
			}
		}

		// if attachments then upload
		if (isset($request->attachments)) {
			if ($request->attachments != null) {
				for ($i = 0; $i < count($request->attachments); $i++) {
					$theFile = $request->file("attachments.$i.file");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]['file_name'];
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'bill invoice';
					$attachment->ref_id = $purchase->id;
					$attachment->save();
				}
			}
		}

		//Update Invoice item
		foreach ($purchase->items as $purchase_item) {
			$product = $purchase_item->product;
			if ($product->type == 'product' && $product->stock_management == 1) {
				$product->stock = $product->stock - $purchase_item->quantity;
				$product->save();
			}

			if ($purchase_item->project_id && $purchase_item->project_task_id && $purchase_item->cost_code_id) {
				$projectBudget = ProjectBudget::where('project_id', $purchase_item->project_id)->where('project_task_id', $purchase_item->project_task_id)->where('cost_code_id', $purchase_item->cost_code_id)->first();
				if ($projectBudget) {
					$projectBudget->actual_budget_quantity -= $purchase_item->quantity;
					$projectBudget->actual_budget_amount -= $purchase_item->sub_total;
					$projectBudget->save();
				}
			}

			$purchase_item->delete();

			// delete transaction
			$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')
				->where('account_id', $purchase_item->account_id)
				->first();

			if ($transaction != null) {
				$transaction->delete();
			}

			// delete pending transaction
			$pending_transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')
				->where('account_id', $purchase_item->account_id)
				->first();

			if ($pending_transaction != null) {
				$pending_transaction->delete();
			}
		}

		$currentTime = Carbon::now();

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseItem([
				'purchase_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => isset($request->description[$i]) ? $request->description[$i] : null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
				'project_id' => $request->project_id[$i],
				'project_task_id' => $request->project_task_id[$i],
				'cost_code_id' => $request->cost_code_id[$i],
			]));

			if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
				$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
				if ($projectBudget) {
					$projectBudget->actual_budget_quantity += $purchaseItem->quantity;
					$projectBudget->actual_budget_amount += $purchaseItem->sub_total;
					$projectBudget->save();
				}
			}

			if (isset($request->taxes)) {
				foreach ($request->taxes as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseItemTax([
						'purchase_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));

					if (has_permission('bill_invoices.approve') || request()->isOwner) {
						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						} else {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'dr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					} else {
						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new PendingTransaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						} else {
							$transaction              = new PendingTransaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'dr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Bill Invoice Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'bill invoice tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('bill_invoices.approve') || request()->isOwner) {
				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = Account::find($request->input('account_id')[$i])->dr_cr;
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				} else {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				}
			} else {
				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new PendingTransaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = Account::find($request->input('account_id')[$i])->dr_cr;
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				} else {
					$transaction              = new PendingTransaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate);
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchase->exchange_rate));
					$transaction->ref_type    = 'bill invoice';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Bill Invoice #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				}
			}

			// update stock
			if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
				$purchaseItem->product->stock = $purchaseItem->product->stock + $request->quantity[$i];
				$purchaseItem->product->save();
			}
		}

		DB::commit();

		if (has_permission('bill_invoices.approve') || request()->isOwner) {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			} else {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}

			if ($request->input('discount_value') > 0) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id  = get_account('Purchase Discount Allowed')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate           = $purchase->exchange_rate;
				$transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
				$transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
				$transaction->ref_id      = $purchase->id;
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}
		} else {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			} else {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = get_account('Accounts Payable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Bill Invoice Payable #' . $purchase->bill_no;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}

			if ($request->input('discount_value') > 0) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id  = get_account('Purchase Discount Allowed')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate           = $purchase->exchange_rate;
				$transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
				$transaction->description = _lang('Bill Invoice Discount') . ' #' . $purchase->bill_no;
				$transaction->ref_id      = $purchase->id;
				$transaction->ref_type    = 'bill invoice';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}
		}

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Updated Bill Invoice ' . $purchase->bill_no;
		$audit->save();

		return redirect()->route('bill_invoices.show', $purchase->id)->with('success', _lang('Updated Successfully'));
	}

	public function send_email(Request $request, $id)
	{
		$validator = Validator::make($request->all(), [
			'email'   => 'required|email',
			'subject' => 'required',
			'message' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json([
				'message' => $validator->errors()->first()
			], 422);
		}

		$customMessage = [
			'subject' => $request->subject,
			'message' => $request->message,
		];

		$purchase = Purchase::find($id);
		$vendor = $purchase->vendor;
		$vendor->email = $request->email;

		try {
			Notification::send($vendor, new SendBillInvoice($purchase, $customMessage, $request->template));
			$purchase->email_send = 1;
			$purchase->email_send_at = now();
			$purchase->save();

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Sent Bill Invoice ' . $purchase->bill_no . ' to ' . $vendor->email;
			$audit->save();

			return redirect()->back()->with('success', _lang('Email has been sent'));
		} catch (\Exception $e) {
			return redirect()->back()->with('error', $e->getMessage());
		}
	}

	public function import_bills(Request $request)
	{
		$request->validate([
			'bills_file' => 'required|mimes:xls,xlsx',
		]);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Bill Invoices Imported ' . $request->file('bills_file')->getClientOriginalName();
		$audit->save();

		try {
			Excel::import(new BillInvoiceImport, $request->file('bills_file'));
		} catch (\Exception $e) {
			return back()->with('error', $e->getMessage());
		}

		return redirect()->route('bill_invoices.index')->with('success', _lang('Bills Imported'));
	}

	public function bill_invoices_filter(Request $request)
	{
		$from =  explode('to', $request->date_range)[0] ?? '';
		$to = explode('to', $request->date_range)[1] ?? '';

		$query = Purchase::select('purchases.*')
			->with('vendor');

		if ($request->vendor_id != '') {
			$query->where('vendor_id', $request->vendor_id);
		}

		if ($request->status != '') {
			$query->where('status', $request->status);
		}

		if ($from != '' && $to != '') {
			$query->whereDate('purchase_date', '>=', Carbon::parse($from)->format('Y-m-d'))
				->whereDate('purchase_date', '<=', Carbon::parse($to)->format('Y-m-d'));
		}

		$purchases = $query->get();

		$status = $request->status;
		$vendor_id = $request->vendor_id;
		$date_range = $request->date_range;

		return view('backend.user.purchase.list', compact('purchases', 'status', 'vendor_id', 'date_range'));
	}

	public function export_purchases()
	{
		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Bill Invoices Exported';
		$audit->save();

		return Excel::download(new BillInvoiceExport, 'purchases ' . now()->format('d m Y') . '.xlsx');
	}

	public function bulk_approve(Request $request)
	{
		foreach ($request->ids as $id) {
			$bill = Purchase::find($id);
			$bill->approval_status = 1;
			$bill->approved_by = auth()->user()->id;
			$bill->save();

			// select from pending transactions and insert into transactions
			$transactions = PendingTransaction::where('ref_id', $bill->id)->get();

			foreach ($transactions as $transaction) {
				// Create a new Transaction instance and replicate data from pending
				$new_transaction = $transaction->replicate();
				$new_transaction->setTable('transactions'); // Change the table to 'transactions'
				$new_transaction->save();

				// Delete the pending transaction
				$transaction->delete();
			}


			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Approved Bill Invoice ' . $bill->bill_no;
			$audit->save();
		}

		return redirect()->route('bill_invoices.index')->with('success', _lang('Approved Successfully'));
	}

	public function bulk_reject(Request $request)
	{
		foreach ($request->ids as $id) {
			$bill = Purchase::find($id);
			if ($bill->approval_status == 0) {
				continue;
			}
			$bill->approval_status = 0;
			$bill->approved_by = null;
			$bill->save();


			// delete all transactions
			$transactions = Transaction::where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'bill invoice')
						->orWhere('ref_type', 'bill invoice tax');
				})
				->get();

			foreach ($transactions as $transaction) {
				$new_transaction = $transaction->replicate();
				$new_transaction->setTable('pending_transactions');
				$new_transaction->save();

				$transaction->delete();
			}

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Rejected Bill Invoice ' . $bill->bill_no;
			$audit->save();
		}

		return redirect()->route('bill_invoices.index')->with('success', _lang('Rejected Successfully'));
	}
}
