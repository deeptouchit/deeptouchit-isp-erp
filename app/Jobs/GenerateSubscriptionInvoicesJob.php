<?php

namespace App\Jobs;

use App\Models\TenantSubscription;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSubscriptionInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceService $invoiceService): void
    {
        $today = Carbon::today();
        
        // Find subscriptions due for billing (e.g. within next 3 days or today)
        $subscriptions = TenantSubscription::where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->get();

        foreach ($subscriptions as $subscription) {
            // Check if unhandled invoice for current period already generated
            $hasInvoice = $subscription->invoices()
                ->where('period_start', $subscription->current_period_start)
                ->exists();

            if (!$hasInvoice) {
                $invoice = $invoiceService->generateSubscriptionInvoice($subscription);
                Log::info("Generated recurring invoice #{$invoice->invoice_no} for Tenant #{$subscription->tenant_id}.");
            }
        }
    }
}
