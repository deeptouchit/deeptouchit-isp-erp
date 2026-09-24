<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerTransactionController extends Controller
{
    /**
     * Display gateway payment transactions and handshake audit ledger.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        // 1. Transaction Stats
        $totalTransactions = PaymentTransaction::count();
        $successfulCount = PaymentTransaction::where('status', 'successful')->count();
        $pendingCount = PaymentTransaction::where('status', 'pending')->count();
        $failedCount = PaymentTransaction::where('status', 'failed')->count();
        $successfulVolume = (float) PaymentTransaction::where('status', 'successful')->sum('amount');

        // 2. Query Builder
        $query = PaymentTransaction::with(['tenant', 'invoice']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        return view('owner.transactions.index', compact(
            'transactions',
            'tenants',
            'totalTransactions',
            'successfulCount',
            'pendingCount',
            'failedCount',
            'successfulVolume'
        ));
    }
}
