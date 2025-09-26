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
        Schema::table('payments', function (Blueprint $table) {
            // Add user_id column
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');

            // Update payment_method enum to include more options
            $table->enum('payment_method', ['cash', 'vnpay', 'momo', 'bank_transfer', 'credit_card', 'debit_card'])->change();

            // Add payment provider column (for tracking which specific gateway)
            $table->string('payment_provider')->nullable()->after('payment_method');

            // Add external payment ID for gateway tracking
            $table->string('external_payment_id')->nullable()->after('transaction_id');

            // Add payment notes
            $table->text('notes')->nullable()->after('gateway_response');

            // Update indexes
            $table->index(['user_id', 'status']);
            $table->index(['payment_method', 'status']);
            $table->index('external_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payment_provider', 'external_payment_id', 'notes']);
            $table->enum('payment_method', ['vnpay', 'momo', 'bank_transfer', 'cod'])->change();
        });
    }
};
