<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_sms_gateways')) {
            Schema::create('tenant_sms_gateways', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('gateway_name');
                $table->string('provider'); // mim_sms, greenweb, bulksms, etc.
                $table->string('api_url')->nullable();
                $table->string('api_key')->nullable();
                $table->string('sender_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->decimal('balance', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_notification_templates')) {
            Schema::create('tenant_notification_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('event_type'); // invoice_due, welcome, renewal, etc.
                $table->enum('channel', ['sms', 'whatsapp', 'email'])->default('sms');
                $table->text('template_content');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_notification_templates');
        Schema::dropIfExists('tenant_sms_gateways');
    }
};
