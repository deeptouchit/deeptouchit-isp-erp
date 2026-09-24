<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAutoResponder extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'email_domain_id',
        'email_account_id',
        'email',
        'from_name',
        'subject',
        'body',
        'is_html',
        'interval_hours',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'is_html' => 'boolean',
        'interval_hours' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function emailDomain(): BelongsTo
    {
        return $this->belongsTo(EmailDomain::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    /**
     * Determine if the autoresponder is currently active based on schedule.
     */
    public function getIsCurrentlyRunningAttribute(): bool
    {
        if ($this->status !== 'active') return false;

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;

        return true;
    }
}
