<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('group', 50)->default('general');
                $table->timestamps();
            });

            // Seed default 2FA IAM policies
            $defaultSettings = [
                [
                    'key' => 'two_factor.admin_enforcement_policy',
                    'value' => 'enforced_all',
                    'group' => 'security',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'two_factor.grace_period_days',
                    'value' => '3',
                    'group' => 'security',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'two_factor.allowed_methods',
                    'value' => json_encode(['totp_authenticator', 'email_otp', 'webauthn_hardware']),
                    'group' => 'security',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'two_factor.remember_device_days',
                    'value' => '30',
                    'group' => 'security',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'two_factor.client_policy',
                    'value' => 'optional',
                    'group' => 'security',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('system_settings')->insert($defaultSettings);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
