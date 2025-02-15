<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItemTax extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'receipt_item_taxes';

    protected $fillable = [
        'receipt_id',
        'receipt_item_id',
        'tax_id',
        'name',
        'amount',
    ];

    protected function amount(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
