<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Leave extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leaves';

    public function staff() {
        return $this->belongsTo(Employee::class, 'employee_id')->withDefault();
    }

    protected function startDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    protected function endDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}