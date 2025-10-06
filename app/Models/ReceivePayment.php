<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivePayment extends Model
{
    use HasFactory, MultiTenant, SoftDeletes;

    protected $table = 'receive_payments';

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoices() {
        return $this->belongsToMany(Invoice::class, 'invoice_payments', 'payment_id', 'invoice_id')->withPivot('amount');
    }

    public function defferedReceivePayment() {
        return $this->belongsToMany(DefferedPayment::class, 'deffered_receive_payments', 'payment_id', 'deffered_payment_id')->withPivot('amount');
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function account() {
        return $this->belongsTo(Account::class, 'account_id');
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}
