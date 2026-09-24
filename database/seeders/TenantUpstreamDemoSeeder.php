<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\TenantUpstreamInvoice;
use App\Models\TenantUpstreamLink;
use App\Models\TenantUpstreamPayment;
use App\Models\TenantUpstreamProvider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TenantUpstreamDemoSeeder extends Seeder
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
            $user = User::where('tenant_id', $tenant->id)->first();
            $userId = $user ? $user->id : null;

            $router = TenantRouter::where('tenant_id', $tenant->id)->first();
            $routerId = $router ? $router->id : null;

            $currentMonth = Carbon::now()->startOfMonth();

            // 1. Summit Communications Limited (Primary IIG Carrier)
            $summit = TenantUpstreamProvider::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Summit Communications Ltd.'],
                [
                    'carrier_type' => 'IIG',
                    'contact_person' => 'Engr. Fazle Rabbi',
                    'phone' => '01711-987654',
                    'email' => 'carrier.billing@summitcommunications.net',
                    'address' => 'Summit Centre, 18 Kawran Bazar C/A, Dhaka-1215',
                    'bank_name' => 'Standard Chartered Bank',
                    'bank_account_no' => '01-8976543-01',
                    'bank_branch' => 'Gulshan Main Branch',
                    'routing_no' => '215261789',
                    'is_active' => true,
                    'notes' => 'Primary Tier-1 IIG upstream provider. Submarine cable SEA-ME-WE-4 & 5 route.',
                ]
            );

            $summitLink = TenantUpstreamLink::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $summit->id, 'link_name' => 'Summit 10G Primary Gateway Trunk'],
                [
                    'router_id' => $routerId,
                    'interface_port' => 'sfp-sfpplus1',
                    'circuit_id' => 'SMT-DHK-9801',
                    'global_mbps' => 5000.00,
                    'bdix_mbps' => 3000.00,
                    'cdn_mbps' => 2000.00,
                    'ggc_mbps' => 2500.00,
                    'fna_mbps' => 2500.00,
                    'other_mbps' => 500.00,
                    'total_mbps' => 15500.00,
                    'global_rate' => 120.00,
                    'bdix_rate' => 35.00,
                    'cdn_rate' => 40.00,
                    'ggc_rate' => 38.00,
                    'fna_rate' => 38.00,
                    'other_rate' => 40.00,
                    'monthly_transmission_cost' => 45000.00,
                    'est_monthly_bill' => 1040000.00,
                    'status' => 'ACTIVE',
                ]
            );

            $summitInvoice = TenantUpstreamInvoice::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $summit->id, 'invoice_no' => 'SMT-2026-09-01'],
                [
                    'link_id' => $summitLink->id,
                    'billing_month' => $currentMonth->toDateString(),
                    'bandwidth_cost' => 995000.00,
                    'transmission_cost' => 45000.00,
                    'vat_tax' => 0.00,
                    'other_charges' => 0.00,
                    'total_amount' => 1040000.00,
                    'paid_amount' => 700000.00,
                    'due_amount' => 340000.00,
                    'payment_status' => 'PARTIAL',
                    'due_date' => $currentMonth->copy()->addDays(20)->toDateString(),
                    'notes' => 'September 2026 Carrier invoice with 15.5G aggregate committed capacity.',
                ]
            );

            TenantUpstreamPayment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $summit->id, 'voucher_no' => 'UPV-202609-001'],
                [
                    'invoice_id' => $summitInvoice->id,
                    'amount' => 700000.00,
                    'payment_method' => 'BANK_TRANSFER',
                    'bank_name' => 'Standard Chartered Bank',
                    'cheque_no' => 'BEFTN-SCB-987123',
                    'transaction_ref' => 'TRX-SUMMIT-SEP26-P1',
                    'paid_at' => Carbon::now()->subDays(5),
                    'created_by' => $userId,
                    'notes' => '1st Installment payment via Corporate Online Banking BEFTN.',
                ]
            );

            // 2. Fiber@Home Limited (NTTN Transmission Gateway)
            $fiber = TenantUpstreamProvider::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Fiber@Home Limited'],
                [
                    'carrier_type' => 'NTTN',
                    'contact_person' => 'Moynul Islam',
                    'phone' => '01819-876543',
                    'email' => 'noc-transmission@fiberathome.net',
                    'address' => 'House #7/B, Road #13, Gulshan-1, Dhaka',
                    'bank_name' => 'The City Bank Ltd',
                    'bank_account_no' => '1102876543001',
                    'bank_branch' => 'Dhanmondi Branch',
                    'routing_no' => '225271890',
                    'is_active' => true,
                    'notes' => 'Nationwide NTTN Optical Core Dark Fiber Transmission Provider.',
                ]
            );

            $fiberLink = TenantUpstreamLink::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $fiber->id, 'link_name' => 'F@H Metro Dark Fiber Ring Trunk'],
                [
                    'router_id' => $routerId,
                    'interface_port' => 'sfp-sfpplus2',
                    'circuit_id' => 'FAH-TX-4402',
                    'global_mbps' => 0.00,
                    'bdix_mbps' => 0.00,
                    'cdn_mbps' => 0.00,
                    'ggc_mbps' => 0.00,
                    'fna_mbps' => 0.00,
                    'other_mbps' => 0.00,
                    'total_mbps' => 0.00,
                    'global_rate' => 0.00,
                    'bdix_rate' => 0.00,
                    'cdn_rate' => 0.00,
                    'ggc_rate' => 0.00,
                    'fna_rate' => 0.00,
                    'other_rate' => 0.00,
                    'monthly_transmission_cost' => 85000.00,
                    'est_monthly_bill' => 85000.00,
                    'status' => 'ACTIVE',
                ]
            );

            $fiberInvoice = TenantUpstreamInvoice::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $fiber->id, 'invoice_no' => 'FAH-INV-202609'],
                [
                    'link_id' => $fiberLink->id,
                    'billing_month' => $currentMonth->toDateString(),
                    'bandwidth_cost' => 0.00,
                    'transmission_cost' => 85000.00,
                    'vat_tax' => 0.00,
                    'other_charges' => 0.00,
                    'total_amount' => 85000.00,
                    'paid_amount' => 85000.00,
                    'due_amount' => 0.00,
                    'payment_status' => 'PAID',
                    'due_date' => $currentMonth->copy()->addDays(15)->toDateString(),
                    'notes' => 'Underground Metro Core Optical Ring lease rental.',
                ]
            );

            TenantUpstreamPayment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $fiber->id, 'voucher_no' => 'UPV-202609-002'],
                [
                    'invoice_id' => $fiberInvoice->id,
                    'amount' => 85000.00,
                    'payment_method' => 'CHEQUE',
                    'bank_name' => 'The City Bank Ltd',
                    'cheque_no' => 'CHQ-77889901',
                    'transaction_ref' => 'FAH-SEPT-CLEAR',
                    'paid_at' => Carbon::now()->subDays(3),
                    'created_by' => $userId,
                    'notes' => 'Full payment settled via Account Payee Cheque.',
                ]
            );

            // 3. Mango Teleservices Limited (ITC Carrier)
            $mango = TenantUpstreamProvider::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Mango Teleservices Limited'],
                [
                    'carrier_type' => 'ITC',
                    'contact_person' => 'Shahadat Hossain',
                    'phone' => '01713-112233',
                    'email' => 'billing@mangoteleservices.com',
                    'address' => 'Green City Edge, 89 Kakrail, Dhaka-1000',
                    'bank_name' => 'BRAC Bank PLC',
                    'bank_account_no' => '1501209876543001',
                    'bank_branch' => 'Banani Branch',
                    'routing_no' => '060261456',
                    'is_active' => true,
                    'notes' => 'International Terrestrial Cable (ITC) upstream provider via Benapole / Agartala gateway.',
                ]
            );

            $mangoLink = TenantUpstreamLink::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $mango->id, 'link_name' => 'Mango ITC Terrestrial Backup Trunk'],
                [
                    'router_id' => $routerId,
                    'interface_port' => 'sfp-sfpplus3',
                    'circuit_id' => 'MNG-ITC-1029',
                    'global_mbps' => 3000.00,
                    'bdix_mbps' => 1000.00,
                    'cdn_mbps' => 1500.00,
                    'ggc_mbps' => 1500.00,
                    'fna_mbps' => 1500.00,
                    'other_mbps' => 0.00,
                    'total_mbps' => 8500.00,
                    'global_rate' => 115.00,
                    'bdix_rate' => 30.00,
                    'cdn_rate' => 35.00,
                    'ggc_rate' => 35.00,
                    'fna_rate' => 35.00,
                    'other_rate' => 0.00,
                    'monthly_transmission_cost' => 30000.00,
                    'est_monthly_bill' => 565000.00,
                    'status' => 'ACTIVE',
                ]
            );

            TenantUpstreamInvoice::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $mango->id, 'invoice_no' => 'MNG-2026-SEP-09'],
                [
                    'link_id' => $mangoLink->id,
                    'billing_month' => $currentMonth->toDateString(),
                    'bandwidth_cost' => 535000.00,
                    'transmission_cost' => 30000.00,
                    'vat_tax' => 0.00,
                    'other_charges' => 0.00,
                    'total_amount' => 565000.00,
                    'paid_amount' => 0.00,
                    'due_amount' => 565000.00,
                    'payment_status' => 'UNPAID',
                    'due_date' => $currentMonth->copy()->addDays(25)->toDateString(),
                    'notes' => 'Monthly bandwidth invoice. Payment due by end of month.',
                ]
            );

            // 4. Bangladesh Internet Exchange (BDIX Peering)
            $bdix = TenantUpstreamProvider::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Bangladesh Internet Exchange (BDIX)'],
                [
                    'carrier_type' => 'BDIX',
                    'contact_person' => 'BDIX Operations Desk',
                    'phone' => '02-9887766',
                    'email' => 'accounts@bdix.net',
                    'address' => 'Red Crescent Concord Tower, 17 Mohakhali C/A, Dhaka',
                    'bank_name' => 'Dutch-Bangla Bank Limited',
                    'bank_account_no' => '1031100987654',
                    'bank_branch' => 'Mohakhali Branch',
                    'routing_no' => '090261890',
                    'is_active' => true,
                    'notes' => 'Direct peering exchange for ultra-low latency national traffic routing.',
                ]
            );

            $bdixLink = TenantUpstreamLink::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $bdix->id, 'link_name' => 'BDIX Direct 10G Peering Link'],
                [
                    'router_id' => $routerId,
                    'interface_port' => 'sfp-sfpplus4',
                    'circuit_id' => 'BDIX-IX-10G',
                    'global_mbps' => 0.00,
                    'bdix_mbps' => 5000.00,
                    'cdn_mbps' => 0.00,
                    'ggc_mbps' => 0.00,
                    'fna_mbps' => 0.00,
                    'other_mbps' => 0.00,
                    'total_mbps' => 5000.00,
                    'global_rate' => 0.00,
                    'bdix_rate' => 20.00,
                    'cdn_rate' => 0.00,
                    'ggc_rate' => 0.00,
                    'fna_rate' => 0.00,
                    'other_rate' => 0.00,
                    'monthly_transmission_cost' => 15000.00,
                    'est_monthly_bill' => 115000.00,
                    'status' => 'ACTIVE',
                ]
            );

            $bdixInvoice = TenantUpstreamInvoice::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $bdix->id, 'invoice_no' => 'BDIX-202609-BILL'],
                [
                    'link_id' => $bdixLink->id,
                    'billing_month' => $currentMonth->toDateString(),
                    'bandwidth_cost' => 100000.00,
                    'transmission_cost' => 15000.00,
                    'vat_tax' => 0.00,
                    'other_charges' => 0.00,
                    'total_amount' => 115000.00,
                    'paid_amount' => 115000.00,
                    'due_amount' => 0.00,
                    'payment_status' => 'PAID',
                    'due_date' => $currentMonth->copy()->addDays(10)->toDateString(),
                    'notes' => '10Gbps national peering exchange port subscription.',
                ]
            );

            TenantUpstreamPayment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $bdix->id, 'voucher_no' => 'UPV-202609-003'],
                [
                    'invoice_id' => $bdixInvoice->id,
                    'amount' => 115000.00,
                    'payment_method' => 'RTGS',
                    'bank_name' => 'Dutch-Bangla Bank Limited',
                    'cheque_no' => 'RTGS-DBBL-554433',
                    'transaction_ref' => 'RTGS-BDIX-CLEAR',
                    'paid_at' => Carbon::now()->subDays(2),
                    'created_by' => $userId,
                    'notes' => 'Full settlement executed via RTGS Instant Settlement.',
                ]
            );

            // 5. CDN Content Edge Provider (Akamai / Google Cache)
            $cdn = TenantUpstreamProvider::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Edge CDN & Content Cache Cluster'],
                [
                    'carrier_type' => 'CACHE_PROVIDER',
                    'contact_person' => 'NOC Peering Team',
                    'phone' => '01911-334455',
                    'email' => 'edge-noc@cdn-cluster.net',
                    'address' => 'Data Center Rack #4, NOC Facility',
                    'bank_name' => 'Eastern Bank PLC',
                    'bank_account_no' => '1041060987654',
                    'bank_branch' => 'Principal Branch',
                    'routing_no' => '095261890',
                    'is_active' => true,
                    'notes' => 'On-premise cache cluster nodes for YouTube, Facebook and OTT stream offloading.',
                ]
            );

            TenantUpstreamLink::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider_id' => $cdn->id, 'link_name' => 'Local Edge Cache Cluster Node'],
                [
                    'router_id' => $routerId,
                    'interface_port' => 'ether10',
                    'circuit_id' => 'CDN-LOCAL-NODE',
                    'global_mbps' => 0.00,
                    'bdix_mbps' => 0.00,
                    'cdn_mbps' => 4000.00,
                    'ggc_mbps' => 4000.00,
                    'fna_mbps' => 4000.00,
                    'other_mbps' => 0.00,
                    'total_mbps' => 12000.00,
                    'global_rate' => 0.00,
                    'bdix_rate' => 0.00,
                    'cdn_rate' => 15.00,
                    'ggc_rate' => 15.00,
                    'fna_rate' => 15.00,
                    'other_rate' => 0.00,
                    'monthly_transmission_cost' => 20000.00,
                    'est_monthly_bill' => 200000.00,
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}
