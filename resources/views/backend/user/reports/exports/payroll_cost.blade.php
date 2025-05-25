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
            <b>Payroll Cost Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $report_data['month'] }} {{ $report_data['year'] }}</b>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;" colspan="3">{{ _lang('TOTAL OF PAYROLL COST') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_netsalary'] }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_basicsalary'] }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_allowance'] }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_deduction'] }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_tax'] }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ $report_data['total_advance'] }}</td>
    </tr>
    <tr>
        <td>{{ _lang('NO') }}</td>
        <td>{{ _lang('Employee ID') }}</td>
        <td>{{ _lang('Employee Name') }}</td>
        <td>{{ _lang('Basic Salary') }}</td>
        <td>{{ _lang('Allowance') }}</td>
        <td>{{ _lang('Deduction') }}</td>
        <td>{{ _lang('Total Tax') }}</td>
        <td>{{ _lang('Net Salary') }}</td>
    </tr>
    <tbody>
        @foreach($report_data['payroll'] as $index => $payroll)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $payroll->staff->employee_id }}</td>
            <td>{{ $payroll->staff->name }}</td>
            <td>{{ $payroll->current_salary }}</td>
            <td>{{ $payroll->total_allowance }}</td>
            <td>{{ $payroll->total_deduction }}</td>
            <td>{{ $payroll->tax_amount }}</td>
            <td>{{ $payroll->net_salary }}</td>
        </tr>
        @endforeach
    </tbody>
</table>