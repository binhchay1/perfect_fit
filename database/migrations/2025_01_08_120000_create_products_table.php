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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên sản phẩm
            $table->string('slug')->unique(); // Slug cho URL
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->bigInteger('brand_id'); // Foreign key cho brand
            $table->string('material')->nullable(); // Chất vải
            $table->text('images')->nullable(); // Hình ảnh dạng JSON
            $table->decimal('price', 10, 2)->nullable(); // Giá
            $table->enum('product_type', ['genuine', 'self_produced'])->default('self_produced'); // Hàng chính hãng hoặc tự sản xuất
            $table->string('product_link')->nullable(); // Link sản phẩm
            $table->enum('gender', ['male', 'female'])->default('female'); // Giới tính (Nam/Nữ)
            $table->boolean('is_active')->default(true); // Trạng thái hoạt động
            $table->timestamps();

            // Indexes for performance
            $table->index(['brand_id']);
            $table->index(['is_active']);
            $table->index(['slug']);
            $table->index(['product_type']);
            $table->index(['gender']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};