<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomerInvoice extends Model
{
    use HasFactory;

    protected $table = 'tenant_customer_invoices';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat_tax' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'auto_generated' => 'boolean',
    ];

    /**
     * Tenant Relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Sub-ISP / Reseller Relationship
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    /**
     * Customer Subscriber Relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    /**
     * Package Relationship
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(TenantInternetPackage::class, 'package_id');
    }

    /**
     * Staff Creator Relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate Unique Serialized Invoice Number (e.g. INV-202609-1001)
     */
    public static function generateInvoiceNo(int $tenantId, ?string $billingMonth = null): string
    {
        $monthPrefix = $billingMonth ? str_replace('-', '', $billingMonth) : Carbon::now()->format('Ym');
        $prefix = "INV-{$monthPrefix}-";

        $lastInvoice = self::where('tenant_id', $tenantId)
            ->where('invoice_no', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1001;
        if ($lastInvoice && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/i', $lastInvoice->invoice_no, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        while (self::where('tenant_id', $tenantId)->where('invoice_no', "{$prefix}{$nextSeq}")->exists()) {
            $nextSeq++;
        }

        return "{$prefix}{$nextSeq}";
    }

    /**
     * Exact MikroTik Provisioned Package / Profile Name (e.g. 10Mbps, 15Mbps)
     */
    public function getMikrotikPackageNameAttribute(): string
    {
        if ($this->package) {
            return $this->package->name ?: ($this->package->mikrotik_profile ?: ($this->package->package_name ?: 'default'));
        }
        if ($this->customer) {
            return $this->customer->mikrotik_profile_name ?: ($this->customer->package ? ($this->customer->package->name ?: ($this->customer->package->mikrotik_profile ?: $this->customer->package->package_name)) : ($this->customer->package_name ?: ($this->package_name ?: 'default')));
        }
        return $this->package_name ?: 'default';
    }

    /**
     * Formatted Month Label (e.g. September 2026)
     */
    public function getFormattedMonthAttribute(): string
    {
        if (empty($this->billing_month)) return '--';
        try {
            return Carbon::createFromFormat('Y-m', $this->billing_month)->format('F Y');
        } catch (\Exception $e) {
            return $this->billing_month;
        }
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'paid' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check-circle',
                'dot' => 'bg-emerald-500'
            ],
            'partially_paid' => [
                'label' => 'Partial',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-circle-half-stroke',
                'dot' => 'bg-amber-500'
            ],
            'unpaid' => [
                'label' => 'Unpaid',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-clock',
                'dot' => 'bg-rose-500'
            ],
            'overdue' => [
                'label' => 'Overdue',
                'class' => 'bg-red-50 text-red-800 border-red-200',
                'icon' => 'fa-triangle-exclamation',
                'dot' => 'bg-red-600'
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
                'icon' => 'fa-ban',
                'dot' => 'bg-slate-400'
            ],
            default => [
                'label' => ucfirst($this->status),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-circle-info',
                'dot' => 'bg-slate-500'
            ],
        };
    }

    /**
     * Human-friendly payment method label
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match (strtolower($this->payment_method ?? '')) {
            'cash' => 'Cash in Hand',
            'bkash' => 'bKash (MFS)',
            'nagad' => 'Nagad (MFS)',
            'rocket' => 'Rocket (MFS)',
            'pos' => 'POS / Handheld Terminal',
            'online' => 'Online Gateway',
            'bank' => 'Bank Transfer / Cheque',
            'wallet' => 'Customer Wallet Balance',
            default => $this->payment_method ? ucfirst($this->payment_method) : '--',
        };
    }
}
