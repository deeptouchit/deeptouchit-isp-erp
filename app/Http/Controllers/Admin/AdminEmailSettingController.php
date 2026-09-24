<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminEmailSettingController extends Controller
{
    /**
     * Standard recommended defaults for Outbound Mailer & SMTP Settings.
     */
    private const DEFAULTS = [
        'mail_mailer' => 'smtp',
        'mail_host' => '127.0.0.1',
        'mail_port' => 587,
        'mail_username' => '',
        'mail_password' => '',
        'mail_encryption' => 'tls',
        'mail_from_address' => 'noreply@deeptouchit.com',
        'mail_from_name' => 'DeepTouch Host Cloud Platform',
        'mail_reply_to' => 'support@deeptouchit.com',
        'mail_timeout' => 30,
        'mail_queue_enabled' => false,
        'rate_limit_per_minute' => 60,
        'verify_ssl' => true,
        'log_emails' => true,
        'bcc_alerts' => '',
    ];

    /**
     * Display Outbound Email & SMTP Settings Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // 4 Clean 3-Tier Metric Stats
        $stats = [
            'mail_mailer' => strtoupper($settings['mail_mailer']),
            'mail_host' => $settings['mail_host'],
            'mail_port' => (int) $settings['mail_port'],
            'mail_encryption' => strtoupper($settings['mail_encryption']),
            'mail_from_address' => $settings['mail_from_address'],
            'mail_from_name' => $settings['mail_from_name'],
            'is_authenticated' => !empty($settings['mail_username']),
            'mail_queue_enabled' => (bool) $settings['mail_queue_enabled'],
            'rate_limit_per_minute' => (int) $settings['rate_limit_per_minute'],
            'verify_ssl' => (bool) $settings['verify_ssl'],
        ];

        return Inertia::render('Admin/Settings/Email/Index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Update Email / SMTP Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'mail_mailer' => ['required', 'string', Rule::in(['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log'])],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', 'string', Rule::in(['tls', 'ssl', 'starttls', 'none'])],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:100'],
            'mail_reply_to' => ['nullable', 'email', 'max:255'],
            'mail_timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'mail_queue_enabled' => ['required', 'boolean'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'verify_ssl' => ['required', 'boolean'],
            'log_emails' => ['required', 'boolean'],
            'bcc_alerts' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set('email.' . $key, $value, 'email');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'update_email_settings',
            'description' => "Updated mail delivery configuration (Driver: {$validated['mail_mailer']}, Host: {$validated['mail_host']}:{$validated['mail_port']})",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Email Settings',
            'new_values' => [
                'mail_mailer' => $validated['mail_mailer'],
                'mail_host' => $validated['mail_host'],
                'mail_port' => $validated['mail_port'],
                'mail_encryption' => $validated['mail_encryption'],
                'mail_from_address' => $validated['mail_from_address'],
            ],
        ]);

        return redirect()->back()->with('success', 'Email & SMTP transport settings saved successfully.');
    }

    /**
     * Send diagnostic test email.
     */
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $settings = $this->loadSettings();
        $mailer = $settings['mail_mailer'];
        $recipient = $validated['recipient_email'];

        // If log mailer, test succeeds instantly
        if ($mailer === 'log') {
            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'send_test_email',
                'description' => "Logged diagnostic test email for {$recipient}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'Email Settings',
            ]);

            return redirect()->back()->with('success', "Test email recorded to local system log for {$recipient} (Log Driver Active).");
        }

        // Apply dynamic runtime configuration for the test
        config([
            'mail.default' => $mailer,
            "mail.mailers.{$mailer}.host" => $settings['mail_host'],
            "mail.mailers.{$mailer}.port" => (int) $settings['mail_port'],
            "mail.mailers.{$mailer}.encryption" => $settings['mail_encryption'] === 'none' ? null : $settings['mail_encryption'],
            "mail.mailers.{$mailer}.username" => $settings['mail_username'] ?: null,
            "mail.mailers.{$mailer}.password" => $settings['mail_password'] ?: null,
            "mail.mailers.{$mailer}.timeout" => (int) ($settings['mail_timeout'] ?: 30),
            'mail.from.address' => $settings['mail_from_address'],
            'mail.from.name' => $settings['mail_from_name'],
        ]);

        try {
            $fromName = $settings['mail_from_name'];
            $fromEmail = $settings['mail_from_address'];
            $serverIp = '103.59.177.138';
            $sentAt = now()->toDateTimeString();

            Mail::raw("DeepTouchHost Mail Relay Diagnostic Test\n\nThis is a verified test email sent from {$fromName} on server {$serverIp}.\nTimestamp: {$sentAt}\nTransport: {$mailer} (Host: {$settings['mail_host']}, Port: {$settings['mail_port']}, Encryption: {$settings['mail_encryption']})\n\nIf you received this message, your outbound email delivery configuration is fully operational.", function ($message) use ($recipient, $fromEmail, $fromName) {
                $message->to($recipient)
                    ->subject("DeepTouchHost SMTP Test Dispatch - " . now()->format('Y-m-d H:i:s'))
                    ->from($fromEmail, $fromName);
            });

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'send_test_email',
                'description' => "Dispatched live test email to {$recipient} via {$mailer}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'Email Settings',
            ]);

            return redirect()->back()->with('success', "Test email successfully dispatched to {$recipient}. Please verify your inbox.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors([
                'test_email' => 'Mail delivery failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset Email Settings to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('email.' . $key, $value, 'email');
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'reset_email_settings',
            'description' => 'Reset outbound mail and SMTP transport to factory defaults',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Email Settings',
        ]);

        return redirect()->back()->with('success', 'Email & SMTP transport settings reset to factory defaults.');
    }

    /**
     * Load current settings with fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('email.' . $key, $default);

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
