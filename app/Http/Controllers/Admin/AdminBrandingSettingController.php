<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminBrandingSettingController extends Controller
{
    /**
     * Standard recommended defaults for Branding & White-Label Settings.
     */
    private const DEFAULTS = [
        'brand_name' => 'DeepTouchHost',
        'tagline' => 'Next-Gen Cloud Hosting & Server Management',
        'logo_light_url' => '',
        'logo_dark_url' => '',
        'favicon_url' => '/favicon.ico',
        'primary_color' => '#673DE6',
        'secondary_color' => '#4F46E5',
        'admin_theme' => 'light',
        'border_style' => 'rounded-lg',
        'white_label_enabled' => true,
        'title_suffix' => '| DeepTouch Host Cloud Platform',
        'copyright_text' => '© 2026 DeepTouch IT Ltd. All rights reserved.',
        'help_url' => 'https://help.deeptouchit.com',
        'terms_url' => 'https://deeptouchit.com/terms',
        'privacy_url' => 'https://deeptouchit.com/privacy',
        'custom_css' => '',
        'custom_js' => '',
    ];

    /**
     * Display Branding & White-Label Settings Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // 4 Clean 3-Tier Metric Stats
        $stats = [
            'brand_name' => $settings['brand_name'],
            'primary_color' => $settings['primary_color'],
            'white_label_enabled' => (bool) $settings['white_label_enabled'],
            'has_custom_logo' => !empty($settings['logo_light_url']) || !empty($settings['logo_dark_url']),
            'admin_theme' => $settings['admin_theme'],
        ];

        return Inertia::render('Admin/Settings/Branding/Index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Update Branding Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'logo_light_url' => ['nullable', 'string', 'max:255'],
            'logo_dark_url' => ['nullable', 'string', 'max:255'],
            'favicon_url' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/'],
            'admin_theme' => ['required', 'string', 'in:light,dark,auto'],
            'border_style' => ['required', 'string', 'in:rounded-md,rounded-lg,rounded-xl'],
            'white_label_enabled' => ['required', 'boolean'],
            'title_suffix' => ['nullable', 'string', 'max:100'],
            'copyright_text' => ['required', 'string', 'max:255'],
            'help_url' => ['nullable', 'url', 'max:255'],
            'terms_url' => ['nullable', 'url', 'max:255'],
            'privacy_url' => ['nullable', 'url', 'max:255'],
            'custom_css' => ['nullable', 'string', 'max:50000'],
            'custom_js' => ['nullable', 'string', 'max:50000'],
            'logo_light_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'logo_dark_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon_file' => ['nullable', 'mimes:ico,png,svg', 'max:1024'],
        ]);

        // Handle file uploads if present
        if ($request->hasFile('logo_light_file')) {
            $path = $request->file('logo_light_file')->store('branding', 'public');
            $validated['logo_light_url'] = '/storage/' . $path;
        }

        if ($request->hasFile('logo_dark_file')) {
            $path = $request->file('logo_dark_file')->store('branding', 'public');
            $validated['logo_dark_url'] = '/storage/' . $path;
        }

        if ($request->hasFile('favicon_file')) {
            $path = $request->file('favicon_file')->store('branding', 'public');
            $validated['favicon_url'] = '/storage/' . $path;
        }

        unset($validated['logo_light_file'], $validated['logo_dark_file'], $validated['favicon_file']);

        foreach ($validated as $key => $value) {
            SystemSetting::set('branding.' . $key, $value, 'branding');
        }

        // Log audit event
        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'update_branding_settings',
            'description' => 'Updated brand appearance, color tokens, and white-label parameters',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Branding Console',
            'new_values' => [
                'brand_name' => $validated['brand_name'],
                'primary_color' => $validated['primary_color'],
                'white_label_enabled' => $validated['white_label_enabled'],
            ],
        ]);

        return redirect()->back()->with('success', 'Branding & visual appearance saved successfully.');
    }

    /**
     * Reset Branding Settings to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('branding.' . $key, $value, 'branding');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'reset_branding_settings',
            'description' => 'Reset branding and visual theme to factory defaults',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Branding Console',
        ]);

        return redirect()->back()->with('success', 'Branding settings reset to factory defaults.');
    }

    /**
     * Load current settings with fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('branding.' . $key, $default);

            if (is_bool($default)) {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }

            $settings[$key] = $val;
        }

        return $settings;
    }
}
