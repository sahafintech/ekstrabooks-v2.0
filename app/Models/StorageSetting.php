<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSetting extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'disk',
        'visibility',
        'directory',
        'signed_url_ttl',
    ];

    protected $casts = [
        'business_id' => 'integer',
        'user_id' => 'integer',
        'signed_url_ttl' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereNull('business_id')->where('user_id', $userId);
    }
}
