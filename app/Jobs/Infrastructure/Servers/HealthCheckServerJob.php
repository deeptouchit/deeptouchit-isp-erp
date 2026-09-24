<?php

namespace App\Jobs\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\ServerHealthInterface;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthCheckServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Server $server) {}

    public function uniqueId(): string
    {
        return "health_check_server_{$this->server->id}";
    }

    public function handle(ServerHealthInterface $healthService): void
    {
        Log::info("[HealthCheckServerJob] Checking health status for Server #{$this->server->id}");
        $healthService->checkHealth($this->server);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("[HealthCheckServerJob] Health check failed for Server #{$this->server->id}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
