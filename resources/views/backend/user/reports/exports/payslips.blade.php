<table>
<tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>Generated Payroll</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ date('F', mktime(0, 0, 0, $month, 10)) . ' / ' . $year }}</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">id</td>
        <td style="background-color: lightgray; font-size: 12px">Name</td>
        <td style="background-color: lightgray; font-size: 12px">Start Date</td>
        <td style="background-color: lightgray; font-size: 12px">End Date</td>
        <td style="background-color: lightgray; font-size: 12px">Department</td>
        <td style="background-color: lightgray; font-size: 12px">Job Title</td>
        <td style="background-color: lightgray; font-size: 12px">Bank Name</td>
        <td style="background-color: lightgray; font-size: 12px">Account Number</td>
        <td style="background-color: lightgray; font-size: 12px">Base Salary</td>
        <td style="background-color: lightgray; font-size: 12px">Total Allowance</td>
        <td style="background-color: lightgray; font-size: 12px">Other Deductions</td>
        @if($payslips->first()?->taxes != null)
        @foreach(\App\Models\Tax::whereIn('id', json_decode($payslips->first()->taxes, true) ?? [])->get() as $tax)
        <td style="background-color: lightgray; font-size: 12px">{{ $tax->name }}</td>
        @endforeach
        @endif
        <td style="background-color: lightgray; font-size: 12px">Advance</td>
        <td style="background-color: lightgray; font-size: 12px">Net Salary</td>
    </tr>
    <tbody>
        @foreach($payslips as $payslip)
        <tr>
            <td>{{ $payslip->employee->employee_id }}</td>
            <td>{{ $payslip->employee->name }}</td>
            <td>{{ $payslip->employee->joining_date }}</td>
            <td>{{ $payslip->employee->end_date }}</td>
            <td>{{ $payslip->employee->department->name }}</td>
            <td>{{ $payslip->employee->designation->name }}</td>
            <td>{{ $payslip->employee->bank_name }}</td>
            <td>{{ $payslip->employee->account_number }}</td>
            <td>{{ $payslip->current_salary }}</td>
            <td>{{ $payslip->total_allowance }}</td>
            <td>{{ $payslip->total_deduction - $payslip->advance }}</td>
            @if($payslips->first()?->taxes != null)
            @foreach(\App\Models\Tax::whereIn('id', json_decode($payslips->first()->taxes, true) ?? [])->get() as $tax)
            <td>{{ ($payslip->current_salary * $tax->rate ) / 100 }}</td>
            @endforeach
            @endif
            <td>{{ $payslip->advance }}</td>
            <td>{{ $payslip->net_salary }}</td>
        </tr>
        @endforeach
    </tbody>
</table>