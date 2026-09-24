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

class ResellerMarginController extends Controller
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
     * Display Partner Margin & Profit Rates Analytics.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
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
                  ->orWhere('mikrotik_profile', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('price')->paginate($perPage)->withQueryString();

        // 2. Fetch subscriber counts per package
        $subscriberCounts = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw('package_id, COUNT(*) as total_subscribers, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_subscribers')
            ->groupBy('package_id')
            ->get()
            ->keyBy('package_id');

        $commissionRate = (float) ($reseller->commission_rate ?? 30);

        // 3. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $activeCustomers = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->where('status', 'active')
            ->with('package')
            ->get();

        $activeSubscribersCount = $activeCustomers->count();
        $totalRetailMrr = (float) $activeCustomers->sum('monthly_bill');
        if ($totalRetailMrr <= 0) {
            $totalRetailMrr = (float) $activeCustomers->sum(fn($c) => $c->package?->price ?? 0);
        }

        $totalMonthlyProfit = ($totalRetailMrr * $commissionRate) / 100;
        $totalWholesaleCost = max(0, $totalRetailMrr - $totalMonthlyProfit);
        $avgProfitPerUser = $activeSubscribersCount > 0 ? ($totalMonthlyProfit / $activeSubscribersCount) : 0;

        $stats = [
            'commission_rate' => $commissionRate,
            'total_monthly_profit' => $totalMonthlyProfit,
            'total_retail_mrr' => $totalRetailMrr,
            'total_wholesale_cost' => $totalWholesaleCost,
            'avg_profit_per_user' => $avgProfitPerUser,
            'active_subscribers' => $activeSubscribersCount,
        ];

        $currencySymbol = '৳';

        return view('reseller.margins.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'packages',
            'subscriberCounts',
            'commissionRate',
            'stats',
            'search',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Show detailed package margin simulation as JSON.
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
                'name' => $pkg->mikrotik_profile ?? $pkg->name,
                'package_name' => $pkg->package_name ?? $pkg->name,
                'download_speed' => $pkg->download_speed,
                'upload_speed' => $pkg->upload_speed,
                'price' => $retailPrice,
                'wholesale_cost' => $wholesaleCost,
                'profit_margin' => $profitMargin,
                'commission_rate' => $commissionRate,
                'active_subscribers' => $activeCount,
                'monthly_revenue' => $activeCount * $retailPrice,
                'monthly_profit' => $activeCount * $profitMargin,
            ]
        ]);
    }

    /**
     * Export Margin & Profit Rates Schedule as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $subscriberCounts = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw('package_id, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_subscribers')
            ->groupBy('package_id')
            ->get()
            ->keyBy('package_id');

        $commissionRate = (float) ($reseller->commission_rate ?? 30);
        $filename = 'margin_rates_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($packages, $subscriberCounts, $commissionRate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'MikroTik Profile',
                'Retail Price (BDT)',
                'Partner Wholesale Cost (BDT)',
                'Margin Per User (BDT)',
                'Commission Rate (%)',
                'Active Subscribers',
                'Total Monthly Profit (BDT)'
            ]);

            foreach ($packages as $idx => $p) {
                $retail = (float) $p->price;
                $margin = ($retail * $commissionRate) / 100;
                $cost = max(0, $retail - $margin);
                $activeSubs = $subscriberCounts->get($p->id)?->active_subscribers ?? 0;
                $totalProfit = $activeSubs * $margin;

                fputcsv($handle, [
                    $idx + 1,
                    $p->mikrotik_profile ?? $p->name,
                    $retail,
                    $cost,
                    $margin,
                    $commissionRate . '%',
                    $activeSubs,
                    $totalProfit
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print Commercial Margin Schedule.
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

        $subscriberCounts = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw('package_id, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_subscribers')
            ->groupBy('package_id')
            ->get()
            ->keyBy('package_id');

        $totalSubscribers = (int) $subscriberCounts->sum('active_subscribers');
        $totalProfit = 0;
        foreach ($packages as $pkg) {
            $retail = (float) $pkg->price;
            $margin = ($retail * $commissionRate) / 100;
            $activeSubs = (int) ($subscriberCounts->get($pkg->id)?->active_subscribers ?? 0);
            $totalProfit += ($activeSubs * $margin);
        }

        return view('reseller.margins.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'packages',
            'subscriberCounts',
            'commissionRate',
            'currencySymbol',
            'totalSubscribers',
            'totalProfit'
        ));
    }
}
