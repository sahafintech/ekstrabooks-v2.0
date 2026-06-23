<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceCategory extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = ['name', 'slug'];

    public function templateSections()
    {
        return $this->hasMany(InsuranceCategorySection::class)
            ->where('purpose', 'certificate')
            ->orderBy('sort_order');
    }

    public function quotationSections()
    {
        return $this->hasMany(InsuranceCategorySection::class)
            ->where('purpose', 'quotation')
            ->orderBy('sort_order');
    }

    public function ratingRules()
    {
        return $this->hasMany(RatingRule::class);
    }
}
