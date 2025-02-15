<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PayrollBenefit extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payslip_benefits';

    protected $fillable = ['payslip_id', 'name', 'amount', 'type'];

    protected function amount(): Attribute{
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

}