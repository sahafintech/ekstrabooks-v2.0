<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'attendance_logs';

    public function staff()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
