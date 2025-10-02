<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use App\Services\UserDeviceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Social Authentication",
 *     description="Social login operations (Google, Facebook, Tiktok)"
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
     *     description="Authenticate user with Google ID token",
     *     tags={"Social Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="Google ID token"),
     *             @OA\Property(property="device_id", type="string"),
     *             @OA\Property(property="device_name", type="string"),
     *             @OA\Property(property="device_type", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation error', 422, $validator->errors());
            }

            $socialUser = $this->socialAuthService->verifyGoogleToken($request->token);

            if (!$socialUser) {
                return $this->unauthorizedResponse('Invalid Google token');
            }

            $result = $this->socialAuthService->handleSocialLogin('google', $socialUser);

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

