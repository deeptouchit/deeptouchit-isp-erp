<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_reseller_invoices')) {
            Schema::create('tenant_reseller_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->cascadeOnDelete();
                $table->string('invoice_no')->unique();
                $table->string('billing_month', 20); // e.g. 2026-10
                $table->decimal('subtotal', 12, 2)->default(0.00);
                $table->decimal('tax', 12, 2)->default(0.00);
                $table->decimal('discount', 12, 2)->default(0.00);
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('due_amount', 12, 2)->default(0.00);
                $table->enum('payment_status', ['paid', 'partial', 'unpaid', 'cancelled'])->default('unpaid');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'billing_month']);
            });
        }

        if (!Schema::hasTable('tenant_reseller_escalations')) {
            Schema::create('tenant_reseller_escalations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->cascadeOnDelete();
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->string('escalated_to')->nullable();
                $table->text('reason');
                $table->enum('status', ['pending', 'investigating', 'resolved'])->default('pending');
                $table->timestamp('resolved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_escalations');
        Schema::dropIfExists('tenant_reseller_invoices');
    }
};
