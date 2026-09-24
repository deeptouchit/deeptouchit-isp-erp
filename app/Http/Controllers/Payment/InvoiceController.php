<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display client invoices with financial summary, live search, and filters.
     */
    public function index(Request $request): Response
    {
        $userId = auth()->id();

        // 1. Financial summary metrics
        $totalDue = (float) Invoice::where('user_id', $userId)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->sum('total_amount');

        $totalPaid = (float) Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $unpaidCount = Invoice::where('user_id', $userId)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->count();

        $paidCount = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->count();

        $totalCount = Invoice::where('user_id', $userId)->count();

        // 2. Query with search and status filter
        $query = Invoice::where('user_id', $userId)
            ->with(['subscription.plan', 'items']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('subscription', function ($subQ) use ($search) {
                      $subQ->where('domain', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        // 3. Active payment channels for quick pay
        $activeGateways = PaymentGateway::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name', 'category', 'instructions']);

        return Inertia::render('Client/Billing/Invoices', [
            'invoices' => $invoices,
            'stats' => [
                'total_due' => $totalDue,
                'total_paid' => $totalPaid,
                'unpaid_count' => $unpaidCount,
                'paid_count' => $paidCount,
                'total_count' => $totalCount,
            ],
            'filters' => [
                'status' => $request->query('status', 'all'),
                'search' => $request->query('search', ''),
            ],
            'activeGateways' => $activeGateways,
        ]);
    }

    /**
     * Display detailed printable invoice with payment options.
     */
    public function show(Invoice $invoice): Response
    {
        if (auth()->user()->role !== 'admin' && $invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load(['user', 'items', 'payments', 'subscription.plan']);

        $activeGateways = PaymentGateway::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name', 'category', 'instructions']);

        return Inertia::render('Client/Billing/Show', [
            'invoice' => $invoice,
            'activeGateways' => $activeGateways,
        ]);
    }
}

