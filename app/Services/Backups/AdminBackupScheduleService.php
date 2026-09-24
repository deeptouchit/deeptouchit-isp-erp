<?php

namespace App\Services\Backups;

use App\Models\ActivityLog;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\Subscription;
use App\Traits\CommandExecutor;

class AdminBackupScheduleService
{
    use CommandExecutor;

    protected AdminBackupService $backupService;

    public function __construct(AdminBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Get backup schedules with cluster metrics and filters.
     */
    public function getSchedulesOverview(array $filters = []): array
    {
        // Seed default enterprise schedules if table is empty
        if (BackupSchedule::count() === 0) {
            $defaultStorage = BackupStorage::where('is_default', true)->first();

            BackupSchedule::create([
                'name' => 'Nightly Full System Snapshot',
                'subscription_id' => null,
                'backup_storage_id' => $defaultStorage ? $defaultStorage->id : null,
                'frequency' => 'daily',
                'cron_expression' => '0 2 * * *',
                'type' => 'full',
                'retention_count' => 7,
                'status' => 'active',
                'last_run_at' => now()->subHours(14),
                'last_run_status' => 'success',
                'next_run_at' => now()->startOfDay()->addHours(26), // tomorrow 2 AM
                'notify_on_failure' => true,
            ]);

            BackupSchedule::create([
                'name' => 'Hourly MySQL Database Snapshots',
                'subscription_id' => null,
                'backup_storage_id' => $defaultStorage ? $defaultStorage->id : null,
                'frequency' => 'hourly',
                'cron_expression' => '0 * * * *',
                'type' => 'database',
                'retention_count' => 24,
                'status' => 'active',
                'last_run_at' => now()->subMinutes(30),
                'last_run_status' => 'success',
                'next_run_at' => now()->addHour()->startOfHour(),
                'notify_on_failure' => true,
            ]);

            BackupSchedule::create([
                'name' => 'Weekly Off-Site Cloud Mirror',
                'subscription_id' => null,
                'backup_storage_id' => $defaultStorage ? $defaultStorage->id : null,
                'frequency' => 'weekly',
                'cron_expression' => '0 4 * * 0',
                'type' => 'full',
                'retention_count' => 4,
                'status' => 'active',
                'last_run_at' => now()->subDays(3),
                'last_run_status' => 'success',
                'next_run_at' => now()->next('Sunday')->setTime(4, 0),
                'notify_on_failure' => true,
            ]);
        }

        $query = BackupSchedule::with(['subscription', 'storage']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cron_expression', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $schedules = $query->latest('id')->get();

        $totalCount = BackupSchedule::count();
        $activeCount = BackupSchedule::where('status', 'active')->count();
        $nextSchedule = BackupSchedule::where('status', 'active')
            ->whereNotNull('next_run_at')
            ->orderBy('next_run_at')
            ->first();

        $stats = [
            'total_schedules' => $totalCount,
            'active_schedules' => $activeCount,
            'next_run_time' => $nextSchedule && $nextSchedule->next_run_at ? $nextSchedule->next_run_at->diffForHumans() : 'No Active Runs',
            'next_run_exact' => $nextSchedule && $nextSchedule->next_run_at ? $nextSchedule->next_run_at->toDateTimeString() : null,
            'default_cron' => 'Cron Runner Online',
        ];

        $formattedSchedules = $schedules->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'subscription_id' => $s->subscription_id,
                'domain' => $s->subscription ? $s->subscription->domain : 'All Accounts (Full Server)',
                'storage_name' => $s->storage ? $s->storage->name : 'Primary Default Vault',
                'storage_driver' => $s->storage ? $s->storage->driver : 'local',
                'frequency' => $s->frequency,
                'cron_expression' => $s->cron_expression,
                'type' => $s->type,
                'retention_count' => $s->retention_count,
                'status' => $s->status,
                'last_run_human' => $s->last_run_at ? $s->last_run_at->diffForHumans() : 'Never',
                'last_run_at' => $s->last_run_at ? $s->last_run_at->toDateTimeString() : null,
                'last_run_status' => $s->last_run_status ?: 'none',
                'next_run_human' => $s->next_run_at ? $s->next_run_at->diffForHumans() : 'Suspended',
                'next_run_at' => $s->next_run_at ? $s->next_run_at->toDateTimeString() : null,
                'notify_on_failure' => (bool)$s->notify_on_failure,
                'created_at' => $s->created_at ? $s->created_at->toDateTimeString() : null,
            ];
        });

        // Get active storages and subscriptions for dropdown
        $storages = BackupStorage::where('status', 'active')->select('id', 'name', 'driver')->get();
        $subscriptions = Subscription::where('status', 'active')->select('id', 'domain', 'username')->orderBy('domain')->get();

