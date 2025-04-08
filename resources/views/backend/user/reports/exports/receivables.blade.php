<table>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>Receivables Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $date1->format(get_date_format()) }} - {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;" colspan="4">{{ _lang('TOTAL OF RECEIVABLES') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $receivables->sum('grand_total') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $receivables->sum('paid') }}</td>
        <td style="background-color: lightgray; font-size: 12px;" colspan="3">{{ $receivables->sum('grand_total') - $receivables->sum('paid') }}</td>
    </tr>
    <tr>
        <td>{{ _lang('Invoice Date') }}</td>
        <td>{{ _lang('Customer(provider)') }}</td>
        <td>{{ _lang('Client') }}</td>
        <td>{{ _lang('Invoice Number') }}</td>
        <td>{{ _lang('Invoice Amount') }}</td>
        <td>{{ _lang('Paid Amount') }}</td>
        <td>{{ _lang('Due Amount') }}</td>
        <td>{{ _lang('Due Date') }}</td>
        <td>{{ _lang('Status') }}</td>
    </tr>
    <tbody>
        @foreach($receivables as $invoice)
        <tr>
            <td>{{ $invoice->invoice_date }}</td>
            <td>{{ $invoice->customer->name }}</td>
            <td>{{ $invoice->client?->name }} - {{ $invoice->client?->contract_no }}</td>
            <td>{{ $invoice->invoice_number }}</td>
            <td>{{ $invoice->grand_total }}</td>
            <td>{{ $invoice->paid }}</td>
            <td>{{ $invoice->grand_total - $invoice->paid }}</td>
            <td>{{ $invoice->due_date }}</td>
            <td>
                {!! xss_clean(invoice_status($invoice)) !!}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>