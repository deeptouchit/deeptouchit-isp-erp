<?php

namespace App\Services\Billing;

use App\Models\SaasInvoice;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewalService
{
    /**
     * Renew subscription upon successful invoice settlement.
     */
    public function renewSubscription(TenantSubscription $subscription, ?SaasInvoice $invoice = null): TenantSubscription
    {
        return DB::transaction(function () use ($subscription, $invoice) {
            $lockedSubscription = TenantSubscription::where('id', $subscription->id)->lockForUpdate()->first() ?: $subscription;

            $currentEnd = $lockedSubscription->current_period_end ? Carbon::parse($lockedSubscription->current_period_end) : now();
            
            // If subscription was already expired/past, start from today, else extend from current period end
            if ($currentEnd->isPast()) {
                $newStart = now();
            } else {
                $newStart = $currentEnd->copy()->addDay();
            }

            $durationMonths = 1;
            if ($subscription->billing_cycle === 'quarterly') {
                $durationMonths = 3;
            } elseif ($subscription->billing_cycle === 'yearly') {
                $durationMonths = 12;
            }

            $newEnd = $newStart->copy()->addMonths($durationMonths)->subDay();
            $nextBilling = $newEnd->copy()->addDay();

            $subscription->update([
                'current_period_start' => $newStart->format('Y-m-d'),
                'current_period_end' => $newEnd->format('Y-m-d'),
                'next_billing_date' => $nextBilling->format('Y-m-d'),
                'status' => 'active',
                'grace_period_ends_at' => null,
                'suspended_at' => null,
            ]);

            // Sync with Tenant root model
            $subscription->tenant->update([
                'subscription_expires_at' => $newEnd->format('Y-m-d'),
                'status' => 'active',
            ]);

            Log::info("Tenant #{$subscription->tenant_id} subscription renewed until {$newEnd->format('Y-m-d')}.");
            return $subscription;
        });
    }
}
