<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Contracts\Infrastructure\Servers\ServerVerificationInterface;
use App\Data\Infrastructure\Servers\ServerVerificationResult;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerStatus;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

class ServerVerificationService implements ServerVerificationInterface
{
    protected RemoteServerClientInterface $client;
    protected ServerLifecycleService $lifecycleService;
    protected ServerEventService $eventService;

    public function __construct(
        RemoteServerClientInterface $client,
        ServerLifecycleService $lifecycleService,
        ServerEventService $eventService
    ) {
        $this->client = $client;
        $this->lifecycleService = $lifecycleService;
        $this->eventService = $eventService;
    }

    /**
     * Verify server connectivity, authentication, and hardware probing.
     */
    public function verify(Server $server): ServerVerificationResult
    {
        $startTime = microtime(true);

        Log::info("[ServerVerification] Starting verification for Server #{$server->id} ({$server->hostname})");

        // 1. Move to verifying state if allowed
        if ($this->lifecycleService->canTransition($server->status, ServerStatus::VERIFYING)) {
            $this->lifecycleService->transition($server, ServerStatus::VERIFYING, 'Initiated SSH connectivity probe');
        }

        // 2. Perform remote transport check
        $result = $this->client->verifyConnectivity($server);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($result->success) {
            // 3. Mark as verified
            $this->lifecycleService->transition(
                $server,
                ServerStatus::VERIFIED,
                'Remote host connectivity and credentials verified successfully',
                ['duration_ms' => $durationMs]
            );

            $server->update([
                'last_ping_at' => now(),
                'last_seen_at' => now(),
                'last_health_check_at' => now(),
            ]);

            $this->eventService->recordEvent(
                $server,
                ServerEventType::SERVER_VERIFIED,
                "Server #{$server->id} ({$server->hostname}) verified in {$durationMs}ms",
                ServerEventSeverity::INFO,
                ['duration_ms' => $durationMs, 'os_family' => $result->osFamily]
            );

            return ServerVerificationResult::successful(
                message: $result->message,
                osFamily: $result->osFamily,
                detectedArchitecture: $result->detectedArchitecture,
                durationMs: $durationMs,
                context: $result->context
            );
        }

        // 4. Handle Failure
        if ($this->lifecycleService->canTransition($server->status, ServerStatus::OFFLINE)) {
            $this->lifecycleService->transition(
                $server,
                ServerStatus::OFFLINE,
                "Verification failed: {$result->message}"
            );
        }

        $this->eventService->recordEvent(
            $server,
            ServerEventType::SERVER_OFFLINE,
            "Verification failed for server #{$server->id}: {$result->message}",
            ServerEventSeverity::CRITICAL,
            [
                'error_code' => $result->errorCode,
                'is_retryable' => $result->isRetryable,
                'duration_ms' => $durationMs
            ]
        );

        return ServerVerificationResult::failed(
            message: $result->message,
            errorCode: $result->errorCode ?? 'SERVER_VERIFICATION_FAILED',
            isRetryable: $result->isRetryable,
            durationMs: $durationMs,
            context: $result->context
        );
    }
}
