<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'cpu_usage',
        'memory_total',
        'memory_used',
        'memory_available',
        'disk_total',
        'disk_used',
        'disk_usage',
        'load_1m',
        'load_5m',
        'load_15m',
        'network_rx',
        'network_tx',
        'disk_read',
        'disk_write',
        'process_count',
        'open_file_descriptors',
        'active_tcp_connections',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'memory_available' => 'integer',
        'disk_total' => 'integer',
        'disk_used' => 'integer',
        'disk_usage' => 'float',
        'load_1m' => 'float',
        'load_5m' => 'float',
        'load_15m' => 'float',
        'network_rx' => 'integer',
        'network_tx' => 'integer',
        'disk_read' => 'integer',
        'disk_write' => 'integer',
        'process_count' => 'integer',
        'open_file_descriptors' => 'integer',
        'active_tcp_connections' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }
}
