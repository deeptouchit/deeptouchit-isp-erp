<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpAllowlist;
use App\Models\Subscription;
use App\Services\Security\IpAllowlistService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class IpAllowlistController extends Controller
{
    protected IpAllowlistService $ipAllowlistService;

    public function __construct(IpAllowlistService $ipAllowlistService)
    {
        $this->ipAllowlistService = $ipAllowlistService;
    }

    /**
     * Display Trusted IP Allowlist (Whitelist) console.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $scope = $request->query('scope', 'all');

        $query = IpAllowlist::with(['creator', 'subscription'])->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($scope !== 'all') {
            $query->where('scope', $scope);
        }

        $ipAllowlists = $query->paginate(20)->withQueryString();

        $allAllows = IpAllowlist::all();
        $stats = [
            'total_allows' => $allAllows->count(),
            'active_allows' => $allAllows->filter(fn($a) => !$a->is_expired)->count(),
            'subnet_allows' => $allAllows->where('is_subnet', true)->count(),
            'global_allows' => $allAllows->where('scope', 'global')->count(),
            'temporary_allows' => $allAllows->filter(fn($a) => $a->expires_at && !$a->is_expired)->count(),
        ];

        $currentIp = $request->ip();
        $isCurrentIpAllowed = IpAllowlist::where('ip_address', $currentIp)->exists();
        $subscriptions = Subscription::select('id', 'domain')->orderBy('domain')->get();

        return Inertia::render('Admin/Security/Allowlist', [
            'ipAllowlists' => $ipAllowlists,
            'stats' => $stats,
            'currentIp' => $currentIp,
            'isCurrentIpAllowed' => $isCurrentIpAllowed,
            'subscriptions' => $subscriptions,
            'filters' => [
                'search' => $search,
                'scope' => $scope,
            ],
        ]);
    }

    /**
     * Store new Allowlist IP.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|string|max:45',
            'label' => 'nullable|string|max:191',
            'scope' => 'required|string|in:global,ssh_only,database,panel_admin',
            'duration' => 'nullable|string|in:24h,7d,30d,90d,permanent',
            'notes' => 'nullable|string|max:500',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $res = $this->ipAllowlistService->allowIp($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * 1-Click Whitelist Current Admin IP.
     */
    public function allowMyIp(Request $request)
    {
        $currentIp = $request->ip();

        $res = $this->ipAllowlistService->allowIp([
            'ip_address' => $currentIp,
            'label' => 'Lead Administrator Station (Auto-Detected)',
            'scope' => 'global',
            'duration' => '30d',
            'notes' => 'Automatically added via 1-Click Whitelist My IP button.',
        ], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update Allowlist details.
     */
    public function update(Request $request, IpAllowlist $ipAllowlist)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:191',
            'scope' => 'required|string|in:global,ssh_only,database,panel_admin',
            'duration' => 'nullable|string|in:24h,7d,30d,90d,permanent',
            'notes' => 'nullable|string|max:500',
        ]);

        $res = $this->ipAllowlistService->updateAllow($ipAllowlist, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Remove from Allowlist.
     */
    public function destroy(IpAllowlist $ipAllowlist)
    {
        $res = $this->ipAllowlistService->removeAllow($ipAllowlist, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Export Whitelist as plain text.
     */
    public function export(Request $request): HttpResponse
    {
        $ips = IpAllowlist::all()->pluck('ip_address')->implode("\n");

        return response($ips, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="deeptouchhost-allowlist-ips.txt"',
        ]);
    }
}
