<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class ProjectSubcontractTax extends Model
{
    use MultiTenant;

    protected $fillable = [
        'project_subcontract_id',
        'tax_id',
        'rate',
        'amount',
    ];
}
