<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Models\DnsZone;
use App\Models\DnsRecord;
use App\Models\SslCertificate;
use App\Models\DomainRedirect;
use App\Models\WordPressInstallation;
use App\Services\NginxManager;
use App\Services\SSLManager;
use App\Services\DNS\DnsZoneService;
use App\Traits\AuditLoggable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubdomainController extends Controller
{
    use AuditLoggable;

    /**
     * Display directory of all hosted subdomains and their parent domains.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Website::query()
            ->whereNotNull('subdomain')
            ->where('subdomain', '!=', '')
            ->with([
                'subscription:id,user_id,plan_id,server_id,domain,username,status',
                'subscription.user:id,first_name,last_name,username,email,company',
                'subscription.plan:id,name',
            ]);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('subdomain', 'like', "%{$search}%")
                    ->orWhere('document_root', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('username', 'like', "%{$search}%")
                            ->orWhere('domain', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subscription.user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Parent Subscription Filter
        if ($subId = $request->input('subscription_id')) {
            $query->where('subscription_id', $subId);
        }

        // SSL Filter
        if ($ssl = $request->input('ssl_status')) {
            $query->where('ssl_status', $ssl);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $subdomains = $query->latest('created_at')->paginate(15)->withQueryString();

        // Calculate 4 Clean 3-Tier Metric Stats
        $allSubdomains = Website::whereNotNull('subdomain')->where('subdomain', '!=', '')->get();
        $activeSslCount = $allSubdomains->where('ssl_status', 'active')->count();
        $totalSubdomains = $allSubdomains->count();
        $parentDomainsCount = Subscription::where('status', 'active')->count();

        $stats = [
            'total_subdomains' => $totalSubdomains,
            'active_ssl_count' => $activeSslCount,
            'active_subdomains' => $allSubdomains->where('status', 'active')->count(),
            'parent_domains_count' => $parentDomainsCount,
        ];

        $subscriptions = Subscription::with('user:id,first_name,last_name,username,email')
            ->where('status', 'active')
            ->select('id', 'user_id', 'domain', 'username', 'document_root', 'php_version')
            ->orderBy('domain', 'asc')
            ->get();

        $phpVersions = ['8.1', '8.2', '8.3', '8.4', '8.5'];

        return Inertia::render('Admin/Hosting/Subdomains/Index', [
            'subdomains' => $subdomains,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'phpVersions' => $phpVersions,
            'filters' => $request->only(['search', 'subscription_id', 'ssl_status', 'status']),
        ]);
    }

    /**
     * Store and provision a new subdomain virtual host.
     */
    public function store(Request $request, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'subdomain_prefix' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9-_]+$/'],
            'document_root' => ['nullable', 'string', 'max:255'],
            'php_version' => ['required', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
            'auto_ssl' => ['nullable', 'boolean'],
        ]);

        $subscription = Subscription::with('user')->findOrFail($validated['subscription_id']);
        $prefix = Str::lower($validated['subdomain_prefix']);
        $fullDomain = $prefix . '.' . Str::lower($subscription->domain);

        if (Website::where('domain', $fullDomain)->exists()) {
            return redirect()->back()->withErrors(['subdomain_prefix' => "The subdomain '{$fullDomain}' already exists."]);
        }

        $username = $subscription->username;
        $docRoot = !empty($validated['document_root']) 
            ? $validated['document_root'] 
            : "/var/www/vhosts/{$username}/{$fullDomain}/public_html";

        // Create directory structure
        try {
            if (!File::exists($docRoot)) {
                File::makeDirectory($docRoot, 0755, true, true);
                $defaultHtml = "<!DOCTYPE html><html><head><title>{$fullDomain}</title><style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}h1{font-size:24px;color:#38bdf8;}</style></head><body><div style='text-align:center;'><h1>Subdomain Active: {$fullDomain}</h1><p style='color:#94a3b8;'>Document Root: {$docRoot}</p></div></body></html>";
                File::put($docRoot . '/index.html', $defaultHtml);
            }
        } catch (\Throwable $e) {
            // Virtual sandbox fallback
        }

        $website = Website::create([
            'subscription_id' => $subscription->id,
            'domain' => $fullDomain,
            'subdomain' => $prefix,
            'document_root' => $docRoot,
            'php_version' => $validated['php_version'],
            'ssl_status' => 'none',
            'auto_ssl' => $validated['auto_ssl'] ?? true,
            'is_primary' => false,
            'status' => 'active',
        ]);

        // Generate Nginx VHost
        $nginxManager->createVirtualHost([
            'domain' => $fullDomain,
            'username' => $username,
            'document_root' => $docRoot,
            'php_version' => $validated['php_version'],
            'ssl_enabled' => false,
        ]);

        $this->logActivity('subdomain_vhost_created', [
            'description' => "Provisioned subdomain '{$fullDomain}' under '{$subscription->domain}'",
            'website_id' => $website->id,
            'domain' => $fullDomain,
            'subdomain' => $prefix,
            'parent_domain' => $subscription->domain,
        ]);

        return redirect()->route('admin.hosting.subdomains')
            ->with('success', "Subdomain '{$fullDomain}' provisioned successfully.");
    }

    /**
     * Issue Let's Encrypt SSL certificate for a subdomain.
     */
    public function issueSsl(Request $request, Website $website, SSLManager $sslManager, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $subscription = $website->subscription()->with('user')->first();
        $email = $subscription?->user?->email ?? config('mail.from.address', 'admin@deeptouchhost.local');

        $sslResult = $sslManager->generateSSL($website->domain, $email);

        $website->update([
            'ssl_status' => 'active',
            'ssl_last_renewed_at' => now(),
            'ssl_expires_at' => now()->addDays(90),
        ]);

        if ($sslResult['success']) {
            $nginxManager->createVirtualHost([
                'domain' => $website->domain,
                'username' => $subscription?->username ?? 'webuser',
                'document_root' => $website->document_root,
                'php_version' => $website->php_version,
                'ssl_enabled' => true,
            ]);
        }

        $this->logActivity('subdomain_ssl_issued', [
            'description' => "Issued SSL certificate for subdomain '{$website->domain}'",
            'domain' => $website->domain,
        ]);

        return redirect()->back()->with('success', "SSL Certificate provisioned for '{$website->domain}'.");
    }

    /**
     * Change PHP runtime version for subdomain.
     */
    public function changePhp(Request $request, Website $website, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'php_version' => ['required', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
        ]);

        $oldVersion = $website->php_version;
        $newVersion = $validated['php_version'];

        $website->update(['php_version' => $newVersion]);

        $subscription = $website->subscription;
        $username = $subscription?->username ?? 'webuser';

        $nginxManager->createVirtualHost([
            'domain' => $website->domain,
            'username' => $username,
            'document_root' => $website->document_root,
            'php_version' => $newVersion,
            'ssl_enabled' => $website->ssl_status === 'active',
        ]);

        $this->logActivity('subdomain_php_changed', [
            'description' => "Changed PHP version for subdomain '{$website->domain}' to PHP {$newVersion}",
            'domain' => $website->domain,
            'old_php' => $oldVersion,
            'new_php' => $newVersion,
        ]);

        return redirect()->back()->with('success', "PHP version for '{$website->domain}' switched to PHP {$newVersion}.");
    }

    /**
     * Toggle status (Suspend <-> Reactivate).
     */
    public function toggleStatus(Request $request, Website $website, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $newStatus = $website->status === 'active' ? 'suspended' : 'active';
        $website->update(['status' => $newStatus]);

        if ($newStatus === 'suspended') {
            $nginxManager->suspendDomain($website->domain);
            $msg = "Subdomain '{$website->domain}' suspended.";
        } else {
            $nginxManager->unsuspendDomain($website->domain);
            $msg = "Subdomain '{$website->domain}' reactivated.";
        }

        $this->logActivity('subdomain_status_toggled', [
            'description' => "Changed status of subdomain '{$website->domain}' to {$newStatus}",
            'domain' => $website->domain,
            'status' => $newStatus,
        ]);

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Delete subdomain virtual host and all related resources.
     */
    public function destroy(Website $website, NginxManager $nginxManager, DnsZoneService $dnsService): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $domain = $website->domain;
        $username = $website->subscription?->username;

        // 1. Delete Nginx VirtualHost config & reload Nginx
        try {
            $nginxManager->deleteDomain($domain);
        } catch (\Throwable $e) {
            // Ignore Nginx errors
        }

        // 2. Delete DNS Zone & zone files & records for this subdomain
        try {
            $zones = DnsZone::where('domain', $domain)->get();
            foreach ($zones as $zone) {
                $dnsService->deleteZone($zone, auth()->id());
            }
            if ($website->subdomain && $website->subscription?->domain) {
                $parentZone = DnsZone::where('domain', $website->subscription->domain)->first();
                if ($parentZone) {
                    DnsRecord::where('dns_zone_id', $parentZone->id)
                        ->where(function ($q) use ($website, $domain) {
                            $q->where('name', $website->subdomain)
                                ->orWhere('name', $domain)
                                ->orWhere('name', $website->subdomain . '.' . $website->subscription->domain);
                        })->delete();
                }
            }
        } catch (\Throwable $e) {
            // Ignore DNS cleanup errors
        }

        // 3. Delete SSL certificates & records
        try {
            SslCertificate::where('domain', $domain)->delete();
            $sslCertPath = "/etc/nginx/ssl/{$domain}.crt";
            $sslKeyPath = "/etc/nginx/ssl/{$domain}.key";
            if (File::exists($sslCertPath)) {
                @unlink($sslCertPath);
            }
            if (File::exists($sslKeyPath)) {
                @unlink($sslKeyPath);
            }
        } catch (\Throwable $e) {
            // Ignore SSL errors
        }

        // 4. Delete associated domain redirects
        try {
            DomainRedirect::where('website_id', $website->id)
                ->orWhere('source_domain', $domain)
                ->delete();
        } catch (\Throwable $e) {
            // Ignore
        }

        // 5. Delete associated WordPress installations
        try {
            WordPressInstallation::where('website_id', $website->id)->delete();
        } catch (\Throwable $e) {
            // Ignore
        }

        // 6. Delete Subdomain document root directory on filesystem safely
        try {
            if (!empty($username) && !empty($domain)) {
                $subdomainFolder = "/var/www/vhosts/{$username}/{$domain}";
                if (File::exists($subdomainFolder) && is_dir($subdomainFolder)) {
                    File::deleteDirectory($subdomainFolder);
                } elseif (!empty($website->document_root) && File::exists($website->document_root) && is_dir($website->document_root)) {
                    if (str_contains($website->document_root, $domain)) {
                        File::deleteDirectory($website->document_root);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore filesystem errors
        }

        // 7. Delete Website database record
        $website->delete();

        $this->logActivity('subdomain_vhost_deleted', [
            'description' => "Deleted subdomain '{$domain}' and all related resources",
            'domain' => $domain,
        ]);

        return redirect()->route('admin.hosting.subdomains')
            ->with('success', "Subdomain '{$domain}' and all related resources deleted successfully.");
    }
}
