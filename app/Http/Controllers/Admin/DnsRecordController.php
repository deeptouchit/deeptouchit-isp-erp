<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Services\DNS\DnsRecordService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DnsRecordController extends Controller
{
    protected DnsRecordService $recordService;

    public function __construct(DnsRecordService $recordService)
    {
        $this->recordService = $recordService;
    }

    /**
     * Display DNS Resource Records Management console.
     */
    public function index(Request $request): Response
    {
        $zones = DnsZone::select('id', 'domain', 'serial', 'status')->orderBy('domain')->get();

        $selectedZoneId = $request->query('zone_id');
        $selectedZone = null;

        if ($selectedZoneId) {
            $selectedZone = DnsZone::find($selectedZoneId);
        }

        if (!$selectedZone && $zones->isNotEmpty()) {
            $selectedZone = $zones->first();
        }

        $typeFilter = $request->query('type', 'ALL');
        $search = $request->query('search', '');

        $records = [];
        $stats = [
            'total_records' => 0,
            'address_count' => 0,
            'mx_count' => 0,
            'txt_count' => 0,
        ];

        if ($selectedZone) {
            $query = $selectedZone->records()->latest();

            if ($typeFilter !== 'ALL') {
                $query->where('type', $typeFilter);
            }

            if ($search && trim($search)) {
                $s = trim($search);
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('content', 'like', "%{$s}%")
                      ->orWhere('type', 'like', "%{$s}%");
                });
            }

            $records = $query->get();

            $allZoneRecords = $selectedZone->records()->get();
            $stats = [
                'total_records' => $allZoneRecords->count(),
                'address_count' => $allZoneRecords->whereIn('type', ['A', 'AAAA', 'CNAME'])->count(),
                'mx_count' => $allZoneRecords->where('type', 'MX')->count(),
                'txt_count' => $allZoneRecords->where('type', 'TXT')->count(),
            ];
        }

        return Inertia::render('Admin/DNS/Records', [
            'zones' => $zones,
            'selectedZone' => $selectedZone,
            'records' => $records,
            'stats' => $stats,
            'filters' => [
                'type' => $typeFilter,
                'search' => $search,
                'zone_id' => $selectedZone?->id,
            ],
        ]);
    }

    /**
     * Store new DNS Record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dns_zone_id' => 'required|exists:dns_zones,id',
            'name' => 'required|string|max:191',
            'type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA,PTR',
            'content' => 'required|string',
            'ttl' => 'required|integer|min:60|max:604800',
            'priority' => 'nullable|integer|min:0|max:65535',
            'port' => 'nullable|integer|min:1|max:65535',
            'weight' => 'nullable|integer|min:0|max:65535',
        ]);

        $zone = DnsZone::findOrFail($validated['dns_zone_id']);
        $res = $this->recordService->createRecord($zone, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update DNS Record.
     */
    public function update(Request $request, DnsRecord $dnsRecord)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA,PTR',
            'content' => 'required|string',
            'ttl' => 'required|integer|min:60|max:604800',
            'priority' => 'nullable|integer|min:0|max:65535',
            'port' => 'nullable|integer|min:1|max:65535',
            'weight' => 'nullable|integer|min:0|max:65535',
        ]);

        $res = $this->recordService->updateRecord($dnsRecord, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle Record Active / Disabled status.
     */
    public function toggleStatus(DnsRecord $dnsRecord)
    {
        $newStatus = ($dnsRecord->status === 'active') ? 'disabled' : 'active';
        $dnsRecord->update(['status' => $newStatus]);

        $this->recordService->updateRecord($dnsRecord, ['status' => $newStatus], auth()->id());

        return redirect()->back()->with('success', "DNS record `{$dnsRecord->name}` marked as {$newStatus}.");
    }

    /**
     * Apply 1-Click Preset (Google Workspace, M365, etc.).
     */
    public function applyPreset(Request $request)
    {
        $validated = $request->validate([
            'dns_zone_id' => 'required|exists:dns_zones,id',
            'preset' => 'required|string|in:google_workspace,microsoft_365,lets_encrypt_caa',
        ]);

        $zone = DnsZone::findOrFail($validated['dns_zone_id']);
        $res = $this->recordService->applyPreset($zone, $validated['preset']);

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete DNS Record.
     */
    public function destroy(DnsRecord $dnsRecord)
    {
        $res = $this->recordService->deleteRecord($dnsRecord, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
