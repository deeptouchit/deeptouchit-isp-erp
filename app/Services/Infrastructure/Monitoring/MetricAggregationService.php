<?php

namespace App\Services\Infrastructure\Monitoring;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\ServerMetricAggregate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetricAggregationService
{
    /**
     * Aggregate hourly rollups for all servers for the given hour.
     */
    public function aggregateHourly(Carbon $periodStart): void
    {
        $periodEnd = $periodStart->copy()->addHour();

        $servers = Server::select('id')->get();

        foreach ($servers as $server) {
            $stats = ServerMetric::where('server_id', $server->id)
                ->whereBetween('recorded_at', [$periodStart, $periodEnd])
                ->selectRaw('
                    AVG(cpu_usage) as cpu_avg,
                    MIN(cpu_usage) as cpu_min,
                    MAX(cpu_usage) as cpu_max,
                    AVG(memory_used) as ram_avg,
                    MAX(memory_used) as ram_max,
                    AVG(disk_usage) as disk_avg,
                    MAX(disk_usage) as disk_max,
                    AVG(load_1m) as load_1m_avg,
                    SUM(network_rx) as network_rx_total,
                    SUM(network_tx) as network_tx_total,
                    COUNT(*) as sample_count
                ')
                ->first();

            if (!$stats || $stats->sample_count === 0) {
                continue;
            }

            ServerMetricAggregate::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'period_start' => $periodStart,
                    'interval' => '1h',
                ],
                [
                    'cpu_avg' => round((float) $stats->cpu_avg, 2),
                    'cpu_min' => round((float) $stats->cpu_min, 2),
                    'cpu_max' => round((float) $stats->cpu_max, 2),
                    'ram_avg' => round((float) $stats->ram_avg, 2),
                    'ram_max' => round((float) $stats->ram_max, 2),
                    'disk_avg' => round((float) $stats->disk_avg, 2),
                    'disk_max' => round((float) $stats->disk_max, 2),
                    'load_1m_avg' => round((float) $stats->load_1m_avg, 2),
                    'network_rx_total' => (int) $stats->network_rx_total,
                    'network_tx_total' => (int) $stats->network_tx_total,
                    'sample_count' => (int) $stats->sample_count,
                ]
            );
        }
    }

    /**
     * Aggregate daily rollups from hourly rollups.
     */
    public function aggregateDaily(Carbon $dayStart): void
    {
        $dayEnd = $dayStart->copy()->addDay();

        $servers = Server::select('id')->get();

        foreach ($servers as $server) {
            $stats = ServerMetricAggregate::where('server_id', $server->id)
                ->where('interval', '1h')
                ->whereBetween('period_start', [$dayStart, $dayEnd])
                ->selectRaw('
                    AVG(cpu_avg) as cpu_avg,
                    MIN(cpu_min) as cpu_min,
                    MAX(cpu_max) as cpu_max,
                    AVG(ram_avg) as ram_avg,
                    MAX(ram_max) as ram_max,
                    AVG(disk_avg) as disk_avg,
                    MAX(disk_max) as disk_max,
                    AVG(load_1m_avg) as load_1m_avg,
                    SUM(network_rx_total) as network_rx_total,
                    SUM(network_tx_total) as network_tx_total,
                    SUM(sample_count) as sample_count
                ')
                ->first();

            if (!$stats || $stats->sample_count === 0) {
                continue;
            }

            ServerMetricAggregate::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'period_start' => $dayStart,
                    'interval' => '1d',
                ],
                [
                    'cpu_avg' => round((float) $stats->cpu_avg, 2),
                    'cpu_min' => round((float) $stats->cpu_min, 2),
                    'cpu_max' => round((float) $stats->cpu_max, 2),
                    'ram_avg' => round((float) $stats->ram_avg, 2),
                    'ram_max' => round((float) $stats->ram_max, 2),
                    'disk_avg' => round((float) $stats->disk_avg, 2),
                    'disk_max' => round((float) $stats->disk_max, 2),
                    'load_1m_avg' => round((float) $stats->load_1m_avg, 2),
                    'network_rx_total' => (int) $stats->network_rx_total,
                    'network_tx_total' => (int) $stats->network_tx_total,
                    'sample_count' => (int) $stats->sample_count,
                ]
            );
        }
    }
}
