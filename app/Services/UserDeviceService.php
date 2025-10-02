<?php

namespace App\Services;

use App\Enums\UserDevice as UserDeviceEnum;
use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\UserDeviceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class UserDeviceService
{
    public function __construct(
        private readonly UserDeviceRepository $userDeviceRepository
    ) {}

    public function registerOrUpdateDevice(User $user, Request $request): UserDevice
    {
        $deviceData = $this->prepareDeviceData($user, $request);

        $device = $this->userDeviceRepository->findByUserAndDeviceId($user, $request->device_id);

        if ($device) {
            $this->userDeviceRepository->updateDevice($device, $deviceData);
            return $device->fresh();
        }

        $device = $this->userDeviceRepository->store($deviceData);

        if ($this->userDeviceRepository->countUserDevices($user) === 1 || $request->boolean('remember_device')) {
            $this->userDeviceRepository->markAsTrusted($device);
        }

        return $device->fresh();
    }

    public function getUserDevices(User $user): Collection
    {
        return $this->userDeviceRepository->getUserDevices($user);
    }

    public function updateDeviceName(User $user, int $deviceId, string $name): ?UserDevice
    {
        $device = $this->userDeviceRepository->findByUserAndId($user, $deviceId);

        if (!$device) {
            return null;
        }

        $this->userDeviceRepository->updateDeviceName($device, $name);
        return $device->fresh();
    }

    public function toggleTrust(User $user, int $deviceId): ?array
    {
        $device = $this->userDeviceRepository->findByUserAndId($user, $deviceId);

        if (!$device) {
            return null;
        }

        if ($device->is_trusted) {
            $this->userDeviceRepository->markAsUntrusted($device);
            $message = 'Device marked as untrusted';
        } else {
            $this->userDeviceRepository->markAsTrusted($device);
            $message = 'Device marked as trusted';
        }

        return [
            'device' => $device->fresh(),
            'message' => $message,
        ];
    }

    public function revokeDevice(User $user, int $deviceId, ?string $currentDeviceId = null): ?array
    {
        $device = $this->userDeviceRepository->findByUserAndId($user, $deviceId);

        if (!$device) {
            return null;
        }

        if ($currentDeviceId && $device->device_id === $currentDeviceId) {
            return ['error' => 'Cannot revoke current device'];
        }

        $this->userDeviceRepository->deactivate($device);

        $user->tokens()
            ->where('name', 'like', "%{$device->device_name}%")
            ->delete();

        return ['success' => true];
    }

    public function revokeAllOtherDevices(User $user, string $currentDeviceId): bool
    {
        $this->userDeviceRepository->deactivateOtherDevices($user, $currentDeviceId);

        $currentDevice = $this->userDeviceRepository->findByUserAndDeviceId($user, $currentDeviceId);

        if ($currentDevice) {
            $user->tokens()
                ->where('name', 'not like', "%{$currentDevice->device_name}%")
                ->delete();
        }

        return true;
    }

    public function updateFcmToken(User $user, string $deviceId, string $fcmToken): ?UserDevice
    {
        $device = $this->userDeviceRepository->findByUserAndDeviceId($user, $deviceId);

        if (!$device) {
            return null;
        }

        $this->userDeviceRepository->updateFcmToken($device, $fcmToken);
        return $device->fresh();
    }

    public function updateLastUsed(UserDevice $device): void
    {
        $this->userDeviceRepository->updateLastUsed($device);
    }

    public function getDisplayName(UserDevice $device): string
    {
        if ($device->device_name) {
            return $device->device_name;
        }

        $parts = array_filter([
            $device->device_type,
            $device->device_model,
        ]);

        return implode(' ', $parts) ?: 'Unknown Device';
    }

    public function isRecentlyUsed(UserDevice $device): bool
    {
        return $device->last_used_at && 
               $device->last_used_at->greaterThan(now()->subDays(UserDeviceEnum::RECENTLY_USED_DAYS));
    }

    private function prepareDeviceData(User $user, Request $request): array
    {
        return [
            'user_id' => $user->id,
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'device_model' => $request->device_model,
            'os_version' => $request->os_version,
            'app_version' => $request->app_version,
            'fcm_token' => $request->fcm_token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_used_at' => now(),
            'is_active' => UserDeviceEnum::STATUS_ACTIVE,
        ];
    }
}

