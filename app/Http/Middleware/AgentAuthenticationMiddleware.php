<?php

namespace App\Http\Middleware;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Models\Server;
use App\Services\Infrastructure\Servers\ServerEventService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthenticationMiddleware
{
    protected ServerEventService $eventService;

    // Clock skew allowance window in seconds (5 minutes)
    protected int $clockSkewTolerance = 300;

    public function __construct(ServerEventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Agent-Token');
        if (empty($token)) {
            return response()->json([
                'success' => false,
                'error' => 'AGENT_UNAUTHENTICATED',
                'message' => 'Missing agent authentication token.',
            ], 401);
        }

        // 1. Resolve agent identity
        $server = Server::where('agent_token', $token)->first();
        if (!$server) {
            return response()->json([
                'success' => false,
                'error' => 'AGENT_TOKEN_INVALID',
                'message' => 'Agent token not recognized.',
            ], 401);
        }

        // 2. Replay & Clock skew protection if timestamp header is provided
        $timestamp = $request->header('X-Agent-Timestamp');
        if ($timestamp) {
            $reqTime = (int) $timestamp;
            $now = time();

            if (abs($now - $reqTime) > $this->clockSkewTolerance) {
                $this->eventService->recordEvent(
                    $server,
                    ServerEventType::AGENT_AUTH_FAILED,
                    "Agent request rejected due to expired timestamp clock skew (Diff: " . abs($now - $reqTime) . "s)",
                    ServerEventSeverity::WARNING
                );

                return response()->json([
                    'success' => false,
                    'error' => 'AGENT_TIMESTAMP_EXPIRED',
                    'message' => 'Request timestamp is outside the valid clock skew tolerance window.',
                ], 400);
            }
        }

        // 3. Nonce Replay Protection
        $nonce = $request->header('X-Agent-Nonce');
        if ($nonce) {
            $nonceKey = "agent_nonce_{$server->id}_{$nonce}";
            if (!Cache::add($nonceKey, 1, 600)) { // 10 minutes cache
                $this->eventService->recordEvent(
                    $server,
                    ServerEventType::AGENT_AUTH_FAILED,
                    "SECURITY WARNING: Duplicate agent nonce replay attempt detected on Server #{$server->id}",
                    ServerEventSeverity::CRITICAL,
                    ['nonce' => $nonce]
                );

                return response()->json([
                    'success' => false,
                    'error' => 'AGENT_REPLAY_DETECTED',
                    'message' => 'Duplicate request nonce detected.',
                ], 403);
            }
        }

        // 4. HMAC-SHA256 signature verification if signature header is provided
        $signature = $request->header('X-Agent-Signature');
        if ($signature) {
            $canonicalPayload = ($timestamp ?? '') . '.' . ($nonce ?? '') . '.' . $request->getContent();
            $expectedSignature = hash_hmac('sha256', $canonicalPayload, $token);

            if (!hash_equals($expectedSignature, $signature)) {
                $this->eventService->recordEvent(
                    $server,
                    ServerEventType::AGENT_AUTH_FAILED,
                    "Agent HMAC-SHA256 signature verification failed for Server #{$server->id}",
                    ServerEventSeverity::CRITICAL
                );

                return response()->json([
                    'success' => false,
                    'error' => 'AGENT_SIGNATURE_INVALID',
                    'message' => 'HMAC signature verification failed.',
                ], 403);
            }
        }

        // Bind resolved server to request attributes
        $request->attributes->set('agent_server', $server);

        return $next($request);
    }
}
