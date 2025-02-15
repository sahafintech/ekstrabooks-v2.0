<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_types';

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
}