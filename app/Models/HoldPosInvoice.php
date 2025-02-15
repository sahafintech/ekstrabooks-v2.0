<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoldPosInvoice extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'hold_pos_invoices';

    public function items()
    {
        return $this->hasMany(HoldPosInvoiceItem::class, 'hold_pos_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function taxes()
    {
        return $this->hasMany(HoldPosInvoiceItemTax::class, "hold_pos_invoice_id")
            ->withoutGlobalScopes()
            ->selectRaw('hold_pos_invoice_item_taxes.*, sum(amount) as amount')
            ->groupBy('tax_id');
    }
}
