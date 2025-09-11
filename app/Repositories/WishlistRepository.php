<?php

namespace App\Repositories;

use App\Models\Wishlist;

class WishlistRepository extends BaseRepository
{
    public function model()
    {
        return Wishlist::class;
    }

    /**
     * Get wishlist items for user with relationships
     */
    public function getForUserWithRelations($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['product.brand', 'product.colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if product is in user's wishlist
     */
    public function isInWishlist($userId, $productId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Add product to wishlist
     */
    public function addToWishlist($userId, $productId): Wishlist
    {
        return $this->create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist($userId, $productId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    /**
     * Get wishlist item for user (security check)
     */
    public function getForUser($wishlistId, $userId)
    {
        return $this->model
            ->where('id', $wishlistId)
            ->where('user_id', $userId)
            ->with(['product.brand', 'product.colors.sizes'])
            ->first();
    }

    /**
     * Delete wishlist item for user (security check)
     */
    public function deleteForUser($wishlistId, $userId): bool
    {
        $wishlistItem = $this->model
            ->where('id', $wishlistId)
            ->where('user_id', $userId)
            ->first();

        if ($wishlistItem) {
            $wishlistItem->delete();
            return true;
        }

        return false;
    }

    /**
     * Clear all wishlist items for user
     */
    public function clearForUser($userId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Get wishlist count for user
     */
    public function getCountForUser($userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Get wishlist items with pagination
     */
    public function getForUserPaginated($userId, $perPage = 15)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['product.brand', 'product.colors.sizes'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}