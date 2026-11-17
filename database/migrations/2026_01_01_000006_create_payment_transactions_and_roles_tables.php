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
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('saas_invoice_id')->nullable();
                $table->string('payment_method', 255)->default('online');
                $table->string('gateway', 255)->nullable();
                $table->string('transaction_reference', 255);
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('BDT');
                $table->string('status', 255)->default('pending');
                $table->json('gateway_response')->nullable();
                $table->timestamp('initiated_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['saas_invoice_id'], 'payment_transactions_saas_invoice_id_foreign');
                $table->index(['tenant_id'], 'payment_transactions_tenant_id_foreign');
                $table->unique(['transaction_reference'], 'payment_transactions_transaction_reference_unique');
                $table->foreign(['saas_invoice_id'], 'payment_transactions_saas_invoice_id_foreign')->references(['id'])->on('saas_invoices')->onUpdate('no action')->onDelete('set null');
                $table->foreign(['tenant_id'], 'payment_transactions_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
            });
        }
        if (!Schema::hasTable('tenant_roles')) {
            Schema::create('tenant_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name', 50);
                $table->string('display_name', 100);
                $table->text('description')->nullable();
                $table->string('color', 30)->default('purple');
                $table->string('icon', 50)->default('fa-shield-halved');
                $table->boolean('is_system')->default('0');
                $table->json('permissions')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['tenant_id'], 'tenant_roles_tenant_id_index');
                $table->unique(['tenant_id', 'name'], 'tenant_roles_tenant_id_name_unique');
                $table->foreign(['tenant_id'], 'tenant_roles_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_roles');
        Schema::dropIfExists('payment_transactions');
    }
};
