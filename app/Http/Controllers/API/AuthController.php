<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use App\Models\User;
use App\Models\UserVerify;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User login",
     *     description="Authenticate user and return access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
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
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
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
    

            $tokenResult = $user->createToken('Perfect Fit API');
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

    /**
     * @OA\Get(
     *     path="/me",
     *     summary="Get authenticated user details",
     *     description="Retrieve the current authenticated user's information",
     *     tags={"User"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User details retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="phone", type="string", example="1234567890"),
     *                 @OA\Property(property="country", type="string", example="Vietnam"),
     *                 @OA\Property(property="province", type="string", example="Ho Chi Minh"),
     *                 @OA\Property(property="district", type="string", example="District 1"),
     *                 @OA\Property(property="ward", type="string", example="Ward 1"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="postal_code", type="string", example="70000"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="profile_photo_path", type="string", example="images/upload/user/photo.jpg"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     * Verify user account via token
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
     * Resend verification email
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
