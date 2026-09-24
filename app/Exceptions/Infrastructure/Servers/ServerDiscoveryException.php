<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerDiscoveryException extends ServerException
{
    public function __construct(string $message, string $errorCode = 'SERVER_DISCOVERY_FAILED', bool $isRetryable = false, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
