<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Exceptions\Infrastructure\Servers\ServerException;
use App\Models\Server;
use App\Models\ServerService;

class ServerServiceManager
{
    protected RemoteServerClientInterface $client;
    protected ServerEventService $eventService;

    // Strict allowlist of manageable infrastructure daemons
    protected array $allowedServices = [
        'nginx',
        'mysql',
        'mariadb',
        'php-fpm',
        'php7.4-fpm',
        'php8.0-fpm',
        'php8.1-fpm',
        'php8.2-fpm',
        'php8.3-fpm',
        'php8.4-fpm',
        'php8.5-fpm',
        'redis',
        'redis-server',
        'ssh',
        'ufw',
        'fail2ban',
        'cron',
        'supervisor',
    ];

    // Allowed actions
    protected array $allowedActions = ['start', 'stop', 'restart', 'reload', 'status'];

    public function __construct(RemoteServerClientInterface $client, ServerEventService $eventService)
    {
        $this->client = $client;
        $this->eventService = $eventService;
    }

    /**
     * Safely manage an allowlisted daemon on a target server.
     */
    public function manageService(Server $server, string $serviceName, string $action): array
    {
        if (!in_array($serviceName, $this->allowedServices, true)) {
            throw new ServerException("Service '{$serviceName}' is not in the allowed infrastructure service whitelist.");
        }

        if (!in_array($action, $this->allowedActions, true)) {
            throw new ServerException("Action '{$action}' is invalid. Allowed: " . implode(', ', $this->allowedActions));
        }

        $result = $this->client->executeAllowlistedOperation($server, "systemd_{$action}", [
            'service' => $serviceName,
        ]);

        if (!empty($result['success'])) {
            $newStatus = match ($action) {
                'start', 'restart', 'reload' => ServerServiceStatus::RUNNING,
                'stop' => ServerServiceStatus::STOPPED,
                default => ServerServiceStatus::RUNNING,
            };

            ServerService::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'service_name' => $serviceName,
                ],
                [
                    'display_name' => ucfirst(str_replace(['-', '_'], ' ', $serviceName)),
                    'service_type' => 'system',
                    'status' => $newStatus,
                    'last_checked_at' => now(),
                ]
            );

            $this->eventService->recordEvent(
                $server,
                match ($action) {
                    'start' => ServerEventType::SERVICE_STARTED,
                    'stop' => ServerEventType::SERVICE_STOPPED,
                    default => ServerEventType::SERVICE_STARTED,
                },
                "Executed {$action} on service {$serviceName} for server #{$server->id}",
                ServerEventSeverity::INFO,
                ['service' => $serviceName, 'action' => $action]
            );
        }

        return $result;
    }
}
