<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'content_type',
        'verify_ssl',
        'status',
        'last_triggered_at',
        'last_response_code',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'verify_ssl' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_response_code' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
