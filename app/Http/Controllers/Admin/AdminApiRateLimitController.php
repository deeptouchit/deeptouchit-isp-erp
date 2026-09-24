<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiRateLimit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminApiRateLimitController extends Controller
{
    /**
     * Display API rate limiting and DDoS flood protection policies.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ApiRateLimit::query();

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('endpoint_pattern', 'like', "%{$search}%")
                    ->orWhere('scope_type', 'like', "%{$search}%");
            });
        }

        // Scope Filter
        if ($scope = $request->input('scope')) {
            if ($scope !== 'all') {
                $query->where('scope_type', $scope);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $policies = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $all = ApiRateLimit::all();
        $activePolicies = $all->where('status', 'active')->count();
        $totalBreaches = (int) $all->sum('total_breaches');
        $strictRules = $all->where('action_on_breach', 'temp_ip_ban')->count();
        $dryRunCount = $all->where('status', 'dry_run')->count();

        $stats = [
            'total_policies' => $all->count(),
            'active_policies' => $activePolicies,
            'total_breaches' => $totalBreaches,
            'strict_rules' => $strictRules,
            'dry_run_count' => $dryRunCount,
        ];

        return Inertia::render('Admin/Api/RateLimits/Index', [
            'policies' => $policies,
            'stats' => $stats,
            'filters' => $request->only(['search', 'scope', 'status']),
        ]);
    }

    /**
     * Store a newly created rate limit policy.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'scope_type' => ['required', 'in:global_ip,authenticated_keys,service_accounts,endpoint_specific,whitelisted_cidr'],
            'endpoint_pattern' => ['required', 'string', 'max:255'],
            'requests_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'burst_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'action_on_breach' => ['required', 'in:http_429,delay_throttle,temp_ip_ban,alert_admin'],
            'ban_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,disabled,dry_run'],
        ]);

        $policy = ApiRateLimit::create([
            'name' => $validated['name'],
            'scope_type' => $validated['scope_type'],
            'endpoint_pattern' => $validated['endpoint_pattern'],
            'requests_per_minute' => $validated['requests_per_minute'],
            'burst_capacity' => $validated['burst_capacity'],
            'action_on_breach' => $validated['action_on_breach'],
            'ban_duration_minutes' => $validated['ban_duration_minutes'] ?? 15,
            'status' => $validated['status'],
            'total_breaches' => 0,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'rate_limit_created',
            'description' => "Created Rate Limit Policy '{$policy->name}' ({$policy->requests_per_minute} req/min).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'name' => $policy->name,
                'rate' => $policy->requests_per_minute,
                'scope' => $policy->scope_type,
            ],
        ]);

        return redirect()->route('admin.api.rate-limits')
            ->with('success', "Rate limit policy '{$policy->name}' created successfully.");
    }

    /**
     * Update existing rate limit policy.
     */
    public function update(Request $request, ApiRateLimit $apiRateLimit): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'scope_type' => ['required', 'in:global_ip,authenticated_keys,service_accounts,endpoint_specific,whitelisted_cidr'],
            'endpoint_pattern' => ['required', 'string', 'max:255'],
            'requests_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'burst_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'action_on_breach' => ['required', 'in:http_429,delay_throttle,temp_ip_ban,alert_admin'],
            'ban_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,disabled,dry_run'],
        ]);

        $oldValues = $apiRateLimit->only(['name', 'scope_type', 'endpoint_pattern', 'requests_per_minute', 'burst_capacity', 'action_on_breach', 'status']);
        $apiRateLimit->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'rate_limit_updated',
            'description' => "Updated Rate Limit Policy '{$apiRateLimit->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $apiRateLimit->only(['name', 'scope_type', 'endpoint_pattern', 'requests_per_minute', 'burst_capacity', 'action_on_breach', 'status']),
        ]);

        return redirect()->route('admin.api.rate-limits')
            ->with('success', "Rate limit policy '{$apiRateLimit->name}' updated successfully.");
    }

    /**
     * Toggle status between active and disabled.
     */
    public function toggleStatus(Request $request, ApiRateLimit $apiRateLimit): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newStatus = $apiRateLimit->status === 'active' ? 'disabled' : 'active';
        $apiRateLimit->update(['status' => $newStatus]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'rate_limit_status_toggled',
            'description' => "Changed status of Rate Limit Policy '{$apiRateLimit->name}' to {$newStatus}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $apiRateLimit->getOriginal('status')],
            'new_values' => ['status' => $newStatus],
        ]);

        return redirect()->route('admin.api.rate-limits')
            ->with('success', "Rate limit policy status changed to {$newStatus}.");
    }

    /**
     * Delete rate limit policy.
     */
    public function destroy(Request $request, ApiRateLimit $apiRateLimit): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = $apiRateLimit->name;
        $apiRateLimit->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'rate_limit_deleted',
            'description' => "Deleted Rate Limit Policy '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.api.rate-limits')
            ->with('success', "Rate limit policy '{$name}' deleted successfully.");
    }
}
