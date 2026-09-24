<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Contracts\Infrastructure\Servers\ServerDiscoveryInterface;
use App\Data\Infrastructure\Servers\ServerDiscoveryData;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Models\Server;
use App\Models\ServerService as ServerServiceModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServerDiscoveryService implements ServerDiscoveryInterface
{
    protected RemoteServerClientInterface $client;
    protected ServerEventService $eventService;

    public function __construct(RemoteServerClientInterface $client, ServerEventService $eventService)
    {
        $this->client = $client;
        $this->eventService = $eventService;
    }

    /**
     * Discover hardware specs and active system daemons on remote node.
     */
    public function discover(Server $server): ServerDiscoveryData
    {
        Log::info("[ServerDiscovery] Probing hardware & runtimes for Server #{$server->id} ({$server->hostname})");

        $discovery = $this->client->discoverHardware($server);

        DB::transaction(function () use ($server, $discovery) {
            // 1. Update Hardware & OS properties
            $server->update([
                'os_name' => $discovery->osName,
                'os_version' => $discovery->osVersion,
                'kernel_version' => $discovery->kernelVersion,
                'architecture' => $discovery->architecture,
                'cpu_cores' => $discovery->cpuCores,
                'total_ram' => $discovery->totalRamMb,
                'total_disk' => $discovery->totalDiskGb,
                'last_seen_at' => now(),
            ]);

            // 2. Sync discovered system services
            foreach ($discovery->detectedServices as $serviceName) {
                ServerServiceModel::updateOrCreate(
                    [
                        'server_id' => $server->id,
                        'service_name' => $serviceName,
                    ],
                    [
                        'display_name' => ucfirst(str_replace(['-', '_'], ' ', $serviceName)),
                        'service_type' => 'system',
                        'status' => ServerServiceStatus::RUNNING,
                        'enabled' => true,
                        'last_checked_at' => now(),
                    ]
                );
            }

            // 3. Log discovery event
            $this->eventService->recordEvent(
                $server,
                ServerEventType::SERVER_VERIFIED,
                "Hardware discovery completed: {$discovery->osName} {$discovery->osVersion}, {$discovery->cpuCores} vCPUs, {$discovery->totalRamMb}MB RAM, {$discovery->totalDiskGb}GB Disk",
                ServerEventSeverity::INFO,
                [
                    'os' => "{$discovery->osName} {$discovery->osVersion}",
                    'cores' => $discovery->cpuCores,
                    'ram' => $discovery->totalRamMb,
                    'disk' => $discovery->totalDiskGb,
                    'services_count' => count($discovery->detectedServices),
                ]
            );
        });

        return $discovery;
    }
}
