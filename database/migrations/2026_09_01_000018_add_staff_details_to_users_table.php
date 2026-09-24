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
            if (!Schema::hasColumn('users', 'is_staff')) {
                $table->boolean('is_staff')->default(false)->after('admin_role');
            }
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id', 30)->nullable()->after('is_staff');
            }
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('staff_id')->constrained('support_departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'designation')) {
                $table->string('designation', 100)->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('users', 'shift')) {
                $table->string('shift', 60)->default('Morning (08:00 - 16:00)')->after('designation');
            }
            if (!Schema::hasColumn('users', 'shift_status')) {
                $table->enum('shift_status', ['on_duty', 'off_duty', 'on_leave'])->default('on_duty')->after('shift');
            }
        });

        // Seed initial staff members if not existing
        $deptTech = DB::table('support_departments')->where('name', 'like', '%Technical%')->value('id') ?: 1;
        $deptBilling = DB::table('support_departments')->where('name', 'like', '%Billing%')->value('id') ?: 2;
        $deptSales = DB::table('support_departments')->where('name', 'like', '%Sales%')->value('id') ?: 3;
        $deptAbuse = DB::table('support_departments')->where('name', 'like', '%Abuse%')->value('id') ?: 4;

        $staffMembers = [
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'is_staff' => true,
                'staff_id' => 'SH-STF-101',
                'department_id' => $deptTech,
                'designation' => 'Senior Linux & Cloud Engineer',
                'shift' => 'Morning (08:00 - 16:00)',
                'shift_status' => 'on_duty',
                'username' => 'rakib_tech',
                'email' => 'rakib.tech@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'first_name' => 'Rakibul',
                'last_name' => 'Hasan',
                'phone' => '+8801720000101',
                'status' => 'active',
                'two_factor_enforced' => true,
                'two_factor_confirmed_at' => now()->subMonths(2),
                'last_login_ip' => '103.59.177.138',
                'last_login_at' => now()->subMinutes(40),
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subMinutes(40),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'is_staff' => true,
                'staff_id' => 'SH-STF-102',
                'department_id' => $deptTech,
                'designation' => 'Technical Support Specialist (L1)',
                'shift' => 'Evening (16:00 - 00:00)',
                'shift_status' => 'on_duty',
                'username' => 'mehedi_sup',
                'email' => 'mehedi.sup@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'first_name' => 'Mehedi',
                'last_name' => 'Zaman',
                'phone' => '+8801720000102',
                'status' => 'active',
                'two_factor_enforced' => true,
                'two_factor_confirmed_at' => now()->subMonths(1),
                'last_login_ip' => '103.59.177.138',
                'last_login_at' => now()->subHours(2),
                'created_at' => now()->subMonths(3),
                'updated_at' => now()->subHours(2),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'is_staff' => true,
                'staff_id' => 'SH-STF-103',
                'department_id' => $deptBilling,
                'designation' => 'Accounts & Invoicing Specialist',
                'shift' => 'Morning (08:00 - 16:00)',
                'shift_status' => 'on_duty',
                'username' => 'sultana_bill',
                'email' => 'sultana.billing@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'first_name' => 'Sultana',
                'last_name' => 'Razia',
                'phone' => '+8801820000103',
                'status' => 'active',
                'two_factor_enforced' => true,
                'two_factor_confirmed_at' => now()->subMonths(1),
                'last_login_ip' => '103.26.247.144',
                'last_login_at' => now()->subHours(5),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subHours(5),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'is_staff' => true,
                'staff_id' => 'SH-STF-104',
                'department_id' => $deptSales,
                'designation' => 'Enterprise Sales Consultant',
                'shift' => 'Morning (08:00 - 16:00)',
                'shift_status' => 'off_duty',
                'username' => 'tanvir_sales',
                'email' => 'tanvir.sales@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'first_name' => 'Tanvir',
                'last_name' => 'Mahmud',
                'phone' => '+8801920000104',
                'status' => 'active',
                'two_factor_enforced' => false,
                'two_factor_confirmed_at' => null,
                'last_login_ip' => '103.59.177.138',
                'last_login_at' => now()->subDays(1),
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subDays(1),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'is_staff' => true,
                'staff_id' => 'SH-STF-105',
                'department_id' => $deptAbuse,
                'designation' => 'NOC & Abuse Investigation Officer',
                'shift' => 'Night (00:00 - 08:00)',
                'shift_status' => 'on_duty',
                'username' => 'shovon_noc',
                'email' => 'shovon.noc@deeptouchhost.com',
                'password' => Hash::make('DeepTouchHost@2026!'),
                'first_name' => 'Ashfaqur',
                'last_name' => 'Rahman',
                'phone' => '+8801720000105',
                'status' => 'active',
                'two_factor_enforced' => true,
                'two_factor_confirmed_at' => now()->subMonths(1),
                'last_login_ip' => '103.59.177.138',
                'last_login_at' => now()->subHours(1),
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subHours(1),
            ],
        ];

        foreach ($staffMembers as $staff) {
            if (!DB::table('users')->where('email', $staff['email'])->exists()) {
                $valid = [];
                foreach ($staff as $key => $val) {
                    if (Schema::hasColumn('users', $key)) {
                        $valid[$key] = $val;
                    }
                }
                if (!isset($valid['name']) && isset($staff['first_name'])) {
                    $valid['name'] = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
                }
                if (!empty($valid)) {
                    DB::table('users')->insert($valid);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_staff')) {
                $table->dropColumn('is_staff');
            }
            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('users', 'designation')) {
                $table->dropColumn('designation');
            }
            if (Schema::hasColumn('users', 'shift')) {
                $table->dropColumn('shift');
            }
            if (Schema::hasColumn('users', 'shift_status')) {
                $table->dropColumn('shift_status');
            }
        });
    }
};
