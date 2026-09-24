<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FirewallRule;
use App\Services\Security\FirewallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FirewallController extends Controller
{
    protected FirewallService $firewallService;

    public function __construct(FirewallService $firewallService)
    {
        $this->firewallService = $firewallService;
    }

    /**
     * Display Linux UFW Firewall console and packet filter rules.
     */
    public function index(Request $request): Response
    {
        $status = $this->firewallService->getFirewallStatus();
        $this->firewallService->syncFromSystem();

        $search = $request->query('search', '');
        $actionFilter = $request->query('action', 'all');

        $query = FirewallRule::latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('label', 'like', "%{$s}%")
                  ->orWhere('port', 'like', "%{$s}%")
                  ->orWhere('from_ip', 'like', "%{$s}%");
            });
        }

        if ($actionFilter !== 'all') {
            $query->where('action', $actionFilter);
        }

        $rules = $query->get();
        $listeningPorts = $this->firewallService->getListeningPorts();

        $allRules = FirewallRule::all();
        $stats = [
            'is_active' => $status['is_active'],
            'status_text' => $status['status_text'],
            'default_incoming' => $status['default_incoming'],
            'default_outgoing' => $status['default_outgoing'],
            'total_rules' => $allRules->count(),
            'allowed_count' => $allRules->where('action', 'allow')->count(),
            'blocked_count' => $allRules->whereIn('action', ['deny', 'reject', 'limit'])->count(),
            'listening_services' => count($listeningPorts),
        ];

        return Inertia::render('Admin/Security/Firewall', [
            'rules' => $rules,
            'listeningPorts' => $listeningPorts,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'action' => $actionFilter,
            ],
        ]);
    }

    /**
     * Store new Firewall Rule in UFW.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:191',
            'port' => 'required|string|max:100',
            'protocol' => 'required|string|in:tcp,udp,any',
            'action' => 'required|string|in:allow,deny,reject,limit',
            'from_ip' => 'nullable|string|max:100',
        ]);

        $res = $this->firewallService->createRule($validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update Firewall Rule in UFW and database.
     */
    public function update(Request $request, FirewallRule $firewallRule)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:191',
            'port' => 'required|string|max:100',
            'protocol' => 'required|string|in:tcp,udp,any',
            'action' => 'required|string|in:allow,deny,reject,limit',
            'from_ip' => 'nullable|string|max:100',
        ]);

        $res = $this->firewallService->updateRule($firewallRule, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle master UFW Firewall power.
     */
    public function toggleMaster(Request $request)
    {
        $validated = $request->validate([
            'enable' => 'required|boolean',
        ]);

        $res = $this->firewallService->toggleMaster($validated['enable'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Apply 1-Click Essential Service Preset.
     */
    public function applyPreset(Request $request)
    {
        $validated = $request->validate([
            'preset' => 'required|string|in:web,dns,mail,ssh_limit,ftp',
            'from_ip' => 'nullable|string|max:100',
        ]);

        $res = $this->firewallService->applyPreset(
            $validated['preset'],
            $validated['from_ip'] ?? null,
            auth()->id()
        );

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Sync Rules from OS UFW daemon.
     */
    public function syncSystem()
    {
        $this->firewallService->syncFromSystem();

        return redirect()->back()->with('success', 'Firewall rules synchronized from Linux UFW daemon.');
    }

    /**
     * Delete Firewall Rule from UFW and database.
     */
    public function destroy(FirewallRule $firewallRule)
    {
        $res = $this->firewallService->deleteRule($firewallRule, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
