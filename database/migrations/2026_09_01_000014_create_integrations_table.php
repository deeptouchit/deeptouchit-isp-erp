<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            $table->string('name', 100);
            $table->enum('category', ['dns_cdn', 'billing', 'storage', 'git_dev', 'notifications', 'migration'])->default('dns_cdn');
            $table->string('icon', 50)->default('globe');
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->string('health_status', 150)->nullable();
            $table->unsignedInteger('total_events')->default(0);
            $table->timestamps();

            $table->index(['category', 'status'], 'idx_integrations_cat_status');
        });

        // Seed Initial Authoritative Integrations
        $defaults = [
            [
                'provider' => 'cloudflare',
                'name' => 'Cloudflare DNS & Edge CDN',
                'category' => 'dns_cdn',
                'icon' => 'cloudflare',
                'credentials' => json_encode(['api_token' => 'cf_tok_••••••••••••••••', 'zone_id' => '023e105f4258f8e37']),
                'settings' => json_encode(['auto_purge' => true, 'ssl_strict' => true, 'always_online' => true]),
                'status' => 'connected',
                'last_synced_at' => now()->subMinutes(12),
                'last_health_check' => now()->subMinutes(5),
                'health_status' => 'All Zones & Edge Cache Operational',
                'total_events' => 14200,
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'provider' => 'whmcs',
                'name' => 'WHMCS Billing & Auto-Provisioning',
                'category' => 'billing',
                'icon' => 'whmcs',
                'credentials' => json_encode(['api_url' => 'https://billing.deeptouchhost.test/includes/api.php', 'api_identifier' => 'whmcs_adm_••••', 'api_secret' => 'whsec_••••••••']),
                'settings' => json_encode(['sync_clients' => true, 'auto_suspend_sync' => true, 'sso_enabled' => true]),
                'status' => 'connected',
                'last_synced_at' => now()->subMinutes(30),
                'last_health_check' => now()->subMinutes(10),
                'health_status' => 'REST API & SSO Provisioning Connected',
                'total_events' => 890,
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subMinutes(10),
            ],
            [
                'provider' => 'aws_s3',
                'name' => 'AWS S3 & Wasabi Offsite Backups',
                'category' => 'storage',
                'icon' => 'aws',
                'credentials' => json_encode(['access_key' => 'AKIA••••••••••••', 'secret_key' => '••••••••••••••••••••••••', 'bucket' => 'deeptouchhost-primary-backups', 'region' => 'ap-southeast-1']),
                'settings' => json_encode(['storage_class' => 'STANDARD', 'encryption' => 'AES256', 'lifecycle_days' => 90]),
                'status' => 'connected',
                'last_synced_at' => now()->subHours(2),
                'last_health_check' => now()->subMinutes(15),
                'health_status' => 'Bucket deeptouchhost-primary-backups Healthy',
                'total_events' => 530,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'provider' => 'telegram',
                'name' => 'Telegram Incident & Ops Bot',
                'category' => 'notifications',
                'icon' => 'telegram',
                'credentials' => json_encode(['bot_token' => '718293849:AA••••••••••••••••', 'chat_id' => '-100192837465']),
                'settings' => json_encode(['alert_security' => true, 'alert_load' => true, 'alert_backups' => true]),
                'status' => 'connected',
                'last_synced_at' => now()->subMinutes(4),
                'last_health_check' => now()->subMinutes(2),
                'health_status' => 'Bot @DeepTouchHostOpsBot Online & Polling',
                'total_events' => 1640,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMinutes(2),
            ],
            [
                'provider' => 'github',
                'name' => 'GitHub CI/CD Deployments',
                'category' => 'git_dev',
                'icon' => 'github',
                'credentials' => json_encode(['access_token' => 'ghp_••••••••••••••••', 'organization' => 'deeptouchhost-cloud']),
                'settings' => json_encode(['auto_deploy_master' => true, 'post_deploy_migrate' => true]),
                'status' => 'connected',
                'last_synced_at' => now()->subHours(6),
                'last_health_check' => now()->subMinutes(20),
                'health_status' => 'Webhooks & Deploy Keys Verified',
                'total_events' => 312,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMinutes(20),
            ],
            [
                'provider' => 'cpanel_whm',
                'name' => 'cPanel & WHM Account Importer',
                'category' => 'migration',
                'icon' => 'cpanel',
                'credentials' => json_encode(['whm_url' => '', 'access_hash' => '']),
                'settings' => json_encode(['preserve_passwords' => true, 'import_dns' => true]),
                'status' => 'disconnected',
                'last_synced_at' => null,
                'last_health_check' => null,
                'health_status' => 'Not Configured',
                'total_events' => 0,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMonths(1),
            ],
            [
                'provider' => 'google_drive',
                'name' => 'Google Cloud Storage & Drive',
                'category' => 'storage',
                'icon' => 'google',
                'credentials' => json_encode(['client_id' => '', 'client_secret' => '', 'folder_id' => '']),
                'settings' => json_encode(['compress' => true]),
                'status' => 'disconnected',
                'last_synced_at' => null,
                'last_health_check' => null,
                'health_status' => 'Credentials Missing',
                'total_events' => 0,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMonths(1),
            ],
            [
                'provider' => 'digitalocean',
                'name' => 'DigitalOcean DNS & Floating IPs',
                'category' => 'dns_cdn',
                'icon' => 'digitalocean',
                'credentials' => json_encode(['api_token' => '']),
                'settings' => json_encode(['sync_records' => true]),
                'status' => 'disconnected',
                'last_synced_at' => null,
                'last_health_check' => null,
                'health_status' => 'Awaiting API Token',
                'total_events' => 0,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMonths(1),
            ],
        ];

        DB::table('integrations')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
