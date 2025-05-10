<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Notifications\SendPurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmailTemplate;

class PurchaseOrderController extends Controller
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
		$sortColumn = $request->get('sorting.column', 'id');
		$sortDirection = $request->get('sorting.direction', 'desc');
		$vendorId = $request->get('vendor_id', '');
		$dateRange = $request->get('date_range', '');

		$query = PurchaseOrder::with('vendor');

		// Handle sorting
		if ($sortColumn === 'vendor.name') {
			$query->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
				->orderBy('vendors.name', $sortDirection)
				->select('purchase_orders.*');
		} else {
			$query->orderBy($sortColumn, $sortDirection);
		}

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('order_number', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		if ($vendorId) {
			$query->where('vendor_id', $vendorId);
		}

		if ($dateRange) {
			$startDate = Carbon::parse($dateRange[0])->startOfDay();
			$endDate = Carbon::parse($dateRange[1])->endOfDay();
			$query->whereBetween('order_date', [$startDate, $endDate]);
		}

		$orders = $query->paginate($perPage)->withQueryString();

		$vendors = Vendor::orderBy('name')->get();

		return Inertia::render('Backend/User/PurchaseOrder/List', [
			'orders' => $orders->items(),
			'meta' => [
				'current_page' => $orders->currentPage(),
				'per_page' => $orders->perPage(),
				'last_page' => $orders->lastPage(),
				'total' => $orders->total(),
			],
			'filters' => [
				'search' => $search,
				'per_page' => $perPage,
				'sorting' => $request->get('sorting', []),
				'vendor_id' => $vendorId,
				'date_range' => $dateRange,
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

		$purchase_order_title = get_business_option('purchase_order_title', 'Purchase Order');

		$inventory = Account::where('account_name', 'Inventory')->first();

		$decimalPlace = get_business_option('decimal_place', 2);

		return Inertia::render('Backend/User/PurchaseOrder/Create', [
			'vendors' => $vendors,
			'products' => $products,
			'currencies' => $currencies,
			'taxes' => $taxes,
			'accounts' => $accounts,
			'purchase_order_title' => $purchase_order_title,
			'decimalPlace' => $decimalPlace,
			'inventory' => $inventory,
			'base_currency' => $request->activeBusiness->currency,
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
			'order_date' => 'required|date',
			'product_name' => 'required',
			'currency'  =>  'required',
		], [
			'product_id.required' => _lang('You must add at least one item'),
		]);

		if ($validator->fails()) {
			return redirect()->route('purchase_orders.create')
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

		$purchase = new PurchaseOrder();
		$purchase->vendor_id = $request->input('vendor_id');
		$purchase->title = $request->input('title');
		$purchase->order_number = get_business_option('purchase_order_number');
		$purchase->order_date = Carbon::parse($request->input('order_date'))->format('Y-m-d');
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
		$purchase->footer = $request->input('footer');
		$purchase->created_by = auth()->user()->id;
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
					$attachment->ref_type = 'purchase order';
					$attachment->ref_id = $purchase->id;
					$attachment->save();
				}
			}
		}

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseOrderItem([
				'purchase_order_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => isset($request->description[$i]) ? $request->description[$i] : null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));

			if (isset($request->taxes)) {
				foreach ($request->taxes as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseOrderItemTax([
						'purchase_order_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));
				}
			}
		}

		//Increment Bill Number
		BusinessSetting::where('name', 'purchase_order_number')->increment('value');

		DB::commit();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Order Created' . ' ' . $purchase->bill_no;
		$audit->save();

		return redirect()->route('purchase_orders.show', $purchase->id)->with('success', _lang('Saved Successfully'));
	}

	/**
	 * Preview Private Invoice
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$purchase_order = PurchaseOrder::with(['business', 'items', 'taxes', 'vendor'])->find($id);
		$attachments = Attachment::where('ref_id', $id)->where('ref_type', 'purchase order')->get();
		$email_templates = EmailTemplate::whereIn('slug', ['NEW_PURCHASE_ORDER_CREATED'])
			->where('email_status', 1)
			->get();

		return Inertia::render('Backend/User/PurchaseOrder/View', [
			'purchase_order' => $purchase_order,
			'attachments' => $attachments,
			'email_templates' => $email_templates,
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$purchase_order = PurchaseOrder::with(['business', 'items', 'taxes', 'vendor'])
			->where('id', $id)
			->first();

		$theAttachments = Attachment::where('ref_id', $id)->where('ref_type', 'purchase order')->get();
		$accounts = Account::all();
		$currencies = Currency::all();
		$vendors = Vendor::all();
		$products = Product::all();
		$taxes = Tax::all();
		$inventory = Account::where('account_name', 'Inventory')->first();
		$taxIds = $purchase_order->taxes
			->pluck('tax_id')
			->map(fn($id) => (string) $id)
			->toArray();

		return Inertia::render('Backend/User/PurchaseOrder/Edit', [
			'purchase_order' => $purchase_order,
			'theAttachments' => $theAttachments,
			'accounts' => $accounts,
			'currencies' => $currencies,
			'vendors' => $vendors,
			'products' => $products,
			'taxes' => $taxes,
			'inventory' => $inventory,
			'taxIds' => $taxIds,
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
			'order_date' => 'required|date',
			'product_id' => 'required',
		], [
			'product_id.required' => _lang('You must add at least one item'),
		]);

		if ($validator->fails()) {
			return redirect()->route('purchase_orders.edit', $id)
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

		$purchase = PurchaseOrder::where('id', $id)
			->first();
		$purchase->vendor_id = $request->input('vendor_id') ?? null;
		$purchase->title = $request->input('title');
		$purchase->order_date = Carbon::parse($request->input('order_date'))->format('Y-m-d');
		$purchase->sub_total = $summary['subTotal'];
		$purchase->grand_total = $summary['grandTotal'];
		$purchase->converted_total = $request->input('converted_total');
		$purchase->exchange_rate   = $request->input('exchange_rate');
		$purchase->discount = $summary['discountAmount'];
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->footer = $request->input('footer');
		$purchase->save();

		// delete old attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'purchase order')->get(); // Get attachments from the database

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
					$attachment->ref_type = 'purchase order';
					$attachment->ref_id = $purchase->id;
					$attachment->save();
				}
			}
		}

		//Update Invoice item
		foreach ($purchase->items as $purchase_item) {
			$purchase_item->delete();
		}

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseOrderItem([
				'purchase_order_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => isset($request->description[$i]) ? $request->description[$i] : null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));

			if (isset($request->taxes)) {

				$purchaseItem->taxes()->delete();

				foreach ($request->taxes as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseOrderItemTax([
						'purchase_order_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));
				}
			}
		}

		if ($purchase->order_number == null) {
			//Increment Order Number
			BusinessSetting::where('name', 'purchase_order_number')->increment('value');
		}

		DB::commit();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Order Updated' . ' ' . $purchase->order_number;
		$audit->save();

		return redirect()->route('purchase_orders.show', $purchase->id)->with('success', _lang('Updated Successfully'));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		$purchase = PurchaseOrder::find($id);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Order Deleted' . ' ' . $purchase->order_number;
		$audit->save();

		// delete attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'purchase order')->get();
		foreach ($attachments as $attachment) {
			$filePath = public_path($attachment->path);
			if (file_exists($filePath)) {
				unlink($filePath);
			}
			$attachment->delete();
		}

		$purchase->delete();
		return redirect()->route('purchase_orders.index')->with('success', _lang('Deleted Successfully'));
	}

	public function bulk_destroy(Request $request)
	{
		$purchases = PurchaseOrder::whereIn('id', $request->ids)->get();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = count($purchases) . 'Purchae Orders Deleted';
		$audit->save();

		foreach ($purchases as $order) {
			$order->delete();
		}

		return redirect()->route('purchase_orders.index')->with('success', _lang('Deleted Successfully'));
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

	public function import_purchase_orders(Request $request)
	{
		$request->validate([
			'orders_file' => 'required|mimes:xls,xlsx',
		]);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Orders Imported ' . $request->file('orders_file')->getClientOriginalName();
		$audit->save();

		try {
			Excel::import(new PurchaseOrderImport, $request->file('orders_file'));
		} catch (\Exception $e) {
			return back()->with('error', $e->getMessage());
		}

		return redirect()->route('purchase_orders.index')->with('success', _lang('Purchase Orders Imported'));
	}

	public function purchases_filter(Request $request)
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

		return view('backend.user.purchase_order.list', compact('purchases', 'status', 'vendor_id', 'date_range'));
	}

	public function export_purchase_orders()
	{
		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Orders Exported';
		$audit->save();

		return Excel::download(new BillInvoiceExport, 'purchases ' . now()->format('d m Y') . '.xlsx');
	}

	public function convert_to_bill($id)
	{
		DB::beginTransaction();
		try {
			$purchase_order = PurchaseOrder::find($id);
			$purchase_order->status = 1;
			$purchase_order->save();

			$bill = new Purchase();
			$bill->vendor_id = $purchase_order->vendor_id;
			$bill->title = $purchase_order->title;
			$bill->bill_no = get_business_option('purchase_number');
			$bill->po_so_number = $purchase_order->po_so_number;
			$bill->purchase_date = Carbon::parse($purchase_order->purchase_date)->format('Y-m-d');
			$bill->due_date = Carbon::parse($purchase_order->due_date)->format('Y-m-d');
			$bill->sub_total = $purchase_order->sub_total;
			$bill->grand_total = $purchase_order->grand_total;
			$bill->converted_total = $purchase_order->converted_total;
			$bill->exchange_rate = $purchase_order->exchange_rate;
			$bill->currency = $purchase_order->currency;
			$bill->paid = 0;
			$bill->discount = $purchase_order->discount;
			$bill->discount_type = $purchase_order->discount_type;
			$bill->discount_value = $purchase_order->discount_value;
			$bill->template_type = 0;
			$bill->template = $purchase_order->template;
			$bill->note = $purchase_order->note;
			$bill->footer = $purchase_order->footer;
			if (has_permission('bill_invoices.approve') || request()->isOwner) {
				$bill->approval_status = 1;
			} else {
				$bill->approval_status = 0;
			}
			$bill->created_by = auth()->user()->id;
			if (has_permission('bill_invoices.approve') || request()->isOwner) {
				$bill->approved_by = auth()->user()->id;
			} else {
				$bill->approved_by = null;
			}
			$bill->short_code = rand(100000, 9999999) . uniqid();

			$bill->save();

			// Set current time for transaction dates
			$currentTime = Carbon::now();

			// Create transactions for each purchase item
			foreach ($purchase_order->items as $purchaseItem) {
				$bill->items()->create([
					'product_id' => $purchaseItem->product_id,
					'quantity' => $purchaseItem->quantity,
					'price' => $purchaseItem->price,
					'sub_total' => $purchaseItem->sub_total,
					'taxes' => $purchaseItem->taxes,
					'total' => $purchaseItem->total,
				]);
				// Create transaction for the item
				$transaction = new Transaction();
				$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
				$transaction->account_id = $purchaseItem->account_id;
				$transaction->dr_cr = 'dr';
				$transaction->transaction_amount = $purchaseItem->sub_total;
				$transaction->transaction_currency = $bill->currency;
				$transaction->currency_rate = $bill->exchange_rate;
				$transaction->base_currency_amount = convert_currency($bill->currency, $bill->business->currency, $purchaseItem->sub_total);
				$transaction->ref_type = 'bill invoice';
				$transaction->vendor_id = $bill->vendor_id;
				$transaction->ref_id = $bill->id;
				$transaction->description = 'Bill Invoice #' . $bill->bill_no;
				$transaction->save();

				// increase product stock
				if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
					$purchaseItem->product->stock = $purchaseItem->product->stock + $purchaseItem->quantity;
					$purchaseItem->product->save();
				}

				// Create transactions for taxes if any
				foreach ($purchaseItem->taxes as $tax) {
					$bill->taxes()->create([
						'purchase_id' => $bill->id,
						'tax_id' => $tax->tax_id,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]);
					$transaction = new Transaction();
					$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
					$transaction->account_id = $tax->account_id;
					$transaction->dr_cr = 'dr';
					$transaction->transaction_amount = $tax->amount;
					$transaction->transaction_currency = $bill->currency;
					$transaction->currency_rate = $bill->exchange_rate;
					$transaction->base_currency_amount = convert_currency($bill->currency, $bill->business->currency, $tax->amount);
					$transaction->description = _lang('Bill Invoice Tax') . ' #' . $bill->bill_no;
					$transaction->ref_id = $bill->id;
					$transaction->ref_type = 'bill invoice tax';
					$transaction->tax_id = $tax->tax_id;
					$transaction->save();
				}
			}

			// Create accounts payable transaction
			$transaction = new Transaction();
			$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
			$transaction->account_id = get_account('Accounts Payable')->id;
			$transaction->dr_cr = 'cr';
			$transaction->transaction_amount = $bill->grand_total;
			$transaction->transaction_currency = $bill->currency;
			$transaction->currency_rate = $bill->exchange_rate;
			$transaction->base_currency_amount = convert_currency($bill->currency, $bill->business->currency, $bill->grand_total);
			$transaction->ref_type = 'bill invoice';
			$transaction->vendor_id = $bill->vendor_id;
			$transaction->ref_id = $bill->id;
			$transaction->description = 'Bill Invoice Payable #' . $bill->bill_no;
			$transaction->save();

			// Create discount transaction if applicable
			if ($bill->discount > 0) {
				$transaction = new Transaction();
				$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
				$transaction->account_id = get_account('Purchase Discount Allowed')->id;
				$transaction->dr_cr = 'cr';
				$transaction->transaction_amount = $bill->discount;
				$transaction->transaction_currency = $bill->currency;
				$transaction->currency_rate = $bill->exchange_rate;
				$transaction->base_currency_amount = convert_currency($bill->currency, $bill->business->currency, $bill->discount);
				$transaction->description = _lang('Bill Invoice Discount') . ' #' . $bill->bill_no;
				$transaction->ref_id = $bill->id;
				$transaction->ref_type = 'bill invoice';
				$transaction->vendor_id = $bill->vendor_id;
				$transaction->save();
			}

			$bill->save();

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Purchase Order Converted to Bill Invoice';
			$audit->save();

			DB::commit();
			return redirect()->route('purchase_orders.index')->with('success', _lang('Converted Successfully'));
		} catch (\Exception $e) {
			DB::rollback();
			return redirect()->route('purchase_orders.index')->with('error', _lang('An error occurred while converting the purchase order'));
		}
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

		$purchase_order = PurchaseOrder::find($id);
		$vendor = $purchase_order->vendor;
		$vendor->email = $request->email;

		try {
			Notification::send($vendor, new SendPurchaseOrder($purchase_order, $customMessage, $request->template));
			$purchase_order->email_send = 1;
			$purchase_order->email_send_at = now();
			$purchase_order->save();

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Sent Purchase Order ' . $purchase_order->order_number . ' to ' . $vendor->email;
			$audit->save();

			return redirect()->back()->with('success', _lang('Email has been sent'));
		} catch (\Exception $e) {
			return redirect()->back()->with('error', $e->getMessage());
		}
	}

	public function show_public_purchase_order($short_code)
	{
		$purchase_order   = PurchaseOrder::withoutGlobalScopes()->with(['vendor', 'business', 'items', 'taxes'])
			->where('short_code', $short_code)
			->first();

		$request = request();
		// add activeBusiness object to request
		$request->merge(['activeBusiness' => $purchase_order->business]);

		return Inertia::render('Backend/User/PurchaseOrder/PublicView', [
			'purchase_order' => $purchase_order,
			'request' => $request,
		]);
	}

	public function convert_to_cash_purchase($id)
	{
		DB::beginTransaction();
		$purchase_order = PurchaseOrder::find($id);
		$purchase_order->status = 1;
		$purchase_order->save();

		$cash_purchase = new Purchase();
		$cash_purchase->vendor_id = $purchase_order->vendor_id;
		$cash_purchase->title = $purchase_order->title;
		$cash_purchase->bill_no = get_business_option('purchase_number');
		$cash_purchase->po_so_number = $purchase_order->po_so_number;
		$cash_purchase->purchase_date = Carbon::parse($purchase_order->purchase_date)->format('Y-m-d');
		$cash_purchase->due_date = Carbon::parse($purchase_order->purchase_date)->format('Y-m-d');
		$cash_purchase->sub_total = $purchase_order->sub_total;
		$cash_purchase->grand_total = $purchase_order->grand_total;
		$cash_purchase->converted_total = $purchase_order->converted_total;
		$cash_purchase->exchange_rate = $purchase_order->exchange_rate;
		$cash_purchase->currency = $purchase_order->currency;
		$cash_purchase->paid = 0;
		$cash_purchase->discount = $purchase_order->discount;
		$cash_purchase->cash = 1;
		$cash_purchase->discount_type = $purchase_order->discount_type;
		$cash_purchase->discount_value = $purchase_order->discount_value;
		$cash_purchase->template_type = 0;
		$cash_purchase->template = $purchase_order->template;
		$cash_purchase->note = $purchase_order->note;
		$cash_purchase->footer = $purchase_order->footer;
		if (has_permission('cash_purchases.approve') || request()->isOwner) {
			$cash_purchase->approval_status = 1;
		} else {
			$cash_purchase->approval_status = 0;
		}
		$cash_purchase->created_by = auth()->user()->id;
		if (has_permission('cash_purchases.approve') || request()->isOwner) {
			$cash_purchase->approved_by = auth()->user()->id;
		} else {
			$cash_purchase->approved_by = null;
		}
		$cash_purchase->short_code = rand(100000, 9999999) . uniqid();
		$cash_purchase->status = 2;

		$cash_purchase->save();

		// Set current time for transaction dates
		$currentTime = Carbon::now();

		// Create transactions for each purchase item
		foreach ($purchase_order->items as $purchaseItem) {

			$cash_purchase->items()->create([
				'product_id' => $purchaseItem->product_id,
				'quantity' => $purchaseItem->quantity,
				'price' => $purchaseItem->price,
				'sub_total' => $purchaseItem->sub_total,
				'taxes' => $purchaseItem->taxes,
				'total' => $purchaseItem->total,
			]);
			// Create transaction for the item
			$transaction = new Transaction();
			$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
			$transaction->account_id = $purchaseItem->account_id;
			$transaction->dr_cr = 'dr';
			$transaction->transaction_amount = $purchaseItem->sub_total;
			$transaction->transaction_currency = $cash_purchase->currency;
			$transaction->currency_rate = $cash_purchase->exchange_rate;
			$transaction->base_currency_amount = convert_currency($cash_purchase->currency, $cash_purchase->business->currency, $purchaseItem->sub_total);
			$transaction->ref_type = 'cash purchase';
			$transaction->vendor_id = $cash_purchase->vendor_id;
			$transaction->ref_id = $cash_purchase->id;
			$transaction->description = 'Cash Purchase #' . $cash_purchase->bill_no;
			$transaction->save();

			// increase product stock
			if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
				$purchaseItem->product->stock += $purchaseItem->quantity;
				$purchaseItem->product->save();
			}

			// Create transactions for taxes if any
			foreach ($purchaseItem->taxes as $tax) {

				$cash_purchase->taxes()->create([
					'purchase_id' => $cash_purchase->id,
					'tax_id' => $tax->tax_id,
					'name' => $tax->name . ' ' . $tax->rate . ' %',
					'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
				]);
				$transaction = new Transaction();
				$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
				$transaction->account_id = $tax->tax->account_id;
				$transaction->dr_cr = 'dr';
				$transaction->transaction_amount = $tax->amount;
				$transaction->transaction_currency = $cash_purchase->currency;
				$transaction->currency_rate = $cash_purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($cash_purchase->currency, $cash_purchase->business->currency, $tax->amount);
				$transaction->description = _lang('Cash Purchase Tax') . ' #' . $cash_purchase->bill_no;
				$transaction->ref_id = $cash_purchase->id;
				$transaction->ref_type = 'cash purchase tax';
				$transaction->tax_id = $tax->tax_id;
				$transaction->save();
			}
		}

		// Create credit account transaction
		$transaction = new Transaction();
		$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
		$transaction->account_id = request()->input('credit_account_id');
		$transaction->dr_cr = 'cr';
		$transaction->transaction_amount = $cash_purchase->grand_total;
		$transaction->transaction_currency = $cash_purchase->currency;
		$transaction->currency_rate = $cash_purchase->exchange_rate;
		$transaction->base_currency_amount = convert_currency($cash_purchase->currency, $cash_purchase->business->currency, $cash_purchase->grand_total);
		$transaction->ref_type = 'cash purchase payment';
		$transaction->ref_id = $cash_purchase->id;
		$transaction->description = 'Cash Purchase #' . $cash_purchase->bill_no;
		$transaction->save();

		// Create discount transaction if applicable
		if ($cash_purchase->discount > 0) {
			$transaction = new Transaction();
			$transaction->trans_date = $currentTime->format('Y-m-d H:i:s');
			$transaction->account_id = get_account('Purchase Discount Allowed')->id;
			$transaction->dr_cr = 'cr';
			$transaction->transaction_amount = $cash_purchase->discount;
			$transaction->transaction_currency = $cash_purchase->currency;
			$transaction->currency_rate = $cash_purchase->exchange_rate;
			$transaction->base_currency_amount = convert_currency($cash_purchase->currency, $cash_purchase->business->currency, $cash_purchase->discount);
			$transaction->description = _lang('Cash Purchase Discount') . ' #' . $cash_purchase->bill_no;
			$transaction->ref_id = $cash_purchase->id;
			$transaction->ref_type = 'cash purchase';
			$transaction->vendor_id = $cash_purchase->vendor_id;
			$transaction->save();
		}

		$cash_purchase->save();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Order Converted to Cash Purchase';
		$audit->save();

		DB::commit();
		return redirect()->route('purchase_orders.index')->with('success', _lang('Converted Successfully'));
	}
}
