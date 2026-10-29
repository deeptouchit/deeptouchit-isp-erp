<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_gateway_transactions')) {
            Schema::create('tenant_gateway_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('tenant_customers')->nullOnDelete();
                $table->string('gateway'); // bkash, nagad, sslcommerz
                $table->string('trx_id')->unique();
                $table->decimal('amount', 10, 2);
                $table->decimal('fee', 10, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->enum('status', ['initiated', 'completed', 'failed', 'refunded'])->default('initiated');
                $table->string('intent')->default('bill_payment');
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_expense_categories')) {
            Schema::create('tenant_expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', ['expense', 'income'])->default('expense');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_expense_transactions')) {
            Schema::create('tenant_expense_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('tenant_expense_categories')->cascadeOnDelete();
                $table->string('voucher_no')->unique();
                $table->enum('type', ['expense', 'income'])->default('expense');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash');
                $table->date('date');
                $table->string('paid_to')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['tenant_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_expense_transactions');
        Schema::dropIfExists('tenant_expense_categories');
        Schema::dropIfExists('tenant_gateway_transactions');
    }
};
