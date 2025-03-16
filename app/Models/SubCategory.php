<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'sub_categories';

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }
}
