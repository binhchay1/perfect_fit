<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserBodyMeasurement;

class UserBodyMeasurementRepository extends BaseRepository
{
    public function model()
    {
        return UserBodyMeasurement::class;
    }

    public function getUserMeasurements(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->first();
    }

    public function createOrUpdate(User $user, array $data)
    {
        return $this->model->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }

    public function deleteMeasurements(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->delete();
    }
}

