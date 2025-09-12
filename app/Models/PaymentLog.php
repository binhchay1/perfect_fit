<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'status',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the payment that owns the log
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
