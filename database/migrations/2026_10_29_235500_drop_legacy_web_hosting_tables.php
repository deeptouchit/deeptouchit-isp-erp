<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely drop 38 legacy Web Hosting & Server Control Panel tables.
     */
    public function up(): void
    {
        $legacyTables = [
            'wordpress_installations',
            'websites',
            'hosting_plans',
            'ftp_accounts',
            'sftp_users',
            'dns_template_records',
            'dns_templates',
            'dns_records',
            'dns_zones',
            'nameservers',
            'domain_aliases',
            'domain_redirects',
            'email_auto_responders',
            'email_forwarders',
            'email_messages',
            'email_relay_settings',
            'email_spam_settings',
            'email_accounts',
            'email_domains',
            'firewall_rules',
            'ip_allowlists',
            'ip_blocks',
            'git_deployment_logs',
            'git_repositories',
            'hotlink_protections',
            'directory_indexings',
            'protected_directory_users',
            'protected_directories',
            'ssl_certificates',
            'server_credentials',
            'server_events',
            'server_logs',
            'server_metric_aggregates',
            'server_metrics',
            'server_services',
            'server_groups',
            'servers',
            'databases',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($legacyTables as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tables are permanently purged legacy dead code.
    }
};
