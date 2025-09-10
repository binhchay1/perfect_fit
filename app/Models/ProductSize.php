<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_color_id',
        'size_name',
        'quantity',
        'sku',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the product color that owns the size.
     */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Get the product through color relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id')
            ->through('productColor');
    }

    /**
     * Check if size is in stock
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Scope for in stock sizes
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope for specific size
     */
    public function scopeBySize($query, $size)
    {
        return $query->where('size_name', $size);
    }

    /**
     * Generate SKU automatically
     */
    public static function generateSku($productId, $colorName, $sizeName): string
    {
        $prefix = 'PRD';
        $productCode = str_pad($productId, 4, '0', STR_PAD_LEFT);
        $colorCode = strtoupper(substr($colorName, 0, 3));
        $sizeCode = strtoupper($sizeName);

        return $prefix . $productCode . $colorCode . $sizeCode;
    }
}