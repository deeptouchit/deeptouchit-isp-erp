<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh-install definitions verified against live schema on 2026-11-17.
        // Existing tables are never altered or backfilled by this migration.
        if (!Schema::hasTable('saas_invoices')) {
            Schema::create('saas_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('tenant_subscription_id')->nullable();
                $table->unsignedBigInteger('saas_plan_id')->nullable();
                $table->string('invoice_no', 255);
                $table->decimal('amount', 10, 2);
                $table->decimal('subtotal', 10, 2)->default('0.00');
                $table->decimal('discount', 10, 2)->default('0.00');
                $table->decimal('tax', 10, 2)->default('0.00');
                $table->decimal('paid_amount', 10, 2)->default('0.00');
                $table->decimal('credit_amount', 10, 2)->default('0.00');
                $table->decimal('due_amount', 10, 2)->default('0.00');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->enum('status', ['paid', 'unpaid', 'cancelled'])->default('unpaid');
                $table->string('payment_method', 255)->nullable();
                $table->string('trx_id', 255)->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique(['invoice_no'], 'saas_invoices_invoice_no_unique');
                $table->index(['saas_plan_id'], 'saas_invoices_saas_plan_id_foreign');
                $table->index(['tenant_id'], 'saas_invoices_tenant_id_foreign');
                $table->index(['tenant_subscription_id'], 'saas_invoices_tenant_subscription_id_foreign');
                $table->foreign(['saas_plan_id'], 'saas_invoices_saas_plan_id_foreign')->references(['id'])->on('saas_plans')->onUpdate('no action')->onDelete('set null');
                $table->foreign(['tenant_id'], 'saas_invoices_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
                $table->foreign(['tenant_subscription_id'], 'saas_invoices_tenant_subscription_id_foreign')->references(['id'])->on('tenant_subscriptions')->onUpdate('no action')->onDelete('set null');
            });
        }
        if (!Schema::hasTable('subscription_invoice_items')) {
            Schema::create('subscription_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('saas_invoice_id');
                $table->string('description', 255);
                $table->integer('quantity')->default('1');
                $table->decimal('unit_price', 10, 2)->default('0.00');
                $table->decimal('total_price', 10, 2)->default('0.00');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['saas_invoice_id'], 'subscription_invoice_items_saas_invoice_id_foreign');
                $table->foreign(['saas_invoice_id'], 'subscription_invoice_items_saas_invoice_id_foreign')->references(['id'])->on('saas_invoices')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_items');
        Schema::dropIfExists('saas_invoices');
    }
};
