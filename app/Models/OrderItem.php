<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_color_id',
        'product_size_id',
        'product_name',
        'product_sku',
        'color_name',
        'size_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the order that owns the order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that belongs to the order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product color that belongs to the order item.
     */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * Get the product size that belongs to the order item.
     */
    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }

    /**
     * Calculate total price.
     */
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
