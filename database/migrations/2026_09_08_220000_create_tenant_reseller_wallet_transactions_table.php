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
        if (!Schema::hasTable('tenant_reseller_wallet_transactions')) {
            Schema::create('tenant_reseller_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->onDelete('cascade');
                $table->string('trx_id', 50);
                $table->enum('type', ['CREDIT', 'DEBIT']);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_before', 12, 2)->default(0.00);
                $table->decimal('balance_after', 12, 2)->default(0.00);
                $table->string('payment_method', 50)->default('ADMIN_ADJUSTMENT');
                $table->string('reference_no', 100)->nullable();
                $table->string('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->unique(['tenant_id', 'trx_id']);
                $table->index(['tenant_id', 'reseller_id']);
                $table->index(['tenant_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_wallet_transactions');
    }
};
