<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\VnpayService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $vnpayService;

    public function __construct(VnpayService $vnpayService)
    {
        $this->vnpayService = $vnpayService;
    }

    /**
     * Create payment request (supports multiple payment methods)
     */
    public function createPayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'payment_method' => 'required|string|in:cash,vnpay,momo,bank_transfer,credit_card,debit_card',
            ]);

            $user = Auth::user();
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return $this->errorResponse('Order not found or access denied', 404);
            }

            // Check if order is pending
            if ($order->status !== 'pending') {
                return $this->errorResponse('Order is not in pending status', 400);
            }

            // Check if order has items
            if (!$order->orderItems || $order->orderItems->count() === 0) {
                return $this->errorResponse('Order has no items', 400);
            }

            // Check if order has valid total amount
            if (!$order->total_amount || $order->total_amount <= 0) {
                return $this->errorResponse('Order has invalid total amount', 400);
            }

            // Check if payment already exists
            $existingPayment = Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();

            if ($existingPayment) {
                return $this->errorResponse('Payment request already exists for this order', 400);
            }

            DB::beginTransaction();

            try {
                // Get payment method
                $paymentMethodIdentifier = $validated['payment_method'];
                $paymentMethod = $paymentMethodIdentifier;

                // Create payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'payment_method' => $paymentMethodIdentifier,
                    'payment_provider' => $paymentMethodIdentifier,
                    'status' => 'pending',
                ]);

                // Create transaction record
                $transaction = $this->vnpayService->createTransaction($payment);

                // Handle different payment methods
                if (in_array($paymentMethodIdentifier, ['vnpay', 'momo', 'credit_card', 'debit_card'])) {
                    // Online gateway payment
                    $vnpayParams = [
                        'vnp_Amount' => $this->vnpayService->formatAmount((float) $order->total_amount),
                        'vnp_OrderInfo' => $this->vnpayService->generateOrderInfo($order->order_number),
                        'vnp_TxnRef' => $payment->id,
                        'vnp_ReturnUrl' => config('vnpay.return_url'),
                    ];

                    $paymentUrl = $this->vnpayService->createPaymentUrl($vnpayParams);

                    // Update payment with gateway response
                    $payment->update([
                        'gateway_response' => [
                            'payment_url' => $paymentUrl,
                            'gateway_params' => $vnpayParams,
                            'created_at' => now()->toISOString(),
                        ]
                    ]);

                    DB::commit();

                    return $this->successResponse([
                        'payment_id' => $payment->id,
                        'transaction_id' => $transaction->id,
                        'payment_url' => $paymentUrl,
                        'order_id' => $order->id,
                        'amount' => $order->total_amount,
                        'order_number' => $order->order_number,
                        'payment_method' => $paymentMethodIdentifier,
                    ], 'Payment request created successfully');
                } else {
                    // Offline payment methods (cash, bank_transfer)
                    $payment->update(['status' => 'pending']);
                    $transaction->update(['status' => 'pending']);

                    // For cash payments, we might want to mark as completed immediately
                    if ($paymentMethodIdentifier === 'cash') {
                        $payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                        $transaction->update([
                            'status' => 'completed',
                            'processed_at' => now(),
                        ]);

                        // Update order status
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'confirmed',
                        ]);

                        // Deduct stock
                        $this->deductStockForOrder($order);
                    }

                    DB::commit();

                    return $this->successResponse([
                        'payment_id' => $payment->id,
                        'transaction_id' => $transaction->id,
                        'order_id' => $order->id,
                        'amount' => $order->total_amount,
                        'order_number' => $order->order_number,
                        'payment_method' => $paymentMethodIdentifier,
                        'message' => $paymentMethodIdentifier === 'cash' ? 'Payment completed successfully' : 'Payment request created. Please complete the payment.',
                    ], 'Payment request created successfully');
                }
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            Log::error('Create payment failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to create payment request', $e->getMessage());
        }
    }

    /**
     * Handle VNPay callback
     */
    public function vnpayCallback(Request $request)
    {
        try {
            Log::info('VNPay callback received', $request->all());

            // Verify signature
            if (!$this->vnpayService->verifySignature($request->all())) {
                Log::error('VNPay signature verification failed', $request->all());
                return redirect($this->buildRedirectUrl($request, 'failed', 'Invalid signature'));
            }

            // Process the callback using the service
            $result = $this->vnpayService->processCallback($request->all());

            $payment = $result['payment'];
            $transaction = $result['transaction'];
            $status = $result['status'];
            $message = $result['message'];
            $gatewayTransactionId = $result['gateway_transaction_id'];

            // Update order status if payment was successful
            if ($status === 'paid') {
                $payment->order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);

                // Deduct stock
                $this->deductStockForOrder($payment->order);

                Log::info('Payment successful', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'transaction_id' => $gatewayTransactionId
                ]);
            } else {
                Log::info('Payment failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $status,
                    'message' => $message
                ]);
            }

            return redirect($this->buildRedirectUrl($request, $status, $message, [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'transaction_id' => $gatewayTransactionId,
                'amount' => $payment->amount,
            ]));
        } catch (\Exception $e) {
            Log::error('VNPay callback processing failed: ' . $e->getMessage(), $request->all());
            return redirect($this->buildRedirectUrl($request, 'error', $e->getMessage()));
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payment_id' => 'required|integer|exists:payments,id',
            ]);

            $user = Auth::user();
            $payment = Payment::with(['order', 'transactions'])
                ->where('id', $validated['payment_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return $this->errorResponse('Payment not found or access denied', 404);
            }

            // Get latest transaction
            $latestTransaction = $payment->transactions()->latest()->first();

            return $this->successResponse([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order->order_number,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_provider' => $payment->payment_provider,
                'status' => $payment->status,
                'transaction_id' => $latestTransaction?->gateway_transaction_id,
                'external_payment_id' => $payment->external_payment_id,
                'paid_at' => $payment->paid_at,
                'order_status' => $payment->order->status,
                'total_paid' => $payment->order_id ? Transaction::getTotalForOrder($payment->order_id) : 0,
                'total_refunded' => $payment->order_id ? Transaction::getTotalRefundsForOrder($payment->order_id) : 0,
                'transactions' => $payment->transactions ? $payment->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'status' => $transaction->status,
                        'amount' => $transaction->amount,
                        'gateway_transaction_id' => $transaction->gateway_transaction_id,
                        'processed_at' => $transaction->processed_at,
                        'description' => $transaction->description,
                    ];
                }) : [],
            ], 'Payment status retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get payment status', $e->getMessage());
        }
    }

    /**
     * Deduct stock for order (only when payment is successful)
     */
    private function deductStockForOrder(Order $order): void
    {
        if (!$order->orderItems) {
            return;
        }

        foreach ($order->orderItems as $orderItem) {
            if ($orderItem && $orderItem->productSize && $orderItem->quantity > 0) {
                $orderItem->productSize->decrement('quantity', $orderItem->quantity);
            }
        }
    }

    /**
     * Build redirect URL for frontend
     */
    private function buildRedirectUrl(Request $request, string $status, string $message, array $data = []): string
    {
        $baseUrl = config('vnpay.frontend_redirect_url');

        if (empty($baseUrl)) {
            Log::error('Frontend redirect URL not configured');
            $baseUrl = config('app.url', 'http://localhost:8000') . '/payment/result';
        }

        // Build query parameters
        $params = [
            'status' => $status,
            'message' => urlencode($message),
            'payment_id' => $request->vnp_TxnRef ?? null,
            'order_id' => $request->vnp_OrderInfo ? $this->extractOrderIdFromOrderInfo($request->vnp_OrderInfo) : null,
            'transaction_id' => $request->vnp_TransactionNo ?? null,
            'amount' => $request->vnp_Amount ? $this->vnpayService->parseAmount((int) $request->vnp_Amount) : null,
        ];

        // Merge with additional data
        $params = array_merge($params, $data);

        // Remove null values and encode
        $params = array_filter($params, function($value) {
            return $value !== null && $value !== '';
        });

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Extract order ID from VNPay order info
     */
    private function extractOrderIdFromOrderInfo(string $orderInfo): ?string
    {
        // Order info format: "Thanh toan don hang #{order_number}"
        // We need to extract the order number
        if (preg_match('/#([A-Za-z0-9\-_]+)/', $orderInfo, $matches)) {
            return $matches[1];
        }
        return null;
    }
}