<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Admin Orders",
 *     description="Admin order management operations"
 * )
 */
class OrderController extends Controller
{
    use ApiResponseTrait;

    protected OrderRepository $orderRepository;
    protected OrderItemRepository $orderItemRepository;

    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @OA\Get(
     *     path="/admin/orders",
     *     summary="Get all orders",
     *     description="Get a paginated list of all orders with optional filtering for admin",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of orders per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "refunded"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="payment_status",
     *         in="query",
     *         description="Filter by payment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "paid", "failed"}, example="paid")
     *     ),
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         description="Filter by payment method",
     *         required=false,
     *         @OA\Schema(type="string", enum={"cash", "vnpay", "momo", "bank_transfer", "credit_card", "debit_card"}, example="vnpay")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter orders from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter orders to this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orders retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                         @OA\Property(property="status", type="string", example="pending"),
     *                         @OA\Property(property="payment_status", type="string", example="pending"),
     *                         @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                         @OA\Property(property="total_amount", type="number", format="float", example=450.00),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="John Doe"),
     *                             @OA\Property(property="email", type="string", example="john@example.com")
     *                         ),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string"),
     *                 @OA\Property(property="prev_page_url", type="string"),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orders = $this->orderRepository->getAllForAdmin($request);
            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve orders', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/statistics",
     *     summary="Get order statistics",
     *     description="Get comprehensive order statistics for admin dashboard",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Order statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_orders", type="integer", example=150),
     *                 @OA\Property(property="pending_orders", type="integer", example=10),
     *                 @OA\Property(property="confirmed_orders", type="integer", example=20),
     *                 @OA\Property(property="processing_orders", type="integer", example=15),
     *                 @OA\Property(property="shipped_orders", type="integer", example=25),
     *                 @OA\Property(property="delivered_orders", type="integer", example=70),
     *                 @OA\Property(property="cancelled_orders", type="integer", example=8),
     *                 @OA\Property(property="refunded_orders", type="integer", example=2),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=150000.00),
     *                 @OA\Property(property="today_orders", type="integer", example=5),
     *                 @OA\Property(property="today_revenue", type="number", format="float", example=2500.00)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->orderRepository->getStatistics();
            return $this->successResponse($stats, 'Order statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve order statistics', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/order/{id}",
     *     summary="Get specific order",
     *     description="Get detailed information about a specific order by ID for admin",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_status", type="string", example="pending"),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="phone", type="string", example="1234567890")
     *                 ),
     *                 @OA\Property(property="orderItems", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_name", type="string", example="Nike Air Max"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="total_price", type="number", format="float", example=300.00)
     *                     )
     *                 ),
     *                 @OA\Property(property="shipping_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->orderRepository->getByIdWithAllRelations($id);
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }
            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve order', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/order/{id}/status",
     *     summary="Update order status",
     *     description="Update the status of a specific order",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "refunded"}, example="confirmed"),
     *             @OA\Property(property="notes", type="string", example="Order confirmed and ready for processing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="confirmed"),
     *                 @OA\Property(property="notes", type="string", example="Order confirmed and ready for processing")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status transition",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid status transition")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
                'notes' => 'sometimes|string|max:500',
            ]);

            $order = $this->orderRepository->getByIdWithAllRelations($id);
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Check if status transition is valid
            if (!$this->isValidStatusTransition($order->status, $validated['status'])) {
                return $this->errorResponse('Invalid status transition', 400);
            }

            DB::beginTransaction();

            $this->orderRepository->updateStatus($id, $validated['status'], $validated['notes'] ?? null);

            DB::commit();

            $order->refresh();
            return $this->successResponse($order, 'Order status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update order status', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/order/{id}/tracking",
     *     summary="Update tracking information",
     *     description="Update tracking number and shipping information for an order",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tracking_number"},
     *             @OA\Property(property="tracking_number", type="string", example="VN123456789"),
     *             @OA\Property(property="shipping_method", type="string", example="Express Shipping"),
     *             @OA\Property(property="estimated_delivery", type="string", format="date", example="2024-12-28")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tracking information updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tracking information updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="tracking_number", type="string", example="VN123456789"),
     *                 @OA\Property(property="shipping_method", type="string", example="Express Shipping"),
     *                 @OA\Property(property="estimated_delivery", type="string", format="date", example="2024-12-28")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateTracking(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tracking_number' => 'required|string|max:100',
                'shipping_method' => 'sometimes|string|max:100',
                'estimated_delivery' => 'sometimes|date|after:today',
            ]);

            $order = $this->orderRepository->getByIdWithAllRelations($id);
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            DB::beginTransaction();

            $this->orderRepository->updateTracking($id, $validated);

            DB::commit();

            $order->refresh();
            return $this->successResponse($order, 'Tracking information updated successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update tracking information', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/order/{id}/cancel",
     *     summary="Cancel order",
     *     description="Cancel a specific order with reason",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", example="Customer requested cancellation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="cancelled_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order cannot be cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order cannot be cancelled at this stage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $order = $this->orderRepository->getByIdWithAllRelations($id);
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            if (!$order->canBeCancelled()) {
                return $this->errorResponse('Order cannot be cancelled at this stage', 400);
            }

            DB::beginTransaction();

            $this->orderRepository->cancelOrder($id, $validated['reason']);

            DB::commit();

            $order->refresh();
            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to cancel order', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/order/{id}/refund",
     *     summary="Process refund",
     *     description="Process a refund for a specific order",
     *     tags={"Admin Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", example="Product was defective"),
     *             @OA\Property(property="refund_amount", type="number", format="float", example=450.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refund processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Refund processed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="refunded"),
     *                 @OA\Property(property="refund_amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="refunded_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order cannot be refunded",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order cannot be refunded at this stage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function refund(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'refund_amount' => 'sometimes|numeric|min:0',
            ]);

            $order = $this->orderRepository->getByIdWithAllRelations($id);
            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            if (!$order->canBeRefunded()) {
                return $this->errorResponse('Order cannot be refunded at this stage', 400);
            }

            DB::beginTransaction();

            $refundAmount = $validated['refund_amount'] ?? $order->total_amount;
            $this->orderRepository->processRefund($id, $refundAmount, $validated['reason']);

            DB::commit();

            $order->refresh();
            return $this->successResponse($order, 'Refund processed successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to process refund', $e->getMessage());
        }
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}
