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
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
                $table->foreignId('package_id')->nullable()->constrained('tenant_internet_packages')->nullOnDelete();
                $table->string('invoice_no')->unique();
                $table->string('billing_month', 20); // 2026-10
                $table->string('cycle_key', 40)->nullable();
                $table->string('package_name')->nullable();
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->decimal('vat_tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('total_payable', 10, 2)->default(0.00);
                $table->decimal('total_amount', 10, 2)->default(0.00);
                $table->decimal('paid_amount', 10, 2)->default(0.00);
                $table->decimal('due_amount', 10, 2)->default(0.00);
                $table->string('status', 30)->default('unpaid');
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method', 40)->nullable();
                $table->boolean('auto_generated')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'billing_month']);
                $table->index(['tenant_id', 'status']);
                $table->unique(['tenant_id', 'customer_id', 'cycle_key'], 'customer_invoice_cycle_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_customer_invoices');
    }
};
