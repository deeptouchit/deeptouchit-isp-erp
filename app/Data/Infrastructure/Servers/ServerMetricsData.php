<?php

namespace App\Data\Infrastructure\Servers;

use App\Exceptions\Infrastructure\Servers\ServerMetricsValidationException;
use DateTimeInterface;

class ServerMetricsData
{
    public function __construct(
        public readonly float $cpuUsage,
        public readonly int $memoryTotal,
        public readonly int $memoryUsed,
        public readonly int $memoryAvailable,
        public readonly int $diskTotal,
        public readonly int $diskUsed,
        public readonly float $diskUsage,
        public readonly float $load1m,
        public readonly float $load5m,
        public readonly float $load15m,
        public readonly int $networkRx,
        public readonly int $networkTx,
        public readonly int $diskRead,
        public readonly int $diskWrite,
        public readonly int $processCount,
        public readonly int $openFileDescriptors,
        public readonly int $activeTcpConnections,
        public readonly ?DateTimeInterface $recordedAt = null
    ) {}

    public static function fromPayload(array $payload): self
    {
        $cpu = (float) ($payload['cpu_usage'] ?? $payload['cpu'] ?? 0);
        if ($cpu < 0.0 || $cpu > 100.0) {
            throw new ServerMetricsValidationException("CPU usage out of valid 0-100% range: {$cpu}");
        }

        $memTotal = (int) ($payload['memory_total'] ?? $payload['mem_total'] ?? 0);
        $memUsed = (int) ($payload['memory_used'] ?? $payload['mem_used'] ?? 0);
        if ($memTotal < 0 || $memUsed < 0) {
            throw new ServerMetricsValidationException("Memory values cannot be negative: total={$memTotal}, used={$memUsed}");
        }

        $memAvailable = (int) ($payload['memory_available'] ?? max(0, $memTotal - $memUsed));

        $diskTotal = (int) ($payload['disk_total'] ?? 0);
        $diskUsed = (int) ($payload['disk_used'] ?? 0);
        if ($diskTotal < 0 || $diskUsed < 0) {
            throw new ServerMetricsValidationException("Disk values cannot be negative: total={$diskTotal}, used={$diskUsed}");
        }

        $diskUsage = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : (float) ($payload['disk_usage'] ?? 0);

        return new self(
            cpuUsage: round($cpu, 2),
            memoryTotal: $memTotal,
            memoryUsed: $memUsed,
            memoryAvailable: $memAvailable,
            diskTotal: $diskTotal,
            diskUsed: $diskUsed,
            diskUsage: $diskUsage,
            load1m: round((float) ($payload['load_1m'] ?? $payload['load_avg_1min'] ?? 0), 2),
            load5m: round((float) ($payload['load_5m'] ?? $payload['load_avg_5min'] ?? 0), 2),
            load15m: round((float) ($payload['load_15m'] ?? $payload['load_avg_15min'] ?? 0), 2),
            networkRx: max(0, (int) ($payload['network_rx'] ?? 0)),
            networkTx: max(0, (int) ($payload['network_tx'] ?? 0)),
            diskRead: max(0, (int) ($payload['disk_read'] ?? 0)),
            diskWrite: max(0, (int) ($payload['disk_write'] ?? 0)),
            processCount: max(0, (int) ($payload['process_count'] ?? 0)),
            openFileDescriptors: max(0, (int) ($payload['open_file_descriptors'] ?? 0)),
            activeTcpConnections: max(0, (int) ($payload['active_tcp_connections'] ?? 0)),
            recordedAt: isset($payload['recorded_at']) && $payload['recorded_at'] instanceof DateTimeInterface 
                ? $payload['recorded_at'] 
                : now()
        );
    }
}
