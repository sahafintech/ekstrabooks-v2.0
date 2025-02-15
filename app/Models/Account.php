<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Account extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'accounts';

    protected function openingDate(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn ($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function uncategorized_transactions()
    {
        return $this->hasMany(UncategorizedTransaction::class, 'account_id');
    }
}
