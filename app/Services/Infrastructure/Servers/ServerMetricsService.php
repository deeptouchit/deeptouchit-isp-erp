<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerMetricsInterface;
use App\Data\Infrastructure\Servers\ServerMetricsData;
use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Support\Facades\DB;

class ServerMetricsService implements ServerMetricsInterface
{
    /**
     * Ingest, normalize, validate and persist high-frequency telemetry.
     */
    public function recordMetrics(Server $server, ServerMetricsData|array $telemetry): ServerMetric
    {
        $metricsData = $telemetry instanceof ServerMetricsData 
            ? $telemetry 
            : ServerMetricsData::fromPayload($telemetry);

        return DB::transaction(function () use ($server, $metricsData) {
            // 1. Create time-series record
            $metric = ServerMetric::create([
                'server_id' => $server->id,
                'cpu_usage' => $metricsData->cpuUsage,
                'memory_total' => $metricsData->memoryTotal,
                'memory_used' => $metricsData->memoryUsed,
                'memory_available' => $metricsData->memoryAvailable,
                'disk_total' => $metricsData->diskTotal,
                'disk_used' => $metricsData->diskUsed,
                'disk_usage' => $metricsData->diskUsage,
                'load_1m' => $metricsData->load1m,
                'load_5m' => $metricsData->load5m,
                'load_15m' => $metricsData->load15m,
                'network_rx' => $metricsData->networkRx,
                'network_tx' => $metricsData->networkTx,
                'disk_read' => $metricsData->diskRead,
                'disk_write' => $metricsData->diskWrite,
                'process_count' => $metricsData->processCount,
                'open_file_descriptors' => $metricsData->openFileDescriptors,
                'active_tcp_connections' => $metricsData->activeTcpConnections,
                'recorded_at' => $metricsData->recordedAt ?? now(),
            ]);

            // 2. Update Server current snapshot
            $server->update([
                'total_ram' => $metricsData->memoryTotal > 0 ? $metricsData->memoryTotal : $server->total_ram,
                'used_ram' => $metricsData->memoryUsed,
                'total_disk' => $metricsData->diskTotal > 0 ? $metricsData->diskTotal : $server->total_disk,
                'used_disk' => $metricsData->diskUsed,
                'load_avg_1min' => $metricsData->load1m,
                'load_avg_5min' => $metricsData->load5m,
                'load_avg_15min' => $metricsData->load15m,
                'last_seen_at' => now(),
                'last_ping_at' => now(),
            ]);

            return $metric;
        });
    }
}
