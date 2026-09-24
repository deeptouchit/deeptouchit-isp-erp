<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSmsGateway extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'custom_headers' => 'array',
        'custom_params' => 'array',
        'cost_per_sms' => 'float',
        'balance' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
