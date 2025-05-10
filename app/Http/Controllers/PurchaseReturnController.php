<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseReturnItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $vendorId = $request->get('vendor_id', '');
        $dateRange = $request->get('date_range', '');
        $status = $request->get('status', '');

        $query = PurchaseReturn::with('vendor');

        // Handle sorting
        if ($sortColumn === 'vendor.name') {
            $query->join('vendors', 'purchase_returns.vendor_id', '=', 'vendors.id')
                ->orderBy('vendors.name', $sortDirection)
                ->select('purchase_returns.*');
        } else {
            $query->orderBy('purchase_returns.' . $sortColumn, $sortDirection);
        }

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%$search%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if ($dateRange) {
            $query->whereDate('return_date', '>=', Carbon::parse($dateRange[0])->format('Y-m-d'))
                ->whereDate('return_date', '<=', Carbon::parse($dateRange[1])->format('Y-m-d'));
        }

        if ($status) {
            $query->where('status', $status);
        }

        $returns = $query->paginate($perPage);
        $accounts = Account::all();
        $vendors = Vendor::all();

        return Inertia::render('Backend/User/PurchaseReturn/List', [
            'returns' => $returns->items(),
            'accounts' => $accounts,
            'vendors' => $vendors,
            'meta' => [
                'total' => $returns->total(),
                'per_page' => $returns->perPage(),
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'from' => $returns->firstItem(),
                'to' => $returns->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'vendor_id' => $vendorId,
                'date_range' => $dateRange,
                'status' => $status,
                'sorting' => $sorting,
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
        $purchase_return_title = get_business_option('purchase_return_title', 'Return');
        $vendors = Vendor::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $accounts = Account::all();

        return Inertia::render('Backend/User/PurchaseReturn/Create', [
            'purchase_return_title' => $purchase_return_title,
            'vendors' => $vendors,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'base_currency' => get_business_option('currency'),
        ]);
    }

    public function show(Request $request, $id)
    {
        $purchase_return  = PurchaseReturn::with(['business', 'items', 'taxes', 'vendor'])->find($id);

        return Inertia::render('Backend/User/PurchaseReturn/View', [
            'purchase_return' => $purchase_return,
        ]);
    }

    public function show_public_purchase_return($short_code)
	{
		$purchase_return   = PurchaseReturn::where('short_code', $short_code)->with('vendor', 'business', 'items', 'taxes')->first();

		$request = request();
		// add activeBusiness object to request
		$request->merge(['activeBusiness' => $purchase_return->business]);

		return Inertia::render('Backend/User/PurchaseReturn/PublicView', [
			'purchase_return' => $purchase_return,
			'request' => $request,
		]);
	}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id'    => 'required',
            'title'          => 'required',
            'return_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('purchase_returns.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $return                  = new PurchaseReturn();
        $return->vendor_id       = $request->input('vendor_id') ?? NULL;
        $return->title           = $request->input('title');
        $return->return_number   = get_business_option('purchase_return_number');
        $return->return_date     = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $return->sub_total       = $summary['subTotal'];
        $return->grand_total     = $summary['grandTotal'];
        $return->currency        = $request['currency'];
        $return->converted_total = $request->input('converted_total');
        $return->exchange_rate   = $request->input('exchange_rate');
        $return->paid            = 0;
        $return->discount        = $summary['discountAmount'];
        $return->discount_type   = $request->input('discount_type');
        $return->discount_value  = $request->input('discount_value');
        $return->note            = $request->input('note');
        $return->footer          = $request->input('footer');
        $return->short_code      = rand(100000, 9999999) . uniqid();
        $return->save();

        $currentTime = Carbon::now();

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
                    $account_obj->dr_cr   = 'cr';
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

        for ($i = 0; $i < count($request->product_id); $i++) {
            $returnItem = $return->items()->save(new PurchaseReturnItem([
                'purchase_return_id'   => $return->id,
                'product_id'        => $request->product_id[$i],
                'product_name'      => $request->product_name[$i],
                'description'       => $request->description[$i],
                'quantity'          => $request->quantity[$i],
                'unit_cost'         => $request->unit_cost[$i],
                'sub_total'         => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->description = $returnItem->product_name . ' Purchase Returned #' . $returnItem->quantity;
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 'p return';
                $transaction->save();
            }

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $returnItem->taxes()->save(new PurchaseReturnItemTax([
                        'purchase_return_id' => $return->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($returnItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $return->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Purchase Return Tax') . ' #' . $return->return_number;
                    $transaction->ref_id      = $return->id;
                    $transaction->ref_type    = 'p return tax';
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $returnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'purchase_return_number')->increment('value');

        DB::commit();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate = $return->exchange_rate;
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->ref_id      = $return->id;
        $transaction->ref_type    = 'p return';
        $transaction->description = 'Purchase Return #' . $return->return_number;
        $transaction->save();

        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $return->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Purchase Return Discount') . ' #' . $return->return_number;
            $transaction->ref_id      = $return->id;
            $transaction->ref_type    = 'p return';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Created: ' . $return->return_number;
        $audit->save();

        return redirect()->route('purchase_returns.show', $return->id)->with('success', _lang('Saved Successfully'));
    }

    private function calculateTotal(Request $request)
    {
        $subTotal       = 0;
        $taxAmount      = 0;
        $discountAmount = 0;
        $grandTotal     = 0;

        for ($i = 0; $i < count($request->product_id); $i++) {
            //Calculate Sub Total
            $line_qnt       = $request->quantity[$i];
            $line_unit_cost = $request->unit_cost[$i];
            $line_total     = ($line_qnt * $line_unit_cost);

            //Show Sub Total
            $subTotal = ($subTotal + $line_total);

            //Calculate Taxes
            if (isset($request->taxes[$request->product_id[$i]])) {
                for ($j = 0; $j < count($request->taxes[$request->product_id[$i]]); $j++) {
                    $taxId       = $request->taxes[$request->product_id[$i]][$j];
                    $tax         = Tax::find($taxId);
                    $product_tax = ($line_total / 100) * $tax->rate;
                    $taxAmount += $product_tax;
                }
            }

            //Calculate Discount
            if ($request->discount_type == '0') {
                $discountAmount = ($subTotal / 100) * $request->discount_value;
            } else if ($request->discount_type == '1') {
                $discountAmount = $request->discount_value;
            }
        }

        //Calculate Grand Total
        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

        return array(
            'subTotal'                 => $subTotal,
            'discountAmount'         => $discountAmount,
            'grandTotal'             => $grandTotal,
        );
    }

    public function edit(Request $request, $id)
    {
        $purchase_return = PurchaseReturn::with('items')
            ->where('id', $id)
            ->where('status', '!=', 2)
            ->first();

        if ($purchase_return == null) {
            return back()->with('error', _lang('This purchase return is already paid'));
        }

        // Get required data for the edit form
        $vendors = Vendor::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $accounts = Account::all();

        $taxIds = $purchase_return->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        return Inertia::render('Backend/User/PurchaseReturn/Edit', [
            'purchase_return' => $purchase_return,
            'vendors' => $vendors,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'taxIds' => $taxIds
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id'    => 'required',
            'title'          => 'required',
            'return_date'   => 'required|date',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('purchase_returns.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $return                  = PurchaseReturn::find($id);
        $return->vendor_id       = $request->input('vendor_id') ?? NULL;
        $return->title           = $request->input('title');
        $return->return_date     = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $return->sub_total       = $summary['subTotal'];
        $return->grand_total     = $summary['grandTotal'];
        $return->currency        = $request['currency'];
        $return->converted_total = $request->input('converted_total');
        $return->exchange_rate   = $request->input('exchange_rate');
        $return->paid            = 0;
        $return->discount        = $summary['discountAmount'];
        $return->discount_type   = $request->input('discount_type');
        $return->discount_value  = $request->input('discount_value');
        $return->note            = $request->input('note');
        $return->footer          = $request->input('footer');
        $return->save();

        $currentTime = Carbon::now();

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
                    $account_obj->dr_cr   = 'cr';
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

        //Update return item
        foreach ($return->items as $return_item) {
            $product = $return_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $return_item->quantity;
                $product->save();
            }
            $return_item->delete();

            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')
                ->where('account_id', get_account('Inventory')->id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }
        }

        for ($i = 0; $i < count($request->product_id); $i++) {
            $returnItem = $return->items()->save(new PurchaseReturnItem([
                'purchase_return_id'   => $return->id,
                'product_id'        => $request->product_id[$i],
                'product_name'      => $request->product_name[$i],
                'description'       => $request->description[$i],
                'quantity'          => $request->quantity[$i],
                'unit_cost'         => $request->unit_cost[$i],
                'sub_total'         => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity);
                $transaction->description = $returnItem->product_name . ' Purchase Returned #' . $returnItem->quantity;
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $returnItem->quantity));
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 'p return';
                $transaction->save();
            }

            if (isset($request->taxes)) {
                $returnItem->taxes()->delete();
                $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return tax')
                    ->get();

                foreach ($transaction as $t) {
                    $t->delete();
                }

                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $returnItem->taxes()->save(new PurchaseReturnItemTax([
                        'purchase_return_id' => $return->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($returnItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $return->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($returnItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Purchase Return Tax') . ' #' . $return->return_number;
                    $transaction->ref_id      = $return->id;
                    $transaction->ref_type    = 'p return tax';
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $returnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        DB::commit();

        $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')
            ->where('account_id', get_account('Accounts Payable')->id)
            ->first();
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->save();

        if ($request->input('discount_value') == 0) {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')
                ->where('account_id', get_account('Purchase Discount Allowed')->id)
                ->first();
            if ($transaction != null) {
                $transaction->delete();
            }
        }

        if ($request->input('discount_value') > 0) {
            $transaction = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')
                ->where('account_id', get_account('Purchase Discount Allowed')->id)
                ->first();
            if ($transaction == null) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $return->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Purchase Return Discount') . ' #' . $return->return_number;
                $transaction->ref_id      = $return->id;
                $transaction->ref_type    = 'p return';
                $transaction->save();
            } else {
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Updated: ' . $return->return_number;
        $audit->save();

        if ($return->id > 0) {
            return redirect()->route('purchase_returns.show', $return->id)->with('success', _lang('Updated  Successfully'));
        } else {
            return back()->with('error', _lang('Something going wrong, Please try again'));
        }
    }

    public function destroy($id)
    {
        $return = PurchaseReturn::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Deleted: ' . $return->return_number;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        // increase stock
        foreach ($return->items as $item) {
            $product = Product::find($item->product_id);
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $item->quantity;
                $product->save();
            }
        }

        // delete p refund transactions
        $refund_transactions = Transaction::where('ref_id', $return->id)
            ->where(function ($query) {
                $query->where('ref_type', 'p return')
                    ->orWhere('ref_type', 'p return tax');
            })
            ->get();
        foreach ($refund_transactions as $transaction) {
            $transaction->delete();
        }

        $return->delete();
        return redirect()->route('purchase_returns.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $return = PurchaseReturn::find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Purchase Return Deleted: ' . $return->return_number;
            $audit->save();

            // delete transactions
            $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 'p return')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            // increase stock
            foreach ($return->items as $item) {
                $product = Product::find($item->product_id);
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock + $item->quantity;
                    $product->save();
                }
            }

            // delete p refund transactions
            $refund_transactions = Transaction::where('ref_id', $return->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'p return')
                        ->orWhere('ref_type', 'p return tax');
                })
                ->get();
            foreach ($refund_transactions as $transaction) {
                $transaction->delete();
            }

            $return->delete();
        }
        return redirect()->route('purchase_returns.index')->with('success', _lang('Deleted Successfully'));
    }

    public function refund_store(Request $request, $id)
    {
        $request->validate(
            [
                'refund_date' => 'required',
                'amount'      => 'required|numeric',
                'account_id'  => 'required',
            ],
            [
                'account_id.required' => _lang('Please select an account'),
                'amount.required'      => _lang('Please enter refund amount'),
                'amount.numeric'       => _lang('Refund amount must be a number'),
                'refund_date.required' => _lang('Please select refund date'),
            ]
        );

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
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
                    $account_obj->dr_cr   = 'cr';
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

        $purchaseReturn = PurchaseReturn::with('vendor')->where('id', $id)->first();

        if ($request->amount > $purchaseReturn->grand_total - $purchaseReturn->paid) {
            return back()->with('error', _lang('Refund amount can not be greater than due amount'));
        }

        $currentTime = Carbon::now();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = $request->account_id;
        $transaction->transaction_method      = $request->method;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchaseReturn->currency, $request->amount);
        $transaction->transaction_currency    = $purchaseReturn->currency;
        $transaction->currency_rate = $purchaseReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($purchaseReturn->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchaseReturn->currency, $request->amount));
        $transaction->reference   = $request->reference;
        $transaction->description = _lang('Purchase Return Refund') . ' #' . $purchaseReturn->return_number;
        $transaction->attachment  = $attachment;
        $transaction->ref_id      = $id;
        $transaction->ref_type    = 'p refund';
        $transaction->customer_id = $request->customer_id ?? NULL;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('trans_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'cr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchaseReturn->currency, $request->amount);
        $transaction->transaction_currency    = $purchaseReturn->currency;
        $transaction->currency_rate = $purchaseReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($purchaseReturn->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchaseReturn->currency, $request->amount));
        $transaction->ref_id      = $id;
        $transaction->ref_type    = 'p refund';
        $transaction->description = 'Purchase Return Refund #' . $purchaseReturn->return_number;
        $transaction->save();

        $purchaseReturn->paid   = $purchaseReturn->paid + $request->amount;
        $purchaseReturn->status = 2; //Partially refund
        if ($purchaseReturn->paid >= $purchaseReturn->grand_total) {
            $purchaseReturn->status = 1; //Paid
        }
        $purchaseReturn->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Refund: ' . $purchaseReturn->return_number;
        $audit->save();

        return redirect()->route('purchase_returns.index')->with('success', _lang('Saved Successfully'));
    }
}
