<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'role_badge',
        'scope_badge',
        'status_badge',
        'avatar_url',
    ];

    /**
     * Get avatar image URL or null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!empty($this->avatar)) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller()
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function assignedCustomers()
    {
        return $this->hasMany(TenantCustomer::class, 'assigned_collector_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->role === 'super_admin';
    }

    public function isIspAdmin(): bool
    {
        return $this->role === 'isp_admin';
    }

    public function isResellerUser(): bool
    {
        return !empty($this->reseller_id) || str_starts_with($this->role ?? '', 'reseller_');
    }

    public function isResellerAdmin(): bool
    {
        if ($this->isOwner() || $this->isIspAdmin()) {
            return true;
        }
        return in_array($this->role, ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'], true);
    }

    public function isResellerCollector(): bool
    {
        return in_array($this->role, ['reseller_collector', 'collector', 'isp_collector'], true);
    }

    public function isCollector(): bool
    {
        return in_array($this->role, ['isp_collector', 'collector', 'reseller_collector'], true);
    }

    public function isResellerTech(): bool
    {
        return in_array($this->role, ['reseller_technician', 'technician', 'isp_technician', 'isp_noc', 'isp_lineman'], true);
    }

    public function isTechnician(): bool
    {
        return in_array($this->role, ['isp_technician', 'technician', 'reseller_technician', 'isp_noc', 'isp_lineman'], true);
    }

    /**
     * Tenant Role relationship (dynamic RBAC)
     */
    public function tenantRole()
    {
        return $this->hasOne(TenantRole::class, 'name', 'role')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner() || $this->role === 'isp_admin') {
            return true;
        }

        if (empty($this->role) || empty($this->tenant_id)) {
            return false;
        }

        // Per-request static cache for maximum speed
        static $permissionsCache = [];
        $cacheKey = "{$this->tenant_id}_{$this->role}";

        if (!isset($permissionsCache[$cacheKey])) {
            $roleObj = TenantRole::where('tenant_id', $this->tenant_id)
                ->where('name', $this->role)
                ->where('status', 'active')
                ->first();

            $perms = $roleObj ? ($roleObj->permissions ?? []) : [];
            if ($this->isCollector()) {
                $perms = array_unique(array_merge($perms, ['customers.view', 'billing.collect', 'billing.cash_handover', 'support.tickets']));
            } elseif ($this->isTechnician()) {
                $perms = array_unique(array_merge($perms, ['customers.view', 'support.tickets', 'support.field_jobs', 'support.installations']));
            }

            $permissionsCache[$cacheKey] = $perms;
        }

        return in_array($permission, $permissionsCache[$cacheKey], true);
    }

    /**
     * Check if user has any of given roles
     */
    public function hasRole(string ...$roles): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($this->role, $roles, true);
    }

    /**
     * Role Visual Badge
     */
    public function getRoleBadgeAttribute(): array
    {
        // Check dynamic custom TenantRole first if tenant_id is set
        if ($this->tenant_id && $this->role) {
            static $roleBadgeCache = [];
            $cacheKey = "{$this->tenant_id}_{$this->role}";
            if (!isset($roleBadgeCache[$cacheKey])) {
                $customRole = TenantRole::where('tenant_id', $this->tenant_id)
                    ->where('name', $this->role)
                    ->first();
                $roleBadgeCache[$cacheKey] = $customRole ? $customRole->badge : null;
            }
            if ($roleBadgeCache[$cacheKey]) {
                return $roleBadgeCache[$cacheKey];
            }
        }

        return match ($this->role) {
            'isp_admin' => [
                'label' => 'ISP Super Admin',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-shield-halved',
            ],
            'isp_manager' => [
                'label' => 'ISP Manager',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-user-tie',
            ],
            'isp_technician', 'technician' => [
                'label' => 'NOC / Field Tech',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-screwdriver-wrench',
            ],
            'isp_collector', 'collector' => [
                'label' => 'Bill Collector',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-money-bill-wave',
            ],
            'reseller_admin' => [
                'label' => 'Sub-ISP Admin',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-crown',
            ],
            'reseller_manager' => [
                'label' => 'Sub-ISP Manager',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200',
                'icon' => 'fa-user-gear',
            ],
            'reseller_technician' => [
                'label' => 'Sub-ISP Tech',
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
                'icon' => 'fa-wrench',
            ],
            'reseller_collector' => [
                'label' => 'Sub-ISP Collector',
                'class' => 'bg-orange-50 text-orange-700 border-orange-200',
                'icon' => 'fa-hand-holding-dollar',
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $this->role ?? 'Staff Member')),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-user',
            ],
        };
    }

    /**
     * Scope Affiliation Badge (ISP Core HQ vs Reseller Sub-ISP Partner)
     */
    public function getScopeBadgeAttribute(): array
    {
        if ($this->reseller_id && $this->reseller) {
            return [
                'scope' => 'RESELLER',
                'label' => $this->reseller->name,
                'sub_label' => 'Partner (' . $this->reseller->code . ')',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-handshake',
            ];
        }

        return [
            'scope' => 'ISP_CORE',
            'label' => 'ISP Headquarters',
            'sub_label' => 'Core Direct Operations',
            'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            'icon' => 'fa-building',
        ];
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => [
                'label' => 'Active',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
            ],
            'suspended' => [
                'label' => 'Suspended',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => 'Inactive',
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
                'icon' => 'fa-circle-pause',
            ],
        };
    }

    /**
     * Auto Generate Next Staff ID
     */
    public static function generateNextStaffId(int $tenantId): string
    {
        $count = self::where('tenant_id', $tenantId)->count();
        return 'STF-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Staff Attendances relation
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantStaffAttendance::class, 'user_id');
    }
}
