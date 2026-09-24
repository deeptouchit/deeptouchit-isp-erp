<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupStorage extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'path',
        'credentials',
        'is_default',
        'status',
        'capacity_bytes',
        'used_bytes',
        'retention_days',
        'encryption_enabled',
        'last_tested_at',
        'last_test_result',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_default' => 'boolean',
        'encryption_enabled' => 'boolean',
        'capacity_bytes' => 'integer',
        'used_bytes' => 'integer',
        'retention_days' => 'integer',
        'last_tested_at' => 'datetime',
    ];
}
