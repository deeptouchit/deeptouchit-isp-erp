<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key_prefix',
        'secret_hash',
        'abilities',
        'ip_allowlist',
        'rate_limit_per_minute',
        'status',
        'last_used_at',
        'last_used_ip',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'ip_allowlist' => 'array',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
