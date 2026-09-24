<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'payment_id',
        'transaction_number',
        'type',
        'category',
        'description',
        'amount',
        'currency',
        'payment_method',
        'gateway_reference',
        'status',
        'notes',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $prefix = 'TRX-';
                $dateStr = now()->format('Ymd');
                $random = strtoupper(substr(uniqid(), -6));
                $transaction->transaction_number = "{$prefix}{$dateStr}-{$random}";
            }
            if (empty($transaction->transacted_at)) {
                $transaction->transacted_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
