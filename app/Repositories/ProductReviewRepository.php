<?php

namespace App\Repositories;

use App\Enums\ProductReview as ProductReviewEnum;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReaction;
use App\Models\User;

class ProductReviewRepository extends BaseRepository
{
    public function model()
    {
        return ProductReview::class;
    }

    public function getProductReviews(Product $product)
    {
        return $this->model
            ->where('product_id', $product->id)
            ->where('is_approved', ProductReviewEnum::APPROVED)
            ->with(['user', 'reactions'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserReview(User $user, int $productId)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
    }

    public function createReview(array $data)
    {
        return $this->model->create($data);
    }

    public function updateReview(ProductReview $review, array $data)
    {
        return $review->update($data);
    }

    public function deleteReview(ProductReview $review)
    {
        return $review->delete();
    }

    public function getUserReaction(int $reviewId, int $userId)
    {
        return ReviewReaction::where('review_id', $reviewId)
            ->where('user_id', $userId)
            ->first();
    }

    public function addReaction(int $reviewId, int $userId, string $reactionType)
    {
        return ReviewReaction::updateOrCreate(
            ['review_id' => $reviewId, 'user_id' => $userId],
            ['reaction_type' => $reactionType]
        );
    }

    public function removeReaction(ReviewReaction $reaction)
    {
        return $reaction->delete();
    }

    public function updateReactionCounts(ProductReview $review)
    {
        $likesCount = $review->reactions()->where('reaction_type', ProductReviewEnum::REACTION_LIKE)->count();
        $dislikesCount = $review->reactions()->where('reaction_type', ProductReviewEnum::REACTION_DISLIKE)->count();

        return $review->update([
            'likes_count' => $likesCount,
            'dislikes_count' => $dislikesCount,
        ]);
    }

    public function getAverageRating(int $productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('is_approved', ProductReviewEnum::APPROVED)
            ->avg('rating');
    }

    public function getReviewsCount(int $productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('is_approved', ProductReviewEnum::APPROVED)
            ->count();
    }
}

