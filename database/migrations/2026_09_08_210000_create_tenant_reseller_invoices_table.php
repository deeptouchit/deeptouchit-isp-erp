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
        if (!Schema::hasTable('tenant_reseller_invoices')) {
            Schema::create('tenant_reseller_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->onDelete('cascade');
                $table->string('invoice_no', 50);
                $table->date('billing_month')->nullable();
                $table->string('type', 40)->default('PANEL_SOFTWARE_FEE'); // PANEL_SOFTWARE_FEE, BANDWIDTH_WHOLESALE, MANUAL_CHARGE
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('due_amount', 12, 2)->default(0.00);
                $table->enum('payment_status', ['PAID', 'UNPAID', 'PARTIAL', 'CANCELLED'])->default('UNPAID');
                $table->string('payment_method', 50)->nullable(); // WALLET_DEDUCT, CASH, BANK, BKASH, NAGAD
                $table->timestamp('paid_at')->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'invoice_no']);
                $table->index(['tenant_id', 'reseller_id']);
                $table->index(['tenant_id', 'payment_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_invoices');
    }
};
