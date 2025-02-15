<?php

namespace App\Http\Controllers\User;

use App\Exports\ProductExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseItem;
use App\Models\ReceiptItem;
use App\Models\Transaction;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

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
    public function index()
    {
        $products = Product::select('products.*')
            ->with('product_unit')
            ->orderBy("products.id", "desc")
            ->get();

        return view('backend.user.product.list', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $productUnits = ProductUnit::all();

        return view('backend.user.product.create', compact('productUnits'));
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
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('products.create')
                    ->withErrors($validator)
                    ->withInput();
            }
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
        $product->expiry_date          = Carbon::parse($request->input('expiry_date'))->format('Y-m-d');
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
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'New Product Created - ' . $product->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('products.index')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $product, 'table' => '#products_table']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $product = Product::with('income_account')->with('expense_account')->find($id);
        if (!$request->ajax()) {
            return view('backend.user.product.view', compact('product', 'id'));
        } else {
            return view('backend.user.product.modal.view', compact('product', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $product   = Product::find($id);
        $product_invoices = InvoiceItem::where('product_id', $id)->sum('quantity');
        $product_receipts = ReceiptItem::where('product_id', $id)->sum('quantity');
        $product_purchases = PurchaseItem::where('product_id', $id)->sum('quantity');

        $product_sales = $product_invoices + $product_receipts + $product_purchases;

        return view('backend.user.product.edit', compact('product', 'id', 'product_sales'));
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
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('products.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

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

        $product                  = Product::find($id);
        $product->name                 = $request->input('name');
        $product->type                 = $request->input('type');
        $product->product_unit_id      = $request->input('product_unit_id');
        $product->purchase_cost        = $request->allow_for_purchasing == 1 ? $request->input('purchase_cost') : 0;
        $product->selling_price        = $request->allow_for_selling == 1 ? $request->input('selling_price') : 0;
        if ($request->hasfile('image')) {
            $product->image = $image;
        }
        $product->descriptions         = $request->input('descriptions');
        $product->expiry_date          = Carbon::parse($request->input('expiry_date'))->format('Y-m-d');
        $product->code                 = $request->input('code');
        $product->reorder_point        = $request->input('reorder_point');
        $product->allow_for_selling    = $request->input('allow_for_selling');
        $product->allow_for_purchasing = $request->input('allow_for_purchasing');
        $product->income_account_id    = $request->input('income_account_id');
        $product->expense_account_id   = $request->input('expense_account_id');
        $product->status               = $request->input('status');
        $product->stock_management     = $request->stock_management;
        $product->stock                = $request->input('initial_stock') ?? 0;
        $product->initial_stock        = $request->input('initial_stock') ?? 0;
        $product->category_id          = $request->input('category_id');
        $product->brand_id             = $request->input('brand_id');
        $product->save();

        if ($request->input('initial_stock') == 0) {
            // delete previous transactions
            Transaction::where('ref_id', $id)->where('ref_type', 'product')->delete();
        }

        if ($request->input('initial_stock') > 0) {
            // delete previous transactions
            Transaction::where('ref_id', $id)->where('ref_type', 'product')->delete();

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
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Product Updated - ' . $product->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('products.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $product, 'table' => '#products_table']);
        }
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
        $audit->changed_by = auth()->user()->id;
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

    public function import_products(Request $request)
    {
        $request->validate([
            'products_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProductImport, $request->file('products_file'));
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Products Imported - ' . $request->file('products_file')->getClientOriginalName();
        $audit->save();

        return redirect()->route('products.index')->with('success', _lang('Products Imported'));
    }

    public function product_export()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Products Exported';
        $audit->save();

        return Excel::download(new ProductExport, 'products export ' . now()->format('d m Y') . '.xlsx');
    }

    public function products_all(Request $request)
    {
        if ($request->products == null) {
            return redirect()->route('products.index')->with('error', _lang('Please Select Product'));
        }

        $products = Product::whereIn('id', $request->products)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Products Deleted - ' . count($products) . ' Products';
        $audit->save();

        foreach ($products as $product) {
            // delete product image
            if (file_exists(public_path() . '/uploads/media/' . $product->image && $product->image != 'default.png')) {
                unlink(public_path() . '/uploads/media/' . $product->image);
            }

            // delete transactions
            $transactions = Transaction::where('ref_id', $product->id)->where('ref_type', 'product')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $product->delete();
        }

        return redirect()->route('products.index')->with('success', _lang('Deleted Successfully'));
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
