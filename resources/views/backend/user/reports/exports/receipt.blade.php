<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">customer_name</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_number</td>
        <td style="background-color: lightgray; font-size: 12px">title</td>
        <td style="background-color: lightgray; font-size: 12px">order_number</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_date</td>
        <td style="background-color: lightgray; font-size: 12px">project_name</td>
        <td style="background-color: lightgray; font-size: 12px">transaction_currency</td>
        <td style="background-color: lightgray; font-size: 12px">exchange_rate</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">discount_amount</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_tax_total</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_grand_total</td>
        <td style="background-color: lightgray; font-size: 12px">payment_accounts</td>
        <td style="background-color: lightgray; font-size: 12px">payment_methods</td>
        <td style="background-color: lightgray; font-size: 12px">payment_references</td>
        <td style="background-color: lightgray; font-size: 12px">payment_amounts</td>
        <td style="background-color: lightgray; font-size: 12px">note</td>
        <td style="background-color: lightgray; font-size: 12px">footer</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
        <td style="background-color: lightgray; font-size: 12px">line_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">tax_names</td>
        <td style="background-color: lightgray; font-size: 12px">line_tax_total</td>
    </tr>
    <tbody>
        @foreach ($invoices as $invoice)
            @php
                $paymentTransactions = $invoice->transactions->filter(function ($transaction) {
                    $accountType = strtolower((string) optional($transaction->account)->account_type);

                    return strtolower((string) $transaction->dr_cr) === 'dr'
                        && in_array($accountType, ['bank', 'cash'], true);
                })->values();

                $paymentAccounts = $paymentTransactions->map(function ($transaction) {
                    return optional($transaction->account)->account_name;
                })->filter()->unique()->implode(', ');

                $paymentMethods = $paymentTransactions->pluck('transaction_method')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $paymentReferences = $paymentTransactions->pluck('reference')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $paymentAmounts = $paymentTransactions->map(function ($transaction) {
                    return (string) ($transaction->getRawOriginal('transaction_amount') ?: $transaction->transaction_amount);
                })->implode(', ');

                $invoiceTaxTotal = $invoice->taxes->sum(function ($tax) {
                    return (float) $tax->amount;
                });

                $discountType = '';
                if ((string) $invoice->discount_type === '0') {
                    $discountType = 'Percentage';
                } elseif ((string) $invoice->discount_type === '1') {
                    $discountType = 'Fixed';
                }
            @endphp

            @forelse ($invoice->items as $item)
                @php
                    $itemTaxNames = $item->taxes->pluck('name')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $itemTaxTotal = $item->taxes->sum(function ($tax) {
                        return (float) $tax->amount;
                    });
                @endphp
                <tr>
                    <td>{{ optional($invoice->customer)->name ?: 'Walk-in Customer' }}</td>
                    <td>{{ $invoice->receipt_number }}</td>
                    <td>{{ $invoice->title }}</td>
                    <td>{{ $invoice->order_number }}</td>
                    <td>{{ $invoice->getRawOriginal('receipt_date') ?: $invoice->receipt_date }}</td>
                    <td>{{ optional($invoice->project)->name }}</td>
                    <td>{{ $invoice->currency }}</td>
                    <td>{{ $invoice->getRawOriginal('exchange_rate') ?: $invoice->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $invoice->getRawOriginal('discount_value') ?: $invoice->discount_value }}</td>
                    <td>{{ $invoice->getRawOriginal('discount') ?: $invoice->discount }}</td>
                    <td>{{ number_format($invoiceTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $invoice->getRawOriginal('sub_total') ?: $invoice->sub_total }}</td>
                    <td>{{ $invoice->getRawOriginal('grand_total') ?: $invoice->grand_total }}</td>
                    <td>{{ $paymentAccounts }}</td>
                    <td>{{ $paymentMethods }}</td>
                    <td>{{ $paymentReferences }}</td>
                    <td>{{ $paymentAmounts }}</td>
                    <td>{{ $invoice->note }}</td>
                    <td>{{ $invoice->footer }}</td>
                    <td>{{ $item->product_name ?: optional($item->product)->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->getRawOriginal('unit_cost') ?: $item->unit_cost }}</td>
                    <td>{{ $item->getRawOriginal('sub_total') ?: $item->sub_total }}</td>
                    <td>{{ $itemTaxNames }}</td>
                    <td>{{ number_format($itemTaxTotal, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ optional($invoice->customer)->name ?: 'Walk-in Customer' }}</td>
                    <td>{{ $invoice->receipt_number }}</td>
                    <td>{{ $invoice->title }}</td>
                    <td>{{ $invoice->order_number }}</td>
                    <td>{{ $invoice->getRawOriginal('receipt_date') ?: $invoice->receipt_date }}</td>
                    <td>{{ optional($invoice->project)->name }}</td>
                    <td>{{ $invoice->currency }}</td>
                    <td>{{ $invoice->getRawOriginal('exchange_rate') ?: $invoice->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $invoice->getRawOriginal('discount_value') ?: $invoice->discount_value }}</td>
                    <td>{{ $invoice->getRawOriginal('discount') ?: $invoice->discount }}</td>
                    <td>{{ number_format($invoiceTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $invoice->getRawOriginal('sub_total') ?: $invoice->sub_total }}</td>
                    <td>{{ $invoice->getRawOriginal('grand_total') ?: $invoice->grand_total }}</td>
                    <td>{{ $paymentAccounts }}</td>
                    <td>{{ $paymentMethods }}</td>
                    <td>{{ $paymentReferences }}</td>
                    <td>{{ $paymentAmounts }}</td>
                    <td>{{ $invoice->note }}</td>
                    <td>{{ $invoice->footer }}</td>
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
