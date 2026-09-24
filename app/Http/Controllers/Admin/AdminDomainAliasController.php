<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainAlias;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NginxManager;
use App\Services\SSLManager;
use App\Traits\AuditLoggable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminDomainAliasController extends Controller
{
    use AuditLoggable;

    /**
     * Display directory of all parked domains and domain aliases.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = DomainAlias::query()
            ->with([
                'subscription:id,user_id,plan_id,server_id,domain,username,status,document_root,php_version',
                'subscription.user:id,first_name,last_name,username,email,company',
                'subscription.plan:id,name',
            ]);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('redirect_url', 'like', "%{$search}%")
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

        // Target Type Filter
        if ($targetType = $request->input('target_type')) {
            $query->where('target_type', $targetType);
        }

        // SSL Filter
        if ($ssl = $request->input('ssl_status')) {
            $query->where('ssl_status', $ssl);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $aliases = $query->latest('created_at')->paginate(15)->withQueryString();

        // Calculate 4 Clean 3-Tier Metric Stats
        $allAliases = DomainAlias::all();
        $activeSslCount = $allAliases->where('ssl_status', 'active')->count();
        $totalAliases = $allAliases->count();
        $parkedMirrorsCount = $allAliases->where('target_type', 'parked')->count();

        $stats = [
            'total_aliases' => $totalAliases,
            'active_ssl_count' => $activeSslCount,
            'parked_mirrors_count' => $parkedMirrorsCount,
            'active_aliases' => $allAliases->where('status', 'active')->count(),
        ];

        $subscriptions = Subscription::with('user:id,first_name,last_name,username,email')
            ->where('status', 'active')
            ->select('id', 'user_id', 'domain', 'username', 'document_root', 'php_version')
            ->orderBy('domain', 'asc')
            ->get();

        return Inertia::render('Admin/Hosting/Aliases/Index', [
            'aliases' => $aliases,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'target_type', 'ssl_status', 'status']),
        ]);
    }

    /**
     * Store and deploy a new parked domain alias or URL redirect.
     */
    public function store(Request $request, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'domain' => ['required', 'string', 'max:255', 'unique:domain_aliases,domain', 'regex:/^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,}$/'],
            'target_type' => ['required', 'string', 'in:parked,redirect'],
            'redirect_url' => ['nullable', 'required_if:target_type,redirect', 'string', 'max:500'],
            'redirect_status_code' => ['nullable', 'integer', 'in:301,302'],
            'auto_ssl' => ['nullable', 'boolean'],
        ]);

        $subscription = Subscription::with('user')->findOrFail($validated['subscription_id']);
        $domain = Str::lower($validated['domain']);

        $alias = DomainAlias::create([
            'subscription_id' => $subscription->id,
            'domain' => $domain,
            'target_type' => $validated['target_type'],
            'redirect_url' => $validated['redirect_url'] ?? null,
            'redirect_status_code' => $validated['redirect_status_code'] ?? 301,
            'ssl_status' => 'none',
            'auto_ssl' => $validated['auto_ssl'] ?? true,
            'status' => 'active',
        ]);

        // Generate Nginx VHost for Alias
        $nginxManager->createVirtualHost([
            'domain' => $domain,
            'username' => $subscription->username,
            'document_root' => $subscription->document_root,
            'php_version' => $subscription->php_version ?? '8.5',
            'ssl_enabled' => false,
        ]);

        $this->logActivity('domain_alias_created', [
            'description' => "Created domain alias '{$domain}' pointing to '{$subscription->domain}'",
            'alias_id' => $alias->id,
            'domain' => $domain,
            'target_type' => $alias->target_type,
            'parent_domain' => $subscription->domain,
        ]);

        return redirect()->route('admin.hosting.aliases')
            ->with('success', "Domain alias '{$domain}' provisioned successfully.");
    }

    /**
     * Issue Let's Encrypt SSL certificate for domain alias.
     */
    public function issueSsl(Request $request, DomainAlias $alias, SSLManager $sslManager, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $subscription = $alias->subscription()->with('user')->first();
        $email = $subscription?->user?->email ?? config('mail.from.address', 'admin@deeptouchhost.local');

        $sslResult = $sslManager->generateSSL($alias->domain, $email);

        $alias->update([
            'ssl_status' => 'active',
            'ssl_last_renewed_at' => now(),
            'ssl_expires_at' => now()->addDays(90),
        ]);

        if ($sslResult['success']) {
            $nginxManager->createVirtualHost([
                'domain' => $alias->domain,
                'username' => $subscription?->username ?? 'webuser',
                'document_root' => $subscription?->document_root ?? "/var/www/vhosts/{$alias->domain}/public_html",
                'php_version' => $subscription?->php_version ?? '8.5',
                'ssl_enabled' => true,
            ]);
        }

        $this->logActivity('domain_alias_ssl_issued', [
            'description' => "Issued SSL certificate for domain alias '{$alias->domain}'",
            'domain' => $alias->domain,
        ]);

        return redirect()->back()->with('success', "SSL Certificate provisioned for alias '{$alias->domain}'.");
    }

    /**
     * Toggle status (Active <-> Suspended).
     */
    public function toggleStatus(Request $request, DomainAlias $alias, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $newStatus = $alias->status === 'active' ? 'suspended' : 'active';
        $alias->update(['status' => $newStatus]);

        if ($newStatus === 'suspended') {
            $nginxManager->suspendDomain($alias->domain);
            $msg = "Domain alias '{$alias->domain}' suspended.";
        } else {
            $nginxManager->unsuspendDomain($alias->domain);
            $msg = "Domain alias '{$alias->domain}' reactivated.";
        }

        $this->logActivity('domain_alias_status_toggled', [
            'description' => "Changed status of domain alias '{$alias->domain}' to {$newStatus}",
            'domain' => $alias->domain,
            'status' => $newStatus,
        ]);

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Delete parked domain alias.
     */
    public function destroy(DomainAlias $alias, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $domain = $alias->domain;
        $nginxManager->deleteDomain($domain);
        $alias->delete();

        $this->logActivity('domain_alias_deleted', [
            'description' => "Deleted domain alias '{$domain}'",
            'domain' => $domain,
        ]);

        return redirect()->route('admin.hosting.aliases')
            ->with('success', "Domain alias '{$domain}' deleted successfully.");
    }
}
