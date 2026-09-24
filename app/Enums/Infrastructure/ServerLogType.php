<?php

namespace App\Enums\Infrastructure;

enum ServerLogType: string
{
    case SYSTEM = 'system';
    case AUTH = 'auth';
    case SSH = 'ssh';
    case NGINX = 'nginx';
    case PHP = 'php';
    case MYSQL = 'mysql';
    case AGENT = 'agent';
    case SECURITY = 'security';
    case PROVISIONING = 'provisioning';
    case SERVICE = 'service';
}
