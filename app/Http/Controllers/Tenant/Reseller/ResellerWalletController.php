<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResellerWalletController extends Controller
{
    /**
     * Resolve the active Tenant safely.
     */
    protected function getTenant(): Tenant
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Display Reseller Wallets & Balances Management.
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $balanceFilter = $request->input('balance_status');
        $billingType = $request->input('billing_type');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        // 1. Fetch All Resellers for dropdown filter
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Resellers for Wallets Grid
        $resellerQuery = TenantReseller::where('tenant_id', $tenant->id);

        if ($search) {
            $resellerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('prefix', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($selectedResellerId) {
            $resellerQuery->where('id', $selectedResellerId);
        }

        if ($billingType) {
            $resellerQuery->where('billing_type', $billingType);
        }

        if ($balanceFilter === 'low') {
            $resellerQuery->where('wallet_balance', '>=', 0)->where('wallet_balance', '<', 1000);
        } elseif ($balanceFilter === 'healthy') {
            $resellerQuery->where('wallet_balance', '>=', 5000);
        } elseif ($balanceFilter === 'negative') {
            $resellerQuery->where('wallet_balance', '<', 0);
        }

        $resellers = $resellerQuery->orderBy('wallet_balance', 'desc')->paginate($perPage)->withQueryString();

        // 3. Calculate 6-Card Metric KPIs
        $totalWalletLiquidity = (float) $allResellers->sum('wallet_balance');
        $totalCreditLimits = (float) $allResellers->sum('credit_limit');
        $totalPurchasingPower = $totalWalletLiquidity + $totalCreditLimits;
        $lowBalanceCount = $allResellers->where('wallet_balance', '>=', 0)->where('wallet_balance', '<', 1000)->count();
        $overdrawnCount = $allResellers->where('wallet_balance', '<', 0)->count();
        $prepaidResellersCount = $allResellers->where('billing_type', 'PREPAID_WALLET')->count();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalRechargedThisMonth = (float) TenantResellerWalletTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'CREDIT')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return view('tenant.resellers.wallets', compact(
            'tenant',
            'currencySymbol',
            'resellers',
            'allResellers',
            'totalWalletLiquidity',
            'totalCreditLimits',
            'totalPurchasingPower',
            'lowBalanceCount',
            'overdrawnCount',
            'prepaidResellersCount',
            'totalRechargedThisMonth',
            'search',
            'selectedResellerId',
            'balanceFilter',
            'billingType',
            'perPage'
        ));
    }

    /**
     * Top-up (Credit), Deduct (Debit), or Set Exact Reseller Wallet Balance.
     */
    public function adjustBalance(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'type' => 'required|in:CREDIT,DEBIT,SET,credit,debit,set',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $inputAmount = (float) $validated['amount'];
        $type = strtoupper($validated['type']);
        $balanceBefore = (float) $reseller->wallet_balance;
        $paymentMethod = $validated['payment_method'] ?: 'Cash';

        if ($type === 'CREDIT') {
            $balanceAfter = $balanceBefore + $inputAmount;
            $txnAmount = $inputAmount;
            $txnType = 'CREDIT';
        } elseif ($type === 'DEBIT') {
            $balanceAfter = $balanceBefore - $inputAmount;
            $txnAmount = $inputAmount;
            $txnType = 'DEBIT';
        } else { // SET exact balance
            $balanceAfter = $inputAmount;
            $diff = $balanceAfter - $balanceBefore;
            $txnAmount = abs($diff);
            $txnType = ($diff >= 0) ? 'CREDIT' : 'DEBIT';
        }

        DB::beginTransaction();
        try {
            $reseller->wallet_balance = $balanceAfter;
            $reseller->save();

            $trxId = TenantResellerWalletTransaction::generateTrxId($tenant->id);

            $trx = null;
            if ($txnAmount > 0) {
                $trx = TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => $trxId,
                    'type' => $txnType,
                    'amount' => $txnAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'payment_method' => $paymentMethod,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'description' => $validated['description'] ?: ($type === 'CREDIT' ? "Wallet Top-up of ৳{$txnAmount}" : ($type === 'DEBIT' ? "Wallet deduction of ৳{$txnAmount}" : "Manual balance set to ৳{$balanceAfter}")),
                    'created_by' => Auth::id() ?: 1,
                ]);
            }

            DB::commit();

            $actionWord = ($type === 'CREDIT') ? 'recharged with' : (($type === 'DEBIT') ? 'deducted by' : 'adjusted to');
            $msg = "Wallet for '{$reseller->name}' successfully {$actionWord} ৳" . number_format($inputAmount, 2) . ". New Balance: ৳" . number_format($balanceAfter, 2) . ".";

            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'transaction' => $trx,
                    'new_balance' => $balanceAfter,
                    'wallet_balance' => $balanceAfter,
                ]);
            }

            return redirect()->route('tenant.resellers.wallets')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * Update Reseller Credit Limit & Overdraft Ceiling.
     */
    public function updateCreditLimit(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $reseller->credit_limit = (float) $validated['credit_limit'];
        $reseller->save();

        $msg = "Credit limit for '{$reseller->name}' updated to ৳" . number_format($validated['credit_limit'], 2) . "!";

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'credit_limit' => $validated['credit_limit'],
            ]);
        }

        return redirect()->route('tenant.resellers.wallets')->with('success', $msg);
    }
}
