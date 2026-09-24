<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerRecharge;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use App\Notifications\ResellerRechargePendingNotification;
use App\Services\Payment\BkashPaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerRechargeController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Reseller Wallet Recharge Ledger & Topup Requests.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // 1. Base Query for this Reseller
        $query = TenantResellerRecharge::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['approver', 'creator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('recharge_no', 'like', "%{$search}%")
                  ->orWhere('gateway_trx_id', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', strtoupper($status));
        }

        if ($method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

        $recharges = $query->latest('id')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards
        $allRecharges = TenantResellerRecharge::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $walletBalance = (float) ($reseller->wallet_balance ?? 0);
        $creditLimit = (float) ($reseller->credit_limit ?? 0);
        $totalAvailable = (float) ($reseller->total_available_balance ?? ($walletBalance + $creditLimit));

        $approvedAmount = (float) (clone $allRecharges)->where('status', 'APPROVED')->sum('total_credited');
        $pendingAmount = (float) (clone $allRecharges)->where('status', 'PENDING')->sum('amount');
        $totalCount = (int) (clone $allRecharges)->count();

        $stats = [
            'wallet_balance' => $walletBalance,
            'credit_limit' => $creditLimit,
            'total_available' => $totalAvailable,
            'approved_amount' => $approvedAmount,
            'pending_amount' => $pendingAmount,
            'total_count' => $totalCount,
        ];

        // 3. Dropdown options
        $availableMonths = TenantResellerRecharge::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw("DISTINCT DATE_FORMAT(created_at, '%Y-%m') as m")
            ->orderByDesc('m')
            ->pluck('m')
            ->filter()
            ->values();

        $availableMethods = TenantResellerRecharge::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values();

        $gateways = PaymentGateway::where('is_active', true)->get()->keyBy('slug');
        $paymentSettings = Setting::getGroup('payment', $tenantId);
        $bkashMerchantNumber = $paymentSettings['bkash_merchant_number'] ?? ($paymentSettings['bkash_username'] ?? ($gateways['bkash']->credentials['username'] ?? '01819-000000'));
        $bkashApiEnabled = !empty($paymentSettings['bkash_app_key']) && !empty($paymentSettings['bkash_app_secret']) && ($paymentSettings['bkash_enabled'] ?? '1') == '1';

        $currencySymbol = '৳';

        return view('reseller.recharge.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'recharges',
            'stats',
            'search',
            'status',
            'method',
            'month',
            'perPage',
            'availableMonths',
            'availableMethods',
            'gateways',
            'paymentSettings',
            'bkashMerchantNumber',
            'bkashApiEnabled',
            'currencySymbol'
        ));
    }

    /**
     * Submit a new Wallet Recharge Request.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        if (!$reseller || !$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'payment_method' => 'required|string|in:BKASH,NAGAD,ROCKET,BANK_TRANSFER,CASH,CARD,ONLINE_GATEWAY',
            'gateway_trx_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:100',
            'deposit_date' => 'nullable|date',
            'slip' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store("recharges/{$tenant->id}", 'public');
        }

        $amount = (float) $validated['amount'];
        $rechargeNo = TenantResellerRecharge::generateRechargeNo($tenant->id);

        $recharge = TenantResellerRecharge::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $reseller->id,
            'recharge_no' => $rechargeNo,
            'amount' => $amount,
            'bonus_amount' => 0.00,
            'total_credited' => $amount,
            'payment_method' => $validated['payment_method'],
            'gateway_trx_id' => $validated['gateway_trx_id'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'bank_account_no' => $validated['bank_account_no'] ?? null,
            'deposit_date' => $validated['deposit_date'] ?? now()->toDateString(),
            'slip_path' => $slipPath,
            'status' => 'PENDING',
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Send Realtime Push / In-App Notification to Tenant Admins
        try {
            $tenantAdmins = User::where('tenant_id', $tenant->id)
                ->whereNull('reseller_id')
                ->get();

            if ($tenantAdmins->isNotEmpty()) {
                Notification::send($tenantAdmins, new ResellerRechargePendingNotification(
                    $recharge,
                    $reseller,
                    $amount,
                    $validated['payment_method'],
                    $validated['gateway_trx_id'] ?? null
                ));
            }
        } catch (\Throwable $e) {
            // Non-blocking notification fallback
        }

        $successMsg = "Recharge request #{$rechargeNo} for ৳" . number_format($amount, 2) . " submitted successfully! Please allow some time for approval.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'recharge' => $recharge,
            ]);
        }

        return redirect()->route('reseller.recharge.index')->with('success', $successMsg);
    }

    /**
     * View Recharge Receipt / Voucher data as JSON.
     */
    public function receipt(int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $recharge = TenantResellerRecharge::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->with(['approver', 'creator', 'tenant'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $recharge->id,
                'recharge_no' => $recharge->recharge_no,
                'amount' => (float) $recharge->amount,
                'bonus_amount' => (float) $recharge->bonus_amount,
                'total_credited' => (float) $recharge->total_credited,
                'payment_method' => $recharge->payment_method,
                'payment_method_label' => $recharge->payment_method_badge['label'] ?? $recharge->payment_method,
                'payment_method_badge' => $recharge->payment_method_badge,
                'gateway_trx_id' => $recharge->gateway_trx_id,
                'bank_name' => $recharge->bank_name,
                'bank_branch' => $recharge->bank_branch,
                'bank_account_no' => $recharge->bank_account_no,
                'deposit_date' => $recharge->deposit_date ? $recharge->deposit_date->format('d M Y') : null,
                'slip_url' => $recharge->slip_path ? Storage::url($recharge->slip_path) : null,
                'status' => $recharge->status,
                'status_badge' => $recharge->status_badge,
                'rejection_reason' => $recharge->rejection_reason,
                'approved_by' => $recharge->approver?->name,
                'approved_at' => $recharge->approved_at ? $recharge->approved_at->format('d M Y, h:i A') : null,
                'notes' => $recharge->notes,
                'created_at' => $recharge->created_at ? $recharge->created_at->format('d M Y, h:i A') : null,
                'reseller_name' => $reseller->name,
                'reseller_code' => $reseller->code,
                'tenant_name' => $tenant->company_name ?? $tenant->name,
            ],
        ]);
    }

    /**
     * Export Recharges as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');

        $query = TenantResellerRecharge::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('recharge_no', 'like', "%{$search}%")
                  ->orWhere('gateway_trx_id', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', strtoupper($status));
        }

        if ($method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

        $recharges = $query->latest('id')->get();
        $filename = 'wallet_recharges_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($recharges) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Recharge No',
                'Method',
                'Transaction ID / Cheque',
                'Bank / Branch',
                'Amount',
                'Bonus',
                'Total Credited',
                'Status',
                'Deposit Date',
                'Approved At',
                'Requested At',
                'Notes'
            ]);

            foreach ($recharges as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->recharge_no,
                    $r->payment_method,
                    $r->gateway_trx_id ?? 'N/A',
                    $r->bank_name ? ($r->bank_name . ' (' . $r->bank_branch . ')') : 'N/A',
                    $r->amount,
                    $r->bonus_amount,
                    $r->total_credited,
                    $r->status,
                    $r->deposit_date ? $r->deposit_date->format('Y-m-d') : 'N/A',
                    $r->approved_at ? $r->approved_at->format('Y-m-d H:i') : 'N/A',
                    $r->created_at ? $r->created_at->format('Y-m-d H:i') : 'N/A',
                    $r->notes ?? ''
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print Recharge Statement / Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $method = $request->input('payment_method', 'all');
        $month = $request->input('month', 'all');

        $query = TenantResellerRecharge::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['approver', 'creator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('recharge_no', 'like', "%{$search}%")
                  ->orWhere('gateway_trx_id', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', strtoupper($status));
        }

        if ($method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

        $recharges = $query->latest('id')->get();

        $totalApproved = $recharges->where('status', 'APPROVED')->sum('total_credited');
        $totalPending = $recharges->where('status', 'PENDING')->sum('amount');
        $currencySymbol = '৳';

        return view('reseller.recharge.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'recharges',
            'totalApproved',
            'totalPending',
            'currencySymbol',
            'month',
            'status',
            'method'
        ));
    }

    /**
     * Process Instant Online Wallet Recharge (bKash Merchant, Bank Gateway, Bangla QR).
     */
    public function onlineRecharge(Request $request): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        if (!$reseller || !$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'gateway_channel' => 'required|string|in:BKASH_MERCHANT,BANGLA_QR,BANK_GATEWAY,CARD,ONLINE_GATEWAY',
            'trx_id' => 'required|string|min:3|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amount'];
        $channel = strtoupper($validated['gateway_channel']);
        $trxId = strtoupper(trim($validated['trx_id']));

        $channelNames = [
            'BKASH_MERCHANT' => 'bKash Merchant Checkout',
            'BANGLA_QR' => 'Bangla QR Interoperable',
            'BANK_GATEWAY' => 'Bank Online Gateway',
            'CARD' => 'Debit/Credit Card',
            'ONLINE_GATEWAY' => 'Online Payment Gateway',
        ];
        $channelLabel = $channelNames[$channel] ?? 'Online Gateway';

        $paymentMethod = match ($channel) {
            'BKASH_MERCHANT' => 'BKASH_MERCHANT',
            'BANGLA_QR' => 'BANGLA_QR',
            'BANK_GATEWAY' => 'BANK_GATEWAY',
            default => 'ONLINE_GATEWAY',
        };

        return DB::transaction(function () use ($tenant, $reseller, $amount, $paymentMethod, $trxId, $channelLabel, $channel, $validated, $request) {
            $rechargeNo = TenantResellerRecharge::generateRechargeNo($tenant->id);

            // 1. Create Approved Recharge Record
            $recharge = TenantResellerRecharge::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'recharge_no' => $rechargeNo,
                'amount' => $amount,
                'bonus_amount' => 0.00,
                'total_credited' => $amount,
                'payment_method' => $paymentMethod,
                'gateway_trx_id' => $trxId,
                'bank_name' => $validated['bank_name'] ?? ($channel === 'BANK_GATEWAY' ? 'Online Bank Transfer' : null),
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'deposit_date' => now()->toDateString(),
                'status' => 'APPROVED',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'notes' => $validated['notes'] ?? "Instant Online Recharge via {$channelLabel} (TrxID: {$trxId})",
                'created_by' => Auth::id(),
            ]);

            // 2. Update Reseller Wallet Balance
            $balanceBefore = (float) ($reseller->wallet_balance ?? 0);
            $reseller->increment('wallet_balance', $amount);
            $freshReseller = $reseller->fresh();
            $balanceAfter = (float) $freshReseller->wallet_balance;

            // 3. Record in Wallet Transaction Ledger
            $wtxId = TenantResellerWalletTransaction::generateTrxId($tenant->id);
            TenantResellerWalletTransaction::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'trx_id' => $wtxId,
                'type' => 'CREDIT',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $paymentMethod,
                'reference_no' => $rechargeNo,
                'description' => "Online Instant Recharge via {$channelLabel} (TrxID: {$trxId})",
                'created_by' => Auth::id(),
            ]);

            $successMsg = "Wallet recharged successfully with ৳" . number_format($amount, 2) . " via {$channelLabel}! Updated balance: ৳" . number_format($balanceAfter, 2);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'new_balance' => $balanceAfter,
                    'recharge' => $recharge,
                ]);
            }

            return redirect()->route('reseller.recharge.index')->with('success', $successMsg);
        });
    }

    /**
     * Initiate Direct 1-Click bKash Merchant API Checkout.
     * Does NOT create any unconfirmed recharge record before payment succeeds.
     */
    public function initiateBkashCheckout(Request $request): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        if (!$reseller || !$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $amount = (float) $validated['amount'];
        $callbackUrl = route('reseller.recharge.bkash.callback');
        $bkashService = BkashPaymentService::forTenant($tenant->id);

        $result = $bkashService->createResellerCheckoutSession(
            $amount,
            (string) ($reseller->code ?? ('RES-' . $reseller->id)),
            $callbackUrl,
            $tenant->id
        );

        if ($result['success'] ?? false) {
            $paymentId = $result['payment_id'];

            // Store transient recharge session in Cache (expires in 1 hour)
            // NO rows are inserted into tenant_reseller_recharges yet!
            Cache::put("bkash_reseller_session_{$paymentId}", [
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'amount' => $amount,
                'created_by' => Auth::id(),
            ], now()->addHour());

            return response()->json([
                'success' => true,
                'redirect_url' => $result['redirect_url'],
                'payment_id' => $paymentId,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to initialize bKash gateway session. Please verify API credentials in Admin Settings.',
        ], 422);
    }

    /**
     * Handle bKash Gateway Return Callback.
     * Generates voucher / invoice ONLY AFTER bKash payment verification succeeds!
     */
    public function handleBkashCallback(Request $request): RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $paymentId = $request->input('paymentID');
        $status = $request->input('status');

        if (!$paymentId) {
            return redirect()->route('reseller.recharge.index')->with('error', 'bKash session was invalid or closed.');
        }

        if ($status === 'cancel') {
            Cache::forget("bkash_reseller_session_{$paymentId}");
            return redirect()->route('reseller.recharge.index')->with('error', 'bKash payment was cancelled. No invoice or recharge record was created.');
        }

        if ($status === 'failure') {
            Cache::forget("bkash_reseller_session_{$paymentId}");
            return redirect()->route('reseller.recharge.index')->with('error', 'bKash payment failed or was declined. No record was created.');
        }

        $sessionData = Cache::get("bkash_reseller_session_{$paymentId}");
        if (!$sessionData) {
            // Check if already processed
            $existing = TenantResellerRecharge::where('tenant_id', $tenant?->id)
                ->where(function ($q) use ($paymentId) {
                    $q->where('gateway_trx_id', $paymentId)
                      ->orWhere('notes', 'like', "%{$paymentId}%");
                })
                ->first();
            if ($existing) {
                return redirect()->route('reseller.recharge.index')->with('success', "Payment already processed. Voucher #{$existing->recharge_no} (TrxID: {$existing->gateway_trx_id}).");
            }
            return redirect()->route('reseller.recharge.index')->with('error', 'Recharge session expired. Please initiate payment again.');
        }

        // Execute & Capture payment - ONLY NOW will record be generated in DB
        $bkashService = BkashPaymentService::forTenant($sessionData['tenant_id'] ?? $tenant?->id);
        $result = $bkashService->executeResellerPayment($paymentId, $sessionData, $tenant?->id);

        Cache::forget("bkash_reseller_session_{$paymentId}");

        if ($result['success'] ?? false) {
            $amount = number_format((float) ($result['recharge']->amount ?? $sessionData['amount']), 2);
            $trxId = $result['trx_id'] ?? $paymentId;
            $newBalance = number_format((float) ($result['new_balance'] ?? 0), 2);
            $rechargeNo = $result['recharge']->recharge_no ?? '';
            return redirect()->route('reseller.recharge.index')->with('success', "Payment successful! Wallet credited with ৳{$amount} via bKash. Voucher #{$rechargeNo} generated (TrxID: {$trxId}). Updated Balance: ৳{$newBalance}");
        }

        return redirect()->route('reseller.recharge.index')->with('error', $result['message'] ?? 'Failed to complete bKash payment.');
    }
}
