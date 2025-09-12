<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\ProductSizeRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponseTrait;

    protected $orderRepository;
    protected $orderItemRepository;
    protected $productSizeRepository;

    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        ProductSizeRepository $productSizeRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productSizeRepository = $productSizeRepository;
    }

    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);

            $orders = $this->orderRepository->getForUser($user->id, $perPage);

            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve orders', 500);
        }
    }

    /**
     * Create a new order from cart.
     */
    public function store(StoreOrderRequest $request)
    {
        try {

            $user = Auth::user();

            // Get user's cart
            $cart = $this->orderRepository->getUserCart($user->id);
            if (!$cart || $cart->cartItems->isEmpty()) {
                return $this->errorResponse('Cart is empty', 400);
            }

            // Calculate totals
            $subtotal = $cart->total_amount;
            // $taxAmount = $subtotal * 0.1; // VAT 10%
            // $shippingFee = $this->calculateShippingFee($request->shipping_address);
            // $discountAmount = $request->discount_amount ?? 0;

            $taxAmount = 0; // VAT 10%
            $shippingFee = 0;
            $discountAmount = 0;
            $totalAmount = $subtotal + $taxAmount + $shippingFee - $discountAmount;


            // Prepare order data
            $orderData = [
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => $request->payment_status,
                'payment_method' => $request->payment_method ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address ?? $request->shipping_address,
                'notes' => $request->notes ?? null,
            ];

            // Create order
            $order = $this->orderRepository->createOrder($orderData);

            // Create order items from cart items and deduct stock
            foreach ($cart->cartItems as $cartItem) {
                // Check stock availability before creating order item
                if (!$this->productSizeRepository->hasStock($cartItem->product_size_id, $cartItem->quantity)) {
                    $availableStock = $this->productSizeRepository->getAvailableStock($cartItem->product_size_id);
                    return $this->errorResponse(
                        "Insufficient stock for product: {$cartItem->product->name}. Available: {$availableStock}, Required: {$cartItem->quantity}",
                        400
                    );
                }

                // Create order item
                $order->orderItems()->create([
                    'product_id' => $cartItem->product_id,
                    'product_color_id' => $cartItem->product_color_id,
                    'product_size_id' => $cartItem->product_size_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $this->generateSku($cartItem),
                    'color_name' => $cartItem->productColor?->color_name,
                    'size_name' => $cartItem->productSize?->size_name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'total_price' => $cartItem->quantity * $cartItem->price
                ]);

                // Deduct stock from product_size
                $this->productSizeRepository->deductStock($cartItem->product_size_id, $cartItem->quantity);
            }

            // Load relationships
            $order->load(['orderItems.product', 'orderItems.productColor', 'orderItems.productSize']);

            // Clear cart
            $this->orderRepository->clearUserCart($user->id);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();
            $order = $this->orderRepository->getByIdForUser($id, $user->id);

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve order', 500);
        }
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(string $id)
    {
        try {
            $user = Auth::user();
            $order = $this->orderRepository->cancelOrderForUser($id, $user->id);

            if (!$order) {
                return $this->errorResponse('Order cannot be cancelled or not found', 400);
            }

            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel order', 500);
        }
    }

    /**
     * Get order tracking information.
     */
    public function tracking(string $id)
    {
        try {
            $user = Auth::user();
            $order = $this->orderRepository->getByIdForUser($id, $user->id);

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            $trackingData = [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'shipping_address' => $order->shipping_address,
            ];

            return $this->successResponse($trackingData, 'Tracking information retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve tracking information', 500);
        }
    }

    /**
     * Calculate shipping fee based on address
     */
    private function calculateShippingFee($shippingAddress)
    {
        // Base shipping fee
        $baseFee = 30000; // 30k VND

        // You can implement distance-based calculation here
        // For now, return base fee
        return $baseFee;
    }

    /**
     * Generate SKU for order item
     */
    private function generateSku($cartItem)
    {
        if ($cartItem->productColor && $cartItem->productSize) {
            return $cartItem->productColor->sku . $cartItem->productSize->sku;
        }

        return $cartItem->product->sku ?? 'N/A';
    }

    /**
     * Get purchased products for the authenticated user
     */
    public function purchasedProducts(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status', 'delivered'); // Default to delivered orders

            $purchasedProducts = $this->orderRepository->getPurchasedProducts($user->id, $perPage, $status);

            return $this->successResponse($purchasedProducts, 'Purchased products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchased products', 500);
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (\App\Models\Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}