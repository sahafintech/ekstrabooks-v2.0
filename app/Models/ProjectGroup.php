<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectGroup extends Model
{
    use MultiTenant, SoftDeletes;

    protected $table = 'project_groups';
}
