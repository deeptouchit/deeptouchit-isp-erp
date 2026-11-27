<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerSmsGateway extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_balance_checked_at' => 'datetime',
        'last_balance' => 'decimal:2',
    ];

    public function getMaskedCredentialsAttribute(): array
    {
        return collect($this->credentials ?? [])->mapWithKeys(
            fn ($value, $key) => [$key => $value === null || $value === '' ? '' : '••••••••••••']
        )->all();
    }
}
