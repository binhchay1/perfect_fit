<?php

namespace App\Repositories;

use App\Enums\Users;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function index()
    {
        return $this->model->latest()->get();
    }
    public function getDataUser($dataUser)
    {
        return $this->model->where('id', $dataUser)->first();
    }

    public function update($dataUser, $id)
    {
        return $this->model->where('id', $id)->update($dataUser);
    }

    public function getInfo($dataEmail)
    {
        return $this->model->where('email', $dataEmail)->first();
    }

    public function updatePassword($email, $input)
    {
        return $this->model->where('email', $email)->update($input);
    }

    public function getAllForAdmin(int $perPage = 15, ?string $search = null, ?string $role = null, $status = null)
    {
        $query = $this->model->newQuery();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status !== null) {
            $query->where('status', (int) $status);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function deleteUser(string $id)
    {
        return $this->model->where('id', $id)->delete();
    }

    public function toggleStatus(string $id)
    {
        $user = $this->model->find($id);
        if (!$user) {
            return null;
        }
        $user->status = $user->status ? 0 : 1;
        $user->save();
        return $user;
    }

    public function getStatistics()
    {
        return [
            'total_users' => $this->model->count(),
            'active_users' => $this->model->where('status', 1)->count(),
            'inactive_users' => $this->model->where('status', 0)->count(),
            'admin_users' => $this->model->where('role', 'admin')->count(),
            'regular_users' => $this->model->where('role', 'user')->orWhereNull('role')->count(),
            'verified_users' => $this->model->whereNotNull('email_verified_at')->count(),
            'unverified_users' => $this->model->whereNull('email_verified_at')->count(),
        ];
    }

    /**
     * Get new customers count
     */
    public function getNewCustomersCount()
    {
        return $this->model
            ->where('role', 'user')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();
    }

    /**
     * Get returning customers count
     */
    public function getReturningCustomersCount()
    {
        return $this->model
            ->where('role', 'user')
            ->whereHas('orders', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
    }
}
