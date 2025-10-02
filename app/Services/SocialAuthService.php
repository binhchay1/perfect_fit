<?php

namespace App\Services;

use App\Enums\Users as UsersEnum;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SocialAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function handleSocialLogin(string $provider, array $socialUser): array
    {
        $providerIdField = $provider . '_id';
        $user = null;

        $user = User::where($providerIdField, $socialUser['id'])->first();

        if (!$user && isset($socialUser['email'])) {
            $user = User::where('email', $socialUser['email'])->first();
            
            if ($user) {
                $user->update([
                    $providerIdField => $socialUser['id'],
                    'avatar_url' => $socialUser['avatar'] ?? null,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }
        }

        if (!$user) {
            $user = User::create([
                'name' => $socialUser['name'] ?? $socialUser['email'],
                'email' => $socialUser['email'],
                $providerIdField => $socialUser['id'],
                'avatar_url' => $socialUser['avatar'] ?? null,
                'password' => Hash::make(Str::random(32)),
                'role' => UsersEnum::USER,
                'status' => UsersEnum::STATUS_ACTIVE_USER,
                'email_verified_at' => now(),
            ]);
        }

        $tokenName = "Perfect Fit API - {$provider}";
        $tokenResult = $user->createToken($tokenName);

        return [
            'user' => $user,
            'token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
        ];
    }

    public function verifyGoogleToken(string $token): ?array
    {
        try {
            $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($token);

            if ($payload) {
                return [
                    'id' => $payload['sub'],
                    'email' => $payload['email'],
                    'name' => $payload['name'] ?? '',
                    'avatar' => $payload['picture'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
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
                return [
                    'id' => $data['id'],
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? '',
                    'avatar' => $data['picture']['data']['url'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            return null;
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
                return [
                    'id' => $data['data']['open_id'] ?? null,
                    'email' => $data['data']['email'] ?? null,
                    'name' => $data['data']['display_name'] ?? '',
                    'avatar' => $data['data']['avatar_url'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}

