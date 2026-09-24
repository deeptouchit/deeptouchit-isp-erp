<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerVerificationException extends ServerException
{
    public function __construct(string $message, string $errorCode = 'SERVER_VERIFICATION_FAILED', bool $isRetryable = false, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
