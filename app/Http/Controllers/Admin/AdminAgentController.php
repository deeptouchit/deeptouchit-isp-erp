<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportAgentProfile;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAgentController extends Controller
{
    /**
     * Display directory of support engineers, workload telemetry, and dispatch capacity.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $departments = SupportDepartment::orderBy('sort_order', 'asc')->get();

        // All Staff Members (Admin or with Agent Profile)
        $staffUsers = User::whereIn('role', ['admin', 'reseller'])
            ->with('agentProfile')
            ->orderBy('first_name')
            ->get();

        // Get all tickets and replies
        $allTickets = SupportTicket::all();
        $openTickets = $allTickets->where('status', '!=', 'closed');
        $allReplies = TicketReply::where('is_staff', true)->get();

        $agentsData = $staffUsers->map(function ($user) use ($allTickets, $openTickets, $allReplies, $departments) {
            $profile = $user->agentProfile;
            $assignedOpen = $openTickets->where('assigned_to', $user->id)->count();
            $assignedResolved = $allTickets->where('assigned_to', $user->id)->where('status', 'closed')->count();
            $totalReplies = $allReplies->where('user_id', $user->id)->count();

            $deptSlugs = $profile->department_slugs ?? ['technical'];
            $assignedDepts = $departments->filter(fn($d) => in_array($d->slug, $deptSlugs))->values();

            return [
                'id' => $user->id,
                'name' => $user->name ?: "{$user->first_name} {$user->last_name}",
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'job_title' => $profile->job_title ?? 'Support Engineer',
                'department_slugs' => $deptSlugs,
                'assigned_departments' => $assignedDepts,
                'signature' => $profile->signature ?? '',
                'max_active_tickets' => $profile->max_active_tickets ?? 20,
                'is_auto_assignable' => $profile ? (bool)$profile->is_auto_assignable : true,
                'is_online' => $profile ? (bool)$profile->is_online : true,
                'rating' => $profile->rating ?? '5.00',
                'assigned_open_tickets' => $assignedOpen,
                'assigned_resolved_tickets' => $assignedResolved,
                'total_replies_count' => $totalReplies,
            ];
        });

        // 4 Clean 3-Tier Metric Stats
        $stats = [
            'total_agents' => $agentsData->count(),
            'active_roster_count' => $agentsData->where('is_online', true)->count(),
            'total_assigned_open' => $agentsData->sum('assigned_open_tickets'),
            'total_staff_replies' => $allReplies->count(),
            'auto_assign_count' => $agentsData->where('is_auto_assignable', true)->count(),
        ];

        return Inertia::render('Admin/Support/Agents/Index', [
            'agents' => $agentsData,
            'stats' => $stats,
            'departments' => $departments,
            'availableUsers' => $staffUsers,
        ]);
    }

    /**
     * Store new support agent profile.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'job_title' => ['required', 'string', 'max:100'],
            'department_slugs' => ['nullable', 'array'],
            'department_slugs.*' => ['string'],
            'signature' => ['nullable', 'string'],
            'max_active_tickets' => ['required', 'integer', 'min:1', 'max:100'],
            'is_auto_assignable' => ['nullable', 'boolean'],
        ]);

        $validated['department_slugs'] = $validated['department_slugs'] ?? ['technical'];
        $validated['is_auto_assignable'] = $validated['is_auto_assignable'] ?? true;
        $validated['is_online'] = true;

        SupportAgentProfile::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        $user = User::find($validated['user_id']);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'agent_profile_saved',
            'description' => "Updated support agent profile for '{$user->username}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['user_id' => $user->id, 'job_title' => $validated['job_title']],
        ]);

        return redirect()->route('admin.support.agents')
            ->with('success', "Agent profile for '{$user->first_name}' updated successfully.");
    }

    /**
     * Update support agent profile.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'job_title' => ['required', 'string', 'max:100'],
            'department_slugs' => ['nullable', 'array'],
            'department_slugs.*' => ['string'],
            'signature' => ['nullable', 'string'],
            'max_active_tickets' => ['required', 'integer', 'min:1', 'max:100'],
            'is_auto_assignable' => ['nullable', 'boolean'],
            'is_online' => ['nullable', 'boolean'],
        ]);

        $validated['department_slugs'] = $validated['department_slugs'] ?? ['technical'];

        SupportAgentProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'agent_profile_updated',
            'description' => "Updated agent profile for '{$user->username}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.support.agents')
            ->with('success', "Agent profile for '{$user->first_name}' updated successfully.");
    }

    /**
     * 1-Click Toggle auto assignment dispatch.
     */
    public function toggleAutoAssign(User $user): RedirectResponse
    {
        $this->authorize('create', User::class);

        $profile = SupportAgentProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['job_title' => 'Support Engineer', 'department_slugs' => ['technical']]
        );

        $profile->update(['is_auto_assignable' => !$profile->is_auto_assignable]);
        $statusStr = $profile->is_auto_assignable ? 'enabled' : 'disabled';

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'agent_auto_assign_toggled',
            'description' => "Auto-assignment {$statusStr} for '{$user->username}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['is_auto_assignable' => !$profile->is_auto_assignable],
            'new_values' => ['is_auto_assignable' => $profile->is_auto_assignable],
        ]);

        return redirect()->route('admin.support.agents')
            ->with('success', "Auto-assignment {$statusStr} for '{$user->first_name}'.");
    }

    /**
     * Remove agent profile.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($user->agentProfile) {
            $user->agentProfile->delete();
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'agent_profile_deleted',
            'description' => "Removed agent profile for '{$user->username}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['user_id' => $user->id],
        ]);

        return redirect()->route('admin.support.agents')
            ->with('success', "Agent profile for '{$user->first_name}' reset.");
    }
}
