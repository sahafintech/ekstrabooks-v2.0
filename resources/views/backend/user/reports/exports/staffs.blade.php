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
            <b>Staff List</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('employee_id') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('date_of_birth') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('email') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('phone') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('city') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('country') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('department') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('designation') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('base_salary') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('joining_date') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('end_date') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('bank_name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('branch_name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('account_name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('account_number') }}</td>
    </tr>
    <tbody>
        @foreach($staffs as $staff)
        <tr>
            <td>{{ $staff->employee_id }}</td>
            <td>{{ $staff->name }}</td>
            <td>{{ $staff->date_of_birth }}</td>
            <td>{{ $staff->email }}</td>
            <td>{{ $staff->phone }}</td>
            <td>{{ $staff->city }}</td>
            <td>{{ $staff->country }}</td>
            <td>{{ \App\Models\Department::find($staff->department_id)->name }}</td>
            <td>{{ \App\Models\Designation::find($staff->designation_id)->name }}</td>
            <td>{{ $staff->base_salary }}</td>
            <td>{{ $staff->joining_date }}</td>
            <td>{{ $staff->end_date }}</td>
            <td>{{ $staff->bank_name }}</td>
            <td>{{ $staff->branch_name }}</td>
            <td>{{ $staff->account_name }}</td>
            <td>{{ $staff->account_number }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
