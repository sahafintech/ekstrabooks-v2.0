<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeOrder extends Model
{
    use SoftDeletes, MultiTenant;
    
    protected $table = 'change_orders';

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function project_task()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function cost_code()
    {
        return $this->belongsTo(CostCode::class);
    }

    protected function changeOrderDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
