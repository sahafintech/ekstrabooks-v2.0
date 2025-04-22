<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\DefferedAddition;
use App\Models\DefferedDeduction;
use App\Models\DefferedEarning;
use App\Models\DefferedPayment;
use App\Models\InsuranceBenefit;
use App\Models\InsuranceFamilySize;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DefferedInvoiceController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->deffered_invoice != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
                }
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Invoice::where('is_deffered', 1)->with('customer')->orderBy("id", "desc");

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('grand_total', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $invoices = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Invoice/Deffered/List', [
            'invoices' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'from' => $invoices->firstItem(),
                'last_page' => $invoices->lastPage(),
                'links' => $invoices->linkCollection(),
                'path' => $invoices->path(),
                'per_page' => $invoices->perPage(),
                'to' => $invoices->lastItem(),
                'total' => $invoices->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
        ]);
    }

    public function create()
    {
        $invoice_title = get_business_option('invoice_title', 'Invoice');
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $familySizes = InsuranceFamilySize::all();
        $benefits = InsuranceBenefit::all();
        $decimalPlace = get_business_option('decimal_place', 2);
        $dateFormat = get_business_option('date_format', 'Y-m-d');

        return Inertia::render('Backend/User/Invoice/Deffered/Create', [
            'invoice_title' => $invoice_title,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'decimalPlace' => $decimalPlace,
            'familySizes' => $familySizes,
            'benefits' => $benefits,
            'dateFormat' => $dateFormat
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => 'required',
            'title'          => 'required',
            'deffered_start' => 'required|date',
            'deffered_end'   => 'required|after_or_equal:deffered_start',
            'product_id'     => 'required',
            'template'       => 'required',
            'currency'       => 'required',
            'invoice_category' => 'required',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|after_or_equal:invoice_date',
            'earnings'  => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory', 'Unearned Revenue'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                } else if ($account == 'Unearned Revenue') {
                    $account_obj->account_code = '2300';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                } else if ($account == 'Unearned Revenue') {
                    $account_obj->account_type = 'Current Liability';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                } else if ($account == 'Unearned Revenue') {
                    $account_obj->dr_cr   = 'cr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $summary = $this->calculateTotal($request);

        $currentTime = Carbon::now();

        $invoice                  = new Invoice();
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->invoice_number  = get_business_option('invoice_number');
        $invoice->order_number    = $request->input('order_number');
        $invoice->invoice_date    = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $invoice->due_date        = Carbon::parse($request->input('due_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->currency        = $request['currency'];
        $invoice->converted_total = $request->input('converted_total');
        $invoice->exchange_rate   = Currency::where('name', $request->currency)->first()->exchange_rate;
        $invoice->paid            = 0;
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value');
        $invoice->template_type   = is_numeric($request->template) ? 1 : 0;
        $invoice->template        = $request->input('template');
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        $invoice->short_code      = rand(100000, 9999999) . uniqid();
        // deffered invoice
        $invoice->is_deffered     = 1;
        $invoice->invoice_category = $request->input('invoice_category');
        $invoice->deffered_start  = Carbon::parse($request->input('deffered_start'))->format('Y-m-d');
        $invoice->deffered_end    = Carbon::parse($request->input('deffered_end'))->format('Y-m-d');
        $invoice->active_days     = $request->input('active_days');
        $invoice->cost_per_day    = $request->input('cost_per_day');
        $invoice->created_by      = auth()->user()->id;
        $invoice->save();

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
                    $attachment->ref_type = 'invoice';
                    $attachment->ref_id = $invoice->id;
                    $attachment->save();
                }
            }
        }

        if ($request->earnings) {
            foreach ($request->earnings as $earning) {
                $defferedEarnings = new DefferedEarning();
                $defferedEarnings->invoice_id = $invoice->id;
                $defferedEarnings->start_date = Carbon::parse($earning['start_date'])->format('Y-m-d');
                $defferedEarnings->end_date = Carbon::parse($earning['end_date'])->format('Y-m-d');
                $defferedEarnings->days = $earning['number_of_days'];
                $defferedEarnings->amount = $earning['amount'];
                $defferedEarnings->save();
            }
        }

        // if ($request->payments['date'][0] != null) {
        //     foreach ($request->payments['date'] as $key => $date) {
        //         $defferedPayment = new DefferedPayment();
        //         $defferedPayment->invoice_id = $invoice->id;
        //         $defferedPayment->date = Carbon::parse($date)->format('Y-m-d');
        //         $defferedPayment->due_date = Carbon::parse($request->payments['due_date'][$key])->format('Y-m-d');
        //         $defferedPayment->amount = $request->payments['amount'][$key];
        //         $defferedPayment->save();
        //     }
        // }

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => Product::find($request->product_id[$i])->name,
                'description'  => null,
                'sum_insured'  => $request->sum_insured[$i] ?? null,
                'benefits'     => $request->benefits[$i] ?? null,
                'limits'       => $request->limits[$i] ?? null,
                'family_size'  => $request->family_size[$i] ?? null,
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }

            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $invoice->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Deffered Invoice Tax') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'd invoice tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $request->quantity[$i]) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'invoice_number')->increment('value');

        DB::commit();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Unearned Revenue')->id;
        $transaction->dr_cr       = 'cr';
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate = $invoice->exchange_rate;
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['subTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['subTotal']));
        $transaction->description = _lang('Deffered Invoice Liability') . ' #' . $invoice->invoice_number;
        $transaction->ref_id      = $invoice->id;
        $transaction->ref_type    = 'd invoice';
        $transaction->customer_id = $invoice->customer_id;
        $transaction->save();

        $transaction              = new Transaction();
        $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->account_id  = get_account('Accounts Receivable')->id;
        $transaction->dr_cr       = 'dr';
        $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->transaction_currency    = $request->currency;
        $transaction->currency_rate           = $invoice->exchange_rate;
        $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->ref_id      = $invoice->id;
        $transaction->ref_type    = 'd invoice';
        $transaction->customer_id = $invoice->customer_id;
        $transaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
        $transaction->save();

        if ($request->input('discount_value') > 0) {
            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = get_account('Sales Discount Allowed')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
            $transaction->transaction_currency    = $request->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
            $transaction->description = _lang('Deffered Invoice Discount') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $invoice->id;
            $transaction->ref_type    = 'd invoice';
            $transaction->customer_id = $invoice->customer_id;
            $transaction->save();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deffered Invoice Created: ' . $invoice->title;
        $audit->save();

        return redirect()->route('deffered_invoices.show', $invoice->id)->with('success', _lang('Saved Successfully'));
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
            if (isset($request->tax_amount)) {
                foreach ($request->tax_amount as $index => $amount) {
                    if ($amount == 0) {
                        continue;
                    }
                    $tax         = Tax::find($index);
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
            'subTotal'       => $subTotal / $request->exchange_rate,
            'taxAmount'      => $taxAmount / $request->exchange_rate,
            'discountAmount' => $discountAmount / $request->exchange_rate,
            'grandTotal'     => $grandTotal / $request->exchange_rate,
        );
    }

    public function payments($id)
    {
        $payments = DefferedPayment::where('invoice_id', $id)->get();
        return view('backend.user.invoice.deffered.modals.payments', compact('payments'));
    }

    public function earnings($id)
    {
        $payments = DefferedEarning::where('invoice_id', $id)->get();
        return view('backend.user.invoice.deffered.modals.earnings', compact('payments'));
    }

    public function destroy($id)
    {
        $invoice = Invoice::find($id);
        if ($invoice) {
            $invoice->items()->delete();
            // deffered payments
            $defferedPayments = DefferedPayment::where('invoice_id', $id)->get();
            foreach ($defferedPayments as $defferedPayment) {
                $defferedPayment->delete();
            }
            // deffered deductions
            $defferedDeductions = DefferedDeduction::where('invoice_id', $id)->get();
            foreach ($defferedDeductions as $defferedDeduction) {
                $defferedDeduction->delete();
            }
            // deffered additions
            $defferedAdditions = DefferedAddition::where('invoice_id', $id)->get();
            foreach ($defferedAdditions as $defferedAddition) {
                $defferedAddition->delete();
            }
            // transactions
            $transactions = Transaction::where('ref_id', $invoice->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'd invoice')
                        ->orWhere('ref_type', 'd invoice tax')
                        ->orWhere('ref_type', 'd invoice payment')
                        ->orWhere('ref_type', 'd invoice income');
                })
                ->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }
            // delete attachments
            $attachments = Attachment::where('ref_id', $invoice->id)->where('ref_type', 'invoice')->get();
            foreach ($attachments as $attachment) {
                $filePath = public_path($attachment->path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $attachment->delete();
            }
            $invoice->delete();
            return redirect()->route('deffered_invoices.index')->with('success', _lang('Deleted Successfully'));
        } else {
            return redirect()->route('deffered_invoices.index')->with('error', _lang('Something going wrong, Please try again'));
        }
    }

    public function edit($id)
    {
        $invoice = Invoice::where('id', $id)->with('deffered_earnings', 'items', 'taxes')->first();

        $attachments = Attachment::where('ref_id', $id)->where('ref_type', 'invoice')->get();
        $customers = Customer::all();
        $currencies = Currency::all();
        $products = Product::all();
        $taxes = Tax::all();
        $familySizes = InsuranceFamilySize::all();
        $benefits = InsuranceBenefit::all();
        $decimalPlace = get_business_option('decimal_place', 2);
        $dateFormat = get_business_option('date_format', 'Y-m-d');
        $taxIds = $invoice->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();


        return Inertia::render('Backend/User/Invoice/Deffered/Edit', [
            'invoice' => $invoice,
            'attachments' => $attachments,
            'customers' => $customers,
            'currencies' => $currencies,
            'products' => $products,
            'taxes' => $taxes,
            'taxIds' => $taxIds,
            'decimalPlace' => $decimalPlace,
            'familySizes' => $familySizes,
            'benefits' => $benefits,
            'dateFormat' => $dateFormat
        ]);
    }

    public function get_invoices(Request $request)
    {
        return Customer::where('id', $request->id)
            ->with(['invoices' => function ($query) {
                $query->where('status', '!=', 2)
                    ->where('is_deffered', 1)
                    ->with('deffered_payments');
            }])
            ->first();
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'customer_id'    => 'required',
            'title'          => 'required',
            'deffered_start' => 'required|date',
            'deffered_end'   => 'required|after_or_equal:deffered_start',
            'product_id'     => 'required',
            'template'       => 'required',
            'currency'       => 'required',
            'invoice_category' => 'required',
            'invoice_date'   => 'required|date',
            'due_date'       => 'required|after_or_equal:invoice_date',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $currentTime = Carbon::now();

        $invoice                  = Invoice::find($id);
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->invoice_date    = Carbon::parse($request->input('invoice_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $invoice->due_date        = Carbon::parse($request->input('due_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $invoice->order_number    = $request->input('order_number');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->currency        = $request['currency'];
        $invoice->converted_total = $request->input('converted_total');
        $invoice->exchange_rate   = Currency::where('name', $request->currency)->first()->exchange_rate;
        $invoice->paid            = 0;
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value');
        $invoice->template_type   = is_numeric($request->template) ? 1 : 0;
        $invoice->template        = $request->input('template');
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        $invoice->short_code      = rand(100000, 9999999) . uniqid();
        // deffered invoice
        $invoice->invoice_category = $request->input('invoice_category');
        $invoice->is_deffered     = 1;
        $invoice->deffered_start  = Carbon::parse($request->input('deffered_start'))->format('Y-m-d');
        $invoice->deffered_end    = Carbon::parse($request->input('deffered_end'))->format('Y-m-d');
        $invoice->active_days     = $request->input('active_days');
        $invoice->cost_per_day    = $request->input('cost_per_day');
        $invoice->updated_by      = auth()->user()->id;
        $invoice->save();

        // delete old attachments
        $attachments = Attachment::where('ref_id', $invoice->id)->where('ref_type', 'invoice')->get(); // Get attachments from the database

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
                    $attachment->ref_type = 'invoice';
                    $attachment->ref_id = $invoice->id;
                    $attachment->save();
                }
            }
        }

        if ($request->earnings) {
            foreach ($request->earnings as $earning) {
                $defferedEarning = DefferedEarning::where('invoice_id', $invoice->id)->where('start_date', Carbon::parse($earning['start_date'])->format('Y-m-d'))->where('end_date', Carbon::parse($earning['end_date'])->format('Y-m-d'))->where('status', 0)->first();

                if ($defferedEarning) {
                    $defferedEarning->amount = $earning['amount'];
                    $defferedEarning->save();
                }
            }
        }

        // if (isset($request->payments) &&  isset($request->payments['date'])) {
        //     if ($request->payments['date'][0] != null) {
        //         // delete all previous payments which have status 0
        //         $defferedPayment = DefferedPayment::where('status', 0)->get();
        //         foreach ($defferedPayment as $payment) {
        //             $payment->delete();
        //         }
        //         foreach ($request->payments['date'] as $key => $date) {
        //             $defferedPayment = new DefferedPayment();
        //             $defferedPayment->invoice_id = $invoice->id;
        //             $defferedPayment->date = Carbon::parse($date)->format('Y-m-d');
        //             $defferedPayment->due_date = Carbon::parse($request->payments['due_date'][$key])->format('Y-m-d');
        //             $defferedPayment->amount = $request->payments['amount'][$key];
        //             $defferedPayment->save();
        //         }
        //     }
        // }

        // if ($request->deductions['amount'][0] != null) {
        //     $deductions = DefferedDeduction::where('invoice_id', $invoice->id)->get();

        //     foreach ($deductions as $deduction) {
        //         $deduction->delete();
        //     }

        //     $invoice->grand_total = $invoice->grand_total - array_sum($request->deductions['amount']);
        //     $invoice->converted_total = convert_currency($request->currency, $request->activeBusiness->currency, $invoice->grand_total);
        //     $invoice->save();

        //     foreach ($request->deductions['amount'] as $key => $amount) {
        //         $defferedDeduction = new DefferedDeduction();
        //         $defferedDeduction->invoice_id = $invoice->id;
        //         $defferedDeduction->amount = $amount;
        //         $defferedDeduction->description = $request->deductions['description'][$key];
        //         $defferedDeduction->save();
        //     }
        // }

        // if ($request->additions['amount'][0] != null) {
        //     $additions = DefferedAddition::where('invoice_id', $invoice->id)->get();

        //     foreach ($additions as $addition) {
        //         $addition->delete();
        //     }

        //     $invoice->grand_total = $invoice->grand_total + array_sum($request->additions['amount']);
        //     $invoice->converted_total = convert_currency($request->currency, $request->activeBusiness->currency, $invoice->grand_total);
        //     $invoice->save();

        //     foreach ($request->additions['amount'] as $key => $amount) {
        //         $defferedAddition = new DefferedAddition();
        //         $defferedAddition->invoice_id = $invoice->id;
        //         $defferedAddition->amount = $amount;
        //         $defferedAddition->description = $request->additions['description'][$key];
        //         $defferedAddition->save();
        //     }
        // }

        //Update Invoice item
        foreach ($invoice->items as $invoice_item) {
            $product = $invoice_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $invoice_item->quantity;
                $product->save();
            }

            if ($invoice_item->taxes) {
                $invoice_item->taxes()->delete();

                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice tax')
                    ->get();

                foreach ($transaction as $t) {
                    $t->delete();
                }
            }

            $invoice_item->delete();

            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
                ->where('account_id', $product->income_account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            if ($product->stock_management == 1) {
                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
                    ->where('account_id', get_account('Inventory')->id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
                    ->where('account_id', $product->expense_account_id)
                    ->first();

                if ($transaction != null) {
                    $transaction->delete();
                }
            }
        }

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => Product::find($request->product_id[$i])->name,
                'description'  => null,
                'sum_insured'  => $request->sum_insured[$i] ?? null,
                'benefits'     => $request->benefits[$i] ?? null,
                'limits'       => $request->limits[$i] ?? null,
                'family_size'  => $request->family_size[$i] ?? null,
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            $product = Product::where('id', $request->product_id[$i])->first();

            if ($product->stock_management == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Inventory')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->description = $invoiceItem->product_name . ' Sales #' . $invoiceItem->quantity;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            }

            if ($product->allow_for_purchasing == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $product->expense_account_id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $invoice->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $product->purchase_cost * $invoiceItem->quantity));
                $transaction->ref_type    = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->ref_id      = $invoice->id;
                $transaction->description = 'Deffered Invoice #' . $invoice->invoice_number;
                $transaction->save();
            }



            if (isset($request->taxes)) {
                foreach ($request->taxes as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));

                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $invoice->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($invoiceItem->sub_total / 100) * $tax->rate);
                    $transaction->description = _lang('Deffered Invoice Tax') . ' #' . $invoice->invoice_number;
                    $transaction->ref_id      = $invoice->id;
                    $transaction->ref_type    = 'd invoice tax';
                    $transaction->tax_id      = $taxId;
                    $transaction->save();
                }
            }

            //Update Stock
            $product = $invoiceItem->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                //Check Available Stock Quantity
                if ($product->stock < $request->quantity[$i]) {
                    DB::rollBack();
                    return back()->with('error', $product->name . ' ' . _lang('Stock is not available!'));
                }

                $product->stock = $product->stock - $request->quantity[$i];
                $product->save();
            }
        }

        //Increment Invoice Number
        BusinessSetting::where('name', 'invoice_number')->increment('value');

        DB::commit();

        $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
            ->where('account_id', get_account('Unearned Revenue')->id)
            ->first();
        $transaction->trans_date = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['subTotal']);
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['subTotal']));
        $transaction->save();

        $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
            ->where('account_id', get_account('Accounts Receivable')->id)
            ->first();
        $transaction->trans_date = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
        $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
        $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
        $transaction->save();

        if ($request->input('discount_value') == 0) {
            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction != null) {
                $transaction->delete();
            }
        }

        if ($request->input('discount_value') > 0) {
            $transaction = Transaction::where('ref_id', $invoice->id)->where('ref_type', 'd invoice')
                ->where('account_id', get_account('Sales Discount Allowed')->id)
                ->first();
            if ($transaction == null) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Sales Discount Allowed')->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $invoice->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Differed Invoice Discount') . ' #' . $invoice->invoice_number;
                $transaction->ref_id      = $invoice->id;
                $transaction->ref_type    = 'd invoice';
                $transaction->customer_id = $invoice->customer_id;
                $transaction->save();
            } else {
                $transaction->trans_date = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->transaction_amount = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deffered Invoice Updated: ' . $invoice->title;
        $audit->save();

        return redirect()->route('deffered_invoices.show', $invoice->id)->with('success', _lang('Invoice Updated Successfully'));
    }

    /**
     * Preview Private Invoice
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $invoice = Invoice::with([
            'business',
            'items',
            'deffered_earnings',
            'customer',
            'taxes'
        ])->find($id);

        $attachments = Attachment::where('ref_id', $id)
            ->where('ref_type', 'invoice')
            ->get();

        $decimalPlaces = get_business_option('decimal_places', 2);

        return Inertia::render('Backend/User/Invoice/Deffered/View', [
            'invoice' => $invoice,
            'attachments' => $attachments,
            'decimalPlaces' => $decimalPlaces
        ]);
    }

    public function deffered_invoices_filter(Request $request)
    {
        $from =  explode('to', $request->date_range)[0] ?? '';
        $to = explode('to', $request->date_range)[1] ?? '';

        $query = Invoice::select('invoices.*')
            ->with('customer')
            ->where('is_recurring', 0)
            ->where('is_deffered', 1);

        if ($request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status != '') {
            if ($request->status == 1) {
                $query->where('deffered_end', '>=', Carbon::now()->format('Y-m-d'));
            } else {
                $query->where('deffered_end', '<', Carbon::now()->format('Y-m-d'));
            }
        }

        if ($from != '' && $to != '') {
            $query->whereDate('invoice_date', '>=', Carbon::parse($from)->format('Y-m-d'))
                ->whereDate('due_date', '<=', Carbon::parse($to)->format('Y-m-d'));
        }

        $invoices = $query->get();

        $status = $request->status;
        $customer_id = $request->customer_id;
        $date_range = $request->date_range;

        return view('backend.user.invoice.deffered.list', compact('invoices', 'status', 'customer_id', 'date_range'));
    }
}
