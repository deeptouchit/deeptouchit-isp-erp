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

        // 1. Primary Filter: Reseller / Territory Scoping
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

        // 2. Search Filter (Zone Name, Code, City, Sub-Areas, Assigned Staff, Partner)
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city_upazila', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('in_charge_name', 'like', "%{$search}%")
                  ->orWhere('in_charge_phone', 'like', "%{$search}%")
                  ->orWhereHas('assignedStaff', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('staff_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Primary Filter: Status
        $statusFilter = $request->input('status', 'all');
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        // 4. Secondary Filters (More Filters Drawer)
        $cityFilter = trim((string) $request->input('city_upazila', ''));
        if ($cityFilter !== '') {
            $query->where('city_upazila', $cityFilter);
        }

        $staffFilter = (string) $request->input('staff_id', '');
        if ($staffFilter === 'unassigned') {
            $query->whereNull('staff_id')->where(function ($sq) {
                $sq->whereNull('in_charge_name')->orWhere('in_charge_name', '');
            });
        } elseif ($staffFilter !== '' && is_numeric($staffFilter)) {
            $query->where('staff_id', (int)$staffFilter);
        }

        $subAreasFilter = (string) $request->input('has_sub_areas', '');
        if ($subAreasFilter === 'with_sub') {
            $query->whereNotNull('description')->where('description', '!=', '');
        } elseif ($subAreasFilter === 'without_sub') {
            $query->where(function ($sq) {
                $sq->whereNull('description')->orWhere('description', '');
            });
        }

        $occupancyFilter = (string) $request->input('occupancy', '');
        if ($occupancyFilter === 'populated') {
            $query->whereExists(function ($sub) use ($tenant) {
                $sub->select(DB::raw(1))
                    ->from('tenant_customers')
                    ->where('tenant_customers.tenant_id', $tenant->id)
                    ->where(function ($match) {
                        $match->whereColumn('tenant_customers.zone_id', 'tenant_coverage_zones.id')
                              ->orWhereColumn('tenant_customers.zone', 'tenant_coverage_zones.name');
                    });
            });
        } elseif ($occupancyFilter === 'empty') {
            $query->whereNotExists(function ($sub) use ($tenant) {
                $sub->select(DB::raw(1))
                    ->from('tenant_customers')
                    ->where('tenant_customers.tenant_id', $tenant->id)
                    ->where(function ($match) {
                        $match->whereColumn('tenant_customers.zone_id', 'tenant_coverage_zones.id')
                              ->orWhereColumn('tenant_customers.zone', 'tenant_coverage_zones.name');
                    });
            });
        }

        $allowedPerPage = [15, 20, 30, 50, 100, 250, 500];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $zones = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Batch-load subscriber counts & MRR for the current page in ONE query (eliminates N+1)
        $zoneMetrics = [];
        $pageZones = $zones->getCollection();
        if ($pageZones->isNotEmpty()) {
            $zoneIds = $pageZones->pluck('id')->all();
            $zoneNames = $pageZones->pluck('name')->filter()->unique()->values()->all();

            $matchingCustomers = TenantCustomer::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($zoneIds, $zoneNames) {
                    $q->whereIn('zone_id', $zoneIds);
                    if (!empty($zoneNames)) {
                        $q->orWhereIn('zone', $zoneNames);
                    }
                })
                ->select('zone_id', 'zone', 'status', 'monthly_bill')
                ->get();

            foreach ($pageZones as $pz) {
                $subCount = 0;
                $activeCount = 0;
                $mrrTotal = 0.0;
                foreach ($matchingCustomers as $mc) {
                    if ((int)$mc->zone_id === (int)$pz->id || ($mc->zone !== null && $mc->zone === $pz->name)) {
                        $subCount++;
                        if ($mc->status === 'active') {
                            $activeCount++;
                        }
                        $mrrTotal += (float)($mc->monthly_bill ?? 0);
                    }
                }
                $zoneMetrics[$pz->id] = [
                    'subscribers_count' => $subCount,
                    'active_subscribers' => $activeCount,
                    'mrr' => $mrrTotal,
                ];
            }
        }

        // Distinct cities for Tier-2 filter dropdown
        $distinctCitiesQuery = TenantCoverageZone::where('tenant_id', $tenant->id)
            ->whereNotNull('city_upazila')
            ->where('city_upazila', '!=', '');
        if ($isResellerUser) {
            $distinctCitiesQuery->where('reseller_id', $userResellerId);
        }
        $distinctCities = $distinctCitiesQuery->distinct()->orderBy('city_upazila')->pluck('city_upazila');

        // 5. Compute 6 KPI Stats Scoped to Reseller/Tenant cleanly
        $kpiBaseZonesQuery = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $kpiBaseZonesQuery->where('reseller_id', $userResellerId);
        } elseif ($selectedResellerId === 'isp') {
            $kpiBaseZonesQuery->whereNull('reseller_id');
        } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
            $kpiBaseZonesQuery->where('reseller_id', (int)$selectedResellerId);
        }

        $kpiStats = (clone $kpiBaseZonesQuery)
            ->selectRaw('COUNT(*) as total_zones')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_zones')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_zones')
            ->selectRaw("SUM(CASE WHEN staff_id IS NOT NULL OR (in_charge_name IS NOT NULL AND TRIM(in_charge_name) != '') THEN 1 ELSE 0 END) as assigned_staff_count")
            ->first();

        $totalZones = (int) ($kpiStats->total_zones ?? 0);
        $activeZones = (int) ($kpiStats->active_zones ?? 0);
        $inactiveZones = (int) ($kpiStats->inactive_zones ?? 0);
        $assignedStaffCount = (int) ($kpiStats->assigned_staff_count ?? 0);

        // Subscribers query scoped
        $custQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $custQuery->where('reseller_id', $userResellerId);
        } elseif ($selectedResellerId === 'isp') {
            $custQuery->whereNull('reseller_id');
        } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
            $custQuery->where('reseller_id', (int)$selectedResellerId);
        }

        $totalSubscribersAssigned = (clone $custQuery)->where(function ($q) {
            $q->whereNotNull('zone_id')
              ->orWhere(function ($sub) {
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

        $topZoneName = $topZoneData ? ($topZoneData->zone . ' (' . $topZoneData->total . ')') : __('None');

        return view('tenant.customers.zones', compact(
            'tenant',
            'zones',
            'zoneMetrics',
            'allResellers',
            'allStaff',
            'staffListJson',
            'distinctCities',
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
            'cityFilter',
            'staffFilter',
            'subAreasFilter',
            'occupancyFilter',
            'perPage'
        ));
    }

    /**
     * Resolve Coverage Zone within tenant & reseller boundaries
     */
    protected function findScopedZone(Tenant $tenant, $id): TenantCoverageZone
    {
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));

        $query = TenantCoverageZone::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $query->where('reseller_id', $user->reseller_id);
        }

        return $query->findOrFail($id);
    }

    /**
     * Validate and normalize tenant-scoped reseller & staff attributes for store/update
     */
    protected function prepareZoneAttributes(Tenant $tenant, array $validated): array
    {
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));

        if ($isResellerUser) {
            $validated['reseller_id'] = $user->reseller_id;
        } elseif (!empty($validated['reseller_id'])) {
            $reseller = TenantReseller::where('tenant_id', $tenant->id)->find($validated['reseller_id']);
            $validated['reseller_id'] = $reseller?->id;
        } else {
            $validated['reseller_id'] = null;
        }

        if (!empty($validated['staff_id'])) {
            $assignedStaff = User::where('tenant_id', $tenant->id)->find($validated['staff_id']);
            if ($assignedStaff) {
                $validated['staff_id'] = $assignedStaff->id;
                $validated['in_charge_name'] = $assignedStaff->name;
                if (empty($validated['in_charge_phone'])) {
                    $validated['in_charge_phone'] = $assignedStaff->phone;
                }
            } else {
                $validated['staff_id'] = null;
            }
        } else {
            $validated['staff_id'] = null;
        }

        return $validated;
    }

    /**
     * Store new Coverage Zone
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();

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

        $validated = $this->prepareZoneAttributes($tenant, $validated);

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
            $exists = TenantCoverageZone::where('tenant_id', $tenant->id)
                ->where('code', $validated['code'])
                ->exists();
            if ($exists) {
                $errMsg = __('Zone Code is already in use.');
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 422);
                }
                return back()->withErrors(['code' => $errMsg])->withInput();
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

        $msg = __("Coverage Zone ':name' created successfully.", ['name' => $zone->name]);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'zone' => $zone
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', $msg);
    }

    /**
     * Update Coverage Zone
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $zone = $this->findScopedZone($tenant, $id);

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

        $validated = $this->prepareZoneAttributes($tenant, $validated);

        // Code uniqueness check
        $codeExists = TenantCoverageZone::where('tenant_id', $tenant->id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $zone->id)
            ->exists();

        if ($codeExists) {
            $errMsg = __('Zone Code is already taken by another area.');
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->withErrors(['code' => $errMsg])->withInput();
        }

        $oldName = $zone->name;
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : $zone->is_active;

        $zone->update($validated);

        if ($oldName !== $zone->name) {
            TenantCustomer::where('tenant_id', $tenant->id)
                ->where('zone_id', $zone->id)
                ->update(['zone' => $zone->name]);
        }

        $msg = __("Coverage Zone ':name' updated successfully.", ['name' => $zone->name]);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'zone' => $zone
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', $msg);
    }

    /**
     * Toggle Coverage Zone status (Strict adherence to AGENTS.md rule 5)
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $zone = $this->findScopedZone($tenant, $id);

        $zone->is_active = !$zone->is_active;
        $zone->save();

        $msg = $zone->is_active
            ? __("Coverage Zone ':name' is now enabled.", ['name' => $zone->name])
            : __("Coverage Zone ':name' is now disabled.", ['name' => $zone->name]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $zone->is_active,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete Coverage Zone
     */
    public function destroy(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $zone = $this->findScopedZone($tenant, $id);

        $subscriberCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($zone) {
                $q->where('zone_id', $zone->id)
                  ->orWhere('zone', $zone->name);
            })
            ->count();

        if ($subscriberCount > 0) {
            $msg = __("Cannot delete zone ':name' because :count subscribers are assigned to it.", [
                'name' => $zone->name,
                'count' => $subscriberCount,
            ]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $zoneName = $zone->name;
        $zone->delete();

        $msg = __("Coverage Zone ':name' deleted successfully.", ['name' => $zoneName]);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.customers.zones')->with('success', $msg);
    }

    /**
     * Download sample CSV template for Coverage Zones & Areas
     */
    public function downloadSampleTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="zones_and_sub_areas_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Zone Name', 'Code', 'Reseller Code', 'City / Upazila', 
            'Sub Areas', 'In Charge Name', 'In Charge Phone', 'Status'
        ];

        $sampleRows = [
            ['গড়েরবাড়ী', 'ZN-001', 'RES-004', 'গাবতলি, বগুড়া', 'পশ্চিমপাড়া, পূর্বপাড়া, গুচ্ছগ্রাম', '', '', 'active'],
            ['বটিয়াভাঙ্গা', 'ZN-014', 'RES-001', 'গাবতলি, বগুড়া', 'কৃতনিয়া, উত্তরপাড়া, চেয়ারম্যান পাড়া', '', '', 'active'],
            ['বেতুয়ারকান্দি', 'ZN-016', 'RES-002', 'গাবতলি, বগুড়া', 'দহপাড়া, চারমাথা', '', '', 'active'],
            ['ডোমকান্দি', 'ZN-019', '', 'গাবতলি, বগুড়া', 'প্রধান বাজার', '', '', 'active'],
        ];

        $callback = function () use ($columns, $sampleRows) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel
            fputcsv($file, $columns);
            foreach ($sampleRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process Bulk Coverage Zones / Areas Import from CSV or Excel file
     */
    public function importProcess(Request $request)
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $request->validate([
            'file' => 'required|file|max:10240',
            'duplicate_action' => 'nullable|string|in:skip,update',
            'default_reseller_id' => 'nullable',
            'default_city_upazila' => 'nullable|string|max:150',
            'default_status' => 'nullable|string|in:active,inactive',
        ]);

        $file = $request->file('file');
        $duplicateAction = $request->input('duplicate_action', 'skip');
        $defaultResellerId = $isResellerUser ? $userResellerId : $request->input('default_reseller_id');
        if ($defaultResellerId === 'isp' || $defaultResellerId === '' || $defaultResellerId === 'null') {
            $defaultResellerId = null;
        } elseif ($defaultResellerId) {
            $defaultResellerId = (int) $defaultResellerId;
        }

        $defaultCity = $request->input('default_city_upazila', '');
        $defaultStatus = $request->input('default_status', 'active') === 'inactive' ? false : true;

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            $errMsg = __('Unable to read uploaded file.');
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            $errMsg = __('The uploaded file is empty.');
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }

        // Normalize headers
        $cleanHeaders = array_map(function($h) {
            $h = strtolower(trim((string)$h));
            return preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '/', '-', '(', ')', '%'], '_', $h));
        }, $headerRow);

        // Preload resellers for this tenant for fast lookup
        $resellers = TenantReseller::where('tenant_id', $tenant->id)->get();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($cleanHeaders as $idx => $key) {
                    $data[$key] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }

                // 1. Zone / Area Name
                $name = '';
                foreach ($data as $k => $v) {
                    if ((str_contains($k, 'name') || str_contains($k, 'zone') || str_contains($k, 'area')) && !str_contains($k, 'reseller') && !str_contains($k, 'charge') && !str_contains($k, 'sub') && !empty($v)) {
                        $name = $v;
                        break;
                    }
                }
                if (empty($name)) {
                    $name = $data['name'] ?? ($data['zone_name'] ?? ($data['area_name'] ?? ''));
                }

                if (empty($name)) {
                    $skipped++;
                    continue;
                }

                // 2. Zone Code
                $code = '';
                foreach ($data as $k => $v) {
                    if ((str_starts_with($k, 'code') || str_contains($k, 'zone_code') || str_contains($k, 'area_code')) && !str_contains($k, 'reseller') && !empty($v)) {
                        $code = strtoupper($v);
                        break;
                    }
                }

                // 3. Reseller mapping
                $resellerId = $defaultResellerId;
                if (!$isResellerUser) {
                    $resellerVal = '';
                    foreach ($data as $k => $v) {
                        if (str_contains($k, 'reseller') && !empty($v)) {
                            $resellerVal = trim($v);
                            break;
                        }
                    }

                    if (!empty($resellerVal)) {
                        if (in_array(strtolower($resellerVal), ['isp', 'direct', 'hq', 'headquarters', 'null', 'none'])) {
                            $resellerId = null;
                        } else {
                            $matched = $resellers->first(function($r) use ($resellerVal) {
                                return strcasecmp($r->code, $resellerVal) === 0
                                    || strcasecmp($r->name, $resellerVal) === 0
                                    || str_contains(strtolower($r->name), strtolower($resellerVal))
                                    || (string)$r->id === (string)$resellerVal;
                            });
                            if ($matched) {
                                $resellerId = $matched->id;
                            }
                        }
                    }
                }

                // 4. City / Upazila
                $city = $defaultCity;
                foreach ($data as $k => $v) {
                    if ((str_contains($k, 'city') || str_contains($k, 'upazila') || str_contains($k, 'thana') || str_contains($k, 'district')) && !empty($v)) {
                        $city = $v;
                        break;
                    }
                }

                // 5. Sub Areas / Description
                $subAreas = '';
                foreach ($data as $k => $v) {
                    if ((str_contains($k, 'sub') || str_contains($k, 'para') || str_contains($k, 'desc') || str_contains($k, 'note') || str_contains($k, 'detail')) && !empty($v)) {
                        $subAreas = $v;
                        break;
                    }
                }

                // 6. In-Charge Name & Phone
                $inChargeName = '';
                $inChargePhone = '';
                foreach ($data as $k => $v) {
                    if ((str_contains($k, 'charge_name') || str_contains($k, 'contact') || str_contains($k, 'manager')) && !empty($v)) {
                        $inChargeName = $v;
                    }
                    if ((str_contains($k, 'charge_phone') || str_contains($k, 'phone') || str_contains($k, 'mobile')) && !empty($v)) {
                        $inChargePhone = $v;
                    }
                }

                // 7. Status
                $isActive = $defaultStatus;
                foreach ($data as $k => $v) {
                    if (str_starts_with($k, 'status') && !empty($v)) {
                        $st = strtolower(trim($v));
                        if (in_array($st, ['active', '1', 'true', 'enabled'])) {
                            $isActive = true;
                        } elseif (in_array($st, ['inactive', '0', 'false', 'disabled'])) {
                            $isActive = false;
                        }
                        break;
                    }
                }

                // Auto-generate code if empty
                if (empty($code)) {
                    $prefix = $resellerId ? ('ZN-R' . $resellerId . '-') : 'ZN-';
                    $count = TenantCoverageZone::where('tenant_id', $tenant->id)->count() + $imported + 1;
                    $code = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
                    while (TenantCoverageZone::where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
                        $count++;
                        $code = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
                    }
                }

                // Check duplicate by (name + reseller_id) OR code
                $existing = TenantCoverageZone::where('tenant_id', $tenant->id)
                    ->where(function($q) use ($name, $code, $resellerId) {
                        $q->where('code', $code)
                          ->orWhere(function($subQ) use ($name, $resellerId) {
                              $subQ->where('name', $name);
                              if ($resellerId) {
                                  $subQ->where('reseller_id', $resellerId);
                              } else {
                                  $subQ->whereNull('reseller_id');
                              }
                          });
                    })
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'update') {
                        $existing->update([
                            'name' => $name,
                            'reseller_id' => $resellerId,
                            'city_upazila' => $city ?: $existing->city_upazila,
                            'description' => $subAreas ?: $existing->description,
                            'in_charge_name' => $inChargeName ?: $existing->in_charge_name,
                            'in_charge_phone' => $inChargePhone ?: $existing->in_charge_phone,
                            'is_active' => $isActive,
                        ]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    continue;
                }

                $createdZone = TenantCoverageZone::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $resellerId,
                    'code' => $code,
                    'name' => $name,
                    'city_upazila' => $city,
                    'description' => $subAreas,
                    'in_charge_name' => $inChargeName,
                    'in_charge_phone' => $inChargePhone,
                    'is_active' => $isActive,
                ]);

                $syncQuery = TenantCustomer::where('tenant_id', $tenant->id)
                    ->where('zone', $name)
                    ->whereNull('zone_id');
                if ($createdZone->reseller_id) {
                    $syncQuery->where('reseller_id', $createdZone->reseller_id);
                }
                $syncQuery->update(['zone_id' => $createdZone->id]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            $msg = __('Import completed! Imported :count coverage zones.', ['count' => $imported]);
            if ($updated > 0) {
                $msg .= ' ' . __('Updated :count existing.', ['count' => $updated]);
            }
            if ($skipped > 0) {
                $msg .= ' ' . __('Skipped :count duplicates/empty.', ['count' => $skipped]);
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                ]);
            }

            return redirect()->route('tenant.customers.zones')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }

            $errMsg = __('Import failed: :error', ['error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errMsg,
                ], 500);
            }

            return back()->with('error', $errMsg);
        }
    }

    /**
     * Update Sub-Areas of a Coverage Zone
     */
    public function updateSubAreas(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $zone = $this->findScopedZone($tenant, $id);

        $subAreas = $request->input('sub_areas');
        if (is_array($subAreas)) {
            $clean = array_values(array_filter(array_map('trim', $subAreas)));
            $zone->description = implode(', ', $clean);
        } else {
            $zone->description = trim((string)$subAreas);
        }
        $zone->save();

        $msg = __("Sub-areas for ':name' updated successfully.", ['name' => $zone->name]);
        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'sub_areas' => $zone->sub_areas_list,
                'description' => $zone->description,
            ]);
        }

        return back()->with('success', $msg);
    }
}
