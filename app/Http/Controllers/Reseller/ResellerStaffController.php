<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerStaffController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
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
     * Display Staff & Collectors Directory.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $roleFilter = $request->input('role', 'all');
        $statusFilter = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // 1. Query staff members belonging to this reseller
        $query = User::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->withCount(['assignedCustomers as assigned_customers_count']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'all' && !empty($roleFilter)) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter !== 'all' && !empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $staffMembers = $query->orderBy('name')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $baseStaffQuery = User::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);

        $totalStaff = (clone $baseStaffQuery)->count();
        $activeStaff = (clone $baseStaffQuery)->where('status', 'active')->count();
        $techniciansCount = (clone $baseStaffQuery)->whereIn('role', ['reseller_technician', 'technician'])->count();
        $collectorsCount = (clone $baseStaffQuery)->whereIn('role', ['reseller_collector', 'collector'])->count();
        
        // Customers managed by this reseller
        $totalCustomers = TenantCustomer::where('tenant_id', $tenantId)->where('reseller_id', $resellerId)->count();
        
        // Total monthly collections collected by this reseller's collectors this month
        $currentMonthCollections = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $currencySymbol = '৳';

        $stats = [
            'total_staff' => $totalStaff,
            'active_staff' => $activeStaff,
            'technicians_count' => $techniciansCount,
            'collectors_count' => $collectorsCount,
            'total_customers' => $totalCustomers,
            'monthly_collections' => $currentMonthCollections,
            'total_collected_amount' => $currentMonthCollections,
        ];

        return view('reseller.staff.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'staffMembers',
            'stats',
            'search',
            'roleFilter',
            'statusFilter',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Store a newly created Staff Member / Collector.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:25'],
            'role' => ['required', 'string', 'in:reseller_technician,reseller_collector,reseller_operator,reseller_manager,reseller_admin'],
            'password' => ['required', 'string', 'min:6'],
            'designation' => ['nullable', 'string', 'max:100'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'collection_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        DB::beginTransaction();
        try {
            // Auto generate Staff ID (e.g. STF-CCN-101)
            $prefix = strtoupper($reseller->prefix ?? substr($reseller->code ?? 'P', 0, 3));
            $lastCount = User::where('tenant_id', $tenant->id)->where('reseller_id', $reseller->id)->count();
            $staffId = 'STF-' . $prefix . '-' . str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'mobile' => $validated['phone'],
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
                'staff_id' => $staffId,
                'designation' => $validated['designation'] ?? $this->formatRoleName($validated['role']),
                'salary_amount' => $validated['salary_amount'] ?? 0,
                'collection_commission_rate' => $validated['collection_commission_rate'] ?? 0,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'email_verified_at' => now(),
            ]);

            // Log activity
            try {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_type' => 'reseller',
                    'actor_id' => $authUser->id,
                    'actor_name' => $authUser->name ?? ($reseller?->name ?? 'Reseller Partner'),
                    'event_type' => 'staff_created',
                    'description' => "Partner '{$reseller->name}' onboarded employee '{$user->name}' ({$user->staff_id}) as {$user->designation}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }

            DB::commit();

            $msg = "স্টাফ সদস্য '{$user->name}' ({$user->staff_id}) সফলভাবে তৈরি করা হয়েছে।";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'user' => $user,
                ]);
            }

            return redirect()->route('reseller.staff.index')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'স্টাফ তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'স্টাফ তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Staff Member.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $user = User::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:25'],
            'role' => ['required', 'string', 'in:reseller_technician,reseller_collector,reseller_operator,reseller_manager,reseller_admin'],
            'password' => ['nullable', 'string', 'min:6'],
            'designation' => ['nullable', 'string', 'max:100'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'collection_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'mobile' => $validated['phone'],
                'role' => $validated['role'],
                'designation' => $validated['designation'] ?? $this->formatRoleName($validated['role']),
                'salary_amount' => $validated['salary_amount'] ?? $user->salary_amount,
                'collection_commission_rate' => $validated['collection_commission_rate'] ?? $user->collection_commission_rate,
                'address' => $validated['address'] ?? $user->address,
                'status' => $validated['status'] ?? $user->status,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Log activity
            try {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_type' => 'reseller',
                    'actor_id' => $authUser->id,
                    'actor_name' => $authUser->name ?? ($reseller?->name ?? 'Reseller Partner'),
                    'event_type' => 'staff_updated',
                    'description' => "Partner '{$reseller->name}' updated employee profile '{$user->name}'",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }

            DB::commit();

            $msg = "স্টাফ সদস্য '{$user->name}' সফলভাবে আপডেট করা হয়েছে।";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'user' => $user,
                ]);
            }

            return redirect()->route('reseller.staff.index')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Delete / Remove a Staff Member.
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $user = User::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        if ($user->id === $authUser->id) {
            $err = 'আপনি নিজের অ্যাকাউন্ট মুছতে পারবেন না।';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            return back()->with('error', $err);
        }

        $userName = $user->name;
        $user->delete();

        $msg = "স্টাফ সদস্য '{$userName}' মুছে ফেলা হয়েছে।";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('reseller.staff.index')->with('success', $msg);
    }

    /**
     * Toggle Staff Status (Active / Inactive) - AGENTS.md Rule 5.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $user = User::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        if ($user->id === $authUser->id) {
            return response()->json(['success' => false, 'message' => 'You cannot disable your own active account.'], 422);
        }

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        $stateText = ($user->status === 'active') ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'is_active' => ($user->status === 'active'),
            'status' => $user->status,
            'message' => "Staff member '{$user->name}' is now {$stateText}.",
        ]);
    }

    /**
     * Export Staff Roster as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $fileName = 'reseller_staff_' . date('Ymd_His') . '.csv';

        $staff = User::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($staff) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Staff ID', 'Name', 'Role', 'Designation', 'Phone', 'Email', 'Salary', 'Status', 'Joined Date']);

            foreach ($staff as $index => $s) {
                fputcsv($handle, [
                    $index + 1,
                    $s->staff_id ?: ('ID-' . $s->id),
                    $s->name,
                    ucwords(str_replace(['reseller_', '_'], ['', ' '], $s->role)),
                    $s->designation ?: 'Staff',
                    $s->phone ?: $s->mobile,
                    $s->email,
                    $s->salary_amount,
                    strtoupper($s->status),
                    $s->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Print Staff Directory Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->orderBy('name')
            ->get();

        return view('reseller.staff.print', compact('authUser', 'reseller', 'tenant', 'staff'));
    }

    /**
     * Helper to format role names cleanly.
     */
    protected function formatRoleName(string $role): string
    {
        return match ($role) {
            'reseller_technician' => 'Field Technician',
            'reseller_collector' => 'Bill Collector',
            'reseller_operator' => 'Desk Operator',
            'reseller_manager' => 'Branch Manager',
            'reseller_admin' => 'Partner Admin',
            default => ucwords(str_replace(['reseller_', '_'], ['', ' '], $role)),
        };
    }
}
