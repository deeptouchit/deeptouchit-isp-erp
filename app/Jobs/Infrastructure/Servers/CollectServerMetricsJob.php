<?php

namespace App\Jobs\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerMetricsInterface;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectServerMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public Server $server,
        public array $telemetryPayload
    ) {}

    public function handle(ServerMetricsInterface $metricsService): void
    {
        Log::info("[CollectServerMetricsJob] Ingesting metrics for Server #{$this->server->id}");
        $metricsService->recordMetrics($this->server, $this->telemetryPayload);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("[CollectServerMetricsJob] Failed ingesting metrics for Server #{$this->server->id}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
