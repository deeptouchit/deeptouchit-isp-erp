<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\Setting;
use App\Models\TenantReseller;
use App\Models\TenantResellerRecharge;
use App\Models\TenantResellerWalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashPaymentService
{
    protected string $appKey;
    protected string $appSecret;
    protected string $username;
    protected string $password;
    protected string $baseUrl;
    protected bool $isLive;
    protected ?int $tenantId = null;

    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
        $tenantSettings = $tenantId ? Setting::getGroup('payment', $tenantId) : [];
        $globalSettings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $gw = PaymentGateway::where('slug', 'bkash')->first();
        $gwCreds = $gw?->credentials ?? [];

        $this->appKey = $tenantSettings['bkash_app_key'] ?? ($globalSettings['bkash_app_key'] ?? ($gwCreds['app_key'] ?? ''));
        $this->appSecret = $tenantSettings['bkash_app_secret'] ?? ($globalSettings['bkash_app_secret'] ?? ($gwCreds['app_secret'] ?? ''));
        $this->username = $tenantSettings['bkash_username'] ?? ($globalSettings['bkash_username'] ?? ($gwCreds['username'] ?? ''));
        $this->password = $tenantSettings['bkash_password'] ?? ($globalSettings['bkash_password'] ?? ($gwCreds['password'] ?? ''));
        
        $mode = $tenantSettings['bkash_mode'] ?? ($globalSettings['bkash_mode'] ?? ($gw?->mode ?? 'sandbox'));
        $this->isLive = $mode === 'live';
        $this->baseUrl = $this->isLive 
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    }

    public static function forTenant(?int $tenantId = null): self
    {
        return new self($tenantId);
    }

    /**
     * Acquire bKash authentication id_token with caching.
     */
    public function grantToken(): array
    {
        if (empty($this->appKey) || empty($this->appSecret) || empty($this->username) || empty($this->password)) {
            $missing = [];
            if (empty($this->appKey)) $missing[] = 'App Key';
            if (empty($this->appSecret)) $missing[] = 'App Secret';
            if (empty($this->username)) $missing[] = 'Username';
            if (empty($this->password)) $missing[] = 'Password';
            return [
                'token' => null,
                'error' => 'bKash credentials missing in Owner Settings: ' . implode(', ', $missing) . '. Please configure at https://somitysoft.com/owner/payment-gateways',
            ];
        }

        $cacheKey = 'bkash_token_' . md5($this->appKey . $this->baseUrl);
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return ['token' => $cachedToken, 'error' => null];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'username' => $this->username,
                    'password' => $this->password,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/token/grant", [
                    'app_key' => $this->appKey,
                    'app_secret' => $this->appSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['id_token'] ?? null;
                if ($token) {
                    Cache::put($cacheKey, $token, 3500);
                    return ['token' => $token, 'error' => null];
                }
            }

            $errorData = $response->json();
            $errorMsg = $errorData['statusMessage'] ?? $errorData['message'] ?? ('HTTP ' . $response->status() . ': ' . $response->body());
            Log::error('bKash Token Grant Failed', ['status' => $response->status(), 'response' => $errorData]);
            return ['token' => null, 'error' => 'bKash API Error: ' . $errorMsg];
        } catch (\Throwable $e) {
            Log::error('bKash Token Grant Exception: ' . $e->getMessage());
            return ['token' => null, 'error' => 'bKash Connection Error: ' . $e->getMessage()];
        }
    }

    /**
     * Initiate bKash tokenized checkout transaction.
     */
    public function initiatePayment(SaasInvoice $invoice, float $amount, string $callbackUrl): array
    {
        // Enforce invoice & amount sanity
        $due = (float) $invoice->calculated_due;
        $chargeAmount = round(min($amount, $due > 0 ? $due : $amount), 2);
        $merchantInvoiceNumber = 'INV-' . $invoice->id . '-' . strtoupper(Str::random(6));

        $tokenResult = $this->grantToken();
        $token = $tokenResult['token'];

        if (!$token) {
            return [
                'success' => false,
                'message' => $tokenResult['error'] ?? 'Failed to authenticate with bKash API.',
            ];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $token,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/create", [
                    'mode' => '0011',
                    'payerReference' => (string) $invoice->tenant_id,
                    'callbackURL' => $callbackUrl,
                    'amount' => number_format($chargeAmount, 2, '.', ''),
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $merchantInvoiceNumber,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['statusCode'] ?? '') === '0000') {
                $paymentId = $data['paymentID'] ?? null;
                $redirectUrl = $data['bkashURL'] ?? null;

                if ($paymentId && $redirectUrl) {
                    $transaction = PaymentTransaction::create([
                        'tenant_id' => $invoice->tenant_id,
                        'saas_invoice_id' => $invoice->id,
                        'payment_method' => 'bkash',
                        'gateway' => 'bkash',
                        'transaction_reference' => $paymentId,
                        'amount' => $chargeAmount,
                        'currency' => 'BDT',
                        'status' => 'pending',
                        'gateway_response' => $data,
                        'initiated_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'transaction_id' => $transaction->id,
                        'transaction_reference' => $paymentId,
                        'amount' => $chargeAmount,
                        'redirect_url' => $redirectUrl,
                    ];
                }
            }

            $errMsg = $data['statusMessage'] ?? $data['message'] ?? 'Failed to generate bKash payment URL.';
            Log::error('bKash Create Payment API Error', ['response' => $data]);
            return [
                'success' => false,
                'message' => 'bKash API Error: ' . $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('bKash Create Payment Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'bKash Connection Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Execute and verify bKash payment with Idempotency, Row Locking & Replay Protection.
     */
    public function verifyPayment(string $paymentId, ?string $trxId = null, ?int $tenantId = null): array
    {
        return DB::transaction(function () use ($paymentId, $trxId, $tenantId) {
            // Acquire pessimistic row-level lock on the transaction
            $query = PaymentTransaction::where('transaction_reference', $paymentId)->lockForUpdate();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            $transaction = $query->first();

            if (!$transaction) {
                return ['success' => false, 'message' => 'Payment transaction reference not found or unauthorized.'];
            }

            // IDEMPOTENCY & REPLAY PROTECTION GUARD:
            // If already settled, return success immediately without double-settling invoice
            if ($transaction->status === 'successful') {
                return [
                    'success' => true,
                    'already_processed' => true,
                    'transaction' => $transaction,
                    'invoice' => $transaction->invoice,
                    'trx_id' => $transaction->transaction_reference,
                ];
            }

            $finalTrxId = $trxId;
            $gatewayResponse = [];

            // Attempt Live bKash Execution
            $tokenResult = $this->grantToken();
            $token = $tokenResult['token'] ?? null;

            if ($token) {
                try {
                    $response = Http::timeout(20)
                        ->withHeaders([
                            'Authorization' => $token,
                            'X-APP-Key' => $this->appKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post("{$this->baseUrl}/tokenized/checkout/execute", [
                            'paymentID' => $paymentId,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['statusCode'] ?? '') === '0000') {
                            $finalTrxId = $data['trxID'] ?? null;
                            $gatewayResponse = $data;
                            
                            // Amount Verification
                            $executedAmount = (float)($data['amount'] ?? $transaction->amount);
                            if (abs($executedAmount - (float)$transaction->amount) > 0.01) {
                                Log::error('bKash Amount Mismatch', [
                                    'expected' => $transaction->amount,
                                    'received' => $executedAmount
                                ]);
                                return ['success' => false, 'message' => 'Payment amount verification mismatch.'];
                            }
                        } else {
                            $transaction->update(['status' => 'failed', 'gateway_response' => $data]);
                            return ['success' => false, 'message' => 'bKash Error: ' . ($data['statusMessage'] ?? 'Execution failed.')];
                        }
                    } else {
                        $errorData = $response->json();
                        $transaction->update(['status' => 'failed', 'gateway_response' => $errorData]);
                        return ['success' => false, 'message' => 'bKash API Error: ' . ($errorData['statusMessage'] ?? $errorData['message'] ?? 'Execution failed.')];
                    }
                } catch (\Throwable $e) {
                    Log::error('bKash Execute Payment Exception: ' . $e->getMessage());
                    return ['success' => false, 'message' => 'bKash Connection Exception: ' . $e->getMessage()];
                }
            } else {
                return ['success' => false, 'message' => $tokenResult['error'] ?? 'bKash Authentication failed.'];
            }

            if (empty($finalTrxId)) {
                return ['success' => false, 'message' => 'bKash transaction ID not received. Payment was not confirmed.'];
            }

            // Update Transaction State
            $transaction->update([
                'status' => 'successful',
                'completed_at' => now(),
                'gateway_response' => $gatewayResponse,
            ]);

            // Settle Invoice accurately inside transaction
            if ($transaction->invoice) {
                app(\App\Services\Billing\InvoiceService::class)->recordPayment(
                    $transaction->invoice,
                    $transaction->amount,
                    'bKash',
                    $finalTrxId,
                    $gatewayResponse
                );
            }

            return [
                'success' => true,
                'transaction' => $transaction,
                'invoice' => $transaction->invoice,
                'trx_id' => $finalTrxId,
            ];
        });
    }

    /**
     * Create live bKash checkout session without writing unconfirmed records to DB.
     */
    public function createResellerCheckoutSession(float $amount, string $resellerCode, string $callbackUrl, ?int $tenantId = null): array
    {
        $tokenResult = $this->grantToken();
        $token = $tokenResult['token'] ?? null;

        if (!$token) {
            return [
                'success' => false,
                'message' => $tokenResult['error'] ?? 'Failed to authenticate with bKash API.',
            ];
        }

        $merchantInvoiceNumber = 'RCH-SESS-' . strtoupper(Str::random(10));

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $token,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/create", [
                    'mode' => '0011',
                    'payerReference' => $resellerCode,
                    'callbackURL' => $callbackUrl,
                    'amount' => number_format($amount, 2, '.', ''),
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $merchantInvoiceNumber,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['statusCode'] ?? '') === '0000') {
                $paymentId = $data['paymentID'] ?? null;
                $redirectUrl = $data['bkashURL'] ?? null;

                if ($paymentId && $redirectUrl) {
                    return [
                        'success' => true,
                        'payment_id' => $paymentId,
                        'redirect_url' => $redirectUrl,
                    ];
                }
            }

            $errMsg = $data['statusMessage'] ?? $data['message'] ?? 'Failed to generate bKash payment URL.';
            Log::error('bKash Reseller Recharge API Error', ['response' => $data]);
            return [
                'success' => false,
                'message' => 'bKash API Error: ' . $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('bKash Reseller Recharge Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'bKash Connection Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Backward-compatible alias for initiateResellerRecharge.
     */
    public function initiateResellerRecharge(TenantResellerRecharge $recharge, string $callbackUrl): array
    {
        return $this->createResellerCheckoutSession(
            (float) $recharge->amount,
            (string) ($recharge->reseller?->code ?? $recharge->reseller_id),
            $callbackUrl,
            $recharge->tenant_id
        );
    }

    /**
     * Execute and Capture bKash Reseller Wallet Recharge Payment.
     * ONLY writes to database AFTER bKash payment verification succeeds!
     */
    public function executeResellerPayment(string $paymentId, array|TenantResellerRecharge|null $sessionData = null, ?int $tenantId = null): array
    {
        if ($sessionData instanceof TenantResellerRecharge) {
            $tenantId = $sessionData->tenant_id;
            $resellerId = $sessionData->reseller_id;
            $amount = (float) $sessionData->amount;
            $createdBy = $sessionData->created_by;
        } else {
            $tenantId = $tenantId ?? ($sessionData['tenant_id'] ?? null);
            $resellerId = $sessionData['reseller_id'] ?? null;
            $amount = (float) ($sessionData['amount'] ?? 0);
            $createdBy = $sessionData['created_by'] ?? null;
        }

        if (!$tenantId || !$resellerId || $amount <= 0) {
            return ['success' => false, 'message' => 'Invalid or expired recharge session.'];
        }

        $reseller = TenantReseller::where('tenant_id', $tenantId)->find($resellerId);
        if (!$reseller) {
            return ['success' => false, 'message' => 'Reseller account not found.'];
        }

        // Idempotency check: verify if already settled
        $existing = TenantResellerRecharge::where('tenant_id', $tenantId)
            ->where(function ($q) use ($paymentId) {
                $q->where('gateway_trx_id', $paymentId)
                  ->orWhere('notes', 'like', "%{$paymentId}%");
            })
            ->first();

        if ($existing && $existing->status === 'APPROVED') {
            return [
                'success' => true,
                'already_processed' => true,
                'recharge' => $existing,
                'trx_id' => $existing->gateway_trx_id,
                'new_balance' => (float) $reseller->wallet_balance,
            ];
        }

        $tokenResult = $this->grantToken();
        $token = $tokenResult['token'] ?? null;

        if (!$token) {
            return ['success' => false, 'message' => $tokenResult['error'] ?? 'bKash authentication failed.'];
        }

        $finalTrxId = null;
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $token,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/execute", [
                    'paymentID' => $paymentId,
                ]);

            $data = $response->json();
            if ($response->successful() && ($data['statusCode'] ?? '') === '0000') {
                $finalTrxId = $data['trxID'] ?? null;
            } else {
                $errMsg = $data['statusMessage'] ?? 'bKash payment execution was not successful.';
                Log::warning('bKash execute failed', ['response' => $data]);
                return ['success' => false, 'message' => $errMsg];
            }
        } catch (\Throwable $e) {
            Log::error('bKash Reseller Execute Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'bKash Connection Exception: ' . $e->getMessage()];
        }

        if (empty($finalTrxId)) {
            return ['success' => false, 'message' => 'bKash transaction ID not received. Payment was not confirmed.'];
        }

        // Idempotency check with final TrxID
        $existingTrx = TenantResellerRecharge::where('tenant_id', $tenantId)
            ->where('gateway_trx_id', $finalTrxId)
            ->first();

        if ($existingTrx) {
            return [
                'success' => true,
                'already_processed' => true,
                'recharge' => $existingTrx,
                'trx_id' => $finalTrxId,
                'new_balance' => (float) $reseller->fresh()->wallet_balance,
            ];
        }

        // ONLY NOW CREATE RECHARGE & TRANSACTION IN DATABASE
        return DB::transaction(function () use ($tenantId, $reseller, $amount, $finalTrxId, $paymentId, $createdBy) {
            $rechargeNo = TenantResellerRecharge::generateRechargeNo($tenantId);

            $recharge = TenantResellerRecharge::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $reseller->id,
                'recharge_no' => $rechargeNo,
                'amount' => $amount,
                'bonus_amount' => 0.00,
                'total_credited' => $amount,
                'payment_method' => 'BKASH_MERCHANT',
                'gateway_trx_id' => $finalTrxId,
                'deposit_date' => now()->toDateString(),
                'status' => 'APPROVED',
                'approved_by' => $createdBy,
                'approved_at' => now(),
                'notes' => "Instant bKash API Checkout Clearance (PaymentID: {$paymentId}, TrxID: {$finalTrxId})",
                'created_by' => $createdBy,
            ]);

            // Increment Reseller Wallet Balance
            $balanceBefore = (float) ($reseller->wallet_balance ?? 0);
            $reseller->increment('wallet_balance', $amount);
            $balanceAfter = (float) $reseller->fresh()->wallet_balance;

            // Record in Wallet Transaction Ledger
            $wtxId = TenantResellerWalletTransaction::generateTrxId($tenantId);
            TenantResellerWalletTransaction::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $reseller->id,
                'trx_id' => $wtxId,
                'type' => 'CREDIT',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => 'BKASH_MERCHANT',
                'reference_no' => $rechargeNo,
                'description' => "bKash Merchant API Online Recharge (TrxID: {$finalTrxId})",
                'created_by' => $createdBy,
            ]);

            // Record in Central Online Gateway Transactions
            try {
                \App\Models\TenantGatewayTransaction::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'transaction_id' => 'PGW-RCH-' . $recharge->id,
                    ],
                    [
                        'gateway_trx_id' => $finalTrxId,
                        'gateway' => 'bkash',
                        'purpose' => 'RESELLER_TOPUP',
                        'customer_id' => null,
                        'reseller_id' => $reseller->id,
                        'user_id' => $createdBy,
                        'reference_id' => $rechargeNo,
                        'amount' => $amount,
                        'fee_amount' => 0.00,
                        'net_amount' => $amount,
                        'currency' => 'BDT',
                        'payer_account' => $reseller->phone,
                        'payer_name' => $reseller->name,
                        'status' => 'SUCCESS',
                        'status_message' => 'Instant bKash API Checkout Clearance',
                        'initiated_at' => now(),
                        'completed_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                // Non-blocking
            }

            return [
                'success' => true,
                'recharge' => $recharge,
                'trx_id' => $finalTrxId,
                'new_balance' => $balanceAfter,
            ];
        });
    }

    /**
     * Create generic bKash Tokenized Checkout Session for subscriber bill payment.
     */
    public function createGenericCheckoutSession(float $amount, string $reference, string $callbackUrl, string $merchantInvoicePrefix = 'INV'): array
    {
        $tokenResult = $this->grantToken();
        $token = $tokenResult['token'] ?? null;

        if (!$token) {
            return [
                'success' => false,
                'message' => $tokenResult['error'] ?? 'Failed to authenticate with bKash API.',
            ];
        }

        $merchantInvoiceNumber = $merchantInvoicePrefix . '-' . strtoupper(Str::random(10));

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $token,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/create", [
                    'mode' => '0011',
                    'payerReference' => $reference,
                    'callbackURL' => $callbackUrl,
                    'amount' => number_format($amount, 2, '.', ''),
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $merchantInvoiceNumber,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['statusCode'] ?? '') === '0000') {
                $paymentId = $data['paymentID'] ?? null;
                $redirectUrl = $data['bkashURL'] ?? null;

                if ($paymentId && $redirectUrl) {
                    return [
                        'success' => true,
                        'payment_id' => $paymentId,
                        'redirect_url' => $redirectUrl,
                    ];
                }
            }

            $errMsg = $data['statusMessage'] ?? $data['message'] ?? 'Failed to generate bKash payment URL.';
            Log::error('bKash API Error', ['response' => $data]);
            return [
                'success' => false,
                'message' => 'bKash API Error: ' . $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('bKash Checkout Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'bKash Connection Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Execute generic bKash Tokenized Payment without tying to a specific model.
     */
    public function executeTokenizedPayment(string $paymentId): array
    {
        $tokenResult = $this->grantToken();
        $token = $tokenResult['token'] ?? null;

        if (!$token) {
            return ['success' => false, 'message' => $tokenResult['error'] ?? 'bKash authentication failed.'];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => $token,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/tokenized/checkout/execute", [
                    'paymentID' => $paymentId,
                ]);

            $data = $response->json();
            if ($response->successful() && ($data['statusCode'] ?? '') === '0000') {
                return [
                    'success' => true,
                    'trx_id' => $data['trxID'] ?? null,
                    'amount' => (float) ($data['amount'] ?? 0),
                    'data' => $data,
                ];
            } else {
                $errMsg = $data['statusMessage'] ?? 'bKash payment execution was not successful.';
                Log::warning('bKash execute failed', ['response' => $data]);
                return ['success' => false, 'message' => $errMsg, 'data' => $data];
            }
        } catch (\Throwable $e) {
            Log::error('bKash Execute Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'bKash Connection Exception: ' . $e->getMessage()];
        }
    }
}
