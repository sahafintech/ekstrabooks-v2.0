<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id', 'product_id', 'product_name', 'description', 'quantity', 'unit_cost', 'sub_total', 'user_id', 'business_id',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id')->withDefault();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->withDefault();
    }

    public function taxes()
    {
        return $this->hasMany(ReceiptItemTax::class, 'receipt_item_id');
    }

    protected function unitCost(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function subTotal(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
