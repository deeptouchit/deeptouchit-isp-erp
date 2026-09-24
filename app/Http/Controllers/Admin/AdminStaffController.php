<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminStaffController extends Controller
{
    /**
     * Display operational staff personnel directory.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::where('is_staff', true)->with('department:id,name,slug');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('staff_id', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Department Filter
        if ($deptId = $request->input('department_id')) {
            if ($deptId !== 'all') {
                $query->where('department_id', $deptId);
            }
        }

        // Shift Status Filter
        if ($shiftStatus = $request->input('shift_status')) {
            if ($shiftStatus !== 'all') {
                $query->where('shift_status', $shiftStatus);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $staff = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allStaff = User::where('is_staff', true)->get();
        $totalStaff = $allStaff->count();
        $onDutyCount = $allStaff->where('shift_status', 'on_duty')->where('status', 'active')->count();
        $departmentsCovered = $allStaff->whereNotNull('department_id')->pluck('department_id')->unique()->count();
        $twoFaProtected = $allStaff->filter(fn($u) => !empty($u->two_factor_confirmed_at) || $u->two_factor_enforced)->count();

        $stats = [
            'total_staff' => $totalStaff,
            'on_duty_count' => $onDutyCount,
            'departments_covered' => $departmentsCovered,
            'two_factor_protected' => $twoFaProtected,
        ];

        $departments = SupportDepartment::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Administration/Staff/Index', [
            'staff' => $staff,
            'stats' => $stats,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department_id', 'shift_status', 'status']),
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'staff_id' => ['nullable', 'string', 'max:30', 'unique:users,staff_id'],
            'department_id' => ['nullable', 'exists:support_departments,id'],
            'designation' => ['required', 'string', 'max:100'],
            'shift' => ['required', 'string', 'max:60'],
            'shift_status' => ['required', 'in:on_duty,off_duty,on_leave'],
            'two_factor_enforced' => ['boolean'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $staffId = $validated['staff_id'] ?: 'SH-STF-' . (User::where('is_staff', true)->count() + 101);

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'role' => 'admin',
            'is_staff' => true,
            'admin_role' => $validated['designation'],
            'staff_id' => $staffId,
            'department_id' => $validated['department_id'] ?? null,
            'designation' => $validated['designation'],
            'shift' => $validated['shift'],
            'shift_status' => $validated['shift_status'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'two_factor_enforced' => $validated['two_factor_enforced'] ?? false,
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'staff_created',
            'description' => "Added Staff Member '{$user->first_name} {$user->last_name}' ({$user->designation}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'staff_id' => $user->staff_id,
                'email' => $user->email,
                'designation' => $user->designation,
            ],
        ]);

        return redirect()->route('admin.administration.staff')
            ->with('success', "Staff member '{$user->first_name} {$user->last_name}' added successfully.");
    }

    /**
     * Update existing staff member.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'username' => ['required', 'string', 'max:60', Rule::unique('users')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'staff_id' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($staff->id)],
            'department_id' => ['nullable', 'exists:support_departments,id'],
            'designation' => ['required', 'string', 'max:100'],
            'shift' => ['required', 'string', 'max:60'],
            'shift_status' => ['required', 'in:on_duty,off_duty,on_leave'],
            'two_factor_enforced' => ['boolean'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'phone' => $validated['phone'] ?? null,
            'staff_id' => $validated['staff_id'] ?? $staff->staff_id,
            'department_id' => $validated['department_id'] ?? null,
            'designation' => $validated['designation'],
            'admin_role' => $validated['designation'],
            'shift' => $validated['shift'],
            'shift_status' => $validated['shift_status'],
            'two_factor_enforced' => $validated['two_factor_enforced'] ?? false,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $oldValues = $staff->only(['first_name', 'last_name', 'email', 'username', 'designation', 'shift', 'shift_status', 'status']);
        $staff->update($updateData);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'staff_updated',
            'description' => "Updated Staff Member profile for '{$staff->first_name} {$staff->last_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $staff->only(['first_name', 'last_name', 'email', 'username', 'designation', 'shift', 'shift_status', 'status']),
        ]);

        return redirect()->route('admin.administration.staff')
            ->with('success', "Staff member '{$staff->first_name} {$staff->last_name}' updated.");
    }

    /**
     * Toggle shift duty status.
     */
    public function toggleDuty(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newDuty = $staff->shift_status === 'on_duty' ? 'off_duty' : 'on_duty';
        $staff->update(['shift_status' => $newDuty]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'staff_duty_toggled',
            'description' => "Changed shift duty status of '{$staff->first_name} {$staff->last_name}' to {$newDuty}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['shift_status' => $staff->getOriginal('shift_status')],
            'new_values' => ['shift_status' => $newDuty],
        ]);

        return redirect()->route('admin.administration.staff')
            ->with('success', "Shift duty status updated to {$newDuty}.");
    }

    /**
     * Toggle status between active and suspended.
     */
    public function toggleStatus(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newStatus = $staff->status === 'active' ? 'suspended' : 'active';
        $staff->update(['status' => $newStatus]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'staff_status_toggled',
            'description' => "Changed status of Staff Member '{$staff->first_name} {$staff->last_name}' to {$newStatus}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $staff->getOriginal('status')],
            'new_values' => ['status' => $newStatus],
        ]);

        return redirect()->route('admin.administration.staff')
            ->with('success', "Staff member status changed to {$newStatus}.");
    }

    /**
     * Delete staff member.
     */
    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = "{$staff->first_name} {$staff->last_name}";
        $staff->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'staff_deleted',
            'description' => "Removed Staff Member '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.administration.staff')
            ->with('success', "Staff member '{$name}' removed.");
    }
}
