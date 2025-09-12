<?php

namespace App\Repositories;

use App\Models\ProductSize;

class ProductSizeRepository extends BaseRepository
{
    public function model()
    {
        return ProductSize::class;
    }

    /**
     * Get product size by ID with relationships
     */
    public function getByIdWithRelations($id)
    {
        return $this->model
            ->with(['productColor.product'])
            ->findOrFail($id);
    }

    /**
     * Check if size belongs to color
     */
    public function belongsToColor($sizeId, $colorId): bool
    {
        return $this->model
            ->where('id', $sizeId)
            ->where('product_color_id', $colorId)
            ->exists();
    }

    /**
     * Check stock availability
     */
    public function hasStock($sizeId, $quantity = 1): bool
    {
        $size = $this->model->find($sizeId);
        return $size && $size->quantity >= $quantity;
    }

    /**
     * Get available stock quantity
     */
    public function getAvailableStock($sizeId): int
    {
        $size = $this->model->find($sizeId);
        return $size ? $size->quantity : 0;
    }

    /**
     * Get available sizes for a color
     */
    public function getAvailableSizesForColor($colorId)
    {
        return $this->model
            ->where('product_color_id', $colorId)
            ->where('quantity', '>', 0)
            ->get();
    }

    /**
     * Get size by name and color
     */
    public function getBySizeAndColor($sizeName, $colorId)
    {
        return $this->model
            ->where('size_name', $sizeName)
            ->where('product_color_id', $colorId)
            ->first();
    }

    /**
     * Deduct stock quantity
     */
    public function deductStock($sizeId, $quantity): bool
    {
        $size = $this->model->find($sizeId);
        if (!$size || $size->quantity < $quantity) {
            return false;
        }

        $size->quantity -= $quantity;
        return $size->save();
    }

    /**
     * Add stock quantity (for restocking)
     */
    public function addStock($sizeId, $quantity): bool
    {
        $size = $this->model->find($sizeId);
        if (!$size) {
            return false;
        }

        $size->quantity += $quantity;
        return $size->save();
    }
}
