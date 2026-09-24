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

class ProcessAutoRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceService $invoiceService): void
    {
        $today = Carbon::today();

        // Find active auto-renew subscriptions reaching next billing date
        $subscriptions = TenantSubscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', $today->toDateString())
            ->get();

        foreach ($subscriptions as $subscription) {
            $latestInvoice = $subscription->invoices()->latest()->first();
            if ($latestInvoice && $latestInvoice->isPaid()) {
                app(\App\Services\Billing\RenewalService::class)->renewSubscription($subscription, $latestInvoice);
                Log::info("Auto-renewed subscription for Tenant #{$subscription->tenant_id}.");
            }
        }
    }
}
