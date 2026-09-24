<?php

namespace App\Models;

use App\Enums\Infrastructure\MonitoringAlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'alert_type',
        'warning_threshold',
        'critical_threshold',
        'duration_seconds',
        'cooldown_seconds',
        'enabled',
        'channels',
    ];

    protected $casts = [
        'alert_type' => MonitoringAlertType::class,
        'warning_threshold' => 'float',
        'critical_threshold' => 'float',
        'duration_seconds' => 'integer',
        'cooldown_seconds' => 'integer',
        'enabled' => 'boolean',
        'channels' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
