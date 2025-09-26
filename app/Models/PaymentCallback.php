<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCallback extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'gateway_name',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'error_message' => 'string',
    ];

    /**
     * Get the payment that owns the callback
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Mark callback as processed
     */
    public function markAsProcessed(array $responseData = null): void
    {
        $this->update([
            'status' => 'processed',
            'response_data' => $responseData,
        ]);
    }

    /**
     * Mark callback as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
