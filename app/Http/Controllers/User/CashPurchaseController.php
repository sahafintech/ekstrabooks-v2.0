<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Validator;

class CashPurchaseController extends Controller
{
	public function index(Request $request)
	{
		$search = $request->get('search', '');
		$perPage = $request->get('per_page', 10);

		$query = Purchase::with('vendor')
			->where('cash', 1)
			->orderBy('id', 'desc');

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->where('bill_no', 'like', "%$search%")
					->orWhere('title', 'like', "%$search%")
					->orWhereHas('vendor', function ($q) use ($search) {
						$q->where('name', 'like', "%$search%");
					});
			});
		}

		$purchases = $query->paginate($perPage)->withQueryString();

		return Inertia::render('Backend/User/CashPurchase/List', [
			'purchases' => $purchases->items(),
			'meta' => [
				'current_page' => $purchases->currentPage(),
				'per_page' => $purchases->perPage(),
				'last_page' => $purchases->lastPage(),
				'total' => $purchases->total(),
			],
			'filters' => [
				'search' => $search,
				'per_page' => $perPage,
			],
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
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

		return Inertia::render('Backend/User/CashPurchase/Create', [
			'vendors' => $vendors,
			'products' => $products,
			'currencies' => $currencies,
			'inventory' => $inventory,
			'taxes' => $taxes,
			'accounts' => $accounts,
			'purchase_title' => $purchase_title,
			'base_currency' => $base_currency,
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
			'vendor_id' => 'nullable',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'product_name' => 'required',
			'currency'  =>  'required',
			'credit_account_id' => 'required',
		], [
			'product_name.required' => _lang('You must add at least one item'),
		]);

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
		if (has_permission('cash_purchases.approve') || request()->isOwner) {
			$purchase->approval_status = 1;
		} else {
			$purchase->approval_status = 0;
		}
		$purchase->created_by = auth()->user()->id;
		if (has_permission('cash_purchases.approve') || request()->isOwner) {
			$purchase->approved_by = auth()->user()->id;
		} else {
			$purchase->approved_by = null;
		}
		$purchase->benificiary = $request->input('benificiary');
		$purchase->short_code = rand(100000, 9999999) . uniqid();
		$purchase->paid = $summary['grandTotal'];
		$purchase->status = 2;

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
				'description' => null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));


			if (isset($request->taxes)) {
				foreach ($request->taxes as $taxId) {
					$tax = Tax::find($taxId);

					$purchaseItem->taxes()->save(new PurchaseItemTax([
						'purchase_id' => $purchase->id,
						'tax_id' => $taxId,
						'name' => $tax->name . ' ' . $tax->rate . ' %',
						'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
					]));

					if (has_permission('cash_purchases.approve') || request()->isOwner) {
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
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('cash_purchases.approve') || request()->isOwner) {

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

		if (has_permission('cash_purchases.approve') || request()->isOwner) {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
				$transaction->save();
			} else {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
				$transaction->save();
			}
		} else {
			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
				$transaction->save();
			} else {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
				$transaction->ref_type    = 'cash purchase';
				$transaction->vendor_id   = $purchase->vendor_id;
				$transaction->save();
			}
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
		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);
		$attachments = Attachment::where('ref_type', 'cash purchase')->where('ref_id', $id)->get();

		return Inertia::render('Backend/User/CashPurchase/View', [
			'bill' => $bill,
			'attachments' => $attachments,
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
		$bill = Purchase::with(['business', 'items', 'taxes', 'vendor'])->find($id);

		if (!has_permission('cash_purchases.approve') && !request()->isOwner && $bill->approval_status == 1) {
			return back()->with('error', _lang('Permission denied'));
		}

		if ($bill->approval_status == 1) {
			$credit_account = Transaction::where('ref_id', $id)->where('ref_type', 'cash purchase payment')->first();
		} else {
			$credit_account = PendingTransaction::where('ref_id', $id)->where('ref_type', 'cash purchase payment')->first();
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

		return Inertia::render('Backend/User/CashPurchase/Edit', [
			'bill' => $bill,
			'theAttachments' => $theAttachments,
			'credit_account' => $credit_account->account_id,
			'accounts' => $accounts,
			'currencies' => $currencies,
			'vendors' => $vendors,
			'products' => $products,
			'taxes' => $taxes,
			'inventory' => $inventory,
			'taxIds' => $taxIds
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
			'vendor_id' => 'nullable',
			'title' => 'required',
			'purchase_date' => 'required|date',
			'product_name' => 'required',
			'credit_account_id' => 'required',
		], [
			'product_name.required' => _lang('You must add at least one item'),
		]);

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
		$attachments = Attachment::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')->get(); // Get attachments from the database

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

			$purchase_item->delete();

			// delete transaction
			$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
				->where('account_id', $purchase_item->account_id)
				->first();

			if ($transaction != null) {
				$transaction->delete();
			}

			// delete pending transaction
			$pending_transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
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
				'description' => null,
				'quantity' => $request->quantity[$i],
				'unit_cost' => $request->unit_cost[$i],
				'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
				'account_id' => $request->account_id[$i],
			]));

			if (has_permission('cash_purchases.approve') || request()->isOwner && $purchase->approval_status == 1) {
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
							$transaction->save();
						}
					}
				}
			}

			if (has_permission('cash_purchases.approve') || request()->isOwner && $purchase->approval_status == 1) {

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

		if (has_permission('cash_purchases.approve') || request()->isOwner && $purchase->approval_status == 1) {

			$transaction = Transaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase payment')
				->first();

			// delete
			if ($transaction != null) {
				$transaction->delete();
			}

			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($summary['grandTotal'] - $summary['taxAmount']));
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($summary['grandTotal'] - $summary['taxAmount'])));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
				$transaction->save();
			} else {
				$transaction              = new Transaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
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
					$transaction->save();
				} else {
					$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
					$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
					$transaction->save();
				}
			}
		} else {
			$transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase payment')
				->first();

			// delete
			if ($transaction != null) {
				$transaction->delete();
			}

			if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($summary['grandTotal'] - $summary['taxAmount']));
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($summary['grandTotal'] - $summary['taxAmount'])));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
				$transaction->save();
			} else {
				$transaction              = new PendingTransaction();
				$transaction->trans_date  = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
				$transaction->account_id  = $request->input('credit_account_id');
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
				$transaction->transaction_currency    = $request->currency;
				$transaction->currency_rate = $purchase->exchange_rate;
				$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
				$transaction->ref_type    = 'cash purchase payment';
				$transaction->ref_id      = $purchase->id;
				$transaction->description = 'Cash Purchase #' . $purchase->bill_no;
				$transaction->save();
			}


			if ($request->input('discount_value') == 0) {
				$transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
					->where('account_id', get_account('Purchase Discount Allowed')->id)
					->first();
				if ($transaction != null) {
					$transaction->delete();
				}
			}

			if ($request->input('discount_value') > 0) {
				$transaction = PendingTransaction::where('ref_id', $purchase->id)->where('ref_type', 'cash purchase')
					->where('account_id', get_account('Purchase Discount Allowed')->id)
					->first();
				if ($transaction == null) {
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
					$transaction->ref_type    = 'cash purchase';
					$transaction->vendor_id   = $purchase->vendor_id;
					$transaction->save();
				} else {
					$transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
					$transaction->trans_date = Carbon::parse($request->input('purchase_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
					$transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
					$transaction->save();
				}
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

	public function cash_purchases_filter(Request $request)
	{
		$from =  explode('to', $request->date_range)[0] ?? '';
		$to = explode('to', $request->date_range)[1] ?? '';

		$query = Purchase::select('purchases.*')
			->with('vendor');

		if ($request->vendor_id != '') {
			$query->where('vendor_id', $request->vendor_id);
		}

		if ($from != '' && $to != '') {
			$query->whereDate('purchase_date', '>=', Carbon::parse($from)->format('Y-m-d'))
				->whereDate('purchase_date', '<=', Carbon::parse($to)->format('Y-m-d'));
		}

		if ($request->approval_status != '') {
			$query->where('approval_status', $request->approval_status);
		}

		$purchases = $query->get();

		$approval_status = $request->approval_status;
		$vendor_id = $request->vendor_id;
		$date_range = $request->date_range;

		return view('backend.user.cash_purchase.list', compact('purchases', 'approval_status', 'vendor_id', 'date_range'));
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
		}

		// delete transactions
		$transactions = Transaction::where('ref_id', $bill->id)
			->where(function ($query) {
				$query->where('ref_type', 'cash purchase')
					->orWhere('ref_type', 'cash purchase tax');
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
					->orWhere('ref_type', 'cash purchase tax');
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

		// delete attachments
		$attachments = Attachment::where('ref_id', $bill->id)->where('ref_type', 'cash purchase')->get();
		foreach ($attachments as $attachment) {
			$filePath = public_path($attachment->path);
			if (file_exists($filePath)) {
				unlink($filePath);
			}
			$attachment->delete();
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
			}

			// delete transactions
			$transactions = Transaction::where('ref_id', $bill->id)
				->where(function ($query) {
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax');
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
						->orWhere('ref_type', 'cash purchase tax');
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

			// delete attachments
			$attachments = Attachment::where('ref_id', $bill->id)->where('ref_type', 'cash purchase')->get();
			foreach ($attachments as $attachment) {
				$filePath = public_path($attachment->path);
				if (file_exists($filePath)) {
					unlink($filePath);
				}
				$attachment->delete();
			}

			$bill->delete();
			return redirect()->route('cash_purchases.index')->with('success', _lang('Deleted Successfully'));
		}
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
			$audit->event = 'Approved Cash Purchase ' . $bill->bill_no;
			$audit->save();
		}

		return redirect()->route('cash_purchases.index')->with('success', _lang('Approved Successfully'));
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
					$query->where('ref_type', 'cash purchase')
						->orWhere('ref_type', 'cash purchase tax');
				})
				->get();

			foreach ($transactions as $transaction) {
				$new_transaction = $transaction->replicate();
				$new_transaction->setTable('pending_transactions');
				$new_transaction->save();

				$transaction->delete();
			}

			// bill invoice payment
			$transaction = Transaction::where('ref_id', $bill->id)->where('ref_type', 'cash purchase payment')->get();
			foreach ($transaction as $data) {
				$data->delete();
			}


			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = 'Rejected Cash Purchase ' . $bill->bill_no;
			$audit->save();
		}
		return redirect()->route('cash_purchases.index')->with('success', _lang('Rejected Successfully'));
	}
}
