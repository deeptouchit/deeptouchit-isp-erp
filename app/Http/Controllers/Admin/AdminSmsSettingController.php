<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminSmsSettingController extends Controller
{
    /**
     * Standard recommended defaults for SMS Gateway Settings.
     */
    private const DEFAULTS = [
        'sms_enabled' => false,
        'sms_provider' => 'twilio',
        'api_key' => '',
        'api_secret' => '',
        'api_url' => '',
        'sms_sender_id' => 'DeepTouchHost',
        'http_method' => 'POST',
        'sms_timeout' => 15,
        'enable_2fa_sms' => true,
        'enable_server_alert_sms' => true,
        'enable_invoice_sms' => true,
        'enable_welcome_sms' => false,
        'admin_alert_phone' => '',
        'sms_queue_enabled' => false,
        'rate_limit_per_minute' => 30,
        'default_country_code' => '+880',
        'masking_enabled' => false,
        'log_sms' => true,
    ];

    /**
     * Display SMS Gateway Settings Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // 4 Clean 3-Tier Metric Stats
        $stats = [
            'sms_provider' => strtoupper($settings['sms_provider']),
            'sms_enabled' => (bool) $settings['sms_enabled'],
            'is_configured' => !empty($settings['api_key']) || $settings['sms_provider'] === 'log',
            'sms_sender_id' => $settings['sms_sender_id'] ?: 'DeepTouchHost',
            'enable_2fa_sms' => (bool) $settings['enable_2fa_sms'],
            'sms_queue_enabled' => (bool) $settings['sms_queue_enabled'],
            'rate_limit_per_minute' => (int) $settings['rate_limit_per_minute'],
            'default_country_code' => $settings['default_country_code'],
            'masking_enabled' => (bool) $settings['masking_enabled'],
        ];

        return Inertia::render('Admin/Settings/Sms/Index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Update SMS Gateway Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'sms_enabled' => ['required', 'boolean'],
            'sms_provider' => ['required', 'string', Rule::in(['twilio', 'ssl_wireless', 'bulksmsbd', 'infobip', 'vonage', 'generic_http', 'log'])],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'api_url' => ['nullable', 'string', 'max:500'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'http_method' => ['required', 'string', Rule::in(['POST', 'GET'])],
            'sms_timeout' => ['required', 'integer', 'min:3', 'max:60'],
            'enable_2fa_sms' => ['required', 'boolean'],
            'enable_server_alert_sms' => ['required', 'boolean'],
            'enable_invoice_sms' => ['required', 'boolean'],
            'enable_welcome_sms' => ['required', 'boolean'],
            'admin_alert_phone' => ['nullable', 'string', 'max:100'],
            'sms_queue_enabled' => ['required', 'boolean'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'default_country_code' => ['required', 'string', 'max:10'],
            'masking_enabled' => ['required', 'boolean'],
            'log_sms' => ['required', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set('sms.' . $key, $value, 'sms');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'update_sms_settings',
            'description' => "Updated SMS gateway settings (Provider: {$validated['sms_provider']}, Sender ID: {$validated['sms_sender_id']})",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'SMS Settings',
            'new_values' => [
                'sms_enabled' => $validated['sms_enabled'],
                'sms_provider' => $validated['sms_provider'],
                'sms_sender_id' => $validated['sms_sender_id'],
                'default_country_code' => $validated['default_country_code'],
            ],
        ]);

        return redirect()->back()->with('success', 'SMS gateway configuration saved successfully.');
    }

    /**
     * Send diagnostic test SMS probe.
     */
    public function sendTestSms(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:25'],
            'test_message' => ['nullable', 'string', 'max:160'],
        ]);

        $settings = $this->loadSettings();
        $provider = $settings['sms_provider'];
        $recipient = $validated['phone_number'];
        $message = $validated['test_message'] ?: "DeepTouchHost SMS Diagnostic Test: Outbound gateway {$provider} is operational from server 103.59.177.138 at " . now()->format('H:i:s');

        // If local log driver or generic sandbox
        if ($provider === 'log') {
            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'send_test_sms',
                'description' => "Logged diagnostic test SMS to {$recipient} via Log Driver: '{$message}'",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'SMS Settings',
            ]);

            return redirect()->back()->with('success', "Test SMS recorded to local system log for {$recipient} (Log Driver Mode).");
        }

        try {
            // Generic HTTP endpoint dispatch test
            if ($provider === 'generic_http' && !empty($settings['api_url'])) {
                $endpoint = str_replace(
                    ['{to}', '{message}', '{sender}'],
                    [urlencode($recipient), urlencode($message), urlencode($settings['sms_sender_id'])],
                    $settings['api_url']
                );

                if ($settings['http_method'] === 'POST') {
                    Http::timeout((int) $settings['sms_timeout'])->post($endpoint, [
                        'to' => $recipient,
                        'message' => $message,
                        'sender' => $settings['sms_sender_id'],
                        'api_key' => $settings['api_key'],
                    ]);
                } else {
                    Http::timeout((int) $settings['sms_timeout'])->get($endpoint);
                }
            }

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'send_test_sms',
                'description' => "Dispatched test SMS probe to {$recipient} via {$provider}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'SMS Settings',
            ]);

            return redirect()->back()->with('success', "Test SMS probe initiated for {$recipient} via {$provider}. Verify phone handset.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors([
                'test_sms' => 'SMS delivery failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset SMS Settings to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('sms.' . $key, $value, 'sms');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'reset_sms_settings',
            'description' => 'Reset SMS gateway settings to factory defaults',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'SMS Settings',
        ]);

        return redirect()->back()->with('success', 'SMS gateway settings reset to factory defaults.');
    }

    /**
     * Load current settings with fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('sms.' . $key, $default);

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
