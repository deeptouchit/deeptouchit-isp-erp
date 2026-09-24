<?php

namespace App\Services\Infrastructure\Monitoring\Channels;

use App\Contracts\Infrastructure\Monitoring\NotificationChannelInterface;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationChannel implements NotificationChannelInterface
{
    public function send(Server $server, MonitoringAlertState $alertState, string $transitionType): bool
    {
        if (!config('monitoring.notifications.slack.enabled', false)) {
            return false;
        }

        $webhookUrl = config('monitoring.notifications.slack.webhook_url');
        if (!$webhookUrl) {
            return false;
        }

        $color = match ($alertState->severity) {
            'critical' => '#e11d48',
            'warning' => '#f59e0b',
            default => '#10b981',
        };

        if ($transitionType === 'recovered') {
            $color = '#10b981';
        }

        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "HostingOS Alert: [{$alertState->severity}] {$alertState->alert_type->value}",
                    'text' => "Server *{$server->name}* ({$server->ip_address}) reported {$alertState->alert_type->value} on `{$alertState->resource_identity}`. Current: {$alertState->current_value}, Threshold: {$alertState->threshold_value}.",
                    'ts' => now()->timestamp,
                ]
            ]
        ];

        try {
            $response = Http::timeout(5)->post($webhookUrl, $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed sending Slack alert: " . $e->getMessage(), ['server_id' => $server->id]);
            return false;
        }
    }
}
