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
        if (!Schema::hasTable('tenant_gateway_transactions')) {
            Schema::create('tenant_gateway_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('transaction_id', 80)->unique();
                $table->string('gateway_trx_id', 100)->nullable()->index();
                $table->string('gateway', 50)->default('bkash')->index(); // bkash, nagad, rocket, sslcommerz, shurjopay, upay, aamarpay, stripe
                $table->string('purpose', 50)->default('CUSTOMER_BILL')->index(); // CUSTOMER_BILL, CUSTOMER_RECHARGE, RESELLER_TOPUP, MANUAL
                $table->foreignId('customer_id')->nullable()->constrained('tenant_customers')->nullOnDelete();
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reference_id', 80)->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->decimal('fee_amount', 10, 2)->default(0.00);
                $table->decimal('net_amount', 12, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->string('payer_account', 50)->nullable();
                $table->string('payer_name', 100)->nullable();
                $table->enum('status', ['SUCCESS', 'PENDING', 'FAILED', 'CANCELLED', 'REFUNDED'])->default('PENDING')->index();
                $table->string('status_message', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('gateway_payload')->nullable();
                $table->timestamp('initiated_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'gateway']);
                $table->index(['tenant_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_gateway_transactions');
    }
};
