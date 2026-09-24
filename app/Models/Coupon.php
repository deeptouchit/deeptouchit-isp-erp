<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'applies_to',
        'plan_ids',
        'billing_cycles',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'user_limit',
        'is_active',
        'starts_at',
        'expires_at',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'plan_ids' => 'array',
        'billing_cycles' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($coupon) {
            if (!empty($coupon->code)) {
                $coupon->code = strtoupper(trim($coupon->code));
            }
        });
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
