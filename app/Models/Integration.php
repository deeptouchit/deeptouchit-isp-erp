<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = [
        'provider',
        'name',
        'category',
        'icon',
        'credentials',
        'settings',
        'status',
        'last_synced_at',
        'last_health_check',
        'health_status',
        'total_events',
    ];

    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'total_events' => 'integer',
        'last_synced_at' => 'datetime',
        'last_health_check' => 'datetime',
    ];
}
