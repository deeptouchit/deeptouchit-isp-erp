<?php

/**
 * Sidebar Navigation Configuration: ISP Operations Manager
 *
 * Managers handle day-to-day subscribers, staff, billing, reports and support.
 * Network, upstream, reseller, subscription and system administration remain
 * restricted to the ISP administrator or dedicated specialist roles.
 */

return [
    [
        'title'    => 'Dashboard',
        'route'    => 'tenant.dashboard',
        'active'   => ['tenant.dashboard*'],
        'icon'     => 'fas fa-house-chimney',
        'color'    => 'blue',
        'keywords' => 'dashboard overview stats home analytics manager',
    ],
    [
        'title'       => 'Staff & HRM',
        'icon'        => 'fas fa-user-tie',
        'color'       => 'indigo',
        'active'      => ['tenant.staff*'],
        'permissions' => ['staff.view'],
        'keywords'    => 'staff hrm employees attendance logs commission',
        'children'    => [
            ['title' => 'Staff Directory', 'route' => 'tenant.staff.index', 'active' => ['tenant.staff.index'], 'permissions' => ['staff.view']],
            ['title' => 'Operations Staff', 'route' => 'tenant.staff.isp', 'active' => ['tenant.staff.isp*'], 'permissions' => ['staff.view']],
            ['title' => 'Attendance', 'route' => 'tenant.staff.attendance', 'active' => ['tenant.staff.attendance*'], 'permissions' => ['staff.view']],
            ['title' => 'Activity Logs', 'route' => 'tenant.staff.logs', 'active' => ['tenant.staff.logs*'], 'permissions' => ['staff.view']],
            ['title' => 'Targets & Commission', 'route' => 'tenant.staff.commissions', 'active' => ['tenant.staff.commissions*'], 'permissions' => ['staff.view']],
        ],
    ],
    [
        'title'       => 'Customers & CRM',
        'icon'        => 'fas fa-users-line',
        'color'       => 'cyan',
        'active'      => ['tenant.customers*'],
        'permissions' => ['customers.view', 'customers.create', 'customers.edit', 'customers.status_toggle', 'customers.export'],
        'keywords'    => 'customers crm subscribers zones online expired due disconnected bulk payments analytics',
        'children'    => [
            ['title' => 'Coverage Zones', 'route' => 'tenant.customers.zones', 'active' => ['tenant.customers.zones*'], 'permissions' => ['customers.view']],
            ['title' => 'All Customers', 'route' => 'tenant.customers.index', 'active' => ['tenant.customers.index', 'tenant.customers.create', 'tenant.customers.edit', 'tenant.customers.show'], 'exclude_routes' => ['tenant.customers.expired*', 'tenant.customers.due*', 'tenant.customers.online*', 'tenant.customers.disconnected*', 'tenant.customers.zones*', 'tenant.customers.bulk-payments*', 'tenant.customers.monthly-analytics*'], 'require_no_status' => true, 'permissions' => ['customers.view']],
            ['title' => 'Online Sessions', 'route' => 'tenant.customers.online', 'active' => ['tenant.customers.online*'], 'permissions' => ['customers.view']],
            ['title' => 'Expired Customers', 'route' => 'tenant.customers.expired', 'fallback_route' => 'tenant.customers.index', 'fallback_params' => ['status' => 'expired'], 'active' => ['tenant.customers.expired*'], 'status_in' => ['expired'], 'active_color' => 'rose', 'permissions' => ['customers.view']],
            ['title' => 'Due Customers', 'route' => 'tenant.customers.due', 'fallback_route' => 'tenant.customers.index', 'fallback_params' => ['status' => 'due'], 'active' => ['tenant.customers.due*'], 'status_in' => ['due'], 'active_color' => 'amber', 'permissions' => ['customers.view']],
            ['title' => 'Disconnected', 'route' => 'tenant.customers.disconnected', 'fallback_route' => 'tenant.customers.index', 'fallback_params' => ['status' => 'disconnected'], 'active' => ['tenant.customers.disconnected*', 'tenant.customers.expired-60*'], 'status_in' => ['disconnected', 'expired_60'], 'active_color' => 'rose', 'permissions' => ['customers.view']],
            ['title' => 'Bulk Payments', 'route' => 'tenant.customers.bulk-payments', 'active' => ['tenant.customers.bulk-payments*'], 'permissions' => ['billing.collect']],
            ['title' => 'Monthly Analytics', 'route' => 'tenant.customers.monthly-analytics', 'active' => ['tenant.customers.monthly-analytics*'], 'permissions' => ['reports.collection']],
        ],
    ],
    [
        'title'       => 'Billing & Payment',
        'icon'        => 'fas fa-coins',
        'color'       => 'amber',
        'active'      => ['tenant.finance*'],
        'permissions' => ['billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.expenses', 'billing.ledger'],
        'keywords'    => 'billing catch-up due paid invoices collector collections cash handover expenses ledger',
        'children'    => [
            ['title' => 'Billing Catch-Up', 'route' => 'tenant.finance.billing-catchup', 'active' => ['tenant.finance.billing-catchup*'], 'permissions' => ['billing.invoices']],
            ['title' => 'Due Invoices', 'route' => 'tenant.finance.invoices', 'params' => ['status' => 'due'], 'active_mode' => 'due_invoices', 'permissions' => ['billing.invoices']],
            ['title' => 'Paid Invoices', 'route' => 'tenant.finance.invoices', 'params' => ['status' => 'paid'], 'active_mode' => 'paid_invoices', 'permissions' => ['billing.invoices']],
            ['title' => 'Collector Collections', 'route' => 'tenant.finance.collector-collections', 'active' => ['tenant.finance.collector-collections*'], 'permissions' => ['billing.cash_handover']],
            ['title' => 'Cash Handover', 'route' => 'tenant.finance.cash-handover', 'fallback_route' => 'tenant.collector.handover', 'active' => ['tenant.finance.cash-handover*', 'tenant.collector.handover*'], 'permissions' => ['billing.cash_handover']],
            ['title' => 'Income & Expenses', 'route' => 'tenant.finance.expenses', 'active' => ['tenant.finance.expenses*'], 'permissions' => ['billing.expenses']],
            ['title' => 'Accounts & Ledger', 'route' => 'tenant.finance.ledger', 'active' => ['tenant.finance.ledger*'], 'permissions' => ['billing.ledger']],
        ],
    ],
    [
        'title'       => 'Reports & Analytics',
        'icon'        => 'fas fa-chart-line',
        'color'       => 'rose',
        'active'      => ['tenant.reports*'],
        'permissions' => ['reports.revenue', 'reports.collection', 'reports.inventory'],
        'keywords'    => 'reports analytics revenue collection inventory',
        'children'    => [
            ['title' => 'Revenue & Growth', 'route' => 'tenant.reports.revenue', 'active' => ['tenant.reports.revenue*'], 'permissions' => ['reports.revenue']],
            ['title' => 'Collection & Due', 'route' => 'tenant.reports.collection', 'active' => ['tenant.reports.collection*'], 'permissions' => ['reports.collection']],
            ['title' => 'Equipment & Inventory', 'route' => 'tenant.reports.inventory', 'active' => ['tenant.reports.inventory*'], 'permissions' => ['reports.inventory']],
        ],
    ],
    [
        'title'       => 'Helpdesk & Support',
        'icon'        => 'fas fa-headset',
        'color'       => 'blue',
        'active'      => ['tenant.tickets*'],
        'permissions' => ['support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla'],
        'keywords'    => 'helpdesk support tickets field jobs installations escalations sla',
        'children'    => [
            ['title' => 'Support Tickets', 'route' => 'tenant.tickets.index', 'active' => ['tenant.tickets.index', 'tenant.tickets.show', 'tenant.tickets.create'], 'permissions' => ['support.tickets']],
            ['title' => 'Field Jobs', 'route' => 'tenant.tickets.field-jobs', 'active' => ['tenant.tickets.field-jobs*'], 'permissions' => ['support.field_jobs']],
            ['title' => 'Installations', 'route' => 'tenant.tickets.installations', 'active' => ['tenant.tickets.installations*'], 'permissions' => ['support.installations']],
            ['title' => 'Escalations', 'route' => 'tenant.tickets.escalations', 'active' => ['tenant.tickets.escalations*'], 'permissions' => ['support.escalations']],
            ['title' => 'SLA & Analytics', 'route' => 'tenant.tickets.sla-analytics', 'active' => ['tenant.tickets.sla-analytics*'], 'permissions' => ['support.sla']],
        ],
    ],
];
