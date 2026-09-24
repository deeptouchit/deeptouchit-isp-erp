<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\TenantInternetPackage;
use App\Models\User;
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
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $billingType = $request->input('billing_type');
        $status = $request->input('status');
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
            abort(403, 'Unauthorized.');
        }

        // 0. Enforce ISP Tenant Plan Reseller Quota
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $resellerQuota = $plan ? (int) $plan->reseller_limit : 5;
        $currentCount = TenantReseller::where('tenant_id', $tenant->id)->count();

        if ($resellerQuota > 0 && $currentCount >= $resellerQuota) {
            $planName = $plan ? $plan->name : 'Current Plan';
            $msg = "You have reached your Plan ({$planName}) quota limit of {$resellerQuota} resellers. Please upgrade your subscription plan to onboard more resellers.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 403);
            }
            return back()->withErrors(['plan' => $msg])->withInput();
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
                    'message' => "Reseller code '{$validated['code']}' already exists.",
                ], 422);
            }
            return back()->withErrors(['code' => 'Reseller code already exists.'])->withInput();
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
                        'message' => "Customer Username prefix '{$prefix}' is already used by another reseller.",
                    ], 422);
                }
                return back()->withErrors(['prefix' => 'Prefix already used by another reseller.'])->withInput();
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
                    'message' => "Reseller '{$reseller->name}' created successfully!",
                    'reseller' => $reseller,
                ]);
            }

            return redirect()->route('tenant.resellers.index')->with('success', "Reseller '{$reseller->name}' created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create reseller: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Failed to create reseller: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Return JSON details of a single Reseller for modals and details drawer.
     */
    public function show(int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'reseller' => $reseller,
        ]);
    }

    /**
     * Update the specified Reseller in storage.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
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
                return response()->json(['success' => false, 'message' => "Code '{$validated['code']}' is already in use."], 422);
            }
            return back()->withErrors(['code' => 'Code already in use.'])->withInput();
        }

        // Check prefix uniqueness excluding this record
        if ($prefix) {
            $prefixExists = TenantReseller::where('tenant_id', $tenant->id)
                ->where('prefix', $prefix)
                ->where('id', '!=', $id)
                ->exists();
            if ($prefixExists) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => "Prefix '{$prefix}' is already in use."], 422);
                }
                return back()->withErrors(['prefix' => 'Prefix already in use.'])->withInput();
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
                'message' => "Reseller '{$reseller->name}' updated successfully!",
                'reseller' => $reseller,
            ]);
        }

        return redirect()->route('tenant.resellers.index')->with('success', "Reseller '{$reseller->name}' updated successfully!");
    }

    /**
     * Toggle status (active/suspended/inactive).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
            $newStatus = ($reseller->status === 'active') ? 'suspended' : 'active';
        }

        $reseller->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Reseller '{$reseller->name}' status changed to " . ucfirst($newStatus) . ".",
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
            abort(403, 'Unauthorized.');
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
            'message' => "Wallet balance updated! New Balance: {$newBalance}",
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
            abort(403, 'Unauthorized.');
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);
        $name = $reseller->name;
        $reseller->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Reseller '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('tenant.resellers.index')->with('success', "Reseller '{$name}' deleted successfully.");
    }
}
