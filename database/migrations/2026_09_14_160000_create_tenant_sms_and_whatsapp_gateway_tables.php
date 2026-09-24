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
        // 1. Tenant SMS & WhatsApp Gateways Configuration Table
        if (!Schema::hasTable('tenant_sms_gateways')) {
            Schema::create('tenant_sms_gateways', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('channel_type')->default('sms'); // sms, whatsapp
                $table->string('provider'); // greenweb, onnorokom, alphanet, twilio, meta_whatsapp, ultramsg, custom_http
                $table->string('name');
                $table->string('sender_id')->nullable(); // Masking Sender Name / Number
                $table->string('api_endpoint')->nullable();
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->string('account_sid')->nullable();
                $table->string('http_method')->default('POST'); // GET, POST
                $table->json('custom_headers')->nullable();
                $table->json('custom_params')->nullable();
                $table->decimal('cost_per_sms', 8, 4)->default(0.2500);
                $table->decimal('balance', 12, 2)->default(1000.00);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }

        // 2. Notification Event Triggers & Templates Table
        if (!Schema::hasTable('tenant_notification_templates')) {
            Schema::create('tenant_notification_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('event_trigger')->unique(); // bill_generated, payment_received, expiry_warning, autocut_suspend, account_welcome, ticket_update, otp_verification
                $table->string('title');
                $table->text('sms_body')->nullable();
                $table->text('whatsapp_body')->nullable();
                $table->boolean('send_sms')->default(true);
                $table->boolean('send_whatsapp')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_notification_templates');
        Schema::dropIfExists('tenant_sms_gateways');
    }
};
