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
     * Display Reseller Wallet Transaction Ledger.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $type = $request->input('type', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Scope filter closure
        $applyFilterScope = function ($q) use ($search, $type, $method, $month) {
            if ($search !== '') {
                $q->where(function ($sub) use ($search) {
                    $sub->where('trx_id', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($type !== 'all' && !empty($type)) {
                $q->where('type', strtoupper($type));
            }

            if ($method !== 'all' && !empty($method)) {
                $q->where('payment_method', strtoupper($method));
            }

            if ($month !== 'all' && !empty($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $q->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
            }
        };

        // 1. Base Query
        $query = TenantResellerWalletTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['creator']);

        $applyFilterScope($query);

        $transactions = $query->latest('id')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $walletBalance = (float) ($reseller->wallet_balance ?? 0);
        $creditLimit = (float) ($reseller->credit_limit ?? 0);
        $totalAvailable = (float) ($reseller->total_available_balance ?? ($walletBalance + $creditLimit));

        $kpiBaseQuery = TenantResellerWalletTransaction::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $applyFilterScope($kpiBaseQuery);

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
            ->selectRaw("DISTINCT DATE_FORMAT(created_at, '%Y-%m') as m")
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

        $currencySymbol = '৳';

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

        $search = trim($request->input('search', ''));
        $type = $request->input('type', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');

        $query = TenantResellerWalletTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type !== 'all') {
            $query->where('type', strtoupper($type));
        }

        if ($method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

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

        $search = trim($request->input('search', ''));
        $type = $request->input('type', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');

        $query = TenantResellerWalletTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['creator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type !== 'all') {
            $query->where('type', strtoupper($type));
        }

        if ($method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

        $transactions = $query->latest('id')->get();

        $totalCredits = $transactions->where('type', 'CREDIT')->sum('amount');
        $totalDebits = $transactions->where('type', 'DEBIT')->sum('amount');
        $currencySymbol = '৳';

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
