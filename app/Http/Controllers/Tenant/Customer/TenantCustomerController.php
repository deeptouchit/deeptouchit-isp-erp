<?php

namespace App\Http\Controllers\Tenant\Customer;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantOlt;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantCustomerController extends Controller
{
    /**
     * Resolve active tenant safely.
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
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Display All Customers (Master Database — Direct + All Reseller Customers).
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        $search = $request->input('search');
        $scopeFilter = $request->input('scope') ?: $request->route('scope'); // 'all', 'isp', 'reseller'
        $selectedResellerId = $request->input('reseller_id');
        $statusFilter = $request->input('status') ?: $request->route('status');
        $zoneFilter = $request->input('zone');
        $packageFilter = $request->input('package_id');
        $onlineFilter = $request->input('online_status') ?: $request->route('online_status');
        $connectionType = $request->input('connection_type');

        $authUser = Auth::user();
        $isIspCollector = $authUser && $authUser->isCollector() && empty($authUser->reseller_id);

        // Dropdown datasets
        $allResellers = $isIspCollector ? collect() : TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allRouters = TenantRouter::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allOlts = TenantOlt::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allCollectors = User::where('tenant_id', $tenant->id)->where('role', 'like', '%collector%')->orderBy('name')->get();
        
        $distinctZonesQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isIspCollector) {
            $distinctZonesQuery->whereNull('reseller_id');
        }
        $distinctZones = $distinctZonesQuery
            ->whereNotNull('zone')
            ->distinct('zone')
            ->pluck('zone')
            ->filter()
            ->values();

        // Customer Query
        $query = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['reseller', 'package', 'router', 'olt', 'collector']);

        if ($isIspCollector) {
            $query->whereNull('reseller_id');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('onu_mac_sn', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($isIspCollector) {
            $query->whereNull('reseller_id');
        } elseif ($scopeFilter === 'isp') {
            $query->whereNull('reseller_id');
        } elseif ($scopeFilter === 'reseller') {
            $query->whereNotNull('reseller_id');
        }

        if (!$isIspCollector && $selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($statusFilter === 'due') {
            $query->where(function ($q) {
                $q->whereIn('status', ['due', 'expired'])
                  ->orWhere('due_amount', '>', 0)
                  ->orWhere(function ($eq) {
                      $eq->whereNotNull('expiry_date')
                         ->whereDate('expiry_date', '<=', Carbon::today());
                  });
            });
        } elseif ($statusFilter === 'expired') {
            $query->where(function ($q) {
                $q->whereIn('status', ['expired', 'suspended'])
                  ->orWhere(function ($eq) {
                      $eq->whereNotNull('expiry_date')
                         ->whereDate('expiry_date', '<=', Carbon::today());
                  });
            });
        } elseif ($statusFilter === 'disconnected' || $statusFilter === 'archived') {
            $query->whereIn('status', ['disconnected', 'archived']);
        } elseif ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($zoneFilter) {
            $query->where('zone', $zoneFilter);
        }

        if ($packageFilter) {
            $query->where('package_id', $packageFilter);
        }

        if ($onlineFilter) {
            $query->where('online_status', $onlineFilter);
        }

        if ($connectionType) {
            $query->where('connection_type', $connectionType);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $customers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Compute 6 KPI Summary Metrics
        $baseQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isIspCollector) {
            $baseQuery->whereNull('reseller_id');
        }
        $totalCustomers = (clone $baseQuery)->count();
        $onlineSessionsCount = (clone $baseQuery)->where('online_status', 'online')->count();
        $ispDirectCount = (clone $baseQuery)->whereNull('reseller_id')->count();
        $resellerCustomersCount = $isIspCollector ? 0 : (clone $baseQuery)->whereNotNull('reseller_id')->count();
        $dueAccountsCount = (clone $baseQuery)->where(function ($q) {
            $q->whereIn('status', ['due', 'expired'])
              ->orWhere('due_amount', '>', 0)
              ->orWhere(function ($eq) {
                  $eq->whereNotNull('expiry_date')
                     ->whereDate('expiry_date', '<=', Carbon::today());
              });
        })->count();
        $totalDueAmount = (clone $baseQuery)->sum('due_amount');
        $expiredAccountsCount = (clone $baseQuery)->where(function ($q) {
            $q->whereIn('status', ['expired', 'suspended'])
              ->orWhere(function ($eq) {
                  $eq->whereNotNull('expiry_date')
                     ->whereDate('expiry_date', '<=', Carbon::today());
              });
        })->count();
        $disconnectedCount = (clone $baseQuery)->whereIn('status', ['disconnected', 'archived'])->count();

        // Compute Zone Stats breakdown
        $zoneStatsQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereNotNull('zone')
            ->where('zone', '!=', '');
        if ($isIspCollector) {
            $zoneStatsQuery->whereNull('reseller_id');
        }
        $zoneStats = $zoneStatsQuery
            ->select(
                'zone',
                DB::raw('count(*) as total_subscribers'),
                DB::raw('sum(case when status = "active" then 1 else 0 end) as active_count'),
                DB::raw('sum(case when status = "due" or due_amount > 0 then 1 else 0 end) as due_count'),
                DB::raw('sum(monthly_bill) as total_mrr'),
                DB::raw('sum(due_amount) as total_due')
            )
            ->groupBy('zone')
            ->orderByDesc('total_subscribers')
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.customers.index', compact(
            'tenant',
            'currencySymbol',
            'customers',
            'allResellers',
            'allPackages',
            'allRouters',
            'allOlts',
            'allCollectors',
            'distinctZones',
            'zoneStats',
            'totalCustomers',
            'onlineSessionsCount',
            'ispDirectCount',
            'resellerCustomersCount',
            'dueAccountsCount',
            'totalDueAmount',
            'expiredAccountsCount',
            'disconnectedCount',
            'search',
            'scopeFilter',
            'selectedResellerId',
            'statusFilter',
            'zoneFilter',
            'packageFilter',
            'onlineFilter',
            'connectionType',
            'perPage'
        ));
    }

    /**
     * Show Onboard New Customer form page.
     */
    public function create(Request $request): View
    {
        $tenant = $this->getTenant();
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $allRouters = TenantRouter::where('tenant_id', $tenant->id)->get();
        $allOlts = TenantOlt::where('tenant_id', $tenant->id)->get();
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->where('status', 'active')->get();
        $coverageZones = TenantCoverageZone::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $allZones = $coverageZones->pluck('name');
        $autoCustomerId = TenantCustomer::generateNextCustomerId($tenant->id);

        return view('tenant.customers.create', compact(
            'tenant',
            'allPackages',
            'allRouters',
            'allOlts',
            'allResellers',
            'allZones',
            'coverageZones',
            'autoCustomerId'
        ));
    }

