<?php

namespace App\Data\Infrastructure\Servers;

use Illuminate\Support\Str;

class ServerOperationResult
{
    public function __construct(
        public readonly string $operationId,
        public readonly int $serverId,
        public readonly string $action,
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $errorCode = null,
        public readonly array $data = [],
        public readonly float $durationMs = 0.0
    ) {}

    public static function success(
        int $serverId,
        string $action,
        string $message,
        array $data = [],
        float $durationMs = 0.0,
        ?string $operationId = null
    ): self {
        return new self(
            operationId: $operationId ?? (string) Str::uuid(),
            serverId: $serverId,
            action: $action,
            success: true,
            message: $message,
            errorCode: null,
            data: $data,
            durationMs: $durationMs
        );
    }

    public static function failure(
        int $serverId,
        string $action,
        string $message,
        string $errorCode,
        array $data = [],
        float $durationMs = 0.0,
        ?string $operationId = null
    ): self {
        return new self(
            operationId: $operationId ?? (string) Str::uuid(),
            serverId: $serverId,
            action: $action,
            success: false,
            message: $message,
            errorCode: $errorCode,
            data: $data,
            durationMs: $durationMs
        );
    }
}
