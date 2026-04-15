<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class InventoryTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'transfer_date',
        'from_entity_id',
        'to_entity_id',
        'status',
        'remarks',
        'sent_at',
        'received_at',
        'rejected_at',
        'cancelled_at',
        'created_user_id',
        'updated_user_id',
        'sent_user_id',
        'received_user_id',
        'rejected_user_id',
        'cancelled_user_id',
        'deleted_user_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->transfer_number)) {
                $model->transfer_number = static::generateTransferNumber();
            }
        });
    }

    // Relationships
    public function fromEntity(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'from_entity_id');
    }

    public function toEntity(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'to_entity_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(InventoryTransferComment::class);
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    public function sentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_user_id');
    }

    public function receivedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_user_id');
    }

    public function rejectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_user_id');
    }

    public function cancelledUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_user_id');
    }

    // Scopes
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->where('from_entity_id', $businessId)
              ->orWhere('to_entity_id', $businessId);
        });
    }

    public function scopeOutgoing($query, $businessId)
    {
        return $query->where('from_entity_id', $businessId);
    }

    public function scopeIncoming($query, $businessId)
    {
        return $query->where('to_entity_id', $businessId);
    }

    // Status checks
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Status transitions
    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    public function canSend(): bool
    {
        return $this->isDraft() && $this->items()->count() > 0;
    }

    public function canReceive(): bool
    {
        return $this->isSent();
    }

    public function canReject(): bool
    {
        return $this->isSent();
    }

    public function canCancel(): bool
    {
        return $this->isDraft() || $this->isSent();
    }

    // Business logic methods
    public function send($userId = null)
    {
        if (!$this->canSend()) {
            throw new \Exception('Transfer cannot be sent in current status');
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_user_id' => $userId ?? auth()->user()->id,
        ]);
    }

    public function receive($userId = null, $countedQuantities = [])
    {
        if (!$this->canReceive()) {
            throw new \Exception('Transfer cannot be received in current status');
        }

        DB::transaction(function () use ($userId, $countedQuantities) {
            // Update item counted quantities
            foreach ($countedQuantities as $itemId => $countedQty) {
                $this->items()->where('id', $itemId)->update([
                    'counted_quantity' => $countedQty
                ]);
            }

            // Transfer products to destination entity
            foreach ($this->items as $item) {
                $quantity = $item->counted_quantity ?? $item->requested_quantity;
                if ($quantity > 0) {
                    $product = $item->product;
                    $product->update([
                        'business_id' => $this->to_entity_id,
                        'stock' => $product->stock // Keep current stock for now
                    ]);
                }
            }

            $this->update([
                'status' => 'received',
                'received_at' => now(),
                'received_user_id' => $userId ?? auth()->user()->id,
            ]);
        });
    }

    public function reject($comment, $userId = null)
    {
        if (!$this->canReject()) {
            throw new \Exception('Transfer cannot be rejected in current status');
        }

        DB::transaction(function () use ($comment, $userId) {
            $this->comments()->create([
                'comment' => $comment,
                'type' => 'rejection',
                'created_user_id' => $userId ?? auth()->user()->id,
            ]);

            $this->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_user_id' => $userId ?? auth()->user()->id,
            ]);
        });
    }

    public function cancel($userId = null)
    {
        if (!$this->canCancel()) {
            throw new \Exception('Transfer cannot be cancelled in current status');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_user_id' => $userId ?? auth()->user()->id,
        ]);
    }

    // Helper methods
    public function getTotalItemsCount(): int
    {
        return $this->items()->count();
    }

    public function getTotalRequestedQuantity(): float
    {
        return $this->items()->sum('requested_quantity');
    }

    public function getTotalCountedQuantity(): float
    {
        return $this->items()->sum('counted_quantity');
    }

    // Generate unique transfer number
    protected static function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $lastTransfer = static::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastTransfer ? (int)substr($lastTransfer->transfer_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
