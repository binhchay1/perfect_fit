<?php

namespace App\Services;

use App\Enums\OrderReturn as OrderReturnEnum;
use App\Models\OrderReturn;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\OrderReturnRepository;
use Illuminate\Database\Eloquent\Collection;

final class OrderReturnService
{
    public function __construct(
        private readonly OrderReturnRepository $returnRepository,
        private readonly OrderRepository $orderRepository
    ) {}

    public function getUserReturns(User $user): Collection
    {
        return $this->returnRepository->getUserReturns($user);
    }

    public function getAllReturns(): Collection
    {
        return $this->returnRepository->getAllReturns();
    }

    public function createReturn(User $user, array $data): ?OrderReturn
    {
        $order = $this->orderRepository->getById($data['order_id']);

        if (!$order || $order->user_id !== $user->id) {
            return null;
        }

        $existingReturn = $this->returnRepository->findByOrderId($data['order_id']);
        if ($existingReturn) {
            return null;
        }

        $returnCode = $this->returnRepository->generateReturnCode();

        $returnData = [
            'order_id' => $data['order_id'],
            'user_id' => $user->id,
            'return_code' => $returnCode,
            'return_type' => $data['return_type'],
            'reason' => $data['reason'],
            'description' => $data['description'],
            'images' => $data['images'] ?? null,
            'status' => OrderReturnEnum::STATUS_PENDING,
        ];

        return $this->returnRepository->store($returnData);
    }

    public function getReturnDetail(string $returnCode): ?OrderReturn
    {
        return $this->returnRepository->findByReturnCode($returnCode);
    }

    public function updateReturnStatus(int $returnId, string $status, array $additionalData = []): ?OrderReturn
    {
        $return = $this->returnRepository->getById($returnId);

        if (!$return) {
            return null;
        }

        $this->returnRepository->updateStatus($return, $status, $additionalData);

        return $return->fresh();
    }

    public function cancelReturn(User $user, int $returnId): ?array
    {
        $return = $this->returnRepository->getById($returnId);

        if (!$return || $return->user_id !== $user->id) {
            return null;
        }

        if (!in_array($return->status, [OrderReturnEnum::STATUS_PENDING])) {
            return ['error' => 'Cannot cancel return in current status'];
        }

        $this->returnRepository->updateStatus($return, OrderReturnEnum::STATUS_CANCELLED);

        return ['success' => true];
    }
}

