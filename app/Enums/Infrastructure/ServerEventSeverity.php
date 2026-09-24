<?php

namespace App\Enums\Infrastructure;

enum ServerEventSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case EMERGENCY = 'emergency';
}
