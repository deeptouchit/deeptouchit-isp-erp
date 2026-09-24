<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantWallet;
use App\Models\TenantWalletTransaction;
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

        $query = TenantWallet::with(['tenant', 'transactions' => fn($q) => $q->latest()->take(5)]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('tenant', function ($t) use ($search) {
                $t->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $wallets = $query->orderByDesc('balance')->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        // Recent Wallet Transactions Audit
        $recentTransactions = TenantWalletTransaction::with(['tenant', 'wallet'])->latest()->limit(15)->get();

        return view('owner.wallets.index', compact(
            'wallets',
            'tenants',
            'recentTransactions',
            'totalWalletsCount',
            'totalBalance',
            'walletsWithBalanceCount'
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
            ['balance' => 0, 'currency' => 'BDT']
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
