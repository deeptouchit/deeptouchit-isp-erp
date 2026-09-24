<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiUser extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'role',
        'scopes',
        'ip_restrictions',
        'rate_limit_multiplier',
        'status',
        'last_activity_at',
        'last_activity_ip',
        'total_calls',
    ];

    protected $casts = [
        'scopes' => 'array',
        'ip_restrictions' => 'array',
        'rate_limit_multiplier' => 'integer',
        'total_calls' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
