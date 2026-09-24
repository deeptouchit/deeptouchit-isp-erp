<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['credit', 'debit', 'fee', 'refund', 'adjustment'])->default('credit');
            $table->string('category', 50)->default('invoice_payment');
            $table->string('description', 255);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('BDT');
            $table->string('payment_method', 50)->nullable();
            $table->string('gateway_reference', 100)->nullable();
            $table->enum('status', ['completed', 'pending', 'cancelled', 'failed', 'reversed'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'category', 'status'], 'idx_transactions_filter');
            $table->index('transacted_at', 'idx_transactions_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
