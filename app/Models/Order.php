<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'shipping_address',
        'billing_address',
        'notes',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastOrder ? (int)substr($lastOrder->order_number, -6) + 1 : 1;
        return 'ORD-' . $date . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    /**
     * Check if order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'delivered' && $this->payment_status === 'paid';
    }

    /**
     * Check if payment is required.
     */
    public function isPaymentRequired(): bool
    {
        return $this->payment_method !== null && $this->payment_status === null;
    }
}