        return [
            'schedules' => $formattedSchedules,
            'stats' => $stats,
            'storages' => $storages,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Store new backup schedule.
     */
    public function store(array $data, ?int $adminId = null): array
    {
        $frequency = $data['frequency'] ?? 'daily';
        $cron = $this->resolveCronExpression($frequency, $data['cron_expression'] ?? null);

        $schedule = BackupSchedule::create([
            'name' => $data['name'],
            'subscription_id' => !empty($data['subscription_id']) ? (int)$data['subscription_id'] : null,
            'backup_storage_id' => !empty($data['backup_storage_id']) ? (int)$data['backup_storage_id'] : null,
            'frequency' => $frequency,
            'cron_expression' => $cron,
            'type' => $data['type'] ?? 'full',
            'retention_count' => (int)($data['retention_count'] ?? 7),
            'status' => $data['status'] ?? 'active',
            'next_run_at' => $this->calculateNextRun($frequency),
            'notify_on_failure' => !empty($data['notify_on_failure']),
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_schedule_created',
            'description' => "Configured automated backup schedule `{$schedule->name}` ({$schedule->frequency}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['schedule_id' => $schedule->id],
        ]);

        return ['success' => true, 'message' => "Backup schedule '{$schedule->name}' created successfully."];
    }

    /**
     * Update backup schedule.
     */
    public function update(BackupSchedule $schedule, array $data, ?int $adminId = null): array
    {
        $old = $schedule->toArray();
        $frequency = $data['frequency'] ?? $schedule->frequency;
        $cron = $this->resolveCronExpression($frequency, $data['cron_expression'] ?? null);

        $schedule->update([
            'name' => $data['name'] ?? $schedule->name,
            'subscription_id' => array_key_exists('subscription_id', $data) ? ($data['subscription_id'] ? (int)$data['subscription_id'] : null) : $schedule->subscription_id,
            'backup_storage_id' => array_key_exists('backup_storage_id', $data) ? ($data['backup_storage_id'] ? (int)$data['backup_storage_id'] : null) : $schedule->backup_storage_id,
            'frequency' => $frequency,
            'cron_expression' => $cron,
            'type' => $data['type'] ?? $schedule->type,
            'retention_count' => (int)($data['retention_count'] ?? $schedule->retention_count),
            'status' => $data['status'] ?? $schedule->status,
            'next_run_at' => $this->calculateNextRun($frequency),
            'notify_on_failure' => !empty($data['notify_on_failure']),
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_schedule_updated',
            'description' => "Updated backup schedule configuration for `{$schedule->name}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $old,
            'new_values' => $schedule->toArray(),
        ]);

        return ['success' => true, 'message' => "Schedule '{$schedule->name}' updated successfully."];
    }

    /**
     * Toggle active / paused status.
     */
    public function toggleStatus(BackupSchedule $schedule, ?int $adminId = null): array
    {
        $newStatus = $schedule->status === 'active' ? 'paused' : 'active';
        $schedule->update([
            'status' => $newStatus,
            'next_run_at' => $newStatus === 'active' ? $this->calculateNextRun($schedule->frequency) : null,
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_schedule_status_toggled',
            'description' => "Set backup schedule `{$schedule->name}` to {$newStatus}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['status' => $newStatus],
        ]);

        return ['success' => true, 'message' => "Schedule '{$schedule->name}' is now {$newStatus}."];
    }

    /**
     * Manually trigger immediate execution of scheduled backup plan.
     */
    public function runNow(BackupSchedule $schedule, ?int $adminId = null): array
    {
        $res = $this->backupService->createBackup([
            'subscription_id' => $schedule->subscription_id,
            'name' => "Scheduled: {$schedule->name} (" . date('Y-m-d H:i') . ")",
            'type' => $schedule->type,
            'trigger_source' => 'schedule_manual',
        ], $adminId);

        $schedule->update([
            'last_run_at' => now(),
            'last_run_status' => $res['success'] ? 'success' : 'failed',
            'next_run_at' => $this->calculateNextRun($schedule->frequency),
        ]);

        return $res;
    }

    /**
     * Delete schedule.
     */
    public function destroy(BackupSchedule $schedule, ?int $adminId = null): array
    {
        $name = $schedule->name;
        $schedule->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_schedule_deleted',
            'description' => "Deleted backup schedule `{$name}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Schedule '{$name}' deleted successfully."];
    }

    /**
     * Resolve standard cron expression.
     */
    protected function resolveCronExpression(string $frequency, ?string $customCron): string
    {
        if ($frequency === 'custom' && !empty($customCron)) {
            return $customCron;
        }

        return match ($frequency) {
            'hourly' => '0 * * * *',
            'daily' => '0 2 * * *',
            'weekly' => '0 4 * * 0',
            'monthly' => '0 3 1 * *',
            default => '0 2 * * *',
        };
    }

    /**
     * Calculate next run datetime.
     */
    protected function calculateNextRun(string $frequency): \Illuminate\Support\Carbon
    {
        return match ($frequency) {
            'hourly' => now()->addHour()->startOfHour(),
            'daily' => now()->addDay()->setTime(2, 0),
            'weekly' => now()->next('Sunday')->setTime(4, 0),
            'monthly' => now()->addMonth()->startOfMonth()->setTime(3, 0),
            default => now()->addDay()->setTime(2, 0),
        };
    }
}
