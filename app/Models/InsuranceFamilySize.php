<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceFamilySize extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'insurance_family_sizes';
}
