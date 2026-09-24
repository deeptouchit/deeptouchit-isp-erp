<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDomain;
use App\Models\Subscription;
use App\Services\Email\EmailSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSecurityController extends Controller
{
    protected EmailSecurityService $securityService;

    public function __construct(EmailSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Display DKIM, SPF, and DMARC Security & Deliverability Matrix.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $filter = $request->query('filter', 'all');

        $query = EmailDomain::with(['subscription.user'])->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('spf_record', 'like', "%{$s}%")
                  ->orWhere('dmarc_record', 'like', "%{$s}%")
                  ->orWhereHas('subscription.user', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $domains = $query->get();

        $dkimCount = 0;
        $spfCount = 0;
        $dmarcCount = 0;

        $domainsData = $domains->map(function ($dom) {
            $hasDkim = !empty($dom->dkim_public_key) && $dom->dkim_status === 'active';
            $hasSpf = !empty($dom->spf_record);
            $hasDmarc = !empty($dom->dmarc_record);

            $score = 25; // Base domain configuration
            if ($hasDkim) $score += 25;
            if ($hasSpf) $score += 25;
            if ($hasDmarc) $score += 25;

            return [
                'id' => $dom->id,
                'domain' => $dom->domain,
                'status' => $dom->status,
                'dkim_status' => $dom->dkim_status,
                'dkim_selector' => $dom->dkim_selector ?: 'default',
                'dkim_public_key' => $dom->dkim_public_key,
                'spf_record' => $dom->spf_record ?: "v=spf1 mx a ip4:" . (request()->server('SERVER_ADDR') ?: '127.0.0.1') . " ~all",
                'dmarc_record' => $dom->dmarc_record ?: "v=DMARC1; p=quarantine; sp=quarantine; pct=100; rua=mailto:dmarc@{$dom->domain}",
                'score' => $score,
                'subscription' => $dom->subscription,
                'created_at' => $dom->created_at?->toIso8601String(),
            ];
        });

        foreach ($domainsData as $d) {
            if (!empty($d['dkim_public_key'])) $dkimCount++;
            if (!empty($d['spf_record'])) $spfCount++;
            if (!empty($d['dmarc_record'])) $dmarcCount++;
        }

        $totalDomains = count($domainsData);
        $avgScore = $totalDomains > 0 ? round($domainsData->sum('score') / $totalDomains) : 100;

        $stats = [
            'total_domains' => $totalDomains,
            'avg_score' => $avgScore,
            'dkim_count' => $dkimCount,
            'spf_count' => $spfCount,
            'dmarc_count' => $dmarcCount,
        ];

        return Inertia::render('Admin/Email/DkimSpf', [
            'domains' => $domainsData,
            'stats' => $stats,
            'serverIp' => request()->server('SERVER_ADDR') ?: '127.0.0.1',
        ]);
    }

    /**
     * Regenerate DKIM 2048-bit key.
     */
    public function generateDkim(Request $request, EmailDomain $emailDomain)
    {
        $selector = $request->input('selector', 'default');
        $res = $this->securityService->generateDkimKeypair($emailDomain, $selector, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['dkim' => $res['error']]);
    }

    /**
     * Update SPF record.
     */
    public function updateSpf(Request $request, EmailDomain $emailDomain)
    {
        $validated = $request->validate([
            'spf_record' => 'required|string|max:500',
        ]);

        $res = $this->securityService->updateSpfRecord($emailDomain, $validated['spf_record'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update DMARC record.
     */
    public function updateDmarc(Request $request, EmailDomain $emailDomain)
    {
        $validated = $request->validate([
            'dmarc_record' => 'required|string|max:500',
        ]);

        $res = $this->securityService->updateDmarcRecord($emailDomain, $validated['dmarc_record'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Live DNS verify endpoint.
     */
    public function verifyDns(EmailDomain $emailDomain): JsonResponse
    {
        $results = $this->securityService->verifyDomainDns($emailDomain);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
