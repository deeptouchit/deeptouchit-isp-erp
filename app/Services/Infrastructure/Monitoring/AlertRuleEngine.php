<?php

namespace App\Services\Infrastructure\Monitoring;

use App\Enums\Infrastructure\MonitoringAlertStatus;
use App\Enums\Infrastructure\MonitoringAlertType;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Models\MonitoringAlertRule;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Services\Infrastructure\Servers\ServerEventService;

class AlertRuleEngine
{
    protected MonitoringNotificationDispatcher $dispatcher;
    protected ServerEventService $eventService;

    public function __construct(
        MonitoringNotificationDispatcher $dispatcher,
        ServerEventService $eventService
    ) {
        $this->dispatcher = $dispatcher;
        $this->eventService = $eventService;
    }

    /**
     * Evaluate normalized telemetry against alert rules.
     */
    public function evaluate(Server $server, array $normalized): void
    {
        if (!config('monitoring.alerts.enabled', true)) {
            return;
        }

        // 1. CPU Evaluation
        $cpu = $normalized['cpu']['usage_percent'] ?? 0;
        $this->evaluateMetric(
            $server,
            MonitoringAlertType::CPU_HIGH,
            'cpu',
            $cpu,
            config('monitoring.thresholds.cpu_warning', 75.0),
            config('monitoring.thresholds.cpu_critical', 90.0),
            "{$cpu}%"
        );

        // 2. Memory Evaluation
        $ramPercent = $normalized['memory']['usage_percent'] ?? 0;
        $this->evaluateMetric(
            $server,
            MonitoringAlertType::MEMORY_HIGH,
            'memory',
            $ramPercent,
            config('monitoring.thresholds.memory_warning', 80.0),
            config('monitoring.thresholds.memory_critical', 90.0),
            "{$ramPercent}%"
        );

        // 3. Disk Mounts Evaluation
        foreach ($normalized['disks'] as $mount => $disk) {
            $diskPercent = $disk['usage_percent'] ?? 0;
            $this->evaluateMetric(
                $server,
                MonitoringAlertType::DISK_HIGH,
                $mount,
                $diskPercent,
                config('monitoring.thresholds.disk_warning', 80.0),
                config('monitoring.thresholds.disk_critical', 90.0),
                "{$diskPercent}%"
            );
        }

        // 4. Systemd & Multi-PHP Services Evaluation
        foreach ($normalized['services'] as $serviceName => $state) {
            $isDown = ($state === 'failed' || $state === 'stopped');
            $alertType = str_starts_with($serviceName, 'php') 
                ? MonitoringAlertType::PHP_FPM_DOWN 
                : MonitoringAlertType::SERVICE_DOWN;

            $this->evaluateBinaryCondition(
                $server,
                $alertType,
                $serviceName,
                $isDown,
                $state,
                'running'
            );
        }
    }

    /**
     * Evaluate continuous float metrics with Warning and Critical thresholds.
     */
    protected function evaluateMetric(
        Server $server,
        MonitoringAlertType $alertType,
        string $resource,
        float $value,
        float $warningThreshold,
        float $criticalThreshold,
        string $valueFormatted
    ): void {
        $fingerprint = MonitoringAlertState::generateFingerprint($server->id, $alertType->value, $resource);
        $stateRecord = MonitoringAlertState::where('fingerprint', $fingerprint)->first();

        $isCritical = $value >= $criticalThreshold;
        $isWarning = $value >= $warningThreshold;

        if ($isCritical) {
            $this->transitionAlert(
                $server,
                $stateRecord,
                $alertType,
                $resource,
                MonitoringAlertStatus::CRITICAL,
                'critical',
                $valueFormatted,
                "{$criticalThreshold}%"
            );
        } elseif ($isWarning) {
            $this->transitionAlert(
                $server,
                $stateRecord,
                $alertType,
                $resource,
                MonitoringAlertStatus::WARNING,
                'warning',
                $valueFormatted,
                "{$warningThreshold}%"
            );
        } else {
            // Normal / Recovery condition
            if ($stateRecord && in_array($stateRecord->state, [MonitoringAlertStatus::WARNING, MonitoringAlertStatus::CRITICAL], true)) {
                $this->transitionAlert(
                    $server,
                    $stateRecord,
                    $alertType,
                    $resource,
                    MonitoringAlertStatus::RECOVERED,
                    'info',
                    $valueFormatted,
                    "{$warningThreshold}%"
                );
            }
        }
    }

