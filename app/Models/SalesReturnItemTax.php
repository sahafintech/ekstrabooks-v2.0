<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItemTax extends Model
{
    use HasFactory;

    use MultiTenant;

    public $table = 'sales_return_item_taxes';

    protected $fillable = [
        'sales_return_id', 'tax_id', 'name', 'amount',
    ];

    protected function amount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
