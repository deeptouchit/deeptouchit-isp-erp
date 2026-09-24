<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDomain;
use App\Models\Subscription;
use App\Services\Email\EmailDomainService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailDomainController extends Controller
{
    protected EmailDomainService $domainService;

    public function __construct(EmailDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * Display all email domains, mailbox stats, and DNS delivery health.
     */
    public function index(Request $request): Response
    {
        $domains = EmailDomain::with(['subscription.user', 'accounts'])
            ->latest()
            ->get();

        $totalAccounts = 0;
        $activeDkimCount = 0;
        $totalQuotaMb = 0;

        $domainsData = $domains->map(function ($dom) use (&$totalAccounts, &$activeDkimCount, &$totalQuotaMb) {
            $accCount = $dom->accounts->count();
            $totalAccounts += $accCount;
            if ($dom->dkim_status === 'active') {
                $activeDkimCount++;
            }
            $totalQuotaMb += $dom->max_quota_mb;

            return [
                'id' => $dom->id,
                'domain' => $dom->domain,
                'status' => $dom->status,
                'is_catchall_enabled' => $dom->is_catchall_enabled,
                'catchall_destination' => $dom->catchall_destination,
                'dkim_status' => $dom->dkim_status,
                'dkim_selector' => $dom->dkim_selector,
                'dkim_public_key' => $dom->dkim_public_key,
                'spf_record' => $dom->spf_record,
                'dmarc_record' => $dom->dmarc_record,
                'max_accounts' => $dom->max_accounts,
                'max_quota_mb' => $dom->max_quota_mb,
                'accounts_count' => $accCount,
                'subscription' => $dom->subscription,
                'created_at' => $dom->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total_domains' => $domains->count(),
            'total_accounts' => $totalAccounts,
            'dkim_verified_percent' => $domains->count() > 0 ? round(($activeDkimCount / $domains->count()) * 100) : 100,
            'total_quota_gb' => round($totalQuotaMb / 1024, 1),
        ];

        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Email/Domains', [
            'domains' => $domainsData,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Store / Provision new Email Domain.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'domain' => 'required|string|max:191|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/|unique:email_domains,domain',
            'max_accounts' => 'nullable|integer|min:1|max:500',
            'max_quota_mb' => 'nullable|integer|min:100|max:1048576',
        ]);

        $res = $this->domainService->createDomain($validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['domain' => $res['error']]);
    }

    /**
     * Verify live DNS resolution.
     */
    public function verifyDns(EmailDomain $emailDomain)
    {
        $dnsResults = $this->domainService->verifyDnsRecords($emailDomain);

        return response()->json([
            'success' => true,
            'results' => $dnsResults,
        ]);
    }

    /**
     * Re-generate 2048-bit DKIM Keypair.
     */
    public function generateDkim(EmailDomain $emailDomain)
    {
        $dkim = $this->domainService->generateDkimKeyPair($emailDomain->domain, $emailDomain->dkim_selector ?: 'default');

        if ($dkim['success']) {
            $emailDomain->update([
                'dkim_status' => 'active',
                'dkim_private_key' => $dkim['private_key'],
                'dkim_public_key' => $dkim['public_key'],
            ]);

            return redirect()->back()->with('success', "Fresh 2048-bit DKIM key generated for {$emailDomain->domain}.");
        }

        return redirect()->back()->withErrors(['error' => 'Failed to generate DKIM key: ' . ($dkim['error'] ?? 'Unknown error')]);
    }

    /**
     * Update Catch-All Configuration.
     */
    public function updateCatchall(Request $request, EmailDomain $emailDomain)
    {
        $validated = $request->validate([
            'is_catchall_enabled' => 'required|boolean',
            'catchall_destination' => 'nullable|required_if:is_catchall_enabled,true|email|max:191',
        ]);

        $res = $this->domainService->updateCatchall(
            $emailDomain,
            $validated['is_catchall_enabled'],
            $validated['catchall_destination'] ?? null,
            auth()->id()
        );

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle Domain Status (active / suspended).
     */
    public function toggleStatus(EmailDomain $emailDomain)
    {
        $res = $this->domainService->toggleStatus($emailDomain, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Drop / Delete Email Domain.
     */
    public function destroy(EmailDomain $emailDomain)
    {
        $res = $this->domainService->deleteDomain($emailDomain, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
