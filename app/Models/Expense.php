<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use MultiTenant;

    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'date',
        'payment_account',
        'expense_account',
        'method',
        'description',
        'amount',
        'user_id',
        'business_id',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function expense_account_relation()
    {
        return $this->belongsTo(Account::class, 'expense_account');
    }

    public function payment_account_relation()
    {
        return $this->belongsTo(Account::class, 'payment_account');
    }

    protected function expenseDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}
