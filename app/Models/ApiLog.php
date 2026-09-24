<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'api_key_id',
        'api_user_id',
        'method',
        'endpoint',
        'status_code',
        'ip_address',
        'user_agent',
        'duration_ms',
        'request_headers',
        'request_payload',
        'response_body',
        'error_message',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_payload' => 'array',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function apiUser()
    {
        return $this->belongsTo(ApiUser::class);
    }
}
