<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestController extends Controller
{
    /**
     * Create a test order for payment testing
     */
    public function createTestOrder()
    {
        try {
            // Create or get a test user
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]);
            }

            // Get a test product
            $product = Product::first();
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products found. Please create a product first.',
                    'instructions' => 'Run: php artisan db:seed --class=ProductSeeder'
                ]);
            }

            DB::beginTransaction();

            // Create test order
            $orderNumber = 'TEST-' . time();
            $totalAmount = 100000; // 100,000 VND

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipping_address' => 'Test Address',
                'shipping_phone' => '0123456789',
                'notes' => 'Test order for VNPay payment testing',
            ]);

            // Get a product color and size for the order item
            $productColor = $product->colors()->first();
            $productSize = $productColor ? $productColor->sizes()->first() : null;

            // Create order item with all required fields
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_color_id' => $productColor ? $productColor->id : null,
                'product_size_id' => $productSize ? $productSize->id : null,
                'product_name' => $product->name,
                'product_sku' => $product->slug . '-test', // Use slug as SKU since Product doesn't have SKU field
                'color_name' => $productColor ? $productColor->color_name : 'Default',
                'size_name' => $productSize ? $productSize->size_name : 'One Size',
                'quantity' => 1,
                'unit_price' => $totalAmount,
                'total_price' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'product_name' => $product->name,
                    'payment_methods' => [
                        'cash' => 'Immediate payment completion',
                        'vnpay' => 'VNPay gateway (redirect to VNPay)',
                        'bank_transfer' => 'Manual bank transfer',
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test VNPay payment creation
     */
    public function testVnpayPayment(Request $request)
    {
        try {
            $orderId = $request->query('order_id');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide order_id parameter',
                    'example' => '/test/vnpay-payment?order_id=1'
                ]);
            }

            $order = Order::find($orderId);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

            // Test VNPay payment creation (without authentication)
            $paymentService = app(\App\Services\VnpayService::class);

            // Create payment record
            $payment = Payment::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_method' => 'vnpay',
                'payment_provider' => 'vnpay',
                'status' => 'pending',
            ]);

            // Create transaction
            $transaction = $paymentService->createTransaction($payment);

            // Create VNPay payment URL
            $vnpayParams = [
                'vnp_Amount' => $paymentService->formatAmount((float) $order->total_amount),
                'vnp_OrderInfo' => $paymentService->generateOrderInfo($order->order_number),
                'vnp_TxnRef' => $payment->id,
                'vnp_ReturnUrl' => config('vnpay.return_url'),
            ];

            $paymentUrl = $paymentService->createPaymentUrl($vnpayParams);

            // Update payment with gateway response
            $payment->update([
                'gateway_response' => [
                    'payment_url' => $paymentUrl,
                    'gateway_params' => $vnpayParams,
                    'created_at' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'VNPay payment URL generated successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'payment_url' => $paymentUrl,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'test_instructions' => [
                        '1. Copy the payment_url',
                        '2. Open it in your browser',
                        '3. Use test card: 9704198526191432198',
                        '4. CVV: 123, Expiry: 12/25, OTP: 123456',
                        '5. Complete payment',
                        '6. VNPay will redirect back with result'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create VNPay payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test payment result handler
     */
    public function testPaymentResult(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment result received',
            'data' => [
                'status' => $request->query('status'),
                'message' => $request->query('message'),
                'payment_id' => $request->query('payment_id'),
                'order_id' => $request->query('order_id'),
                'transaction_id' => $request->query('transaction_id'),
                'amount' => $request->query('amount'),
                'timestamp' => now()->toISOString(),
                'instructions' => 'This is where your frontend would display the payment result'
            ]
        ]);
    }

    /**
     * Get test information
     */
    public function getTestInfo()
    {
        return response()->json([
            'success' => true,
            'message' => 'VNPay Payment Testing Information',
            'data' => [
                'test_card' => [
                    'number' => '9704198526191432198',
                    'cvv' => '123',
                    'expiry' => '12/25',
                    'otp' => '123456'
                ],
                'sandbox_url' => 'https://sandbox.vnpayment.vn',
                'endpoints' => [
                    'create_test_order' => '/test/create-order',
                    'test_vnpay_payment' => '/test/vnpay-payment?order_id={order_id}',
                    'payment_result' => '/test/payment-result',
                    'api_payment_create' => '/api/payment/create',
                    'api_payment_status' => '/api/payment/status',
                    'vnpay_callback' => '/api/payment/vnpay/callback'
                ],
                'testing_steps' => [
                    '1. Create test order: GET /test/create-order',
                    '2. Create VNPay payment: GET /test/vnpay-payment?order_id={order_id}',
                    '3. Copy the payment_url from response',
                    '4. Open payment_url in browser',
                    '5. Use test card information',
                    '6. Complete payment on VNPay',
                    '7. Check payment status: GET /api/payment/status?payment_id={payment_id}'
                ]
            ]
        ]);
    }
}
