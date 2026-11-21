<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerMarginController extends Controller
{
    use ResellerPackageReport;

    /**
     * Display Partner Margin & Profit Rates Analytics.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        [$query, $search, $serviceType, $speedTier] = $this->filteredPackages($request, $tenant->id);
        $pageSizes = self::PAGE_SIZES;
        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, $pageSizes, true) ? $perPage : 20;
        $subscriberCounts = $this->subscriberMetrics($query, $reseller);
        $stats = $this->packageStats($query, $subscriberCounts, $reseller);
        $packages = $query->orderBy('price')->orderBy('id')->paginate($perPage)->withQueryString();
        $commissionRate = $stats['commission_rate'];
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.margins.index', compact(
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
            'pageSizes',
            'currencySymbol'
        ));
    }

    /**
     * Show detailed package margin simulation as JSON.
     */
    public function show(int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $query = $this->availablePackages($tenant->id)->whereKey($id);
        $pkg = (clone $query)->firstOrFail();
        $commissionRate = (float) ($reseller->commission_rate ?? 0);
        $retailPrice = (float) $pkg->price;
        $profitMargin = $reseller->calculateCommissionAmount($retailPrice);
        $wholesaleCost = $reseller->calculateResellerCost($retailPrice);
        $metrics = $this->subscriberMetrics($query, $reseller)->get($id);
        $activeCount = (int) ($metrics?->active_subscribers ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pkg->id,
                'name' => $pkg->name ?: ($pkg->mikrotik_profile ?: $pkg->package_name),
                'package_name' => $pkg->name ?: ($pkg->mikrotik_profile ?: $pkg->package_name),
                'download_speed' => $pkg->download_speed,
                'upload_speed' => $pkg->upload_speed,
                'price' => $retailPrice,
                'wholesale_cost' => $wholesaleCost,
                'profit_margin' => $profitMargin,
                'commission_rate' => $commissionRate,
                'active_subscribers' => $activeCount,
                'monthly_revenue' => (float) ($metrics?->monthly_revenue ?? 0),
                'monthly_profit' => (float) ($metrics?->estimated_margin ?? 0),
            ]
        ]);
    }

    /**
     * Export Margin & Profit Rates Schedule as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        [$query] = $this->filteredPackages($request, $tenant->id);
        $packages = (clone $query)->orderBy('price')->orderBy('id')->get();
        $subscriberCounts = $this->subscriberMetrics($query, $reseller);
        $commissionRate = (float) ($reseller->commission_rate ?? 0);
        $currency = $tenant->currency_code ?? config('app.currency', 'BDT');
        $filename = 'margin_rates_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($packages, $subscriberCounts, $commissionRate, $reseller, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'MikroTik Profile',
                'Retail Price (' . $currency . ')',
                'Partner Wholesale Cost (' . $currency . ')',
                'Margin Per User (' . $currency . ')',
                'Commission Rate (%)',
                'Active Subscribers',
                'Estimated Monthly Margin (' . $currency . ')'
            ]);

            foreach ($packages as $idx => $p) {
                $retail = (float) $p->price;
                $margin = $reseller->calculateCommissionAmount($retail);
                $cost = $reseller->calculateResellerCost($retail);
                $activeSubs = $subscriberCounts->get($p->id)?->active_subscribers ?? 0;
                $totalProfit = (float) ($subscriberCounts->get($p->id)?->estimated_margin ?? 0);

                fputcsv($handle, [
                    $idx + 1,
                    $p->name ?: ($p->mikrotik_profile ?: $p->package_name),
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
        [$query] = $this->filteredPackages($request, $tenant->id);
        $packages = (clone $query)->orderBy('price')->orderBy('id')->get();
        $commissionRate = (float) ($reseller->commission_rate ?? 0);
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $subscriberCounts = $this->subscriberMetrics($query, $reseller);
        $totalSubscribers = (int) $subscriberCounts->sum('active_subscribers');
        $totalProfit = (float) $subscriberCounts->sum('estimated_margin');

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
