<?php

namespace App\Http\Controllers\Tenant\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantFieldJob;
use App\Models\TenantCustomer;
use App\Models\TenantCoverageZone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantFieldJobController extends Controller
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
     * Display All Assigned Field Jobs & Dispatch Roster
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $jobType = $request->get('job_type', 'all');
        $priority = $request->get('priority', 'all');
        $status = $request->get('status', 'all');
        $technicianId = $request->get('technician_id', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Fetch Technicians & Coverage Zones for Filters
        $technicians = User::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('role', 'like', '%tech%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->orderBy('name')
            ->get();

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->orderBy('name')->get();

        // 2. Query Field Jobs
        $query = TenantFieldJob::where('tenant_id', $tenantId)
            ->with(['customer', 'coverageZone', 'technician'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('assigned_technician_name', 'like', "%{$search}%")
                  ->orWhere('issue_description', 'like', "%{$search}%");
            });
        }

        if ($jobType !== 'all' && !empty($jobType)) {
            $query->where('job_type', $jobType);
        }

        if ($priority !== 'all' && !empty($priority)) {
            $query->where('priority', $priority);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if ($technicianId !== 'all' && !empty($technicianId)) {
            $query->where('assigned_to', (int)$technicianId);
        }

        $jobs = $query->paginate($perPage)->withQueryString();

        // 3. Compute 6 KPI Stats
        $totalJobs = TenantFieldJob::where('tenant_id', $tenantId)->count();
        $pendingJobs = TenantFieldJob::where('tenant_id', $tenantId)->where('status', 'pending')->count();
        $dispatchedJobs = TenantFieldJob::where('tenant_id', $tenantId)->where('status', 'dispatched')->count();
        $inProgressJobs = TenantFieldJob::where('tenant_id', $tenantId)->where('status', 'in_progress')->count();
        $urgentJobs = TenantFieldJob::where('tenant_id', $tenantId)->whereIn('priority', ['urgent', 'high'])->whereNotIn('status', ['completed', 'cancelled'])->count();
        $completedJobs = TenantFieldJob::where('tenant_id', $tenantId)->where('status', 'completed')->count();

        $stats = [
            'total' => $totalJobs,
            'pending' => $pendingJobs,
            'dispatched' => $dispatchedJobs,
            'in_progress' => $inProgressJobs,
            'urgent' => $urgentJobs,
            'completed' => $completedJobs,
        ];

        return view('tenant.tickets.field_jobs', compact(
            'tenant',
            'jobs',
            'stats',
            'technicians',
            'zones',
            'jobType',
            'priority',
            'status',
            'technicianId',
            'search',
            'perPage'
        ));
    }

    /**
     * Store Newly Dispatched Field Task
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $request->validate([
            'job_type' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'address' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'issue_description' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $jobNumber = 'JOB-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        while (TenantFieldJob::where('job_number', $jobNumber)->exists()) {
            $jobNumber = 'JOB-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        }

        $tech = $request->assigned_to ? User::find($request->assigned_to) : null;

        TenantFieldJob::create([
            'tenant_id' => $tenantId,
            'job_number' => $jobNumber,
            'job_type' => $request->job_type,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'address' => $request->address,
            'zone_id' => $request->zone_id,
            'assigned_to' => $tech?->id,
            'assigned_technician_name' => $tech?->name,
            'priority' => $request->priority,
            'status' => $tech ? 'dispatched' : 'pending',
            'scheduled_at' => $request->scheduled_at ? Carbon::parse($request->scheduled_at) : now()->addHours(2),
            'issue_description' => $request->issue_description,
            'created_by' => auth()->id() ?? 1,
        ]);

        return back()->with('success', "Field job {$jobNumber} has been dispatched successfully.");
    }

    /**
     * Update Field Job Status
     */
    public function updateStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $job = TenantFieldJob::where('tenant_id', $tenant->id)->findOrFail($id);

        $status = $request->input('status');
        if (!in_array($status, ['pending', 'dispatched', 'in_progress', 'completed', 'cancelled'])) {
            return back()->with('error', 'Invalid job status.');
        }

        $job->status = $status;
        if ($status === 'completed') {
            $job->completed_at = now();
            if ($request->filled('resolution_notes')) {
                $job->resolution_notes = $request->input('resolution_notes');
            }
            if ($request->filled('optical_rx_after')) {
                $job->optical_rx_after = $request->input('optical_rx_after');
            }
        }
        $job->save();

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => "Job {$job->job_number} status updated to " . strtoupper($status) . ".",
            ]);
        }

        return back()->with('success', "Job {$job->job_number} status updated to " . strtoupper($status) . ".");
    }

    /**
     * Standalone A4 Printable Field Job Sheet
     */
    public function printJobSheet(Request $request, $id): View
    {
        $tenant = $this->getTenant();
        $job = TenantFieldJob::where('tenant_id', $tenant->id)
            ->with(['customer', 'coverageZone', 'technician'])
            ->findOrFail($id);

        return view('tenant.tickets.field_job_print', compact('tenant', 'job'));
    }

    /**
     * Streamed CSV Export of Field Tasks
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $filename = 'field_dispatch_jobs_' . date('Y_m_d_His') . '.csv';

        $jobs = TenantFieldJob::where('tenant_id', $tenant->id)
            ->with(['customer', 'coverageZone', 'technician'])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($jobs, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header Block
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, ['FIELD TECHNICIAN DISPATCH & JOB ROSTER REPORT']);
            fputcsv($handle, ['Generated At', date('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Column Headers
            fputcsv($handle, [
                'SL',
                'Job Number',
                'Job Type',
                'Customer Name',
                'Customer Phone',
                'Address / Location',
                'Assigned Technician',
                'Priority',
                'Status',
                'Scheduled Date',
                'Completion Date',
                'Optical RX Before',
                'Optical RX After',
                'Issue Description',
            ]);

            foreach ($jobs as $idx => $j) {
                fputcsv($handle, [
                    $idx + 1,
                    $j->job_number,
                    $j->job_type_name,
                    $j->customer_name,
                    $j->customer_phone,
                    $j->address,
                    $j->assigned_technician_name ?: 'Unassigned',
                    strtoupper($j->priority),
                    strtoupper($j->status),
                    $j->scheduled_at ? $j->scheduled_at->format('d-M-Y h:i A') : 'N/A',
                    $j->completed_at ? $j->completed_at->format('d-M-Y h:i A') : 'N/A',
                    $j->optical_rx_before ?: 'N/A',
                    $j->optical_rx_after ?: 'N/A',
                    $j->issue_description,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
