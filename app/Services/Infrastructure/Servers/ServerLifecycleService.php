<?php

namespace App\Services\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerStatus;
use App\Exceptions\Infrastructure\Servers\ServerLifecycleException;
use App\Exceptions\Infrastructure\Servers\ServerOperationLockedException;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServerLifecycleService
{
    protected ServerEventService $eventService;

    // Strict transition state machine map
    protected array $allowedTransitions = [
        'pending' => ['verifying', 'decommissioning'],
        'verifying' => ['verified', 'pending', 'offline'],
        'verified' => ['provisioning', 'active', 'online', 'maintenance', 'offline', 'decommissioning'],
        'provisioning' => ['active', 'online', 'warning', 'offline', 'pending'],
        'active' => ['warning', 'offline', 'maintenance', 'suspended', 'decommissioning'],
        'online' => ['warning', 'offline', 'maintenance', 'suspended', 'decommissioning'],
        'warning' => ['active', 'online', 'offline', 'maintenance', 'suspended', 'decommissioning'],
        'offline' => ['verifying', 'active', 'online', 'maintenance', 'decommissioning'],
        'maintenance' => ['active', 'online', 'offline', 'decommissioning'],
        'suspended' => ['active', 'online', 'offline', 'decommissioning'],
        'decommissioning' => ['decommissioned', 'offline'],
        'decommissioned' => [],
    ];

    public function __construct(ServerEventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Check if a state transition is permitted.
     */
    public function canTransition(ServerStatus $from, ServerStatus $to): bool
    {
        if ($from === $to) {
            return true; // Idempotent same-state transition
        }

        $allowed = $this->allowedTransitions[$from->value] ?? [];
        return in_array($to->value, $allowed, true);
    }

    /**
     * Transition a server to a target status with atomic lock, database transaction, and event logging.
     */
    public function transition(Server $server, ServerStatus $targetStatus, ?string $reason = null, array $metadata = []): Server
    {
        // 1. Concurrency lock per server (10s max lock)
        $lockKey = "server_lifecycle_lock_{$server->id}";
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            throw new ServerOperationLockedException(
                "Cannot change server status: Another operation is currently running on server #{$server->id}.",
                'SERVER_LIFECYCLE_LOCKED',
                true,
                ['server_id' => $server->id, 'current_status' => $server->status->value, 'target_status' => $targetStatus->value]
            );
        }

        try {
            // Refresh model from database to avoid stale state
            $server->refresh();
            $currentStatus = $server->status;

            if ($currentStatus === $targetStatus) {
                return $server; // Idempotent no-op
            }

            if (!$this->canTransition($currentStatus, $targetStatus)) {
                throw new ServerLifecycleException(
                    "Illegal server status transition from '{$currentStatus->value}' to '{$targetStatus->value}'.",
                    'SERVER_ILLEGAL_STATE_TRANSITION',
                    false,
                    [
                        'server_id' => $server->id,
                        'from' => $currentStatus->value,
                        'to' => $targetStatus->value,
                        'reason' => $reason
                    ]
                );
            }

            // 2. Perform atomic state transition
            return DB::transaction(function () use ($server, $currentStatus, $targetStatus, $reason, $metadata) {
                $updateData = [
                    'status' => $targetStatus,
                ];

                if ($targetStatus === ServerStatus::MAINTENANCE) {
                    $updateData['maintenance_at'] = now();
                    $updateData['maintenance_reason'] = $reason ?? 'Scheduled maintenance';
                } elseif ($currentStatus === ServerStatus::MAINTENANCE) {
                    $updateData['maintenance_at'] = null;
                    $updateData['maintenance_reason'] = null;
                }

                if ($targetStatus === ServerStatus::DECOMMISSIONED) {
                    $updateData['decommissioned_at'] = now();
                }

                $server->update($updateData);

                // 3. Map event type and severity
                $eventType = match ($targetStatus) {
                    ServerStatus::ONLINE, ServerStatus::ACTIVE => ServerEventType::SERVER_ONLINE,
                    ServerStatus::OFFLINE => ServerEventType::SERVER_OFFLINE,
                    ServerStatus::MAINTENANCE => ServerEventType::SERVER_MAINTENANCE_STARTED,
                    ServerStatus::VERIFIED => ServerEventType::SERVER_VERIFIED,
                    ServerStatus::PROVISIONING => ServerEventType::SERVER_PROVISIONING_STARTED,
                    default => ServerEventType::SERVER_ONLINE,
                };

                $severity = match ($targetStatus) {
                    ServerStatus::OFFLINE => ServerEventSeverity::CRITICAL,
                    ServerStatus::WARNING, ServerStatus::MAINTENANCE => ServerEventSeverity::WARNING,
                    default => ServerEventSeverity::INFO,
                };

                $this->eventService->recordEvent(
                    $server,
                    $eventType,
                    "Server status changed from {$currentStatus->value} to {$targetStatus->value}" . ($reason ? " (Reason: {$reason})" : ''),
                    $severity,
                    array_merge($metadata, [
                        'from_status' => $currentStatus->value,
                        'to_status' => $targetStatus->value,
                        'reason' => $reason,
                    ])
                );

                return $server;
            });
        } finally {
            $lock->release();
        }
    }
}
