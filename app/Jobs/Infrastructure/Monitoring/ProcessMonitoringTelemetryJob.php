<?php

namespace App\Jobs\Infrastructure\Monitoring;

use App\Models\Server;
use App\Services\Infrastructure\Monitoring\AlertRuleEngine;
use App\Services\Infrastructure\Monitoring\TelemetryNormalizer;
use App\Services\Infrastructure\Servers\ServerAgentService;
use App\Services\Infrastructure\Servers\ServerHealthService;
use App\Services\Infrastructure\Servers\ServerMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonitoringTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    protected int $serverId;
    protected array $rawPayload;
    protected ?string $agentVersion;

    public function __construct(int $serverId, array $rawPayload, ?string $agentVersion = null)
    {
        $this->serverId = $serverId;
        $this->rawPayload = $rawPayload;
        $this->agentVersion = $agentVersion;
    }

    public function handle(
        TelemetryNormalizer $normalizer,
        ServerMetricsService $metricsService,
        ServerAgentService $agentService,
        ServerHealthService $healthService,
        AlertRuleEngine $alertEngine
    ): void {
        $server = Server::find($this->serverId);
        if (!$server) {
            return;
        }

        try {
            // 1. Normalize telemetry
            $normalized = $normalizer->normalize($this->rawPayload);

            // 2. Record agent heartbeat
            $agentService->recordHeartbeat($server, $this->agentVersion);

            // 3. Persist normalized time-series metric snapshot
            $metricsService->recordMetrics($server, $normalized['metrics_data']);

            // 4. Update services state if services provided
            if (!empty($normalized['services'])) {
                foreach ($normalized['services'] as $svcName => $state) {
                    $server->services()->updateOrCreate(
                        ['service_name' => $svcName],
                        [
                            'display_name' => ucfirst(str_replace(['-fpm', '_'], [' FPM', ' '], $svcName)),
                            'status' => $state,
                            'last_checked_at' => now(),
                        ]
                    );
                }
            }

            // 5. Evaluate Composite Server Health
            $healthService->checkHealth($server);

            // 6. Evaluate Alert Thresholds & State Machine
            $alertEngine->evaluate($server, $normalized);

        } catch (\Throwable $e) {
            Log::error("Failed processing monitoring telemetry for server #{$this->serverId}: " . $e->getMessage(), [
                'server_id' => $this->serverId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
