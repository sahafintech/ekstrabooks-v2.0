<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'projects';

    public function project_group()
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    protected function startDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function endDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
    
}
