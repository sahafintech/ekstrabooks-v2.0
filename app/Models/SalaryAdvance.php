<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryAdvance extends Model
{
    use MultiTenant, SoftDeletes;

    protected $table = 'salary_advances';

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    protected function date(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
