<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Website;
use App\Services\DNS\DnsZoneService;
use App\Services\NginxManager;
use App\Services\SSLManager;
use App\Support\ServerHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    /**
     * Display list of websites with quota analytics and management shortcuts.
     */
    public function index(): Response
    {
        $userId = auth()->id();

        // 1. Fetch user active subscriptions with plan details
        $subscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['plan', 'websites'])
            ->get();

        // 2. Compute strict domain quotas
        $totalAllowed = $subscriptions->sum(fn($sub) => (int) ($sub->plan->max_domains ?? 1));
        $totalUsed = Website::whereHas('subscription', fn($q) => $q->where('user_id', $userId))->count();
        $canAddMore = $subscriptions->contains(function ($sub) {
            $allowed = (int) ($sub->plan->max_domains ?? 1);
            return $sub->websites->count() < $allowed;
        });

        $quota = [
            'total_allowed' => $totalAllowed,
            'total_used' => $totalUsed,
            'remaining' => max(0, $totalAllowed - $totalUsed),
            'can_add' => $canAddMore,
            'usage_percentage' => $totalAllowed > 0 ? min(100, round(($totalUsed / $totalAllowed) * 100)) : 100,
        ];

        // 3. Fetch all websites
        $websites = Website::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with('subscription.plan')
        ->latest()
        ->get()
        ->map(function ($site) {
            return [
                'id' => $site->id,
                'domain' => $site->domain,
                'document_root' => $site->document_root,
                'php_version' => $site->php_version ?? '8.2',
                'status' => $site->status ?? 'active',
                'ssl_status' => $site->ssl_status ?? 'none',
                'auto_ssl' => (bool) $site->auto_ssl,
                'is_primary' => (bool) $site->is_primary,
                'created_at' => $site->created_at?->format('M d, Y'),
                'subscription' => [
                    'id' => $site->subscription?->id,
                    'domain' => $site->subscription?->domain,
                    'plan_name' => $site->subscription?->plan?->name ?? 'Hosting Plan',
                    'max_domains' => $site->subscription?->plan?->max_domains ?? 1,
                ],
            ];
        });

        // 4. Server connection reference info
        $serverInfo = [
            'public_ip' => ServerHelper::getPublicIp(),
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
        ];

        return Inertia::render('Client/Websites/Index', [
            'websites' => $websites,
            'subscriptions' => $subscriptions->map(fn($s) => [
                'id' => $s->id,
                'domain' => $s->domain,
                'username' => $s->username,
                'plan_name' => $s->plan?->name,
                'max_domains' => $s->plan?->max_domains ?? 1,
                'used_domains' => $s->websites->count(),
                'can_add' => $s->websites->count() < ($s->plan?->max_domains ?? 1),
            ]),
            'quota' => $quota,
            'serverInfo' => $serverInfo,
        ]);
    }

    /**
     * Show form to deploy a new website if package quota allows.
     */
    public function create(): Response|RedirectResponse
    {
        $userId = auth()->id();
        $subscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['plan', 'websites'])
            ->get();

        // Filter subscriptions that still have quota available
        $availableSubscriptions = $subscriptions->filter(function ($sub) {
            $allowed = (int) ($sub->plan->max_domains ?? 1);
            return $sub->websites->count() < $allowed;
        })->values();

        if ($availableSubscriptions->isEmpty()) {
            return redirect()->route('websites.index')->with('error', 'Package Domain Limit Reached! Your active hosting plan does not permit additional domains. Please upgrade your plan.');
        }

        return Inertia::render('Client/Websites/Create', [
            'subscriptions' => $availableSubscriptions->map(fn($s) => [
                'id' => $s->id,
                'domain' => $s->domain,
                'username' => $s->username,
                'plan_name' => $s->plan?->name,
                'max_domains' => $s->plan?->max_domains ?? 1,
                'used_domains' => $s->websites->count(),
            ]),
            'serverIp' => ServerHelper::getPublicIp(),
        ]);
    }

    /**
     * Store new website and enforce package quota limits!
     */
    public function store(Request $request, NginxManager $nginxManager, DnsZoneService $dnsService): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'domain' => 'required|string|max:255|unique:websites,domain',
            'php_version' => 'required|in:8.1,8.2,8.3,8.5',
            'auto_ssl' => 'nullable|boolean',
        ]);

        $subscription = Subscription::with(['plan', 'websites'])->findOrFail($validated['subscription_id']);
        if ($subscription->user_id !== auth()->id()) {
            abort(403);
        }

        // 1. STRICT DOMAIN QUOTA CHECK
        $maxAllowed = (int) ($subscription->plan->max_domains ?? 1);
        $currentCount = $subscription->websites()->count();

        if ($currentCount >= $maxAllowed) {
            return back()->withErrors([
                'domain' => "Package Limit Reached: Your hosting plan ({$subscription->plan->name}) permits a maximum of {$maxAllowed} domain(s). You have already used {$currentCount}/{$maxAllowed}. Please upgrade your plan to add more domains.",
            ]);
        }

        // Clean domain name (strip scheme, paths, whitespace)
        $cleanDomain = strtolower(trim(preg_replace('#^https?://#', '', rtrim($validated['domain'], '/'))));
        if (Website::where('domain', $cleanDomain)->exists()) {
            return back()->withErrors(['domain' => "The domain `{$cleanDomain}` is already registered on this system."]);
        }

        $documentRoot = "/var/www/vhosts/{$subscription->username}/{$cleanDomain}/public_html";
        if (!is_dir($documentRoot)) {
            mkdir($documentRoot, 0755, true);
            @chown($documentRoot, $subscription->username);
            
            // Professional default holding page
            $templatePath = resource_path('views/templates/default_holding_page.html');
            if (file_exists($templatePath)) {
                $welcomeHtml = str_replace('{{DOMAIN}}', $cleanDomain, file_get_contents($templatePath));
            } else {
                $welcomeHtml = "<!DOCTYPE html><html><head><title>{$cleanDomain}</title></head><body><h1>Welcome to {$cleanDomain}</h1></body></html>";
            }
            file_put_contents($documentRoot . '/index.html', $welcomeHtml);
            @chmod($documentRoot . '/index.html', 0664);
            @chown($documentRoot . '/index.html', $subscription->username);
        }

        // 2. Provision Nginx VirtualHost
        $nginxManager->createVirtualHost([
            'domain' => $cleanDomain,
            'username' => $subscription->username,
            'document_root' => $documentRoot,
            'php_version' => $validated['php_version'],
            'ssl_enabled' => false,
        ]);

        // 3. Create Website Record
        $website = Website::create([
            'subscription_id' => $subscription->id,
            'domain' => $cleanDomain,
            'document_root' => $documentRoot,
            'php_version' => $validated['php_version'],
            'ssl_status' => 'none',
            'auto_ssl' => $validated['auto_ssl'] ?? true,
            'status' => 'active',
            'is_primary' => false,
        ]);

        // 4. Auto-compile DNS Zone in BIND9
        try {
            $dnsService->createZone([
                'domain' => $cleanDomain,
                'user_id' => auth()->id(),
                'subscription_id' => $subscription->id,
                'auto_populate' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning("DNS Zone auto-create warning for {$cleanDomain}: " . $e->getMessage());
        }

        return redirect()->route('websites.index')->with('success', "Website `{$cleanDomain}` provisioned successfully with PHP {$validated['php_version']} and Nginx vHost.");
    }

    /**
     * Switch PHP-FPM version for website.
     */
    public function updatePhp(Request $request, Website $website, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorizeAccess($website);

        $validated = $request->validate([
            'php_version' => 'required|in:8.1,8.2,8.3,8.5',
        ]);

        $nginxManager->createVirtualHost([
            'domain' => $website->domain,
            'username' => $website->subscription->username,
            'document_root' => $website->document_root,
            'php_version' => $validated['php_version'],
            'ssl_enabled' => $website->ssl_status === 'active',
        ]);

        $website->update(['php_version' => $validated['php_version']]);

        return back()->with('success', "PHP version for `{$website->domain}` updated to PHP {$validated['php_version']} FPM.");
    }

    /**
     * Show detailed website settings.
     */
    public function show(Website $website): Response
    {
        $this->authorizeAccess($website);

        return Inertia::render('Client/Websites/Show', [
            'website' => $website->load('subscription.plan'),
            'serverIp' => ServerHelper::getPublicIp(),
        ]);
    }

    /**
     * Remove website.
     */
    public function destroy(Website $website, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorizeAccess($website);

        // Disallow deleting primary domain without deleting the subscription
        if ($website->is_primary) {
            return back()->with('error', 'Cannot delete the Primary Domain of your hosting subscription. If you need to change your primary domain, please contact support.');
        }

        $domain = $website->domain;
        $nginxManager->deleteDomain($domain);
        $website->delete();

        return redirect()->route('websites.index')->with('success', "Website `{$domain}` deleted successfully.");
    }

    private function authorizeAccess(Website $website): void
    {
        if (auth()->user()->role !== 'admin' && $website->subscription->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
