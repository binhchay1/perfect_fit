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
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->enum('shipping_type', ['domestic', 'inter_province']);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // JSON configuration
            $table->json('information');
            $table->json('cod');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_carriers');
    }
};
