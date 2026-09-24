<?php

namespace App\Services\Billing;

use App\Models\SaasInvoice;
use App\Models\SubscriptionInvoiceItem;
use App\Models\TenantSubscription;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Automatically generate an invoice for a subscription cycle.
     */
    public function generateSubscriptionInvoice(TenantSubscription $subscription, ?Carbon $dueDate = null): SaasInvoice
    {
        return DB::transaction(function () use ($subscription, $dueDate) {
            $plan = $subscription->plan;
            $subtotal = (float)($plan ? $plan->monthly_price : 0);
            $tax = 0.00;
            $discount = 0.00;
            $totalAmount = $subtotal + $tax - $discount;

            $prefix = Setting::get('invoice_prefix', 'INV-');
            $invoiceNo = $prefix . date('Y') . '-' . strtoupper(Str::random(4)) . rand(100, 999);
            $due = $dueDate ?: ($subscription->next_billing_date ? Carbon::parse($subscription->next_billing_date) : now());

            $invoice = SaasInvoice::create([
                'tenant_id' => $subscription->tenant_id,
                'tenant_subscription_id' => $subscription->id,
                'saas_plan_id' => $plan ? $plan->id : null,
                'invoice_no' => $invoiceNo,
                'amount' => $totalAmount,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'paid_amount' => 0.00,
                'credit_amount' => 0.00,
                'due_amount' => $totalAmount,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
                'status' => 'unpaid',
                'due_date' => $due->format('Y-m-d'),
                'notes' => "Recurring Subscription for {$plan->name} ({$subscription->billing_cycle})",
            ]);

            // Create Itemized line item
            SubscriptionInvoiceItem::create([
                'saas_invoice_id' => $invoice->id,
                'description' => "SaaS Software Subscription - {$plan->name} (Max {$plan->customer_limit} Customers)",
                'quantity' => 1,
                'unit_price' => $subtotal,
                'total_price' => $subtotal,
            ]);

            return $invoice;
        });
    }

    /**
     * Record payment and settle invoice dues accurately.
     */
    public function recordPayment(SaasInvoice $invoice, float $amount, string $paymentMethod, string $trxId, ?array $gatewayResponse = null, ?string $notes = null): SaasInvoice
    {
        return DB::transaction(function () use ($invoice, $amount, $paymentMethod, $trxId, $gatewayResponse, $notes) {
            $lockedInvoice = SaasInvoice::where('id', $invoice->id)->lockForUpdate()->first() ?: $invoice;

            $newPaidAmount = (float)$lockedInvoice->paid_amount + $amount;
            $totalAmount = (float)$lockedInvoice->amount;
            $creditAmount = (float)$lockedInvoice->credit_amount;

            // Strict Due Amount Formula:
            // due_amount = total_amount - paid_amount - credit_amount
            $remainingDue = max(0, $totalAmount - $newPaidAmount - $creditAmount);

            $isFullyPaid = ($remainingDue <= 0);
            $newStatus = $isFullyPaid ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'unpaid');

            $lockedInvoice->update([
                'paid_amount' => $newPaidAmount,
                'due_amount' => $remainingDue,
                'status' => $newStatus,
                'payment_method' => $paymentMethod,
                'trx_id' => $trxId,
                'paid_at' => $isFullyPaid ? now() : $lockedInvoice->paid_at,
                'notes' => $notes ?: $lockedInvoice->notes,
            ]);

            // If subscription exists and invoice is now fully settled, renew subscription!
            if ($isFullyPaid && $lockedInvoice->subscription) {
                app(RenewalService::class)->renewSubscription($lockedInvoice->subscription, $lockedInvoice);
            }

            return $lockedInvoice;
        });
    }
}
