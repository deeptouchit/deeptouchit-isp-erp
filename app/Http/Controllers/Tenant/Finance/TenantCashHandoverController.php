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
use Illuminate\Support\Facades\Schema;
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

        return $tenant ?? abort(404, __('No active tenant found.'));
    }

    /**
     * Display listing of Daily Cash Handover & Closing Vouchers
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));

        $allCollectors = User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'email', 'role']);

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

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        // Core Accounting Query: All un-handed customer payments for this collector
        $query = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('collected_by', $collectorId)
            ->when($hasHandoverCol, fn($q) => $q->whereNull('handover_id'))
            ->whereNotIn('status', ['void', 'refunded', 'cancelled']);

        if (!empty($date)) {
            $query->whereDate('paid_at', '<=', $date);
        }

        $payments = $query->with('customer:id,customer_id,name,username,phone')->get();

        $cashPayments = $payments->where('payment_method', 'cash');
        $digitalPayments = $payments->where('payment_method', '!=', 'cash');

        $cashTotal = (float) $cashPayments->sum('amount');
        $digitalTotal = (float) $digitalPayments->sum('amount');
        $discountTotal = (float) $payments->sum('discount');
        $receiptsCount = $cashPayments->count();

        // Check if a pending handover voucher already exists for this collector
        $pendingHandover = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->where('collector_id', $collectorId)
            ->where('status', 'pending')
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
            'has_existing' => (bool) $pendingHandover,
            'existing_status' => $pendingHandover?->status,
            'existing_handover_no' => $pendingHandover?->handover_no,
            'receipts' => $cashPayments->map(function ($p) use ($currencySymbol) {
                return [
                    'id' => $p->id,
                    'invoice_no' => $p->invoice_no,
                    'customer_name' => $p->customer?->name ?? __('N/A'),
                    'customer_username' => $p->customer?->username ?? '--',
                    'customer_code' => $p->customer?->customer_id ?? 'SO1000',
                    'amount' => (float) $p->amount,
                    'amount_formatted' => $currencySymbol . number_format($p->amount, 2),
                    'payment_method' => $p->payment_method,
                    'payment_method_name' => $p->payment_method_name,
                    'paid_time' => $p->paid_at ? $p->paid_at->translatedFormat('h:i A') : '--',
                ];
            })->values(),
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

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        DB::beginTransaction();
        try {
            // 1. Calculate actual unhanded system collections for this collector up to handover_date
            $paymentsQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('collected_by', $collectorId)
                ->when($hasHandoverCol, fn($q) => $q->whereNull('handover_id'))
                ->whereNotIn('status', ['void', 'refunded', 'cancelled']);

            if (!empty($handoverDate)) {
                $paymentsQuery->whereDate('paid_at', '<=', $handoverDate);
            }

            $allPayments = $paymentsQuery->get();
            $cashPayments = $allPayments->where('payment_method', 'cash');
            $digitalPayments = $allPayments->where('payment_method', '!=', 'cash');

            $systemCash = (float) $cashPayments->sum('amount');
            $digitalTotal = (float) $digitalPayments->sum('amount');
            $receiptsCount = $cashPayments->count();

            // 2. Discrepancy Calculation
            $shortage = max(0, $systemCash - $handedOverAmount);
            $excess = max(0, $handedOverAmount - $systemCash);

            // 3. Admin / Owner direct cash collection auto-vaulting check
            $collector = User::find($collectorId);
            $isCollectorAdmin = $collector && ($collector->isOwner() || $collector->isIspAdmin());
            $status = $isCollectorAdmin ? 'approved' : 'pending';

            // 4. Generate Handover Number
            $handoverNo = TenantDailyCashHandover::generateHandoverNo($tenant->id, $handoverDate);
            $currencySymbol = $tenant->currency_symbol ?? '৳';
            $formattedHanded = $currencySymbol . number_format($handedOverAmount, 2);
            $formattedSystem = $currencySymbol . number_format($systemCash, 2);
            $collectorName = $collector?->name ?? __('Staff Collector');

            // 5. Create Handover Record
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
                'status' => $status,
                'verified_by' => $isCollectorAdmin ? (Auth::id() ?: 1) : null,
                'verified_at' => $isCollectorAdmin ? Carbon::now() : null,
                'manager_remarks' => $isCollectorAdmin ? __('Auto-approved Admin direct office vault collection') : null,
                'notes' => $validated['notes'] ?? ($isCollectorAdmin
                    ? __('Admin direct office vault cash closing for :date', ['date' => $handoverDate])
                    : __('Daily cash handover for :date', ['date' => $handoverDate])),
            ]);

            // 6. Lock constituent cash payments to prevent double-counting or re-submission
            if ($hasHandoverCol && $cashPayments->isNotEmpty()) {
                TenantCustomerPayment::whereIn('id', $cashPayments->pluck('id'))
                    ->update(['handover_id' => $handover->id]);
            }

            // 7. Audit Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: __('Admin'),
                'event_type' => $isCollectorAdmin ? 'CASH_HANDOVER_APPROVED' : 'CASH_HANDOVER_SUBMITTED',
                'description' => $isCollectorAdmin
                    ? __("Admin ':name' closed and auto-approved cash handover :no (:amount) directly into vault. Locked :count receipt(s).", [
                        'name' => $collectorName,
                        'no' => $handoverNo,
                        'amount' => $formattedHanded,
                        'count' => $receiptsCount,
                    ])
                    : __("Submitted cash handover voucher :no for collector ':name'. Handed: :handed (System Cash: :system). Locked :count receipt(s).", [
                        'no' => $handoverNo,
                        'name' => $collectorName,
                        'handed' => $formattedHanded,
                        'system' => $formattedSystem,
                        'count' => $receiptsCount,
                    ]),
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
                    'status' => $status,
                    'locked_receipts_count' => $receiptsCount,
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $formattedHanded = $currencySymbol . number_format($handedOverAmount, 2);
        $msg = $isCollectorAdmin
            ? __('Admin cash handover voucher :no (:amount) has been approved and transferred directly into the vault.', [
                'no' => $handoverNo,
                'amount' => $formattedHanded,
            ])
            : __('Cash handover voucher :no (:amount) submitted successfully and awaiting manager verification.', [
                'no' => $handoverNo,
                'amount' => $formattedHanded,
            ]);

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

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        // Load constituent money receipts tagged with this handover
        $payments = collect();
        if ($hasHandoverCol) {
            $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('handover_id', $handover->id)
                ->with('customer:id,customer_id,name,username,phone')
                ->get();
        }

        // Fallback for older legacy handover records created before handover_id was populated
        if ($payments->isEmpty()) {
            $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('collected_by', $handover->collector_id)
                ->whereDate('paid_at', $handover->handover_date->toDateString())
                ->where('payment_method', 'cash')
                ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                ->with('customer:id,customer_id,name,username,phone')
                ->get();
        }

        $voucherData = [
            'id' => $handover->id,
            'handover_no' => $handover->handover_no,
            'handover_date' => $handover->handover_date->translatedFormat('d M Y'),
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
            'notes' => $handover->notes ?: __('Daily collection closing handover'),
            'manager_remarks' => $handover->manager_remarks,
            'created_at' => $handover->created_at->translatedFormat('d M Y, h:i A'),
            'verified_at' => $handover->verified_at ? $handover->verified_at->translatedFormat('d M Y, h:i A') : null,
            'collector' => [
                'id' => $handover->collector?->id,
                'name' => $handover->collector?->name ?? __('Staff Collector'),
                'email' => $handover->collector?->email ?? '--',
                'phone' => $handover->collector?->phone ?? '--',
            ],
            'verifier' => $handover->verifier ? [
                'id' => $handover->verifier->id,
                'name' => $handover->verifier->name,
                'email' => $handover->verifier->email,
            ] : null,
            'company' => [
                'name' => $tenant->company_name ?: ($tenant->name ?? __('ISP Management Portal')),
                'phone' => $tenant->phone ?: ($tenant->support_phone ?? '01700-000000'),
                'email' => $tenant->email ?: 'support@somitysoft.com',
                'address' => $tenant->address ?: __('Head Office, Internet Service Provider'),
            ],
            'receipts' => $payments->map(function ($p) use ($currencySymbol) {
                return [
                    'id' => $p->id,
                    'invoice_no' => $p->invoice_no,
                    'customer_name' => $p->customer?->name ?? __('N/A'),
                    'customer_username' => $p->customer?->username ?? '--',
                    'customer_code' => $p->customer?->customer_id ?? 'SO1000',
                    'amount' => (float) $p->amount,
                    'amount_formatted' => $currencySymbol . number_format($p->amount, 2),
                    'payment_method' => $p->payment_method,
                    'payment_method_name' => $p->payment_method_name,
                    'paid_time' => $p->paid_at ? $p->paid_at->translatedFormat('h:i A') : '--',
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'handover' => $voucherData,
        ]);
    }

    /**
     * Verify and Approve Handover Voucher (Transfers to Vault & locks payments)
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($handover->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('This cash handover voucher is already approved.'),
            ], 422);
        }

        $remarks = $request->input('manager_remarks', __('Verified physical cash and approved into ISP vault.'));
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $formattedAmount = $currencySymbol . number_format($handover->handed_over_amount, 2);

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        DB::beginTransaction();
        try {
            $handover->status = 'approved';
            $handover->verified_by = Auth::id() ?: 1;
            $handover->verified_at = Carbon::now();
            $handover->manager_remarks = $remarks;
            $handover->save();

            // For legacy records created before handover_id was set, link matching cash payments
            if ($hasHandoverCol && $handover->payments()->count() === 0) {
                TenantCustomerPayment::where('tenant_id', $tenant->id)
                    ->where('collected_by', $handover->collector_id)
                    ->whereDate('paid_at', $handover->handover_date->toDateString())
                    ->where('payment_method', 'cash')
                    ->whereNull('handover_id')
                    ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                    ->update(['handover_id' => $handover->id]);
            }

            // Audit Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: __('Admin'),
                'event_type' => 'CASH_HANDOVER_APPROVED',
                'description' => __("Approved cash handover voucher :no (:amount) for collector ':name'.", [
                    'no' => $handover->handover_no,
                    'amount' => $formattedAmount,
                    'name' => $handover->collector?->name ?? __('Staff Collector'),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'handover_id' => $handover->id,
                    'handover_no' => $handover->handover_no,
                    'collector_id' => $handover->collector_id,
                    'handed_over_amount' => (float) $handover->handed_over_amount,
                    'shortage_amount' => (float) $handover->shortage_amount,
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $msg = __('Cash handover voucher :no (:amount) has been approved successfully and transferred into the vault.', [
            'no' => $handover->handover_no,
            'amount' => $formattedAmount,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.finance.cash-handover')->with('success', $msg);
    }

    /**
     * Reject Handover Voucher (Unlocks constituent payments)
     */
    public function reject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        $remarks = $request->input('manager_remarks', __('Cash discrepancy not resolved / rejected by accounts manager.'));

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        DB::beginTransaction();
        try {
            $handover->status = 'rejected';
            $handover->verified_by = Auth::id() ?: 1;
            $handover->verified_at = Carbon::now();
            $handover->manager_remarks = $remarks;
            $handover->save();

            // Unlock constituent payments so they return to collector's un-handed balance
            if ($hasHandoverCol) {
                TenantCustomerPayment::where('handover_id', $handover->id)
                    ->update(['handover_id' => null]);
            }

            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: __('Admin'),
                'event_type' => 'CASH_HANDOVER_REJECTED',
                'description' => __('Rejected cash handover voucher :no. Reason: :reason. Unlocked constituent cash payments.', [
                    'no' => $handover->handover_no,
                    'reason' => $remarks,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'handover_id' => $handover->id,
                    'handover_no' => $handover->handover_no,
                    'collector_id' => $handover->collector_id,
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $msg = __('Cash handover voucher :no has been rejected.', ['no' => $handover->handover_no]);

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
        $currency = $tenant->currency_symbol ?? '৳';

        return response()->streamDownload(function () use ($handovers, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                __('Handover No'),
                __('Date'),
                __('Collector Name'),
                __('Collector Email'),
                __('Shift'),
                __('System Cash (:currency)', ['currency' => $currency]),
                __('Handed Cash (:currency)', ['currency' => $currency]),
                __('Shortage (:currency)', ['currency' => $currency]),
                __('Excess (:currency)', ['currency' => $currency]),
                __('Digital Amount (:currency)', ['currency' => $currency]),
                __('Receipts Count'),
                __('Status'),
                __('Verified By'),
                __('Verified At'),
                __('Collector Notes'),
                __('Manager Remarks'),
            ]);

            foreach ($handovers as $h) {
                fputcsv($handle, [
                    $h->handover_no,
                    $h->handover_date->format('Y-m-d'),
                    $h->collector?->name ?? __('N/A'),
                    $h->collector?->email ?? __('N/A'),
                    $h->shift_label,
                    number_format($h->system_collected_amount, 2, '.', ''),
                    number_format($h->handed_over_amount, 2, '.', ''),
                    number_format($h->shortage_amount, 2, '.', ''),
                    number_format($h->excess_amount, 2, '.', ''),
                    number_format($h->digital_collected_amount, 2, '.', ''),
                    $h->total_receipts_count,
                    $h->status_badge['label'] ?? __(ucfirst($h->status ?? 'Pending')),
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
