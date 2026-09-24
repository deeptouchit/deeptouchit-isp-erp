<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\Subscription;
use App\Services\Email\EmailAccountService;
use App\Services\EmailManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    /**
     * Display client business email accounts and connection setup.
     */
    public function index(): Response
    {
        $userId = auth()->id();

        // 1. Fetch active subscriptions
        $subscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['plan'])
            ->get();

        // 2. Fetch all email accounts owned by this client
        $emails = EmailAccount::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['subscription.plan', 'emailDomain'])
        ->latest()
        ->get()
        ->map(function ($acc) {
            $quotaMb = (int) ($acc->quota_mb ?: 1024);
            $usedMb = (int) ($acc->used_quota_mb ?: 0);
            $pct = $quotaMb > 0 ? min(100, round(($usedMb / $quotaMb) * 100, 1)) : 0;

            return [
                'id' => $acc->id,
                'email' => $acc->email,
                'domain' => $acc->subscription ? $acc->subscription->domain : ($acc->emailDomain ? $acc->emailDomain->domain : 'Unknown'),
                'subscription_id' => $acc->subscription_id,
                'quota_mb' => $quotaMb,
                'used_quota_mb' => $usedMb,
                'usage_percent' => $pct,
                'status' => $acc->status ?? 'active',
                'forward_to' => $acc->forward_to,
                'created_at' => $acc->created_at ? $acc->created_at->format('M d, Y') : null,
                'created_at_human' => $acc->created_at ? $acc->created_at->diffForHumans() : null,
            ];
        });

        // 3. Subscription quota calculations
        $totalAllowed = (int) $subscriptions->sum(function ($sub) {
            return $sub->plan ? (int) $sub->plan->max_email_accounts : 10;
        });

        $totalUsed = $emails->count();
        $remaining = max(0, $totalAllowed - $totalUsed);
        $usagePercentage = $totalAllowed > 0 ? min(100, round(($totalUsed / $totalAllowed) * 100)) : 0;
        $canAdd = $totalAllowed === 0 || $totalUsed < $totalAllowed;

        // Primary client domain for connection settings
        $primaryDomain = $subscriptions->first()?->domain ?? 'yourdomain.com';

        $connectionInfo = [
            'imap_host' => 'mail.' . $primaryDomain,
            'imap_port' => 993,
            'imap_encryption' => 'SSL / TLS',
            'smtp_host' => 'mail.' . $primaryDomain,
            'smtp_port' => 465,
            'smtp_encryption' => 'SSL / TLS',
            'pop3_port' => 995,
            'webmail_url' => route('webmail.login'),
            'server_hostname' => gethostname() ?: 'deeptouchit.com',
        ];

        return Inertia::render('Client/Emails/Index', [
            'emails' => $emails,
            'subscriptions' => $subscriptions->map(fn($s) => [
                'id' => $s->id,
                'domain' => $s->domain,
                'plan_name' => $s->plan?->name ?? 'Standard Plan',
                'max_emails' => $s->plan?->max_email_accounts ?? 10,
            ]),
            'quota' => [
                'total_allowed' => $totalAllowed,
                'total_used' => $totalUsed,
                'remaining' => $remaining,
                'usage_percentage' => $usagePercentage,
                'can_add' => $canAdd,
            ],
            'connectionInfo' => $connectionInfo,
        ]);
    }

    /**
     * Create / Provision a new Business Mailbox.
     */
    public function store(Request $request, EmailAccountService $accountService, EmailManager $emailManager)
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'username' => 'required|string|max:64|regex:/^[a-zA-Z0-9._-]+$/',
            'password' => 'required|string|min:8',
            'quota_mb' => 'nullable|integer|min:100|max:10240',
        ], [
            'username.regex' => 'Username may only contain letters, numbers, dots, dashes, and underscores.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        $subscription = Subscription::with('plan')->findOrFail($validated['subscription_id']);
        if ($subscription->user_id !== $userId && auth()->user()->role !== 'admin') {
            abort(403);
        }

        // Check Quota Limit
        $maxAllowed = $subscription->plan ? (int) $subscription->plan->max_email_accounts : 10;
        $currentCount = EmailAccount::where('subscription_id', $subscription->id)->count();
        if ($maxAllowed > 0 && $currentCount >= $maxAllowed) {
            return back()->withErrors([
                'username' => "Mailbox quota reached. Your plan allows maximum {$maxAllowed} email accounts."
            ]);
        }

        $cleanUsername = strtolower(trim($validated['username']));
        $fullEmail = $cleanUsername . '@' . strtolower($subscription->domain);

        if (EmailAccount::where('email', $fullEmail)->exists()) {
            return back()->withErrors(['username' => "Mailbox `{$fullEmail}` already exists."]);
        }

        // Ensure EmailDomain model exists
        $emailDomain = EmailDomain::firstOrCreate(
            ['domain' => strtolower($subscription->domain)],
            [
                'subscription_id' => $subscription->id,
                'status' => 'active',
                'max_accounts' => $maxAllowed ?: 50,
                'max_quota_mb' => 10240,
            ]
        );

        $quotaMb = (int) ($validated['quota_mb'] ?? 1024);

        // Provision Mailbox
        $account = EmailAccount::create([
            'subscription_id' => $subscription->id,
            'email_domain_id' => $emailDomain->id,
            'email' => $fullEmail,
            'password' => Hash::make($validated['password']),
            'quota_mb' => $quotaMb,
            'used_quota_mb' => 0,
            'status' => 'active',
        ]);

        try {
            $emailManager->createMailbox($fullEmail, $validated['password'], $quotaMb);
        } catch (\Throwable $e) {}

        return redirect()->route('email-accounts.index')->with('success', "Business mailbox `{$fullEmail}` provisioned successfully.");
    }

    /**
     * Change Mailbox Password.
     */
    public function changePassword(Request $request, EmailAccount $emailAccount, EmailAccountService $accountService)
    {
        if ($emailAccount->subscription && $emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $emailAccount->update([
            'password' => Hash::make($validated['password']),
        ]);

        try {
            $accountService->changePassword($emailAccount, $validated['password']);
        } catch (\Throwable $e) {}

        return redirect()->route('email-accounts.index')->with('success', "Password for `{$emailAccount->email}` updated successfully.");
    }

    /**
     * Update Mailbox Storage Quota.
     */
    public function updateQuota(Request $request, EmailAccount $emailAccount, EmailAccountService $accountService)
    {
        if ($emailAccount->subscription && $emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'quota_mb' => 'required|integer|min:100|max:10240',
        ]);

        $emailAccount->update([
            'quota_mb' => $validated['quota_mb'],
        ]);

        try {
            $accountService->updateQuota($emailAccount, $validated['quota_mb']);
        } catch (\Throwable $e) {}

        return redirect()->route('email-accounts.index')->with('success', "Storage quota for `{$emailAccount->email}` updated to {$validated['quota_mb']} MB.");
    }

    /**
     * 1-Click Webmail Auto-Login.
     */
    public function webmailSso(EmailAccount $emailAccount)
    {
        if ($emailAccount->subscription && $emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        Session::put('webmail_account_id', $emailAccount->id);
        Session::put('webmail_email', $emailAccount->email);

        return redirect()->route('webmail.inbox');
    }

    /**
     * Delete Mailbox.
     */
    public function destroy(EmailAccount $emailAccount, EmailAccountService $accountService, EmailManager $emailManager)
    {
        if ($emailAccount->subscription && $emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $email = $emailAccount->email;

        try {
            $accountService->deleteAccount($emailAccount);
        } catch (\Throwable $e) {
            try {
                $emailManager->deleteMailbox($email);
            } catch (\Throwable $e2) {}
        }

        $emailAccount->delete();

        return redirect()->route('email-accounts.index')->with('success', "Mailbox `{$email}` deleted successfully.");
    }

    /**
     * Show detail / redirect back to index.
     */
    public function show(?EmailAccount $emailAccount = null)
    {
        return redirect()->route('email-accounts.index');
    }

    /**
     * Toggle Mailbox Status (Active / Suspended)
     */
    public function toggleStatus(EmailAccount $emailAccount)
    {
        if ($emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $newStatus = ($emailAccount->status === 'active') ? 'suspended' : 'active';
        $emailAccount->update(['status' => $newStatus]);

        return redirect()->route('email-accounts.index')->with('success', "Mailbox `{$emailAccount->email}` is now {$newStatus}.");
    }

    /**
     * Set or Remove Email Forwarding
     */
    public function updateForwarding(Request $request, EmailAccount $emailAccount)
    {
        if ($emailAccount->subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'forward_to' => 'nullable|email|max:120',
        ]);

        $emailAccount->update([
            'forward_to' => $validated['forward_to'] ?: null,
        ]);

        return redirect()->route('email-accounts.index')->with('success', "Forwarding for `{$emailAccount->email}` updated successfully.");
    }
}

