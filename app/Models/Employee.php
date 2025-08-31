<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model {
	use MultiTenant, SoftDeletes;
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'employees';

	public static function scopeActive($query) {
		$date = date('Y-m-d');
		return $query->whereRaw("employees.end_date is NULL OR employees.end_date > $date");
	}

	public function department() {
		return $this->belongsTo(Department::class, 'department_id')->withDefault();
	}

	public function designation() {
		return $this->belongsTo(Designation::class, 'designation_id')->withDefault();
	}

	public function payslips() {
		return $this->hasMany(Payroll::class, 'employee_id');
	}

	public function attendance() {
		return $this->hasMany(Attendance::class, 'employee_id');
	}

	public function employee_benefits() {
		return $this->hasMany(EmployeeBenefit::class, 'employee_id');
	}

	public function documents() {
		return $this->hasMany(EmployeeDocument::class, 'employee_id');
	}

	public function department_history() {
		return $this->hasMany(EmployeeDepartmentHistory::class, 'employee_id');
	}

	protected function dateOfBirth(): Attribute {
		$date_format = get_date_format();
		return Attribute::make(
			get: fn($value) => \Carbon\Carbon::parse($value)->format("$date_format"),
		);
	}

	protected function joiningDate(): Attribute {
		$date_format = get_date_format();
		return Attribute::make(
			get: fn($value) => \Carbon\Carbon::parse($value)->format("$date_format"),
		);
	}

	protected function basicSalary(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

	protected function endDate(): Attribute {
		$date_format = get_date_format();
		return Attribute::make(
			get: fn($value) => $value != null?\Carbon\Carbon::parse($value)->format("$date_format") : '',
		);
	}

}