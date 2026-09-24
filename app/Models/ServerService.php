<?php

namespace App\Models;

use App\Enums\Infrastructure\ServerServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerService extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'service_name',
        'display_name',
        'service_type',
        'status',
        'version',
        'port',
        'enabled',
        'pid',
        'cpu_usage',
        'memory_usage',
        'last_checked_at',
        'metadata',
    ];

    protected $casts = [
        'status' => ServerServiceStatus::class,
        'enabled' => 'boolean',
        'port' => 'integer',
        'pid' => 'integer',
        'cpu_usage' => 'float',
        'memory_usage' => 'integer',
        'metadata' => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', ServerServiceStatus::RUNNING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ServerServiceStatus::FAILED);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
