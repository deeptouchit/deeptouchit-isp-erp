<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'mail_enabled' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'wallet_balance' => 'float',
    ];

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class)->latestOfMany();
    }

    public function invoices()
    {
        return $this->hasMany(SaasInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function wallet()
    {
        return $this->hasOne(TenantWallet::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(TenantActivityLog::class)->latest();
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class)->latest();
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class)->latest();
    }

    public function getTotalSmsCountAttribute(): int
    {
        return $this->smsLogs()->count();
    }

    public function getTotalSmsCostAttribute(): float
    {
        return (float) $this->smsLogs()->where('status', '!=', 'failed')->sum('total_cost');
    }

    public function getAdminUserAttribute()
    {
        return $this->users()->where('role', 'isp_admin')->first();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
