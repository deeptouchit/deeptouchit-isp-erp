<?php

namespace App\Jobs\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerDiscoveryInterface;
use App\Contracts\Infrastructure\Servers\ServerHealthInterface;
use App\Contracts\Infrastructure\Servers\ServerVerificationInterface;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public Server $server) {}

    public function uniqueId(): string
    {
        return "sync_server_{$this->server->id}";
    }

    public function handle(
        ServerVerificationInterface $verificationService,
        ServerDiscoveryInterface $discoveryService,
        ServerHealthInterface $healthService
    ): void {
        Log::info("[SyncServerJob] Running complete cluster synchronization for Server #{$this->server->id}");

        // 1. Verify connectivity
        $verificationResult = $verificationService->verify($this->server);

        if ($verificationResult->success) {
            // 2. Discover hardware & daemons
            $discoveryService->discover($this->server);

            // 3. Evaluate health
            $healthService->checkHealth($this->server);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("[SyncServerJob] Cluster synchronization failed for Server #{$this->server->id}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
