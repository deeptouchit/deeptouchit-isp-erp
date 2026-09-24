<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use App\Services\Payment\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantBillingController extends Controller
{
    protected function currentTenant(): ?Tenant
    {
        $user = Auth::user();
        return $user ? $user->tenant : null;
    }

    /**
     * Tenant Billing & Subscription Overview Dashboard.
     */
    public function dashboard()
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return redirect('/login');
        }

        $subscription = $tenant->activeSubscription ?: $tenant->subscriptions()->latest()->first();
        $plan = $tenant->plan;

        // Current pending invoice
        $currentInvoice = $tenant->invoices()->where('status', '!=', 'paid')->latest()->first();

        // Financial totals
        $totalInvoiced = (float) $tenant->invoices()->sum('amount');
        $totalPaid = (float) $tenant->invoices()->where('status', 'paid')->sum('amount');
        $totalOutstanding = (float) $tenant->invoices()->where('status', '!=', 'paid')->sum('amount');

        // ISP Tenant Resource Usage Counts
        $customersCount = class_exists('\App\Models\TenantCustomer') ? \App\Models\TenantCustomer::where('tenant_id', $tenant->id)->count() : 0;
        $activeCustomersCount = class_exists('\App\Models\TenantCustomer') ? \App\Models\TenantCustomer::where('tenant_id', $tenant->id)->where('status', 'active')->count() : 0;
        $resellersCount = class_exists('\App\Models\TenantReseller') ? \App\Models\TenantReseller::where('tenant_id', $tenant->id)->count() : 0;
        $staffCount = \App\Models\User::where('tenant_id', $tenant->id)->count();
        $mikrotikCount = class_exists('\App\Models\MikrotikRouter') ? \App\Models\MikrotikRouter::where('tenant_id', $tenant->id)->count() : 0;
        $oltCount = class_exists('\App\Models\OltDevice') ? \App\Models\OltDevice::where('tenant_id', $tenant->id)->count() : 0;
        $packagesCount = class_exists('\App\Models\TenantInternetPackage') ? \App\Models\TenantInternetPackage::where('tenant_id', $tenant->id)->count() : 0;

        return view('tenant.billing.dashboard', compact(
            'tenant',
            'subscription',
            'plan',
            'currentInvoice',
            'totalInvoiced',
            'totalPaid',
            'totalOutstanding',
            'customersCount',
            'activeCustomersCount',
            'resellersCount',
            'staffCount',
            'mikrotikCount',
            'oltCount',
            'packagesCount'
        ));
    }

    /**
     * Tenant Invoices Listing.
     */
    public function invoices(Request $request)
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return redirect('/login');
        }

        $search = $request->input('search');
        $status = $request->input('status');

        $query = $tenant->invoices()->with(['plan', 'payments']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('plan', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        // 6 KPI Summary Metrics
        $baseQuery = $tenant->invoices();
        $totalInvoicesCount = (clone $baseQuery)->count();
        $paidInvoicesCount = (clone $baseQuery)->where('status', 'paid')->count();
        $unpaidInvoicesCount = (clone $baseQuery)->whereIn('status', ['unpaid', 'pending'])->count();
        $totalInvoicedAmount = (float) (clone $baseQuery)->sum('amount');
        $totalPaidAmount = (float) (clone $baseQuery)->sum('paid_amount');
        $totalDueAmount = (float) (clone $baseQuery)->where('status', '!=', 'paid')->sum('amount') - $totalPaidAmount;
        if ($totalDueAmount < 0) {
            $totalDueAmount = 0;
        }

        // Current outstanding due invoice if any
        $latestDueInvoice = $tenant->invoices()->where('status', '!=', 'paid')->latest()->first();

        return view('tenant.billing.invoices', compact(
            'tenant',
            'invoices',
            'totalInvoicesCount',
            'paidInvoicesCount',
            'unpaidInvoicesCount',
            'totalInvoicedAmount',
            'totalPaidAmount',
            'totalDueAmount',
            'latestDueInvoice',
            'search',
            'status'
        ));
    }

    /**
     * Tenant Settlement & Payment Transactions Listing.
     */
    public function payments(Request $request)
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return redirect('/login');
        }

        $search = $request->input('search');
        $gateway = $request->input('gateway');

        $query = $tenant->payments()->with('invoice');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('invoice', function ($iq) use ($search) {
                      $iq->where('invoice_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($gateway) {
            $query->where('payment_method', $gateway);
        }

        $payments = $query->latest('id')->paginate(15)->withQueryString();

        // 6 KPI Financial Summary Metrics
        $baseQuery = $tenant->payments();
        $totalTransactionsCount = (clone $baseQuery)->count();
        $totalPaidAmount = (float) (clone $baseQuery)->sum('amount');
        $bkashCount = (clone $baseQuery)->where('payment_method', 'bkash')->count();
        $nagadCount = (clone $baseQuery)->where('payment_method', 'nagad')->count();
        $onlineCount = (clone $baseQuery)->whereIn('payment_method', ['bkash', 'nagad', 'sslcommerz', 'aamarpay', 'shurjopay'])->count();
        $manualCount = (clone $baseQuery)->whereNotIn('payment_method', ['bkash', 'nagad', 'sslcommerz', 'aamarpay', 'shurjopay'])->count();

        return view('tenant.billing.payments', compact(
            'tenant',
            'payments',
            'totalTransactionsCount',
            'totalPaidAmount',
            'bkashCount',
            'nagadCount',
            'onlineCount',
            'manualCount',
            'search',
            'gateway'
        ));
    }

    /**
     * Show Single Invoice & Checkout Options.
     */
    public function showInvoice(SaasInvoice $invoice)
    {
        $tenant = $this->currentTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403);
        }

        $invoice->load(['items', 'plan', 'payments']);
        return view('tenant.billing.invoice_show', compact('tenant', 'invoice'));
    }

    /**
     * Initiate Payment Gateway Checkout.
     */
    public function initiatePayment(Request $request, SaasInvoice $invoice)
    {
        $tenant = $this->currentTenant();
        if ($invoice->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            'gateway' => ['required', 'in:bkash,nagad,bank,wallet'],
            'amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        $gateway = $request->gateway;
        $amount = (float)($request->amount ?: $invoice->due_amount ?: $invoice->amount);

        // Disallow overpayment beyond due
        $due = (float)($invoice->due_amount ?: $invoice->amount);
        $amount = min($amount, $due);

        $callbackUrl = route('tenant.billing.payment.callback');

        try {
            $response = app(PaymentGatewayManager::class)->initiate($invoice, $gateway, $amount, $callbackUrl);
            if (!empty($response['success']) && !empty($response['redirect_url'])) {
                return redirect($response['redirect_url']);
            }
            return back()->with('error', $response['message'] ?? 'Failed to initiate payment gateway session.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Payment Gateway Callback / Return URL.
     */
    public function paymentCallback(Request $request)
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return redirect('/login');
        }

        $paymentRef = $request->get('paymentID') ?? $request->get('payment_ref');
        $status = $request->get('status');

        if (!$paymentRef || $status !== 'success') {
            return redirect()->route('tenant.billing.dashboard')->with('error', 'Payment was cancelled or failed.');
        }

        // Strictly verify transaction belongs to the currently authenticated tenant
        $transaction = PaymentTransaction::where('tenant_id', $tenant->id)
            ->where('transaction_reference', $paymentRef)
            ->first();

        if (!$transaction) {
            return redirect()->route('tenant.billing.dashboard')->with('error', 'Invalid transaction reference or unauthorized tenant access.');
        }

        // Verify gateway execution with tenant context
        $gatewayService = app(PaymentGatewayManager::class)->getGateway($transaction->gateway);
        $verifyResult = $gatewayService->verifyPayment($paymentRef, null, $tenant->id);

        if ($verifyResult['success']) {
            $msg = !empty($verifyResult['already_processed'])
                ? "Payment was already processed. Trx ID: {$verifyResult['trx_id']}."
                : "Payment successful! Trx ID: {$verifyResult['trx_id']}. Your subscription is active.";
            return redirect()->route('tenant.billing.dashboard')->with('success', $msg);
        }

        return redirect()->route('tenant.billing.dashboard')->with('error', $verifyResult['message'] ?? 'Payment verification failed.');
    }

    /**
     * Tenant Service Suspension Landing Page.
     */
    public function suspended()
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return redirect('/login');
        }

        $outstandingInvoice = $tenant->invoices()->where('status', '!=', 'paid')->latest()->first();
        $totalDue = (float)$tenant->invoices()->where('status', '!=', 'paid')->sum('amount');

        return view('tenant.billing.suspended', compact('tenant', 'outstandingInvoice', 'totalDue'));
    }
}
