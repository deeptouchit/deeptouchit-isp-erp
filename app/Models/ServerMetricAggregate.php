<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetricAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'period_start',
        'interval',
        'cpu_avg',
        'cpu_min',
        'cpu_max',
        'ram_avg',
        'ram_max',
        'disk_avg',
        'disk_max',
        'load_1m_avg',
        'network_rx_total',
        'network_tx_total',
        'sample_count',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'cpu_avg' => 'float',
        'cpu_min' => 'float',
        'cpu_max' => 'float',
        'ram_avg' => 'float',
        'ram_max' => 'float',
        'disk_avg' => 'float',
        'disk_max' => 'float',
        'load_1m_avg' => 'float',
        'network_rx_total' => 'integer',
        'network_tx_total' => 'integer',
        'sample_count' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
