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
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_color_id');
            $table->string('size_name'); // S, M, L, XL
            $table->integer('quantity')->default(0);
            $table->string('sku')->unique(); // SKU duy nhất cho từng size+màu
            $table->timestamps();

            // Indexes for performance
            $table->index(['product_color_id']);
            $table->index(['size_name']);
            $table->index(['sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};