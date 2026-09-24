<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRole extends Model
{
    use HasFactory;

    protected $table = 'tenant_roles';

    protected $guarded = ['id'];

    protected $casts = [
        'is_system' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Tenant Relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Count staff assigned to this role
     */
    public function getStaffCountAttribute(): int
    {
        return User::where('tenant_id', $this->tenant_id)
            ->where('role', $this->name)
            ->count();
    }

    /**
     * Check if role has a given permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->name === 'isp_admin' || $this->name === 'owner') {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions, true);
    }

    /**
     * Visual Role Badge Configuration
     */
    public function getBadgeAttribute(): array
    {
        $color = $this->color ?: 'purple';

        $colorMap = [
            'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200'],
            'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
            'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200'],
            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
            'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200'],
            'cyan' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200'],
            'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200'],
            'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200'],
            'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200'],
        ];

        $classes = $colorMap[$color] ?? $colorMap['purple'];

        return [
            'label' => $this->display_name ?: ucfirst(str_replace('_', ' ', $this->name)),
            'class' => "{$classes['bg']} {$classes['text']} {$classes['border']}",
            'icon' => $this->icon ?: 'fa-shield-halved',
            'color' => $color,
        ];
    }

    /**
     * Master ISP Granular Permission Definitions
     */
    public static function getPermissionCatalog(): array
    {
        return [
            'Subscriber Management (CRM)' => [
                'icon' => 'fa-users',
                'color' => 'blue',
                'permissions' => [
                    'customers.view' => 'View Subscribers & Detailed Profiles',
                    'customers.create' => 'Register & Onboard New Subscribers',
                    'customers.edit' => 'Modify Customer Info & Assigned Packages',
                    'customers.delete' => 'Delete & Archive Subscriber Accounts',
                    'customers.status_toggle' => 'Change Account Status (Active/Suspended/Due)',
                    'customers.export' => 'Export Customer Master Directory to CSV',
                ],
            ],
            'Network, MikroTik & FreeRADIUS' => [
                'icon' => 'fa-network-wired',
                'color' => 'indigo',
                'permissions' => [
                    'network.routers' => 'Manage MikroTik Routers & Sync Secrets',
                    'network.radius' => 'FreeRADIUS Authentication & NAS Config',
                    'network.olts_onus' => 'OLT PON Ports & ONU Device Telemetry',
                    'network.packages' => 'Internet Bandwidth & Profile Speed Tiers',
                    'network.ip_pools' => 'IPv4 / IPv6 Subnet Pools & Static Routing',
                    'network.disconnect_user' => 'Kill Active PPPoE Session / Kick Subscriber',
                ],
            ],
            'Billing, Invoices & Financials' => [
                'icon' => 'fa-file-invoice-dollar',
                'color' => 'emerald',
                'permissions' => [
                    'billing.invoices' => 'Generate & Manage Monthly Subscriber Bills',
                    'billing.collect' => 'Collect Payments & Issue POS/A4 Receipts',
                    'billing.cash_handover' => 'Daily Cash Closing & Collection Handover',
                    'billing.wholesale' => 'Reseller Wholesale Billing & CIR Bandwidth',
                    'billing.gateways' => 'Online Payment Gateways (bKash/Nagad/Cards)',
                    'billing.expenses' => 'Operating Expenses (OPEX) & Debit Vouchers',
                    'billing.ledger' => 'General Ledger & Real-Time Profit & Loss',
                ],
            ],
            'Sub-ISP & Reseller Network' => [
                'icon' => 'fa-building-shield',
                'color' => 'purple',
                'permissions' => [
                    'resellers.view' => 'View Sub-ISP Partner Directory & Portals',
                    'resellers.create' => 'Onboard New Sub-ISP Franchise Partner',
                    'resellers.edit' => 'Configure Bandwidth Quotas & Reseller Pricing',
                    'resellers.wallet' => 'Top-up / Adjust Reseller Prepaid Wallet Balance',
                ],
            ],
            'Helpdesk & Field Operations' => [
                'icon' => 'fa-headset',
                'color' => 'cyan',
                'permissions' => [
                    'support.tickets' => 'Manage Customer Support Tickets & Live Replies',
                    'support.field_jobs' => 'Dispatch Field Technicians & Fiber Splicing',
                    'support.installations' => 'New Customer Provisioning & Wiring Pipeline',
                    'support.escalations' => 'Sub-ISP Wholesale Technical Escalations',
                    'support.sla' => 'NOC & Field SLA Performance Scorecards',
                ],
            ],
            'Reports & Regulatory Compliance' => [
                'icon' => 'fa-chart-pie',
                'color' => 'amber',
                'permissions' => [
                    'reports.revenue' => 'Revenue & Business Growth Analytics',
                    'reports.collection' => 'Collection Efficiency & Aging Dues Breakdown',
                    'reports.mrtg' => 'Bandwidth MRTG Utilization Real-Time Graphs',
                    'reports.btrc' => 'BTRC Statutory Regulatory Subscriber Registry',
                    'reports.inventory' => 'Hardware Inventory & Stock Valuation Ledger',
                ],
            ],
            'System Administration & Security' => [
                'icon' => 'fa-gears',
                'color' => 'rose',
                'permissions' => [
                    'settings.profile' => 'ISP Corporate Profile & Official Branding',
                    'settings.sms_gateway' => 'SMS / WhatsApp Gateway & Alert Templates',
                    'settings.automation' => 'Auto-Cut Rules, Auto-Billing & Cron Engine',
                    'settings.rbac' => 'Role-Based Access Control & Permission Matrix',
                    'settings.backup' => 'Automated Database Snapshot & Cloud Backup',
                    'settings.audit_logs' => 'Full Administrative Activity & Audit Trail',
                ],
            ],
        ];
    }
}
