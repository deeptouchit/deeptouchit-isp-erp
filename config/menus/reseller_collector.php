<?php

return [
    ['title' => 'Dashboard', 'route' => 'reseller.dashboard', 'active' => ['reseller.dashboard'], 'icon' => 'fas fa-chart-pie', 'color' => 'cyan'],
    ['title' => 'Assigned Customers', 'route' => 'reseller.customers.index', 'active' => ['reseller.customers.index', 'reseller.customers.show'], 'not_status_in' => ['due'], 'icon' => 'fas fa-users', 'color' => 'cyan'],
    ['title' => 'Due Customers', 'route' => 'reseller.customers.index', 'active' => ['reseller.customers.index'], 'params' => ['status' => 'due'], 'params_match' => ['status' => 'due'], 'icon' => 'fas fa-file-invoice-dollar', 'color' => 'amber'],
    ['title' => 'My Collections', 'route' => 'reseller.collections.index', 'active' => ['reseller.collections*'], 'icon' => 'fas fa-receipt', 'color' => 'emerald'],
    ['title' => 'Cash Handover', 'route' => 'reseller.finance.cash-handover', 'active' => ['reseller.finance.cash-handover*'], 'icon' => 'fas fa-vault', 'color' => 'amber'],
    ['title' => 'Support & Tickets', 'route' => 'reseller.tickets.index', 'active' => ['reseller.tickets*'], 'icon' => 'fas fa-headset', 'color' => 'cyan'],
    ['title' => 'My Profile', 'route' => 'reseller.profile', 'active' => ['reseller.profile*'], 'icon' => 'fas fa-id-card', 'color' => 'cyan'],
];
