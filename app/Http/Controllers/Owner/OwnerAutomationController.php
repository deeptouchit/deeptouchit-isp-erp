<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AutomationLog;
use App\Models\Setting;
use App\Services\Billing\AutomationEngineService;
use App\Services\Queue\QueueMonitorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class OwnerAutomationController extends Controller
{
    protected AutomationEngineService $engine;
    protected QueueMonitorService $queueMonitor;

    public function __construct(AutomationEngineService $engine, QueueMonitorService $queueMonitor)
    {
        $this->engine = $engine;
        $this->queueMonitor = $queueMonitor;
    }

    /**
     * Display Automation & Cron Control Hub.
     */
    public function index()
    {
        $policy = $this->engine->getPolicy();

        // Calculate Cron Heartbeat Status
        $queueHealth = $this->queueMonitor->getQueueHealth();
        $failedJobsList = $this->queueMonitor->getFailedJobs(30);

        $lastHeartbeat = Setting::get('cron_last_heartbeat_at');
        $heartbeatCarbon = $lastHeartbeat ? Carbon::parse($lastHeartbeat) : null;
        $diffMinutes = $heartbeatCarbon ? $heartbeatCarbon->diffInMinutes(now()) : 9999;

        if (!$heartbeatCarbon) {
            $cronStatus = 'unconfigured';
            $cronStatusText = 'Never Detected';
        } elseif ($diffMinutes <= 3) {
            $cronStatus = 'healthy';
            $cronStatusText = 'Active & Healthy';
        } elseif ($diffMinutes <= 15) {
            $cronStatus = 'warning';
            $cronStatusText = 'Delayed (' . round($diffMinutes) . 'm ago)';
        } else {
            $cronStatus = 'down';
            $cronStatusText = 'Offline (' . round($diffMinutes) . 'm ago)';
        }

        // Server crontab code snippet
        $phpPath = PHP_BINARY ?: '/usr/bin/php';
        $basePath = base_path();
        $cronSnippet = "* * * * * cd {$basePath} && {$phpPath} artisan schedule:run >> /dev/null 2>&1";
        $supervisorSnippet = "php {$basePath}/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=90";

        // Audit Logs
        $logs = AutomationLog::latest()->paginate(20)->withQueryString();

        // Metrics Summary
        $totalRuns = AutomationLog::count();
        $successRuns = AutomationLog::where('status', 'success')->count();
        $failedRuns = AutomationLog::where('status', 'failed')->count();

        return view('owner.automation.index', compact(
            'policy',
            'cronStatus',
            'cronStatusText',
            'heartbeatCarbon',
            'cronSnippet',
            'supervisorSnippet',
            'queueHealth',
            'failedJobsList',
            'logs',
            'totalRuns',
            'successRuns',
            'failedRuns'
        ));
    }

    /**
     * Update Automation Engine Policies.
     */
    public function updatePolicy(Request $request)
    {
        $validated = $request->validate([
            'automation_invoice_lead_days' => ['required', 'integer', 'min:1', 'max:30'],
            'automation_grace_period_days' => ['required', 'integer', 'min:0', 'max:30'],
            'automation_auto_suspend_enabled' => ['nullable', 'boolean'],
            'automation_auto_wallet_renew_enabled' => ['nullable', 'boolean'],
            'automation_cron_run_hour' => ['required', 'string', 'max:10'],
        ]);

        Setting::set('automation_invoice_lead_days', $validated['automation_invoice_lead_days']);
        Setting::set('automation_grace_period_days', $validated['automation_grace_period_days']);
        Setting::set('automation_auto_suspend_enabled', $request->has('automation_auto_suspend_enabled') ? '1' : '0');
        Setting::set('automation_auto_wallet_renew_enabled', $request->has('automation_auto_wallet_renew_enabled') ? '1' : '0');
        Setting::set('automation_cron_run_hour', $validated['automation_cron_run_hour']);

        return back()->with('success', 'Automation policies updated successfully.');
    }

    /**
     * Failed Jobs Operations (Retry, Retry All, Delete, Flush).
     */
    public function retryFailedJob(string|int $id)
    {
        $success = $this->queueMonitor->retryJob($id);
        return back()->with($success ? 'success' : 'error', $success ? "Failed Job #{$id} queued for retry." : "Failed to retry Job #{$id}.");
    }

    public function retryAllFailedJobs()
    {
        $success = $this->queueMonitor->retryAll();
        return back()->with($success ? 'success' : 'error', $success ? "All failed jobs sent back to queue for retry." : "Failed to retry jobs.");
    }

    public function deleteFailedJob(string|int $id)
    {
        $success = $this->queueMonitor->forgetJob($id);
        return back()->with($success ? 'success' : 'error', $success ? "Failed Job #{$id} removed permanently." : "Failed to delete job.");
    }

    public function flushAllFailedJobs()
    {
        $success = $this->queueMonitor->flushAll();
        return back()->with($success ? 'success' : 'error', $success ? "All failed jobs flushed." : "Failed to flush jobs.");
    }

    /**
     * Manual Trigger for Automation Tasks.
     */
    public function dispatchTask(Request $request, string $task)
    {
        $adminName = auth()->user()->name ?? 'Owner';

        switch ($task) {
            case 'full_engine':
                $result = $this->engine->runFullEngine("manual ({$adminName})");
                return back()->with('success', "Master Billing Engine executed: {$result['summary']}");

            case 'invoices_only':
                $policy = $this->engine->getPolicy();
                $count = $this->engine->generateUpcomingInvoices($policy['invoice_lead_days']);
                AutomationLog::create([
                    'task_name' => 'invoices_generation_only',
                    'triggered_by' => "manual ({$adminName})",
                    'status' => 'success',
                    'duration_ms' => 150,
                    'metrics' => ['invoices_generated' => $count],
                    'output_summary' => "Manually generated {$count} upcoming invoices.",
                ]);
                return back()->with('success', "Invoice generation completed. {$count} upcoming invoices generated.");

            case 'suspensions_only':
                $policy = $this->engine->getPolicy();
                $res = $this->engine->enforceGraceAndSuspensionPolicy($policy['grace_period_days'], $policy['auto_suspend_enabled']);
                AutomationLog::create([
                    'task_name' => 'suspensions_check_only',
                    'triggered_by' => "manual ({$adminName})",
                    'status' => 'success',
                    'duration_ms' => 120,
                    'metrics' => $res,
                    'output_summary' => "Manually enforced {$res['grace_periods']} grace periods and {$res['suspensions']} suspensions.",
                ]);
                return back()->with('success', "Suspension policy checked. Applied {$res['grace_periods']} grace warnings and {$res['suspensions']} suspensions.");

            case 'reminders_only':
                $count = $this->engine->dispatchReminders();
                AutomationLog::create([
                    'task_name' => 'reminders_dispatch_only',
                    'triggered_by' => "manual ({$adminName})",
                    'status' => 'success',
                    'duration_ms' => 100,
                    'metrics' => ['active_subscriptions' => $count],
                    'output_summary' => "Manually dispatched reminders for {$count} active/due subscriptions.",
                ]);
                return back()->with('success', "Billing reminder notifications dispatched.");

            case 'heartbeat_now':
                Artisan::call('system:heartbeat');
                return back()->with('success', 'Heartbeat ping updated successfully.');

            default:
                return back()->with('error', 'Unknown automation task.');
        }
    }
}
