<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'category')) {
                $table->string('category', 60)->default('General')->after('name');
            }
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->string('description', 255)->nullable()->after('category');
            }
        });

        $descriptions = [
            'servers.view' => ['category' => 'Infrastructure & Servers', 'description' => 'View server telemetry, CPU/RAM utilization, and hardware diagnostics.'],
            'servers.manage' => ['category' => 'Infrastructure & Servers', 'description' => 'Manage system services, install/remove PHP-FPM pools, and MySQL instances.'],
            'servers.reboot' => ['category' => 'Infrastructure & Servers', 'description' => 'Restart Nginx webserver, database daemon, and background system services.'],
            'servers.terminal' => ['category' => 'Infrastructure & Servers', 'description' => 'Access web terminal and root diagnostic console.'],

            'dns.view' => ['category' => 'Domains & DNS', 'description' => 'View hosted zones, DNS records, and domain aliases.'],
            'dns.manage' => ['category' => 'Domains & DNS', 'description' => 'Create, modify, and delete DNS zone records and custom nameservers.'],

            'email.view' => ['category' => 'Mail & Postfix', 'description' => 'View email accounts, storage quotas, and SMTP/IMAP logs.'],
            'email.manage' => ['category' => 'Mail & Postfix', 'description' => 'Create mailboxes, email forwarders, and auto-responders.'],
            'email.security' => ['category' => 'Mail & Postfix', 'description' => 'Generate and enforce DKIM keys, SPF records, and DMARC policies.'],

            'security.view' => ['category' => 'Security & Firewall', 'description' => 'View security events, Fail2ban ban lists, and login history logs.'],
            'security.firewall' => ['category' => 'Security & Firewall', 'description' => 'Modify UFW firewall rules, IP blocklists, and trusted allowlists.'],
            'security.fail2ban' => ['category' => 'Security & Firewall', 'description' => 'Unban IP addresses and configure jail threshold parameters.'],

            'billing.view' => ['category' => 'Billing & Finance', 'description' => 'View customer invoices, payment transactions, and gateway metrics.'],
            'billing.invoices' => ['category' => 'Billing & Finance', 'description' => 'Generate, refund, cancel, and modify billing invoices.'],
            'billing.gateways' => ['category' => 'Billing & Finance', 'description' => 'Configure payment processors, coupons, and client credit balances.'],

            'support.view' => ['category' => 'Support & Helpdesk', 'description' => 'View support tickets, customer conversations, and satisfaction ratings.'],
            'support.tickets' => ['category' => 'Support & Helpdesk', 'description' => 'Reply to tickets, assign staff agents, and close support requests.'],
            'support.departments' => ['category' => 'Support & Helpdesk', 'description' => 'Manage support departments, canned replies, and announcements.'],

            'cron.view' => ['category' => 'Automation & Tasks', 'description' => 'View scheduled cron jobs, background queue jobs, and maintenance tasks.'],
            'cron.manage' => ['category' => 'Automation & Tasks', 'description' => 'Trigger automated backups, auto-SSL certificate renewal, and queue workers.'],

            'api.view' => ['category' => 'REST API & Webhooks', 'description' => 'View inbound REST API logs, webhook delivery histories, and rate limit stats.'],
            'api.manage' => ['category' => 'REST API & Webhooks', 'description' => 'Generate REST API keys, configure webhooks, and enforce throttling rules.'],

            'admin.view' => ['category' => 'Administration & RBAC', 'description' => 'View platform administrators and staff personnel rosters.'],
            'admin.users' => ['category' => 'Administration & RBAC', 'description' => 'Create and modify administrator accounts and enforce 2FA.'],
            'admin.roles' => ['category' => 'Administration & RBAC', 'description' => 'Create custom roles and assign capability permission matrices.'],
        ];

        foreach ($descriptions as $name => $meta) {
            DB::table('permissions')->where('name', $name)->update($meta);
        }
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('permissions', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
