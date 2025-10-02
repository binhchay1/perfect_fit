<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderReturnService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Admin - Order Returns",
 *     description="Admin order return management operations"
 * )
 */
final class OrderReturnController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly OrderReturnService $returnService
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/returns",
     *     summary="Get all return requests",
     *     description="Get all return requests (admin only)",
     *     tags={"Admin - Order Returns"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Returns retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $returns = $this->returnService->getAllReturns();

            $returnsData = $returns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_code' => $return->return_code,
                    'order_id' => $return->order_id,
                    'customer_name' => $return->user->name,
                    'customer_email' => $return->user->email,
                    'return_type' => $return->return_type,
                    'reason' => $return->reason,
                    'status' => $return->status,
                    'refund_amount' => $return->refund_amount,
                    'created_at' => $return->created_at,
                ];
            });

            return $this->successResponse($returnsData, 'Returns retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve returns', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/returns/{id}/status",
     *     summary="Update return status",
     *     description="Update the status of a return request",
     *     tags={"Admin - Order Returns"},
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
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "processing", "completed", "cancelled"}),
     *             @OA\Property(property="admin_notes", type="string"),
     *             @OA\Property(property="refund_amount", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Return status updated successfully")
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected,processing,completed,cancelled',
                'admin_notes' => 'nullable|string|max:1000',
                'refund_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $additionalData = [
                'admin_notes' => $request->admin_notes,
                'refund_amount' => $request->refund_amount,
            ];

            $return = $this->returnService->updateReturnStatus($id, $request->status, $additionalData);

            if (!$return) {
                return $this->errorResponse('Return request not found', 404);
            }

            return $this->successResponse([
                'id' => $return->id,
                'status' => $return->status,
                'admin_notes' => $return->admin_notes,
                'refund_amount' => $return->refund_amount,
            ], 'Return status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update return status', $e->getMessage());
        }
    }
}

