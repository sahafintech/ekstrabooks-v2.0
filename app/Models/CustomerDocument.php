<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDocument extends Model
{
    use MultiTenant, HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_documents';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'name',
        'document',
        'business_id',
    ];
    
    /**
     * Get the customer that owns the document.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault();
    }
}
