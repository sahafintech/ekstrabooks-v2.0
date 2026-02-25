<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\PendingTransaction;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use function Spatie\LaravelPdf\Support\pdf;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('purchase_returns.view');

        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 50);
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $vendorId = $request->get('vendor_id', '');
        $dateRange = $request->get('date_range', '');
        $status = $request->get('status', '');
        $approvalStatus = $request->get('approval_status', '');

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

        if ($approvalStatus) {
            $query->where('approval_status', $approvalStatus);
        }

        $returns = $query->paginate($perPage);
        $accounts = Account::all();
        $vendors = Vendor::all();

        // Get summary statistics for all purchase returns
        $allReturns = PurchaseReturn::query();
        if ($search) {
            $allReturns->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%$search%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($vendorId) {
            $allReturns->where('vendor_id', $vendorId);
        }

        if ($dateRange) {
            $allReturns->whereDate('return_date', '>=', Carbon::parse($dateRange[0])->format('Y-m-d'))
                ->whereDate('return_date', '<=', Carbon::parse($dateRange[1])->format('Y-m-d'));
        }

        if ($status) {
            $allReturns->where('status', $status);
        }

        if ($approvalStatus) {
            $allReturns->where('approval_status', $approvalStatus);
        }

        $allReturns = $allReturns->get();

        $summary = [
            'total_returns' => $allReturns->count(),
            'total_refunded' => $allReturns->where('status', 1)->count(),
            'grand_total' => $allReturns->sum('grand_total'),
            'total_due' => $allReturns->sum(function ($return) {
                return $return->grand_total - $return->paid;
            }),
        ];

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
                'approval_status' => $approvalStatus,
                'sorting' => $sorting,
            ],
            'summary' => $summary,
            'trashed_purchase_returns' => PurchaseReturn::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('purchase_returns.view');

        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 50);
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $vendorId = $request->get('vendor_id', '');
        $dateRange = $request->get('date_range', '');
        $status = $request->get('status', '');
        $approvalStatus = $request->get('approval_status', '');

        $query = PurchaseReturn::onlyTrashed()->with('vendor', 'business');

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

        if ($approvalStatus) {
            $query->where('approval_status', $approvalStatus);
        }

        $returns = $query->paginate($perPage);
        $accounts = Account::all();
        $vendors = Vendor::all();

        return Inertia::render('Backend/User/PurchaseReturn/Trash', [
            'purchaseReturns' => $returns->items(),
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
                'approval_status' => $approvalStatus,
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
        Gate::authorize('purchase_returns.create');

        $purchase_return_title = get_business_option('purchase_return_title', 'Return');
        $vendors = Vendor::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $accounts = Account::all();
        $inventory = get_account('Inventory');

        return Inertia::render('Backend/User/PurchaseReturn/Create', [
            'purchase_return_title' => $purchase_return_title,
            'vendors' => $vendors,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'base_currency' => get_business_option('currency'),
            'inventory' => $inventory,
        ]);
    }

    public function show($id)
    {
        Gate::authorize('purchase_returns.view');

        $purchase_return  = PurchaseReturn::with(['business', 'items', 'taxes', 'vendor'])->find($id);

        return Inertia::render('Backend/User/PurchaseReturn/View', [
            'purchase_return' => $purchase_return,
        ]);
    }

    public function pdf($id)
    {
        Gate::authorize('purchase_returns.pdf');

        $purchase_return = PurchaseReturn::with(['business', 'items', 'taxes', 'vendor'])->find($id);
        return pdf()
        ->view('backend.user.pdf.purchase-return', compact('purchase_return'))
        ->name('purchase-return-' . $purchase_return->return_number . '.pdf')
        ->download();
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
        Gate::authorize('purchase_returns.create');

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'nullable',
            'title' => 'required',
            'return_date' => 'required|date',
            'product_name' => 'required',
            'currency'  =>  'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('purchase_returns.create')
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

        $month = Carbon::parse($request->return_date)->format('F');
        $year = Carbon::parse($request->return_date)->format('Y');
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

        $purchaseReturn = new PurchaseReturn();
        $purchaseReturn->vendor_id = $request->input('vendor_id');
        $purchaseReturn->title = $request->input('title');
        $purchaseReturn->return_number = get_business_option('purchase_return_number');
        $purchaseReturn->return_date = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $purchaseReturn->sub_total = $summary['subTotal'];
        $purchaseReturn->grand_total = $summary['grandTotal'];
        $purchaseReturn->converted_total = $request->input('converted_total');
        $purchaseReturn->exchange_rate   = $request->input('exchange_rate');
        $purchaseReturn->currency   = $request->input('currency');
        $purchaseReturn->paid = 0;
        $purchaseReturn->discount = $summary['discountAmount'];
        $purchaseReturn->discount_type = $request->input('discount_type');
        $purchaseReturn->discount_value = $request->input('discount_value') ?? 0;
        $purchaseReturn->note = $request->input('note');
        $purchaseReturn->footer = $request->input('footer');
        if (has_permission('purchase_returns.bulk_approve') || request()->isOwner) {
            $purchaseReturn->approval_status = 1;
        } else {
            $purchaseReturn->approval_status = 0;
        }
        if (has_permission('purchase_returns.bulk_approve') || request()->isOwner) {
            $purchaseReturn->approved_by = Auth::id();
        } else {
            $purchaseReturn->approved_by = null;
        }
        $purchaseReturn->short_code = rand(100000, 9999999) . uniqid();

        $purchaseReturn->save();


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
                    $attachment->ref_type = 'purchase return';
                    $attachment->ref_id = $purchaseReturn->id;
                    $attachment->save();
                }
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_name); $i++) {
            $purchaseItem = $purchaseReturn->items()->save(new PurchaseReturnItem([
                'purchase_return_id' => $purchaseReturn->id,
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

                    $purchaseItem->taxes()->save(new PurchaseReturnItemTax([
                        'purchase_return_id' => $purchaseReturn->id,
                        'tax_id' => $taxId,
                        'name' => $tax->name . ' ' . $tax->rate . ' %',
                        'amount' => ($purchaseItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchaseReturn->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchaseReturn->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseItem->sub_total / $purchaseReturn->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Purchase Return Tax') . ' #' . $purchaseReturn->return_number;
                    $transaction->ref_id      = $purchaseReturn->id;
                    $transaction->ref_type    = 'purchase return tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            // Per-item account transaction (credits the item's account, e.g. Inventory)
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = $request->input('account_id')[$i];
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchaseReturn->exchange_rate);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $purchaseReturn->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseItem->sub_total / $purchaseReturn->exchange_rate));
            $transaction->ref_type    = 'purchase return';
            $transaction->vendor_id   = $purchaseReturn->vendor_id;
            $transaction->ref_id      = $purchaseReturn->id;
            $transaction->description = 'Purchase Return #' . $purchaseReturn->return_number;
            $transaction->save();

            // update stock
            if ($purchaseItem->product->type == 'product' && $purchaseItem->product->stock_management == 1) {
                $purchaseItem->product->stock = $purchaseItem->product->stock - $request->quantity[$i];
                $purchaseItem->product->save();
            }
        }

        //Increment Purchase Return Number
        BusinessSetting::where('name', 'purchase_return_number')->increment('value');

        DB::commit();

        // Accounts Payable transaction (debit - supplier owes us less)
        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate = $purchaseReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->ref_type    = 'purchase return';
        $transaction->vendor_id   = $purchaseReturn->vendor_id;
        $transaction->ref_id      = $purchaseReturn->id;
        $transaction->description = 'Purchase Return #' . $purchaseReturn->return_number;
        $transaction->save();

        // Discount transaction
        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $purchaseReturn->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Purchase Return Discount') . ' #' . $purchaseReturn->return_number;
            $transaction->ref_id      = $purchaseReturn->id;
            $transaction->ref_type    = 'purchase return';
            $transaction->vendor_id   = $purchaseReturn->vendor_id;
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Created' . ' ' . $purchaseReturn->return_number;
        $audit->save();

        return redirect()->route('purchase_returns.show', $purchaseReturn->id)->with('success', _lang('Saved Successfully'));
    }

    public function edit($id)
    {
        Gate::authorize('purchase_returns.update');

        $purchase_return = PurchaseReturn::with(['business', 'items', 'taxes', 'vendor'])
            ->where('id', $id)
            ->where('status', '!=', 1)
            ->first();

        if ($purchase_return == null) {
            return back()->with('error', _lang('This purchase return is already refunded'));
        }

        if (!has_permission('purchase_returns.bulk_approve') && !request()->isOwner && $purchase_return->approval_status == 1) {
            return back()->with('error', _lang('Permission denied'));
        }

        $theAttachments = Attachment::where('ref_id', $id)->where('ref_type', 'purchase return')->get();
        $accounts = Account::all();
        $currencies = Currency::all();
        $vendors = Vendor::all();
        $products = Product::all();
        $taxes = Tax::all();
        $inventory = Account::where('account_name', 'Inventory')->first();
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
            'taxIds' => $taxIds,
            'theAttachments' => $theAttachments,
            'inventory' => $inventory,
        ]);
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('purchase_returns.update');

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'nullable',
            'title' => 'required',
            'return_date' => 'required|date',
            'product_name' => 'required',
            'currency'  =>  'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('purchase_returns.edit', $id)
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

        $month = Carbon::parse($request->return_date)->format('F');
        $year = Carbon::parse($request->return_date)->format('Y');
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

        $purchaseReturn = PurchaseReturn::where('id', $id)
            ->first();
        $purchaseReturn->vendor_id = $request->input('vendor_id') ?? null;
        $purchaseReturn->title = $request->input('title');
        if ($purchaseReturn->return_number == null) {
            $purchaseReturn->return_number = get_business_option('purchase_return_number');
        }
        $purchaseReturn->return_date = Carbon::parse($request->input('return_date'))->format('Y-m-d');
        $purchaseReturn->sub_total = $summary['subTotal'];
        $purchaseReturn->grand_total = $summary['grandTotal'];
        $purchaseReturn->converted_total = $request->input('converted_total');
        $purchaseReturn->exchange_rate   = $request->input('exchange_rate');
        $purchaseReturn->currency   = $request->input('currency');
        $purchaseReturn->discount = $summary['discountAmount'];
        $purchaseReturn->discount_type = $request->input('discount_type');
        $purchaseReturn->discount_value = $request->input('discount_value') ?? 0;
        $purchaseReturn->note = $request->input('note');
        $purchaseReturn->footer = $request->input('footer');
        $purchaseReturn->save();

        // delete old attachments
        $attachments = Attachment::where('ref_id', $purchaseReturn->id)->where('ref_type', 'purchase return')->get(); // Get attachments from the database

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
                    $attachment->ref_type = 'purchase return';
                    $attachment->ref_id = $purchaseReturn->id;
                    $attachment->save();
                }
            }
        }

        //Update Purchase Return item - revert stock and delete old items
        foreach ($purchaseReturn->items as $purchaseReturnItem) {
            $product = $purchaseReturnItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $purchaseReturnItem->quantity;
                $product->save();
            }

            // delete item taxes (forceDelete because table lacks deleted_at column)
            $purchaseReturnItem->taxes()->forceDelete();
            $purchaseReturnItem->forceDelete();
        }

        // delete all transactions for this purchase return (done once, outside the loop)
        Transaction::where('ref_id', $purchaseReturn->id)->whereIn('ref_type', ['purchase return', 'purchase return tax', 'purchase return payment', 'purchase return tax payment'])->forceDelete();

        // delete pending transactions
        PendingTransaction::where('ref_id', $purchaseReturn->id)->whereIn('ref_type', ['purchase return', 'purchase return tax', 'purchase return payment', 'purchase return tax payment'])->forceDelete();

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->product_name); $i++) {
            $purchaseReturnItem = $purchaseReturn->items()->save(new PurchaseReturnItem([
                'purchase_return_id' => $purchaseReturn->id,
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

                    $purchaseReturnItem->taxes()->save(new PurchaseReturnItemTax([
                        'purchase_return_id' => $purchaseReturn->id,
                        'tax_id' => $taxId,
                        'name' => $tax->name . ' ' . $tax->rate . ' %',
                        'amount' => ($purchaseReturnItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $purchaseReturn->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseReturnItem->sub_total / $purchaseReturn->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($purchaseReturnItem->sub_total / $purchaseReturn->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Purchase Return Tax') . ' #' . $purchaseReturn->return_number;
                    $transaction->ref_id      = $purchaseReturn->id;
                    $transaction->ref_type    = 'purchase return tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            // Per-item account transaction (credits the item's account, e.g. Inventory)
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = $request->input('account_id')[$i];
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $purchaseReturnItem->sub_total / $purchaseReturn->exchange_rate);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate = $purchaseReturn->exchange_rate;
            $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $purchaseReturnItem->sub_total / $purchaseReturn->exchange_rate));
            $transaction->ref_type    = 'purchase return';
            $transaction->vendor_id   = $purchaseReturn->vendor_id;
            $transaction->ref_id      = $purchaseReturn->id;
            $transaction->description = 'Purchase Return #' . $purchaseReturn->return_number;
            $transaction->save();

            // update stock (purchase return decreases stock - returning to supplier)
            if ($purchaseReturnItem->product->type == 'product' && $purchaseReturnItem->product->stock_management == 1) {
                $purchaseReturnItem->product->stock = $purchaseReturnItem->product->stock - $request->quantity[$i];
                $purchaseReturnItem->product->save();
            }
        }

        DB::commit();

        // Accounts Payable transaction (debit - supplier owes us less)
        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
        $transaction->account_id  = get_account('Accounts Payable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate = $purchaseReturn->exchange_rate;
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->ref_type    = 'purchase return';
        $transaction->vendor_id   = $purchaseReturn->vendor_id;
        $transaction->ref_id      = $purchaseReturn->id;
        $transaction->description = 'Purchase Return #' . $purchaseReturn->return_number;
        $transaction->save();

        // Discount transaction
        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->input('return_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $purchaseReturn->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Purchase Return Discount') . ' #' . $purchaseReturn->return_number;
            $transaction->ref_id      = $purchaseReturn->id;
            $transaction->ref_type    = 'purchase return';
            $transaction->vendor_id   = $purchaseReturn->vendor_id;
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Purchase Return ' . $purchaseReturn->return_number;
        $audit->save();

        return redirect()->route('purchase_returns.show', $purchaseReturn->id)->with('success', _lang('Updated Successfully'));
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

    public function destroy($id)
    {
        Gate::authorize('purchase_returns.delete');

        $return = PurchaseReturn::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Deleted: ' . $return->return_number;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 'purchase return')->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        // restore stock (undo the purchase return's stock decrease)
        foreach ($return->items as $item) {
            if ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock + $item->quantity;
                    $product->save();
                }
            }
        }

        // delete p refund transactions
        $refund_transactions = Transaction::where('ref_id', $return->id)
            ->where(function ($query) {
                $query->where('ref_type', 'purchase return')
                    ->orWhere('ref_type', 'purchase return tax');
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
        Gate::authorize('purchase_returns.delete');

        foreach ($request->ids as $id) {
            $return = PurchaseReturn::find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Purchase Return Deleted: ' . $return->return_number;
            $audit->save();

            // delete transactions
            $transactions = Transaction::where('ref_id', $return->id)->where('ref_type', 'purchase return')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            // restore stock (undo the purchase return's stock decrease)
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product->type == 'product' && $product->stock_management == 1) {
                        $product->stock = $product->stock + $item->quantity;
                        $product->save();
                    }
                }
            }

            // delete p refund transactions
            $refund_transactions = Transaction::where('ref_id', $return->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'purchase return')
                        ->orWhere('ref_type', 'purchase return tax');
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
        Gate::authorize('purchase_returns.refund');

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

    public function unrefund($id)
    {
        Gate::authorize('purchase_returns.refund');

        $purchaseReturn = PurchaseReturn::findOrFail($id);

        DB::beginTransaction();

        try {
            $transactions = Transaction::where('ref_id', $purchaseReturn->id)
                ->where('ref_type', 'p refund')
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $purchaseReturn->paid = 0;
            $purchaseReturn->status = 0; // Active
            $purchaseReturn->save();

            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Purchase Return Unrefund' . ' ' . $purchaseReturn->return_number;
            $audit->save();

            DB::commit();

            return redirect()->route('purchase_returns.index')->with('success', _lang('Unrefund completed successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', _lang('Something going wrong, Please try again'));
        }
    }

    public function restore($id)
    {
        Gate::authorize('purchase_returns.restore');

        $purchaseReturn = PurchaseReturn::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Restored: ' . $purchaseReturn->return_number;
        $audit->save();

        // restore transactions
        $transactions = Transaction::onlyTrashed()->where('ref_id', $purchaseReturn->id)
            ->where(function ($query) {
                $query->where('ref_type', 'purchase return')
                    ->orWhere('ref_type', 'purchase return tax');
            })
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->restore();
        }

        // re-apply stock decrease (restoring the purchase return's effect)
        foreach ($purchaseReturn->items as $item) {
            if ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product->type == 'product' && $product->stock_management == 1) {
                    $product->stock = $product->stock - $item->quantity;
                    $product->save();
                }
            }
        }

        $purchaseReturn->restore();
        return redirect()->route('purchase_returns.trash')->with('success', _lang('Restored Successfully'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('purchase_returns.restore');

        foreach ($request->ids as $id) {
            $purchaseReturn = PurchaseReturn::onlyTrashed()->find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Purchase Return Restored: ' . $purchaseReturn->return_number;
            $audit->save();

            // restore transactions
            $transactions = Transaction::onlyTrashed()->where('ref_id', $purchaseReturn->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'purchase return')
                        ->orWhere('ref_type', 'purchase return tax');
                })
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->restore();
            }

            // re-apply stock decrease (restoring the purchase return's effect)
            foreach ($purchaseReturn->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product->type == 'product' && $product->stock_management == 1) {
                        $product->stock = $product->stock - $item->quantity;
                        $product->save();
                    }
                }
            }

            $purchaseReturn->restore();
        }

        return redirect()->route('purchase_returns.trash')->with('success', _lang('Restored Successfully'));
    }

    public function permanent_destroy($id)
    {
        Gate::authorize('purchase_returns.delete');

        $purchaseReturn = PurchaseReturn::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Purchase Return Permanently Deleted: ' . $purchaseReturn->return_number;
        $audit->save();

        // delete transactions
        $transactions = Transaction::onlyTrashed()->where('ref_id', $purchaseReturn->id)
            ->where(function ($query) {
                $query->where('ref_type', 'purchase return')
                    ->orWhere('ref_type', 'purchase return tax');
            })
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->forceDelete();
        }

        // delete attachments
        $attachments = Attachment::where('ref_id', $purchaseReturn->id)->where('ref_type', 'purchase return')->get();
        foreach ($attachments as $attachment) {
            $filePath = public_path($attachment->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $attachment->delete();
        }

        $purchaseReturn->forceDelete();
        return redirect()->route('purchase_returns.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('purchase_returns.delete');

        foreach ($request->ids as $id) {
            $purchaseReturn = PurchaseReturn::onlyTrashed()->find($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Purchase Return Permanently Deleted: ' . $purchaseReturn->return_number;
            $audit->save();

            // delete transactions
            $transactions = Transaction::onlyTrashed()->where('ref_id', $purchaseReturn->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'purchase return')
                        ->orWhere('ref_type', 'purchase return tax');
                })
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->forceDelete();
            }

            // delete attachments
            $attachments = Attachment::where('ref_id', $purchaseReturn->id)->where('ref_type', 'purchase return')->get();
            foreach ($attachments as $attachment) {
                $filePath = public_path($attachment->path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $attachment->delete();
            }

            $purchaseReturn->forceDelete();
        }

        return redirect()->route('purchase_returns.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_approve(Request $request)
    {
        Gate::authorize('purchase_returns.bulk_approve');

        foreach ($request->ids as $id) {
            $purchaseReturn = PurchaseReturn::find($id);
            $purchaseReturn->approval_status = 1;
            $purchaseReturn->approved_by = Auth::id();
            $purchaseReturn->save();

            // select from pending transactions and insert into transactions
            $transactions = PendingTransaction::where('ref_id', $purchaseReturn->id)->get();

            foreach ($transactions as $transaction) {
                // Create a new Transaction instance and replicate data from pending
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('transactions'); // Change the table to 'transactions'
                $new_transaction->save();

                // Delete the pending transaction
                $transaction->forceDelete();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Approved Purchase Return ' . $purchaseReturn->return_number;
            $audit->save();
        }

        return redirect()->route('purchase_returns.index')->with('success', _lang('Approved Successfully'));
    }

    public function bulk_reject(Request $request)
    {
        Gate::authorize('purchase_returns.bulk_approve');

        foreach ($request->ids as $id) {
            $purchaseReturn = PurchaseReturn::find($id);
            if ($purchaseReturn->approval_status == 0) {
                continue;
            }
            $purchaseReturn->approval_status = 0;
            $purchaseReturn->approved_by = null;
            $purchaseReturn->save();

            // delete all transactions
            $transactions = Transaction::where('ref_id', $purchaseReturn->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'purchase return')
                        ->orWhere('ref_type', 'purchase return tax');
                })
                ->get();

            foreach ($transactions as $transaction) {
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('pending_transactions');
                $new_transaction->save();

                $transaction->forceDelete();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Rejected Purchase Return ' . $purchaseReturn->return_number;
            $audit->save();
        }

        return redirect()->route('purchase_returns.index')->with('success', _lang('Rejected Successfully'));
    }
}
