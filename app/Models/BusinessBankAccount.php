<?php

namespace App\Models;

use Carbon\Traits\Mutability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBankAccount extends Model
{
    use HasFactory;
    use Mutability;

    protected $table = 'business_bank_accounts';
}
