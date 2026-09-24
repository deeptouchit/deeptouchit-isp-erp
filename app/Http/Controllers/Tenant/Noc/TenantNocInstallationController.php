<?php

namespace App\Http\Controllers\Tenant\Noc;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantInternetPackage;
use App\Models\TenantCoverageZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantNocInstallationController extends Controller
{
    /**
     * Resolve active Tenant.
     */
    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }
        return $tenant;
    }

    /**
     * Display Pending New Installations Queue.
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;

        $search = $request->input('search');
        $zoneFilter = $request->input('zone_id');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->whereNull('reseller_id')
            ->with(['package', 'coverageZone']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($zoneFilter && $zoneFilter !== 'all') {
            $query->where('zone_id', $zoneFilter);
        }

        $installations = $query->latest('id')->paginate($perPage)->withQueryString();

        // 6 Summary Metric Cards (AGENTS.md Rule 2.B)
        $baseQuery = TenantCustomer::where('tenant_id', $tenantId)->whereNull('reseller_id');
        $stats = [
            'pending_installations' => (clone $baseQuery)->where('status', 'inactive')->orWhereNull('online_status')->count(),
            'active_lines' => (clone $baseQuery)->where('status', 'active')->count(),
            'total_subscribers' => (clone $baseQuery)->count(),
            'new_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'zones_count' => TenantCoverageZone::where('tenant_id', $tenantId)->count(),
            'packages_count' => TenantInternetPackage::where('tenant_id', $tenantId)->count(),
        ];

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->get();
        $currencySymbol = '৳';

        return view('tenant.noc.installations', compact(
            'tenant',
            'installations',
            'zones',
            'stats',
            'search',
            'zoneFilter',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Activate Line on Field & Provision ONU / Router MAC.
     */
    public function activate(Request $request, $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $request->validate([
            'onu_mac_sn' => 'nullable|string|max:50',
            'fiber_route_info' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:50',
        ]);

        if ($request->filled('onu_mac_sn')) {
            $customer->onu_mac_sn = $request->onu_mac_sn;
        }
        if ($request->filled('fiber_route_info')) {
            $customer->fiber_route_info = $request->fiber_route_info;
        }
        if ($request->filled('ip_address')) {
            $customer->ip_address = $request->ip_address;
        }

        $customer->status = 'active';
        $customer->online_status = 'online';
        $customer->save();

        $msg = "গ্রাহক '{$customer->username}' এর নতুন সংযোগ সফলভাবে একটিভ ও প্রভিশন করা হয়েছে!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'customer' => $customer,
            ]);
        }

        return back()->with('success', $msg);
    }
}
