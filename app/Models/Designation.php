<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'designations';

    public function department(){
        return $this->belongsTo(Department::class, 'department_id')->withDefault();
    }
}