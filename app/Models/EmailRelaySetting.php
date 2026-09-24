<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailRelaySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'mode',
        'provider',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'sender_domain',
        'last_test_at',
        'last_test_status',
        'last_test_log',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'port' => 'integer',
        'last_test_at' => 'datetime',
    ];
}
