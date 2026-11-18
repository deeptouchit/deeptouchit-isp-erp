<?php

/**
 * Sidebar Navigation Configuration: Bill Collector Role
 * Applies to: isp_collector, collector, reseller_collector
 */

return [
    [
        'title'    => 'Dashboard',
        'route'    => 'tenant.dashboard',
        'active'   => ['tenant.dashboard*'],
        'icon'     => 'fas fa-chart-pie',
        'color'    => 'purple',
        'keywords' => 'dashboard home overview metrics analytics ড্যাশবোর্ড',
    ],
    [
        'title'       => 'Customers & CRM',
        'icon'        => 'fas fa-users',
        'color'       => 'cyan',
        'active'      => ['tenant.customers*', 'tenant.collector.due-customers*'],
        'permissions' => ['customers.view'],
        'keywords'    => 'customers crm online expired due disconnected assigned area গ্রাহক',
        'children'    => [
            ['title' => 'All Customers', 'route' => 'tenant.customers.index', 'active' => ['tenant.customers.index'], 'permissions' => ['customers.view'], 'keywords' => 'all assigned customers'],
            ['title' => 'Online Sessions', 'route' => 'tenant.customers.online', 'active' => ['tenant.customers.online*'], 'permissions' => ['customers.view'], 'keywords' => 'online active sessions'],
            ['title' => 'Expired Customers', 'route' => 'tenant.customers.expired', 'active' => ['tenant.customers.expired*'], 'permissions' => ['customers.view'], 'keywords' => 'expired customers'],
            ['title' => 'Due Customers', 'route' => 'tenant.collector.due-customers', 'active' => ['tenant.collector.due-customers*', 'tenant.customers.due*'], 'permissions' => ['customers.view', 'billing.collect'], 'keywords' => 'assigned due unpaid customers'],
            ['title' => 'Disconnected', 'route' => 'tenant.customers.disconnected', 'active' => ['tenant.customers.disconnected*'], 'permissions' => ['customers.view'], 'keywords' => 'disconnected customers'],
        ],
    ],
    [
        'title'       => 'Billing & Payment',
        'icon'        => 'fas fa-coins',
        'color'       => 'emerald',
        'active'      => ['tenant.finance.invoices*', 'tenant.finance.payments*', 'tenant.finance.collector-collections*', 'tenant.collector.handover*', 'tenant.finance.cash-handover*'],
        'permissions' => ['billing.invoices', 'billing.collect', 'billing.cash_handover'],
        'keywords'    => 'billing payment due paid invoices collector collections cash handover',
        'children'    => [
            ['title' => 'Due Invoices', 'route' => 'tenant.finance.invoices', 'params' => ['status' => 'due'], 'active_mode' => 'due_invoices', 'permissions' => ['billing.invoices', 'billing.collect'], 'keywords' => 'due unpaid invoices'],
            ['title' => 'Paid Invoices', 'route' => 'tenant.finance.invoices', 'params' => ['status' => 'paid'], 'active_mode' => 'paid_invoices', 'permissions' => ['billing.invoices', 'billing.collect'], 'keywords' => 'paid invoices'],
            ['title' => 'Collector Collections', 'route' => 'tenant.finance.collector-collections', 'active' => ['tenant.finance.collector-collections*'], 'permissions' => ['billing.collect', 'billing.cash_handover'], 'keywords' => 'collector collection report receipts'],
            ['title' => 'Cash Handover', 'route' => 'tenant.collector.handover', 'active' => ['tenant.collector.handover*'], 'permissions' => ['billing.cash_handover'], 'keywords' => 'daily cash handover'],
            ['title' => 'My Collections', 'route' => 'tenant.finance.payments', 'active' => ['tenant.finance.payments*'], 'permissions' => ['billing.collect'], 'keywords' => 'my collection receipts'],
        ],
    ],
    [
        'title'    => 'Support & Tickets',
        'route'    => 'tenant.tickets.index',
        'active'   => ['tenant.tickets*'],
        'icon'     => 'fas fa-headset',
        'color'    => 'purple',
        'keywords' => 'support tickets helpdesk issues trouble complaints noc টিকিট সাপোর্ট অভিযোগ',
    ],
    [
        'title'    => 'My Profile',
        'route'    => 'tenant.profile',
        'active'   => ['tenant.profile*'],
        'icon'     => 'fas fa-id-card',
        'color'    => 'purple',
        'keywords' => 'profile settings partner information account company security প্রোফাইল সেটিংস',
    ],
];

