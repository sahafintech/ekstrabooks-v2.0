<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyCertificate extends Model
{
    use MultiTenant, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'insurance_category_id',
        'certificate_number',
        'policy_number',
        'policy_start_date',
        'policy_end_date',
        'short_code',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function insuranceCategory()
    {
        return $this->belongsTo(InsuranceCategory::class);
    }

    public function sections()
    {
        return $this->hasMany(PolicyCertificateSection::class)->orderBy('sort_order');
    }

    public function fields()
    {
        return $this->hasMany(PolicyCertificateField::class)->orderBy('sort_order');
    }

    protected function policyStartDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function policyEndDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }
}
