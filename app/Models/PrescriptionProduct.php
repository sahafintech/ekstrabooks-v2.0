<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionProduct extends Model
{
    use HasFactory;
    use MultiTenant;

    protected $table = 'prescription_products';

    public function items()
    {
        return $this->hasMany(PrescriptionProductItem::class, 'prescription_products_id');
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Prescription::class, 'id', 'id', 'prescription_id', 'customer_id');
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
