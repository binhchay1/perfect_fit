<?php

namespace Tests\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestDataHelper
{
    /**
     * Tạo user test với password đã hash
     */
    public static function createTestUser(array $attributes = []): User
    {
        $defaultAttributes = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('currentpassword123'),
            'status' => 1,
            'email_verified_at' => now(),
            'role' => 'user',
        ];

        return User::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Tạo user admin test
     */
    public static function createAdminUser(array $attributes = []): User
    {
        $defaultAttributes = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword123'),
            'status' => 1,
            'email_verified_at' => now(),
            'role' => 'admin',
        ];

        return User::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Tạo user chưa verify email
     */
    public static function createUnverifiedUser(array $attributes = []): User
    {
        $defaultAttributes = [
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'status' => 0,
            'email_verified_at' => null,
            'role' => 'user',
        ];

        return User::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Data test cho change password
     */
    public static function getChangePasswordTestData(): array
    {
        return [
            'valid_data' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123'
            ],
            'invalid_current_password' => [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123'
            ],
            'short_password' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'short',
                'new_password_confirmation' => 'short'
            ],
            'password_mismatch' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'differentpassword'
            ],
            'empty_fields' => [
                'current_password' => '',
                'new_password' => '',
                'new_password_confirmation' => ''
            ],
            'special_characters' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'NewP@ssw0rd!@#',
                'new_password_confirmation' => 'NewP@ssw0rd!@#'
            ],
            'long_password' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'ThisIsAVeryLongPasswordThatExceedsNormalLengthButShouldStillWork123!@#',
                'new_password_confirmation' => 'ThisIsAVeryLongPasswordThatExceedsNormalLengthButShouldStillWork123!@#'
            ],
            'numbers_only' => [
                'current_password' => 'currentpassword123',
                'new_password' => '123456789',
                'new_password_confirmation' => '123456789'
            ],
            'mixed_case' => [
                'current_password' => 'currentpassword123',
                'new_password' => 'NewPasswordWithMixedCase123',
                'new_password_confirmation' => 'NewPasswordWithMixedCase123'
            ]
        ];
    }

    /**
     * Data test cho login
     */
    public static function getLoginTestData(): array
    {
        return [
            'valid_credentials' => [
                'email' => 'test@example.com',
                'password' => 'currentpassword123'
            ],
            'invalid_email' => [
                'email' => 'nonexistent@example.com',
                'password' => 'currentpassword123'
            ],
            'invalid_password' => [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ],
            'empty_fields' => [
                'email' => '',
                'password' => ''
            ],
            'malformed_email' => [
                'email' => 'invalid-email',
                'password' => 'currentpassword123'
            ]
        ];
    }

    /**
     * Tạo multiple users cho testing
     */
    public static function createMultipleUsers(int $count = 5): array
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $users[] = self::createTestUser([
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'password' => Hash::make("password{$i}123"),
            ]);
        }
        return $users;
    }

    /**
     * Tạo user với profile đầy đủ
     */
    public static function createUserWithFullProfile(array $attributes = []): User
    {
        $defaultAttributes = [
            'name' => 'Full Profile User',
            'email' => 'fullprofile@example.com',
            'password' => Hash::make('password123'),
            'status' => 1,
            'email_verified_at' => now(),
            'role' => 'user',
            'country' => 'Vietnam',
            'province' => 'Ho Chi Minh',
            'district' => 'District 1',
            'ward' => 'Ben Nghe',
            'address' => '123 Test Street',
            'postal_code' => '70000',
            'phone' => '+84123456789',
            'profile_photo_path' => 'images/upload/user/test-photo.jpg',
        ];

        return User::factory()->create(array_merge($defaultAttributes, $attributes));
    }
}