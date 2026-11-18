<?php

namespace App\Services\Billing;

use App\Models\AutomationLog;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantAutomationSetting;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantRouter;
use App\Services\Backup\TenantBackupService;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IspCustomerAutomationService
{
    protected MikrotikApiService $mikrotikService;
    protected RadiusService $radiusService;
    protected TenantBackupService $backupService;

    public function __construct(
        MikrotikApiService $mikrotikService,
        RadiusService $radiusService,
        TenantBackupService $backupService
    ) {
        $this->mikrotikService = $mikrotikService;
        $this->radiusService = $radiusService;
        $this->backupService = $backupService;
    }

    /**
     * Master Dynamic Evaluator (Runs every minute via cron)
     * Compares current server time against the exact UI settings saved in admin/settings/automation:
     * - Billing Generation Time (e.g. 12:05 AM)
     * - Auto-Cut Time (e.g. 02:00 AM)
     * - SMS Reminder Dispatch Time (e.g. 10:00 AM)
     * - Backup Time (e.g. 03:00 AM)
     */
    public function runDynamicSchedule(?int $tenantId = null): array
    {
        $tenantQuery = Tenant::query();
        if ($tenantId) {
            $tenantQuery->where('id', $tenantId);
        }
        $tenants = $tenantQuery->get();

        $overallResults = [];

        foreach ($tenants as $tenant) {
            $tenantNow = Carbon::now($tenant->timezone ?: config('app.timezone'));
            $currentTime = $tenantNow->format('H:i');
            $settings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
            $billingTime = Carbon::parse($settings->billing_generation_time ?: '00:05')->format('H:i');
            $cutTime = Carbon::parse($settings->auto_cut_time ?: '02:00')->format('H:i');
            $reminderTime = Carbon::parse($settings->reminder_dispatch_time ?: '10:00')->format('H:i');
            $backupTime = Carbon::parse($settings->backup_time ?: '03:00')->format('H:i');

            // 1. Check if it's time for Auto-Billing (Invoice Generation)
            if ($settings->auto_billing_enabled && $currentTime === $billingTime) {
                $overallResults[] = $this->generateInvoicesForTenant($tenant, $settings, 'cron_dynamic');
            }

            // 2. Check if it's time for Auto-Cut (Line Disconnection)
            if ($settings->auto_cut_enabled && $currentTime === $cutTime) {
                $overallResults[] = $this->enforceAutoCutForTenant($tenant, $settings, 'cron_dynamic');
            }

            // 3. Check if it's time for Pre-Expiry Reminders (SMS Alerts)
            if ($settings->expiry_reminders_enabled && $currentTime === $reminderTime) {
                $overallResults[] = $this->dispatchRemindersForTenant($tenant, $settings, 'cron_dynamic');
            }

            // 4. Check if it's time for Automatic Backup
            if ($settings->auto_backup_enabled && $currentTime === $backupTime) {
                $frequency = $settings->backup_frequency ?: 'daily';
                $shouldRun = match ($frequency) {
                    'weekly' => $tenantNow->isSunday(),
                    'monthly' => $tenantNow->day === 1,
                    default => true, // 'daily'
                };

                if ($shouldRun) {
                    $overallResults[] = $this->executeAutoBackupForTenant($tenant, $settings, 'cron_dynamic');
                }
            }
        }

        return $overallResults;
    }

    /**
     * Generate Invoices for a Tenant based on UI Lead Days
     * Self-healing: captures all upcoming expiring users AND any previously missed cycle subscribers.
     */
    public function generateInvoicesForTenant(Tenant $tenant, TenantAutomationSetting $settings, string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $timezone = $tenant->timezone ?: config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $leadDays = (int) ($settings->billing_generation_day ?: 3);
        $targetExpiryDate = $today->copy()->addDays($leadDays);
        $billingMonth = $targetExpiryDate->format('Y-m');

        $invoicesGenerated = 0;
        $errors = [];

        // Self-Healing Range Query: Catches all upcoming subscribers due within lead days AND anyone previously missed
        $upcomingSubscribers = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due', 'suspended', 'expired'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $targetExpiryDate)
            ->with(['package', 'reseller'])
            ->get();

        foreach ($upcomingSubscribers as $customer) {
            try {
                $created = DB::transaction(function () use ($tenant, $customer, $today, $leadDays): bool {
                    Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
                    $lockedCustomer = TenantCustomer::where('tenant_id', $tenant->id)
                        ->whereKey($customer->id)->lockForUpdate()->with('package')->firstOrFail();
                    $customerTargetExpiry = Carbon::parse($lockedCustomer->expiry_date);
                    $cycleMonth = $customerTargetExpiry->format('Y-m');
                    $cycleKey = 'expiry:' . $customerTargetExpiry->toDateString();
                    $monthlyBill = (float) ($lockedCustomer->monthly_bill > 0
                        ? $lockedCustomer->monthly_bill
                        : ($lockedCustomer->package?->price ?? 0));
                    if ($monthlyBill <= 0) {
                        return false;
                    }

                    if (TenantCustomerInvoice::where('tenant_id', $tenant->id)
                        ->where('customer_id', $lockedCustomer->id)
                        ->where('cycle_key', $cycleKey)->exists()) {
                        return false;
                    }

                    $invoiceNo = TenantCustomerInvoice::generateInvoiceNo($tenant->id, $cycleMonth);
                    TenantCustomerInvoice::create([
                        'tenant_id' => $tenant->id,
                        'reseller_id' => $lockedCustomer->reseller_id,
                        'customer_id' => $lockedCustomer->id,
                        'invoice_no' => $invoiceNo,
                        'billing_month' => $cycleMonth,
                        'cycle_key' => $cycleKey,
                        'package_id' => $lockedCustomer->package_id,
                        'package_name' => $lockedCustomer->package_display_name ?: ($lockedCustomer->package?->name ?? 'Internet Package'),
                        'amount' => $monthlyBill,
                        'discount' => 0.00,
                        'vat_tax' => 0.00,
                        'total_payable' => $monthlyBill,
                        'total_amount' => $monthlyBill,
                        'paid_amount' => 0.00,
                        'due_amount' => $monthlyBill,
                        'issue_date' => $today->toDateString(),
                        'due_date' => $customerTargetExpiry->toDateString(),
                        'status' => 'unpaid',
                        'auto_generated' => true,
                        'notes' => "Auto-generated {$leadDays}-day cycle subscription bill for {$cycleMonth}.",
                        'created_by' => null,
                    ]);

                    // Update customer due amount
                    $lockedCustomer->due_amount = (float) ($lockedCustomer->due_amount ?? 0) + $monthlyBill;
                    $lockedCustomer->save();
                    return true;
                });

                if ($created) {
                    $lastRadiusError = null;
                    for ($attempt = 1; $attempt <= 2; $attempt++) {
                        try {
                            $this->radiusService->syncCustomerSubscriber($customer->fresh());
                            $lastRadiusError = null;
                            break;
                        } catch (\Throwable $rErr) {
                            $lastRadiusError = $rErr;
                        }
                    }
                    if ($lastRadiusError) {
                        Log::warning('IspAutomation: FreeRADIUS invoice sync failed after retry.', [
                            'tenant_id' => $tenant->id,
                            'customer_id' => $customer->id,
                            'attempts' => 2,
                            'error' => $lastRadiusError->getMessage(),
                        ]);
                    }
                    $invoicesGenerated++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Invoice Gen Failed for Customer #{$customer->id}: " . $e->getMessage();
                Log::error("IspAutomation: Invoice Gen Error", ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            }
        }

        $duration = (int) round((microtime(true) - $startTime) * 1000);
        AutomationLog::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'Monthly Billing Invoice Generator',
            'triggered_by' => $triggeredBy,
            'status' => empty($errors) ? 'success' : 'warning',
            'duration_ms' => $duration,
            'metrics' => ['invoices_generated' => $invoicesGenerated, 'lead_days' => $leadDays],
            'output_summary' => "Invoice Generator: Created {$invoicesGenerated} invoices for {$billingMonth} ({$leadDays}-day advance cycle).",
            'created_at' => now(),
        ]);

        return [
            'task' => 'billing',
            'invoices_generated' => $invoicesGenerated,
            'summary' => "Invoice Generator: {$invoicesGenerated} invoices generated.",
            'errors' => $errors
        ];
    }

    /**
     * Enforce Auto-Cut on Expired Subscribers based on Grace Period & Threshold from UI
     * Controlled 100% via FreeRADIUS AAA (radcheck Auth-Type := Reject & RADIUS CoA Disconnect on Port 3799)
     */
    public function enforceAutoCutForTenant(Tenant $tenant, TenantAutomationSetting $settings, string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $today = Carbon::now($tenant->timezone ?: config('app.timezone'))->startOfDay();
        $graceDays = (int) ($settings->grace_period_days ?: 0);
        $minThreshold = (float) ($settings->min_due_threshold ?: 0.00);
        $cutDateThreshold = $today->copy()->subDays($graceDays);

        $expiredCount = 0;
        $disconnectedCount = 0;
        $errors = [];

        $expiredSubscribers = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $cutDateThreshold->toDateString())
            ->where('due_amount', '>', $minThreshold)
            ->get();

        foreach ($expiredSubscribers as $customer) {
            try {
                // Update status in database
                $customer->status = 'expired';
                $customer->save();
                $expiredCount++;

                // 1. FreeRADIUS AAA Sync: Reject Authentication (radcheck Auth-Type := Reject)
                try {
                    $this->radiusService->syncCustomerSubscriber($customer);
                } catch (\Throwable $rError) {
                    Log::warning("IspAutomation: RADIUS sync warning for {$customer->username}: " . $rError->getMessage());
                }

                // 2. FreeRADIUS AAA Disconnect: Terminate active live session via CoA Disconnect-Request (UDP Port 3799)
                try {
                    $activeSession = DB::table('radacct')
                        ->where('tenant_id', $tenant->id)
                        ->where('username', $customer->username)
                        ->whereNull('acctstoptime')
                        ->first();

                    if ($activeSession && !empty($activeSession->nasipaddress)) {
                        $nas = DB::table('nas')
                            ->where('tenant_id', $tenant->id)
                            ->where('nasname', $activeSession->nasipaddress)
                            ->first();
                        $nasSecret = $nas?->secret ?: ($customer->router?->decrypted_radius_secret ?: 'radiussecret');
                        $res = $this->radiusService->disconnectUser($customer->username, $activeSession->nasipaddress, $nasSecret, 3799);
                        if (!empty($res['success'])) {
                            $disconnectedCount++;
                        }
                    } elseif ($customer->router && !empty($customer->router->ip_address)) {
                        $nasSecret = $customer->router->decrypted_radius_secret ?: 'radiussecret';
                        $res = $this->radiusService->disconnectUser($customer->username, $customer->router->ip_address, $nasSecret, 3799);
                        if (!empty($res['success'])) {
                            $disconnectedCount++;
                        }
                    }
                } catch (\Throwable $coaErr) {
                    Log::warning("IspAutomation: CoA Disconnect warning for {$customer->username}: " . $coaErr->getMessage());
                }

                // Optional Cut SMS
                if ($settings->auto_send_cut_sms && !empty($customer->phone)) {
                    try {
                        $companyName = $tenant->company_name ?: ($tenant->name ?: 'ISP');
                        $cutMsg = "Dear {$customer->name}, your {$companyName} internet line is suspended due to unpaid bill. Please pay to restore connection instantly.";
                        SmsService::send($customer->phone, $cutMsg);
                    } catch (\Throwable $smsErr) {
                        Log::warning("IspAutomation: Cut SMS error for {$customer->phone}: " . $smsErr->getMessage());
                    }
                }

                // Log Activity
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => null,
                    'action' => 'customer.auto_expired',
                    'module' => 'customer',
                    'entity_type' => TenantCustomer::class,
                    'entity_id' => $customer->id,
                    'description' => "Customer '{$customer->username}' auto-suspended on {$today->toDateString()} via FreeRADIUS Auto-Cut rules (Grace: {$graceDays}d).",
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'FreeRADIUS Auto-Cut Engine',
                ]);

            } catch (\Throwable $e) {
                $errors[] = "Auto-Cut Error for Customer #{$customer->id}: " . $e->getMessage();
                Log::error("IspAutomation: Auto-Cut Error", ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            }
        }

        $duration = (int) round((microtime(true) - $startTime) * 1000);
        AutomationLog::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'Auto-Cut Line Disconnection Engine',
            'triggered_by' => $triggeredBy,
            'status' => empty($errors) ? 'success' : 'warning',
            'duration_ms' => $duration,
            'metrics' => ['suspended_users' => $expiredCount, 'sessions_disconnected' => $disconnectedCount],
            'output_summary' => "Auto-Cut Engine: Suspended {$expiredCount} overdue accounts (Grace: {$graceDays} days, Action: {$settings->auto_cut_action}).",
            'created_at' => now(),
        ]);

        return [
            'task' => 'autocut',
            'subscribers_expired' => $expiredCount,
            'sessions_disconnected' => $disconnectedCount,
            'summary' => "Auto-Cut Engine: {$expiredCount} overdue accounts suspended.",
            'errors' => $errors
        ];
    }

    /**
     * Dispatch Reminders based on 1st Alert Days and 2nd Alert Days from UI
     */
    public function dispatchRemindersForTenant(Tenant $tenant, TenantAutomationSetting $settings, string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $today = Carbon::now($tenant->timezone ?: config('app.timezone'))->startOfDay();
        $alert1Days = (int) ($settings->reminder_1_days_before ?: 3);
        $alert2Days = (int) ($settings->reminder_2_days_before ?: 1);

        $target1Date = $today->copy()->addDays($alert1Days)->toDateString();
        $target2Date = $today->copy()->addDays($alert2Days)->toDateString();

        $companyName = $tenant->company_name ?: ($tenant->name ?: 'ISP Service');
        $smsSent = 0;
        $errors = [];

        // Find unpaid invoices due on Alert 1 date or Alert 2 date
        $pendingInvoices = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->where('status', 'unpaid')
            ->where(function ($q) use ($target1Date, $target2Date, $today) {
                $q->whereDate('due_date', $target1Date)
                  ->orWhereDate('due_date', $target2Date)
                  ->orWhereDate('due_date', $today->toDateString());
            })
            ->with(['customer', 'package'])
            ->get();

        foreach ($pendingInvoices as $invoice) {
            $customer = $invoice->customer;
            if (!$customer || empty($customer->phone)) {
                continue;
            }

            // Avoid sending multiple reminders on the exact same date
            $alreadySentToday = TenantActivityLog::where('tenant_id', $tenant->id)
                ->where('action', 'sms.expiry_reminder')
                ->where('entity_id', $customer->id)
                ->whereDate('created_at', $today->toDateString())
                ->exists();

            if ($alreadySentToday) {
                continue;
            }

            try {
                $dueFormatted = number_format((float)$invoice->due_amount, 2);
                $dueDateFormatted = $invoice->due_date ? $invoice->due_date->format('d M Y') : 'soon';
                
                $smsMessage = "Dear {$customer->name}, your {$companyName} internet bill of BDT {$dueFormatted} is due on {$dueDateFormatted}. Please pay to avoid line suspension. Thank you.";

                $smsResult = SmsService::send($customer->phone, $smsMessage);

                if (!empty($smsResult['success'])) {
                    $smsSent++;

                    TenantActivityLog::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => null,
                        'action' => 'sms.expiry_reminder',
                        'module' => 'sms',
                        'entity_type' => TenantCustomer::class,
                        'entity_id' => $customer->id,
                        'description' => "Reminder SMS dispatched to {$customer->phone} for invoice {$invoice->invoice_no}.",
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Pre-Expiry Reminder Dispatcher',
                    ]);
                }
            } catch (\Throwable $e) {
                $errors[] = "SMS Error for {$customer->phone}: " . $e->getMessage();
            }
        }

        $duration = (int) round((microtime(true) - $startTime) * 1000);
        AutomationLog::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'Pre-Expiry Due Reminder Dispatcher',
            'triggered_by' => $triggeredBy,
            'status' => empty($errors) ? 'success' : 'warning',
            'duration_ms' => $duration,
            'metrics' => ['reminders_sent' => $smsSent, 'channel' => 'SMS'],
            'output_summary' => "Pre-Expiry Dispatcher: Dispatched {$smsSent} reminder SMS (Alert 1: {$alert1Days}d, Alert 2: {$alert2Days}d).",
            'created_at' => now(),
        ]);

        return [
            'task' => 'reminders',
            'sms_sent' => $smsSent,
            'summary' => "Pre-Expiry Dispatcher: {$smsSent} reminder SMS sent.",
            'errors' => $errors
        ];
    }

    /**
     * Run Midnight Batch directly (for manual button or midnight cron)
     */
    public function runMidnightBatch(?int $tenantId = null, string $triggeredBy = 'cron'): array
    {
        $tenantQuery = Tenant::query();
        if ($tenantId) {
            $tenantQuery->where('id', $tenantId);
        }
        $tenants = $tenantQuery->get();

        $totalInvoices = 0;
        $totalExpired = 0;
        $totalDisconnected = 0;

        foreach ($tenants as $tenant) {
            $settings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
            $resBilling = $this->generateInvoicesForTenant($tenant, $settings, $triggeredBy);
            $resCut = $this->enforceAutoCutForTenant($tenant, $settings, $triggeredBy);

            $totalInvoices += $resBilling['invoices_generated'] ?? 0;
            $totalExpired += $resCut['subscribers_expired'] ?? 0;
            $totalDisconnected += $resCut['sessions_disconnected'] ?? 0;
        }

        return [
            'invoices_generated' => $totalInvoices,
            'subscribers_expired' => $totalExpired,
            'sessions_disconnected' => $totalDisconnected,
            'summary' => "Automation Engine Finished: {$totalInvoices} invoices generated, {$totalExpired} overdue accounts suspended, {$totalDisconnected} sessions disconnected."
        ];
    }

    /**
     * Run Daytime Reminders directly (for manual button or reminder cron)
     */
    public function runDaytimeReminders(?int $tenantId = null, string $triggeredBy = 'cron'): array
    {
        $tenantQuery = Tenant::query();
        if ($tenantId) {
            $tenantQuery->where('id', $tenantId);
        }
        $tenants = $tenantQuery->get();

        $totalSms = 0;
        foreach ($tenants as $tenant) {
            $settings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
            $res = $this->dispatchRemindersForTenant($tenant, $settings, $triggeredBy);
            $totalSms += $res['sms_sent'] ?? 0;
        }

        return [
            'sms_sent' => $totalSms,
            'summary' => "Pre-Expiry Reminders Finished: {$totalSms} SMS messages sent successfully."
        ];
    }

    /**
     * Instant Payment Settle & Auto-Reactivation
     */
    public function settleAndReactivate(TenantCustomer $customer, float $amountPaid, string $paymentMethod = 'cash', ?string $notes = null): array
    {
        return DB::transaction(function () use ($customer, $amountPaid, $paymentMethod, $notes) {
            $lockedCustomer = TenantCustomer::where('tenant_id', $customer->tenant_id)
                ->whereKey($customer->id)->lockForUpdate()->with(['package', 'router', 'tenant'])->firstOrFail();
            $settings = TenantAutomationSetting::where('tenant_id', $lockedCustomer->tenant_id)->first();

            // 1. Settle oldest unpaid invoice
            $pendingInvoice = TenantCustomerInvoice::where('tenant_id', $lockedCustomer->tenant_id)
                ->where('customer_id', $lockedCustomer->id)
                ->whereIn('status', ['unpaid', 'partial', 'partially_paid', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->lockForUpdate()
                ->first();

            if ($pendingInvoice) {
                $applied = min($amountPaid, (float) $pendingInvoice->due_amount);
                $pendingInvoice->paid_amount = round((float) $pendingInvoice->paid_amount + $applied, 2);
                $pendingInvoice->due_amount = max(0, round((float) $pendingInvoice->due_amount - $applied, 2));
                $pendingInvoice->status = ($pendingInvoice->due_amount <= 0) ? 'paid' : 'partially_paid';
                $pendingInvoice->paid_at = $pendingInvoice->due_amount <= 0 ? Carbon::now() : null;
                $pendingInvoice->payment_method = $paymentMethod;
                $pendingInvoice->save();
            }

            $monthlyBill = (float) ($lockedCustomer->monthly_bill > 0
                ? $lockedCustomer->monthly_bill
                : ($lockedCustomer->package?->price ?? 0));
            $policy = app(CustomerPaymentPolicy::class)->calculate(
                oldDue: (float) $lockedCustomer->due_amount,
                oldExpiry: $lockedCustomer->expiry_date ? Carbon::parse($lockedCustomer->expiry_date) : null,
                monthlyBill: $monthlyBill,
                mode: 'due',
                amountPaid: $amountPaid,
                discount: 0,
                months: 1,
                extendValidity: true,
                reactivateRequested: true,
                now: Carbon::now($lockedCustomer->tenant?->timezone ?: config('app.timezone')),
            );
            $lockedCustomer->due_amount = $policy['new_due'];
            if ($policy['new_expiry']) {
                $lockedCustomer->expiry_date = $policy['new_expiry'];
            }
            if ($policy['can_reactivate']) {
                $lockedCustomer->status = 'active';
            }
            $lockedCustomer->save();
            $newExpiry = $lockedCustomer->expiry_date;

            // 3. Auto Re-activate in MikroTik Router
            if ($policy['can_reactivate'] && (!$settings || $settings->auto_reconnect_enabled)) {
                if ($lockedCustomer->router) {
                    try {
                        $this->mikrotikService->togglePppSecret($lockedCustomer->router, $lockedCustomer->username, false);
                    } catch (\Throwable $e) {
                        Log::warning("IspAutomation: Failed to enable secret in MikroTik for {$lockedCustomer->username}: " . $e->getMessage());
                    }
                }

                // 4. Auto Re-activate in FreeRADIUS
                try {
                    $this->radiusService->syncCustomerSubscriber($lockedCustomer);
                } catch (\Throwable $e) {
                    Log::warning("IspAutomation: Failed to sync RADIUS for {$lockedCustomer->username}: " . $e->getMessage());
                }

                // 5. Optional Restore SMS
                if ($settings && $settings->auto_send_restore_sms && !empty($lockedCustomer->phone)) {
                    try {
                        $tenant = $lockedCustomer->tenant;
                        $companyName = $tenant?->company_name ?: ($tenant?->name ?: 'ISP');
                        $restoreMsg = "Dear {$lockedCustomer->name}, payment of BDT {$amountPaid} received. Your {$companyName} line is active until {$newExpiry->format('d M Y')}. Thank you!";
                        SmsService::send($lockedCustomer->phone, $restoreMsg);
                    } catch (\Throwable $smsErr) {
                        Log::warning("IspAutomation: Restore SMS error for {$lockedCustomer->phone}: " . $smsErr->getMessage());
                    }
                }
            }

            return [
                'success' => true,
                'customer_id' => $lockedCustomer->id,
                'new_expiry_date' => $newExpiry?->toDateString(),
                'status' => $lockedCustomer->status,
                'invoice_id' => $pendingInvoice?->id
            ];
        });
    }

    /**
     * Execute Automated Tenant Database Snapshot via Cron Scheduler
     */
    public function executeAutoBackupForTenant(Tenant $tenant, TenantAutomationSetting $settings, string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $errors = [];
        $backup = null;

        try {
            $backup = $this->backupService->createSnapshot(
                tenant: $tenant,
                backupType: 'full_database',
                triggerType: 'scheduled_cron',
                notes: "Automated {$settings->backup_frequency} backup executed by scheduler at {$settings->backup_time}.",
                actorId: null,
                actorName: 'Automated Backup Engine',
                ipAddress: '127.0.0.1',
                userAgent: 'Scheduler Cron Engine'
            );

            // Prune older backups according to retention days
            $retentionDays = (int) ($settings->backup_retention_days ?: 30);
            $this->backupService->pruneOldBackups($tenant, $retentionDays);

        } catch (\Throwable $e) {
            $errors[] = "Auto-Backup Failed: " . $e->getMessage();
            Log::error("IspAutomation: Backup Error", ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }

        $duration = (int) round((microtime(true) - $startTime) * 1000);
        AutomationLog::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'Automated Database Backup',
            'triggered_by' => $triggeredBy,
            'status' => empty($errors) ? 'success' : 'warning',
            'duration_ms' => $duration,
            'metrics' => [
                'filename' => $backup?->filename,
                'size' => $backup?->formatted_size,
                'records' => $backup?->records_count,
            ],
            'output_summary' => empty($errors)
                ? "Auto-Backup Engine: Created snapshot '{$backup->filename}' ({$backup->formatted_size}, {$backup->records_count} records)."
                : "Auto-Backup Engine: Failed - " . implode(' ', $errors),
            'created_at' => now(),
        ]);

        return [
            'task' => 'backup',
            'filename' => $backup?->filename,
            'size' => $backup?->formatted_size,
            'summary' => empty($errors) ? "Auto-Backup: Created '{$backup->filename}' ({$backup->formatted_size})." : "Auto-Backup Failed.",
            'errors' => $errors,
        ];
    }

    /**
     * Audit and self-heal FreeRADIUS AAA consistency for all subscribers
     */
    public function auditFreeRadiusIntegrity(Tenant $tenant): array
    {
        $startTime = microtime(true);
        $auditedCount = 0;
        $repairedCount = 0;
        $errors = [];

        // 1. Sync all NAS clients (Routers) into `nas` table
        try {
            $this->radiusService->syncNasClients($tenant);
        } catch (\Throwable $nasErr) {
            $errors[] = "NAS sync error: " . $nasErr->getMessage();
        }

        // 2. Audit all subscribers
        $subscribers = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->with('package')
            ->get();

        foreach ($subscribers as $customer) {
            $auditedCount++;
            try {
                $username = $customer->username;
                $effectiveStatus = $customer->effective_status;
                $shouldBeDisabled = in_array($effectiveStatus, ['disabled', 'suspended', 'expired']);

                $radcheck = DB::table('radcheck')
                    ->where('tenant_id', $tenant->id)
                    ->where('username', $username)
                    ->first();

                $needsRepair = false;

                if (!$radcheck) {
                    $needsRepair = true;
                } elseif ($shouldBeDisabled && ($radcheck->attribute !== 'Auth-Type' || $radcheck->value !== 'Reject')) {
                    $needsRepair = true;
                } elseif (!$shouldBeDisabled && $radcheck->attribute === 'Auth-Type' && $radcheck->value === 'Reject') {
                    $needsRepair = true;
                }

                if ($needsRepair) {
                    $this->radiusService->syncCustomerSubscriber($customer);
                    $repairedCount++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Audit error for {$customer->username}: " . $e->getMessage();
            }
        }

        $duration = (int) round((microtime(true) - $startTime) * 1000);

        AutomationLog::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'FreeRADIUS AAA Audit & Self-Healing',
            'triggered_by' => 'audit',
            'status' => empty($errors) ? 'success' : 'warning',
            'duration_ms' => $duration,
            'metrics' => ['audited' => $auditedCount, 'repaired' => $repairedCount],
            'output_summary' => "FreeRADIUS Audit: Audited {$auditedCount} subscribers, repaired {$repairedCount} discrepancies.",
            'created_at' => now(),
        ]);

        return [
            'task' => 'radius_audit',
            'audited_count' => $auditedCount,
            'repaired_count' => $repairedCount,
            'summary' => "FreeRADIUS Audit complete: {$auditedCount} audited, {$repairedCount} discrepancies repaired.",
            'errors' => $errors,
        ];
    }
}
