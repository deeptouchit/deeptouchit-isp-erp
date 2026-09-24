<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color', 30)->default('purple')->after('is_system');
            }
        });

        // 1. Seed Granular Platform Permissions
        $permissionGroups = [
            'Infrastructure & Servers' => [
                'servers.view' => 'View server telemetry and performance graphs',
                'servers.manage' => 'Manage system services, PHP versions, and MySQL pools',
                'servers.reboot' => 'Restart webserver, database daemon, and background services',
                'servers.terminal' => 'Access web terminal and root diagnostic console',
            ],
            'Domains & DNS' => [
                'dns.view' => 'View hosted zones, records, and domain aliases',
                'dns.manage' => 'Create and modify DNS records and nameservers',
            ],
            'Mail & Email Hosting' => [
                'email.view' => 'View email accounts, quotas, and logs',
                'email.manage' => 'Create mailboxes, forwarders, and auto-responders',
                'email.security' => 'Configure DKIM, SPF, and DMARC policies',
            ],
            'Security & Firewall' => [
                'security.view' => 'View security events, bans, and audit logs',
                'security.firewall' => 'Modify UFW rules, blocklists, and allowlists',
                'security.fail2ban' => 'Unban IP addresses and manage jail thresholds',
            ],
            'Billing & Finance' => [
                'billing.view' => 'View invoices, transactions, and revenue stats',
                'billing.invoices' => 'Generate, cancel, and modify billing invoices',
                'billing.gateways' => 'Configure payment processors and coupons',
            ],
            'Support & Helpdesk' => [
                'support.view' => 'View tickets, customer messages, and ratings',
                'support.tickets' => 'Reply, assign, and resolve support tickets',
                'support.departments' => 'Manage support departments and canned replies',
            ],
            'Automation & Maintenance' => [
                'cron.view' => 'View scheduled tasks, cron jobs, and queues',
                'cron.manage' => 'Trigger backups, auto-SSL renewal, and queue workers',
            ],
            'API & Integrations' => [
                'api.view' => 'View REST API logs and webhook dispatches',
                'api.manage' => 'Generate API keys, configure webhooks, and rate limits',
            ],
            'Administration & RBAC' => [
                'admin.view' => 'View administrator and staff directories',
                'admin.users' => 'Create and manage administrative accounts',
                'admin.roles' => 'Build custom roles and assign permission matrices',
            ],
        ];

        if (class_exists('Spatie\Permission\Models\Permission') && class_exists('Spatie\Permission\Models\Role')) {
            foreach ($permissionGroups as $group => $permissions) {
                foreach ($permissions as $name => $desc) {
                    Permission::firstOrCreate(
                        ['name' => $name, 'guard_name' => 'web']
                    );
                }
            }

            // 2. Seed Standard Roles
            $allPermissions = Permission::all();

            // Super Admin
            $superAdmin = Role::firstOrCreate(
                ['name' => 'super_admin', 'guard_name' => 'web'],
                [
                    'display_name' => 'Super Administrator',
                    'description' => 'Unrestricted root-level access to all platform systems, servers, billing, and security controls.',
                    'is_system' => true,
                    'color' => 'purple',
                ]
            );
            $superAdmin->syncPermissions($allPermissions);

            // Systems Engineer
            $sysEngineer = Role::firstOrCreate(
                ['name' => 'systems_engineer', 'guard_name' => 'web'],
                [
                    'display_name' => 'Systems & Infrastructure Engineer',
                    'description' => 'Manages server performance, PHP/DB services, cron schedules, backups, and terminal diagnostics.',
                    'is_system' => true,
                    'color' => 'blue',
                ]
            );
            $sysEngineer->syncPermissions([
                'servers.view', 'servers.manage', 'servers.reboot', 'servers.terminal',
                'dns.view', 'dns.manage',
                'cron.view', 'cron.manage',
                'security.view',
            ]);

            // Security Officer
            $secOfficer = Role::firstOrCreate(
                ['name' => 'security_officer', 'guard_name' => 'web'],
                [
                    'display_name' => 'Security & SOC Officer',
                    'description' => 'Monitors intrusion alerts, Fail2ban jails, audit trails, firewall allowlists, and API rate limits.',
                    'is_system' => true,
                    'color' => 'emerald',
                ]
            );
            $secOfficer->syncPermissions([
                'security.view', 'security.firewall', 'security.fail2ban',
                'api.view', 'api.manage',
                'admin.view',
            ]);

            // Billing Administrator
            $billingAdmin = Role::firstOrCreate(
                ['name' => 'billing_admin', 'guard_name' => 'web'],
                [
                    'display_name' => 'Billing Administrator',
                    'description' => 'Handles customer invoices, refund processing, payment gateways, credits, and coupon campaigns.',
                    'is_system' => false,
                    'color' => 'amber',
                ]
            );
            $billingAdmin->syncPermissions([
                'billing.view', 'billing.invoices', 'billing.gateways',
                'support.view',
            ]);

            // Support Lead
            $supportLead = Role::firstOrCreate(
                ['name' => 'support_lead', 'guard_name' => 'web'],
                [
                    'display_name' => 'Customer Support Lead',
                    'description' => 'Supervises ticket queues, agent assignments, department routing, and customer escalations.',
                    'is_system' => false,
                    'color' => 'indigo',
                ]
            );
            $supportLead->syncPermissions([
                'support.view', 'support.tickets', 'support.departments',
                'email.view',
            ]);

            // Support Agent (L1)
            $supportAgent = Role::firstOrCreate(
                ['name' => 'support_agent', 'guard_name' => 'web'],
                [
                    'display_name' => 'Technical Support Agent (L1)',
                    'description' => 'Responds to customer support tickets and uses pre-approved canned responses.',
                    'is_system' => false,
                    'color' => 'cyan',
                ]
            );
            $supportAgent->syncPermissions([
                'support.view', 'support.tickets',
            ]);

            // 3. Assign Super Admin to user ID 1 and other seeded administrators
            $user1 = DB::table('users')->where('id', 1)->first();
            if ($user1 && Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $superAdmin->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => 1,
                ]);
            }

            $sysAdmin = DB::table('users')->where('email', 'sysops@deeptouchhost.com')->first();
            if ($sysAdmin && Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $sysEngineer->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $sysAdmin->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('roles', 'is_system')) {
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('roles', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
