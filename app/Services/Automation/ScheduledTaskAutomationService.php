<?php

namespace App\Services\Automation;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

class ScheduledTaskAutomationService
{
    use CommandExecutor;

    /**
     * Get real-time overview of all kernel scheduled events and next execution times.
     */
    public function getScheduledTasksOverview(): array
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $formattedTasks = [];
        $categoryCounters = [];
        $nextUpcomingTask = null;
        $shortestDiffMinutes = PHP_INT_MAX;

        foreach ($events as $index => $event) {
            $command = $event->command;
            // Clean up command string: 'artisan' horizon:snapshot -> horizon:snapshot
            $cleanCmd = $command;
            if (preg_match("/'artisan'\s+(.+)$/", $command, $m)) {
                $cleanCmd = trim($m[1]);
            } elseif (preg_match('/artisan\s+(.+)$/', $command, $m)) {
                $cleanCmd = trim($m[1]);
            }

            $expression = $event->expression;
            $timezone = config('app.timezone', 'UTC');
            if ($event->timezone instanceof \DateTimeZone) {
                $timezone = $event->timezone->getName();
            } elseif (is_string($event->timezone)) {
                $timezone = $event->timezone;
            }

            // Calculate Next Run Date
            $nextRun = null;
            $nextRunHuman = 'Pending';
            $diffMinutes = 0;
            try {
                $nextDate = $event->nextRunDate();
                $nextRun = $nextDate->toIso8601String();
                $nextRunHuman = $nextDate->diffForHumans();
                $diffMinutes = max(0, Carbon::now()->diffInMinutes($nextDate, false));

                if ($diffMinutes < $shortestDiffMinutes) {
                    $shortestDiffMinutes = $diffMinutes;
                    $nextUpcomingTask = [
                        'command' => $cleanCmd,
                        'due' => $nextRunHuman,
                    ];
                }
            } catch (\Throwable $e) {
                $nextRunHuman = 'Calculated by cron';
            }

            // Categorize and generate friendly metadata
            $meta = $this->getTaskMetadata($cleanCmd);

            $category = $meta['category'];
            $categoryCounters[$category] = ($categoryCounters[$category] ?? 0) + 1;

            $formattedTasks[] = [
                'id' => $index + 1,
                'command' => $cleanCmd,
                'full_command' => $command ?: "Closure / Custom Task #{$index}",
                'title' => $meta['title'],
                'description' => $meta['description'],
                'category' => $category,
                'cron_expression' => $expression,
                'schedule_human' => $this->formatHumanSchedule($expression),
                'next_run_at' => $nextRun,
                'next_run_formatted' => $nextRunHuman,
                'diff_minutes' => $diffMinutes,
                'timezone' => $timezone,
                'run_in_background' => (bool)$event->runInBackground,
                'without_overlapping' => (bool)$event->withoutOverlapping,
                'even_in_maintenance' => (bool)$event->evenInMaintenanceMode,
                'on_one_server' => (bool)$event->onOneServer,
            ];
        }

        // Sort by next run order
        usort($formattedTasks, fn($a, $b) => $a['diff_minutes'] <=> $b['diff_minutes']);

        $stats = [
            'total_tasks' => count($formattedTasks),
            'next_upcoming_command' => $nextUpcomingTask['command'] ?? 'None scheduled',
            'next_upcoming_due' => $nextUpcomingTask['due'] ?? 'N/A',
            'frequent_tasks_count' => count(array_filter($formattedTasks, fn($t) => str_starts_with($t['cron_expression'], '*/'))),
            'scheduler_status' => 'active',
            'scheduler_label' => 'Scheduler Dispatcher Online',
            'scheduler_mode' => 'System Cron Heartbeat (Every Minute)',
        ];

