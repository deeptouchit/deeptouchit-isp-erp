<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminGeneralSettingController extends Controller
{
    /**
     * Standard recommended defaults for General System Settings.
     */
    private const DEFAULTS = [
        'app_name' => 'DeepTouch Host Cloud Platform',
        'company_name' => 'DeepTouch IT Ltd.',
        'app_url' => 'https://deeptouchit.com',
        'admin_email' => 'admin@deeptouchit.com',
        'support_email' => 'support@deeptouchit.com',
        'server_hostname' => 'deeptouchit.com',
        'server_ip' => '103.59.177.138',
        'admin_port' => 443,
        'force_https' => true,
        'maintenance_mode' => false,
        'maintenance_message' => 'DeepTouch Host Cloud Platform is currently undergoing scheduled infrastructure maintenance. Normal service will resume shortly.',
        'maintenance_ip_allowlist' => '103.59.177.138, 127.0.0.1',
        'allow_registration' => true,
        'require_email_verification' => true,
        'session_timeout_minutes' => 60,
        'max_concurrent_sessions' => 3,
    ];

    /**
     * Display General System Settings Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // 4 Clean 3-Tier Metric Stats calculated live from Linux server and environment
        $stats = [
            'app_name' => $settings['app_name'],
            'app_url' => $settings['app_url'],
            'server_ip' => $settings['server_ip'],
            'server_hostname' => $settings['server_hostname'],
            'maintenance_mode' => (bool) $settings['maintenance_mode'],
            'laravel_version' => app()->version(),
            'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
            'timezone' => config('app.timezone', 'UTC'),
            'current_time' => now()->format('H:i:s T (d M Y)'),
        ];

        return Inertia::render('Admin/Settings/General/Index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Update General System Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'server_hostname' => ['required', 'string', 'max:255'],
            'server_ip' => ['required', 'ip'],
            'admin_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'force_https' => ['required', 'boolean'],
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'maintenance_ip_allowlist' => ['nullable', 'string', 'max:500'],
            'allow_registration' => ['required', 'boolean'],
            'require_email_verification' => ['required', 'boolean'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'max_concurrent_sessions' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set('general.' . $key, $value, 'general');
        }

        // Log audit event
        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'update_general_settings',
            'description' => 'Updated core general system configurations and operational parameters',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'System Settings Console',
            'new_values' => [
                'app_name' => $validated['app_name'],
                'maintenance_mode' => $validated['maintenance_mode'],
                'server_hostname' => $validated['server_hostname'],
                'server_ip' => $validated['server_ip'],
            ],
        ]);

        return redirect()->back()->with('success', 'General system settings saved successfully.');
    }

    /**
     * Reset General System Settings to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('general.' . $key, $value, 'general');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'reset_general_settings',
            'description' => 'Reset general system settings to factory defaults',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'System Settings Console',
        ]);

        return redirect()->back()->with('success', 'General system settings reset to factory defaults.');
    }

    /**
     * Load current settings with fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('general.' . $key, $default);

            // Type casts
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
