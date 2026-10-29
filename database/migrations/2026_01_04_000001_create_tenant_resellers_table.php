<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_resellers')) {
            Schema::create('tenant_resellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('phone');
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0.00);
                $table->decimal('credit_limit', 12, 2)->default(0.00);
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->enum('billing_scheme', ['prepaid', 'postpaid'])->default('prepaid');
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_reseller_wallet_transactions')) {
            Schema::create('tenant_reseller_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->cascadeOnDelete();
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_before', 12, 2)->default(0.00);
                $table->decimal('balance_after', 12, 2)->default(0.00);
                $table->text('description')->nullable();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_reseller_recharges')) {
            Schema::create('tenant_reseller_recharges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->cascadeOnDelete();
                $table->string('recharge_code')->unique();
                $table->decimal('amount', 12, 2);
                $table->decimal('bonus_amount', 12, 2)->default(0.00);
                $table->string('payment_method')->default('cash');
                $table->string('gateway_transaction_id')->nullable();
                $table->enum('status', ['approved', 'pending', 'rejected'])->default('approved');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_recharges');
        Schema::dropIfExists('tenant_reseller_wallet_transactions');
        Schema::dropIfExists('tenant_resellers');
    }
};
