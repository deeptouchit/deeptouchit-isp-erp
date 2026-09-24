<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tenant Activity & Audit Trail Table
        if (!Schema::hasTable('tenant_activity_logs')) {
            Schema::create('tenant_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                $table->string('actor_type')->default('owner'); // owner, tenant_admin, system_cron
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name')->nullable();
                $table->string('event_type'); // tenant_created, status_toggled, subscription_extended, plan_switched, wallet_adjusted, impersonated, service_suspended, service_restored
                $table->text('description');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'event_type']);
            });
        }

        // 2. SMS Activity & Cost Consumption Ledger Table
        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
                $table->string('gateway_name')->default('Greenweb'); // Greenweb, Onnorokom, ElitBuzz, BulkSMSBD, Custom
                $table->string('recipient_phone', 30);
                $table->string('sms_type')->default('general'); // billing_reminder, due_notice, payment_receipt, account_suspended, welcome_sms, test_sms
                $table->text('message_body');
                $table->unsignedInteger('character_count')->default(0);
                $table->unsignedSmallInteger('parts_count')->default(1);
                $table->decimal('cost_per_part', 8, 4)->default(0.3500); // ৳0.35
                $table->decimal('total_cost', 10, 4)->default(0.3500);
                $table->string('status')->default('delivered'); // delivered, sent, failed, pending
                $table->text('api_response')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'sms_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('tenant_activity_logs');
    }
};
