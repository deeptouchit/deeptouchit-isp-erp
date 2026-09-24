<?php

namespace App\Contracts\Infrastructure\Servers;

use App\Data\Infrastructure\Servers\ServerDiscoveryData;
use App\Models\Server;

interface ServerDiscoveryInterface
{
    public function discover(Server $server): ServerDiscoveryData;
}
