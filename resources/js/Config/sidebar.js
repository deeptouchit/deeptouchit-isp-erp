import { h } from 'vue'
import {
    HomeIcon,
    ServerIcon,
    UsersIcon,
    GlobeAltIcon,
    CpuChipIcon,
    CircleStackIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
    FolderIcon,
    ArrowPathIcon,
    ChartBarIcon,
    BoltIcon,
    CreditCardIcon,
    LifebuoyIcon,
    DocumentTextIcon,
    CommandLineIcon,
    UserGroupIcon,
    WrenchIcon,
    Cog6ToothIcon,
    AdjustmentsHorizontalIcon,
    ServerStackIcon
} from '@heroicons/vue/24/outline'

// Speedometer Gauge Icon matching modern ERP Executive Dashboard
const SpeedometerIcon = (props) => h('svg', {
    ...props,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: '1.8',
    strokeLinecap: 'round',
    strokeLinejoin: 'round'
}, [
    h('path', { d: 'M12 14l4-4' }),
    h('path', { d: 'M3.34 19a10 10 0 1 1 17.32 0' }),
    h('circle', { cx: '12', cy: '14', r: '2' })
])

/**
 * Enterprise Sidebar Navigation Blueprint.
 * Grouped into clear sections matching modern ERP / HostingOS architecture.
 *
 * @param {Function} route Ziggy route resolution function
 * @returns {Array} List of categorized navigation items
 */
