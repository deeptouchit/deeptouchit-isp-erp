<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expense & Income Categories Table (Chart of Accounts)
        if (!Schema::hasTable('tenant_expense_categories')) {
            Schema::create('tenant_expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name', 100);
                $table->enum('type', ['EXPENSE', 'INCOME'])->default('EXPENSE');
                $table->string('code', 30)->nullable();
                $table->decimal('monthly_budget', 12, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->string('icon', 50)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'type', 'is_active'], 'idx_tec_filter');
            });
        }

        // 2. Expense & Income Vouchers / Transactions Table
        if (!Schema::hasTable('tenant_expense_transactions')) {
            Schema::create('tenant_expense_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('tenant_expense_categories')->nullOnDelete();
                $table->string('voucher_no', 50);
                $table->enum('type', ['EXPENSE', 'INCOME'])->default('EXPENSE');
                $table->string('title', 200);
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->string('payment_method', 50)->default('CASH'); // CASH, BANK, BKASH, NAGAD, CHEQUE, PETTY_CASH
                $table->string('account_name', 100)->nullable();
                $table->string('payee_payer', 150)->nullable();
                $table->string('reference_no', 100)->nullable();
                $table->date('transaction_date');
                $table->enum('status', ['APPROVED', 'PENDING', 'REJECTED'])->default('APPROVED');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'voucher_no'], 'uniq_tet_tenant_vouch');
                $table->index(['tenant_id', 'type', 'transaction_date'], 'idx_tet_type_date');
                $table->index(['tenant_id', 'status'], 'idx_tet_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_expense_transactions');
        Schema::dropIfExists('tenant_expense_categories');
    }
};
