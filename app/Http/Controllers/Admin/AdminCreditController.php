<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClientCredit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminCreditController extends Controller
{
    /**
     * Display client account credit balances, wallet ledger, and fund movements.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ClientCredit::with([
            'user:id,first_name,last_name,username,email,credit_balance',
            'admin:id,first_name,last_name,username,email',
            'invoice:id,invoice_no,total_amount,paid_amount,due_amount,status'
        ]);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
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

        // Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $credits = $query->latest('id')->paginate(15)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $allClients = User::where('role', 'client')->get();
        $totalWalletPool = $allClients->sum('credit_balance');
        $fundedClientsCount = $allClients->where('credit_balance', '>', 0)->count();

        $allCreditLogs = ClientCredit::all();
        $totalAdded = $allCreditLogs->whereIn('type', ['add', 'refund'])->sum('amount');
        $totalDeducted = $allCreditLogs->whereIn('type', ['deduct', 'auto_apply'])->sum('amount');

        $stats = [
            'total_wallet_pool' => round($totalWalletPool, 2),
            'funded_clients_count' => $fundedClientsCount,
            'total_clients_count' => $allClients->count(),
            'total_added' => round($totalAdded, 2),
            'added_count' => $allCreditLogs->whereIn('type', ['add', 'refund'])->count(),
            'total_deducted' => round($totalDeducted, 2),
            'deducted_count' => $allCreditLogs->whereIn('type', ['deduct', 'auto_apply'])->count(),
        ];

        // Active clients with their balances for the credit manager modal
        $clients = User::where('role', 'client')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'credit_balance')
            ->orderBy('first_name')
            ->get();

        // Unpaid or sent invoices for applying credits
        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'partially_paid', 'overdue', 'draft'])
            ->select('id', 'user_id', 'invoice_no', 'total_amount', 'due_amount', 'status')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Billing/Credits/Index', [
            'credits' => $credits,
            'stats' => $stats,
            'clients' => $clients,
            'unpaidInvoices' => $unpaidInvoices,
            'filters' => $request->only(['search', 'type', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store new account credit adjustment (Add, Deduct, or Settle Invoice).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:add,deduct,auto_apply,refund'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:255'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = (float) $validated['amount'];
        $type = $validated['type'];

        // If deducting, check if user has enough balance
        if (in_array($type, ['deduct', 'auto_apply']) && (float) $user->credit_balance < $amount) {
            return back()->with('error', "Insufficient credit balance. User only has ৳" . number_format($user->credit_balance, 2));
        }

        DB::transaction(function () use ($user, $amount, $type, $validated) {
            $balanceBefore = (float) $user->credit_balance;

            if (in_array($type, ['add', 'refund'])) {
                $balanceAfter = $balanceBefore + $amount;
            } else {
                $balanceAfter = $balanceBefore - $amount;
            }

            $user->update(['credit_balance' => $balanceAfter]);

            $creditLog = ClientCredit::create([
                'user_id' => $user->id,
                'admin_id' => auth()->id() ?: 1,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $validated['currency'] ?? 'BDT',
                'description' => $validated['description'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // If an invoice is linked, apply credit towards the invoice
            if (!empty($validated['invoice_id'])) {
                $invoice = Invoice::find($validated['invoice_id']);
                if ($invoice) {
                    $paidAmount = (float) $invoice->paid_amount + $amount;
                    $dueAmount = max(0, (float) $invoice->total_amount - $paidAmount);
                    $newStatus = $dueAmount <= 0 ? 'paid' : 'partially_paid';

                    $invoice->update([
                        'paid_amount' => $paidAmount,
                        'due_amount' => $dueAmount,
                        'status' => $newStatus,
                        'paid_at' => $newStatus === 'paid' ? now() : $invoice->paid_at,
                    ]);

                    // If invoice is fully paid and subscription was suspended, auto-unsuspend
                    if ($newStatus === 'paid' && $invoice->subscription_id) {
                        $sub = $invoice->subscription;
                        if ($sub && $sub->status === 'suspended') {
                            $sub->update([
                                'status' => 'active',
                                'suspended_at' => null,
                            ]);
                        }
                    }
                }
            }

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'client_credit_adjusted',
                'description' => "Adjusted credit for client {$user->name}: {$type} ৳{$amount} (New Balance: ৳{$balanceAfter}).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['credit_balance' => $balanceBefore],
                'new_values' => ['credit_balance' => $balanceAfter, 'type' => $type, 'amount' => $amount],
            ]);
        });

        return redirect()->route('admin.billing.credits')
            ->with('success', "Credit adjustment of ৳{$amount} applied for {$user->name}.");
    }

    /**
     * Show single credit transaction details.
     */
    public function show(ClientCredit $credit): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $credit->load([
            'user:id,first_name,last_name,username,email,phone,credit_balance',
            'admin:id,first_name,last_name,username,email',
            'invoice:id,invoice_no,total_amount,paid_amount,due_amount,status'
        ]);

        return response()->json([
            'credit' => $credit
        ]);
    }

    /**
     * Delete/revert credit log entry.
     */
    public function destroy(ClientCredit $credit): RedirectResponse
    {
        $this->authorize('create', User::class);

        DB::transaction(function () use ($credit) {
            $user = $credit->user;
            if ($user) {
                // Revert balance
                if (in_array($credit->type, ['add', 'refund'])) {
                    $user->decrement('credit_balance', $credit->amount);
                } else {
                    $user->increment('credit_balance', $credit->amount);
                }
            }

            $credit->delete();

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'client_credit_deleted',
                'description' => "Reverted credit log #{$credit->id} for user {$user?->name}.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['credit_id' => $credit->id, 'amount' => $credit->amount],
                'new_values' => [],
            ]);
        });

        return redirect()->route('admin.billing.credits')
            ->with('success', "Credit transaction reverted and balance restored.");
    }
}
