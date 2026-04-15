<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferItem extends Model
{
    protected $fillable = [
        'inventory_transfer_id',
        'product_id',
        'requested_quantity',
        'counted_quantity',
        'notes',
        'created_user_id',
        'updated_user_id',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
    ];

    // Relationships
    public function inventoryTransfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    // Helper methods
    public function getQuantityDifference(): float
    {
        if ($this->counted_quantity === null) {
            return 0;
        }
        
        return $this->counted_quantity - $this->requested_quantity;
    }

    public function hasDiscrepancy(): bool
    {
        return $this->counted_quantity !== null && 
               $this->counted_quantity != $this->requested_quantity;
    }

    public function getEffectiveQuantity(): float
    {
        return $this->counted_quantity ?? $this->requested_quantity;
    }
}
