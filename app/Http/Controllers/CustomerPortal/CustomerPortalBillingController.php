<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\CustomerPaymentIntent;
use App\Models\Tenant;
use App\Models\TenantCustomerInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerPortalBillingController extends Controller
{
    public function invoices(Request $request, Tenant $tenant)
    {
        $account = Auth::guard('customer')->user();
        $invoices = TenantCustomerInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $account->customer_id)
            ->latest('issue_date')->latest('id')
            ->paginate(10)->withQueryString();

        return view('customer-portal.billing.invoices', compact('tenant', 'invoices'));
    }

    public function showInvoice(Tenant $tenant, TenantCustomerInvoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $invoice->customer_id === (int) Auth::guard('customer')->user()->customer_id, 404);

        return view('customer-portal.billing.show', compact('tenant', 'invoice'));
    }

    public function createPaymentIntent(Request $request, Tenant $tenant, TenantCustomerInvoice $invoice)
    {
        $account = Auth::guard('customer')->user();
        abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $invoice->customer_id === (int) $account->customer_id, 404);

        $data = $request->validate([
            'gateway' => ['required', Rule::in(['bkash', 'nagad', 'sslcommerz', 'aamarpay', 'stripe'])],
        ]);
        $key = trim((string) $request->header('Idempotency-Key'));
        abort_if($key === '' || strlen($key) > 255, 422, 'A valid Idempotency-Key header is required.');

        $intent = DB::transaction(function () use ($tenant, $invoice, $account, $data, $key) {
            $lockedInvoice = TenantCustomerInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedInvoice->tenant_id === (int) $tenant->id && (int) $lockedInvoice->customer_id === (int) $account->customer_id, 404);
            abort_if(in_array($lockedInvoice->status, ['paid', 'cancelled'], true) || (float) $lockedInvoice->due_amount <= 0, 422, 'This invoice is not payable.');

            $existing = CustomerPaymentIntent::where('tenant_id', $tenant->id)->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                abort_if((int) $existing->customer_id !== (int) $account->customer_id || (int) $existing->invoice_id !== (int) $lockedInvoice->id, 409, 'Idempotency key belongs to another payment request.');
                return $existing;
            }

            return CustomerPaymentIntent::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $account->customer_id,
                'invoice_id' => $lockedInvoice->id,
                'idempotency_key' => $key,
                'amount' => $lockedInvoice->due_amount,
                'currency' => $tenant->currency_code ?? 'BDT',
                'gateway' => $data['gateway'],
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'success' => true,
            'status' => $intent->status,
            'intent_id' => $intent->id,
            'amount' => $intent->amount,
            'currency' => $intent->currency,
            'message' => 'Payment intent created. Gateway checkout will be connected in the payment adapter phase.',
        ], 201);
    }
}
