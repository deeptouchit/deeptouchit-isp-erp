<?php

namespace App\Enums\Infrastructure;

enum ServerType: string
{
    case MASTER = 'master';
    case WORKER = 'worker';
    case WEB = 'web';
    case DATABASE = 'database';
    case MAIL = 'mail';
    case DNS = 'dns';
    case BACKUP = 'backup';

    public function label(): string
    {
        return match ($this) {
            self::MASTER => 'Master Node (Control Plane)',
            self::WORKER => 'Worker Node (General Compute)',
            self::WEB => 'Dedicated Web / Nginx Node',
            self::DATABASE => 'Dedicated Database Cluster Node',
            self::MAIL => 'Dedicated Mail Server Node',
            self::DNS => 'Dedicated Nameserver (DNS) Node',
            self::BACKUP => 'Dedicated Backup Storage Node',
        };
    }
}
