<?php

namespace App\Repositories;

use App\Models\ProductColor;

class ProductColorRepository extends BaseRepository
{
    public function model()
    {
        return ProductColor::class;
    }

    /**
     * Get product color by ID with relationships
     */
    public function getByIdWithRelations($id)
    {
        return $this->model
            ->with(['product', 'sizes'])
            ->findOrFail($id);
    }

    /**
     * Check if color belongs to product
     */
    public function belongsToProduct($colorId, $productId): bool
    {
        return $this->model
            ->where('id', $colorId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get available colors for a product (with stock)
     */
    public function getAvailableColorsForProduct($productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->whereHas('sizes', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->with(['sizes' => function ($query) {
                $query->where('quantity', '>', 0);
            }])
            ->get();
    }

    /**
     * Check if color has stock
     */
    public function hasStock($colorId): bool
    {
        return $this->model
            ->where('id', $colorId)
            ->whereHas('sizes', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->exists();
    }
}
