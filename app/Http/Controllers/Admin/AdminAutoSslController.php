<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsZone;
use App\Models\SslCertificate;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Security\SslService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAutoSslController extends Controller
{
    public function __construct(
        protected SslService $sslService
    ) {}

    /**
     * Display AutoSSL Automation & Certificate Lifecycle Manager.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status']);
        $data = $this->sslService->getAutoSslOverview($filters);

        $hostedDomains = DnsZone::select('id', 'domain')->orderBy('domain')->get();
        $subscriptions = Subscription::select('id', 'domain')->orderBy('domain')->get();

        return Inertia::render('Admin/Automation/AutoSSL', [
            'stats' => $data['stats'],
            'certificates' => $data['certificates'],
            'hostedDomains' => $hostedDomains,
            'subscriptions' => $subscriptions,
            'filters' => $filters,
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for AutoSSL.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status']);
        $data = $this->sslService->getAutoSslOverview($filters);

        return response()->json($data);
    }

    /**
     * Execute immediate AutoSSL renewal sweep for certificates expiring within 30 days.
     */
    public function runAutoSslSweep(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->sslService->runAutoSslSweep(auth()->id());

        return response()->json($res);
    }

    /**
     * Issue Let's Encrypt AutoSSL certificate.
     */
    public function issue(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'san_domains' => 'nullable|array',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'force_https' => 'nullable|boolean',
            'hsts_enabled' => 'nullable|boolean',
        ]);

        $res = $this->sslService->issueLetsEncrypt($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle AutoSSL auto-renewal on/off for a domain.
     */
    public function toggleAutoRenew(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $cert = SslCertificate::findOrFail($id);
        $cert->update(['auto_renew' => !$cert->auto_renew]);
        $state = $cert->auto_renew ? 'Enabled' : 'Disabled';

        return redirect()->back()->with('success', "AutoSSL auto-renewal {$state} for `{$cert->domain}`.");
    }

    /**
     * Toggle Force HTTPS redirect on/off for a domain.
     */
    public function toggleForceHttps(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $cert = SslCertificate::findOrFail($id);
        $cert->update(['force_https' => !$cert->force_https]);
        $state = $cert->force_https ? 'Enforced' : 'Disabled';

        return redirect()->back()->with('success', "Force HTTPS 301 redirect {$state} for `{$cert->domain}`.");
    }

    /**
     * Renew certificate on-demand immediately.
     */
    public function renewNow(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $cert = SslCertificate::findOrFail($id);
        $res = $this->sslService->renewCertificate($cert, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
