<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DnsZone;
use App\Services\DNS\DnsDiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DnsDiagnosticsController extends Controller
{
    protected DnsDiagnosticsService $diagService;

    public function __construct(DnsDiagnosticsService $diagService)
    {
        $this->diagService = $diagService;
    }

    /**
     * Display DNS Diagnostics & RFC Health Audit console.
     */
    public function index(Request $request): Response
    {
        $zones = DnsZone::select('id', 'domain', 'status')->orderBy('domain')->get();

        $targetDomain = $request->query('domain');
        if (!$targetDomain) {
            $targetDomain = $zones->first()?->domain ?? 'deeptouchit.com';
        }

        $publicIp = \App\Support\ServerHelper::getPublicIp();
        $audit = $this->diagService->runFullAudit($targetDomain);
        $rdns = $this->diagService->checkRdns($publicIp);
        $initialDig = $this->diagService->executeDig($targetDomain, 'A', '127.0.0.1');

        $stats = [
            'score' => $audit['score'],
            'target_domain' => $targetDomain,
            'server_ip' => $publicIp,
            'port53_status' => 'Listening (UDP/TCP 53)',
            'total_zones' => $zones->count(),
        ];

        return Inertia::render('Admin/DNS/Diagnostics', [
            'audit' => $audit,
            'zones' => $zones,
            'rdns' => $rdns,
            'initialDig' => $initialDig,
            'stats' => $stats,
            'filters' => [
                'domain' => $targetDomain,
            ],
        ]);
    }

    /**
     * AJAX endpoint for Interactive Web Dig.
     */
    public function executeDig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'type' => 'required|string|in:ANY,A,AAAA,CNAME,MX,TXT,NS,SOA,PTR,SRV,CAA',
            'nameserver' => 'nullable|string',
        ]);

        $ns = !empty($validated['nameserver']) ? $validated['nameserver'] : '127.0.0.1';
        $res = $this->diagService->executeDig($validated['domain'], $validated['type'], $ns);

        return response()->json($res);
    }

    /**
     * AJAX endpoint for rDNS check.
     */
    public function checkRdns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
        ]);

        $res = $this->diagService->checkRdns($validated['ip']);

        return response()->json($res);
    }
}
