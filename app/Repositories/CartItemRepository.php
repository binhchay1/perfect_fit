<?php

namespace App\Repositories;

use App\Models\CartItem;

class CartItemRepository extends BaseRepository
{
    public function model()
    {
        return CartItem::class;
    }

    /**
     * Find existing cart item
     */
    public function findExistingItem($cartId, $productId, $productColorId, $productSizeId)
    {
        return $this->model
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('product_color_id', $productColorId)
            ->where('product_size_id', $productSizeId)
            ->first();
    }

    /**
     * Create new cart item
     */
    public function createItem($cartId, $productId, $productColorId, $productSizeId, $quantity, $price, $sizeName, $colorName): CartItem
    {
        return $this->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'product_color_id' => $productColorId,
            'product_size_id' => $productSizeId,
            'quantity' => $quantity,
            'price' => $price,
            'size' => $sizeName,
            'color' => $colorName,
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($itemId, $quantity): bool
    {
        $item = $this->model->find($itemId);

        if ($item) {
            $item->update(['quantity' => $quantity]);
            return true;
        }

        return false;
    }

    /**
     * Get cart item by ID with relationships
     */
    public function getByIdWithRelations($id)
    {
        return $this->model
            ->with(['product.brand', 'productColor', 'productSize'])
            ->findOrFail($id);
    }

    /**
     * Get cart item for user (security check)
     */
    public function getForUser($itemId, $userId)
    {
        return $this->model
            ->where('id', $itemId)
            ->whereHas('cart', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['product.brand', 'productColor', 'productSize'])
            ->first();
    }

    /**
     * Delete cart item for user (security check)
     */
    public function deleteForUser($itemId, $userId): bool
    {
        $item = $this->model
            ->where('id', $itemId)
            ->whereHas('cart', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();

        if ($item) {
            $item->delete();
            return true;
        }

        return false;
    }

    /**
     * Check if quantity exceeds maximum limit
     */
    public function exceedsMaxQuantity($currentQuantity, $additionalQuantity, $maxQuantity = 10): bool
    {
        return ($currentQuantity + $additionalQuantity) > $maxQuantity;
    }

    /**
     * Get cart items for cart with relationships
     */
    public function getForCartWithRelations($cartId)
    {
        return $this->model
            ->where('cart_id', $cartId)
            ->with(['product.brand', 'productColor', 'productSize'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
