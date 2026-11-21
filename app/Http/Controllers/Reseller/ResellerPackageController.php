<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerPackageController extends Controller
{
    use ResellerPackageReport;

    /**
     * Display Assigned Internet Packages & Margin Rates.
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
            'pageSizes',
            'currencySymbol'
        ));
    }

    /**
     * Show detailed package specs and profit margins as JSON.
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
                'package_name' => $pkg->name ?: ($pkg->mikrotik_profile ?: $pkg->package_name),
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
                'total_revenue' => (float) ($metrics?->monthly_revenue ?? 0),
                'total_profit' => (float) ($metrics?->estimated_margin ?? 0),
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
        [$query] = $this->filteredPackages($request, $tenant->id);
        $packages = (clone $query)->orderBy('price')->orderBy('id')->get();
        $commissionRate = (float) ($reseller->commission_rate ?? 0);
        $currency = $tenant->currency_code ?? config('app.currency', 'BDT');
        $filename = 'available_packages_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($packages, $commissionRate, $reseller, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Package Name',
                'Download Speed (Mbps)',
                'Upload Speed (Mbps)',
                'BDIX Speed (Mbps)',
                'Retail Price (' . $currency . ')',
                'Wholesale Cost (' . $currency . ')',
                'Margin Amount (' . $currency . ')',
                'Commission Rate (%)',
                'Validity (Days)',
                'Service Type'
            ]);

            foreach ($packages as $idx => $p) {
                $retail = (float) $p->price;
                $margin = $reseller->calculateCommissionAmount($retail);
                $wholesale = $reseller->calculateResellerCost($retail);

                fputcsv($handle, [
                    $idx + 1,
                    $p->name ?: ($p->mikrotik_profile ?: $p->package_name),
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
        [$query] = $this->filteredPackages($request, $tenant->id);
        $packages = (clone $query)->orderBy('price')->orderBy('id')->get();
        $commissionRate = (float) ($reseller->commission_rate ?? 0);
        $currencySymbol = $tenant->currency_symbol ?? '৳';

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
