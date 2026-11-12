<?php

namespace App\Http\Controllers\Tenant\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInstallationTask;
use App\Models\TenantCoverageZone;
use App\Models\TenantInternetPackage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantInstallationTaskController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, __('Tenant context not found. Please log in.'));
        }

        return $tenant;
    }

    /**
     * Get localized pipeline stage display label
     */
    protected function localizedStageName(?string $stage, ?string $fallback = null): string
    {
        return match ($stage) {
            'feasibility_check' => __('1. Feasibility & Port Check'),
            'cable_pulling' => __('2. Drop Cable Pulling'),
            'splicing_power_test' => __('3. Splicing & Power Test'),
            'mikrotik_binding' => __('4. Router / PPPoE Binding'),
            'active_completed' => __('5. Activated & Live'),
            'cancelled' => __('Cancelled'),
            default => __($fallback ?: ucwords(str_replace('_', ' ', (string) $stage))),
        };
    }

    /**
     * Get localized task status display label
     */
    protected function localizedStatusName(?string $status): string
    {
        return match ($status) {
            'pending' => __('Pending'),
            'scheduled' => __('Scheduled'),
            'in_progress' => __('In Progress'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            default => __((string) $status),
        };
    }

    /**
     * Display New Installation Tasks & Provisioning Pipeline
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $stage = $request->get('stage', 'all');
        $zoneId = $request->get('zone_id', 'all');
        $technicianId = $request->get('technician_id', 'all');
        $packageId = $request->get('package_id', 'all');
        $status = $request->get('status', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Fetch Dropdown Resources
        $technicians = User::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('role', 'like', '%tech%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->orderBy('name')
            ->get();

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->orderBy('name')->get();
        $packages = TenantInternetPackage::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        // 2. Query Installation Tasks
        $query = TenantInstallationTask::where('tenant_id', $tenantId)
            ->with(['coverageZone', 'package', 'technician'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('task_number', 'like', "%{$search}%")
                  ->orWhere('applicant_name', 'like', "%{$search}%")
                  ->orWhere('applicant_phone', 'like', "%{$search}%")
                  ->orWhere('installation_address', 'like', "%{$search}%")
                  ->orWhere('assigned_technician_name', 'like', "%{$search}%")
                  ->orWhere('splitter_location', 'like', "%{$search}%");
            });
        }

        if ($stage !== 'all' && !empty($stage)) {
            $query->where('installation_stage', $stage);
        }

        if ($zoneId !== 'all' && !empty($zoneId)) {
            $query->where('zone_id', (int)$zoneId);
        }

        if ($technicianId !== 'all' && !empty($technicianId)) {
            $query->where('assigned_technician_id', (int)$technicianId);
        }

        if ($packageId !== 'all' && !empty($packageId)) {
            $query->where('package_id', (int)$packageId);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $tasks = $query->paginate($perPage)->withQueryString();

        // Active more filters counter for 2-Tier customer standard filter
        $activeMoreCount = 0;
        if ($technicianId !== 'all' && !empty($technicianId)) $activeMoreCount++;
        if ($packageId !== 'all' && !empty($packageId)) $activeMoreCount++;
        if ($status !== 'all' && !empty($status)) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;

        // 3. Compute 6 Pipeline Stage KPIs
        $totalOrders = TenantInstallationTask::where('tenant_id', $tenantId)->count();
        $feasibilityCount = TenantInstallationTask::where('tenant_id', $tenantId)->where('installation_stage', 'feasibility_check')->count();
        $cablePullingCount = TenantInstallationTask::where('tenant_id', $tenantId)->where('installation_stage', 'cable_pulling')->count();
        $splicingCount = TenantInstallationTask::where('tenant_id', $tenantId)->where('installation_stage', 'splicing_power_test')->count();
        $bindingCount = TenantInstallationTask::where('tenant_id', $tenantId)->where('installation_stage', 'mikrotik_binding')->count();
        $completedCount = TenantInstallationTask::where('tenant_id', $tenantId)->where('installation_stage', 'active_completed')->count();

        $stats = [
            'total' => $totalOrders,
            'feasibility' => $feasibilityCount,
            'cable_pulling' => $cablePullingCount,
            'splicing' => $splicingCount,
            'binding' => $bindingCount,
            'completed' => $completedCount,
        ];

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.tickets.installations', compact(
            'tenant',
            'currencySymbol',
            'tasks',
            'stats',
            'technicians',
            'zones',
            'packages',
            'stage',
            'zoneId',
            'technicianId',
            'packageId',
            'activeMoreCount',
            'isMoreOpen',
            'status',
            'search',
            'perPage'
        ));
    }

    /**
     * Store Newly Created Physical Installation Task
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $request->validate([
            'applicant_name' => 'required|string|max:255',
            'applicant_phone' => 'required|string|max:30',
            'installation_address' => 'required|string|max:255',
            'package_id' => 'required|exists:tenant_internet_packages,id',
            'zone_id' => 'nullable|exists:tenant_coverage_zones,id',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'connection_fee' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'scheduled_at' => 'nullable|date',
            'remarks' => 'nullable|string',
        ], [
            'applicant_name.required' => __('Subscriber full name is required.'),
            'applicant_phone.required' => __('Contact phone is required.'),
            'installation_address.required' => __('Installation address is required.'),
            'package_id.required' => __('Internet package is required.'),
            'package_id.exists' => __('Selected internet package is invalid.'),
            'zone_id.exists' => __('Selected coverage area is invalid.'),
            'assigned_technician_id.exists' => __('Selected technician is invalid.'),
            'connection_fee.numeric' => __('Connection fee must be a valid number.'),
            'connection_fee.min' => __('Connection fee cannot be negative.'),
            'advance_payment.numeric' => __('Advance payment must be a valid number.'),
            'advance_payment.min' => __('Advance payment cannot be negative.'),
            'scheduled_at.date' => __('Scheduled date and time must be a valid date.'),
        ], [
            'applicant_name' => __('Subscriber Full Name'),
            'applicant_phone' => __('Contact Phone'),
            'installation_address' => __('Installation Address'),
            'package_id' => __('Internet Package'),
            'zone_id' => __('Coverage Area'),
            'assigned_technician_id' => __('Assign Technician'),
            'connection_fee' => __('Connection Fee'),
            'advance_payment' => __('Advance Payment'),
            'scheduled_at' => __('Scheduled Date & Time'),
            'remarks' => __('Installation Notes & Routing Remarks'),
        ]);

        $taskNumber = 'INST-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        while (TenantInstallationTask::where('task_number', $taskNumber)->exists()) {
            $taskNumber = 'INST-' . date('y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        }

        $tech = $request->assigned_technician_id ? User::find($request->assigned_technician_id) : null;

        TenantInstallationTask::create([
            'tenant_id' => $tenantId,
            'task_number' => $taskNumber,
            'applicant_name' => $request->applicant_name,
            'applicant_phone' => $request->applicant_phone,
            'installation_address' => $request->installation_address,
            'zone_id' => $request->zone_id,
            'package_id' => $request->package_id,
            'assigned_technician_id' => $tech?->id,
            'assigned_technician_name' => $tech?->name,
            'installation_stage' => 'feasibility_check',
            'status' => $tech ? 'scheduled' : 'pending',
            'connection_fee' => $request->connection_fee ?? 1000.00,
            'advance_payment' => $request->advance_payment ?? 0.00,
            'scheduled_at' => $request->scheduled_at ? Carbon::parse($request->scheduled_at) : now()->addHours(3),
            'remarks' => $request->remarks,
            'created_by' => auth()->id() ?? 1,
        ]);

        return back()->with('success', __('New installation task :number has been created.', ['number' => $taskNumber]));
    }

    /**
     * Advance / Update Installation Pipeline Stage
     */
    public function updateStage(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $task = TenantInstallationTask::where('tenant_id', $tenant->id)->findOrFail($id);

        $stage = $request->input('installation_stage');
        $validStages = ['feasibility_check', 'cable_pulling', 'splicing_power_test', 'mikrotik_binding', 'active_completed', 'cancelled'];

        if (!in_array($stage, $validStages)) {
            return back()->with('error', __('Invalid installation pipeline stage.'));
        }

        $task->installation_stage = $stage;

        if ($request->filled('cable_length_meters')) {
            $task->cable_length_meters = (int)$request->input('cable_length_meters');
        }
        if ($request->filled('splitter_location')) {
            $task->splitter_location = $request->input('splitter_location');
        }
        if ($request->filled('onu_model')) {
            $task->onu_model = $request->input('onu_model');
        }
        if ($request->filled('onu_mac_serial')) {
            $task->onu_mac_serial = $request->input('onu_mac_serial');
        }
        if ($request->filled('optical_rx_power')) {
            $task->optical_rx_power = $request->input('optical_rx_power');
        }

        if ($stage === 'active_completed') {
            $task->status = 'completed';
            $task->activated_at = now();
        } elseif ($stage === 'cancelled') {
            $task->status = 'cancelled';
        } else {
            $task->status = 'in_progress';
        }

        $task->save();

        $stageLabel = $this->localizedStageName($task->installation_stage, $task->stage_name);
        $message = __('Installation :number progressed to :stage.', [
            'number' => $task->task_number,
            'stage' => $stageLabel,
        ]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Delete Installation Task (Super Admin / ISP Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $authUser = auth()->user();

        if (!$authUser || !$authUser->canDeleteTickets()) {
            $unauthorizedMessage = __('Unauthorized: Only Super Admin / ISP Admin can delete installation tasks.');

            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => $unauthorizedMessage,
                ], 403);
            }
            abort(403, $unauthorizedMessage);
        }

        $task = TenantInstallationTask::where('tenant_id', $tenant->id)->findOrFail($id);
        $taskNo = $task->task_number;
        $task->delete();

        $deletedMessage = __('Installation Task #:number deleted successfully.', ['number' => $taskNo]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => $deletedMessage,
            ]);
        }

        return back()->with('success', $deletedMessage);
    }

    /**
     * Standalone A4 Printable Customer Installation & Activation Slip
     */
    public function printActivationSlip(Request $request, $id): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $task = TenantInstallationTask::where('tenant_id', $tenant->id)
            ->with(['coverageZone', 'package', 'technician'])
            ->findOrFail($id);

        return view('tenant.tickets.installation_print', compact('tenant', 'currencySymbol', 'task'));
    }

    /**
     * Streamed CSV Export of Installation Pipeline
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $filename = 'new_installation_pipeline_' . date('Y_m_d_His') . '.csv';

        $tasks = TenantInstallationTask::where('tenant_id', $tenant->id)
            ->with(['coverageZone', 'package', 'technician'])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($tasks, $tenant, $currencySymbol) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header Block
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, [__('NEW BROADBAND CONNECTION INSTALLATION & ACTIVATION ROSTER')]);
            fputcsv($handle, [__('Generated At'), now()->translatedFormat('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Column Headers
            fputcsv($handle, [
                __('SL'),
                __('Task Number'),
                __('Subscriber Name'),
                __('Contact Phone'),
                __('Premise Address'),
                __('Package Plan'),
                __('Coverage Area'),
                __('Assigned Technician'),
                __('Installation Stage'),
                __('Cable Length (m)'),
                __('Splitter Location'),
                __('Optical RX Power'),
                __('Connection Fee (:currency)', ['currency' => $currencySymbol]),
                __('Advance Paid (:currency)', ['currency' => $currencySymbol]),
                __('Scheduled Date'),
                __('Activation Date'),
                __('Pipeline Status'),
            ]);

            foreach ($tasks as $idx => $t) {
                fputcsv($handle, [
                    $idx + 1,
                    $t->task_number,
                    $t->applicant_name,
                    $t->applicant_phone,
                    $t->installation_address,
                    $t->package?->name ?? __('Standard Plan'),
                    $t->coverageZone?->name ?? __('Primary Zone'),
                    $t->assigned_technician_name ?: __('Unassigned'),
                    $this->localizedStageName($t->installation_stage, $t->stage_name),
                    __(':length m', ['length' => $t->cable_length_meters]),
                    $t->splitter_location ?: __('N/A'),
                    $t->optical_rx_power ?: __('Pending'),
                    number_format($t->connection_fee, 2, '.', ''),
                    number_format($t->advance_payment, 2, '.', ''),
                    $t->scheduled_at ? $t->scheduled_at->translatedFormat('d-M-Y h:i A') : __('Immediate'),
                    $t->activated_at ? $t->activated_at->translatedFormat('d-M-Y h:i A') : __('Pending'),
                    mb_strtoupper($this->localizedStatusName($t->status), 'UTF-8'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