        return [
            'stats' => $stats,
            'tasks' => $formattedTasks,
            'categories' => array_keys($categoryCounters),
        ];
    }

    /**
     * Execute a specific scheduled task on-demand immediately.
     */
    public function runTaskNow(string $command, ?int $adminId = null): array
    {
        $startTime = microtime(true);
        $cleanCmd = trim($command);

        try {
            $output = [];
            $exitCode = 0;

            exec("php " . base_path('artisan') . " " . escapeshellcmd($cleanCmd) . " 2>&1", $output, $exitCode);

            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            $status = ($exitCode === 0) ? 'success' : 'failed';
            $outputStr = implode("\n", $output);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'scheduled_task_executed_manually',
                'description' => "Executed scheduled task `{$cleanCmd}` manually (Result: {$status}, Duration: {$durationMs}ms).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['command' => $cleanCmd, 'status' => $status, 'duration_ms' => $durationMs],
            ]);

            return [
                'success' => true,
                'command' => $cleanCmd,
                'status' => $status,
                'exit_code' => $exitCode,
                'duration_ms' => $durationMs,
                'output' => $outputStr ?: "(Task finished with zero output)",
                'message' => "Task `{$cleanCmd}` executed ({$status} in {$durationMs}ms).",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'command' => $cleanCmd,
                'status' => 'error',
                'message' => "Failed to execute task: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch full schedule:run check on-demand.
     */
    public function runFullScheduleDispatch(?int $adminId = null): array
    {
        $startTime = microtime(true);

        try {
            $output = [];
            $exitCode = 0;

            exec("php " . base_path('artisan') . " schedule:run 2>&1", $output, $exitCode);

            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            $outputStr = implode("\n", $output);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'schedule_dispatcher_run_manually',
                'description' => "Dispatched `schedule:run` check manually.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['exit_code' => $exitCode, 'duration_ms' => $durationMs],
            ]);

            return [
                'success' => true,
                'exit_code' => $exitCode,
                'duration_ms' => $durationMs,
                'output' => $outputStr ?: "No scheduled commands are ready to run right now.",
                'message' => "Schedule dispatcher cycle finished in {$durationMs}ms.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Failed to run schedule dispatcher: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Get task human title, description, and category.
     */
    protected function getTaskMetadata(string $command): array
    {
        return match (true) {
            str_contains($command, 'horizon:snapshot') => [
                'title' => 'Horizon Queue Metrics Snapshot',
                'description' => 'Snapshots queue throughput, active workers, and runtime metrics for dashboard telemetry',
                'category' => 'Infrastructure & Health',
            ],
            str_contains($command, 'renew-ssl') => [
                'title' => 'Automated SSL Certificate Renewal',
                'description' => 'Checks Let\'s Encrypt SSL certificates expiring in <30 days and triggers automated ACME renewal',
                'category' => 'Security & SSL',
            ],
            str_contains($command, 'suspend-expired') => [
                'title' => 'Auto-Suspend Expired Accounts',
                'description' => 'Sweeps overdue hosting subscriptions past grace period and safely deactivates vhosts',
                'category' => 'Billing & Invoicing',
            ],
            str_contains($command, 'generate-invoices') => [
                'title' => 'Automated Invoice Generation',
                'description' => 'Generates recurring monthly and annual invoices for active hosting plans and addons',
                'category' => 'Billing & Invoicing',
            ],
            str_contains($command, 'backup:run') => [
                'title' => 'Automated Nightly Backup',
                'description' => 'Executes scheduled database dumps, web root archives, and syncs to remote S3 storage',
                'category' => 'Backups & Data',
            ],
            str_contains($command, 'log:clean') => [
                'title' => 'Weekly System Log Rotation & Clean',
                'description' => 'Compresses rotated logs and purges entries older than configured retention policy',
                'category' => 'Maintenance & Queues',
            ],
            str_contains($command, 'server:monitor') => [
                'title' => 'Real-Time Server Health Poller',
                'description' => 'Collects CPU, memory, load average, disk space, and daemon status heartbeats',
                'category' => 'Infrastructure & Health',
            ],
            str_contains($command, 'report:daily') => [
                'title' => 'Daily Executive Digest Report',
                'description' => 'Compiles daily infrastructure, revenue, account provisioning, and alert summary',
                'category' => 'Infrastructure & Health',
            ],
            str_contains($command, 'queue:prune-failed') => [
                'title' => 'Prune Failed Queue Jobs',
                'description' => 'Cleans up resolved failed jobs and orphaned worker locks from the database queue',
                'category' => 'Maintenance & Queues',
            ],
            str_contains($command, 'db:optimize') => [
                'title' => 'Weekly Database Table Optimization',
                'description' => 'Runs OPTIMIZE TABLE on MySQL tables to reclaim defragmented InnoDB disk space',
                'category' => 'Backups & Data',
            ],
            str_contains($command, 'system:metrics') => [
                'title' => 'Hourly System Metrics Aggregator',
                'description' => 'Rolls up raw telemetry metrics into hourly analytical historical data series',
                'category' => 'Infrastructure & Health',
            ],
            default => [
                'title' => "Kernel Command: {$command}",
                'description' => 'Registered Laravel Console Scheduled Command Event',
                'category' => 'General Automation',
            ],
        };
    }

    /**
     * Convert cron expression to human readable format.
     */
    protected function formatHumanSchedule(string $expr): string
    {
        $expr = trim($expr);

        return match ($expr) {
            '* * * * *' => 'Every minute',
            '*/2 * * * *' => 'Every 2 minutes',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/10 * * * *' => 'Every 10 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Hourly (at minute 0)',
            '0 0 * * *' => 'Daily at Midnight (00:00)',
            '0 1 * * *' => 'Daily at 01:00 AM',
            '0 2 * * *' => 'Daily at 02:00 AM',
            '0 3 * * *' => 'Daily at 03:00 AM',
            '0 4 * * *' => 'Daily at 04:00 AM',
            '0 9 * * *' => 'Daily at 09:00 AM',
            '0 0 * * 0' => 'Weekly (Sunday at 00:00)',
            '0 0 1 * *' => 'Monthly (1st day at 00:00)',
            default => "Custom ({$expr})",
        };
    }
}
