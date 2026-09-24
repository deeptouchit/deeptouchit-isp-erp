<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SftpUser extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'username',
        'password',
        'auth_type',
        'public_key',
        'path',
        'shell',
        'permissions',
        'status',
        'last_connected_at',
        'last_connected_ip',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
