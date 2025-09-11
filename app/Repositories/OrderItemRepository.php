<?php

namespace App\Repositories;

use App\Models\OrderItem;

class OrderItemRepository extends BaseRepository
{
    public function model()
    {
        return OrderItem::class;
    }


    /**
     * Get order items for order
     */
    public function getForOrder($orderId)
    {
        return $this->model
            ->where('order_id', $orderId)
            ->with(['product', 'productColor', 'productSize'])
            ->get();
    }

    /**
     * Get product sales statistics
     */
    public function getProductSales($productId, $dateRange = null)
    {
        $query = $this->model->where('product_id', $productId);

        if ($dateRange) {
            $query->whereHas('order', function ($q) use ($dateRange) {
                $q->whereBetween('created_at', $dateRange);
            });
        }

        return [
            'total_quantity_sold' => $query->sum('quantity'),
            'total_revenue' => $query->sum('total_price'),
            'total_orders' => $query->distinct('order_id')->count(),
        ];
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10, $dateRange = null)
    {
        $query = $this->model
            ->select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(total_price) as total_revenue')
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->groupBy('product_id', 'product_name');

        if ($dateRange) {
            $query->whereHas('order', function ($q) use ($dateRange) {
                $q->whereBetween('created_at', $dateRange);
            });
        }

        return $query->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get order items with product details
     */
    public function getWithProductDetails($orderId)
    {
        return $this->model
            ->where('order_id', $orderId)
            ->with([
                'product.brand',
                'productColor',
                'productSize'
            ])
            ->get();
    }
}
