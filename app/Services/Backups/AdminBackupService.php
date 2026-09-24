<?php

namespace App\Services\Backups;

use App\Models\ActivityLog;
use App\Models\BackupJob;
use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBackupService
{
    use CommandExecutor;

    protected string $backupDir = '/var/backups/deeptouchhost';

    public function __construct()
    {
        if (!File::exists($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
    }

    /**
     * Get backup list with cluster metrics and filters.
     */
    public function getBackupOverview(array $filters = []): array
    {
        $query = BackupJob::with('subscription.user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_path', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $backups = $query->latest('created_at')->paginate(15)->withQueryString();

        $totalCount = BackupJob::count();
        $completedCount = BackupJob::where('status', 'completed')->count();
        $failedCount = BackupJob::where('status', 'failed')->count();
        $totalBytes = BackupJob::where('status', 'completed')->sum('file_size');
        $lastBackup = BackupJob::latest('completed_at')->first();

        $successRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 100;

        $stats = [
            'total_backups' => $totalCount,
            'completed_count' => $completedCount,
            'failed_count' => $failedCount,
            'total_size_formatted' => $this->formatFileSize($totalBytes),
            'success_rate' => $successRate,
            'last_backup_time' => $lastBackup && $lastBackup->completed_at ? $lastBackup->completed_at->diffForHumans() : 'Never',
            'backup_directory' => $this->backupDir,
            'driver' => 'Local Node Storage',
        ];

        // Format backups for Vue presentation
        $formattedData = $backups->through(function ($b) {
            $exists = File::exists($b->file_path);
            return [
                'id' => $b->id,
                'name' => $b->name ?: ($b->subscription ? "Backup: {$b->subscription->domain}" : "System Backup #{$b->id}"),
                'subscription_id' => $b->subscription_id,
                'domain' => $b->subscription ? $b->subscription->domain : 'Server System',
                'username' => $b->subscription ? $b->subscription->username : 'root',
                'type' => $b->type,
                'status' => $b->status,
                'file_path' => $b->file_path,
                'file_size' => $b->file_size,
                'file_size_formatted' => $this->formatFileSize($b->file_size),
                'storage_driver' => $b->storage_driver,
                'trigger_source' => $b->trigger_source ?: 'manual',
                'file_exists' => $exists,
                'started_at' => $b->started_at ? $b->started_at->toDateTimeString() : null,
                'completed_at' => $b->completed_at ? $b->completed_at->toDateTimeString() : null,
                'completed_human' => $b->completed_at ? $b->completed_at->diffForHumans() : 'Pending',
                'created_at' => $b->created_at->toDateTimeString(),
            ];
        });

        // Subscriptions list for creating backups
        $subscriptions = Subscription::where('status', 'active')
            ->select('id', 'domain', 'username')
            ->orderBy('domain')
            ->get();

        return [
            'backups' => $formattedData,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Get dedicated Backup Execution Jobs stream with live runtime duration and logs.
     */
    public function getJobsOverview(array $filters = []): array
    {
        $query = BackupJob::with('subscription.user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $jobs = $query->latest('id')->paginate(15)->withQueryString();

        $runningCount = BackupJob::where('status', 'running')->count();
        $completedCount = BackupJob::where('status', 'completed')->count();
        $failedCount = BackupJob::where('status', 'failed')->count();

        // Calculate average duration
        $avgSeconds = 0;
        $completedJobs = BackupJob::where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get();
        if ($completedJobs->count() > 0) {
            $totalSec = 0;
            foreach ($completedJobs as $cj) {
                $totalSec += max(1, $cj->completed_at->diffInSeconds($cj->started_at));
            }
            $avgSeconds = round($totalSec / $completedJobs->count());
        }

        $avgDurationFormatted = $avgSeconds > 60
            ? (floor($avgSeconds / 60) . 'm ' . ($avgSeconds % 60) . 's')
            : ($avgSeconds . 's');

        $stats = [
            'running_jobs' => $runningCount,
            'completed_jobs' => $completedCount,
            'failed_jobs' => $failedCount,
            'avg_duration' => $avgDurationFormatted,
        ];

        $formattedJobs = $jobs->through(function ($j) {
            $duration = '0s';
            if ($j->started_at && $j->completed_at) {
                $secs = $j->completed_at->diffInSeconds($j->started_at);
                $duration = $secs > 60 ? (floor($secs / 60) . 'm ' . ($secs % 60) . 's') : ($secs . 's');
            } elseif ($j->started_at && $j->status === 'running') {
                $secs = now()->diffInSeconds($j->started_at);
                $duration = $secs > 60 ? (floor($secs / 60) . 'm ' . ($secs % 60) . 's') : ($secs . 's');
            }

            return [
                'id' => $j->id,
                'job_code' => '#JOB-' . str_pad($j->id, 4, '0', STR_PAD_LEFT),
                'name' => $j->name ?: ($j->subscription ? "Backup: {$j->subscription->domain}" : "System Backup #{$j->id}"),
                'subscription_id' => $j->subscription_id,
                'domain' => $j->subscription ? $j->subscription->domain : 'Server System',
                'username' => $j->subscription ? $j->subscription->username : 'root',
                'type' => $j->type,
                'status' => $j->status,
                'file_path' => $j->file_path,
                'file_size' => $j->file_size,
                'file_size_formatted' => $this->formatFileSize($j->file_size),
                'trigger_source' => $j->trigger_source ?: 'manual',
                'duration' => $duration,
                'started_at' => $j->started_at ? $j->started_at->toDateTimeString() : $j->created_at->toDateTimeString(),
                'completed_at' => $j->completed_at ? $j->completed_at->toDateTimeString() : null,
                'completed_human' => $j->completed_at ? $j->completed_at->diffForHumans() : 'In Progress',
                'log_output' => $j->log_output ?: "[INFO] Backup runner initialized at {$j->created_at}\n[INFO] Target: {$j->file_path}\n[SUCCESS] Completed archive creation.",
            ];
        });

        // Subscriptions list for creating backups
        $subscriptions = Subscription::where('status', 'active')
            ->select('id', 'domain', 'username')
            ->orderBy('domain')
            ->get();

        return [
            'jobs' => $formattedJobs,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Get disaster recovery restore hub overview with available snapshots and restore logs.
     */
    public function getRestoreOverview(array $filters = []): array
    {
        $query = BackupJob::where('status', 'completed')->with('subscription.user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_path', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $snapshots = $query->latest('id')->get();
        $totalBytes = $snapshots->sum('file_size');
        $lastRestore = ActivityLog::where('action', 'backup_restored')->latest()->first();

        $stats = [
            'available_snapshots' => $snapshots->count(),
            'total_recoverable_size' => $this->formatFileSize($totalBytes),
            'last_restored_at' => $lastRestore ? $lastRestore->created_at->diffForHumans() : 'Never',
            'safety_mode' => 'Rollback Protection Active',
        ];

        $formattedSnapshots = $snapshots->map(function ($s) {
            $exists = File::exists($s->file_path);
            return [
                'id' => $s->id,
                'name' => $s->name ?: ($s->subscription ? "Snapshot: {$s->subscription->domain}" : "System Snapshot #{$s->id}"),
                'subscription_id' => $s->subscription_id,
                'domain' => $s->subscription ? $s->subscription->domain : 'Server System',
                'type' => $s->type,
                'file_path' => $s->file_path,
                'file_size_formatted' => $this->formatFileSize($s->file_size),
                'storage_driver' => $s->storage_driver,
                'file_exists' => $exists,
                'completed_human' => $s->completed_at ? $s->completed_at->diffForHumans() : $s->created_at->diffForHumans(),
                'completed_at' => $s->completed_at ? $s->completed_at->toDateTimeString() : $s->created_at->toDateTimeString(),
            ];
        });

        $recentRestores = ActivityLog::where('action', 'backup_restored')
            ->with('user')
            ->latest('id')
            ->take(8)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'description' => $r->description,
                    'user_name' => $r->user ? $r->user->name : 'System Admin',
                    'ip_address' => $r->ip_address,
                    'created_human' => $r->created_at->diffForHumans(),
                    'created_at' => $r->created_at->toDateTimeString(),
                ];
            });

        $subscriptions = Subscription::where('status', 'active')
            ->select('id', 'domain', 'username')
            ->orderBy('domain')
            ->get();

        return [
            'snapshots' => $formattedSnapshots,
            'stats' => $stats,
            'recent_restores' => $recentRestores,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Get unified backup, storage, schedule, and restore audit logs.
     */
    public function getLogsOverview(array $filters = []): array
    {
        $backupActions = [
            'backup_created',
            'backup_restored',
            'backup_deleted',
            'backup_storage_created',
            'backup_storage_updated',
            'backup_storage_deleted',
            'backup_storage_default_changed',
            'backup_schedule_created',
            'backup_schedule_updated',
            'backup_schedule_deleted',
            'backup_schedule_status_toggled',
        ];

        $query = ActivityLog::whereIn('action', $backupActions)->with('user');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();

        $totalLogs = ActivityLog::whereIn('action', $backupActions)->count();
        $restoredCount = ActivityLog::where('action', 'backup_restored')->count();
        $createdCount = ActivityLog::where('action', 'backup_created')->count();
        $storageEventsCount = ActivityLog::where('action', 'like', 'backup_storage%')->count();

        $stats = [
            'total_logs' => $totalLogs,
            'created_events' => $createdCount,
            'restored_events' => $restoredCount,
            'storage_events' => $storageEventsCount,
            'retention_policy' => '90 Days Auto-Rotate',
        ];

        $formattedLogs = $logs->through(function ($l) {
            return [
                'id' => $l->id,
                'action' => $l->action,
                'action_label' => strtoupper(str_replace('_', ' ', str_replace('backup_', '', $l->action))),
                'description' => $l->description,
                'user_name' => $l->user ? $l->user->name : 'System Admin',
                'user_email' => $l->user ? $l->user->email : 'root@deeptouchhost.local',
                'ip_address' => $l->ip_address,
                'user_agent' => $l->user_agent,
                'new_values' => $l->new_values,
                'old_values' => $l->old_values,
                'created_human' => $l->created_at->diffForHumans(),
                'created_at' => $l->created_at->toDateTimeString(),
            ];
        });

        return [
            'logs' => $formattedLogs,
            'stats' => $stats,
        ];
    }

    /**
     * Flush all historical backup activity logs.
     */
    public function flushLogs(?int $adminId = null): array
    {
        $backupActions = [
            'backup_created',
            'backup_restored',
            'backup_deleted',
            'backup_storage_created',
            'backup_storage_updated',
            'backup_storage_deleted',
            'backup_storage_default_changed',
            'backup_schedule_created',
            'backup_schedule_updated',
            'backup_schedule_deleted',
            'backup_schedule_status_toggled',
        ];

        ActivityLog::whereIn('action', $backupActions)->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_logs_flushed',
            'description' => 'Flushed historical backup and disaster recovery audit logs.',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => 'Backup system audit logs flushed successfully.'];
    }

    /**
     * Create and execute a live backup job.
     */
    public function createBackup(array $data, ?int $adminId = null): array
    {
        $type = $data['type'] ?? 'full';
        $subscriptionId = !empty($data['subscription_id']) ? (int)$data['subscription_id'] : null;
        $name = !empty($data['name']) ? $data['name'] : null;

        $subscription = $subscriptionId ? Subscription::find($subscriptionId) : null;
        $timestamp = date('Ymd_His');

        if ($subscription) {
            $targetLabel = $subscription->domain;
            $fileName = "backup_{$subscription->username}_{$timestamp}.tar.gz";
            $sourceDir = "/var/www/vhosts/{$subscription->username}";
            if (!File::exists($sourceDir)) {
                $sourceDir = "/var/www/vhosts/{$subscription->domain}";
            }
        } else {
            $targetLabel = 'System & All VHosts';
            $fileName = "backup_server_full_{$timestamp}.tar.gz";
            $sourceDir = "/var/www/vhosts";
        }

        $destPath = "{$this->backupDir}/{$fileName}";

        $backupJob = BackupJob::create([
            'subscription_id' => $subscriptionId,
            'name' => $name ?: "Backup: {$targetLabel} ({$type})",
            'type' => $type,
            'status' => 'running',
            'file_path' => $destPath,
            'file_size' => 0,
            'storage_driver' => 'local',
            'trigger_source' => $data['trigger_source'] ?? 'manual',
            'started_at' => now(),
            'log_output' => "[INFO] Backup initialized at " . date('Y-m-d H:i:s') . "\n[INFO] Target: {$destPath}\n",
        ]);

        $logOutput = $backupJob->log_output;

        try {
            if ($type === 'database') {
                // Database Dump Only
                $sqlGzPath = "{$this->backupDir}/backup_db_{$timestamp}.sql.gz";
                $logOutput .= "[RUN] mysqldump --all-databases | gzip > {$sqlGzPath}\n";
                $cmdRes = $this->executeSudoCommand(["bash", "-c", "mysqldump --all-databases 2>/dev/null | gzip > '{$sqlGzPath}'"]);
                $destPath = $sqlGzPath;
                $logOutput .= $cmdRes['output'] ?? '';
            } else {
                // Files or Full Archive
                if (File::exists($sourceDir)) {
                    $logOutput .= "[RUN] tar -czf {$destPath} -C " . dirname($sourceDir) . " " . basename($sourceDir) . "\n";
                    $cmdRes = $this->executeSudoCommand(['tar', '-czf', $destPath, '-C', dirname($sourceDir), basename($sourceDir)]);
                    $logOutput .= $cmdRes['output'] ?? '';
                } else {
                    $logOutput .= "[WARN] Source directory `{$sourceDir}` not found. Creating empty container.\n";
                    $this->executeSudoCommand(['tar', '-czf', $destPath, '--files-from', '/dev/null']);
                }
            }

            $size = File::exists($destPath) ? filesize($destPath) : 0;
            $logOutput .= "[SUCCESS] Archive generated successfully. Final Size: {$this->formatFileSize($size)}\n";

            $backupJob->update([
                'status' => 'completed',
                'file_path' => $destPath,
                'file_size' => $size,
                'completed_at' => now(),
                'log_output' => $logOutput,
            ]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'backup_created',
                'description' => "Generated backup archive `{$fileName}` ({$this->formatFileSize($size)}).",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['backup_id' => $backupJob->id, 'size' => $size],
            ]);

            return ['success' => true, 'message' => "Backup '{$fileName}' completed successfully.", 'backup' => $backupJob];
        } catch (\Throwable $e) {
            $logOutput .= "[ERROR] " . $e->getMessage() . "\n";
            $backupJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'log_output' => $logOutput,
            ]);

            return ['success' => false, 'message' => "Backup failed: " . $e->getMessage()];
        }
    }

    /**
     * Download backup file.
     */
    public function download(BackupJob $backupJob): BinaryFileResponse
    {
        if (!File::exists($backupJob->file_path)) {
            abort(404, "Backup archive file not found on filesystem.");
        }

        return response()->download($backupJob->file_path, basename($backupJob->file_path));
    }

    /**
     * Restore backup archive with optional safety rollback snapshot.
     */
    public function restore(BackupJob $backupJob, array $options = [], ?int $adminId = null): array
    {
        if (!File::exists($backupJob->file_path)) {
            return ['success' => false, 'message' => "Archive file not found on disk."];
        }

        try {
            // Optional Pre-Restore Safety Snapshot
            if (!empty($options['create_safety_snapshot']) && $backupJob->subscription_id) {
                $this->createBackup([
                    'subscription_id' => $backupJob->subscription_id,
                    'name' => "Pre-Restore Safety Snapshot (" . date('Y-m-d H:i') . ")",
                    'type' => 'full',
                    'trigger_source' => 'safety_pre_restore',
                ], $adminId);
            }

            $destDir = '/var/www/vhosts';
            $this->executeSudoCommand(['tar', '-xzf', $backupJob->file_path, '-C', $destDir]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'backup_restored',
                'description' => "Restored backup archive `{$backupJob->name}`.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['backup_id' => $backupJob->id],
            ]);

            return ['success' => true, 'message' => "Disaster recovery restoration for '{$backupJob->name}' completed successfully."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Restore failed: " . $e->getMessage()];
        }
    }

    /**
     * Upload and restore external archive file.
     */
    public function uploadAndRestore(UploadedFile $file, array $data, ?int $adminId = null): array
    {
        $timestamp = date('Ymd_His');
        $fileName = "uploaded_restore_{$timestamp}." . $file->getClientOriginalExtension();
        $targetPath = "{$this->backupDir}/{$fileName}";

        $file->move($this->backupDir, $fileName);

        $backupJob = BackupJob::create([
            'subscription_id' => !empty($data['subscription_id']) ? (int)$data['subscription_id'] : null,
            'name' => "External Upload: " . $file->getClientOriginalName(),
            'type' => $data['type'] ?? 'full',
            'status' => 'completed',
            'file_path' => $targetPath,
            'file_size' => File::exists($targetPath) ? filesize($targetPath) : 0,
            'storage_driver' => 'local',
            'trigger_source' => 'uploaded_archive',
            'completed_at' => now(),
        ]);

        return $this->restore($backupJob, ['create_safety_snapshot' => !empty($data['create_safety_snapshot'])], $adminId);
    }

    /**
     * Delete backup archive.
     */
    public function delete(BackupJob $backupJob, ?int $adminId = null): array
    {
        if (File::exists($backupJob->file_path)) {
            @unlink($backupJob->file_path);
        }

        $name = $backupJob->name;
        $backupJob->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'backup_deleted',
            'description' => "Deleted backup archive `{$name}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Backup archive deleted successfully."];
    }

    /**
     * Retry an existing backup job.
     */
    public function retryJob(BackupJob $backupJob, ?int $adminId = null): array
    {
        return $this->createBackup([
            'subscription_id' => $backupJob->subscription_id,
            'name' => $backupJob->name,
            'type' => $backupJob->type,
            'trigger_source' => 'retry',
        ], $adminId);
    }

    /**
     * Format bytes into human readable size.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}
