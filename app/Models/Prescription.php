<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'prescriptions';

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function medical_record()
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_records_id');
    }

    public function items() {
        return $this->hasManyThrough(PrescriptionProductItem::class, PrescriptionProduct::class, 'prescription_id', 'prescription_products_id');
    }

    protected function date(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn ($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function resultDate(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn ($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
