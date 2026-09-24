<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantWalletTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];

    public function wallet()
    {
        return $this->belongsTo(TenantWallet::class, 'wallet_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
