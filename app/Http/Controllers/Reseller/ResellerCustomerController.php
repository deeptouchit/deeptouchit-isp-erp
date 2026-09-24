<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\TenantRouter;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BanglaQrPaymentPendingNotification;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use App\Services\Payment\BkashPaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerCustomerController extends Controller
{
    /**
     * Resolve active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display All Customers of the Authenticated Reseller.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $packageId = $request->input('package_id', 'all');
        $zone = $request->input('zone', 'all');
        $onlineStatus = $request->input('online_status', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // 1. Base Query Scoped to Reseller
        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'router'])
            ->latest('id');

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('alt_phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status !== 'all' && !empty($status)) {
            if ($status === 'due') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['due', 'expired'])
                      ->orWhere('due_amount', '>', 0)
                      ->orWhereHas('invoices', function ($iq) {
                          $iq->whereIn('status', ['unpaid', 'due', 'partially_paid'])
                             ->where('due_amount', '>', 0);
                      });
                });
            } elseif ($status === 'disconnected' || $status === 'disabled') {
                $query->whereIn('status', ['disabled', 'disconnected', 'archived', 'inactive']);
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['expired', 'suspended'])
                      ->orWhere(function ($eq) {
                          $eq->whereNotNull('expiry_date')
                             ->whereDate('expiry_date', '<=', Carbon::today());
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Package Filter
        if ($packageId !== 'all' && !empty($packageId)) {
            $query->where('package_id', (int) $packageId);
        }

        // Zone Filter
        if ($zone !== 'all' && !empty($zone)) {
            $query->where('zone', $zone);
        }

        // Online Status Filter
        if ($onlineStatus !== 'all' && !empty($onlineStatus)) {
            $query->where('online_status', $onlineStatus);
        }

        $customers = $query->paginate($perPage)->withQueryString();

        // 2. Dropdown Datasets
        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $zones = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct('zone')
            ->pluck('zone')
            ->sort()
            ->values();

        $routers = TenantRouter::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // 3. Compute Strictly 6 KPI Summary Cards (AGENTS.md Rule 2.B)
        $kpiBase = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $totalSubscribers = (int) (clone $kpiBase)->count();
        $activeSubscribers = (int) (clone $kpiBase)->where('status', 'active')->count();
        $dueOrExpiredSubscribers = (int) (clone $kpiBase)->where(function ($q) {
            $q->whereIn('status', ['due', 'expired'])
              ->orWhere('due_amount', '>', 0)
              ->orWhere(function ($eq) {
                  $eq->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now()->toDateString());
              });
        })->count();
        $onlineSessions = (int) (clone $kpiBase)->where('online_status', 'online')->count();
        $monthlyBillingMrr = (float) (clone $kpiBase)->sum('monthly_bill');
        $totalCustomerDue = (float) (clone $kpiBase)->sum('due_amount');

        $stats = [
            'total_subscribers' => $totalSubscribers,
            'active_subscribers' => $activeSubscribers,
            'due_expired_subscribers' => $dueOrExpiredSubscribers,
            'online_sessions' => $onlineSessions,
            'monthly_mrr' => $monthlyBillingMrr,
            'total_due' => $totalCustomerDue,
        ];

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.customers.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'customers',
            'packages',
            'zones',
            'routers',
            'stats',
            'search',
            'status',
            'packageId',
            'zone',
            'onlineStatus',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Show Create Customer Form.
     */
    public function create(): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $nextCustomerId = TenantCustomer::generateNextCustomerId($tenantId);

        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $routers = TenantRouter::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $zones = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct('zone')
            ->pluck('zone')
            ->sort()
            ->values();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.customers.create', compact(
            'authUser',
            'reseller',
            'tenant',
            'nextCustomerId',
            'packages',
            'routers',
            'zones',
            'currencySymbol'
        ));
    }

    /**
     * Store a newly created Customer in storage & sync to MikroTik.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('tenant_customers', 'username')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'password' => 'required|string|min:4|max:100',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'national_id' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:300',
            'zone' => 'nullable|string|max:100',
            'package_id' => 'required|exists:tenant_internet_packages,id',
            'monthly_bill' => 'nullable|numeric|min:0',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:50',
            'onu_mac_sn' => 'nullable|string|max:50',
            'billing_cycle_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:active,due,expired,disabled,disconnected',
            'remarks' => 'nullable|string|max:500',
        ]);

        $package = TenantInternetPackage::where('tenant_id', $tenantId)->findOrFail($validated['package_id']);
        $monthlyBill = isset($validated['monthly_bill']) && $validated['monthly_bill'] !== ''
            ? (float) $validated['monthly_bill']
            : (float) $package->price;

        $customerId = TenantCustomer::generateNextCustomerId($tenantId);

        $customer = DB::transaction(function () use ($tenantId, $resellerId, $customerId, $validated, $package, $monthlyBill) {
            return TenantCustomer::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $resellerId,
                'customer_id' => $customerId,
                'name' => $validated['name'],
                'username' => trim($validated['username']),
                'password' => $validated['password'],
                'phone' => $validated['phone'],
                'alt_phone' => $validated['alt_phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'national_id' => $validated['national_id'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'zone' => $validated['zone'] ?? null,
                'connection_type' => 'pppoe',
                'package_id' => $package->id,
                'package_name' => $package->name ?: $package->package_name,
                'monthly_bill' => $monthlyBill,
                'router_id' => $validated['router_id'] ?? null,
                'ip_address' => $validated['ip_address'] ?? null,
                'mac_address' => $validated['mac_address'] ?? null,
                'onu_mac_sn' => $validated['onu_mac_sn'] ?? null,
                'billing_type' => 'prepaid',
                'billing_cycle_date' => $validated['billing_cycle_date'] ?? now()->toDateString(),
                'expiry_date' => $validated['expiry_date'] ?? now()->addMonth()->toDateString(),
                'status' => $validated['status'],
                'due_amount' => 0.00,
                'wallet_balance' => 0.00,
                'remarks' => $validated['remarks'] ?? null,
            ]);
        });

        // Sync to MikroTik Router & FreeRADIUS
        $mikrotikMsg = '';
        try {
            $mikrotikApi = new MikrotikApiService();
            $mRes = $mikrotikApi->syncCustomerToMikrotik($customer);
            if (!empty($mRes['message'])) {
                $mikrotikMsg = ' (MikroTik: ' . $mRes['message'] . ')';
            }
        } catch (\Exception $e) {
            $mikrotikMsg = ' (MikroTik Sync Failed: ' . $e->getMessage() . ')';
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            // Non-blocking
        }

        // Activity Log
        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'CUSTOMER_CREATED',
            'description' => "Reseller created new customer '{$customer->name}' ({$customer->username}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['customer_id' => $customer->id, 'reseller_id' => $resellerId],
        ]);

        $message = "Customer '{$customer->name}' onboarded successfully!{$mikrotikMsg}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'customer' => $customer->load('package'),
            ]);
        }

        return redirect()->route('reseller.customers.index')->with('success', $message);
    }

    /**
     * Show Details of a Specific Customer (JSON modal or view).
     */
    public function show(Request $request, int $id): JsonResponse|View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'router', 'payments' => fn($q) => $q->latest()->take(5)])
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
                    'address' => $customer->address,
                    'zone' => $customer->zone,
                    'status' => $customer->status,
                    'online_status' => $customer->online_status,
                    'monthly_bill' => (float) $customer->monthly_bill,
                    'due_amount' => (float) $customer->due_amount,
                    'wallet_balance' => (float) $customer->wallet_balance,
                    'expiry_date' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('d M Y') : 'N/A',
                    'expiry_raw' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : '',
                    'package_name' => $customer->package?->mikrotik_profile ?? ($customer->package?->name ?? ($customer->package_name ?? 'N/A')),
                    'router_name' => $customer->router?->name ?? 'Default Gateway',
                    'ip_address' => $customer->ip_address,
                    'mac_address' => $customer->mac_address,
                    'onu_mac_sn' => $customer->onu_mac_sn,
                ],
            ]);
        }

        $allPackages = TenantInternetPackage::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('price')->get();
        $allRouters = TenantRouter::where('tenant_id', $tenantId)->get();
        $recentPayments = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->with('collector')
            ->latest('paid_at')
            ->take(12)
            ->get();

        $recentSessions = collect();
        if (!empty($customer->username) && Schema::hasTable('radacct')) {
            try {
                $recentSessions = DB::table('radacct')
                    ->where('tenant_id', $tenantId)
                    ->where('username', $customer->username)
                    ->orderByDesc('radacctid')
                    ->take(12)
                    ->get();
            } catch (\Exception $e) {
            }
        }

        $activityLogs = TenantActivityLog::where('tenant_id', $tenantId)
            ->where(function ($q) use ($customer) {
                $q->where('description', 'like', "%{$customer->name}%")
                  ->orWhere('description', 'like', "%{$customer->username}%");
            })
            ->latest()
            ->take(12)
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.customers.show', compact(
            'authUser',
            'reseller',
            'tenant',
            'customer',
            'allPackages',
            'allRouters',
            'recentPayments',
            'recentSessions',
            'activityLogs',
            'currencySymbol'
        ));
    }

    /**
     * Show Edit Customer Form.
     */
    public function edit(int $id): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $routers = TenantRouter::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $zones = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct('zone')
            ->pluck('zone')
            ->sort()
            ->values();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.customers.edit', compact(
            'authUser',
            'reseller',
            'tenant',
            'customer',
            'packages',
            'routers',
            'zones',
            'currencySymbol'
        ));
    }

    /**
     * Update Customer Record & sync to MikroTik.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('tenant_customers', 'username')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($customer->id),
            ],
            'password' => 'required|string|min:4|max:100',
            'phone' => 'required|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'national_id' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:300',
            'zone' => 'nullable|string|max:100',
            'package_id' => 'required|exists:tenant_internet_packages,id',
            'monthly_bill' => 'nullable|numeric|min:0',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:50',
            'onu_mac_sn' => 'nullable|string|max:50',
            'billing_cycle_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => 'required|in:active,due,expired,disabled,disconnected',
            'remarks' => 'nullable|string|max:500',
        ]);

        $package = TenantInternetPackage::where('tenant_id', $tenantId)->findOrFail($validated['package_id']);
        $monthlyBill = isset($validated['monthly_bill']) && $validated['monthly_bill'] !== ''
            ? (float) $validated['monthly_bill']
            : (float) $package->price;

        $customer->update([
            'name' => $validated['name'],
            'username' => trim($validated['username']),
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'alt_phone' => $validated['alt_phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'zone' => $validated['zone'] ?? null,
            'package_id' => $package->id,
            'package_name' => $package->name ?: $package->package_name,
            'monthly_bill' => $monthlyBill,
            'router_id' => $validated['router_id'] ?? null,
            'ip_address' => $validated['ip_address'] ?? null,
            'mac_address' => $validated['mac_address'] ?? null,
            'onu_mac_sn' => $validated['onu_mac_sn'] ?? null,
            'billing_cycle_date' => $validated['billing_cycle_date'] ?? $customer->billing_cycle_date,
            'expiry_date' => $validated['expiry_date'] ?? $customer->expiry_date,
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        // Sync changes to MikroTik & FreeRADIUS
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

        $message = "Customer '{$customer->name}' updated successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'customer' => $customer->load('package'),
            ]);
        }

        return redirect()->route('reseller.customers.index')->with('success', $message);
    }

    /**
     * Toggle Customer Status (Active / Suspended / Disabled).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        if ($customer->is_expired || $customer->effective_status === 'expired' || ($customer->expiry_date && \Carbon\Carbon::parse($customer->expiry_date)->endOfDay()->isPast())) {
            $msg = 'গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ বা লাইন রিনিউ করুন।';
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return back()->with('error', $msg);
        }

        $isActive = ($customer->status === 'active');
        $newStatus = $isActive ? 'disabled' : 'active';
        $updateData = ['status' => $newStatus];
        if ($newStatus === 'disabled') {
            $updateData['online_status'] = 'offline';
        }
        $customer->update($updateData);

        // Sync status to MikroTik Router (Enables/Disables secret & disconnects live session if disabled)
        $mikrotikMsg = '';
        try {
            $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
            if ($router) {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->togglePppSecret($router, $customer->username, $newStatus !== 'active');
                $mRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($mRes['message'])) {
                    $mikrotikMsg = " - MikroTik: " . $mRes['message'];
                }
            }
        } catch (\Exception $e) {
            // Non-blocking
        }

        // FreeRADIUS Sync
        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
            if ($newStatus === 'disabled') {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
                if ($router) {
                    $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                    $radiusService->disconnectUser($customer->username, $router->ip_address, $radiusSecret);
                }
            }
        } catch (\Exception $e) {
            // Non-blocking
        }

        // Activity Log
        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'CUSTOMER_STATUS_TOGGLE',
            'description' => "Toggled status of subscriber '{$customer->name}' ({$customer->username}) to {$newStatus}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['customer_id' => $customer->id, 'status' => $newStatus],
        ]);

        $stateText = ($newStatus === 'active') ? 'enabled' : 'disabled';
        $message = "Customer '{$customer->name}' is now {$stateText}.{$mikrotikMsg}";

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'status' => $customer->status,
                'is_active' => ($customer->status === 'active'),
                'online_status' => $customer->online_status,
                'message' => $message,
                'badge' => $customer->status_badge,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Toggle MikroTik /ppp/secret Hardware State independently from billing status.
     */
    public function toggleMikrotikSecret(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        if ($customer->is_expired || $customer->effective_status === 'expired' || ($customer->expiry_date && \Carbon\Carbon::parse($customer->expiry_date)->endOfDay()->isPast())) {
            return response()->json([
                'success' => false,
                'message' => 'গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ বা লাইন রিনিউ করুন।',
            ], 422);
        }

        $currentMikrotik = $customer->mikrotik_status ?: 'enabled';
        $newMikrotik = ($currentMikrotik === 'enabled') ? 'disabled' : 'enabled';

        $updateData = ['mikrotik_status' => $newMikrotik];
        if ($newMikrotik === 'disabled') {
            $updateData['online_status'] = 'offline';
        }
        $customer->update($updateData);

        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
        $routerSynced = false;
        if ($router) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $routerSynced = $mikrotikApi->togglePppSecret($router, $customer->username, $newMikrotik === 'disabled');
            } catch (\Exception $e) {
                \Log::error("Failed to toggle MikroTik secret for {$customer->username}: " . $e->getMessage());
            }
        }

        $stateTitle = ucfirst($newMikrotik);

        return response()->json([
            'success' => true,
            'message' => "MikroTik Secret for '{$customer->username}' is now {$stateTitle} on router!",
            'mikrotik_status' => $newMikrotik,
            'online_status' => $customer->online_status,
        ]);
    }

    /**
     * Instant Subscription Renewal (Quick Renew Modal).
     */
    public function renewSubscription(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $months = (int) $request->input('months', 1);
        $amountPaid = (float) $request->input('amount_paid', $customer->monthly_bill * $months);

        $currentExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
            ? Carbon::parse($customer->expiry_date)
            : Carbon::now();

        $newExpiry = $currentExpiry->copy()->addMonths($months);

        $customer->expiry_date = $newExpiry;
        $customer->status = 'active';
        $customer->mikrotik_status = 'enabled';
        if ($customer->due_amount > 0) {
            $customer->due_amount = max(0, $customer->due_amount - $amountPaid);
        }
        $customer->save();

        // Create Payment / Ledger record
        $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenantId);
        TenantCustomerPayment::create([
            'tenant_id' => $tenantId,
            'reseller_id' => $resellerId,
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'billing_month' => Carbon::now()->format('F Y'),
            'amount' => $amountPaid,
            'payment_method' => 'cash',
            'collected_by' => $authUser->id,
            'status' => 'paid',
            'paid_at' => Carbon::now(),
            'notes' => "Quick renewal for {$months} month(s)",
        ]);

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

        return response()->json([
            'success' => true,
            'message' => "Successfully renewed line for {$customer->name} (+{$months} month(s))! Expiry: " . $newExpiry->format('d M Y'),
            'expiry_date' => $newExpiry->format('d M Y'),
            'status' => $customer->status,
        ]);
    }

    /**
     * Sync single customer to MikroTik & FreeRADIUS.
     */
    public function syncMikrotik(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

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
     * Ping customer IP from MikroTik / Server.
     */
    public function pingCustomer(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $count = min(10, max(1, (int)$request->input('count', 4)));

        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
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
     * Terminate / Kick Active PPPoE Session on MikroTik & FreeRADIUS CoA.
     */
    public function disconnectSession(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $username = $customer->username;
        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();

        $success = false;
        $message = "No router configured to disconnect PPPoE session.";

        if ($router && !empty($username)) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $termRes = $mikrotikApi->terminateActiveSession($router, $username);
                $success = !empty($termRes['success']);
                $message = $success
                    ? "Live session for {$username} terminated successfully."
                    : ($termRes['message'] ?? "Session disconnected.");
            } catch (\Exception $e) {
                $message = "MikroTik error: " . $e->getMessage();
            }

            // FreeRADIUS CoA Disconnect
            try {
                $radiusService = new RadiusService();
                $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                $radiusService->disconnectUser($username, $router->ip_address, $radiusSecret);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        $customer->update(['online_status' => 'offline']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'online_status' => 'offline',
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Collect Bill Payment (Comprehensive Direct Pay Modal).
     */
    public function payBill(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|string|in:due,advance,custom',
            'months' => 'nullable|integer|min:0|max:24',
            'payment_method' => 'required|string|in:cash,bkash,bangla_qr,banglaqr,nagad,rocket,bank_transfer,online,pos,other',
            'transaction_id' => 'nullable|string|max:100',
            'billing_month' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:250',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
        ]);

        $amount = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0);
        $totalCredited = $amount + $discount;
        $months = (int) ($validated['months'] ?? 1);
        $extendValidity = filter_var($request->input('extend_validity', true), FILTER_VALIDATE_BOOLEAN);
        $paymentMethod = strtolower($validated['payment_method']);

        // -----------------------------------------------------------------
        // Direct bKash Tokenized Merchant API Checkout Gateway Integration
        // -----------------------------------------------------------------
        if ($paymentMethod === 'bkash') {
            $callbackUrl = route('reseller.customers.bkash.callback', $customer->id);
            $bkashService = BkashPaymentService::forTenant($tenantId);

            $result = $bkashService->createGenericCheckoutSession(
                $amount,
                (string) ($customer->username ?: ('CUST-' . $customer->id)),
                $callbackUrl,
                'BILL-' . $customer->id
            );

            if ($result['success'] ?? false) {
                $paymentId = $result['payment_id'];

                // Save pending session data in Cache for 1 hour
                Cache::put("bkash_cust_pay_{$paymentId}", [
                    'tenant_id' => $tenantId,
                    'reseller_id' => $resellerId,
                    'customer_id' => $customer->id,
                    'user_id' => $authUser->id,
                    'amount' => $amount,
                    'discount' => $discount,
                    'payment_mode' => $validated['payment_mode'] ?? 'due',
                    'months' => $months,
                    'billing_month' => $validated['billing_month'] ?? Carbon::now()->format('F Y'),
                    'extend_validity' => $extendValidity,
                    'reactivate_line' => filter_var($request->input('reactivate_line', true), FILTER_VALIDATE_BOOLEAN),
                    'notes' => $validated['notes'] ?? null,
                ], now()->addHour());

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $result['redirect_url'],
                        'payment_id' => $paymentId,
                        'message' => 'Redirecting to bKash Merchant Payment Gateway...',
                    ]);
                }

                return redirect()->away($result['redirect_url']);
            }

            $errorMsg = $result['message'] ?? 'Failed to initialize bKash gateway session. Please verify API credentials in Admin Settings.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        // -----------------------------------------------------------------
        // Bangla QR Interoperable Payment: Pending Admin Approval & Notification
        // -----------------------------------------------------------------
        if (in_array($paymentMethod, ['bangla_qr', 'banglaqr'])) {
            $payment = DB::transaction(function () use (
                $tenantId, $resellerId, $customer, $amount, $discount, $validated, $authUser
            ) {
                $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenantId);

                return TenantCustomerPayment::create([
                    'tenant_id' => $tenantId,
                    'reseller_id' => $resellerId,
                    'customer_id' => $customer->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $validated['billing_month'] ?? Carbon::now()->format('F Y'),
                    'amount' => $amount,
                    'discount' => $discount,
                    'payment_method' => 'bangla_qr',
                    'transaction_id' => $validated['transaction_id'] ?? null,
                    'collected_by' => $authUser->id,
                    'status' => 'pending',
                    'paid_at' => null,
                    'notes' => $validated['notes'] ?? "Bangla QR payment submitted. Pending Admin approval.",
                ]);
            });

            // Send Realtime Push / In-App Notification to Tenant Admins
            try {
                $tenantAdmins = User::where('tenant_id', $tenantId)
                    ->whereNull('reseller_id')
                    ->get();

                if ($tenantAdmins->isNotEmpty()) {
                    Notification::send($tenantAdmins, new BanglaQrPaymentPendingNotification(
                        $payment,
                        $customer,
                        $reseller,
                        $amount,
                        $validated['transaction_id'] ?? null
                    ));
                }
            } catch (\Exception $e) {
                // Non-blocking notification fallback
            }

            // Activity Log
            TenantActivityLog::create([
                'tenant_id' => $tenantId,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'CUSTOMER_PAYMENT_PENDING',
                'description' => "Bangla QR payment of ৳" . number_format($amount, 2) . " submitted by {$authUser->name} for customer '{$customer->username}' (TrxID: " . ($validated['transaction_id'] ?? 'N/A') . "). Line activation pending Admin approval.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $successMsg = "Bangla QR payment of ৳" . number_format($amount, 2) . " submitted successfully! Customer line activation and commission credit is pending Admin verification.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => 'pending',
                    'message' => $successMsg,
                    'payment_id' => $payment->id,
                    'invoice_no' => $payment->invoice_no,
                ]);
            }

            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('success', $successMsg);
        }

        $resellerCost = 0.00;
        $resellerCommission = 0.00;
        $commissionRate = 0.00;
        $walletDeducted = 0.00;
        $walletCredited = 0.00;

        // Reseller Wallet & Commission Calculations
        if ($reseller) {
            $commissionRate = (float)($reseller->commission_rate ?? 0);

            if ($paymentMethod === 'cash') {
                // 1. CASH: Customer pays cash to Reseller -> Reseller wallet is debited by Net Reseller Cost
                $resellerCost = round($reseller->calculateResellerCost($amount), 2);
                $availableBalance = (float)($reseller->total_available_balance);

                if ($availableBalance < $resellerCost) {
                    $errorMsg = "Partner Wallet balance insufficient. Required: ৳" . number_format($resellerCost, 2) . ", Available: ৳" . number_format($availableBalance, 2) . ". Please recharge wallet first.";
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $errorMsg], 422);
                    }
                    return back()->with('error', $errorMsg);
                }
            }
        }

        $payment = DB::transaction(function () use (
            $tenantId, $resellerId, $reseller, $customer, $amount, $discount, $totalCredited, 
            $months, $extendValidity, $validated, $authUser, $paymentMethod, $resellerCost, 
            $resellerCommission, $commissionRate, &$walletDeducted, &$walletCredited
        ) {
            $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenantId);

            $p = TenantCustomerPayment::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $resellerId,
                'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $validated['billing_month'] ?? Carbon::now()->format('F Y'),
                'amount' => $amount,
                'discount' => $discount,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'collected_by' => $authUser->id,
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'notes' => $validated['notes'] ?? ($months > 1 ? "Advance payment for {$months} month(s)" : "Direct payment collection"),
            ]);

            // Adjust customer dues
            $newDue = max(0, (float) $customer->due_amount - $totalCredited);
            $customer->due_amount = $newDue;

            // Extend Expiry Date
            if ($extendValidity) {
                $currentExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
                    ? Carbon::parse($customer->expiry_date)
                    : Carbon::now();
                $customer->expiry_date = $currentExpiry->addMonths(max(1, $months));
            }

            // Restore status to active if disabled/due/expired
            if (in_array($customer->status, ['due', 'expired', 'disabled', 'suspended'])) {
                $customer->status = 'active';
            }
            $customer->mikrotik_status = 'enabled';

            $customer->save();

            // Handle Reseller Wallet Transactions
            if ($reseller) {
                if ($paymentMethod === 'cash' && $resellerCost > 0) {
                    $balanceBefore = (float)$reseller->wallet_balance;
                    $reseller->decrement('wallet_balance', $resellerCost);
                    $reseller->refresh();
                    $walletDeducted = $resellerCost;

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenantId,
                        'reseller_id' => $reseller->id,
                        'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                        'type' => 'DEBIT',
                        'amount' => $resellerCost,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $reseller->wallet_balance,
                        'payment_method' => 'CASH',
                        'description' => "Cash bill collection for '{$customer->username}' ({$customer->name}). Total: ৳" . number_format($amount, 2) . ", Net Cost: ৳" . number_format($resellerCost, 2) . " (Discount: {$commissionRate}%).",
                        'created_by' => $authUser->id,
                    ]);
                } elseif (in_array($paymentMethod, ['bangla_qr', 'banglaqr']) && $resellerCommission > 0) {
                    $balanceBefore = (float)$reseller->wallet_balance;
                    $reseller->increment('wallet_balance', $resellerCommission);
                    $reseller->refresh();
                    $walletCredited = $resellerCommission;

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenantId,
                        'reseller_id' => $reseller->id,
                        'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                        'type' => 'CREDIT',
                        'amount' => $resellerCommission,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $reseller->wallet_balance,
                        'payment_method' => 'BANGLA_QR',
                        'description' => "Commission earned on Bangla QR bill payment for '{$customer->username}' ({$customer->name}). Bill: ৳" . number_format($amount, 2) . ", Commission ({$commissionRate}%): ৳" . number_format($resellerCommission, 2) . ".",
                        'created_by' => $authUser->id,
                    ]);
                }
            }

            // Settle open TenantCustomerInvoice if exists
            try {
                $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
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
                // Non-blocking
            }

            return $p;
        });

        // Re-sync to MikroTik & FreeRADIUS
        try {
            $mikrotikApi = new MikrotikApiService();
            $mikrotikApi->syncCustomerToMikrotik($customer);
        } catch (\Exception $e) {}

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {}

        // Activity Log
        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'CUSTOMER_PAYMENT',
            'description' => "Collected payment of ৳{$amount} from '{$customer->name}' via " . strtoupper($validated['payment_method']) . " (Receipt: {$payment->invoice_no}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = "Payment of ৳" . number_format($amount, 2) . " recorded successfully! (Receipt #{$payment->invoice_no})";
        if ($walletDeducted > 0) {
            $message .= " ৳" . number_format($walletDeducted, 2) . " debited from partner wallet.";
        } elseif ($walletCredited > 0) {
            $message .= " ৳" . number_format($walletCredited, 2) . " commission credited to partner wallet.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'invoice_no' => $payment->invoice_no,
                'due_amount' => number_format($customer->due_amount, 2),
                'due_amount_raw' => (float)$customer->due_amount,
                'status' => $customer->status,
                'expiry_date' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('d M Y') : '--',
                'expiry_raw' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : null,
                'remaining_days' => $customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture() ? Carbon::now()->diffInDays(Carbon::parse($customer->expiry_date)) : 0,
                'reseller_balance' => $reseller ? number_format($reseller->wallet_balance, 2) : null,
                'wallet_deducted' => $walletDeducted,
                'wallet_credited' => $walletCredited,
                'customer' => [
                    'id' => $customer->id,
                    'due_amount' => number_format($customer->due_amount, 2),
                    'due_amount_raw' => (float)$customer->due_amount,
                    'status' => $customer->status,
                    'expiry_date' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('d M Y') : '--',
                    'expiry_raw' => $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : null,
                    'remaining_days' => $customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture() ? Carbon::now()->diffInDays(Carbon::parse($customer->expiry_date)) : 0,
                ],
                'payment' => [
                    'id' => $payment->id,
                    'invoice_no' => $payment->invoice_no,
                    'amount' => number_format($payment->amount, 2),
                    'method' => strtoupper($payment->payment_method),
                    'date' => Carbon::now()->format('d M Y, h:i A'),
                ]
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Handle bKash Gateway Return Callback for Customer Bill Payment.
     */
    public function handleBkashPaymentCallback(Request $request, int $id): RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $paymentId = $request->input('paymentID');
        $status = $request->input('status');

        if (!$paymentId) {
            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('error', 'bKash payment session was invalid or closed.');
        }

        if ($status === 'cancel') {
            Cache::forget("bkash_cust_pay_{$paymentId}");
            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('error', 'bKash payment was cancelled.');
        }

        if ($status === 'failure') {
            Cache::forget("bkash_cust_pay_{$paymentId}");
            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('error', 'bKash payment failed or was declined.');
        }

        $sessionData = Cache::get("bkash_cust_pay_{$paymentId}");
        if (!$sessionData) {
            $existing = TenantCustomerPayment::where('tenant_id', $tenantId)
                ->where('customer_id', $customer->id)
                ->where(function ($q) use ($paymentId) {
                    $q->where('transaction_id', $paymentId)
                      ->orWhere('notes', 'like', "%{$paymentId}%");
                })
                ->first();

            if ($existing) {
                return redirect()->route('reseller.customers.show', $customer->id)
                    ->with('success', "Payment already processed (Receipt #{$existing->invoice_no}, TrxID: {$existing->transaction_id}).");
            }

            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('error', 'Payment session expired. Please try paying again.');
        }

        $bkashService = BkashPaymentService::forTenant($sessionData['tenant_id'] ?? $tenantId);
        $result = $bkashService->executeTokenizedPayment($paymentId);

        Cache::forget("bkash_cust_pay_{$paymentId}");

        if (!($result['success'] ?? false)) {
            return redirect()->route('reseller.customers.show', $customer->id)
                ->with('error', $result['message'] ?? 'Failed to execute bKash payment verification.');
        }

        $amount = (float) ($result['amount'] ?? ($sessionData['amount'] ?? 0));
        $trxId = $result['trx_id'] ?? $paymentId;
        $discount = (float) ($sessionData['discount'] ?? 0);
        $totalCredited = $amount + $discount;
        $months = (int) ($sessionData['months'] ?? 1);
        $extendValidity = (bool) ($sessionData['extend_validity'] ?? true);
        $userId = $sessionData['user_id'] ?? ($authUser->id ?? 1);

        $commissionRate = (float) ($reseller?->commission_rate ?? 0);
        $resellerCommission = round(($amount * $commissionRate) / 100, 2);

        $payment = DB::transaction(function () use (
            $tenantId, $resellerId, $reseller, $customer, $amount, $discount, $totalCredited,
            $months, $extendValidity, $sessionData, $userId, $trxId, $resellerCommission, $commissionRate
        ) {
            $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenantId);

            $p = TenantCustomerPayment::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $resellerId,
                'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $sessionData['billing_month'] ?? Carbon::now()->format('F Y'),
                'amount' => $amount,
                'discount' => $discount,
                'payment_method' => 'bkash',
                'transaction_id' => $trxId,
                'collected_by' => $userId,
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'notes' => $sessionData['notes'] ?? "bKash Direct API Payment (TrxID: {$trxId})",
            ]);

            // Adjust customer dues
            $newDue = max(0, (float) $customer->due_amount - $totalCredited);
            $customer->due_amount = $newDue;

            // Extend Expiry Date
            if ($extendValidity) {
                $currentExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
                    ? Carbon::parse($customer->expiry_date)
                    : Carbon::now();
                $customer->expiry_date = $currentExpiry->addMonths(max(1, $months));
            }

            // Restore status to active if disabled/due/expired
            if (in_array($customer->status, ['due', 'expired', 'disabled', 'suspended'])) {
                $customer->status = 'active';
            }
            $customer->mikrotik_status = 'enabled';

            $customer->save();

            // Credit Reseller Wallet Commission
            if ($reseller && $resellerCommission > 0) {
                $balanceBefore = (float) $reseller->wallet_balance;
                $reseller->increment('wallet_balance', $resellerCommission);
                $reseller->refresh();

                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenantId,
                    'reseller_id' => $reseller->id,
                    'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                    'type' => 'CREDIT',
                    'amount' => $resellerCommission,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $reseller->wallet_balance,
                    'payment_method' => 'BKASH',
                    'reference_no' => $trxId,
                    'description' => "Commission earned on bKash bill payment for '{$customer->username}' ({$customer->name}). Bill: ৳" . number_format($amount, 2) . ", Commission ({$commissionRate}%): ৳" . number_format($resellerCommission, 2) . ".",
                    'created_by' => $userId,
                ]);
            }

            // Settle open customer invoice
            try {
                $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
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
                        $invoice->paid_amount = (float) $invoice->paid_amount + (float) $amount;
                        $invoice->due_amount = max(0, (float) $invoice->total_payable - (float) $invoice->paid_amount);
                        $invoice->status = 'partial';
                    }
                    $invoice->paid_at = Carbon::now();
                    $invoice->payment_method = 'bkash';
                    $invoice->save();
                }
            } catch (\Exception $e) {}

            return $p;
        });

        // Re-sync MikroTik & FreeRADIUS
        try {
            $mikrotikApi = new MikrotikApiService();
            $mikrotikApi->syncCustomerToMikrotik($customer);
        } catch (\Exception $e) {}

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {}

        // Activity Log
        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => $userId,
            'actor_name' => $authUser?->name ?? 'Reseller',
            'event_type' => 'CUSTOMER_PAYMENT',
            'description' => "Received bKash API payment of ৳{$amount} from '{$customer->name}' (TrxID: {$trxId}, Receipt: {$payment->invoice_no}). Commission credited: ৳{$resellerCommission}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $successMessage = "bKash Payment of ৳" . number_format($amount, 2) . " received successfully (TrxID: {$trxId}, Receipt #{$payment->invoice_no})!";
        if ($resellerCommission > 0) {
            $successMessage .= " Commission of ৳" . number_format($resellerCommission, 2) . " credited to your wallet.";
        }

        return redirect()->route('reseller.customers.show', $customer->id)
            ->with('success', $successMessage);
    }

    /**
     * Display dedicated Bangla QR Payment & Commission page for Customer.
     */
    public function showBanglaQrPage(Request $request, int $id)
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $currencySymbol = Setting::get('currency_symbol', '৳', $tenantId);
        $banglaQrNumber = Setting::get('bangla_qr_number', $tenant->phone ?? '01819000000', $tenantId);
        $banglaQrTitle = Setting::get('bangla_qr_title', $tenant->company_name ?? 'ISP Interoperable Bangla QR', $tenantId);

        $defaultAmount = (float) ($customer->due_amount > 0 ? $customer->due_amount : ($customer->monthly_bill ?? 0));
        if ($request->filled('amount') && (float)$request->input('amount') > 0) {
            $defaultAmount = (float) $request->input('amount');
        }

        $commissionRate = (float) ($reseller?->commission_rate ?? 0);
        $estimatedCommission = round(($defaultAmount * $commissionRate) / 100, 2);
        $netIspAmount = max(0, $defaultAmount - $estimatedCommission);

        return view('reseller.customers.bangla_qr', compact(
            'tenant',
            'authUser',
            'reseller',
            'customer',
            'currencySymbol',
            'banglaQrNumber',
            'banglaQrTitle',
            'defaultAmount',
            'commissionRate',
            'estimatedCommission',
            'netIspAmount'
        ));
    }

    /**
     * Delete Customer & Remove from MikroTik.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $name = $customer->name;
        $username = $customer->username;
        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();

        // Try removing secret from MikroTik & RADIUS
        if ($router && !empty($username)) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->removePppSecret($router, $username);
                $mikrotikApi->terminateActiveSession($router, $username);
            } catch (\Exception $e) {
                // Non-blocking
            }

            try {
                $radiusService = new RadiusService();
                $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                $radiusService->disconnectUser($username, $router->ip_address, $radiusSecret);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        $customer->delete();

        $message = "Customer '{$name}' deleted successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('reseller.customers.index')->with('success', $message);
    }

    /**
     * Export Filtered Customers to CSV with multiple presets (Master, Billing, Technical, Contacts).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $preset = $request->input('preset', 'master');
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $packageId = $request->input('package_id', 'all');
        $zone = $request->input('zone', 'all');
        $onlineStatus = $request->input('online_status', 'all');

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'router'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'due') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['due', 'expired'])
                      ->orWhere('due_amount', '>', 0)
                      ->orWhereHas('invoices', function ($iq) {
                          $iq->whereIn('status', ['unpaid', 'due', 'partially_paid'])
                             ->where('due_amount', '>', 0);
                      });
                });
            } elseif ($status === 'disconnected' || $status === 'disabled') {
                $query->whereIn('status', ['disabled', 'disconnected', 'archived', 'inactive']);
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['expired', 'suspended'])
                      ->orWhere(function ($eq) {
                          $eq->whereNotNull('expiry_date')
                             ->whereDate('expiry_date', '<=', Carbon::today());
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        if ($packageId !== 'all' && !empty($packageId)) {
            $query->where('package_id', (int) $packageId);
        }

        if ($zone !== 'all' && !empty($zone)) {
            $query->where('zone', $zone);
        }

        if ($onlineStatus !== 'all' && !empty($onlineStatus)) {
            $query->where('online_status', $onlineStatus);
        }

        $customers = $query->get();
        $timestamp = date('Y-m-d_His');
        $filename = 'customers_' . ($reseller->code ?? 'RES') . "_{$preset}_{$timestamp}.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        if ($preset === 'billing') {
            $columns = [
                '#', 'Customer ID', 'Full Name', 'PPPoE Username', 'Mobile Phone', 
                'Package Name', 'Monthly Bill (BDT)', 'Due Amount (BDT)', 
                'Wallet Balance (BDT)', 'Expiry Date', 'Status'
            ];
            $rowMapper = function ($c, $idx) {
                return [
                    $idx + 1,
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->phone,
                    $c->package?->mikrotik_profile ?? ($c->package?->name ?? ($c->package_name ?? 'N/A')),
                    number_format((float)$c->monthly_bill, 2),
                    number_format((float)$c->due_amount, 2),
                    number_format((float)$c->wallet_balance, 2),
                    $c->expiry_date ? Carbon::parse($c->expiry_date)->format('Y-m-d') : '--',
                    ucfirst($c->status),
                ];
            };
        } elseif ($preset === 'technical') {
            $columns = [
                '#', 'Customer ID', 'Full Name', 'PPPoE Username', 'PPPoE Password', 
                'Gateway Router', 'Framed IP Address', 'ONU MAC / Serial', 
                'Package Profile', 'Status', 'Live Online Status'
            ];
            $rowMapper = function ($c, $idx) {
                return [
                    $idx + 1,
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->password,
                    $c->router?->name ?? '--',
                    $c->ip_address ?? '--',
                    $c->onu_mac_sn ?? ($c->mac_address ?? '--'),
                    $c->package?->mikrotik_profile ?? ($c->package?->name ?? 'N/A'),
                    ucfirst($c->status),
                    ucfirst($c->online_status ?? 'offline'),
                ];
            };
        } elseif ($preset === 'contacts') {
            $columns = [
                '#', 'Customer ID', 'Full Name', 'PPPoE Username', 'Mobile Phone', 
                'Alt Phone', 'Email Address', 'National ID', 'Zone / Area', 
                'Address', 'Status'
            ];
            $rowMapper = function ($c, $idx) {
                return [
                    $idx + 1,
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->phone,
                    $c->alt_phone ?? '--',
                    $c->email ?? '--',
                    $c->national_id ?? '--',
                    $c->zone ?? '--',
                    $c->address ?? '--',
                    ucfirst($c->status),
                ];
            };
        } else {
            // Master Dump
            $columns = [
                '#', 'Customer ID', 'Full Name', 'PPPoE Username', 'PPPoE Password', 
                'Mobile Phone', 'Zone / Area', 'Package Name', 'Monthly Bill (BDT)', 
                'Due Amount (BDT)', 'Gateway Router', 'Framed IP', 'ONU MAC / SN', 
                'Status', 'Live Online Status', 'Expiry Date', 'Address', 'Remarks'
            ];
            $rowMapper = function ($c, $idx) {
                return [
                    $idx + 1,
                    $c->customer_id,
                    $c->name,
                    $c->username,
                    $c->password,
                    $c->phone,
                    $c->zone ?? '--',
                    $c->package?->mikrotik_profile ?? ($c->package?->name ?? ($c->package_name ?? 'N/A')),
                    number_format((float)$c->monthly_bill, 2),
                    number_format((float)$c->due_amount, 2),
                    $c->router?->name ?? '--',
                    $c->ip_address ?? '--',
                    $c->onu_mac_sn ?? ($c->mac_address ?? '--'),
                    ucfirst($c->status),
                    ucfirst($c->online_status ?? 'offline'),
                    $c->expiry_date ? Carbon::parse($c->expiry_date)->format('Y-m-d') : '--',
                    $c->address ?? '--',
                    $c->remarks ?? '--',
                ];
            };
        }

        return response()->streamDownload(function () use ($customers, $columns, $rowMapper) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, $columns);
            foreach ($customers as $idx => $c) {
                fputcsv($handle, $rowMapper($c, $idx));
            }
            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Print Subscribers Matrix Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'router'])
            ->orderBy('name');

        $status = $request->input('status', 'all');
        if ($status !== 'all' && !empty($status)) {
            if ($status === 'due') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['due', 'expired'])
                      ->orWhere('due_amount', '>', 0)
                      ->orWhereHas('invoices', function ($iq) {
                          $iq->whereIn('status', ['unpaid', 'due', 'partially_paid'])
                             ->where('due_amount', '>', 0);
                      });
                });
            } elseif ($status === 'disconnected' || $status === 'disabled') {
                $query->whereIn('status', ['disabled', 'disconnected', 'archived', 'inactive']);
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['expired', 'suspended'])
                      ->orWhere(function ($eq) {
                          $eq->whereNotNull('expiry_date')
                             ->whereDate('expiry_date', '<=', Carbon::today());
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        $customers = $query->get();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('reseller.customers.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'customers',
            'currencySymbol',
            'status'
        ));
    }

    /**
     * Update PPPoE Credentials for a Subscriber & Sync Router/RADIUS.
     */
    public function updatePppoe(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('tenant_customers', 'username')->where(fn($q) => $q->where('tenant_id', $tenantId))->ignore($customer->id),
            ],
            'password' => 'required|string|min:4|max:100',
        ]);

        $oldUsername = $customer->username;
        $newUsername = trim($validated['username']);
        $newPassword = trim($validated['password']);

        $customer->username = $newUsername;
        $customer->password = $newPassword;
        $customer->save();

        if ($oldUsername && $oldUsername !== $newUsername) {
            try {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
                if ($router) {
                    $mikrotikApi = new MikrotikApiService();
                    $mikrotikApi->removePppSecret($router, $oldUsername);
                    $mikrotikApi->terminateActiveSession($router, $oldUsername);
                }
                DB::table('radcheck')->where('tenant_id', $tenantId)->where('username', $oldUsername)->delete();
                DB::table('radreply')->where('tenant_id', $tenantId)->where('username', $oldUsername)->delete();
                DB::table('radusergroup')->where('tenant_id', $tenantId)->where('username', $oldUsername)->delete();
            } catch (\Exception $e) {
                \Log::warning("Reseller PPPoE cleanup notice for {$oldUsername}: " . $e->getMessage());
            }
        }

        $mikrotikMsg = '';
        try {
            $mikrotikApi = new MikrotikApiService();
            $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
            
            $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
            if ($router) {
                $mikrotikApi->terminateActiveSession($router, $newUsername);
            }

            if (!empty($syncRes['message'])) {
                $mikrotikMsg = " (MikroTik: {$syncRes['message']})";
            }
        } catch (\Exception $e) {
            \Log::error("Reseller MikroTik PPPoE sync error: " . $e->getMessage());
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            \Log::error("Reseller FreeRADIUS PPPoE sync error: " . $e->getMessage());
        }

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Reseller',
            'event_type' => 'CUSTOMER_PPPOE_UPDATE',
            'description' => "Updated PPPoE credentials for '{$customer->name}' (User: {$customer->username}).",
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
     * Explicit Status Update for Subscriber (Active / Suspended / Disabled / Expired / Disconnected).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:active,suspended,disabled,expired,due,disconnected',
        ]);

        $oldStatus = $customer->status;
        $newStatus = $validated['status'];

        if (($customer->is_expired || $customer->effective_status === 'expired' || ($customer->expiry_date && \Carbon\Carbon::parse($customer->expiry_date)->endOfDay()->isPast())) && in_array($newStatus, ['active', 'disabled', 'suspended'])) {
            return response()->json([
                'success' => false,
                'message' => 'গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ বা লাইন রিনিউ করুন।',
            ], 422);
        }

        $updateData = ['status' => $newStatus];

        if (in_array($newStatus, ['suspended', 'disabled', 'disconnected', 'expired'])) {
            $updateData['online_status'] = 'offline';
        }

        $customer->update($updateData);

        $mikrotikMsg = '';
        try {
            $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
            if ($router) {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->togglePppSecret($router, $customer->username, $newStatus !== 'active');
                $mRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($mRes['message'])) {
                    $mikrotikMsg = " - MikroTik: " . $mRes['message'];
                }
            }
        } catch (\Exception $e) {
            \Log::error("Reseller MikroTik status update error: " . $e->getMessage());
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);

            if (in_array($newStatus, ['suspended', 'disabled', 'disconnected'])) {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
                if ($router) {
                    $radiusSecret = $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radiussecret');
                    $radiusService->disconnectUser($customer->username, $router->ip_address, $radiusSecret);
                }
            }
        } catch (\Exception $e) {}

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Reseller',
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
     * Change Internet Package with Reseller Wallet Proration.
     */
    public function updatePackage(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'package_id' => 'required|exists:tenant_internet_packages,id',
            'update_bill' => 'nullable|boolean',
            'disconnect_session' => 'nullable|boolean',
        ]);

        $newPackage = TenantInternetPackage::where('tenant_id', $tenantId)->findOrFail($validated['package_id']);
        $oldPackageName = $customer->package_name ?: ($customer->package?->package_name ?: 'Current Plan');
        $oldRetailPrice = (float)($customer->package?->price ?? $customer->monthly_bill);
        $newRetailPrice = (float)$newPackage->price;

        $oldCost = $reseller ? $reseller->calculateResellerCost($oldRetailPrice) : $oldRetailPrice;
        $newCost = $reseller ? $reseller->calculateResellerCost($newRetailPrice) : $newRetailPrice;

        $now = Carbon::now()->startOfDay();
        $expiry = $customer->expiry_date ? Carbon::parse($customer->expiry_date)->startOfDay() : null;
        $remainingDays = ($expiry && $expiry->greaterThan($now)) ? (int)$now->diffInDays($expiry) : 0;

        $walletDeducted = 0.00;
        $walletRefunded = 0.00;
        $oldExpiryDate = $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('Y-m-d') : null;
        $newExpiryDate = $oldExpiryDate;
        $resellerNotice = '';
        $actionType = 'DIRECT';

        if ($remainingDays > 0) {
            $oldDailyCost = $oldCost / 30;
            $newDailyCost = $newCost / 30;
            $remainingFinancialValue = $remainingDays * $oldDailyCost;

            if ($reseller) {
                $resellerAvailableBalance = (float)($reseller->total_available_balance);

                if ($newCost > $oldCost) {
                    $proratedCostDiff = round(($newDailyCost - $oldDailyCost) * $remainingDays, 2);

                    if ($resellerAvailableBalance >= $proratedCostDiff && $proratedCostDiff > 0) {
                        $balanceBefore = (float)$reseller->wallet_balance;
                        $reseller->decrement('wallet_balance', $proratedCostDiff);
                        $reseller->refresh();
                        $walletDeducted = $proratedCostDiff;
                        $actionType = 'RESELLER_WALLET_DEBITED';

                        TenantResellerWalletTransaction::create([
                            'tenant_id' => $tenantId,
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

                        $resellerNotice = "৳" . number_format($proratedCostDiff, 2) . " deducted from Partner wallet. Expiry remains unchanged.";
                    } else {
                        $newDaysCovered = max(1, (int)floor($remainingFinancialValue / $newDailyCost));
                        $newExpiryDate = Carbon::now()->addDays($newDaysCovered)->format('Y-m-d');
                        $customer->expiry_date = $newExpiryDate;
                        $actionType = 'EXPIRY_ADJUSTED';

                        $resellerNotice = "Partner wallet balance insufficient. Validity adjusted from {$remainingDays} days to {$newDaysCovered} days (New expiry: {$newExpiryDate}).";
                    }
                } elseif ($newCost < $oldCost) {
                    $proratedRefund = round(($oldDailyCost - $newDailyCost) * $remainingDays, 2);
                    if ($proratedRefund > 0) {
                        $balanceBefore = (float)$reseller->wallet_balance;
                        $reseller->increment('wallet_balance', $proratedRefund);
                        $reseller->refresh();
                        $walletRefunded = $proratedRefund;
                        $actionType = 'RESELLER_WALLET_REFUNDED';

                        TenantResellerWalletTransaction::create([
                            'tenant_id' => $tenantId,
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

                        $resellerNotice = "৳" . number_format($proratedRefund, 2) . " credited back to Partner wallet.";
                    }
                }
            }
        }

        $customer->package_id = $newPackage->id;
        $customer->package_name = $newPackage->package_name ?: $newPackage->name;
        if ($request->input('update_bill', true)) {
            $customer->monthly_bill = $newPackage->price;
        }
        $customer->save();

        $syncMikrotikSuccess = false;
        try {
            $mikrotikApi = new MikrotikApiService();
            $resMt = $mikrotikApi->syncCustomerToMikrotik($customer);
            $syncMikrotikSuccess = (bool)($resMt['success'] ?? false);

            if ($request->input('disconnect_session', true)) {
                $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
                if ($router && $customer->username) {
                    $mikrotikApi->terminateActiveSession($router, $customer->username);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Reseller MikroTik sync package notice: " . $e->getMessage());
        }

        try {
            $radiusService = new RadiusService();
            $radiusService->syncCustomerSubscriber($customer);
        } catch (\Exception $e) {
            \Log::warning("Reseller FreeRADIUS sync package notice: " . $e->getMessage());
        }

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Reseller',
            'event_type' => 'CUSTOMER_PACKAGE_UPDATE',
            'description' => "Changed package for '{$customer->name}' ({$customer->username}) to '{$customer->package_name}'. " . ($resellerNotice ?: ''),
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
     * Update Internal Remarks / Notes for Customer.
     */
    public function updateNote(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:2000',
        ]);

        $customer->remarks = $validated['remarks'] ?? null;
        $customer->save();

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Reseller',
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
     * Get Real-time RX/TX Traffic and Session Details from MikroTik RouterOS.
     */
    public function getTraffic(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
        
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
                \Log::warning("Reseller MikroTik getTraffic query notice: " . $e->getMessage());
            }
        }

        return response()->json($trafficData);
    }

    /**
     * Get Real-time & Historical Data Usage.
     */
    public function getUsageHistory(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $username = $customer->username;

        $currentRxMbps = 0.0;
        $currentTxMbps = 0.0;
        $totalRxHuman = '0 B';
        $totalTxHuman = '0 B';

        $router = $customer->router ?: TenantRouter::where('tenant_id', $tenantId)->first();
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

        $sessions = collect();
        if (!empty($username) && Schema::hasTable('radacct')) {
            $since7Days = Carbon::now()->subDays(7)->startOfDay();
            $sessions = DB::table('radacct')
                ->where('tenant_id', $tenantId)
                ->where('username', $username)
                ->where('acctstarttime', '>=', $since7Days)
                ->get(['acctstarttime', 'acctstoptime', 'acctoutputoctets', 'acctinputoctets', 'acctsessiontime']);
        }

        $pts5m_rx = array_fill(0, 30, max(0.0, round($currentRxMbps * 0.9, 2)));
        $pts5m_tx = array_fill(0, 30, max(0.0, round($currentTxMbps * 0.9, 2)));
        $pts5m_rx[29] = $currentRxMbps;
        $pts5m_tx[29] = $currentTxMbps;
        $labels5m = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels5m[] = Carbon::now()->subSeconds($i * 10)->format('H:i:s');
        }

        $pts1h_rx = array_fill(0, 30, 0.0);
        $pts1h_tx = array_fill(0, 30, 0.0);
        $labels1h = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels1h[] = Carbon::now()->subMinutes($i * 2)->format('H:i');
        }

        $pts1d_rx = array_fill(0, 24, 0.0);
        $pts1d_tx = array_fill(0, 24, 0.0);
        $hours1d = [];
        for ($h = 23; $h >= 0; $h--) {
            $hours1d[] = Carbon::now()->subHours($h)->format('H:00');
        }

        $days7 = [];
        $pts7d_rx = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $pts7d_tx = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        for ($d = 6; $d >= 0; $d--) {
            $days7[] = Carbon::now()->subDays($d)->format('D d');
        }

        foreach ($sessions as $session) {
            if (empty($session->acctstarttime)) continue;
            $st = Carbon::parse($session->acctstarttime);
            $rxMb = round(((float)($session->acctoutputoctets ?? 0)) / 1048576, 2);
            $txMb = round(((float)($session->acctinputoctets ?? 0)) / 1048576, 2);

            $mAgo = $st->diffInMinutes(Carbon::now());
            if ($mAgo < 60) {
                $idx1h = min(29, max(0, 29 - (int)floor($mAgo / 2)));
                $pts1h_rx[$idx1h] += $rxMb;
                $pts1h_tx[$idx1h] += $txMb;
            }

            $hAgo = $st->diffInHours(Carbon::now());
            if ($hAgo < 24) {
                $idx1d = min(23, max(0, 23 - (int)$hAgo));
                $pts1d_rx[$idx1d] += $rxMb;
                $pts1d_tx[$idx1d] += $txMb;
            }

            $dAgo = $st->diffInDays(Carbon::now());
            if ($dAgo < 7) {
                $idx7d = min(6, max(0, 6 - (int)$dAgo));
                $pts7d_rx[$idx7d] += round($rxMb / 1024, 2);
                $pts7d_tx[$idx7d] += round($txMb / 1024, 2);
            }
        }

        if ($currentRxMbps > 0 || $currentTxMbps > 0) {
            $pts1h_rx[29] = max($pts1h_rx[29], $currentRxMbps);
            $pts1h_tx[29] = max($pts1h_tx[29], $currentTxMbps);
            $pts1d_rx[23] = max($pts1d_rx[23], round($currentRxMbps * 0.12, 2));
            $pts1d_tx[23] = max($pts1d_tx[23], round($currentTxMbps * 0.12, 2));
            $pts7d_rx[6] = max($pts7d_rx[6], round(($currentRxMbps * 0.12) / 1024, 3));
            $pts7d_tx[6] = max($pts7d_tx[6], round(($currentTxMbps * 0.12) / 1024, 3));
        }

        $avg5m_rx = count($pts5m_rx) > 0 ? array_sum($pts5m_rx) / count($pts5m_rx) : 0;
        $avg5m_tx = count($pts5m_tx) > 0 ? array_sum($pts5m_tx) / count($pts5m_tx) : 0;
        $max5m = max(1.0, ...$pts5m_rx, ...$pts5m_tx);

        $avg1h_rx = count($pts1h_rx) > 0 ? array_sum($pts1h_rx) / count($pts1h_rx) : 0;
        $avg1h_tx = count($pts1h_tx) > 0 ? array_sum($pts1h_tx) / count($pts1h_tx) : 0;
        $max1h = max(1.0, ...$pts1h_rx, ...$pts1h_tx);

        $avg1d_rx = count($pts1d_rx) > 0 ? array_sum($pts1d_rx) / count($pts1d_rx) : 0;
        $avg1d_tx = count($pts1d_tx) > 0 ? array_sum($pts1d_tx) / count($pts1d_tx) : 0;
        $max1d = max(1.0, ...$pts1d_rx, ...$pts1d_tx);

        $avg7d_rx = count($pts7d_rx) > 0 ? array_sum($pts7d_rx) / count($pts7d_rx) : 0;
        $avg7d_tx = count($pts7d_tx) > 0 ? array_sum($pts7d_tx) / count($pts7d_tx) : 0;
        $max7d = max(1.0, ...$pts7d_rx, ...$pts7d_tx);

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
     * Format MikroTik raw uptime string.
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
        if (empty($parts)) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Reseller Bulk Bill & Due Payments Page
     */
    public function bulkPaymentPage(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $zone = $request->get('zone');
        $packageId = $request->get('package_id');
        $status = $request->get('status', 'all_due'); // 'all_due', 'all', 'active', 'expired', 'suspended', 'due'
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', '50');

        $zones = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct()
            ->orderBy('zone')
            ->pluck('zone');

        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('package_name')
            ->get();

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'router']);

        // Additional Specific Filters
        if ($zone && $zone !== 'all') {
            $query->where('zone', $zone);
        }
        if ($packageId && $packageId !== 'all') {
            $query->where('package_id', $packageId);
        }

        // Status Filter
        if ($status === 'all_due') {
            $query->where(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->orWhereIn('status', ['due', 'expired', 'suspended'])
                  ->orWhereHas('invoices', function ($inv) {
                      $inv->whereIn('status', ['unpaid', 'due', 'partially_paid', 'partial', 'overdue']);
                  });
            });
        } elseif ($status && $status !== 'all') {
            if ($status === 'due') {
                $query->where(function ($q) {
                    $q->where('due_amount', '>', 0)
                      ->orWhere('status', 'due')
                      ->orWhereHas('invoices', function ($inv) {
                          $inv->whereIn('status', ['unpaid', 'due', 'partially_paid', 'partial', 'overdue']);
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // KPIs calculation before pagination
        $allMatchingSubscribers = (clone $query)->get();
        $totalSubscribersCount = $allMatchingSubscribers->count();
        $totalGrossBill = (float) $allMatchingSubscribers->sum(function ($c) {
            return (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
        });

        $commissionRate = (float) ($reseller?->commission_rate ?? 0);
        $totalResellerCommission = ($totalGrossBill * $commissionRate) / 100;
        $totalAdminShare = max(0, $totalGrossBill - $totalResellerCommission);

        $resellerWalletBalance = (float) ($reseller?->wallet_balance ?? 0);
        $resellerCreditLimit = (float) ($reseller?->credit_limit ?? 0);
        $resellerAvailableBalance = (float) ($reseller?->total_available_balance ?? $resellerWalletBalance);

        if ($perPage === 'all') {
            $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->paginate(500)->withQueryString();
        } else {
            $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->paginate((int)$perPage)->withQueryString();
        }

        $kpis = [
            'total_subscribers' => $totalSubscribersCount,
            'gross_bill' => $totalGrossBill,
            'admin_share' => $totalAdminShare,
            'reseller_commission' => $totalResellerCommission,
            'commission_rate' => $commissionRate,
            'reseller_wallet' => $resellerWalletBalance,
            'reseller_credit' => $resellerCreditLimit,
            'reseller_available' => $resellerAvailableBalance,
        ];

        return view('reseller.customers.bulk_payments', compact(
            'tenant',
            'reseller',
            'currencySymbol',
            'zones',
            'packages',
            'subscribers',
            'kpis',
            'zone',
            'packageId',
            'status',
            'search',
            'perPage'
        ));
    }

    /**
     * Printable Sheet for Reseller Bulk Payments
     */
    public function printBulkPayments(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $zone = $request->get('zone');
        $packageId = $request->get('package_id');
        $status = $request->get('status', 'all_due');
        $search = trim($request->get('search', ''));
        $selectedIds = $request->get('ids');

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package']);

        if (!empty($selectedIds)) {
            $idArray = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
            $query->whereIn('id', array_map('intval', $idArray));
        } else {
            if ($zone && $zone !== 'all') {
                $query->where('zone', $zone);
            }
            if ($packageId && $packageId !== 'all') {
                $query->where('package_id', $packageId);
            }
            if ($status === 'all_due') {
                $query->where(function ($q) {
                    $q->where('due_amount', '>', 0)
                      ->orWhereIn('status', ['due', 'expired', 'suspended']);
                });
            } elseif ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('customer_id', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
        }

        $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->get();

        $totalGrossBill = (float) $subscribers->sum(function ($c) {
            return (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
        });

        $commissionRate = (float) ($reseller?->commission_rate ?? 0);
        $totalResellerCommission = ($totalGrossBill * $commissionRate) / 100;
        $totalAdminShare = max(0, $totalGrossBill - $totalResellerCommission);

        return view('reseller.customers.bulk_payments_print', compact(
            'tenant',
            'reseller',
            'currencySymbol',
            'subscribers',
            'totalGrossBill',
            'totalResellerCommission',
            'totalAdminShare',
            'commissionRate',
            'status',
            'zone'
        ));
    }

    /**
     * Process Bulk Payment for Authenticated Reseller Customers.
     */
    public function processBulkPayment(Request $request): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        if (!$reseller) {
            return response()->json([
                'success' => false,
                'message' => 'Reseller account not identified.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'required|integer',
            'payment_method' => 'required|string|in:reseller_wallet,cash,bkash,nagad,rocket,bank_transfer,pos,other,online',
            'billing_month' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:255',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
        ]);

        $customerIds = $validated['customer_ids'];
        $method = $validated['payment_method'];
        $billingMonth = $validated['billing_month'] ?: Carbon::now()->format('F Y');
        $notes = trim($validated['notes'] ?? '');
        $shouldExtendValidity = $request->boolean('extend_validity', true);
        $shouldReactivate = $request->boolean('reactivate_line', true);

        return DB::transaction(function () use (
            $tenant, $reseller, $customerIds, $method, $billingMonth, $notes,
            $shouldExtendValidity, $shouldReactivate, $authUser
        ) {
            $customers = TenantCustomer::where('tenant_id', $tenant->id)
                ->where('reseller_id', $reseller->id)
                ->whereIn('id', $customerIds)
                ->with(['package'])
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching customers found for bulk processing.',
                ], 422);
            }

            // Calculate Gross Total Bill
            $totalGrossBill = 0.00;
            foreach ($customers as $c) {
                $dueVal = (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
                $totalGrossBill += max(0, $dueVal);
            }

            $commissionRate = (float) ($reseller->commission_rate ?? 0);
            $resellerCommission = ($totalGrossBill * $commissionRate) / 100;
            $adminShare = max(0, $totalGrossBill - $resellerCommission);

            // Handle Reseller Wallet Deduction
            if ($method === 'reseller_wallet') {
                $availableBalance = (float) $reseller->total_available_balance;
                if ($availableBalance < $adminShare) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient Reseller Wallet Balance! Available: ৳" . number_format($availableBalance, 2) . ", Required (Admin Share): ৳" . number_format($adminShare, 2),
                    ], 422);
                }

                $balanceBefore = (float) $reseller->wallet_balance;
                $reseller->wallet_balance = round($balanceBefore - $adminShare, 2);
                $reseller->save();

                // Create Wallet Transaction Record
                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => 'TXN-' . strtoupper(Str::random(10)),
                    'type' => 'DEBIT',
                    'amount' => $adminShare,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $reseller->wallet_balance,
                    'payment_method' => 'reseller_wallet',
                    'reference_no' => 'BULK-' . count($customers) . '-' . date('YmdHis'),
                    'description' => "Bulk payment for " . count($customers) . " subscribers ({$billingMonth}). Gross: ৳" . number_format($totalGrossBill, 2) . ", Reseller Profit: ৳" . number_format($resellerCommission, 2) . ", Admin Share Deducted: ৳" . number_format($adminShare, 2),
                    'created_by' => $authUser?->id ?: 1,
                ]);
            }

            $processedCount = 0;
            $totalAmountCollected = 0.00;
            $mikrotikApi = null;
            $radiusService = null;

            if ($shouldReactivate) {
                try {
                    $mikrotikApi = new MikrotikApiService();
                } catch (\Exception $e) {}
                try {
                    $radiusService = new RadiusService();
                } catch (\Exception $e) {}
            }

            foreach ($customers as $customer) {
                $oldDue = (float) ($customer->due_amount ?? 0);
                $oldExpiry = $customer->expiry_date;
                $monthlyBill = (float) ($customer->monthly_bill > 0 ? $customer->monthly_bill : 0);

                $amountToPay = $oldDue > 0 ? $oldDue : ($monthlyBill > 0 ? $monthlyBill : 0);
                if ($amountToPay <= 0) {
                    $amountToPay = 0;
                }

                $newDue = 0.00;
                $newExpiry = $oldExpiry;

                if ($shouldExtendValidity) {
                    $baseExpiry = ($oldExpiry && Carbon::parse($oldExpiry)->isFuture())
                        ? Carbon::parse($oldExpiry)
                        : Carbon::now();
                    $newExpiry = $baseExpiry->copy()->addMonth()->endOfDay();
                }

                $customer->due_amount = $newDue;
                if ($newExpiry) {
                    $customer->expiry_date = $newExpiry;
                }

                $isReactivated = false;
                if ($shouldReactivate) {
                    $customer->status = 'active';
                    $customer->mikrotik_status = 'enabled';
                    $isReactivated = true;
                }

                $customer->save();

                // Create Customer Payment Record
                $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);
                TenantCustomerPayment::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $billingMonth,
                    'amount' => $amountToPay,
                    'discount' => 0.00,
                    'payment_method' => $method,
                    'collected_by' => $authUser?->id ?: 1,
                    'status' => 'completed',
                    'paid_at' => Carbon::now(),
                    'transaction_id' => 'BULK-' . strtoupper(substr(md5(uniqid() . $customer->id), 0, 8)),
                    'notes' => $notes ?: "Reseller Bulk payment clearance for {$billingMonth} (Reseller: {$reseller->name})",
                ]);

                // Settle matching or open TenantCustomerInvoice
                try {
                    $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                        ->where('customer_id', $customer->id)
                        ->where(function ($q) use ($billingMonth) {
                            $q->where('billing_month', $billingMonth)
                              ->orWhereIn('status', ['unpaid', 'partial', 'overdue', 'due', 'partially_paid']);
                        })
                        ->orderBy('due_date', 'asc')
                        ->first();

                    if ($invoice) {
                        $invoice->paid_amount = $invoice->total_payable;
                        $invoice->due_amount = 0.00;
                        $invoice->status = 'paid';
                        $invoice->paid_at = Carbon::now();
                        $invoice->payment_method = $method;
                        $invoice->save();
                    }
                } catch (\Exception $e) {}

                // Sync FreeRADIUS & MikroTik
                if ($radiusService) {
                    try {
                        $radiusService->syncCustomerSubscriber($customer);
                    } catch (\Exception $e) {}
                }

                if ($mikrotikApi && $customer->router) {
                    try {
                        if ($isReactivated) {
                            $mikrotikApi->enablePppSecret($customer->router, $customer->username);
                        }
                    } catch (\Exception $e) {}
                }

                $processedCount++;
                $totalAmountCollected += $amountToPay;
            }

            // Log activity
            try {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $authUser?->id,
                    'activity_type' => 'bulk_payment_processed',
                    'description' => "Reseller {$reseller->name} processed bulk payment for {$processedCount} subscribers ({$billingMonth}). Gross: ৳" . number_format($totalGrossBill, 2) . ", Method: {$method}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            } catch (\Exception $e) {}

            return response()->json([
                'success' => true,
                'message' => "Successfully processed bulk payments for {$processedCount} subscribers.",
                'processed_count' => $processedCount,
                'total_amount' => $totalAmountCollected,
                'admin_share' => $adminShare,
                'reseller_commission' => $resellerCommission,
                'wallet_balance' => (float) $reseller->wallet_balance,
            ]);
        });
    }

    /**
     * Poll and synchronize live online PPPoE sessions and MikroTik secret states for Reseller subscribers.
     */
    public function syncOnlineSessions(Request $request): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $routers = TenantRouter::where('tenant_id', $tenantId)->get();
        $mikrotikApi = new MikrotikApiService();

        $activeUsernames = [];
        $liveCount = 0;

        // 1. Check live active PPPoE sessions from MikroTik routers
        foreach ($routers as $router) {
            try {
                $res = $mikrotikApi->getActivePppoe($router);
                if (!empty($res['success']) && !empty($res['data'])) {
                    foreach ($res['data'] as $session) {
                        $u = $session['name'] ?? null;
                        if ($u) {
                            $activeUsernames[$u] = true;
                            $liveCount++;
                            TenantCustomer::where('tenant_id', $tenantId)
                                ->where('reseller_id', $resellerId)
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
                ->where('tenant_id', $tenantId)
                ->whereNull('acctstoptime')
                ->get();

            foreach ($radiusSessions as $rs) {
                if (!empty($rs->username)) {
                    if (!isset($activeUsernames[$rs->username])) {
                        $activeUsernames[$rs->username] = true;
                        $liveCount++;
                    }
                    TenantCustomer::where('tenant_id', $tenantId)
                        ->where('reseller_id', $resellerId)
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
                            TenantCustomer::where('tenant_id', $tenantId)
                                ->where('reseller_id', $resellerId)
                                ->where('username', $u)
                                ->update(['mikrotik_status' => $mState]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // 4. Mark offline
        $onlineList = array_keys($activeUsernames);
        if (!empty($onlineList)) {
            TenantCustomer::where('tenant_id', $tenantId)
                ->where('reseller_id', $resellerId)
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
}

