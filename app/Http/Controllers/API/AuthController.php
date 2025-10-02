<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Models\User;
use App\Models\UserVerify;
use App\Services\UserDeviceService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     title="Perfect Fit API",
 *     version="1.0.0",
 *     description="API documentation for Perfect Fit application"
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer <token>"
 * )
 */
final class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserDeviceService $deviceService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User login with device tracking",
     *     description="Authenticate user and return access token. Optionally register/update device information for session management",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_id", type="string", example="unique-device-id-123", description="Unique device identifier"),
     *             @OA\Property(property="device_name", type="string", example="My iPhone 14", description="Device name"),
     *             @OA\Property(property="device_type", type="string", example="ios", description="Device type (ios, android, web, desktop, tablet)"),
     *             @OA\Property(property="device_model", type="string", example="iPhone 14 Pro", description="Device model"),
     *             @OA\Property(property="os_version", type="string", example="17.0", description="OS version"),
     *             @OA\Property(property="app_version", type="string", example="1.0.0", description="App version"),
     *             @OA\Property(property="fcm_token", type="string", example="fcm-token-xyz", description="Firebase Cloud Messaging token"),
     *             @OA\Property(property="remember_device", type="boolean", example=true, description="Mark device as trusted")
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
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="role", type="string", example="user"),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-10-02T10:30:00.000000Z"),
     *                 @OA\Property(property="device", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="device_name", type="string", example="My iPhone 14"),
     *                     @OA\Property(property="is_trusted", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials or account not active",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'device_id' => 'nullable|string|max:255',
                'device_name' => 'nullable|string|max:255',
                'device_type' => 'nullable|string|max:50',
                'device_model' => 'nullable|string|max:100',
                'os_version' => 'nullable|string|max:50',
                'app_version' => 'nullable|string|max:20',
                'fcm_token' => 'nullable|string|max:255',
                'remember_device' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation error',
                    422,
                    $validator->errors()
                );
            }

            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check if user is active
            if ($user->status != 1) {
                return $this->unauthorizedResponse('Account is not active');
            }

            $device = null;
            if ($request->device_id) {
                $device = $this->deviceService->registerOrUpdateDevice($user, $request);
            }

            $tokenName = $device ? "Perfect Fit API - {$device->device_name}" : 'Perfect Fit API';
            $tokenResult = $user->createToken($tokenName);

            if ($device) {
                $this->deviceService->updateLastUsed($device);
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status
                ],
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
                'device' => $device ? [
                    'id' => $device->id,
                    'device_name' => $this->deviceService->getDisplayName($device),
                    'is_trusted' => $device->is_trusted,
                ] : null,
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Login failed', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="User registration",
     *     description="Register a new user account",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully. Verification email sent."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="role", type="string", example="user"),
     *                     @OA\Property(property="status", type="integer", example=0)
     *                 ),
     *                 @OA\Property(property="message", type="string", example="Please check your email to verify your account")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation error',
                    422,
                    $validator->errors()
                );
            }

            $input = $request->all();
            $input['password'] = Hash::make($input['password']);
            $input['status'] = 0; // Inactive until verified
            $input['role'] = 'user'; // Default role
            $input['ip_address'] = $request->ip();

            /** @var \App\Models\User $user */
            $user = User::create($input);

            $token = Str::random(64);
            $dataMail = [
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ];
            $userMail = $user->email;

            UserVerify::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => Carbon::now('Asia/Ho_Chi_Minh')->addHours(24), // Token expires in 24 hours
            ]);

            //dùng job để xử lý mail
            SendEmail::dispatch($userMail, $dataMail);
            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status
                ],
                'message' => 'Please check your email to verify your account'
            ], 'User created successfully. Verification email sent.', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Registration failed', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/token/refresh",
     *     summary="Refresh access token",
     *     description="Generate a new access token",
     *     tags={"Authentication"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="1|def456..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            // Revoke current token
            $request->user()->token()->revoke();

            // Create new token
            $tokenResult = $user->createToken('Perfect Fit API');

            return $this->successResponse([
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to refresh token', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="User logout",
     *     description="Revoke the current access token",
     *     tags={"Authentication"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            // Revoke current token
            $request->user()->token()->revoke();

            return $this->successResponse(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to logout', $e->getMessage());
        }
    }

    public function user(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'province' => $user->province,
                'district' => $user->district,
                'ward' => $user->ward,
                'address' => $user->address,
                'postal_code' => $user->postal_code,
                'role' => $user->role,
                'status' => $user->status,
                'profile_photo_path' => $user->profile_photo_path,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ], 'User details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user details', $e->getMessage());
        }
    }


    /**
     * @OA\Get(
     *     path="/auth/verify/{token}",
     *     summary="Verify user account",
     *     description="Verify user email and activate account using verification token",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Email verification token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to frontend with verification result"
     *     )
     * )
     */
    public function verifyAccount($token)
    {
        try {
            // Check token exists
            $verifyUser = UserVerify::where('token', $token)->first();
            if (!$verifyUser) {
                return redirect()->away(env('FRONTEND_URL') . '/login?active=false&message=invalid_token');
            }

            // Check if token is expired (24 hours)
            $currentTime = Carbon::now('Asia/Ho_Chi_Minh');

            if ($verifyUser->expires_at < $currentTime) {
                return redirect()->away(env('FRONTEND_URL') . '/resend-email?email=' . $verifyUser->user->email . '&message=token_expired');
            }

            // Check if user already verified
            $user = $verifyUser->user;
            if ($user->email_verified_at) {
                return redirect()->away(env('FRONTEND_URL') . '/login?active=true&message=already_verified');
            }

            // Verify email and activate account
            $user->update([
                'email_verified_at' => $currentTime,
                'status' => 1, // Activate account
            ]);

            // Delete the verification token (optional - for security)
            $verifyUser->delete();

            return redirect()->away(env('FRONTEND_URL') . '/login?active=true&message=verification_success');
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return redirect()->away(env('FRONTEND_URL') . '/login?active=false&message=verification_failed');
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/verify/resend",
     *     summary="Resend verification email",
     *     description="Resend email verification link to user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email xác thực đã được gửi lại, hãy kiểm tra lại email"),
     *             @OA\Property(property="type", type="string", example="resend_email_success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tài khoản email không tồn tại"),
     *             @OA\Property(property="type", type="string", example="data_email_not_exist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email đã được xác thực"),
     *             @OA\Property(property="type", type="string", example="email_already_verified")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="message", type="string", example="Dữ liệu đầu vào không hợp lệ"),
     *             @OA\Property(property="type", type="string", example="validate_data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limited",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You can only send one email every minute."),
     *             @OA\Property(property="type", type="string", example="prevent_request_email")
     *         )
     *     )
     * )
     */
    public function resendVerifyAccount(Request $request)
    {
        try {
            // Validate email
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                    'message' => 'Dữ liệu đầu vào không hợp lệ',
                    'type' => 'validate_data',
                ], 422);
            }

            $dataEmail = $request->input('email');
            $user = User::where('email', $dataEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản email không tồn tại',
                    'type' => 'data_email_not_exist',
                ], 404);
            }

            // Check if email already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email đã được xác thực',
                    'type' => 'email_already_verified',
                ], 400);
            }

            // Rate limiting - prevent spam
            $cacheKey = 'sent_email_' . $user->id;
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'message' => 'You can only send one email every minute.',
                    'type' => 'prevent_request_email',
                ], 429);
            }

            // Clean up old tokens
            UserVerify::where('user_id', $user->id)->delete();

            // Generate new token
            $token = Str::random(64);
            $dataMail = [
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ];

            // Create new verification record
            UserVerify::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => Carbon::now('Asia/Ho_Chi_Minh')->addHours(6), // 6 hours expiration
            ]);

            // Send email
            SendEmail::dispatch($user->email, $dataMail);

            // Set rate limiting cache
            Cache::put($cacheKey, true, now()->addMinute());

            return response()->json([
                'success' => true,
                'message' => 'Email xác thực đã được gửi lại, hãy kiểm tra lại email',
                'type' => 'resend_email_success',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Resend verification email failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gửi email',
                'type' => 'error_send_email',
            ], 500);
        }
    }
}
