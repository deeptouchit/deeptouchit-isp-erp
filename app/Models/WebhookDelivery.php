<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'response_time_ms',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_code' => 'integer',
        'response_time_ms' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
