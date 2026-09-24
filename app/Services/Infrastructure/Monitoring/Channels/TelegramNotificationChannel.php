<?php

namespace App\Services\Infrastructure\Monitoring\Channels;

use App\Contracts\Infrastructure\Monitoring\NotificationChannelInterface;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationChannel implements NotificationChannelInterface
{
    public function send(Server $server, MonitoringAlertState $alertState, string $transitionType): bool
    {
        if (!config('monitoring.notifications.telegram.enabled', false)) {
            return false;
        }

        $botToken = config('monitoring.notifications.telegram.bot_token');
        $chatId = config('monitoring.notifications.telegram.chat_id');

        if (!$botToken || !$chatId) {
            return false;
        }

        $icon = match ($alertState->severity) {
            'critical' => '🚨 [CRITICAL]',
            'warning' => '⚠️ [WARNING]',
            default => 'ℹ️ [INFO]',
        };

        if ($transitionType === 'recovered') {
            $icon = '✅ [RECOVERED]';
        }

        $text = "{$icon} HostingOS Infrastructure Alert\n"
              . "Server: {$server->name} ({$server->ip_address})\n"
              . "Type: {$alertState->alert_type->value}\n"
              . "Resource: {$alertState->resource_identity}\n"
              . "Value: {$alertState->current_value} (Threshold: {$alertState->threshold_value})\n"
              . "Status: {$alertState->state->value}\n"
              . "Time: " . now()->toIso8601String();

        try {
            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed sending Telegram alert: " . $e->getMessage(), ['server_id' => $server->id]);
            return false;
        }
    }
}
