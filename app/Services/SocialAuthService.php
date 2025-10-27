<?php

namespace App\Services;

use App\Enums\Users as UsersEnum;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SocialAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function handleSocialLogin(string $provider, array $socialUser): array
    {
        $providerIdField = $provider . '_id';

        try {
            // First, try to find user by provider ID
            $user = User::where($providerIdField, $socialUser['id'])->first();

            if (!$user && isset($socialUser['email'])) {
                // If not found by provider ID, try to find by email
                $user = User::where('email', $socialUser['email'])->first();

                if ($user) {
                    // Link the social account to existing user
                    $user->update([
                        $providerIdField => $socialUser['id'],
                        'avatar_url' => $socialUser['avatar'] ?? $user->avatar_url,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ]);

                    Log::info("Social login: Linked {$provider} account to existing user", [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'social_id' => $socialUser['id']
                    ]);
                }
            }

            if (!$user) {
                // Create new user
                $userData = [
                    'name' => $socialUser['name'] ?? $socialUser['email'],
                    'email' => $socialUser['email'],
                    $providerIdField => $socialUser['id'],
                    'avatar_url' => $socialUser['avatar'] ?? null,
                    'password' => Hash::make(Str::random(32)),
                    'role' => UsersEnum::USER,
                    'status' => UsersEnum::STATUS_ACTIVE_USER,
                    'email_verified_at' => now(),
                ];

                // Ensure email is provided for new users
                if (!isset($socialUser['email']) || empty($socialUser['email'])) {
                    throw new \Exception('Email is required for new user registration');
                }

                $user = User::create($userData);

                Log::info("Social login: Created new user via {$provider}", [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'email' => $socialUser['email']
                ]);
            }

            // Create API token
            $tokenName = "Perfect Fit API - {$provider}";
            $tokenResult = $user->createToken($tokenName);

            Log::info("Social login: Token created for user", [
                'user_id' => $user->id,
                'provider' => $provider,
                'token_name' => $tokenName
            ]);

            return [
                'user' => $user,
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error("Social login failed for {$provider}", [
                'provider' => $provider,
                'social_user' => $socialUser,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function verifyGoogleToken(string $token): ?array
    {
        try {
            $clientId = config('services.google.client_id');

            if (!$clientId) {
                Log::error('Google OAuth: Client ID not configured');
                return null;
            }

            $client = new \Google_Client([
                'client_id' => $clientId
            ]);

            // Verify the ID token
            $payload = $client->verifyIdToken($token);

            if ($payload) {
                // Verify the token was issued for this app
                if ($payload['aud'] !== $clientId) {
                    Log::warning('Google OAuth: Token audience mismatch', [
                        'expected' => $clientId,
                        'received' => $payload['aud']
                    ]);
                    return null;
                }

                // Verify token hasn't expired
                if (isset($payload['exp']) && $payload['exp'] < time()) {
                    Log::warning('Google OAuth: Token expired');
                    return null;
                }

                return [
                    'id' => $payload['sub'],
                    'email' => $payload['email'],
                    'name' => $payload['name'] ?? '',
                    'avatar' => $payload['picture'] ?? null,
                ];
            }

            Log::warning('Google OAuth: Invalid token format or signature');
            return null;

        } catch (\Exception $e) {
            Log::error('Google OAuth verification failed', [
                'error' => $e->getMessage(),
                'token_length' => strlen($token)
            ]);
            return null;
        }
    }

    public function verifyFacebookToken(string $token): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email,picture',
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verify we got the required data
                if (!isset($data['id'])) {
                    Log::warning('Facebook OAuth: Missing user ID in response');
                    return null;
                }

                return [
                    'id' => $data['id'],
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? '',
                    'avatar' => $data['picture']['data']['url'] ?? null,
                ];
            }

            Log::warning('Facebook OAuth: API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook OAuth verification failed', [
                'error' => $e->getMessage(),
                'token_length' => strlen($token)
            ]);
        }

        return null;
    }

    public function verifyTiktokToken(string $token): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('https://open-api.tiktok.com/oauth/userinfo/', [
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verify we got the required data
                if (!isset($data['data']['open_id'])) {
                    Log::warning('TikTok OAuth: Missing open_id in response');
                    return null;
                }

                return [
                    'id' => $data['data']['open_id'],
                    'email' => $data['data']['email'] ?? null,
                    'name' => $data['data']['display_name'] ?? '',
                    'avatar' => $data['data']['avatar_url'] ?? null,
                ];
            }

            Log::warning('TikTok OAuth: API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('TikTok OAuth verification failed', [
                'error' => $e->getMessage(),
                'token_length' => strlen($token)
            ]);
        }

        return null;
    }
}

