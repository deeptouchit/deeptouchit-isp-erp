<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('url', 255);
            $table->string('secret', 100);
            $table->json('events')->nullable();
            $table->string('content_type', 50)->default('application/json');
            $table->boolean('verify_ssl')->default(true);
            $table->enum('status', ['active', 'paused', 'failing'])->default('active');
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('last_response_code')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_webhooks_status');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->string('event', 100);
            $table->json('payload')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms')->default(0);
            $table->enum('status', ['success', 'failed', 'retrying'])->default('success');
            $table->timestamp('delivered_at')->useCurrent();
            $table->timestamps();

            $table->index(['webhook_id', 'delivered_at'], 'idx_webhook_deliv_time');
        });

        // Seed Initial Authoritative Webhooks
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $webhookId = DB::table('webhooks')->insertGetId([
            'user_id' => $adminId,
            'name' => 'WHMCS Provisioning & Billing Sync',
            'url' => 'https://billing.deeptouchhost.test/modules/servers/deeptouchhost/webhook.php',
            'secret' => 'whsec_' . bin2hex(random_bytes(16)),
            'events' => json_encode(['hosting.account.created', 'hosting.account.suspended', 'invoice.paid']),
            'content_type' => 'application/json',
            'verify_ssl' => true,
            'status' => 'active',
            'last_triggered_at' => now()->subMinutes(14),
            'last_response_code' => 200,
            'success_count' => 412,
            'failure_count' => 0,
            'created_at' => now()->subMonths(2),
            'updated_at' => now()->subMinutes(14),
        ]);

        DB::table('webhook_deliveries')->insert([
            [
                'webhook_id' => $webhookId,
                'event' => 'hosting.account.created',
                'payload' => json_encode(['account' => 'client77', 'domain' => 'mybrand.com', 'plan' => 'Pro Cloud']),
                'response_code' => 200,
                'response_body' => '{"success":true,"provision_id":1029}',
                'response_time_ms' => 142,
                'status' => 'success',
                'delivered_at' => now()->subMinutes(14),
                'created_at' => now()->subMinutes(14),
                'updated_at' => now()->subMinutes(14),
            ],
            [
                'webhook_id' => $webhookId,
                'event' => 'invoice.paid',
                'payload' => json_encode(['invoice_number' => 'INV-2026-0042', 'amount' => 49.00, 'currency' => 'USD']),
                'response_code' => 200,
                'response_body' => '{"acknowledged":true}',
                'response_time_ms' => 88,
                'status' => 'success',
                'delivered_at' => now()->subHours(1),
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subHours(1),
            ],
        ]);

        DB::table('webhooks')->insert([
            [
                'user_id' => $adminId,
                'name' => 'Discord Ops & Security Alerts',
                'url' => 'https://discord.com/api/webhooks/128391823/deeptouchhost-sec-alerts',
                'secret' => 'whsec_' . bin2hex(random_bytes(16)),
                'events' => json_encode(['security.ip_banned', 'server.alert.triggered']),
                'content_type' => 'application/json',
                'verify_ssl' => true,
                'status' => 'active',
                'last_triggered_at' => now()->subHours(3),
                'last_response_code' => 204,
                'success_count' => 1890,
                'failure_count' => 2,
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Slack Support Team Channel',
                'url' => 'https://hooks.slack.com/services/T00000000/B00000000/X00000000',
                'secret' => 'whsec_' . bin2hex(random_bytes(16)),
                'events' => json_encode(['ticket.created', 'ticket.reply']),
                'content_type' => 'application/json',
                'verify_ssl' => true,
                'status' => 'active',
                'last_triggered_at' => now()->subHours(5),
                'last_response_code' => 200,
                'success_count' => 845,
                'failure_count' => 0,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subHours(5),
            ],
            [
                'user_id' => $adminId,
                'name' => 'External CRM Ingestion Endpoint',
                'url' => 'https://crm.partner-corp.io/api/v2/webhooks/ingest',
                'secret' => 'whsec_' . bin2hex(random_bytes(16)),
                'events' => json_encode(['customer.created', 'invoice.paid']),
                'content_type' => 'application/json',
                'verify_ssl' => true,
                'status' => 'failing',
                'last_triggered_at' => now()->subMinutes(45),
                'last_response_code' => 502,
                'success_count' => 120,
                'failure_count' => 18,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMinutes(45),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
