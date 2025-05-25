<table>
    <tr>
        <td>{{ _lang('Pay Component') }}</td>
        <td>{{ _lang('Amount') }}</td>
    </tr>
    <tbody>
        <tr>
            <td>Current Salary</td>
            <td>{{ $report_data['current_salary'] }}</td>
        </tr>
        <tr>
            <td>Allowance</td>
            <td>{{ $report_data['total_allowance'] }}</td>
        </tr>
        <tr>
            <td>Deduction</td>
            <td>{{ $report_data['total_deduction'] }}</td>
        </tr>
        <tr>
            <td>Net Salary</td>
            <td>{{ $report_data['net_salary'] }}</td>
        </tr>
        <tr>
            <td>Total Tax</td>
            <td>{{ $report_data['total_tax'] }}</td>
        </tr>
    </tbody>
</table>