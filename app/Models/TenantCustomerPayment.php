<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomerPayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_at' => 'datetime',
        'sms_sent' => 'boolean',
        'sms_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantCustomerInvoice::class, 'invoice_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(TenantDailyCashHandover::class, 'handover_id');
    }

    /**
     * Generate Unique Serialized Money Receipt / Invoice Number (e.g. REC-202609-1001)
     */
    public static function generateInvoiceNo(int $tenantId): string
    {
        $monthPrefix = Carbon::now()->format('Ym');
        $prefix = "REC-{$monthPrefix}-";

        $lastPayment = self::where('tenant_id', $tenantId)
            ->where('invoice_no', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1001;
        if ($lastPayment && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/i', $lastPayment->invoice_no, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        while (self::where('tenant_id', $tenantId)->where('invoice_no', "{$prefix}{$nextSeq}")->exists()) {
            $nextSeq++;
        }

        return "{$prefix}{$nextSeq}";
    }

    /**
     * Human-friendly payment method label
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match (strtolower($this->payment_method ?? 'cash')) {
            'cash' => __('Cash in Hand'),
            'bkash' => __('bKash (MFS)'),
            'bangla_qr', 'banglaqr' => __('Bangla QR'),
            'nagad' => __('Nagad (MFS)'),
            'rocket' => __('Rocket (MFS)'),
            'bank_transfer', 'bank' => __('Bank Transfer'),
            'pos' => __('Card / POS'),
            'online' => __('Online Gateway'),
            'reseller_wallet' => __('Reseller Wallet'),
            default => __(ucfirst($this->payment_method ?? 'Cash')),
        };
    }

    /**
     * Payment Method Icon Class
     */
    public function getMethodIconAttribute(): string
    {
        return match (strtolower($this->payment_method ?? 'cash')) {
            'cash' => 'fas fa-money-bill-wave text-emerald-600',
            'bkash' => 'fas fa-mobile-screen text-pink-600',
            'bangla_qr', 'banglaqr' => 'fas fa-qrcode text-emerald-700',
            'nagad' => 'fas fa-mobile-button text-orange-600',
            'rocket' => 'fas fa-wallet text-purple-600',
            'bank_transfer', 'bank' => 'fas fa-building-columns text-blue-600',
            'pos' => 'fas fa-credit-card text-indigo-600',
            'online' => 'fas fa-globe text-cyan-600',
            'reseller_wallet' => 'fas fa-handshake text-amber-600',
            default => 'fas fa-coins text-slate-600',
        };
    }

    /**
     * Formatted Month Label (e.g. September 2026)
     */
    public function getFormattedMonthAttribute(): string
    {
        if (empty($this->billing_month)) return '--';
        try {
            if (preg_match('/^\d{4}-\d{2}$/', $this->billing_month)) {
                return Carbon::createFromFormat('Y-m', $this->billing_month)->translatedFormat('F Y');
            }
            return Carbon::parse($this->billing_month)->translatedFormat('F Y');
        } catch (\Exception $e) {
            return $this->billing_month;
        }
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match (strtolower($this->status ?? 'paid')) {
            'completed', 'paid', 'approved' => [
                'label' => __('Completed'),
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check-circle',
                'dot' => 'bg-emerald-500'
            ],
            'pending' => [
                'label' => __('Pending'),
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
                'dot' => 'bg-amber-500'
            ],
            'void', 'refunded', 'cancelled' => [
                'label' => __('Voided'),
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
                'icon' => 'fa-ban',
                'dot' => 'bg-slate-400'
            ],
            default => [
                'label' => __(ucfirst($this->status ?? 'Paid')),
                'class' => 'bg-slate-50 text-slate-700 border-slate-200',
                'icon' => 'fa-circle-info',
                'dot' => 'bg-slate-400'
            ],
        };
    }
}
