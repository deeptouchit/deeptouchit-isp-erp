<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\PaymentTransaction;
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
use App\Services\NotificationScopeService;
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
        $reseller = $user?->reseller;
        $tenant = $user?->tenant;
        abort_unless($reseller && $tenant && (int) $reseller->tenant_id === (int) $tenant->id, 403);

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Reseller Wallet Recharge Ledger & Topup Requests.
     */

    private function filteredWalletRecords(Request $request, int $tenantId, int $resellerId): array
    {
        $search = trim($request->string('search')->toString());
        $status = strtoupper($request->string('status', 'all')->toString());
        $status = $status === 'ALL' ? 'all' : $status;
        $method = strtoupper($request->string('payment_method', 'all')->toString());
        $method = $method === 'ALL' ? 'all' : $method;
        $month = $request->string('month', 'all')->toString();
        $dates = $request->validate(['date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from']);

        $query = TenantResellerRecharge::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);
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
            $query->where('status', $status);
        }
        if ($method !== 'all') {
            $query->where('payment_method', $method);
        }
        if (preg_match('/^\d{4}-(?:0[1-9]|1[0-2])$/', $month)) {
            $startOfMonth = Carbon::createFromFormat('!Y-m', $month);
            $query->whereBetween('created_at', [$startOfMonth, $startOfMonth->copy()->endOfMonth()]);
        }
        if (!empty($dates['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($dates['date_from'])->startOfDay());
        }
        if (!empty($dates['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($dates['date_to'])->endOfDay());
        }

        return [$query, $search, $status, $method, $month];
    }

    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$query, $search, $status, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);
        $pageSizes = [10, 15, 20, 30, 50, 100, 250, 500];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $pageSizes, true)) {
            $perPage = 20;
        }

        $filteredTotals = clone $query;
        $query->with(['approver', 'creator']);

        $recharges = $query->latest('id')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards
        $allRecharges = $filteredTotals;

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
            ->selectRaw((new TenantResellerRecharge)->getConnection()->getDriverName() === 'sqlite'
                ? "DISTINCT strftime('%Y-%m', created_at) as m"
                : "DISTINCT DATE_FORMAT(created_at, '%Y-%m') as m")
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

        $pendingCheckouts = PaymentTransaction::where('tenant_id', $tenantId)
            ->where('gateway', 'bkash')->where('payment_method', 'reseller_wallet')
            ->where('gateway_response->reseller_id', $resellerId)
            ->where('status', 'pending')->latest('id')->limit(10)->get();

        $paymentSettings = Setting::getGroup('payment', $tenantId);
        $bkashApiEnabled = !empty($paymentSettings['bkash_app_key']) && !empty($paymentSettings['bkash_app_secret']) && ($paymentSettings['bkash_enabled'] ?? '1') == '1';

        $currencySymbol = $tenant->currency_symbol ?? '৳';

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
            'pageSizes',
            'availableMonths',
            'availableMethods',
            'bkashApiEnabled',
            'pendingCheckouts',
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
            'amount' => 'required|numeric|decimal:0,2|min:10|max:9999999999.99',
            'payment_method' => 'required|string|in:BKASH,NAGAD,ROCKET,BANK_TRANSFER,CASH,CARD,ONLINE_GATEWAY,BANGLA_QR',
            'gateway_trx_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:100',
            'deposit_date' => 'nullable|date',
            'slip' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $reference = isset($validated['gateway_trx_id']) ? strtoupper(trim($validated['gateway_trx_id'])) : null;
        $validated['gateway_trx_id'] = $reference ?: null;

        if ($validated['payment_method'] !== 'CASH' && !$reference && !$request->hasFile('slip')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'gateway_trx_id' => __('Enter the payment reference or attach a deposit slip.'),
            ]);
        }

        $recharge = DB::transaction(function () use ($request, $validated, $tenant, $reseller) {
            Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();

            if ($validated['gateway_trx_id'] && TenantResellerRecharge::where('tenant_id', $tenant->id)
                ->whereRaw('UPPER(TRIM(gateway_trx_id)) = ?', [$validated['gateway_trx_id']])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'gateway_trx_id' => __('This payment reference has already been submitted.'),
                ]);
            }

            $slipPath = null;
            if ($request->hasFile('slip')) {
                $slipPath = $request->file('slip')->store("recharges/{$tenant->id}", 'public');
            }

            $amount = (float) $validated['amount'];
            $rechargeNo = TenantResellerRecharge::generateRechargeNo($tenant->id);

            return TenantResellerRecharge::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'recharge_no' => $rechargeNo,
                'amount' => $amount,
                'bonus_amount' => 0.00,
                'total_credited' => 0,
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
        });
        $amount = (float) $recharge->amount;
        $rechargeNo = $recharge->recharge_no;

        // Send Realtime Push / In-App Notification to Tenant Admins
        try {
            $tenantAdmins = User::where('tenant_id', $tenant->id)
                ->whereNull('reseller_id')
                ->get();
            $tenantAdmins = app(NotificationScopeService::class)
                ->filterRecipients($tenantAdmins, 'reseller_recharge_pending');

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

        [$query, $search, $status, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);

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

        [$query, $search, $status, $method, $month] = $this->filteredWalletRecords($request, $tenantId, $resellerId);

        $recharges = $query->with(['approver', 'creator'])->latest('id')->get();

        $totalApproved = $recharges->where('status', 'APPROVED')->sum('total_credited');
        $totalPending = $recharges->where('status', 'PENDING')->sum('amount');
        $currencySymbol = $tenant->currency_symbol ?? '৳';

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
            'amount' => 'required|numeric|decimal:0,2|min:10|max:9999999999.99',
            'gateway_channel' => 'required|string|in:BKASH_MERCHANT,BANGLA_QR,BANK_GATEWAY,CARD,ONLINE_GATEWAY',
            'trx_id' => 'required|string|min:3|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $request->merge([
            'payment_method' => match ($validated['gateway_channel']) {
                'BKASH_MERCHANT' => 'BKASH',
                'BANK_GATEWAY' => 'BANK_TRANSFER',
                default => $validated['gateway_channel'],
            },
            'gateway_trx_id' => $validated['trx_id'],
        ]);

        return $this->store($request);
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
            'amount' => 'required|numeric|decimal:0,2|min:10|max:99999999.99',
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

            PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'payment_method' => 'reseller_wallet',
                'gateway' => 'bkash',
                'transaction_reference' => 'reseller-bkash:' . $paymentId,
                'amount' => $amount,
                'currency' => 'BDT',
                'status' => 'pending',
                'initiated_at' => now(),
                'gateway_response' => [
                    'reseller_id' => $reseller->id,
                    'payment_id' => $paymentId,
                    'merchant_invoice' => $result['merchant_invoice'],
                    'created_by' => $authUser->id,
                ],
            ]);

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
        $paymentId = trim($request->string('paymentID')->toString());
        if ($paymentId === '' || strlen($paymentId) > 200) {
            return redirect()->route('reseller.recharge.index')->with('error', __('Invalid bKash payment reference.'));
        }

        $session = PaymentTransaction::where('tenant_id', $tenant->id)
            ->where('transaction_reference', 'reseller-bkash:' . $paymentId)
            ->where('payment_method', 'reseller_wallet')
            ->where('gateway', 'bkash')
            ->first();
        if (!$session) {
            $legacyRecharge = TenantResellerRecharge::where('tenant_id', $tenant->id)
                ->where('reseller_id', $reseller->id)->where('status', 'APPROVED')
                ->where('payment_method', 'BKASH_MERCHANT')
                ->where('notes', 'like', 'Instant bKash API Checkout Clearance (PaymentID: ' . addcslashes($paymentId, '%_\\\\') . ', TrxID: %')
                ->first();
            if ($legacyRecharge) {
                return redirect()->route('reseller.recharge.index')->with('success', __('This recharge was already credited.'));
            }

            // Preserve in-flight checkouts started by the previous release.
            $legacy = Cache::get("bkash_reseller_session_{$paymentId}");
            abort_unless($legacy && (int) ($legacy['tenant_id'] ?? 0) === (int) $tenant->id
                && (int) ($legacy['reseller_id'] ?? 0) === (int) $reseller->id, 404);

            $session = PaymentTransaction::firstOrCreate(
                ['transaction_reference' => 'reseller-bkash:' . $paymentId],
                [
                    'tenant_id' => $tenant->id, 'payment_method' => 'reseller_wallet', 'gateway' => 'bkash',
                    'amount' => $legacy['amount'], 'currency' => 'BDT', 'status' => 'pending',
                    'initiated_at' => now(), 'gateway_response' => $legacy + ['payment_id' => $paymentId],
                ]
            );
        }

        abort_unless((int) $session->tenant_id === (int) $tenant->id
            && (int) ($session->gateway_response['reseller_id'] ?? 0) === (int) $reseller->id, 404);

        if ($session->status !== 'successful' && in_array($request->input('status'), ['cancel', 'failure'], true)) {
            return redirect()->route('reseller.recharge.index')->with('error', __('The checkout was cancelled or failed. You can check the payment status again.'));
        }

        $result = BkashPaymentService::forTenant($tenant->id)->executeResellerPayment($paymentId, $session, $request->input('status') === 'success');
        if ($result['success'] ?? false) {
            Cache::forget("bkash_reseller_session_{$paymentId}");
            $amount = number_format((float) ($result['recharge']->amount ?? $session->amount), 2);
            $trxId = $result['trx_id'] ?? $paymentId;
            $newBalance = number_format((float) ($result['new_balance'] ?? 0), 2);
            $rechargeNo = $result['recharge']->recharge_no ?? '';
            return redirect()->route('reseller.recharge.index')->with('success', "Payment successful! Wallet credited with ৳{$amount} via bKash. Voucher #{$rechargeNo} generated (TrxID: {$trxId}). Updated Balance: ৳{$newBalance}");
        }

        return redirect()->route('reseller.recharge.index')->with('error', $result['message'] ?? 'Failed to complete bKash payment.');
    }
}
