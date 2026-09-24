<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Mail\SystemTestMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class OwnerMailSettingController extends Controller
{
    /**
     * Display the domain mail server and SMTP configuration interface.
     */
    public function index()
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();

        return view('owner.mail-settings.index', compact('settings'));
    }

    /**
     * Update domain SMTP server credentials and sender parameters.
     */
    public function update(Request $request)
    {
        $inputs = $request->except(['_token']);

        $checkboxKeys = ['mail_verify_peer'];
        foreach ($checkboxKeys as $key) {
            $inputs[$key] = $request->has($key) ? '1' : '0';
        }

        foreach ($inputs as $key => $value) {
            Setting::set($key, (string)$value, 'smtp', null);
        }

        return back()->with('success', 'Domain mail & SMTP configuration saved successfully.');
    }

    /**
     * Send a real-time diagnostic test email using the configured domain credentials.
     */
    public function test(Request $request)
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();

        $startTime = microtime(true);
        $mailer = $settings['mail_mailer'] ?? 'smtp';
        $host = $settings['mail_host'] ?? 'mail.somitysoft.com';
        $port = (int)($settings['mail_port'] ?? 465);
        $encryption = $settings['mail_encryption'] ?? 'ssl';
        $username = $settings['mail_username'] ?? 'billing@somitysoft.com';
        $password = $settings['mail_password'] ?? '';
        $fromAddress = $settings['mail_from_address'] ?? $settings['billing_email'] ?? 'billing@somitysoft.com';
        $fromName = $settings['mail_from_name'] ?? 'SomitySoft Billing';

        // When connecting from this same server to somitysoft.com/mail.somitysoft.com,
        // use local loopback 'deeptouchcloud' to bypass router hairpin NAT timeout and match local SSL cert
        $connectHost = in_array(strtolower(trim($host)), ['mail.somitysoft.com', 'somitysoft.com', 'localhost', '127.0.0.1', '103.59.177.138', '103.59.177.139'])
            ? 'deeptouchcloud'
            : $host;

        // Override Laravel Mail configuration dynamically
        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.host', $connectHost);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.username', !empty($username) ? $username : null);
        Config::set('mail.mailers.smtp.password', !empty($password) ? $password : null);
        Config::set('mail.mailers.smtp.timeout', (int)($settings['mail_timeout'] ?? 15));
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        $details = [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'from_address' => $fromAddress,
            'recipient' => $request->test_email,
            'latency' => round((microtime(true) - $startTime) * 1000) . ' ms',
        ];

        try {
            Mail::to($request->test_email)->send(new SystemTestMail($details));
            $latency = round((microtime(true) - $startTime) * 1000);

            return back()->with('success', "Test email successfully delivered to '{$request->test_email}' in {$latency}ms via {$host}:{$port} ({$encryption}).");
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            return back()->with('error', "SMTP Dispatch Failed ({$latency}ms): {$errorMessage}");
        }
    }
}
