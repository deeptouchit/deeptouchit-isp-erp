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
            abort(404, __('ISP Tenant record not found.'));
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

        // Tier-2 Secondary Filters Count
        $activeMoreCount = 0;
        if (!empty($billingType)) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;

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
            'activeMoreCount',
            'isMoreOpen',
            'perPage'
        ));
    }

    /**
     * Top-up (Credit), Deduct (Debit), or Set Exact Reseller Wallet Balance.
     */
    public function adjustBalance(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $currency = $tenant->currency_symbol ?? '৳';

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
                $defaultDescription = match ($type) {
                    'CREDIT' => __('Wallet Top-up of :currency:amount', [
                        'currency' => $currency,
                        'amount' => number_format($txnAmount, 2),
                    ]),
                    'DEBIT' => __('Wallet deduction of :currency:amount', [
                        'currency' => $currency,
                        'amount' => number_format($txnAmount, 2),
                    ]),
                    default => __('Manual balance set to :currency:amount', [
                        'currency' => $currency,
                        'amount' => number_format($balanceAfter, 2),
                    ]),
                };

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
                    'description' => $validated['description'] ?: $defaultDescription,
                    'created_by' => Auth::id() ?: 1,
                ]);
            }

            DB::commit();

            $formattedAmount = number_format($inputAmount, 2);
            $formattedBalance = number_format($balanceAfter, 2);

            if ($type === 'CREDIT') {
                $msg = __("Wallet for ':name' successfully recharged with :currency:amount. New Balance: :currency:balance.", [
                    'name' => $reseller->name,
                    'currency' => $currency,
                    'amount' => $formattedAmount,
                    'balance' => $formattedBalance,
                ]);
            } elseif ($type === 'DEBIT') {
                $msg = __("Wallet for ':name' successfully deducted by :currency:amount. New Balance: :currency:balance.", [
                    'name' => $reseller->name,
                    'currency' => $currency,
                    'amount' => $formattedAmount,
                    'balance' => $formattedBalance,
                ]);
            } else {
                $msg = __("Wallet for ':name' successfully adjusted to :currency:amount. New Balance: :currency:balance.", [
                    'name' => $reseller->name,
                    'currency' => $currency,
                    'amount' => $formattedAmount,
                    'balance' => $formattedBalance,
                ]);
            }

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
            $errorMsg = __('Transaction failed: :error', ['error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json(['success' => false, 'message' => $errorMsg], 500);
            }
            return back()->with('error', $errorMsg);
        }
    }

    /**
     * Update Reseller Credit Limit & Overdraft Ceiling.
     */
    public function updateCreditLimit(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $currency = $tenant->currency_symbol ?? '৳';

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $reseller->credit_limit = (float) $validated['credit_limit'];
        $reseller->save();

        $msg = __("Credit limit for ':name' updated to :currency:amount!", [
            'name' => $reseller->name,
            'currency' => $currency,
            'amount' => number_format((float) $validated['credit_limit'], 2),
        ]);

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
