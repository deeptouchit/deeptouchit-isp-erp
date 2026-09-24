<?php

namespace App\Models;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'event_type',
        'severity',
        'message',
        'metadata',
        'occurred_at',
        'resolved_at',
    ];

    protected $casts = [
        'event_type' => ServerEventType::class,
        'severity' => ServerEventSeverity::class,
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('severity', [ServerEventSeverity::CRITICAL, ServerEventSeverity::EMERGENCY]);
    }
}
