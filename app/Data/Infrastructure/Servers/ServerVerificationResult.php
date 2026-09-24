<?php

namespace App\Data\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerStatus;

class ServerVerificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ServerStatus $status,
        public readonly string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $osFamily = null,
        public readonly ?string $detectedArchitecture = null,
        public readonly float $executionDurationMs = 0.0,
        public readonly bool $isRetryable = false,
        public readonly array $context = []
    ) {}

    public static function successful(
        string $message = 'Server connectivity and credentials verified successfully.',
        ?string $osFamily = 'Linux',
        ?string $detectedArchitecture = 'x86_64',
        float $durationMs = 0.0,
        array $context = []
    ): self {
        return new self(
            success: true,
            status: ServerStatus::VERIFIED,
            message: $message,
            errorCode: null,
            osFamily: $osFamily,
            detectedArchitecture: $detectedArchitecture,
            executionDurationMs: $durationMs,
            isRetryable: false,
            context: $context
        );
    }

    public static function failed(
        string $message,
        string $errorCode = 'SERVER_VERIFICATION_FAILED',
        bool $isRetryable = false,
        float $durationMs = 0.0,
        array $context = []
    ): self {
        return new self(
            success: false,
            status: ServerStatus::OFFLINE,
            message: $message,
            errorCode: $errorCode,
            osFamily: null,
            detectedArchitecture: null,
            executionDurationMs: $durationMs,
            isRetryable: $isRetryable,
            context: $context
        );
    }
}
