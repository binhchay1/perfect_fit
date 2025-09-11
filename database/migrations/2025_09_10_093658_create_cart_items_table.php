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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_color_id')->nullable()->constrained('product_colors')->onDelete('cascade');
            $table->foreignId('product_size_id')->nullable()->constrained('product_sizes')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->string('size')->nullable(); // Keep for backward compatibility
            $table->string('color')->nullable(); // Keep for backward compatibility
            $table->timestamps();

            // Ensure unique combination of cart, product, color, and size
            $table->unique(['cart_id', 'product_id', 'product_color_id', 'product_size_id'], 'unique_cart_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};