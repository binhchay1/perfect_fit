<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository
{
    public function model()
    {
        return Order::class;
    }

    /**
     * Create order record
     */
    public function createOrder($orderData)
    {
        return $this->create($orderData);
    }

    /**
     * Get user's cart with items
     */
    public function getUserCart($userId)
    {
        return Cart::where('user_id', $userId)
            ->with(['cartItems.product', 'cartItems.productColor', 'cartItems.productSize'])
            ->first();
    }

    /**
     * Clear user's cart
     */
    public function clearUserCart($userId)
    {
        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cart->cartItems()->delete();
            $cart->delete();
        }
    }

    /**
     * Get orders for user with pagination
     */
    public function getForUser($userId, $perPage = 15)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['orderItems.product', 'orderItems.productColor', 'orderItems.productSize'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get order by ID for user (security check)
     */
    public function getByIdForUser($orderId, $userId)
    {
        return $this->model
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->with(['orderItems.product', 'orderItems.productColor', 'orderItems.productSize'])
            ->first();
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $userId = null)
    {
        $query = $this->model->where('id', $orderId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $order = $query->first();
        if (!$order) {
            return false;
        }

        $order->status = $status;

        // Set timestamps for specific statuses
        if ($status === 'shipped' && !$order->shipped_at) {
            $order->shipped_at = now();
        } elseif ($status === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
        }

        return $order->save();
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId, $userId)
    {
        $order = $this->getByIdForUser($orderId, $userId);

        if (!$order || !$order->canBeCancelled()) {
            return false;
        }

        $order->status = 'cancelled';
        $order->save();

        // Restore stock
        $this->restoreStock($order);

        return $order;
    }

    /**
     * Get order statistics
     */
    public function getStatistics($dateRange = null)
    {
        $query = $this->model->query();

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_amount'),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'completed_orders' => $query->where('status', 'delivered')->count(),
            'cancelled_orders' => $query->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Get pending orders for admin
     */
    public function getPendingOrders()
    {
        return $this->model
            ->where('status', 'pending')
            ->with(['user', 'orderItems.product'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus($status, $perPage = 15)
    {
        return $this->model
            ->where('status', $status)
            ->with(['user', 'orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }


    /**
     * Restore stock when order is cancelled
     */
    private function restoreStock($order)
    {
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->productSize) {
                $orderItem->productSize->increment('quantity', $orderItem->quantity);
            }
        }
    }
}