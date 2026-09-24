<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Traits\AuditLoggable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    use AuditLoggable;

    /**
     * Display directory of hosting packages, quotas, and pricing.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = HostingPlan::query()
            ->withCount('subscriptions');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $plans = $query->orderBy('sort_order', 'asc')->latest('created_at')->get();

        // Calculate 4 Clean 3-Tier Metric Stats
        $allPlans = HostingPlan::all();
        $totalSubscriptions = Subscription::where('status', 'active')->count();
        $avgPrice = $allPlans->count() > 0 ? round($allPlans->avg('price_monthly'), 2) : 0;

        $stats = [
            'total_plans' => $allPlans->count(),
            'active_plans' => $allPlans->where('is_active', true)->count(),
            'total_subscribers' => $totalSubscriptions,
            'avg_price' => $avgPrice,
        ];

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'stats' => $stats,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for hosting packages.
     */
    public function apiMetrics(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = HostingPlan::query()
            ->withCount('subscriptions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $plans = $query->orderBy('sort_order', 'asc')->latest('created_at')->get();
        $allPlans = HostingPlan::all();
        $totalSubscriptions = Subscription::where('status', 'active')->count();
        $avgPrice = $allPlans->count() > 0 ? round($allPlans->avg('price_monthly'), 2) : 0;

        return response()->json([
            'plans' => $plans,
            'stats' => [
                'total_plans' => $allPlans->count(),
                'active_plans' => $allPlans->where('is_active', true)->count(),
                'total_subscribers' => $totalSubscriptions,
                'avg_price' => $avgPrice,
            ],
        ]);
    }

    /**
     * 1-Click Clone / Duplicate an existing hosting package.
     */
    public function clone(HostingPlan $plan): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $plan->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['subscriptions_count']);

        $baseName = $plan->name . ' (Copy)';
        $data['name'] = $baseName;
        
        $baseSlug = Str::slug($baseName);
        $slug = $baseSlug;
        $counter = 1;
        while (HostingPlan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;

        $newPlan = HostingPlan::create($data);

        $this->logActivity('hosting_plan_cloned', [
            'description' => "Cloned hosting package '{$plan->name}' into '{$newPlan->name}'",
            'source_plan_id' => $plan->id,
            'new_plan_id' => $newPlan->id,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Package '{$plan->name}' cloned as '{$newPlan->name}'.");
    }

    /**
     * Show create plan view (or redirect).
     */
    public function create(): Response
    {
        $this->authorize('viewAny', User::class);
        return Inertia::render('Admin/Plans/Create');
    }

    /**
     * Store new hosting plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:hosting_plans,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'disk_space' => ['required', 'integer', 'min:50'],
            'bandwidth' => ['required', 'integer', 'min:100'],
            'max_domains' => ['required', 'integer', 'min:1'],
            'max_subdomains' => ['nullable', 'integer', 'min:0'],
            'max_databases' => ['nullable', 'integer', 'min:0'],
            'max_email_accounts' => ['nullable', 'integer', 'min:0'],
            'max_ftp_accounts' => ['nullable', 'integer', 'min:0'],
            'cpu_limit' => ['nullable', 'integer', 'min:10', 'max:400'],
            'ram_limit' => ['nullable', 'integer', 'min:128', 'max:32768'],
            'php_version_default' => ['nullable', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'auto_ssl' => ['nullable', 'boolean'],
            'allow_ssh_access' => ['nullable', 'boolean'],
            'allow_custom_php_ini' => ['nullable', 'boolean'],
            'allow_git_deploy' => ['nullable', 'boolean'],
            'allow_redis' => ['nullable', 'boolean'],
            'redis_memory_mb' => ['nullable', 'integer', 'min:16', 'max:8192'],
            'allow_memcached' => ['nullable', 'boolean'],
            'allow_nodejs' => ['nullable', 'boolean'],
            'allow_python' => ['nullable', 'boolean'],
            'allow_cron_jobs' => ['nullable', 'boolean'],
            'allow_backups' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            $orig = $validated['slug'];
            $c = 1;
            while (HostingPlan::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $orig . '-' . $c;
                $c++;
            }
        }

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['auto_ssl'] = $validated['auto_ssl'] ?? true;
        $validated['php_version_default'] = $validated['php_version_default'] ?? '8.2';
        $validated['allow_redis'] = $validated['allow_redis'] ?? false;
        $validated['redis_memory_mb'] = $validated['redis_memory_mb'] ?? 64;
        $validated['allow_memcached'] = $validated['allow_memcached'] ?? false;
        $validated['allow_nodejs'] = $validated['allow_nodejs'] ?? false;
        $validated['allow_python'] = $validated['allow_python'] ?? false;
        $validated['allow_cron_jobs'] = $validated['allow_cron_jobs'] ?? true;
        $validated['allow_backups'] = $validated['allow_backups'] ?? true;

        $plan = HostingPlan::create($validated);

        $this->logActivity('hosting_plan_created', [
            'description' => "Created new hosting plan '{$plan->name}' ({$plan->disk_space} MB)",
            'plan_id' => $plan->id,
            'name' => $plan->name,
            'price_monthly' => $plan->price_monthly,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Hosting plan '{$plan->name}' created successfully.");
    }

    /**
     * Show plan details.
     */
    public function show(HostingPlan $plan): Response
    {
        $this->authorize('viewAny', User::class);
        $plan->loadCount('subscriptions');
        return Inertia::render('Admin/Plans/Show', ['plan' => $plan]);
    }

    /**
     * Show edit form.
     */
    public function edit(HostingPlan $plan): Response
    {
        $this->authorize('viewAny', User::class);
        return Inertia::render('Admin/Plans/Edit', ['plan' => $plan]);
    }

    /**
     * Update existing hosting plan.
     */
    public function update(Request $request, HostingPlan $plan): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:hosting_plans,slug,' . $plan->id],
            'description' => ['nullable', 'string', 'max:500'],
            'disk_space' => ['required', 'integer', 'min:50'],
            'bandwidth' => ['required', 'integer', 'min:100'],
            'max_domains' => ['required', 'integer', 'min:1'],
            'max_subdomains' => ['nullable', 'integer', 'min:0'],
            'max_databases' => ['nullable', 'integer', 'min:0'],
            'max_email_accounts' => ['nullable', 'integer', 'min:0'],
            'max_ftp_accounts' => ['nullable', 'integer', 'min:0'],
            'cpu_limit' => ['nullable', 'integer', 'min:10', 'max:400'],
            'ram_limit' => ['nullable', 'integer', 'min:128', 'max:32768'],
            'php_version_default' => ['nullable', 'string', 'in:8.1,8.2,8.3,8.4,8.5'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'auto_ssl' => ['nullable', 'boolean'],
            'allow_ssh_access' => ['nullable', 'boolean'],
            'allow_custom_php_ini' => ['nullable', 'boolean'],
            'allow_git_deploy' => ['nullable', 'boolean'],
            'allow_redis' => ['nullable', 'boolean'],
            'redis_memory_mb' => ['nullable', 'integer', 'min:16', 'max:8192'],
            'allow_memcached' => ['nullable', 'boolean'],
            'allow_nodejs' => ['nullable', 'boolean'],
            'allow_python' => ['nullable', 'boolean'],
            'allow_cron_jobs' => ['nullable', 'boolean'],
            'allow_backups' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan->update($validated);

        $this->logActivity('hosting_plan_updated', [
            'description' => "Updated hosting plan '{$plan->name}'",
            'plan_id' => $plan->id,
            'name' => $plan->name,
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Hosting plan '{$plan->name}' updated successfully.");
    }

    /**
     * Toggle active state.
     */
    public function toggleStatus(HostingPlan $plan): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $plan->update(['is_active' => !$plan->is_active]);

        $statusStr = $plan->is_active ? 'activated' : 'deactivated';

        $this->logActivity('hosting_plan_status_toggled', [
            'description' => "Package '{$plan->name}' {$statusStr}",
            'plan_id' => $plan->id,
            'is_active' => $plan->is_active,
        ]);

        return redirect()->back()->with('success', "Hosting plan '{$plan->name}' {$statusStr}.");
    }

    /**
     * Delete hosting plan.
     */
    public function destroy(HostingPlan $plan): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        if ($plan->subscriptions()->count() > 0) {
            return redirect()->back()->with('error', "Cannot delete '{$plan->name}' because it has active subscriptions attached.");
        }

        $name = $plan->name;
        $plan->delete();

        $this->logActivity('hosting_plan_deleted', [
            'description' => "Deleted hosting plan '{$name}'",
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Hosting plan '{$name}' deleted successfully.");
    }
}
