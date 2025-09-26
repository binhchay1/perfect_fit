<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_id',
        'type',
        'status',
        'amount',
        'currency',
        'gateway_name',
        'gateway_transaction_id',
        'reference_id',
        'description',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment that owns the transaction
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(string $gatewayTransactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_transaction_id' => $gatewayTransactionId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'description' => $reason,
        ]);
    }

    /**
     * Mark transaction as cancelled
     */
    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'description' => $reason,
        ]);
    }

    /**
     * Get total amount for an order
     */
    public static function getTotalForOrder(int $orderId): float
    {
        return static::where('order_id', $orderId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get total refunds for an order
     */
    public static function getTotalRefundsForOrder(int $orderId): float
    {
        return static::where('order_id', $orderId)
            ->where('type', 'refund')
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get total adjustments for an order
     */
    public static function getTotalAdjustmentsForOrder(int $orderId): float
    {
        return static::where('order_id', $orderId)
            ->where('type', 'adjustment')
            ->where('status', 'completed')
            ->sum('amount');
    }
}
