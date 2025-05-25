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
            <b>Payables Report</b>
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
        <td style="background-color: lightgray; font-size: 12px;" colspan="4">{{ _lang('TOTAL OF PAYABLES') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $grand_total }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $paid_amount }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $due_amount }}</td>
    </tr>
    <tr>
        <td>{{ _lang('Purchase Date') }}</td>
        <td>{{ _lang('Vendor') }}</td>
        <td>{{ _lang('Purchase Number') }}</td>
        <td>{{ _lang('Purchase Amount') }}</td>
        <td>{{ _lang('Paid Amount') }}</td>
        <td>{{ _lang('Due Amount') }}</td>
        <td>{{ _lang('Due Date') }}</td>
        <td>{{ _lang('Status') }}</td>
    </tr>
    <tbody>
        @foreach($payables as $bill)
        <tr>
            <td>{{ $bill['purchase_date'] }}</td>
            <td>{{ $bill['vendor_name'] }}</td>
            <td>{{ $bill['purchase_number'] }}</td>
            <td>{{ $bill['grand_total'] }}</td>
            <td>{{ $bill['paid_amount'] }}</td>
            <td>{{ $bill['due_amount'] }}</td>
            <td>{{ $bill['due_date'] }}</td>
            <td>{{ $bill['status'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>