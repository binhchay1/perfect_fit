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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên thương hiệu
            $table->string('slug'); // Slug cho URL
            $table->text('description')->nullable(); // Mô tả thương hiệu
            $table->string('logo')->nullable(); // Logo thương hiệu
            $table->boolean('is_active')->default(true); // Trạng thái hoạt động
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active']);
            $table->index(['slug']);
            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};