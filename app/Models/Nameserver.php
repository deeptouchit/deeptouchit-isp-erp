<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nameserver extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostname',
        'ip_address',
        'ipv6_address',
        'is_primary',
        'is_default',
        'status',
        'last_checked_at',
        'check_status',
        'response_time_ms',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_default' => 'boolean',
        'last_checked_at' => 'datetime',
        'response_time_ms' => 'integer',
    ];
}
