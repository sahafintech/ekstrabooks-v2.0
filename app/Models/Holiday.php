<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Holiday extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'holidays';

    protected function date(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}