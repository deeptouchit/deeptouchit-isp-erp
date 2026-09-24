<?php

namespace App\Jobs\Infrastructure\Monitoring;

use App\Services\Infrastructure\Monitoring\MetricAggregationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateServerMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MetricAggregationService $aggregationService): void
    {
        // 1. Aggregate for previous hour
        $previousHour = now()->subHour()->startOfHour();
        $aggregationService->aggregateHourly($previousHour);

        // 2. Aggregate for previous day
        $previousDay = now()->subDay()->startOfDay();
        $aggregationService->aggregateDaily($previousDay);
    }
}
