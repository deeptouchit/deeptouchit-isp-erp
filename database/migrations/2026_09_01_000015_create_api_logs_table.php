<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->foreignId('api_user_id')->nullable()->constrained('api_users')->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->integer('status_code');
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->integer('duration_ms')->default(0);
            $table->json('request_headers')->nullable();
            $table->json('request_payload')->nullable();
            $table->text('response_body')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['status_code', 'created_at'], 'idx_api_logs_status_time');
            $table->index(['ip_address', 'created_at'], 'idx_api_logs_ip_time');
        });

        // Seed Initial Authoritative REST API Logs
        $apiKeyId = DB::table('api_keys')->value('id');
        $apiUserId = DB::table('api_users')->value('id');

        $defaults = [
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => $apiUserId,
                'method' => 'POST',
                'endpoint' => '/api/v1/accounts/provision',
                'status_code' => 201,
                'ip_address' => '103.59.177.138',
                'user_agent' => 'WHMCS-DeepTouchHost-Module/3.2.0',
                'duration_ms' => 112,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••', 'Content-Type' => 'application/json']),
                'request_payload' => json_encode(['username' => 'client_cloud88', 'domain' => 'clientdomain.com', 'plan' => 'Enterprise Cloud', 'quota_gb' => 50]),
                'response_body' => json_encode(['success' => true, 'account_id' => 842, 'status' => 'active', 'ip' => '103.59.177.138']),
                'error_message' => null,
                'created_at' => now()->subMinutes(6),
                'updated_at' => now()->subMinutes(6),
            ],
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => $apiUserId,
                'method' => 'GET',
                'endpoint' => '/api/v1/servers/telemetry',
                'status_code' => 200,
                'ip_address' => '10.70.0.2',
                'user_agent' => 'Prometheus-Exporter/2.45.0',
                'duration_ms' => 18,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••']),
                'request_payload' => null,
                'response_body' => json_encode(['cpu_percent' => 12.4, 'ram_used_mb' => 4820, 'ram_total_mb' => 32150, 'load_avg' => [0.85, 0.92, 0.78]]),
                'error_message' => null,
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => $apiUserId,
                'method' => 'POST',
                'endpoint' => '/api/v1/dns/records',
                'status_code' => 200,
                'ip_address' => '103.59.177.138',
                'user_agent' => 'WHMCS-DeepTouchHost-Module/3.2.0',
                'duration_ms' => 45,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••', 'Content-Type' => 'application/json']),
                'request_payload' => json_encode(['zone' => 'clientdomain.com', 'type' => 'A', 'name' => '@', 'content' => '103.59.177.138', 'ttl' => 3600]),
                'response_body' => json_encode(['success' => true, 'record_id' => 'rec_91823', 'synced' => true]),
                'error_message' => null,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => $apiUserId,
                'method' => 'POST',
                'endpoint' => '/api/v1/invoices/pay',
                'status_code' => 200,
                'ip_address' => '103.26.247.144',
                'user_agent' => 'Payment-Gateway-Hook/1.0',
                'duration_ms' => 88,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••', 'Content-Type' => 'application/json']),
                'request_payload' => json_encode(['invoice_id' => 1048, 'transaction_id' => 'TXN-BK-91823', 'amount' => 49.00]),
                'response_body' => json_encode(['success' => true, 'status' => 'paid', 'paid_at' => now()->toISOString()]),
                'error_message' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'api_key_id' => null,
                'api_user_id' => null,
                'method' => 'GET',
                'endpoint' => '/api/v1/admin/secrets',
                'status_code' => 401,
                'ip_address' => '198.51.100.99',
                'user_agent' => 'curl/7.88.1',
                'duration_ms' => 5,
                'request_headers' => json_encode(['User-Agent' => 'curl/7.88.1']),
                'request_payload' => null,
                'response_body' => json_encode(['error' => 'Unauthenticated', 'message' => 'Invalid or missing Bearer token.']),
                'error_message' => 'Invalid or missing Bearer token',
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => $apiUserId,
                'method' => 'POST',
                'endpoint' => '/api/v1/accounts/mass-delete',
                'status_code' => 403,
                'ip_address' => '198.51.100.55',
                'user_agent' => 'Custom-Integration-Script/2.1',
                'duration_ms' => 8,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••']),
                'request_payload' => json_encode(['account_ids' => [1, 2, 3]]),
                'response_body' => json_encode(['error' => 'Forbidden', 'message' => 'Token lacks required ability accounts:mass-delete.']),
                'error_message' => 'Token lacks required ability accounts:mass-delete',
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ],
            [
                'api_key_id' => $apiKeyId,
                'api_user_id' => null,
                'method' => 'POST',
                'endpoint' => '/api/v1/servers/restart',
                'status_code' => 429,
                'ip_address' => '203.0.113.80',
                'user_agent' => 'Python-Requests/2.31.0',
                'duration_ms' => 3,
                'request_headers' => json_encode(['Authorization' => 'Bearer sh_live_••••••••']),
                'request_payload' => json_encode(['service' => 'nginx']),
                'response_body' => json_encode(['error' => 'Too Many Requests', 'retry_after_seconds' => 42]),
                'error_message' => 'Rate limit of 60 req/min exceeded',
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
        ];

        DB::table('api_logs')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
