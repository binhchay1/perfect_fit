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
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->json('shipping_location');
            $table->string('phone');
            $table->boolean('enable_domestic_shipping')->default(true);
            $table->boolean('enable_inter_province_shipping')->default(false);
            $table->json('carriers_id')->nullable(); // Lưu carriers đã chọn theo dạng JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};