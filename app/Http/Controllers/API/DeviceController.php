<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\UserDeviceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Device Management",
 *     description="User device and session management operations"
 * )
 */
final class DeviceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserDeviceService $deviceService
    ) {}

    /**
     * @OA\Get(
     *     path="/devices",
     *     summary="Get user's devices",
     *     description="Retrieve all devices associated with the authenticated user",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="X-Device-ID",
     *         in="header",
     *         description="Current device ID to mark as current",
     *         required=false,
     *         @OA\Schema(type="string", example="unique-device-id-123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Devices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Devices retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="device_name", type="string", example="iPhone 14 Pro"),
     *                     @OA\Property(property="device_type", type="string", example="ios"),
     *                     @OA\Property(property="device_model", type="string", example="iPhone 14 Pro"),
     *                     @OA\Property(property="os_version", type="string", example="17.0"),
     *                     @OA\Property(property="app_version", type="string", example="1.0.0"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_trusted", type="boolean", example=true),
     *                     @OA\Property(property="last_used_at", type="string", format="date-time", example="2025-10-02T10:30:00.000000Z"),
     *                     @OA\Property(property="is_current", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve devices")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $devices = $this->deviceService->getUserDevices($user);

            $devicesData = $devices->map(function ($device) use ($request) {
                return [
                    'id' => $device->id,
                    'device_name' => $this->deviceService->getDisplayName($device),
                    'device_type' => $device->device_type,
                    'device_model' => $device->device_model,
                    'os_version' => $device->os_version,
                    'app_version' => $device->app_version,
                    'is_active' => $device->is_active,
                    'is_trusted' => $device->is_trusted,
                    'last_used_at' => $device->last_used_at,
                    'is_current' => $device->device_id === $request->header('X-Device-ID'),
                ];
            });

            return $this->successResponse($devicesData, 'Devices retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve devices', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/devices/{id}/name",
     *     summary="Update device name",
     *     description="Update the name of a specific device",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Device ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_name"},
     *             @OA\Property(property="device_name", type="string", example="My iPhone 14", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device name updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Device name updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="device_name", type="string", example="My iPhone 14")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Device not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     )
     * )
     */
    public function updateName(Request $request, int $deviceId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $device = $this->deviceService->updateDeviceName($user, $deviceId, $request->device_name);

            if (!$device) {
                return $this->errorResponse('Device not found', 404);
            }

            return $this->successResponse([
                'id' => $device->id,
                'device_name' => $device->device_name,
            ], 'Device name updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update device name', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/devices/{id}/trust",
     *     summary="Toggle device trust status",
     *     description="Toggle the trusted status of a device (trusted/untrusted)",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Device ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device trust status updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Device marked as trusted"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="is_trusted", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Device not found")
     *         )
     *     )
     * )
     */
    public function toggleTrust(Request $request, int $deviceId): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->deviceService->toggleTrust($user, $deviceId);

            if (!$result) {
                return $this->errorResponse('Device not found', 404);
            }

            return $this->successResponse([
                'id' => $result['device']->id,
                'is_trusted' => $result['device']->is_trusted,
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update device trust status', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/devices/{id}",
     *     summary="Revoke/deactivate device",
     *     description="Revoke a device and delete all associated tokens",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Device ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="X-Device-ID",
     *         in="header",
     *         description="Current device ID (cannot revoke current device)",
     *         required=false,
     *         @OA\Schema(type="string", example="unique-device-id-123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Device revoked successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot revoke current device",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot revoke current device")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Device not found")
     *         )
     *     )
     * )
     */
    public function revoke(Request $request, int $deviceId): JsonResponse
    {
        try {
            $user = Auth::user();
            $currentDeviceId = $request->header('X-Device-ID');

            $result = $this->deviceService->revokeDevice($user, $deviceId, $currentDeviceId);

            if (!$result) {
                return $this->errorResponse('Device not found', 404);
            }

            if (isset($result['error'])) {
                return $this->errorResponse($result['error'], 400);
            }

            return $this->successResponse(null, 'Device revoked successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to revoke device', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/devices/revoke-others",
     *     summary="Revoke all other devices",
     *     description="Revoke all devices except the current one",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="X-Device-ID",
     *         in="header",
     *         description="Current device ID (this device will not be revoked)",
     *         required=true,
     *         @OA\Schema(type="string", example="unique-device-id-123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All other devices revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All other devices revoked successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Current device ID not provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Current device ID not provided")
     *         )
     *     )
     * )
     */
    public function revokeAllOthers(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $currentDeviceId = $request->header('X-Device-ID');

            if (!$currentDeviceId) {
                return $this->errorResponse('Current device ID not provided', 400);
            }

            $this->deviceService->revokeAllOtherDevices($user, $currentDeviceId);

            return $this->successResponse(null, 'All other devices revoked successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to revoke other devices', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/devices/fcm-token",
     *     summary="Update FCM token",
     *     description="Update Firebase Cloud Messaging token for push notifications",
     *     tags={"Device Management"},
     *     security={{"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id", "fcm_token"},
     *             @OA\Property(property="device_id", type="string", example="unique-device-id-123", maxLength=255),
     *             @OA\Property(property="fcm_token", type="string", example="fcm-token-abc123xyz...", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FCM token updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FCM token updated successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Device not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Device not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     )
     * )
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string|max:255',
                'device_id' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $user = Auth::user();
            $device = $this->deviceService->updateFcmToken($user, $request->device_id, $request->fcm_token);

            if (!$device) {
                return $this->errorResponse('Device not found', 404);
            }

            return $this->successResponse(null, 'FCM token updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update FCM token', $e->getMessage());
        }
    }
}

