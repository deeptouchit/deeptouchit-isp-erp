<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerLifecycleException extends ServerException
{
    public function __construct(string $message, string $errorCode = 'SERVER_INVALID_STATE_TRANSITION', bool $isRetryable = false, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
