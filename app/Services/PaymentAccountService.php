<?php

namespace App\Services;

use App\Models\PaymentAccount;
use App\Models\User;
use App\Repositories\PaymentAccountRepository;
use Illuminate\Database\Eloquent\Collection;

final class PaymentAccountService
{
    public function __construct(
        private readonly PaymentAccountRepository $paymentAccountRepository
    ) {}

    public function getUserAccounts(User $user): Collection
    {
        return $this->paymentAccountRepository->getUserAccounts($user);
    }

    public function getActiveAccounts(User $user): Collection
    {
        return $this->paymentAccountRepository->getActiveAccounts($user);
    }

    public function getDefaultAccount(User $user): ?PaymentAccount
    {
        return $this->paymentAccountRepository->getDefaultAccount($user);
    }

    public function createAccount(User $user, array $data): PaymentAccount
    {
        $data['user_id'] = $user->id;

        $account = $this->paymentAccountRepository->store($data);

        if ($data['is_default'] ?? false) {
            $this->paymentAccountRepository->setAsDefault($account);
        }

        return $account->fresh();
    }

    public function updateAccount(User $user, int $id, array $data): ?PaymentAccount
    {
        $account = $this->paymentAccountRepository->findByUserAndId($user, $id);

        if (!$account) {
            return null;
        }

        $this->paymentAccountRepository->updateById($account->id, $data);

        if (($data['is_default'] ?? false) && !$account->is_default) {
            $this->paymentAccountRepository->setAsDefault($account);
        }

        return $account->fresh();
    }

    public function deleteAccount(User $user, int $id): ?array
    {
        $account = $this->paymentAccountRepository->findByUserAndId($user, $id);

        if (!$account) {
            return null;
        }

        if ($account->is_default) {
            return ['error' => 'Cannot delete default payment account'];
        }

        $this->paymentAccountRepository->deleteById($account->id);
        return ['success' => true];
    }

    public function setDefault(User $user, int $id): ?PaymentAccount
    {
        $account = $this->paymentAccountRepository->findByUserAndId($user, $id);

        if (!$account) {
            return null;
        }

        $this->paymentAccountRepository->setAsDefault($account);
        return $account->fresh();
    }

    public function toggleStatus(User $user, int $id): ?array
    {
        $account = $this->paymentAccountRepository->findByUserAndId($user, $id);

        if (!$account) {
            return null;
        }

        $this->paymentAccountRepository->toggleStatus($account);

        return [
            'account' => $account->fresh(),
            'message' => $account->is_active ? 'Account deactivated' : 'Account activated',
        ];
    }
}

