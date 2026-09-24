<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerMetricsValidationException extends ServerException
{
    public function __construct(string $message, string $errorCode = 'SERVER_METRICS_INVALID', bool $isRetryable = false, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
