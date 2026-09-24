<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCashHandoverController extends Controller
{
    /**
     * Resolve active tenant
     */
    protected function getTenant(): Tenant
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }

        return $tenant ?? abort(404, 'No active tenant found.');
    }

    /**
     * Display listing of Daily Cash Handover & Closing Vouchers
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));

        $allCollectors = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'email']);

        $query = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->with(['collector', 'verifier']);

        // 1. Collector Filter
        $collectorFilter = $request->input('collector_id', 'all');
        if (!empty($collectorFilter) && $collectorFilter !== 'all') {
            $query->where('collector_id', (int) $collectorFilter);
        }

        // 2. Status Filter
        $statusFilter = $request->input('status', 'all');
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // 3. Shift Type Filter
        $shiftFilter = $request->input('shift', 'all');
        if (!empty($shiftFilter) && $shiftFilter !== 'all') {
            $query->where('shift_type', $shiftFilter);
        }

        // 4. Period & Date Range Filter (Day-to-day resolution)
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!empty($period) && $period !== 'custom' && $period !== 'all') {
            switch ($period) {
                case 'today':
                    $dateFrom = Carbon::today()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'yesterday':
                    $dateFrom = Carbon::yesterday()->toDateString();
                    $dateTo = Carbon::yesterday()->toDateString();
                    break;
                case 'this_week':
                    $dateFrom = Carbon::now()->startOfWeek()->toDateString();
                    $dateTo = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    break;
            }
        }

        if ($dateFrom) {
            $query->whereDate('handover_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('handover_date', '<=', $dateTo);
        }

        // 5. Search Filter
        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('handover_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('manager_remarks', 'like', "%{$search}%")
                  ->orWhereHas('collector', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $handovers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // 6. Compute 6 KPI Stats (Scoped to active query scope)
        $kpiBaseQuery = clone $query;
        $allKpiHandovers = $kpiBaseQuery->get();
        $totalApprovedVault = (float) $allKpiHandovers->where('status', 'approved')->sum('handed_over_amount');
        $totalPendingCash = (float) $allKpiHandovers->where('status', 'pending')->sum('handed_over_amount');
        $totalVouchersCount = $allKpiHandovers->count();
        $totalShortageAmount = (float) $allKpiHandovers->sum('shortage_amount');

        $todayDate = Carbon::today()->toDateString();
        $todayClosingCash = (float) $allKpiHandovers->filter(function ($h) use ($todayDate) {
            return $h->handover_date && Carbon::parse($h->handover_date)->toDateString() === $todayDate;
        })->sum('handed_over_amount');

        $approvedCount = $allKpiHandovers->where('status', 'approved')->count();
        $approvalRate = $totalVouchersCount > 0 ? round(($approvedCount / $totalVouchersCount) * 100, 1) : 0.0;

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.finance.cash_handover', compact(
            'tenant',
            'handovers',
            'allCollectors',
            'collectorFilter',
            'statusFilter',
            'shiftFilter',
            'period',
            'dateFrom',
            'dateTo',
            'search',
            'perPage',
            'currencySymbol',
            'isResellerUser',
            'totalApprovedVault',
            'totalPendingCash',
            'totalShortageAmount',
            'totalVouchersCount',
            'todayClosingCash',
            'approvalRate'
        ));
    }

    /**
     * Get real-time unhanded cash summary for a collector on a specified date
     */
    public function getCollectorPendingCash(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $collectorId = (int) $request->input('collector_id', Auth::id());
        $date = $request->input('date', Carbon::today()->toDateString());

        $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('collected_by', $collectorId)
            ->whereDate('paid_at', $date)
            ->whereIn('status', ['completed', 'paid', 'approved'])
            ->with('customer:id,customer_id,name,username,phone')
            ->get();

        $cashPayments = $payments->where('payment_method', 'cash');
        $digitalPayments = $payments->where('payment_method', '!=', 'cash');

        $cashTotal = (float) $cashPayments->sum('amount');
        $digitalTotal = (float) $digitalPayments->sum('amount');
        $discountTotal = (float) $payments->sum('discount');
        $receiptsCount = $payments->count();

        // Check if a handover voucher already exists for this collector and date
        $existingHandover = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->where('collector_id', $collectorId)
            ->where('handover_date', $date)
            ->first();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return response()->json([
            'success' => true,
            'collector_id' => $collectorId,
            'date' => $date,
            'cash_total' => $cashTotal,
            'cash_total_formatted' => $currencySymbol . number_format($cashTotal, 2),
            'digital_total' => $digitalTotal,
            'digital_total_formatted' => $currencySymbol . number_format($digitalTotal, 2),
            'discount_total' => $discountTotal,
            'discount_total_formatted' => $currencySymbol . number_format($discountTotal, 2),
            'receipts_count' => $receiptsCount,
            'has_existing' => (bool) $existingHandover,
            'existing_status' => $existingHandover?->status,
            'existing_handover_no' => $existingHandover?->handover_no,
            'receipts' => $payments->map(function ($p) use ($currencySymbol) {
                return [
                    'id' => $p->id,
                    'invoice_no' => $p->invoice_no,
                    'customer_name' => $p->customer?->name ?? 'N/A',
                    'customer_username' => $p->customer?->username ?? '--',
                    'customer_code' => $p->customer?->customer_id ?? 'SO1000',
                    'amount' => (float) $p->amount,
                    'amount_formatted' => $currencySymbol . number_format($p->amount, 2),
                    'payment_method' => $p->payment_method,
                    'payment_method_name' => $p->payment_method_name,
                    'paid_time' => $p->paid_at ? $p->paid_at->format('h:i A') : '--',
                ];
            }),
        ]);
    }

    /**
     * Store / Submit a New Daily Cash Handover Voucher
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'collector_id' => 'required|exists:users,id',
            'handover_date' => 'required|date',
            'shift_type' => 'required|string|in:daily,morning,evening,night,full_day',
            'handed_over_amount' => 'required|numeric|min:0',
            'denominations' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        $collectorId = (int) $validated['collector_id'];
        $handoverDate = $validated['handover_date'];
        $shiftType = $validated['shift_type'];
        $handedOverAmount = (float) $validated['handed_over_amount'];

        // 1. Calculate actual system collections for this collector on this date
        $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('collected_by', $collectorId)
            ->whereDate('paid_at', $handoverDate)
            ->whereIn('status', ['completed', 'paid', 'approved'])
            ->get();

        $systemCash = (float) $payments->where('payment_method', 'cash')->sum('amount');
        $digitalTotal = (float) $payments->where('payment_method', '!=', 'cash')->sum('amount');
        $receiptsCount = $payments->count();

        // 2. Discrepancy Calculation
        $shortage = max(0, $systemCash - $handedOverAmount);
        $excess = max(0, $handedOverAmount - $systemCash);

        // 3. Generate Handover Number
        $handoverNo = TenantDailyCashHandover::generateHandoverNo($tenant->id, $handoverDate);

        // 4. Create Handover Record
        $handover = TenantDailyCashHandover::create([
            'tenant_id' => $tenant->id,
            'handover_no' => $handoverNo,
            'collector_id' => $collectorId,
            'handover_date' => $handoverDate,
            'shift_type' => $shiftType,
            'system_collected_amount' => $systemCash,
            'handed_over_amount' => $handedOverAmount,
            'shortage_amount' => $shortage,
            'excess_amount' => $excess,
            'digital_collected_amount' => $digitalTotal,
            'total_receipts_count' => $receiptsCount,
            'denominations' => $validated['denominations'] ?? null,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? "Daily cash handover for {$handoverDate}",
        ]);

        $collector = User::find($collectorId);

        // 5. Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CASH_HANDOVER_SUBMITTED',
            'description' => "Submitted cash handover voucher {$handoverNo} for collector '{$collector?->name}'. Handed: ৳" . number_format($handedOverAmount, 2) . " (System Cash: ৳" . number_format($systemCash, 2) . ").",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'handover_id' => $handover->id,
                'handover_no' => $handoverNo,
                'collector_id' => $collectorId,
                'handed_over_amount' => $handedOverAmount,
                'system_collected_amount' => $systemCash,
                'shortage_amount' => $shortage,
                'excess_amount' => $excess,
            ],
        ]);

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $msg = "Cash handover voucher {$handoverNo} ({$currencySymbol}" . number_format($handedOverAmount, 2) . ") submitted successfully and awaiting manager verification.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'handover_id' => $handover->id,
                'handover_no' => $handoverNo,
            ]);
        }

        return redirect()->route('tenant.finance.cash-handover')->with('success', $msg);
    }

    /**
     * Show / Fetch single Cash Handover Voucher for View / POS Print Modal
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->with(['collector', 'verifier'])
            ->findOrFail($id);

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        // Load constituent money receipts
        $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('collected_by', $handover->collector_id)
            ->whereDate('paid_at', $handover->handover_date->toDateString())
            ->whereIn('status', ['completed', 'paid', 'approved'])
            ->with('customer:id,customer_id,name,username,phone')
            ->get();

        $voucherData = [
            'id' => $handover->id,
            'handover_no' => $handover->handover_no,
            'handover_date' => $handover->handover_date->format('d M Y'),
            'shift_type' => $handover->shift_type,
            'shift_label' => $handover->shift_label,
            'system_collected_amount' => (float) $handover->system_collected_amount,
            'system_collected_formatted' => $currencySymbol . number_format($handover->system_collected_amount, 2),
            'handed_over_amount' => (float) $handover->handed_over_amount,
            'handed_over_formatted' => $currencySymbol . number_format($handover->handed_over_amount, 2),
            'shortage_amount' => (float) $handover->shortage_amount,
            'shortage_formatted' => $currencySymbol . number_format($handover->shortage_amount, 2),
            'excess_amount' => (float) $handover->excess_amount,
            'excess_formatted' => $currencySymbol . number_format($handover->excess_amount, 2),
            'digital_collected_amount' => (float) $handover->digital_collected_amount,
            'digital_collected_formatted' => $currencySymbol . number_format($handover->digital_collected_amount, 2),
            'total_receipts_count' => (int) $handover->total_receipts_count,
            'denominations' => $handover->denominations,
            'status' => $handover->status,
            'status_badge' => $handover->status_badge,
            'discrepancy_badge' => $handover->discrepancy_badge,
            'notes' => $handover->notes ?: 'Daily collection closing handover',
            'manager_remarks' => $handover->manager_remarks,
            'created_at' => $handover->created_at->format('d M Y, h:i A'),
            'verified_at' => $handover->verified_at ? $handover->verified_at->format('d M Y, h:i A') : null,
            'collector' => [
                'id' => $handover->collector?->id,
                'name' => $handover->collector?->name ?? 'Staff Collector',
                'email' => $handover->collector?->email ?? '--',
                'phone' => $handover->collector?->phone ?? '--',
            ],
            'verifier' => $handover->verifier ? [
                'id' => $handover->verifier->id,
                'name' => $handover->verifier->name,
                'email' => $handover->verifier->email,
            ] : null,
            'company' => [
                'name' => $tenant->company_name ?: ($tenant->name ?? 'ISP Management Portal'),
                'phone' => $tenant->phone ?: ($tenant->support_phone ?? '01700-000000'),
                'email' => $tenant->email ?: 'support@somitysoft.com',
                'address' => $tenant->address ?: 'Head Office, Internet Service Provider',
            ],
            'receipts' => $payments->map(function ($p) use ($currencySymbol) {
                return [
                    'id' => $p->id,
                    'invoice_no' => $p->invoice_no,
                    'customer_name' => $p->customer?->name ?? 'N/A',
                    'customer_username' => $p->customer?->username ?? '--',
                    'customer_code' => $p->customer?->customer_id ?? 'SO1000',
                    'amount' => (float) $p->amount,
                    'amount_formatted' => $currencySymbol . number_format($p->amount, 2),
                    'payment_method' => $p->payment_method,
                    'payment_method_name' => $p->payment_method_name,
                    'paid_time' => $p->paid_at ? $p->paid_at->format('h:i A') : '--',
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'handover' => $voucherData,
        ]);
    }

    /**
     * Verify and Approve Handover Voucher (Transfers to Vault)
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($handover->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'This cash handover voucher is already approved.',
            ], 422);
        }

        $remarks = $request->input('manager_remarks', 'Verified physical cash and approved into ISP vault.');

        $handover->status = 'approved';
        $handover->verified_by = Auth::id() ?: 1;
        $handover->verified_at = Carbon::now();
        $handover->manager_remarks = $remarks;
        $handover->save();

        // Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CASH_HANDOVER_APPROVED',
            'description' => "Approved cash handover voucher {$handover->handover_no} (৳" . number_format($handover->handed_over_amount, 2) . ") for collector '{$handover->collector?->name}'.",
            'ip_address' => $request->ip(),
        ]);

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $msg = "Cash handover voucher {$handover->handover_no} ({$currencySymbol}" . number_format($handover->handed_over_amount, 2) . ") has been approved successfully and transferred into the vault.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.finance.cash-handover')->with('success', $msg);
    }

    /**
     * Reject Handover Voucher
     */
    public function reject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        $remarks = $request->input('manager_remarks', 'Cash discrepancy not resolved / rejected by accounts manager.');

        $handover->status = 'rejected';
        $handover->verified_by = Auth::id() ?: 1;
        $handover->verified_at = Carbon::now();
        $handover->manager_remarks = $remarks;
        $handover->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'CASH_HANDOVER_REJECTED',
            'description' => "Rejected cash handover voucher {$handover->handover_no}. Reason: {$remarks}",
            'ip_address' => $request->ip(),
        ]);

        $msg = "Cash handover voucher {$handover->handover_no} has been rejected.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.finance.cash-handover')->with('success', $msg);
    }

    /**
     * Stream CSV Export of Cash Handovers
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $handovers = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->with(['collector', 'verifier'])
            ->orderByDesc('id')
            ->get();

        $filename = 'cash_handovers_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($handovers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Handover No',
                'Date',
                'Collector Name',
                'Collector Email',
                'Shift',
                'System Cash (BDT)',
                'Handed Cash (BDT)',
                'Shortage (BDT)',
                'Excess (BDT)',
                'Digital Amount (BDT)',
                'Receipts Count',
                'Status',
                'Verified By',
                'Verified At',
                'Collector Notes',
                'Manager Remarks',
            ]);

            foreach ($handovers as $h) {
                fputcsv($handle, [
                    $h->handover_no,
                    $h->handover_date->format('Y-m-d'),
                    $h->collector?->name ?? 'N/A',
                    $h->collector?->email ?? 'N/A',
                    $h->shift_label,
                    number_format($h->system_collected_amount, 2, '.', ''),
                    number_format($h->handed_over_amount, 2, '.', ''),
                    number_format($h->shortage_amount, 2, '.', ''),
                    number_format($h->excess_amount, 2, '.', ''),
                    number_format($h->digital_collected_amount, 2, '.', ''),
                    $h->total_receipts_count,
                    $h->status,
                    $h->verifier?->name ?? '--',
                    $h->verified_at ? $h->verified_at->format('Y-m-d H:i:s') : '--',
                    $h->notes,
                    $h->manager_remarks,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
