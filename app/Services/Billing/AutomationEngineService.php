<?php

namespace App\Services\Billing;

use App\Models\AutomationLog;
use App\Models\Setting;
use App\Models\TenantSubscription;
use App\Models\TenantWallet;
use App\Models\TenantWalletTransaction;
use App\Models\SaasInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutomationEngineService
{
    protected InvoiceService $invoiceService;
    protected SuspensionService $suspensionService;
    protected RenewalService $renewalService;
    protected BillingReminderService $reminderService;

    public function __construct(
        InvoiceService $invoiceService,
        SuspensionService $suspensionService,
        RenewalService $renewalService,
        BillingReminderService $reminderService
    ) {
        $this->invoiceService = $invoiceService;
        $this->suspensionService = $suspensionService;
        $this->renewalService = $renewalService;
        $this->reminderService = $reminderService;
    }

    /**
     * Get dynamic automation policy settings.
     */
    public function getPolicy(): array
    {
        $invoiceLeadDays = Setting::get('invoice_generate_days_before') !== null
            ? (int) Setting::get('invoice_generate_days_before')
            : (int) Setting::get('automation_invoice_lead_days', 7);

        $gracePeriodDays = Setting::get('billing_grace_days') !== null
            ? (int) Setting::get('billing_grace_days')
            : (int) Setting::get('automation_grace_period_days', 3);

        $autoSuspend = Setting::get('auto_suspend_tenant') !== null
            ? (Setting::get('auto_suspend_tenant') == '1')
            : (Setting::get('automation_auto_suspend_enabled', '1') === '1');

        return [
            'invoice_lead_days' => $invoiceLeadDays,
            'grace_period_days' => $gracePeriodDays,
            'auto_suspend_enabled' => $autoSuspend,
            'auto_wallet_renew_enabled' => Setting::get('automation_auto_wallet_renew_enabled', '1') === '1',
            'reminder_lead_days' => json_decode(Setting::get('automation_reminder_lead_days', '[7, 3, 1, 0]'), true) ?: [7, 3, 1, 0],
            'cron_run_hour' => Setting::get('automation_cron_run_hour', '00:05'),
            'last_heartbeat_at' => Setting::get('cron_last_heartbeat_at'),
        ];
    }

    /**
     * Execute full Master Billing Engine with Idempotency, Per-Tenant Failure Isolation, and Audit Ledger.
     */
    public function runFullEngine(string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $policy = $this->getPolicy();

        $metrics = [
            'invoices_generated' => 0,
            'auto_renewals_processed' => 0,
            'grace_periods_applied' => 0,
            'suspensions_enforced' => 0,
            'reminders_dispatched' => 0,
        ];

        $errors = [];

        // 1. Idempotent Upcoming Invoices Generation
        try {
            $metrics['invoices_generated'] = $this->generateUpcomingInvoices($policy['invoice_lead_days']);
        } catch (\Throwable $e) {
            $errors[] = "Invoice Generation Error: " . $e->getMessage();
            Log::error("AutomationEngine: Invoice Generation Batch Failed", ['error' => $e->getMessage()]);
        }

        // 2. Automated Wallet Renewals
        if ($policy['auto_wallet_renew_enabled']) {
            try {
                $metrics['auto_renewals_processed'] = $this->processAutoRenewalsFromWallets();
            } catch (\Throwable $e) {
                $errors[] = "Auto-Renewal Error: " . $e->getMessage();
                Log::error("AutomationEngine: Auto-Renewal Batch Failed", ['error' => $e->getMessage()]);
            }
        }

        // 3. Grace Period & Non-Destructive Suspensions
        try {
            $suspensionResults = $this->enforceGraceAndSuspensionPolicy($policy['grace_period_days'], $policy['auto_suspend_enabled']);
            $metrics['grace_periods_applied'] = $suspensionResults['grace_periods'];
            $metrics['suspensions_enforced'] = $suspensionResults['suspensions'];
        } catch (\Throwable $e) {
            $errors[] = "Suspension Check Error: " . $e->getMessage();
            Log::error("AutomationEngine: Suspension Check Batch Failed", ['error' => $e->getMessage()]);
        }

        // 4. Staged Multi-Channel Reminders
        try {
            $metrics['reminders_dispatched'] = $this->dispatchReminders();
        } catch (\Throwable $e) {
            $errors[] = "Reminder Dispatch Error: " . $e->getMessage();
            Log::error("AutomationEngine: Reminder Dispatch Failed", ['error' => $e->getMessage()]);
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $status = empty($errors) ? 'success' : (count($errors) < 4 ? 'warning' : 'failed');
        $summary = "Processed: {$metrics['invoices_generated']} invoices generated, {$metrics['auto_renewals_processed']} wallet renewals, {$metrics['suspensions_enforced']} suspensions, {$metrics['reminders_dispatched']} reminders.";
        if (!empty($errors)) {
            $summary .= " Issues: " . implode('; ', $errors);
        }

        // Save Audit Log
        AutomationLog::create([
            'task_name' => 'master_billing_engine',
            'triggered_by' => $triggeredBy,
            'status' => $status,
            'duration_ms' => $durationMs,
            'metrics' => $metrics,
            'output_summary' => $summary,
        ]);

        return [
            'status' => $status,
            'duration_ms' => $durationMs,
            'metrics' => $metrics,
            'summary' => $summary,
        ];
    }

    /**
     * 1. Idempotent Upcoming Invoice Generation with Per-Tenant Failure Isolation.
     */
    public function generateUpcomingInvoices(int $leadDays = 3): int
    {
        $targetDate = Carbon::today()->addDays($leadDays);
        $count = 0;

        $subscriptions = TenantSubscription::with(['tenant', 'plan'])
            ->whereIn('status', ['active', 'trial', 'grace_period'])
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', $targetDate)
            ->get();

        foreach ($subscriptions as $sub) {
            try {
                DB::transaction(function () use ($sub, &$count) {
                    // Check if an unpaid invoice already exists for this subscription (Idempotency)
                    $unpaidExists = SaasInvoice::where('tenant_id', $sub->tenant_id)
                        ->where('tenant_subscription_id', $sub->id)
                        ->whereIn('status', ['unpaid', 'partially_paid'])
                        ->exists();

                    if (!$unpaidExists) {
                        $this->invoiceService->generateSubscriptionInvoice($sub);
                        $count++;
                    }
                });
            } catch (\Throwable $e) {
                // Per-Tenant Isolation: Log error and continue loop for remaining tenants
                Log::error("Invoice Generation Failed for Tenant #{$sub->tenant_id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * 2. Auto-Renewal via Prepaid Wallets with Per-Tenant Failure Isolation & Row Locking.
     */
    public function processAutoRenewalsFromWallets(): int
    {
        $count = 0;
        $unpaidInvoices = SaasInvoice::with(['tenant.wallet', 'subscription'])
            ->where('status', 'unpaid')
            ->whereNotNull('tenant_subscription_id')
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            try {
                $tenant = $invoice->tenant;
                if (!$tenant) continue;

                DB::transaction(function () use ($invoice, $tenant, &$count) {
                    // Lock wallet row for update
                    $wallet = TenantWallet::where('tenant_id', $tenant->id)->lockForUpdate()->first();
                    $due = (float) $invoice->calculated_due;

                    if ($wallet && $wallet->balance >= $due && $due > 0) {
                        // Deduct from wallet
                        $wallet->balance -= $due;
                        $wallet->save();

                        TenantWalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'tenant_id' => $tenant->id,
                            'type' => 'debit',
                            'amount' => $due,
                            'balance_after' => $wallet->balance,
                            'description' => "Auto settlement for Invoice #{$invoice->invoice_no}",
                            'reference' => 'AUTO-' . strtoupper(Str::random(6)),
                        ]);

                        // Record Payment & Renew Subscription
                        $this->invoiceService->recordPayment($invoice, $due, 'wallet', 'WALLET-AUTO-' . $invoice->id);
                        $count++;
                    }
                });
            } catch (\Throwable $e) {
                // Per-Tenant Isolation: Continue processing remaining tenants
                Log::error("Auto-Renewal from Wallet Failed for Invoice #{$invoice->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * 3. Grace Period & Suspensions with Method Fix & Per-Tenant Isolation.
     */
    public function enforceGraceAndSuspensionPolicy(int $gracePeriodDays = 3, bool $autoSuspend = true): array
    {
        $today = Carbon::today();
        $graceCount = 0;
        $suspensionCount = 0;

        $overdueSubscriptions = TenantSubscription::with('tenant')
            ->whereIn('status', ['active', 'trial', 'grace_period', 'past_due'])
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $today)
            ->get();

        foreach ($overdueSubscriptions as $sub) {
            try {
                DB::transaction(function () use ($sub, $today, $gracePeriodDays, $autoSuspend, &$graceCount, &$suspensionCount) {
                    $daysOverdue = $sub->current_period_end->diffInDays($today);

                    if ($daysOverdue <= $gracePeriodDays) {
                        if ($sub->status !== 'grace_period') {
                            $sub->update([
                                'status' => 'grace_period',
                                'grace_period_ends_at' => $sub->current_period_end->copy()->addDays($gracePeriodDays),
                            ]);
                            $graceCount++;
                        }
                    } else {
                        if ($autoSuspend && $sub->status !== 'suspended') {
                            // Method Fix: Call SubscriptionService::suspendSubscription
                            app(\App\Services\Billing\SubscriptionService::class)->suspendSubscription(
                                $sub, 
                                "Overdue subscription unpaid after {$gracePeriodDays} days grace period."
                            );
                            $suspensionCount++;
                        }
                    }
                });
            } catch (\Throwable $e) {
                // Per-Tenant Failure Isolation: Log and continue
                Log::error("Grace/Suspension Policy Error for Subscription #{$sub->id}: " . $e->getMessage());
            }
        }

        return [
            'grace_periods' => $graceCount,
            'suspensions' => $suspensionCount,
        ];
    }

    /**
     * 4. Dispatch Reminders.
     */
    public function dispatchReminders(): int
    {
        $res = $this->reminderService->sendReminders();
        return ($res['upcoming'] ?? 0) + ($res['due_today'] ?? 0) + ($res['overdue'] ?? 0);
    }
}
