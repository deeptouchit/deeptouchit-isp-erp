<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBlock;
use App\Models\Subscription;
use App\Services\Security\IpBlockService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class IpBlockController extends Controller
{
    protected IpBlockService $ipBlockService;

    public function __construct(IpBlockService $ipBlockService)
    {
        $this->ipBlockService = $ipBlockService;
    }

    /**
     * Display IP Blocklist & Threat Mitigation console.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $filter = $request->query('filter', 'all');

        $query = IpBlock::with(['creator', 'subscription'])->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                  ->orWhere('reason', 'like', "%{$s}%");
            });
        }

        if ($filter === 'permanent') {
            $query->whereNull('expires_at');
        } elseif ($filter === 'temporary') {
            $query->whereNotNull('expires_at')->where('expires_at', '>', now());
        } elseif ($filter === 'subnet') {
            $query->where('is_subnet', true);
        }

        $ipBlocks = $query->paginate(20)->withQueryString();

        $allBlocks = IpBlock::all();
        $stats = [
            'total_blocks' => $allBlocks->count(),
            'active_blocks' => $allBlocks->filter(fn($b) => !$b->is_expired)->count(),
            'subnet_blocks' => $allBlocks->where('is_subnet', true)->count(),
            'temporary_blocks' => $allBlocks->filter(fn($b) => $b->expires_at && !$b->is_expired)->count(),
            'permanent_blocks' => $allBlocks->whereNull('expires_at')->count(),
        ];

        $subscriptions = Subscription::select('id', 'domain')->orderBy('domain')->get();

        return Inertia::render('Admin/Security/Blocklist', [
            'ipBlocks' => $ipBlocks,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    /**
     * Store new single IP block.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|string|max:45',
            'reason' => 'nullable|string|max:255',
            'duration' => 'nullable|string|in:1h,24h,7d,30d,90d,permanent',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $res = $this->ipBlockService->blockIp($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Bulk store multiple IP blocks.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'ip_list' => 'required|string',
            'reason' => 'nullable|string|max:255',
            'duration' => 'nullable|string|in:1h,24h,7d,30d,90d,permanent',
        ]);

        $res = $this->ipBlockService->bulkBlock($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update IP block details (Reason or Expiration).
     */
    public function update(Request $request, IpBlock $ipBlock)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'duration' => 'nullable|string|in:1h,24h,7d,30d,90d,permanent',
        ]);

        $res = $this->ipBlockService->updateBlock($ipBlock, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Unblock IP address.
     */
    public function destroy(IpBlock $ipBlock)
    {
        $res = $this->ipBlockService->unblock($ipBlock, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Export Blocked IPs.
     */
    public function export(Request $request): HttpResponse
    {
        $ips = IpBlock::all()->pluck('ip_address')->implode("\n");

        return response($ips, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="deeptouchhost-blocked-ips.txt"',
        ]);
    }
}
