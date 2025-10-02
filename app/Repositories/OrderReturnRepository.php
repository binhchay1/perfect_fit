<?php

namespace App\Repositories;

use App\Enums\OrderReturn as OrderReturnEnum;
use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnRepository extends BaseRepository
{
    public function model()
    {
        return OrderReturn::class;
    }

    public function getUserReturns(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAllReturns()
    {
        return $this->model
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByOrderId(int $orderId)
    {
        return $this->model
            ->where('order_id', $orderId)
            ->first();
    }

    public function findByReturnCode(string $returnCode)
    {
        return $this->model
            ->where('return_code', $returnCode)
            ->with(['order', 'user'])
            ->first();
    }

    public function updateStatus(OrderReturn $return, string $status, ?array $additionalData = [])
    {
        $updateData = ['status' => $status];

        if ($status === OrderReturnEnum::STATUS_APPROVED) {
            $updateData['approved_at'] = now();
        }

        if ($status === OrderReturnEnum::STATUS_COMPLETED) {
            $updateData['completed_at'] = now();
        }

        if (isset($additionalData['admin_notes'])) {
            $updateData['admin_notes'] = $additionalData['admin_notes'];
        }

        if (isset($additionalData['refund_amount'])) {
            $updateData['refund_amount'] = $additionalData['refund_amount'];
        }

        return $return->update($updateData);
    }

    public function generateReturnCode(): string
    {
        do {
            $code = 'RET-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while ($this->model->where('return_code', $code)->exists());

        return $code;
    }
}

