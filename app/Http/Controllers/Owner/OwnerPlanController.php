<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerPlanController extends Controller
{
    public function index()
    {
        $plans = SaasPlan::withCount('tenants')
            ->orderBy('sort_order', 'asc')
            ->orderBy('customer_limit', 'asc')
            ->get();

        $activePlansCount = $plans->where('is_active', true)->count();
        $totalSubscribers = $plans->sum('tenants_count');
        $minPrice = $plans->min('monthly_price') ?? 600;
        $maxPrice = $plans->max('monthly_price') ?? 8000;
        $popularPlan = $plans->where('is_popular', true)->first() ?? $plans->first();

        return view('owner.plans.index', compact(
            'plans',
            'activePlansCount',
            'totalSubscribers',
            'minPrice',
            'maxPrice',
            'popularPlan'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'customer_limit' => ['required', 'integer', 'min:1'],
            'otc_charge' => ['required', 'numeric', 'min:0'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'premium_monthly_price' => ['nullable', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'olt_limit' => ['required', 'integer', 'min:1'],
            'mikrotik_limit' => ['required', 'integer', 'min:1'],
            'reseller_limit' => ['required', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_popular' => ['nullable', 'boolean'],
            'allow_radius' => ['nullable', 'boolean'],
            'allow_wireguard' => ['nullable', 'boolean'],
            'allow_snmp_monitoring' => ['nullable', 'boolean'],
            'features' => ['nullable', 'string'],
        ]);

        $featuresArray = $request->filled('features')
            ? array_values(array_filter(array_map('trim', explode(',', $request->features))))
            : [];

        $slug = Str::slug($validated['name']);
        if (SaasPlan::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . rand(100, 999);
        }

        SaasPlan::create([
            'code' => $validated['code'] ?? ('P' . (SaasPlan::count() + 1)),
            'name' => $validated['name'],
            'slug' => $slug,
            'customer_limit' => $validated['customer_limit'],
            'otc_charge' => $validated['otc_charge'],
            'monthly_price' => $validated['monthly_price'],
            'premium_monthly_price' => $validated['premium_monthly_price'] ?? ($validated['monthly_price'] * 1.2),
            'yearly_price' => $validated['yearly_price'] ?? ($validated['monthly_price'] * 10),
            'olt_limit' => $validated['olt_limit'],
            'mikrotik_limit' => $validated['mikrotik_limit'],
            'reseller_limit' => $validated['reseller_limit'],
            'trial_days' => $validated['trial_days'] ?? 14,
            'badge_text' => $validated['badge_text'] ?? null,
            'is_popular' => $request->has('is_popular'),
            'allow_radius' => $request->has('allow_radius'),
            'allow_wireguard' => $request->has('allow_wireguard'),
            'allow_snmp_monitoring' => $request->has('allow_snmp_monitoring'),
            'features' => $featuresArray,
            'is_active' => true,
            'sort_order' => SaasPlan::max('sort_order') + 1,
        ]);

        return back()->with('success', 'New SaaS subscription pricing tier created successfully.');
    }

    public function update(Request $request, SaasPlan $plan)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'customer_limit' => ['required', 'integer', 'min:1'],
            'otc_charge' => ['required', 'numeric', 'min:0'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'premium_monthly_price' => ['nullable', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'olt_limit' => ['required', 'integer', 'min:1'],
            'mikrotik_limit' => ['required', 'integer', 'min:1'],
            'reseller_limit' => ['required', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'is_popular' => ['nullable', 'boolean'],
            'allow_radius' => ['nullable', 'boolean'],
            'allow_wireguard' => ['nullable', 'boolean'],
            'allow_snmp_monitoring' => ['nullable', 'boolean'],
            'features' => ['nullable', 'string'],
        ]);

        $featuresArray = $request->filled('features')
            ? array_values(array_filter(array_map('trim', explode(',', $request->features))))
            : $plan->features;

        $plan->update([
            'code' => $validated['code'] ?? $plan->code,
            'name' => $validated['name'],
            'customer_limit' => $validated['customer_limit'],
            'otc_charge' => $validated['otc_charge'],
            'monthly_price' => $validated['monthly_price'],
            'premium_monthly_price' => $validated['premium_monthly_price'] ?? ($validated['monthly_price'] * 1.2),
            'yearly_price' => $validated['yearly_price'] ?? ($validated['monthly_price'] * 10),
            'olt_limit' => $validated['olt_limit'],
            'mikrotik_limit' => $validated['mikrotik_limit'],
            'reseller_limit' => $validated['reseller_limit'],
            'trial_days' => $validated['trial_days'] ?? 14,
            'badge_text' => $validated['badge_text'] ?? null,
            'is_popular' => $request->has('is_popular'),
            'allow_radius' => $request->has('allow_radius'),
            'allow_wireguard' => $request->has('allow_wireguard'),
            'allow_snmp_monitoring' => $request->has('allow_snmp_monitoring'),
            'features' => $featuresArray,
        ]);

        return back()->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    public function toggle(SaasPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $statusText = $plan->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Plan '{$plan->name}' {$statusText} successfully.");
    }

    public function destroy(SaasPlan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return back()->with('error', "Cannot delete plan '{$plan->name}' because {$plan->tenants()->count()} ISP tenants are currently subscribed to it.");
        }

        $plan->delete();
        return back()->with('success', "Plan '{$plan->name}' deleted successfully.");
    }

    public function seedDefaults()
    {
        $defaultPlans = [
            [
                "code" => "P1",
                "name" => "P1",
                "slug" => "p1-plan",
                "customer_limit" => 100,
                "otc_charge" => 4000.00,
                "monthly_price" => 600.00,
                "premium_monthly_price" => 800.00,
                "yearly_price" => 6000.00,
                "olt_limit" => 1,
                "mikrotik_limit" => 1,
                "reseller_limit" => 2,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 1,
                "features" => ["100 Customers Limit", "1 OLT Device", "1 MikroTik Router", "Automated Billing & Invoice", "SMS Gateway Support", "Daily Cloud Backup"],
            ],
            [
                "code" => "P2",
                "name" => "P2",
                "slug" => "p2-plan",
                "customer_limit" => 200,
                "otc_charge" => 4000.00,
                "monthly_price" => 1000.00,
                "premium_monthly_price" => 1200.00,
                "yearly_price" => 10000.00,
                "olt_limit" => 1,
                "mikrotik_limit" => 2,
                "reseller_limit" => 3,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 2,
                "features" => ["200 Customers Limit", "1 OLT Device", "2 MikroTik Routers", "bKash & Nagad Online PGW", "Customer Portal App", "Reseller Sub-Billing"],
            ],
            [
                "code" => "P3",
                "name" => "P3",
                "slug" => "p3-plan",
                "customer_limit" => 300,
                "otc_charge" => 4000.00,
                "monthly_price" => 1400.00,
                "premium_monthly_price" => 1500.00,
                "yearly_price" => 14000.00,
                "olt_limit" => 2,
                "mikrotik_limit" => 2,
                "reseller_limit" => 5,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 3,
                "features" => ["300 Customers Limit", "2 OLT Devices", "2 MikroTik Routers", "SMS & WhatsApp Alerts", "Staff Roles & Access", "Bandwidth Queue Manager"],
            ],
            [
                "code" => "P4",
                "name" => "P4",
                "slug" => "p4-plan",
                "customer_limit" => 400,
                "otc_charge" => 4000.00,
                "monthly_price" => 1700.00,
                "premium_monthly_price" => 1800.00,
                "yearly_price" => 17000.00,
                "olt_limit" => 3,
                "mikrotik_limit" => 3,
                "reseller_limit" => 7,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 4,
                "features" => ["400 Customers Limit", "3 OLT Devices", "3 MikroTik Routers", "Multi-POP Management", "Real-Time PON Signal Status", "Automated Expiry Locking"],
            ],
            [
                "code" => "P5",
                "name" => "P5",
                "slug" => "p5-plan",
                "customer_limit" => 500,
                "otc_charge" => 6000.00,
                "monthly_price" => 2000.00,
                "premium_monthly_price" => 2100.00,
                "yearly_price" => 20000.00,
                "olt_limit" => 3,
                "mikrotik_limit" => 3,
                "reseller_limit" => 10,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 5,
                "features" => ["500 Customers Limit", "3 OLT Monitoring", "3 MikroTik Routers", "Optical Power Graphing", "Corporate Account Invoicing", "Automated Due Reminders"],
            ],
            [
                "code" => "P6",
                "name" => "P6",
                "slug" => "p6-plan",
                "customer_limit" => 800,
                "otc_charge" => 6000.00,
                "monthly_price" => 2700.00,
                "premium_monthly_price" => 3000.00,
                "yearly_price" => 27000.00,
                "olt_limit" => 5,
                "mikrotik_limit" => 5,
                "reseller_limit" => 15,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 6,
                "features" => ["800 Customers Limit", "5 OLT Monitoring", "5 MikroTik Routers", "BGP / Multi-Gateway Engine", "Reseller Recharge System", "24/7 Priority Support"],
            ],
            [
                "code" => "P7",
                "name" => "P7",
                "slug" => "p7-plan",
                "customer_limit" => 1000,
                "otc_charge" => 6000.00,
                "monthly_price" => 3000.00,
                "premium_monthly_price" => 3500.00,
                "yearly_price" => 30000.00,
                "olt_limit" => 6,
                "mikrotik_limit" => 6,
                "reseller_limit" => 20,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 7,
                "features" => ["1,000 Customers Limit", "6 OLT Devices", "6 MikroTik Routers", "Unlimited Collector Accounts", "Automated Tax & VAT Invoicing", "Dedicated Resource Pool"],
            ],
            [
                "code" => "P8",
                "name" => "P8 (Popular)",
                "slug" => "p8-plan",
                "customer_limit" => 1500,
                "otc_charge" => 10000.00,
                "monthly_price" => 4000.00,
                "premium_monthly_price" => 4500.00,
                "yearly_price" => 40000.00,
                "olt_limit" => 7,
                "mikrotik_limit" => 8,
                "reseller_limit" => 25,
                "trial_days" => 14,
                "is_popular" => true,
                "badge_text" => null,
                "sort_order" => 8,
                "features" => ["1,500 Customers Limit", "7 OLT Devices", "8 MikroTik Routers", "High-Volume SMS Dispatcher", "Multi-Branch Network", "Direct Webhook & API Access"],
            ],
            [
                "code" => "P9",
                "name" => "P9",
                "slug" => "p9-plan",
                "customer_limit" => 2000,
                "otc_charge" => 10000.00,
                "monthly_price" => 4500.00,
                "premium_monthly_price" => 5000.00,
                "yearly_price" => 45000.00,
                "olt_limit" => 8,
                "mikrotik_limit" => 10,
                "reseller_limit" => 30,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 9,
                "features" => ["2,000 Customers Limit", "8 OLT Devices", "10 MikroTik Routers", "Redundant FreeRADIUS Cluster", "Dedicated Account Manager", "Custom Domain & Branding"],
            ],
            [
                "code" => "P10",
                "name" => "P10",
                "slug" => "p10-plan",
                "customer_limit" => 3000,
                "otc_charge" => 10000.00,
                "monthly_price" => 6000.00,
                "premium_monthly_price" => 6500.00,
                "yearly_price" => 60000.00,
                "olt_limit" => 10,
                "mikrotik_limit" => 12,
                "reseller_limit" => 40,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 10,
                "features" => ["3,000 Customers Limit", "10 OLT Monitoring", "12 MikroTik Routers", "High-Availability Load Balancer", "Custom Payment Gateway", "99.99% Uptime SLA"],
            ],
            [
                "code" => "P11",
                "name" => "P11",
                "slug" => "p11-plan",
                "customer_limit" => 4000,
                "otc_charge" => 10000.00,
                "monthly_price" => 7000.00,
                "premium_monthly_price" => 8000.00,
                "yearly_price" => 70000.00,
                "olt_limit" => 11,
                "mikrotik_limit" => 15,
                "reseller_limit" => 50,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 11,
                "features" => ["4,000 Customers Limit", "11 OLT Devices", "15 MikroTik Routers", "Automated ONU Provisioning", "Custom Billing Rules Engine", "VIP Dedicated Support Line"],
            ],
            [
                "code" => "P12",
                "name" => "P12",
                "slug" => "p12-plan",
                "customer_limit" => 5000,
                "otc_charge" => 10000.00,
                "monthly_price" => 8000.00,
                "premium_monthly_price" => 9000.00,
                "yearly_price" => 80000.00,
                "olt_limit" => 12,
                "mikrotik_limit" => 20,
                "reseller_limit" => 100,
                "trial_days" => 14,
                "is_popular" => false,
                "badge_text" => null,
                "sort_order" => 12,
                "features" => ["5,000+ Customers Limit", "12 OLT Devices", "20 MikroTik Routers", "Full API Webhook Ecosystem", "Dedicated Cloud VPS Infrastructure", "Supreme Enterprise SLAs"],
            ],
        ];

        foreach ($defaultPlans as $pData) {
            SaasPlan::updateOrCreate(
                ['code' => $pData['code']],
                $pData + ['is_active' => true]
            );
        }

        return back()->with('success', 'Standard P1 to P12 ISP SaaS pricing matrix successfully restored and updated!');
    }
}
