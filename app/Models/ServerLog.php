<?php

namespace App\Models;

use App\Enums\Infrastructure\ServerLogLevel;
use App\Enums\Infrastructure\ServerLogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'log_type',
        'level',
        'message',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'log_type' => ServerLogType::class,
        'level' => ServerLogLevel::class,
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', [ServerLogLevel::ERROR, ServerLogLevel::CRITICAL]);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }
}
