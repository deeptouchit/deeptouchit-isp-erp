<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRateLimit extends Model
{
    protected $fillable = [
        'name',
        'scope_type',
        'endpoint_pattern',
        'requests_per_minute',
        'burst_capacity',
        'action_on_breach',
        'ban_duration_minutes',
        'status',
        'total_breaches',
        'last_breached_at',
    ];

    protected $casts = [
        'requests_per_minute' => 'integer',
        'burst_capacity' => 'integer',
        'ban_duration_minutes' => 'integer',
        'total_breaches' => 'integer',
        'last_breached_at' => 'datetime',
    ];
}
