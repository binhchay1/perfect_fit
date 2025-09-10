<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'tag',
    ];

    /**
     * Get the product that owns the tag.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}