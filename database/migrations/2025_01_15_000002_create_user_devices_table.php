<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_id')->unique(); // Unique device identifier
            $table->string('device_name')->nullable(); // User-friendly device name
            $table->string('device_type')->nullable(); // iOS, Android, Web, etc.
            $table->string('device_model')->nullable(); // iPhone 14, Samsung Galaxy, etc.
            $table->string('os_version')->nullable(); // iOS 17.0, Android 14, etc.
            $table->string('app_version')->nullable(); // App version
            $table->string('fcm_token')->nullable(); // For push notifications
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_trusted')->default(false); // Trusted devices don't need re-login
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'device_id']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};

