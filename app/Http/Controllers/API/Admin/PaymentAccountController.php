<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentAccountService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Admin - Payment Accounts",
 *     description="Admin payment account management operations"
 * )
 */
final class PaymentAccountController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PaymentAccountService $paymentAccountService
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/payment-accounts",
     *     summary="Get all payment accounts",
     *     description="Get all payment accounts for the shop/admin",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Payment accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment accounts retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="bank_name", type="string", example="Vietcombank"),
     *                     @OA\Property(property="account_number", type="string", example="**** **** 6557"),
     *                     @OA\Property(property="account_holder_name", type="string", example="Nguyễn Văn A"),
     *                     @OA\Property(property="is_default", type="boolean", example=true),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $accounts = $this->paymentAccountService->getUserAccounts($user);

            $accountsData = $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_number' => $this->maskAccountNumber($account->account_number),
                    'account_number_full' => $account->account_number,
                    'account_holder_name' => $account->account_holder_name,
                    'bank_branch' => $account->bank_branch,
                    'account_type' => $account->account_type,
                    'is_default' => $account->is_default,
                    'is_active' => $account->is_active,
                    'created_at' => $account->created_at,
                ];
            });

            return $this->successResponse($accountsData, 'Payment accounts retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payment accounts', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/payment-accounts",
     *     summary="Create payment account",
     *     description="Create a new payment account for receiving payments",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bank_name", "account_number", "account_holder_name"},
     *             @OA\Property(property="bank_name", type="string", example="Vietcombank"),
     *             @OA\Property(property="account_number", type="string", example="1234567890"),
     *             @OA\Property(property="account_holder_name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="bank_branch", type="string", example="Chi nhánh HCM"),
     *             @OA\Property(property="swift_code", type="string", example="BFTVVNVX"),
     *             @OA\Property(property="account_type", type="string", enum={"savings", "checking", "business"}, example="savings"),
     *             @OA\Property(property="is_default", type="boolean", example=false),
     *             @OA\Property(property="notes", type="string", example="Tài khoản chính")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment account created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:50',
                'account_holder_name' => 'required|string|max:255',
                'bank_branch' => 'nullable|string|max:255',
                'swift_code' => 'nullable|string|max:20',
                'account_type' => 'nullable|in:savings,checking,business',
                'is_default' => 'nullable|boolean',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $account = $this->paymentAccountService->createAccount($user, $request->all());

            return $this->successResponse([
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $this->maskAccountNumber($account->account_number),
                'account_holder_name' => $account->account_holder_name,
                'is_default' => $account->is_default,
            ], 'Payment account created successfully', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create payment account', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/payment-accounts/{id}",
     *     summary="Update payment account",
     *     description="Update payment account information",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bank_name", type="string"),
     *             @OA\Property(property="account_number", type="string"),
     *             @OA\Property(property="account_holder_name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Payment account updated successfully")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_name' => 'sometimes|required|string|max:255',
                'account_number' => 'sometimes|required|string|max:50',
                'account_holder_name' => 'sometimes|required|string|max:255',
                'bank_branch' => 'nullable|string|max:255',
                'swift_code' => 'nullable|string|max:20',
                'account_type' => 'nullable|in:savings,checking,business',
                'is_default' => 'nullable|boolean',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $account = $this->paymentAccountService->updateAccount($user, $id, $request->all());

            if (!$account) {
                return $this->errorResponse('Payment account not found', 404);
            }

            return $this->successResponse([
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $this->maskAccountNumber($account->account_number),
                'account_holder_name' => $account->account_holder_name,
                'is_default' => $account->is_default,
            ], 'Payment account updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update payment account', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/payment-accounts/{id}",
     *     summary="Delete payment account",
     *     description="Delete a payment account (cannot delete default account)",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payment account deleted successfully")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->paymentAccountService->deleteAccount($user, $id);

            if (!$result) {
                return $this->errorResponse('Payment account not found', 404);
            }

            if (isset($result['error'])) {
                return $this->errorResponse($result['error'], 400);
            }

            return $this->successResponse(null, 'Payment account deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete payment account', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/payment-accounts/{id}/set-default",
     *     summary="Set default payment account",
     *     description="Set a payment account as default for receiving payments",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Default payment account set successfully")
     * )
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $account = $this->paymentAccountService->setDefault($user, $id);

            if (!$account) {
                return $this->errorResponse('Payment account not found', 404);
            }

            return $this->successResponse([
                'id' => $account->id,
                'is_default' => $account->is_default,
            ], 'Default payment account set successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to set default account', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/payment-accounts/{id}/toggle-status",
     *     summary="Toggle payment account status",
     *     description="Activate or deactivate a payment account",
     *     tags={"Admin - Payment Accounts"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Account status toggled successfully")
     * )
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->paymentAccountService->toggleStatus($user, $id);

            if (!$result) {
                return $this->errorResponse('Payment account not found', 404);
            }

            return $this->successResponse([
                'id' => $result['account']->id,
                'is_active' => $result['account']->is_active,
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to toggle account status', $e->getMessage());
        }
    }

    private function maskAccountNumber(string $accountNumber): string
    {
        if (strlen($accountNumber) <= 4) {
            return $accountNumber;
        }

        $visibleDigits = 4;
        $maskedPart = str_repeat('*', strlen($accountNumber) - $visibleDigits);
        $visiblePart = substr($accountNumber, -$visibleDigits);

        return $maskedPart . ' ' . $visiblePart;
    }
}

