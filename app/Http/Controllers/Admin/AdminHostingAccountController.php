<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\NginxManager;
use App\Traits\AuditLoggable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminHostingAccountController extends Controller
{
    use AuditLoggable;

    /**
     * Display a paginated, filterable directory of hosting accounts / subscriptions.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Subscription::query()
            ->with([
                'user:id,first_name,last_name,username,email,company,role,status',
                'plan:id,name,disk_space,bandwidth,php_version_default,max_databases,max_email_accounts,max_ftp_accounts',
                'server:id,name,hostname,ip_address,status,health_status',
                'websites:id,subscription_id,domain,php_version,ssl_status,status,document_root',
            ]);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Plan Filter
        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        // Server Filter
        if ($serverId = $request->input('server_id')) {
            $query->where('server_id', $serverId);
        }

        $accounts = $query->latest('created_at')->paginate(15)->withQueryString();

        // Calculate 4 Clean 3-Tier Metric Stats
        $allSubscriptions = Subscription::all();
        $totalAllocatedDiskMb = 0;
        $totalAllocatedBwMb = 0;

        foreach ($allSubscriptions as $sub) {
            if ($sub->plan) {
                $totalAllocatedDiskMb += (int) $sub->plan->disk_space;
                $totalAllocatedBwMb += (int) $sub->plan->bandwidth;
            }
        }

        $stats = [
            'total_accounts' => $allSubscriptions->count(),
            'active_accounts' => $allSubscriptions->where('status', 'active')->count(),
            'suspended_accounts' => $allSubscriptions->where('status', 'suspended')->count(),
            'allocated_disk_formatted' => $totalAllocatedDiskMb >= 1024 
                ? round($totalAllocatedDiskMb / 1024, 1) . ' GB' 
                : $totalAllocatedDiskMb . ' MB',
            'allocated_bw_formatted' => $totalAllocatedBwMb >= 1024 
                ? round($totalAllocatedBwMb / 1024, 1) . ' GB' 
                : $totalAllocatedBwMb . ' MB',
        ];

        $plans = HostingPlan::where('is_active', true)
            ->select('id', 'name', 'disk_space', 'bandwidth', 'php_version_default', 'price_monthly')
            ->orderBy('sort_order', 'asc')
            ->get();

        $servers = Server::select('id', 'name', 'hostname', 'ip_address', 'status')
            ->orderBy('name', 'asc')
            ->get();

        $customers = User::whereIn('role', ['client', 'reseller'])
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'company', 'role')
            ->orderBy('first_name', 'asc')
            ->get();

        $phpVersions = ['8.1', '8.2', '8.3', '8.4', '8.5'];

        return Inertia::render('Admin/Hosting/Accounts/Index', [
            'accounts' => $accounts,
            'stats' => $stats,
            'plans' => $plans,
            'servers' => $servers,
            'customers' => $customers,
            'phpVersions' => $phpVersions,
            'filters' => $request->only(['search', 'status', 'plan_id', 'server_id']),
        ]);
    }

    /**
     * Provision and deploy a new hosting account (VHost, Filesystem, Nginx).
     */
    public function store(Request $request, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'domain' => ['required', 'string', 'max:255', 'unique:subscriptions,domain', 'unique:websites,domain', 'regex:/^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,}$/'],
            'plan_id' => ['required', 'exists:hosting_plans,id'],
            'server_id' => ['required', 'exists:servers,id'],
            'php_version' => ['required', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
            'username' => ['nullable', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]{2,31}$/', 'unique:subscriptions,username'],
            'period' => ['nullable', 'string', 'in:monthly,yearly'],
        ]);

        $domain = Str::lower($validated['domain']);
        $plan = HostingPlan::findOrFail($validated['plan_id']);
        $user = User::findOrFail($validated['user_id']);
        $server = Server::findOrFail($validated['server_id']);

        // Generate Unix Username
        if (empty($validated['username'])) {
            $base = Str::slug(explode('.', $domain)[0], '');
            $username = Str::lower(substr(preg_replace('/[^a-z0-9]/', '', $base), 0, 16));
            if (empty($username)) {
                $username = 'u' . Str::lower(Str::random(6));
            }
            $orig = $username;
            $counter = 1;
            while (Subscription::where('username', $username)->exists()) {
                $username = substr($orig, 0, 14) . $counter;
                $counter++;
            }
        } else {
            $username = $validated['username'];
        }

        $docRoot = "/var/www/vhosts/{$username}/{$domain}/public_html";

        // Create Real Filesystem Directory & Default Index Page
        try {
            if (!File::exists($docRoot)) {
                File::makeDirectory($docRoot, 0755, true, true);
                
                $templatePath = resource_path('views/templates/default_holding_page.html');
                if (file_exists($templatePath)) {
                    $defaultIndexContent = str_replace('{{DOMAIN}}', $domain, file_get_contents($templatePath));
                } else {
                    $defaultIndexContent = "<!DOCTYPE html><html><head><title>{$domain}</title></head><body><h1>Welcome to {$domain}</h1></body></html>";
                }
                File::put($docRoot . '/index.html', $defaultIndexContent);
                @chmod($docRoot . '/index.html', 0664);
                @chown($docRoot . '/index.html', $username);
            }
        } catch (\Throwable $e) {
            // Log file permission notice but continue
        }

        // Create Database Subscription Record
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => $domain,
            'username' => $username,
            'document_root' => $docRoot,
            'php_version' => $validated['php_version'],
            'status' => 'active',
            'period' => $validated['period'] ?? 'yearly',
            'price' => $plan->price_yearly ?? $plan->price_monthly ?? 0,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
        ]);

        // Create Primary Website Record
        Website::create([
            'subscription_id' => $subscription->id,
            'domain' => $domain,
            'document_root' => $docRoot,
            'php_version' => $validated['php_version'],
            'ssl_status' => 'none',
            'auto_ssl' => true,
            'is_primary' => true,
            'status' => 'active',
        ]);

        // Deploy Real Nginx VHost
        $vhostResult = $nginxManager->createVirtualHost([
            'domain' => $domain,
            'username' => $username,
            'document_root' => $docRoot,
            'php_version' => $validated['php_version'],
            'ssl_enabled' => false,
        ]);

        $this->logActivity('hosting_account_provisioned', [
            'description' => "Provisioned hosting account '{$domain}' for client '{$user->name}'",
            'subscription_id' => $subscription->id,
            'domain' => $domain,
            'username' => $username,
            'plan' => $plan->name,
            'php_version' => $validated['php_version'],
            'nginx_status' => $vhostResult['success'] ? 'deployed' : 'notice: ' . ($vhostResult['error'] ?? 'skipped'),
        ]);

        $notice = $vhostResult['success'] 
            ? "Hosting account '{$domain}' provisioned and Nginx virtual host activated."
            : "Hosting account '{$domain}' created (Nginx notice: " . ($vhostResult['error'] ?? 'check config') . ").";

        return redirect()->route('admin.hosting.accounts')
            ->with('success', $notice);
    }

    /**
     * Toggle status (Suspend <-> Unsuspend) with real Nginx state change.
     */
    public function toggleStatus(Request $request, Subscription $subscription, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $newStatus = $subscription->status === 'active' ? 'suspended' : 'active';

        if ($newStatus === 'suspended') {
            $subscription->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);
            $subscription->websites()->update(['status' => 'suspended']);
            $nginxManager->suspendDomain($subscription->domain);
            
            $msg = "Hosting account '{$subscription->domain}' suspended.";
        } else {
            $subscription->update([
                'status' => 'active',
                'suspended_at' => null,
            ]);
            $subscription->websites()->update(['status' => 'active']);
            $nginxManager->unsuspendDomain($subscription->domain);

            $msg = "Hosting account '{$subscription->domain}' reactivated.";
        }

        $this->logActivity('hosting_account_status_toggled', [
            'description' => "Changed status of '{$subscription->domain}' to {$newStatus}",
            'subscription_id' => $subscription->id,
            'domain' => $subscription->domain,
            'new_status' => $newStatus,
        ]);

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Change / upgrade hosting plan.
     */
    public function changePlan(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:hosting_plans,id'],
        ]);

        $oldPlan = $subscription->plan?->name ?? 'Custom';
        $newPlan = HostingPlan::findOrFail($validated['plan_id']);

        $subscription->update([
            'plan_id' => $newPlan->id,
            'price' => $subscription->period === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly,
            'custom_disk_space' => null,
            'custom_inodes' => null,
        ]);

        $this->logActivity('hosting_account_plan_changed', [
            'description' => "Upgraded '{$subscription->domain}' plan from {$oldPlan} to {$newPlan->name}",
            'subscription_id' => $subscription->id,
            'old_plan' => $oldPlan,
            'new_plan' => $newPlan->name,
        ]);

        return redirect()->back()->with('success', "Plan for '{$subscription->domain}' updated to {$newPlan->name}.");
    }

    /**
     * Change PHP runtime version and reload Nginx socket.
     */
    public function changePhp(Request $request, Subscription $subscription, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'php_version' => ['required', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
        ]);

        $oldVersion = $subscription->php_version;
        $newVersion = $validated['php_version'];

        $subscription->update(['php_version' => $newVersion]);
        $subscription->websites()->update(['php_version' => $newVersion]);

        // Re-generate Nginx VHost with new socket
        $vhostResult = $nginxManager->createVirtualHost([
            'domain' => $subscription->domain,
            'username' => $subscription->username,
            'document_root' => $subscription->document_root,
            'php_version' => $newVersion,
            'ssl_enabled' => false,
        ]);

        $this->logActivity('hosting_account_php_changed', [
            'description' => "Updated PHP runtime of '{$subscription->domain}' from PHP {$oldVersion} to PHP {$newVersion}",
            'subscription_id' => $subscription->id,
            'old_php' => $oldVersion,
            'new_php' => $newVersion,
        ]);

        return redirect()->back()->with('success', "PHP version for '{$subscription->domain}' updated to PHP {$newVersion}.");
    }

    /**
     * Terminate and permanently delete hosting account.
     */
    public function destroy(Request $request, Subscription $subscription, NginxManager $nginxManager): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $domain = $subscription->domain;
        $username = $subscription->username;

        // Delete Nginx config
        $nginxManager->deleteDomain($domain);

        // Delete associated database records
        $subscription->websites()->delete();
        $subscription->databases()->delete();
        $subscription->ftpAccounts()->delete();
        $subscription->emailAccounts()->delete();
        $subscription->delete();

        $this->logActivity('hosting_account_terminated', [
            'description' => "Terminated hosting account '{$domain}' (@{$username})",
            'domain' => $domain,
            'username' => $username,
        ]);

        return redirect()->route('admin.hosting.accounts')
            ->with('success', "Hosting account '{$domain}' terminated successfully.");
    }
}
