<?php

namespace App\Services\Billing;

use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Provision or create a subscription for a tenant.
     */
    public function createSubscription(Tenant $tenant, SaasPlan $plan, string $billingCycle = 'monthly', int $durationMonths = 1): TenantSubscription
    {
        return DB::transaction(function () use ($tenant, $plan, $billingCycle, $durationMonths) {
            $startDate = now();
            $periodEnd = $startDate->copy()->addMonths($durationMonths)->subDay();
            $nextBillingDate = $periodEnd->copy()->addDay();

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'started_at' => $startDate,
                'current_period_start' => $startDate->format('Y-m-d'),
                'current_period_end' => $periodEnd->format('Y-m-d'),
                'next_billing_date' => $nextBillingDate->format('Y-m-d'),
                'grace_period_ends_at' => null,
                'status' => 'active',
                'auto_renew' => true,
            ]);

            // Synchronize tenant top-level fields
            $tenant->update([
                'saas_plan_id' => $plan->id,
                'status' => 'active',
                'subscription_expires_at' => $periodEnd->format('Y-m-d'),
            ]);

            app(\App\Services\Audit\TenantAuditService::class)->log(
                $tenant,
                'subscription_created',
                "Created subscription for {$plan->name} ({$billingCycle}) valid until " . $periodEnd->format('d M, Y') . ".",
                ['plan' => $plan->name, 'billing_cycle' => $billingCycle, 'duration_months' => $durationMonths]
            );

            return $subscription;
        });
    }

    /**
     * Mark subscription in grace period.
     */
    public function applyGracePeriod(TenantSubscription $subscription, int $graceDays = 3): TenantSubscription
    {
        $graceEndsAt = now()->addDays($graceDays);

        $subscription->update([
            'status' => 'grace_period',
            'grace_period_ends_at' => $graceEndsAt,
        ]);

        app(\App\Services\Audit\TenantAuditService::class)->log(
            $subscription->tenant_id,
            'grace_period_applied',
            "Subscription entered {$graceDays}-day grace period until " . $graceEndsAt->format('d M, Y h:i A') . ".",
            ['grace_days' => $graceDays, 'grace_period_ends_at' => $graceEndsAt->toDateTimeString()],
            'system_cron'
        );

        Log::info("Tenant #{$subscription->tenant_id} entered grace period until {$graceEndsAt}.");
        return $subscription;
    }

    /**
     * Suspend subscription when past grace period and unpaid.
     */
    public function suspendSubscription(TenantSubscription $subscription, string $reason = 'Overdue non-payment'): TenantSubscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);

            $subscription->tenant->update([
                'status' => 'suspended',
            ]);

            app(\App\Services\Audit\TenantAuditService::class)->log(
                $subscription->tenant_id,
                'service_suspended',
                "Automated non-destructive suspension enforced. Reason: {$reason}",
                ['reason' => $reason, 'suspended_at' => now()->toDateTimeString()],
                'system_cron'
            );

            Log::warning("Tenant #{$subscription->tenant_id} suspended. Reason: {$reason}");
            return $subscription;
        });
    }

    /**
     * Reactivate subscription upon successful invoice payment.
     */
    public function reactivateSubscription(TenantSubscription $subscription): TenantSubscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'active',
                'suspended_at' => null,
                'grace_period_ends_at' => null,
            ]);

            $subscription->tenant->update([
                'status' => 'active',
            ]);

            app(\App\Services\Audit\TenantAuditService::class)->log(
                $subscription->tenant_id,
                'service_restored',
                "Subscription reactivated and service access restored.",
                ['reactivated_at' => now()->toDateTimeString()]
            );

            Log::info("Tenant #{$subscription->tenant_id} subscription reactivated successfully.");
            return $subscription;
        });
    }
}
