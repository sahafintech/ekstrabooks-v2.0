<?php

namespace App\Http\Controllers\User;

use App\Exports\ProductExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Brands;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SubCategory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Product::orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('selling_price', 'like', "%{$search}%")
                    ->orWhere('purchase_cost', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $products = $query->paginate($per_page)->withQueryString();

        // Get summary statistics for all products
        $allProducts = Product::query();
        if (!empty($search)) {
            $allProducts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('selling_price', 'like', "%{$search}%")
                    ->orWhere('purchase_cost', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        $allProducts = $allProducts->get();

        $summary = [
            'total_products' => $allProducts->count(),
            'active_products' => $allProducts->where('status', 1)->count(),
            'total_stock' => $allProducts->sum('stock'),
            'total_value' => $allProducts->sum(function ($product) {
                return $product->stock * $product->purchase_cost;
            }),
        ];

        // Return Inertia view
        return Inertia::render('Backend/User/Product/List', [
            'products' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'links' => $products->linkCollection(),
                'path' => $products->path(),
                'per_page' => $products->perPage(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
            'summary' => $summary,
            'trashed_products' => Product::onlyTrashed()->count(),
            ]);
    }

    public function trash(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Product::orderBy($sortColumn, $sortDirection)->onlyTrashed();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('selling_price', 'like', "%{$search}%")
                    ->orWhere('purchase_cost', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $products = $query->paginate($per_page)->withQueryString();

        // Get summary statistics for all products
        $allProducts = Product::query()->onlyTrashed();
        if (!empty($search)) {
            $allProducts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('selling_price', 'like', "%{$search}%")
                    ->orWhere('purchase_cost', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        $allProducts = $allProducts->get();

        $summary = [
            'total_products' => $allProducts->count(),
            'active_products' => $allProducts->where('status', 1)->count(),
            'total_stock' => $allProducts->sum('stock'),
            'total_value' => $allProducts->sum(function ($product) {
                return $product->stock * $product->purchase_cost;
            }),
        ];

        // Return Inertia view
        return Inertia::render('Backend/User/Product/Trash', [
            'products' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'links' => $products->linkCollection(),
                'path' => $products->path(),
                'per_page' => $products->perPage(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
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
        $productUnits = ProductUnit::select('id', 'unit')->get();
        $categories = SubCategory::select('id', 'name')->get();
        $brands = Brands::select('id', 'name')->get();
        $accounts = Account::select('id', 'account_name')->get();

        return Inertia::render('Backend/User/Product/Create', compact('productUnits', 'categories', 'brands', 'accounts'));
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
            'name'                 => 'required',
            'type'                 => 'required',
            'image'                => 'nullable|image|max:2048',
            'allow_for_selling'    => 'required_without_all:allow_for_purchasing',
            'allow_for_purchasing' => 'nullable',
            'purchase_cost'        => 'nullable|required_if:allow_for_purchasing,1|numeric',
            'selling_price'        => 'nullable|required_if:allow_for_selling,1|numeric',
            'income_account_id'    => 'nullable|required_if:allow_for_selling,1|numeric',
            'expense_account_id'   => 'nullable|required_if:allow_for_purchasing,1|numeric',
            'status'               => 'required',
            'stock_management'     => 'required',
        ], [
            'allow_for_selling.required_without_all' => 'You need to choose at least for selling or purchasing',
            'purchase_cost.required_if'              => 'Purchase Cost is required',
            'selling_price.required_if'              => 'Selling Cost is required',
            'income_account_id.required_if'              => 'Income Account is required',
            'expense_account_id.required_if'              => 'Expense Account is required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('products.create')
                ->withErrors($validator)
                ->withInput();
        }

        $image = 'default.png';
        if ($request->hasfile('image')) {
            $file  = $request->file('image');
            $image = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $image);
        }

        if (!Account::where('account_name', 'Common Shares')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '3000';
            $account->account_name    = 'Common Shares';
            $account->account_type    = 'Equity';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'cr';
            $account->save();
        }

        if (!Account::where('account_name', 'Inventory')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '1000';
            $account->account_name    = 'Inventory';
            $account->account_type    = 'Other Current Asset';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'dr';
            $account->save();
        }

        $product                       = new Product();
        $product->name                 = $request->input('name');
        $product->type                 = $request->input('type');
        $product->product_unit_id      = $request->input('product_unit_id');
        $product->purchase_cost        = $request->allow_for_purchasing == 1 ? $request->input('purchase_cost') : 0;
        $product->selling_price        = $request->allow_for_selling == 1 ? $request->input('selling_price') : 0;
        $product->image                = $image;
        $product->descriptions         = $request->input('descriptions');
        $product->expiry_date          = $request->input('expiry_date') ? Carbon::parse($request->input('expiry_date'))->format('Y-m-d') : null;
        $product->code                 = $request->input('code');
        $product->reorder_point        = $request->input('reorder_point');
        $product->stock                = $request->input('initial_stock') ?? 0;
        $product->initial_stock        = $request->input('initial_stock') ?? 0;
        $product->allow_for_selling    = $request->input('allow_for_selling');
        $product->allow_for_purchasing = $request->input('allow_for_purchasing');
        $product->income_account_id    = $request->input('income_account_id');
        $product->expense_account_id   = $request->input('expense_account_id');
        $product->status               = $request->input('status');
        $product->stock_management     = $request->stock_management;
        $product->sub_category_id      = $request->input('sub_category_id');
        $product->brand_id             = $request->input('brand_id');
        $product->save();

        if ($request->input('initial_stock') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = now()->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Inventory')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = $product->purchase_cost * $request->input('initial_stock');
            $transaction->transaction_amount      = $product->purchase_cost * $request->input('initial_stock');
            $transaction->description = $product->name . ' Opening Stock #' . $request->input('initial_stock');
            $transaction->ref_id      = $product->id;
            $transaction->ref_type    = 'product';
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = now()->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Common Shares')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_currency    = $request->activeBusiness->currency;
            $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = $product->purchase_cost * $request->input('initial_stock');
            $transaction->transaction_amount      = $product->purchase_cost * $request->input('initial_stock');
            $transaction->description = $product->name . ' Opening Stock #' . $request->input('initial_stock');
            $transaction->ref_id      = $product->id;
            $transaction->ref_type    = 'product';
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'New Product Created - ' . $product->name;
        $audit->save();

        return redirect()->route('products.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $activeTab = request()->get('tab', 'details');
        $product = Product::withTrashed()->with(['income_account', 'expense_account', 'product_unit', 'brand', 'category'])
            ->withSum('invoice_items', 'quantity')
            ->withSum('purchase_items', 'quantity')
            ->withSum('sales_return_items', 'quantity')
            ->withSum('purchase_return_items', 'quantity')
            ->withSum('receipt_items', 'quantity')
            ->find($id);

        // Calculate currency-converted totals manually
        $invoiceItemsTotal = $product->invoice_items()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->selectRaw('SUM(invoice_items.sub_total / invoices.exchange_rate) as total')
            ->value('total') ?? 0;

        $purchaseItemsTotal = $product->purchase_items()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->selectRaw('SUM(purchase_items.sub_total / purchases.exchange_rate) as total')
            ->value('total') ?? 0;

        $receiptItemsTotal = $product->receipt_items()
            ->join('receipts', 'receipts.id', '=', 'receipt_items.receipt_id')
            ->selectRaw('SUM(receipt_items.sub_total / receipts.exchange_rate) as total')
            ->value('total') ?? 0;

        // Add the converted totals to the product object
        $product->invoice_items_sum_sub_total = $invoiceItemsTotal;
        $product->purchase_items_sum_sub_total = $purchaseItemsTotal;
        $product->receipt_items_sum_sub_total = $receiptItemsTotal;

        $transactions = $product->getAllTransactions();
        $suppliers = $product->getSuppliers();

        return inertia('Backend/User/Product/View', [
            'product' => $product,
            'id' => $id,
            'transactions' => $transactions,
            'suppliers' => $suppliers,
            'activeTab' => $activeTab,
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
        $product = Product::with(['income_account', 'expense_account', 'product_unit'])
            ->findOrFail($id);

        $productUnits = ProductUnit::select('id', 'unit')->get();
        $categories = SubCategory::select('id', 'name')->get();
        $brands = Brands::select('id', 'name')->get();
        $accounts = Account::select('id', 'account_name')->get();

        return inertia('Backend/User/Product/Edit', [
            'product' => $product,
            'productUnits' => $productUnits,
            'categories' => $categories,
            'brands' => $brands,
            'accounts' => $accounts,
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
            'name'                 => 'required',
            'type'                 => 'required',
            'image'                => 'nullable|image|max:2048',
            'allow_for_selling'    => 'required_without_all:allow_for_purchasing',
            'allow_for_purchasing' => 'nullable',
            'purchase_cost'        => 'nullable|required_if:allow_for_purchasing,1|numeric',
            'selling_price'        => 'nullable|required_if:allow_for_selling,1|numeric',
            'income_account_id'    => 'nullable|required_if:allow_for_selling,1|numeric',
            'expense_account_id'   => 'nullable|required_if:allow_for_purchasing,1|numeric',
            'status'               => 'required',
            'stock_management'     => 'required',
        ], [
            'allow_for_selling.required_without_all' => 'You need to choose at least for selling or purchasing',
            'purchase_cost.required_if'              => 'Purchase Cost is required',
            'selling_price.required_if'              => 'Selling Cost is required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('products.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        if (!Account::where('account_name', 'Common Shares')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '3000';
            $account->account_name    = 'Common Shares';
            $account->account_type    = 'Equity';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'cr';
            $account->save();
        }

        if (!Account::where('account_name', 'Inventory')->where('business_id', $request->activeBusiness->id)->exists()) {
            $account                  = new Account();
            $account->account_code    = '1000';
            $account->account_name    = 'Inventory';
            $account->account_type    = 'Other Current Asset';
            $account->opening_date    = Carbon::now()->format('Y-m-d');
            $account->business_id     = $request->activeBusiness->id;
            $account->user_id         = $request->activeBusiness->user->id;
            $account->dr_cr           = 'dr';
            $account->save();
        }

        $product                  = Product::find($id);

        $image = $product->image;

        if($request->image == null) {
            if (file_exists(public_path() . '/uploads/media/' . $image && $image != 'default.png')) {
                unlink(public_path() . '/uploads/media/' . $image);
            }

            $image = 'default.png';
        }

        if ($request->hasfile('image')) {
            $file  = $request->file('image');
            $image = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $image);
        }

        $previous_initial_stock = $product->initial_stock ?? 0;
        $new_initial_stock = $request->input('initial_stock') ?? 0;

        $stock_difference = $new_initial_stock - $previous_initial_stock;

        $product->name                 = $request->input('name');
        $product->type                 = $request->input('type');
        $product->product_unit_id      = $request->input('product_unit_id');
        $product->purchase_cost        = $request->allow_for_purchasing == 1 ? $request->input('purchase_cost') : 0;
        $product->selling_price        = $request->allow_for_selling == 1 ? $request->input('selling_price') : 0;
        $product->image = $image;
        $product->descriptions         = $request->input('descriptions');
        $product->expiry_date          = $request->input('expiry_date') ? Carbon::parse($request->input('expiry_date'))->format('Y-m-d') : null;
        $product->code                 = $request->input('code');
        $product->reorder_point        = $request->input('reorder_point');
        $product->allow_for_selling    = $request->input('allow_for_selling');
        $product->allow_for_purchasing = $request->input('allow_for_purchasing');
        $product->income_account_id    = $request->input('income_account_id');
        $product->expense_account_id   = $request->input('expense_account_id');
        $product->status               = $request->input('status');
        $product->stock_management     = $request->stock_management;
        $product->initial_stock        = $new_initial_stock;
        
        // Update current stock based on the difference in initial stock
        if($stock_difference != 0) {
            $product->stock = $product->stock + $stock_difference;
        }
        
        $product->sub_category_id      = $request->input('sub_category_id');
        $product->brand_id             = $request->input('brand_id');
        $product->save();

        // Handle transactions for initial stock changes
        if ($stock_difference != 0) {
            // Delete previous initial stock transactions
            Transaction::where('ref_id', $id)->where('ref_type', 'product')->delete();

            if ($stock_difference > 0) {
                // Add stock - Debit Inventory, Credit Common Shares
                $transaction              = new Transaction();
                $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $product->purchase_cost * $stock_difference;
                $transaction->transaction_amount      = $product->purchase_cost * $stock_difference;
                $transaction->description = $product->name . ' Opening Stock Adjustment +' . $stock_difference;
                $transaction->ref_id      = $product->id;
                $transaction->ref_type    = 'product';
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Common Shares')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $product->purchase_cost * $stock_difference;
                $transaction->transaction_amount      = $product->purchase_cost * $stock_difference;
                $transaction->description = $product->name . ' Opening Stock Adjustment +' . $stock_difference;
                $transaction->ref_id      = $product->id;
                $transaction->ref_type    = 'product';
                $transaction->save();
            } else {
                // Reduce stock - Credit Inventory, Debit Common Shares
                $transaction              = new Transaction();
                $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $product->purchase_cost * abs($stock_difference);
                $transaction->transaction_amount      = $product->purchase_cost * abs($stock_difference);
                $transaction->description = $product->name . ' Opening Stock Adjustment -' . abs($stock_difference);
                $transaction->ref_id      = $product->id;
                $transaction->ref_type    = 'product';
                $transaction->save();

                $transaction              = new Transaction();
                $transaction->trans_date  = now()->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Common Shares')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->activeBusiness->currency;
                $transaction->currency_rate           = Currency::where('name', $request->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount    = $product->purchase_cost * abs($stock_difference);
                $transaction->transaction_amount      = $product->purchase_cost * abs($stock_difference);
                $transaction->description = $product->name . ' Opening Stock Adjustment -' . abs($stock_difference);
                $transaction->ref_id      = $product->id;
                $transaction->ref_type    = 'product';
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Product Updated - ' . $product->name;
        $audit->save();

        return redirect()->route('products.index')->with('success', _lang('Updated Successfully'));
    }

    public function getProducts($type)
    {
        if ($type == 'sell') {
            $products = Product::active()->where('allow_for_selling', 1)->get();
        } else if ($type == 'buy') {
            $products = Product::active()->where('allow_for_purchasing', 1)->get();
        }
        return $products;
    }

    public function findProduct($id)
    {
        $product       = Product::active()->find($id);
        $decimal_place = get_business_option('decimal_places', 2);
        return response()->json(array('product' => $product, 'decimal_place' => $decimal_place));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Product Deleted - ' . $product->name;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $id)->where('ref_type', 'product')->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', _lang('Deleted Successfully'));
    }

    public function permanent_destroy($id)
    {
        $product = Product::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Product Permanent Deleted - ' . $product->name;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $id)->where('ref_type', 'product')->get();
        foreach ($transactions as $transaction) {
            $transaction->forceDelete();
        }

        // delete product image
        if (file_exists(public_path() . '/uploads/media/' . $product->image && $product->image != 'default.png')) {
            unlink(public_path() . '/uploads/media/' . $product->image);
        }

        $product->forceDelete();
        return redirect()->route('products.trash')->with('success', _lang('Permanent Deleted Successfully'));
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()->find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Product Restored - ' . $product->name;
        $audit->save();

        //  restore transactions
        $transactions = Transaction::onlyTrashed()->where('ref_id', $id)->where('ref_type', 'product')->get();
        foreach ($transactions as $transaction) {
            $transaction->restore();
        }

        $product->restore();
        return redirect()->route('products.trash')->with('success', _lang('Restored Successfully'));
    }

    public function import_products(Request $request)
    {
        $request->validate([
            'products_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProductImport, $request->file('products_file'));

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::user()->id;
            $audit->event = 'Products Imported - ' . $request->file('products_file')->getClientOriginalName();
            $audit->save();
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }

        return redirect()->route('products.index')->with('success', _lang('Products Imported'));
    }

    public function product_export()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Products Exported';
        $audit->save();

        return Excel::download(new ProductExport, 'products export ' . now()->format('d m Y') . '.xlsx');
    }

    public function bulk_destroy(Request $request)
    {

        $products = Product::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Products Deleted - ' . count($products) . ' Products';
        $audit->save();

        foreach ($products as $product) {

            // delete transactions
            $transactions = Transaction::where('ref_id', $product->id)->where('ref_type', 'product')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $product->delete();
        }

        return redirect()->route('products.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_restore(Request $request)
    {

        $products = Product::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Products Restored - ' . count($products) . ' Products';
        $audit->save();

        foreach ($products as $product) {

            // restore transactions
            $transactions = Transaction::onlyTrashed()->where('ref_id', $product->id)->where('ref_type', 'product')->get();
            foreach ($transactions as $transaction) {
                $transaction->restore();
            }

            $product->restore();
        }

        return redirect()->route('products.trash')->with('success', _lang('Restored Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {

        $products = Product::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::user()->id;
        $audit->event = 'Products Permanent Deleted - ' . count($products) . ' Products';
        $audit->save();

        foreach ($products as $product) {
            // delete product image
            if (file_exists(public_path() . '/uploads/media/' . $product->image && $product->image != 'default.png')) {
                unlink(public_path() . '/uploads/media/' . $product->image);
            }

            // delete transactions
            $transactions = Transaction::onlyTrashed()->where('ref_id', $product->id)->where('ref_type', 'product')->get();
            foreach ($transactions as $transaction) {
                $transaction->forceDelete();
            }

            $product->forceDelete();
        }

        return redirect()->route('products.trash')->with('success', _lang('Permanent Deleted Successfully'));
    }

    public function search_product(Request $request)
    {
        $products = Product::where('name', 'like', '%' . $request->search_term . '%')->get();
        $data     = array();
        foreach ($products as $product) {
            $data[] = array('id' => $product->id, 'name' => $product->name);
        }
        return $data;
    }


    public function getProductByBarcode(Request $request)
    {
        $product = Product::where('code', $request->barcode)->first();
        return response()->json($product);
    }
}
