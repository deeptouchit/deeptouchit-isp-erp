<?php

namespace App\Services\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerLogLevel;
use App\Enums\Infrastructure\ServerLogType;
use App\Models\Server;
use App\Models\ServerEvent;
use App\Models\ServerLog;
use Illuminate\Support\Facades\Log;

class ServerEventService
{
    /**
     * Record a high-level operational lifecycle event.
     */
    public function recordEvent(
        Server $server,
        ServerEventType $type,
        string $message,
        ServerEventSeverity $severity = ServerEventSeverity::INFO,
        array $metadata = []
    ): ServerEvent {
        $event = ServerEvent::create([
            'server_id' => $server->id,
            'event_type' => $type,
            'severity' => $severity,
            'message' => $message,
            'metadata' => $this->sanitizeContext($metadata),
            'occurred_at' => now(),
        ]);

        // Also broadcast to Laravel structured log channel
        $logLevel = match ($severity) {
            ServerEventSeverity::INFO => 'info',
            ServerEventSeverity::WARNING => 'warning',
            ServerEventSeverity::CRITICAL, ServerEventSeverity::EMERGENCY => 'error',
        };

        Log::channel('stack')->{$logLevel}("[Server #{$server->id}][Event: {$type->value}] {$message}", [
            'server_id' => $server->id,
            'hostname' => $server->hostname,
            'event_id' => $event->id,
        ]);

        return $event;
    }

    /**
     * Record a technical daemon / system log.
     */
    public function recordLog(
        Server $server,
        string $message,
        ServerLogType $type = ServerLogType::SYSTEM,
        ServerLogLevel $level = ServerLogLevel::INFO,
        array $context = []
    ): ServerLog {
        return ServerLog::create([
            'server_id' => $server->id,
            'log_type' => $type,
            'level' => $level,
            'message' => $message,
            'context' => $this->sanitizeContext($context),
            'occurred_at' => now(),
        ]);
    }

    /**
     * Resolve pending unresolved events for a server.
     */
    public function resolveEvents(Server $server, ServerEventType $type): int
    {
        return ServerEvent::where('server_id', $server->id)
            ->where('event_type', $type)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    /**
     * Strictly mask passwords, secrets, private keys, and tokens from logging context.
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'secret', 'key', 'private_key', 'token', 'auth_token',
            'agent_token', 'encrypted_ssh_password', 'encrypted_ssh_key', 'bearer'
        ];

        array_walk_recursive($context, function (&$value, $key) use ($sensitiveKeys) {
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                foreach ($sensitiveKeys as $sensitive) {
                    if (str_contains($lowerKey, $sensitive)) {
                        $value = '***MASKED***';
                        break;
                    }
                }
            }
        });

        return $context;
    }
}
