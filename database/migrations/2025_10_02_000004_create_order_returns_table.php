<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('return_code')->unique();
            $table->enum('return_type', ['return', 'refund', 'exchange'])->default('return');
            $table->enum('reason', [
                'damaged',
                'wrong_item',
                'wrong_size',
                'not_as_described',
                'quality_issue',
                'changed_mind',
                'other'
            ]);
            $table->text('description');
            $table->json('images')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'processing',
                'completed',
                'cancelled'
            ])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['user_id', 'status']);
            $table->index(['status']);
            $table->index(['return_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};

