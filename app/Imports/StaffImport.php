<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\SalaryScale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StaffImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $date_of_birth = Date::excelToDateTimeObject($row['date_of_birth'])->format('Y-m-d');
            if ($row['joining_date'] == null) {
                $joining_date = null;
            } else {
                $joining_date = Date::excelToDateTimeObject($row['joining_date'])->format('Y-m-d');
            }
            if ($row['end_date'] != null) {
                $end_date      = Date::excelToDateTimeObject($row['end_date'])->format('Y-m-d');
            } else {
                $end_date = null;
            }


            $staff                              = new Employee();
            $staff->employee_id                 = $row['employee_id'];
            $staff->name                        = $row['name'];
            $staff->date_of_birth               = $date_of_birth;
            $staff->email                       = $row['email'];
            $staff->phone                       = $row['phone'];
            $staff->city                        = $row['city'];
            $staff->country                     = $row['country'];
            $staff->department_id               = Department::where('name', $row['department'])->first()->id;
            $staff->designation_id              = Designation::where('name', $row['designation'])->first()->id;
            $staff->basic_salary                = $row['base_salary'];
            $staff->working_hours               = $row['working_hours'];
            $staff->time_sheet_based            = $row['time_sheet_based'];
            $staff->max_overtime_hours          = $row['max_overtime_hours'];
            $staff->joining_date                = $joining_date;
            $staff->end_date                    = $end_date;
            $staff->bank_name                   = $row['bank_name'];
            $staff->branch_name                 = $row['branch_name'];
            $staff->account_name                = $row['account_name'];
            $staff->account_number              = $row['account_number'];
            $staff->save();
        }
    }

    public function rules(): array
    {
        return [
            'employee_id'   => 'required',
            'name'          => 'required',
            'date_of_birth' => 'nullable',
            'email'         => 'nullable|email',
            'phone'         => 'nullable',
            'city'          => 'required',
            'country'       => 'required',
            'department'    => 'required|exists:departments,name',
            'designation'   => 'required|exists:designations,name',
            'base_salary'   => 'required',
            'working_hours' => 'required',
            'time_sheet_based' => 'required|in:0,1',
            'max_overtime_hours' => 'nullable',
            'joining_date'  => 'required',
            'end_date'      => 'nullable',
            'bank_name'     => 'nullable',
            'branch_name'   => 'nullable',
            'account_name'  => 'nullable',
            'account_number' => 'nullable',
        ];
    }
}
