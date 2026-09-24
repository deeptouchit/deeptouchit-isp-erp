<?php

namespace App\Services\Automation;

use App\Models\ActivityLog;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;

class CronAutomationService
{
    use CommandExecutor;

    /**
     * Get real-time Cron jobs overview, daemon status, system crontabs, and stats.
     */
    public function getCronOverview(): array
    {
        // 1. Check systemd cron daemon status
        $daemonActive = false;
        $daemonPid = null;
        $daemonSince = 'Active';

        try {
            $statusRaw = @shell_exec("systemctl is-active cron 2>/dev/null");
            $daemonActive = (trim((string)$statusRaw) === 'active');
            $pidRaw = @shell_exec("systemctl show cron -p MainPID --value 2>/dev/null");
            $daemonPid = (int)trim((string)$pidRaw) ?: null;
        } catch (\Throwable $e) {
            $daemonActive = true;
        }

        // 2. Fetch database managed cron jobs
        $jobs = CronJob::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalJobs = $jobs->count();
        $enabledJobs = $jobs->where('is_enabled', true)->count();
        $failedLast24h = CronJobLog::where('status', 'failed')
            ->where('executed_at', '>=', now()->subHours(24))
            ->count();

        $formattedJobs = $jobs->map(function (CronJob $job) {
            return [
                'id' => $job->id,
                'title' => $job->title ?: 'Scheduled Job #' . $job->id,
                'command' => $job->command,
                'cron_expression' => $job->cron_expression,
                'schedule_human' => $job->schedule_human,
                'description' => $job->description,
                'output_handling' => $job->output_handling,
                'log_file_path' => $job->log_file_path,
                'is_enabled' => (bool)$job->is_enabled,
                'run_as_user' => $job->run_as_user ?: 'www-data',
                'user_name' => $job->user ? $job->user->name : 'System Admin',
                'last_run_at' => $job->last_run_at ? $job->last_run_at->toIso8601String() : null,
                'last_run_formatted' => $job->last_run_at ? $job->last_run_at->diffForHumans() : 'Never executed',
                'last_run_status' => $job->last_run_status ?: 'pending',
                'last_run_duration_ms' => $job->last_run_duration_ms,
                'last_output_preview' => $job->last_output_preview,
                'created_at' => $job->created_at ? $job->created_at->format('M d, Y H:i') : null,
            ];
        });

        // 3. Dynamically discover OS crontabs in /etc/cron.d and /etc/crontab
        $systemCrons = [];
        if (File::isDirectory('/etc/cron.d')) {
            $files = File::files('/etc/cron.d');
            foreach ($files as $f) {
                $fname = $f->getFilename();
                if (!str_starts_with($fname, '.')) {
                    $content = @file_get_contents($f->getRealPath());
                    $lines = array_values(array_filter(explode("\n", (string)$content), function ($l) {
                        $tl = trim($l);
                        return $tl !== '' && !str_starts_with($tl, '#');
                    }));

                    $systemCrons[] = [
                        'file' => "/etc/cron.d/{$fname}",
                        'name' => $fname,
                        'entries_count' => count($lines),
                        'snippet' => implode("\n", array_slice($lines, 0, 3)),
                    ];
                }
            }
        }

        $stats = [
            'total_jobs' => $totalJobs,
            'enabled_jobs' => $enabledJobs,
            'disabled_jobs' => $totalJobs - $enabledJobs,
            'failed_24h' => $failedLast24h,
            'daemon_status' => $daemonActive ? 'active' : 'inactive',
            'daemon_label' => $daemonActive ? 'Cron Daemon Running' : 'Daemon Stopped',
            'daemon_pid' => $daemonPid,
            'system_crons_count' => count($systemCrons),
        ];

        return [
            'stats' => $stats,
            'jobs' => $formattedJobs,
            'system_crons' => $systemCrons,
        ];
    }

