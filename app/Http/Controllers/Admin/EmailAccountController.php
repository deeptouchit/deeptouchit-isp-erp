<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\Subscription;
use App\Services\Email\EmailAccountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailAccountController extends Controller
{
    protected EmailAccountService $accountService;

    public function __construct(EmailAccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Display all email accounts / mailboxes.
     */
    public function index(Request $request): Response
    {
        $domainFilter = $request->query('domain');
        $statusFilter = $request->query('status');
        $search = $request->query('search');

        $query = EmailAccount::with(['emailDomain', 'subscription.user'])->latest();

        if ($domainFilter && $domainFilter !== 'all') {
            $query->whereHas('emailDomain', function ($q) use ($domainFilter) {
                $q->where('domain', $domainFilter);
            });
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('forward_to', 'like', "%{$s}%")
                  ->orWhereHas('subscription.user', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $accounts = $query->get();

        $totalQuotaMb = 0;
        $totalUsedMb = 0;
        $forwardersCount = 0;

        $accountsData = $accounts->map(function ($acc) use (&$totalQuotaMb, &$totalUsedMb, &$forwardersCount) {
            $totalQuotaMb += $acc->quota_mb;
            $totalUsedMb += $acc->used_quota_mb;
            if (!empty($acc->forward_to)) {
                $forwardersCount++;
            }

            return [
                'id' => $acc->id,
                'email' => $acc->email,
                'domain' => $acc->emailDomain?->domain,
                'email_domain_id' => $acc->email_domain_id,
                'quota_mb' => $acc->quota_mb,
                'used_quota_mb' => $acc->used_quota_mb,
                'used_percent' => $acc->quota_mb > 0 ? round(($acc->used_quota_mb / $acc->quota_mb) * 100, 1) : 0,
                'forward_to' => $acc->forward_to,
                'auto_responder' => $acc->auto_responder,
                'status' => $acc->status,
                'subscription' => $acc->subscription,
                'created_at' => $acc->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total_accounts' => $accounts->count(),
            'total_quota_gb' => round($totalQuotaMb / 1024, 1),
            'used_storage_mb' => $totalUsedMb,
            'forwarders_count' => $forwardersCount,
        ];

        $domains = EmailDomain::where('status', 'active')->get(['id', 'domain']);
        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Email/Accounts', [
            'accounts' => $accountsData,
            'stats' => $stats,
            'domains' => $domains,
            'subscriptions' => $subscriptions,
            'currentDomain' => $domainFilter ?: 'all',
            'currentStatus' => $statusFilter ?: 'all',
        ]);
    }

    /**
     * Store / Provision new Mailbox.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_domain_id' => 'required|exists:email_domains,id',
            'username' => 'required|string|max:64|regex:/^[a-zA-Z0-9._-]+$/',
            'password' => 'required|string|min:8',
            'quota_mb' => 'nullable|integer|min:100|max:1048576',
            'forward_to' => 'nullable|email|max:191',
        ]);

        $res = $this->accountService->createAccount($validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['username' => $res['error']]);
    }

    /**
     * Change mailbox password.
     */
    public function changePassword(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'password' => 'nullable|string|min:8',
            'new_password' => 'nullable|string|min:8',
        ], [
            'password.min' => 'Password must be at least 8 characters long.',
            'new_password.min' => 'Password must be at least 8 characters long.',
        ]);

        $newPassword = $validated['password'] ?? $validated['new_password'] ?? null;
        if (empty($newPassword)) {
            return redirect()->back()->withErrors(['password' => 'Password field is required.']);
        }

        $res = $this->accountService->changePassword($emailAccount, $newPassword, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update mailbox quota.
     */
    public function updateQuota(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'quota_mb' => 'required|integer|min:100|max:1048576',
        ]);

        $res = $this->accountService->updateQuota($emailAccount, $validated['quota_mb'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update Forwarding and Autoresponder.
     */
    public function updateForwarding(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'forward_to' => 'nullable|email|max:191',
            'auto_responder' => 'nullable|array',
            'auto_responder.is_enabled' => 'nullable|boolean',
            'auto_responder.subject' => 'nullable|string|max:191',
            'auto_responder.body' => 'nullable|string|max:5000',
        ]);

        $res = $this->accountService->updateForwarding($emailAccount, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle status (active / suspended).
     */
    public function toggleStatus(EmailAccount $emailAccount)
    {
        $res = $this->accountService->toggleStatus($emailAccount, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Drop / Delete mailbox.
     */
    public function destroy(EmailAccount $emailAccount)
    {
        $res = $this->accountService->deleteAccount($emailAccount, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
