<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">customer_name</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_number</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_type</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_category</td>
        <td style="background-color: lightgray; font-size: 12px">title</td>
        <td style="background-color: lightgray; font-size: 12px">po_so_number</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_date</td>
        <td style="background-color: lightgray; font-size: 12px">expired_date</td>
        <td style="background-color: lightgray; font-size: 12px">status</td>
        <td style="background-color: lightgray; font-size: 12px">transaction_currency</td>
        <td style="background-color: lightgray; font-size: 12px">exchange_rate</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">discount_amount</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_tax_total</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">quotation_grand_total</td>
        <td style="background-color: lightgray; font-size: 12px">note</td>
        <td style="background-color: lightgray; font-size: 12px">footer</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">sum_insured</td>
        <td style="background-color: lightgray; font-size: 12px">medical_coverage_configuration</td>
        <td style="background-color: lightgray; font-size: 12px">family_size</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
        <td style="background-color: lightgray; font-size: 12px">line_sub_total</td>
        <td style="background-color: lightgray; font-size: 12px">tax_names</td>
        <td style="background-color: lightgray; font-size: 12px">line_tax_total</td>
    </tr>
    <tbody>
        @foreach ($quotations as $quotation)
            @php
                $quotationTaxTotal = $quotation->taxes->sum(function ($tax) {
                    return (float) ($tax->getRawOriginal('amount') ?: $tax->amount);
                });

                $discountType = '';
                if ((string) $quotation->discount_type === '0') {
                    $discountType = 'Percentage';
                } elseif ((string) $quotation->discount_type === '1') {
                    $discountType = 'Fixed';
                }

                $statusLabel = 'Pending';
                if ((int) ($quotation->status ?? 0) === 1) {
                    $statusLabel = 'Accepted';
                } elseif ((int) ($quotation->status ?? 0) === 2) {
                    $statusLabel = 'Rejected';
                } elseif (\Carbon\Carbon::parse($quotation->getRawOriginal('expired_date'))->lt(\Carbon\Carbon::today())) {
                    $statusLabel = 'Expired';
                } else {
                    $statusLabel = 'Active';
                }

                $quotationType = (int) ($quotation->is_deffered ?? 0) === 1 ? 'Deferred' : 'Normal';
            @endphp

            @forelse ($quotation->items as $item)
                @php
                    $itemTaxNames = $item->taxes->pluck('name')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $itemTaxTotal = $item->taxes->sum(function ($tax) {
                        return (float) ($tax->getRawOriginal('amount') ?: $tax->amount);
                    });

                    $medicalCoverageConfiguration = collect([
                        'Inpatient' => 'inpatient',
                        'Maternity' => 'maternity',
                        'Outpatient' => 'outpatient',
                        'Dental' => 'dental',
                        'Optical' => 'optical',
                        'Telemedicine' => 'telemedicine',
                    ])->map(function ($key, $label) use ($item) {
                        $limit = $item->{"{$key}_limit_per_family"};
                        $contribution = $item->{"{$key}_contribution_per_family"};
                        $total = $item->{"{$key}_total_contribution"};

                        if ($limit === null && $contribution === null && $total === null) {
                            return null;
                        }

                        return sprintf(
                            '%s: Limit/Family %s | Contribution/Family %s | Total %s',
                            $label,
                            $limit ?? '-',
                            $contribution ?? '-',
                            $total ?? '-'
                        );
                    })->filter()->implode('; ');
                @endphp
                <tr>
                    <td>{{ optional($quotation->customer)->name }}</td>
                    <td>{{ $quotation->quotation_number }}</td>
                    <td>{{ $quotationType }}</td>
                    <td>{{ $quotation->invoice_category }}</td>
                    <td>{{ $quotation->title }}</td>
                    <td>{{ $quotation->po_so_number }}</td>
                    <td>{{ $quotation->getRawOriginal('quotation_date') ?: $quotation->quotation_date }}</td>
                    <td>{{ $quotation->getRawOriginal('expired_date') ?: $quotation->expired_date }}</td>
                    <td>{{ $statusLabel }}</td>
                    <td>{{ $quotation->currency }}</td>
                    <td>{{ $quotation->getRawOriginal('exchange_rate') ?: $quotation->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $quotation->getRawOriginal('discount_value') ?: $quotation->discount_value }}</td>
                    <td>{{ $quotation->getRawOriginal('discount') ?: $quotation->discount }}</td>
                    <td>{{ number_format($quotationTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $quotation->getRawOriginal('sub_total') ?: $quotation->sub_total }}</td>
                    <td>{{ $quotation->getRawOriginal('grand_total') ?: $quotation->grand_total }}</td>
                    <td>{{ $quotation->note }}</td>
                    <td>{{ $quotation->footer }}</td>
                    <td>{{ $item->product_name ?: optional($item->product)->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->getRawOriginal('sum_insured') ?: $item->sum_insured }}</td>
                    <td>{{ $medicalCoverageConfiguration }}</td>
                    <td>{{ $item->family_size }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->getRawOriginal('unit_cost') ?: $item->unit_cost }}</td>
                    <td>{{ $item->getRawOriginal('sub_total') ?: $item->sub_total }}</td>
                    <td>{{ $itemTaxNames }}</td>
                    <td>{{ number_format($itemTaxTotal, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ optional($quotation->customer)->name }}</td>
                    <td>{{ $quotation->quotation_number }}</td>
                    <td>{{ $quotationType }}</td>
                    <td>{{ $quotation->invoice_category }}</td>
                    <td>{{ $quotation->title }}</td>
                    <td>{{ $quotation->po_so_number }}</td>
                    <td>{{ $quotation->getRawOriginal('quotation_date') ?: $quotation->quotation_date }}</td>
                    <td>{{ $quotation->getRawOriginal('expired_date') ?: $quotation->expired_date }}</td>
                    <td>{{ $statusLabel }}</td>
                    <td>{{ $quotation->currency }}</td>
                    <td>{{ $quotation->getRawOriginal('exchange_rate') ?: $quotation->exchange_rate }}</td>
                    <td>{{ $discountType }}</td>
                    <td>{{ $quotation->getRawOriginal('discount_value') ?: $quotation->discount_value }}</td>
                    <td>{{ $quotation->getRawOriginal('discount') ?: $quotation->discount }}</td>
                    <td>{{ number_format($quotationTaxTotal, 2, '.', '') }}</td>
                    <td>{{ $quotation->getRawOriginal('sub_total') ?: $quotation->sub_total }}</td>
                    <td>{{ $quotation->getRawOriginal('grand_total') ?: $quotation->grand_total }}</td>
                    <td>{{ $quotation->note }}</td>
                    <td>{{ $quotation->footer }}</td>
                    <td></td>
                    <td></td>
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
