<?php

namespace App\Http\Controllers\Tenant\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantResellerEscalation;
use App\Models\TenantReseller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantResellerEscalationController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display All Reseller Escalations & Tier-2/3 NOC Incident Pipeline
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $category = $request->get('category', 'all');
        $impact = $request->get('impact', 'all');
        $resellerId = $request->get('reseller_id', 'all');
        $status = $request->get('status', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Fetch Resellers & NOC Engineers
        $resellers = TenantReseller::where('tenant_id', $tenantId)->orderBy('name')->get();
        $engineers = User::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('role', 'like', '%tech%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->orderBy('name')
            ->get();

        // 2. Query Escalations
        $query = TenantResellerEscalation::where('tenant_id', $tenantId)
            ->with(['reseller', 'engineer'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('escalation_number', 'like', "%{$search}%")
                  ->orWhere('reseller_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('affected_circuits', 'like', "%{$search}%")
                  ->orWhere('assigned_engineer_name', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($impact !== 'all' && !empty($impact)) {
            $query->where('impact_level', $impact);
        }

        if ($resellerId !== 'all' && !empty($resellerId)) {
            $query->where('reseller_id', (int)$resellerId);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $escalations = $query->paginate($perPage)->withQueryString();

        // 3. Compute 6 KPI Stats
        $totalEscalations = TenantResellerEscalation::where('tenant_id', $tenantId)->count();
        $openCount = TenantResellerEscalation::where('tenant_id', $tenantId)->where('status', 'open')->count();
        $tier2Count = TenantResellerEscalation::where('tenant_id', $tenantId)->where('status', 'tier2_investigating')->count();
        $tier3Count = TenantResellerEscalation::where('tenant_id', $tenantId)->where('status', 'tier3_noc_escalated')->count();
        $criticalCount = TenantResellerEscalation::where('tenant_id', $tenantId)->where('impact_level', 'critical_outage')->whereNotIn('status', ['resolved', 'closed'])->count();
        $resolvedCount = TenantResellerEscalation::where('tenant_id', $tenantId)->whereIn('status', ['resolved', 'closed'])->count();

        $stats = [
            'total' => $totalEscalations,
            'open' => $openCount,
            'tier2' => $tier2Count,
            'tier3' => $tier3Count,
            'critical' => $criticalCount,
            'resolved' => $resolvedCount,
        ];

        return view('tenant.tickets.escalations', compact(
            'tenant',
            'escalations',
            'stats',
            'resellers',
            'engineers',
            'category',
            'impact',
            'resellerId',
            'status',
            'search',
            'perPage'
        ));
    }

    /**
     * Store Newly Escalated Reseller Technical Issue
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'category' => 'required|string',
            'subject' => 'required|string|max:255',
            'impact_level' => 'required|in:critical_outage,high_degraded,medium_packet_loss,low_inquiry',
            'affected_circuits' => 'nullable|string|max:255',
            'issue_description' => 'required|string',
            'assigned_engineer_id' => 'nullable|exists:users,id',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenantId)->findOrFail($request->reseller_id);
        $engineer = $request->assigned_engineer_id ? User::find($request->assigned_engineer_id) : null;

        $escNumber = 'ESC-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        while (TenantResellerEscalation::where('escalation_number', $escNumber)->exists()) {
            $escNumber = 'ESC-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        }

        TenantResellerEscalation::create([
            'tenant_id' => $tenantId,
            'escalation_number' => $escNumber,
            'reseller_id' => $reseller->id,
            'reseller_name' => $reseller->name,
            'contact_person' => $reseller->contact_person ?? $reseller->name,
            'contact_phone' => $reseller->phone,
            'category' => $request->category,
            'subject' => $request->subject,
            'impact_level' => $request->impact_level,
            'status' => $engineer ? 'tier2_investigating' : 'open',
            'assigned_engineer_id' => $engineer?->id,
            'assigned_engineer_name' => $engineer?->name,
            'affected_circuits' => $request->affected_circuits,
            'issue_description' => $request->issue_description,
            'first_response_at' => now(),
            'created_by' => auth()->id() ?? 1,
        ]);

        return back()->with('success', "Reseller technical escalation {$escNumber} logged successfully.");
    }

    /**
     * Update Escalation Status & RCA Resolution Summary
     */
    public function updateStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $escalation = TenantResellerEscalation::where('tenant_id', $tenant->id)->findOrFail($id);

        $status = $request->input('status');
        $validStatuses = ['open', 'tier2_investigating', 'tier3_noc_escalated', 'resolved', 'closed'];

        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Invalid escalation status.');
        }

        $escalation->status = $status;

        if ($request->filled('resolution_summary')) {
            $escalation->resolution_summary = $request->input('resolution_summary');
        }

        if (in_array($status, ['resolved', 'closed'])) {
            $escalation->resolved_at = now();
        }

        $escalation->save();

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => "Escalation {$escalation->escalation_number} status changed to " . strtoupper(str_replace('_', ' ', $status)) . ".",
            ]);
        }

        return back()->with('success', "Escalation {$escalation->escalation_number} status changed to " . strtoupper(str_replace('_', ' ', $status)) . ".");
    }

    /**
     * Standalone A4 Printable Escalation Telemetry & Root Cause Analysis Statement
     */
    public function printReport(Request $request, $id): View
    {
        $tenant = $this->getTenant();
        $escalation = TenantResellerEscalation::where('tenant_id', $tenant->id)
            ->with(['reseller', 'engineer'])
            ->findOrFail($id);

        return view('tenant.tickets.escalation_print', compact('tenant', 'escalation'));
    }

    /**
     * Streamed CSV Export of Reseller Technical Escalations
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $filename = 'reseller_escalations_log_' . date('Y_m_d_His') . '.csv';

        $escalations = TenantResellerEscalation::where('tenant_id', $tenant->id)
            ->with(['reseller', 'engineer'])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($escalations, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header Block
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, ['SUB-ISP & RESELLER TECHNICAL ESCALATION LOG']);
            fputcsv($handle, ['Generated At', date('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Column Headers
            fputcsv($handle, [
                'SL',
                'Escalation Number',
                'Sub-ISP / Reseller Name',
                'Technical Category',
                'Issue Subject',
                'Impact Severity',
                'Affected Trunk / Circuit',
                'Assigned NOC Engineer',
                'Status',
                'Logged Date',
                'Resolved Date',
                'RCA Resolution Summary',
            ]);

            foreach ($escalations as $idx => $e) {
                fputcsv($handle, [
                    $idx + 1,
                    $e->escalation_number,
                    $e->reseller_name,
                    $e->category_name,
                    $e->subject,
                    strtoupper(str_replace('_', ' ', $e->impact_level)),
                    $e->affected_circuits ?: 'N/A',
                    $e->assigned_engineer_name ?: 'Unassigned',
                    strtoupper(str_replace('_', ' ', $e->status)),
                    $e->created_at ? $e->created_at->format('d-M-Y h:i A') : 'N/A',
                    $e->resolved_at ? $e->resolved_at->format('d-M-Y h:i A') : 'Pending',
                    $e->resolution_summary ?: 'Under active investigation',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
