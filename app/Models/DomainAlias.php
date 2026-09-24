<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'domain',
        'target_type',
        'redirect_url',
        'redirect_status_code',
        'ssl_status',
        'ssl_expires_at',
        'ssl_last_renewed_at',
        'auto_ssl',
        'status',
    ];

    protected $casts = [
        'redirect_status_code' => 'integer',
        'auto_ssl' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'ssl_last_renewed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
