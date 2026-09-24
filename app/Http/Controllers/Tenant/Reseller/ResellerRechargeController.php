<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\TenantResellerRecharge;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use App\Notifications\ResellerRechargeStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ResellerRechargeController extends Controller
{
    /**
     * Display Reseller Recharge History & Approval Management.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $statusFilter = $request->input('status');
        $methodFilter = $request->input('payment_method');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        // 1. Fetch All Resellers for filter dropdown & modal
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Recharges
        $query = TenantResellerRecharge::where('tenant_id', $tenant->id)
            ->with(['reseller', 'approver', 'creator']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recharge_no', 'like', "%{$search}%")
                  ->orWhere('gateway_trx_id', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhere('prefix', 'like', "%{$search}%")
                         ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        if ($selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($methodFilter) {
            $query->where('payment_method', $methodFilter);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $recharges = $query->latest('id')->paginate($perPage)->withQueryString();

        // Distinct payment methods for filter
        $availableMethods = TenantResellerRecharge::where('tenant_id', $tenant->id)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method');

        // 3. Compute 6-Card Metric KPIs
        $approvedQuery = TenantResellerRecharge::where('tenant_id', $tenant->id)->where('status', 'APPROVED');
        $totalRechargedEver = (float) (clone $approvedQuery)->sum('amount');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $totalRechargedThisMonth = (float) (clone $approvedQuery)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $startOfDay = Carbon::today()->startOfDay();
        $endOfDay = Carbon::today()->endOfDay();
        $totalRechargedToday = (float) (clone $approvedQuery)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');

        $pendingQuery = TenantResellerRecharge::where('tenant_id', $tenant->id)->where('status', 'PENDING');
        $pendingCount = $pendingQuery->count();
        $pendingAmount = (float) $pendingQuery->sum('amount');

        $onlinePgwCount = (clone $approvedQuery)
            ->whereIn('payment_method', ['BKASH', 'NAGAD', 'ROCKET', 'ONLINE_GATEWAY', 'CARD'])
            ->count();

        $bankAndCashCount = (clone $approvedQuery)
            ->whereIn('payment_method', ['BANK_TRANSFER', 'CASH', 'CHEQUE'])
            ->count();

        return view('tenant.resellers.recharge', compact(
            'tenant',
            'recharges',
            'allResellers',
            'totalRechargedEver',
            'totalRechargedThisMonth',
            'totalRechargedToday',
            'pendingCount',
            'pendingAmount',
            'onlinePgwCount',
            'bankAndCashCount',
            'availableMethods',
            'search',
            'selectedResellerId',
            'statusFilter',
            'methodFilter',
            'dateFrom',
            'dateTo',
            'perPage'
        ));
    }

    /**
     * Store a new recharge record (Direct Approved or Pending Approval).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'amount' => 'required|numeric|min:1',
            'bonus_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'gateway_trx_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:100',
            'deposit_date' => 'nullable|date',
            'status' => 'required|in:APPROVED,PENDING',
            'notes' => 'nullable|string|max:255',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $amount = (float) $validated['amount'];
        $bonusAmount = (float) ($validated['bonus_amount'] ?? 0);
        $totalCredited = $amount + $bonusAmount;
        $status = $validated['status'];

        DB::beginTransaction();
        try {
            $rechargeNo = TenantResellerRecharge::generateRechargeNo($tenant->id);

            $recharge = TenantResellerRecharge::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'recharge_no' => $rechargeNo,
                'amount' => $amount,
                'bonus_amount' => $bonusAmount,
                'total_credited' => $totalCredited,
                'payment_method' => $validated['payment_method'],
                'gateway_trx_id' => $validated['gateway_trx_id'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'deposit_date' => $validated['deposit_date'] ?? null,
                'status' => $status,
                'approved_by' => $status === 'APPROVED' ? Auth::id() : null,
                'approved_at' => $status === 'APPROVED' ? now() : null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // If Approved immediately, credit wallet and record wallet transaction
            if ($status === 'APPROVED') {
                $balanceBefore = (float) $reseller->wallet_balance;
                $balanceAfter = $balanceBefore + $totalCredited;
                $reseller->update(['wallet_balance' => $balanceAfter]);

                $wtxId = TenantResellerWalletTransaction::generateTrxId($tenant->id);
                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => $wtxId,
                    'type' => 'CREDIT',
                    'amount' => $totalCredited,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'payment_method' => $validated['payment_method'],
                    'reference_no' => $rechargeNo,
                    'description' => "Recharge #{$rechargeNo}" . ($bonusAmount > 0 ? " (incl. ৳{$bonusAmount} bonus)" : ""),
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();

            $msg = $status === 'APPROVED' 
                ? "Recharge #{$rechargeNo} of ৳" . number_format($totalCredited, 2) . " successfully credited to {$reseller->name}!"
                : "Recharge request #{$rechargeNo} of ৳" . number_format($amount, 2) . " submitted for approval.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'recharge' => $recharge,
                ]);
            }

            return redirect()->route('tenant.resellers.recharge')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save recharge: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save recharge: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending recharge request.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $recharge = TenantResellerRecharge::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($recharge->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'This recharge is already ' . strtolower($recharge->status) . '.'], 422);
        }

        $reseller = $recharge->reseller;
        $totalCredited = (float) $recharge->total_credited;
        if ($totalCredited <= 0) {
            $totalCredited = (float) $recharge->amount + (float) $recharge->bonus_amount;
            $recharge->total_credited = $totalCredited;
        }

        DB::beginTransaction();
        try {
            $balanceBefore = (float) $reseller->wallet_balance;
            $balanceAfter = $balanceBefore + $totalCredited;

            $reseller->update(['wallet_balance' => $balanceAfter]);

            $wtxId = TenantResellerWalletTransaction::generateTrxId($tenant->id);
            TenantResellerWalletTransaction::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'trx_id' => $wtxId,
                'type' => 'CREDIT',
                'amount' => $totalCredited,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $recharge->payment_method,
                'reference_no' => $recharge->recharge_no,
                'description' => "Approved Recharge #{$recharge->recharge_no}" . ($recharge->bonus_amount > 0 ? " (incl. ৳{$recharge->bonus_amount} bonus)" : ""),
                'created_by' => Auth::id(),
            ]);

            $recharge->update([
                'status' => 'APPROVED',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            // Notify Reseller Users
            try {
                $resellerUsers = User::where('tenant_id', $tenant->id)
                    ->where('reseller_id', $reseller->id)
                    ->get();

                if ($resellerUsers->isNotEmpty()) {
                    Notification::send($resellerUsers, new ResellerRechargeStatusNotification(
                        $recharge,
                        $reseller,
                        'APPROVED'
                    ));
                }
            } catch (\Throwable $e) {
                // Non-blocking notification fallback
            }

            return response()->json([
                'success' => true,
                'message' => "Recharge #{$recharge->recharge_no} approved! ৳" . number_format($totalCredited, 2) . " credited to {$reseller->name}.",
                'new_balance' => $balanceAfter,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Approval failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a pending recharge request.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $recharge = TenantResellerRecharge::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($recharge->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'This recharge is already ' . strtolower($recharge->status) . '.'], 422);
        }

        $recharge->update([
            'status' => 'REJECTED',
            'rejection_reason' => $validated['reason'],
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Notify Reseller Users
        try {
            $reseller = $recharge->reseller;
            if ($reseller) {
                $resellerUsers = User::where('tenant_id', $tenant->id)
                    ->where('reseller_id', $reseller->id)
                    ->get();

                if ($resellerUsers->isNotEmpty()) {
                    Notification::send($resellerUsers, new ResellerRechargeStatusNotification(
                        $recharge,
                        $reseller,
                        'REJECTED',
                        $validated['reason']
                    ));
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking notification fallback
        }

        return response()->json([
            'success' => true,
            'message' => "Recharge #{$recharge->recharge_no} has been rejected.",
        ]);
    }

    /**
     * Get Printable Receipt Payload.
     */
    public function receipt(int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $recharge = TenantResellerRecharge::where('tenant_id', $tenant->id)
            ->with(['reseller', 'approver', 'creator', 'tenant'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'receipt' => [
                'recharge_no' => $recharge->recharge_no,
                'date' => $recharge->created_at->format('d M, Y h:i A'),
                'reseller_name' => $recharge->reseller?->name,
                'reseller_code' => $recharge->reseller?->code,
                'reseller_mobile' => $recharge->reseller?->mobile,
                'reseller_address' => $recharge->reseller?->address,
                'amount' => (float) $recharge->amount,
                'bonus_amount' => (float) $recharge->bonus_amount,
                'total_credited' => (float) $recharge->total_credited,
                'payment_method' => $recharge->payment_method_badge['label'],
                'gateway_trx_id' => $recharge->gateway_trx_id,
                'bank_details' => $recharge->bank_name ? "{$recharge->bank_name} ({$recharge->bank_branch})" : null,
                'status' => $recharge->status,
                'status_badge' => $recharge->status_badge,
                'notes' => $recharge->notes,
                'approved_by_name' => $recharge->approver?->name,
                'tenant_name' => $tenant->company_name ?? $tenant->name,
                'tenant_phone' => $tenant->phone ?? '',
                'tenant_email' => $tenant->email ?? '',
                'tenant_address' => $tenant->address ?? '',
            ],
        ]);
    }
}
