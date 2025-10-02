<?php

namespace App\Repositories;

use App\Enums\UserDevice as UserDeviceEnum;
use App\Models\User;
use App\Models\UserDevice;

class UserDeviceRepository extends BaseRepository
{
    public function model()
    {
        return UserDevice::class;
    }

    /**
     * Find device by user and device ID
     */
    public function findByUserAndDeviceId(User $user, string $deviceId)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();
    }

    /**
     * Find device by user and ID
     */
    public function findByUserAndId(User $user, int $id)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
    }

    /**
     * Get all user devices ordered by last used
     */
    public function getUserDevices(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get active devices for user
     */
    public function getActiveDevices(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('is_active', UserDeviceEnum::STATUS_ACTIVE)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get trusted devices for user
     */
    public function getTrustedDevices(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('is_trusted', UserDeviceEnum::TRUSTED)
            ->where('is_active', UserDeviceEnum::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Get recently used devices
     */
    public function getRecentlyUsedDevices(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('last_used_at', '>=', now()->subDays(UserDeviceEnum::RECENTLY_USED_DAYS))
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Create new device
     */
    public function store(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update device
     */
    public function updateDevice(UserDevice $device, array $data)
    {
        return $device->update($data);
    }

    /**
     * Delete device
     */
    public function deleteDevice(UserDevice $device)
    {
        return $device->delete();
    }

    /**
     * Count user devices
     */
    public function countUserDevices(User $user)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Deactivate all other devices except current
     */
    public function deactivateOtherDevices(User $user, string $currentDeviceId)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('device_id', '!=', $currentDeviceId)
            ->update(['is_active' => UserDeviceEnum::STATUS_INACTIVE]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(UserDevice $device)
    {
        return $device->update(['last_used_at' => now()]);
    }

    /**
     * Mark device as trusted
     */
    public function markAsTrusted(UserDevice $device)
    {
        return $device->update(['is_trusted' => UserDeviceEnum::TRUSTED]);
    }

    /**
     * Mark device as untrusted
     */
    public function markAsUntrusted(UserDevice $device)
    {
        return $device->update(['is_trusted' => UserDeviceEnum::UNTRUSTED]);
    }

    /**
     * Deactivate device
     */
    public function deactivate(UserDevice $device)
    {
        return $device->update(['is_active' => UserDeviceEnum::STATUS_INACTIVE]);
    }

    /**
     * Activate device
     */
    public function activate(UserDevice $device)
    {
        return $device->update(['is_active' => UserDeviceEnum::STATUS_ACTIVE]);
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(UserDevice $device, string $fcmToken)
    {
        return $device->update(['fcm_token' => $fcmToken]);
    }

    /**
     * Update device name
     */
    public function updateDeviceName(UserDevice $device, string $name)
    {
        return $device->update(['device_name' => $name]);
    }
}

