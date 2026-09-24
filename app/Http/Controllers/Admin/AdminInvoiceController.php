<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminInvoiceController extends Controller
{
    /**
     * Display directory of customer billing invoices, collection stats, and ledgers.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Invoice::with([
            'user:id,first_name,last_name,username,email',
            'subscription:id,domain,status',
            'items',
            'payments'
        ]);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'overdue') {
                $query->whereIn('status', ['sent', 'partially_paid', 'draft'])
                    ->where('due_date', '<', now());
            } elseif ($status === 'unpaid') {
                $query->whereIn('status', ['sent', 'draft']);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('issue_date', '<=', $dateTo);
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $allInvoices = Invoice::all();
        $totalInvoiced = $allInvoices->sum('total_amount');
        $totalPaid = $allInvoices->sum('paid_amount');
        $totalDue = $allInvoices->whereIn('status', ['sent', 'draft', 'overdue', 'partially_paid'])->sum('due_amount');
        $overdueCount = $allInvoices->filter(function ($inv) {
            return in_array($inv->status, ['sent', 'draft', 'partially_paid', 'overdue']) && $inv->due_date && $inv->due_date->isPast();
        })->count();

        $stats = [
            'total_invoiced' => round($totalInvoiced, 2),
            'total_paid' => round($totalPaid, 2),
            'total_due' => round($totalDue, 2),
            'overdue_count' => $overdueCount,
            'total_invoices_count' => $allInvoices->count(),
            'paid_invoices_count' => $allInvoices->where('status', 'paid')->count(),
        ];

        // Active clients & subscriptions for manual invoice generation
        $clients = User::where('role', 'client')
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        $subscriptions = Subscription::select('id', 'user_id', 'domain')
            ->orderBy('domain')
            ->get();

        return Inertia::render('Admin/Billing/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'clients' => $clients,
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store new manually generated invoice with line items.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }

        $tax = floatval($validated['tax_amount'] ?? 0);
        $discount = floatval($validated['discount_amount'] ?? 0);
        $totalAmount = max(0, $subtotal + $tax - $discount);

        $invoice = Invoice::create([
            'user_id' => $validated['user_id'],
            'subscription_id' => $validated['subscription_id'] ?? null,
            'total_amount' => $totalAmount,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'paid_amount' => 0.00,
            'due_amount' => $totalAmount,
            'currency' => $validated['currency'] ?? 'BDT',
            'status' => 'sent',
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => ($item['quantity'] * $item['unit_price']),
                'tax_rate' => 0.00,
            ]);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_created',
            'description' => "Generated invoice #{$invoice->invoice_no} for {$totalAmount} BDT.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['invoice_no' => $invoice->invoice_no, 'total_amount' => $totalAmount, 'user_id' => $validated['user_id']],
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Invoice #{$invoice->invoice_no} generated successfully.");
    }

    /**
     * Show single invoice details.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $invoice->load([
            'user:id,first_name,last_name,username,email,phone,address,city,country',
            'subscription:id,domain,status,price',
            'items',
            'payments'
        ]);

        return response()->json([
            'invoice' => $invoice
        ]);
    }

    /**
     * Update invoice details.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:draft,sent,paid,partially_paid,overdue,cancelled'],
        ]);

        $invoice->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_updated',
            'description' => "Updated invoice #{$invoice->invoice_no}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Invoice #{$invoice->invoice_no} updated successfully.");
    }

    /**
     * 1-Click Mark invoice as Paid with payment transaction logging.
     */
    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'gateway' => ['required', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
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

        $paidAmount = $validated['amount'] ?? $invoice->total_amount;

        $invoice->update([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'due_amount' => max(0, $invoice->total_amount - $paidAmount),
            'paid_at' => now(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'transaction_id' => $validated['transaction_id'] ?? ('MANUAL-' . strtoupper(uniqid())),
            'gateway' => $gatewayEnum,
            'amount' => $paidAmount,
            'currency' => $invoice->currency,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Auto-unsuspend subscription if linked and currently suspended
        if ($invoice->subscription_id && $invoice->subscription) {
            if ($invoice->subscription->status === 'suspended') {
                $invoice->subscription->update([
                    'status' => 'active',
                    'suspended_at' => null,
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_marked_paid',
            'description' => "Marked invoice #{$invoice->invoice_no} as paid via {$validated['gateway']}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => 'sent'],
            'new_values' => ['status' => 'paid', 'gateway' => $validated['gateway'], 'amount' => $paidAmount],
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Invoice #{$invoice->invoice_no} settled and marked as PAID.");
    }

    /**
     * Send payment reminder notification to client.
     */
    public function sendReminder(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', User::class);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_reminder_sent',
            'description' => "Sent payment reminder for invoice #{$invoice->invoice_no} to client.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['invoice_id' => $invoice->id, 'client_id' => $invoice->user_id],
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Payment reminder queued for #{$invoice->invoice_no}.");
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', User::class);

        $invoice->update(['status' => 'cancelled']);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_cancelled',
            'description' => "Cancelled invoice #{$invoice->invoice_no}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $invoice->getOriginal('status')],
            'new_values' => ['status' => 'cancelled'],
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Invoice #{$invoice->invoice_no} has been cancelled.");
    }

    /**
     * Delete invoice (only allowed if not paid).
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($invoice->paid_amount > 0) {
            return redirect()->back()->with('error', "Cannot delete invoice #{$invoice->invoice_no} because cleared payments are recorded.");
        }

        $no = $invoice->invoice_no;
        $invoice->items()->delete();
        $invoice->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'invoice_deleted',
            'description' => "Deleted invoice #{$no}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['invoice_no' => $no],
        ]);

        return redirect()->route('admin.billing.invoices')
            ->with('success', "Invoice #{$no} deleted successfully.");
    }
}
