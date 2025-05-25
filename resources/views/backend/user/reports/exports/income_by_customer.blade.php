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
            <b>Income By Customer Report</b>
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
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('TOTAL OF INCOME') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $grand_total_income }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $grand_total_paid }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $grand_total_income - $grand_total_paid }}</td>
    </tr>
    <tr>
        <td>{{ _lang('Customer(provider)') }}</td>
        <td>{{ _lang('Total Income') }}</td>
        <td>{{ _lang('Total Paid') }}</td>
        <td>{{ _lang('Total Due') }}</td>
    </tr>
    <tbody>
        @foreach($report_data as $data)
        <tr>
            <td>{{ $data['customer_name'] }}</td>
            <td>{{ $data['total_income'] }}</td>
            <td>{{ $data['total_paid'] }}</td>
            <td>{{ $data['total_due'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>