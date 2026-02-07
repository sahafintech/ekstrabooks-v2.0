<?php

namespace App\Http\Controllers\User;

use App\Exports\CashPurchaseExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Approvals;
use App\Models\Attachment;
use App\Models\AuditLog;
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
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use App\Models\Vendor;
use App\Models\User;
use App\Notifications\SendCashPurchase;
use App\Notifications\SendPurchaseApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use function Spatie\LaravelPdf\Support\pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class CashPurchaseController extends Controller
{
	public function index(Request $request)
	{
		Gate::authorize('cash_purchases.view');
		$search = $request->get('search', '');
		$perPage = $request->get('per_page', 50);
		$sorting = $request->get('sorting', []);
		$sortColumn = $sorting['column'] ?? 'id';
		$sortDirection = $sorting['direction'] ?? 'desc';
		$vendorId = $request->get('vendor_id', '');
		$dateRange = $request->get('date_range', []);
		$status = $request->get('status', '');

		session(['cash_purchases_from' => $dateRange[0] ?? '']);
		session(['cash_purchases_to' => $dateRange[1] ?? '']);
		session(['cash_purchases_approval_status' => $status]);
		session(['cash_purchases_vendor_id' => $vendorId]);

		$query = Purchase::with('vendor', 'business')
			->where('cash', 1);

		if ($sortColumn === 'vendor.name') {
			$query->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
				->orderBy('vendors.name', $sortDirection)
				->select('purchases.*');
		} else {
			$query->orderBy($sortColumn, $sortDirection);
		}

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('bill_no', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhere('purchase_date', 'like', "%$search%")
					->orWhere('grand_total', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		// Filter by vendor
		if ($vendorId) {
			$query->where('vendor_id', $vendorId);
		}

		// Filter by date range
		if (!empty($dateRange)) {
			$query->whereBetween('purchase_date', $dateRange);
		}

		// Filter by status
		if ($status !== null && $status !== '') {
			$query->where('approval_status', $status);
		}

		// Get paginated purchases
		$purchases = $query->paginate($perPage)->withQueryString();

		// Get summary statistics for all purchases matching filters
		$allPurchases = Purchase::where('cash', 1);

		if ($search) {
			$allPurchases->where(function ($q) use ($search) {
				$q->where('bill_no', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		if ($vendorId) {
			$allPurchases->where('vendor_id', $vendorId);
		}

		if (!empty($dateRange)) {
			$allPurchases->whereBetween('purchase_date', $dateRange);
		}

		if ($status !== null && $status !== '') {
			$allPurchases->where('approval_status', $status);
		}

		$allPurchases = $allPurchases->get();

		$summary = [
			'total_purchases' => $allPurchases->count(),
			'total_approved' => $allPurchases->where('approval_status', 1)->count(),
			'total_pending' => $allPurchases->where('approval_status', 0)->count(),
			'grand_total' => $allPurchases->sum('grand_total'),
		];

		$vendors = Vendor::orderBy('name', 'asc')->get();

		// Check if there are configured approval users for this business
		$purchaseApprovalUsersJson = get_business_option('purchase_approval_users', '[]');
		$approvalUsersArray = json_decode($purchaseApprovalUsersJson, true);
		$hasConfiguredApprovers = is_array($approvalUsersArray) && count($approvalUsersArray) > 0;

		// Check if there are configured checker users for this business
		$purchaseCheckerUsersJson = get_business_option('purchase_checker_users', '[]');
		$checkerUsersArray = json_decode($purchaseCheckerUsersJson, true);
		$hasConfiguredCheckers = is_array($checkerUsersArray) && count($checkerUsersArray) > 0;

		$approvers = [];
		if ($hasConfiguredApprovers) {
			$approvers = User::whereIn('id', $approvalUsersArray)
				->select('id', 'name', 'email')
				->orderBy('name')
				->get();
		}

		$approvalEmailTemplate = EmailTemplate::where('slug', 'PURCHASE_APPROVAL_REQUEST')
			->where('email_status', 1)
			->first();

		return Inertia::render('Backend/User/CashPurchase/List', [
			'purchases' => $purchases->items(),
			'meta' => [
				'current_page' => $purchases->currentPage(),
				'from' => $purchases->firstItem(),
				'last_page' => $purchases->lastPage(),
				'links' => $purchases->linkCollection(),
				'path' => $purchases->path(),
				'per_page' => $purchases->perPage(),
				'to' => $purchases->lastItem(),
				'total' => $purchases->total(),
			],
			'filters' => [
				'search' => $search,
				'per_page' => $perPage,
				'sorting' => $sorting,
				'vendor_id' => $vendorId,
				'date_range' => $dateRange,
				'status' => $status,
			],
			'vendors' => $vendors,
			'summary' => $summary,
			'trashed_cash_purchases' => Purchase::onlyTrashed()->where('cash', 1)->count(),
			'hasConfiguredApprovers' => $hasConfiguredApprovers,
			'hasConfiguredCheckers' => $hasConfiguredCheckers,
			'currentUserId' => auth()->id(),
			'approvers' => $approvers,
			'emailTemplate' => $approvalEmailTemplate,
			'appUrl' => config('app.url') ?? url('/'),
		]);
	}

	public function trash(Request $request)
	{
		Gate::authorize('cash_purchases.view');
		$search = $request->get('search', '');
		$perPage = $request->get('per_page', 50);
		$sorting = $request->get('sorting', []);
		$sortColumn = $sorting['column'] ?? 'id';
		$sortDirection = $sorting['direction'] ?? 'desc';
		$vendorId = $request->get('vendor_id', '');
		$dateRange = $request->get('date_range', []);
		$status = $request->get('status', '');

		session(['cash_purchases_from' => $dateRange[0] ?? '']);
		session(['cash_purchases_to' => $dateRange[1] ?? '']);
		session(['cash_purchases_approval_status' => $status]);
		session(['cash_purchases_vendor_id' => $vendorId]);

		$query = Purchase::onlyTrashed()->with('vendor', 'business')
			->where('cash', 1);

		if ($sortColumn === 'vendor.name') {
			$query->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
				->orderBy('vendors.name', $sortDirection)
				->select('purchases.*');
		} else {
			$query->orderBy($sortColumn, $sortDirection);
		}

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('bill_no', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhere('purchase_date', 'like', "%$search%")
					->orWhere('grand_total', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		// Filter by vendor
		if ($vendorId) {
			$query->where('vendor_id', $vendorId);
		}

		// Filter by date range
		if (!empty($dateRange)) {
			$query->whereBetween('purchase_date', $dateRange);
		}

		// Filter by status
		if ($status) {
			$query->where('approval_status', $status);
		}

		// Get paginated purchases
		$purchases = $query->paginate($perPage)->withQueryString();

		// Get summary statistics for all purchases matching filters
		$allPurchases = Purchase::where('cash', 1);

		if ($search) {
			$allPurchases->where(function ($q) use ($search) {
				$q->where('bill_no', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		if ($vendorId) {
			$allPurchases->where('vendor_id', $vendorId);
		}

		if (!empty($dateRange)) {
			$allPurchases->whereBetween('purchase_date', $dateRange);
		}

		if ($status) {
			$allPurchases->where('approval_status', $status);
		}

		$allPurchases = $allPurchases->get();

		$vendors = Vendor::orderBy('name', 'asc')->get();

		return Inertia::render('Backend/User/CashPurchase/Trash', [
			'purchases' => $purchases->items(),
			'meta' => [
				'current_page' => $purchases->currentPage(),
				'from' => $purchases->firstItem(),
				'last_page' => $purchases->lastPage(),
				'links' => $purchases->linkCollection(),
				'path' => $purchases->path(),
				'per_page' => $purchases->perPage(),
				'to' => $purchases->lastItem(),
				'total' => $purchases->total(),
			],
			'filters' => [
				'search' => $search,
				'per_page' => $perPage,
				'sorting' => $sorting,
				'vendor_id' => $vendorId,
				'date_range' => $dateRange,
				'status' => $status,
			],
			'vendors' => $vendors,
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		Gate::authorize('cash_purchases.create');
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

		$vendors = Vendor::orderBy('id', 'desc')
			->get();
		$products = Product::orderBy('id', 'desc')
			->get();
		$currencies = Currency::orderBy('id', 'desc')
			->get();
		$taxes = Tax::orderBy('id', 'desc')
			->get();
		$accounts = Account::all();
		$inventory = Account::where('account_name', 'Inventory')->first();
		$purchase_title = get_business_option('purchase_title', 'Cash Purchase');
		$base_currency = get_business_option('currency');
		$projects = Project::orderBy('id', 'desc')
			->with('tasks')
			->get();
		$cost_codes = CostCode::orderBy('id', 'desc')
			->get();

		// Get payment methods
		$methods = TransactionMethod::all();

		return Inertia::render('Backend/User/CashPurchase/Create', [
			'vendors' => $vendors,
			'products' => $products,
			'currencies' => $currencies,
			'inventory' => $inventory,
			'taxes' => $taxes,
			'accounts' => $accounts,
			'purchase_title' => $purchase_title,
			'base_currency' => $base_currency,
			'projects' => $projects,
			'cost_codes' => $cost_codes,
			'construction_module' => package()->construction_module,
			'methods' => $methods,
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
		Gate::authorize('cash_purchases.create');
		$validator = Validator::make($request->all(), [
			'vendor_id' => 'nullable',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'product_name' => 'required',
			'currency'  =>  'required',
			'credit_account_id' => 'nullable', // Changed to nullable as we now use payment_accounts
			'payment_accounts' => 'required|array|min:1',
		], [
			'product_name.required' => _lang('You must add at least one item'),
			'payment_accounts.required' => _lang('At least one payment account is required'),
			'payment_accounts.min' => _lang('At least one payment account is required'),
		]);

		// Additional validation for payment accounts
		if ($request->has('payment_accounts') && is_array($request->payment_accounts)) {
			$paymentAccounts = $request->payment_accounts;
			$totalAmount = 0;
			
			foreach ($paymentAccounts as $index => $payment) {
				if (empty($payment['account_id'])) {
					$validator->errors()->add("payment_accounts.{$index}.account_id", _lang('Payment account is required'));
				}
				if (empty($payment['amount']) || $payment['amount'] <= 0) {
					$validator->errors()->add("payment_accounts.{$index}.amount", _lang('Payment amount must be greater than 0'));
				}
				if (empty($payment['method'])) {
					$validator->errors()->add("payment_accounts.{$index}.method", _lang('Payment method is required'));
				}
				$totalAmount += floatval($payment['amount'] ?? 0);
			}
			
			// Calculate expected total
			$summary = $this->calculateTotal($request);
			$expectedTotal = $summary['grandTotal'];
			
			if (abs($totalAmount - $expectedTotal) > 0.01) {
				$validator->errors()->add("payment_accounts", _lang('Total payment amount must equal purchase total'));
			}
		}

		if ($validator->fails()) {
			return redirect()->route('cash_purchases.create')
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

		// if account_id is null or empty then return with error
		if (in_array(null, $request->account_id) || in_array('', $request->account_id)) {
			return redirect()->back()->withInput()->with('error', _lang('Account is required for each item'));
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

		$purchase = new Purchase();
		$purchase->vendor_id = $request->input('vendor_id') ?? null;
		$purchase->title = $request->input('title');
		$purchase->bill_no = get_business_option('purchase_number');
		$purchase->po_so_number = $request->input('po_so_number');
		$purchase->purchase_date = Carbon::parse($request->input('purchase_date'))->format('Y-m-d');
		$purchase->due_date = Carbon::parse($request->input('purchase_date'))->format('Y-m-d');
		$purchase->sub_total = $summary['subTotal'];
		$purchase->grand_total = $summary['grandTotal'];
		$purchase->converted_total = $request->input('converted_total');
		$purchase->exchange_rate   = $request->input('exchange_rate');
		$purchase->currency   = $request->input('currency');
		$purchase->paid = 0;
		$purchase->discount = $summary['discountAmount'];
		$purchase->cash = 1;
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->withholding_tax = $request->input('withholding_tax') ?? 0;
		$purchase->footer = $request->input('footer');
		// All purchases start as pending - must go through approval workflow
		$purchase->approval_status = 0;
		$purchase->checker_status = 0; // Must go through checker verification first
		$purchase->created_by = auth()->user()->id;
		$purchase->approved_by = null;
		$purchase->checked_by = null;
		$purchase->benificiary = $request->input('benificiary');
		$purchase->short_code = rand(100000, 9999999) . uniqid();
		$purchase->paid = $summary['grandTotal'];
		$purchase->status = 2;

		$purchase->save();

		// Create approval records for configured approvers
		$this->createPurchaseApprovalRecords($purchase);

		// Create checker records for configured checkers
		$this->createPurchaseCheckerRecords($purchase);

		// if attachments then upload
		if (isset($request->attachments)) {
			if ($request->attachments != null) {
				for ($i = 0; $i < count($request->attachments); $i++) {
					$theFile = $request->file("attachments.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]->getClientOriginalName();
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'cash purchase';
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
				'description' => $request->description[$i],
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

					if (has_permission('cash_purchases.bulk_approve') || request()->isOwner) {
						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchase->exchange_rate) / 100) * $tax->rate);
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
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
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
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
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
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
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('cash_purchases.bulk_approve') || request()->isOwner) {

				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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

		// Create multiple payment transactions
		if ($request->has('payment_accounts') && is_array($request->payment_accounts) && count($request->payment_accounts) > 0) {
			foreach ($request->payment_accounts as $index => $payment) {
				if (has_permission('cash_purchases.bulk_approve') || request()->isOwner) {
					$transaction = new Transaction();
				} else {
					$transaction = new PendingTransaction();
				}
				
				$transaction->vendor_id = $request->input('vendor_id') ?? NULL;
				$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id = $payment['account_id'];
				$transaction->dr_cr = 'cr';
				$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $payment['amount'] / $purchase->exchange_rate);
				$transaction->transaction_currency = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $payment['amount'] / $purchase->exchange_rate));
				$transaction->description = 'Cash Purchase Payment #' . $purchase->bill_no . ' (Payment ' . ($index + 1) . ')';
				$transaction->transaction_method = $payment['method'];
				$transaction->reference = $payment['reference'] ?? '';
				$transaction->ref_id = $purchase->id;
				$transaction->ref_type = 'cash purchase payment';
				$transaction->vendor_id = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id ?? null;
				$transaction->save();
			}
		} else {
			// Fallback to single payment for backward compatibility
			if (has_permission('cash_purchases.bulk_approve') || request()->isOwner) {
				$transaction = new Transaction();
			} else {
				$transaction = new PendingTransaction();
			}
			
			$transaction->vendor_id = $request->input('vendor_id') ?? NULL;
			$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
			$transaction->account_id = $request->input('credit_account_id');
			$transaction->dr_cr = 'cr';
			$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
			$transaction->transaction_currency = $request->currency;
			$transaction->currency_rate = $purchase->exchange_rate;
			$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
			$transaction->description = 'Cash Purchase Payment #' . $purchase->bill_no;
			$transaction->transaction_method = $request->input('method') ?? '';
			$transaction->reference = $request->input('reference') ?? '';
			$transaction->ref_id = $purchase->id;
			$transaction->ref_type = 'cash purchase payment';
			$transaction->vendor_id = $purchase->vendor_id;
			$transaction->project_id = $purchase->items->first()->project_id ?? null;
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
				$transaction->ref_type    = 'cash purchase';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id;
				$transaction->save();
			}


		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Created Cash Purchase ' . $purchase->bill_no;
		$audit->save();

		if ($purchase->id > 0) {
			return redirect()->route('cash_purchases.show', $purchase->id)->with('success', _lang('Saved Successfully'));
		} else {
			return back()->with('error', _lang('Something going wrong, Please try again'));
		}
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

	public function show($id)
	{
		Gate::authorize('cash_purchases.view');
		$bill = Purchase::with(['business', 'items.account', 'taxes', 'vendor', 'approvals.actionUser', 'checkers.actionUser'])->find($id);
		$attachments = Attachment::where('ref_type', 'cash purchase')->where('ref_id', $id)->get();
		$email_templates = EmailTemplate::whereIn('slug', ['NEW_CASH_PURCHASE_CREATED'])
			->where('email_status', 1)
			->get();

		// Check if there are configured approval users for this business
		$purchaseApprovalUsersJson = get_business_option('purchase_approval_users', '[]');
		$approvalUsersCount = 0;
		$hasConfiguredApprovers = false;
		$configuredApproverUserIds = [];
		
		$approvalUsersArray = json_decode($purchaseApprovalUsersJson, true);
		if (is_array($approvalUsersArray) && count($approvalUsersArray) > 0) {
			$approvalUsersCount = count($approvalUsersArray);
			$hasConfiguredApprovers = true;
			$configuredApproverUserIds = $approvalUsersArray;
		}

		// Check if there are configured checker users for this business
		$purchaseCheckerUsersJson = get_business_option('purchase_checker_users', '[]');
		$checkerUsersCount = 0;
		$hasConfiguredCheckers = false;
		$configuredCheckerUserIds = [];
		
		$checkerUsersArray = json_decode($purchaseCheckerUsersJson, true);
		if (is_array($checkerUsersArray) && count($checkerUsersArray) > 0) {
			$checkerUsersCount = count($checkerUsersArray);
			$hasConfiguredCheckers = true;
			$configuredCheckerUserIds = $checkerUsersArray;
		}

		// Sync approval records for pending purchases
		// This ensures new approvers are added to existing purchases
		if ($hasConfiguredApprovers && $bill->approval_status != 1) {
			$this->syncApprovalRecordsForPurchase($bill, $configuredApproverUserIds);
			// Reload approvals with actionUser relationship
			$bill->load('approvals.actionUser');
		}

		// Sync checker records for pending purchases
		// This ensures new checkers are added to existing purchases
		if ($hasConfiguredCheckers && $bill->checker_status != 1) {
			$this->syncCheckerRecordsForPurchase($bill, $configuredCheckerUserIds);
			// Reload checkers with actionUser relationship
			$bill->load('checkers.actionUser');
		}

		return Inertia::render('Backend/User/CashPurchase/View', [
			'bill' => $bill,
			'attachments' => $attachments,
			'email_templates' => $email_templates,
			'decimalPlace' => get_business_option('decimal_places', 2),
			'approvalUsersCount' => $bill->approvals->count(), // Actual approval records for this purchase
			'hasConfiguredApprovers' => $hasConfiguredApprovers,
			'checkerUsersCount' => $bill->checkers->count(), // Actual checker records for this purchase
			'hasConfiguredCheckers' => $hasConfiguredCheckers,
		]);
	}

	/**
	 * Sync approval records for a purchase with the configured approvers
	 * Adds missing approvers and removes approvers no longer in the list (if they haven't acted)
	 */
	private function syncApprovalRecordsForPurchase(Purchase $purchase, array $configuredUserIds): void
	{
		// Filter to only include user IDs that actually exist in the database
		$validUserIds = \App\Models\User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
		
		if (empty($validUserIds)) {
			return;
		}

		// Get existing approval user IDs for this purchase
		$existingApproverIds = $purchase->approvals->pluck('action_user')->toArray();

		// Add missing approvers
		foreach ($validUserIds as $userId) {
			if (!in_array($userId, $existingApproverIds)) {
				Approvals::create([
					'ref_id' => $purchase->id,
					'ref_name' => 'purchase',
					'checker_type' => 'approval',
					'action_user' => $userId,
					'status' => 0, // pending
				]);
			}
		}

		// Remove approvers who are no longer in the configured list
		// Only remove if they haven't taken action yet (status = 0)
		Approvals::where('ref_id', $purchase->id)
			->where('ref_name', 'purchase')
			->where('checker_type', 'approval')
			->where('status', 0) // Only remove pending approvals
			->whereNotIn('action_user', $validUserIds)
			->delete();
	}

	public function pdf($id)
	{
		Gate::authorize('cash_purchases.pdf');
		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);
		return pdf()
		->view('backend.user.pdf.cash-purchase', compact('bill'))
		->name('cash-purchase-' . $bill->bill_no . '.pdf')
		->download();
	}

	public function show_public_cash_purchase($short_code)
	{
		Gate::authorize('cash_purchases.show_public');
		$purchase   = Purchase::withoutGlobalScopes()->with(['vendor', 'business', 'items', 'taxes'])
			->where('short_code', $short_code)
			->first();

		$request = request();
		// add activeBusiness object to request
		$request->merge(['activeBusiness' => $purchase->business]);

		return Inertia::render('Backend/User/CashPurchase/PublicView', [
			'purchase' => $purchase,
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Request $request, $id)
	{
		Gate::authorize('cash_purchases.update');
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

		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);

		if (!has_permission('cash_purchases.bulk_approve') && !request()->isOwner && $bill->approval_status == 1) {
			return back()->with('error', _lang('Permission denied'));
		}

		// Get all payment transactions for this purchase
		if ($bill->approval_status == 1) {
			$paymentTransactions = Transaction::where('ref_id', $bill->id)
				->where('ref_type', 'cash purchase payment')
				->whereHas('account', function ($query) {
					$query->where('account_type', 'Bank')
						->orWhere('account_type', 'Cash');
				})
				->with('account')
				->get();
		} else {
			$paymentTransactions = PendingTransaction::where('ref_id', $bill->id)
				->where('ref_type', 'cash purchase payment')
				->whereHas('account', function ($query) {
					$query->where('account_type', 'Bank')
						->orWhere('account_type', 'Cash');
				})
				->with('account')
				->get();
		}

		$theAttachments = Attachment::where('ref_id', $id)->where('ref_type', 'cash purchase')->get();
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

		// Get payment methods
		$methods = TransactionMethod::all();

		return Inertia::render('Backend/User/CashPurchase/Edit', [
			'bill' => $bill,
			'theAttachments' => $theAttachments,
			'paymentTransactions' => $paymentTransactions,
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
			'methods' => $methods,
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
		Gate::authorize('cash_purchases.update');
		$validator = Validator::make($request->all(), [
			'vendor_id' => 'nullable',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'product_name' => 'required',
			'credit_account_id' => 'nullable', // Changed to nullable as we now use payment_accounts
			'payment_accounts' => 'required|array|min:1',
		], [
			'product_name.required' => _lang('You must add at least one item'),
			'payment_accounts.required' => _lang('At least one payment account is required'),
			'payment_accounts.min' => _lang('At least one payment account is required'),
		]);

		// Additional validation for payment accounts
		if ($request->has('payment_accounts') && is_array($request->payment_accounts)) {
			$paymentAccounts = $request->payment_accounts;
			$totalAmount = 0;
			
			foreach ($paymentAccounts as $index => $payment) {
				if (empty($payment['account_id'])) {
					$validator->errors()->add("payment_accounts.{$index}.account_id", _lang('Payment account is required'));
				}
				if (empty($payment['amount']) || $payment['amount'] <= 0) {
					$validator->errors()->add("payment_accounts.{$index}.amount", _lang('Payment amount must be greater than 0'));
				}
				if (empty($payment['method'])) {
					$validator->errors()->add("payment_accounts.{$index}.method", _lang('Payment method is required'));
				}
				$totalAmount += floatval($payment['amount'] ?? 0);
			}
			
			// Calculate expected total
			$summary = $this->calculateTotal($request);
			$expectedTotal = $summary['grandTotal'];
			
			if (abs($totalAmount - $expectedTotal) > 0.01) {
				$validator->errors()->add("payment_accounts", _lang('Total payment amount must equal purchase total'));
			}
		}

		if ($validator->fails()) {
			return redirect()->route('cash_purchases.edit', $id)
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

		// if account_id is null or empty then return with error
		if (in_array(null, $request->account_id) || in_array('', $request->account_id)) {
			return redirect()->back()->withInput()->with('error', _lang('Account is required for each item'));
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
		$purchase->paid = $summary['grandTotal'];
		$purchase->save();

		// delete old attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')->get(); // Get attachments from the database

		if (isset($request->attachments)) {
			foreach ($attachments as $attachment) {
				if (!in_array($attachment->path, $request->attachments)) {
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
					$theFile = $request->file("attachments.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]->getClientOriginalName();
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'cash purchase';
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
			$transaction = Transaction::where('ref_id', $purchase->id)->whereIn('ref_type', ['cash purchase', 'cash purchase payment', 'cash purchase tax'])->get();

			foreach ($transaction as $t) {
				$t->forceDelete();
			}

			// delete pending transaction
			$pending_transaction = PendingTransaction::where('ref_id', $purchase->id)->whereIn('ref_type', ['cash purchase', 'cash purchase payment', 'cash purchase tax'])
			->get();

			foreach ($pending_transaction as $t) {
				$t->forceDelete();
			}
		}

		$currentTime = Carbon::now();

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseItem([
				'purchase_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => $request->description[$i],
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

			if (has_permission('cash_purchases.bulk_approve') || request()->isOwner && $purchase->approval_status == 1) {
				if (isset($request->taxes)) {

					$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase tax')
						->get();

					foreach ($transaction as $trans) {
						$trans->delete();
					}

					$purchaseItem->taxes()->delete();

					foreach ($request->taxes as $taxId) {
						$tax = Tax::find($taxId);

						$purchaseItem->taxes()->save(new PurchaseItemTax([
							'purchase_id' => $purchase->id,
							'tax_id' => $taxId,
							'name' => $tax->name . ' ' . $tax->rate . ' %',
							'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
						]));

						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new Transaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate);
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
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
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate);
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					}
				}
			} else {
				if (isset($request->taxes[$purchaseItem->product_name])) {

					$transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase tax')
						->get();

					foreach ($transaction as $trans) {
						$trans->delete();
					}

					$purchaseItem->taxes()->delete();

					foreach ($request->taxes[$purchaseItem->product_name] as $taxId) {
						$tax = Tax::find($taxId);

						$purchaseItem->taxes()->save(new PurchaseItemTax([
							'purchase_id' => $purchase->id,
							'tax_id' => $taxId,
							'name' => $tax->name . ' ' . $tax->rate . ' %',
							'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
						]));

						if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
							$transaction              = new PendingTransaction();
							$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
							$transaction->account_id  = $tax->account_id;
							$transaction->dr_cr       = 'cr';
							$transaction->transaction_currency    = $request->currency;
							$transaction->currency_rate = $purchase->exchange_rate;
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate);
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
							$transaction->tax_id      = $tax->id;
							$transaction->vendor_id   = $purchase->vendor_id;
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
							$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate));
							$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / 100) * $tax->rate);
							$transaction->description = _lang('Cash Purchase Tax') . ' #' . $purchase->bill_no;
							$transaction->ref_id      = $purchase->id;
							$transaction->ref_type    = 'cash purchase tax';
							$transaction->tax_id      = $tax->id;
							$transaction->project_id = $purchaseItem->project_id;
							$transaction->project_task_id = $purchaseItem->project_task_id;
							$transaction->cost_code_id = $purchaseItem->cost_code_id;
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('cash_purchases.bulk_approve') || request()->isOwner && $purchase->approval_status == 1) {

				if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new Transaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
					$transaction->project_id = $purchaseItem->project_id;
					$transaction->project_task_id = $purchaseItem->project_task_id;
					$transaction->cost_code_id = $purchaseItem->cost_code_id;
					$transaction->save();
				}
			} else {
				if (isset($request->taxes[$purchaseItem->product_name]) && isset($request->withholding_tax) && $request->withholding_tax == 1) {
					$transaction              = new PendingTransaction();
					$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
					$transaction->account_id  = $request->input('account_id')[$i];
					$transaction->dr_cr       = 'dr';
					$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount'));
					$transaction->transaction_currency    = $request->currency;
					$transaction->currency_rate = $purchase->exchange_rate;
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($purchaseItem->sub_total / $purchase->exchange_rate) + $purchaseItem->taxes->sum('amount')));
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->ref_id      = $purchase->id;
					$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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

		if ($purchase->bill_no == null) {
			//Increment Bill Number
			BusinessSetting::where('name', 'purchase_number')->increment('value');
		}

		DB::commit();

		// Delete existing payment transactions
		if (has_permission('cash_purchases.bulk_approve') || request()->isOwner && $purchase->approval_status == 1) {
			Transaction::where('ref_id', $purchase->id)
				->where('ref_type', 'cash purchase payment')
				->whereHas('account', function ($query) {
					$query->where('account_type', 'Bank')
						->orWhere('account_type', 'Cash');
				})
				->delete();
		} else {
			PendingTransaction::where('ref_id', $purchase->id)
				->where('ref_type', 'cash purchase payment')
				->whereHas('account', function ($query) {
					$query->where('account_type', 'Bank')
						->orWhere('account_type', 'Cash');
				})
				->delete();
		}

		// Create new payment transactions
		if ($request->has('payment_accounts') && is_array($request->payment_accounts) && count($request->payment_accounts) > 0) {
			foreach ($request->payment_accounts as $index => $payment) {
				if (has_permission('cash_purchases.bulk_approve') || request()->isOwner && $purchase->approval_status == 1) {
					$transaction = new Transaction();
				} else {
					$transaction = new PendingTransaction();
				}
				
				$transaction->vendor_id = $request->input('vendor_id') ?? NULL;
				$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
				$transaction->account_id = $payment['account_id'];
				$transaction->dr_cr = 'cr';
				$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $payment['amount'] / $purchase->exchange_rate);
				$transaction->transaction_currency = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $payment['amount'] / $purchase->exchange_rate));
				$transaction->description = 'Cash Purchase Payment #' . $purchase->bill_no . ' (Payment ' . ($index + 1) . ')';
				$transaction->transaction_method = $payment['method'];
				$transaction->reference = $payment['reference'] ?? '';
				$transaction->ref_id = $purchase->id;
				$transaction->ref_type = 'cash purchase payment';
				$transaction->vendor_id = $purchase->vendor_id;
				$transaction->project_id = $purchase->items->first()->project_id ?? null;
				$transaction->save();
			}
		} else {
			// Fallback to single payment for backward compatibility
			if (has_permission('cash_purchases.bulk_approve') || request()->isOwner && $purchase->approval_status == 1) {
				$transaction = new Transaction();
			} else {
				$transaction = new PendingTransaction();
			}
			
			$transaction->vendor_id = $request->input('vendor_id') ?? NULL;
			$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
			$transaction->account_id = $request->input('credit_account_id');
			$transaction->dr_cr = 'cr';
			$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
			$transaction->transaction_currency = $request->currency;
			$transaction->currency_rate = $purchase->exchange_rate;
			$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
			$transaction->description = 'Cash Purchase Payment #' . $purchase->bill_no;
			$transaction->transaction_method = $request->input('method') ?? '';
			$transaction->reference = $request->input('reference') ?? '';
			$transaction->ref_id = $purchase->id;
			$transaction->ref_type = 'cash purchase payment';
			$transaction->vendor_id = $purchase->vendor_id;
			$transaction->project_id = $purchase->items->first()->project_id ?? null;
			$transaction->save();
		}


			if ($request->input('discount_value') == 0) {
				$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
					->where('account_id', get_account('Purchase Discount Allowed')->id)
					->first();
				if ($transaction != null) {
					$transaction->delete();
				}
			}

			if ($request->input('discount_value') > 0) {
				$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
					->where('account_id', get_account('Purchase Discount Allowed')->id)
					->first();
				if ($transaction == null) {
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->project_id = $purchase->items->first()->project_id;
					$transaction->save();
				} else {
					$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
					$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
					$transaction->save();
				}
			}


		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Updated Cash Purchase ' . $purchase->bill_no;
		$audit->save();

		return redirect()->route('cash_purchases.show', $purchase->id)->with('success', _lang('Updated Successfully'));
	}

	public function export_cash_purchases()
	{
		$query = Purchase::select('purchases.*')
			->with('vendor')
			->where('cash', 1);

		if (session('cash_purchases_vendor_id') !== "") {
			$query->where('vendor_id', session('cash_purchases_vendor_id'));
		}

		if (session('cash_purchases_from') !== "" && session('cash_purchases_to') !== "") {
			$query->whereDate('purchase_date', '>=', Carbon::parse(session('cash_purchases_from'))->format('Y-m-d'))
				->whereDate('purchase_date', '<=', Carbon::parse(session('cash_purchases_to'))->format('Y-m-d'));
		}

		if (session('cash_purchases_approval_status') !== "") {
			$query->where('approval_status', session('cash_purchases_approval_status'));
		}

		$purchases = $query->get();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Exported Cash Purchases';
		$audit->save();

		return Excel::download(new CashPurchaseExport($purchases), 'cash_purchases_' . now()->format('d m Y') . '.xlsx');
	}

	/**
	 * Show the import page
	 */
	public function import()
	{
		Gate::authorize('cash_purchases.create');
		
		return Inertia::render('Backend/User/CashPurchase/Import');
	}

	/**
	 * Handle file upload and return headers for mapping
	 */
	public function uploadImportFile(Request $request)
	{
		Gate::authorize('cash_purchases.create');
		
		// If this is a GET request (page refresh), redirect to step 1
		if ($request->isMethod('get')) {
			return redirect()->route('cash_purchases.import.page');
		}
		
		$request->validate([
			'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
		]);

		try {
			// Create a unique temporary directory
			$sessionId = session()->getId();
			$tempDir = storage_path("app/imports/temp/{$sessionId}");

			// Ensure directory exists with proper permissions
			if (!is_dir($tempDir)) {
				mkdir($tempDir, 0755, true);
			}

			// Generate unique filename
			$fileName = uniqid() . '_' . $request->file('file')->getClientOriginalName();
			$fullPath = $tempDir . '/' . $fileName;

			// Move uploaded file
			$request->file('file')->move($tempDir, $fileName);

			// Verify file exists
			if (!file_exists($fullPath)) {
				throw new \Exception('Failed to store uploaded file');
			}

			// Store relative path for later use
			$relativePath = "imports/temp/{$sessionId}/{$fileName}";

			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
			$worksheet = $spreadsheet->getActiveSheet();
			$headers = [];

			foreach ($worksheet->getRowIterator(1, 1) as $row) {
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false);

				foreach ($cellIterator as $cell) {
					$value = $cell->getValue();
					if ($value) {
						$headers[] = (string) $value;
					}
				}
			}

			// Store in session with explicit save
			session()->put('cash_purchase_import_file_path', $relativePath);
			session()->put('cash_purchase_import_full_path', $fullPath);
			session()->put('cash_purchase_import_file_name', $request->file('file')->getClientOriginalName());
			session()->put('cash_purchase_import_headers', $headers);
			session()->save();

			return Inertia::render('Backend/User/CashPurchase/Import', [
				'previewData' => [
					'headers' => $headers,
				],
			]);
		} catch (\Exception $e) {
			return back()->with('error', 'Failed to process file: ' . $e->getMessage());
		}
	}

	/**
	 * Preview import data with validation
	 */
	public function previewImport(Request $request)
	{
		Gate::authorize('cash_purchases.create');
		
		// If this is a GET request (page refresh), redirect to step 1
		if ($request->isMethod('get')) {
			return redirect()->route('cash_purchases.import.page');
		}
		
		$mappings = $request->input('mappings', []);
		$fullPath = session('cash_purchase_import_full_path');
		$headers = session('cash_purchase_import_headers', []);

		if (!$fullPath || !file_exists($fullPath)) {
			return back()->with('error', 'Import session expired or file not found. Please upload your file again.');
		}

		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
			$worksheet = $spreadsheet->getActiveSheet();

			$headers = session('cash_purchase_import_headers', []);
			$previewRecords = [];
			$validCount = 0;
			$errorCount = 0;
			$warningCount = 0;
			$totalRows = 0;
			$uniqueBillNos = [];

			// Process rows (skip header row)
			foreach ($worksheet->getRowIterator(2) as $row) {
				$rowIndex = $row->getRowIndex();
				$totalRows++;

				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false);

				$rowData = [];
				$cellIndex = 0;

				foreach ($cellIterator as $cell) {
					if ($cellIndex < count($headers)) {
						$header = $headers[$cellIndex];
						$systemField = $mappings[$header] ?? null;

						if ($systemField && $systemField !== 'skip') {
							$rowData[$systemField] = $cell->getValue();
						}
					}
					$cellIndex++;
				}

				// Skip completely empty rows
				if (empty($rowData['bill_no']) && empty($rowData['product_name'])) {
					$totalRows--;
					continue;
				}

				// Track unique bill numbers
				if (!empty($rowData['bill_no'])) {
					$uniqueBillNos[trim((string) $rowData['bill_no'])] = true;
				}

				// Validate row
				$errors = [];

				// Product name is required
				if (empty($rowData['product_name'])) {
					$errors[] = 'Product name is required';
				} else {
					// Check if product exists
					$product = Product::where('name', 'like', '%' . trim((string) $rowData['product_name']) . '%')->first();
					if (!$product) {
						$errors[] = 'Product "' . $rowData['product_name'] . '" not found';
					}
				}

				// Quantity required and must be positive
				if (empty($rowData['quantity']) || floatval($rowData['quantity']) <= 0) {
					$errors[] = 'Quantity must be greater than 0';
				}

				// Unit cost required and must be non-negative
				if (!isset($rowData['unit_cost']) || $rowData['unit_cost'] === '' || floatval($rowData['unit_cost']) < 0) {
					$errors[] = 'Unit cost is required and must be non-negative';
				}

				// Vendor validation (optional but should exist if provided)
				if (!empty($rowData['vendor_name'])) {
					$vendor = Vendor::where('name', 'like', '%' . trim((string) $rowData['vendor_name']) . '%')->first();
					if (!$vendor) {
						$warningCount++;
					}
				}

				$status = count($errors) > 0 ? 'error' : 'valid';
				if ($status === 'error') {
					$errorCount++;
				} else {
					$validCount++;
				}

				// Only add rows with errors to preview_records (limit to 50)
				if ($status === 'error' && count($previewRecords) < 50) {
					$previewRecords[] = [
						'row' => $rowIndex,
						'data' => $rowData,
						'status' => $status,
						'errors' => $errors,
					];
				}
			}

			session()->put('cash_purchase_import_mappings', $mappings);
			session()->save();

			return Inertia::render('Backend/User/CashPurchase/Import', [
				'previewData' => [
					'headers' => $headers,
					'total_rows' => $totalRows,
					'unique_purchases' => count($uniqueBillNos),
					'preview_records' => $previewRecords,
					'valid_count' => $validCount,
					'error_count' => $errorCount,
					'warning_count' => $warningCount,
				],
			]);
		} catch (\Exception $e) {
			return back()->with('error', 'Failed to preview import: ' . $e->getMessage());
		}
	}

	/**
	 * Execute the actual import
	 */
	public function executeImport(Request $request)
	{
		Gate::authorize('cash_purchases.create');
		
		// If this is a GET request (page refresh), redirect to step 1
		if ($request->isMethod('get')) {
			return redirect()->route('cash_purchases.import.page');
		}
		
		$mappings = session('cash_purchase_import_mappings', []);
		$fullPath = session('cash_purchase_import_full_path');
		$headers = session('cash_purchase_import_headers', []);

		if (!$fullPath || !file_exists($fullPath)) {
			return redirect()
				->route('cash_purchases.index')
				->with('error', 'Import session expired or file not found. Please start over.');
		}

		try {
			Excel::import(new \App\Imports\CashPurchaseImport($mappings), $fullPath);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Cash Purchases Imported - ' . session('cash_purchase_import_file_name');
			$audit->save();

			// Clean up
			if (file_exists($fullPath)) {
				unlink($fullPath);
			}
			session()->forget(['cash_purchase_import_file_path', 'cash_purchase_import_full_path', 'cash_purchase_import_file_name', 'cash_purchase_import_headers', 'cash_purchase_import_mappings']);

			return redirect()
				->route('cash_purchases.index')
				->with('success', 'Cash purchases imported successfully.');
		} catch (\Exception $e) {
			return redirect()
				->route('cash_purchases.index')
				->with('error', 'Import failed: ' . $e->getMessage());
		}
	}

	public function voucher($id)
	{
		$bill = Purchase::find($id);
		if ($bill->approval_status == 1) {
			$credit_transaction = Transaction::where('ref_id', $id)->where('ref_type', 'cash purchase payment')->first();
		} else {
			$credit_transaction = PendingTransaction::where('ref_id', $id)->where('ref_type', 'cash purchase payment')->first();
		}
		return view('backend.user.cash_purchase.voucher', compact('bill', 'credit_transaction'));
	}

	public function destroy($id)
	{
		$bill = Purchase::find($id);
		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Deleted Cash Purchase ' . $bill->bill_no;
		$audit->save();

		// descrease stock
		foreach ($bill->items as $purchaseItem) {
			$product = $purchaseItem->product;
			if ($product && $product->type == 'product' && $product->stock_management == 1) {
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
		$transactions = Transaction::where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();


		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->delete();
			}
		}

		// delete pending transactions
		$transactions = PendingTransaction::where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();

		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->delete();
			}
		}

		// bill invoice payment
		$transaction = Transaction::where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
		foreach ($transaction as $data) {
			$data->delete();
		}

		$bill->delete();
		return redirect()->route('cash_purchases.index')->with('success', _lang('Deleted Successfully'));
	}

	public function bulk_destroy(Request $request)
	{
		foreach ($request->ids as $id) {
			$bill = Purchase::find($id);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Deleted Cash Purchase ' . $bill->bill_no;
			$audit->save();

			// descrease stock
			foreach ($bill->items as $purchaseItem) {
				$product = $purchaseItem->product;
				if ($product && $product->type == 'product' && $product->stock_management == 1) {
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
			$transactions = Transaction::where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();


			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->delete();
				}
			}

			// delete pending transactions
			$transactions = PendingTransaction::where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();

			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->delete();
				}
			}

			// bill invoice payment
			$transaction = Transaction::where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
			foreach ($transaction as $data) {
				$data->delete();
			}

			$bill->delete();
		}
		return redirect()->route('cash_purchases.index')->with('success', _lang('Deleted Successfully'));
	}
	
	public function permanent_destroy($id)
	{
		$bill = Purchase::onlyTrashed()->find($id);
		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Permanently Deleted Cash Purchase ' . $bill->bill_no;
		$audit->save();

		// delete transactions
		$transactions = Transaction::onlyTrashed()->where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();


		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->forceDelete();
			}
		}

		// delete pending transactions
		$transactions = PendingTransaction::onlyTrashed()->where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();

		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->forceDelete();
			}
		}


		// bill invoice payment
		$transaction = Transaction::onlyTrashed()->where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
		foreach ($transaction as $data) {
			$data->forceDelete();
		}

		// delete attachments
		$attachments = Attachment::where('ref_id', $bill->id)->where('ref_type', 'cash purchase')->get();
		foreach ($attachments as $attachment) {
			$filePath = public_path($attachment->path);
			if (file_exists($filePath)) {
				unlink($filePath);
			}
			$attachment->delete();
		}

		$bill->forceDelete();
		return redirect()->route('cash_purchases.trash')->with('success', _lang('Deleted Successfully'));
	}

	public function bulk_permanent_destroy(Request $request)
	{
		foreach ($request->ids as $id) {
			$bill = Purchase::onlyTrashed()->find($id);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Permanently Deleted Cash Purchase ' . $bill->bill_no;
			$audit->save();

			// delete transactions
			$transactions = Transaction::onlyTrashed()->where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();


			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->forceDelete();
				}
			}

			// delete pending transactions
			$transactions = PendingTransaction::onlyTrashed()->where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();

			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->forceDelete();
				}
			}


			// bill invoice payment
			$transaction = Transaction::onlyTrashed()->where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
			foreach ($transaction as $data) {
				$data->forceDelete();
			}

			// delete attachments
			$attachments = Attachment::where('ref_id', $bill->id)->where('ref_type', 'cash purchase')->get();
			foreach ($attachments as $attachment) {
				$filePath = public_path($attachment->path);
				if (file_exists($filePath)) {
					unlink($filePath);
				}
				$attachment->delete();
			}

			$bill->forceDelete();
		}
		return redirect()->route('cash_purchases.trash')->with('success', _lang('Deleted Successfully'));
	}

	public function restore($id)
	{
		$bill = Purchase::onlyTrashed()->find($id);
		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Restored Cash Purchase ' . $bill->bill_no;
		$audit->save();

		// increase stock
		foreach ($bill->items as $purchaseItem) {
			$product = $purchaseItem->product;
			if ($product && $product->type == 'product' && $product->stock_management == 1) {
				$product->stock = $product->stock + $purchaseItem->quantity;
				$product->save();
			}

			if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
				$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
				if ($projectBudget) {
					$projectBudget->actual_budget_quantity += $purchaseItem->quantity;
					$projectBudget->actual_budget_amount += $purchaseItem->sub_total;
					$projectBudget->save();
				}
			}
		}

		// delete transactions
		$transactions = Transaction::onlyTrashed()->where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();


		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->restore();
			}
		}

		// delete pending transactions
		$transactions = PendingTransaction::onlyTrashed()->where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();

		if ($transactions->count() > 0) {
			foreach ($transactions as $transaction) {
				$transaction->restore();
			}
		}


		// bill invoice payment
		$transaction = Transaction::onlyTrashed()->where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
		foreach ($transaction as $data) {
			$data->restore();
		}

		$bill->restore();
		return redirect()->route('cash_purchases.trash')->with('success', _lang('Restored Successfully'));
	}

	public function bulk_restore(Request $request)
	{
		foreach ($request->ids as $id) {
			$bill = Purchase::onlyTrashed()->find($id);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Restored Cash Purchase ' . $bill->bill_no;
			$audit->save();

			// increase stock
			foreach ($bill->items as $purchaseItem) {
				$product = $purchaseItem->product;
				if ($product && $product->type == 'product' && $product->stock_management == 1) {
					$product->stock = $product->stock + $purchaseItem->quantity;
					$product->save();
				}

				if ($purchaseItem->project_id && $purchaseItem->project_task_id && $purchaseItem->cost_code_id) {
					$projectBudget = ProjectBudget::where('project_id', $purchaseItem->project_id)->where('project_task_id', $purchaseItem->project_task_id)->where('cost_code_id', $purchaseItem->cost_code_id)->first();
					if ($projectBudget) {
						$projectBudget->actual_budget_quantity += $purchaseItem->quantity;
						$projectBudget->actual_budget_amount += $purchaseItem->sub_total;
						$projectBudget->save();
					}
				}
			}

			// delete transactions
			$transactions = Transaction::onlyTrashed()->where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();


			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->restore();
				}
			}

			// delete pending transactions
			$transactions = PendingTransaction::onlyTrashed()->where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax')
						->orWhere('ref_type', 'cash purchase payment');
				})
				->get();

			if ($transactions->count() > 0) {
				foreach ($transactions as $transaction) {
					$transaction->restore();
				}
			}


			// bill invoice payment
			$transaction = Transaction::onlyTrashed()->where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
			foreach ($transaction as $data) {
				$data->restore();
			}

			$bill->restore();
		}
		return redirect()->route('cash_purchases.trash')->with('success', _lang('Deleted Successfully'));
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
			Notification::send($vendor, new SendCashPurchase($purchase, $customMessage, $request->template));
			$purchase->email_send = 1;
			$purchase->email_send_at = now();
			$purchase->save();

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Sent Cash Purchase ' . $purchase->bill_no . ' to ' . $vendor->email;
			$audit->save();

			return redirect()->back()->with('success', _lang('Email has been sent'));
		} catch (\Exception $e) {
			return redirect()->back()->with('error', $e->getMessage());
		}
	}

	/**
	 * Bulk email selected cash purchases using PURCHASE_APPROVAL_REQUEST template
	 */
	public function bulk_email(Request $request)
	{
		Gate::authorize('cash_purchases.send_email');

		$request->validate([
			'ids' => ['required', 'array', 'min:1'],
			'ids.*' => ['integer', 'exists:purchases,id'],
			'recipient_id' => ['required', 'exists:users,id'],
			'subject' => ['required', 'string', 'max:255'],
			'message' => ['required', 'string'],
		]);

		$purchases = Purchase::with(['vendor', 'business'])
			->whereIn('id', $request->ids)
			->where('cash', 1)
			->get();

		if ($purchases->isEmpty()) {
			return back()->with('error', _lang('No purchases found for this request'));
		}

		$recipient = User::find($request->recipient_id);

		$customMessage = [
			'subject' => $request->subject,
			'message' => $request->message,
		];

		try {
			Notification::send($recipient, new SendPurchaseApprovalRequest($purchases, $recipient, $customMessage));
		} catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			if (empty($errorMessage)) {
				$errorMessage = 'Failed to send email. Please check mail configuration and try again.';
			}
			return back()->with('error', $errorMessage);
		}

		foreach ($purchases as $purchase) {
			$purchase->email_send = 1;
			$purchase->email_send_at = now();
			$purchase->save();

			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Sent Purchase Approval Request for Cash Purchase ' . $purchase->bill_no . ' to ' . $recipient->email;
			$audit->save();
		}

		return back()->with('success', _lang('Email has been sent'));
	}

	public function bulk_approve(Request $request)
	{
		Gate::authorize('cash_purchases.approve');
		$currentUserId = auth()->id();

		// Check if there are any configured approval users for this business
		$approvalUsersJson = get_business_option('purchase_approval_users', '[]');
		$configuredUserIds = json_decode($approvalUsersJson, true);
		$hasConfiguredApprovers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

		if (!$hasConfiguredApprovers) {
			return redirect()->route('cash_purchases.index')->with('error', _lang('No approvers are configured. Please configure approvers in business settings first.'));
		}

		// Check if current user is in the configured approvers list
		if (!in_array($currentUserId, $configuredUserIds)) {
			return redirect()->route('cash_purchases.index')->with('error', _lang('You are not assigned as an approver for purchases'));
		}

		foreach ($request->ids as $id) {
			$purchase = Purchase::with('approvals')->find($id);
			
			if (!$purchase) {
				continue;
			}

			// Skip if already approved
			if ($purchase->approval_status == 1) {
				continue;
			}

			// Ensure approval records exist for this purchase
			if ($purchase->approvals->isEmpty()) {
				$this->createPurchaseApprovalRecords($purchase);
				$purchase->load('approvals');
			}

			// Find the current user's approval record
			$approval = $purchase->approvals()
				->where('action_user', $currentUserId)
				->first();

			if (!$approval) {
				continue; // User is not an approver for this purchase
			}

			// Update the approval record
			$approval->update([
				'status' => 1, // Approved
				'action_date' => now(),
			]);

			// Check if purchase should be marked as approved based on required approvals
			$this->checkAndUpdatePurchaseStatus($purchase);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = $currentUserId;
			$audit->event = 'Bulk Approved Cash Purchase ' . $purchase->bill_no;
			$audit->save();
		}

		return redirect()->route('cash_purchases.index')->with('success', _lang('Approved Successfully'));
	}

	public function bulk_reject(Request $request)
	{
		Gate::authorize('cash_purchases.reject');
		$currentUserId = auth()->id();

		// Get configured approvers and checkers
		$approvalUsersJson = get_business_option('purchase_approval_users', '[]');
		$approverUserIds = json_decode($approvalUsersJson, true);
		$isApprover = is_array($approverUserIds) && in_array($currentUserId, $approverUserIds);

		$checkerUsersJson = get_business_option('purchase_checker_users', '[]');
		$checkerUserIds = json_decode($checkerUsersJson, true);
		$isChecker = is_array($checkerUserIds) && in_array($currentUserId, $checkerUserIds);

		if (!$isApprover && !$isChecker) {
			return redirect()->route('cash_purchases.index')->with('error', _lang('You are not assigned as an approver or checker for purchases'));
		}

		$rejectedCount = 0;

		foreach ($request->ids as $id) {
			$purchase = Purchase::with(['approvals', 'checkers'])->find($id);
			
			if (!$purchase) {
				continue;
			}

			// Determine rejection type based on purchase status
			if ($purchase->approval_status == 1 && $isApprover) {
				// Purchase is in Approved state - reject as approver
				// Ensure approval records exist
				if ($purchase->approvals->isEmpty()) {
					$this->createPurchaseApprovalRecords($purchase);
					$purchase->load('approvals');
				}

				$approval = $purchase->approvals()
					->where('action_user', $currentUserId)
					->first();

				if (!$approval) {
					continue;
				}

				// Update the approver record as Rejected
				$approval->update([
					'status' => 2, // Rejected
					'action_date' => now(),
				]);

				// Reset purchase approval_status to Pending (back to Verified state)
				$purchase->update([
					'approval_status' => 0,
					'approved_by' => null,
				]);

				// Move transactions back to pending
				$this->moveTransactionsToPending($purchase);

				// Audit log
				$audit = new AuditLog();
				$audit->date_changed = date('Y-m-d H:i:s');
				$audit->changed_by = $currentUserId;
				$audit->event = 'Bulk Rejected Approved Cash Purchase ' . $purchase->bill_no . ' (Approver)';
				$audit->save();

				$rejectedCount++;

			} elseif ($purchase->checker_status == 1 && $isChecker) {
				// Purchase is in Verified state - reject as checker
				// Ensure checker records exist
				if ($purchase->checkers->isEmpty()) {
					$this->createPurchaseCheckerRecords($purchase);
					$purchase->load('checkers');
				}

				$checker = $purchase->checkers()
					->where('action_user', $currentUserId)
					->first();

				if (!$checker) {
					continue;
				}

				// Update the checker record as Rejected
				$checker->update([
					'status' => 2, // Rejected
					'action_date' => now(),
				]);

				// Reset purchase checker_status to Pending AND approval_status to Pending
				$purchase->update([
					'checker_status' => 0,
					'approval_status' => 0, // Reset to Pending
					'checked_by' => null,
				]);

				// Audit log
				$audit = new AuditLog();
				$audit->date_changed = date('Y-m-d H:i:s');
				$audit->changed_by = $currentUserId;
				$audit->event = 'Bulk Rejected Verified Cash Purchase ' . $purchase->bill_no . ' (Checker)';
				$audit->save();

				$rejectedCount++;
			}
			// Skip purchases in Pending state
		}

		if ($rejectedCount > 0) {
			return redirect()->route('cash_purchases.index')->with('success', _lang('Rejected Successfully') . ' (' . $rejectedCount . ')');
		} else {
			return redirect()->route('cash_purchases.index')->with('error', _lang('No purchases were rejected. They may already be in Pending status or you do not have the appropriate role.'));
		}
	}

	/**
	 * Approve a cash purchase (single user approval)
	 */
	public function approve(Request $request, $id)
	{
		$request->validate([
			'comment' => ['nullable', 'string', 'max:1000'],
		]);

		$purchase = Purchase::with('approvals')->find($id);

		if (!$purchase) {
			return back()->with('error', _lang('Purchase not found'));
		}

		$currentUserId = auth()->id();

		// Check if there are any configured approval users for this business
		$approvalUsersJson = get_business_option('purchase_approval_users', '[]');
		$configuredUserIds = json_decode($approvalUsersJson, true);
		$hasConfiguredApprovers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

		// If no approvers configured, tell user to configure them first
		if (!$hasConfiguredApprovers) {
			return back()->with('error', _lang('No approvers are configured. Please configure approvers in business settings first.'));
		}

		// Check if current user is in the configured approvers list
		if (!in_array($currentUserId, $configuredUserIds)) {
			return back()->with('error', _lang('You are not assigned as an approver for this purchase'));
		}

		// Find the approval record by action_user (who is assigned as approver)
		$approval = $purchase->approvals()
			->where('action_user', $currentUserId)
			->first();

		if (!$approval) {
			return back()->with('error', _lang('You are not assigned as an approver for this purchase'));
		}

		// Update the approval record (action_user remains the same - it's already the approver)
		$approval->update([
			'status' => 1, // Approved
			'comment' => $request->input('comment'),
			'action_date' => now(),
		]);

		// Check if purchase should be marked as approved based on required approvals
		$this->checkAndUpdatePurchaseStatus($purchase);

		// Audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = $currentUserId;
		$audit->event = 'Approved Cash Purchase ' . $purchase->bill_no . ' by ' . auth()->user()->name;
		$audit->save();

		return back()->with('success', _lang('Purchase approved successfully'));
	}

	/**
	 * Reject a cash purchase (conditional based on purchase status)
	 * - If Verified: Reset checker_status to Pending and mark checker record as Rejected
	 * - If Approved: Reset approval_status to Pending and mark approver record as Rejected
	 */
	public function reject(Request $request, $id)
	{
		$request->validate([
			'comment' => ['required', 'string', 'max:1000'],
		], [
			'comment.required' => _lang('Comment is required when rejecting'),
		]);

		$purchase = Purchase::with(['approvals', 'checkers'])->find($id);

		if (!$purchase) {
			return back()->with('error', _lang('Purchase not found'));
		}

		$currentUserId = auth()->id();

		// Determine rejection type based on purchase status
		// If purchase is Approved (approval_status = 1), reject as approver
		// If purchase is Verified (checker_status = 1, approval_status = 0), reject as checker
		
		if ($purchase->approval_status == 1) {
			// Purchase is in Approved state - reject as approver
			$approvalUsersJson = get_business_option('purchase_approval_users', '[]');
			$configuredUserIds = json_decode($approvalUsersJson, true);
			$hasConfiguredApprovers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

			if (!$hasConfiguredApprovers) {
				return back()->with('error', _lang('No approvers are configured. Please configure approvers in business settings first.'));
			}

			if (!in_array($currentUserId, $configuredUserIds)) {
				return back()->with('error', _lang('You are not assigned as an approver for this purchase'));
			}

			// Find the approval record for the current user
			$approval = $purchase->approvals()
				->where('action_user', $currentUserId)
				->first();

			if (!$approval) {
				return back()->with('error', _lang('You are not assigned as an approver for this purchase'));
			}

			// Update the approver record as Rejected
			$approval->update([
				'status' => 2, // Rejected
				'comment' => $request->input('comment'),
				'action_date' => now(),
			]);

			// Reset purchase approval_status to Pending (back to Verified state)
			$purchase->update([
				'approval_status' => 0,
				'approved_by' => null,
			]);

			// Move transactions back to pending
			$this->moveTransactionsToPending($purchase);

			// Audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = $currentUserId;
			$audit->event = 'Rejected Approved Cash Purchase ' . $purchase->bill_no . ' by ' . auth()->user()->name . ' (Approver)';
			$audit->save();

			return back()->with('success', _lang('Purchase approval rejected. Status reset to Verified.'));

		} elseif ($purchase->checker_status == 1) {
			// Purchase is in Verified state - reject as checker
			$checkerUsersJson = get_business_option('purchase_checker_users', '[]');
			$configuredUserIds = json_decode($checkerUsersJson, true);
			$hasConfiguredCheckers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

			if (!$hasConfiguredCheckers) {
				return back()->with('error', _lang('No checkers are configured. Please configure checkers in business settings first.'));
			}

			if (!in_array($currentUserId, $configuredUserIds)) {
				return back()->with('error', _lang('You are not assigned as a checker for this purchase'));
			}

			// Find the checker record for the current user
			$checker = $purchase->checkers()
				->where('action_user', $currentUserId)
				->first();

			if (!$checker) {
				return back()->with('error', _lang('You are not assigned as a checker for this purchase'));
			}

			// Update the checker record as Rejected
			$checker->update([
				'status' => 2, // Rejected
				'comment' => $request->input('comment'),
				'action_date' => now(),
			]);

			// Reset purchase checker_status to Pending AND approval_status to Pending
			$purchase->update([
				'checker_status' => 0,
				'approval_status' => 0, // Reset to Pending
				'checked_by' => null,
			]);

			// Audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = $currentUserId;
			$audit->event = 'Rejected Verified Cash Purchase ' . $purchase->bill_no . ' by ' . auth()->user()->name . ' (Checker)';
			$audit->save();

			return back()->with('success', _lang('Purchase verification rejected. Status reset to Pending.'));

		} else {
			// Purchase is in Pending state - cannot reject
			return back()->with('error', _lang('This purchase is already in Pending status and cannot be rejected.'));
		}
	}

	/**
	 * Check and update purchase status based on all approval records
	 * - Approved: Required count of approvers reached (from settings)
	 * - Rejected: ALL assigned users rejected (unanimous rejection)
	 * - Pending: Not enough approvals yet
	 */
	private function checkAndUpdatePurchaseStatus(Purchase $purchase): void
	{
		// Reload approvals to get the most current state
		$purchase->load('approvals');
		$approvals = $purchase->approvals;
		
		// If no approvals configured, nothing to check
		if ($approvals->isEmpty()) {
			return;
		}

		// Get the required approval count from settings for the current business
		// Default to 1 if not set (any single approver can approve the purchase)
		$requiredApprovalCount = (int) get_business_option('purchase_approval_required_count', 1);

		$totalApprovers = $approvals->count();
		$approvedCount = $approvals->where('status', 1)->count();
		$rejectedCount = $approvals->where('status', 2)->count();
		$pendingCount = $approvals->where('status', 0)->count();
		
		// Ensure required count doesn't exceed total approvers
		$requiredApprovalCount = min($requiredApprovalCount, $totalApprovers);
		
		// Store previous status to detect changes
		$previousStatus = $purchase->approval_status;

		// First check: If purchase was previously approved but now doesn't have enough approvals
		// (e.g., someone changed their vote from approve to reject), reset to pending immediately
		if ($previousStatus == 1 && $approvedCount < $requiredApprovalCount) {
			$purchase->update([
				'approval_status' => 0, // Back to Pending
				'approved_by' => null,
			]);
			
			// Move transactions back to pending_transactions
			$this->moveTransactionsToPending($purchase);
			return; // Exit early
		}

		// Second check: If ALL assigned users have rejected (unanimous rejection)
		if ($rejectedCount === $totalApprovers) {
			// Ensure it stays pending/rejected
			if ($previousStatus == 1) {
				// Was approved, now all rejected - move transactions back
				$purchase->update([
					'approval_status' => 0, // Rejected (stay pending)
					'approved_by' => null,
				]);
				$this->moveTransactionsToPending($purchase);
			} else {
				$purchase->update([
					'approval_status' => 0, // Rejected (stay pending)
				]);
			}
			return;
		}

		// Third check: If we have enough approvals AND not everyone rejected
		// This ensures we only approve when criteria is met and there's no blocking rejection
		if ($approvedCount >= $requiredApprovalCount && $rejectedCount < $totalApprovers) {
			// Only process if not already approved
			if ($previousStatus != 1) {
				$purchase->update([
					'approval_status' => 1, // Approved
					'approved_by' => auth()->id(),
				]);
				
				// Move pending transactions to transactions table
				$this->movePendingTransactionsToTransactions($purchase);
			}
			return;
		}

		// Default: Not enough approvals yet - ensure it stays pending
		if ($previousStatus == 1) {
			// Was approved but no longer meets criteria
			$purchase->update([
				'approval_status' => 0,
				'approved_by' => null,
			]);
			$this->moveTransactionsToPending($purchase);
		}
	}
	
	/**
	 * Move pending transactions to the transactions table when purchase is approved
	 */
	private function movePendingTransactionsToTransactions(Purchase $purchase): void
	{
		$transactions = PendingTransaction::where('ref_id', $purchase->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();

		foreach ($transactions as $transaction) {
			// Create a new Transaction instance and replicate data from pending
			$new_transaction = $transaction->replicate();
			$new_transaction->setTable('transactions'); // Change the table to 'transactions'
			$new_transaction->save();

			// Delete the pending transaction
			$transaction->forceDelete();
		}
	}
	
	/**
	 * Move transactions back to pending_transactions when purchase approval is revoked
	 */
	private function moveTransactionsToPending(Purchase $purchase): void
	{
		$transactions = Transaction::where('ref_id', $purchase->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax')
					->orWhere('ref_type', 'cash purchase payment');
			})
			->get();

		foreach ($transactions as $transaction) {
			// Create a new PendingTransaction instance and replicate data
			$new_pending = $transaction->replicate();
			$new_pending->setTable('pending_transactions'); // Change the table to 'pending_transactions'
			$new_pending->save();

			// Delete the transaction
			$transaction->forceDelete();
		}
	}

	/**
	 * Create approval records for all configured approvers for a given purchase
	 */
	private function createPurchaseApprovalRecords(Purchase $purchase): void
	{
		$purchaseApprovalUsersJson = get_business_option('purchase_approval_users', '[]');
		$configuredUserIds = json_decode($purchaseApprovalUsersJson, true);

		if (!is_array($configuredUserIds) || empty($configuredUserIds)) {
			return;
		}

		// Filter to only include user IDs that actually exist in the database
		$validUserIds = \App\Models\User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
		
		if (empty($validUserIds)) {
			return;
		}

		foreach ($validUserIds as $userId) {
			// Check if approval record already exists for this user
			$existingApproval = Approvals::where('ref_id', $purchase->id)
				->where('ref_name', 'purchase')
				->where('checker_type', 'approval')
				->where('action_user', $userId)
				->first();

			if (!$existingApproval) {
				Approvals::create([
					'ref_id' => $purchase->id,
					'ref_name' => 'purchase',
					'checker_type' => 'approval',
					'action_user' => $userId,
					'status' => 0, // pending
				]);
			}
		}
	}

	/**
	 * Create checker records for all configured checkers for a given purchase
	 */
	private function createPurchaseCheckerRecords(Purchase $purchase): void
	{
		$purchaseCheckerUsersJson = get_business_option('purchase_checker_users', '[]');
		$configuredUserIds = json_decode($purchaseCheckerUsersJson, true);

		if (!is_array($configuredUserIds) || empty($configuredUserIds)) {
			return;
		}

		// Filter to only include user IDs that actually exist in the database
		$validUserIds = \App\Models\User::whereIn('id', $configuredUserIds)->pluck('id')->toArray();
		
		if (empty($validUserIds)) {
			return;
		}

		foreach ($validUserIds as $userId) {
			// Check if checker record already exists for this user
			$existingChecker = Approvals::where('ref_id', $purchase->id)
				->where('ref_name', 'purchase')
				->where('checker_type', 'checker')
				->where('action_user', $userId)
				->first();

			if (!$existingChecker) {
				Approvals::create([
					'ref_id' => $purchase->id,
					'ref_name' => 'purchase',
					'checker_type' => 'checker',
					'action_user' => $userId,
					'status' => 0, // pending
				]);
			}
		}
	}

	/**
	 * Verify a cash purchase (single user verification - checker step)
	 */
	public function verify(Request $request, $id)
	{
		$request->validate([
			'comment' => ['nullable', 'string', 'max:1000'],
		]);

		$purchase = Purchase::with('checkers')->find($id);

		if (!$purchase) {
			return back()->with('error', _lang('Purchase not found'));
		}

		$currentUserId = auth()->id();

		// Check if there are any configured checker users for this business
		$checkerUsersJson = get_business_option('purchase_checker_users', '[]');
		$configuredUserIds = json_decode($checkerUsersJson, true);
		$hasConfiguredCheckers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

		// If no checkers configured, tell user to configure them first
		if (!$hasConfiguredCheckers) {
			return back()->with('error', _lang('No checkers are configured. Please configure checkers in business settings first.'));
		}

		// Check if current user is in the configured checkers list
		if (!in_array($currentUserId, $configuredUserIds)) {
			return back()->with('error', _lang('You are not assigned as a checker for this purchase'));
		}

		// Find the checker record by action_user (who is assigned as checker)
		$checker = $purchase->checkers()
			->where('action_user', $currentUserId)
			->first();

		if (!$checker) {
			return back()->with('error', _lang('You are not assigned as a checker for this purchase'));
		}

		// Update the checker record
		$checker->update([
			'status' => 1, // Verified
			'comment' => $request->input('comment'),
			'action_date' => now(),
		]);

		// Check if purchase should be marked as verified based on required verifications
		$this->checkAndUpdatePurchaseCheckerStatus($purchase);

		// Audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = $currentUserId;
		$audit->event = 'Verified Cash Purchase ' . $purchase->bill_no . ' by ' . auth()->user()->name;
		$audit->save();

		return back()->with('success', _lang('Purchase verified successfully'));
	}

	/**
	 * Bulk verify cash purchases
	 */
	public function bulk_verify(Request $request)
	{
		Gate::authorize('cash_purchases.verify');
		$currentUserId = auth()->id();

		// Check if there are any configured checker users for this business
		$checkerUsersJson = get_business_option('purchase_checker_users', '[]');
		$configuredUserIds = json_decode($checkerUsersJson, true);
		$hasConfiguredCheckers = is_array($configuredUserIds) && count($configuredUserIds) > 0;

		if (!$hasConfiguredCheckers) {
			return redirect()->route('cash_purchases.index')->with('error', _lang('No checkers are configured. Please configure checkers in business settings first.'));
		}

		// Check if current user is in the configured checkers list
		if (!in_array($currentUserId, $configuredUserIds)) {
			return redirect()->route('cash_purchases.index')->with('error', _lang('You are not assigned as a checker for purchases'));
		}

		foreach ($request->ids as $id) {
			$purchase = Purchase::with('checkers')->find($id);
			
			if (!$purchase) {
				continue;
			}

			// Skip if already verified
			/*if ($purchase->checker_status == 1) {
				continue;
			}*/

			// Ensure checker records exist for this purchase
			if ($purchase->checkers->isEmpty()) {
				$this->createPurchaseCheckerRecords($purchase);
				$purchase->load('checkers');
			}

			// Find the current user's checker record
			$checker = $purchase->checkers()
				->where('action_user', $currentUserId)
				->first();

			if (!$checker) {
				continue; // User is not a checker for this purchase
			}

			// Update the checker record
			$checker->update([
				'status' => 1, // Verified
				'action_date' => now(),
			]);

			// Check if purchase should be marked as verified based on required verifications
			$this->checkAndUpdatePurchaseCheckerStatus($purchase);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = $currentUserId;
			$audit->event = 'Bulk Verified Cash Purchase ' . $purchase->bill_no;
			$audit->save();
		}

		return redirect()->route('cash_purchases.index')->with('success', _lang('Verified Successfully'));
	}

	/**
	 * Check and update purchase checker status based on all checker records
	 * - Verified: Required count of checker verifications reached (from settings)
	 * - Pending: Not enough verifications yet
	 */
	private function checkAndUpdatePurchaseCheckerStatus(Purchase $purchase): void
	{
		// Reload checkers to get the most current state
		$purchase->load('checkers');
		$checkers = $purchase->checkers;
		
		// If no checkers configured, nothing to check
		if ($checkers->isEmpty()) {
			return;
		}

		// Get the required checker count from settings for the current business
		// Default to 1 if not set (any single checker can verify the purchase)
		$requiredCheckerCount = (int) get_business_option('purchase_checker_required_count', 1);

		$totalCheckers = $checkers->count();
		$verifiedCount = $checkers->where('status', 1)->count();
		$rejectedCount = $checkers->where('status', 2)->count();
		
		// Ensure required count doesn't exceed total checkers
		$requiredCheckerCount = min($requiredCheckerCount, $totalCheckers);
		
		// Store previous status to detect changes
		$previousStatus = $purchase->checker_status;

		// First check: If purchase was previously verified but now doesn't have enough verifications
		// (e.g., someone changed their vote from verify to reject), reset to pending immediately
		if ($previousStatus == 1 && $verifiedCount < $requiredCheckerCount) {
			$purchase->update([
				'checker_status' => 0, // Back to Pending
				'approval_status' => 0, // Back to Pending
				'checked_by' => null,
			]);
			return; // Exit early
		}

		// Second check: If ALL assigned checkers have rejected (unanimous rejection)
		if ($rejectedCount === $totalCheckers) {
			if ($previousStatus == 1) {
				$purchase->update([
					'checker_status' => 0, // Reset to pending
					'approval_status' => 0, // Reset to pending
					'checked_by' => null,
				]);
			}
			return;
		}

		// Third check: If we have enough verifications AND not everyone rejected
		if ($verifiedCount >= $requiredCheckerCount && $rejectedCount < $totalCheckers) {
			// Only process if not already verified
			if ($previousStatus != 1) {
				$purchase->update([
					'checker_status' => 1, // Verified
					'approval_status' => 4, // Verified
					'checked_by' => auth()->id(),
				]);
			}
			return;
		}

		// Default: Not enough verifications yet - ensure it stays pending
		if ($previousStatus == 1) {
			$purchase->update([
				'checker_status' => 0,
				'approval_status' => 0,
				'checked_by' => null,
			]);
		}
	}

	/**
	 * Sync checker records for a purchase with configured checkers
	 */
	private function syncCheckerRecordsForPurchase(Purchase $purchase, array $configuredCheckerUserIds): void
	{
		if (empty($configuredCheckerUserIds)) {
			return;
		}

		// Filter to only include user IDs that actually exist in the database
		$validUserIds = \App\Models\User::whereIn('id', $configuredCheckerUserIds)->pluck('id')->toArray();
		
		if (empty($validUserIds)) {
			return;
		}

		foreach ($validUserIds as $userId) {
			// Check if checker record already exists for this user
			$existingChecker = Approvals::where('ref_id', $purchase->id)
				->where('ref_name', 'purchase')
				->where('checker_type', 'checker')
				->where('action_user', $userId)
				->first();

			if (!$existingChecker) {
				Approvals::create([
					'ref_id' => $purchase->id,
					'ref_name' => 'purchase',
					'checker_type' => 'checker',
					'action_user' => $userId,
					'status' => 0, // pending
				]);
			}
		}

		// Remove checker records for users no longer in the checkers list
		// Only remove if they haven't taken action yet (status = 0)
		Approvals::where('ref_id', $purchase->id)
			->where('ref_name', 'purchase')
			->where('checker_type', 'checker')
			->where('status', 0)
			->whereNotIn('action_user', $validUserIds)
			->delete();
	}
}
