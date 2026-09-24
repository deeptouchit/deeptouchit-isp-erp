<?php

namespace App\Contracts\Infrastructure\Monitoring;

use App\Models\MonitoringAlertState;
use App\Models\Server;

interface NotificationChannelInterface
{
    /**
     * Send alert notification through channel.
     */
    public function send(Server $server, MonitoringAlertState $alertState, string $transitionType): bool;
}
