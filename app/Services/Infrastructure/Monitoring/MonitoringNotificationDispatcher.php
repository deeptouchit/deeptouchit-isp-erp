<?php

namespace App\Services\Infrastructure\Monitoring;

use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Services\Infrastructure\Monitoring\Channels\EmailNotificationChannel;
use App\Services\Infrastructure\Monitoring\Channels\SlackNotificationChannel;
use App\Services\Infrastructure\Monitoring\Channels\TelegramNotificationChannel;
use App\Services\Infrastructure\Monitoring\Channels\WebhookNotificationChannel;
use Illuminate\Support\Facades\Log;

class MonitoringNotificationDispatcher
{
    protected array $channels;

    public function __construct(
        EmailNotificationChannel $emailChannel,
        TelegramNotificationChannel $telegramChannel,
        SlackNotificationChannel $slackChannel,
        WebhookNotificationChannel $webhookChannel
    ) {
        $this->channels = [
            'email' => $emailChannel,
            'telegram' => $telegramChannel,
            'slack' => $slackChannel,
            'webhook' => $webhookChannel,
        ];
    }

    /**
     * Dispatch alert notification across enabled channels.
     */
    public function dispatch(Server $server, MonitoringAlertState $alertState, string $transitionType): void
    {
        foreach ($this->channels as $channelName => $channel) {
            try {
                $channel->send($server, $alertState, $transitionType);
            } catch (\Throwable $e) {
                Log::error("Channel [{$channelName}] notification failed: " . $e->getMessage(), [
                    'server_id' => $server->id,
                    'alert_type' => $alertState->alert_type->value,
                ]);
            }
        }

        $alertState->update([
            'notification_dispatched_at' => now(),
        ]);
    }
}
