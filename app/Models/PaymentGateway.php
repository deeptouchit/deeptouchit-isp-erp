<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'credentials',
        'mode',
        'fee_type',
        'fee_value',
        'min_amount',
        'max_amount',
        'sort_order',
        'is_active',
        'icon',
        'instructions',
    ];

    protected $casts = [
        'credentials' => 'array',
        'fee_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getWebhookUrlAttribute(): string
    {
        return url("/api/webhooks/payment/{$this->slug}");
    }

    public function getMaskedCredentialsAttribute(): array
    {
        $creds = $this->credentials ?? [];
        $masked = [];

        foreach ($creds as $k => $v) {
            if (is_string($v) && strlen($v) > 6 && !in_array($k, ['username', 'bank_name', 'account_name', 'branch_name'])) {
                $masked[$k] = substr($v, 0, 3) . '••••••••' . substr($v, -3);
            } else {
                $masked[$k] = $v;
            }
        }

        return $masked;
    }
}
