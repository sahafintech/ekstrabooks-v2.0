<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class QuotationItemTax extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quotation_item_taxes';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quotation_id', 'tax_id', 'name', 'amount',
    ];

    protected function amount(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

}