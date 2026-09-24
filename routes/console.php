<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Tasks & Master Automation Scheduler
|--------------------------------------------------------------------------
| Formulated as:
| Scheduler → Dispatcher → Queue → Job → Idempotent Service → DB Transaction → Audit Ledger → Monitoring/Alerting
*/

// 1. Live Cron Heartbeat Monitor (Every Minute)
Schedule::command('system:heartbeat')
    ->everyMinute()
    ->runInBackground();

// 2. Master SaaS Subscription Billing Engine (Daily at 00:05 AM)
// Evaluates upcoming renewals, generates itemized invoices, executes wallet auto-settlements,
// enforces grace periods, and applies non-destructive safe suspensions.
Schedule::command('billing:process-subscriptions --triggered-by=cron')
    ->dailyAt('00:05')
    ->withoutOverlapping(60)
    ->runInBackground();

// 3. Staged Multi-Channel Reminder Dispatcher (Daily at 09:00 AM)
// Sends email & SMS notifications for upcoming, due today, and past due invoices.
Schedule::command('billing:process-subscriptions --triggered-by=cron_reminder')
    ->dailyAt('09:00')
    ->withoutOverlapping(30)
    ->runInBackground();

// 4. Failed Jobs & Log Housekeeping (Weekly)
Schedule::command('queue:prune-failed --hours=168')
    ->weekly()
    ->runInBackground();

// 5. Automated Core OLT Fleet & ONU Diagnostics Sync (Every 5 Minutes)
Schedule::command('network:sync-olts')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();

// 6. ISP Master Subscriber Automation Engine (Dynamic UI-Driven Scheduler)
// Continuously evaluates and executes the exact rules configured on admin/settings/automation:
// - Monthly Billing Generation Time (e.g. 12:05 AM)
// - Auto-Cut Expiry & MikroTik/RADIUS Disconnection Time (e.g. 02:00 AM)
// - Pre-Expiry Multi-Stage SMS Reminder Dispatch Time (e.g. 10:00 AM)
Schedule::command('isp:process-subscribers --mode=auto')
    ->everyMinute()
    ->withoutOverlapping(15)
    ->runInBackground();


