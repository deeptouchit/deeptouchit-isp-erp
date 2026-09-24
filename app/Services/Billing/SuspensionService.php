<?php

namespace App\Services\Billing;

use App\Models\Setting;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SuspensionService
{
    /**
     * Inspect all active subscriptions and enforce grace period / suspension lifecycle.
     */
    public function processSuspensions(): array
    {
        $today = Carbon::today();
        $graceDays = (int) (Setting::whereNull('tenant_id')->where('key', 'grace_period_days')->value('value') ?? 3);

        $results = [
            'grace_period_applied' => 0,
            'suspended' => 0,
        ];

        // 1. Check active subscriptions that have passed current_period_end
        $overdueSubscriptions = TenantSubscription::whereIn('status', ['active', 'past_due'])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $today->toDateString())
            ->get();

        foreach ($overdueSubscriptions as $sub) {
            // Check if there is an unpaid invoice
            $hasUnpaidInvoice = $sub->invoices()->where('status', '!=', 'paid')->exists();
            if ($hasUnpaidInvoice) {
                app(SubscriptionService::class)->applyGracePeriod($sub, $graceDays);
                $results['grace_period_applied']++;
            }
        }

        // 2. Check subscriptions in grace_period that have passed grace_period_ends_at
        $expiredGraceSubscriptions = TenantSubscription::where('status', 'grace_period')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->get();

        foreach ($expiredGraceSubscriptions as $sub) {
            $hasUnpaidInvoice = $sub->invoices()->where('status', '!=', 'paid')->exists();
            if ($hasUnpaidInvoice) {
                app(SubscriptionService::class)->suspendSubscription($sub, 'Grace period expired without invoice settlement.');
                $results['suspended']++;
            }
        }

        return $results;
    }
}
