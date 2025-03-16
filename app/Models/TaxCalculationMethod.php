<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenant;

class TaxCalculationMethod extends Model
{
    use HasFactory, MultiTenant;
    
    protected $fillable = ['name', 'method_type', 'description', 'business_id'];
    
    public function brackets()
    {
        return $this->hasMany(TaxBracket::class);
    }
    
    /**
     * Calculate tax amount based on the method
     * 
     * @param float $income The income amount to calculate tax on
     * @return float The calculated tax amount
     */
    public function calculateTax($income)
    {
        if ($this->method_type === 'fixed') {
            // Fixed tax rate calculations
            $brackets = $this->brackets;
            
            if ($brackets->count() === 0) {
                return 0;
            }
            
            // Find the applicable bracket
            $bracket = $brackets->filter(function ($bracket) use ($income) {
                return $income >= $bracket->income_from && 
                       ($bracket->income_to === null || $income <= $bracket->income_to);
            })->first();
            
            if (!$bracket) {
                return 0;
            }
            
            return ($income * $bracket->rate / 100) + $bracket->fixed_amount;
        } else {
            // Progressive tax calculation
            $brackets = $this->brackets->sortBy('income_from');
            $taxAmount = 0;
            
            foreach ($brackets as $bracket) {
                $lowerLimit = $bracket->income_from;
                $upperLimit = $bracket->income_to === null ? PHP_FLOAT_MAX : $bracket->income_to;
                
                if ($income < $lowerLimit) {
                    continue;
                }
                
                $taxableAmount = min($income, $upperLimit) - $lowerLimit;
                if ($taxableAmount > 0) {
                    $taxAmount += ($taxableAmount * $bracket->rate / 100) + $bracket->fixed_amount;
                }
                
                if ($income <= $upperLimit) {
                    break;
                }
            }
            
            return $taxAmount;
        }
    }
}
