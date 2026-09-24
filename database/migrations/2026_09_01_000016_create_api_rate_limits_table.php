<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('scope_type', ['global_ip', 'authenticated_keys', 'service_accounts', 'endpoint_specific', 'whitelisted_cidr'])->default('global_ip');
            $table->string('endpoint_pattern', 255)->default('*');
            $table->integer('requests_per_minute')->default(60);
            $table->integer('burst_capacity')->default(10);
            $table->enum('action_on_breach', ['http_429', 'delay_throttle', 'temp_ip_ban', 'alert_admin'])->default('http_429');
            $table->integer('ban_duration_minutes')->default(15);
            $table->enum('status', ['active', 'disabled', 'dry_run'])->default('active');
            $table->unsignedInteger('total_breaches')->default(0);
            $table->timestamp('last_breached_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scope_type'], 'idx_rate_limits_status_scope');
        });

        // Seed Initial Authoritative Rate Limit Policies
        $defaults = [
            [
                'name' => 'Public Anonymous IP Baseline',
                'scope_type' => 'global_ip',
                'endpoint_pattern' => '/api/*',
                'requests_per_minute' => 60,
                'burst_capacity' => 10,
                'action_on_breach' => 'http_429',
                'ban_duration_minutes' => 15,
                'status' => 'active',
                'total_breaches' => 42,
                'last_breached_at' => now()->subHours(1),
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subHours(1),
            ],
            [
                'name' => 'Standard API Key Quota',
                'scope_type' => 'authenticated_keys',
                'endpoint_pattern' => '*',
                'requests_per_minute' => 300,
                'burst_capacity' => 30,
                'action_on_breach' => 'http_429',
                'ban_duration_minutes' => 15,
                'status' => 'active',
                'total_breaches' => 3,
                'last_breached_at' => now()->subHours(4),
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subHours(4),
            ],
            [
                'name' => 'Authentication & Token Minting Flood Guard',
                'scope_type' => 'endpoint_specific',
                'endpoint_pattern' => '/api/v1/auth/*',
                'requests_per_minute' => 15,
                'burst_capacity' => 5,
                'action_on_breach' => 'temp_ip_ban',
                'ban_duration_minutes' => 30,
                'status' => 'active',
                'total_breaches' => 8,
                'last_breached_at' => now()->subHours(2),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'name' => 'Service Account High-Volume Exporter',
                'scope_type' => 'service_accounts',
                'endpoint_pattern' => '/api/v1/servers/telemetry',
                'requests_per_minute' => 1200,
                'burst_capacity' => 100,
                'action_on_breach' => 'delay_throttle',
                'ban_duration_minutes' => 0,
                'status' => 'active',
                'total_breaches' => 0,
                'last_breached_at' => null,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMonths(1),
            ],
            [
                'name' => 'Billing & Invoicing Gateway Endpoints',
                'scope_type' => 'endpoint_specific',
                'endpoint_pattern' => '/api/v1/invoices/*',
                'requests_per_minute' => 120,
                'burst_capacity' => 20,
                'action_on_breach' => 'http_429',
                'ban_duration_minutes' => 15,
                'status' => 'active',
                'total_breaches' => 1,
                'last_breached_at' => now()->subDays(1),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subDays(1),
            ],
        ];

        DB::table('api_rate_limits')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
