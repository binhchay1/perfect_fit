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
     * Update order status (user version)
     */
    public function updateStatusForUser($orderId, $status, $userId)
    {
        $query = $this->model->where('id', $orderId)->where('user_id', $userId);

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
     * Cancel order (user version)
     */
    public function cancelOrderForUser($orderId, $userId)
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
     * Get all orders for admin with filtering
     */
    public function getAllForAdmin($request)
    {
        $query = $this->model->with(['user', 'orderItems.product', 'orderItems.productColor', 'orderItems.productSize']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment_status
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order number or customer info
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->get('per_page', 15));
    }

    /**
     * Get order by ID with all relations for admin
     */
    public function getByIdWithAllRelations($id)
    {
        return $this->model
            ->with(['user', 'orderItems.product', 'orderItems.productColor', 'orderItems.productSize'])
            ->find($id);
    }

    /**
     * Update order status (admin version)
     */
    public function updateStatus($orderId, $status, $notes = null)
    {
        $order = $this->model->find($orderId);
        if (!$order) {
            return false;
        }

        $order->status = $status;
        if ($notes) {
            $order->notes = $notes;
        }

        // Set timestamps for specific statuses
        if ($status === 'shipped' && !$order->shipped_at) {
            $order->shipped_at = now();
        } elseif ($status === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
        }

        return $order->save();
    }

    /**
     * Update tracking information
     */
    public function updateTracking($orderId, $trackingData)
    {
        $order = $this->model->find($orderId);
        if (!$order) {
            return false;
        }

        $order->tracking_number = $trackingData['tracking_number'];
        if (isset($trackingData['shipping_method'])) {
            $order->shipping_method = $trackingData['shipping_method'];
        }
        if (isset($trackingData['estimated_delivery'])) {
            $order->estimated_delivery = $trackingData['estimated_delivery'];
        }

        return $order->save();
    }

    /**
     * Cancel order (admin version)
     */
    public function cancelOrder($orderId, $reason)
    {
        $order = $this->model->find($orderId);
        if (!$order || !$order->canBeCancelled()) {
            return false;
        }

        $order->status = 'cancelled';
        $order->notes = $reason;
        $order->save();

        // Restore stock
        $this->restoreStock($order);

        return $order;
    }

    /**
     * Process refund
     */
    public function processRefund($orderId, $refundAmount, $reason)
    {
        $order = $this->model->find($orderId);
        if (!$order || !$order->canBeRefunded()) {
            return false;
        }

        $order->status = 'refunded';
        $order->payment_status = 'refunded';
        $order->notes = $reason;
        $order->save();

        // Restore stock
        $this->restoreStock($order);

        return $order;
    }

    /**
     * Get order statistics for admin dashboard
     */
    public function getStatistics()
    {
        return [
            'total_orders' => $this->model->count(),
            'total_revenue' => $this->model->sum('total_amount'),
            'pending_orders' => $this->model->where('status', 'pending')->count(),
            'confirmed_orders' => $this->model->where('status', 'confirmed')->count(),
            'processing_orders' => $this->model->where('status', 'processing')->count(),
            'shipped_orders' => $this->model->where('status', 'shipped')->count(),
            'delivered_orders' => $this->model->where('status', 'delivered')->count(),
            'cancelled_orders' => $this->model->where('status', 'cancelled')->count(),
            'refunded_orders' => $this->model->where('status', 'refunded')->count(),
            'today_orders' => $this->model->whereDate('created_at', today())->count(),
            'today_revenue' => $this->model->whereDate('created_at', today())->sum('total_amount'),
        ];
    }

    /**
     * Get revenue by specific date
     */
    public function getRevenueByDate($date)
    {
        return $this->model
            ->whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get orders count by specific date
     */
    public function getOrdersByDate($date)
    {
        return $this->model
            ->whereDate('created_at', $date)
            ->count();
    }

    /**
     * Get revenue by date range
     */
    public function getRevenueByDateRange($startDate, $endDate)
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get orders count by date range
     */
    public function getOrdersByDateRange($startDate, $endDate)
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10)
    {
        return \App\Models\OrderItem::selectRaw('
                product_id,
                product_name,
                SUM(quantity) as total_sold,
                SUM(total_price) as total_revenue,
                AVG(unit_price) as average_price
            ')
            ->groupBy('product_id', 'product_name')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get brand performance
     */
    public function getBrandPerformance()
    {
        return \App\Models\OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->selectRaw('
                brands.id,
                brands.name,
                COUNT(DISTINCT order_items.order_id) as total_orders,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.total_price) as total_revenue
            ')
            ->groupBy('brands.id', 'brands.name')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get purchased products for a user
     */
    public function getPurchasedProducts($userId, $perPage = 15, $status = 'delivered')
    {
        return \App\Models\OrderItem::whereHas('order', function ($query) use ($userId, $status) {
            $query->where('user_id', $userId)
                ->where('status', $status);
        })
            ->with([
                'product:id,name,slug,images,price',
                'productColor:id,color_name',
                'productSize:id,size_name',
                'order:id,order_number,status,created_at'
            ])
            ->select([
                'id',
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
                'order_id',
                'created_at'
            ])
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