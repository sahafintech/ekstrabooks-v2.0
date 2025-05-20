<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectBudget extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'project_budgets';

    public function tasks()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function cost_codes()
    {
        return $this->belongsTo(CostCode::class, 'cost_code_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
