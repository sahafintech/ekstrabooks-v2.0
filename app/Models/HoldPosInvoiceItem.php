<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoldPosInvoiceItem extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'hold_pos_invoice_items';

    protected $fillable = [
        'hold_pos_invoice_id',
        'product_id',
        'product_name',
        'description',
        'quantity',
        'unit_cost',
        'sub_total',
    ];
}
