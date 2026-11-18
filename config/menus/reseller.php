<?php

/**
 * Sidebar Navigation Configuration: Sub-ISP / Reseller Portal
 */

return [
    [
        'title' => 'Dashboard',
        'route' => 'reseller.dashboard',
        'active' => ['reseller.dashboard*'],
        'icon' => 'fas fa-chart-pie',
        'color' => 'purple',
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
        'keywords' => 'dashboard home overview metrics analytics',
    ],
    [
        'title' => 'Customers',
        'icon' => 'fas fa-users',
        'color' => 'cyan',
        'active' => ['reseller.customers*'],
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
        'keywords' => 'customers subscribers due active disconnected',
        'children' => [
            [
                'title' => 'All Customers',
                'route' => 'reseller.customers.index',
                'active' => ['reseller.customers.index'],
                'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
                'require_no_status' => true,
            ],
            [
                'title' => 'Due / Unpaid',
                'route' => 'reseller.customers.index',
                'params' => ['status' => 'due'],
                'active' => ['reseller.customers.index'],
                'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'admin', 'manager', 'collector'],
                'status_in' => ['due'],
                'badge' => 'Collect',
            ],
            [
                'title' => 'Active Subscribers',
                'route' => 'reseller.customers.index',
                'params' => ['status' => 'active'],
                'active' => ['reseller.customers.index'],
                'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
                'status_in' => ['active'],
            ],
            [
                'title' => 'Bulk Payments',
                'route' => 'reseller.customers.bulk-payments',
                'active' => ['reseller.customers.bulk-payments*'],
                'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'],
                'badge' => 'Fast',
            ],
            [
                'title' => 'Disconnected',
                'route' => 'reseller.customers.index',
                'params' => ['status' => 'disconnected'],
                'active' => ['reseller.customers.index'],
                'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
                'status_in' => ['disconnected'],
            ],
        ],
    ],
    [
        'title' => 'Billing & Invoices',
        'icon' => 'fas fa-file-invoice-dollar',
        'color' => 'blue',
        'active' => ['reseller.customer-invoices*', 'reseller.collections*', 'reseller.invoices*'],
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'],
        'keywords' => 'billing invoices payments collections wholesale',
        'children' => [
            ['title' => 'Customer Invoices', 'route' => 'reseller.customer-invoices.index', 'active' => ['reseller.customer-invoices*']],
            ['title' => 'Customer Payments', 'route' => 'reseller.collections.index', 'active' => ['reseller.collections*']],
            ['title' => 'Wholesale Invoices', 'route' => 'reseller.invoices.index', 'active' => ['reseller.invoices*']],
        ],
    ],
    [
        'title' => 'Collections',
        'route' => 'reseller.collections.index',
        'active' => ['reseller.collections*'],
        'icon' => 'fas fa-receipt',
        'color' => 'blue',
        'roles' => ['reseller_collector', 'collector'],
    ],
    [
        'title' => 'Wallet & Balance',
        'icon' => 'fas fa-wallet',
        'color' => 'emerald',
        'active' => ['reseller.recharge*', 'reseller.ledger*'],
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'],
        'children' => [
            ['title' => 'Balance Recharge', 'route' => 'reseller.recharge.index', 'active' => ['reseller.recharge*']],
            ['title' => 'Balance Deductions', 'route' => 'reseller.ledger.index', 'params' => ['type' => 'DEBIT'], 'active' => ['reseller.ledger*']],
            ['title' => 'Transaction Ledger', 'route' => 'reseller.ledger.index', 'active' => ['reseller.ledger*']],
        ],
    ],
    [
        'title' => 'Packages & Rates',
        'icon' => 'fas fa-boxes-stacked',
        'color' => 'cyan',
        'active' => ['reseller.packages*', 'reseller.margins*'],
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'],
        'children' => [
            ['title' => 'Assigned Packages', 'route' => 'reseller.packages.index', 'active' => ['reseller.packages*']],
            ['title' => 'Margin & Profit Rates', 'route' => 'reseller.margins.index', 'active' => ['reseller.margins*']],
        ],
    ],
    [
        'title' => 'Staff & Team',
        'icon' => 'fas fa-users-gear',
        'color' => 'cyan',
        'active' => ['reseller.staff*'],
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'admin', 'manager'],
        'children' => [
            ['title' => 'Staff Directory', 'route' => 'reseller.staff.index', 'active' => ['reseller.staff*']],
        ],
    ],
    [
        'title' => 'Bandwidth Usage',
        'route' => 'reseller.bandwidth.index',
        'active' => ['reseller.bandwidth*'],
        'icon' => 'fas fa-chart-line',
        'color' => 'purple',
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_technician', 'admin', 'manager', 'technician'],
    ],
    [
        'title' => 'Support & Tickets',
        'route' => 'reseller.tickets.index',
        'active' => ['reseller.tickets*'],
        'icon' => 'fas fa-headset',
        'color' => 'purple',
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
    ],
    [
        'title' => 'My Profile',
        'route' => 'reseller.profile',
        'active' => ['reseller.profile*'],
        'icon' => 'fas fa-id-card',
        'color' => 'purple',
        'roles' => ['reseller_admin', 'reseller_owner', 'reseller_manager', 'reseller_collector', 'reseller_technician', 'admin', 'manager', 'collector', 'technician'],
    ],
];
