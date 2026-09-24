<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'email_domain_id',
        'email',
        'password',
        'quota_mb',
        'used_quota_mb',
        'forward_to',
        'auto_responder',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'quota_mb' => 'integer',
        'used_quota_mb' => 'integer',
        'auto_responder' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function emailDomain(): BelongsTo
    {
        return $this->belongsTo(EmailDomain::class);
    }
}
