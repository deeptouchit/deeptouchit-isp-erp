<?php

namespace App\Services\Infrastructure\Monitoring;

use App\Models\ServerMetric;
use App\Models\ServerMetricAggregate;
use Illuminate\Support\Facades\Log;

class MetricRetentionService
{
    /**
     * Prune expired raw telemetry records in safe chunks.
     */
    public function pruneExpiredRawMetrics(): int
    {
        $days = config('monitoring.retention.raw_days', 7);
        $chunkSize = config('monitoring.retention.prune_chunk_size', 1000);
        $threshold = now()->subDays($days);

        $totalDeleted = 0;

        do {
            $ids = ServerMetric::where('recorded_at', '<', $threshold)
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = ServerMetric::whereIn('id', $ids)->delete();
            $totalDeleted += $deleted;
        } while ($deleted >= $chunkSize);

        if ($totalDeleted > 0) {
            Log::info("Metric Retention: Pruned {$totalDeleted} raw telemetry samples older than {$days} days.");
        }

        return $totalDeleted;
    }

    /**
     * Prune expired aggregates.
     */
    public function pruneExpiredAggregates(): int
    {
        $hourlyDays = config('monitoring.retention.hourly_days', 30);
        $dailyDays = config('monitoring.retention.daily_days', 365);

        $deletedHourly = ServerMetricAggregate::where('interval', '1h')
            ->where('period_start', '<', now()->subDays($hourlyDays))
            ->delete();

        $deletedDaily = ServerMetricAggregate::where('interval', '1d')
            ->where('period_start', '<', now()->subDays($dailyDays))
            ->delete();

        return $deletedHourly + $deletedDaily;
    }
}
