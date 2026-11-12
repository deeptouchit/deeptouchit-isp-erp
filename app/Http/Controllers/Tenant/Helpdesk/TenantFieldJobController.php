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
        $user = auth()->user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        $tenantId = session('tenant_id');
        if (!$tenant && $tenantId) {
            $tenant = Tenant::find($tenantId);
        }

        if (!$tenant) {
            abort(403, __('Tenant context not found. Please log in.'));
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
        $zoneId = $request->get('zone_id', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Fetch Technicians, Coverage Zones & Recent Customers
        $technicians = User::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('role', 'like', '%tech%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->orderBy('name')
            ->get();

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->orderBy('name')->get();
        $customers = TenantCustomer::where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone', 'address', 'zone_id')
            ->orderBy('name')
            ->limit(300)
            ->get();

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

        if ($zoneId !== 'all' && !empty($zoneId)) {
            $query->where('zone_id', (int)$zoneId);
        }

        $jobs = $query->paginate($perPage)->withQueryString();

        // Active more filters counter for 2-Tier customer standard filter
        $activeMoreCount = 0;
        if ($technicianId !== 'all' && !empty($technicianId)) $activeMoreCount++;
        if ($priority !== 'all' && !empty($priority)) $activeMoreCount++;
        if ($zoneId !== 'all' && !empty($zoneId)) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;

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

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.tickets.field_jobs', compact(
            'tenant',
            'currencySymbol',
            'jobs',
            'stats',
            'technicians',
            'zones',
            'customers',
            'jobType',
            'priority',
            'status',
            'technicianId',
            'zoneId',
            'activeMoreCount',
            'isMoreOpen',
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
            'customer_name' => 'required_without:customer_id|nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'address' => 'required_without:customer_id|nullable|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'issue_description' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ], [], [
            'job_type' => __('Job Classification'),
            'customer_id' => __('Subscriber'),
            'customer_name' => __('Subscriber / Site Name'),
            'customer_phone' => __('Contact Phone'),
            'address' => __('Premise Address / Splicing Pole Location'),
            'priority' => __('Priority Level'),
            'assigned_to' => __('Assign Technician'),
            'issue_description' => __('Issue Description & Technical Notes'),
            'scheduled_at' => __('Scheduled Date & Time'),
        ]);

        $customerId = $request->customer_id ? (int)$request->customer_id : null;
        $customerName = $request->customer_name;
        $customerPhone = $request->customer_phone;
        $address = $request->address;

        if ($customerId) {
            $cust = TenantCustomer::where('tenant_id', $tenantId)->find($customerId);
            if ($cust) {
                $customerName = $customerName ?: $cust->name;
                $customerPhone = $customerPhone ?: $cust->phone;
                $address = $address ?: $cust->address;
            }
        }

        $jobNumber = 'JOB-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        while (TenantFieldJob::where('job_number', $jobNumber)->exists()) {
            $jobNumber = 'JOB-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        }

        $tech = $request->assigned_to ? User::where('tenant_id', $tenantId)->find($request->assigned_to) : null;

        TenantFieldJob::create([
            'tenant_id' => $tenantId,
            'job_number' => $jobNumber,
            'job_type' => $request->job_type,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'address' => $address,
            'zone_id' => $request->zone_id,
            'assigned_to' => $tech?->id,
            'assigned_technician_name' => $tech?->name,
            'priority' => $request->priority,
            'status' => $tech ? 'dispatched' : 'pending',
            'scheduled_at' => $request->scheduled_at ? Carbon::parse($request->scheduled_at) : now()->addHours(2),
            'optical_rx_before' => $request->optical_rx_before,
            'issue_description' => $request->issue_description,
            'created_by' => auth()->id() ?? 1,
        ]);

        return back()->with('success', __('Field job :jobNumber has been dispatched successfully.', ['jobNumber' => $jobNumber]));
    }

    /**
     * Delete Field Job (Super Admin / ISP Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $authUser = auth()->user();

        if (!$authUser || !$authUser->canDeleteTickets()) {
            $unauthorizedMessage = __('Unauthorized: Only Super Admin / ISP Admin can delete field jobs.');
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => $unauthorizedMessage,
                ], 403);
            }
            abort(403, $unauthorizedMessage);
        }

        $job = TenantFieldJob::where('tenant_id', $tenant->id)->findOrFail($id);
        $jobNo = $job->job_number;
        $job->delete();

        $deletedMessage = __('Field Job #:jobNumber deleted successfully.', ['jobNumber' => $jobNo]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $deletedMessage,
            ]);
        }

        return back()->with('success', $deletedMessage);
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
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid job status.'),
                ], 422);
            }
            return back()->with('error', __('Invalid job status.'));
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

        $statusLabels = [
            'pending' => __('Pending'),
            'dispatched' => __('Dispatched'),
            'in_progress' => __('In Progress'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
        ];
        $statusLabel = $statusLabels[$status] ?? strtoupper(str_replace('_', ' ', $status));
        $statusMessage = __('Job :jobNumber status updated to :status.', [
            'jobNumber' => $job->job_number,
            'status' => $statusLabel,
        ]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $statusMessage,
            ]);
        }

        return back()->with('success', $statusMessage);
    }

    /**
     * Standalone A4 Printable Field Job Sheet
     */
    public function printJobSheet(Request $request, $id): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $job = TenantFieldJob::where('tenant_id', $tenant->id)
            ->with(['customer', 'coverageZone', 'technician'])
            ->findOrFail($id);

        return view('tenant.tickets.field_job_print', compact('tenant', 'currencySymbol', 'job'));
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

        $jobTypeLabels = [
            'fiber_splicing' => __('Fiber Splicing & Optical Link'),
            'onu_replacement' => __('ONU Replacement / Upgrade'),
            'home_visit' => __('Home Visit & Diagnostics'),
            'cable_repair' => __('Drop Cable Re-pulling / Repair'),
            'pop_maintenance' => __('POP Node & Splitter Maintenance'),
            'new_connection' => __('New Physical Line Installation'),
        ];

        $priorityLabels = [
            'urgent' => __('Urgent'),
            'high' => __('High'),
            'medium' => __('Medium'),
            'low' => __('Low'),
        ];

        $statusLabels = [
            'pending' => __('Pending'),
            'dispatched' => __('Dispatched'),
            'in_progress' => __('In Progress'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
        ];

        return response()->stream(function () use ($jobs, $tenant, $jobTypeLabels, $priorityLabels, $statusLabels) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header Block
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, [__('FIELD TECHNICIAN DISPATCH & JOB ROSTER REPORT')]);
            fputcsv($handle, [__('Generated At'), now()->translatedFormat('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Column Headers
            fputcsv($handle, [
                __('SL'),
                __('Job Number'),
                __('Job Type'),
                __('Customer Name'),
                __('Customer Phone'),
                __('Address / Location'),
                __('Assigned Technician'),
                __('Priority'),
                __('Status'),
                __('Scheduled Date'),
                __('Completion Date'),
                __('Optical RX Before'),
                __('Optical RX After'),
                __('Issue Description'),
            ]);

            foreach ($jobs as $idx => $j) {
                fputcsv($handle, [
                    $idx + 1,
                    $j->job_number,
                    $jobTypeLabels[$j->job_type] ?? __($j->job_type_name),
                    $j->customer_name,
                    $j->customer_phone,
                    $j->address,
                    $j->assigned_technician_name ?: __('Unassigned'),
                    $priorityLabels[$j->priority] ?? strtoupper($j->priority),
                    $statusLabels[$j->status] ?? strtoupper($j->status),
                    $j->scheduled_at ? $j->scheduled_at->translatedFormat('d-M-Y h:i A') : __('N/A'),
                    $j->completed_at ? $j->completed_at->translatedFormat('d-M-Y h:i A') : __('N/A'),
                    $j->optical_rx_before ?: __('N/A'),
                    $j->optical_rx_after ?: __('N/A'),
                    $j->issue_description,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
