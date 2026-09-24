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
        if (!Schema::hasTable('tenant_reseller_recharges')) {
            Schema::create('tenant_reseller_recharges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->onDelete('cascade');
                $table->string('recharge_no', 50);
                $table->decimal('amount', 12, 2);
                $table->decimal('bonus_amount', 12, 2)->default(0.00);
                $table->decimal('total_credited', 12, 2)->default(0.00);
                $table->string('payment_method', 50)->default('CASH'); // CASH, BKASH, NAGAD, ROCKET, BANK_TRANSFER, ONLINE_GATEWAY, CHEQUE
                $table->string('gateway_trx_id', 100)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_branch', 100)->nullable();
                $table->string('bank_account_no', 100)->nullable();
                $table->date('deposit_date')->nullable();
                $table->string('slip_path', 255)->nullable();
                $table->enum('status', ['APPROVED', 'PENDING', 'REJECTED'])->default('APPROVED');
                $table->text('rejection_reason')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->string('notes', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->unique(['tenant_id', 'recharge_no']);
                $table->index(['tenant_id', 'reseller_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'payment_method']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_recharges');
    }
};
