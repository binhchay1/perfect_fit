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
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Login user and create token
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
     * Register user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',

                //không cần những trường này vì register chỉ cần set name, mail, pass
                //                'password' => 'required|string|min:6|confirmed', //không cần set confirm
                //                'phone' => 'nullable|string|max:20',
                //                'country' => 'nullable|string|max:100',
                //                'province' => 'nullable|string|max:100',
                //                'district' => 'nullable|string|max:100',
                //                'ward' => 'nullable|string|max:100',
                //                'address' => 'nullable|string|max:255',
                //                'postal_code' => 'nullable|string|max:10',
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
            //$input['verification_token'] = Str::random(60); anh thấy có em set verification_token nhưng trong db lại không tạo để lưu

            /** @var \App\Models\User $user */
            $user = User::create($input);

            // Send verification email (tạo thêm bảng verify mail để xử lý mail)

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
            // $this->sendVerificationEmail($user);

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
     * Refresh Token
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
     * Logout user (Revoke token)
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
     * Get authenticated user details
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
