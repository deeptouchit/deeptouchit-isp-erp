<?php

namespace App\Http\Controllers\Reseller;

use App\Models\TenantCustomer;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResellerPackageReport
{
    private const PAGE_SIZES = [10, 15, 20, 30, 50, 100, 250, 500];

    protected function getResellerData(): array
    {
        $user = auth()->user();
        $tenant = $user?->tenant;
        $reseller = $user?->reseller;
        abort_unless($tenant && $reseller && (int) $reseller->tenant_id === (int) $tenant->id, 403);

        return [$user, $reseller, $tenant];
    }

    private function availablePackages(int $tenantId): Builder
    {
        return TenantInternetPackage::where('tenant_id', $tenantId)->where('is_active', true);
    }

    private function filteredPackages(Request $request, int $tenantId): array
    {
        $search = trim($request->string('search')->toString());
        $serviceType = strtoupper($request->string('service_type')->toString());
        $serviceType = in_array($serviceType, ['PPPOE', 'STATIC_IP', 'HOTSPOT'], true) ? $serviceType : 'all';
        $speed = $request->string('speed', 'all')->toString();
        $speed = in_array($speed, ['10', '20', '35', '50+'], true) ? $speed : 'all';

        $query = $this->availablePackages($tenantId);
        if ($search !== '') {
            $query->where(fn ($packages) => $packages->where('package_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('mikrotik_profile', 'like', "%{$search}%"));
        }
        if ($serviceType !== 'all') {
            $query->whereRaw('UPPER(service_type) = ?', [$serviceType]);
        }
        match ($speed) {
            '10' => $query->where('download_speed', '<=', 10),
            '20' => $query->where('download_speed', '>', 10)->where('download_speed', '<=', 20),
            '35' => $query->where('download_speed', '>', 20)->where('download_speed', '<=', 35),
            '50+' => $query->where('download_speed', '>', 35),
            default => null,
        };

        return [$query, $search, $serviceType, $speed];
    }

    private function subscriberMetrics(Builder $packages, TenantReseller $reseller): Collection
    {
        return TenantCustomer::where('tenant_customers.tenant_id', $reseller->tenant_id)
            ->where('tenant_customers.reseller_id', $reseller->id)
            ->whereIn('tenant_customers.package_id', (clone $packages)->select('tenant_internet_packages.id'))
            ->join('tenant_internet_packages', 'tenant_internet_packages.id', '=', 'tenant_customers.package_id')
            ->selectRaw('tenant_customers.package_id, COUNT(*) AS total_subscribers')
            ->selectRaw('SUM(CASE WHEN tenant_customers.status = ? THEN 1 ELSE 0 END) AS active_subscribers', ['active'])
            ->selectRaw('SUM(CASE WHEN tenant_customers.status = ? THEN COALESCE(tenant_customers.monthly_bill, tenant_internet_packages.price, 0) ELSE 0 END) AS monthly_revenue', ['active'])
            ->groupBy('tenant_customers.package_id')
            ->get()
            ->each(fn ($row) => $row->estimated_margin = round($reseller->calculateCommissionAmount((float) $row->monthly_revenue), 2))
            ->keyBy('package_id');
    }

    private function packageStats(Builder $packages, Collection $metrics, TenantReseller $reseller): array
    {
        $active = (int) $metrics->sum('active_subscribers');
        $revenue = (float) $metrics->sum('monthly_revenue');
        $margin = round((float) $metrics->sum('estimated_margin'), 2);

        return [
            'total_assigned_plans' => (clone $packages)->count(),
            'commission_rate' => (float) ($reseller->commission_rate ?? 0),
            'active_subscribers' => $active,
            'total_retail_mrr' => $revenue,
            'total_monthly_profit' => $margin,
            'total_wholesale_cost' => round($revenue - $margin, 2),
            'avg_profit_per_user' => $active ? $margin / $active : 0,
        ];
    }
}
