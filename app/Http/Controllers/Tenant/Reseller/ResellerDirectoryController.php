<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\TenantInternetPackage;
use App\Models\TenantCustomer;
use App\Models\TenantCoverageZone;
use App\Models\User;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResellerDirectoryController extends Controller
{
    /**
     * Display a listing of Sub-ISPs / Resellers.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, __('Tenant record not found.'));
        }

        $search = $request->input('search');
        $billingType = $request->input('billing_type');
        $status = $request->input('status');
        $walletState = $request->input('wallet_state');
        $hasPrefix = $request->input('has_prefix');
        $perPage = (int) $request->input('per_page', 20);

        // 1. Base Query
        $query = TenantReseller::where('tenant_id', $tenant->id);

        // 2. Filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('prefix', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($billingType) {
            $query->where('billing_type', $billingType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($walletState === 'positive') {
            $query->where('wallet_balance', '>', 0);
        } elseif ($walletState === 'zero_negative') {
            $query->where('wallet_balance', '<=', 0);
        } elseif ($walletState === 'credit_exceeded') {
            $query->whereRaw('wallet_balance < -credit_limit');
        }

        if ($hasPrefix === 'yes') {
            $query->whereNotNull('prefix')->where('prefix', '!=', '');
        } elseif ($hasPrefix === 'no') {
            $query->where(function ($q) {
                $q->whereNull('prefix')->orWhere('prefix', '');
            });
        }

        $activeMoreCount = ($request->filled('wallet_state') ? 1 : 0) + ($request->filled('has_prefix') ? 1 : 0);
        $isMoreOpen = $activeMoreCount > 0;

        // 3. Pagination
        $resellers = $query->latest('id')->paginate($perPage)->withQueryString();

        // 4. Calculate 6-Card Metric KPIs
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->get();
        
        $totalResellers = $allResellers->count();
        $activeResellers = $allResellers->where('status', 'active')->count();
        $prepaidResellers = $allResellers->where('billing_type', 'PREPAID_WALLET')->count();
        $postpaidResellers = $allResellers->whereIn('billing_type', ['POSTPAID_MONTHLY', 'BANDWIDTH_WHOLESALE'])->count();
        $totalWalletBalance = (float) $allResellers->sum('wallet_balance');
        $totalMonthlySoftwareFees = (float) $allResellers->sum('monthly_panel_charge');

        // 5. Plan Quota Resolution
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $resellerQuota = $plan ? (int) $plan->reseller_limit : 5;
        $quotaUsed = $totalResellers;
        $quotaRemaining = max(0, $resellerQuota - $quotaUsed);
        $isQuotaReached = ($resellerQuota > 0 && $quotaUsed >= $resellerQuota);

        // Next default code for quick add modal
        $nextCode = TenantReseller::generateNextCode($tenant->id);

        return view('tenant.resellers.index', compact(
            'tenant',
            'plan',
            'resellers',
            'totalResellers',
            'activeResellers',
            'prepaidResellers',
            'postpaidResellers',
            'totalWalletBalance',
            'totalMonthlySoftwareFees',
            'resellerQuota',
            'quotaUsed',
            'quotaRemaining',
            'isQuotaReached',
            'nextCode',
            'search',
            'billingType',
            'status',
            'walletState',
            'hasPrefix',
            'activeMoreCount',
            'isMoreOpen',
            'perPage'
        ));
    }

    /**
     * Store a newly created Reseller in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        // Enforce ISP Tenant Plan Reseller Quota via PlanQuotaService
        $resellerQuotaCheck = PlanQuotaService::checkQuota($tenant, 'reseller');
        if (!$resellerQuotaCheck['allowed']) {
            return PlanQuotaService::denyResponse($request, $resellerQuotaCheck['message']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'prefix' => 'nullable|string|max:20',
            'contact_person' => 'required|string|max:255',
            'mobile' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'billing_type' => 'required|in:PREPAID_WALLET,POSTPAID_MONTHLY,BANDWIDTH_WHOLESALE',
            'wallet_balance' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'monthly_panel_charge' => 'nullable|numeric|min:0',
            'panel_expiry_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,suspended',
            'notes' => 'nullable|string',
            'create_portal_user' => 'nullable|boolean',
            'portal_password' => 'nullable|string|min:6',
        ]);

        // Clean prefix (alphanumeric only, lowercase)
        $prefix = !empty($validated['prefix']) ? Str::slug($validated['prefix'], '') : null;

        // Check unique code within tenant
        $exists = TenantReseller::where('tenant_id', $tenant->id)
            ->where('code', $validated['code'])
            ->exists();
        if ($exists) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Reseller code :code already exists.', ['code' => $validated['code']]),
                ], 422);
            }
            return back()->withErrors(['code' => __('Reseller code already exists.')])->withInput();
        }

        // Check unique prefix within tenant
        if ($prefix) {
            $prefixExists = TenantReseller::where('tenant_id', $tenant->id)
                ->where('prefix', $prefix)
                ->exists();
            if ($prefixExists) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Customer Username prefix :prefix is already used by another reseller.', ['prefix' => $prefix]),
                    ], 422);
                }
                return back()->withErrors(['prefix' => __('Prefix already used by another reseller.')])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $reseller = TenantReseller::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'prefix' => $prefix,
                'contact_person' => $validated['contact_person'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'billing_type' => $validated['billing_type'],
                'wallet_balance' => $validated['wallet_balance'] ?? 0.00,
                'credit_limit' => $validated['credit_limit'] ?? 0.00,
                'commission_rate' => $validated['commission_rate'] ?? 0.00,
                'monthly_panel_charge' => $validated['monthly_panel_charge'] ?? 0.00,
                'panel_expiry_date' => $validated['panel_expiry_date'] ?? null,
                'panel_billing_status' => 'ACTIVE',
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Optional portal admin user creation
            if (!empty($validated['create_portal_user']) && !empty($validated['email'])) {
                $userExists = User::where('email', $validated['email'])->exists();
                if (!$userExists) {
                    User::create([
                        'tenant_id' => $tenant->id,
                        'name' => $validated['contact_person'] ?: $validated['name'],
                        'email' => $validated['email'],
                        'phone' => $validated['mobile'],
                        'password' => Hash::make($validated['portal_password'] ?: '12345678'),
                        'role' => 'reseller',
                        'status' => 'active',
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Reseller :name created successfully!', ['name' => $reseller->name]),
                    'reseller' => $reseller,
                ]);
            }

            return redirect()->route('tenant.resellers.index')->with('success', __('Reseller :name created successfully!', ['name' => $reseller->name]));
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to create reseller: :error', ['error' => $e->getMessage()]),
                ], 500);
            }
            return back()->with('error', __('Failed to create reseller: :error', ['error' => $e->getMessage()]))->withInput();
        }
    }

    /**
     * Display the dedicated details page of a single Reseller.
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)
            ->with(['bandwidthAllocation'])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'reseller' => $reseller,
            ]);
        }

        // 1. Subscriber Counts and Stats
        $customersQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id);

        $totalCustomers = (clone $customersQuery)->count();
        $activeCustomers = (clone $customersQuery)->where('status', 'active')->count();
        $onlineCustomers = (clone $customersQuery)->where('online_status', 'online')->count();
        $dueCustomers = (clone $customersQuery)->where('due_amount', '>', 0)->count();
        $totalMonthlyRevenue = (float) (clone $customersQuery)->sum('monthly_bill');

        // 2. Staff and Operators
        $staffCount = User::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->count();
        $staffList = User::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->latest('id')
            ->take(6)
            ->get();

        // 3. Recent Wholesale Invoices
        $recentInvoices = $reseller->invoices()
            ->latest('id')
            ->take(5)
            ->get();

        // 4. Recent Subscribers Preview
        $recentCustomers = (clone $customersQuery)
            ->with('package')
            ->latest('id')
            ->take(8)
            ->get();

        // 5. Assigned Coverage Zones
        $coverageZones = TenantCoverageZone::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->get();

        // 6. Bandwidth Allocation Profile
        $bandwidth = $reseller->bandwidthAllocation;
        $totalBandwidthMbps = $bandwidth ? (float) ($bandwidth->total_bandwidth_mbps ?? 0) : 0;

        return view('tenant.resellers.show', compact(
            'tenant',
            'reseller',
            'totalCustomers',
            'activeCustomers',
            'onlineCustomers',
            'dueCustomers',
            'totalMonthlyRevenue',
            'staffCount',
            'staffList',
            'recentInvoices',
            'recentCustomers',
            'coverageZones',
            'bandwidth',
            'totalBandwidthMbps'
        ));
    }

    /**
     * Update the specified Reseller in storage.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'prefix' => 'nullable|string|max:20',
            'contact_person' => 'required|string|max:255',
            'mobile' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'billing_type' => 'required|in:PREPAID_WALLET,POSTPAID_MONTHLY,BANDWIDTH_WHOLESALE',
            'credit_limit' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'monthly_panel_charge' => 'nullable|numeric|min:0',
            'panel_expiry_date' => 'nullable|date',
            'panel_billing_status' => 'nullable|in:ACTIVE,EXPIRED,GRACE_PERIOD',
            'status' => 'required|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        $prefix = !empty($validated['prefix']) ? Str::slug($validated['prefix'], '') : null;

        // Check code uniqueness excluding this record
        $codeExists = TenantReseller::where('tenant_id', $tenant->id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $id)
            ->exists();
        if ($codeExists) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Code :code is already in use.', ['code' => $validated['code']])], 422);
            }
            return back()->withErrors(['code' => __('Code already in use.')])->withInput();
        }

        // Check prefix uniqueness excluding this record
        if ($prefix) {
            $prefixExists = TenantReseller::where('tenant_id', $tenant->id)
                ->where('prefix', $prefix)
                ->where('id', '!=', $id)
                ->exists();
            if ($prefixExists) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('Prefix :prefix is already in use.', ['prefix' => $prefix])], 422);
                }
                return back()->withErrors(['prefix' => __('Prefix already in use.')])->withInput();
            }
        }

        $reseller->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'prefix' => $prefix,
            'contact_person' => $validated['contact_person'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'billing_type' => $validated['billing_type'],
            'credit_limit' => $validated['credit_limit'] ?? 0.00,
            'commission_rate' => $validated['commission_rate'] ?? 0.00,
            'monthly_panel_charge' => $validated['monthly_panel_charge'] ?? 0.00,
            'panel_expiry_date' => $validated['panel_expiry_date'] ?? null,
            'panel_billing_status' => $validated['panel_billing_status'] ?? 'ACTIVE',
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Reseller :name updated successfully!', ['name' => $reseller->name]),
                'reseller' => $reseller,
            ]);
        }

        return redirect()->route('tenant.resellers.index')->with('success', __('Reseller :name updated successfully!', ['name' => $reseller->name]));
    }

    /**
     * Toggle status (active/suspended/inactive).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
            $newStatus = ($reseller->status === 'active') ? 'suspended' : 'active';
        }

        $reseller->update(['status' => $newStatus]);

        $statusMessage = match ($newStatus) {
            'active' => __('Reseller :name has been activated.', ['name' => $reseller->name]),
            'suspended' => __('Reseller :name has been suspended.', ['name' => $reseller->name]),
            'inactive' => __('Reseller :name has been deactivated.', ['name' => $reseller->name]),
            default => __('Reseller :name status updated.', ['name' => $reseller->name]),
        };

        return response()->json([
            'success' => true,
            'message' => $statusMessage,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Quick Wallet Recharge / Balance Adjustment.
     */
    public function adjustWallet(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $oldBalance = (float) $reseller->wallet_balance;

        if ($validated['type'] === 'credit') {
            $newBalance = $oldBalance + $amount;
        } else {
            $newBalance = $oldBalance - $amount;
        }

        $reseller->update(['wallet_balance' => $newBalance]);

        return response()->json([
            'success' => true,
            'message' => __('Wallet balance updated! New Balance: :balance', ['balance' => number_format($newBalance, 2)]),
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * Delete reseller if allowed.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, __('Unauthorized.'));
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);
        $name = $reseller->name;
        $reseller->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Reseller :name deleted successfully.', ['name' => $name]),
            ]);
        }

        return redirect()->route('tenant.resellers.index')->with('success', __('Reseller :name deleted successfully.', ['name' => $name]));
    }

    /**
     * Show Excel / CSV Import Page for Resellers.
     */
    public function importView(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, __('Tenant record not found.'));
        }

        $totalResellers = TenantReseller::where('tenant_id', $tenant->id)->count();

        return view('tenant.resellers.import', compact('tenant', 'totalResellers'));
    }

    /**
     * Download Standard CSV / Excel Template for Reseller Import.
     */
    public function downloadSampleTemplate(Request $request)
    {
        $fileName = "reseller_import_template_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'Reseller Name', 'Code', 'Prefix', 'Contact Person', 'Mobile Phone', 
            'Email', 'Address', 'Billing Type (prepaid/postpaid)', 'Commission Rate (%)', 
            'Credit Limit', 'Monthly Panel Charge', 'Initial Wallet Balance', 'Status (active/inactive)', 'Notes'
        ];

        $sampleRows = [
            [
                'SpeedNet Online Ltd', 'RES-001', 'SN', 'Md. Rahim Ahmed', '01711223344',
                'rahim@speednet.com', 'House 12, Road 4, Sector 3, Uttara, Dhaka', 'prepaid', '25.00',
                '5000.00', '500.00', '0.00', 'active', 'Primary Sub-ISP Partner'
            ],
            [
                'FastLink Fiber Network', 'RES-002', 'FL', 'Karim Chowdhury', '01811334455',
                'karim@fastlink.net', 'Agrabad Commercial Area, Chattogram', 'postpaid', '30.00',
                '10000.00', '1000.00', '0.00', 'active', 'Enterprise Franchise Partner'
            ],
            [
                'Surma Broadband', 'RES-003', 'SB', 'Tanjil Hasan', '01911445566',
                'tanjil@surmabb.com', 'Zindabazar, Sylhet', 'prepaid', '20.00',
                '3000.00', '400.00', '0.00', 'active', 'Zonal Partner'
            ]
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
     * Process Bulk Reseller Import from CSV / Excel file.
     */
    public function importProcess(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, __('Tenant record not found.'));
        }

        $request->validate([
            'file' => 'required|file|max:10240',
            'duplicate_action' => 'nullable|string|in:skip,update',
            'default_billing_type' => 'nullable|string|in:prepaid,postpaid',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'default_status' => 'nullable|string|in:active,inactive',
        ]);

        $file = $request->file('file');
        $normalizeBillingType = function($val, $fallback = 'PREPAID_WALLET') {
            $v = strtolower(trim((string)$val));
            if (str_contains($v, 'postpaid') || str_contains($v, 'monthly')) {
                return 'POSTPAID_MONTHLY';
            }
            if (str_contains($v, 'bandwidth') || str_contains($v, 'wholesale')) {
                return 'BANDWIDTH_WHOLESALE';
            }
            if (str_contains($v, 'prepaid') || str_contains($v, 'wallet')) {
                return 'PREPAID_WALLET';
            }
            return $fallback;
        };

        $defaultBillingType = $normalizeBillingType($request->input('default_billing_type', 'PREPAID_WALLET'));
        $defaultCommission = (float) $request->input('default_commission_rate', 0);
        $defaultStatus = in_array(strtolower((string)$request->input('default_status', 'active')), ['active', 'inactive', 'suspended'])
            ? strtolower((string)$request->input('default_status', 'active'))
            : 'active';

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Unable to read uploaded file.')], 422);
            }
            return back()->with('error', __('Unable to read uploaded file.'));
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('The uploaded file is empty.')], 422);
            }
            return back()->with('error', __('The uploaded file is empty.'));
        }

        // Normalize headers
        $cleanHeaders = array_map(function($h) {
            $h = strtolower(trim((string)$h));
            return preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '/', '-', '(', ')', '%'], '_', $h));
        }, $headerRow);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Skip empty row
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($cleanHeaders as $idx => $key) {
                    $data[$key] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }

                // Reseller Name
                $name = '';
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'name') && !str_contains($k, 'contact') && !empty($v)) {
                        $name = $v;
                        break;
                    }
                }
                if (empty($name)) {
                    $skipped++;
                    $errors[] = __('Row :row: Reseller Name is missing.', ['row' => $rowNum]);
                    continue;
                }

                $code = $data['code'] ?? ($data['reseller_code'] ?? '');
                $prefix = $data['prefix'] ?? '';
                $contactPerson = $data['contact_person'] ?? ($data['contact'] ?? '');
                $mobile = $data['mobile_phone'] ?? ($data['mobile'] ?? ($data['phone'] ?? ($data['phone_number'] ?? '')));
                $email = $data['email'] ?? '';
                $address = $data['address'] ?? '';

                // Billing Type
                $billingType = $defaultBillingType;
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'billing') && !empty($v)) {
                        $billingType = $normalizeBillingType($v, $defaultBillingType);
                        break;
                    }
                }

                // Commission Rate
                $commRate = $defaultCommission;
                foreach ($data as $k => $v) {
                    if (str_starts_with($k, 'commission') && is_numeric($v)) {
                        $commRate = (float) $v;
                        break;
                    }
                }

                // Credit Limit
                $creditLimit = 0.00;
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'credit') && is_numeric($v)) {
                        $creditLimit = (float) $v;
                        break;
                    }
                }

                // Monthly Panel Charge
                $panelCharge = 0.00;
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'panel') && is_numeric($v)) {
                        $panelCharge = (float) $v;
                        break;
                    }
                }

                // Initial Wallet Balance
                $initialWallet = 0.00;
                foreach ($data as $k => $v) {
                    if (str_contains($k, 'wallet') && is_numeric($v)) {
                        $initialWallet = (float) $v;
                        break;
                    }
                }

                // Status
                $status = $defaultStatus;
                foreach ($data as $k => $v) {
                    if (str_starts_with($k, 'status') && !empty($v)) {
                        $st = strtolower(trim($v));
                        if (in_array($st, ['active', 'inactive', 'suspended'])) {
                            $status = $st;
                        }
                        break;
                    }
                }

                $notes = $data['notes'] ?? ($data['note'] ?? ($data['remarks'] ?? ''));

                // Generate code if missing
                if (empty($code)) {
                    $nextNum = TenantReseller::where('tenant_id', $tenant->id)->count() + $imported + 1;
                    $code = 'RES-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                }

                if (empty($prefix)) {
                    $cleanWords = preg_replace('/[^A-Za-z0-9]/', '', $name);
                    $prefix = strtoupper(substr($cleanWords, 0, 3));
                }

                // Check duplicate by code or (name + mobile)
                $existing = TenantReseller::where('tenant_id', $tenant->id)
                    ->where(function($q) use ($code, $name, $mobile) {
                        $q->where('code', $code);
                        if (!empty($mobile)) {
                            $q->orWhere('mobile', $mobile);
                        }
                    })
                    ->first();

                if ($existing) {
                    if ($duplicateAction === 'update') {
                        $existing->update([
                            'name' => $name,
                            'prefix' => $prefix ?: $existing->prefix,
                            'contact_person' => $contactPerson ?: $existing->contact_person,
                            'mobile' => $mobile ?: $existing->mobile,
                            'email' => $email ?: $existing->email,
                            'address' => $address ?: $existing->address,
                            'billing_type' => $billingType,
                            'commission_rate' => $commRate,
                            'credit_limit' => $creditLimit ?: $existing->credit_limit,
                            'monthly_panel_charge' => $panelCharge ?: $existing->monthly_panel_charge,
                            'status' => $status,
                            'notes' => $notes ?: $existing->notes,
                        ]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    continue;
                }

                TenantReseller::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'code' => $code,
                    'prefix' => $prefix,
                    'contact_person' => $contactPerson,
                    'mobile' => $mobile,
                    'email' => $email,
                    'address' => $address,
                    'billing_type' => $billingType,
                    'wallet_balance' => $initialWallet,
                    'credit_limit' => $creditLimit,
                    'commission_rate' => $commRate,
                    'monthly_panel_charge' => $panelCharge,
                    'status' => $status,
                    'notes' => $notes,
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            $parts = [
                __('Import completed! Successfully imported :count resellers.', ['count' => $imported]),
            ];
            if ($updated > 0) {
                $parts[] = __('Updated :count existing.', ['count' => $updated]);
            }
            if ($skipped > 0) {
                $parts[] = __('Skipped :count duplicates/empty.', ['count' => $skipped]);
            }
            $msg = implode(' ', $parts);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => array_slice($errors, 0, 10),
                ]);
            }

            return redirect()->route('tenant.resellers.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            \Illuminate\Support\Facades\Log::error("Reseller Import Error: " . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Import failed: :error', ['error' => $e->getMessage()])
                ], 500);
            }

            return back()->with('error', __('Import failed: :error', ['error' => $e->getMessage()]));
        }
    }
}
