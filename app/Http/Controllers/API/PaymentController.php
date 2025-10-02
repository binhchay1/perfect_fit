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

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment processing operations"
 * )
 */
class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $vnpayService;

    public function __construct(VnpayService $vnpayService)
    {
        $this->vnpayService = $vnpayService;
    }

    /**
     * @OA\Post(
     *     path="/payment/create",
     *     summary="Create payment request",
     *     description="Create a payment request for an order with various payment methods",
     *     tags={"Payments"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "payment_method"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "vnpay", "momo", "bank_transfer", "credit_card", "debit_card"}, example="vnpay")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment request created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="transaction_id", type="integer", example=1),
     *                 @OA\Property(property="payment_url", type="string", example="https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=4500000&vnp_TxnRef=1..."),
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or order status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order is not in pending status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found or access denied")
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

                // Log payment creation
                $payment->addLog('pending', 'Payment created for order', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethodIdentifier,
                    'amount' => $order->total_amount,
                ]);

                // Generate session token for online gateway payments (30 minutes expiration)
                if (in_array($paymentMethodIdentifier, ['vnpay', 'momo', 'credit_card', 'debit_card'])) {
                    $payment->generateSessionToken(30);
                }

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

                    // Log gateway payment URL generation
                    $payment->addLog('gateway_url_generated', 'Payment gateway URL generated', [
                        'payment_url' => $paymentUrl,
                        'gateway' => $paymentMethodIdentifier,
                        'amount' => $order->total_amount,
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

                        // Log payment completion
                        $payment->addLog('paid', 'Cash payment completed successfully', [
                            'transaction_id' => $transaction->id,
                            'order_status_updated' => true,
                        ]);
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
     * @OA\Get(
     *     path="/payment/vnpay/callback",
     *     summary="VNPay callback",
     *     description="Handle payment callback from VNPay gateway (public endpoint)",
     *     tags={"Payments"},
     *     @OA\Parameter(
     *         name="vnp_Amount",
     *         in="query",
     *         description="Payment amount",
     *         required=true,
     *         @OA\Schema(type="string", example="4500000")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_BankCode",
     *         in="query",
     *         description="Bank code",
     *         required=false,
     *         @OA\Schema(type="string", example="NCB")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_BankTranNo",
     *         in="query",
     *         description="Bank transaction number",
     *         required=false,
     *         @OA\Schema(type="string", example="VNP12345678")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_CardType",
     *         in="query",
     *         description="Card type",
     *         required=false,
     *         @OA\Schema(type="string", example="ATM")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_OrderInfo",
     *         in="query",
     *         description="Order information",
     *         required=true,
     *         @OA\Schema(type="string", example="Thanh toan don hang #ORD-20241226-ABC123")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_PayDate",
     *         in="query",
     *         description="Payment date",
     *         required=true,
     *         @OA\Schema(type="string", example="20241226103000")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_ResponseCode",
     *         in="query",
     *         description="Response code (00 = success)",
     *         required=true,
     *         @OA\Schema(type="string", example="00")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_TmnCode",
     *         in="query",
     *         description="Terminal code",
     *         required=true,
     *         @OA\Schema(type="string", example="VNPAYMENT")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_TransactionNo",
     *         in="query",
     *         description="Transaction number",
     *         required=true,
     *         @OA\Schema(type="string", example="12345678")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_TxnRef",
     *         in="query",
     *         description="Transaction reference",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="vnp_SecureHash",
     *         in="query",
     *         description="Security hash",
     *         required=true,
     *         @OA\Schema(type="string", example="abc123...")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to frontend with payment result"
     *     )
     * )
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

                // Log payment success
                $payment->addLog('paid', 'Payment successful via gateway callback', [
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'gateway' => 'vnpay',
                    'order_status_updated' => true,
                ]);

                Log::info('Payment successful', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'transaction_id' => $gatewayTransactionId
                ]);
            } else {
                // Log payment failure
                $payment->addLog('failed', 'Payment failed via gateway callback', [
                    'gateway' => 'vnpay',
                    'failure_reason' => $message,
                ]);

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
     * @OA\Get(
     *     path="/payment/status",
     *     summary="Get payment status",
     *     description="Get detailed status information for a specific payment",
     *     tags={"Payments"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="payment_id",
     *         in="query",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment status retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                 @OA\Property(property="payment_provider", type="string", example="vnpay"),
     *                 @OA\Property(property="status", type="string", example="paid"),
     *                 @OA\Property(property="transaction_id", type="string", example="VNP123456789"),
     *                 @OA\Property(property="external_payment_id", type="string", example="12345678"),
     *                 @OA\Property(property="paid_at", type="string", format="date-time", example="2024-12-26T10:30:00Z"),
     *                 @OA\Property(property="order_status", type="string", example="confirmed"),
     *                 @OA\Property(property="total_paid", type="number", format="float", example=450.00),
     *                 @OA\Property(property="total_refunded", type="number", format="float", example=0.00),
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="payment"),
     *                         @OA\Property(property="status", type="string", example="completed"),
     *                         @OA\Property(property="amount", type="number", format="float", example=450.00),
     *                         @OA\Property(property="gateway_transaction_id", type="string", example="VNP123456789"),
     *                         @OA\Property(property="processed_at", type="string", format="date-time", example="2024-12-26T10:30:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found or access denied")
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
     * @OA\Get(
     *     path="/orders/{order_id}/payment-link",
     *     summary="Get payment link",
     *     description="Get or renew payment link for an order (handles interrupted payment flows)",
     *     tags={"Payments"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment link is still valid"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="valid"),
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="payment_url", type="string", example="https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?vnp_Amount=4500000&vnp_TxnRef=1..."),
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=450.00),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20241226-ABC123"),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2024-12-26T11:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid order status or payment method",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order is not in pending status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found or access denied")
     *         )
     *     )
     * )
     */
    public function getPaymentLink(Request $request, $orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $order = Order::where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return $this->errorResponse('Order not found or access denied', 404);
            }

            // Check if order is in pending status
            if ($order->status !== 'pending') {
                return $this->errorResponse('Order is not in pending status', 400);
            }

            // Get the latest pending payment for this order
            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$payment) {
                return $this->errorResponse('No pending payment found for this order', 404);
            }

            // Check payment status and session validity
            if ($payment->status === 'paid') {
                return $this->successResponse([
                    'status' => 'paid',
                    'message' => 'Payment has already been completed',
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                ], 'Payment already completed');
            }

            // Check if it's an online gateway payment (VNPay, MoMo, etc.)
            $isOnlineGateway = in_array($payment->payment_method, ['vnpay', 'momo', 'credit_card', 'debit_card']);

            if (!$isOnlineGateway) {
                return $this->errorResponse('Payment method does not support link recovery', 400);
            }

            // Handle different payment states
            if ($payment->isSessionValid()) {
                // Session is valid, return existing payment URL
                return $this->successResponse([
                    'status' => 'valid',
                    'payment_id' => $payment->id,
                    'payment_url' => $payment->getPaymentUrl(),
                    'order_id' => $order->id,
                    'amount' => $payment->amount,
                    'order_number' => $order->order_number,
                    'payment_method' => $payment->payment_method,
                    'expires_at' => $payment->session_expires_at,
                ], 'Payment link is still valid');
            } elseif ($payment->isSessionExpired()) {
                // Session expired, create new payment session
                $payment->markSessionAsUsed(); // This will log the session usage

                // Create new payment record with new session
                $newPayment = $this->createNewPaymentSession($order, $payment->payment_method);

                // Log new payment creation
                $newPayment->addLog('pending', 'New payment session created due to expiration', [
                    'original_payment_id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'amount' => $order->total_amount,
                ]);

                return $this->successResponse([
                    'status' => 'renewed',
                    'payment_id' => $newPayment->id,
                    'payment_url' => $newPayment->getPaymentUrl(),
                    'order_id' => $order->id,
                    'amount' => $newPayment->amount,
                    'order_number' => $order->order_number,
                    'payment_method' => $newPayment->payment_method,
                    'expires_at' => $newPayment->session_expires_at,
                ], 'Payment link has been renewed due to expiration');
            } else {
                return $this->errorResponse('Payment session is invalid or expired', 400);
            }

        } catch (\Exception $e) {
            Log::error('Get payment link failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to get payment link', $e->getMessage());
        }
    }

    /**
     * Create new payment session for order
     */
    private function createNewPaymentSession(Order $order, string $paymentMethod): Payment
    {
        // Create payment record
        $payment = Payment::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'payment_method' => $paymentMethod,
            'payment_provider' => $paymentMethod,
            'status' => 'pending',
        ]);

        // Log payment creation
        $payment->addLog('pending', 'Payment session renewed', [
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => $order->total_amount,
        ]);

        // Generate session token for online gateway payments
        if (in_array($paymentMethod, ['vnpay', 'momo', 'credit_card', 'debit_card'])) {
            $payment->generateSessionToken(30); // 30 minutes expiration
        }

        // Create transaction record
        $this->vnpayService->createTransaction($payment);

        // Generate payment URL for VNPay (or other gateways)
        if (in_array($paymentMethod, ['vnpay', 'momo', 'credit_card', 'debit_card'])) {
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
        }

        return $payment;
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