<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use App\Services\UserDeviceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Social Authentication",
 *     description="OAuth 2.0 social login with Google, Facebook, and TikTok. Auto account creation, email linking, and profile sync. Configure client credentials in .env (GOOGLE_CLIENT_ID, FACEBOOK_CLIENT_ID, TIKTOK_CLIENT_KEY)"
 * )
 */
final class SocialAuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SocialAuthService $socialAuthService,
        private readonly UserDeviceService $deviceService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/social/google",
     *     summary="Google OAuth login",
     *     description="Authenticate user with Google ID token from mobile SDK. Supports device registration and user account creation/linking.",
     *     tags={"Social Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="Google ID token from Google Sign-In SDK", minLength=10),
     *             @OA\Property(property="device_id", type="string", description="Unique device identifier", maxLength=255),
     *             @OA\Property(property="device_name", type="string", description="Human-readable device name", maxLength=255),
     *             @OA\Property(property="device_type", type="string", description="Device platform (Android/iOS)", maxLength=50),
     *             @OA\Property(property="device_model", type="string", description="Device model identifier", maxLength=100),
     *             @OA\Property(property="os_version", type="string", description="Operating system version", maxLength=50),
     *             @OA\Property(property="app_version", type="string", description="Application version", maxLength=20),
     *             @OA\Property(property="fcm_token", type="string", description="Firebase Cloud Messaging token", maxLength=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                     @OA\Property(property="role", type="string", example="user"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/...")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|abc123def456..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", example="2025-10-28 10:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid Google token"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string|min:10',
                'device_id' => 'nullable|string|max:255',
                'device_name' => 'nullable|string|max:255',
                'device_type' => 'nullable|string|max:50',
                'device_model' => 'nullable|string|max:100',
                'os_version' => 'nullable|string|max:50',
                'app_version' => 'nullable|string|max:20',
                'fcm_token' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                Log::warning('Google login: Validation failed', [
                    'errors' => $validator->errors(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            Log::info('Google login attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $request->device_id,
                'token_length' => strlen($request->token)
            ]);

            $socialUser = $this->socialAuthService->verifyGoogleToken($request->token);

            if (!$socialUser) {
                Log::warning('Google login: Invalid token', [
                    'ip' => $request->ip(),
                    'token_length' => strlen($request->token)
                ]);
                return $this->unauthorizedResponse('Invalid Google token');
            }

            Log::info('Google login: Token verified successfully', [
                'user_email' => $socialUser['email'],
                'user_id' => $socialUser['id']
            ]);

            $result = $this->socialAuthService->handleSocialLogin('google', $socialUser);

            // Register or update device if device_id is provided
            if ($request->device_id) {
                try {
                    $device = $this->deviceService->registerOrUpdateDevice($result['user'], $request);
                    $this->deviceService->updateLastUsed($device);

                    Log::info('Google login: Device registered/updated', [
                        'user_id' => $result['user']->id,
                        'device_id' => $request->device_id,
                        'device_name' => $request->device_name
                    ]);
                } catch (\Exception $e) {
                    Log::error('Google login: Device registration failed', [
                        'user_id' => $result['user']->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail the login if device registration fails
                }
            }

            Log::info('Google login: Success', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email
            ]);

            return $this->successResponse([
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role,
                    'avatar' => $result['user']->avatar_url,
                ],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ], 'Login successful');
        } catch (\Exception $e) {
            Log::error('Google login: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return $this->serverErrorResponse('Google login failed', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/social/facebook",
     *     summary="Facebook OAuth login",
     *     description="Authenticate user with Facebook access token",
     *     tags={"Social Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="Facebook access token")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function facebookLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $socialUser = $this->socialAuthService->verifyFacebookToken($request->token);

            if (!$socialUser) {
                return $this->unauthorizedResponse('Invalid Facebook token');
            }

            $result = $this->socialAuthService->handleSocialLogin('facebook', $socialUser);

            if ($request->device_id) {
                $device = $this->deviceService->registerOrUpdateDevice($result['user'], $request);
                $this->deviceService->updateLastUsed($device);
            }

            return $this->successResponse([
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role,
                    'avatar' => $result['user']->avatar_url,
                ],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Facebook login failed', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/social/tiktok",
     *     summary="Tiktok OAuth login",
     *     description="Authenticate user with Tiktok access token",
     *     tags={"Social Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="Tiktok access token")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function tiktokLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $socialUser = $this->socialAuthService->verifyTiktokToken($request->token);

            if (!$socialUser) {
                return $this->unauthorizedResponse('Invalid Tiktok token');
            }

            $result = $this->socialAuthService->handleSocialLogin('tiktok', $socialUser);

            if ($request->device_id) {
                $device = $this->deviceService->registerOrUpdateDevice($result['user'], $request);
                $this->deviceService->updateLastUsed($device);
            }

            return $this->successResponse([
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role,
                    'avatar' => $result['user']->avatar_url,
                ],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Tiktok login failed', $e->getMessage());
        }
    }
}

