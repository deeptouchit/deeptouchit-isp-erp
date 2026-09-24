<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBackup extends Model
{
    use HasFactory;

    protected $table = 'tenant_backups';

    protected $guarded = ['id'];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'tables_count' => 'integer',
        'records_count' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Human-Readable File Size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int)$this->file_size_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Backup Type Badge
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->backup_type) {
            'full_database' => [
                'label' => 'Full Backup',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-database',
            ],
            'subscribers_only' => [
                'label' => 'Customers & PPPoE',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-users',
            ],
            'mikrotik_rsc' => [
                'label' => 'Router Config',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-network-wired',
            ],
            'billing_ledger' => [
                'label' => 'Billing & Invoices',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-file-invoice-dollar',
            ],
            'system_config' => [
                'label' => 'System Settings',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-gears',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $this->backup_type)),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-box-archive',
            ],
        };
    }

    /**
     * Trigger Source Badge
     */
    public function getTriggerBadgeAttribute(): array
    {
        return match ($this->trigger_type) {
            'manual_admin' => [
                'label' => 'Manual',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-user-gear',
            ],
            'scheduled_cron', 'auto_daily' => [
                'label' => 'Auto Scheduled',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-clock',
            ],
            'pre_upgrade' => [
                'label' => 'System Update',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-shield-halved',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $this->trigger_type)),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-robot',
            ],
        };
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'completed' => [
                'label' => 'Completed',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
            ],
            'verified' => [
                'label' => 'Verified',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-shield-check',
            ],
            'in_progress' => [
                'label' => 'Creating...',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-spinner fa-spin',
            ],
            'failed' => [
                'label' => 'Failed',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => ucfirst($this->status),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-circle-info',
            ],
        };
    }
}
