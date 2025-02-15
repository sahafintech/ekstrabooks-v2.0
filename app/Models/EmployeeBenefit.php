<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBenefit extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'employee_benefits';

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'advance',
    ];

    public function salary_benefits() {
        return $this->hasMany(SalaryBenefit::class, 'employee_benefit_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
