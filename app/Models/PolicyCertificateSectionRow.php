<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyCertificateSectionRow extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = [
        'policy_certificate_section_id',
        'data',
        'sort_order',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
