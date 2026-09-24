<?php

namespace App\Enums\Infrastructure;

enum ServerServiceStatus: string
{
    case UNKNOWN = 'unknown';
    case RUNNING = 'running';
    case STOPPED = 'stopped';
    case FAILED = 'failed';
    case INACTIVE = 'inactive';
}
