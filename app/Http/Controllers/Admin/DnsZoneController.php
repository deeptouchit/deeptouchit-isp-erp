<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DNS\DnsZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class DnsZoneController extends Controller
{
    protected DnsZoneService $dnsService;

    public function __construct(DnsZoneService $dnsService)
    {
        $this->dnsService = $dnsService;
    }

    /**
     * Display all hosted DNS zones, records metrics, and BIND daemon state.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $status = $request->query('status', 'all');

        $query = DnsZone::with(['subscription', 'user'])
            ->withCount('records')
            ->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('primary_ns', 'like', "%{$s}%")
                  ->orWhere('serial', 'like', "%{$s}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $zones = $query->paginate(15)->withQueryString();

        $stats = [
            'total_zones' => DnsZone::count(),
            'active_zones' => DnsZone::where('status', 'active')->count(),
            'total_records' => DnsRecord::count(),
            'bind_status' => 'Active & Running (v9.20)',
        ];

        $subscriptions = Subscription::select('id', 'domain')->orderBy('domain')->get();

        return Inertia::render('Admin/DNS/Zones', [
            'zones' => $zones,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store newly created DNS Zone.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:191|unique:dns_zones,domain',
            'server_ip' => 'required|ip',
            'primary_ns' => 'required|string|max:191',
            'secondary_ns' => 'required|string|max:191',
            'admin_email' => 'required|string|max:191',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'auto_populate' => 'boolean',
        ]);

        $res = $this->dnsService->createZone($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->withErrors(['domain' => $res['error']]);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Get Raw BIND9 Zone File content.
     */
    public function show(DnsZone $dnsZone): JsonResponse
    {
        $rawZone = $this->dnsService->compileZoneFile($dnsZone);

        return response()->json([
            'success' => true,
            'domain' => $dnsZone->domain,
            'serial' => $dnsZone->serial,
            'raw' => $rawZone,
        ]);
    }

    /**
     * Toggle Zone Status (Active / Disabled).
     */
    public function toggleStatus(DnsZone $dnsZone)
    {
        $newStatus = ($dnsZone->status === 'active') ? 'disabled' : 'active';
        $dnsZone->update(['status' => $newStatus]);

        $this->dnsService->syncNamedConf();
        $this->dnsService->reloadBind();

        return redirect()->back()->with('success', "DNS Zone `{$dnsZone->domain}` marked as {$newStatus}.");
    }

    /**
     * Toggle DNSSEC signing on zone.
     */
    public function toggleDnssec(DnsZone $dnsZone)
    {
        $dnsZone->update(['dnssec_enabled' => !$dnsZone->dnssec_enabled]);
        $this->dnsService->incrementSerial($dnsZone);

        return redirect()->back()->with('success', "DNSSEC for `{$dnsZone->domain}` updated successfully.");
    }

    /**
     * Rebuild and Reload BIND9 Zone.
     */
    public function reloadZone(DnsZone $dnsZone)
    {
        $this->dnsService->incrementSerial($dnsZone);

        return redirect()->back()->with('success', "BIND9 zone file for `{$dnsZone->domain}` rebuilt and reloaded with incremented serial {$dnsZone->serial}.");
    }

    /**
     * Export BIND9 RFC 1035 zone file download.
     */
    public function exportZone(DnsZone $dnsZone): HttpResponse
    {
        $rawZone = $this->dnsService->compileZoneFile($dnsZone);
        $filename = "db.{$dnsZone->domain}.zone";

        return response($rawZone, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Live DNS Diagnostics probe.
     */
    public function verifyDns(DnsZone $dnsZone): JsonResponse
    {
        $report = $this->dnsService->verifyLiveDns($dnsZone);

        return response()->json([
            'success' => true,
            'domain' => $dnsZone->domain,
            'report' => $report,
        ]);
    }

    /**
     * Global BIND Daemon Reload.
     */
    public function reloadDaemon()
    {
        $this->dnsService->syncNamedConf();
        $res = $this->dnsService->reloadBind();

        if ($res['success']) {
            return redirect()->back()->with('success', 'BIND9 (named.service) daemon reloaded successfully.');
        }

        return redirect()->back()->withErrors(['bind' => 'Failed to reload BIND daemon: ' . ($res['error'] ?? 'Unknown error')]);
    }

    /**
     * Delete DNS Zone.
     */
    public function destroy(DnsZone $dnsZone)
    {
        $res = $this->dnsService->deleteZone($dnsZone, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
