<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerLedgerController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user?->reseller;
        $tenant = $user?->tenant;
        abort_unless($reseller && $tenant && (int) $reseller->tenant_id === (int) $tenant->id, 403);

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Reseller Wallet Transaction Ledger.
     */

    private function filteredWalletRecords(Request $request, int $tenantId, int $resellerId): array
    {
        $search = trim($request->string('search')->toString());
        $type = strtoupper($request->string('type', 'all')->toString());
        $type = $type === 'ALL' ? 'all' : $type;
        $method = strtoupper($request->string('payment_method', 'all')->toString());
        $method = $method === 'ALL' ? 'all' : $method;
        $month = $request->string('month', 'all')->toString();
        $dates = $request->validate(['date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from']);

        $query = TenantResellerWalletTransaction::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }
        if ($method !== 'all') {
            $query->where('payment_method', $method);
        }
        if (preg_match('/^\d{4}-(?:0[1-9]|1[0-2])$/', $month)) {
            $startOfMonth = Carbon::createFromFormat('!Y-m', $month);
            $query->whereBetween('created_at', [$startOfMonth, $startOfMonth->copy()->endOfMonth()]);
        }
        if (!empty($dates['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($dates['date_from'])->startOfDay());
        }
        if (!empty($dates['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($dates['date_to'])->endOfDay());
        }

        return [$query, $search, $type, $method, $month];
    }

    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$query, $search, $type, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);
        $pageSizes = [10, 15, 20, 30, 50, 100, 250, 500];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $pageSizes, true)) {
            $perPage = 20;
        }

        $filteredTotals = clone $query;
        $query->with(['creator']);

        $transactions = $query->latest('id')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $walletBalance = (float) ($reseller->wallet_balance ?? 0);
        $creditLimit = (float) ($reseller->credit_limit ?? 0);
        $totalAvailable = (float) ($reseller->total_available_balance ?? ($walletBalance + $creditLimit));

        $kpiBaseQuery = $filteredTotals;

        $totalCredits = (float) (clone $kpiBaseQuery)->where('type', 'CREDIT')->sum('amount');
        $totalDebits = (float) (clone $kpiBaseQuery)->where('type', 'DEBIT')->sum('amount');
        $totalCount = (int) (clone $kpiBaseQuery)->count();

        $stats = [
            'wallet_balance' => $walletBalance,
            'credit_limit' => $creditLimit,
            'total_available' => $totalAvailable,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'total_count' => $totalCount,
        ];

        // 3. Dropdowns
        $availableMonths = TenantResellerWalletTransaction::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw((new TenantResellerWalletTransaction)->getConnection()->getDriverName() === 'sqlite'
                ? "DISTINCT strftime('%Y-%m', created_at) as m"
                : "DISTINCT DATE_FORMAT(created_at, '%Y-%m') as m")
            ->orderByDesc('m')
            ->pluck('m')
            ->filter()
            ->values();

        $availableMethods = TenantResellerWalletTransaction::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.ledger.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'transactions',
            'stats',
            'search',
            'type',
            'method',
            'month',
            'perPage',
            'pageSizes',
            'availableMonths',
            'availableMethods',
            'currencySymbol'
        ));
    }

    /**
     * View transaction details as JSON.
     */
    public function show(int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $trx = TenantResellerWalletTransaction::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->with(['creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $trx->id,
                'trx_id' => $trx->trx_id,
                'type' => $trx->type,
                'type_badge' => $trx->type_badge,
                'amount' => (float) $trx->amount,
                'balance_before' => (float) $trx->balance_before,
                'balance_after' => (float) $trx->balance_after,
                'payment_method' => $trx->payment_method,
                'reference_no' => $trx->reference_no,
                'description' => $trx->description,
                'created_at' => $trx->created_at ? $trx->created_at->format('d M Y, h:i:s A') : 'N/A',
                'created_by' => $trx->creator?->name ?? 'System Automated',
                'reseller_name' => $reseller->name,
                'reseller_code' => $reseller->code,
                'tenant_name' => $tenant->company_name ?? $tenant->name,
            ],
        ]);
    }

    /**
     * Export Transactions as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$query, $search, $type, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);

        $transactions = $query->latest('id')->get();
        $filename = 'wallet_ledger_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Trx ID',
                'Type',
                'Amount',
                'Balance Before',
                'Balance After',
                'Method',
                'Reference No',
                'Description',
                'Date & Time'
            ]);

            foreach ($transactions as $idx => $t) {
                fputcsv($handle, [
                    $idx + 1,
                    $t->trx_id,
                    $t->type,
                    $t->amount,
                    $t->balance_before,
                    $t->balance_after,
                    $t->payment_method ?? 'N/A',
                    $t->reference_no ?? 'N/A',
                    $t->description ?? '',
                    $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print wallet transaction statement report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$query, $search, $type, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);

        $transactions = $query->with(['creator'])->latest('id')->get();

        $totalCredits = $transactions->where('type', 'CREDIT')->sum('amount');
        $totalDebits = $transactions->where('type', 'DEBIT')->sum('amount');
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.ledger.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'transactions',
            'totalCredits',
            'totalDebits',
            'currencySymbol',
            'month',
            'type',
            'method'
        ));
    }
}
