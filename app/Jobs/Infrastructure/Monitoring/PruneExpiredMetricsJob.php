<?php

namespace App\Jobs\Infrastructure\Monitoring;

use App\Services\Infrastructure\Monitoring\MetricRetentionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PruneExpiredMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MetricRetentionService $retentionService): void
    {
        $retentionService->pruneExpiredRawMetrics();
        $retentionService->pruneExpiredAggregates();
    }
}
