<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDepartmentController extends Controller
{
    /**
     * Display directory of support departments, SLA response policies, and staff routing.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $departments = SupportDepartment::orderBy('sort_order', 'asc')->get();

        $allTickets = SupportTicket::all();
        $openTickets = $allTickets->where('status', 'open');

        // All Staff Agents for assignment multi-select
        $allStaff = User::whereIn('role', ['admin', 'reseller'])
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        $departmentsData = $departments->map(function ($dept) use ($allTickets, $openTickets, $allStaff) {
            $slug = strtolower($dept->slug);
            $deptTickets = $allTickets->filter(fn($t) => strtolower($t->department) === $slug);
            $deptOpen = $openTickets->filter(fn($t) => strtolower($t->department) === $slug);

            $staffIds = $dept->assigned_staff_ids ?? [];
            $assignedStaff = $allStaff->filter(fn($s) => in_array($s->id, $staffIds))->values();

            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'slug' => $dept->slug,
                'email' => $dept->email,
                'description' => $dept->description,
                'assigned_staff_ids' => $staffIds,
                'assigned_staff' => $assignedStaff,
                'is_client_selectable' => (bool) $dept->is_client_selectable,
                'is_active' => (bool) $dept->is_active,
                'sla_response_hours' => $dept->sla_response_hours,
                'sort_order' => $dept->sort_order,
                'total_tickets' => $deptTickets->count(),
                'open_tickets' => $deptOpen->count(),
            ];
        });

        // 4 Clean 3-Tier Metric Stats
        $stats = [
            'total_departments' => $departments->count(),
            'active_departments' => $departments->where('is_active', true)->count(),
            'open_tickets_count' => $openTickets->count(),
            'total_staff_count' => $allStaff->count(),
        ];

        return Inertia::render('Admin/Support/Departments/Index', [
            'departments' => $departmentsData,
            'stats' => $stats,
            'staff' => $allStaff,
        ]);
    }

    /**
     * Store new support department.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:support_departments,slug'],
            'email' => ['nullable', 'email', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'assigned_staff_ids' => ['nullable', 'array'],
            'assigned_staff_ids.*' => ['exists:users,id'],
            'is_client_selectable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sla_response_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['slug']);
        $validated['is_client_selectable'] = $validated['is_client_selectable'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $department = SupportDepartment::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'department_created',
            'description' => "Created support department '{$department->name}' ({$department->slug}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['name' => $department->name, 'slug' => $department->slug],
        ]);

        return redirect()->route('admin.support.departments')
            ->with('success', "Department '{$department->name}' created successfully.");
    }

    /**
     * Update support department.
     */
    public function update(Request $request, SupportDepartment $department): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:support_departments,slug,' . $department->id],
            'email' => ['nullable', 'email', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'assigned_staff_ids' => ['nullable', 'array'],
            'assigned_staff_ids.*' => ['exists:users,id'],
            'is_client_selectable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sla_response_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['slug']);
        $department->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'department_updated',
            'description' => "Updated support department '{$department->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.support.departments')
            ->with('success', "Department '{$department->name}' updated successfully.");
    }

    /**
     * 1-Click Toggle active status.
     */
    public function toggleStatus(SupportDepartment $department): RedirectResponse
    {
        $this->authorize('create', User::class);

        $department->update(['is_active' => !$department->is_active]);
        $statusStr = $department->is_active ? 'activated' : 'deactivated';

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'department_status_toggled',
            'description' => "Department '{$department->name}' {$statusStr}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['is_active' => !$department->is_active],
            'new_values' => ['is_active' => $department->is_active],
        ]);

        return redirect()->route('admin.support.departments')
            ->with('success', "Department '{$department->name}' {$statusStr}.");
    }

    /**
     * Delete support department.
     */
    public function destroy(SupportDepartment $department): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = $department->name;
        $department->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'department_deleted',
            'description' => "Deleted support department '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['name' => $name],
        ]);

        return redirect()->route('admin.support.departments')
            ->with('success', "Department '{$name}' deleted.");
    }
}
