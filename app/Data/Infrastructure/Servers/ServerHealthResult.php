<?php

namespace App\Data\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerHealthStatus;
use DateTimeInterface;

class ServerHealthResult
{
    public function __construct(
        public readonly ServerHealthStatus $status,
        public readonly bool $isOnline,
        public readonly bool $cpuWarning,
        public readonly bool $ramWarning,
        public readonly bool $diskWarning,
        public readonly int $servicesRunningCount,
        public readonly int $servicesFailedCount,
        public readonly string $message,
        public readonly DateTimeInterface $checkedAt
    ) {}
}
