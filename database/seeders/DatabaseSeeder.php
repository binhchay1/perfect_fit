<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or get a test user
        $testUser = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // If user already exists, update the password
        if ($testUser->wasRecentlyCreated === false) {
            $testUser->update(['password' => bcrypt('password')]);
        }

        // Run seeders to populate test data
        $this->call([
            BrandSeeder::class,
            ProductSeeder::class,
        ]);

        // Create additional test users if needed
        // \App\Models\User::factory(10)->create();
    }
}