export function getSidebarNavigation(route) {
    return [
        // ==========================================
        // 1. MAIN / OVERVIEW
        // ==========================================
        {
            section: 'MAIN',
            sectionTitle: 'Main',
            id: 'dashboard',
            title: 'Executive Dashboard',
            icon: SpeedometerIcon,
            href: route('admin.dashboard'),
            active: route().current('admin.dashboard'),
            subItems: []
        },

        // ==========================================
        // 2. HOSTING & CLOUD INFRASTRUCTURE
        // ==========================================
        {
            section: 'HOSTING',
            sectionTitle: 'Hosting & Infrastructure',
            id: 'infrastructure',
            title: 'Infrastructure',
            icon: ServerIcon,
            active: route().current('admin.servers.*') || route().current('admin.infrastructure.*') || route().current('admin.groups.*'),
            subItems: [
                { name: 'Servers', href: route('admin.servers.index'), active: route().current('admin.servers.*') || route().current('admin.infrastructure.servers*') },
                { name: 'Server Groups', href: route('admin.infrastructure.groups'), active: route().current('admin.infrastructure.groups*') || route().current('admin.groups.*') },
                { name: 'Server Health', href: route('admin.infrastructure.health'), active: route().current('admin.infrastructure.health*') },
                { name: 'Resources', href: route('admin.infrastructure.resources'), active: route().current('admin.infrastructure.resources*') },
                { name: 'Service Control', href: route('admin.infrastructure.services'), active: route().current('admin.infrastructure.services*') },
                { name: 'Maintenance', href: route('admin.infrastructure.maintenance'), active: route().current('admin.infrastructure.maintenance*') },
            ]
        },
        {
            section: 'HOSTING',
            id: 'hosting',
            title: 'Hosting',
            icon: GlobeAltIcon,
            active: route().current('admin.websites.*') || route().current('admin.plans.*') || route().current('admin.hosting.*'),
            subItems: [
                { name: 'Hosting Accounts', href: route('admin.hosting.accounts'), active: route().current('admin.hosting.accounts*') },
                { name: 'Packages', href: route('admin.plans.index'), active: route().current('admin.plans.*') || route().current('admin.hosting.packages*') },
                { name: 'Domains', href: route('admin.websites.index'), active: route().current('admin.websites.*') || route().current('admin.hosting.domains*') },
                { name: 'Subdomains', href: route('admin.hosting.subdomains'), active: route().current('admin.hosting.subdomains*') },
                { name: 'Aliases', href: route('admin.hosting.aliases'), active: route().current('admin.hosting.aliases*') },
                { name: 'Account Activity', href: route('admin.hosting.activity'), active: route().current('admin.hosting.activity*') },
            ]
        },
        {
            section: 'HOSTING',
            id: 'customers',
            title: 'Customers',
            icon: UsersIcon,
            active: route().current('admin.users.*') || route().current('admin.customers.*'),
            subItems: [
                { name: 'All Customers', href: route('admin.users.index'), active: route().current('admin.users.*') || route().current('admin.customers.all*') || route().current('admin.customers.index') },
                { name: 'Active', href: route('admin.customers.active'), active: route().current('admin.customers.active*') },
                { name: 'Suspended', href: route('admin.customers.suspended'), active: route().current('admin.customers.suspended*') },
                { name: 'Customer Activity', href: route('admin.customers.activity'), active: route().current('admin.customers.activity*') },
            ]
        },

        // ==========================================
        // 3. SERVICES, ENGINES & STORAGE
        // ==========================================
        {
            section: 'SERVICES',
            sectionTitle: 'Services & Engines',
            id: 'php',
            title: 'PHP Manager',
            icon: CpuChipIcon,
            badge: 'Multi-PHP',
            active: route().current('admin.php.*'),
            subItems: [
                { name: 'PHP Versions', href: route('admin.php.index'), active: route().current('admin.php.index') || route().current('admin.php.versions') },
                { name: 'Install / Remove', href: route('admin.php.install-remove'), active: route().current('admin.php.install-remove') },
                { name: 'PHP-FPM Pools', href: route('admin.php.pools'), active: route().current('admin.php.pools') },
                { name: 'Extensions', href: route('admin.php.extensions'), active: route().current('admin.php.extensions') },
                { name: 'Configuration', href: route('admin.php.configuration'), active: route().current('admin.php.configuration') },
                { name: 'Per-Site PHP', href: route('admin.php.per-site'), active: route().current('admin.php.per-site') },
                { name: 'PHP-FPM Logs', href: route('admin.php.logs'), active: route().current('admin.php.logs') },
            ]
        },
        {
            section: 'SERVICES',
            id: 'databases',
            title: 'Databases',
            icon: CircleStackIcon,
            active: route().current('admin.databases.*'),
            subItems: [
                { name: 'MySQL / MariaDB', href: route('admin.databases.mysql'), active: route().current('admin.databases.mysql') || route().current('admin.databases.index') },
                { name: 'PostgreSQL', href: route('admin.databases.postgres'), active: route().current('admin.databases.postgres') },
                { name: 'Databases', href: route('admin.databases.list'), active: route().current('admin.databases.list') },
                { name: 'Database Users', href: route('admin.databases.users'), active: route().current('admin.databases.users') },
                { name: 'phpMyAdmin', href: '/phpmyadmin', active: false, external: true, badge: 'SSO' },
                { name: 'Slow Query Logs', href: route('admin.databases.logs'), active: route().current('admin.databases.logs') },
            ]
        },
        {
            section: 'SERVICES',
            id: 'email',
            title: 'Email',
            icon: EnvelopeIcon,
            active: route().current('admin.email.*'),
            subItems: [
                { name: 'Mail Domains', href: route('admin.email.domains'), active: route().current('admin.email.domains') },
                { name: 'Mail Accounts', href: route('admin.email.accounts'), active: route().current('admin.email.accounts') },
                { name: 'Forwarders', href: route('admin.email.forwarders'), active: route().current('admin.email.forwarders') },
                { name: 'Auto Responders', href: route('admin.email.auto-responders'), active: route().current('admin.email.auto-responders') },
                { name: 'DKIM / SPF / DMARC', href: route('admin.email.dkim-spf'), active: route().current('admin.email.dkim-spf') },
                { name: 'Spam Protection', href: route('admin.email.spam'), active: route().current('admin.email.spam') },
                { name: 'SMTP DeepTouchHost Relay', href: route('admin.email.relay'), active: route().current('admin.email.relay*') },
                { name: 'Mail Queue Logs', href: route('admin.email.logs'), active: route().current('admin.email.logs') },
                { name: 'Webmail Client', href: '/webmail', active: false, external: true, badge: 'Live' },
            ]
        },
        {
            section: 'SERVICES',
            id: 'dns',
            title: 'DNS',
            icon: GlobeAltIcon,
            active: route().current('admin.dns.*'),
            subItems: [
                { name: 'DNS Zones', href: route('admin.dns.zones'), active: route().current('admin.dns.zones') },
                { name: 'DNS Records', href: route('admin.dns.records'), active: route().current('admin.dns.records') },
                { name: 'DNS Templates', href: route('admin.dns.templates'), active: route().current('admin.dns.templates') },
                { name: 'Nameservers', href: route('admin.dns.nameservers'), active: route().current('admin.dns.nameservers') },
                { name: 'DNS Diagnostics', href: route('admin.dns.diagnostics'), active: route().current('admin.dns.diagnostics') },
            ]
        },
        {
            section: 'SERVICES',
            id: 'files',
            title: 'Files & Storage',
            icon: FolderIcon,
            active: route().current('admin.files.*'),
            subItems: [
                { name: 'File Manager', href: route('admin.files.manager'), active: route().current('admin.files.manager') },
                { name: 'Storage', href: route('admin.files.storage'), active: route().current('admin.files.storage') },
                { name: 'FTP Accounts', href: route('admin.files.ftp'), active: route().current('admin.files.ftp*') },
                { name: 'SFTP Users', href: route('admin.files.sftp'), active: route().current('admin.files.sftp*') },
                { name: 'Disk Quotas', href: route('admin.files.quotas'), active: route().current('admin.files.quotas') },
                { name: 'Storage Cleanup', href: route('admin.files.cleanup'), active: route().current('admin.files.cleanup') },
            ]
        },

        // ==========================================
        // 4. SECURITY & DATA PROTECTION
        // ==========================================
        {
            section: 'SECURITY',
            sectionTitle: 'Security & Protection',
            id: 'security',
            title: 'Security',
            icon: ShieldCheckIcon,
            active: route().current('admin.security.*'),
            subItems: [
                { name: 'SSL Certificates', href: route('admin.security.ssl'), active: route().current('admin.security.ssl') },
                { name: 'Firewall', href: route('admin.security.firewall'), active: route().current('admin.security.firewall') },
                { name: 'IP Blocklist', href: route('admin.security.blocklist'), active: route().current('admin.security.blocklist') || route().current('admin.security.index') },
                { name: 'IP Allowlist', href: route('admin.security.allowlist'), active: route().current('admin.security.allowlist') },
                { name: 'Fail2Ban', href: route('admin.security.fail2ban'), active: route().current('admin.security.fail2ban') },
                { name: 'Security Events', href: route('admin.security.events'), active: route().current('admin.security.events') },
            ]
        },
        {
            section: 'SECURITY',
            id: 'backups',
            title: 'Backups',
            icon: ArrowPathIcon,
            active: route().current('admin.backups.*'),
            subItems: [
                { name: 'Backup Dashboard', href: route('admin.backups.dashboard'), active: route().current('admin.backups.dashboard') },
                { name: 'Backup Jobs', href: route('admin.backups.jobs'), active: route().current('admin.backups.jobs') },
                { name: 'Backup Storage', href: route('admin.backups.storage'), active: route().current('admin.backups.storage') },
                { name: 'Backup Schedules', href: route('admin.backups.schedules'), active: route().current('admin.backups.schedules') },
                { name: 'Restore', href: route('admin.backups.restore'), active: route().current('admin.backups.restore') },
                { name: 'Backup Logs', href: route('admin.backups.logs'), active: route().current('admin.backups.logs') },
            ]
        },

        // ==========================================
        // 5. AUTOMATION & MONITORING
        // ==========================================
        {
            section: 'MONITORING',
            sectionTitle: 'Automation & Monitoring',
            id: 'monitoring',
            title: 'Monitoring',
            icon: ChartBarIcon,
            active: route().current('admin.monitoring.*'),
            subItems: [
                { name: 'Server Monitoring', href: route('admin.monitoring.overview'), active: route().current('admin.monitoring.overview') },
                { name: 'CPU', href: route('admin.monitoring.cpu'), active: route().current('admin.monitoring.cpu') },
                { name: 'RAM', href: route('admin.monitoring.ram'), active: route().current('admin.monitoring.ram') },
                { name: 'Disk', href: route('admin.monitoring.disk'), active: route().current('admin.monitoring.disk') },
                { name: 'Network Traffic', href: route('admin.monitoring.network'), active: route().current('admin.monitoring.network') },
                { name: 'PHP-FPM', href: route('admin.monitoring.php-fpm'), active: route().current('admin.monitoring.php-fpm') },
                { name: 'Database', href: route('admin.monitoring.database'), active: route().current('admin.monitoring.database') },
                { name: 'Service Uptime', href: route('admin.monitoring.services'), active: route().current('admin.monitoring.services') },
                { name: 'Alerts', href: route('admin.monitoring.alerts'), active: route().current('admin.monitoring.alerts') },
            ]
        },
        {
            section: 'MONITORING',
            id: 'automation',
            title: 'Automation',
            icon: BoltIcon,
            active: route().current('admin.automation.*'),
            subItems: [
                { name: 'Cron Jobs', href: route('admin.automation.cron'), active: route().current('admin.automation.cron') },
                { name: 'Scheduled Tasks', href: route('admin.automation.scheduled-tasks'), active: route().current('admin.automation.scheduled-tasks') },
                { name: 'Queue Workers', href: route('admin.automation.queues'), active: route().current('admin.automation.queues') },
                { name: 'Auto Backup', href: route('admin.automation.auto-backup'), active: route().current('admin.automation.auto-backup') },
                { name: 'Auto SSL', href: route('admin.automation.auto-ssl'), active: route().current('admin.automation.auto-ssl') },
                { name: 'Auto Suspension', href: route('admin.automation.auto-suspension'), active: route().current('admin.automation.auto-suspension') },
                { name: 'Maintenance Jobs', href: route('admin.automation.maintenance'), active: route().current('admin.automation.maintenance') },
            ]
        },

        // ==========================================
        // 6. BILLING & SUPPORT
        // ==========================================
        {
            section: 'BUSINESS',
            sectionTitle: 'Billing & Support',
            id: 'billing',
            title: 'Billing',
            icon: CreditCardIcon,
            active: route().current('admin.billing.*'),
            subItems: [
                { name: 'Invoices', href: route('admin.billing.invoices'), active: route().current('admin.billing.invoices') },
                { name: 'Payments', href: route('admin.billing.payments'), active: route().current('admin.billing.payments') },
                { name: 'Transactions', href: route('admin.billing.transactions'), active: route().current('admin.billing.transactions') },
                { name: 'Coupons', href: route('admin.billing.coupons'), active: route().current('admin.billing.coupons') },
                { name: 'Credits', href: route('admin.billing.credits'), active: route().current('admin.billing.credits') },
                { name: 'Payment Gateways', href: route('admin.billing.gateways'), active: route().current('admin.billing.gateways') },
            ]
        },
        {
            section: 'BUSINESS',
            id: 'support',
            title: 'Support',
            icon: LifebuoyIcon,
            active: route().current('admin.tickets.*') || route().current('admin.support.*'),
            subItems: [
                { name: 'Tickets', href: route('admin.tickets.index'), active: route().current('admin.tickets.*') },
                { name: 'Departments', href: route('admin.support.departments'), active: route().current('admin.support.departments') },
                { name: 'Support Agents', href: route('admin.support.agents'), active: route().current('admin.support.agents') },
                { name: 'Canned Responses', href: route('admin.support.canned-responses'), active: route().current('admin.support.canned-responses') },
                { name: 'Announcements', href: route('admin.support.announcements'), active: route().current('admin.support.announcements') },
            ]
        },

        // ==========================================
        // 7. SYSTEM, AUDIT & CONFIGURATION
        // ==========================================
        {
            section: 'SYSTEM',
            sectionTitle: 'System & Preferences',
            id: 'logs',
            title: 'Logs & Audit',
            icon: DocumentTextIcon,
            active: route().current('admin.logs.*'),
            subItems: [
                { name: 'System Logs', href: route('admin.logs.system'), active: route().current('admin.logs.system') },
                { name: 'Web Server Logs', href: route('admin.logs.web-server'), active: route().current('admin.logs.web-server') },
                { name: 'PHP System Logs', href: route('admin.logs.php'), active: route().current('admin.logs.php') },
                { name: 'DB Audit Trail', href: route('admin.logs.database'), active: route().current('admin.logs.database') },
                { name: 'Email Activity Logs', href: route('admin.logs.mail'), active: route().current('admin.logs.mail') },
                { name: 'Security Logs', href: route('admin.logs.security'), active: route().current('admin.logs.security') },
                { name: 'Login History', href: route('admin.logs.login-history'), active: route().current('admin.logs.login-history') },
                { name: 'Audit Trail', href: route('admin.logs.audit'), active: route().current('admin.logs.audit') },
            ]
        },
        {
            section: 'SYSTEM',
            id: 'api',
            title: 'API & Integrations',
            icon: CommandLineIcon,
            active: route().current('admin.api.*'),
            subItems: [
                { name: 'API Keys', href: route('admin.api.keys'), active: route().current('admin.api.keys') },
                { name: 'API Users', href: route('admin.api.users'), active: route().current('admin.api.users') },
                { name: 'Webhooks', href: route('admin.api.webhooks'), active: route().current('admin.api.webhooks') },
                { name: 'Integrations', href: route('admin.api.integrations'), active: route().current('admin.api.integrations') },
                { name: 'API Logs', href: route('admin.api.logs'), active: route().current('admin.api.logs') },
                { name: 'Rate Limits', href: route('admin.api.rate-limits'), active: route().current('admin.api.rate-limits') },
            ]
        },
        {
            section: 'SYSTEM',
            id: 'administration',
            title: 'Administration',
            icon: UserGroupIcon,
            active: route().current('admin.administration.*'),
            subItems: [
                { name: 'Administrators', href: route('admin.administration.administrators'), active: route().current('admin.administration.administrators') },
                { name: 'Staff', href: route('admin.administration.staff'), active: route().current('admin.administration.staff') },
                { name: 'Roles', href: route('admin.administration.roles'), active: route().current('admin.administration.roles') },
                { name: 'Permissions', href: route('admin.administration.permissions'), active: route().current('admin.administration.permissions') },
                { name: 'Sessions', href: route('admin.administration.sessions'), active: route().current('admin.administration.sessions') },
                { name: '2FA', href: route('admin.administration.2fa'), active: route().current('admin.administration.2fa') },
            ]
        },
        {
            section: 'SYSTEM',
            id: 'root-tools',
            title: 'Root Tools',
            icon: WrenchIcon,
            badge: 'Root',
            active: route().current('admin.root-tools.*'),
            subItems: [
                { name: 'Terminal', href: route('admin.root-tools.terminal'), active: route().current('admin.root-tools.terminal*'), badge: 'SSH' },
                { name: 'Process Manager', href: route('admin.root-tools.processes'), active: route().current('admin.root-tools.processes') },
                { name: 'Service Manager', href: route('admin.root-tools.services'), active: route().current('admin.root-tools.services') },
                { name: 'Package Manager', href: route('admin.root-tools.packages'), active: route().current('admin.root-tools.packages') },
                { name: 'Network Tools', href: route('admin.root-tools.network'), active: route().current('admin.root-tools.network') },
                { name: 'System Users', href: route('admin.root-tools.system-users'), active: route().current('admin.root-tools.system-users') },
                { name: 'System Groups', href: route('admin.root-tools.system-groups'), active: route().current('admin.root-tools.system-groups') },
                { name: 'System Commands', href: route('admin.root-tools.commands'), active: route().current('admin.root-tools.commands') },
            ]
        },
        {
            section: 'SYSTEM',
            id: 'settings',
            title: 'Settings',
            icon: Cog6ToothIcon,
            active: route().current('admin.settings*'),
            subItems: [
                { name: 'General', href: route('admin.settings.general'), active: route().current('admin.settings.general') },
                { name: 'Branding', href: route('admin.settings.branding'), active: route().current('admin.settings.branding') },
                { name: 'Localization', href: route('admin.settings.localization'), active: route().current('admin.settings.localization') },
                { name: 'Mail SMTP & Gateway', href: route('admin.settings.email'), active: route().current('admin.settings.email') },
                { name: 'SMS', href: route('admin.settings.sms'), active: route().current('admin.settings.sms') },
                { name: 'Notifications', href: route('admin.settings.notifications'), active: route().current('admin.settings.notifications') },
                { name: 'Security Policies', href: route('admin.settings.security'), active: route().current('admin.settings.security') },
                { name: 'Disaster Recovery & Retention', href: route('admin.settings.backup'), active: route().current('admin.settings.backup') },
                { name: 'System Defaults', href: route('admin.settings.defaults'), active: route().current('admin.settings.defaults') },
            ]
        }
    ]
}
