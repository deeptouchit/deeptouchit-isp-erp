<?php

/**
 * Sidebar Navigation Configuration: Master Admin / Owner / Dynamic RBAC Menu Tree
 * Applies to: owner, super_admin, isp_admin, and custom TenantRole staff (filtered by permissions)
 */

return [
    // 1. Standalone Top Main Menu: Overview Dashboard
    [
        'title'    => 'Dashboard',
        'route'    => 'tenant.dashboard',
        'active'   => ['tenant.dashboard*'],
        'icon'     => 'fas fa-house-chimney',
        'color'    => 'blue',
        'keywords' => 'dashboard overview stats home analytics admin',
    ],

    // 2. My Subscription
    [
        'title'    => 'My Subscription',
        'icon'     => 'fas fa-file-invoice-dollar',
        'color'    => 'blue',
        'active'   => ['tenant.billing*'],
        'roles'    => ['owner', 'super_admin', 'isp_admin', 'isp_manager'],
        'keywords' => 'billing subscription invoices payments packages renewal license plan overview my subscription',
        'children' => [
            [
                'title'    => 'Plan Overview',
                'route'    => 'tenant.billing.dashboard',
                'active'   => ['tenant.billing.dashboard'],
                'keywords' => 'subscription status plan details expiry renewal overview license',
            ],
            [
                'title'    => 'Invoices',
                'route'    => 'tenant.billing.invoices',
                'active'   => ['tenant.billing.invoices*', 'tenant.billing.invoice.show*'],
                'keywords' => 'invoices bills receipts dues subscription bill software',
            ],
            [
                'title'    => 'Payment History',
                'route'    => 'tenant.billing.payments',
                'active'   => ['tenant.billing.payments*'],
                'keywords' => 'payment history settlements transaction trx bkash nagad receipts',
            ],
        ],
    ],

    // 3. Upstream Bandwidth
    [
        'title'    => 'Upstream Bandwidth',
        'icon'     => 'fas fa-tower-broadcast',
        'color'    => 'blue',
        'active'   => ['tenant.upstream*'],
        'roles'    => ['owner', 'super_admin', 'isp_admin', 'isp_manager'],
        'keywords' => 'upstream bandwidth carrier iig itc nttn bdix capacity bills payment vouchers transmission',
        'children' => [
            [
                'title'    => 'Carriers & Links',
                'route'    => 'tenant.upstream.index',
                'active'   => ['tenant.upstream.index'],
                'keywords' => 'upstream carriers links capacity iig itc nttn bdix cdn accounting',
            ],
            [
                'title'    => 'Carrier Bills',
                'route'    => 'tenant.upstream.invoices',
                'active'   => ['tenant.upstream.invoices*'],
                'keywords' => 'carrier invoices bills monthly dues transmission nttn',
            ],
            [
                'title'    => 'Payment Vouchers',
                'route'    => 'tenant.upstream.payments',
                'active'   => ['tenant.upstream.payments*', 'tenant.upstream.payment.voucher*'],
                'keywords' => 'payment vouchers disbursements settlements bank transfer cheque rtgs receipts',
            ],
        ],
    ],

    // 4. Network & Infra
    [
        'title'       => 'Network & Infra',
        'icon'        => 'fas fa-network-wired',
        'color'       => 'blue',
        'active'      => ['tenant.network*'],
        'permissions' => ['network.routers', 'network.radius', 'network.packages', 'network.olts_onus', 'network.ip_pools'],
        'keywords'    => 'network mikrotik olt onu ont ip pools vlans routing map',
        'children'    => [
            ['title' => 'MikroTik Routers',  'route' => 'tenant.network.mikrotik', 'active' => ['tenant.network.mikrotik*'], 'keywords' => 'mikrotik routers gateway ros rb pppoe queues'],
            ['title' => 'NAS & RADIUS',      'route' => 'tenant.network.nas',      'active' => ['tenant.network.nas*'],      'keywords' => 'nas network access servers radius aaa coa bras gateway'],
            ['title' => 'Internet Packages', 'route' => 'tenant.network.packages', 'active' => ['tenant.network.packages*'], 'keywords' => 'packages internet plans bandwidth pppoe speed rate price'],
            ['title' => 'OLT Devices',       'route' => 'tenant.network.olt',      'active' => ['tenant.network.olt*'],      'keywords' => 'olt devices optical gpon epon bdcom zte huawei'],
            ['title' => 'ONU / ONT',         'route' => 'tenant.network.onu',      'active' => ['tenant.network.onu*'],      'keywords' => 'onu ont terminals signal optical dbm client'],
            ['title' => 'IP Pools',          'route' => 'tenant.network.ip-pools', 'active' => ['tenant.network.ip-pools*'], 'keywords' => 'ip pools subnets ipv4 ipv6 cgnat dhcp'],
            ['title' => 'VLANs',             'route' => 'tenant.network.vlans',    'active' => ['tenant.network.vlans*'],    'keywords' => 'vlans 802.1q trunk access qinq broadcast'],
            ['title' => 'Routing & BGP',     'route' => 'tenant.network.routing',  'active' => ['tenant.network.routing*'],  'keywords' => 'networks routing bgp ospf static gateway transit'],
            ['title' => 'Network Map',       'route' => 'tenant.network.map',      'active' => ['tenant.network.map*'],      'keywords' => 'network map topology gis fiber pop location'],
        ],
    ],

    // 5. Reseller Partners
    [
        'title'       => 'Reseller Partners',
        'icon'        => 'fas fa-handshake',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.index*', 'tenant.resellers.show*', 'tenant.resellers.import*', 'tenant.resellers.margins*', 'tenant.resellers.subscriptions*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'reseller partners directory sub-isps franchise add packages margins subscriptions licenses import excel',
        'children'    => [
            [
                'title'    => 'Partner Directory',
                'route'    => 'tenant.resellers.index',
                'active'   => ['tenant.resellers.index*', 'tenant.resellers.show*'],
                'keywords' => 'reseller directory list profiles sub-isps franchise add retail',
            ],
            [
                'title'    => 'Import Resellers (Excel)',
                'route'    => 'tenant.resellers.import',
                'active'   => ['tenant.resellers.import*'],
                'keywords' => 'import excel csv bulk upload resellers partner sub-isps',
            ],
            [
                'title'    => 'Panel Licenses',
                'route'    => 'tenant.resellers.subscriptions',
                'active'   => ['tenant.resellers.subscriptions*'],
                'keywords' => 'panel licenses software renewal status expiry reseller',
            ],
        ],
    ],

    // 6. Wholesale Bandwidth
    [
        'title'       => 'Wholesale Bandwidth',
        'icon'        => 'fas fa-network-wired',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.bandwidth*', 'tenant.resellers.bandwidth-plans*', 'tenant.resellers.invoices*', 'tenant.resellers.capacity*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'wholesale bandwidth allocation rate plans tariff pricing capacity gateway mrtg traffic router core invoices bills payments collection',
        'children'    => [
            [
                'title'    => 'Bandwidth Rate Plans',
                'route'    => 'tenant.resellers.bandwidth-plans',
                'active'   => ['tenant.resellers.bandwidth-plans*'],
                'keywords' => 'bandwidth rate plans tariff pricing per mbps global bdix cdn wholesale packages',
            ],
            [
                'title'    => 'Bandwidth Allocation',
                'route'    => 'tenant.resellers.bandwidth',
                'active'   => ['tenant.resellers.bandwidth'],
                'keywords' => 'bandwidth allocation mbps wholesale mrtg traffic usage profiles cir mir',
            ],
            [
                'title'    => 'Invoices & Payments',
                'route'    => 'tenant.resellers.invoices',
                'active'   => ['tenant.resellers.invoices*'],
                'keywords' => 'wholesale invoices billing receipts money receipts bills payments dues collection',
            ],
            [
                'title'    => 'Gateway Capacity',
                'route'    => 'tenant.resellers.capacity',
                'active'   => ['tenant.resellers.capacity*'],
                'keywords' => 'gateway capacity router distribution load wholesale bandwidth throughput',
            ],
        ],
    ],

    // 7. Reseller Accounts
    [
        'title'       => 'Reseller Accounts',
        'icon'        => 'fas fa-wallet',
        'color'       => 'cyan',
        'active'      => ['tenant.resellers.recharge*', 'tenant.resellers.wallets*', 'tenant.resellers.ledger*'],
        'permissions' => ['resellers.view'],
        'keywords'    => 'reseller accounts billing recharge wallets credit adjustment ledger vouchers',
        'children'    => [
            [
                'title'    => 'Wallets & Credit',
                'route'    => 'tenant.resellers.wallets',
                'active'   => ['tenant.resellers.wallets*'],
                'keywords' => 'wallets balances credit limit topup balance reseller',
            ],
            [
                'title'    => 'Recharge History',
                'route'    => 'tenant.resellers.recharge',
                'active'   => ['tenant.resellers.recharge*'],
                'keywords' => 'recharge history online offline bank cash deposit gateway bkash nagad approval',
            ],
            [
                'title'    => 'Adjustment Ledger',
                'route'    => 'tenant.resellers.ledger',
                'active'   => ['tenant.resellers.ledger*'],
                'keywords' => 'ledger audit logs transactions voucher recharge adjustment credit debit reseller',
            ],
        ],
    ],

    // 8. Staff & HRM
    [
        'title'       => 'Staff & HRM',
        'icon'        => 'fas fa-user-tie',
        'color'       => 'indigo',
        'active'      => ['tenant.staff*', 'tenant.hrm*'],
        'roles'       => ['owner', 'super_admin', 'isp_admin', 'isp_manager'],
        'permissions' => ['settings.rbac'],
        'keywords'    => 'human resources staff hrm employees managers technicians collectors roster attendance commission',
        'children'    => [
            ['title' => 'Staff Directory',     'route' => 'tenant.staff.index',       'active' => ['tenant.staff.index'],       'keywords' => 'all employees directory unified list staff users hrm profile'],
            ['title' => 'Operations Staff',    'route' => 'tenant.staff.isp',         'active' => ['tenant.staff.isp*'],        'keywords' => 'isp operations staff managers noc engineers technicians collectors core'],
            ['title' => 'Reseller Staff',      'route' => 'tenant.staff.resellers',   'active' => ['tenant.staff.resellers*'],  'keywords' => 'reseller staff roster sub-isp managers technicians collectors partner team'],
            ['title' => 'Attendance',          'route' => 'tenant.staff.attendance',  'active' => ['tenant.staff.attendance*'], 'keywords' => 'staff attendance daily register punch present absent late leave shift'],
            ['title' => 'Activity Logs',       'route' => 'tenant.staff.logs',        'active' => ['tenant.staff.logs*'],       'keywords' => 'activity logs audit trail system security events telemetry history'],
            ['title' => 'Targets & Commission','route' => 'tenant.staff.commissions', 'active' => ['tenant.staff.commissions*'],'keywords' => 'staff target commission collector targets sales performance logs incentive'],
        ],
    ],

    // 9. Customers & CRM
    [
        'title'       => 'Customers & CRM',
        'icon'        => 'fas fa-users-line',
        'color'       => 'cyan',
        'active'      => ['tenant.customers*'],
        'permissions' => ['customers.view'],
        'keywords'    => 'customers crm subscribers retail users pppoe online sessions expired due zones areas disconnected master database onboard new create add',
        'children'    => [
            [
                'title'    => 'Coverage Zones',
                'route'    => 'tenant.customers.zones',
                'active'   => ['tenant.customers.zones*'],
                'keywords' => 'customer zones areas division district thana pop zone area mapping coverage location territory',
            ],
            [
                'title'             => 'All Customers',
                'route'             => 'tenant.customers.index',
                'active'            => ['tenant.customers.index', 'tenant.customers.create', 'tenant.customers.edit', 'tenant.customers.show'],
                'exclude_routes'    => ['tenant.customers.expired*', 'tenant.customers.due*', 'tenant.customers.online*', 'tenant.customers.disconnected*', 'tenant.customers.zones*', 'tenant.customers.bulk-payments*', 'tenant.customers.monthly-analytics*'],
                'require_no_status' => true,
                'keywords'          => 'all customers master database direct reseller subscribers list clients onboard create add',
            ],
            [
                'title'    => 'Online Sessions',
                'route'    => 'tenant.customers.online',
                'active'   => ['tenant.customers.online*'],
                'keywords' => 'online pppoe live active sessions uptime ip allocation nas radius',
            ],
            [
                'title'           => 'Expired Customers',
                'route'           => 'tenant.customers.expired',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'expired'],
                'active'          => ['tenant.customers.expired*'],
                'status_in'       => ['expired'],
                'active_color'    => 'rose',
                'keywords'        => 'expired subscribers customers list overdue validity মেয়াদ উত্তীর্ণ এক্সপায়ার',
            ],
            [
                'title'           => 'Due Customers',
                'route'           => 'tenant.customers.due',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'due'],
                'active'          => ['tenant.customers.due*'],
                'status_in'       => ['due'],
                'active_color'    => 'amber',
                'keywords'        => 'due overdue billing suspended unpaid customers বকেয়া',
            ],
            [
                'title'           => 'Disconnected',
                'route'           => 'tenant.customers.disconnected',
                'fallback_route'  => 'tenant.customers.index',
                'fallback_params' => ['status' => 'disconnected'],
                'active'          => ['tenant.customers.disconnected*', 'tenant.customers.expired-60*'],
                'status_in'       => ['disconnected', 'expired_60'],
                'active_color'    => 'rose',
                'keywords'        => 'disconnected churned closed users left equipment return onu return status inactive বন্ধ ডিসকানেক্ট ৬০ দিন',
            ],
            [
                'title'    => 'Bulk Payments',
                'route'    => 'tenant.customers.bulk-payments',
                'active'   => ['tenant.customers.bulk-payments*'],
                'keywords' => 'bulk payments batch bill receive pay reseller commission dues',
            ],
            [
                'title'    => 'Monthly Analytics',
                'route'    => 'tenant.customers.monthly-analytics',
                'active'   => ['tenant.customers.monthly-analytics*'],
                'keywords' => 'monthly analytics growth recharge new customers report cohorts trends cohorts billing month',
            ],
        ],
    ],

    // 10. Billing & Payment
    [
        'title'       => 'Billing & Payment',
        'icon'        => 'fas fa-coins',
        'color'       => 'amber',
        'active'      => ['tenant.finance*', 'tenant.collector*', 'tenant.accounts*', 'tenant.erp*'],
        'active_paths'=> ['admin/collector*', 'admin/finance*'],
        'permissions' => ['billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.wholesale', 'billing.gateways', 'billing.expenses', 'billing.ledger'],
        'keywords'    => 'billing collection accounts erp finance invoices bills receipts payments cash handover closing collector hub due wholesale gateway bkash nagad rocket sslcommerz income expense expenses ledger trial balance profit loss statement',
        'children'    => [
            [
                'title'    => 'Billing Catch-Up',
                'route'    => 'tenant.finance.billing-catchup',
                'active'   => ['tenant.finance.billing-catchup*'],
                'keywords' => 'billing catch up catchup uninvoiced missed invoices auto billing generate cron recovery queue audit',
            ],
            [
                'title'          => 'Due Invoices',
                'route'          => 'tenant.finance.invoices',
                'params'         => ['status' => 'due'],
                'active_mode'    => 'due_invoices',
                'keywords'       => 'due invoices unpaid pending customer bills ledger',
            ],
            [
                'title'          => 'Paid Invoices',
                'route'          => 'tenant.finance.invoices',
                'params'         => ['status' => 'paid'],
                'active_mode'    => 'paid_invoices',
                'keywords'       => 'paid invoices customer bills monthly billing ledger',
            ],
            [
                'title'    => 'Collector Collections',
                'route'    => 'tenant.finance.collector-collections',
                'active'   => ['tenant.finance.collector-collections*'],
                'keywords' => 'collector collections report receipts payments money summary audit',
            ],
            [
                'title'          => 'Cash Handover',
                'route'          => 'tenant.finance.cash-handover',
                'fallback_route' => 'tenant.collector.handover',
                'active'         => ['tenant.finance.cash-handover*', 'tenant.collector.handover*'],
                'keywords'       => 'daily cash handover closing collector manager verification cash approval vault',
            ],
            [
                'title'    => 'Reseller Billing',
                'route'    => 'tenant.finance.wholesale-billing',
                'active'   => ['tenant.finance.wholesale-billing*'],
                'keywords' => 'reseller wholesale billing monthly bandwidth package invoices sub-isp billing',
            ],
            [
                'title'    => 'Gateway Payments',
                'route'    => 'tenant.finance.gateway-transactions',
                'active'   => ['tenant.finance.gateway-transactions*'],
                'keywords' => 'online gateway transactions bkash nagad rocket sslcommerz shurjopay pgw logs payment gateway',
            ],
            [
                'title'    => 'Income & Expenses',
                'route'    => 'tenant.finance.expenses',
                'active'   => ['tenant.finance.expenses*'],
                'keywords' => 'income expense accounts company operational office rent bandwidth cost bills voucher',
            ],
            [
                'title'    => 'Accounts & Ledger',
                'route'    => 'tenant.finance.ledger',
                'active'   => ['tenant.finance.ledger*'],
                'keywords' => 'financial statements ledger trial balance cash book profit loss balance sheet accounts',
            ],
        ],
    ],

    // 11. Reports & Analytics
    [
        'title'       => 'Reports & Analytics',
        'icon'        => 'fas fa-chart-line',
        'color'       => 'rose',
        'active'      => ['tenant.reports*', 'tenant.analytics*', 'tenant.compliance*'],
        'permissions' => ['reports.revenue', 'reports.collection', 'reports.mrtg', 'reports.btrc', 'reports.inventory'],
        'keywords'    => 'reports analytics regulatory compliance monthly revenue growth arpu new connections churn collection due breakdown bandwidth mrtg peak graph btrc govt log server subscriber list equipment inventory fiber splitters stock',
        'children'    => [
            ['title' => 'Revenue & Growth',      'route' => 'tenant.reports.revenue',    'active' => ['tenant.reports.revenue*'],    'keywords' => 'monthly revenue growth report arpu new connections churn rate sales growth sales revenue'],
            ['title' => 'Collection & Due',      'route' => 'tenant.reports.collection', 'active' => ['tenant.reports.collection*'], 'keywords' => 'collection due breakdown zone collector package wise collection unpaid due report'],
            ['title' => 'Bandwidth & MRTG',      'route' => 'tenant.reports.mrtg',       'active' => ['tenant.reports.mrtg*'],       'keywords' => 'bandwidth mrtg peak graph total upstream consumption peak hours traffic analysis graph usage'],
            ['title' => 'BTRC Compliance',       'route' => 'tenant.reports.btrc',       'active' => ['tenant.reports.btrc*'],       'keywords' => 'btrc regulatory compliance log subscriber list log server govt compliance audit regulatory'],
            ['title' => 'Equipment & Inventory', 'route' => 'tenant.reports.inventory',  'active' => ['tenant.reports.inventory*'],  'keywords' => 'equipment inventory report fiber drum splitters onus patch cords stock hardware stock'],
        ],
    ],

    // 12. Helpdesk & Support
    [
        'title'       => 'Helpdesk & Support',
        'icon'        => 'fas fa-headset',
        'color'       => 'blue',
        'active'      => ['tenant.tickets*', 'tenant.helpdesk*', 'tenant.support*'],
        'permissions' => ['support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla'],
        'keywords'    => 'helpdesk support noc tickets ticketing complaints slow internet line disconnections fiber splicing onu replacement home visits field jobs new installation cable pulling reseller escalations sla performance analytics response resolution',
        'children'    => [
            ['title' => 'Support Tickets', 'route' => 'tenant.tickets.index',         'active' => ['tenant.tickets.index', 'tenant.tickets.show', 'tenant.tickets.create'], 'keywords' => 'all support tickets customer complaints slow internet line disconnections open closed pending'],
            ['title' => 'Field Jobs',      'route' => 'tenant.tickets.field-jobs',    'active' => ['tenant.tickets.field-jobs*'],    'keywords' => 'assigned field jobs technician fiber splicing onu replacement home visits roster dispatch'],
            ['title' => 'Installations',   'route' => 'tenant.tickets.installations', 'active' => ['tenant.tickets.installations*'], 'keywords' => 'new installation tasks pending physical connections cable pulling subscriber setup line active'],
            ['title' => 'Escalations',     'route' => 'tenant.tickets.escalations',   'active' => ['tenant.tickets.escalations*'],   'keywords' => 'reseller escalations complex technical issues escalated by sub-isps franchise partner support'],
            ['title' => 'SLA & Analytics', 'route' => 'tenant.tickets.sla-analytics', 'active' => ['tenant.tickets.sla-analytics*'], 'keywords' => 'sla performance analytics average response time ticket resolution rate engineer score kpi'],
        ],
    ],

    // 13. Settings & Admin
    [
        'title'       => 'Settings & Admin',
        'icon'        => 'fas fa-gear',
        'color'       => 'slate',
        'active'      => ['tenant.settings.profile*', 'tenant.settings.sms*', 'tenant.settings.payment-gateways*', 'tenant.settings.automation*', 'tenant.settings.backup*'],
        'permissions' => ['settings.profile', 'settings.sms_gateway', 'settings.automation', 'settings.backup', 'billing.gateways'],
        'keywords'    => 'system settings administration company profile sms whatsapp gateway automation auto-cut backup maintenance',
        'children'    => [
            ['title' => 'Company Profile',   'route' => 'tenant.settings.profile',          'active' => ['tenant.settings.profile*'],          'keywords' => 'isp company profile company name logo trade license contact info branding'],
            ['title' => 'SMS & Notifications','route'=> 'tenant.settings.sms-gateway',      'active' => ['tenant.settings.sms*'],              'keywords' => 'sms whatsapp gateway bulk sms configuration payment expiry templates notifications'],
            ['title' => 'Payment Gateways',  'route' => 'tenant.settings.payment-gateways', 'active' => ['tenant.settings.payment-gateways*'], 'keywords' => 'payment gateways bkash nagad rocket merchant api bangla qr bank transfer gateway pgw'],
            ['title' => 'Automation Rules',  'route' => 'tenant.settings.automation',       'active' => ['tenant.settings.automation*'],       'keywords' => 'automation auto-cut rules auto suspend non-paid users grace days setting cron billing'],
            ['title' => 'Backup & Restore',  'route' => 'tenant.settings.backup',           'active' => ['tenant.settings.backup*'],           'keywords' => 'system backup maintenance automated cloud local db database restore snapshot'],
        ],
    ],

    // 14. User & Access Control (Strictly scoped to RBAC/Audit/Users, zero leak!)
    [
        'title'       => 'User & Access Control',
        'icon'        => 'fas fa-user-shield',
        'color'       => 'slate',
        'active'      => ['tenant.settings.rbac*', 'tenant.settings.audit*', 'tenant.users*'],
        'permissions' => ['settings.rbac', 'settings.audit_logs'],
        'keywords'    => 'user access management security roles permissions users list staff members audit trail activity logs telemetry',
        'children'    => [
            ['title' => 'Roles & Permissions',    'route' => 'tenant.settings.rbac',       'active' => ['tenant.settings.rbac*'],  'keywords' => 'role based access control rbac permissions menu visibility roles acl security'],
            ['title' => 'Users List',             'route' => 'tenant.users.index',         'active' => ['tenant.users*'],          'keywords' => 'users list all users directory security logins staff employee accounts login credentials passwords'],
            ['title' => 'Activity & Audit Trail', 'route' => 'tenant.settings.audit-logs', 'active' => ['tenant.settings.audit*'], 'keywords' => 'activity audit trail full audit logs admin staff actions security login logs telemetry'],
        ],
    ],
];