    /**
     * Evaluate binary states (e.g. Service Down / Server Offline).
     */
    protected function evaluateBinaryCondition(
        Server $server,
        MonitoringAlertType $alertType,
        string $resource,
        bool $isFailed,
        string $currentValue,
        string $expectedValue
    ): void {
        $fingerprint = MonitoringAlertState::generateFingerprint($server->id, $alertType->value, $resource);
        $stateRecord = MonitoringAlertState::where('fingerprint', $fingerprint)->first();

        if ($isFailed) {
            $this->transitionAlert(
                $server,
                $stateRecord,
                $alertType,
                $resource,
                MonitoringAlertStatus::CRITICAL,
                'critical',
                $currentValue,
                $expectedValue
            );
        } else {
            if ($stateRecord && in_array($stateRecord->state, [MonitoringAlertStatus::WARNING, MonitoringAlertStatus::CRITICAL], true)) {
                $this->transitionAlert(
                    $server,
                    $stateRecord,
                    $alertType,
                    $resource,
                    MonitoringAlertStatus::RECOVERED,
                    'info',
                    $currentValue,
                    $expectedValue
                );
            }
        }
    }

    /**
     * Perform state machine transition and dispatch notifications.
     */
    protected function transitionAlert(
        Server $server,
        ?MonitoringAlertState $stateRecord,
        MonitoringAlertType $alertType,
        string $resource,
        MonitoringAlertStatus $newState,
        string $severity,
        string $currentValue,
        string $thresholdValue
    ): void {
        $fingerprint = MonitoringAlertState::generateFingerprint($server->id, $alertType->value, $resource);
        $isNew = !$stateRecord;

        if ($isNew) {
            $stateRecord = MonitoringAlertState::create([
                'server_id' => $server->id,
                'alert_type' => $alertType,
                'resource_identity' => $resource,
                'state' => $newState,
                'severity' => $severity,
                'current_value' => $currentValue,
                'threshold_value' => $thresholdValue,
                'fingerprint' => $fingerprint,
                'started_at' => now(),
            ]);

            $transitionType = 'triggered';
        } else {
            $previousState = $stateRecord->state;

            // If state unchanged and within cooldown, do not spam notifications
            $cooldown = config('monitoring.alerts.default_cooldown_seconds', 300);
            $lastDispatched = $stateRecord->notification_dispatched_at;

            $shouldNotify = false;
            $transitionType = 'updated';

            if ($previousState !== $newState) {
                if ($newState === MonitoringAlertStatus::RECOVERED) {
                    $transitionType = 'recovered';
                    $stateRecord->resolved_at = now();
                    $shouldNotify = true;
                } elseif ($previousState === MonitoringAlertStatus::WARNING && $newState === MonitoringAlertStatus::CRITICAL) {
                    $transitionType = 'escalated';
                    $stateRecord->last_escalated_at = now();
                    $shouldNotify = true;
                } else {
                    $transitionType = 'triggered';
                    $shouldNotify = true;
                }
            } elseif (!$lastDispatched || $lastDispatched->diffInSeconds(now()) >= $cooldown) {
                $shouldNotify = true;
            }

            $stateRecord->update([
                'state' => $newState,
                'severity' => $severity,
                'current_value' => $currentValue,
                'threshold_value' => $thresholdValue,
                'resolved_at' => $newState === MonitoringAlertStatus::RECOVERED ? now() : null,
            ]);

            if (!$shouldNotify) {
                return;
            }
        }

        // Record audit event
        $this->eventService->recordEvent(
            $server,
            $newState === MonitoringAlertStatus::RECOVERED ? ServerEventType::HEALTH_WARNING : ServerEventType::HEALTH_CRITICAL,
            "Monitoring Alert [{$severity}]: {$alertType->value} on {$resource} (Value: {$currentValue}) - {$transitionType}",
            $severity === 'critical' ? ServerEventSeverity::CRITICAL : ServerEventSeverity::WARNING,
            [
                'alert_type' => $alertType->value,
                'resource' => $resource,
                'transition' => $transitionType,
                'current_value' => $currentValue,
                'threshold' => $thresholdValue,
            ]
        );

        // Dispatch notifications across configured channels
        $this->dispatcher->dispatch($server, $stateRecord, $transitionType);
    }
}
