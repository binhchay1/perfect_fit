<?php

namespace App\Repositories;

use App\Enums\PaymentAccount as PaymentAccountEnum;
use App\Models\PaymentAccount;
use App\Models\User;

class PaymentAccountRepository extends BaseRepository
{
    public function model()
    {
        return PaymentAccount::class;
    }

    public function getUserAccounts(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveAccounts(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('is_active', PaymentAccountEnum::STATUS_ACTIVE)
            ->orderBy('is_default', 'desc')
            ->get();
    }

    public function getDefaultAccount(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('is_default', PaymentAccountEnum::DEFAULT)
            ->where('is_active', PaymentAccountEnum::STATUS_ACTIVE)
            ->first();
    }

    public function findByUserAndId(User $user, int $id)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
    }

    public function setAsDefault(PaymentAccount $account)
    {
        $this->model
            ->where('user_id', $account->user_id)
            ->update(['is_default' => PaymentAccountEnum::NOT_DEFAULT]);

        return $account->update(['is_default' => PaymentAccountEnum::DEFAULT]);
    }

    public function toggleStatus(PaymentAccount $account)
    {
        $newStatus = !$account->is_active;
        return $account->update(['is_active' => $newStatus]);
    }
}

