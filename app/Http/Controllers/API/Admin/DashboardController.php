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

/**
 * @OA\Tag(
 *     name="Admin Dashboard",
 *     description="Admin dashboard and analytics operations"
 * )
 */
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
     * @OA\Get(
     *     path="/admin/dashboard/overview",
     *     summary="Get dashboard overview",
     *     description="Get comprehensive dashboard statistics including orders, users, products, and revenue",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard overview retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard overview retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="orders", type="object",
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="pending", type="integer", example=10),
     *                     @OA\Property(property="confirmed", type="integer", example=20),
     *                     @OA\Property(property="processing", type="integer", example=15),
     *                     @OA\Property(property="shipped", type="integer", example=25),
     *                     @OA\Property(property="delivered", type="integer", example=70),
     *                     @OA\Property(property="cancelled", type="integer", example=8),
     *                     @OA\Property(property="refunded", type="integer", example=2),
     *                     @OA\Property(property="today", type="integer", example=5)
     *                 ),
     *                 @OA\Property(property="users", type="object",
     *                     @OA\Property(property="total", type="integer", example=1200),
     *                     @OA\Property(property="active", type="integer", example=1150),
     *                     @OA\Property(property="inactive", type="integer", example=50),
     *                     @OA\Property(property="admin", type="integer", example=3),
     *                     @OA\Property(property="regular", type="integer", example=1197),
     *                     @OA\Property(property="verified", type="integer", example=1100),
     *                     @OA\Property(property="unverified", type="integer", example=100)
     *                 ),
     *                 @OA\Property(property="products", type="object",
     *                     @OA\Property(property="total", type="integer", example=500),
     *                     @OA\Property(property="active", type="integer", example=450),
     *                     @OA\Property(property="inactive", type="integer", example=50),
     *                     @OA\Property(property="low_stock", type="integer", example=15)
     *                 ),
     *                 @OA\Property(property="revenue", type="object",
     *                     @OA\Property(property="total", type="number", format="float", example=150000.00),
     *                     @OA\Property(property="today", type="number", format="float", example=2500.00),
     *                     @OA\Property(property="average_order_value", type="number", format="float", example=120.50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated or not admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/admin/dashboard/revenue-analytics",
     *     summary="Get revenue analytics",
     *     description="Get detailed revenue analytics with time-based data",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "1year"}, example="30days", default="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenue analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Revenue analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period", type="string", example="30days"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="date", type="string", example="2024-12-01"),
     *                         @OA\Property(property="revenue", type="number", format="float", example=2500.00),
     *                         @OA\Property(property="orders", type="integer", example=15)
     *                     )
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=75000.00),
     *                     @OA\Property(property="total_orders", type="integer", example=450),
     *                     @OA\Property(property="average_daily_revenue", type="number", format="float", example=2500.00)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated or not admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/admin/dashboard/order-analytics",
     *     summary="Get order analytics",
     *     description="Get detailed order analytics including status breakdown and conversion rates",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "1year"}, example="30days", default="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status_breakdown", type="object",
     *                     @OA\Property(property="pending", type="integer", example=10),
     *                     @OA\Property(property="confirmed", type="integer", example=20),
     *                     @OA\Property(property="processing", type="integer", example=15),
     *                     @OA\Property(property="shipped", type="integer", example=25),
     *                     @OA\Property(property="delivered", type="integer", example=70),
     *                     @OA\Property(property="cancelled", type="integer", example=8),
     *                     @OA\Property(property="refunded", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="conversion_rate", type="number", format="float", example=75.5),
     *                 @OA\Property(property="average_processing_time", type="number", format="float", example=2.5)
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/admin/dashboard/top-products",
     *     summary="Get top selling products",
     *     description="Get the top selling products based on order data",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of products to return",
     *         required=false,
     *         @OA\Schema(type="integer", example=10, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "1year"}, example="30days", default="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Top selling products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Top selling products retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                     @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="total_sold", type="integer", example=45),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=6750.00),
     *                     @OA\Property(property="brand", type="object",
     *                         @OA\Property(property="name", type="string", example="Nike")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/admin/dashboard/customer-analytics",
     *     summary="Get customer analytics",
     *     description="Get detailed customer analytics including retention and value metrics",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "1year"}, example="30days", default="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="new_customers", type="integer", example=150),
     *                 @OA\Property(property="returning_customers", type="integer", example=1050),
     *                 @OA\Property(property="customer_retention_rate", type="number", format="float", example=75.5),
     *                 @OA\Property(property="average_customer_value", type="number", format="float", example=125.75)
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/admin/dashboard/brand-analytics",
     *     summary="Get brand analytics",
     *     description="Get brand performance analytics including sales and revenue data",
     *     tags={"Admin Dashboard"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days", "1year"}, example="30days", default="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brand analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Brand analytics retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="brand_name", type="string", example="Nike"),
     *                     @OA\Property(property="total_orders", type="integer", example=120),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=18000.00),
     *                     @OA\Property(property="average_order_value", type="number", format="float", example=150.00),
     *                     @OA\Property(property="product_count", type="integer", example=25)
     *                 )
     *             )
     *         )
     *     )
     * )
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
