<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_name',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Get the product that owns the color.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all sizes for this color.
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * Get available sizes (with stock > 0)
     */
    public function availableSizes(): HasMany
    {
        return $this->sizes()->where('quantity', '>', 0);
    }

    /**
     * Get total stock for this color
     */
    public function getTotalStockAttribute()
    {
        return $this->sizes()->sum('quantity');
    }

    /**
     * Check if color has stock
     */
    public function hasStock(): bool
    {
        return $this->sizes()->where('quantity', '>', 0)->exists();
    }

    /**
     * Get color images URLs
     */
    public function getImageUrlsAttribute()
    {
        if ($this->images && is_array($this->images)) {
            return array_map(function ($image) {
                return asset($image);
            }, $this->images);
        }
        return [];
    }

    /**
     * Get first color image URL (for backward compatibility)
     */
    public function getFirstImageUrlAttribute()
    {
        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            return asset($this->images[0]);
        }
        return null;
    }
}