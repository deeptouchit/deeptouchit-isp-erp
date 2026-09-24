<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDailyCashHandover extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'handover_date' => 'date',
        'system_collected_amount' => 'decimal:2',
        'handed_over_amount' => 'decimal:2',
        'shortage_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'digital_collected_amount' => 'decimal:2',
        'total_receipts_count' => 'integer',
        'denominations' => 'array',
        'verified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Generate Unique Serialized Handover Voucher Number (e.g. HND-202609-1001)
     */
    public static function generateHandoverNo(int $tenantId, ?string $date = null): string
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::now();
        $prefix = 'HND-' . $targetDate->format('Ym') . '-';

        $lastHandover = self::where('tenant_id', $tenantId)
            ->where('handover_no', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1001;
        if ($lastHandover && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/i', $lastHandover->handover_no, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        while (self::where('tenant_id', $tenantId)->where('handover_no', "{$prefix}{$nextSeq}")->exists()) {
            $nextSeq++;
        }

        return "{$prefix}{$nextSeq}";
    }

    /**
     * Human-friendly shift label
     */
    public function getShiftLabelAttribute(): string
    {
        return match (strtolower($this->shift_type ?? 'daily')) {
            'morning' => 'Morning Shift',
            'evening' => 'Evening Shift',
            'night' => 'Night Shift',
            'full_day' => 'Full Day Closing',
            default => 'Daily Closing',
        };
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match (strtolower($this->status ?? 'pending')) {
            'approved' => [
                'label' => 'Approved',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
                'dot' => 'bg-emerald-500'
            ],
            'pending' => [
                'label' => 'Pending Verification',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
                'dot' => 'bg-amber-500'
            ],
            'rejected' => [
                'label' => 'Rejected',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
                'dot' => 'bg-rose-500'
            ],
            default => [
                'label' => ucfirst($this->status ?? 'Pending'),
                'class' => 'bg-slate-50 text-slate-700 border-slate-200',
                'icon' => 'fa-circle-info',
                'dot' => 'bg-slate-400'
            ],
        };
    }

    /**
     * Discrepancy Status Badge
     */
    public function getDiscrepancyBadgeAttribute(): array
    {
        if ($this->shortage_amount > 0) {
            return [
                'label' => 'Shortage',
                'amount' => (float) $this->shortage_amount,
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'type' => 'shortage',
            ];
        }
        if ($this->excess_amount > 0) {
            return [
                'label' => 'Excess',
                'amount' => (float) $this->excess_amount,
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'type' => 'excess',
            ];
        }
        return [
            'label' => 'Balanced (0.00)',
            'amount' => 0.00,
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'type' => 'balanced',
        ];
    }
}
