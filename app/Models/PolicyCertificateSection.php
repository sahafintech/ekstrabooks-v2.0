<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyCertificateSection extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = [
        'policy_certificate_id',
        'title',
        'type',
        'sort_order',
    ];

    public function rows()
    {
        return $this->hasMany(PolicyCertificateSectionRow::class, 'policy_certificate_section_id')
            ->orderBy('sort_order');
    }
}
