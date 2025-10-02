<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex');
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('chest', 5, 2)->nullable();
            $table->decimal('waist', 5, 2)->nullable();
            $table->decimal('hips', 5, 2)->nullable();
            $table->decimal('thigh', 5, 2)->nullable();
            $table->decimal('shoulder', 5, 2)->nullable();
            $table->decimal('arm_length', 5, 2)->nullable();
            $table->decimal('leg_length', 5, 2)->nullable();
            $table->enum('height_unit', ['cm', 'in'])->default('cm');
            $table->enum('weight_unit', ['kg', 'lbs'])->default('kg');
            $table->enum('measurement_unit', ['cm', 'in'])->default('cm');
            $table->string('preferred_fit')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['user_id', 'gender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_body_measurements');
    }
};