    /**
     * Create a new scheduled cron job.
     */
    public function createJob(array $data, ?int $adminId = null): array
    {
        try {
            $job = CronJob::create([
                'user_id' => $data['user_id'] ?? $adminId ?? auth()->id(),
                'title' => $data['title'] ?? 'Custom Cron Command',
                'command' => trim($data['command']),
                'cron_expression' => trim($data['cron_expression'] ?? '* * * * *'),
                'description' => $data['description'] ?? null,
                'output_handling' => $data['output_handling'] ?? 'log_file',
                'log_file_path' => $data['log_file_path'] ?? null,
                'is_enabled' => (bool)($data['is_enabled'] ?? true),
                'run_as_user' => $data['run_as_user'] ?? 'www-data',
            ]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'cron_job_created',
                'description' => "Created cron job #{$job->id}: `{$job->command}` ({$job->cron_expression}).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['id' => $job->id, 'command' => $job->command, 'expression' => $job->cron_expression],
            ]);

            return ['success' => true, 'message' => 'Cron job scheduled successfully.', 'job' => $job];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to create cron job: ' . $e->getMessage()];
        }
    }

    /**
     * Update an existing scheduled cron job.
     */
    public function updateJob(int $id, array $data, ?int $adminId = null): array
    {
        try {
            $job = CronJob::findOrFail($id);
            $job->update([
                'title' => $data['title'] ?? $job->title,
                'command' => trim($data['command'] ?? $job->command),
                'cron_expression' => trim($data['cron_expression'] ?? $job->cron_expression),
                'description' => $data['description'] ?? $job->description,
                'output_handling' => $data['output_handling'] ?? $job->output_handling,
                'log_file_path' => $data['log_file_path'] ?? $job->log_file_path,
                'is_enabled' => isset($data['is_enabled']) ? (bool)$data['is_enabled'] : $job->is_enabled,
                'run_as_user' => $data['run_as_user'] ?? $job->run_as_user,
            ]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'cron_job_updated',
                'description' => "Updated cron job #{$job->id}: `{$job->command}` ({$job->cron_expression}).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['id' => $job->id, 'command' => $job->command, 'expression' => $job->cron_expression],
            ]);

            return ['success' => true, 'message' => 'Cron job updated successfully.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to update cron job: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a scheduled cron job.
     */
    public function deleteJob(int $id, ?int $adminId = null): array
    {
        try {
            $job = CronJob::findOrFail($id);
            $cmd = $job->command;
            $job->delete();

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'cron_job_deleted',
                'description' => "Deleted cron job #{$id} (`{$cmd}`).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['id' => $id, 'command' => $cmd],
                'new_values' => [],
            ]);

            return ['success' => true, 'message' => 'Cron job deleted successfully.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to delete cron job: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle enabled / disabled state of a cron job.
     */
    public function toggleJob(int $id, ?int $adminId = null): array
    {
        try {
            $job = CronJob::findOrFail($id);
            $job->is_enabled = !$job->is_enabled;
            $job->save();

            $state = $job->is_enabled ? 'enabled' : 'disabled';

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => "cron_job_{$state}",
                'description' => "Set cron job #{$id} status to {$state}.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['id' => $id, 'is_enabled' => $job->is_enabled],
            ]);

            return ['success' => true, 'message' => "Cron job #{$id} {$state} successfully.", 'is_enabled' => $job->is_enabled];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to toggle cron job: ' . $e->getMessage()];
        }
    }

    /**
     * Run a scheduled cron job immediately on-demand.
     */
    public function runJobNow(int $id, ?int $adminId = null): array
    {
        try {
            $job = CronJob::findOrFail($id);
            $startTime = microtime(true);

            // Execute command
            $cmd = $job->command;
            $output = [];
            $exitCode = 0;

            exec("bash -c " . escapeshellarg($cmd) . " 2>&1", $output, $exitCode);

            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            $status = ($exitCode === 0) ? 'success' : 'failed';
            $outputStr = implode("\n", $output);
            $preview = mb_substr($outputStr, 0, 500);

            // Update Job stats
            $job->update([
                'last_run_at' => now(),
                'last_run_status' => $status,
                'last_run_duration_ms' => $durationMs,
                'last_output_preview' => $preview,
            ]);

            // Record Log
            $log = CronJobLog::create([
                'cron_job_id' => $job->id,
                'executed_at' => now(),
                'status' => $status,
                'duration_ms' => $durationMs,
                'exit_code' => $exitCode,
                'output' => $outputStr,
            ]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'cron_job_executed_manually',
                'description' => "Executed cron job #{$id} manually (Result: {$status}, Exit Code: {$exitCode}, Duration: {$durationMs}ms).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['id' => $id, 'status' => $status, 'exit_code' => $exitCode],
            ]);

            return [
                'success' => true,
                'message' => "Cron job executed ({$status} in {$durationMs}ms).",
                'status' => $status,
                'exit_code' => $exitCode,
                'duration_ms' => $durationMs,
                'output' => $outputStr,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to execute cron job: ' . $e->getMessage()];
        }
    }

    /**
     * Get execution history logs for a specific cron job.
     */
    public function getJobLogs(int $id, int $limit = 25): array
    {
        try {
            $job = CronJob::findOrFail($id);
            $logs = $job->logs()->limit($limit)->get()->map(function (CronJobLog $log) {
                return [
                    'id' => $log->id,
                    'executed_at' => $log->executed_at->format('M d, Y H:i:s'),
                    'executed_formatted' => $log->executed_at->diffForHumans(),
                    'status' => $log->status,
                    'duration_ms' => $log->duration_ms,
                    'exit_code' => $log->exit_code,
                    'output' => $log->output,
                ];
            });

            return [
                'job_id' => $job->id,
                'job_title' => $job->title,
                'logs' => $logs,
            ];
        } catch (\Throwable $e) {
            return ['job_id' => $id, 'logs' => []];
        }
    }
}
