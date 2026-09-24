<?php

namespace App\Services\Automation;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class QueueAutomationService
{
    use CommandExecutor;

    /**
     * Get real-time overview of queue workers, pending jobs, failed jobs, and pools.
     */
    public function getQueueOverview(): array
    {
        // 1. Scan live worker and horizon processes
        $workers = [];
        $masterSupervisor = null;
        $totalRss = 0;

        try {
            $psOutput = @shell_exec("ps -eo pid,user,%cpu,%mem,rss,args 2>/dev/null");
            if ($psOutput) {
                foreach (explode("\n", trim($psOutput)) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_contains($line, 'grep')) {
                        continue;
                    }

                    if (str_contains($line, 'artisan horizon') && !str_contains($line, 'horizon:work') && !str_contains($line, 'horizon:supervisor')) {
                        $parts = preg_split('/\s+/', $line, 6);
                        if (count($parts) >= 6) {
                            $masterSupervisor = [
                                'pid' => (int)$parts[0],
                                'user' => $parts[1],
                                'cpu' => (float)$parts[2],
                                'mem' => (float)$parts[3],
                                'rss_mb' => round((int)$parts[4] / 1024, 1),
                                'command' => 'php artisan horizon',
                                'status' => 'active',
                            ];
                        }
                    } elseif (str_contains($line, 'artisan horizon:work') || str_contains($line, 'artisan queue:work')) {
                        $parts = preg_split('/\s+/', $line, 6);
                        if (count($parts) >= 6) {
                            $args = $parts[5];
                            $queue = 'default';
                            if (preg_match('/--queue=([^\s]+)/', $args, $qm)) {
                                $queue = $qm[1];
                            }

                            $rssMb = round((int)$parts[4] / 1024, 1);
                            $totalRss += $rssMb;

                            $workers[] = [
                                'pid' => (int)$parts[0],
                                'user' => $parts[1],
                                'cpu' => (float)$parts[2],
                                'mem' => (float)$parts[3],
                                'rss_mb' => $rssMb,
                                'queue' => $queue,
                                'command' => $args,
                                'status' => 'processing',
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // 2. Discover Queue Pools & Pending Counts
        $knownQueues = ['default', 'high', 'medium', 'low', 'backup', 'email'];
        $queuePools = [];

        // Check Redis Queues if Redis is reachable
        $redisActive = false;
        try {
            $redis = Redis::connection();
            $redis->ping();
            $redisActive = true;
        } catch (\Throwable $e) {
            $redisActive = false;
        }

        // Count pending database jobs
        $dbPendingCount = 0;
        $dbJobsByQueue = [];
        try {
            $dbPendingCount = DB::table('jobs')->count();
            $counts = DB::table('jobs')->select('queue', DB::raw('count(*) as total'))->groupBy('queue')->get();
            foreach ($counts as $c) {
                $dbJobsByQueue[$c->queue] = (int)$c->total;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        foreach ($knownQueues as $qName) {
            $assignedWorkers = count(array_filter($workers, fn($w) => $w['queue'] === $qName || str_contains($w['queue'], $qName)));
            $pendingInQueue = $dbJobsByQueue[$qName] ?? 0;

            if ($redisActive) {
                try {
                    $rPending = (int)Redis::llen("queues:{$qName}");
                    $pendingInQueue += $rPending;
                } catch (\Throwable $e) {
                    // skip
                }
            }

            $queuePools[] = [
                'name' => $qName,
                'assigned_workers' => $assignedWorkers,
                'pending_jobs' => $pendingInQueue,
                'status' => $assignedWorkers > 0 ? 'Active' : 'Idle',
                'priority' => match ($qName) {
                    'high' => 'High (Tier 1)',
                    'email' => 'Real-Time (Tier 1)',
                    'default' => 'Normal (Tier 2)',
                    'medium' => 'Normal (Tier 2)',
                    'backup' => 'Background (Tier 3)',
                    'low' => 'Low (Tier 3)',
                    default => 'Standard',
                },
            ];
        }

        // 3. Fetch Pending Jobs from DB (up to 20)
        $pendingJobsList = [];
        try {
            $pendingJobsList = DB::table('jobs')->limit(20)->get()->map(function ($j) {
                $displayName = 'Custom Job';
                try {
                    $payload = json_decode($j->payload, true);
                    $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Anonymous Job');
                } catch (\Throwable $e) {
                    // skip
                }

                return [
                    'id' => $j->id,
                    'queue' => $j->queue,
                    'name' => class_basename($displayName),
                    'full_class' => $displayName,
                    'attempts' => $j->attempts,
                    'created_at' => date('Y-m-d H:i:s', $j->created_at),
                ];
            });
        } catch (\Throwable $e) {
            // fallback
        }

        // 4. Fetch Failed Jobs from DB (up to 30)
        $failedJobsList = [];
        $totalFailed = 0;
        try {
            $totalFailed = DB::table('failed_jobs')->count();
            $failedJobsList = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($fj) {
                    $displayName = 'Failed Job';
                    try {
                        $payload = json_decode($fj->payload, true);
                        $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Anonymous Job');
                    } catch (\Throwable $e) {
                        // skip
                    }

                    return [
                        'id' => $fj->id,
                        'uuid' => $fj->uuid,
                        'connection' => $fj->connection,
                        'queue' => $fj->queue,
                        'name' => class_basename($displayName),
                        'full_class' => $displayName,
                        'failed_at' => (string)$fj->failed_at,
                        'exception_preview' => mb_substr((string)$fj->exception, 0, 180) . '...',
                        'exception_full' => (string)$fj->exception,
                    ];
                });
        } catch (\Throwable $e) {
            // fallback
        }

        // 5. Fetch Job Batches
        $jobBatches = [];
        try {
            $jobBatches = DB::table('job_batches')
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get()
                ->map(function ($b) {
                    $progress = ($b->total_jobs > 0)
                        ? round((($b->total_jobs - $b->pending_jobs) / $b->total_jobs) * 100, 1)
                        : 100;

                    return [
                        'id' => $b->id,
                        'name' => $b->name,
                        'total_jobs' => $b->total_jobs,
                        'pending_jobs' => $b->pending_jobs,
                        'failed_jobs' => $b->failed_jobs,
                        'progress' => $progress,
                        'created_at' => date('Y-m-d H:i:s', $b->created_at),
                        'finished_at' => $b->finished_at ? date('Y-m-d H:i:s', $b->finished_at) : null,
                        'status' => $b->finished_at ? ($b->failed_jobs > 0 ? 'Failed' : 'Completed') : 'Processing',
                    ];
                });
        } catch (\Throwable $e) {
            // fallback
        }

        $stats = [
            'total_workers' => count($workers),
            'supervisor_active' => $masterSupervisor !== null,
            'supervisor_pid' => $masterSupervisor['pid'] ?? null,
            'total_memory_mb' => round($totalRss + ($masterSupervisor['rss_mb'] ?? 0), 1),
            'pending_jobs' => $dbPendingCount,
            'failed_jobs' => $totalFailed,
            'pools_count' => count($queuePools),
            'driver' => config('queue.default', 'database'),
            'redis_active' => $redisActive,
        ];

        return [
            'stats' => $stats,
            'supervisor' => $masterSupervisor,
            'workers' => $workers,
            'pools' => $queuePools,
            'pending_jobs' => $pendingJobsList,
            'failed_jobs' => $failedJobsList,
            'batches' => $jobBatches,
        ];
    }

    /**
     * Retry a specific failed job by UUID.
     */
    public function retryFailedJob(string $uuid, ?int $adminId = null): array
    {
        try {
            $exitCode = Artisan::call('queue:retry', ['id' => [$uuid]]);
            $output = Artisan::output();

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_job_retried',
                'description' => "Retried failed queue job `{$uuid}`.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['uuid' => $uuid],
                'new_values' => ['exit_code' => $exitCode, 'output' => trim($output)],
            ]);

            return ['success' => true, 'message' => "Failed job {$uuid} pushed back to queue for retry."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to retry job: ' . $e->getMessage()];
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAllFailedJobs(?int $adminId = null): array
    {
        try {
            $exitCode = Artisan::call('queue:retry', ['id' => ['all']]);
            $output = Artisan::output();

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_all_jobs_retried',
                'description' => "Retried all failed queue jobs in the system.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['exit_code' => $exitCode],
            ]);

            return ['success' => true, 'message' => 'All failed jobs have been pushed back to active queues.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to retry all jobs: ' . $e->getMessage()];
        }
    }

    /**
     * Forget / delete a specific failed job.
     */
    public function forgetFailedJob(string $uuid, ?int $adminId = null): array
    {
        try {
            $exitCode = Artisan::call('queue:forget', ['id' => $uuid]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_job_forgotten',
                'description' => "Deleted failed queue job record `{$uuid}`.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['uuid' => $uuid],
                'new_values' => [],
            ]);

            return ['success' => true, 'message' => "Failed job {$uuid} permanently removed."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to forget job: ' . $e->getMessage()];
        }
    }

    /**
     * Flush all failed jobs.
     */
    public function flushAllFailedJobs(?int $adminId = null): array
    {
        try {
            $exitCode = Artisan::call('queue:flush');

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_all_jobs_flushed',
                'description' => "Flushed and purged all failed jobs from the database.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => [],
            ]);

            return ['success' => true, 'message' => 'All failed job records have been flushed and purged.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to flush failed jobs: ' . $e->getMessage()];
        }
    }

    /**
     * Gracefully restart queue workers.
     */
    public function restartQueueWorkers(?int $adminId = null): array
    {
        try {
            Artisan::call('queue:restart');

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_workers_restarted',
                'description' => "Gracefully broadcasted restart signal to all active queue workers.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => [],
            ]);

            return ['success' => true, 'message' => 'Queue worker restart broadcast signal dispatched successfully.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to restart workers: ' . $e->getMessage()];
        }
    }

    /**
     * Dispatch a test job to verify worker throughput.
     */
    public function dispatchTestJob(string $queue = 'default', ?int $adminId = null): array
    {
        try {
            // Dispatch closure job
            dispatch(function () {
                \Illuminate\Support\Facades\Log::info("DeepTouchHost Queue Worker Benchmark Test completed successfully.");
            })->onQueue($queue);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'queue_test_job_dispatched',
                'description' => "Dispatched diagnostic test job to `{$queue}` queue.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['queue' => $queue],
            ]);

            return ['success' => true, 'message' => "Test job dispatched to `{$queue}` queue. Workers will consume it immediately."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to dispatch test job: ' . $e->getMessage()];
        }
    }
}
