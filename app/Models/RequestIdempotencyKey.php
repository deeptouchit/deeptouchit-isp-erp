<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestIdempotencyKey extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'response_body' => 'array',
        'completed_at' => 'datetime',
    ];
}
