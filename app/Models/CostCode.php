<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCode extends Model
{
    use HasFactory, SoftDeletes, MultiTenant;

    protected $table = 'cost_codes';
}
