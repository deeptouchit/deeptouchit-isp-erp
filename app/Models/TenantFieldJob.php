<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantFieldJob extends Model
{
    use HasFactory;

    protected $table = 'tenant_field_jobs';

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'materials_used' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    public function coverageZone()
    {
        return $this->belongsTo(TenantCoverageZone::class, 'zone_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getJobTypeNameAttribute(): string
    {
        return match ($this->job_type) {
            'fiber_splicing' => 'Fiber Splicing & Optical Link',
            'onu_replacement' => 'ONU Replacement / Upgrade',
            'home_visit' => 'Home Visit & Diagnostics',
            'cable_repair' => 'Drop Cable Re-pulling / Repair',
            'pop_maintenance' => 'POP Node & Splitter Maintenance',
            'new_connection' => 'New Physical Line Installation',
            default => ucwords(str_replace('_', ' ', $this->job_type)),
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
            'high' => 'bg-amber-50 text-amber-800 border-amber-200',
            'medium' => 'bg-blue-50 text-blue-700 border-blue-200',
            'low' => 'bg-slate-100 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dispatched' => 'bg-blue-50 text-blue-700 border-blue-200',
            'in_progress' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
