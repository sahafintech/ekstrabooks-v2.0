<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class TransactionMethod extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_methods';

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
}