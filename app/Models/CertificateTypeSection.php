<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTypeSection extends Model
{
    protected $fillable = [
        'certificate_type_id',
        'title',
        'type',
        'sort_order',
        'fields_json',
        'columns_json',
        'rows_json',
        'content',
    ];

    protected $casts = [
        'fields_json'  => 'array',
        'columns_json' => 'array',
        'rows_json'    => 'array',
    ];

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }
}
