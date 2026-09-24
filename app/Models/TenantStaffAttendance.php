<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStaffAttendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'punch_in_at' => 'datetime',
        'punch_out_at' => 'datetime',
        'work_duration_minutes' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Attendance status badge styling and localized label.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'present' => [
                'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
                'label' => 'Present',
            ],
            'late' => [
                'bg' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
                'label' => 'Late',
            ],
            'field_duty' => [
                'bg' => 'bg-blue-50 text-blue-700 border-blue-200',
                'dot' => 'bg-blue-500',
                'label' => 'Field Duty',
            ],
            'on_leave' => [
                'bg' => 'bg-purple-50 text-purple-700 border-purple-200',
                'dot' => 'bg-purple-500',
                'label' => 'On Leave',
            ],
            'half_day' => [
                'bg' => 'bg-orange-50 text-orange-700 border-orange-200',
                'dot' => 'bg-orange-500',
                'label' => 'Half Day',
            ],
            'absent' => [
                'bg' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot' => 'bg-rose-500',
                'label' => 'Absent',
            ],
            default => [
                'bg' => 'bg-slate-50 text-slate-700 border-slate-200',
                'dot' => 'bg-slate-400',
                'label' => ucfirst($this->status),
            ],
        };
    }

    /**
     * Human readable duration helper (e.g., 8h 15m).
     */
    public function getDurationFormattedAttribute(): string
    {
        if ($this->work_duration_minutes > 0) {
            $hours = floor($this->work_duration_minutes / 60);
            $mins = $this->work_duration_minutes % 60;
            return "{$hours}h {$mins}m";
        }

        if ($this->punch_in_at && !$this->punch_out_at) {
            $mins = $this->punch_in_at->diffInMinutes(now());
            $hours = floor($mins / 60);
            $remainingMins = $mins % 60;
            return "{$hours}h {$remainingMins}m (Running)";
        }

        return '--';
    }
}
