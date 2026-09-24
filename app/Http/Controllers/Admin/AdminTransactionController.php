<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTransactionController extends Controller
{
    /**
     * Display unified double-entry billing ledger and transaction audit records.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Transaction::with([
            'user:id,first_name,last_name,username,email',
            'invoice:id,invoice_no,total_amount,status',
            'payment:id,transaction_id,gateway,status'
        ]);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('gateway_reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_no', 'like', "%{$search}%");
                    });
            });
        }

        // Type Filter
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category Filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->where('transacted_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('transacted_at', '<=', $dateTo . ' 23:59:59');
        }

        $transactions = $query->latest('id')->paginate(15)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $allTxns = Transaction::all();
        $completedTxns = $allTxns->where('status', 'completed');
        
        $totalCredits = $completedTxns->whereIn('type', ['credit', 'adjustment'])->sum('amount');
        $totalDebits = $completedTxns->whereIn('type', ['debit', 'fee', 'refund'])->sum('amount');
        $netInflow = $totalCredits - $totalDebits;

        $adjustments = $allTxns->where('type', 'adjustment');
        $adjustmentVolume = $adjustments->sum('amount');

        $stats = [
            'net_inflow' => round($netInflow, 2),
            'total_credits' => round($totalCredits, 2),
            'credit_count' => $completedTxns->whereIn('type', ['credit', 'adjustment'])->count(),
            'total_debits' => round($totalDebits, 2),
            'debit_count' => $completedTxns->whereIn('type', ['debit', 'fee', 'refund'])->count(),
            'adjustment_volume' => round($adjustmentVolume, 2),
            'adjustment_count' => $adjustments->count(),
            'total_transactions_count' => $allTxns->count(),
        ];

        // Active clients & invoices for creating manual ledger entries
        $clients = User::where('role', 'client')
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        $invoices = Invoice::select('id', 'user_id', 'invoice_no', 'total_amount', 'due_amount')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Billing/Transactions/Index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'clients' => $clients,
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'type', 'category', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Record a manual journal ledger transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'type' => ['required', 'in:credit,debit,fee,refund,adjustment'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'gateway_reference' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:completed,pending,cancelled,failed,reversed'],
            'transacted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction = Transaction::create([
            'user_id' => $validated['user_id'],
            'invoice_id' => $validated['invoice_id'] ?? null,
            'type' => $validated['type'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'BDT',
            'payment_method' => $validated['payment_method'] ?? 'manual',
            'gateway_reference' => $validated['gateway_reference'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'transacted_at' => $validated['transacted_at'] ?? now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'transaction_created',
            'description' => "Recorded ledger transaction {$transaction->transaction_number} of {$validated['amount']} BDT ({$validated['type']}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['transaction_number' => $transaction->transaction_number, 'amount' => $validated['amount'], 'type' => $validated['type']],
        ]);

        return redirect()->route('admin.billing.transactions')
            ->with('success', "Transaction {$transaction->transaction_number} recorded in accounting ledger.");
    }

    /**
     * Show single transaction record JSON.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $transaction->load([
            'user:id,first_name,last_name,username,email,phone,address',
            'invoice:id,invoice_no,total_amount,paid_amount,due_amount,status',
            'payment:id,transaction_id,gateway,status'
        ]);

        return response()->json([
            'transaction' => $transaction
        ]);
    }

    /**
     * Update transaction notes or metadata.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'transaction_updated',
            'description' => "Updated ledger entry {$transaction->transaction_number}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.billing.transactions')
            ->with('success', "Transaction {$transaction->transaction_number} updated.");
    }

    /**
     * Delete transaction entry.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('create', User::class);

        $num = $transaction->transaction_number;
        $transaction->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'transaction_deleted',
            'description' => "Deleted ledger entry {$num}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['transaction_number' => $num],
        ]);

        return redirect()->route('admin.billing.transactions')
            ->with('success', "Transaction entry {$num} deleted from ledger.");
    }
}
