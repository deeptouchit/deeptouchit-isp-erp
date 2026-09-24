<?php

namespace App\Services\Infrastructure\Monitoring\Channels;

use App\Contracts\Infrastructure\Monitoring\NotificationChannelInterface;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookNotificationChannel implements NotificationChannelInterface
{
    public function send(Server $server, MonitoringAlertState $alertState, string $transitionType): bool
    {
        if (!config('monitoring.notifications.webhook.enabled', false)) {
            return false;
        }

        $url = config('monitoring.notifications.webhook.url');
        $secret = config('monitoring.notifications.webhook.secret', '');
        $timeout = config('monitoring.notifications.webhook.timeout_seconds', 5);

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsedUrl = parse_url($url);
        if (($parsedUrl['scheme'] ?? '') !== 'https' && !app()->environment('local', 'testing')) {
            Log::warning("Monitoring webhook rejected: HTTPS required in production", ['url' => $url]);
            return false;
        }

        $payload = [
            'event' => 'monitoring.alert',
            'version' => 1,
            'transition' => $transitionType,
            'timestamp' => now()->toIso8601String(),
            'server' => [
                'id' => $server->id,
                'uuid' => $server->uuid,
                'name' => $server->name,
                'hostname' => $server->hostname,
                'ip_address' => $server->ip_address,
            ],
            'alert' => [
                'id' => $alertState->id,
                'type' => $alertState->alert_type->value,
                'state' => $alertState->state->value,
                'severity' => $alertState->severity,
                'resource' => $alertState->resource_identity,
                'current_value' => $alertState->current_value,
                'threshold_value' => $alertState->threshold_value,
                'started_at' => $alertState->started_at?->toIso8601String(),
            ],
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-HostingOS-Signature' => $signature,
                    'X-HostingOS-Event' => 'monitoring.alert',
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($url);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed dispatching monitoring webhook: " . $e->getMessage(), ['server_id' => $server->id]);
            return false;
        }
    }
}
