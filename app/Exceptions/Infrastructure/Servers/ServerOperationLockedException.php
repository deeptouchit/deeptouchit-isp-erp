<?php

namespace App\Exceptions\Infrastructure\Servers;

class ServerOperationLockedException extends ServerException
{
    public function __construct(string $message = 'Server operation is currently locked by another concurrent process.', string $errorCode = 'SERVER_OPERATION_LOCKED', bool $isRetryable = true, array $context = [])
    {
        parent::__construct($message, $errorCode, $isRetryable, $context);
    }
}
