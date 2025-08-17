<?php

namespace App\Models;

use App\Models\PurchaseReturn;
use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use HasFactory, MultiTenant, SoftDeletes;

    protected $table = 'purchase_return_items';

    protected $fillable = [
        'purchase_return_id', 'product_id', 'product_name', 'description', 'quantity', 'unit_cost', 'sub_total', 'account_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id')->withDefault();
    }

    public function taxes() {
        return $this->hasMany(PurchaseReturnItemTax::class, 'purchase_return_item_id');
    }

    public function purchase_return() {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    protected function unitCost(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function subTotal(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
