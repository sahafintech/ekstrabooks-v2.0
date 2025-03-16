<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenant;

class TaxBracket extends Model
{
    use HasFactory, MultiTenant;
    
    protected $fillable = [
        'tax_calculation_method_id', 
        'income_from', 
        'income_to',
        'rate',
        'fixed_amount'
    ];
    
    public function taxCalculationMethod()
    {
        return $this->belongsTo(TaxCalculationMethod::class);
    }
}
