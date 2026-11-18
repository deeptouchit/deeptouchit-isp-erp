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
        'title'       => 'Due Customers',
        'route'       => 'tenant.collector.due-customers',
        'active'      => ['tenant.collector.due-customers*'],
        'icon'        => 'fas fa-file-invoice-dollar',
        'color'       => 'rose',
        'permissions' => ['customers.view', 'billing.collect'],
        'keywords'    => 'due unpaid expired customers field collection বকেয়া গ্রাহক',
    ],
    [
        'title'       => 'My Collections',
        'route'       => 'tenant.finance.payments',
        'active'      => ['tenant.finance.payments*'],
        'icon'        => 'fas fa-receipt',
        'color'       => 'emerald',
        'permissions' => ['billing.collect'],
        'keywords'    => 'collections payments receipts money my collection রিসিট পেমেন্ট আদায়',
    ],
    [
        'title'       => 'Cash Handover',
        'route'       => 'tenant.collector.handover',
        'active'      => ['tenant.collector.handover*'],
        'icon'        => 'fas fa-vault',
        'color'       => 'amber',
        'permissions' => ['billing.cash_handover'],
        'keywords'    => 'cash handover daily closing vault টাকা জমা',
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

