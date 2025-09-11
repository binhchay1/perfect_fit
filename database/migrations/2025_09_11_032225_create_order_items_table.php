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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_color_id')->nullable()->constrained('product_colors')->onDelete('cascade');
            $table->foreignId('product_size_id')->nullable()->constrained('product_sizes')->onDelete('cascade');

            // Backup product information at time of purchase
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('color_name')->nullable();
            $table->string('size_name')->nullable();

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            // Indexes for better performance
            $table->index(['order_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};