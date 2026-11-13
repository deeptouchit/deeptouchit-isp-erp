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
        'title'    => 'Customers',
        'icon'     => 'fas fa-users',
        'color'    => 'cyan',
        'active'   => ['tenant.customers*'],
        'keywords' => 'customers subscribers retail pppoe online expired due disconnected direct users client গ্রাহক',
        'children' => [
            [
                'title'         => 'All Customers',
                'route'         => 'tenant.customers.index',
                'active'        => ['tenant.customers.index', 'tenant.customers.create', 'tenant.customers.edit', 'tenant.customers.show'],
                'not_status_in' => ['due', 'active', 'expired', 'disconnected'],
                'active_color'  => 'cyan',
                'keywords'      => 'all customers master list গ্রাহক',
            ],
            [
                'title'        => 'Due / Unpaid',
                'route'        => 'tenant.customers.index',
                'params'       => ['status' => 'due'],
                'active'       => ['tenant.customers.due*'],
                'status_in'    => ['due'],
                'active_color' => 'amber',
                'badge'        => 'Collect',
                'keywords'     => 'due unpaid collect বকেয়া',
            ],
            [
                'title'        => 'Active Subscribers',
                'route'        => 'tenant.customers.index',
                'params'       => ['status' => 'active'],
                'active'       => ['tenant.customers.direct*'],
                'status_in'    => ['active'],
                'active_color' => 'cyan',
                'keywords'     => 'active subscribers সক্রিয়',
            ],
        ],
    ],
    [
        'title'    => 'Collections',
        'route'    => 'tenant.finance.payments',
        'active'   => ['tenant.finance.payments*'],
        'icon'     => 'fas fa-receipt',
        'color'    => 'blue',
        'keywords' => 'collections payments receipts money রিসিট পেমেন্ট আদায় কালেকশন',
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

