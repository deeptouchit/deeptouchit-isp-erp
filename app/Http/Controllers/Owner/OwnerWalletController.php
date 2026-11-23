<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantWallet;
use App\Models\TenantWalletTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerWalletController extends Controller
{
    /**
     * Display all tenant wallets and advance balances.
     */
    public function index(Request $request)
    {
        $totalWalletsCount = TenantWallet::count();
        $totalBalance = (float) TenantWallet::sum('balance');
        $walletsWithBalanceCount = TenantWallet::where('balance', '>', 0)->count();

        $query = TenantWallet::with('tenant')->withCount('transactions');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('tenant', function ($t) use ($search) {
                $t->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('tenant', function ($t) use ($request) {
                if ($request->status === 'expired') {
                    $t->whereNotNull('subscription_expires_at')->where('subscription_expires_at', '<', now());
                } else {
                    $t->where('status', $request->status);
                }
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 15), 100);
        $wallets = $query->orderByDesc('balance')->paginate($perPage)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        // Recent Wallet Transactions Audit
        $recentTransactions = TenantWalletTransaction::with(['tenant', 'wallet'])->latest()->limit(15)->get();
        $ledgerEntriesCount = TenantWalletTransaction::count();
        $currencyCode = Setting::whereNull('tenant_id')->where('key', 'currency_code')->value('value');
        $currencySymbol = Setting::whereNull('tenant_id')->where('key', 'currency_symbol')->value('value');

        return view('owner.wallets.index', compact(
            'wallets',
            'tenants',
            'recentTransactions',
            'totalWalletsCount',
            'totalBalance',
            'walletsWithBalanceCount'
            , 'currencyCode', 'currencySymbol', 'ledgerEntriesCount'
        ));
    }

    /**
     * Adjust a tenant wallet balance (Credit / Debit).
     */
    public function adjust(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $wallet = TenantWallet::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['balance' => 0, 'currency' => Setting::whereNull('tenant_id')->where('key', 'currency_code')->value('value')]
        );

        $amount = (float) $validated['amount'];
        $type = $validated['type'];

        if ($type === 'debit' && $wallet->balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance for debit adjustment.');
        }

        $newBalance = $type === 'credit' ? ($wallet->balance + $amount) : ($wallet->balance - $amount);
        $wallet->balance = $newBalance;
        $wallet->save();

        TenantWalletTransaction::create([
            'wallet_id' => $wallet->id,
            'tenant_id' => $tenant->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $validated['description'],
            'reference' => 'ADJ-' . strtoupper(Str::random(6)),
        ]);

        // Record Audit Log
        app(\App\Services\Audit\TenantAuditService::class)->log(
            $tenant,
            'wallet_adjusted',
            "Manual wallet {$type} of ৳" . number_format($amount, 2) . ". Description: {$validated['description']}",
            ['type' => $type, 'amount' => $amount, 'new_balance' => $newBalance]
        );

        return back()->with('success', "Wallet {$type} adjustment of ৳" . number_format($amount, 2) . " processed successfully for {$tenant->name}.");
    }
}
