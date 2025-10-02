<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ProductReviewService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Product Reviews",
 *     description="Product review and rating operations"
 * )
 */
final class ProductReviewController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProductReviewService $reviewService
    ) {}

    /**
     * @OA\Get(
     *     path="/products/{productId}/reviews",
     *     summary="Get product reviews",
     *     description="Get all approved reviews for a product",
     *     tags={"Product Reviews"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        try {
            $product = \App\Models\Product::findOrFail($productId);
            $reviews = $this->reviewService->getProductReviews($product);
            $stats = $this->reviewService->getProductRatingStats($productId);

            $reviewsData = $reviews->map(function ($review) use ($request) {
                $userReaction = null;
                if (Auth::check()) {
                    $reaction = $review->reactions()->where('user_id', Auth::id())->first();
                    $userReaction = $reaction ? $reaction->reaction_type : null;
                }

                return [
                    'id' => $review->id,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'avatar' => $review->user->profile_photo_path,
                    ],
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'images' => $review->images,
                    'likes_count' => $review->likes_count,
                    'dislikes_count' => $review->dislikes_count,
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'user_reaction' => $userReaction,
                    'created_at' => $review->created_at,
                ];
            });

            return $this->successResponse([
                'reviews' => $reviewsData,
                'stats' => $stats,
            ], 'Reviews retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reviews', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/reviews",
     *     summary="Create product review",
     *     description="Add a review for a product",
     *     tags={"Product Reviews"},
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
     *             required={"rating", "comment"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Great product!"),
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Review created successfully")
     * )
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
                'order_id' => 'nullable|exists:orders,id',
                'images' => 'nullable|array|max:5',
                'images.*' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();

            if (!$this->reviewService->canUserReview($user, $productId)) {
                return $this->errorResponse('You have already reviewed this product', 400);
            }

            $review = $this->reviewService->createReview($user, $productId, $request->all());

            return $this->successResponse([
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ], 'Review created successfully', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create review', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/reviews/{id}",
     *     summary="Update review",
     *     description="Update user's own review",
     *     tags={"Product Reviews"},
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
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Review updated successfully")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'comment' => 'sometimes|required|string|max:1000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $review = $this->reviewService->updateReview($user, $id, $request->all());

            if (!$review) {
                return $this->errorResponse('Review not found or unauthorized', 404);
            }

            return $this->successResponse([
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ], 'Review updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update review', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/reviews/{id}",
     *     summary="Delete review",
     *     description="Delete user's own review",
     *     tags={"Product Reviews"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Review deleted successfully")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->reviewService->deleteReview($user, $id);

            if (!$result) {
                return $this->errorResponse('Review not found or unauthorized', 404);
            }

            return $this->successResponse(null, 'Review deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete review', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/reviews/{id}/react",
     *     summary="React to review",
     *     description="Like or dislike a review",
     *     tags={"Product Reviews"},
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
     *             required={"reaction_type"},
     *             @OA\Property(property="reaction_type", type="string", enum={"like", "dislike"}, example="like")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reaction added successfully")
     * )
     */
    public function react(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reaction_type' => 'required|in:like,dislike',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $result = $this->reviewService->toggleReaction($user, $id, $request->reaction_type);

            if (!$result) {
                return $this->errorResponse('Review not found', 404);
            }

            return $this->successResponse([
                'likes_count' => $result['review']->likes_count,
                'dislikes_count' => $result['review']->dislikes_count,
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to react to review', $e->getMessage());
        }
    }
}

