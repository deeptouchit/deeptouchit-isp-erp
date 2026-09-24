<?php

namespace App\Services\Automation;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Services\NginxManager;
use App\Services\UserManager;
use App\Traits\CommandExecutor;
use Carbon\Carbon;

class AutoSuspensionService
{
    use CommandExecutor;

    protected NginxManager $nginxManager;
    protected UserManager $userManager;

    public function __construct(NginxManager $nginxManager, UserManager $userManager)
    {
        $this->nginxManager = $nginxManager;
        $this->userManager = $userManager;
    }

    /**
     * Get real-time overview of overdue accounts, grace periods, and auto-suspension policies.
     */
    public function getSuspensionOverview(array $filters = []): array
    {
        $query = Subscription::with(['user:id,first_name,last_name,username,email', 'plan']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where('first_name', 'like', "%{$s}%")
                         ->orWhere('last_name', 'like', "%{$s}%")
                         ->orWhere('username', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            if ($filters['status'] === 'grace') {
                $query->where('status', 'active')
                      ->whereNotNull('next_billing_date')
                      ->where('next_billing_date', '<', now());
            } else {
                $query->where('status', $filters['status']);
            }
        }

        $allSubs = Subscription::with(['user', 'plan'])->get();
        $totalCount = $allSubs->count();
        $suspendedCount = $allSubs->where('status', 'suspended')->count();
        $overdueInGrace = $allSubs->filter(function ($sub) {
            return $sub->status === 'active' && $sub->next_billing_date && $sub->next_billing_date->isPast();
        })->count();

        $graceDaysSetting = 3;

        $subscriptionsList = $query->latest()->get()->map(function (Subscription $sub) use ($graceDaysSetting) {
            $isOverdue = $sub->next_billing_date && $sub->next_billing_date->isPast();
            $daysOverdue = $isOverdue ? (int)now()->diffInDays($sub->next_billing_date) : 0;
            $graceRemaining = max(0, $graceDaysSetting - $daysOverdue);

            return [
                'id' => $sub->id,
                'domain' => $sub->domain,
                'username' => $sub->username,
                'user_name' => $sub->user ? $sub->user->name : 'System Client',
                'user_email' => $sub->user ? $sub->user->email : 'N/A',
                'plan_name' => $sub->plan ? $sub->plan->name : 'Standard Hosting',
                'price' => $sub->price ?: ($sub->plan ? $sub->plan->price : 0),
                'status' => $sub->status,
                'is_overdue' => $isOverdue,
                'days_overdue' => $daysOverdue,
                'grace_remaining_days' => $graceRemaining,
                'next_billing_date' => $sub->next_billing_date ? $sub->next_billing_date->format('M d, Y') : 'N/A',
                'next_billing_formatted' => $sub->next_billing_date ? $sub->next_billing_date->diffForHumans() : 'N/A',
                'suspended_at' => $sub->suspended_at ? $sub->suspended_at->format('M d, Y H:i') : null,
                'suspended_human' => $sub->suspended_at ? $sub->suspended_at->diffForHumans() : null,
            ];
        });

        $stats = [
            'total_subscriptions' => $totalCount,
            'currently_suspended' => $suspendedCount,
            'overdue_in_grace' => $overdueInGrace,
            'grace_period_days' => $graceDaysSetting,
            'auto_suspension_enabled' => true,
            'auto_unsuspend_on_payment' => true,
            'next_sweep_schedule' => 'Daily at 03:00 AM (Automated Cron)',
        ];

        return [
            'stats' => $stats,
            'subscriptions' => $subscriptionsList,
        ];
    }

    /**
     * Run Auto-Suspension sweep for all overdue subscriptions past grace period.
     */
    public function runAutoSuspensionSweep(?int $adminId = null): array
    {
        $startTime = microtime(true);
        $graceDays = 3;
        $cutoffDate = now()->subDays($graceDays);

        $expiredSubs = Subscription::where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', $cutoffDate)
            ->get();

        $suspended = [];
        $failed = [];

        foreach ($expiredSubs as $sub) {
            try {
                $sub->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                ]);

                // Suspend Nginx domain & System user
                if ($sub->domain) {
                    @$this->nginxManager->suspendDomain($sub->domain);
                }
                if ($sub->username) {
                    @$this->userManager->suspendSystemUser($sub->username);
                }

                $suspended[] = $sub->domain;
            } catch (\Throwable $e) {
                $failed[] = $sub->domain;
            }
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'auto_suspension_sweep_executed',
            'description' => "Executed Auto-Suspension sweep (Suspended: " . count($suspended) . ", Failed: " . count($failed) . " in {$durationMs}ms).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['suspended' => $suspended, 'failed' => $failed, 'duration_ms' => $durationMs],
        ]);

        return [
            'success' => true,
            'suspended_count' => count($suspended),
            'failed_count' => count($failed),
            'suspended_domains' => $suspended,
            'duration_ms' => $durationMs,
            'message' => "Auto-suspension sweep complete: " . count($suspended) . " overdue account(s) quarantined in {$durationMs}ms.",
        ];
    }

    /**
     * Suspend a subscription immediately.
     */
    public function suspendSubscription(int $id, ?string $reason = null, ?int $adminId = null): array
    {
        try {
            $sub = Subscription::findOrFail($id);
            $sub->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);

            if ($sub->domain) {
                @$this->nginxManager->suspendDomain($sub->domain);
            }
            if ($sub->username) {
                @$this->userManager->suspendSystemUser($sub->username);
            }

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'subscription_suspended_manually',
                'description' => "Suspended subscription `{$sub->domain}` (Reason: " . ($reason ?: 'Administrative quarantine') . ").",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['status' => 'active'],
                'new_values' => ['status' => 'suspended', 'reason' => $reason],
            ]);

            return ['success' => true, 'message' => "Subscription `{$sub->domain}` has been suspended."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to suspend subscription: ' . $e->getMessage()];
        }
    }

    /**
     * Unsuspend / restore a subscription immediately.
     */
    public function unsuspendSubscription(int $id, ?int $adminId = null): array
    {
        try {
            $sub = Subscription::findOrFail($id);
            $sub->update([
                'status' => 'active',
                'suspended_at' => null,
            ]);

            if ($sub->domain) {
                @$this->nginxManager->unsuspendDomain($sub->domain);
            }
            if ($sub->username) {
                @$this->userManager->unsuspendSystemUser($sub->username);
            }

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'subscription_unsuspended_manually',
                'description' => "Unsuspended and reactivated subscription `{$sub->domain}`.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['status' => 'suspended'],
                'new_values' => ['status' => 'active'],
            ]);

            return ['success' => true, 'message' => "Subscription `{$sub->domain}` has been restored and unsuspended."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to unsuspend subscription: ' . $e->getMessage()];
        }
    }

    /**
     * Extend grace period by N days.
     */
    public function extendGracePeriod(int $id, int $days = 7, ?int $adminId = null): array
    {
        try {
            $sub = Subscription::findOrFail($id);
            $newDate = ($sub->next_billing_date && $sub->next_billing_date->isFuture())
                ? $sub->next_billing_date->addDays($days)
                : now()->addDays($days);

            $sub->update(['next_billing_date' => $newDate]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'grace_period_extended',
                'description' => "Extended grace period for `{$sub->domain}` by {$days} days (New Due Date: {$newDate->toDateString()}).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['days_added' => $days, 'new_due_date' => $newDate->toDateString()],
            ]);

            return ['success' => true, 'message' => "Grace period for `{$sub->domain}` extended by {$days} days."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to extend grace period: ' . $e->getMessage()];
        }
    }
}
