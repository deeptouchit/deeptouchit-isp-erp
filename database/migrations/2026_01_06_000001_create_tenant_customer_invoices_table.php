<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_customer_invoices')) {
            Schema::create('tenant_customer_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
                $table->string('invoice_no')->unique();
                $table->string('billing_month', 20); // 2026-10
                $table->string('package_name')->nullable();
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->decimal('vat', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('total_amount', 10, 2)->default(0.00);
                $table->decimal('paid_amount', 10, 2)->default(0.00);
                $table->decimal('due_amount', 10, 2)->default(0.00);
                $table->enum('status', ['unpaid', 'paid', 'partial', 'cancelled'])->default('unpaid');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'billing_month']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_customer_invoices');
    }
};
