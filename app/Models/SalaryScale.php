<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SalaryScale extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'salary_scales';


    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['salary_grade'];

    /**
     * Determine  full name of user
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function getSalaryGradeAttribute()
    {
        return _lang('Grade').' '.$this->grade_number;
    }


    public function department(){
        return $this->belongsTo(Department::class, 'department_id')->withDefault();
    }

    public function designation(){
        return $this->belongsTo(Designation::class, 'designation_id')->withDefault();
    }

    public function salary_benefits() {
        return $this->hasMany(SalaryBenefit::class, 'salary_scale_id');
    }

    protected function basicSalary(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function fullDayAbsenceFine(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function halfDayAbsenceFine(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}