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
        if (!Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('plan_id');
                $table->string('billing_cycle', 255)->default('monthly');
                $table->timestamp('started_at')->nullable();
                $table->date('current_period_start')->nullable();
                $table->date('current_period_end')->nullable();
                $table->date('next_billing_date')->nullable();
                $table->timestamp('grace_period_ends_at')->nullable();
                $table->string('status', 255)->default('active');
                $table->boolean('auto_renew')->default('1');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['plan_id'], 'tenant_subscriptions_plan_id_foreign');
                $table->index(['tenant_id'], 'tenant_subscriptions_tenant_id_foreign');
                $table->foreign(['plan_id'], 'tenant_subscriptions_plan_id_foreign')->references(['id'])->on('saas_plans')->onUpdate('no action')->onDelete('cascade');
                $table->foreign(['tenant_id'], 'tenant_subscriptions_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
