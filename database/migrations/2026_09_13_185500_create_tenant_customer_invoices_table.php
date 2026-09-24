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
        if (!Schema::hasTable('tenant_customer_invoices')) {
            Schema::create('tenant_customer_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
                $table->string('invoice_no', 60)->index();
                $table->string('billing_month', 20)->default(date('Y-m'))->index(); // e.g. 2026-09
                $table->foreignId('package_id')->nullable()->constrained('tenant_internet_packages')->nullOnDelete();
                $table->string('package_name', 150)->nullable();
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->decimal('discount', 12, 2)->default(0.00);
                $table->decimal('vat_tax', 12, 2)->default(0.00);
                $table->decimal('total_payable', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('due_amount', 12, 2)->default(0.00);
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->string('payment_method', 50)->nullable(); // cash, bkash, nagad, rocket, pos, online, bank
                $table->string('status', 30)->default('unpaid')->index(); // paid, unpaid, partially_paid, overdue, cancelled
                $table->boolean('auto_generated')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'invoice_no']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_customer_invoices');
    }
};
