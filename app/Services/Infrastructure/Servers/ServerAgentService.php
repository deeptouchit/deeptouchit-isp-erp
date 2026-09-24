<?php

namespace App\Services\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Models\Server;
use Illuminate\Support\Str;

class ServerAgentService
{
    protected ServerEventService $eventService;

    public function __construct(ServerEventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Generate and assign a cryptographically secure agent token for a node.
     */
    public function generateAgentToken(Server $server): string
    {
        $token = 'sh_agt_' . Str::random(48);

        $server->update([
            'agent_token' => $token,
            'agent_installed_at' => now(),
        ]);

        $this->eventService->recordEvent(
            $server,
            ServerEventType::AGENT_CONNECTED,
            "New agent identity token generated for Server #{$server->id}",
            ServerEventSeverity::INFO
        );

        return $token;
    }

    /**
     * Validate an incoming agent token against registered nodes.
     */
    public function authenticateAgent(string $token): ?Server
    {
        if (empty(trim($token))) {
            return null;
        }

        return Server::where('agent_token', $token)->first();
    }

    /**
     * Ingest heartbeat from registered agent.
     */
    public function recordHeartbeat(Server $server, ?string $agentVersion = null): void
    {
        $server->update([
            'last_seen_at' => now(),
            'last_ping_at' => now(),
            'agent_version' => $agentVersion ?? $server->agent_version,
        ]);
    }
}
