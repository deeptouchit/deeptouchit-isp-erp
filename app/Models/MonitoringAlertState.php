<?php

namespace App\Models;

use App\Enums\Infrastructure\MonitoringAlertStatus;
use App\Enums\Infrastructure\MonitoringAlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlertState extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'alert_type',
        'resource_identity',
        'state',
        'severity',
        'current_value',
        'threshold_value',
        'fingerprint',
        'started_at',
        'last_escalated_at',
        'resolved_at',
        'suppressed_until',
        'suppression_reason',
        'notification_dispatched_at',
    ];

    protected $casts = [
        'alert_type' => MonitoringAlertType::class,
        'state' => MonitoringAlertStatus::class,
        'started_at' => 'datetime',
        'last_escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'suppressed_until' => 'datetime',
        'notification_dispatched_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function generateFingerprint(int $serverId, string $alertType, string $resourceIdentity = 'global'): string
    {
        return hash('sha256', "{$serverId}:{$alertType}:{$resourceIdentity}");
    }
}
