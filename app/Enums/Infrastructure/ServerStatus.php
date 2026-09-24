<?php

namespace App\Enums\Infrastructure;

enum ServerStatus: string
{
    case PENDING = 'pending';
    case VERIFYING = 'verifying';
    case VERIFIED = 'verified';
    case PROVISIONING = 'provisioning';
    case ACTIVE = 'active';
    case ONLINE = 'online';
    case WARNING = 'warning';
    case OFFLINE = 'offline';
    case MAINTENANCE = 'maintenance';
    case SUSPENDED = 'suspended';
    case DECOMMISSIONING = 'decommissioning';
    case DECOMMISSIONED = 'decommissioned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Registration',
            self::VERIFYING => 'Verifying SSH',
            self::VERIFIED => 'Verified',
            self::PROVISIONING => 'Provisioning Stack',
            self::ACTIVE, self::ONLINE => 'Online',
            self::WARNING => 'High Load / Warning',
            self::OFFLINE => 'Offline / Unreachable',
            self::MAINTENANCE => 'Maintenance Mode',
            self::SUSPENDED => 'Suspended',
            self::DECOMMISSIONING => 'Decommissioning',
            self::DECOMMISSIONED => 'Decommissioned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE, self::ONLINE, self::VERIFIED => 'emerald',
            self::WARNING => 'amber',
            self::OFFLINE, self::SUSPENDED, self::DECOMMISSIONED => 'rose',
            self::MAINTENANCE => 'purple',
            self::PENDING, self::VERIFYING, self::PROVISIONING, self::DECOMMISSIONING => 'blue',
        };
    }
}
