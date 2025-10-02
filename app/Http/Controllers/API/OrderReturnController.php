<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\OrderReturnService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Order Returns",
 *     description="Order return and refund request operations"
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
     *     path="/returns",
     *     summary="Get user's return requests",
     *     description="Retrieve all return requests for the authenticated user",
     *     tags={"Order Returns"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Returns retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $returns = $this->returnService->getUserReturns($user);

            $returnsData = $returns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'return_code' => $return->return_code,
                    'order_id' => $return->order_id,
                    'return_type' => $return->return_type,
                    'reason' => $return->reason,
                    'description' => $return->description,
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
     * @OA\Post(
     *     path="/orders/{orderId}/return",
     *     summary="Create return request",
     *     description="Create a return/refund request for an order",
     *     tags={"Order Returns"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"return_type", "reason", "description"},
     *             @OA\Property(property="return_type", type="string", enum={"return", "refund", "exchange"}),
     *             @OA\Property(property="reason", type="string", enum={"damaged", "wrong_item", "wrong_size", "not_as_described", "quality_issue", "changed_mind", "other"}),
     *             @OA\Property(property="description", type="string", example="Product is too small"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Return request created successfully")
     * )
     */
    public function store(Request $request, int $orderId): JsonResponse
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['order_id' => $orderId]), [
                'order_id' => 'required|exists:orders,id',
                'return_type' => 'required|in:return,refund,exchange',
                'reason' => 'required|in:damaged,wrong_item,wrong_size,not_as_described,quality_issue,changed_mind,other',
                'description' => 'required|string|max:1000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $data = $request->all();
            $data['order_id'] = $orderId;

            $return = $this->returnService->createReturn($user, $data);

            if (!$return) {
                return $this->errorResponse('Cannot create return request. Order not found or return already exists', 400);
            }

            return $this->successResponse([
                'id' => $return->id,
                'return_code' => $return->return_code,
                'status' => $return->status,
            ], 'Return request created successfully', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create return request', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/returns/{returnCode}",
     *     summary="Get return request detail",
     *     description="Get detailed information about a return request",
     *     tags={"Order Returns"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="returnCode",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Return details retrieved successfully")
     * )
     */
    public function show(Request $request, string $returnCode): JsonResponse
    {
        try {
            $return = $this->returnService->getReturnDetail($returnCode);

            if (!$return) {
                return $this->errorResponse('Return request not found', 404);
            }

            $user = Auth::user();
            if ($return->user_id !== $user->id && $user->role !== 'admin') {
                return $this->errorResponse('Unauthorized', 403);
            }

            return $this->successResponse([
                'id' => $return->id,
                'return_code' => $return->return_code,
                'order_id' => $return->order_id,
                'return_type' => $return->return_type,
                'reason' => $return->reason,
                'description' => $return->description,
                'images' => $return->images,
                'status' => $return->status,
                'admin_notes' => $return->admin_notes,
                'refund_amount' => $return->refund_amount,
                'created_at' => $return->created_at,
                'approved_at' => $return->approved_at,
                'completed_at' => $return->completed_at,
            ], 'Return details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve return details', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/returns/{id}/cancel",
     *     summary="Cancel return request",
     *     description="Cancel a pending return request",
     *     tags={"Order Returns"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Return request cancelled successfully")
     * )
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->returnService->cancelReturn($user, $id);

            if (!$result) {
                return $this->errorResponse('Return request not found', 404);
            }

            if (isset($result['error'])) {
                return $this->errorResponse($result['error'], 400);
            }

            return $this->successResponse(null, 'Return request cancelled successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel return request', $e->getMessage());
        }
    }
}

