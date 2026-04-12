<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">supplier_name</td>
        <td style="background-color: lightgray; font-size: 12px">bill_no</td>
        <td style="background-color: lightgray; font-size: 12px">title</td>
        <td style="background-color: lightgray; font-size: 12px">order_number</td>
        <td style="background-color: lightgray; font-size: 12px">purchase_date</td>
        <td style="background-color: lightgray; font-size: 12px">due_date</td>
        <td style="background-color: lightgray; font-size: 12px">approval_status</td>
        <td style="background-color: lightgray; font-size: 12px">bill_status</td>
        <td style="background-color: lightgray; font-size: 12px">transaction_currency</td>
        <td style="background-color: lightgray; font-size: 12px">exchange_rate</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">discount_amount</td>
        <td style="background-color: lightgray; font-size: 12px">withholding_tax</td>
        <td style="background-color: lightgray; font-size: 12px">bill_tax_total</td>
        <td style="background-color: lightgray; font-size: 12px">bill_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">bill_grand_total</td>
        <td style="background-color: lightgray; font-size: 12px">bill_paid_total</td>
        <td style="background-color: lightgray; font-size: 12px">bill_due_total</td>
        <td style="background-color: lightgray; font-size: 12px">note</td>
        <td style="background-color: lightgray; font-size: 12px">footer</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">account_name</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
        <td style="background-color: lightgray; font-size: 12px">line_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">tax_names</td>
        <td style="background-color: lightgray; font-size: 12px">line_tax_total</td>
    </tr>
    <tbody>
        @foreach ($purchases as $purchase)
            @php
                $purchaseTaxTotal = $purchase->taxes->sum(function ($tax) {
                    return (float) ($tax->getRawOriginal('amount') ?: $tax->amount);
                });

                $discountType = '';
                if ((string) $purchase->discount_type === '0') {
                    $discountType = 'Percentage';
                } elseif ((string) $purchase->discount_type === '1') {
                    $discountType = 'Fixed';
                }

                $approvalStatusLabel = match ((int) ($purchase->approval_status ?? 0)) {
                    1 => 'Approved',
                    2 => 'Rejected',
                    4 => 'Verified',
                    default => 'Pending',
                };

                $billStatusLabel = match ((int) ($purchase->status ?? 0)) {
                    1 => 'Partial Paid',
                    2 => 'Paid',
                    default => 'Active',
                };

                $grandTotal = $purchase->getRawOriginal('grand_total') ?? $purchase->grand_total;
                $paidTotal = $purchase->getRawOriginal('paid') ?? $purchase->paid;
                $dueTotal = number_format(((float) $grandTotal) - ((float) $paidTotal), 2, '.', '');
            @endphp

            @forelse ($purchase->items as $item)
                @php
                    $itemTaxNames = $item->taxes->pluck('name')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $itemTaxTotal = $item->taxes->sum(function ($tax) {
                        return (float) ($tax->getRawOriginal('amount') ?: $tax->amount);
                    });
                @endphp
                <tr>
                    <td>{{ optional($purchase->vendor)->name }}</td>
                    <td>{{ $purchase->bill_no }}</td>
                    <td>{{ $purchase->title }}</td>
                    <td>{{ $purchase->po_so_number }}</td>
                    <td>{{ $purchase->getRawOriginal('purchase_date') ?: $purchase->purchase_date }}</td>
                    <td>{{ $purchase->getRawOriginal('due_date') ?: $purchase->due_date }}</td>
                    <td>{{ $approvalStatusLabel }}</td>
                    <td>{{ $billStatusLabel }}</td>
                    <td>{{ $purchase->currency }}</td>
                    <td>{{ $purchase->getRawOriginal('exchange_rate') ?? $purchase->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $purchase->getRawOriginal('discount_value') ?? $purchase->discount_value }}</td>
                    <td>{{ $purchase->getRawOriginal('discount') ?? $purchase->discount }}</td>
                    <td>{{ $purchase->getRawOriginal('withholding_tax') ?? $purchase->withholding_tax }}</td>
                    <td>{{ number_format($purchaseTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $purchase->getRawOriginal('sub_total') ?? $purchase->sub_total }}</td>
                    <td>{{ $grandTotal }}</td>
                    <td>{{ $paidTotal }}</td>
                    <td>{{ $dueTotal }}</td>
                    <td>{{ $purchase->note }}</td>
                    <td>{{ $purchase->footer }}</td>
                    <td>{{ $item->product_name ?: optional($item->product)->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ optional($item->account)->account_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->getRawOriginal('unit_cost') ?? $item->unit_cost }}</td>
                    <td>{{ $item->getRawOriginal('sub_total') ?? $item->sub_total }}</td>
                    <td>{{ $itemTaxNames }}</td>
                    <td>{{ number_format($itemTaxTotal, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ optional($purchase->vendor)->name }}</td>
                    <td>{{ $purchase->bill_no }}</td>
                    <td>{{ $purchase->title }}</td>
                    <td>{{ $purchase->po_so_number }}</td>
                    <td>{{ $purchase->getRawOriginal('purchase_date') ?: $purchase->purchase_date }}</td>
                    <td>{{ $purchase->getRawOriginal('due_date') ?: $purchase->due_date }}</td>
                    <td>{{ $approvalStatusLabel }}</td>
                    <td>{{ $billStatusLabel }}</td>
                    <td>{{ $purchase->currency }}</td>
                    <td>{{ $purchase->getRawOriginal('exchange_rate') ?? $purchase->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $purchase->getRawOriginal('discount_value') ?? $purchase->discount_value }}</td>
                    <td>{{ $purchase->getRawOriginal('discount') ?? $purchase->discount }}</td>
                    <td>{{ $purchase->getRawOriginal('withholding_tax') ?? $purchase->withholding_tax }}</td>
                    <td>{{ number_format($purchaseTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $purchase->getRawOriginal('sub_total') ?? $purchase->sub_total }}</td>
                    <td>{{ $grandTotal }}</td>
                    <td>{{ $paidTotal }}</td>
                    <td>{{ $dueTotal }}</td>
                    <td>{{ $purchase->note }}</td>
                    <td>{{ $purchase->footer }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforelse
        @endforeach
    </tbody>
</table>
