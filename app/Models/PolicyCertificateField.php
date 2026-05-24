<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyCertificateField extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = [
        'policy_certificate_id',
        'section',
        'label',
        'value',
        'sort_order',
    ];
}
