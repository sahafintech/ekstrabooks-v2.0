<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationSection extends Model
{
    protected $table = 'quotation_sections';

    protected $fillable = [
        'quotation_id',
        'insurance_category_id',
        'insurance_category_section_id',
        'title',
        'type',
        'sort_order',
        'data_json',
        'content',
    ];

    protected $casts = [
        'data_json' => 'array',
    ];

    public function insuranceCategory()
    {
        return $this->belongsTo(InsuranceCategory::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function templateSection()
    {
        return $this->belongsTo(InsuranceCategorySection::class, 'insurance_category_section_id')->withDefault();
    }
}
