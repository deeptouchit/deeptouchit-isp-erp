<?php

namespace App\Contracts\Infrastructure\Servers;

use App\Data\Infrastructure\Servers\ServerHealthResult;
use App\Models\Server;

interface ServerHealthInterface
{
    public function checkHealth(Server $server): ServerHealthResult;
}
