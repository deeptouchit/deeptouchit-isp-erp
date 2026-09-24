<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerLedgerController extends Controller
{
    /**
     * Display Recharge & Adjustment Ledger page.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $type = $request->input('type'); // CREDIT or DEBIT
        $paymentMethod = $request->input('payment_method');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        // All Resellers for filter dropdown
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // Query Transactions
        $query = TenantResellerWalletTransaction::where('tenant_id', $tenant->id)
            ->with(['reseller', 'creator']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('trx_id', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhere('prefix', 'like', "%{$search}%");
                  });
            });
        }

        if ($selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $transactions = $query->latest('id')->paginate($perPage)->withQueryString();

        // 6 KPI Metric Calculations
        $baseTrx = TenantResellerWalletTransaction::where('tenant_id', $tenant->id);
        $totalCreditRecharges = (float) (clone $baseTrx)->where('type', 'CREDIT')->sum('amount');
        $totalDebits = (float) (clone $baseTrx)->where('type', 'DEBIT')->sum('amount');
        $totalTransactionsCount = (clone $baseTrx)->count();
        $creditCount = (clone $baseTrx)->where('type', 'CREDIT')->count();
        $debitCount = (clone $baseTrx)->where('type', 'DEBIT')->count();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $thisMonthVolume = (float) (clone $baseTrx)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Distinct payment methods used for filter
        $availableMethods = TenantResellerWalletTransaction::where('tenant_id', $tenant->id)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method');

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.resellers.ledger', compact(
            'tenant',
            'transactions',
            'allResellers',
            'availableMethods',
            'totalCreditRecharges',
            'totalDebits',
            'thisMonthVolume',
            'totalTransactionsCount',
            'creditCount',
            'debitCount',
            'search',
            'selectedResellerId',
            'type',
            'paymentMethod',
            'dateFrom',
            'dateTo',
            'perPage',
            'currencySymbol'
        ));
    }
}
