<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Email\EmailRelayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailRelayController extends Controller
{
    protected EmailRelayService $relayService;

    public function __construct(EmailRelayService $relayService)
    {
        $this->relayService = $relayService;
    }

    /**
     * Display Outbound SMTP Relay / DeepTouchHost Management.
     */
    public function index(): Response
    {
        $setting = $this->relayService->getSettings();

        $providers = [
            [
                'id' => 'resend',
                'name' => 'Resend',
                'host' => 'smtp.resend.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Free 3,000 emails/month (100/day). Fixed username: resend, password is API Key.',
                'badge' => 'Recommended Free',
            ],
            [
                'id' => 'brevo',
                'name' => 'Brevo (Sendinblue)',
                'host' => 'smtp-relay.brevo.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Free 300 emails/day, 100% inbox placement into Gmail/Yahoo.',
                'badge' => 'Free Tier',
            ],
            [
                'id' => 'sendgrid',
                'name' => 'SendGrid (Twilio)',
                'host' => 'smtp.sendgrid.net',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Enterprise email relay with real-time deliverability analytics.',
                'badge' => 'Enterprise',
            ],
            [
                'id' => 'mailgun',
                'name' => 'Mailgun',
                'host' => 'smtp.mailgun.org',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Powerful developer-first transactional email relay.',
                'badge' => 'Developer',
            ],
            [
                'id' => 'ses',
                'name' => 'Amazon SES',
                'host' => 'email-smtp.us-east-1.amazonaws.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'High volume, ultra low cost ($0.10 per 10,000 emails).',
                'badge' => 'Cost Effective',
            ],
            [
                'id' => 'gmail',
                'name' => 'Google Workspace / Gmail',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Direct authenticated relay via Gmail SMTP App Password.',
                'badge' => 'Google Relay',
            ],
            [
                'id' => 'custom',
                'name' => 'Custom DeepTouchHost Relay',
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Connect to any external mail relay, MailChannels or ISP gateway.',
                'badge' => 'Custom',
            ],
        ];

        return Inertia::render('Admin/Email/Relay', [
            'setting' => $setting,
            'providers' => $providers,
            'stats' => [
                'is_enabled' => $setting->is_enabled,
                'mode' => $setting->mode,
                'provider_name' => strtoupper($setting->provider),
                'last_test_status' => $setting->last_test_status ?: 'Never Tested',
                'last_test_at' => $setting->last_test_at ? $setting->last_test_at->diffForHumans() : 'Never',
            ],
        ]);
    }

    /**
     * Update Outbound SMTP Relay Settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'mode' => 'required|string|in:direct,relay',
            'provider' => 'required|string|max:50',
            'host' => 'nullable|string|max:191',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'required|string|in:tls,ssl,none',
            'username' => 'nullable|string|max:191',
            'password' => 'nullable|string',
            'sender_domain' => 'nullable|string|max:191',
        ]);

        $res = $this->relayService->updateSettings($validated);

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Trigger live delivery diagnostic test.
     */
    public function testRelay(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $res = $this->relayService->testRelay($validated['test_email']);

        return redirect()->back()->with('success', 'Diagnostic test probe executed successfully.');
    }
}
