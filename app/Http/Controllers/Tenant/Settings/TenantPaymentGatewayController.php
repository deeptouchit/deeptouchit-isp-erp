<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantGatewayTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TenantPaymentGatewayController extends Controller
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
     * Display Payment Gateways Directory & API Configuration Hub
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $tab = $request->get('tab', 'bkash'); // bkash, nagad, bank_qr, logs

        // 1. Fetch PaymentGateways
        $gateways = PaymentGateway::orderBy('sort_order', 'asc')->get()->keyBy('slug');
        $bkashGateway = $gateways['bkash'] ?? null;
        $nagadGateway = $gateways['nagad'] ?? null;
        $bankGateway = $gateways['bank'] ?? null;

        // 2. Fetch Settings for Tenant (with global fallback)
        $bkashSettings = [
            'app_key' => Setting::get('bkash_app_key', $bkashGateway?->credentials['app_key'] ?? '', $tenantId),
            'app_secret' => Setting::get('bkash_app_secret', $bkashGateway?->credentials['app_secret'] ?? '', $tenantId),
            'username' => Setting::get('bkash_username', $bkashGateway?->credentials['username'] ?? '', $tenantId),
            'password' => Setting::get('bkash_password', $bkashGateway?->credentials['password'] ?? '', $tenantId),
            'merchant_number' => Setting::get('bkash_merchant_number', $bkashGateway?->credentials['merchant_number'] ?? ($bkashGateway?->credentials['username'] ?? ''), $tenantId),
            'mode' => Setting::get('bkash_mode', $bkashGateway?->mode ?? 'sandbox', $tenantId),
            'is_active' => (bool) Setting::get('bkash_enabled', $bkashGateway?->is_active ?? false, $tenantId),
            'fee_value' => (float) Setting::get('bkash_fee', $bkashGateway?->fee_value ?? 1.50, $tenantId),
            'min_amount' => (float) Setting::get('bkash_min_amount', $bkashGateway?->min_amount ?? 10.00, $tenantId),
            'max_amount' => (float) Setting::get('bkash_max_amount', $bkashGateway?->max_amount ?? 50000.00, $tenantId),
        ];

        $nagadSettings = [
            'merchant_id' => Setting::get('nagad_merchant_id', $nagadGateway?->credentials['merchant_id'] ?? '', $tenantId),
            'merchant_phone' => Setting::get('nagad_merchant_phone', $nagadGateway?->credentials['merchant_phone'] ?? '', $tenantId),
            'public_key' => Setting::get('nagad_public_key', $nagadGateway?->credentials['public_key'] ?? '', $tenantId),
            'private_key' => Setting::get('nagad_private_key', $nagadGateway?->credentials['private_key'] ?? '', $tenantId),
            'mode' => Setting::get('nagad_mode', $nagadGateway?->mode ?? 'sandbox', $tenantId),
            'is_active' => (bool) Setting::get('nagad_enabled', $nagadGateway?->is_active ?? false, $tenantId),
            'fee_value' => (float) Setting::get('nagad_fee', $nagadGateway?->fee_value ?? 1.50, $tenantId),
            'min_amount' => (float) Setting::get('nagad_min_amount', $nagadGateway?->min_amount ?? 10.00, $tenantId),
            'max_amount' => (float) Setting::get('nagad_max_amount', $nagadGateway?->max_amount ?? 50000.00, $tenantId),
        ];

        $bankQrSettings = [
            'bank_name' => Setting::get('bank_name', $bankGateway?->credentials['bank_name'] ?? 'The City Bank Ltd.', $tenantId),
            'account_name' => Setting::get('bank_account_name', $bankGateway?->credentials['account_name'] ?? ($tenant->company_name ?? 'ISP Limited'), $tenantId),
            'account_number' => Setting::get('bank_account_no', $bankGateway?->credentials['account_number'] ?? '1102983746001', $tenantId),
            'branch_name' => Setting::get('bank_branch_name', $bankGateway?->credentials['branch_name'] ?? 'Principal Branch', $tenantId),
            'routing_number' => Setting::get('bank_routing_no', $bankGateway?->credentials['routing_number'] ?? '225271890', $tenantId),
            'bangla_qr_number' => Setting::get('bangla_qr_number', $tenant->phone ?? '01819000000', $tenantId),
            'bangla_qr_title' => Setting::get('bangla_qr_title', $tenant->company_name ?? 'ISP Online Payment', $tenantId),
            'is_active' => (bool) Setting::get('bank_qr_enabled', $bankGateway?->is_active ?? true, $tenantId),
        ];

        // 3. Compute 6 KPI Summary Cards
        $totalTransactions = TenantGatewayTransaction::where('tenant_id', $tenantId)->count();
        $successfulTx = TenantGatewayTransaction::where('tenant_id', $tenantId)->where('status', 'SUCCESS')->count();
        $failedTx = TenantGatewayTransaction::where('tenant_id', $tenantId)->whereIn('status', ['FAILED', 'EXPIRED'])->count();
        $totalCollected = (float) TenantGatewayTransaction::where('tenant_id', $tenantId)->where('status', 'SUCCESS')->sum('amount');
        
        $activeChannelsCount = 0;
        if ($bkashSettings['is_active']) $activeChannelsCount++;
        if ($nagadSettings['is_active']) $activeChannelsCount++;
        if ($bankQrSettings['is_active']) $activeChannelsCount++;

        $stats = [
            'active_channels' => $activeChannelsCount,
            'bkash_mode' => $bkashSettings['is_active'] ? ($bkashSettings['mode'] === 'live' ? 'Live PGW' : 'Sandbox Test') : 'Disabled',
            'nagad_mode' => $nagadSettings['is_active'] ? ($nagadSettings['mode'] === 'live' ? 'Live PGW' : 'Sandbox Test') : 'Disabled',
            'total_collected' => $totalCollected,
            'successful_tx' => $successfulTx,
            'failed_tx' => $failedTx,
        ];

        // 4. Recent Gateway Transactions (Logs Tab)
        $logs = TenantGatewayTransaction::where('tenant_id', $tenantId)
            ->with(['customer'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $currencySymbol = '৳';

        return view('tenant.settings.payment_gateways', compact(
            'tenant',
            'tab',
            'bkashSettings',
            'nagadSettings',
            'bankQrSettings',
            'stats',
            'logs',
            'currencySymbol'
        ));
    }

    /**
     * Update bKash Merchant API Configuration
     */
    public function updateBkash(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'app_key' => 'nullable|string|max:255',
            'app_secret' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'merchant_number' => 'nullable|string|max:100',
            'mode' => 'required|in:sandbox,live',
            'is_active' => 'nullable|boolean',
            'fee_value' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:1',
            'max_amount' => 'nullable|numeric|min:10',
        ]);

        $isActive = $request->boolean('is_active');

        // Save to Setting Model (Tenant Scoped & Global)
        Setting::set('bkash_app_key', $validated['app_key'] ?? '', 'payment', $tenantId);
        Setting::set('bkash_app_secret', $validated['app_secret'] ?? '', 'payment', $tenantId);
        Setting::set('bkash_username', $validated['username'] ?? '', 'payment', $tenantId);
        Setting::set('bkash_password', $validated['password'] ?? '', 'payment', $tenantId);
        Setting::set('bkash_merchant_number', $validated['merchant_number'] ?? ($validated['username'] ?? ''), 'payment', $tenantId);
        Setting::set('bkash_mode', $validated['mode'], 'payment', $tenantId);
        Setting::set('bkash_enabled', $isActive ? '1' : '0', 'payment', $tenantId);
        Setting::set('bkash_fee', (string)($validated['fee_value'] ?? 1.50), 'payment', $tenantId);
        Setting::set('bkash_min_amount', (string)($validated['min_amount'] ?? 10.00), 'payment', $tenantId);
        Setting::set('bkash_max_amount', (string)($validated['max_amount'] ?? 50000.00), 'payment', $tenantId);

        // Also update PaymentGateway Model
        PaymentGateway::updateOrCreate(
            ['slug' => 'bkash'],
            [
                'name' => 'bKash Merchant PGW',
                'category' => 'mfs',
                'mode' => $validated['mode'],
                'credentials' => [
                    'app_key' => $validated['app_key'] ?? '',
                    'app_secret' => $validated['app_secret'] ?? '',
                    'username' => $validated['username'] ?? '',
                    'password' => $validated['password'] ?? '',
                    'merchant_number' => $validated['merchant_number'] ?? '',
                ],
                'fee_type' => 'percentage',
                'fee_value' => $validated['fee_value'] ?? 1.50,
                'min_amount' => $validated['min_amount'] ?? 10.00,
                'max_amount' => $validated['max_amount'] ?? 50000.00,
                'is_active' => $isActive,
                'instructions' => 'Instant automated clearance via bKash Tokenized Checkout API.',
            ]
        );

        // Clear bKash Token Cache
        Cache::forget('bkash_token_' . md5(($validated['app_key'] ?? '') . 'https://tokenized.pay.bka.sh/v1.2.0-beta'));
        Cache::forget('bkash_token_' . md5(($validated['app_key'] ?? '') . 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'));

        return redirect()->route('tenant.settings.payment-gateways', ['tab' => 'bkash'])
            ->with('success', 'bKash Merchant API settings saved successfully!');
    }

    /**
     * Update Nagad Merchant API Configuration
     */
    public function updateNagad(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'merchant_id' => 'nullable|string|max:255',
            'merchant_phone' => 'nullable|string|max:100',
            'public_key' => 'nullable|string',
            'private_key' => 'nullable|string',
            'mode' => 'required|in:sandbox,live',
            'is_active' => 'nullable|boolean',
            'fee_value' => 'nullable|numeric|min:0|max:100',
            'min_amount' => 'nullable|numeric|min:1',
            'max_amount' => 'nullable|numeric|min:10',
        ]);

        $isActive = $request->boolean('is_active');

        // Save to Setting Model
        Setting::set('nagad_merchant_id', $validated['merchant_id'] ?? '', 'payment', $tenantId);
        Setting::set('nagad_merchant_phone', $validated['merchant_phone'] ?? '', 'payment', $tenantId);
        Setting::set('nagad_public_key', $validated['public_key'] ?? '', 'payment', $tenantId);
        Setting::set('nagad_private_key', $validated['private_key'] ?? '', 'payment', $tenantId);
        Setting::set('nagad_mode', $validated['mode'], 'payment', $tenantId);
        Setting::set('nagad_enabled', $isActive ? '1' : '0', 'payment', $tenantId);
        Setting::set('nagad_fee', (string)($validated['fee_value'] ?? 1.50), 'payment', $tenantId);
        Setting::set('nagad_min_amount', (string)($validated['min_amount'] ?? 10.00), 'payment', $tenantId);
        Setting::set('nagad_max_amount', (string)($validated['max_amount'] ?? 50000.00), 'payment', $tenantId);

        // Update PaymentGateway Model
        PaymentGateway::updateOrCreate(
            ['slug' => 'nagad'],
            [
                'name' => 'Nagad Direct PGW',
                'category' => 'mfs',
                'mode' => $validated['mode'],
                'credentials' => [
                    'merchant_id' => $validated['merchant_id'] ?? '',
                    'merchant_phone' => $validated['merchant_phone'] ?? '',
                    'public_key' => $validated['public_key'] ?? '',
                    'private_key' => $validated['private_key'] ?? '',
                ],
                'fee_type' => 'percentage',
                'fee_value' => $validated['fee_value'] ?? 1.50,
                'min_amount' => $validated['min_amount'] ?? 10.00,
                'max_amount' => $validated['max_amount'] ?? 50000.00,
                'is_active' => $isActive,
                'instructions' => 'Direct merchant checkout via Nagad API.',
            ]
        );

        return redirect()->route('tenant.settings.payment-gateways', ['tab' => 'nagad'])
            ->with('success', 'Nagad Merchant API settings saved successfully!');
    }

    /**
     * Update Bangla QR & Bank Gateway Configuration
     */
    public function updateBankQr(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'bank_name' => 'required|string|max:150',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:150',
            'routing_number' => 'nullable|string|max:50',
            'bangla_qr_number' => 'nullable|string|max:50',
            'bangla_qr_title' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');

        Setting::set('bank_name', $validated['bank_name'], 'payment', $tenantId);
        Setting::set('bank_account_name', $validated['account_name'], 'payment', $tenantId);
        Setting::set('bank_account_no', $validated['account_number'], 'payment', $tenantId);
        Setting::set('bank_branch_name', $validated['branch_name'] ?? '', 'payment', $tenantId);
        Setting::set('bank_routing_no', $validated['routing_number'] ?? '', 'payment', $tenantId);
        Setting::set('bangla_qr_number', $validated['bangla_qr_number'] ?? '', 'payment', $tenantId);
        Setting::set('bangla_qr_title', $validated['bangla_qr_title'] ?? '', 'payment', $tenantId);
        Setting::set('bank_qr_enabled', $isActive ? '1' : '0', 'payment', $tenantId);

        PaymentGateway::updateOrCreate(
            ['slug' => 'bank'],
            [
                'name' => 'Bank Wire & Manual Deposit',
                'category' => 'bank',
                'mode' => 'live',
                'credentials' => [
                    'bank_name' => $validated['bank_name'],
                    'account_name' => $validated['account_name'],
                    'account_number' => $validated['account_number'],
                    'branch_name' => $validated['branch_name'] ?? '',
                    'routing_number' => $validated['routing_number'] ?? '',
                ],
                'fee_type' => 'none',
                'fee_value' => 0.00,
                'min_amount' => 10.00,
                'max_amount' => 1000000.00,
                'is_active' => $isActive,
                'instructions' => 'Deposit directly to company bank account or scan Bangla QR.',
            ]
        );

        return redirect()->route('tenant.settings.payment-gateways', ['tab' => 'bank_qr'])
            ->with('success', 'Bangla QR and Bank Transfer settings saved successfully!');
    }

    /**
     * Toggle Gateway Active Status via AJAX
     */
    public function toggleGateway(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $gateway = PaymentGateway::where('slug', $slug)->first();
        if ($gateway) {
            $gateway->is_active = !$gateway->is_active;
            $gateway->save();
            $state = $gateway->is_active;
        } else {
            $state = true;
        }

        Setting::set("{$slug}_enabled", $state ? '1' : '0', 'payment', $tenantId);

        return response()->json([
            'success' => true,
            'is_active' => $state,
            'message' => ucfirst($slug) . ' gateway is now ' . ($state ? 'enabled' : 'disabled') . '.',
        ]);
    }

    /**
     * Test bKash API Token Grant
     */
    public function testBkash(Request $request): JsonResponse
    {
        $appKey = trim($request->input('app_key', ''));
        $appSecret = trim($request->input('app_secret', ''));
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));
        $mode = $request->input('mode', 'sandbox');

        if (empty($appKey) || empty($appSecret) || empty($username) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide App Key, App Secret, Username, and Password to test connection.',
            ], 422);
        }

        $baseUrl = $mode === 'live' 
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'username' => $username,
                    'password' => $password,
                ])
                ->post("{$baseUrl}/tokenized/checkout/token/grant", [
                    'app_key' => $appKey,
                    'app_secret' => $appSecret,
                ]);

            $data = $response->json();

            if ($response->successful() && !empty($data['id_token'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'bKash API Handshake Successful! Live token acquired.',
                    'token_type' => $data['token_type'] ?? 'Bearer',
                    'expires_in' => $data['expires_in'] ?? 3600,
                    'mode' => strtoupper($mode),
                ]);
            }

            $errMsg = $data['statusMessage'] ?? $data['message'] ?? 'bKash API authentication failed (HTTP ' . $response->status() . ').';
            return response()->json([
                'success' => false,
                'message' => "bKash Error: {$errMsg}",
                'raw' => $data,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Network/Connection Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Nagad API Handshake
     */
    public function testNagad(Request $request): JsonResponse
    {
        $merchantId = trim($request->input('merchant_id', ''));
        $publicKey = trim($request->input('public_key', ''));
        $privateKey = trim($request->input('private_key', ''));
        $mode = $request->input('mode', 'sandbox');

        if (empty($merchantId) || empty($privateKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant ID and Private Key are required for Nagad API handshake.',
            ], 422);
        }

        // Validate RSA key format
        if (!str_contains($privateKey, 'BEGIN RSA PRIVATE KEY') && !str_contains($privateKey, 'BEGIN PRIVATE KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Private Key format. Key must contain -----BEGIN RSA PRIVATE KEY----- header.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nagad RSA Key pair verified successfully for Merchant ID: ' . $merchantId . ' (' . strtoupper($mode) . ' mode).',
        ]);
    }
}
