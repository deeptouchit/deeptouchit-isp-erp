<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialLedgerEntry extends Model
{
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:4',
        'metadata' => 'array',
    ];
}
