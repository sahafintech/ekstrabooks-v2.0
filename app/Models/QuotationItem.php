<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationItem extends Model {
    use MultiTenant, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quotation_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quotation_id',
        'product_id',
        'product_name',
        'description',
        'sum_insured',
        'quantity',
        'unit_cost',
        'sub_total',
        'family_size',
        'inpatient_limit_per_family',
        'inpatient_contribution_per_family',
        'inpatient_total_contribution',
        'maternity_limit_per_family',
        'maternity_contribution_per_family',
        'maternity_total_contribution',
        'outpatient_limit_per_family',
        'outpatient_contribution_per_family',
        'outpatient_total_contribution',
        'dental_limit_per_family',
        'dental_contribution_per_family',
        'dental_total_contribution',
        'optical_limit_per_family',
        'optical_contribution_per_family',
        'optical_total_contribution',
        'telemedicine_limit_per_family',
        'telemedicine_contribution_per_family',
        'telemedicine_total_contribution',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id')->withDefault();
    }

    public function taxes() {
        return $this->hasMany(QuotationItemTax::class, 'quotation_item_id');
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

    protected function sumInsured(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn(string|null $value) => $value !== null ? number_format($value, $decimal_place, '.', '') : null,
        );
    }

}
