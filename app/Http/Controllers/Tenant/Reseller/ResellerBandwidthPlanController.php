<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantBandwidthPlan;
use App\Models\TenantResellerBandwidth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerBandwidthPlanController extends Controller
{
    /**
     * Display Bandwidth Rate Plans list.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $query = TenantBandwidthPlan::where('tenant_id', $tenant->id)
            ->withCount('allocations');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === '1' || $status === 'active');
        }

        $plans = $query->orderBy('min_bandwidth_mbps', 'asc')->latest('id')->paginate($perPage)->withQueryString();

        // 6 KPI Cards
        $allPlans = TenantBandwidthPlan::where('tenant_id', $tenant->id)->get();
        $totalPlansCount = $allPlans->count();
        $activePlansCount = $allPlans->where('is_active', true)->count();
        $avgGlobalRate = (float) ($allPlans->where('global_rate_per_mbps', '>', 0)->avg('global_rate_per_mbps') ?? 0);
        $avgCdnRate = (float) ($allPlans->where('cdn_rate_per_mbps', '>', 0)->avg('cdn_rate_per_mbps') ?? 0);
        $avgBdixRate = (float) ($allPlans->where('bdix_rate_per_mbps', '>', 0)->avg('bdix_rate_per_mbps') ?? 0);
        $totalAssignedResellers = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->whereNotNull('bandwidth_plan_id')
            ->count();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.resellers.bandwidth_plans', compact(
            'tenant',
            'plans',
            'totalPlansCount',
            'activePlansCount',
            'avgGlobalRate',
            'avgCdnRate',
            'avgBdixRate',
            'totalAssignedResellers',
            'currencySymbol',
            'search',
            'status',
            'perPage'
        ));
    }

    /**
     * Store new Bandwidth Rate Plan.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'min_bandwidth_mbps' => 'nullable|numeric|min:0',
            'max_bandwidth_mbps' => 'nullable|numeric|min:0',
            'global_rate_per_mbps' => 'required|numeric|min:0',
            'cdn_rate_per_mbps' => 'nullable|numeric|min:0',
            'bdix_rate_per_mbps' => 'nullable|numeric|min:0',
            'ggc_rate_per_mbps' => 'nullable|numeric|min:0',
            'fna_rate_per_mbps' => 'nullable|numeric|min:0',
            'others_rate_per_mbps' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $code = $validated['code'] ?: ('SLAB-' . str_pad((TenantBandwidthPlan::where('tenant_id', $tenant->id)->max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT));

        $plan = TenantBandwidthPlan::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'code' => $code,
            'min_bandwidth_mbps' => (float) ($validated['min_bandwidth_mbps'] ?? 0),
            'max_bandwidth_mbps' => !empty($validated['max_bandwidth_mbps']) ? (float) $validated['max_bandwidth_mbps'] : null,
            'global_rate_per_mbps' => (float) $validated['global_rate_per_mbps'],
            'cdn_rate_per_mbps' => (float) ($validated['cdn_rate_per_mbps'] ?? 0),
            'bdix_rate_per_mbps' => (float) ($validated['bdix_rate_per_mbps'] ?? 0),
            'ggc_rate_per_mbps' => (float) ($validated['ggc_rate_per_mbps'] ?? 0),
            'fna_rate_per_mbps' => (float) ($validated['fna_rate_per_mbps'] ?? 0),
            'others_rate_per_mbps' => (float) ($validated['others_rate_per_mbps'] ?? 0),
            'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : true,
            'notes' => $validated['notes'] ?? null,
        ]);

        $msg = "Bandwidth Rate Slab '{$plan->name}' created successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'plan' => $plan,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth-plans')->with('success', $msg);
    }

    /**
     * Update existing Bandwidth Rate Plan.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $plan = TenantBandwidthPlan::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'min_bandwidth_mbps' => 'nullable|numeric|min:0',
            'max_bandwidth_mbps' => 'nullable|numeric|min:0',
            'global_rate_per_mbps' => 'required|numeric|min:0',
            'cdn_rate_per_mbps' => 'nullable|numeric|min:0',
            'bdix_rate_per_mbps' => 'nullable|numeric|min:0',
            'ggc_rate_per_mbps' => 'nullable|numeric|min:0',
            'fna_rate_per_mbps' => 'nullable|numeric|min:0',
            'others_rate_per_mbps' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $plan->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?: $plan->code,
            'min_bandwidth_mbps' => (float) ($validated['min_bandwidth_mbps'] ?? 0),
            'max_bandwidth_mbps' => !empty($validated['max_bandwidth_mbps']) ? (float) $validated['max_bandwidth_mbps'] : null,
            'global_rate_per_mbps' => (float) $validated['global_rate_per_mbps'],
            'cdn_rate_per_mbps' => (float) ($validated['cdn_rate_per_mbps'] ?? 0),
            'bdix_rate_per_mbps' => (float) ($validated['bdix_rate_per_mbps'] ?? 0),
            'ggc_rate_per_mbps' => (float) ($validated['ggc_rate_per_mbps'] ?? 0),
            'fna_rate_per_mbps' => (float) ($validated['fna_rate_per_mbps'] ?? 0),
            'others_rate_per_mbps' => (float) ($validated['others_rate_per_mbps'] ?? 0),
            'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : $plan->is_active,
            'notes' => $validated['notes'] ?? null,
        ]);

        $msg = "Bandwidth Rate Slab '{$plan->name}' updated successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'plan' => $plan,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth-plans')->with('success', $msg);
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $plan = TenantBandwidthPlan::where('tenant_id', $tenant->id)->findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        $stateText = $plan->is_active ? 'activated' : 'deactivated';
        $msg = "Rate Slab '{$plan->name}' is now {$stateText}.";

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $plan->is_active,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Destroy Bandwidth Rate Plan if not used.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $plan = TenantBandwidthPlan::where('tenant_id', $tenant->id)->findOrFail($id);

        $usedCount = TenantResellerBandwidth::where('bandwidth_plan_id', $plan->id)->count();
        if ($usedCount > 0) {
            $err = "Cannot delete '{$plan->name}' because it is currently assigned to {$usedCount} reseller(s).";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            return back()->with('error', $err);
        }

        $planName = $plan->name;
        $plan->delete();

        $msg = "Bandwidth Rate Slab '{$planName}' deleted successfully!";
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
