<?php

namespace App\Enums\Infrastructure;

enum MonitoringAlertType: string
{
    case CPU_HIGH = 'cpu_high';
    case MEMORY_HIGH = 'memory_high';
    case DISK_HIGH = 'disk_high';
    case LOAD_HIGH = 'load_high';
    case SERVER_OFFLINE = 'server_offline';
    case AGENT_OFFLINE = 'agent_offline';
    case HEARTBEAT_DELAYED = 'heartbeat_delayed';
    case SERVICE_DOWN = 'service_down';
    case PHP_FPM_DOWN = 'php_fpm_down';
    case NETWORK_ERRORS = 'network_errors';
}
