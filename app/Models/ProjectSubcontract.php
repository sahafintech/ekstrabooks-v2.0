<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSubcontract extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'project_subcontracts';

    public function tasks()
    {
        return $this->hasMany(ProjectSubcontractTask::class)->withoutGlobalScopes();
    }

    public function taxes()
    {
        return $this->hasMany(ProjectSubcontractTax::class)->withoutGlobalScopes();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->withoutGlobalScopes();
    }

    public function project() {
        return $this->belongsTo(Project::class)->withoutGlobalScopes();
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

    public function business() {
        return $this->belongsTo(Business::class)->withoutGlobalScopes();
    }

    public function payments() {
        return $this->hasMany(ProjectSubcontractPayment::class);
    }
}
