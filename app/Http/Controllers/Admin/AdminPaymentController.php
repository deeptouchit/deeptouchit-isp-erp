<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPaymentController extends Controller
{
    /**
     * Display directory of gateway payment clearances, transaction records, and reconciliations.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Payment::with([
            'user:id,first_name,last_name,username,email',
            'invoice:id,invoice_no,total_amount,due_amount,status'
        ]);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
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

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Gateway Filter
        if ($gateway = $request->input('gateway')) {
            if ($gateway !== 'all') {
                $query->where('gateway', strtolower($gateway));
            }
        }

        // Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->where('paid_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('paid_at', '<=', $dateTo . ' 23:59:59');
        }

        $payments = $query->latest('id')->paginate(15)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $allPayments = Payment::all();
        $completedPayments = $allPayments->where('status', 'completed');
        $totalSettled = $completedPayments->sum('amount');
        
        $mfsVolume = $completedPayments->whereIn('gateway', ['bkash', 'nagad'])->sum('amount');
        $cardVolume = $completedPayments->whereIn('gateway', ['sslcommerz', 'stripe', 'paypal', 'bank'])->sum('amount');
        
        $refundedPayments = $allPayments->where('status', 'refunded');
        $refundedVolume = $refundedPayments->sum('amount');

        $stats = [
            'total_settled_volume' => round($totalSettled, 2),
            'completed_count' => $completedPayments->count(),
            'mfs_volume' => round($mfsVolume, 2),
            'mfs_count' => $completedPayments->whereIn('gateway', ['bkash', 'nagad'])->count(),
            'card_volume' => round($cardVolume, 2),
            'card_count' => $completedPayments->whereIn('gateway', ['sslcommerz', 'stripe', 'paypal', 'bank'])->count(),
            'refunded_volume' => round($refundedVolume, 2),
            'refunded_count' => $refundedPayments->count(),
            'total_transactions_count' => $allPayments->count(),
        ];

        // Gateway Volume Distribution
        $gatewayStats = [
            'bkash' => round($completedPayments->where('gateway', 'bkash')->sum('amount'), 2),
            'nagad' => round($completedPayments->where('gateway', 'nagad')->sum('amount'), 2),
            'sslcommerz' => round($completedPayments->where('gateway', 'sslcommerz')->sum('amount'), 2),
            'stripe' => round($completedPayments->where('gateway', 'stripe')->sum('amount'), 2),
            'bank' => round($completedPayments->where('gateway', 'bank')->sum('amount'), 2),
            'paypal' => round($completedPayments->where('gateway', 'paypal')->sum('amount'), 2),
        ];

        // Active clients & pending invoices for manual payment recording
        $clients = User::where('role', 'client')
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'draft', 'partially_paid', 'overdue'])
            ->select('id', 'user_id', 'invoice_no', 'total_amount', 'due_amount', 'currency')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Billing/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'gatewayStats' => $gatewayStats,
            'clients' => $clients,
            'unpaidInvoices' => $unpaidInvoices,
            'filters' => $request->only(['search', 'status', 'gateway', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Record a manual payment clearance.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'gateway' => ['required', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $gwMap = [
            'bkash' => 'bkash',
            'nagad' => 'nagad',
            'rocket' => 'bkash',
            'sslcommerz' => 'sslcommerz',
            'stripe' => 'stripe',
            'paypal' => 'paypal',
            'bank' => 'bank',
            'bank transfer' => 'bank',
            'cash / manual' => 'bank',
        ];
        $gwKey = strtolower(trim($validated['gateway']));
        $gatewayEnum = $gwMap[$gwKey] ?? 'bank';

        $trxId = !empty($validated['transaction_id']) 
            ? trim($validated['transaction_id']) 
            : ('TXN-' . strtoupper(uniqid()));

        // Ensure unique TrxID
        while (Payment::where('transaction_id', $trxId)->exists()) {
            $trxId = 'TXN-' . strtoupper(uniqid());
        }

        $payment = Payment::create([
            'user_id' => $validated['user_id'],
            'invoice_id' => $validated['invoice_id'] ?? null,
            'transaction_id' => $trxId,
            'gateway' => $gatewayEnum,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'BDT',
            'status' => 'completed',
            'gateway_data' => $validated['notes'] ? ['admin_notes' => $validated['notes']] : null,
            'paid_at' => $validated['paid_at'] ?? now(),
        ]);

        // Settle linked invoice if exists
        if (!empty($validated['invoice_id'])) {
            $invoice = Invoice::find($validated['invoice_id']);
            if ($invoice) {
                $newPaid = $invoice->paid_amount + $validated['amount'];
                $newDue = max(0, $invoice->total_amount - $newPaid);
                $newStatus = $newDue <= 0 ? 'paid' : 'partially_paid';

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'due_amount' => $newDue,
                    'status' => $newStatus,
                    'paid_at' => $newStatus === 'paid' ? now() : $invoice->paid_at,
                ]);

                // Auto-unsuspend subscription if fully paid
                if ($newStatus === 'paid' && $invoice->subscription_id && $invoice->subscription) {
                    if ($invoice->subscription->status === 'suspended') {
                        $invoice->subscription->update([
                            'status' => 'active',
                            'suspended_at' => null,
                        ]);
                    }
                }
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'payment_recorded',
            'description' => "Recorded payment clearance {$trxId} for {$validated['amount']} BDT via {$gatewayEnum}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['transaction_id' => $trxId, 'amount' => $validated['amount'], 'gateway' => $gatewayEnum],
        ]);

        return redirect()->route('admin.billing.payments')
            ->with('success', "Payment {$trxId} of ৳{$validated['amount']} recorded successfully.");
    }

    /**
     * Show single payment transaction details and receipt.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $payment->load([
            'user:id,first_name,last_name,username,email,phone,address,city,country',
            'invoice:id,invoice_no,total_amount,paid_amount,due_amount,status,issue_date,due_date'
        ]);

        return response()->json([
            'payment' => $payment
        ]);
    }

    /**
     * Issue refund on a payment.
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('create', User::class);

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        // Adjust attached invoice
        if ($payment->invoice_id && $payment->invoice) {
            $invoice = $payment->invoice;
            $newPaid = max(0, $invoice->paid_amount - $payment->amount);
            $newDue = max(0, $invoice->total_amount - $newPaid);
            
            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => $newPaid <= 0 ? 'sent' : 'partially_paid',
            ]);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'payment_refunded',
            'description' => "Refunded payment {$payment->transaction_id} of {$payment->amount} BDT.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => 'completed'],
            'new_values' => ['status' => 'refunded', 'refunded_at' => now()->toDateTimeString()],
        ]);

        return redirect()->route('admin.billing.payments')
            ->with('success', "Payment {$payment->transaction_id} refunded successfully.");
    }

    /**
     * Delete payment transaction (only allowed if failed or pending).
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($payment->status === 'completed') {
            return redirect()->back()->with('error', "Cannot delete completed payment {$payment->transaction_id}. Issue a refund instead.");
        }

        $trx = $payment->transaction_id;
        $payment->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'payment_deleted',
            'description' => "Deleted payment transaction {$trx}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['transaction_id' => $trx],
        ]);

        return redirect()->route('admin.billing.payments')
            ->with('success', "Payment record {$trx} deleted successfully.");
    }
}
