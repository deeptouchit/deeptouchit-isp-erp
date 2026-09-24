<?php

namespace App\Enums\Infrastructure;

enum MonitoringAlertStatus: string
{
    case OK = 'ok';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case RECOVERED = 'recovered';
    case SUPPRESSED = 'suppressed';
}
