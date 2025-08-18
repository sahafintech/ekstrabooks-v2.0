<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use MultiTenant, Notifiable, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    public function invoices(){
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function quotations(){
        return $this->hasMany(Quotation::class, 'customer_id');
    }
    
    public function documents(){
        return $this->hasMany(CustomerDocument::class, 'customer_id');
    }

}