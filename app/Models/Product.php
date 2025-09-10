<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'brand_id',
        'material',
        'images',
        'price',
        'product_type',
        'product_link',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'images' => 'array',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get all tags for this product
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ProductTag::class);
    }

    /**
     * Get all colors for this product
     */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for products by gender
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope for products by brand
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get available colors for this product
     */
    public function getAvailableColors()
    {
        return $this->colors()
            ->whereHas('sizes', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->get();
    }

    /**
     * Get available sizes for this product
     */
    public function getAvailableSizes()
    {
        return $this->colors()
            ->with(['sizes' => function ($query) {
                $query->where('quantity', '>', 0);
            }])
            ->get()
            ->pluck('sizes')
            ->flatten()
            ->pluck('size_name')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get total stock quantity
     */
    public function getTotalStock()
    {
        return $this->colors()
            ->withSum('sizes', 'quantity')
            ->get()
            ->sum('sizes_sum_quantity');
    }

    /**
     * Check if product has stock
     */
    public function hasStock(): bool
    {
        return $this->colors()
            ->whereHas('sizes', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->exists();
    }
}