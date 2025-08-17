<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItemTax extends Model
{
    use HasFactory, MultiTenant, SoftDeletes;

    protected $table = 'purchase_return_item_taxes';

    protected $fillable = [
        'purchase_return_id', 'purchase_return_item_id', 'tax_id', 'name', 'amount', 'user_id', 'business_id',
    ];

    protected function amount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
