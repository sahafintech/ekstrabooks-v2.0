<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class RatingRule extends Model
{
    use MultiTenant;

    protected $table = 'rating_rules';

    protected $fillable = [
        'insurance_category_id',
        'product_id',
        'name',
        'calculation_type',
        'rate_type',
        'min_rate',
        'max_rate',
        'default_rate',
        'minimum_premium',
        'tax_rate',
        'currency',
        'active_from',
        'active_to',
        'is_active',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'is_active'     => 'boolean',
        'active_from'   => 'date',
        'active_to'     => 'date',
    ];

    public function insuranceCategory()
    {
        return $this->belongsTo(InsuranceCategory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withDefault();
    }
}
