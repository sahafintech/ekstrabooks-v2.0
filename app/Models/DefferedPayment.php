<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefferedPayment extends Model
{
    use HasFactory, MultiTenant, SoftDeletes;

    protected $table = 'deffered_payments';

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    protected function dueDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    protected function amount(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }
}
