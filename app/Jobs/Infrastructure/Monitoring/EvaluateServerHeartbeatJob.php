<?php

namespace App\Jobs\Infrastructure\Monitoring;

use App\Enums\Infrastructure\MonitoringAlertStatus;
use App\Enums\Infrastructure\MonitoringAlertType;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Services\Infrastructure\Monitoring\MonitoringNotificationDispatcher;
use App\Services\Infrastructure\Servers\ServerEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateServerHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        ServerEventService $eventService,
        MonitoringNotificationDispatcher $dispatcher
    ): void {
        $warningSeconds = config('monitoring.heartbeat.warning_after_seconds', 120);
        $offlineSeconds = config('monitoring.heartbeat.offline_after_seconds', 300);

        $servers = Server::whereNotIn('status', [ServerStatus::DECOMMISSIONED, ServerStatus::SUSPENDED])->get();

        foreach ($servers as $server) {
            $lastSeen = $server->last_seen_at ?? $server->last_ping_at ?? $server->created_at;
            if (!$lastSeen) continue;

            $diffSeconds = $lastSeen->diffInSeconds(now());

            $fingerprint = MonitoringAlertState::generateFingerprint($server->id, MonitoringAlertType::SERVER_OFFLINE->value, 'heartbeat');
            $alertState = MonitoringAlertState::where('fingerprint', $fingerprint)->first();

            if ($diffSeconds >= $offlineSeconds) {
                // Server is completely offline / unreachable
                if ($server->status !== ServerStatus::OFFLINE && $server->status !== ServerStatus::MAINTENANCE) {
                    $server->update([
                        'status' => ServerStatus::OFFLINE,
                        'health_status' => ServerHealthStatus::OFFLINE,
                    ]);

                    $eventService->recordEvent(
                        $server,
                        ServerEventType::SERVER_OFFLINE,
                        "Server node #{$server->id} ({$server->name}) marked OFFLINE: no heartbeat received in {$diffSeconds}s",
                        ServerEventSeverity::CRITICAL,
                        ['diff_seconds' => $diffSeconds]
                    );

                    if (!$alertState) {
                        $alertState = MonitoringAlertState::create([
                            'server_id' => $server->id,
                            'alert_type' => MonitoringAlertType::SERVER_OFFLINE,
                            'resource_identity' => 'heartbeat',
                            'state' => MonitoringAlertStatus::CRITICAL,
                            'severity' => 'critical',
                            'current_value' => "{$diffSeconds}s without heartbeat",
                            'threshold_value' => "{$offlineSeconds}s",
                            'fingerprint' => $fingerprint,
                            'started_at' => now(),
                        ]);
                    } else {
                        $alertState->update([
                            'state' => MonitoringAlertStatus::CRITICAL,
                            'severity' => 'critical',
                            'current_value' => "{$diffSeconds}s without heartbeat",
                        ]);
                    }

                    $dispatcher->dispatch($server, $alertState, 'offline');
                }
            } elseif ($diffSeconds >= $warningSeconds) {
                // Heartbeat delayed / degraded
                if ($server->health_status !== ServerHealthStatus::WARNING && $server->status !== ServerStatus::MAINTENANCE) {
                    $server->update(['health_status' => ServerHealthStatus::WARNING]);
                }
            } else {
                // Healthy heartbeat - recover if previous offline alert existed
                if ($alertState && in_array($alertState->state, [MonitoringAlertStatus::WARNING, MonitoringAlertStatus::CRITICAL], true)) {
                    $alertState->update([
                        'state' => MonitoringAlertStatus::RECOVERED,
                        'resolved_at' => now(),
                    ]);

                    $eventService->recordEvent(
                        $server,
                        ServerEventType::SERVER_ONLINE,
                        "Server node #{$server->id} ({$server->name}) recovered to ONLINE",
                        ServerEventSeverity::INFO
                    );

                    $dispatcher->dispatch($server, $alertState, 'recovered');
                }
            }
        }
    }
}
