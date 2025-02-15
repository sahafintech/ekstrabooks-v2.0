<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'audit_logs';

    public function changed_user() {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function dateChanged(): Attribute {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format $time_format") : null,
        );
    }
}
