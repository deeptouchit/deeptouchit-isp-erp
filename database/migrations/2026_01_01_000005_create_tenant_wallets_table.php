<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_wallets')) {
            Schema::create('tenant_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_wallet_transactions')) {
            Schema::create('tenant_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_wallet_id')->constrained('tenant_wallets')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_before', 12, 2)->default(0.00);
                $table->decimal('balance_after', 12, 2)->default(0.00);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_wallet_transactions');
        Schema::dropIfExists('tenant_wallets');
    }
};
