<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\User;

class CartRepository extends BaseRepository
{
    public function model()
    {
        return Cart::class;
    }

    /**
     * Get or create cart for user
     */
    public function getOrCreateForUser($userId): Cart
    {
        $cart = $this->model
            ->where('user_id', $userId)
            ->with(['cartItems.product.brand', 'cartItems.productColor', 'cartItems.productSize'])
            ->first();

        if (!$cart) {
            $cart = $this->create(['user_id' => $userId]);
        }

        return $cart;
    }

    /**
     * Get cart for user with items
     */
    public function getForUserWithItems($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['cartItems.product.brand', 'cartItems.productColor', 'cartItems.productSize'])
            ->first();
    }

    /**
     * Get cart summary for user
     */
    public function getSummaryForUser($userId): array
    {
        $cart = $this->getForUserWithItems($userId);

        if (!$cart) {
            return [
                'total_items' => 0,
                'total_amount' => 0,
            ];
        }

        return [
            'total_items' => $cart->total_items,
            'total_amount' => $cart->total_amount,
        ];
    }

    /**
     * Clear all items from user's cart
     */
    public function clearForUser($userId): bool
    {
        $cart = $this->model->where('user_id', $userId)->first();

        if ($cart) {
            $cart->cartItems()->delete();
            return true;
        }

        return false;
    }

    /**
     * Check if user has cart
     */
    public function userHasCart($userId): bool
    {
        return $this->model->where('user_id', $userId)->exists();
    }
}