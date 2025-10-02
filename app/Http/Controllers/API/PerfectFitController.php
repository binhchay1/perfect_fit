<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PerfectFitService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Perfect Fit AI",
 *     description="AI-powered size recommendation system"
 * )
 */
final class PerfectFitController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PerfectFitService $perfectFitService
    ) {}

    /**
     * @OA\Get(
     *     path="/user/body-measurements",
     *     summary="Get user body measurements",
     *     description="Retrieve saved body measurements for the authenticated user",
     *     tags={"Perfect Fit AI"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Measurements retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="height", type="number", example=158),
     *                 @OA\Property(property="weight", type="number", example=47),
     *                 @OA\Property(property="chest", type="number", example=90),
     *                 @OA\Property(property="waist", type="number", example=60),
     *                 @OA\Property(property="hips", type="number", example=90)
     *             )
     *         )
     *     )
     * )
     */
    public function getMeasurements(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $measurements = $this->perfectFitService->getUserMeasurements($user);

            if (!$measurements) {
                return $this->successResponse(null, 'No measurements found');
            }

            return $this->successResponse([
                'gender' => $measurements->gender,
                'height' => $measurements->height,
                'weight' => $measurements->weight,
                'chest' => $measurements->chest,
                'waist' => $measurements->waist,
                'hips' => $measurements->hips,
                'thigh' => $measurements->thigh,
                'shoulder' => $measurements->shoulder,
                'arm_length' => $measurements->arm_length,
                'leg_length' => $measurements->leg_length,
                'height_unit' => $measurements->height_unit,
                'weight_unit' => $measurements->weight_unit,
                'measurement_unit' => $measurements->measurement_unit,
                'preferred_fit' => $measurements->preferred_fit,
            ], 'Measurements retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve measurements', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/user/body-measurements",
     *     summary="Save body measurements",
     *     description="Save or update user's body measurements",
     *     tags={"Perfect Fit AI"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"gender", "height", "weight"},
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "unisex"}, example="female"),
     *             @OA\Property(property="height", type="number", example=158),
     *             @OA\Property(property="weight", type="number", example=47),
     *             @OA\Property(property="chest", type="number", example=90),
     *             @OA\Property(property="waist", type="number", example=60),
     *             @OA\Property(property="hips", type="number", example=90),
     *             @OA\Property(property="preferred_fit", type="string", enum={"tight", "regular", "loose"}, example="regular"),
     *             @OA\Property(property="height_unit", type="string", enum={"cm", "in"}, example="cm"),
     *             @OA\Property(property="weight_unit", type="string", enum={"kg", "lbs"}, example="kg")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Measurements saved successfully")
     * )
     */
    public function saveMeasurements(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'gender' => 'required|in:male,female,unisex',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
                'chest' => 'nullable|numeric|min:0',
                'waist' => 'nullable|numeric|min:0',
                'hips' => 'nullable|numeric|min:0',
                'thigh' => 'nullable|numeric|min:0',
                'shoulder' => 'nullable|numeric|min:0',
                'arm_length' => 'nullable|numeric|min:0',
                'leg_length' => 'nullable|numeric|min:0',
                'height_unit' => 'nullable|in:cm,in',
                'weight_unit' => 'nullable|in:kg,lbs',
                'measurement_unit' => 'nullable|in:cm,in',
                'preferred_fit' => 'nullable|in:tight,regular,loose',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $measurements = $this->perfectFitService->saveMeasurements($user, $request->all());

            return $this->successResponse([
                'id' => $measurements->id,
                'gender' => $measurements->gender,
                'height' => $measurements->height,
                'weight' => $measurements->weight,
            ], 'Measurements saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save measurements', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/size-recommend-from-image",
     *     summary="Get size recommendation from AI (Image)",
     *     description="Upload user image and get AI-powered size recommendation",
     *     tags={"Perfect Fit AI"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image"},
     *             @OA\Property(property="image", type="string", description="Base64 encoded image or image URL"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "unisex"}),
     *             @OA\Property(property="height", type="number", example=158),
     *             @OA\Property(property="weight", type="number", example=47),
     *             @OA\Property(property="fit_preference", type="string", enum={"tight", "regular", "loose"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Size recommendation from AI",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommended_size", type="string", example="M"),
     *                 @OA\Property(property="confidence", type="string", example="high"),
     *                 @OA\Property(property="fit_type", type="string", example="Comfortable"),
     *                 @OA\Property(property="measurements", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function recommendFromImage(Request $request, int $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|string',
                'gender' => 'nullable|in:male,female,unisex',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'fit_preference' => 'nullable|in:tight,regular,loose',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $result = $this->perfectFitService->recommendSizeFromImage($request->all(), $productId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'AI service error', 500);
            }

            return $this->successResponse($result, 'Size recommendation retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get size recommendation', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/size-recommend",
     *     summary="Get size recommendation from saved measurements",
     *     description="Get AI-powered size recommendation using saved body measurements",
     *     tags={"Perfect Fit AI"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Size recommendation from saved measurements",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommended_size", type="string", example="S"),
     *                 @OA\Property(property="confidence", type="string", example="high"),
     *                 @OA\Property(property="fit_type", type="string", example="Fit Your Body")
     *             )
     *         )
     *     )
     * )
     */
    public function recommendFromMeasurements(Request $request, int $productId): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->perfectFitService->recommendSizeFromMeasurements($user, $productId);

            if (!$result['has_measurements']) {
                return $this->errorResponse($result['message'], 400);
            }

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'AI service error', 500);
            }

            return $this->successResponse($result, 'Size recommendation retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get size recommendation', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/user/body-measurements",
     *     summary="Delete body measurements",
     *     description="Delete user's saved body measurements",
     *     tags={"Perfect Fit AI"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(response=200, description="Measurements deleted successfully")
     * )
     */
    public function deleteMeasurements(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $this->perfectFitService->deleteMeasurements($user);

            return $this->successResponse(null, 'Measurements deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete measurements', $e->getMessage());
        }
    }
}

