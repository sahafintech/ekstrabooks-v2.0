<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoldPosInvoiceItemTax extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'hold_pos_invoice_item_taxes';

    protected $fillable = [
        'hold_pos_invoice_id',
        'hold_pos_invoice_item_id',
        'tax_id',
        'name',
        'amount',
    ];
}
