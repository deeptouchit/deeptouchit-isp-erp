<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerPackageController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Assigned Internet Packages & Margin Rates.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $serviceType = $request->input('service_type', 'all');
        $speedTier = $request->input('speed', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // 1. Base Query
        $query = TenantInternetPackage::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('package_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('mikrotik_profile', 'like', "%{$search}%");
            });
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', strtoupper($serviceType));
        }

        if ($speedTier !== 'all') {
            if ($speedTier === '10') {
                $query->where('download_speed', '<=', 10);
            } elseif ($speedTier === '20') {
                $query->whereBetween('download_speed', [11, 20]);
            } elseif ($speedTier === '35') {
                $query->whereBetween('download_speed', [21, 35]);
            } elseif ($speedTier === '50+') {
                $query->where('download_speed', '>=', 40);
            }
        }

        $packages = $query->orderBy('price')->paginate($perPage)->withQueryString();

        // 2. Fetch subscriber counts per package under this reseller
        $subscriberCounts = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw('package_id, COUNT(*) as total_subscribers, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_subscribers')
            ->groupBy('package_id')
            ->get()
            ->keyBy('package_id');

        $commissionRate = (float) ($reseller->commission_rate ?? 30);

        // 3. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $allPackages = TenantInternetPackage::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $totalAssignedPlans = $allPackages->count();

        $activeCustomers = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->where('status', 'active')
            ->with('package')
            ->get();

        $totalActiveSubscribers = $activeCustomers->count();
        $totalRetailMrr = (float) $activeCustomers->sum('monthly_bill');
        if ($totalRetailMrr <= 0) {
            $totalRetailMrr = (float) $activeCustomers->sum(fn($c) => $c->package?->price ?? 0);
        }

        $totalMonthlyProfit = ($totalRetailMrr * $commissionRate) / 100;
        $totalWholesaleCost = max(0, $totalRetailMrr - $totalMonthlyProfit);

        $stats = [
            'total_assigned_plans' => $totalAssignedPlans,
            'commission_rate' => $commissionRate,
            'active_subscribers' => $totalActiveSubscribers,
            'total_retail_mrr' => $totalRetailMrr,
            'total_monthly_profit' => $totalMonthlyProfit,
            'total_wholesale_cost' => $totalWholesaleCost,
        ];

        $currencySymbol = '৳';

        return view('reseller.packages.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'packages',
            'subscriberCounts',
            'commissionRate',
            'stats',
            'search',
            'serviceType',
            'speedTier',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Show detailed package specs and profit margins as JSON.
     */
    public function show(int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $pkg = TenantInternetPackage::where('tenant_id', $tenant->id)->findOrFail($id);
        $commissionRate = (float) ($reseller->commission_rate ?? 30);
        $retailPrice = (float) $pkg->price;
        $profitMargin = ($retailPrice * $commissionRate) / 100;
        $wholesaleCost = max(0, $retailPrice - $profitMargin);

        $activeCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->where('package_id', $pkg->id)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pkg->id,
                'package_name' => $pkg->package_name ?? $pkg->name,
                'name' => $pkg->name,
                'code' => $pkg->code,
                'service_type' => $pkg->service_type ?? 'PPPoE',
                'download_speed' => $pkg->download_speed,
                'upload_speed' => $pkg->upload_speed,
                'bdix_speed' => $pkg->bdix_speed,
                'youtube_speed' => $pkg->youtube_speed,
                'facebook_speed' => $pkg->facebook_speed,
                'validity_days' => $pkg->validity_days ?? 30,
                'validity_unit' => $pkg->validity_unit ?? 'Days',
                'price' => $retailPrice,
                'commission_rate' => $commissionRate,
                'profit_margin' => $profitMargin,
                'wholesale_cost' => $wholesaleCost,
                'mikrotik_profile' => $pkg->mikrotik_profile ?? 'default',
                'active_subscribers' => $activeCount,
                'total_revenue' => $activeCount * $retailPrice,
                'total_profit' => $activeCount * $profitMargin,
                'description' => $pkg->description,
            ]
        ]);
    }

    /**
     * Export Packages Tariff Sheet to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $serviceType = $request->input('service_type', 'all');

        $query = TenantInternetPackage::where('tenant_id', $tenantId)->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('package_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', strtoupper($serviceType));
        }

        $packages = $query->orderBy('price')->get();
        $commissionRate = (float) ($reseller->commission_rate ?? 30);
        $filename = 'assigned_packages_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($packages, $commissionRate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Package Name',
                'Download Speed (Mbps)',
                'Upload Speed (Mbps)',
                'BDIX Speed (Mbps)',
                'Retail Price (BDT)',
                'Wholesale Cost (BDT)',
                'Margin Amount (BDT)',
                'Commission Rate (%)',
                'Validity (Days)',
                'Service Type'
            ]);

            foreach ($packages as $idx => $p) {
                $retail = (float) $p->price;
                $margin = ($retail * $commissionRate) / 100;
                $wholesale = max(0, $retail - $margin);

                fputcsv($handle, [
                    $idx + 1,
                    $p->package_name ?? $p->name,
                    $p->download_speed,
                    $p->upload_speed,
                    $p->bdix_speed ?? $p->download_speed,
                    $retail,
                    $wholesale,
                    $margin,
                    $commissionRate . '%',
                    $p->validity_days ?? 30,
                    $p->service_type ?? 'PPPoE'
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print Package Tariff & Rate Sheet Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $commissionRate = (float) ($reseller->commission_rate ?? 30);
        $currencySymbol = '৳';

        return view('reseller.packages.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'packages',
            'commissionRate',
            'currencySymbol'
        ));
    }
}
