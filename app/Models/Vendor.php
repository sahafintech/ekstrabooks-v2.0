<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vendors';


    public function purchases(){
        return $this->hasMany(Purchase::class, 'vendor_id');
    }
}