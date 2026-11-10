<?php

/**
 * Sidebar Navigation Configuration: ISP Manager Role
 * Applies to: isp_manager (Dedicated clean menu config, respects TenantRole RBAC permissions)
 */

return [
    // 1. Dashboard
    [
        'title'    => 'Dashboard',
        'route'    => 'tenant.dashboard',
        'active'   => ['tenant.dashboard*'],
        'icon'     => 'fas fa-house-chimney',
        'color'    => 'blue',
        'keywords' => 'dashboard overview stats home analytics manager',
    ],

    // 2. My Subscription
    [
        'title'    => 'My Subscription',
        'icon'     => 'fas fa-file-invoice-dollar',
        'color'    => 'blue',
        'active'   => ['tenant.billing*'],
        'roles'    => ['isp_manager'],
        'keywords' => 'billing subscription invoices payments renewal plan',
        'children' => [
            ['title' => 'Plan Overview',   'route' => 'tenant.billing.dashboard', 'active' => ['tenant.billing.dashboard']],
            ['title' => 'Invoices',        'route' => 'tenant.billing.invoices',  'active' => ['tenant.billing.invoices*', 'tenant.billing.invoice.show*']],
            ['title' => 'Payment History', 'route' => 'tenant.billing.payments',  'active' => ['tenant.billing.payments*']],
        ],
    ],

    // 3. Upstream Bandwidth
    [
        'title'    => 'Upstream Bandwidth',
        'icon'     => 'fas fa-tower-broadcast',
        'color'    => 'blue',
        'active'   => ['tenant.upstream*'],
        'roles'    => ['isp_manager'],
        'keywords' => 'upstream bandwidth carrier iig nttn bills vouchers',
        'children' => [
            ['title' => 'Carriers & Links', 'route' => 'tenant.upstream.index',    'active' => ['tenant.upstream.index']],
            ['title' => 'Carrier Bills',    'route' => 'tenant.upstream.invoices', 'active' => ['tenant.upstream.invoices*']],
            ['title' => 'Payment Vouchers', 'route' => 'tenant.upstream.payments', 'active' => ['tenant.upstream.payments*', 'tenant.upstream.payment.voucher*']],
        ],
    ],

    // 4. Network & Infra
    [
        'title'       => 'Network & Infra',
        'icon'        => 'fas fa-network-wired',
        'color'       => 'blue',
        'active'      => ['tenant.network*'],
        'permissions' => ['network.routers', 'network.radius', 'network.packages', 'network.olts_onus', 'network.ip_pools'],
        'keywords'    => 'network mikrotik olt onu ip pools vlans routing map',
        'children'    => [
            ['title' => 'MikroTik Routers',  'route' => 'tenant.network.mikrotik', 'active' => ['tenant.network.mikrotik*']],
            ['title' => 'NAS & RADIUS',      'route' => 'tenant.network.nas',      'active' => ['tenant.network.nas*']],
            ['title' => 'Internet Packages', 'route' => 'tenant.network.packages', 'active' => ['tenant.network.packages*']],
            ['title' => 'OLT Devices',       'route' => 'tenant.network.olt',      'active' => ['tenant.network.olt*']],
            ['title' => 'ONU / ONT',         'route' => 'tenant.network.onu',      'active' => ['tenant.network.onu*']],
            ['title' => 'IP Pools',          'route' => 'tenant.network.ip-pools', 'active' => ['tenant.network.ip-pools*']],
            ['title' => 'VLANs',             'route' => 'tenant.network.vlans',    'active' => ['tenant.network.vlans*']],
            ['title' => 'Routing & BGP',     'route' => 'tenant.network.routing',  'active' => ['tenant.network.routing*']],
            ['title' => 'Network Map',       'route' => 'tenant.network.map',      'active' => ['tenant.network.map*']],
        ],
    ],

    // 5. Reseller Partners
    [
        'title'       => 'Reseller Partners',
        'icon'        => 'fas fa-handshake',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.index*', 'tenant.resellers.show*', 'tenant.resellers.import*', 'tenant.resellers.subscriptions*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'reseller partners sub-isps franchise licenses import',
        'children'    => [
            ['title' => 'Partner Directory',       'route' => 'tenant.resellers.index',         'active' => ['tenant.resellers.index*', 'tenant.resellers.show*']],
            ['title' => 'Import Resellers (Excel)','route' => 'tenant.resellers.import',        'active' => ['tenant.resellers.import*']],
            ['title' => 'Panel Licenses',          'route' => 'tenant.resellers.subscriptions', 'active' => ['tenant.resellers.subscriptions*']],
        ],
    ],

    // 6. Wholesale Bandwidth
    [
        'title'       => 'Wholesale Bandwidth',
        'icon'        => 'fas fa-network-wired',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.bandwidth*', 'tenant.resellers.bandwidth-plans*', 'tenant.resellers.invoices*', 'tenant.resellers.capacity*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'wholesale bandwidth plans allocation capacity invoices',
        'children'    => [
            ['title' => 'Bandwidth Rate Plans', 'route' => 'tenant.resellers.bandwidth-plans', 'active' => ['tenant.resellers.bandwidth-plans*']],
            ['title' => 'Bandwidth Allocation', 'route' => 'tenant.resellers.bandwidth',       'active' => ['tenant.resellers.bandwidth']],
            ['title' => 'Invoices & Payments',  'route' => 'tenant.resellers.invoices',        'active' => ['tenant.resellers.invoices*']],
            ['title' => 'Gateway Capacity',     'route' => 'tenant.resellers.capacity',        'active' => ['tenant.resellers.capacity*']],
        ],
    ],

    // 7. Reseller Accounts
    [
        'title'       => 'Reseller Accounts',
        'icon'        => 'fas fa-wallet',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.recharge*', 'tenant.resellers.wallets*', 'tenant.resellers.ledger*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'reseller accounts wallets recharge ledger',
        'children'    => [
            ['title' => 'Wallets & Credit',  'route' => 'tenant.resellers.wallets',  'active' => ['tenant.resellers.wallets*']],
            ['title' => 'Recharge History',  'route' => 'tenant.resellers.recharge', 'active' => ['tenant.resellers.recharge*']],
            ['title' => 'Adjustment Ledger', 'route' => 'tenant.resellers.ledger',   'active' => ['tenant.resellers.ledger*']],
        ],
    ],

    // 8. Staff & HRM
    [
        'title'       => 'Staff & HRM',
        'icon'        => 'fas fa-user-tie',
        'color'       => 'indigo',
        'active'      => ['tenant.staff*', 'tenant.hrm*'],
        'roles'       => ['isp_manager'],
        'permissions' => ['settings.rbac'],
        'keywords'    => 'staff hrm employees attendance logs commission',
        'children'    => [
            ['title' => 'Staff Directory',      'route' => 'tenant.staff.index',       'active' => ['tenant.staff.index']],
            ['title' => 'Operations Staff',     'route' => 'tenant.staff.isp',         'active' => ['tenant.staff.isp*']],
            ['title' => 'Reseller Staff',       'route' => 'tenant.staff.resellers',   'active' => ['tenant.staff.resellers*']],
            ['title' => 'Attendance',           'route' => 'tenant.staff.attendance',  'active' => ['tenant.staff.attendance*']],
            ['title' => 'Activity Logs',        'route' => 'tenant.staff.logs',        'active' => ['tenant.staff.logs*']],
            ['title' => 'Targets & Commission', 'route' => 'tenant.staff.commissions', 'active' => ['tenant.staff.commissions*']],
        ],
    ],

    // 9. Customers & CRM
    [
        'title'       => 'Customers & CRM',
        'icon'        => 'fas fa-users-line',
        'color'       => 'cyan',
        'active'      => ['tenant.customers*'],
        'permissions' => ['customers.view'],
        'keywords'    => 'customers crm subscribers zones online expired due disconnected bulk payments analytics',
        'children'    => [
            ['title' => 'Coverage Zones', 'route' => 'tenant.customers.zones', 'active' => ['tenant.customers.zones*']],
            [
                'title'             => 'All Customers',
                'route'             => 'tenant.customers.index',
                'active'            => ['tenant.customers.index', 'tenant.customers.create', 'tenant.customers.edit', 'tenant.customers.show'],
                'exclude_routes'    => ['tenant.customers.expired*', 'tenant.customers.due*', 'tenant.customers.online*', 'tenant.customers.disconnected*', 'tenant.customers.zones*', 'tenant.customers.bulk-payments*', 'tenant.customers.monthly-analytics*'],
                'require_no_status' => true,
            ],
            ['title' => 'Online Sessions', 'route' => 'tenant.customers.online', 'active' => ['tenant.customers.online*']],
            [
                'title'           => 'Expired Customers',
                'route'           => 'tenant.customers.expired',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'expired'],
                'active'          => ['tenant.customers.expired*'],
                'status_in'       => ['expired'],
                'active_color'    => 'rose',
            ],
            [
                'title'           => 'Due Customers',
                'route'           => 'tenant.customers.due',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'due'],
                'active'          => ['tenant.customers.due*'],
                'status_in'       => ['due'],
                'active_color'    => 'amber',
            ],
            [
                'title'           => 'Disconnected',
                'route'           => 'tenant.customers.disconnected',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'disconnected'],
                'active'          => ['tenant.customers.disconnected*', 'tenant.customers.expired-60*'],
                'status_in'       => ['disconnected', 'expired_60'],
                'active_color'    => 'rose',
            ],
            ['title' => 'Bulk Payments',     'route' => 'tenant.customers.bulk-payments',     'active' => ['tenant.customers.bulk-payments*']],
            ['title' => 'Monthly Analytics', 'route' => 'tenant.customers.monthly-analytics', 'active' => ['tenant.customers.monthly-analytics*']],
        ],
    ],

    // 10. Billing & Payment
    [
        'title'        => 'Billing & Payment',
        'icon'         => 'fas fa-coins',
        'color'        => 'amber',
        'active'       => ['tenant.finance*', 'tenant.collector*', 'tenant.accounts*', 'tenant.erp*'],
        'active_paths' => ['admin/collector*', 'admin/finance*'],
        'permissions'  => ['billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.wholesale', 'billing.gateways', 'billing.expenses', 'billing.ledger'],
        'keywords'     => 'billing catch-up due paid invoices collector collections cash handover wholesale gateway expenses ledger',
        'children'     => [
            ['title' => 'Billing Catch-Up',      'route' => 'tenant.finance.billing-catchup',       'active' => ['tenant.finance.billing-catchup*']],
            ['title' => 'Due Invoices',          'route' => 'tenant.finance.invoices',              'params' => ['status' => 'due'],  'active_mode' => 'due_invoices'],
            ['title' => 'Paid Invoices',         'route' => 'tenant.finance.invoices',              'params' => ['status' => 'paid'], 'active_mode' => 'paid_invoices'],
            ['title' => 'Collector Collections', 'route' => 'tenant.finance.collector-collections', 'active' => ['tenant.finance.collector-collections*']],
            ['title' => 'Cash Handover',         'route' => 'tenant.finance.cash-handover',         'fallback_route' => 'tenant.collector.handover', 'active' => ['tenant.finance.cash-handover*', 'tenant.collector.handover*']],
            ['title' => 'Reseller Billing',      'route' => 'tenant.finance.wholesale-billing',     'active' => ['tenant.finance.wholesale-billing*']],
            ['title' => 'Gateway Payments',      'route' => 'tenant.finance.gateway-transactions',  'active' => ['tenant.finance.gateway-transactions*']],
            ['title' => 'Income & Expenses',     'route' => 'tenant.finance.expenses',              'active' => ['tenant.finance.expenses*']],
            ['title' => 'Accounts & Ledger',     'route' => 'tenant.finance.ledger',                'active' => ['tenant.finance.ledger*']],
        ],
    ],

    // 11. Reports & Analytics
    [
        'title'       => 'Reports & Analytics',
        'icon'        => 'fas fa-chart-line',
        'color'       => 'rose',
        'active'      => ['tenant.reports*', 'tenant.analytics*', 'tenant.compliance*'],
        'permissions' => ['reports.revenue', 'reports.collection', 'reports.mrtg', 'reports.btrc', 'reports.inventory'],
        'keywords'    => 'reports analytics revenue collection mrtg btrc inventory',
        'children'    => [
            ['title' => 'Revenue & Growth',      'route' => 'tenant.reports.revenue',    'active' => ['tenant.reports.revenue*']],
            ['title' => 'Collection & Due',      'route' => 'tenant.reports.collection', 'active' => ['tenant.reports.collection*']],
            ['title' => 'Bandwidth & MRTG',      'route' => 'tenant.reports.mrtg',       'active' => ['tenant.reports.mrtg*']],
            ['title' => 'BTRC Compliance',       'route' => 'tenant.reports.btrc',       'active' => ['tenant.reports.btrc*']],
            ['title' => 'Equipment & Inventory', 'route' => 'tenant.reports.inventory',  'active' => ['tenant.reports.inventory*']],
        ],
    ],

    // 12. Helpdesk & Support
    [
        'title'       => 'Helpdesk & Support',
        'icon'        => 'fas fa-headset',
        'color'       => 'blue',
        'active'      => ['tenant.tickets*', 'tenant.helpdesk*', 'tenant.support*'],
        'permissions' => ['support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla'],
        'keywords'    => 'helpdesk support tickets field jobs installations escalations sla',
        'children'    => [
            ['title' => 'Support Tickets', 'route' => 'tenant.tickets.index',         'active' => ['tenant.tickets.index', 'tenant.tickets.show', 'tenant.tickets.create']],
            ['title' => 'Field Jobs',      'route' => 'tenant.tickets.field-jobs',    'active' => ['tenant.tickets.field-jobs*']],
            ['title' => 'Installations',   'route' => 'tenant.tickets.installations', 'active' => ['tenant.tickets.installations*']],
            ['title' => 'Escalations',     'route' => 'tenant.tickets.escalations',   'active' => ['tenant.tickets.escalations*']],
            ['title' => 'SLA & Analytics', 'route' => 'tenant.tickets.sla-analytics', 'active' => ['tenant.tickets.sla-analytics*']],
        ],
    ],

    // 13. Settings & Admin
    [
        'title'       => 'Settings & Admin',
        'icon'        => 'fas fa-gear',
        'color'       => 'slate',
        'active'      => ['tenant.settings.profile*', 'tenant.settings.sms*', 'tenant.settings.payment-gateways*', 'tenant.settings.automation*', 'tenant.settings.backup*'],
        'permissions' => ['settings.profile', 'settings.sms_gateway', 'settings.automation', 'settings.backup', 'billing.gateways'],
        'keywords'    => 'settings profile sms payment gateways automation backup',
        'children'    => [
            ['title' => 'Company Profile',   'route' => 'tenant.settings.profile',          'active' => ['tenant.settings.profile*']],
            ['title' => 'SMS & Notifications','route'=> 'tenant.settings.sms-gateway',      'active' => ['tenant.settings.sms*']],
            ['title' => 'Payment Gateways',  'route' => 'tenant.settings.payment-gateways', 'active' => ['tenant.settings.payment-gateways*']],
            ['title' => 'Automation Rules',  'route' => 'tenant.settings.automation',       'active' => ['tenant.settings.automation*']],
            ['title' => 'Backup & Restore',  'route' => 'tenant.settings.backup',           'active' => ['tenant.settings.backup*']],
        ],
    ],

    // 14. User & Access Control
    [
        'title'       => 'User & Access Control',
        'icon'        => 'fas fa-user-shield',
        'color'       => 'slate',
        'active'      => ['tenant.settings.rbac*', 'tenant.settings.audit*', 'tenant.users*'],
        'permissions' => ['settings.rbac', 'settings.audit_logs'],
        'keywords'    => 'roles permissions users list audit trail security',
        'children'    => [
            ['title' => 'Roles & Permissions',    'route' => 'tenant.settings.rbac',       'active' => ['tenant.settings.rbac*']],
            ['title' => 'Users List',             'route' => 'tenant.users.index',         'active' => ['tenant.users*']],
            ['title' => 'Activity & Audit Trail', 'route' => 'tenant.settings.audit-logs', 'active' => ['tenant.settings.audit*']],
        ],
    ],
];
