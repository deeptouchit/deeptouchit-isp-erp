<?php

namespace App\Services\Queue;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueMonitorService
{
    /**
     * Get real-time queue health, pending count, failed count, and cron heartbeat.
     */
    public function getQueueHealth(): array
    {
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $jobBatches = Schema::hasTable('job_batches') ? DB::table('job_batches')->count() : 0;

        $lastHeartbeat = Setting::get('cron_last_heartbeat_at');
        $heartbeatStatus = 'inactive';
        $heartbeatDiffMinutes = null;

        if ($lastHeartbeat) {
            $heartbeatTime = Carbon::parse($lastHeartbeat);
            $heartbeatDiffMinutes = $heartbeatTime->diffInMinutes(now());
            $heartbeatStatus = $heartbeatDiffMinutes <= 3 ? 'healthy' : ($heartbeatDiffMinutes <= 10 ? 'delayed' : 'offline');
        }

        $workerStatus = $pendingJobs > 100 ? 'congested' : ($pendingJobs > 0 ? 'processing' : 'idle');

        return [
            'status' => $heartbeatStatus === 'healthy' ? 'operational' : 'degraded',
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'job_batches' => $jobBatches,
            'worker_status' => $workerStatus,
            'last_heartbeat_at' => $lastHeartbeat,
            'heartbeat_diff_minutes' => $heartbeatDiffMinutes,
            'heartbeat_status' => $heartbeatStatus,
        ];
    }

    /**
     * Get list of failed jobs with decoded payload details.
     */
    public function getFailedJobs(int $limit = 50): array
    {
        if (!Schema::hasTable('failed_jobs')) {
            return [];
        }

        $records = DB::table('failed_jobs')->orderBy('id', 'desc')->limit($limit)->get();
        $formatted = [];

        foreach ($records as $row) {
            $payload = json_decode($row->payload, true) ?: [];
            $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown Job');

            $formatted[] = [
                'id' => $row->id,
                'uuid' => $row->uuid ?? null,
                'connection' => $row->connection,
                'queue' => $row->queue,
                'name' => class_basename($displayName),
                'full_name' => $displayName,
                'failed_at' => $row->failed_at,
                'exception_summary' => substr($row->exception, 0, 180) . '...',
                'full_exception' => $row->exception,
            ];
        }

        return $formatted;
    }

    /**
     * Retry a single failed job by ID or UUID.
     */
    public function retryJob(string|int $id): bool
    {
        try {
            $exitCode = Artisan::call('queue:retry', ['id' => [(string)$id]]);
            return $exitCode === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll(): bool
    {
        try {
            $exitCode = Artisan::call('queue:retry', ['id' => ['all']]);
            return $exitCode === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete / Forget a single failed job.
     */
    public function forgetJob(string|int $id): bool
    {
        try {
            $exitCode = Artisan::call('queue:forget', ['id' => (string)$id]);
            if ($exitCode === 0) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fallback to database delete
        }

        if (Schema::hasTable('failed_jobs')) {
            return DB::table('failed_jobs')->where('id', $id)->orWhere('uuid', (string)$id)->delete() > 0;
        }

        return false;
    }

    /**
     * Flush / Delete all failed jobs.
     */
    public function flushAll(): bool
    {
        try {
            $exitCode = Artisan::call('queue:flush');
            if ($exitCode === 0) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->truncate();
            return true;
        }

        return false;
    }
}
