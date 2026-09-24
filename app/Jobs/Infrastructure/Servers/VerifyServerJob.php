<?php

namespace App\Jobs\Infrastructure\Servers;

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

class VerifyServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];

    public function __construct(public Server $server) {}

    /**
     * Unique key ensuring no concurrent duplicate verification jobs for the same node.
     */
    public function uniqueId(): string
    {
        return "verify_server_{$this->server->id}";
    }

    public function handle(ServerVerificationInterface $verificationService): void
    {
        Log::info("[VerifyServerJob] Processing verification job for Server #{$this->server->id}");
        $result = $verificationService->verify($this->server);

        if (!$result->success && $result->isRetryable) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 60);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("[VerifyServerJob] Permanent failure verifying Server #{$this->server->id}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
