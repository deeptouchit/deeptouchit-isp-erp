<?php

namespace App\Enums\Infrastructure;

enum ServerHealthStatus: string
{
    case UNKNOWN = 'unknown';
    case HEALTHY = 'healthy';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case OFFLINE = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown / Pending Check',
            self::HEALTHY => 'Healthy (Nominal)',
            self::WARNING => 'Degraded Performance',
            self::CRITICAL => 'Critical Health Alert',
            self::OFFLINE => 'Node Unresponsive',
        };
    }
}