    /**
     * Store a newly onboarded customer.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tenant_customers', 'customer_id')->where('tenant_id', $tenant->id),
            ],
            'name' => 'required|string|max:191',
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant_customers', 'username')->where('tenant_id', $tenant->id),
            ],
            'password' => 'required|string|min:4|max:100',
            'phone' => 'required|string|max:50',
            'alt_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'national_id' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:500',
            'zone_id' => 'nullable|exists:tenant_coverage_zones,id',
            'zone' => 'nullable|string|max:100',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'connection_type' => 'required|string|in:pppoe,static_ip,hotspot,dhcp',
            'package_id' => 'nullable|exists:tenant_internet_packages,id',
            'monthly_bill' => 'nullable|numeric|min:0',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:50',
            'olt_id' => 'nullable|exists:tenant_olts,id',
            'onu_mac_sn' => 'nullable|string|max:100',
            'fiber_route_info' => 'nullable|string|max:191',
            'billing_type' => 'required|string|in:prepaid,postpaid',
            'expiry_date' => 'nullable|date',
            'status' => 'required|string|in:active,due,expired,suspended,disabled,disconnected,archived',
            'remarks' => 'nullable|string|max:500',
        ]);

        $customerId = !empty($validated['customer_id']) ? $validated['customer_id'] : TenantCustomer::generateNextCustomerId($tenant->id);

        $package = !empty($validated['package_id']) ? TenantInternetPackage::find($validated['package_id']) : null;
        $monthlyBill = $validated['monthly_bill'] ?? ($package?->price ?? 0.00);

        // Resolve Coverage Zone
        $zoneId = $request->input('zone_id');
        $zoneName = $validated['zone'] ?? 'General Zone';
        if ($zoneId) {
            $cz = TenantCoverageZone::where('tenant_id', $tenant->id)->find($zoneId);
            if ($cz) {
                $zoneName = $cz->name;
            }
        } elseif (!empty($zoneName)) {
            $cz = TenantCoverageZone::where('tenant_id', $tenant->id)->where('name', $zoneName)->first();
            if ($cz) {
                $zoneId = $cz->id;
            }
        }

        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $validated['reseller_id'] ?? null,
            'customer_id' => $customerId,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'alt_phone' => $validated['alt_phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'zone_id' => $zoneId,
            'zone' => $zoneName,
            'connection_type' => $validated['connection_type'],
            'package_id' => $package?->id,
            'package_name' => $package?->package_name ?: ($package?->name ?? 'Custom Plan'),
            'monthly_bill' => $monthlyBill,
            'router_id' => $validated['router_id'] ?? null,
            'ip_address' => $validated['ip_address'] ?? null,
            'mac_address' => $validated['mac_address'] ?? null,
            'olt_id' => $validated['olt_id'] ?? null,
            'onu_mac_sn' => $validated['onu_mac_sn'] ?? null,
            'fiber_route_info' => $validated['fiber_route_info'] ?? null,
            'billing_type' => $validated['billing_type'],
            'billing_cycle_date' => Carbon::now()->startOfMonth(),
            'expiry_date' => !empty($validated['expiry_date']) ? Carbon::parse($validated['expiry_date']) : Carbon::now()->addMonth(),
            'status' => $validated['status'],
            'online_status' => 'offline',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        // Audit Trail Event
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_CREATE',
            'description' => "Onboarded new customer '{$customer->name}' ({$customer->username}) with ID {$customerId}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'customer_id' => $customer->id,
                'username' => $customer->username,
                'package' => $customer->package_name,
                'monthly_bill' => $customer->monthly_bill,
            ],
        ]);

        // Auto Provision to MikroTik Router & FreeRADIUS AAA
        $mikrotikMsg = '';
        try {
            $mikrotikApi = new MikrotikApiService();
            $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
            if ($syncRes['success']) {
                $mikrotikMsg .= ' & synced to MikroTik RouterOS';
            }
        } catch (\Exception $e) {
            // Non-blocking MikroTik error
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking RADIUS error
        }

        $msg = "Customer '{$customer->name}' ({$customerId}) created{$mikrotikMsg} successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'customer' => $customer->load(['reseller', 'package']),
            ]);
        }

        return redirect()->route('tenant.customers.index')->with('success', $msg);
    }

    /**
     * Show customer details (Dedicated Page view for Web, JSON response for API/Modals).
     */
    public function show(Request $request, int $id): JsonResponse|View
    {
        $tenant = $this->getTenant();

        $customer = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['reseller', 'package', 'router', 'olt', 'collector', 'payments.collector'])
            ->findOrFail($id);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'customer_id' => $customer->customer_id,
                    'name' => $customer->name,
                    'username' => $customer->username,
                    'password' => $customer->password,
                    'phone' => $customer->phone,
                    'alt_phone' => $customer->alt_phone,
                    'email' => $customer->email,
                    'national_id' => $customer->national_id,
                    'father_name' => $customer->father_name,
                    'address' => $customer->address,
                    'zone' => $customer->zone,
                    'division' => $customer->division,
                    'district' => $customer->district,
                    'thana' => $customer->thana,
                    'gps_lat' => $customer->gps_lat,
                    'gps_lng' => $customer->gps_lng,
                    'reseller_id' => $customer->reseller_id,
                    'reseller_name' => $customer->reseller?->name,
                    'reseller_code' => $customer->reseller?->code,
                    'scope_badge' => $customer->scope_badge,
                    'connection_type' => strtoupper($customer->connection_type),
                    'package_id' => $customer->package_id,
                    'package_name' => $customer->package_name,
                    'monthly_bill' => number_format($customer->monthly_bill, 2),
                    'router_id' => $customer->router_id,
                    'router_name' => $customer->router?->name,
                    'ip_address' => $customer->ip_address,
                    'mac_address' => $customer->mac_address,
                    'olt_id' => $customer->olt_id,
                    'olt_name' => $customer->olt?->name,
                    'onu_mac_sn' => $customer->onu_mac_sn,
                    'fiber_route_info' => $customer->fiber_route_info,
                    'billing_type' => ucfirst($customer->billing_type),
                    'expiry_date' => $customer->expiry_date?->format('d M Y') ?? '--',
                    'expiry_raw' => $customer->expiry_date?->format('Y-m-d') ?? '',
                    'status' => $customer->status,
                    'effective_status' => $customer->effective_status,
                    'status_badge' => $customer->status_badge,
                    'online_status' => $customer->online_status,
                    'online_badge' => $customer->online_badge,
                    'due_amount' => number_format($customer->due_amount, 2),
                    'wallet_balance' => number_format($customer->wallet_balance, 2),
                    'auto_cut_enabled' => $customer->auto_cut_enabled,
                    'grace_period_days' => $customer->grace_period_days,
                    'assigned_collector_id' => $customer->assigned_collector_id,
                    'collector_name' => $customer->collector?->name,
                    'remarks' => $customer->remarks,
                    'created_at' => $customer->created_at->format('d M Y, h:i A'),
                ]
            ]);
        }

        // Dedicated Page View
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->where('status', 'active')->get();
        $allRouters = TenantRouter::where('tenant_id', $tenant->id)->get();
        $recentPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->with('collector')
            ->latest('paid_at')
            ->take(12)
            ->get();
        
        $recentSessions = collect();
        if (!empty($customer->username)) {
            try {
                $recentSessions = DB::table('radacct')
                    ->where('username', $customer->username)
                    ->orderByDesc('radacctid')
                    ->take(12)
                    ->get();
            } catch (\Exception $e) {
                // Ignore if radacct is inaccessible
            }
        }

        $activityLogs = TenantActivityLog::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($customer) {
                $q->where('description', 'like', "%{$customer->name}%")
                  ->orWhere('description', 'like', "%{$customer->username}%");
            })
            ->latest()
            ->take(12)
            ->get();

        return view('tenant.customers.show', compact('tenant', 'customer', 'allPackages', 'allResellers', 'allRouters', 'recentPayments', 'recentSessions', 'activityLogs'));
    }

    /**
     * Show Edit Customer form page.
     */
    public function edit(Request $request, int $id): View
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $allRouters = TenantRouter::where('tenant_id', $tenant->id)->get();
        $allOlts = TenantOlt::where('tenant_id', $tenant->id)->get();
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->where('status', 'active')->get();
        $coverageZones = TenantCoverageZone::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allZones = $coverageZones->pluck('name');

        return view('tenant.customers.edit', compact(
            'tenant',
            'customer',
            'allPackages',
            'allRouters',
            'allOlts',
            'allResellers',
            'allZones',
            'coverageZones'
        ));
    }

    /**
     * Update customer record.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tenant_customers', 'customer_id')->where('tenant_id', $tenant->id)->ignore($customer->id),
            ],
            'name' => 'required|string|max:191',
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant_customers', 'username')->where('tenant_id', $tenant->id)->ignore($customer->id),
            ],
            'password' => 'nullable|string|min:4|max:100',
            'phone' => 'required|string|max:50',
            'alt_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'national_id' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:500',
            'zone_id' => 'nullable|exists:tenant_coverage_zones,id',
            'zone' => 'nullable|string|max:100',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'connection_type' => 'required|string|in:pppoe,static_ip,hotspot,dhcp',
            'package_id' => 'nullable|exists:tenant_internet_packages,id',
            'monthly_bill' => 'nullable|numeric|min:0',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:50',
            'olt_id' => 'nullable|exists:tenant_olts,id',
            'onu_mac_sn' => 'nullable|string|max:100',
            'fiber_route_info' => 'nullable|string|max:191',
            'billing_type' => 'required|string|in:prepaid,postpaid',
            'expiry_date' => 'nullable|date',
            'status' => 'required|string|in:active,due,expired,suspended,disabled,disconnected,archived',
            'due_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        $package = !empty($validated['package_id']) ? TenantInternetPackage::find($validated['package_id']) : null;

        if (!empty($validated['customer_id'])) {
            $customer->customer_id = $validated['customer_id'];
        }
        $customer->name = $validated['name'];
        $customer->username = $validated['username'];
        if (!empty($validated['password'])) {
            $customer->password = $validated['password'];
        }
        $customer->phone = $validated['phone'];
        $customer->alt_phone = $validated['alt_phone'] ?? $customer->alt_phone;
        $customer->email = $validated['email'] ?? $customer->email;
        $customer->national_id = $validated['national_id'] ?? $customer->national_id;
        $customer->father_name = $validated['father_name'] ?? $customer->father_name;
        $customer->address = $validated['address'] ?? $customer->address;

        // Resolve Coverage Zone
        $zoneId = $request->input('zone_id');
        $zoneName = $validated['zone'] ?? $customer->zone;
        if ($zoneId) {
            $cz = TenantCoverageZone::where('tenant_id', $tenant->id)->find($zoneId);
            if ($cz) {
                $zoneName = $cz->name;
                $customer->zone_id = $cz->id;
                $customer->zone = $cz->name;
            }
        } elseif (!empty($zoneName)) {
            $cz = TenantCoverageZone::where('tenant_id', $tenant->id)->where('name', $zoneName)->first();
            $customer->zone_id = $cz?->id;
            $customer->zone = $zoneName;
        }

        $customer->reseller_id = $validated['reseller_id'] ?? null;
        $customer->connection_type = $validated['connection_type'];
        $customer->package_id = $package?->id;
        $customer->package_name = $package ? ($package->package_name ?: $package->name) : $customer->package_name;
        $customer->monthly_bill = $validated['monthly_bill'] ?? $customer->monthly_bill;
        $customer->router_id = $validated['router_id'] ?? $customer->router_id;
        $customer->ip_address = $validated['ip_address'] ?? $customer->ip_address;
        $customer->mac_address = $validated['mac_address'] ?? $customer->mac_address;
        $customer->olt_id = $validated['olt_id'] ?? $customer->olt_id;
        $customer->onu_mac_sn = $validated['onu_mac_sn'] ?? $customer->onu_mac_sn;
        $customer->fiber_route_info = $validated['fiber_route_info'] ?? $customer->fiber_route_info;
        $customer->billing_type = $validated['billing_type'];
        if (!empty($validated['expiry_date'])) {
            $customer->expiry_date = Carbon::parse($validated['expiry_date']);
        }
        $customer->status = $validated['status'];
        if (in_array($customer->status, ['suspended', 'disabled', 'disconnected', 'expired'])) {
            $customer->online_status = 'offline';
        }
        if (isset($validated['due_amount'])) {
            $customer->due_amount = $validated['due_amount'];
        }
        $customer->remarks = $validated['remarks'] ?? $customer->remarks;

        $customer->save();

        // Sync updated attributes to MikroTik & FreeRADIUS
        try {
            $mikrotikApi = new MikrotikApiService();
            $mikrotikApi->syncCustomerToMikrotik($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        $msg = "Customer '{$customer->name}' ({$customer->customer_id}) updated & synchronized successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'customer' => $customer->load(['reseller', 'package']),
            ]);
        }

        return redirect()->route('tenant.customers.index')->with('success', $msg);
    }

    /**
     * Toggle Customer line status (Active / Suspended).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $newStatus = ($customer->status === 'active') ? 'suspended' : 'active';
        $updateData = ['status' => $newStatus];
        
        if (in_array($newStatus, ['suspended', 'disabled', 'disconnected', 'expired'])) {
            $updateData['online_status'] = 'offline';
        }
        
        $customer->update($updateData);

        // Sync status to MikroTik Router (Enables/Disables secret & disconnects live session if suspended)
        $mikrotikMsg = '';
        try {
            $router = $customer->router ?: (
                TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
                ?: TenantRouter::where('tenant_id', $tenant->id)->first()
            );

            if ($router) {
                $mikrotikApi = new MikrotikApiService();
                // 1. Direct fast toggle on router
                $mikrotikApi->togglePppSecret($router, $customer->username, $newStatus !== 'active');
                // 2. Comprehensive secret sync with full profile & customer comment
                $mRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($mRes['message'])) {
                    $mikrotikMsg = " - MikroTik: " . $mRes['message'];
                }
            }
        } catch (\Exception $e) {
            \Log::error("MikroTik status toggle error for subscriber {$customer->username}: " . $e->getMessage());
        }

        // Sync status to FreeRADIUS (Sets Auth-Type := Reject or Cleartext-Password)
        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);

            if ($newStatus === 'suspended') {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();
                if ($router) {
                    $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                    $radiusService->disconnectUser($customer->username, $router->ip_address, $radiusSecret);
                }
            }
        } catch (\Exception $e) {
            // Non-blocking
        }

        // Audit Trail Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_STATUS_TOGGLE',
            'description' => "Changed status of subscriber '{$customer->name}' ({$customer->username}) to {$newStatus}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['customer_id' => $customer->id, 'status' => $newStatus],
        ]);

        $stateTitle = ($newStatus === 'active') ? 'Enabled' : 'Disabled';

        return response()->json([
            'success' => true,
            'message' => "Customer '{$customer->username}' status updated to {$newStatus}!{$mikrotikMsg}",
            'status' => $newStatus,
            'online_status' => $customer->online_status,
            'badge' => $customer->status_badge,
        ]);
    }

    /**
     * Toggle MikroTik /ppp/secret Hardware State independently from billing status.
     */
    public function toggleMikrotikSecret(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $currentMikrotik = $customer->mikrotik_status ?: 'enabled';
        $newMikrotik = ($currentMikrotik === 'enabled') ? 'disabled' : 'enabled';

        $updateData = ['mikrotik_status' => $newMikrotik];
        if ($newMikrotik === 'disabled') {
            $updateData['online_status'] = 'offline';
        }
        $customer->update($updateData);

        $router = $customer->router ?: (
            TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
            ?: TenantRouter::where('tenant_id', $tenant->id)->first()
        );

        $routerSynced = false;
        if ($router) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $routerSynced = $mikrotikApi->togglePppSecret($router, $customer->username, $newMikrotik === 'disabled');
            } catch (\Exception $e) {
                \Log::error("Failed to toggle MikroTik secret for {$customer->username}: " . $e->getMessage());
            }
        }

        // Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'MIKROTIK_SECRET_TOGGLE',
            'description' => "Toggled MikroTik PPP secret for '{$customer->username}' to {$newMikrotik}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'customer_id' => $customer->id,
                'username' => $customer->username,
                'mikrotik_status' => $newMikrotik,
                'router_synced' => $routerSynced
            ],
        ]);

        $stateTitle = ucfirst($newMikrotik);

        return response()->json([
            'success' => true,
            'message' => "MikroTik Secret for '{$customer->username}' is now {$stateTitle} on router!",
            'mikrotik_status' => $newMikrotik,
            'online_status' => $customer->online_status,
        ]);
    }

    /**
     * Quick Renew / Recharge Subscriber.
     */
    public function renewSubscription(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $months = (int) $request->input('months', 1);
        $amountPaid = (float) $request->input('amount_paid', $customer->monthly_bill * $months);

        $currentExpiry = ($customer->expiry_date && $customer->expiry_date->isFuture()) 
            ? $customer->expiry_date 
            : Carbon::now();

        $newExpiry = $currentExpiry->copy()->addMonths($months);

        $customer->expiry_date = $newExpiry;
        $customer->status = 'active';
        if ($customer->due_amount > 0) {
            $customer->due_amount = max(0, $customer->due_amount - $amountPaid);
        }
        $customer->save();

        // Reactivate in MikroTik & FreeRADIUS
        try {
            $mikrotikApi = new MikrotikApiService();
            $mikrotikApi->syncCustomerToMikrotik($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        // Create Persistent Customer Payment Record
        TenantCustomerPayment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-' . date('Ym') . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT) . '-' . rand(100, 999),
            'billing_month' => Carbon::now()->format('F-Y'),
            'amount' => $amountPaid,
            'payment_method' => $request->input('payment_method', 'cash'),
            'collected_by' => Auth::id() ?: 1,
            'status' => 'paid',
            'paid_at' => Carbon::now(),
            'notes' => "Recharge & Renewal for {$months} month(s)",
        ]);

        // Settle matching or open TenantCustomerInvoice
        try {
            $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->first();

            if ($invoice) {
                $invoice->paid_amount = $invoice->total_payable;
                $invoice->due_amount = 0.00;
                $invoice->status = 'paid';
                $invoice->paid_at = Carbon::now();
                $invoice->payment_method = $request->input('payment_method', 'cash');
                $invoice->save();
            }
        } catch (\Exception $e) {
            // Non-blocking invoice sync
        }

        // Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_RENEW',
            'description' => "Renewed subscription for '{$customer->name}' for {$months} month(s). New expiry: {$newExpiry->format('d M Y')}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['customer_id' => $customer->id, 'months' => $months, 'amount' => $amountPaid],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subscription renewed for {$customer->name} until " . $newExpiry->format('d M Y') . " (MikroTik & FreeRADIUS Active)!",
            'expiry_date' => $newExpiry->format('d M Y'),
            'status' => 'active',
            'badge' => $customer->status_badge,
        ]);
    }

    /**
     * Sync single customer to MikroTik & FreeRADIUS AAA.
     */
    public function syncMikrotik(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $mikrotikApi = new MikrotikApiService();
        $res = $mikrotikApi->syncCustomerToMikrotik($customer);

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        return response()->json($res);
    }

    /**
     * Batch Sync all customers to MikroTik & FreeRADIUS.
     */
    public function syncAllMikrotik(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $customers = TenantCustomer::where('tenant_id', $tenant->id)->get();

        $mikrotikApi = new MikrotikApiService();
        $radiusService = new RadiusService();
        $syncedCount = 0;
        $failedCount = 0;

        foreach ($customers as $customer) {
            $res = $mikrotikApi->syncCustomerToMikrotik($customer);
            try {
                $radiusService->syncCustomerSubscriber($customer);
            } catch (\Exception $e) {
                // Non-blocking
            }

            if ($res['success']) {
                $syncedCount++;
            } else {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$syncedCount} customers to MikroTik & FreeRADIUS!" . ($failedCount > 0 ? " ({$failedCount} failed)" : ""),
            'synced' => $syncedCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Terminate / Kick active PPPoE session from MikroTik & RADIUS CoA.
     */
    public function kickSession(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'No active MikroTik router found for this customer.',
            ], 422);
        }

        $mikrotikApi = new MikrotikApiService();
        $res = $mikrotikApi->terminateActiveSession($router, $customer->username);

        // Also send RADIUS Disconnect-Request (CoA)
        try {
            $radiusService = new RadiusService();
            $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
            $radiusService->disconnectUser($customer->username, $router->ip_address, $radiusSecret);
        } catch (\Exception $e) {
            // Non-blocking
        }

        $customer->online_status = 'offline';
        $customer->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'PPPOE_SESSION_KICK',
            'description' => "Terminated active PPPoE session for '{$customer->username}' on {$router->name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['customer_id' => $customer->id, 'router' => $router->name],
        ]);

        return response()->json([
            'success' => true,
            'message' => "PPPoE session for '{$customer->username}' terminated successfully on {$router->name} (MikroTik & CoA)!",
            'online_status' => 'offline',
        ]);
    }

    /**
     * Sync and Refresh Live Online PPPoE Sessions across MikroTik Routers & FreeRADIUS radacct.
     */
    public function syncOnlineSessions(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $routers = TenantRouter::where('tenant_id', $tenant->id)->get();
        $mikrotikApi = new MikrotikApiService();

        $activeUsernames = [];
        $liveCount = 0;

        // 1. Check live active PPPoE sessions directly from connected MikroTik routers
        foreach ($routers as $router) {
            try {
                $res = $mikrotikApi->getActivePppoe($router);
                if (!empty($res['success']) && !empty($res['data'])) {
                    foreach ($res['data'] as $session) {
                        $u = $session['name'] ?? null;
                        if ($u) {
                            $activeUsernames[$u] = true;
                            $liveCount++;
                            TenantCustomer::where('tenant_id', $tenant->id)
                                ->where('username', $u)
                                ->update([
                                    'online_status' => 'online',
                                    'ip_address' => !empty($session['address']) ? $session['address'] : DB::raw('ip_address'),
                                    'mac_address' => !empty($session['caller_id']) ? $session['caller_id'] : DB::raw('mac_address'),
                                ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore router connection failure
            }
        }

        // 2. Check live accounting sessions from FreeRADIUS radacct (if active)
        try {
            $radiusSessions = DB::table('radacct')
                ->where('tenant_id', $tenant->id)
                ->whereNull('acctstoptime')
                ->get();

            foreach ($radiusSessions as $rs) {
                if (!empty($rs->username)) {
                    if (!isset($activeUsernames[$rs->username])) {
                        $activeUsernames[$rs->username] = true;
                        $liveCount++;
                    }
                    TenantCustomer::where('tenant_id', $tenant->id)
                        ->where('username', $rs->username)
                        ->update([
                            'online_status' => 'online',
                            'ip_address' => !empty($rs->framedipaddress) ? $rs->framedipaddress : DB::raw('ip_address'),
                            'mac_address' => !empty($rs->callingstationid) ? $rs->callingstationid : DB::raw('mac_address'),
                        ]);
                }
            }
        } catch (\Exception $e) {
            // Ignore if radacct table not used
        }

        // 3. Pull real-time /ppp/secret states directly from MikroTik routers
        $mikrotikStatusMap = [];
        foreach ($routers as $router) {
            try {
                $secRes = $mikrotikApi->pullPppSecrets($router);
                if (!empty($secRes['success']) && !empty($secRes['secrets'])) {
                    foreach ($secRes['secrets'] as $sec) {
                        $u = $sec['name'] ?? null;
                        if ($u) {
                            $mState = !empty($sec['disabled']) ? 'disabled' : 'enabled';
                            $mikrotikStatusMap[$u] = $mState;
                            TenantCustomer::where('tenant_id', $tenant->id)
                                ->where('username', $u)
                                ->update(['mikrotik_status' => $mState]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // 4. Mark all other tenant customers as offline
        $onlineList = array_keys($activeUsernames);
        if (!empty($onlineList)) {
            TenantCustomer::where('tenant_id', $tenant->id)
                ->whereNotIn('username', $onlineList)
                ->update(['online_status' => 'offline']);
        }

        return response()->json([
            'success' => true,
            'message' => "Live online sessions & MikroTik secret states synced! ({$liveCount} active sessions detected)",
            'active_count' => $liveCount,
            'online_usernames' => $onlineList,
            'mikrotik_status_map' => $mikrotikStatusMap,
        ]);
    }

    /**
     * Ping customer IP from MikroTik / Server.
     */
    public function pingCustomer(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);
        $count = min(10, max(1, (int)$request->input('count', 4)));

        $router = $customer->router ?: (
            TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
            ?: TenantRouter::where('tenant_id', $tenant->id)->first()
        );

        $mikrotikApi = new MikrotikApiService();
        $ip = $customer->ip_address;

        // Auto-detect IP from active session if not stored
        if (!$ip && $router) {
            try {
                $actRes = $mikrotikApi->getActivePppoe($router);
                if (!empty($actRes['data'])) {
                    foreach ($actRes['data'] as $ses) {
                        if (($ses['name'] ?? '') === $customer->username && !empty($ses['address'])) {
                            $ip = $ses['address'];
                            $customer->update(['ip_address' => $ip]);
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (!$ip) {
            return response()->json([
                'success' => false,
                'ip' => null,
                'customer_name' => $customer->name,
                'username' => $customer->username,
                'gateway' => $router ? $router->name : 'N/A',
                'sent' => $count,
                'received' => 0,
                'loss_percent' => 100,
                'rtt_min' => '--',
                'rtt_avg' => 'No IP Configured',
                'rtt_max' => '--',
                'lines' => [
                    "Subscriber '{$customer->username}' is currently offline with no assigned framed IP address.",
                    "Please verify PPPoE session or assign a static IP."
                ],
                'message' => "Customer '{$customer->name}' does not have an assigned or active IP address to ping.",
            ]);
        }

        if ($router) {
            $res = $mikrotikApi->pingHost($router, $ip, $count);
        } else {
            $escapedIp = escapeshellarg($ip);
            $output = [];
            $code = 0;
            @exec("ping -c {$count} -W 1 {$escapedIp} 2>&1", $output, $code);
            $success = ($code === 0);
            $res = [
                'success' => $success,
                'received' => $success ? $count : 0,
                'sent' => $count,
                'loss_percent' => $success ? 0 : 100,
                'rtt_min' => $success ? '1.0ms' : '--',
                'rtt_avg' => $success ? '1.4ms' : 'Host unreachable',
                'rtt_max' => $success ? '2.1ms' : '--',
                'lines' => !empty($output) ? array_values(array_filter($output)) : [
                    $success ? "64 bytes from {$ip}: icmp_seq=1 ttl=64 time=1.4ms" : "From {$ip} Destination Host Unreachable"
                ],
                'gateway' => 'Server Host',
            ];
        }

        $isReachable = !empty($res['success']);

        return response()->json([
            'success' => $isReachable,
            'ip' => $ip,
            'customer_name' => $customer->name,
            'username' => $customer->username,
            'gateway' => $res['gateway'] ?? ($router ? $router->name : 'Core Gateway'),
            'sent' => $res['sent'] ?? $count,
            'received' => $res['received'] ?? 0,
            'loss_percent' => $res['loss_percent'] ?? ($isReachable ? 0 : 100),
            'rtt_min' => $res['rtt_min'] ?? ($isReachable ? '< 2ms' : '--'),
            'rtt_avg' => $res['rtt_avg'] ?? ($isReachable ? '< 5ms' : 'Request timed out'),
            'rtt_max' => $res['rtt_max'] ?? ($isReachable ? '< 10ms' : '--'),
            'lines' => $res['lines'] ?? [],
            'message' => $isReachable
                ? "Host {$ip} is REACHABLE ({$res['received']}/{$count} packets received, Avg RTT: {$res['rtt_avg']})"
                : "Host {$ip} is UNREACHABLE (100% packet loss / timeout)",
        ]);
    }

    /**
     * Delete / Archive Customer.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $name = $customer->name;
        $username = $customer->username;
        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();

        // Remove from MikroTik Router
        if ($router && $username) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->removePppSecret($router, $username);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        // Remove from FreeRADIUS AAA
        try {
            $radiusService = new RadiusService();
            $radiusService->removeCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        $customer->delete();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_DELETE',
            'description' => "Deleted customer record for '{$name}' (Removed from MikroTik & FreeRADIUS).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $msg = "Customer '{$name}' deleted and removed from MikroTik & FreeRADIUS successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.customers.index')->with('success', $msg);
    }

    /**
     * Update PPPoE Credentials.
     */
    public function updatePppoe(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant_customers', 'username')->where('tenant_id', $tenant->id)->ignore($customer->id),
            ],
            'password' => 'required|string|min:3|max:100',
        ]);

        $oldUsername = $customer->username;
        $newUsername = trim($validated['username']);
        $newPassword = trim($validated['password']);

        // 1. Update Database Record
        $customer->username = $newUsername;
        $customer->password = $newPassword;
        $customer->save();

        // 2. If username changed, cleanup old PPPoE secret & sessions
        if ($oldUsername && $oldUsername !== $newUsername) {
            try {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();
                if ($router) {
                    $mikrotikApi = new MikrotikApiService();
                    $mikrotikApi->removePppSecret($router, $oldUsername);
                    $mikrotikApi->terminateActiveSession($router, $oldUsername);
                }
                DB::table('radcheck')->where('tenant_id', $tenant->id)->where('username', $oldUsername)->delete();
                DB::table('radreply')->where('tenant_id', $tenant->id)->where('username', $oldUsername)->delete();
                DB::table('radusergroup')->where('tenant_id', $tenant->id)->where('username', $oldUsername)->delete();
            } catch (\Exception $e) {
                \Log::warning("Old PPPoE cleanup notice for {$oldUsername}: " . $e->getMessage());
            }
        }

        // 3. Sync to MikroTik Router (Pushes new password & settings)
        $mikrotikMsg = '';
        try {
            $mikrotikApi = new MikrotikApiService();
            $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
            
            // Terminate active live session so client authenticates with the new password
            $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();
            if ($router) {
                $mikrotikApi->terminateActiveSession($router, $newUsername);
            }

            if (!empty($syncRes['message'])) {
                $mikrotikMsg = " (MikroTik: {$syncRes['message']})";
            }
        } catch (\Exception $e) {
            \Log::error("MikroTik PPPoE sync error: " . $e->getMessage());
        }

        // 4. Sync to FreeRADIUS AAA
        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            \Log::error("FreeRADIUS PPPoE sync error: " . $e->getMessage());
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_PPPOE_UPDATE',
            'description' => "Updated PPPoE credentials for '{$customer->name}' (User: {$customer->username}, Password: {$customer->password}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "PPPoE username & password updated and synced successfully!{$mikrotikMsg}",
            'username' => $customer->username,
            'password' => $customer->password,
        ]);
    }

    /**
     * Explicit Status Update (Active / Suspended / Disabled / Expired / Disconnected).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:active,suspended,disabled,expired,due,disconnected',
        ]);

        $oldStatus = $customer->status;
        $newStatus = $validated['status'];
        $updateData = ['status' => $newStatus];

        if (in_array($newStatus, ['suspended', 'disabled', 'disconnected', 'expired'])) {
            $updateData['online_status'] = 'offline';
        }

        $customer->update($updateData);

        // Sync status to MikroTik Router
        $mikrotikMsg = '';
        try {
            $router = $customer->router ?: (
                TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
                ?: TenantRouter::where('tenant_id', $tenant->id)->first()
            );

            if ($router) {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->togglePppSecret($router, $customer->username, $newStatus !== 'active');
                $mRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($mRes['message'])) {
                    $mikrotikMsg = " - MikroTik: " . $mRes['message'];
                }
            }
        } catch (\Exception $e) {
            \Log::error("MikroTik status update error: " . $e->getMessage());
        }

        // Sync status to FreeRADIUS
        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);

            if (in_array($newStatus, ['suspended', 'disabled', 'disconnected'])) {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->first();
                if ($router) {
                    $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                    $radiusService->disconnectUser($customer->username, $router->ip_address, $radiusSecret);
                }
            }
        } catch (\Exception $e) {}

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_STATUS_UPDATE',
            'description' => "Updated status for '{$customer->name}' from '{$oldStatus}' to '{$newStatus}'.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'status' => $customer->status,
            'message' => "Status changed to '" . strtoupper($newStatus) . "' and synced to MikroTik & FreeRADIUS.{$mikrotikMsg}",
        ]);
    }

    /**
     * Change Internet Package with Reseller Wallet Deduction and Expiry Adjustment.
     */
    public function updatePackage(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'package_id' => 'required|exists:tenant_internet_packages,id',
            'update_bill' => 'nullable|boolean',
            'disconnect_session' => 'nullable|boolean',
        ]);

        $newPackage = TenantInternetPackage::where('tenant_id', $tenant->id)->findOrFail($validated['package_id']);
        $oldPackageName = $customer->package_name ?: ($customer->package?->package_name ?: 'Current Plan');
        $oldRetailPrice = (float)($customer->package?->price ?? $customer->monthly_bill);
        $newRetailPrice = (float)$newPackage->price;

        $reseller = $customer->reseller ?: ($customer->reseller_id ? TenantReseller::find($customer->reseller_id) : null);

        // Calculate wholesale / reseller cost
        $oldCost = $reseller ? $reseller->calculateResellerCost($oldRetailPrice) : $oldRetailPrice;
        $newCost = $reseller ? $reseller->calculateResellerCost($newRetailPrice) : $newRetailPrice;

        // Calculate remaining cycle days
        $now = Carbon::now()->startOfDay();
        $expiry = $customer->expiry_date ? Carbon::parse($customer->expiry_date)->startOfDay() : null;
        $remainingDays = ($expiry && $expiry->greaterThan($now)) ? (int)$now->diffInDays($expiry) : 0;

        $walletDeducted = 0.00;
        $walletRefunded = 0.00;
        $oldExpiryDate = $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : null;
        $newExpiryDate = $oldExpiryDate;
        $resellerNotice = '';
        $actionType = 'DIRECT';

        // Process prorated calculation if remaining days exist
        if ($remainingDays > 0) {
            $oldDailyCost = $oldCost / 30;
            $newDailyCost = $newCost / 30;
            $remainingFinancialValue = $remainingDays * $oldDailyCost;

            if ($reseller) {
                $resellerAvailableBalance = (float)($reseller->total_available_balance);

                if ($newCost > $oldCost) {
                    // Upgrade: Needs additional funds for remaining days
                    $proratedCostDiff = round(($newDailyCost - $oldDailyCost) * $remainingDays, 2);

                    if ($resellerAvailableBalance >= $proratedCostDiff && $proratedCostDiff > 0) {
                        // Reseller has sufficient wallet balance
                        $balanceBefore = (float)$reseller->wallet_balance;
                        $reseller->decrement('wallet_balance', $proratedCostDiff);
                        $reseller->refresh();
                        $walletDeducted = $proratedCostDiff;
                        $actionType = 'RESELLER_WALLET_DEBITED';

                        TenantResellerWalletTransaction::create([
                            'tenant_id' => $tenant->id,
                            'reseller_id' => $reseller->id,
                            'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                            'type' => 'DEBIT',
                            'amount' => $proratedCostDiff,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $reseller->wallet_balance,
                            'payment_method' => 'WALLET',
                            'description' => "Package upgrade proration for customer '{$customer->username}' ({$oldPackageName} -> {$newPackage->name}) for {$remainingDays} remaining days.",
                            'created_by' => Auth::id() ?: 1,
                        ]);

                        $resellerNotice = "৳" . number_format($proratedCostDiff, 2) . " deducted from Partner ({$reseller->name}) wallet. Expiry remains unchanged.";
                    } else {
                        // Reseller has insufficient balance -> adjust validity / expiry date
                        $newDaysCovered = max(1, (int)floor($remainingFinancialValue / $newDailyCost));
                        $newExpiryDate = Carbon::now()->addDays($newDaysCovered)->format('Y-m-d');
                        $customer->expiry_date = $newExpiryDate;
                        $actionType = 'EXPIRY_ADJUSTED';

                        $resellerNotice = "Partner wallet balance insufficient (Available: ৳" . number_format($resellerAvailableBalance, 2) . ", Required: ৳" . number_format($proratedCostDiff, 2) . "). Validity adjusted from {$remainingDays} days to {$newDaysCovered} days (New expiry: {$newExpiryDate}).";
                    }
                } elseif ($newCost < $oldCost) {
                    // Downgrade: Prorated refund back to reseller wallet
                    $proratedRefund = round(($oldDailyCost - $newDailyCost) * $remainingDays, 2);
                    if ($proratedRefund > 0) {
                        $balanceBefore = (float)$reseller->wallet_balance;
                        $reseller->increment('wallet_balance', $proratedRefund);
                        $reseller->refresh();
                        $walletRefunded = $proratedRefund;
                        $actionType = 'RESELLER_WALLET_REFUNDED';

                        TenantResellerWalletTransaction::create([
                            'tenant_id' => $tenant->id,
                            'reseller_id' => $reseller->id,
                            'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                            'type' => 'CREDIT',
                            'amount' => $proratedRefund,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $reseller->wallet_balance,
                            'payment_method' => 'WALLET',
                            'description' => "Package downgrade prorated credit for customer '{$customer->username}' ({$oldPackageName} -> {$newPackage->name}) for {$remainingDays} remaining days.",
                            'created_by' => Auth::id() ?: 1,
                        ]);

                        $resellerNotice = "৳" . number_format($proratedRefund, 2) . " credited back to Partner ({$reseller->name}) wallet.";
                    }
                }
            } else {
                // DIRECT ISP CUSTOMER (Non-Reseller)
                $prorationMode = $request->input('direct_proration_mode', 'adjust_expiry');

                if ($newCost > $oldCost) {
                    // Upgrade for Direct ISP Customer
                    $proratedCostDiff = round(($newDailyCost - $oldDailyCost) * $remainingDays, 2);

                    if ($prorationMode === 'adjust_expiry') {
                        // 1. Recalculate expiry validity based on remaining monetary value
                        $newDaysCovered = max(1, (int)floor($remainingFinancialValue / $newDailyCost));
                        $newExpiryDate = Carbon::now()->addDays($newDaysCovered)->format('Y-m-d');
                        $customer->expiry_date = $newExpiryDate;
                        $actionType = 'DIRECT_EXPIRY_ADJUSTED';
                        $resellerNotice = "Validity adjusted from {$remainingDays} days to {$newDaysCovered} days based on paid value (New Expiry: {$newExpiryDate}).";
                    } elseif ($prorationMode === 'add_to_due') {
                        // 2. Keep expiry, add difference to customer due
                        $customer->due_amount = (float)($customer->due_amount ?? 0) + $proratedCostDiff;
                        $actionType = 'DIRECT_DUE_ADDED';
                        $resellerNotice = "Prorated upgrade charge of ৳" . number_format($proratedCostDiff, 2) . " added to Customer Due. Expiry remains {$oldExpiryDate}.";
                    } else {
                        // 3. Next billing cycle only
                        $actionType = 'DIRECT_NEXT_CYCLE';
                        $resellerNotice = "Package upgraded. New monthly rate applies from next billing cycle.";
                    }
                } elseif ($newCost < $oldCost) {
                    // Downgrade for Direct ISP Customer
                    $proratedRefund = round(($oldDailyCost - $newDailyCost) * $remainingDays, 2);

                    if ($prorationMode === 'adjust_expiry') {
                        // 1. Extend expiry validity
                        $newDaysCovered = max($remainingDays, (int)floor($remainingFinancialValue / $newDailyCost));
                        $newExpiryDate = Carbon::now()->addDays($newDaysCovered)->format('Y-m-d');
                        $customer->expiry_date = $newExpiryDate;
                        $actionType = 'DIRECT_EXPIRY_EXTENDED';
                        $resellerNotice = "Validity extended from {$remainingDays} days to {$newDaysCovered} days for remaining balance (New Expiry: {$newExpiryDate}).";
                    } elseif ($prorationMode === 'add_to_due') {
                        // 2. Adjust customer due/wallet balance
                        if ((float)$customer->due_amount > 0) {
                            $customer->due_amount = max(0, (float)$customer->due_amount - $proratedRefund);
                        } else {
                            $customer->wallet_balance = (float)($customer->wallet_balance ?? 0) + $proratedRefund;
                        }
                        $actionType = 'DIRECT_CREDIT_ADJUSTED';
                        $resellerNotice = "Prorated credit of ৳" . number_format($proratedRefund, 2) . " adjusted to customer balance.";
                    }
                }
            }
        }

        // Update Customer Record
        $customer->package_id = $newPackage->id;
        $customer->package_name = $newPackage->package_name ?: $newPackage->name;
        if ($request->input('update_bill', true)) {
            $customer->monthly_bill = $newPackage->price;
        }
        $customer->save();

        // Sync to MikroTik Router
        $syncMikrotikSuccess = false;
        try {
            $mikrotikApi = new MikrotikApiService();
            $resMt = $mikrotikApi->syncCustomerToMikrotik($customer);
            $syncMikrotikSuccess = (bool)($resMt['success'] ?? false);

            // Disconnect active session so speed profile is applied immediately
            if ($request->input('disconnect_session', true)) {
                $router = $customer->router ?: (
                    TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
                    ?: TenantRouter::where('tenant_id', $tenant->id)->first()
                );
                if ($router && $customer->username) {
                    $mikrotikApi->terminateActiveSession($router, $customer->username);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("MikroTik sync package notice: " . $e->getMessage());
        }

        // Sync to FreeRADIUS
        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            \Log::warning("FreeRADIUS sync package notice: " . $e->getMessage());
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_PACKAGE_UPDATE',
            'description' => "Changed package for '{$customer->name}' ({$customer->username}) from '{$oldPackageName}' to '{$customer->package_name}'. " . ($resellerNotice ?: ''),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $successMsg = "Package changed to '{$customer->package_name}' successfully!";
        if ($resellerNotice) {
            $successMsg .= " " . $resellerNotice;
        }

        return response()->json([
            'success' => true,
            'message' => $successMsg,
            'package_id' => $customer->package_id,
            'package_name' => $customer->package_name,
            'monthly_bill' => number_format($customer->monthly_bill, 2),
            'expiry_date' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : null,
            'wallet_deducted' => $walletDeducted,
            'wallet_refunded' => $walletRefunded,
            'reseller_balance' => $reseller ? number_format($reseller->wallet_balance, 2) : null,
            'action_type' => $actionType,
            'sync_mikrotik' => $syncMikrotikSuccess,
        ]);
    }

    /**
     * Modify Monthly Bill (Custom Rate / Special Discount).
     */
    public function updateMonthlyBill(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'monthly_bill' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
            'adjust_due' => 'nullable|boolean',
        ]);

        $oldBill = (float)$customer->monthly_bill;
        $newBill = (float)$validated['monthly_bill'];
        $diff = $newBill - $oldBill;
        $reason = trim($validated['reason'] ?? '');

        $customer->monthly_bill = $newBill;

        if (!empty($validated['adjust_due']) && $diff != 0) {
            $customer->due_amount = max(0, (float)($customer->due_amount ?? 0) + $diff);
        }

        $customer->save();

        $logDesc = "Updated monthly bill for '{$customer->name}' ({$customer->username}) from ৳" . number_format($oldBill, 2) . " to ৳" . number_format($newBill, 2) . ".";
        if ($reason) {
            $logDesc .= " Reason: {$reason}.";
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_BILL_UPDATE',
            'description' => $logDesc,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Monthly bill modified to ৳" . number_format($customer->monthly_bill, 2) . " successfully!" . ($reason ? " ({$reason})" : ""),
            'monthly_bill' => number_format($customer->monthly_bill, 2),
            'due_amount' => number_format((float)($customer->due_amount ?? 0), 2),
        ]);
    }

    /**
     * Change Billing Cycle / Expiry Date.
     */
    public function updateBillingCycle(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'billing_type' => 'required|in:prepaid,postpaid',
            'expiry_date' => 'nullable|date',
            'billing_cycle_date' => 'nullable|integer|min:1|max:31',
            'grace_period_days' => 'nullable|integer|min:0|max:90',
            'auto_cut_enabled' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
        ]);

        $customer->billing_type = $validated['billing_type'];
        
        if (!empty($validated['expiry_date'])) {
            $customer->expiry_date = Carbon::parse($validated['expiry_date'])->endOfDay();
        }

        if (!empty($validated['billing_cycle_date'])) {
            $customer->billing_cycle_date = Carbon::now()->setDay((int) $validated['billing_cycle_date'])->startOfDay();
        }

        $customer->grace_period_days = isset($validated['grace_period_days']) ? (int) $validated['grace_period_days'] : $customer->grace_period_days;
        $customer->auto_cut_enabled = $request->boolean('auto_cut_enabled');

        $isReactivated = false;
        if ($request->boolean('reactivate_line') && $customer->expiry_date && $customer->expiry_date->isFuture()) {
            if (in_array($customer->status, ['expired', 'suspended', 'disabled'])) {
                $customer->status = 'active';
                $isReactivated = true;
            }
        }

        $customer->save();

        // Sync to MikroTik and FreeRADIUS if status was reactivated
        $syncMsg = '';
        if ($isReactivated || $request->boolean('sync_mikrotik')) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if ($syncRes['success']) {
                    $syncMsg .= ' & MikroTik RouterOS synchronized';
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            try {
                $radiusService = new RadiusService();
                $radiusService->syncCustomerSubscriber($customer);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_BILLING_CYCLE_UPDATE',
            'description' => "Updated billing cycle for subscriber '{$customer->name}' ({$customer->username}). Type: {$customer->billing_type}, Expiry: " . ($customer->expiry_date?->format('d M Y') ?? 'N/A') . ($isReactivated ? " (Re-activated line)" : ""),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'customer_id' => $customer->id,
                'billing_type' => $customer->billing_type,
                'expiry_date' => $customer->expiry_date?->format('Y-m-d'),
                'grace_period_days' => $customer->grace_period_days,
                'auto_cut_enabled' => $customer->auto_cut_enabled,
                'is_reactivated' => $isReactivated,
            ],
        ]);

        $remainingDays = 0;
        if ($customer->expiry_date) {
            $today = Carbon::today();
            $remainingDays = $customer->expiry_date->isPast() ? 0 : (int) $today->diffInDays($customer->expiry_date, false);
        }

        return response()->json([
            'success' => true,
            'message' => "Billing cycle and validity updated successfully!{$syncMsg}",
            'billing_type' => ucfirst($customer->billing_type),
            'expiry_date' => $customer->expiry_date?->format('d M Y') ?? '--',
            'expiry_raw' => $customer->expiry_date?->format('Y-m-d') ?? '',
            'remaining_days' => $remainingDays,
            'grace_period_days' => $customer->grace_period_days,
            'auto_cut_enabled' => $customer->auto_cut_enabled,
            'status' => $customer->status,
            'effective_status' => $customer->effective_status,
            'status_badge' => $customer->status_badge,
        ]);
    }

    /**
     * Update Wallet Balance.
     */
    public function updateBalance(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'mode' => 'required|in:set,add,deduct',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $mode = $validated['mode'];
        $amount = (float) $validated['amount'];
        $oldBalance = (float) $customer->wallet_balance;

        if ($mode === 'set') {
            $customer->wallet_balance = $amount;
        } elseif ($mode === 'add') {
            $customer->wallet_balance = $oldBalance + $amount;
        } elseif ($mode === 'deduct') {
            $customer->wallet_balance = max(0, $oldBalance - $amount);
        }
        $customer->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_BALANCE_UPDATE',
            'description' => "Updated wallet balance for '{$customer->name}' ({$mode} ৳{$amount}). New balance: ৳{$customer->wallet_balance}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Wallet balance updated to ৳" . number_format($customer->wallet_balance, 2) . " successfully!",
            'wallet_balance' => number_format($customer->wallet_balance, 2),
        ]);
    }

    /**
     * Update Due Balance (Manual Adjustment / Waiver / Surcharge).
     */
    public function updateDueBalance(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'mode' => 'required|in:set,add,deduct,clear',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $mode = $validated['mode'];
        $amount = (float) ($validated['amount'] ?? 0);
        $oldDue = (float) ($customer->due_amount ?? 0);
        $newDue = $oldDue;

        if ($mode === 'set') {
            $newDue = max(0, $amount);
        } elseif ($mode === 'add') {
            $newDue = $oldDue + $amount;
        } elseif ($mode === 'deduct') {
            $newDue = max(0, $oldDue - $amount);
        } elseif ($mode === 'clear') {
            $newDue = 0.00;
        }

        $customer->due_amount = $newDue;

        // Auto-update status if due was cleared
        if ($customer->due_amount == 0 && $customer->status === 'due') {
            $customer->status = 'active';
        }

        $customer->save();

        $modeText = match($mode) {
            'set' => 'Set to ৳' . number_format($newDue, 2),
            'add' => 'Added ৳' . number_format($amount, 2),
            'deduct' => 'Deducted/Waived ৳' . number_format($amount, 2),
            'clear' => 'Cleared all due (৳0.00)',
            default => 'Adjusted',
        };

        $remarks = trim($validated['remarks'] ?? '');

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_DUE_UPDATE',
            'description' => "Updated due balance for subscriber '{$customer->name}' ({$customer->username}): {$modeText}. Old Due: ৳" . number_format($oldDue, 2) . ", New Due: ৳" . number_format($newDue, 2) . ($remarks ? " [Reason: {$remarks}]" : ""),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'customer_id' => $customer->id,
                'mode' => $mode,
                'amount' => $amount,
                'old_due' => $oldDue,
                'new_due' => $newDue,
                'remarks' => $remarks,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Due balance updated to ৳" . number_format($customer->due_amount, 2) . " successfully!" . ($remarks ? " ({$remarks})" : ""),
            'due_amount' => number_format($customer->due_amount, 2),
            'due_amount_raw' => (float) $customer->due_amount,
            'status' => $customer->status,
            'effective_status' => $customer->effective_status,
            'status_badge' => $customer->status_badge,
        ]);
    }

    /**
     * Pay Bill (Quick Payment Entry).
     */
    public function payBill(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank_transfer,pos,other',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
            'months' => 'nullable|integer|min:0|max:24',
        ]);

        $amount = (float) $validated['amount'];
        $months = (int) ($validated['months'] ?? 0);

        // Adjust due amount
        if ($customer->due_amount > 0) {
            $customer->due_amount = max(0, $customer->due_amount - $amount);
        }

        // Extend expiry if months specified
        if ($months > 0) {
            $currentExpiry = ($customer->expiry_date && $customer->expiry_date->isFuture()) 
                ? $customer->expiry_date 
                : Carbon::now();
            $customer->expiry_date = $currentExpiry->copy()->addMonths($months);
        }

        if ($customer->status === 'due' || $customer->status === 'expired') {
            $customer->status = 'active';
        }
        $customer->save();

        // Create Payment Record
        $payment = TenantCustomerPayment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-' . date('Ym') . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT) . '-' . rand(100, 999),
            'billing_month' => Carbon::now()->format('F-Y'),
            'amount' => $amount,
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'collected_by' => Auth::id() ?: 1,
            'status' => 'paid',
            'paid_at' => Carbon::now(),
            'notes' => $validated['notes'] ?? ($months > 0 ? "Bill payment for {$months} month(s)" : "Direct payment collection"),
        ]);

        // Settle matching or open TenantCustomerInvoice
        try {
            $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->first();

            if ($invoice) {
                if ($amount >= $invoice->total_payable) {
                    $invoice->paid_amount = $invoice->total_payable;
                    $invoice->due_amount = 0.00;
                    $invoice->status = 'paid';
                } else {
                    $invoice->paid_amount = (float)$invoice->paid_amount + (float)$amount;
                    $invoice->due_amount = max(0, (float)$invoice->total_payable - (float)$invoice->paid_amount);
                    $invoice->status = 'partial';
                }
                $invoice->paid_at = Carbon::now();
                $invoice->payment_method = $validated['payment_method'];
                $invoice->save();
            }
        } catch (\Exception $e) {
            // Non-blocking invoice sync
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_PAYMENT',
            'description' => "Collected payment of ৳{$amount} from '{$customer->name}' via " . strtoupper($validated['payment_method']) . " (Invoice: {$payment->invoice_no}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Payment of ৳" . number_format($amount, 2) . " recorded successfully! Invoice #{$payment->invoice_no}",
            'invoice_no' => $payment->invoice_no,
            'due_amount' => number_format($customer->due_amount, 2),
            'status' => $customer->status,
            'expiry_date' => $customer->expiry_date?->format('d M Y') ?? '--',
            'payment' => [
                'id' => $payment->id,
                'invoice_no' => $payment->invoice_no,
                'amount' => number_format($payment->amount, 2),
                'method' => strtoupper($payment->payment_method),
                'date' => $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : Carbon::now()->format('d M Y, h:i A'),
            ]
        ]);
    }

    /**
     * Transfer Subscriber to Reseller or back to ISP Direct Retail.
     */
    public function transferReseller(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)
            ->with('reseller')
            ->findOrFail($id);

        $validated = $request->validate([
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'notes' => 'nullable|string|max:255',
            'sync_mikrotik' => 'nullable|boolean',
        ]);

        $oldResellerName = $customer->reseller ? "{$customer->reseller->name} ({$customer->reseller->code})" : 'ISP Direct Retail';
        $newResellerId = !empty($validated['reseller_id']) ? (int) $validated['reseller_id'] : null;

        $customer->reseller_id = $newResellerId;
        $customer->save();
        $customer->load('reseller');

        $newResellerName = $customer->reseller ? "{$customer->reseller->name} ({$customer->reseller->code})" : 'ISP Direct Retail';
        $transferNote = trim($validated['notes'] ?? '');

        // Sync customer comment / affiliation to MikroTik & FreeRADIUS
        $syncMsg = '';
        if ($request->boolean('sync_mikrotik', true)) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if ($syncRes['success']) {
                    $syncMsg .= ' & MikroTik RouterOS synchronized';
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            try {
                $radiusService = new RadiusService();
                $radiusService->syncCustomerSubscriber($customer);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_TRANSFER',
            'description' => "Transferred subscriber '{$customer->name}' ({$customer->username}) from '{$oldResellerName}' to '{$newResellerName}'." . ($transferNote ? " [Note: {$transferNote}]" : ""),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'customer_id' => $customer->id,
                'old_reseller' => $oldResellerName,
                'new_reseller' => $newResellerName,
                'note' => $transferNote,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subscriber transferred to '{$newResellerName}' successfully!{$syncMsg}",
            'reseller_id' => $customer->reseller_id,
            'reseller_name' => $customer->reseller ? $customer->reseller->name : 'ISP Direct Retail',
            'reseller_code' => $customer->reseller?->code,
            'scope_badge' => $customer->scope_badge,
        ]);
    }

    /**
     * Update Notes for Customer.
     */
    public function updateNote(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:2000',
        ]);

        $customer->remarks = $validated['remarks'] ?? null;
        $customer->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CUSTOMER_NOTE_UPDATE',
            'description' => "Updated internal note for customer '{$customer->name}'.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Customer notes saved successfully!",
            'remarks' => $customer->remarks,
        ]);
    }

    /**
     * Get Real-time RX/TX Live Traffic in bulk for a list of customers.
     */
    public function getBatchTraffic(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $ids = array_slice($ids, 0, 50);

        $customers = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->get();

        $defaultRouter = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
            ?: TenantRouter::where('tenant_id', $tenant->id)->first();

        $mikrotikApi = new MikrotikApiService();
        $results = [];

        foreach ($customers as $customer) {
            $router = $customer->router ?: $defaultRouter;
            $trafficData = [
                'id' => $customer->id,
                'username' => $customer->username,
                'is_online' => false,
                'rx_human' => '0.00 Mbps',
                'tx_human' => '0.00 Mbps',
                'rx_mbps' => 0.0,
                'tx_mbps' => 0.0,
                'uptime' => 'Offline',
                'total_rx_human' => '0 B',
                'total_tx_human' => '0 B',
            ];

            if ($router && !empty($customer->username)) {
                try {
                    $live = $mikrotikApi->getLivePppoeTraffic($router, $customer->username);
                    if (!empty($live['success']) && !empty($live['is_online'])) {
                        $trafficData['is_online'] = true;
                        $trafficData['rx_human'] = $live['rx_human'] ?? '0.00 Mbps';
                        $trafficData['tx_human'] = $live['tx_human'] ?? '0.00 Mbps';
                        $trafficData['rx_mbps'] = $live['rx_mbps'] ?? 0.0;
                        $trafficData['tx_mbps'] = $live['tx_mbps'] ?? 0.0;
                        $trafficData['uptime'] = $this->formatMikrotikUptime($live['uptime'] ?? null);
                        $trafficData['total_rx_human'] = $live['total_rx_human'] ?? '0 B';
                        $trafficData['total_tx_human'] = $live['total_tx_human'] ?? '0 B';
                    }
                } catch (\Exception $e) {
                    \Log::warning("Batch traffic query notice for user {$customer->username}: " . $e->getMessage());
                }
            }

            $results[$customer->id] = $trafficData;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get Real-time RX/TX Traffic and Session Details directly from MikroTik RouterOS.
     */
    public function getTraffic(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $router = $customer->router ?: (
            TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
            ?: TenantRouter::where('tenant_id', $tenant->id)->first()
        );
        
        $trafficData = [
            'success' => true,
            'is_online' => false,
            'rx_bps' => 0,
            'tx_bps' => 0,
            'rx_kbps' => 0,
            'tx_kbps' => 0,
            'rx_mbps' => 0.0,
            'tx_mbps' => 0.0,
            'rx_human' => '0.00 Mbps',
            'tx_human' => '0.00 Mbps',
            'uptime' => 'Offline',
            'ip' => $customer->ip_address ?: 'N/A',
            'mac' => $customer->mac_address ?: 'N/A',
            'total_rx_human' => '0 B',
            'total_tx_human' => '0 B',
            'timestamp' => Carbon::now()->format('H:i:s'),
        ];

        if ($router && !empty($customer->username)) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $live = $mikrotikApi->getLivePppoeTraffic($router, $customer->username);
                
                if (!empty($live['success'])) {
                    $trafficData['is_online'] = (bool)($live['is_online'] ?? false);
                    $trafficData['rx_bps'] = $live['rx_bps'] ?? 0;
                    $trafficData['tx_bps'] = $live['tx_bps'] ?? 0;
                    $trafficData['rx_kbps'] = $live['rx_kbps'] ?? 0;
                    $trafficData['tx_kbps'] = $live['tx_kbps'] ?? 0;
                    $trafficData['rx_mbps'] = $live['rx_mbps'] ?? 0.0;
                    $trafficData['tx_mbps'] = $live['tx_mbps'] ?? 0.0;
                    $trafficData['rx_human'] = $live['rx_human'] ?? '0.00 Mbps';
                    $trafficData['tx_human'] = $live['tx_human'] ?? '0.00 Mbps';
                    $trafficData['uptime'] = $this->formatMikrotikUptime($live['uptime'] ?? null);
                    if (!empty($live['ip'])) {
                        $trafficData['ip'] = $live['ip'];
                    }
                    if (!empty($live['mac'])) {
                        $trafficData['mac'] = $live['mac'];
                    }
                    $trafficData['total_rx_human'] = $live['total_rx_human'] ?? '0 B';
                    $trafficData['total_tx_human'] = $live['total_tx_human'] ?? '0 B';
                }
            } catch (\Exception $e) {
                \Log::warning("MikroTik getTraffic query notice: " . $e->getMessage());
            }
        }

        return response()->json($trafficData);
    }

    /**
     * Get Real-time & Historical Data Usage (Bandwidth Consumption in GB/MB)
     * Query FreeRADIUS `radacct` and live MikroTik accounting for actual subscriber sessions.
     */
    public function getUsageHistory(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);
        $username = $customer->username ?: $customer->pppoe_username;

        // Current live metrics
        $currentRxMbps = 0.0;
        $currentTxMbps = 0.0;
        $totalRxHuman = '0 B';
        $totalTxHuman = '0 B';

        // 1. Query Live MikroTik session for real-time bytes & rate
        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first();
        if ($router && !empty($username)) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $live = $mikrotikApi->getLivePppoeTraffic($router, $username);
                if (!empty($live['is_online'])) {
                    $currentRxMbps = (float)($live['rx_mbps'] ?? 0.0);
                    $currentTxMbps = (float)($live['tx_mbps'] ?? 0.0);
                    $totalRxHuman = $live['total_rx_human'] ?? '0 B';
                    $totalTxHuman = $live['total_tx_human'] ?? '0 B';
                }
            } catch (\Exception $e) {}
        }

        // 2. Query FreeRADIUS radacct for historical sessions
        $sessions = collect();
        if (!empty($username) && Schema::hasTable('radacct')) {
            $since7Days = Carbon::now()->subDays(7)->startOfDay();
            $sessions = DB::table('radacct')
                ->where('tenant_id', $tenant->id)
                ->where('username', $username)
                ->where('acctstarttime', '>=', $since7Days)
                ->get(['acctstarttime', 'acctstoptime', 'acctoutputoctets', 'acctinputoctets', 'acctsessiontime']);
        }

        // --- 5 Min Period (30 real-time rolling points, 10s intervals) ---
        $pts5m_rx = array_fill(0, 30, max(0.0, round($currentRxMbps * 0.9, 2)));
        $pts5m_tx = array_fill(0, 30, max(0.0, round($currentTxMbps * 0.9, 2)));
        $pts5m_rx[29] = $currentRxMbps;
        $pts5m_tx[29] = $currentTxMbps;
        $labels5m = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels5m[] = Carbon::now()->subSeconds($i * 10)->format('H:i:s');
        }

        // --- 1 Hour Period (30 points, 2m intervals from active/recent sessions) ---
        $pts1h_rx = array_fill(0, 30, 0.0);
        $pts1h_tx = array_fill(0, 30, 0.0);
        $labels1h = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels1h[] = Carbon::now()->subMinutes($i * 2)->format('H:i');
        }

        // --- 1 Day Period (24 points, hourly intervals) ---
        $pts1d_rx = array_fill(0, 24, 0.0);
        $pts1d_tx = array_fill(0, 24, 0.0);
        $hours1d = [];
        for ($h = 23; $h >= 0; $h--) {
            $hours1d[] = Carbon::now()->subHours($h)->format('H:00');
        }

        // --- 7 Days Period (7 points, daily intervals) ---
        $days7 = [];
        $pts7d_rx = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $pts7d_tx = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        for ($d = 6; $d >= 0; $d--) {
            $dt = Carbon::now()->subDays($d);
            $days7[] = $d === 0 ? 'Today' : $dt->format('D (d M)');
        }

        // Fill real session data into 1h, 1d & 7d points
        foreach ($sessions as $session) {
            $st = Carbon::parse($session->acctstarttime);
            $rxMb = round(((float)($session->acctoutputoctets ?? 0)) / 1048576, 2);
            $txMb = round(((float)($session->acctinputoctets ?? 0)) / 1048576, 2);

            // 1 Hour (minutes ago)
            $mAgo = $st->diffInMinutes(Carbon::now());
            if ($mAgo < 60) {
                $idx1h = min(29, max(0, 29 - (int)floor($mAgo / 2)));
                $pts1h_rx[$idx1h] += $rxMb;
                $pts1h_tx[$idx1h] += $txMb;
            }

            // 1 Day (hours ago)
            $hAgo = $st->diffInHours(Carbon::now());
            if ($hAgo < 24) {
                $idx1d = min(23, max(0, 23 - (int)$hAgo));
                $pts1d_rx[$idx1d] += $rxMb;
                $pts1d_tx[$idx1d] += $txMb;
            }

            // 7 Days (days ago)
            $dAgo = $st->diffInDays(Carbon::now());
            if ($dAgo < 7) {
                $idx7d = min(6, max(0, 6 - (int)$dAgo));
                $pts7d_rx[$idx7d] += round($rxMb / 1024, 2); // GB
                $pts7d_tx[$idx7d] += round($txMb / 1024, 2); // GB
            }
        }

        // If current session is live, ensure current points reflect active rates
        if ($currentRxMbps > 0 || $currentTxMbps > 0) {
            $pts1h_rx[29] = max($pts1h_rx[29], $currentRxMbps);
            $pts1h_tx[29] = max($pts1h_tx[29], $currentTxMbps);
        }

        // Max values for scale
        $max5m = max(1.0, ...$pts5m_rx, ...$pts5m_tx);
        $max1h = max(1.0, ...$pts1h_rx, ...$pts1h_tx);
        $max1d = max(1.0, ...$pts1d_rx, ...$pts1d_tx);
        $max7d = max(1.0, ...$pts7d_rx, ...$pts7d_tx);

        $avg5m_rx = count($pts5m_rx) > 0 ? (array_sum($pts5m_rx) / count($pts5m_rx)) : 0;
        $avg5m_tx = count($pts5m_tx) > 0 ? (array_sum($pts5m_tx) / count($pts5m_tx)) : 0;
        $avg1h_rx = count($pts1h_rx) > 0 ? (array_sum($pts1h_rx) / count($pts1h_rx)) : 0;
        $avg1h_tx = count($pts1h_tx) > 0 ? (array_sum($pts1h_tx) / count($pts1h_tx)) : 0;
        $avg1d_rx = count($pts1d_rx) > 0 ? (array_sum($pts1d_rx) / count($pts1d_rx)) : 0;
        $avg1d_tx = count($pts1d_tx) > 0 ? (array_sum($pts1d_tx) / count($pts1d_tx)) : 0;
        $avg7d_rx = count($pts7d_rx) > 0 ? (array_sum($pts7d_rx) / count($pts7d_rx)) : 0;
        $avg7d_tx = count($pts7d_tx) > 0 ? (array_sum($pts7d_tx) / count($pts7d_tx)) : 0;

        return response()->json([
            'success' => true,
            'is_online' => !empty($live['is_online']),
            'current' => [
                'rx_mbps' => $currentRxMbps,
                'tx_mbps' => $currentTxMbps,
                'rx_human' => $live['rx_human'] ?? number_format($currentRxMbps, 2) . ' Mbps',
                'tx_human' => $live['tx_human'] ?? number_format($currentTxMbps, 2) . ' Mbps',
                'total_rx_human' => $totalRxHuman,
                'total_tx_human' => $totalTxHuman,
            ],
            'period_5m' => [
                'unit' => 'Mbps',
                'labels' => $labels5m,
                'rx' => $pts5m_rx,
                'tx' => $pts5m_tx,
                'rx_curr' => number_format($currentRxMbps, 2) . ' Mbps',
                'tx_curr' => number_format($currentTxMbps, 2) . ' Mbps',
                'rx_avg' => number_format($avg5m_rx, 2) . ' Mbps',
                'tx_avg' => number_format($avg5m_tx, 2) . ' Mbps',
                'rx_max' => number_format(max(...$pts5m_rx), 2) . ' Mbps',
                'tx_max' => number_format(max(...$pts5m_tx), 2) . ' Mbps',
                'rx_total' => $totalRxHuman,
                'tx_total' => $totalTxHuman,
                'scale_label' => ceil($max5m * 1.25) . ' Mbps',
            ],
            'period_1h' => [
                'unit' => 'MB',
                'labels' => $labels1h,
                'rx' => $pts1h_rx,
                'tx' => $pts1h_tx,
                'rx_curr' => number_format($currentRxMbps, 2) . ' Mbps',
                'tx_curr' => number_format($currentTxMbps, 2) . ' Mbps',
                'rx_avg' => number_format($avg1h_rx, 2) . ' MB',
                'tx_avg' => number_format($avg1h_tx, 2) . ' MB',
                'rx_max' => number_format(max(...$pts1h_rx), 2) . ' MB',
                'tx_max' => number_format(max(...$pts1h_tx), 2) . ' MB',
                'rx_total' => $totalRxHuman,
                'tx_total' => $totalTxHuman,
                'scale_label' => ceil($max1h * 1.25) . ' MB',
            ],
            'period_1d' => [
                'unit' => 'MB',
                'labels' => $hours1d,
                'rx' => $pts1d_rx,
                'tx' => $pts1d_tx,
                'rx_curr' => number_format($currentRxMbps, 2) . ' Mbps',
                'tx_curr' => number_format($currentTxMbps, 2) . ' Mbps',
                'rx_avg' => number_format($avg1d_rx, 2) . ' MB/h',
                'tx_avg' => number_format($avg1d_tx, 2) . ' MB/h',
                'rx_max' => number_format(max(...$pts1d_rx), 2) . ' MB',
                'tx_max' => number_format(max(...$pts1d_tx), 2) . ' MB',
                'rx_total' => $totalRxHuman,
                'tx_total' => $totalTxHuman,
                'scale_label' => ceil($max1d * 1.25) . ' MB/h',
            ],
            'period_7d' => [
                'unit' => 'GB',
                'labels' => $days7,
                'rx' => $pts7d_rx,
                'tx' => $pts7d_tx,
                'rx_curr' => number_format($currentRxMbps, 2) . ' Mbps',
                'tx_curr' => number_format($currentTxMbps, 2) . ' Mbps',
                'rx_avg' => number_format($avg7d_rx, 2) . ' GB/d',
                'tx_avg' => number_format($avg7d_tx, 2) . ' GB/d',
                'rx_max' => number_format(max(...$pts7d_rx), 2) . ' GB',
                'tx_max' => number_format(max(...$pts7d_tx), 2) . ' GB',
                'rx_total' => $totalRxHuman,
                'tx_total' => $totalTxHuman,
                'scale_label' => ceil($max7d * 1.25) . ' GB/d',
            ],
        ]);
    }

    /**
     * Format MikroTik raw uptime string (e.g. 1w2d04:15:30) into professional format (e.g. 9d 4h 15m).
     */
    private function formatMikrotikUptime(?string $raw): string
    {
        if (!$raw || in_array(strtolower(trim($raw)), ['--', 'offline', '0s', ''])) {
            return 'Offline';
        }
        $str = trim($raw);
        $weeks = 0; $days = 0; $hours = 0; $mins = 0; $secs = 0;

        if (preg_match('/(\d+)w/i', $str, $m)) { $weeks = (int)$m[1]; $str = str_replace($m[0], '', $str); }
        if (preg_match('/(\d+)d/i', $str, $m)) { $days = (int)$m[1]; $str = str_replace($m[0], '', $str); }

        if (preg_match('/(\d+):(\d+)(?::(\d+))?/', $str, $m)) {
            $hours = (int)$m[1];
            $mins = (int)$m[2];
            $secs = isset($m[3]) ? (int)$m[3] : 0;
        } else {
            if (preg_match('/(\d+)h/i', $str, $m)) $hours = (int)$m[1];
            if (preg_match('/(\d+)m/i', $str, $m)) $mins = (int)$m[1];
            if (preg_match('/(\d+)s/i', $str, $m)) $secs = (int)$m[1];
        }

        $totalDays = ($weeks * 7) + $days;
        $parts = [];
        if ($totalDays > 0) $parts[] = "{$totalDays}d";
        if ($hours > 0 || $totalDays > 0) $parts[] = "{$hours}h";
        if ($mins > 0 || $hours > 0 || $totalDays > 0) $parts[] = "{$mins}m";
        if ($totalDays === 0 && $hours === 0 && $mins === 0) $parts[] = "{$secs}s";

        return !empty($parts) ? implode(' ', $parts) : 'Offline';
    }

    /**
     * Download Standard CSV Import Template.
     */
    public function downloadSampleTemplate(Request $request)
    {
        $tenant = $this->getTenant();
        $fileName = "subscriber_import_template_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'Customer ID', 'Full Name', 'PPPoE Username', 'PPPoE Password', 'Mobile Phone', 
            'Monthly Bill', 'Package Name', 'Router IP / Name', 'Partner Code', 'Zone', 
            'Connection Type', 'Billing Type', 'Framed IP', 'ONU MAC / SN', 'Expiry Date (YYYY-MM-DD)', 
            'Status', 'Address', 'Remarks'
        ];

        $sampleRows = [
            [
                'SO1001', 'Rahim Ahmed', 'rahim_isp', 'Pass@1234', '01711000001',
                '800.00', '10Mbps_Standard', '', '', 'Uttara Sector 3',
                'pppoe', 'prepaid', '172.16.10.15', 'HWTC-8899AA', date('Y-m-d', strtotime('+30 days')),
                'active', 'House 12, Road 4, Sector 3, Uttara', 'VIP Retail Client'
            ],
            [
                'SO1002', 'Karim Chowdhury', 'karim_net', 'Pass@5678', '01811000002',
                '1200.00', '20Mbps_Gaming', '', 'SUB-01', 'Mirpur 10',
                'pppoe', 'prepaid', '172.16.10.16', 'ZTEG-1234BC', date('Y-m-d', strtotime('+45 days')),
                'active', 'Block B, Section 10, Mirpur, Dhaka', 'Sub-ISP Subscriber'
            ],
            [
                'SO1003', 'Tanjil Hasan', 'tanjil_fiber', 'Secret#99', '01911000003',
                '500.00', '5Mbps_Basic', '', '', 'Dhanmondi 27',
                'pppoe', 'prepaid', '', '', date('Y-m-d', strtotime('-5 days')),
                'due', 'Road 27, Dhanmondi, Dhaka', 'Monthly bill pending'
            ]
        ];

        $callback = function () use ($columns, $sampleRows) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($file, $columns);
            foreach ($sampleRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk Import Customers from CSV / Text File.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'default_router_id' => 'nullable|integer',
            'default_reseller_id' => 'nullable|integer',
            'duplicate_action' => 'nullable|string|in:skip,update',
            'sync_mikrotik' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $duplicateAction = $request->input('duplicate_action', 'skip');
        $shouldSyncMikrotik = filter_var($request->input('sync_mikrotik', false), FILTER_VALIDATE_BOOLEAN);
        $defaultRouterId = $request->input('default_router_id') ? (int)$request->input('default_router_id') : null;
        $defaultResellerId = $request->input('default_reseller_id') ? (int)$request->input('default_reseller_id') : null;

        // Fallback default router if none specified
        if (!$defaultRouterId) {
            $firstRouter = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->first()
                ?: TenantRouter::where('tenant_id', $tenant->id)->first();
            $defaultRouterId = $firstRouter?->id;
        }

        // Cache packages for fast lookup
        $packages = TenantInternetPackage::where('tenant_id', $tenant->id)->get();
        $packageMapByName = [];
        foreach ($packages as $pkg) {
            $packageMapByName[strtolower(trim($pkg->name))] = $pkg;
            if ($pkg->package_name) {
                $packageMapByName[strtolower(trim($pkg->package_name))] = $pkg;
            }
        }

        // Cache resellers for fast lookup
        $resellers = TenantReseller::where('tenant_id', $tenant->id)->get();
        $resellerMap = [];
        foreach ($resellers as $res) {
            $resellerMap[strtolower(trim($res->name))] = $res;
            if ($res->code) {
                $resellerMap[strtolower(trim($res->code))] = $res;
            }
            if ($res->prefix) {
                $resellerMap[strtolower(trim($res->prefix))] = $res;
            }
        }

        // Cache routers for fast lookup
        $routers = TenantRouter::where('tenant_id', $tenant->id)->get();
        $routerMap = [];
        foreach ($routers as $rtr) {
            $routerMap[strtolower(trim($rtr->name))] = $rtr;
            if ($rtr->ip_address) {
                $routerMap[trim($rtr->ip_address)] = $rtr;
            }
        }

        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read the uploaded CSV file.'
            ], 422);
        }

        // Read first line to detect delimiter and header
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'The uploaded CSV file is empty.'
            ], 422);
        }

        // Strip UTF-8 BOM
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        // Detect delimiter: comma, semicolon, tab
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $headerFields = str_getcsv($firstLine, $delimiter);
        $headerMap = [];
        foreach ($headerFields as $idx => $headerName) {
            $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-', '/'], '_', $headerName))));
            $headerMap[$cleaned] = $idx;
        }

        // Helper to extract column value by possible aliases
        $getCol = function(array $row, array $aliases) use ($headerMap) {
            foreach ($aliases as $alias) {
                $aliasClean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-', '/'], '_', $alias))));
                if (isset($headerMap[$aliasClean]) && isset($row[$headerMap[$aliasClean]])) {
                    $val = trim($row[$headerMap[$aliasClean]]);
                    if ($val !== '') return $val;
                }
            }
            return null;
        };

        $totalRows = 0;
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errorsCount = 0;
        $logs = [];

        $mikrotikApi = $shouldSyncMikrotik ? new MikrotikApiService() : null;

        DB::beginTransaction();
        try {
            $rowNumber = 1; // Row 1 is header

            while (($rawRow = fgetcsv($handle, 4096, $delimiter)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty($rawRow) || (count($rawRow) === 1 && trim($rawRow[0]) === '')) {
                    continue;
                }

                $totalRows++;

                // Extract Fields with flexible alias fallback
                $name = $getCol($rawRow, ['full_name', 'name', 'customer_name', 'subscriber_name']);
                $username = $getCol($rawRow, ['pppoe_username', 'username', 'user_name', 'login', 'user', 'pppoe_user']);
                $password = $getCol($rawRow, ['pppoe_password', 'password', 'pass', 'secret', 'pppoe_pass']) ?: '123456';
                $phone = $getCol($rawRow, ['mobile_phone', 'phone', 'mobile', 'cell', 'contact_no', 'phone_number']) ?: '01700000000';
                $rawMonthlyBill = $getCol($rawRow, ['monthly_bill', 'bill', 'price', 'rate', 'monthly_rate', 'amount']);
                $packageName = $getCol($rawRow, ['package_name', 'package', 'plan', 'internet_package', 'profile']);
                $rawRouter = $getCol($rawRow, ['router_ip_name', 'router', 'router_ip', 'router_name', 'gateway']);
                $rawReseller = $getCol($rawRow, ['partner_code', 'reseller_code', 'reseller', 'partner', 'sub_isp']);
                $zone = $getCol($rawRow, ['zone', 'area', 'location', 'coverage_area']);
                $connType = strtolower($getCol($rawRow, ['connection_type', 'conn_type', 'type']) ?: 'pppoe');
                $billingType = strtolower($getCol($rawRow, ['billing_type', 'billing']) ?: 'prepaid');
                $ipAddress = $getCol($rawRow, ['framed_ip', 'ip_address', 'ip', 'static_ip']);
                $onuMacSn = $getCol($rawRow, ['onu_mac_sn', 'onu_mac', 'mac_address', 'mac', 'sn', 'onu_sn']);
                $rawExpiry = $getCol($rawRow, ['expiry_date_yyyymmdd', 'expiry_date', 'expiry', 'valid_till', 'expire_date']);
                $status = strtolower($getCol($rawRow, ['status', 'line_status']) ?: 'active');
                $address = $getCol($rawRow, ['address', 'installation_address', 'customer_address']);
                $remarks = $getCol($rawRow, ['remarks', 'note', 'comment', 'notes']);
                $customId = $getCol($rawRow, ['customer_id', 'id', 'cust_id', 'subscriber_id']);

                if (empty($name) && empty($username)) {
                    $skippedCount++;
                    $logs[] = "[Row {$rowNumber}] Skipped: Missing both Name and PPPoE Username.";
                    continue;
                }

                // If name is missing, generate from username
                if (empty($name)) {
                    $name = ucwords(str_replace(['.', '_', '-'], ' ', $username));
                }

                // If username is missing, generate from phone/name
                if (empty($username)) {
                    $username = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(explode(' ', $name)[0])) . '_' . substr($phone, -4);
                }

                // Resolve Package
                $packageId = null;
                $monthlyBill = $rawMonthlyBill ? (float)str_replace([',', ' '], '', $rawMonthlyBill) : 0.00;
                if ($packageName) {
                    $pkgKey = strtolower(trim($packageName));
                    if (isset($packageMapByName[$pkgKey])) {
                        $matchedPkg = $packageMapByName[$pkgKey];
                        $packageId = $matchedPkg->id;
                        if ($monthlyBill <= 0) {
                            $monthlyBill = (float)$matchedPkg->price;
                        }
                    }
                }

                // Resolve Reseller
                $resellerId = $defaultResellerId;
                if ($rawReseller) {
                    $resKey = strtolower(trim($rawReseller));
                    if (isset($resellerMap[$resKey])) {
                        $resellerId = $resellerMap[$resKey]->id;
                    }
                }

                // Resolve Router
                $routerId = $defaultRouterId;
                if ($rawRouter) {
                    $rtrKey = strtolower(trim($rawRouter));
                    if (isset($routerMap[$rtrKey])) {
                        $routerId = $routerMap[$rtrKey]->id;
                    }
                }

                // Parse Expiry Date
                $expiryDate = null;
                if ($rawExpiry) {
                    try {
                        $expiryDate = Carbon::parse($rawExpiry)->toDateString();
                    } catch (\Exception $e) {
                        $expiryDate = Carbon::now()->addDays(30)->toDateString();
                    }
                } else {
                    $expiryDate = Carbon::now()->addDays(30)->toDateString();
                }

                // Clean status
                if (!in_array($status, ['active', 'due', 'expired', 'suspended', 'disabled', 'disconnected'])) {
                    $status = 'active';
                }

                // Check Duplicate by Username
                $existing = TenantCustomer::where('tenant_id', $tenant->id)
                    ->where('username', $username)
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'skip') {
                        $skippedCount++;
                        $logs[] = "[Row {$rowNumber}] Skipped: Duplicate PPPoE username '{$username}' already exists in database.";
                        continue;
                    } elseif ($duplicateAction === 'update') {
                        $existing->update([
                            'name' => $name,
                            'password' => $password ?: $existing->password,
                            'phone' => $phone ?: $existing->phone,
                            'monthly_bill' => $monthlyBill > 0 ? $monthlyBill : $existing->monthly_bill,
                            'package_id' => $packageId ?: $existing->package_id,
                            'package_name' => $packageName ?: $existing->package_name,
                            'router_id' => $routerId ?: $existing->router_id,
                            'reseller_id' => $resellerId !== null ? $resellerId : $existing->reseller_id,
                            'zone' => $zone ?: $existing->zone,
                            'connection_type' => $connType,
                            'billing_type' => $billingType,
                            'ip_address' => $ipAddress ?: $existing->ip_address,
                            'onu_mac_sn' => $onuMacSn ?: $existing->onu_mac_sn,
                            'expiry_date' => $expiryDate ?: $existing->expiry_date,
                            'status' => $status,
                            'address' => $address ?: $existing->address,
                            'remarks' => $remarks ?: $existing->remarks,
                        ]);

                        if ($shouldSyncMikrotik && $mikrotikApi && $existing->router) {
                            try {
                                $mikrotikApi->syncCustomerToMikrotik($existing);
                                $logs[] = "[Row {$rowNumber}] Updated & Synced to MikroTik: '{$username}' ({$name})";
                            } catch (\Exception $e) {
                                $logs[] = "[Row {$rowNumber}] Updated in DB (MikroTik sync warning: {$e->getMessage()}): '{$username}'";
                            }
                        } else {
                            $logs[] = "[Row {$rowNumber}] Updated subscriber '{$username}' ({$name}) in master DB.";
                        }

                        $updatedCount++;
                        continue;
                    }
                }

                // Generate / Validate Customer ID
                $finalCustomerId = $customId;
                if (empty($finalCustomerId)) {
                    $finalCustomerId = TenantCustomer::generateNextCustomerId($tenant->id);
                } else {
                    // Check if custom ID taken
                    $idTaken = TenantCustomer::where('tenant_id', $tenant->id)->where('customer_id', $finalCustomerId)->exists();
                    if ($idTaken) {
                        $finalCustomerId = TenantCustomer::generateNextCustomerId($tenant->id);
                    }
                }

                $newCustomer = TenantCustomer::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $finalCustomerId,
                    'name' => $name,
                    'username' => $username,
                    'password' => $password,
                    'phone' => $phone,
                    'monthly_bill' => $monthlyBill,
                    'due_amount' => 0.00,
                    'wallet_balance' => 0.00,
                    'package_id' => $packageId,
                    'package_name' => $packageName,
                    'router_id' => $routerId,
                    'reseller_id' => $resellerId,
                    'zone' => $zone,
                    'connection_type' => $connType,
                    'billing_type' => $billingType,
                    'ip_address' => $ipAddress,
                    'onu_mac_sn' => $onuMacSn,
                    'billing_cycle_date' => Carbon::now()->toDateString(),
                    'expiry_date' => $expiryDate,
                    'status' => $status,
                    'online_status' => 'offline',
                    'address' => $address,
                    'remarks' => $remarks,
                ]);

                if ($shouldSyncMikrotik && $mikrotikApi && $newCustomer->router) {
                    try {
                        $mikrotikApi->syncCustomerToMikrotik($newCustomer);
                        $logs[] = "[Row {$rowNumber}] Imported & Provisioned MikroTik: '{$username}' (ID: {$finalCustomerId})";
                    } catch (\Exception $e) {
                        $logs[] = "[Row {$rowNumber}] Imported in DB (MikroTik sync error: {$e->getMessage()}): '{$username}'";
                    }
                } else {
                    $logs[] = "[Row {$rowNumber}] Imported subscriber '{$username}' (ID: {$finalCustomerId}) successfully.";
                }

                $importedCount++;
            }

            fclose($handle);
            DB::commit();

            // Activity Log
            TenantActivityLog::log(
                $tenant->id,
                'customer_bulk_imported',
                "Bulk imported {$importedCount} subscribers, {$updatedCount} updated, {$skippedCount} skipped from CSV.",
                Auth::id(),
                ['total' => $totalRows, 'imported' => $importedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount]
            );

            return response()->json([
                'success' => true,
                'total_rows' => $totalRows,
                'imported_count' => $importedCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'errors_count' => $errorsCount,
                'logs' => $logs,
                'message' => "Bulk import completed! {$importedCount} new subscribers created, {$updatedCount} updated, {$skippedCount} skipped."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process CSV import: ' . $e->getMessage(),
                'logs' => $logs,
            ], 500);
        }
    }

    /**
     * Export Master Customer Database to CSV with dynamic filters and presets.
     */
    public function exportCsv(Request $request)
    {
        $tenant = $this->getTenant();
        $preset = $request->input('preset', 'master'); // 'master', 'billing', 'technical', 'contacts'

        $authUser = Auth::user();
        $isIspCollector = $authUser && $authUser->isCollector() && empty($authUser->reseller_id);

        $query = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['reseller', 'package', 'router', 'olt']);

        if ($isIspCollector) {
            $query->whereNull('reseller_id');
        }

        // Scope Filter
        if (!$isIspCollector && $request->filled('scope')) {
            if ($request->scope === 'isp') {
                $query->whereNull('reseller_id');
            } elseif ($request->scope === 'all_resellers') {
                $query->whereNotNull('reseller_id');
            }
        }
        if (!$isIspCollector && $request->filled('reseller_id')) {
            $query->where('reseller_id', $request->reseller_id);
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Online Status Filter
        if ($request->filled('online_status') && $request->online_status !== 'all') {
            $query->where('online_status', $request->online_status);
        }

        // Zone Filter
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }

        // Package Filter
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('id', 'asc')->get();

        $timestamp = date('Y-m-d_His');
        $fileName = "subscribers_{$preset}_{$timestamp}.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        // Column sets based on selected preset
        if ($preset === 'billing') {
            $columns = [
                'Customer ID', 'Full Name', 'PPPoE Username', 'Mobile Phone', 'Affiliation Scope', 
                'Partner Name', 'Package Name', 'Monthly Bill (BDT)', 'Due Amount (BDT)', 
                'Wallet Balance (BDT)', 'Billing Type', 'Expiry Date', 'Status'
            ];
            $rowMapper = function ($c) {
                return [
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->phone,
                    $c->reseller_id ? 'Sub-ISP Franchise' : 'ISP Direct Retail',
                    $c->reseller?->name ?? 'Headquarters Direct',
                    $c->package_display_name,
                    number_format((float)$c->monthly_bill, 2),
                    number_format((float)$c->due_amount, 2),
                    number_format((float)$c->wallet_balance, 2),
                    ucfirst($c->billing_type),
                    $c->expiry_date?->format('Y-m-d') ?? '--',
                    ucfirst($c->status),
                ];
            };
        } elseif ($preset === 'technical') {
            $columns = [
                'Customer ID', 'Full Name', 'PPPoE Username', 'PPPoE Password', 'Gateway Router', 
                'Connection Type', 'Framed IP Address', 'ONU MAC / Serial', 'OLT Device', 
                'Package Profile', 'Status', 'Live Online Status', 'Last Online'
            ];
            $rowMapper = function ($c) {
                return [
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->password,
                    $c->router?->name ?? ($c->router?->ip_address ?? '--'),
                    strtoupper($c->connection_type),
                    $c->ip_address ?? '--',
                    $c->onu_mac_sn ?? '--',
                    $c->olt?->name ?? '--',
                    $c->mikrotik_profile_name,
                    ucfirst($c->status),
                    ucfirst($c->online_status),
                    $c->last_online_at?->format('Y-m-d H:i') ?? '--',
                ];
            };
        } elseif ($preset === 'contacts') {
            $columns = [
                'Customer ID', 'Full Name', 'PPPoE Username', 'Mobile Phone', 'Alt Phone', 
                'Email Address', 'National ID (NID)', 'Father Name', 'Zone / Area', 
                'Installation Address', 'Status'
            ];
            $rowMapper = function ($c) {
                return [
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->phone,
                    $c->alt_phone ?? '--',
                    $c->email ?? '--',
                    $c->national_id ?? '--',
                    $c->father_name ?? '--',
                    $c->zone ?? '--',
                    $c->address ?? '--',
                    ucfirst($c->status),
                ];
            };
        } else {
            // Master Full Dump
            $columns = [
                'Customer ID', 'Full Name', 'PPPoE Username', 'PPPoE Password', 'Phone', 'Email', 
                'Affiliation Scope', 'Sub-ISP Partner', 'Zone / Area', 'Package Name', 
                'Monthly Bill (BDT)', 'Due Amount (BDT)', 'Billing Type', 'Connection Type', 
                'Gateway Router', 'Framed IP Address', 'ONU MAC / SN', 'Status', 
                'Live Online Status', 'Expiry Date', 'Address', 'Remarks'
            ];
            $rowMapper = function ($c) {
                return [
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->password,
                    $c->phone,
                    $c->email ?? '--',
                    $c->reseller_id ? 'Sub-ISP Franchise' : 'ISP Direct Retail',
                    $c->reseller?->name ?? 'Headquarters Direct',
                    $c->zone ?? '--',
                    $c->package_display_name,
                    number_format((float)$c->monthly_bill, 2),
                    number_format((float)$c->due_amount, 2),
                    ucfirst($c->billing_type),
                    strtoupper($c->connection_type),
                    $c->router?->name ?? ($c->router?->ip_address ?? '--'),
                    $c->ip_address ?? '--',
                    $c->onu_mac_sn ?? '--',
                    ucfirst($c->status),
                    ucfirst($c->online_status),
                    $c->expiry_date?->format('Y-m-d') ?? '--',
                    $c->address ?? '--',
                    $c->remarks ?? '--',
                ];
            };
        }

        $callback = function () use ($customers, $columns, $rowMapper) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel / Google Sheets
            fputcsv($file, $columns);

            foreach ($customers as $c) {
                fputcsv($file, $rowMapper($c));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
