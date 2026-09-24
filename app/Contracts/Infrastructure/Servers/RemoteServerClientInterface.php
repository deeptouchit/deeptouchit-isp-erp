<?php

namespace App\Contracts\Infrastructure\Servers;

use App\Data\Infrastructure\Servers\ServerDiscoveryData;
use App\Data\Infrastructure\Servers\ServerVerificationResult;
use App\Models\Server;

interface RemoteServerClientInterface
{
    public function verifyConnectivity(Server $server): ServerVerificationResult;

    public function discoverHardware(Server $server): ServerDiscoveryData;

    public function executeAllowlistedOperation(Server $server, string $operation, array $params = []): array;
}
