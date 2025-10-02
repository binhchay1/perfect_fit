<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Services\SocialAuthService;
use App\Services\UserDeviceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Enums\Users as UsersEnum;
use App\Models\User;

/**
 * @OA\Tag(
 *     name="OTP Authentication",
 *     description="Phone OTP verification and authentication"
 * )
 */
final class OtpController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly OtpService $otpService,
        private readonly UserDeviceService $deviceService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/phone/send-otp",
     *     summary="Send OTP to phone",
     *     description="Send OTP code to phone number for verification",
     *     tags={"OTP Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "purpose"},
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="purpose", type="string", enum={"login", "register", "verify_phone", "order_confirm", "password_reset"}, example="login")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|regex:/^[0-9]{10,11}$/',
                'purpose' => 'required|in:login,register,verify_phone,order_confirm,password_reset',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $result = $this->otpService->sendOtp($request->phone, $request->purpose);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 500);
            }

            return $this->successResponse([
                'expires_at' => $result['expires_at'],
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to send OTP', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/phone/verify-otp",
     *     summary="Verify OTP and login",
     *     description="Verify OTP code and authenticate user",
     *     tags={"OTP Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "otp_code"},
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="otp_code", type="string", example="123456"),
     *             @OA\Property(property="purpose", type="string", example="login"),
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="device_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified and login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function verifyOtpAndLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'otp_code' => 'required|string|size:6',
                'purpose' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $purpose = $request->purpose ?? 'login';
            $result = $this->otpService->verifyOtp($request->phone, $request->otp_code, $purpose);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            $user = User::where('phone', $request->phone)->first();

            if (!$user && $purpose === 'login') {
                $user = User::create([
                    'phone' => $request->phone,
                    'name' => 'User ' . substr($request->phone, -4),
                    'email' => $request->phone . '@phone.user',
                    'password' => Hash::make(uniqid()),
                    'role' => UsersEnum::USER,
                    'status' => UsersEnum::STATUS_ACTIVE_USER,
                    'email_verified_at' => now(),
                ]);
            }

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            if ($user->status != UsersEnum::STATUS_ACTIVE_USER) {
                return $this->unauthorizedResponse('Account is not active');
            }

            $device = null;
            if ($request->device_id) {
                $device = $this->deviceService->registerOrUpdateDevice($user, $request);
                $this->deviceService->updateLastUsed($device);
            }

            $tokenName = $device ? "Perfect Fit API - {$device->device_name}" : "Perfect Fit API - Phone";
            $tokenResult = $user->createToken($tokenName);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('OTP verification failed', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/phone/resend-otp",
     *     summary="Resend OTP",
     *     description="Resend OTP code to phone number",
     *     tags={"OTP Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "purpose"},
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="purpose", type="string", example="login")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP resent successfully")
     * )
     */
    public function resendOtp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|regex:/^[0-9]{10,11}$/',
                'purpose' => 'required|in:login,register,verify_phone,order_confirm,password_reset',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $result = $this->otpService->resendOtp($request->phone, $request->purpose);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 500);
            }

            return $this->successResponse([
                'expires_at' => $result['expires_at'],
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to resend OTP', $e->getMessage());
        }
    }
}

