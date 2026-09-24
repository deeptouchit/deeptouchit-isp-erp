<?php

namespace App\Http\Controllers\Tenant\Customer;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantCoverageZoneController extends Controller
{
    /**
     * Get active tenant
     */
    protected function getTenant(): Tenant
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }

        return $tenant ?? abort(404, 'No active tenant found.');
    }

    /**
     * Display listing of Coverage Zones & Areas
     */
    public function index(Request $request)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // Query active staff for technician / staff assignment
        $staffQuery = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->where('status', 'active');

        if ($isResellerUser) {
            $staffQuery->where('reseller_id', $userResellerId);
        }

        $allStaff = $staffQuery->orderBy('name')->get();
        $staffListJson = $allStaff->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone ?? '',
                'staff_id' => $s->staff_id ?? '',
                'role' => $s->role,
                'role_label' => $s->role_badge['label'] ?? ucfirst(str_replace('_', ' ', $s->role)),
                'reseller_id' => $s->reseller_id ? (string)$s->reseller_id : '',
            ];
        })->values();

        $query = TenantCoverageZone::where('tenant_id', $tenant->id)
            ->with(['reseller', 'assignedStaff']);

        // 1. Reseller Scoping
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
            $selectedResellerId = (string)$userResellerId;
        } else {
            $selectedResellerId = $request->input('reseller_id', 'all');
            if ($selectedResellerId === 'isp') {
                $query->whereNull('reseller_id');
            } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
                $query->where('reseller_id', (int)$selectedResellerId);
            }
        }

        // 2. Search filter
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city_upazila', 'like', "%{$search}%")
                  ->orWhere('in_charge_name', 'like', "%{$search}%")
                  ->orWhere('in_charge_phone', 'like', "%{$search}%")
                  ->orWhereHas('assignedStaff', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('staff_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller', function($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Status filter
        $statusFilter = $request->input('status', 'all');
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $perPage = (int) $request->input('per_page', 20);
        $zones = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // 4. Compute 6 KPI Stats Scoped to Reseller/Tenant
        $kpiBaseZonesQuery = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $kpiBaseZonesQuery->where('reseller_id', $userResellerId);
        } elseif ($selectedResellerId === 'isp') {
            $kpiBaseZonesQuery->whereNull('reseller_id');
        } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
            $kpiBaseZonesQuery->where('reseller_id', (int)$selectedResellerId);
        }

        $allZones = $kpiBaseZonesQuery->get();
        $totalZones = $allZones->count();
        $activeZones = $allZones->where('is_active', true)->count();
        $inactiveZones = $allZones->where('is_active', false)->count();

        // Subscribers query scoped
        $custQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $custQuery->where('reseller_id', $userResellerId);
        } elseif ($selectedResellerId === 'isp') {
            $custQuery->whereNull('reseller_id');
        } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
            $custQuery->where('reseller_id', (int)$selectedResellerId);
        }

        $totalSubscribersAssigned = (clone $custQuery)->where(function($q) {
            $q->whereNotNull('zone_id')
              ->orWhere(function($sub) {
                  $sub->whereNotNull('zone')->where('zone', '!=', '');
              });
        })->count();

        // Highest density zone
        $topZoneData = (clone $custQuery)->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->select('zone', DB::raw('count(*) as total'))
            ->groupBy('zone')
            ->orderByDesc('total')
            ->first();

        $topZoneName = $topZoneData ? ($topZoneData->zone . " (" . $topZoneData->total . ")") : 'None';

        // Assigned Staff Count (zones with assigned staff / contact person)
        $assignedStaffCount = $allZones->filter(fn($z) => !empty($z->staff_id) || !empty(trim($z->in_charge_name ?? '')))->count();

        return view('tenant.customers.zones', compact(
            'tenant',
            'zones',
            'allResellers',
            'allStaff',
            'staffListJson',
            'isResellerUser',
            'selectedResellerId',
            'totalZones',
            'activeZones',
            'inactiveZones',
            'totalSubscribersAssigned',
            'topZoneName',
            'assignedStaffCount',
            'search',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Store new Coverage Zone
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'staff_id' => 'nullable|exists:users,id',
            'city_upazila' => 'nullable|string|max:150',
            'in_charge_name' => 'nullable|string|max:150',
            'in_charge_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($isResellerUser) {
            $validated['reseller_id'] = $userResellerId;
        }

        // If staff_id is assigned, link staff name & phone
        if (!empty($validated['staff_id'])) {
            $assignedStaff = User::where('tenant_id', $tenant->id)->find($validated['staff_id']);
            if ($assignedStaff) {
                $validated['in_charge_name'] = $assignedStaff->name;
                if (empty($validated['in_charge_phone'])) {
                    $validated['in_charge_phone'] = $assignedStaff->phone;
                }
            }
        }

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $prefix = $validated['reseller_id'] ? ('ZN-R' . $validated['reseller_id'] . '-') : 'ZN-';
            $count = TenantCoverageZone::where('tenant_id', $tenant->id)->count() + 1;
            $code = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
            while (TenantCoverageZone::where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
                $count++;
                $code = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
            $validated['code'] = $code;
        } else {
            // Check code uniqueness within tenant
            $exists = TenantCoverageZone::where('tenant_id', $tenant->id)
                ->where('code', $validated['code'])
                ->exists();
            if ($exists) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Zone Code is already in use.'], 422);
                }
                return back()->withErrors(['code' => 'Zone Code is already in use.'])->withInput();
            }
        }

        $validated['tenant_id'] = $tenant->id;
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        $zone = TenantCoverageZone::create($validated);

        // Sync existing customers matching this zone name
        $custSyncQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('zone', $zone->name)
            ->whereNull('zone_id');
        if ($zone->reseller_id) {
            $custSyncQuery->where('reseller_id', $zone->reseller_id);
        }
        $custSyncQuery->update(['zone_id' => $zone->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Coverage Zone '{$zone->name}' created successfully.",
                'zone' => $zone
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', "Coverage Zone '{$zone->name}' created successfully.");
    }

    /**
     * Update Coverage Zone
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $zoneQuery = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $zoneQuery->where('reseller_id', $userResellerId);
        }
        $zone = $zoneQuery->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'staff_id' => 'nullable|exists:users,id',
            'city_upazila' => 'nullable|string|max:150',
            'in_charge_name' => 'nullable|string|max:150',
            'in_charge_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($isResellerUser) {
            $validated['reseller_id'] = $userResellerId;
        }

        // If staff_id is assigned, update staff name & phone
        if (!empty($validated['staff_id'])) {
            $assignedStaff = User::where('tenant_id', $tenant->id)->find($validated['staff_id']);
            if ($assignedStaff) {
                $validated['in_charge_name'] = $assignedStaff->name;
                if (empty($validated['in_charge_phone'])) {
                    $validated['in_charge_phone'] = $assignedStaff->phone;
                }
            }
        } else {
            $validated['staff_id'] = null;
        }

        // Code uniqueness check
        $codeExists = TenantCoverageZone::where('tenant_id', $tenant->id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $zone->id)
            ->exists();

        if ($codeExists) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Zone Code is already taken by another area.'], 422);
            }
            return back()->withErrors(['code' => 'Zone Code is already taken.'])->withInput();
        }

        $oldName = $zone->name;
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : $zone->is_active;

        $zone->update($validated);

        // If name changed, synchronize customers table
        if ($oldName !== $zone->name) {
            TenantCustomer::where('tenant_id', $tenant->id)
                ->where('zone_id', $zone->id)
                ->update(['zone' => $zone->name]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Coverage Zone '{$zone->name}' updated successfully.",
                'zone' => $zone
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', "Coverage Zone '{$zone->name}' updated successfully.");
    }

    /**
     * Toggle Coverage Zone status (Strict adherence to AGENTS.md rule 5)
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $zoneQuery = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $zoneQuery->where('reseller_id', $userResellerId);
        }
        $zone = $zoneQuery->findOrFail($id);

        $zone->is_active = !$zone->is_active;
        $zone->save();

        $stateText = $zone->is_active ? 'enabled' : 'disabled';

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $zone->is_active,
                'message' => "Coverage Zone '{$zone->name}' is now {$stateText}.",
            ]);
        }

        return back()->with('success', "Coverage Zone '{$zone->name}' has been {$stateText} successfully.");
    }

    /**
     * Delete Coverage Zone
     */
    public function destroy(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $zoneQuery = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $zoneQuery->where('reseller_id', $userResellerId);
        }
        $zone = $zoneQuery->findOrFail($id);

        $subscriberCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->where(function($q) use ($zone) {
                $q->where('zone_id', $zone->id)
                  ->orWhere('zone', $zone->name);
            })
            ->count();

        if ($subscriberCount > 0) {
            $msg = "Cannot delete zone '{$zone->name}' because {$subscriberCount} active subscribers are currently assigned to it.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $zoneName = $zone->name;
        $zone->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Coverage Zone '{$zoneName}' deleted successfully."
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', "Coverage Zone '{$zoneName}' deleted successfully.");
    }
}
