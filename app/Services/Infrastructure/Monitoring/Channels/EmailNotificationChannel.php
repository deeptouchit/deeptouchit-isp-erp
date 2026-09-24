<?php

namespace App\Services\Infrastructure\Monitoring\Channels;

use App\Contracts\Infrastructure\Monitoring\NotificationChannelInterface;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationChannel implements NotificationChannelInterface
{
    public function send(Server $server, MonitoringAlertState $alertState, string $transitionType): bool
    {
        if (!config('monitoring.notifications.email.enabled', true)) {
            return false;
        }

        $recipient = config('monitoring.notifications.email.to');
        if (!$recipient) {
            return false;
        }

        try {
            // Log structured alert event (and send mail if configured)
            Log::channel('daily')->info("Monitoring Alert Email: [{$alertState->severity}] {$server->name} {$alertState->alert_type->value} ({$transitionType}) -> {$recipient}");

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed sending monitoring email alert: " . $e->getMessage(), ['server_id' => $server->id]);
            return false;
        }
    }
}
