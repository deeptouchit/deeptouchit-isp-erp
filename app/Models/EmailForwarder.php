<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailForwarder extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'email_domain_id',
        'source',
        'destination',
        'keep_local_copy',
        'status',
    ];

    protected $casts = [
        'keep_local_copy' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function emailDomain(): BelongsTo
    {
        return $this->belongsTo(EmailDomain::class);
    }

    /**
     * Get array of destination addresses.
     */
    public function getDestinationsListAttribute(): array
    {
        if (empty($this->destination)) return [];
        return array_map('trim', explode(',', $this->destination));
    }
}
