<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsZone;
use App\Models\SslCertificate;
use App\Models\Subscription;
use App\Services\Security\SslService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SslController extends Controller
{
    protected SslService $sslService;

    public function __construct(SslService $sslService)
    {
        $this->sslService = $sslService;
    }

    /**
     * Display SSL / TLS Certificates console.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $status = $request->query('status', 'all');

        $query = SslCertificate::with('subscription')->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('issuer', 'like', "%{$s}%");
            });
        }

        if ($status !== 'all') {
            if ($status === 'expiring') {
                $query->where('status', 'active')
                      ->where('valid_to', '<=', now()->addDays(30));
            } else {
                $query->where('status', $status);
            }
        }

        $certificates = $query->paginate(15)->withQueryString();

        $allCerts = SslCertificate::all();
        $stats = [
            'total_certs' => $allCerts->count(),
            'active_certs' => $allCerts->where('status', 'active')->count(),
            'autossl_count' => $allCerts->where('auto_renew', true)->count(),
            'expiring_soon' => $allCerts->filter(fn($c) => $c->is_expiring_soon)->count(),
            'https_enforced' => $allCerts->where('force_https', true)->count(),
        ];

        $hostedDomains = DnsZone::select('id', 'domain')->orderBy('domain')->get();
        $subscriptions = Subscription::select('id', 'domain')->orderBy('domain')->get();

        return Inertia::render('Admin/Security/SSL', [
            'certificates' => $certificates,
            'stats' => $stats,
            'hostedDomains' => $hostedDomains,
            'subscriptions' => $subscriptions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Issue Let's Encrypt AutoSSL Certificate.
     */
    public function issueLetsEncrypt(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:191',
            'san_domains' => 'nullable|array',
            'force_https' => 'boolean',
            'hsts_enabled' => 'boolean',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $res = $this->sslService->issueLetsEncrypt($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Install Custom Commercial SSL Certificate.
     */
    public function installCustom(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:191',
            'certificate' => 'required|string',
            'private_key' => 'required|string',
            'ca_bundle' => 'nullable|string',
            'force_https' => 'boolean',
            'hsts_enabled' => 'boolean',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $res = $this->sslService->installCustom($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->withErrors(['certificate' => $res['error']]);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Generate Self-Signed Certificate.
     */
    public function generateSelfSigned(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:191',
            'valid_days' => 'required|integer|min:30|max:1825',
            'force_https' => 'boolean',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $res = $this->sslService->generateSelfSigned($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle Force HTTPS Redirection.
     */
    public function toggleForceHttps(SslCertificate $sslCertificate)
    {
        $sslCertificate->update(['force_https' => !$sslCertificate->force_https]);
        $state = $sslCertificate->force_https ? 'Enforced' : 'Disabled';

        return redirect()->back()->with('success', "Force HTTPS {$state} for `{$sslCertificate->domain}`.");
    }

    /**
     * Toggle AutoSSL Renewal.
     */
    public function toggleAutoRenew(SslCertificate $sslCertificate)
    {
        $sslCertificate->update(['auto_renew' => !$sslCertificate->auto_renew]);
        $state = $sslCertificate->auto_renew ? 'Enabled' : 'Disabled';

        return redirect()->back()->with('success', "AutoSSL Auto-Renewal {$state} for `{$sslCertificate->domain}`.");
    }

    /**
     * Renew SSL Certificate.
     */
    public function renew(SslCertificate $sslCertificate)
    {
        $res = $this->sslService->renewCertificate($sslCertificate, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Download Certificate or Private Key.
     */
    public function download(SslCertificate $sslCertificate, string $part): HttpResponse
    {
        if ($part === 'key') {
            return response($sslCertificate->private_key, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=\"{$sslCertificate->domain}.key\"",
            ]);
        }

        return response($sslCertificate->certificate, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$sslCertificate->domain}.crt\"",
        ]);
    }

    /**
     * Delete Certificate.
     */
    public function destroy(SslCertificate $sslCertificate)
    {
        $res = $this->sslService->deleteCertificate($sslCertificate, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
