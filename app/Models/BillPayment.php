<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'bill_payments';

    public function vendor() {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchases() {
        return $this->belongsToMany(Purchase::class, 'purchase_payments', 'payment_id', 'purchase_id')->withPivot('amount');
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
