<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('key_prefix', 16);
            $table->string('secret_hash', 255);
            $table->json('abilities')->nullable();
            $table->json('ip_allowlist')->nullable();
            $table->integer('rate_limit_per_minute')->default(60);
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_api_keys_status');
            $table->index('key_prefix', 'idx_api_keys_prefix');
        });

        // Seed Initial Authoritative API Keys
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $defaults = [
            [
                'user_id' => $adminId,
                'name' => 'WHMCS Automation Provisioner',
                'key_prefix' => 'sh_live_8f3a9e',
                'secret_hash' => Hash::make('sh_live_8f3a9e1029384756abcdef1234567890'),
                'abilities' => json_encode(['accounts:manage', 'servers:read', 'dns:write']),
                'ip_allowlist' => json_encode(['103.59.177.138', '127.0.0.1']),
                'rate_limit_per_minute' => 120,
                'status' => 'active',
                'last_used_at' => now()->subMinutes(12),
                'last_used_ip' => '103.59.177.138',
                'expires_at' => now()->addYear(),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMinutes(12),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Admin Mobile App API Key',
                'key_prefix' => 'sh_live_4b7c12',
                'secret_hash' => Hash::make('sh_live_4b7c129988776655fedcba0987654321'),
                'abilities' => json_encode(['*']),
                'ip_allowlist' => json_encode([]),
                'rate_limit_per_minute' => 60,
                'status' => 'active',
                'last_used_at' => now()->subHours(1),
                'last_used_ip' => '103.26.247.144',
                'expires_at' => null,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subHours(1),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Prometheus Telemetry Scraper',
                'key_prefix' => 'sh_live_1d9f88',
                'secret_hash' => Hash::make('sh_live_1d9f8800112233445566778899aabbcc'),
                'abilities' => json_encode(['metrics:read', 'servers:read']),
                'ip_allowlist' => json_encode(['10.70.0.2', '127.0.0.1']),
                'rate_limit_per_minute' => 300,
                'status' => 'active',
                'last_used_at' => now()->subSeconds(30),
                'last_used_ip' => '10.70.0.2',
                'expires_at' => now()->addMonths(6),
                'created_at' => now()->subWeeks(3),
                'updated_at' => now()->subSeconds(30),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Legacy Staging Webhook Token',
                'key_prefix' => 'sh_test_7a2b90',
                'secret_hash' => Hash::make('sh_test_7a2b90112233445566778899aabbccdd'),
                'abilities' => json_encode(['webhooks:receive']),
                'ip_allowlist' => json_encode([]),
                'rate_limit_per_minute' => 60,
                'status' => 'revoked',
                'last_used_at' => now()->subMonths(1),
                'last_used_ip' => '198.51.100.55',
                'expires_at' => now()->subDays(10),
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subDays(10),
            ],
        ];

        DB::table('api_keys')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
