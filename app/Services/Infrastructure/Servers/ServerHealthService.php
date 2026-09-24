<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerHealthInterface;
use App\Data\Infrastructure\Servers\ServerHealthResult;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Models\Server;
use App\Models\ServerService;

class ServerHealthService implements ServerHealthInterface
{
    protected ServerEventService $eventService;

    // Thresholds
    protected float $cpuWarningThreshold = 85.0;
    protected float $cpuCriticalThreshold = 95.0;
    protected float $ramWarningThreshold = 90.0;
    protected float $diskWarningThreshold = 90.0;

    public function __construct(ServerEventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Check health status of a server node based on telemetry and services.
     */
    public function checkHealth(Server $server): ServerHealthResult
    {
        $server->refresh();
        $isOnline = in_array($server->status, [ServerStatus::ACTIVE, ServerStatus::ONLINE, ServerStatus::VERIFIED]);

        if (!$isOnline) {
            $healthStatus = ServerHealthStatus::OFFLINE;
            $server->update([
                'health_status' => $healthStatus,
                'last_health_check_at' => now(),
            ]);

            return new ServerHealthResult(
                status: $healthStatus,
                isOnline: false,
                cpuWarning: false,
                ramWarning: false,
                diskWarning: false,
                servicesRunningCount: 0,
                servicesFailedCount: 0,
                message: "Server is currently {$server->status->value}",
                checkedAt: now()
            );
        }

        // 1. Check latest metrics
        $latestMetric = $server->latestMetric;
        $cpuUsage = $latestMetric ? $latestMetric->cpu_usage : 0.0;
        $ramPercent = ($server->total_ram > 0) ? ($server->used_ram / $server->total_ram) * 100 : 0.0;
        $diskPercent = ($server->total_disk > 0) ? ($server->used_disk / $server->total_disk) * 100 : 0.0;

        $cpuWarning = $cpuUsage >= $this->cpuWarningThreshold;
        $cpuCritical = $cpuUsage >= $this->cpuCriticalThreshold;
        $ramWarning = $ramPercent >= $this->ramWarningThreshold;
        $diskWarning = $diskPercent >= $this->diskWarningThreshold;

        // 2. Check failed services
        $runningCount = ServerService::where('server_id', $server->id)->where('status', ServerServiceStatus::RUNNING)->count();
        $failedCount = ServerService::where('server_id', $server->id)->where('status', ServerServiceStatus::FAILED)->count();

        // 3. Determine Overall Health Status
        if ($cpuCritical || $failedCount > 1) {
            $healthStatus = ServerHealthStatus::CRITICAL;
            $this->eventService->recordEvent(
                $server,
                ServerEventType::HEALTH_CRITICAL,
                "Critical health detected on server #{$server->id}: CPU={$cpuUsage}%, Failed Services={$failedCount}",
                ServerEventSeverity::CRITICAL,
                ['cpu' => $cpuUsage, 'failed_services' => $failedCount]
            );
        } elseif ($cpuWarning || $ramWarning || $diskWarning || $failedCount > 0) {
            $healthStatus = ServerHealthStatus::WARNING;
            $this->eventService->recordEvent(
                $server,
                ServerEventType::HEALTH_WARNING,
                "Health warning on server #{$server->id}: CPU={$cpuUsage}%, RAM={$ramPercent}%, Disk={$diskPercent}%",
                ServerEventSeverity::WARNING,
                ['cpu' => $cpuUsage, 'ram' => $ramPercent, 'disk' => $diskPercent]
            );
        } else {
            $healthStatus = ServerHealthStatus::HEALTHY;
        }

        $server->update([
            'health_status' => $healthStatus,
            'last_health_check_at' => now(),
        ]);

        return new ServerHealthResult(
            status: $healthStatus,
            isOnline: true,
            cpuWarning: $cpuWarning,
            ramWarning: $ramWarning,
            diskWarning: $diskWarning,
            servicesRunningCount: $runningCount,
            servicesFailedCount: $failedCount,
            message: "Health check completed. Status: {$healthStatus->value}",
            checkedAt: now()
        );
    }
}
