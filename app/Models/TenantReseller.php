<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantReseller extends Model
{
    use HasFactory;

    protected $table = 'tenant_resellers';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'prefix',
        'contact_person',
        'mobile',
        'email',
        'address',
        'billing_type',
        'wallet_balance',
        'credit_limit',
        'commission_rate',
        'monthly_panel_charge',
        'panel_expiry_date',
        'panel_billing_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'monthly_panel_charge' => 'decimal:2',
        'panel_expiry_date' => 'date',
    ];

    protected $appends = [
        'billing_badge',
        'status_badge',
        'panel_badge',
        'total_available_balance',
    ];

    /**
     * Parent Tenant / ISP
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Calculate Reseller Cost after Commission Discount
     * E.g. retailPrice ৳1000 with commission_rate 30% => ৳700
     */
    public function calculateResellerCost(float $retailPrice): float
    {
        $rate = (float) ($this->commission_rate ?? 0);
        $discount = ($retailPrice * $rate) / 100;
        return max(0, $retailPrice - $discount);
    }

    /**
     * Calculate Reseller Commission Amount
     * E.g. retailPrice ৳1000 with commission_rate 30% => ৳300
     */
    public function calculateCommissionAmount(float $retailPrice): float
    {
        $rate = (float) ($this->commission_rate ?? 0);
        return ($retailPrice * $rate) / 100;
    }

    /**
     * Reseller's Staff Users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    /**
     * Reseller's Retail Customers
     */
    public function customers(): HasMany
    {
        return $this->hasMany(TenantCustomer::class, 'reseller_id');
    }

    /**
     * Total available balance including credit limit
     */
    public function getTotalAvailableBalanceAttribute(): float
    {
        return (float) ($this->wallet_balance + $this->credit_limit);
    }

    /**
     * Billing type visual badge styling
     */
    public function getBillingBadgeAttribute(): array
    {
        return match ($this->billing_type) {
            'POSTPAID_MONTHLY' => [
                'label' => 'Postpaid Monthly',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-file-invoice-dollar',
            ],
            'BANDWIDTH_WHOLESALE' => [
                'label' => 'Bandwidth Wholesale',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-gauge-high',
            ],
            default => [
                'label' => 'Prepaid Wallet',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-wallet',
            ],
        };
    }

    /**
     * Operational Status visual badge styling
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'suspended' => [
                'label' => 'Suspended',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            'inactive' => [
                'label' => 'Inactive',
                'class' => 'bg-slate-50 text-slate-600 border-slate-200',
                'icon' => 'fa-pause',
            ],
            default => [
                'label' => 'Active',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
            ],
        };
    }

    /**
     * Panel Subscription Status badge
     */
    public function getPanelBadgeAttribute(): array
    {
        return match ($this->panel_billing_status) {
            'EXPIRED' => [
                'label' => 'Panel Expired',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-triangle-exclamation',
            ],
            'GRACE_PERIOD' => [
                'label' => 'Grace Period',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
            ],
            default => [
                'label' => 'Panel Active',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check',
            ],
        };
    }

    /**
     * Reseller Invoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(TenantResellerInvoice::class, 'reseller_id');
    }

    /**
     * Latest Bandwidth Wholesale Invoice
     */
    public function latestBandwidthInvoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TenantResellerInvoice::class, 'reseller_id')
            ->where('type', 'BANDWIDTH_WHOLESALE')
            ->latestOfMany();
    }

    /**
     * Bandwidth Allocation Profile
     */
    public function bandwidthAllocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TenantResellerBandwidth::class, 'reseller_id');
    }

    /**
     * Generate Next Reseller Code for a Tenant (e.g. RES-001)
     */
    public static function generateNextCode(int $tenantId): string
    {
        $lastId = self::where('tenant_id', $tenantId)->max('id') ?? 0;
        return 'RES-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
    }
}
