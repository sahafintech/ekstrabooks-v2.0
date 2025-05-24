<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSubcontractPayment extends Model
{
    use SoftDeletes, MultiTenant;

    protected $table = 'project_subcontract_payments';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function project_subcontract()
    {
        return $this->belongsTo(ProjectSubcontract::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
