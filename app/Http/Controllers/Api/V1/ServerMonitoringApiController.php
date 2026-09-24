<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Infrastructure\MonitoringAlertStatus;
use App\Enums\Infrastructure\MonitoringAlertType;
use App\Http\Controllers\Controller;
use App\Http\Resources\MonitoringAlertResource;
use App\Http\Resources\MonitoringHistoryResource;
use App\Http\Resources\MonitoringSnapshotResource;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\ServerMetricAggregate;
use App\Services\Infrastructure\Monitoring\MonitoringNotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServerMonitoringApiController extends Controller
{
    /**
     * Get real-time monitoring snapshot for a server node.
     */
    public function snapshot(Server $server): MonitoringSnapshotResource
    {
        $this->authorize('view', $server);

        $server->load(['latestMetric', 'alertStates']);

        return new MonitoringSnapshotResource($server);
    }

    /**
     * Get historical timeseries metrics with bounded timeframes.
     */
    public function history(Request $request, Server $server): AnonymousResourceCollection
    {
        $this->authorize('view', $server);

        $range = $request->input('range', '1h');
        $allowedRanges = ['1h', '6h', '24h', '7d', '30d'];
        if (!in_array($range, $allowedRanges, true)) {
            $range = '1h';
        }

        switch ($range) {
            case '1h':
                $metrics = ServerMetric::where('server_id', $server->id)
                    ->where('recorded_at', '>=', now()->subHour())
                    ->orderBy('recorded_at', 'asc')
                    ->get();
                break;
            case '6h':
                $metrics = ServerMetric::where('server_id', $server->id)
                    ->where('recorded_at', '>=', now()->subHours(6))
                    ->orderBy('recorded_at', 'asc')
                    ->get();
                break;
            case '24h':
                $metrics = ServerMetric::where('server_id', $server->id)
                    ->where('recorded_at', '>=', now()->subHours(24))
                    ->orderBy('recorded_at', 'asc')
                    ->get();
                break;
            case '7d':
                $metrics = ServerMetricAggregate::where('server_id', $server->id)
                    ->where('interval', '1h')
                    ->where('period_start', '>=', now()->subDays(7))
                    ->orderBy('period_start', 'asc')
                    ->get();
                break;
            case '30d':
                $metrics = ServerMetricAggregate::where('server_id', $server->id)
                    ->where('interval', '1d')
                    ->where('period_start', '>=', now()->subDays(30))
                    ->orderBy('period_start', 'asc')
                    ->get();
                break;
            default:
                $metrics = collect();
        }

        return MonitoringHistoryResource::collection($metrics);
    }

    /**
     * Get alert states for a specific server.
     */
    public function alerts(Request $request, Server $server): AnonymousResourceCollection
    {
        $this->authorize('view', $server);

        $query = MonitoringAlertState::where('server_id', $server->id);

        if ($state = $request->input('state')) {
            $query->where('state', $state);
        }

        return MonitoringAlertResource::collection($query->latest()->paginate(25));
    }

    /**
     * List all active alerts across cluster.
     */
    public function allAlerts(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Server::class);

        $query = MonitoringAlertState::with('server')
            ->whereIn('state', [MonitoringAlertStatus::WARNING, MonitoringAlertStatus::CRITICAL]);

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        return MonitoringAlertResource::collection($query->latest()->paginate(50));
    }

    /**
     * Dispatch a test alert notification to verify channels.
     */
    public function testAlert(Server $server, MonitoringNotificationDispatcher $dispatcher): JsonResponse
    {
        $this->authorize('update', $server);

        $fingerprint = MonitoringAlertState::generateFingerprint($server->id, MonitoringAlertType::CPU_HIGH->value, 'test');
        $dummyAlert = new MonitoringAlertState([
            'server_id' => $server->id,
            'alert_type' => MonitoringAlertType::CPU_HIGH,
            'resource_identity' => 'test-channel-probe',
            'state' => MonitoringAlertStatus::WARNING,
            'severity' => 'warning',
            'current_value' => '88.5%',
            'threshold_value' => '75.0%',
            'fingerprint' => $fingerprint,
            'started_at' => now(),
        ]);

        $dispatcher->dispatch($server, $dummyAlert, 'test_probe');

        return response()->json([
            'success' => true,
            'message' => 'Monitoring test alert dispatched across all configured channels.',
        ]);
    }
}
