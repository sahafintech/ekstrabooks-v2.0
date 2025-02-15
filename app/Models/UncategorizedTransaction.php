<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UncategorizedTransaction extends Model
{
    use MultiTenant;

    protected $table = 'uncategorized_transactions';

    protected $fillable = ['trans_date', 'account_id', 'deposit', 'withdrawal', 'reference', 'description', 'user_id', 'business_id'];

    use HasFactory;

    protected function createdAt(): Attribute
    {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get: fn (string $value) => \Carbon\Carbon::parse($value)->format("$date_format $time_format"),
        );
    }

    protected function updatedAt(): Attribute
    {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get: fn (string $value) => \Carbon\Carbon::parse($value)->format("$date_format $time_format"),
        );
    }

    protected function transDate(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn (string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}
