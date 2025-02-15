<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemTax;
use App\Models\Tax;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecurringInvoiceController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {

            if (package()->recurring_invoice != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $assets = ['datatable'];
        return view('backend.user.invoice.recurring.list', compact('assets'));
    }

    public function get_table_data(Request $request) {
        $invoices = Invoice::select('invoices.*')
            ->with('customer')
            ->where('is_recurring', 1)
            ->orderBy("invoices.id", "desc");

        return Datatables::eloquent($invoices)
            ->editColumn('grand_total', function ($invoice) {
                if ($invoice->customer->currency != request()->activeBusiness->currency) {
                    return '<div class="text-right">' . formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency)) . '<br>'
                    . formatAmount($invoice->converted_total, currency_symbol($invoice->customer->currency)) . '</div>';
                }
                return '<div class="text-right">' . formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency)) . '</div>';
            })
            ->addColumn('recurring_schedule', function ($invoice) {
                return _lang('Recurring Every') . ' ' . $invoice->recurring_value . ' ' . $invoice->recurring_type;
            })
            ->editColumn('status', function ($invoice) {
                return '<div class="text-center">' . recurring_invoice_status($invoice->status) . '</div>';
            })
            ->addColumn('action', function ($invoice) {
                return '<div class="dropdown text-center">'
                . '<button class="btn btn-outline-primary btn-xs dropdown-toggle" type="button" data-toggle="dropdown">' . _lang('Action')
                . '</button>'
                . '<div class="dropdown-menu">'
                . '<a class="dropdown-item ' . ($invoice->status == 2 ? "disabled" : "") . '" href="' . ($invoice->status != 2 ? route('recurring_invoices.edit', $invoice['id']) : '#') . '"><i class="far fa-edit mr-2"></i>' . _lang('Edit') . '</a>'
                . '<a class="dropdown-item" href="' . route('invoices.show', $invoice['id']) . '"><i class="far fa-eye mr-2"></i>' . _lang('Preview') . '</a>'
                . '<a class="dropdown-item" href="' . route('recurring_invoices.duplicate', $invoice['id']) . '"><i class="far fa-copy mr-2"></i>' . _lang('Duplicate') . '</a>'
                . '<form action="' . route('recurring_invoices.destroy', $invoice['id']) . '" method="post">'
                . csrf_field()
                . '<input name="_method" type="hidden" value="DELETE">'
                . ' <div class="dropdown-divider"></div>'
                . '<a class="dropdown-item" href="' . route('recurring_invoices.end_recurring', $invoice['id']) . '"><i class="far fa-stop-circle mr-2"></i>' . _lang('End Recurring') . '</a>'
                . '<button class="dropdown-item btn-remove" type="submit"><i class="fas fa-minus-circle mr-2"></i>' . _lang('Delete') . '</button>'
                    . '</form>'
                    . '</div>'
                    . '</div>';
            })
            ->filter(function ($query) use ($request) {

                if ($request->has('customer_id')) {
                    $query->where('customer_id', $request->customer_id);
                }

                if ($request->has('status')) {
                    $query->whereIn('status', json_decode($request->status));
                }
            })
            ->setRowId(function ($invoice) {
                return "row_" . $invoice->id;
            })
            ->rawColumns(['grand_total', 'recurring_schedule', 'status', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        return view('backend.user.invoice.recurring.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'customer_id'        => 'required',
            'title'              => 'required',
            'recurring_value'    => 'required',
            'recurring_type'     => 'required',
            'recurring_start'    => 'required|date|after_or_equal:today',
            'recurring_end'      => 'required|date|after:recurring_start',
            'recurring_due_date' => 'required',
            'product_id'         => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('recurring_invoices.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $invoice                  = new Invoice();
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->order_number    = $request->input('order_number');
        $invoice->invoice_date    = date('Y-m-d');
        $invoice->due_date        = date('Y-m-d');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->converted_total = convert_currency($request->activeBusiness->currency, $invoice->customer->currency, $invoice->grand_total);
        $invoice->paid            = 0;
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value');
        $invoice->template_type   = 0;
        $invoice->template        = $request->input('template');
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        $invoice->short_code      = rand(100000, 9999999) . uniqid();
        //Set Recurring
        $invoice->is_recurring           = 1;
        $invoice->recurring_start        = $request->recurring_start;
        $invoice->recurring_end          = $request->recurring_end;
        $invoice->recurring_value        = $request->recurring_value;
        $invoice->recurring_type         = $request->recurring_type;
        $invoice->recurring_invoice_date = $request->recurring_start; //Next Invoice Date
        $invoice->recurring_due_date     = $request->recurring_due_date;

        $invoice->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            if (isset($request->taxes[$invoiceItem->product_id])) {
                foreach ($request->taxes[$invoiceItem->product_id] as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        DB::commit();

        if ($invoice->id > 0) {
            return redirect()->route('invoices.show', $invoice->id)->with('success', _lang('Saved Successfully'));
        } else {
            return back()->with('error', _lang('Something going wrong, Please try again'));
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $invoice = Invoice::with('items')
            ->where('id', $id)
            ->where('status', '!=', 2)
            ->where('is_recurring', 1)
            ->first();
        return view('backend.user.invoice.recurring.edit', compact('invoice', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'customer_id'            => 'required',
            'title'                  => 'required',
            'recurring_value'        => 'required',
            'recurring_type'         => 'required',
            'recurring_invoice_date' => 'required|date|after_or_equal:today',
            'recurring_end'          => 'required|date|after:recurring_invoice_date',
            'recurring_due_date'     => 'required',
            'product_id'             => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('recurring_invoices.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $invoice = Invoice::where('id', $id)
            ->where('status', '!=', 2)
            ->where('is_recurring', 1)
            ->first();
        $invoice->customer_id     = $request->input('customer_id');
        $invoice->title           = $request->input('title');
        $invoice->order_number    = $request->input('order_number');
        $invoice->sub_total       = $summary['subTotal'];
        $invoice->grand_total     = $summary['grandTotal'];
        $invoice->converted_total = convert_currency($request->activeBusiness->currency, $invoice->customer->currency, $invoice->grand_total);
        $invoice->discount        = $summary['discountAmount'];
        $invoice->discount_type   = $request->input('discount_type');
        $invoice->discount_value  = $request->input('discount_value');
        $invoice->template_type   = 0;
        $invoice->template        = $request->input('template');
        $invoice->note            = $request->input('note');
        $invoice->footer          = $request->input('footer');
        $invoice->short_code      = rand(100000, 9999999) . uniqid();
        //Set Recurring
        $invoice->is_recurring = 1;

        if ($invoice->recurring_completed == 0) {
            $invoice->recurring_start = $request->recurring_invoice_date;
        }
        $invoice->recurring_end          = $request->recurring_end;
        $invoice->recurring_value        = $request->recurring_value;
        $invoice->recurring_type         = $request->recurring_type;
        $invoice->recurring_invoice_date = $request->recurring_invoice_date; //Next Invoice Date
        $invoice->recurring_due_date     = $request->recurring_due_date;

        $invoice->save();

        //Update Invoice item
        foreach ($invoice->items as $invoice_item) {
            $product = $invoice_item->product;
            if ($product->type == 'product' && $product->stock_management == 1) {
                $product->stock = $product->stock + $invoice_item->quantity;
                $product->save();
            }
            $invoice_item->delete();
        }

        for ($i = 0; $i < count($request->product_id); $i++) {
            $invoiceItem = $invoice->items()->save(new InvoiceItem([
                'invoice_id'   => $invoice->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
            ]));

            if (isset($request->taxes[$invoiceItem->product_id])) {
                $invoiceItem->taxes()->delete();
                foreach ($request->taxes[$invoiceItem->product_id] as $taxId) {
                    $tax = Tax::find($taxId);

                    $invoiceItem->taxes()->save(new InvoiceItemTax([
                        'invoice_id' => $invoice->id,
                        'tax_id'     => $taxId,
                        'name'       => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'     => ($invoiceItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        DB::commit();

        if (!$request->ajax()) {
            return redirect()->route('recurring_invoices.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $invoice, 'table' => '#invoices_table']);
        }

    }

    public function approve($id) {
        $invoice = Invoice::where('id', $id)
            ->where('is_recurring', 1)
            ->first();

        if ($invoice->recurring_start == null || $invoice->recurring_end == null || $invoice->recurring_value == null) {
            $validator = Validator::make([], []);
            $validator->errors()->add('recurring_end', _lang('Recurring end date is required'));
            $validator->errors()->add('recurring_value', _lang('Recurring period is required'));

            return redirect()->route('recurring_invoices.edit', $invoice->id)->withErrors($validator);
        }
        if ($invoice->status == 0) {
            DB::beginTransaction();

            //Create Invoice if starting date is today's date
            if ($invoice->getRawOriginal('recurring_start') == date('Y-m-d')) {
                $newInvoice                         = $invoice->replicate();
                $newInvoice->status                 = 1;
                $newInvoice->invoice_number         = get_business_option('invoice_number', rand());
                $newInvoice->invoice_date           = $invoice->getRawOriginal('recurring_invoice_date');
                $newInvoice->due_date               = date("Y-m-d", strtotime($invoice->getRawOriginal('recurring_invoice_date') . ' ' . $invoice->getRawOriginal('recurring_due_date')));
                $newInvoice->is_recurring           = 0;
                $newInvoice->recurring_completed    = 0;
                $newInvoice->recurring_start        = null;
                $newInvoice->recurring_end          = null;
                $newInvoice->recurring_value        = null;
                $newInvoice->recurring_invoice_date = null;
                $newInvoice->recurring_due_date     = null;
                $newInvoice->short_code             = rand(100000, 9999999) . uniqid();
                $newInvoice->parent_id              = $invoice->id;
                $newInvoice->save();

                foreach ($invoice->items as $invoiceItem) {
                    $newInvoiceItem             = $invoiceItem->replicate();
                    $newInvoiceItem->invoice_id = $newInvoice->id;
                    $newInvoiceItem->save();

                    foreach ($invoiceItem->taxes as $InvoiceItemTax) {
                        $newInvoiceItemTax                  = $InvoiceItemTax->replicate();
                        $newInvoiceItemTax->invoice_id      = $newInvoice->id;
                        $newInvoiceItemTax->invoice_item_id = $newInvoiceItem->id;
                        $newInvoiceItemTax->save();
                    }

                    //Update Stock
                    $product = $invoiceItem->product;
                    if ($product->type == 'product' && $product->stock_management == 1) {
                        $product->stock = $product->stock - $newInvoiceItem->quantity;
                        $product->save();
                    }
                }

                //Increment Invoice Number
                BusinessSetting::where('name', 'invoice_number')->increment('value');

                //Update Next Invoice Date
                $invoice->recurring_invoice_date = date("Y-m-d", strtotime($invoice->getRawOriginal('recurring_invoice_date') . ' +' . $invoice->recurring_value . ' ' . $invoice->recurring_type));
            }

            $invoice->status = 1;
            $invoice->save();

            DB::commit();

            return back()->with('success', _lang('Invoice has been approved'));
        }
        return back();
    }

    /** Duplicate Invoice */
    public function duplicate($id) {
        DB::beginTransaction();
        $invoice = Invoice::where('id', $id)
            ->where('is_recurring', 1)
            ->first();
        $newInvoice             = $invoice->replicate();
        $newInvoice->status     = 0;
        $newInvoice->short_code = rand(100000, 9999999) . uniqid();
        $newInvoice->save();

        //for ($i = 0; $i < count($request->product_id); $i++) {
        foreach ($invoice->items as $invoiceItem) {
            $newInvoiceItem             = $invoiceItem->replicate();
            $newInvoiceItem->invoice_id = $newInvoice->id;
            $newInvoiceItem->save();

            foreach ($invoiceItem->taxes as $InvoiceItemTax) {
                $newInvoiceItemTax                  = $InvoiceItemTax->replicate();
                $newInvoiceItemTax->invoice_id      = $newInvoice->id;
                $newInvoiceItemTax->invoice_item_id = $newInvoiceItem->id;
                $newInvoiceItemTax->save();
            }
        }

        DB::commit();

        return redirect()->route('recurring_invoices.edit', $newInvoice->id);
    }

    public function end_recurring($id) {
        $invoice = Invoice::where('id', $id)
            ->where('is_recurring', 1)
            ->first();
        if ($invoice->status != 2) {
            $invoice->status = 2;
            $invoice->save();
            return back()->with('success', _lang('Recurring has been ended'));
        }
        return back();
    }

    public function convert_recurring($id) {
        $invoice = Invoice::where('id', $id)
            ->where('is_recurring', 0)
            ->first();
        $invoice->status             = 0;
        $invoice->is_recurring       = 1;
        $invoice->recurring_type     = 'days';
        $invoice->recurring_due_date = '+0 day';
        $invoice->save();
        return redirect()->route('recurring_invoices.edit', $invoice->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $invoice = Invoice::find($id);
        $invoice->delete();
        return redirect()->route('recurring_invoices.index')->with('success', _lang('Deleted Successfully'));
    }

    private function calculateTotal(Request $request) {
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

            //Calculate Grand Total
            $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

            return array(
                'subTotal'       => $subTotal / $request->exchange_rate,
                'taxAmount'      => $taxAmount / $request->exchange_rate,
                'discountAmount' => $discountAmount / $request->exchange_rate,
                'grandTotal'     => $grandTotal / $request->exchange_rate,
            );
        }

    }
}