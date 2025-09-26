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

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management operations"
 * )
 */
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
     * @OA\Get(
     *     path="/orders",
     *     summary="Get user's orders",
     *     description="Retrieve a paginated list of the authenticated user's orders",
     *     tags={"Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of orders per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
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
     *                         @OA\Property(property="total_amount", type="number", format="float", example=450.00),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="orderItems", type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="quantity", type="integer", example=2),
     *                                 @OA\Property(property="unit_price", type="number", format="float", example=150.00),
     *                                 @OA\Property(property="product", type="object",
     *                                     @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                                     @OA\Property(property="slug", type="string", example="nike-air-max")
     *                                 )
     *                             )
     *                         )
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
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/order",
     *     summary="Create new order",
     *     description="Create a new order from the user's cart items",
     *     tags={"Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_address"},
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid"}, example="pending"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "vnpay", "momo", "bank_transfer", "credit_card", "debit_card"}, example="vnpay"),
     *             @OA\Property(property="shipping_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *             @OA\Property(property="billing_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *             @OA\Property(property="notes", type="string", example="Please handle with care")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="payment_status", type="string", example="pending"),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=450.00),
     *                 @OA\Property(property="tax_amount", type="number", format="float", example=0.00),
     *                 @OA\Property(property="shipping_fee", type="number", format="float", example=0.00),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=0.00),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="shipping_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *                 @OA\Property(property="billing_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *                 @OA\Property(property="notes", type="string", example="Please handle with care"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="orderItems", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_name", type="string", example="Nike Air Max"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="total_price", type="number", format="float", example=300.00)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cart is empty or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cart is empty")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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

            // Create order items from cart items (DO NOT deduct stock yet)
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

                // NOTE: Stock will be deducted only when payment is successful
                // This is handled in PaymentController@vnpayCallback
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
     * @OA\Get(
     *     path="/orders/{id}",
     *     summary="Get specific order",
     *     description="Retrieve details of a specific order by ID",
     *     tags={"Orders"},
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
     *                 @OA\Property(property="subtotal", type="number", format="float", example=450.00),
     *                 @OA\Property(property="tax_amount", type="number", format="float", example=0.00),
     *                 @OA\Property(property="shipping_fee", type="number", format="float", example=0.00),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=0.00),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="shipping_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *                 @OA\Property(property="billing_address", type="string", example="123 Main St, District 1, Ho Chi Minh City"),
     *                 @OA\Property(property="notes", type="string", example="Please handle with care"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="orderItems", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_name", type="string", example="Nike Air Max"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="total_price", type="number", format="float", example=300.00)
     *                     )
     *                 )
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
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/orders/{id}/cancel",
     *     summary="Cancel order",
     *     description="Cancel a specific order by ID",
     *     tags={"Orders"},
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
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/orders/{id}/tracking",
     *     summary="Get order tracking",
     *     description="Get tracking information for a specific order",
     *     tags={"Orders"},
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
     *         description="Tracking information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tracking information retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="status", type="string", example="shipped"),
     *                 @OA\Property(property="tracking_number", type="string", example="VN123456789"),
     *                 @OA\Property(property="shipped_at", type="string", format="date-time", example="2024-12-27T10:00:00Z"),
     *                 @OA\Property(property="delivered_at", type="string", format="date-time", example="2024-12-28T15:30:00Z"),
     *                 @OA\Property(property="shipping_address", type="string", example="123 Main St, District 1, Ho Chi Minh City")
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
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/purchased-products",
     *     summary="Get purchased products",
     *     description="Get a list of products purchased by the authenticated user",
     *     tags={"Orders"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of products per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "refunded"}, example="delivered")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchased products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchased products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Nike Air Max"),
     *                         @OA\Property(property="slug", type="string", example="nike-air-max"),
     *                         @OA\Property(property="price", type="number", format="float", example=150.00),
     *                         @OA\Property(property="image_path", type="string", example="images/products/nike-air-max.jpg"),
     *                         @OA\Property(property="purchased_at", type="string", format="date-time", example="2024-12-26T10:00:00Z"),
     *                         @OA\Property(property="order_status", type="string", example="delivered")
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
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
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
