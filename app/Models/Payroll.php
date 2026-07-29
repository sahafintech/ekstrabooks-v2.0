<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use MultiTenant, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payslips';
    
    protected $fillable = [
        'employee_id', 'month', 'year', 'current_salary', 'net_salary',
        'tax_amount', 'taxes', 'tax_calculation_method_id', 'total_allowance',
        'total_deduction', 'absence_fine', 'advance', 'advance_description',
        'status', 'transaction_id', 'checker_status', 'checked_by'
    ];

    public function staff() {
        return $this->belongsTo(Employee::class, 'employee_id')->withDefault();
    }

    public function payroll_benefits() {
        return $this->hasMany(PayrollBenefit::class, 'payslip_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id')->withDefault();
    }
    
    public function taxCalculationMethod() {
        return $this->belongsTo(TaxCalculationMethod::class);
    }

    /**
     * Get the approvals for this payroll
     */
    public function approvals()
    {
        return $this->hasMany(Approvals::class, 'ref_id')
            ->where('ref_name', 'payroll')
            ->where('checker_type', 'approval');
    }

    /**
     * Get the verification records for this payroll.
     */
    public function checkers()
    {
        return $this->hasMany(Approvals::class, 'ref_id')
            ->where('ref_name', 'payroll')
            ->where('checker_type', 'checker');
    }

    public function checkedByUser()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    protected function currentSalary(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function expense(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function advance(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function absenceFine(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
