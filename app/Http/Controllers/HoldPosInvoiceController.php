<?php

namespace App\Http\Controllers;

use App\Models\HoldPosInvoice;
use App\Models\HoldPosInvoiceItem;
use App\Models\HoldPosInvoiceItemTax;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HoldPosInvoiceController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('receipts.create');

        $request->validate([
            'customer_id'    => 'nullable',
            'product_id'     => 'required',
            'currency'       => 'required',
        ], [
            'product_id.required' => _lang('You must add at least one item'),
        ]);

        $summary = $this->calculatePosTotal($request);

        $holdPosInvoice                  = new HoldPosInvoice();
        $holdPosInvoice->customer_id     = $request->input('customer_id');
        $holdPosInvoice->title           = 'Cash Invoice';
        $holdPosInvoice->receipt_number  = get_business_option('receipt_number');
        $holdPosInvoice->receipt_date    = now()->format('Y-m-d');
        $holdPosInvoice->sub_total       = $summary['subTotal'];
        $holdPosInvoice->grand_total     = $summary['grandTotal'];
        $holdPosInvoice->currency        = $request['currency'];
        $holdPosInvoice->converted_total = $request->input('converted_total');
        $holdPosInvoice->exchange_rate   = $request->input('exchange_rate');
        $holdPosInvoice->discount        = $summary['discountAmount'];
        $holdPosInvoice->discount_type   = $request->input('discount_type') ?? 0;
        $holdPosInvoice->discount_value  = $request->input('discount_value') ?? 0;
        $holdPosInvoice->user_id         = auth()->user()->id;
        $holdPosInvoice->business_id     = request()->activeBusiness->id;
        $holdPosInvoice->short_code      = rand(100000, 9999999) . uniqid();
        $holdPosInvoice->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $holdPosInvoiceItem = $holdPosInvoice->items()->save(new HoldPosInvoiceItem([
                'hold_pos_invoice_id'   => $holdPosInvoice->id,
                'product_id'            => $request->product_id[$i],
                'product_name'          => $request->product_name[$i],
                'description'           => null,
                'quantity'              => $request->quantity[$i],
                'unit_cost'             => $request->unit_cost[$i],
                'sub_total'             => ($request->unit_cost[$i] * $request->quantity[$i]),
                'user_id'               => auth()->user()->id,
                'business_id'           => request()->activeBusiness->id,
            ]));

            if (isset($request->tax_amount)) {
                foreach ($request->tax_amount as $index => $amount) {
                    $tax = Tax::find($index);

                    $holdPosInvoice->taxes()->save(new HoldPosInvoiceItemTax([
                        'hold_pos_invoice_id'       => $holdPosInvoice->id,
                        'hold_pos_invoice_item_id'  => $holdPosInvoiceItem->id,
                        'tax_id'                    => $index,
                        'name'                      => $tax->name . ' ' . $tax->rate . ' %',
                        'amount'                    => ($holdPosInvoiceItem->sub_total / 100) * $tax->rate,
                    ]));
                }
            }
        }

        return redirect()->route('receipts.pos');
    }

    private function calculatePosTotal(Request $request)
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
            'subTotal'       => $subTotal,
            'taxAmount'      => $taxAmount,
            'discountAmount' => $discountAmount,
            'grandTotal'     => $grandTotal,
        );
    }

    public function show($id) {
        $holdPosInvoice = HoldPosInvoice::with('items', 'customer', 'taxes')->find($id);

        return $holdPosInvoice;
    }
}
