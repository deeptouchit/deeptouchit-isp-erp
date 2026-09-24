<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantStaffController extends Controller
{
    /**
     * Resolve active tenant safely.
     */
    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = \App\Models\Tenant::first();
        }
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Display Unified Employees Directory (ISP Core + Sub-ISP Staff).
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        $search = $request->input('search');
        $scopeFilter = $request->input('scope'); // 'all', 'isp', 'reseller'
        $selectedResellerId = $request->input('reseller_id');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');

        // 1. Fetch All Resellers for dropdowns
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Staff Users
        $query = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->with(['reseller']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($scopeFilter === 'isp') {
            $query->whereNull('reseller_id');
        } elseif ($scopeFilter === 'reseller') {
            $query->whereNotNull('reseller_id');
        }

        if ($selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $employees = $query->latest('id')->paginate($perPage)->withQueryString();

        // 3. Compute 6-Card Summary Metric KPIs
        $baseStaffQuery = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner');
        $totalEmployees = (clone $baseStaffQuery)->count();
        $managersCount = (clone $baseStaffQuery)->whereIn('role', ['isp_manager', 'isp_admin', 'manager', 'admin'])->count();
        $techniciansCount = (clone $baseStaffQuery)->whereIn('role', ['isp_technician', 'technician', 'reseller_technician'])->count();
        $collectorsCount = (clone $baseStaffQuery)->whereIn('role', ['isp_collector', 'collector', 'reseller_collector'])->count();
        $ispStaffCount = (clone $baseStaffQuery)->whereNull('reseller_id')->count();
        $activeStaffCount = (clone $baseStaffQuery)->where('status', 'active')->count();

        // Available dynamic RBAC roles for assignment
        $availableRoles = \App\Models\TenantRole::where('tenant_id', $tenant->id)->where('status', 'active')->orderBy('is_system', 'desc')->get();

        return view('tenant.staff.index', compact(
            'tenant',
            'employees',
            'allResellers',
            'availableRoles',
            'totalEmployees',
            'managersCount',
            'techniciansCount',
            'collectorsCount',
            'ispStaffCount',
            'activeStaffCount',
            'search',
            'scopeFilter',
            'selectedResellerId',
            'roleFilter',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Dedicated ISP Operations Staff View (Managers, NOC Engineers, Field Technicians, Bill Collectors).
     */
    public function ispStaff(Request $request): View
    {
        $tenant = $this->getTenant();
        $search = $request->input('search');
        $department = $request->input('department'); // 'all', 'management', 'noc', 'field', 'billing'
        $roleFilter = $request->input('role');
        $shiftFilter = $request->input('shift');
        $statusFilter = $request->input('status');

        $query = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->whereNull('reseller_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($department === 'management') {
            $query->whereIn('role', ['isp_admin', 'isp_manager', 'admin', 'manager']);
        } elseif ($department === 'noc') {
            $query->where('role', 'isp_technician')->where('designation', 'like', '%NOC%');
        } elseif ($department === 'field') {
            $query->where('role', 'isp_technician')->where('designation', 'not like', '%NOC%');
        } elseif ($department === 'billing') {
            $query->whereIn('role', ['isp_collector', 'collector']);
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($shiftFilter) {
            $query->where('shift', $shiftFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $employees = $query->latest('id')->paginate($perPage)->withQueryString();

        // 6 KPIs for ISP Operations
        $baseQuery = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->whereNull('reseller_id');
        $totalIspStaff = (clone $baseQuery)->count();
        $managersCount = (clone $baseQuery)->whereIn('role', ['isp_admin', 'isp_manager', 'admin', 'manager'])->count();
        $nocCount = (clone $baseQuery)->where('role', 'isp_technician')->where('designation', 'like', '%NOC%')->count();
        $fieldTechCount = (clone $baseQuery)->where('role', 'isp_technician')->count();
        $collectorsCount = (clone $baseQuery)->whereIn('role', ['isp_collector', 'collector'])->count();
        $activeStaffCount = (clone $baseQuery)->where('status', 'active')->count();

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $availableRoles = \App\Models\TenantRole::where('tenant_id', $tenant->id)->where('status', 'active')->orderBy('is_system', 'desc')->get();

        return view('tenant.staff.isp', compact(
            'tenant',
            'employees',
            'allResellers',
            'availableRoles',
            'totalIspStaff',
            'managersCount',
            'nocCount',
            'fieldTechCount',
            'collectorsCount',
            'activeStaffCount',
            'search',
            'department',
            'roleFilter',
            'shiftFilter',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Dedicated Reseller / Sub-ISP Staff Roster View.
     */
    public function resellerRoster(Request $request): View
    {
        $tenant = $this->getTenant();
        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $roleFilter = $request->input('role');
        $shiftFilter = $request->input('shift');
        $statusFilter = $request->input('status');

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $query = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->whereNotNull('reseller_id')
            ->with(['reseller']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($shiftFilter) {
            $query->where('shift', $shiftFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $employees = $query->latest('id')->paginate($perPage)->withQueryString();

        // 6 KPIs for Sub-ISP Roster
        $baseQuery = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->whereNotNull('reseller_id');
        $totalResellerStaff = (clone $baseQuery)->count();
        $partnersWithStaffCount = (clone $baseQuery)->distinct('reseller_id')->count('reseller_id');
        $techniciansCount = (clone $baseQuery)->whereIn('role', ['reseller_technician', 'technician'])->count();
        $collectorsCount = (clone $baseQuery)->whereIn('role', ['reseller_collector', 'collector'])->count();
        $managersCount = (clone $baseQuery)->whereIn('role', ['reseller_manager', 'reseller_admin'])->count();
        $activeStaffCount = (clone $baseQuery)->where('status', 'active')->count();

        return view('tenant.staff.resellers', compact(
            'tenant',
            'employees',
            'allResellers',
            'totalResellerStaff',
            'partnersWithStaffCount',
            'techniciansCount',
            'collectorsCount',
            'managersCount',
            'activeStaffCount',
            'search',
            'selectedResellerId',
            'roleFilter',
            'shiftFilter',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Staff Daily Attendance Register.
     */
    public function attendance(Request $request): View
    {
        $tenant = $this->getTenant();
        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $search = $request->input('search');
        $scopeFilter = $request->input('scope'); // 'all', 'isp', 'reseller'
        $selectedResellerId = $request->input('reseller_id');
        $statusFilter = $request->input('status');
        $shiftFilter = $request->input('shift');

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allStaff = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->orderBy('name')->get();

        // Attendance Query
        $attQuery = \App\Models\TenantStaffAttendance::where('tenant_id', $tenant->id)
            ->whereDate('date', $selectedDate)
            ->with(['user.reseller', 'marker']);

        if ($search) {
            $attQuery->whereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%")
                   ->orWhere('phone', 'like', "%{$search}%")
                   ->orWhere('mobile', 'like', "%{$search}%")
                   ->orWhere('staff_id', 'like', "%{$search}%")
                   ->orWhere('designation', 'like', "%{$search}%")
                   ->orWhereHas('reseller', function ($rq) use ($search) {
                       $rq->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                   });
            });
        }

        if ($scopeFilter === 'isp') {
            $attQuery->whereNull('reseller_id');
        } elseif ($scopeFilter === 'reseller') {
            $attQuery->whereNotNull('reseller_id');
        }

        if ($selectedResellerId) {
            $attQuery->where('reseller_id', $selectedResellerId);
        }

        if ($statusFilter) {
            $attQuery->where('status', $statusFilter);
        }

        if ($shiftFilter) {
            $attQuery->where('shift', $shiftFilter);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $attendances = $attQuery->orderByDesc('punch_in_at')->paginate($perPage)->withQueryString();

        // 6 KPI Cards for Daily Attendance
        $totalActiveStaff = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->where('status', 'active')->count();
        $todayAttQuery = \App\Models\TenantStaffAttendance::where('tenant_id', $tenant->id)->whereDate('date', Carbon::today()->toDateString());
        $presentTodayCount = (clone $todayAttQuery)->whereIn('status', ['present', 'late', 'field_duty', 'half_day'])->count();
        $fieldDutyCount = (clone $todayAttQuery)->where('status', 'field_duty')->count();
        $lateArrivalsCount = (clone $todayAttQuery)->where('status', 'late')->count();
        $halfDayCount = (clone $todayAttQuery)->where('status', 'half_day')->count();
        $absentLeaveCount = (clone $todayAttQuery)->whereIn('status', ['absent', 'on_leave'])->count();

        return view('tenant.staff.attendance', compact(
            'tenant',
            'selectedDate',
            'search',
            'scopeFilter',
            'selectedResellerId',
            'statusFilter',
            'shiftFilter',
            'allResellers',
            'allStaff',
            'attendances',
            'totalActiveStaff',
            'presentTodayCount',
            'fieldDutyCount',
            'lateArrivalsCount',
            'halfDayCount',
            'absentLeaveCount',
            'perPage'
        ));
    }

    /**
     * Staff Activity Audit Trail & Telemetry Stream.
     */
    public function activityLogs(Request $request): View
    {
        $tenant = $this->getTenant();
        $selectedDate = $request->input('date');
        $search = $request->input('search');
        $actorFilter = $request->input('actor_id');
        $eventTypeFilter = $request->input('event_type');

        $allStaff = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->orderBy('name')->get();

        // Activity Logs Query
        $logQuery = \App\Models\TenantActivityLog::where('tenant_id', $tenant->id);

        if ($selectedDate) {
            $logQuery->whereDate('created_at', $selectedDate);
        }

        if ($search) {
            $logQuery->where(function ($lq) use ($search) {
                $lq->where('actor_name', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%")
                   ->orWhere('ip_address', 'like', "%{$search}%")
                   ->orWhere('event_type', 'like', "%{$search}%");
            });
        }

        if ($actorFilter) {
            $logQuery->where('actor_id', $actorFilter);
        }

        if ($eventTypeFilter) {
            $logQuery->where('event_type', $eventTypeFilter);
        }

        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [15, 20, 30, 50, 100])) {
            $perPage = 20;
        }

        $activityLogs = $logQuery->latest()->paginate($perPage)->withQueryString();

        // Distinct event types for dropdown
        $distinctEvents = \App\Models\TenantActivityLog::where('tenant_id', $tenant->id)
            ->distinct('event_type')
            ->pluck('event_type')
            ->filter()
            ->values();

        // 6 KPI Metric Cards for Activity Audit Trail
        $baseLogQuery = \App\Models\TenantActivityLog::where('tenant_id', $tenant->id);
        $todayLogsCount = (clone $baseLogQuery)->whereDate('created_at', Carbon::today()->toDateString())->count();
        $authEventsCount = (clone $baseLogQuery)->where('event_type', 'like', '%AUTH%')->count();
        $networkEventsCount = (clone $baseLogQuery)->where(function($q) {
            $q->where('event_type', 'like', '%ONU%')->orWhere('event_type', 'like', '%NETWORK%')->orWhere('event_type', 'like', '%ROUTER%');
        })->count();
        $billingEventsCount = (clone $baseLogQuery)->where(function($q) {
            $q->where('event_type', 'like', '%BILL%')->orWhere('event_type', 'like', '%RECHARGE%')->orWhere('event_type', 'like', '%WALLET%');
        })->count();
        $supportEventsCount = (clone $baseLogQuery)->where(function($q) {
            $q->where('event_type', 'like', '%TICKET%')->orWhere('event_type', 'like', '%SUPPORT%');
        })->count();
        $attendanceEventsCount = (clone $baseLogQuery)->where(function($q) {
            $q->where('event_type', 'like', '%ATTENDANCE%')->orWhere('event_type', 'like', '%PUNCH%');
        })->count();

        return view('tenant.staff.logs', compact(
            'tenant',
            'selectedDate',
            'search',
            'actorFilter',
            'eventTypeFilter',
            'allStaff',
            'activityLogs',
            'distinctEvents',
            'todayLogsCount',
            'authEventsCount',
            'networkEventsCount',
            'billingEventsCount',
            'supportEventsCount',
            'attendanceEventsCount',
            'perPage'
        ));
    }

    /**
     * Mark or Update Attendance Record manually by Admin/Manager.
     */
    public function markAttendance(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,late,field_duty,on_leave,half_day,absent',
            'punch_in_time' => 'nullable|date_format:H:i',
            'punch_out_time' => 'nullable|date_format:H:i',
            'late_reason' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ]);

        $user = User::where('tenant_id', $tenant->id)->findOrFail($validated['user_id']);
        $dateStr = Carbon::parse($validated['date'])->toDateString();

        $punchIn = !empty($validated['punch_in_time']) ? Carbon::parse($dateStr . ' ' . $validated['punch_in_time']) : null;
        $punchOut = !empty($validated['punch_out_time']) ? Carbon::parse($dateStr . ' ' . $validated['punch_out_time']) : null;

        $duration = 0;
        if ($punchIn && $punchOut) {
            $duration = $punchIn->diffInMinutes($punchOut);
        }

        $attendance = \App\Models\TenantStaffAttendance::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'date' => $dateStr,
            ],
            [
                'reseller_id' => $user->reseller_id,
                'shift' => $user->shift ?: 'Morning Shift (09:00 - 18:00)',
                'punch_in_at' => $punchIn,
                'punch_out_at' => $punchOut,
                'status' => $validated['status'],
                'work_duration_minutes' => $duration,
                'punch_in_ip' => $request->ip(),
                'late_reason' => $validated['late_reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'marked_by' => Auth::id(),
            ]
        );

        // Audit Trail Log
        \App\Models\TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: $user->id,
            'actor_name' => Auth::user()?->name ?: 'System Admin',
            'event_type' => 'ATTENDANCE_MARK',
            'description' => "Marked attendance for '{$user->name}' as " . ucfirst(str_replace('_', ' ', $validated['status'])) . " on {$dateStr}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'target_user_id' => $user->id,
                'status' => $validated['status'],
                'date' => $dateStr,
            ],
        ]);

        $msg = "Attendance for '{$user->name}' on {$dateStr} saved successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'attendance' => $attendance->load('user'),
            ]);
        }

        return redirect()->route('tenant.staff.attendance', ['date' => $dateStr])->with('success', $msg);
    }

    /**
     * Quick Punch In / Out (for self or rapid duty change).
     */
    public function quickPunch(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $userId = $request->input('user_id', Auth::id());
        $user = User::where('tenant_id', $tenant->id)->findOrFail($userId);

        $today = Carbon::today()->toDateString();
        $attendance = \App\Models\TenantStaffAttendance::firstOrNew([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'date' => $today,
        ]);

        $action = 'in';

        if (!$attendance->exists || !$attendance->punch_in_at) {
            // Punch IN
            $attendance->reseller_id = $user->reseller_id;
            $attendance->shift = $user->shift ?: 'Morning Shift (09:00 - 18:00)';
            $attendance->punch_in_at = now();
            $attendance->status = 'present';
            $attendance->punch_in_ip = $request->ip();
            $attendance->punch_in_device = substr($request->userAgent() ?? 'Web Browser', 0, 190);
            $attendance->save();
            $action = 'in';
            $msg = "Punch-IN recorded for {$user->name} at " . now()->format('h:i A') . "!";
        } else {
            // Punch OUT
            $attendance->punch_out_at = now();
            if ($attendance->punch_in_at) {
                $attendance->work_duration_minutes = $attendance->punch_in_at->diffInMinutes(now());
            }
            $attendance->punch_out_ip = $request->ip();
            $attendance->save();
            $action = 'out';
            $msg = "Punch-OUT recorded for {$user->name} at " . now()->format('h:i A') . "! Total: " . $attendance->duration_formatted;
        }

        // Audit Trail Log
        \App\Models\TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'event_type' => ($action === 'in' ? 'STAFF_PUNCH_IN' : 'STAFF_PUNCH_OUT'),
            'description' => $msg,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'action' => $action,
                'time' => now()->toIso8601String(),
                'duration' => $attendance->duration_formatted,
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => $msg,
                'attendance' => $attendance,
            ]);
        }

        return redirect()->route('tenant.staff.attendance')->with('success', $msg);
    }

    /**
     * Export Attendance Records to CSV.
     */
    public function exportAttendanceCsv(Request $request)
    {
        $tenant = $this->getTenant();
        $date = $request->input('date', Carbon::today()->toDateString());

        $attendances = \App\Models\TenantStaffAttendance::where('tenant_id', $tenant->id)
            ->whereDate('date', $date)
            ->with(['user.reseller', 'marker'])
            ->get();

        $fileName = "attendance_report_{$tenant->company_name}_{$date}.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Staff ID', 'Employee Name', 'Role', 'Affiliation / Sub-ISP', 'Date', 'Shift', 'Punch In', 'Punch Out', 'Duration', 'Status', 'IP Address', 'Remarks'];

        $callback = function () use ($attendances, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, $columns);

            foreach ($attendances as $att) {
                fputcsv($file, [
                    $att->user?->staff_id ?? ('STF-' . str_pad($att->user_id, 4, '0', STR_PAD_LEFT)),
                    $att->user?->name ?? 'N/A',
                    $att->user?->role_badge['label'] ?? ucfirst($att->user?->role ?? ''),
                    $att->reseller ? $att->reseller->name . ' (' . $att->reseller->code . ')' : 'ISP Headquarters (Core)',
                    $att->date?->format('Y-m-d') ?? '',
                    $att->shift,
                    $att->punch_in_at ? $att->punch_in_at->format('h:i A') : '--',
                    $att->punch_out_at ? $att->punch_out_at->format('h:i A') : '--',
                    $att->duration_formatted,
                    ucfirst(str_replace('_', ' ', $att->status)),
                    $att->punch_in_ip ?? '--',
                    $att->remarks ?? $att->late_reason ?? '--',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Staff Target & Commission Logs.
     */
    public function commissions(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Store a newly onboarded employee/staff user.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id),
            ],
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'role' => 'required|string|max:100',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'designation' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer',
            'shift' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        // If reseller role, ensure reseller_id is present
        if (str_starts_with($validated['role'], 'reseller_') && empty($validated['reseller_id'])) {
            return back()->withErrors(['reseller_id' => 'Please select a Sub-ISP Partner for reseller staff role.'])->withInput();
        }

        $staffId = User::generateNextStaffId($tenant->id);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $validated['reseller_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $validated['mobile'] ?? null,
            'mobile' => $validated['mobile'] ?? $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_staff' => true,
            'staff_id' => $staffId,
            'designation' => $validated['designation'] ?: ucfirst(str_replace('_', ' ', $validated['role'])),
            'department_id' => $validated['department_id'] ?? null,
            'shift' => $validated['shift'] ?? 'Morning Shift (09:00 - 18:00)',
            'address' => $validated['address'] ?? null,
            'status' => 'active',
            'password' => Hash::make($validated['password']),
        ]);

        $msg = "Employee '{$user->name}' ({$user->role_badge['label']}) onboarded successfully with ID {$staffId}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'employee' => $user->load('reseller'),
            ]);
        }

        return redirect()->route('tenant.staff.index')->with('success', $msg);
    }

    /**
     * Get Employee details for JSON view/modal or Web Profile page.
     */
    public function show(Request $request, int $id): JsonResponse|View
    {
        $tenant = $this->getTenant();

        $user = User::where('tenant_id', $tenant->id)
            ->with(['reseller'])
            ->findOrFail($id);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $user->id,
                    'staff_id' => $user->staff_id ?? ('STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'role_badge' => $user->role_badge,
                    'scope_badge' => $user->scope_badge,
                    'status' => $user->status,
                    'status_badge' => $user->status_badge,
                    'reseller_id' => $user->reseller_id,
                    'reseller_name' => $user->reseller?->name,
                    'reseller_code' => $user->reseller?->code,
                    'designation' => $user->designation,
                    'shift' => $user->shift,
                    'address' => $user->address,
                    'created_at' => $user->created_at->format('d M Y, h:i A'),
                ]
            ]);
        }

        $employee = $user;
        $recentAttendances = \App\Models\TenantStaffAttendance::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->latest('date')
            ->take(7)
            ->get();

        $stats = [
            'staff_id' => $user->staff_id ?? ('STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT)),
            'role' => $user->role_badge['label'] ?? ucfirst($user->role),
            'scope' => $user->reseller_id ? ($user->reseller?->name ?? 'Reseller Partner') : 'ISP Core HQ',
            'shift' => $user->shift ?: 'Regular Shift',
            'status' => ucfirst($user->status ?? 'active'),
            'joined_at' => $user->created_at ? $user->created_at->format('d M Y') : 'N/A',
        ];

        return view('tenant.staff.show', compact('tenant', 'employee', 'stats', 'recentAttendances'));
    }

    /**
     * Update employee record.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id)->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'role' => 'required|string|max:100',
            'reseller_id' => 'nullable|exists:tenant_resellers,id',
            'designation' => 'nullable|string|max:100',
            'shift' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $validated['mobile'] ?? $user->phone;
        $user->mobile = $validated['mobile'] ?? $validated['phone'] ?? $user->mobile;
        $user->role = $validated['role'];
        $user->reseller_id = $validated['reseller_id'] ?? null;
        $user->designation = $validated['designation'] ?: ucfirst(str_replace('_', ' ', $validated['role']));
        $user->shift = $validated['shift'] ?? $user->shift;
        $user->address = $validated['address'] ?? $user->address;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $msg = "Employee '{$user->name}' updated successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'employee' => $user->load('reseller'),
            ]);
        }

        return redirect()->route('tenant.staff.index')->with('success', $msg);
    }

    /**
     * Toggle status (active / suspended).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();

        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You cannot suspend your own logged-in account.'], 422);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Employee '{$user->name}' status updated to " . ($newStatus === 'active' ? 'Active' : 'Suspended') . "!",
            'status' => $newStatus,
            'badge' => $user->status_badge,
        ]);
    }

    /**
     * Reset employee password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);
        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'success' => true,
            'message' => "Password for '{$user->name}' reset successfully!",
        ]);
    }

    /**
     * Delete employee account.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($user->id === Auth::id()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own logged-in account.'], 422);
            }
            return back()->with('error', 'You cannot delete your own logged-in account.');
        }

        $name = $user->name;
        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Employee '{$name}' deleted successfully!",
            ]);
        }

        return redirect()->route('tenant.staff.index')->with('success', "Employee '{$name}' deleted successfully!");
    }
}
