<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\ProductRepository;
use App\Repositories\BrandRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    protected $orderRepository;
    protected $userRepository;
    protected $productRepository;
    protected $brandRepository;

    public function __construct(
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        BrandRepository $brandRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
        $this->brandRepository = $brandRepository;
    }

    /**
     * Get dashboard overview statistics
     */
    public function overview(): JsonResponse
    {
        try {
            $stats = [
                'orders' => $this->getOrderStats(),
                'users' => $this->getUserStats(),
                'products' => $this->getProductStats(),
                'revenue' => $this->getRevenueStats(),
            ];

            return $this->successResponse($stats, 'Dashboard overview retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve dashboard overview', $e->getMessage());
        }
    }

    /**
     * Get revenue analytics with time-based data
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days'); // 7days, 30days, 90days, 1year
            $data = $this->getRevenueAnalytics($period);

            return $this->successResponse($data, 'Revenue analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve revenue analytics', $e->getMessage());
        }
    }

    /**
     * Get order analytics
     */
    public function orderAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $data = $this->getOrderAnalytics($period);

            return $this->successResponse($data, 'Order analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve order analytics', $e->getMessage());
        }
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $period = $request->get('period', '30days');
            $products = $this->getTopSellingProducts($limit, $period);

            return $this->successResponse($products, 'Top selling products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve top selling products', $e->getMessage());
        }
    }

    /**
     * Get customer analytics
     */
    public function customerAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $data = $this->getCustomerAnalytics($period);

            return $this->successResponse($data, 'Customer analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve customer analytics', $e->getMessage());
        }
    }

    /**
     * Get brand performance analytics
     */
    public function brandAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $data = $this->getBrandAnalytics($period);

            return $this->successResponse($data, 'Brand analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve brand analytics', $e->getMessage());
        }
    }

    /**
     * Get order statistics
     */
    private function getOrderStats(): array
    {
        $orderStats = $this->orderRepository->getStatistics();

        return [
            'total' => $orderStats['total_orders'],
            'pending' => $orderStats['pending_orders'],
            'confirmed' => $orderStats['confirmed_orders'],
            'processing' => $orderStats['processing_orders'],
            'shipped' => $orderStats['shipped_orders'],
            'delivered' => $orderStats['delivered_orders'],
            'cancelled' => $orderStats['cancelled_orders'],
            'refunded' => $orderStats['refunded_orders'],
            'today' => $orderStats['today_orders'],
        ];
    }

    /**
     * Get user statistics
     */
    private function getUserStats(): array
    {
        $userStats = $this->userRepository->getStatistics();

        return [
            'total' => $userStats['total_users'],
            'active' => $userStats['active_users'],
            'inactive' => $userStats['inactive_users'],
            'admin' => $userStats['admin_users'],
            'regular' => $userStats['regular_users'],
            'verified' => $userStats['verified_users'],
            'unverified' => $userStats['unverified_users'],
        ];
    }

    /**
     * Get product statistics
     */
    private function getProductStats(): array
    {
        $totalProducts = $this->productRepository->count();
        $activeProducts = $this->productRepository->where('is_active', true)->count();
        $lowStockProducts = $this->getLowStockProductsCount();

        return [
            'total' => $totalProducts,
            'active' => $activeProducts,
            'inactive' => $totalProducts - $activeProducts,
            'low_stock' => $lowStockProducts,
        ];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats(): array
    {
        $orderStats = $this->orderRepository->getStatistics();

        return [
            'total' => $orderStats['total_revenue'],
            'today' => $orderStats['today_revenue'],
            'average_order_value' => $this->getAverageOrderValue(),
        ];
    }

    /**
     * Get revenue analytics with time-based data
     */
    private function getRevenueAnalytics(string $period): array
    {
        $revenueData = [];

        if ($period === '7days') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayRevenue = $this->orderRepository->getRevenueByDate($date);
                $dayOrders = $this->orderRepository->getOrdersByDate($date);

                $revenueData[] = [
                    'date' => $date->format('Y-m-d'),
                    'revenue' => $dayRevenue,
                    'orders' => $dayOrders,
                ];
            }
        } elseif ($period === '30days') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayRevenue = $this->orderRepository->getRevenueByDate($date);
                $dayOrders = $this->orderRepository->getOrdersByDate($date);

                $revenueData[] = [
                    'date' => $date->format('Y-m-d'),
                    'revenue' => $dayRevenue,
                    'orders' => $dayOrders,
                ];
            }
        } elseif ($period === '90days') {
            for ($i = 89; $i >= 0; $i -= 3) {
                $startDate = Carbon::now()->subDays($i + 2);
                $endDate = Carbon::now()->subDays($i);
                $periodRevenue = $this->orderRepository->getRevenueByDateRange($startDate, $endDate);
                $periodOrders = $this->orderRepository->getOrdersByDateRange($startDate, $endDate);

                $revenueData[] = [
                    'date' => $startDate->format('Y-m-d'),
                    'revenue' => $periodRevenue,
                    'orders' => $periodOrders,
                ];
            }
        } elseif ($period === '1year') {
            for ($i = 11; $i >= 0; $i--) {
                $startDate = Carbon::now()->subMonths($i)->startOfMonth();
                $endDate = Carbon::now()->subMonths($i)->endOfMonth();
                $monthRevenue = $this->orderRepository->getRevenueByDateRange($startDate, $endDate);
                $monthOrders = $this->orderRepository->getOrdersByDateRange($startDate, $endDate);

                $revenueData[] = [
                    'date' => $startDate->format('Y-m'),
                    'revenue' => $monthRevenue,
                    'orders' => $monthOrders,
                ];
            }
        }

        return [
            'period' => $period,
            'data' => $revenueData,
            'summary' => [
                'total_revenue' => array_sum(array_column($revenueData, 'revenue')),
                'total_orders' => array_sum(array_column($revenueData, 'orders')),
                'average_daily_revenue' => count($revenueData) > 0 ? array_sum(array_column($revenueData, 'revenue')) / count($revenueData) : 0,
            ],
        ];
    }

    /**
     * Get order analytics
     */
    private function getOrderAnalytics(string $period): array
    {
        $orderStats = $this->orderRepository->getStatistics();

        return [
            'status_breakdown' => [
                'pending' => $orderStats['pending_orders'],
                'confirmed' => $orderStats['confirmed_orders'],
                'processing' => $orderStats['processing_orders'],
                'shipped' => $orderStats['shipped_orders'],
                'delivered' => $orderStats['delivered_orders'],
                'cancelled' => $orderStats['cancelled_orders'],
                'refunded' => $orderStats['refunded_orders'],
            ],
            'conversion_rate' => $this->getOrderConversionRate(),
            'average_processing_time' => 2.5, // days
        ];
    }

    /**
     * Get top selling products
     */
    private function getTopSellingProducts(int $limit, string $period): array
    {
        return $this->orderRepository->getTopSellingProducts($limit);
    }

    /**
     * Get customer analytics
     */
    private function getCustomerAnalytics(string $period): array
    {
        return [
            'new_customers' => $this->userRepository->getNewCustomersCount(),
            'returning_customers' => $this->userRepository->getReturningCustomersCount(),
            'customer_retention_rate' => 75.5, // percentage
            'average_customer_value' => $this->getAverageCustomerValue(),
        ];
    }

    /**
     * Get brand analytics
     */
    private function getBrandAnalytics(string $period): array
    {
        return $this->orderRepository->getBrandPerformance();
    }

    /**
     * Get low stock products count
     */
    private function getLowStockProductsCount(): int
    {
        return \App\Models\ProductSize::where('quantity', '<=', 10)->count();
    }

    /**
     * Get average order value
     */
    private function getAverageOrderValue(): float
    {
        $totalRevenue = $this->orderRepository->getStatistics()['total_revenue'];
        $totalOrders = $this->orderRepository->getStatistics()['total_orders'];

        return $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    }

    /**
     * Get order conversion rate
     */
    private function getOrderConversionRate(): float
    {
        $totalOrders = $this->orderRepository->getStatistics()['total_orders'];
        $deliveredOrders = $this->orderRepository->getStatistics()['delivered_orders'];

        return $totalOrders > 0 ? ($deliveredOrders / $totalOrders) * 100 : 0;
    }

    /**
     * Get average customer value
     */
    private function getAverageCustomerValue(): float
    {
        $totalRevenue = $this->orderRepository->getStatistics()['total_revenue'];
        $totalUsers = $this->userRepository->getStatistics()['total_users'];

        return $totalUsers > 0 ? $totalRevenue / $totalUsers : 0;
    }
}
