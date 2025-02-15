<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class EmployeeDepartmentHistory extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employee_department_histories';

    public function staff(){
        return $this->belongsTo(Employee::class, 'employee_id')->withDefault();
    }

    protected function details(): Attribute {
        return Attribute::make(
            get:fn($value) => json_decode($value),
        );
    }

}