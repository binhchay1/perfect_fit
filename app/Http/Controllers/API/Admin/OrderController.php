<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Get all orders with filtering and pagination
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
     * Get order statistics for admin dashboard
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
     * Show specific order details
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
     * Update order status
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
     * Update tracking number
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
     * Cancel order
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
     * Process refund
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
