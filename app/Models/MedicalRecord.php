<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'medical_records';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
