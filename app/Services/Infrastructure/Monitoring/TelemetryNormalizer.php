<?php

namespace App\Services\Infrastructure\Monitoring;

use App\Data\Infrastructure\Servers\ServerMetricsData;
use App\Enums\Infrastructure\Servers\RemoteOperation;
use App\Exceptions\Infrastructure\Servers\ServerMetricsValidationException;
use Carbon\Carbon;

class TelemetryNormalizer
{
    /**
     * Normalize and sanitize raw telemetry payload from remote agents.
     */
    public function normalize(array $payload): array
    {
        // 1. Timestamp Normalization
        $timestamp = isset($payload['timestamp']) 
            ? Carbon::parse($payload['timestamp']) 
            : now();

        $maxClockSkew = config('monitoring.telemetry.max_clock_skew_seconds', 300);
        if ($timestamp->diffInSeconds(now(), false) > $maxClockSkew || $timestamp->diffInSeconds(now(), false) < -$maxClockSkew) {
            // Clock drift detected: clamp to now() but preserve metadata
            $skewSeconds = $timestamp->diffInSeconds(now(), false);
            $timestamp = now();
        } else {
            $skewSeconds = 0;
        }

        // 2. CPU Normalization
        $cpuRaw = $payload['cpu'] ?? $payload['cpu_usage'] ?? [];
        $cpuUsage = is_array($cpuRaw) ? ($cpuRaw['usage_percent'] ?? $cpuRaw['usage'] ?? ($payload['cpu_usage'] ?? 0)) : (float) $cpuRaw;
        $cpuUsage = min(100.0, max(0.0, (float) $cpuUsage));

        $load1m = is_array($cpuRaw) ? (float) ($cpuRaw['load_1m'] ?? $cpuRaw['load_avg_1min'] ?? $payload['load_1m'] ?? $payload['load_avg_1min'] ?? 0) : (float) ($payload['load_1m'] ?? 0);
        $load5m = is_array($cpuRaw) ? (float) ($cpuRaw['load_5m'] ?? $cpuRaw['load_avg_5min'] ?? $payload['load_5m'] ?? $payload['load_avg_5min'] ?? 0) : (float) ($payload['load_5m'] ?? 0);
        $load15m = is_array($cpuRaw) ? (float) ($cpuRaw['load_15m'] ?? $cpuRaw['load_avg_15min'] ?? $payload['load_15m'] ?? $payload['load_avg_15min'] ?? 0) : (float) ($payload['load_15m'] ?? 0);

        // 3. Memory Normalization
        $memRaw = $payload['memory'] ?? $payload['memory_used'] ?? [];
        $memTotal = is_array($memRaw) ? (int) ($memRaw['total_bytes'] ?? $memRaw['total'] ?? $payload['memory_total'] ?? 0) : (int) ($payload['memory_total'] ?? 0);
        $memUsed = is_array($memRaw) ? (int) ($memRaw['used_bytes'] ?? $memRaw['used'] ?? $payload['memory_used'] ?? 0) : (int) ($payload['memory_used'] ?? 0);
        $memAvailable = is_array($memRaw) ? (int) ($memRaw['available_bytes'] ?? $memRaw['available'] ?? max(0, $memTotal - $memUsed)) : max(0, $memTotal - $memUsed);

        // 4. Disk Mounts Normalization
        $disksRaw = $payload['disk'] ?? $payload['disks'] ?? [];
        $disks = [];
        $primaryDiskTotal = 0;
        $primaryDiskUsed = 0;

        if (is_array($disksRaw)) {
            foreach ($disksRaw as $disk) {
                if (!is_array($disk)) continue;
                $mount = $disk['mount'] ?? $disk['mount_point'] ?? '/';
                $dTotal = max(0, (int) ($disk['total_bytes'] ?? $disk['total'] ?? 0));
                $dUsed = max(0, (int) ($disk['used_bytes'] ?? $disk['used'] ?? 0));
                $dUsage = $dTotal > 0 ? round(($dUsed / $dTotal) * 100, 2) : (float) ($disk['usage_percent'] ?? 0);

                $disks[$mount] = [
                    'mount' => $mount,
                    'device' => $disk['device'] ?? 'unknown',
                    'total_bytes' => $dTotal,
                    'used_bytes' => $dUsed,
                    'usage_percent' => min(100.0, max(0.0, $dUsage)),
                ];

                if ($mount === '/' || empty($primaryDiskTotal)) {
                    $primaryDiskTotal = $dTotal;
                    $primaryDiskUsed = $dUsed;
                }
            }
        }

        // If primary disk not found from mounts array, check top-level disk attributes
        if ($primaryDiskTotal === 0) {
            $primaryDiskTotal = max(0, (int) ($payload['disk_total'] ?? 0));
            $primaryDiskUsed = max(0, (int) ($payload['disk_used'] ?? 0));
        }

        // 5. Network Interfaces Normalization
        $netRaw = $payload['network'] ?? [];
        $network = [];
        $totalRx = 0;
        $totalTx = 0;

        if (is_array($netRaw)) {
            foreach ($netRaw as $iface) {
                if (!is_array($iface)) continue;
                $ifName = $iface['interface'] ?? $iface['name'] ?? 'eth0';
                $rx = max(0, (int) ($iface['rx_bytes'] ?? $iface['rx'] ?? 0));
                $tx = max(0, (int) ($iface['tx_bytes'] ?? $iface['tx'] ?? 0));

                $network[$ifName] = [
                    'interface' => $ifName,
                    'rx_bytes' => $rx,
                    'tx_bytes' => $tx,
                    'rx_errors' => max(0, (int) ($iface['rx_errors'] ?? 0)),
                    'tx_errors' => max(0, (int) ($iface['tx_errors'] ?? 0)),
                ];

                $totalRx += $rx;
                $totalTx += $tx;
            }
        }

        // 6. Services & PHP Normalization
        $servicesRaw = $payload['services'] ?? [];
        $services = [];
        if (is_array($servicesRaw)) {
            foreach ($servicesRaw as $svc) {
                if (!is_array($svc) || empty($svc['name'])) continue;
                $name = strtolower(trim($svc['name']));
                if (in_array($name, RemoteOperation::ALLOWED_SERVICES, true)) {
                    $state = strtolower($svc['state'] ?? $svc['status'] ?? 'unknown');
                    $services[$name] = in_array($state, ['running', 'active'], true) ? 'running' : ($state === 'failed' ? 'failed' : 'stopped');
                }
            }
        }

        return [
            'timestamp' => $timestamp,
            'clock_skew_seconds' => $skewSeconds,
            'cpu' => [
                'usage_percent' => round($cpuUsage, 2),
                'load_1m' => round($load1m, 2),
                'load_5m' => round($load5m, 2),
                'load_15m' => round($load15m, 2),
            ],
            'memory' => [
                'total_bytes' => $memTotal,
                'used_bytes' => $memUsed,
                'available_bytes' => $memAvailable,
                'usage_percent' => $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 2) : 0,
            ],
            'disks' => $disks,
            'primary_disk' => [
                'total_bytes' => $primaryDiskTotal,
                'used_bytes' => $primaryDiskUsed,
                'usage_percent' => $primaryDiskTotal > 0 ? round(($primaryDiskUsed / $primaryDiskTotal) * 100, 2) : 0,
            ],
            'network' => $network,
            'network_totals' => [
                'rx_bytes' => $totalRx,
                'tx_bytes' => $totalTx,
            ],
            'services' => $services,
            'metrics_data' => new ServerMetricsData(
                cpuUsage: round($cpuUsage, 2),
                memoryTotal: $memTotal,
                memoryUsed: $memUsed,
                memoryAvailable: $memAvailable,
                diskTotal: $primaryDiskTotal,
                diskUsed: $primaryDiskUsed,
                diskUsage: $primaryDiskTotal > 0 ? round(($primaryDiskUsed / $primaryDiskTotal) * 100, 2) : 0,
                load1m: round($load1m, 2),
                load5m: round($load5m, 2),
                load15m: round($load15m, 2),
                networkRx: $totalRx,
                networkTx: $totalTx,
                diskRead: max(0, (int) ($payload['disk_read'] ?? 0)),
                diskWrite: max(0, (int) ($payload['disk_write'] ?? 0)),
                processCount: max(0, (int) ($payload['process_count'] ?? 0)),
                openFileDescriptors: max(0, (int) ($payload['open_file_descriptors'] ?? 0)),
                activeTcpConnections: max(0, (int) ($payload['active_tcp_connections'] ?? 0)),
                recordedAt: $timestamp
            ),
        ];
    }
}
