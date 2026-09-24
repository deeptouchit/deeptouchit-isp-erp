<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServerGroupController extends Controller
{
    /**
     * Display a listing of server cluster groups.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServerGroup::class);

        $query = ServerGroup::query()
            ->with(['servers' => function ($q) {
                $q->select('id', 'server_group_id', 'name', 'hostname', 'ip_address', 'status', 'health_status', 'cpu_cores', 'total_ram', 'used_ram', 'total_disk', 'used_disk');
            }])
            ->withCount('servers');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Location Filter
        if ($location = $request->input('location')) {
            $query->where('location', $location);
        }

        $groups = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        // Cluster summary stats
        $allGroups = ServerGroup::with('servers')->get();
        $totalServersCount = Server::count();
        $assignedServersCount = Server::whereNotNull('server_group_id')->count();

        $stats = [
            'total_groups' => $allGroups->count(),
            'active_groups' => $allGroups->where('status', 'active')->count(),
            'total_servers' => $totalServersCount,
            'assigned_servers' => $assignedServersCount,
            'locations' => $allGroups->pluck('location')->filter()->unique()->values()->all(),
        ];

        return Inertia::render('Admin/Infrastructure/Groups/Index', [
            'groups' => $groups,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'location']),
        ]);
    }

    /**
     * Store a newly created server cluster group.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ServerGroup::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:server_groups,name'],
            'location' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $group = ServerGroup::create($validated);

        return redirect()->back()->with('success', "Cluster Group '{$group->name}' created successfully.");
    }

    /**
     * Update the specified server cluster group.
     */
    public function update(Request $request, ServerGroup $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:server_groups,name,{$group->id}"],
            'location' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $group->update($validated);

        return redirect()->back()->with('success', "Cluster Group '{$group->name}' updated successfully.");
    }

    /**
     * Remove the specified server cluster group from storage.
     */
    public function destroy(ServerGroup $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $name = $group->name;

        // Unassign existing servers attached to this group so they don't break
        Server::where('server_group_id', $group->id)->update(['server_group_id' => null]);

        $group->delete();

        return redirect()->back()->with('success', "Cluster Group '{$name}' deleted successfully. Associated nodes unassigned.");
    }
}
