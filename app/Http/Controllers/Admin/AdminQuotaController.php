<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Files\AdminQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminQuotaController extends Controller
{
    protected AdminQuotaService $quotaService;

    public function __construct(AdminQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Display Disk Quotas & Inode Limits Governance Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status']);
        $data = $this->quotaService->getQuotasOverview($filters);

        return Inertia::render('Admin/Files/Quotas', [
            'quotas' => $data['quotas'],
            'stats' => $data['stats'],
            'filters' => $filters,
        ]);
    }

    /**
     * Update quota & inode limits for a subscription.
     */
    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'custom_disk_space' => 'nullable|integer|min:0',
            'custom_inodes' => 'nullable|integer|min:0',
        ]);

        $res = $this->quotaService->updateQuota($subscription, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
