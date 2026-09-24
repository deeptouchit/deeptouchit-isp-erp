<?php

namespace App\Jobs\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerDiscoveryInterface;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscoverServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;
    public array $backoff = [15, 45];

    public function __construct(public Server $server) {}

    public function uniqueId(): string
    {
        return "discover_server_{$this->server->id}";
    }

    public function handle(ServerDiscoveryInterface $discoveryService): void
    {
        Log::info("[DiscoverServerJob] Running hardware discovery for Server #{$this->server->id}");
        $discoveryService->discover($this->server);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("[DiscoverServerJob] Failed hardware discovery for Server #{$this->server->id}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
