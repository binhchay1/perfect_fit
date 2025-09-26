<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'payment_method',
        'payment_provider',
        'status',
        'transaction_id',
        'external_payment_id',
        'gateway_response',
        'notes',
        'session_token',
        'session_expires_at',
        'session_used',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'session_expires_at' => 'datetime',
        'session_used' => 'boolean',
        'notes' => 'string',
    ];

    /**
     * Get the user that owns the payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the transactions for the payment
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the payment callbacks for the payment
     */
    public function paymentCallbacks(): HasMany
    {
        return $this->hasMany(PaymentCallback::class);
    }

    /**
     * Get the payment logs for the payment
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    /**
     * Check if payment is successful
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(string $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }

    /**
     * Mark payment as refunded
     */
    public function markAsRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }

    /**
     * Add payment log
     */
    public function addLog(string $status, string $message = null, array $data = null): void
    {
        $this->paymentLogs()->create([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Generate and set payment session token
     */
    public function generateSessionToken(int $expiresInMinutes = 30): void
    {
        $this->session_token = $this->generateUniqueToken();
        $this->session_expires_at = now()->addMinutes($expiresInMinutes);
        $this->session_used = false;
        $this->save();
    }

    /**
     * Check if payment session is valid (not expired and not used)
     */
    public function isSessionValid(): bool
    {
        if (!$this->session_token || !$this->session_expires_at) {
            return false;
        }

        return $this->session_expires_at->isFuture() && !$this->session_used;
    }

    /**
     * Check if payment session is expired
     */
    public function isSessionExpired(): bool
    {
        return $this->session_expires_at && $this->session_expires_at->isPast();
    }

    /**
     * Mark payment session as used
     */
    public function markSessionAsUsed(): void
    {
        $this->update([
            'session_used' => true,
        ]);
    }

    /**
     * Get payment URL from gateway response
     */
    public function getPaymentUrl(): ?string
    {
        return $this->gateway_response['payment_url'] ?? null;
    }

    /**
     * Generate unique session token
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = 'pay_' . strtoupper(uniqid()) . '_' . time();
        } while (self::where('session_token', $token)->exists());

        return $token;
    }
}