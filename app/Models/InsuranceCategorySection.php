<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCategorySection extends Model
{
    protected $fillable = [
        'insurance_category_id',
        'title',
        'type',
        'sort_order',
        'fields_json',
        'columns_json',
        'rows_json',
        'content',
    ];

    protected $casts = [
        'fields_json'  => 'array',
        'columns_json' => 'array',
        'rows_json'    => 'array',
    ];

    public function insuranceCategory()
    {
        return $this->belongsTo(InsuranceCategory::class);
    }
}
