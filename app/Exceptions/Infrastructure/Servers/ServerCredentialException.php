<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerCredentialException extends ServerException
{
    public function __construct(string $message, string $errorCode = 'SERVER_CREDENTIAL_ERROR', bool $isRetryable = false, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
