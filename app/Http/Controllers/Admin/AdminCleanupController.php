<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Files\AdminCleanupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCleanupController extends Controller
{
    protected AdminCleanupService $cleanupService;

    public function __construct(AdminCleanupService $cleanupService)
    {
        $this->cleanupService = $cleanupService;
    }

    /**
     * Display Junk & Temporary File Cleanup Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $scan = $this->cleanupService->scanJunk();

        return Inertia::render('Admin/Files/Cleanup', [
            'categories' => $scan['categories'],
            'stats' => $scan['stats'],
        ]);
    }

    /**
     * Clean a single junk category.
     */
    public function clean(Request $request, string $category): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->cleanupService->cleanCategory($category, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Perform universal system deep cleanup.
     */
    public function cleanAll(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->cleanupService->cleanAll(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
