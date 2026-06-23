<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationItem extends Model
{
    use MultiTenant, SoftDeletes;

    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
        'insurance_category_id',
        'rating_rule_id',
        'product_id',
        'product_name',
        'description',
        'calculation_type',
        'rate_type',
        'rate_value',
        'basis_amount',
        'basis_quantity',
        'quantity',
        'unit_cost',
        'sub_total',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function ratingRule()
    {
        return $this->belongsTo(RatingRule::class)->withDefault();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->withDefault();
    }

    public function insuranceCategory()
    {
        return $this->belongsTo(InsuranceCategory::class)->withDefault();
    }

    public function taxes()
    {
        return $this->hasMany(QuotationItemTax::class, 'quotation_item_id');
    }

    protected function unitCost(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);
        return Attribute::make(
            get: fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function subTotal(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);
        return Attribute::make(
            get: fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
