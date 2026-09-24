<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantInternetPackage;
use Illuminate\Database\Seeder;

class TenantResellerDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();
        if ($tenants->isEmpty()) {
            return;
        }

        foreach ($tenants as $tenant) {
            // 1. Franchise Prepaid Partner
            $reseller1 = TenantReseller::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'RES-001'],
                [
                    'name' => 'Chittagong Cyber Net (Pvt) Ltd',
                    'prefix' => 'ccn',
                    'contact_person' => 'Engr. Tanvir Ahmed',
                    'mobile' => '01819-234567',
                    'email' => 'tanvir@ctgcybernet.com',
                    'address' => 'House #12, Road #4, Agrabad Commercial Area, Chattogram',
                    'billing_type' => 'PREPAID_WALLET',
                    'wallet_balance' => 25000.00,
                    'credit_limit' => 10000.00,
                    'commission_rate' => 30.00,
                    'monthly_panel_charge' => 500.00,
                    'panel_expiry_date' => now()->addDays(28)->toDateString(),
                    'panel_billing_status' => 'ACTIVE',
                    'status' => 'active',
                    'notes' => 'Primary Sub-ISP partner in Agrabad zone. Operating 220+ FTTH active subscribers.'
                ]
            );

            // 2. Postpaid Trunk Partner
            $reseller2 = TenantReseller::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'RES-002'],
                [
                    'name' => 'Sylhet Speed Fiber Link',
                    'prefix' => 'sfl',
                    'contact_person' => 'Md. Mahbubur Rahman',
                    'mobile' => '01712-890123',
                    'email' => 'mahbub@speedfiberbd.com',
                    'address' => 'Zindabazar Plaza, 3rd Floor, Sylhet',
                    'billing_type' => 'POSTPAID_MONTHLY',
                    'wallet_balance' => 5400.00,
                    'credit_limit' => 30000.00,
                    'commission_rate' => 25.00,
                    'monthly_panel_charge' => 750.00,
                    'panel_expiry_date' => now()->addDays(45)->toDateString(),
                    'panel_billing_status' => 'ACTIVE',
                    'status' => 'active',
                    'notes' => 'Postpaid corporate partner with 1Gbps trunk lease contract.'
                ]
            );

            // 4. Demo Recharge Records
            \App\Models\TenantResellerRecharge::updateOrCreate(
                ['tenant_id' => $tenant->id, 'recharge_no' => 'RCH-' . date('Ymd') . '-0001'],
                [
                    'reseller_id' => $reseller1->id,
                    'amount' => 15000.00,
                    'bonus_amount' => 500.00,
                    'total_credited' => 15500.00,
                    'payment_method' => 'BKASH',
                    'gateway_trx_id' => 'BK98X726419',
                    'status' => 'APPROVED',
                    'approved_at' => now()->subHours(4),
                    'notes' => 'Monthly wallet top-up via bKash Merchant payment.'
                ]
            );

            \App\Models\TenantResellerRecharge::updateOrCreate(
                ['tenant_id' => $tenant->id, 'recharge_no' => 'RCH-' . date('Ymd') . '-0002'],
                [
                    'reseller_id' => $reseller2->id,
                    'amount' => 25000.00,
                    'bonus_amount' => 0.00,
                    'total_credited' => 25000.00,
                    'payment_method' => 'BANK_TRANSFER',
                    'bank_name' => 'Islami Bank Bangladesh Ltd',
                    'bank_branch' => 'Sylhet Main Branch',
                    'bank_account_no' => 'IBBL-2050123984',
                    'deposit_date' => now()->toDateString(),
                    'status' => 'APPROVED',
                    'approved_at' => now()->subHours(2),
                    'notes' => 'Corporate account bank deposit via online BEFTN.'
                ]
            );

            \App\Models\TenantResellerRecharge::updateOrCreate(
                ['tenant_id' => $tenant->id, 'recharge_no' => 'RCH-' . date('Ymd') . '-0003'],
                [
                    'reseller_id' => $reseller1->id,
                    'amount' => 10000.00,
                    'bonus_amount' => 200.00,
                    'total_credited' => 10200.00,
                    'payment_method' => 'NAGAD',
                    'gateway_trx_id' => 'NG77192834',
                    'status' => 'PENDING',
                    'notes' => 'Sub-ISP self-service recharge request pending admin review.'
                ]
            );

            // 5. Demo Bandwidth Allocations
            $router = \App\Models\TenantRouter::where('tenant_id', $tenant->id)->first();

            \App\Models\TenantResellerBandwidth::updateOrCreate(
                ['tenant_id' => $tenant->id, 'reseller_id' => $reseller1->id],
                [
                    'router_id' => $router?->id,
                    'interface_name' => 'vlan200_reseller1',
                    'vlan_id' => 200,
                    'global_bandwidth_mbps' => 300.00,
                    'bdix_bandwidth_mbps' => 500.00,
                    'cdn_bandwidth_mbps' => 200.00,
                    'total_bandwidth_mbps' => 1000.00,
                    'allocation_type' => 'DEDICATED_CIR',
                    'rate_per_mbps' => 120.00,
                    'monthly_bill_amount' => 120000.00,
                    'mikrotik_queue_name' => 'subisp_ccn_limit',
                    'current_usage_mbps' => 742.50,
                    'peak_usage_mbps' => 910.00,
                    'status' => 'ACTIVE',
                    'notes' => '1Gbps wholesale dedicated trunk on 10G SFP+ interface.'
                ]
            );

            \App\Models\TenantResellerBandwidth::updateOrCreate(
                ['tenant_id' => $tenant->id, 'reseller_id' => $reseller2->id],
                [
                    'router_id' => $router?->id,
                    'interface_name' => 'vlan300_reseller2',
                    'vlan_id' => 300,
                    'global_bandwidth_mbps' => 500.00,
                    'bdix_bandwidth_mbps' => 1000.00,
                    'cdn_bandwidth_mbps' => 500.00,
                    'total_bandwidth_mbps' => 2000.00,
                    'allocation_type' => 'BURSTABLE_MIR',
                    'rate_per_mbps' => 110.00,
                    'monthly_bill_amount' => 220000.00,
                    'mikrotik_queue_name' => 'subisp_sfl_limit',
                    'current_usage_mbps' => 1340.00,
                    'peak_usage_mbps' => 1750.00,
                    'status' => 'ACTIVE',
                    'notes' => '2Gbps burstable trunk connection to Sylhet POP.'
                ]
            );
        }
    }
}
