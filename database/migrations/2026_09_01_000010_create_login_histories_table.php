<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 150);
            $table->string('role', 50)->default('client');
            $table->string('ip_address', 45);
            $table->string('location', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 50)->default('Desktop');
            $table->string('browser', 50)->default('Chrome');
            $table->string('os', 50)->default('Windows');
            $table->enum('status', ['success', 'failed', 'blocked', '2fa_prompt'])->default('success');
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'login_at'], 'idx_login_status_at');
            $table->index('ip_address', 'idx_login_ip');
        });

        // Seed Initial Authoritative Login Audit Records
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $defaults = [
            [
                'user_id' => $adminId,
                'email' => 'admin@deeptouchhost.test',
                'role' => 'admin',
                'ip_address' => '103.59.177.138',
                'location' => 'Dhaka, Bangladesh',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                'device' => 'Desktop',
                'browser' => 'Chrome 128',
                'os' => 'Windows 11',
                'status' => 'success',
                'failure_reason' => null,
                'login_at' => now()->subMinutes(15),
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'user_id' => $adminId,
                'email' => 'admin@deeptouchhost.test',
                'role' => 'admin',
                'ip_address' => '103.59.177.138',
                'location' => 'Dhaka, Bangladesh',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
                'device' => 'Desktop',
                'browser' => 'Safari 17',
                'os' => 'macOS Sonoma',
                'status' => 'success',
                'failure_reason' => null,
                'login_at' => now()->subHours(2),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id' => null,
                'email' => 'root@deeptouchhost.test',
                'role' => 'admin',
                'ip_address' => '185.220.101.40',
                'location' => 'Frankfurt, Germany',
                'user_agent' => 'Python-urllib/3.10 (Automated Scan Probe)',
                'device' => 'Bot / Script',
                'browser' => 'Python Script',
                'os' => 'Linux',
                'status' => 'failed',
                'failure_reason' => 'Invalid password credentials provided',
                'login_at' => now()->subHours(3),
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'user_id' => null,
                'email' => 'administrator@deeptouchhost.test',
                'role' => 'admin',
                'ip_address' => '92.118.39.77',
                'location' => 'Amsterdam, Netherlands',
                'user_agent' => 'Go-http-client/1.1 (Brute-force Scanner)',
                'device' => 'Bot / Script',
                'browser' => 'Go HTTP Client',
                'os' => 'Unknown',
                'status' => 'blocked',
                'failure_reason' => 'IP banned by Fail2ban rate limiter',
                'login_at' => now()->subHours(5),
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
            [
                'user_id' => $adminId,
                'email' => 'support@deeptouchhost.test',
                'role' => 'staff',
                'ip_address' => '103.59.177.50',
                'location' => 'Chittagong, Bangladesh',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
                'device' => 'Desktop',
                'browser' => 'Firefox 130',
                'os' => 'Windows 10',
                'status' => 'success',
                'failure_reason' => null,
                'login_at' => now()->subHours(8),
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ],
            [
                'user_id' => $adminId,
                'email' => 'billing@deeptouchhost.test',
                'role' => 'staff',
                'ip_address' => '103.59.177.138',
                'location' => 'Dhaka, Bangladesh',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
                'device' => 'Mobile',
                'browser' => 'Mobile Safari',
                'os' => 'iOS 17',
                'status' => '2fa_prompt',
                'failure_reason' => '2FA OTP Verification in progress',
                'login_at' => now()->subHours(12),
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
        ];

        DB::table('login_histories')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
