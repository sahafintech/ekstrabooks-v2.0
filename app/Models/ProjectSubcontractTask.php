<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSubcontractTask extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'project_subcontract_tasks';

    protected $fillable = [
        'project_subcontract_id',
        'project_task_id',
        'cost_code_id',
        'uom',
        'quantity',
        'unit_cost',
        'sub_total',
        'account_id',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function cost_code()
    {
        return $this->belongsTo(CostCode::class, 'cost_code_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
