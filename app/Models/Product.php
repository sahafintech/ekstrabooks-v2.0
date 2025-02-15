<?php

namespace App\Models;

use App\Models\ProductUnit;
use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    protected $fillable = ['name', 'type', 'product_unit_id', 'purchase_cost', 'selling_price', 'image', 'descriptions', 'stock_management', 'stock', 'allow_for_selling', 'allow_for_purchasing', 'status', 'income_account_id', 'category_id', 'brand_id', 'expense_account_id', 'code', 'expiry_date', 'user_id', 'business_id', 'created_user_id', 'updated_user_id'];

    public function scopeActive($query) {
        return $query->where('status', 1);
    }

    public function product_unit() {
        return $this->belongsTo(ProductUnit::class)->withDefault();
    }

    public function income_account() {
        return $this->belongsTo(Account::class, 'income_account_id', 'id')->withDefault();
    }

    public function expense_account() {
        return $this->belongsTo(Account::class, 'expense_account_id', 'id')->withDefault();
    }

    protected function sellingPrice(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function purchaseCost(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    public function category() {
        return $this->belongsTo(Category::class)->withDefault();
    }

    public function brand() {
        return $this->belongsTo(Brands::class)->withDefault();
    }
}