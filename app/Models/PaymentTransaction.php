<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'gateway_response' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeWithoutWalletCheckouts(Builder $query): Builder
    {
        return $query->where('payment_method', '!=', 'reseller_wallet');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SaasInvoice::class, 'saas_invoice_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }
}
