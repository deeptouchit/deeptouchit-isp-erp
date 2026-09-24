<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'ip_address',
        'is_subnet',
        'reason',
        'type',
        'expires_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_subnet' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $appends = ['is_expired', 'time_remaining'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->isAfter($this->expires_at);
    }

    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->expires_at) {
            return 'Permanent';
        }

        if ($this->is_expired) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans(['parts' => 2, 'short' => true]);
    }
}
