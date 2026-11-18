<?php

/**
 * Sidebar Navigation Configuration: Technical / NOC / Field Staff Role
 * Applies to: isp_technician, technician, reseller_technician, isp_noc, isp_lineman
 */

return [
    [
        'title'    => 'Dashboard',
        'route'    => 'tenant.dashboard',
        'active'   => ['tenant.dashboard*'],
        'icon'     => 'fas fa-house-chimney',
        'color'    => 'blue',
        'keywords' => 'dashboard overview stats home analytics ড্যাশবোর্ড',
    ],
    [
        'title'       => 'Network Operations',
        'icon'        => 'fas fa-network-wired',
        'color'       => 'blue',
        'active'      => ['tenant.network*'],
        'permissions' => ['network.olts_onus'],
        'keywords'    => 'network olt onu ont optical fiber',
        'children'    => [
            ['title' => 'OLT Devices', 'route' => 'tenant.network.olt', 'active' => ['tenant.network.olt*'], 'permissions' => ['network.olts_onus'], 'keywords' => 'olt gpon epon'],
            ['title' => 'ONU / ONT', 'route' => 'tenant.network.onu', 'active' => ['tenant.network.onu*'], 'permissions' => ['network.olts_onus'], 'keywords' => 'onu ont optical signal'],
        ],
    ],
    [
        'title'       => 'Customers & CRM',
        'icon'        => 'fas fa-users-line',
        'color'       => 'cyan',
        'active'      => ['tenant.customers*'],
        'permissions' => ['customers.view'],
        'keywords'    => 'customers crm subscribers pppoe online sessions expired zones',
        'children'    => [
            [
                'title'             => 'All Customers',
                'route'             => 'tenant.customers.index',
                'active'            => ['tenant.customers.index', 'tenant.customers.create', 'tenant.customers.edit', 'tenant.customers.show'],
                'exclude_routes'    => ['tenant.customers.expired*', 'tenant.customers.due*', 'tenant.customers.online*', 'tenant.customers.disconnected*', 'tenant.customers.zones*', 'tenant.customers.bulk-payments*', 'tenant.customers.monthly-analytics*'],
                'require_no_status' => true,
                'permissions'       => ['customers.view'],
                'keywords'          => 'all customers subscribers list',
            ],
        ],
    ],
    [
        'title'       => 'Helpdesk & Support',
        'icon'        => 'fas fa-headset',
        'color'       => 'blue',
        'active'      => ['tenant.tickets*', 'tenant.helpdesk*', 'tenant.support*'],
        'permissions' => ['support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla'],
        'keywords'    => 'helpdesk support noc tickets field jobs installations fiber splicing onu',
        'children'    => [
            [
                'title'      => 'Support Tickets',
                'route'      => 'tenant.tickets.index',
                'active'     => ['tenant.tickets.index', 'tenant.tickets.show', 'tenant.tickets.create'],
                'permission' => 'support.tickets',
                'keywords'   => 'support tickets complaints slow internet',
            ],
            [
                'title'      => 'Field Jobs',
                'route'      => 'tenant.tickets.field-jobs',
                'active'     => ['tenant.tickets.field-jobs*'],
                'permission' => 'support.field_jobs',
                'keywords'   => 'field jobs technician splicing onu',
            ],
            [
                'title'      => 'Installations',
                'route'      => 'tenant.tickets.installations',
                'active'     => ['tenant.tickets.installations*'],
                'permission' => 'support.installations',
                'keywords'   => 'new installation cable pulling setup',
            ],
            [
                'title'      => 'Escalations',
                'route'      => 'tenant.tickets.escalations',
                'active'     => ['tenant.tickets.escalations*'],
                'permission' => 'support.escalations',
                'keywords'   => 'reseller escalations',
            ],
            [
                'title'      => 'SLA & Analytics',
                'route'      => 'tenant.tickets.sla-analytics',
                'active'     => ['tenant.tickets.sla-analytics*'],
                'permission' => 'support.sla',
                'keywords'   => 'sla performance analytics',
            ],
        ],
    ],
    [
        'title'    => 'My Profile',
        'route'    => 'tenant.profile',
        'active'   => ['tenant.profile*'],
        'icon'     => 'fas fa-id-card',
        'color'    => 'purple',
        'keywords' => 'profile settings account password প্রোফাইল',
    ],
];

