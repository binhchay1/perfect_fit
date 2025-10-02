<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserBodyMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gender',
        'height',
        'weight',
        'chest',
        'waist',
        'hips',
        'thigh',
        'shoulder',
        'arm_length',
        'leg_length',
        'height_unit',
        'weight_unit',
        'measurement_unit',
        'preferred_fit',
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'chest' => 'decimal:2',
        'waist' => 'decimal:2',
        'hips' => 'decimal:2',
        'thigh' => 'decimal:2',
        'shoulder' => 'decimal:2',
        'arm_length' => 'decimal:2',
        'leg_length' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

