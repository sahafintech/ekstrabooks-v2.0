<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefferedReceivePayment extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'deffered_receive_payments';
}
