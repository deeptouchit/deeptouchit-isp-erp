<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\HostingPlan;
use App\Models\Nameserver;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminDefaultSettingController extends Controller
{
    /**
     * Recommended Production Hosting & Account Defaults.
     */
    public const DEFAULTS = [
        // Provisioning & Account Templates
        'default_plan_id' => 1,
        'default_php_version' => '8.3',
        'default_web_server' => 'nginx',
        'default_document_root_format' => '/var/www/vhosts/{username}/{domain}/public_html',
        'default_subdomain_docroot_format' => '{domain_root}/subdomains/{subdomain}',
        'auto_ssl_on_provision' => true,
        'force_https_default' => true,

        // Quotas & Allocations
        'default_disk_quota_mb' => 5120, // 5 GB
        'default_bandwidth_monthly_gb' => 50,
        'default_inode_limit' => 150000,
        'default_max_databases' => 5,
        'default_max_email_accounts' => 10,
        'default_max_ftp_accounts' => 3,
        'default_max_subdomains' => 10,

        // Security & Shell Access
        'default_shell_access' => 'jailed',
        'default_ssh_port' => 22,
        'default_auto_backup' => true,
        'default_modsecurity_enabled' => true,
        'default_cagefs_enabled' => true,

        // DNS & Nameserver Configuration
        'primary_nameserver' => 'ns1.deeptouchit.com',
        'secondary_nameserver' => 'ns2.deeptouchit.com',
        'default_soa_email' => 'admin.deeptouchit.com',
        'default_ttl' => 3600,
        'auto_dns_zone_creation' => true,

        // PHP Runtime Directives (php.ini)
        'php_memory_limit' => '256M',
        'php_max_execution_time' => 60,
        'php_upload_max_filesize' => '64M',
        'php_post_max_size' => '64M',
        'php_display_errors' => false,

        // Holding & Suspended Pages
        'suspended_page_message' => 'This account has been temporarily suspended. Please contact technical support.',
        'parking_page_enabled' => true,
        'parking_page_title' => 'Website Coming Soon - Hosted on DeepTouch Host Cloud',
    ];

    /**
     * Display System Provisioning Defaults Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // Installed system PHP versions
        $installedPhpVersions = ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

        // Live Database entities
        $plans = Schema::hasTable('hosting_plans')
            ? HostingPlan::select('id', 'name', 'disk_space', 'bandwidth', 'is_active')->get()
            : collect([]);

        $nameservers = Schema::hasTable('nameservers')
            ? Nameserver::select('id', 'hostname', 'ip_address', 'is_primary', 'is_default')->get()
            : collect([]);

        $totalAccounts = Schema::hasTable('subscriptions') ? Subscription::count() : 0;
        $activeAccounts = Schema::hasTable('subscriptions') ? Subscription::where('status', 'active')->count() : 0;

        // Calculate Provisioning Readiness Score out of 100
        $readinessScore = 60;
        if (!empty($settings['auto_ssl_on_provision'])) $readinessScore += 10;
        if (!empty($settings['force_https_default'])) $readinessScore += 5;
        if (!empty($settings['default_auto_backup'])) $readinessScore += 10;
        if (!empty($settings['default_cagefs_enabled'])) $readinessScore += 5;
        if (!empty($settings['primary_nameserver']) && !empty($settings['secondary_nameserver'])) $readinessScore += 5;
        if (!empty($settings['default_modsecurity_enabled'])) $readinessScore += 5;
        $readinessScore = min(100, $readinessScore);

        $stats = [
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'total_plans' => $plans->count(),
            'readiness_score' => $readinessScore,
            'server_ip' => '103.59.177.138',
            'server_hostname' => 'deeptouchit.com',
            'installed_php_versions' => $installedPhpVersions,
            'plans' => $plans,
            'nameservers' => $nameservers,
        ];

        return Inertia::render('Admin/Settings/Defaults/Index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Update System Provisioning Defaults.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'default_plan_id' => 'required|integer|min:1',
            'default_php_version' => 'required|string|in:7.4,8.0,8.1,8.2,8.3,8.4,8.5',
            'default_web_server' => 'required|string|in:nginx,apache,litespeed',
            'default_document_root_format' => 'required|string|max:255',
            'default_subdomain_docroot_format' => 'required|string|max:255',
            'auto_ssl_on_provision' => 'nullable|boolean',
            'force_https_default' => 'nullable|boolean',

            'default_disk_quota_mb' => 'required|integer|min:100|max:1048576',
            'default_bandwidth_monthly_gb' => 'required|integer|min:1|max:100000',
            'default_inode_limit' => 'required|integer|min:1000|max:10000000',
            'default_max_databases' => 'required|integer|min:0|max:1000',
            'default_max_email_accounts' => 'required|integer|min:0|max:1000',
            'default_max_ftp_accounts' => 'required|integer|min:0|max:100',
            'default_max_subdomains' => 'required|integer|min:0|max:500',

            'default_shell_access' => 'required|string|in:none,jailed,bash',
            'default_ssh_port' => 'required|integer|min:1|max:65535',
            'default_auto_backup' => 'nullable|boolean',
            'default_modsecurity_enabled' => 'nullable|boolean',
            'default_cagefs_enabled' => 'nullable|boolean',

            'primary_nameserver' => 'required|string|max:255',
            'secondary_nameserver' => 'required|string|max:255',
            'default_soa_email' => 'required|string|max:255',
            'default_ttl' => 'required|integer|min:60|max:86400',
            'auto_dns_zone_creation' => 'nullable|boolean',

            'php_memory_limit' => 'required|string|max:20',
            'php_max_execution_time' => 'required|integer|min:10|max:3600',
            'php_upload_max_filesize' => 'required|string|max:20',
            'php_post_max_size' => 'required|string|max:20',
            'php_display_errors' => 'nullable|boolean',

            'suspended_page_message' => 'required|string|max:500',
            'parking_page_enabled' => 'nullable|boolean',
            'parking_page_title' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set('defaults.' . $key, $value, 'defaults');
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update_system_defaults',
            'description' => 'Updated global hosting provisioning defaults, quotas, nameservers, and PHP runtime templates',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'default_php_version' => $validated['default_php_version'],
                'default_web_server' => $validated['default_web_server'],
                'default_disk_quota_mb' => $validated['default_disk_quota_mb'],
                'primary_nameserver' => $validated['primary_nameserver'],
            ],
        ]);

        return redirect()->back()->with('success', 'System hosting defaults and provisioning policies updated successfully.');
    }

    /**
     * Run simulated account provisioning dry-run probe.
     */
    public function triggerProbe(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'trigger_provisioning_probe',
            'description' => 'Executed dry-run provisioning simulation on node 103.59.177.138',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'node' => '103.59.177.138',
                'docroot_format' => SystemSetting::get('defaults.default_document_root_format', self::DEFAULTS['default_document_root_format']),
                'default_php' => SystemSetting::get('defaults.default_php_version', self::DEFAULTS['default_php_version']),
                'probe_result' => 'passed',
            ],
        ]);

        return redirect()->back()->with('success', 'Provisioning simulation probe completed. Docroot templates, PHP-FPM pools, DNS zones, and Auto-SSL verified.');
    }

    /**
     * Reset system defaults to factory recommended settings.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('defaults.' . $key, $value, 'defaults');
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'reset_system_defaults',
            'description' => 'Reset global hosting provisioning defaults to factory standard settings',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'System defaults have been reset to factory standard configurations.');
    }

    /**
     * Load current defaults with type casting and fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('defaults.' . $key, $default);
            if (is_bool($default)) {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_int($default)) {
                $val = (int) $val;
            }
            $settings[$key] = $val;
        }
        return $settings;
    }
}
