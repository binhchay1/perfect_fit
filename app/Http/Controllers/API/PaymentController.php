<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
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
     * Create VNPay payment request
     */
    public function createVnpayPayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
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

            

            // Check if payment already exists
            $existingPayment = Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();

            if ($existingPayment) {
                return $this->errorResponse('Payment request already exists for this order', 400);
            }

            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'payment_method' => 'vnpay',
                    'status' => 'pending',
                ]);

                // Add payment log
                $payment->addLog('created', 'Payment request created');

                // Create VNPay payment URL
                $vnpayParams = [
                    'vnp_Amount' => $this->vnpayService->formatAmount($order->total_amount),
                    'vnp_OrderInfo' => $this->vnpayService->generateOrderInfo($order->order_number),
                    'vnp_TxnRef' => $payment->id,
                    'vnp_ReturnUrl' => config('vnpay.return_url'),
                ];

                $paymentUrl = $this->vnpayService->createPaymentUrl($vnpayParams);

                // Update payment with gateway response
                $payment->update([
                    'gateway_response' => [
                        'payment_url' => $paymentUrl,
                        'vnpay_params' => $vnpayParams,
                        'created_at' => now()->toISOString(),
                    ]
                ]);

                DB::commit();

                return $this->successResponse([
                    'payment_id' => $payment->id,
                    'payment_url' => $paymentUrl,
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'order_number' => $order->order_number,
                ], 'Payment request created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return $this->errorResponse('Validation error', 422, $ve->errors());
        } catch (\Exception $e) {
            Log::error('Create VNPay payment failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to create payment request', $e->getMessage());
        }
    }

    /**
     * Handle VNPay callback
     */
    public function vnpayCallback(Request $request): JsonResponse
    {
        try {
            Log::info('VNPay callback received', $request->all());

            // Verify signature
            if (!$this->vnpayService->verifySignature($request->all())) {
                Log::error('VNPay signature verification failed', $request->all());
                return $this->errorResponse('Invalid signature', 400);
            }

            $vnpResponseCode = $request->vnp_ResponseCode;
            $vnpTxnRef = $request->vnp_TxnRef;
            $vnpAmount = $request->vnp_Amount;
            $vnpTransactionNo = $request->vnp_TransactionNo;

            // Find payment record
            $payment = Payment::find($vnpTxnRef);
            if (!$payment) {
                Log::error('Payment not found', ['payment_id' => $vnpTxnRef]);
                return $this->errorResponse('Payment not found', 404);
            }

            // Check amount
            $expectedAmount = $this->vnpayService->formatAmount($payment->amount);
            if ($vnpAmount != $expectedAmount) {
                Log::error('Amount mismatch', [
                    'expected' => $expectedAmount,
                    'received' => $vnpAmount,
                    'payment_id' => $payment->id
                ]);
                return $this->errorResponse('Amount mismatch', 400);
            }

            DB::beginTransaction();

            try {
                // Update payment with gateway response
                $payment->update([
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        [
                            'callback_response' => $request->all(),
                            'callback_received_at' => now()->toISOString(),
                        ]
                    )
                ]);

                // Process payment result
                $paymentStatus = $this->vnpayService->getPaymentStatus($vnpResponseCode);
                $paymentMessage = $this->vnpayService->getPaymentMessage($vnpResponseCode);

                if ($paymentStatus === 'paid') {
                    // Payment successful
                    $payment->markAsPaid($vnpTransactionNo);
                    $payment->addLog('paid', $paymentMessage, $request->all());

                    // Update order status
                    $payment->order->update([
                        'payment_status' => 'paid',
                        'status' => 'confirmed',
                    ]);

                    // Deduct stock (only when payment is successful)
                    $this->deductStockForOrder($payment->order);

                    Log::info('Payment successful', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'transaction_id' => $vnpTransactionNo
                    ]);
                } else {
                    // Payment failed
                    $payment->markAsFailed();
                    $payment->addLog('failed', $paymentMessage, $request->all());

                    Log::info('Payment failed', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'response_code' => $vnpResponseCode,
                        'message' => $paymentMessage
                    ]);
                }

                DB::commit();

                return $this->successResponse([
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => $paymentStatus,
                    'message' => $paymentMessage,
                    'transaction_id' => $vnpTransactionNo,
                ], 'Payment callback processed successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('VNPay callback processing failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to process payment callback', $e->getMessage());
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
            $payment = Payment::with('order')
                ->where('id', $validated['payment_id'])
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$payment) {
                return $this->errorResponse('Payment not found or access denied', 404);
            }

            return $this->successResponse([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'paid_at' => $payment->paid_at,
                'order_status' => $payment->order->status,
                'order_number' => $payment->order->order_number,
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
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->productSize) {
                $orderItem->productSize->decrement('quantity', $orderItem->quantity);
            }
        }
    }
}