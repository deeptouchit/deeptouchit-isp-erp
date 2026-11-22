<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
        abort_unless($reseller && $user->tenant_id && (int) $reseller->tenant_id === (int) $user->tenant_id, 403);

        return [$user, $reseller, $reseller->tenant];
    }

    private const ROLE_LABELS = [
        'reseller_technician' => 'Field Technician',
        'reseller_collector' => 'Bill Collector',
        'reseller_operator' => 'Desk Operator',
        'reseller_manager' => 'Branch Manager',
        'reseller_admin' => 'Partner Admin',
        'reseller_owner' => 'Reseller Owner',
        'technician' => 'Field Technician',
        'collector' => 'Bill Collector',
        'operator' => 'Desk Operator',
        'manager' => 'Branch Manager',
        'admin' => 'Partner Admin',
    ];

    private function roleRank(string $role): int
    {
        return match ($role) {
            'reseller_technician', 'reseller_collector', 'reseller_operator', 'technician', 'collector', 'operator' => 0,
            'reseller_manager', 'manager' => 1,
            'reseller_admin', 'admin' => 2,
            'reseller_owner' => 3,
            'isp_admin', 'owner', 'super_admin' => 4,
            default => PHP_INT_MAX,
        };
    }

    private function canManage(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && $this->roleRank($actor->role) > $this->roleRank($target->role);
    }

    private function assignableRoles(User $actor): array
    {
        return array_filter(
            array_slice(self::ROLE_LABELS, 0, 5, true),
            fn (string $role) => $this->roleRank($actor->role) > $this->roleRank($role),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function filters(Request $request): array
    {
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        return [
            'search' => $request->string('search')->trim()->toString(),
            'roleFilter' => array_key_exists($role, self::ROLE_LABELS) ? $role : 'all',
            'statusFilter' => in_array($status, ['active', 'inactive'], true) ? $status : 'all',
        ];
    }

    private function staffQuery(User $actor, array $filters): Builder
    {
        ['search' => $search, 'roleFilter' => $role, 'statusFilter' => $status] = $filters;

        return User::where('tenant_id', $actor->tenant_id)
            ->where('reseller_id', $actor->reseller_id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            }))
            ->when($role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * Display Staff & Collectors Directory.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $filters = $this->filters($request);
        $perPage = $request->integer('per_page', 20);
        $pageSizes = [10, 15, 20, 30, 50, 100, 250, 500];
        if (!in_array($perPage, $pageSizes, true)) {
            $perPage = 20;
        }

        $query = $this->staffQuery($authUser, $filters);
        if (Schema::hasColumn('tenant_customers', 'assigned_collector_id')) {
            $query->withCount(['assignedCustomers as assigned_customers_count' => fn ($query) => $query
                ->where('tenant_id', $tenantId)->where('reseller_id', $resellerId)]);
        }
        $staffMembers = $query->paginate($perPage)->withQueryString();
        $roleLabels = self::ROLE_LABELS;
        $assignableRoles = $this->assignableRoles($authUser);
        $manageableIds = $staffMembers->getCollection()->filter(fn (User $staff) => $this->canManage($authUser, $staff))->modelKeys();

        // 2. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $baseStaffQuery = User::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);

        $totalStaff = (clone $baseStaffQuery)->count();
        $activeStaff = (clone $baseStaffQuery)->where('status', 'active')->count();
        $techniciansCount = (clone $baseStaffQuery)->whereIn('role', ['reseller_technician', 'technician'])->count();
        $collectorsCount = (clone $baseStaffQuery)->whereIn('role', ['reseller_collector', 'collector'])->count();
        
        // Customers managed by this reseller
        $totalCustomers = TenantCustomer::where('tenant_id', $tenantId)->where('reseller_id', $resellerId)->count();
        
        // Total monthly collections collected by this reseller's collectors this month
        $payments = TenantCustomerPayment::where('tenant_id', $tenantId);
        if (Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            $payments->where('reseller_id', $resellerId);
        } else {
            $payments->whereHas('customer', fn ($query) => $query->where('tenant_id', $tenantId)->where('reseller_id', $resellerId));
        }
        $currentMonthCollections = (float) $payments
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

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
            'perPage',
            'pageSizes',
            'roleLabels',
            'assignableRoles',
            'manageableIds'
        ) + $filters);
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
            'role' => ['required', 'string', Rule::in(array_keys($this->assignableRoles($authUser)))],
            'password' => ['required', 'string', 'min:6'],
            'designation' => ['nullable', 'string', 'max:100'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'collection_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        DB::beginTransaction();
        try {
            $prefix = substr(strtoupper($reseller->prefix ?? substr($reseller->code ?? 'P', 0, 3)), 0, 5);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'mobile' => $validated['phone'],
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
                'designation' => $validated['designation'] ?? $this->formatRoleName($validated['role']),
                'salary_amount' => $validated['salary_amount'] ?? 0,
                'collection_commission_rate' => $validated['collection_commission_rate'] ?? 0,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'email_verified_at' => now(),
            ]);

            $user->update(['staff_id' => 'STF-' . $prefix . '-' . str_pad((string) $user->id, 3, '0', STR_PAD_LEFT)]);

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
        abort_unless($this->canManage($authUser, $user), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:25'],
            'role' => ['required', 'string', Rule::in(array_merge([$user->role], array_keys($this->assignableRoles($authUser))))],
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
        abort_unless($this->canManage($authUser, $user), 403);

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
        abort_unless($this->canManage($authUser, $user), 403);

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

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        [$actor, $reseller] = $this->getResellerData();

        $target = User::where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)->findOrFail($id);
        abort_unless($this->canManage($actor, $target), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), function ($attribute, $value, $fail) {
                if (is_string($value) && strlen($value) > 72) {
                    $fail(__('The new password must be 72 bytes or fewer.'));
                }
            }],
        ]);

        DB::transaction(function () use ($actor, $reseller, $target, $validated, $request) {
            // Lock both accounts in ID order so permission and scope changes cannot race the reset.
            $accounts = User::where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)
                ->whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $currentActor = $accounts->get($actor->id);
            $currentTarget = $accounts->get($target->id);
            abort_unless($currentActor && $currentActor->status === 'active' && $currentActor->isResellerAdmin(), 403);
            abort_unless($currentTarget, 404);
            abort_unless($this->canManage($currentActor, $currentTarget), 403);

            if (!Hash::check($validated['current_password'], $currentActor->password)) {
                throw ValidationException::withMessages(['current_password' => __('The current password is incorrect.')]);
            }
            if (Hash::check($validated['password'], $currentTarget->password)) {
                throw ValidationException::withMessages(['password' => __('Choose a different password for this staff member.')]);
            }

            $currentTarget->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $currentTarget->id)->delete();
            }

            TenantActivityLog::create([
                'tenant_id' => $reseller->tenant_id,
                'actor_type' => 'reseller',
                'actor_id' => $currentActor->id,
                'actor_name' => $currentActor->name,
                'event_type' => 'staff_password_reset',
                'description' => "Password reset for staff account #{$currentTarget->id}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json(['message' => __('Staff password reset successfully.')]);
    }

    /**
     * Export Staff Roster as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $fileName = 'reseller_staff_' . date('Ymd_His') . '.csv';

        $staff = $this->staffQuery($authUser, $this->filters($request))->cursor();

        return response()->streamDownload(function () use ($staff) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Staff ID', 'Name', 'Role', 'Designation', 'Phone', 'Email', 'Salary', 'Status', 'Joined Date'], escape: '');

            foreach ($staff as $index => $s) {
                $row = [
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
                ];
                $row = array_map(fn ($value) => is_string($value) && preg_match('/^(?:\\s*[=+@-]|[\\t\\r\\n])/', $value) ? "'" . $value : $value, $row);
                fputcsv($handle, $row, escape: '');
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
        $staff = $this->staffQuery($authUser, $this->filters($request))->get();

        return view('reseller.staff.print', compact('authUser', 'reseller', 'tenant', 'staff') + ['roleLabels' => self::ROLE_LABELS]);
    }

    /**
     * Helper to format role names cleanly.
     */
    protected function formatRoleName(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? $role;
    }
}
