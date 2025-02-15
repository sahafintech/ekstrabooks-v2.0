<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_categories';

    public function scopeIncome($query) {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query) {
        return $query->where('type', 'expense');
    }
}