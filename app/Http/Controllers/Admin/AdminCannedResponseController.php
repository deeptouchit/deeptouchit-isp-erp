<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CannedResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCannedResponseController extends Controller
{
    /**
     * Display directory of predefined canned templates, categories, and invocation stats.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = CannedResponse::with('creator:id,first_name,last_name,username,email');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('shortcut_code', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $templates = $query->orderBy('sort_order', 'asc')->get();

        // 4 Clean 3-Tier Metric Stats
        $allTemplates = CannedResponse::all();
        $technicalCount = $allTemplates->where('category', 'technical')->count();
        $billingCount = $allTemplates->where('category', 'billing')->count();
        $totalUsage = $allTemplates->sum('usage_count');

        $stats = [
            'total_templates' => $allTemplates->count(),
            'technical_count' => $technicalCount,
            'billing_count' => $billingCount,
            'total_usage_count' => $totalUsage,
        ];

        return Inertia::render('Admin/Support/CannedResponses/Index', [
            'templates' => $templates,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Store new canned response template.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:technical,billing,sales,security,general'],
            'shortcut_code' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string'],
            'is_shared' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['created_by'] = auth()->id() ?: 1;
        $validated['is_shared'] = $validated['is_shared'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $template = CannedResponse::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'canned_response_created',
            'description' => "Created canned template '{$template->title}' ({$template->category}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['title' => $template->title, 'category' => $template->category],
        ]);

        return redirect()->route('admin.support.canned-responses')
            ->with('success', "Canned response '{$template->title}' created successfully.");
    }

    /**
     * Update canned response template.
     */
    public function update(Request $request, CannedResponse $cannedResponse): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:technical,billing,sales,security,general'],
            'shortcut_code' => ['nullable', 'string', 'max:50'],
            'content' => ['required', 'string'],
            'is_shared' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $cannedResponse->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'canned_response_updated',
            'description' => "Updated canned template '{$cannedResponse->title}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.support.canned-responses')
            ->with('success', "Canned response '{$cannedResponse->title}' updated successfully.");
    }

    /**
     * Increment usage counter when template is pasted into ticket.
     */
    public function incrementUsage(CannedResponse $cannedResponse): JsonResponse
    {
        $cannedResponse->increment('usage_count');

        return response()->json([
            'status' => 'success',
            'id' => $cannedResponse->id,
            'usage_count' => $cannedResponse->usage_count,
        ]);
    }

    /**
     * Delete canned response template.
     */
    public function destroy(CannedResponse $cannedResponse): RedirectResponse
    {
        $this->authorize('create', User::class);

        $title = $cannedResponse->title;
        $cannedResponse->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'canned_response_deleted',
            'description' => "Deleted canned template '{$title}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['title' => $title],
        ]);

        return redirect()->route('admin.support.canned-responses')
            ->with('success', "Canned response '{$title}' deleted.");
    }
}
