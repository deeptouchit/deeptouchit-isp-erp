<?php

namespace App\Contracts\Infrastructure\Servers;

use App\Data\Infrastructure\Servers\ServerVerificationResult;
use App\Models\Server;

interface ServerVerificationInterface
{
    public function verify(Server $server): ServerVerificationResult;
}
