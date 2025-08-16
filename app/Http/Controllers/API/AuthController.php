<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
                'password' => 'required|string|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'district' => 'nullable|string|max:100',
                'ward' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
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
            $input['status'] = 1; // Active by default
            $input['role'] = 'user'; // Default role
            $input['ip_address'] = $request->ip();

            /** @var \App\Models\User $user */
            $user = User::create($input);
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
            ], 'User created successfully', 201);
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
}
