<?php

namespace App\Services;

use App\Enums\ProductReview as ProductReviewEnum;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Repositories\ProductReviewRepository;
use Illuminate\Database\Eloquent\Collection;

final class ProductReviewService
{
    public function __construct(
        private readonly ProductReviewRepository $productReviewRepository
    ) {}

    public function getProductReviews(Product $product): Collection
    {
        return $this->productReviewRepository->getProductReviews($product);
    }

    public function createReview(User $user, int $productId, array $data): ProductReview
    {
        $reviewData = [
            'user_id' => $user->id,
            'product_id' => $productId,
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'images' => $data['images'] ?? null,
            'is_verified_purchase' => $data['order_id'] ? ProductReviewEnum::VERIFIED_PURCHASE : ProductReviewEnum::NOT_VERIFIED_PURCHASE,
            'is_approved' => ProductReviewEnum::APPROVED,
        ];

        return $this->productReviewRepository->createReview($reviewData);
    }

    public function updateReview(User $user, int $reviewId, array $data): ?ProductReview
    {
        $review = $this->productReviewRepository->getById($reviewId);

        if (!$review || $review->user_id !== $user->id) {
            return null;
        }

        $this->productReviewRepository->updateReview($review, $data);
        return $review->fresh();
    }

    public function deleteReview(User $user, int $reviewId): ?array
    {
        $review = $this->productReviewRepository->getById($reviewId);

        if (!$review || $review->user_id !== $user->id) {
            return null;
        }

        $this->productReviewRepository->deleteReview($review);
        return ['success' => true];
    }

    public function toggleReaction(User $user, int $reviewId, string $reactionType): ?array
    {
        $review = $this->productReviewRepository->getById($reviewId);

        if (!$review) {
            return null;
        }

        $existingReaction = $this->productReviewRepository->getUserReaction($reviewId, $user->id);

        if ($existingReaction) {
            if ($existingReaction->reaction_type === $reactionType) {
                $this->productReviewRepository->removeReaction($existingReaction);
                $message = 'Reaction removed';
            } else {
                $existingReaction->update(['reaction_type' => $reactionType]);
                $message = 'Reaction updated';
            }
        } else {
            $this->productReviewRepository->addReaction($reviewId, $user->id, $reactionType);
            $message = 'Reaction added';
        }

        $this->productReviewRepository->updateReactionCounts($review);

        return [
            'review' => $review->fresh(),
            'message' => $message,
        ];
    }

    public function getProductRatingStats(int $productId): array
    {
        $avgRating = $this->productReviewRepository->getAverageRating($productId);
        $reviewsCount = $this->productReviewRepository->getReviewsCount($productId);

        return [
            'average_rating' => round($avgRating, 1),
            'total_reviews' => $reviewsCount,
        ];
    }

    public function canUserReview(User $user, int $productId): bool
    {
        $existingReview = $this->productReviewRepository->getUserReview($user, $productId);
        return $existingReview === null;
    }
}

