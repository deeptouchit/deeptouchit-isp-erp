<?php

namespace Database\Seeders;

use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Platform Owner
        $owner = User::updateOrCreate(
            ['email' => 'owner@somitysoft.com'],
            [
                'name' => 'Platform Owner',
                'phone' => '01700000000',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        // 2. Create SaaS Plans
        $planBasic = SaasPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter Plan',
                'monthly_price' => 1500.00,
                'yearly_price' => 15000.00,
                'customer_limit' => 300,
                'mikrotik_limit' => 2,
                'reseller_limit' => 3,
                'features' => ['Mikrotik Auto Sync', 'SMS Gateway', 'Customer Portal', 'Daily Backup'],
                'is_active' => true,
            ]
        );

        $planPro = SaasPlan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Professional Enterprise',
                'monthly_price' => 3500.00,
                'yearly_price' => 35000.00,
                'customer_limit' => 1500,
                'mikrotik_limit' => 5,
                'reseller_limit' => 15,
                'features' => ['Unlimited Mikrotik', 'OLT/PON Monitoring', 'Reseller Sub-domains', 'bKash/Nagad PGW', 'Dedicated Support'],
                'is_active' => true,
            ]
        );

        // 3. Create a Demo ISP Tenant
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'speednet'],
            [
                'name' => 'SpeedNet Online ISP',
                'company_name' => 'SpeedNet Communications Ltd.',
                'domain' => 'speednet.somitysoft.com',
                'phone' => '01800000000',
                'email' => 'admin@speednet.com',
                'address' => 'Mirpur-10, Dhaka-1216',
                'saas_plan_id' => $planPro->id,
                'status' => 'active',
                'subscription_expires_at' => now()->addYear(),
                'wallet_balance' => 5000.00,
            ]
        );

        // 4. Create ISP Admin for this Tenant
        User::updateOrCreate(
            ['email' => 'admin@speednet.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'SpeedNet Admin',
                'phone' => '01800000000',
                'password' => Hash::make('password'),
                'role' => 'isp_admin',
                'status' => 'active',
            ]
        );
    }
}
