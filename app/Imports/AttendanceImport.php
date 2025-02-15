<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $date = Date::excelToDateTimeObject($row['date'])->format('Y-m-d');

            $attendance = new Attendance();
            $attendance->date = $date;
            $attendance->employee_id = Employee::where('name', 'like', '%' . $row['name'] . '%')->first()->id;
            if($row['status'] == 'absent'){
                $attendance->status = 0;
            }elseif($row['status'] == 'present'){
                $attendance->status = 1;
            }elseif($row['status'] == 'leave'){
                $attendance->status = 2;
            }
            $attendance->leave_type = $row['leave_type'];
            $attendance->leave_duration = $row['leave_duration'];
            $attendance->remarks = $row['remarks'];
            $attendance->save();
        }
    }

    public function rules(): array
    {
        return [
            'date'                  => 'required',
            'name'                  => 'required',
            'status'                => 'required|in:absent,present,leave',
            'leave_type'            => 'nullable:exists:leave_types,title',
            'leave_duration'        => 'nullable',
            'remarks'               => 'nullable',
        ];
    }
}