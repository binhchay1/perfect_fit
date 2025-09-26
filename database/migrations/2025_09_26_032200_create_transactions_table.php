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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['payment', 'refund', 'adjustment', 'fee', 'chargeback']);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('VND');
            $table->string('gateway_name')->nullable(); // 'vnpay', 'momo', 'cash', etc.
            $table->string('gateway_transaction_id')->nullable(); // External transaction ID
            $table->string('reference_id')->nullable(); // Internal reference
            $table->string('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'type']);
            $table->index(['payment_id', 'status']);
            $table->index(['gateway_name', 'created_at']);
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
