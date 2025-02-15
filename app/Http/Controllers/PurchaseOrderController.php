<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

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
	public function index()
	{
		$purchases = Purchase::select('purchases.*')
			->with('vendor')
			->where('order', 1)
			->orderBy("purchases.id", "desc")
			->get();

		return view('backend.user.purchase_order.list', compact('purchases'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		return view('backend.user.purchase_order.create');
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

		$purchase = new Purchase();
		$purchase->vendor_id = $request->input('vendor_id');
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
		$purchase->order = 1;
		$purchase->discount = $summary['discountAmount'];
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->withholding_tax = $request->input('withholding_tax') ?? 0;
		$purchase->footer = $request->input('footer');
		$purchase->short_code = rand(100000, 9999999) . uniqid();

		$purchase->save();

		// if attachments then upload
		if (isset($request->attachments['file'])) {
			if ($request->attachments['file'] != null) {
				for ($i = 0; $i < count($request->attachments['file']); $i++) {
					$theFile = $request->file("attachments.file.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments['file_name'][$i];
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'purchase order';
					$attachment->ref_id = $purchase->id;
					$attachment->save();
				}
			}
		}

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseItem([
				'purchase_id' => $purchase->id,
				'product_id' => isset($request->product_id[$i]) ? $request->product_id[$i] : null,
				'product_name' => $request->product_name[$i],
				'description' => null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));

			if (isset($request->taxes[$purchaseItem->product_name])) {
				foreach ($request->taxes[$purchaseItem->product_name] as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseItemTax([
						'purchase_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));
				}
			}
		}

		//Increment Bill Number
		BusinessSetting::where('name', 'purchase_number')->increment('value');

		DB::commit();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Invoice Created' . ' ' . $purchase->bill_no;
		$audit->save();

		if ($purchase->id > 0) {
			return redirect()->route('purchase_orders.show', $purchase->id)->with('success', _lang('Saved Successfully'));
		} else {
			return back()->with('error', _lang('Something going wrong, Please try again'));
		}
	}

	/**
	 * Preview Private Invoice
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(Request $request, $id)
	{
		$alert_col = 'col-lg-8 offset-lg-2';
		$purchase = Purchase::with(['business', 'items'])->find($id);
		$attachments = Attachment::where('ref_id', $id)->where('ref_type', 'purchase order')->get();
		return view('backend.user.purchase_order.view', compact('purchase', 'id', 'alert_col', 'attachments'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Request $request, $id)
	{
		$purchase = Purchase::with('items')
			->where('id', $id)
			->first();

		$attachments = Attachment::where('ref_id', $id)->where('ref_type', 'purchase order')->get();

		return view('backend.user.purchase_order.edit', compact('purchase', 'id', 'attachments'));
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
		$purchase->discount = $summary['discountAmount'];
		$purchase->discount_type = $request->input('discount_type');
		$purchase->discount_value = $request->input('discount_value') ?? 0;
		$purchase->template_type = 0;
		$purchase->template = $request->input('template');
		$purchase->note = $request->input('note');
		$purchase->withholding_tax = $request->input('withholding_tax') ?? 0;
		$purchase->footer = $request->input('footer');
		$purchase->save();

		// delete old attachments
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'bill invoice')->get(); // Get attachments from the database

		foreach ($attachments as $attachment) {
			// Only delete the file if it exist in the request attachments
			if (isset($request->attachments['file'])) {
				if (!$request->attachments['file'] == null && !in_array($attachment->path, $request->attachments['file'])) {
					$filePath = public_path($attachment->path);
					if (file_exists($filePath)) {
						unlink($filePath); // Delete the file
					}
					$attachment->delete(); // Delete the database record
				}
			}
		}

		// if attachments then upload
		if (isset($request->attachments['file'])) {
			if ($request->attachments['file'] != null) {
				for ($i = 0; $i < count($request->attachments['file']); $i++) {
					$theFile = $request->file("attachments.file.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments['file_name'][$i];
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

		$currentTime = Carbon::now();

		for ($i = 0; $i < count($request->product_name); $i++) {
			$purchaseItem = $purchase->items()->save(new PurchaseItem([
				'purchase_id' => $purchase->id,
				'product_id' => null,
				'product_name' => $request->product_name[$i],
				'description' => null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));
		}

		if ($purchase->bill_no == null) {
			//Increment Bill Number
			BusinessSetting::where('name', 'purchase_number')->increment('value');
		}

		DB::commit();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Purchase Order Updated' . ' ' . $purchase->bill_no;
		$audit->save();

		if (!$request->ajax()) {
			if ($purchase->status == 0) {
				return redirect()->route('purchase_orders.show', $purchase->id)->with('success', _lang('Updated Successfully'));
			} else {
				return redirect()->route('purchase_orders.index')->with('success', _lang('Updated Successfully'));
			}
		} else {
			return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $purchase, 'table' => '#invoices_table']);
		}
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

		return redirect()->route('purchase_orders.edit', $newPurchase->id);
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
		$audit->event = 'Purchase Order Deleted' . ' ' . $purchase->bill_no;
		$audit->save();

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
		return redirect()->route('purchase_orders.index')->with('success', _lang('Deleted Successfully'));
	}

	private function calculateTotal(Request $request)
	{
		$subTotal = 0;
		$taxAmount = 0;
		$discountAmount = 0;
		$grandTotal = 0;

		for ($i = 0; $i < count($request->product_id); $i++) {
			//Calculate Sub Total
			$line_qnt = $request->quantity[$i];
			$line_unit_cost = $request->unit_cost[$i];
			$line_total = ($line_qnt * $line_unit_cost);

			//Show Sub Total
			$subTotal = ($subTotal + $line_total);

			//Calculate Taxes
			if (isset($request->taxes[$request->product_id[$i]])) {
				for ($j = 0; $j < count($request->taxes[$request->product_id[$i]]); $j++) {
					$taxId = $request->taxes[$request->product_id[$i]][$j];
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
		$audit->event = 'Purchase Orders Imported ' . $request->file('bills_file')->getClientOriginalName();
		$audit->save();

		try {
			Excel::import(new BillInvoiceImport, $request->file('bills_file'));
		} catch (\Exception $e) {
			return back()->with('error', $e->getMessage());
		}

		return redirect()->route('purchase_orders.index')->with('success', _lang('Bills Imported'));
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

	public function purchases_all(Request $request)
	{
		if ($request->purchases == null) {
			return redirect()->route('purchase_orders.index')->with('error', _lang('Please Select a Purchase'));
		}

		$purchases = Purchase::whereIn('id', $request->purchases)->get();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = auth()->user()->id;
		$audit->event = 'Deleted ' . count($purchases) . ' Purchases';
		$audit->save();

		foreach ($purchases as $purchase) {
			// descrease stock
			foreach ($purchase->items as $purchaseItem) {
				$product = $purchaseItem->product;
				if ($product->type == 'product' && $product->stock_management == 1) {
					$product->stock = $product->stock - $purchaseItem->quantity;
					$product->save();
				}
			}
			// delete transactions
			$transactions = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'bill')->get();
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

			$purchase->delete();
		}

		return redirect()->route('purchase_orders.index')->with('success', _lang('Deleted Successfully'));
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
}
