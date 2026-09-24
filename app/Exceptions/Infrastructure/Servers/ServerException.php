<?php

namespace App\Exceptions\Infrastructure\Servers;

use Exception;

class ServerException extends Exception
{
    protected string $errorCode;
    protected bool $isRetryable;
    protected array $context;

    public function __construct(
        string $message = '',
        string $errorCode = 'SERVER_ERROR',
        bool $isRetryable = false,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->isRetryable = $isRetryable;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
