<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferComment extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'comment',
        'type',
        'created_user_id',
    ];

    // Relationships
    public function inventoryTransfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class);
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    // Scopes
    public function scopeRejection($query)
    {
        return $query->where('type', 'rejection');
    }

    public function scopeGeneral($query)
    {
        return $query->where('type', 'general');
    }

    // Helper methods
    public function isRejection(): bool
    {
        return $this->type === 'rejection';
    }

    public function isGeneral(): bool
    {
        return $this->type === 'general';
    }
}
