<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->enum('role', ['service_account', 'admin_bot', 'reseller_api', 'read_only', 'custom'])->default('service_account');
            $table->json('scopes')->nullable();
            $table->json('ip_restrictions')->nullable();
            $table->integer('rate_limit_multiplier')->default(1);
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->timestamp('last_activity_at')->nullable();
            $table->string('last_activity_ip', 45)->nullable();
            $table->unsignedBigInteger('total_calls')->default(0);
            $table->timestamps();

            $table->index(['status', 'role'], 'idx_api_users_status_role');
        });

        // Seed Initial Authoritative API Users
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $defaults = [
            [
                'user_id' => $adminId,
                'name' => 'WHMCS Core Provisioning Bot',
                'email' => 'sa-whmcs@deeptouchhost.internal',
                'role' => 'service_account',
                'scopes' => json_encode(['accounts:manage', 'servers:read', 'dns:write']),
                'ip_restrictions' => json_encode(['103.59.177.138', '127.0.0.1']),
                'rate_limit_multiplier' => 2,
                'status' => 'active',
                'last_activity_at' => now()->subMinutes(8),
                'last_activity_ip' => '103.59.177.138',
                'total_calls' => 8420,
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subMinutes(8),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Billing Gateway Webhook Agent',
                'email' => 'sa-billing@deeptouchhost.internal',
                'role' => 'service_account',
                'scopes' => json_encode(['billing:manage', 'invoices:read']),
                'ip_restrictions' => json_encode(['103.26.247.144']),
                'rate_limit_multiplier' => 1,
                'status' => 'active',
                'last_activity_at' => now()->subHours(2),
                'last_activity_ip' => '103.26.247.144',
                'total_calls' => 3150,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id' => $adminId,
                'name' => 'Prometheus Metrics Exporter',
                'email' => 'sa-monitoring@deeptouchhost.internal',
                'role' => 'read_only',
                'scopes' => json_encode(['metrics:read', 'servers:read']),
                'ip_restrictions' => json_encode(['10.70.0.2']),
                'rate_limit_multiplier' => 5,
                'status' => 'active',
                'last_activity_at' => now()->subSeconds(20),
                'last_activity_ip' => '10.70.0.2',
                'total_calls' => 124500,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subSeconds(20),
            ],
            [
                'user_id' => $adminId,
                'name' => 'External Developer Sandbox',
                'email' => 'dev-partner@cloudagency.io',
                'role' => 'custom',
                'scopes' => json_encode(['servers:read', 'dns:read']),
                'ip_restrictions' => json_encode([]),
                'rate_limit_multiplier' => 1,
                'status' => 'suspended',
                'last_activity_at' => now()->subWeeks(2),
                'last_activity_ip' => '198.51.100.55',
                'total_calls' => 240,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subWeeks(2),
            ],
        ];

        DB::table('api_users')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_users');
    }
};
