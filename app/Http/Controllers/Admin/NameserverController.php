<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsZone;
use App\Models\Nameserver;
use App\Services\DNS\NameserverService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NameserverController extends Controller
{
    protected NameserverService $nameserverService;

    public function __construct(NameserverService $nameserverService)
    {
        $this->nameserverService = $nameserverService;
    }

    /**
     * Display Authoritative Nameservers & Glue Records console.
     */
    public function index(Request $request): Response
    {
        $this->nameserverService->ensureDefaultNameservers();

        $nameservers = Nameserver::orderByDesc('is_primary')
            ->orderByDesc('is_default')
            ->get();

        $primaryNs = $nameservers->firstWhere('is_primary', true) ?: $nameservers->first();
        $secondaryNs = $nameservers->where('id', '!=', $primaryNs?->id)->first();

        $stats = [
            'total_nameservers' => $nameservers->count(),
            'primary_ns' => $primaryNs ? $primaryNs->hostname : 'None',
            'secondary_ns' => $secondaryNs ? $secondaryNs->hostname : 'None',
            'primary_ip' => $primaryNs ? $primaryNs->ip_address : \App\Support\ServerHelper::getPublicIp(),
            'total_zones' => DnsZone::count(),
            'bind_port53' => 'Listening (UDP/TCP 53)',
        ];

        return Inertia::render('Admin/DNS/Nameservers', [
            'nameservers' => $nameservers,
            'stats' => $stats,
        ]);
    }

    /**
     * Store new Nameserver node.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:191|unique:nameservers,hostname',
            'ip_address' => 'required|ip',
            'ipv6_address' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $ns = $this->nameserverService->createNameserver($validated, auth()->id());

        return redirect()->back()->with('success', "Nameserver node `{$ns->hostname}` registered successfully.");
    }

    /**
     * Update Nameserver node.
     */
    public function update(Request $request, Nameserver $nameserver)
    {
        $validated = $request->validate([
            'hostname' => 'required|string|max:191|unique:nameservers,hostname,' . $nameserver->id,
            'ip_address' => 'required|ip',
            'ipv6_address' => 'nullable|string|max:100',
            'is_primary' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $this->nameserverService->updateNameserver($nameserver, $validated, auth()->id());

        return redirect()->back()->with('success', "Nameserver node `{$nameserver->hostname}` updated successfully.");
    }

    /**
     * Toggle Nameserver status.
     */
    public function toggleStatus(Nameserver $nameserver)
    {
        $newStatus = ($nameserver->status === 'active') ? 'disabled' : 'active';
        $nameserver->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Nameserver `{$nameserver->hostname}` marked as {$newStatus}.");
    }

    /**
     * Test single nameserver socket probe.
     */
    public function testProbe(Nameserver $nameserver)
    {
        $res = $this->nameserverService->testNameserver($nameserver);

        return redirect()->back()->with('success', "Nameserver `{$nameserver->hostname}` is {$res['status']} ({$res['latency_ms']}ms latency).");
    }

    /**
     * Test all nameservers.
     */
    public function testAll()
    {
        $this->nameserverService->testAllNameservers();

        return redirect()->back()->with('success', 'All authoritative nameservers probed successfully on Port 53.');
    }

    /**
     * Bulk sync nameservers to all hosted zones.
     */
    public function syncZones(Request $request)
    {
        $validated = $request->validate([
            'primary_ns' => 'required|string|max:191',
            'secondary_ns' => 'required|string|max:191',
        ]);

        $res = $this->nameserverService->syncToAllZones(
            $validated['primary_ns'],
            $validated['secondary_ns']
        );

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete custom nameserver.
     */
    public function destroy(Nameserver $nameserver)
    {
        $res = $this->nameserverService->deleteNameserver($nameserver);

        if (!$res['success']) {
            return redirect()->back()->withErrors(['nameserver' => $res['error']]);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
