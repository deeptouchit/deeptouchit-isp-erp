<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantInstallationTask extends Model
{
    use HasFactory;

    protected $table = 'tenant_installation_tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'activated_at' => 'datetime',
        'connection_fee' => 'decimal:2',
        'advance_payment' => 'decimal:2',
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

    public function package()
    {
        return $this->belongsTo(TenantInternetPackage::class, 'package_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function getStageNameAttribute(): string
    {
        return match ($this->installation_stage) {
            'feasibility_check' => '1. Feasibility & Port Check',
            'cable_pulling' => '2. Drop Cable Pulling',
            'splicing_power_test' => '3. Splicing & Power Test',
            'mikrotik_binding' => '4. Router / PPPoE Binding',
            'active_completed' => '5. Activated & Live',
            'cancelled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $this->installation_stage)),
        };
    }

    public function getStageBadgeColorAttribute(): string
    {
        return match ($this->installation_stage) {
            'feasibility_check' => 'bg-amber-50 text-amber-800 border-amber-200',
            'cable_pulling' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
            'splicing_power_test' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            'mikrotik_binding' => 'bg-purple-50 text-purple-800 border-purple-200',
            'active_completed' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
            'in_progress' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
