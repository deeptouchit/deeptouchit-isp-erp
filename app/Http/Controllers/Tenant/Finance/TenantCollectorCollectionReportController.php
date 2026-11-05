<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCollectorCollectionReportController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display Collector-Wise Collection Summary Report
     * Adheres strictly to AGENTS.md Rules (2.A, 2.B, 2.C, 2.D)
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        // 1. Filter Parameters
        $period = $request->get('period', 'monthly'); // daily, monthly, yearly, all
        $partnerFilter = $request->get('partner_id', 'all'); // all, direct, or reseller_id
        $collectorFilter = $request->get('collector_id', 'all');
        $search = trim($request->get('search', ''));
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedYear = $request->get('year', Carbon::now()->format('Y'));
        $perPage = (int) $request->get('per_page', 20);
        if (!in_array($perPage, [10, 15, 20, 30, 50, 100, 250, 500])) {
            $perPage = 20;
        }

        // 2. Resolve Date Range based on Period
        [$startDate, $endDate, $periodLabel] = $this->resolveDateWindow($period, $selectedMonth, $selectedYear, $dateFrom, $dateTo);

        // 3. Overall KPI Metrics (Rule 2.B: Strictly 6 Cards)
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $todayCollections = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->where('status', '!=', 'void')
            ->sum('amount');

        $thisMonthCollections = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->where('status', '!=', 'void')
            ->sum('amount');

        // Filtered Scope Query
        $baseQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'void');

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('paid_at', [$startDate, $endDate]);
        }

        if ($partnerFilter === 'direct') {
            $baseQuery->whereNull('reseller_id');
        } elseif ($partnerFilter !== 'all' && !empty($partnerFilter)) {
            $baseQuery->where('reseller_id', (int) $partnerFilter);
        }

        if ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            if ($collectorFilter === 'direct') {
                $baseQuery->whereNull('collected_by');
            } else {
                $baseQuery->where('collected_by', (int) $collectorFilter);
            }
        }

        $filteredTotalAmount = (float) (clone $baseQuery)->sum('amount');
        $filteredTotalCount = (int) (clone $baseQuery)->count();
        $filteredCashAmount = (float) (clone $baseQuery)->where('payment_method', 'cash')->sum('amount');
        $filteredDigitalAmount = (float) (clone $baseQuery)->where('payment_method', '!=', 'cash')->sum('amount');
        $activeCollectorsCount = (int) (clone $baseQuery)->distinct('collected_by')->count('collected_by');

        // 4. Build Collector-Wise Aggregation Matrix
        // Group by: collected_by, reseller_id, and period chunk
        $query = DB::table('tenant_customer_payments as p')
            ->leftJoin('users as u', 'p.collected_by', '=', 'u.id')
            ->leftJoin('tenant_resellers as r', 'p.reseller_id', '=', 'r.id')
            ->where('p.tenant_id', $tenant->id)
            ->where('p.status', '!=', 'void');

        if ($startDate && $endDate) {
            $query->whereBetween('p.paid_at', [$startDate, $endDate]);
        }

        if ($partnerFilter === 'direct') {
            $query->whereNull('p.reseller_id');
        } elseif ($partnerFilter !== 'all' && !empty($partnerFilter)) {
            $query->where('p.reseller_id', (int) $partnerFilter);
        }

        if ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            if ($collectorFilter === 'direct') {
                $query->whereNull('p.collected_by');
            } else {
                $query->where('p.collected_by', (int) $collectorFilter);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.phone', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('r.name', 'like', "%{$search}%")
                  ->orWhere('r.code', 'like', "%{$search}%");
            });
        }

        // Select and Grouping
        $query->select(
            DB::raw('COALESCE(p.collected_by, 0) as collector_id'),
            DB::raw("DATE_FORMAT(COALESCE(p.paid_at, p.created_at), '%Y-%m') as collection_month"),
            DB::raw('COALESCE(u.name, "Direct / System") as collector_name'),
            DB::raw('u.phone as collector_phone'),
            DB::raw('u.role as collector_role'),
            DB::raw('p.reseller_id'),
            DB::raw('COALESCE(r.name, "Direct HQ") as partner_name'),
            DB::raw('r.code as partner_code'),
            DB::raw('COUNT(p.id) as total_count'),
            DB::raw('SUM(p.amount) as total_amount'),
            DB::raw('SUM(CASE WHEN p.payment_method = "cash" THEN p.amount ELSE 0 END) as cash_amount'),
            DB::raw('SUM(CASE WHEN p.payment_method != "cash" THEN p.amount ELSE 0 END) as digital_amount'),
            DB::raw('MIN(p.paid_at) as first_payment_at'),
            DB::raw('MAX(p.paid_at) as last_payment_at')
        )
        ->groupBy('p.collected_by', DB::raw("DATE_FORMAT(COALESCE(p.paid_at, p.created_at), '%Y-%m')"), 'u.name', 'u.phone', 'u.role', 'p.reseller_id', 'r.name', 'r.code')
        ->orderByDesc('collection_month')
        ->orderByDesc('total_amount');

        $rows = $query->paginate($perPage)->withQueryString();

        // 5. Load Dropdown Options
        $resellers = TenantReseller::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $allCollectors = User::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->where('role', 'like', '%collector%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin')
                  ->orWhere('role', 'admin');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role']);

        return view('tenant.finance.collector_collections', compact(
            'tenant',
            'rows',
            'period',
            'periodLabel',
            'partnerFilter',
            'collectorFilter',
            'search',
            'dateFrom',
            'dateTo',
            'selectedMonth',
            'selectedYear',
            'perPage',
            'todayCollections',
            'thisMonthCollections',
            'filteredTotalAmount',
            'filteredTotalCount',
            'filteredCashAmount',
            'filteredDigitalAmount',
            'activeCollectorsCount',
            'resellers',
            'allCollectors'
        ));
    }

    /**
     * Display Itemized Collection Receipts Full Page for a Specific Collector & Partner
     * (কালেক্টর আদায় বিস্তারিত তালিকা - Dedicated Full Page View)
     */
    public function showDetails(Request $request, $collectorId): View
    {
        $tenant = $this->getTenant();
        $resellerId = $request->get('reseller_id', '0');
        $period = $request->get('period', 'monthly');
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedYear = $request->get('year', Carbon::now()->format('Y'));
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $paymentMethod = $request->get('payment_method', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);
        if (!in_array($perPage, [10, 15, 20, 30, 50, 100, 250, 500])) {
            $perPage = 20;
        }

        // 1. Resolve Date Range
        [$startDate, $endDate, $periodLabel] = $this->resolveDateWindow($period, $selectedMonth, $selectedYear, $dateFrom, $dateTo);

        // 2. Resolve Collector & Partner Identity
        $collector = null;
        $collectorName = 'Direct / System';
        $collectorPhone = '';
        $collectorRole = 'System';
        if ($collectorId !== '0' && $collectorId !== 'direct' && !empty($collectorId)) {
            $collector = User::where('tenant_id', $tenant->id)->find($collectorId);
            if ($collector) {
                $collectorName = $collector->name;
                $collectorPhone = $collector->phone ?? '';
                $collectorRole = ucwords(str_replace('_', ' ', $collector->role ?? 'Collector'));
            }
        }

        $partner = null;
        $partnerName = 'Direct HQ';
        if ($resellerId !== '0' && $resellerId !== 'direct' && !empty($resellerId)) {
            $partner = TenantReseller::where('tenant_id', $tenant->id)->find($resellerId);
            if ($partner) {
                $partnerName = $partner->name . ($partner->code ? " ({$partner->code})" : '');
            }
        }

        // 3. Base Payments Query for this Collector & Scope
        $baseQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'void');

        if ($collectorId === '0' || $collectorId === 'direct') {
            $baseQuery->whereNull('collected_by');
        } else {
            $baseQuery->where('collected_by', (int) $collectorId);
        }

        if ($resellerId === '0' || $resellerId === 'direct') {
            $baseQuery->whereNull('reseller_id');
        } elseif (!empty($resellerId) && $resellerId !== 'all') {
            $baseQuery->where('reseller_id', (int) $resellerId);
        }

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('paid_at', [$startDate, $endDate]);
        }

        // KPI metrics for this collector's session
        $totalAmount = (float) (clone $baseQuery)->sum('amount');
        $totalCount = (int) (clone $baseQuery)->count();
        $cashAmount = (float) (clone $baseQuery)->where('payment_method', 'cash')->sum('amount');
        $digitalAmount = (float) (clone $baseQuery)->where('payment_method', '!=', 'cash')->sum('amount');
        $uniqueCustomersCount = (int) (clone $baseQuery)->distinct('customer_id')->count('customer_id');
        $avgAmount = $totalCount > 0 ? round($totalAmount / $totalCount, 2) : 0;

        // 4. Filtered Paginated Query
        $paymentsQuery = (clone $baseQuery)
            ->with([
                'customer:id,customer_id,username,name,father_name,zone_id,address,package_id,reseller_id,phone',
                'customer.zone:id,name',
                'customer.package:id,name,package_name,mikrotik_profile',
                'customer.reseller:id,name,code',
                'invoice:id,invoice_no,total_payable,paid_amount,status,paid_at',
                'reseller:id,name,code',
                'collector:id,name,phone'
            ]);

        if ($paymentMethod !== 'all' && !empty($paymentMethod)) {
            $paymentsQuery->where('payment_method', $paymentMethod);
        }

        if (!empty($search)) {
            $paymentsQuery->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('customer_id', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $paymentsQuery->orderByDesc('paid_at')->paginate($perPage)->withQueryString();

        return view('tenant.finance.collector_collection_details', compact(
            'tenant',
            'collectorId',
            'collector',
            'collectorName',
            'collectorPhone',
            'collectorRole',
            'resellerId',
            'partner',
            'partnerName',
            'period',
            'periodLabel',
            'selectedMonth',
            'selectedYear',
            'dateFrom',
            'dateTo',
            'paymentMethod',
            'search',
            'perPage',
            'totalAmount',
            'totalCount',
            'cashAmount',
            'digitalAmount',
            'uniqueCustomersCount',
            'avgAmount',
            'payments'
        ));
    }

    /**
     * Get Itemized Payment Receipts List for a Specific Collector & Partner Row
     * Used for API / Quick Fetch
     */
    public function getDetails(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $collectorId = $request->get('collector_id');
        $resellerId = $request->get('reseller_id');
        $period = $request->get('period', 'monthly');
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedYear = $request->get('year', Carbon::now()->format('Y'));
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        [$startDate, $endDate, $periodLabel] = $this->resolveDateWindow($period, $selectedMonth, $selectedYear, $dateFrom, $dateTo);

        $query = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'void')
            ->with(['customer:id,customer_id,username,name,phone,package_id', 'customer.package:id,name,price']);

        if ($collectorId === '0' || $collectorId === '' || is_null($collectorId)) {
            $query->whereNull('collected_by');
        } else {
            $query->where('collected_by', (int) $collectorId);
        }

        if ($resellerId === '0' || $resellerId === '' || is_null($resellerId)) {
            $query->whereNull('reseller_id');
        } else {
            $query->where('reseller_id', (int) $resellerId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('paid_at', [$startDate, $endDate]);
        }

        $payments = $query->orderByDesc('paid_at')->limit(500)->get();

        $collectorName = 'Direct / System';
        if ($collectorId && $collectorId !== '0') {
            $user = User::where('tenant_id', $tenant->id)->find($collectorId);
            if ($user) {
                $collectorName = $user->name . ($user->phone ? " ({$user->phone})" : '');
            }
        }

        $partnerName = 'Direct HQ';
        if ($resellerId && $resellerId !== '0') {
            $partner = TenantReseller::where('tenant_id', $tenant->id)->find($resellerId);
            if ($partner) {
                $partnerName = $partner->name;
            }
        }

        $items = $payments->map(function ($p) {
            return [
                'id' => $p->id,
                'invoice_no' => $p->invoice_no ?: ('REC-' . str_pad($p->id, 5, '0', STR_PAD_LEFT)),
                'customer_id' => $p->customer?->customer_id ?? 'N/A',
                'customer_name' => $p->customer?->name ?? 'Unknown',
                'customer_username' => $p->customer?->username ?? 'N/A',
                'customer_phone' => $p->customer?->phone ?? '—',
                'package_name' => $p->customer?->package?->name ?? '—',
                'amount' => (float) $p->amount,
                'payment_method' => strtoupper($p->payment_method ?: 'CASH'),
                'paid_at' => $p->paid_at ? $p->paid_at->format('d M Y, h:i A') : '—',
                'status' => ucfirst($p->status ?: 'completed'),
                'receipt_url' => route('tenant.finance.payments.show', $p->id),
            ];
        });

        return response()->json([
            'success' => true,
            'collector_name' => $collectorName,
            'partner_name' => $partnerName,
            'period_label' => $periodLabel,
            'total_count' => $items->count(),
            'total_amount' => (float) $payments->sum('amount'),
            'payments' => $items,
        ]);
    }

    /**
     * Export Collector Collections Summary as CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $period = $request->get('period', 'monthly');
        $partnerFilter = $request->get('partner_id', 'all');
        $collectorFilter = $request->get('collector_id', 'all');
        $search = trim($request->get('search', ''));
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedYear = $request->get('year', Carbon::now()->format('Y'));

        [$startDate, $endDate, $periodLabel] = $this->resolveDateWindow($period, $selectedMonth, $selectedYear, $dateFrom, $dateTo);

        $query = DB::table('tenant_customer_payments as p')
            ->leftJoin('users as u', 'p.collected_by', '=', 'u.id')
            ->leftJoin('tenant_resellers as r', 'p.reseller_id', '=', 'r.id')
            ->where('p.tenant_id', $tenant->id)
            ->where('p.status', '!=', 'void');

        if ($startDate && $endDate) {
            $query->whereBetween('p.paid_at', [$startDate, $endDate]);
        }

        if ($partnerFilter === 'direct') {
            $query->whereNull('p.reseller_id');
        } elseif ($partnerFilter !== 'all' && !empty($partnerFilter)) {
            $query->where('p.reseller_id', (int) $partnerFilter);
        }

        if ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            if ($collectorFilter === 'direct') {
                $query->whereNull('p.collected_by');
            } else {
                $query->where('p.collected_by', (int) $collectorFilter);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.phone', 'like', "%{$search}%")
                  ->orWhere('r.name', 'like', "%{$search}%");
            });
        }

        $rows = $query->select(
            DB::raw("DATE_FORMAT(COALESCE(p.paid_at, p.created_at), '%Y-%m') as collection_month"),
            DB::raw('COALESCE(u.name, "Direct / System") as collector_name'),
            DB::raw('u.phone as collector_phone'),
            DB::raw('COALESCE(r.name, "Direct HQ") as partner_name'),
            DB::raw('COUNT(p.id) as total_count'),
            DB::raw('SUM(p.amount) as total_amount'),
            DB::raw('SUM(CASE WHEN p.payment_method = "cash" THEN p.amount ELSE 0 END) as cash_amount'),
            DB::raw('SUM(CASE WHEN p.payment_method != "cash" THEN p.amount ELSE 0 END) as digital_amount')
        )
        ->groupBy('p.collected_by', DB::raw("DATE_FORMAT(COALESCE(p.paid_at, p.created_at), '%Y-%m')"), 'u.name', 'u.phone', 'p.reseller_id', 'r.name')
        ->orderByDesc('collection_month')
        ->orderByDesc('total_amount')
        ->get();

        $filename = 'collector_collections_' . date('Y_m_d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($rows, $periodLabel) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['#', 'Month', 'Collector Name', 'Mobile', 'Partner', 'Total Receipts', 'Total Collected (Tk)']);

            foreach ($rows as $idx => $r) {
                $monthStr = $r->collection_month ? Carbon::parse($r->collection_month . '-01')->format('F Y') : $periodLabel;
                fputcsv($handle, [
                    $idx + 1,
                    $monthStr,
                    $r->collector_name,
                    $r->collector_phone ?? '—',
                    $r->partner_name,
                    $r->total_count,
                    number_format($r->total_amount, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Resolve Date Range Window based on Period
     */
    protected function resolveDateWindow(string $period, string $selectedMonth, string $selectedYear, ?string $dateFrom, ?string $dateTo): array
    {
        switch ($period) {
            case 'daily':
                if (!empty($dateFrom)) {
                    $day = Carbon::parse($dateFrom);
                } else {
                    $day = Carbon::today();
                }
                $startDate = $day->copy()->startOfDay();
                $endDate = $day->copy()->endOfDay();
                $label = $day->format('d M Y');
                break;

            case 'yearly':
                $year = !empty($selectedYear) ? (int) $selectedYear : Carbon::now()->year;
                $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
                $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();
                $label = 'Year ' . $year;
                break;

            case 'all':
                $startDate = null;
                $endDate = null;
                $label = 'All Time';
                break;

            case 'custom':
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $startDate = Carbon::parse($dateFrom)->startOfDay();
                    $endDate = Carbon::parse($dateTo)->endOfDay();
                    $label = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
                } else {
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    $label = $startDate->format('F Y');
                }
                break;

            case 'monthly':
            default:
                if (!empty($selectedMonth)) {
                    $m = Carbon::parse($selectedMonth . '-01');
                } else {
                    $m = Carbon::now();
                }
                $startDate = $m->copy()->startOfMonth();
                $endDate = $m->copy()->endOfMonth();
                $label = $m->format('F Y');
                break;
        }

        return [$startDate, $endDate, $label];
    }
}

