<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_role')) {
                $table->string('admin_role', 60)->default('Super Administrator');
            }
            if (!Schema::hasColumn('users', 'ip_allowlist')) {
                $table->json('ip_allowlist')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_enforced')) {
                $table->boolean('two_factor_enforced')->default(false);
            }
        });

        // Filter helper to ensure only existing columns are touched
        $filterValidColumns = function (array $data) {
            $valid = [];
            foreach ($data as $key => $val) {
                if (Schema::hasColumn('users', $key)) {
                    $valid[$key] = $val;
                }
            }
            return $valid;
        };

        // Ensure Root Super Admin is updated if exists
        $rootUpdate = $filterValidColumns([
            'admin_role' => 'Super Administrator',
            'ip_allowlist' => json_encode(['103.59.177.138', '10.70.0.2']),
            'two_factor_enforced' => true,
        ]);
        if (!empty($rootUpdate)) {
            DB::table('users')->where('id', 1)->update($rootUpdate);
        }

        // Seed additional authoritative administrators for comprehensive management
        $additionalAdmins = [
            [
                'role' => 'admin',
                'admin_role' => 'Systems Engineer',
                'email' => 'sysops@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'name' => 'Tariqul Islam',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role' => 'admin',
                'admin_role' => 'Security Officer',
                'email' => 'security@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'name' => 'Farhan Ahmed',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role' => 'admin',
                'admin_role' => 'Billing Administrator',
                'email' => 'finance@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'name' => 'Nusrat Jahan',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($additionalAdmins as $adminData) {
            if (!DB::table('users')->where('email', $adminData['email'])->exists()) {
                $filtered = $filterValidColumns($adminData);
                if (!empty($filtered)) {
                    DB::table('users')->insert($filtered);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_role')) {
                $table->dropColumn('admin_role');
            }
            if (Schema::hasColumn('users', 'ip_allowlist')) {
                $table->dropColumn('ip_allowlist');
            }
            if (Schema::hasColumn('users', 'two_factor_enforced')) {
                $table->dropColumn('two_factor_enforced');
            }
        });
    }
};
