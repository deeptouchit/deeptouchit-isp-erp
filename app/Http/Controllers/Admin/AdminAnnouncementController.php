<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnnouncementController extends Controller
{
    /**
     * Display announcements, maintenance broadcasts, and dashboard banner alerts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Announcement::with('creator:id,first_name,last_name,username,email');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Type Filter
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // State / Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            } elseif ($status === 'pinned') {
                $query->where('is_pinned', true);
            } elseif ($status === 'banner') {
                $query->where('show_banner', true);
            }
        }

        $announcements = $query->orderBy('is_pinned', 'desc')
            ->latest('published_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $all = Announcement::all();
        $publishedCount = $all->where('is_published', true)->count();
        $bannerCount = $all->where('show_banner', true)->where('is_published', true)->count();
        $maintenanceCount = $all->where('type', 'maintenance')->count();
        $totalViews = $all->sum('views_count');

        $stats = [
            'total_announcements' => $all->count(),
            'published_count' => $publishedCount,
            'banner_count' => $bannerCount,
            'maintenance_count' => $maintenanceCount,
            'total_views' => $totalViews,
        ];

        return Inertia::render('Admin/Support/Announcements/Index', [
            'announcements' => $announcements,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Store new announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:announcements,slug'],
            'type' => ['required', 'in:maintenance,service_outage,promotional,security,general'],
            'severity' => ['required', 'in:info,warning,critical'],
            'target_audience' => ['required', 'in:all,clients_only,guests_only'],
            'summary' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'show_banner' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['is_published'] = $validated['is_published'] ?? true;
        $validated['is_pinned'] = $validated['is_pinned'] ?? false;
        $validated['show_banner'] = $validated['show_banner'] ?? false;
        $validated['published_at'] = $validated['is_published'] ? now() : null;
        $validated['created_by'] = auth()->id() ?: 1;

        $announcement = Announcement::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'announcement_created',
            'description' => "Published announcement '{$announcement->title}' ({$announcement->type}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['title' => $announcement->title, 'type' => $announcement->type],
        ]);

        return redirect()->route('admin.support.announcements')
            ->with('success', "Announcement '{$announcement->title}' created successfully.");
    }

    /**
     * Update announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:announcements,slug,' . $announcement->id],
            'type' => ['required', 'in:maintenance,service_outage,promotional,security,general'],
            'severity' => ['required', 'in:info,warning,critical'],
            'target_audience' => ['required', 'in:all,clients_only,guests_only'],
            'summary' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'show_banner' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        if (($validated['is_published'] ?? false) && !$announcement->is_published) {
            $validated['published_at'] = now();
        }

        $announcement->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'announcement_updated',
            'description' => "Updated announcement '{$announcement->title}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.support.announcements')
            ->with('success', "Announcement '{$announcement->title}' updated successfully.");
    }

    /**
     * 1-Click Toggle publication state.
     */
    public function togglePublish(Announcement $announcement): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newPublished = !$announcement->is_published;
        $announcement->update([
            'is_published' => $newPublished,
            'published_at' => $newPublished ? ($announcement->published_at ?: now()) : null,
        ]);

        $statusStr = $newPublished ? 'published' : 'moved to drafts';

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'announcement_publish_toggled',
            'description' => "Announcement '{$announcement->title}' {$statusStr}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['is_published' => !$newPublished],
            'new_values' => ['is_published' => $newPublished],
        ]);

        return redirect()->route('admin.support.announcements')
            ->with('success', "Announcement '{$announcement->title}' {$statusStr}.");
    }

    /**
     * 1-Click Toggle pinned state.
     */
    public function togglePin(Announcement $announcement): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newPinned = !$announcement->is_pinned;
        $announcement->update(['is_pinned' => $newPinned]);

        $statusStr = $newPinned ? 'pinned to top' : 'unpinned';

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'announcement_pin_toggled',
            'description' => "Announcement '{$announcement->title}' {$statusStr}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['is_pinned' => !$newPinned],
            'new_values' => ['is_pinned' => $newPinned],
        ]);

        return redirect()->route('admin.support.announcements')
            ->with('success', "Announcement '{$announcement->title}' {$statusStr}.");
    }

    /**
     * Delete announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('create', User::class);

        $title = $announcement->title;
        $announcement->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'announcement_deleted',
            'description' => "Deleted announcement '{$title}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['title' => $title],
        ]);

        return redirect()->route('admin.support.announcements')
            ->with('success', "Announcement '{$title}' deleted.");
    }
}
