<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use MultiTenant;

    use HasFactory;

    protected $fillable = [
        'date',
        'journal_number',
        'amount',
        'user_id',
        'business_id',
    ];

    public function approved_user() {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function created_user() {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}
