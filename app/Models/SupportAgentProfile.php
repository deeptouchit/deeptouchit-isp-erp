<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportAgentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'job_title',
        'department_slugs',
        'signature',
        'max_active_tickets',
        'is_auto_assignable',
        'is_online',
        'rating',
    ];

    protected $casts = [
        'department_slugs' => 'array',
        'max_active_tickets' => 'integer',
        'is_auto_assignable' => 'boolean',
        'is_online' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
