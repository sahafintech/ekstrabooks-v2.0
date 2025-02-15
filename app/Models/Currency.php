<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency';

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    protected function exchangeRate(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
