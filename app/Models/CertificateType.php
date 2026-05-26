<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateType extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = ['name', 'slug'];

    public function templateSections()
    {
        return $this->hasMany(CertificateTypeSection::class)->orderBy('sort_order');
    }
}
