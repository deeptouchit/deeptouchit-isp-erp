<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class OwnerPaymentGatewayController extends Controller
{
    public function index()
    {
        $settings = Setting::getGroup('payment_gateways');

        // Calculate dynamic payment statistics
        $activeGatewaysCount = 0;
        $gatewayKeys = [
            'bkash_status',
            'nagad_status',
            'ssl_status',
            'aamarpay_status',
            'shurjopay_status',
            'stripe_status',
            'bank_status',
            'mfs_manual_status',
        ];

        foreach ($gatewayKeys as $key) {
            if (($settings[$key] ?? '0') == '1') {
                $activeGatewaysCount++;
            }
        }

        // Count live vs sandbox
        $liveCount = 0;
        $sandboxCount = 0;
        $modes = [
            $settings['bkash_mode'] ?? 'sandbox',
            $settings['nagad_mode'] ?? 'sandbox',
            $settings['ssl_mode'] ?? 'sandbox',
            $settings['aamarpay_mode'] ?? 'sandbox',
            $settings['shurjopay_mode'] ?? 'sandbox',
            $settings['stripe_mode'] ?? 'test',
        ];
        foreach ($modes as $m) {
            if ($m === 'live') {
                $liveCount++;
            } else {
                $sandboxCount++;
            }
        }

        foreach (config('secure_settings.keys', []) as $secureKey) {
            if (array_key_exists($secureKey, $settings) && $settings[$secureKey] !== '') {
                $settings[$secureKey] = '••••••••••••';
            }
        }

        // Paid Invoices Stats
        $paidInvoicesTotal = SaasInvoice::where('status', 'paid')->sum('amount');
        $paidInvoicesCount = SaasInvoice::where('status', 'paid')->count();

        // Real gateway transaction audit data; never show fabricated production records.
        $auditLogs = PaymentTransaction::withoutWalletCheckouts()
            ->with(['tenant', 'invoice.plan'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (PaymentTransaction $transaction) {
                $fee = (float) data_get($transaction->gateway_response, 'fee', 0);
                return [
                    'trx_id' => $transaction->transaction_reference,
                    'tenant' => $transaction->tenant?->company_name ?? $transaction->tenant?->name ?? '—',
                    'plan' => $transaction->invoice?->plan?->name ?? '—',
                    'gateway' => $transaction->gateway ?: 'Online',
                    'gateway_code' => strtolower((string) $transaction->gateway),
                    'amount' => (float) $transaction->amount,
                    'fee' => $fee,
                    'net' => max(0, (float) $transaction->amount - $fee),
                    'currency' => $settings['currency_code'] ?? 'BDT',
                    'status' => $transaction->status,
                    'mode' => data_get($transaction->gateway_response, 'mode', 'live'),
                    'timestamp' => optional($transaction->created_at)->format('M d, Y - h:i A'),
                ];
            })
            ->values()
            ->all();

        return view('owner.payment-gateways.index', compact(
            'settings',
            'activeGatewaysCount',
            'liveCount',
            'sandboxCount',
            'paidInvoicesTotal',
            'paidInvoicesCount',
            'auditLogs'
        ));
    }

    public function update(Request $request)
    {
        $allowedKeys = [
            'bkash_mode', 'bkash_status', 'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_fee_percent',
            'nagad_mode', 'nagad_status', 'nagad_merchant_id', 'nagad_public_key', 'nagad_private_key', 'nagad_fee_percent',
            'ssl_mode', 'ssl_status', 'ssl_store_id', 'ssl_store_password', 'ssl_fee_percent',
            'aamarpay_mode', 'aamarpay_status', 'aamarpay_store_id', 'aamarpay_signature_key', 'aamarpay_fee_percent',
            'shurjopay_mode', 'shurjopay_status', 'shurjopay_username', 'shurjopay_password', 'shurjopay_prefix', 'shurjopay_fee_percent',
            'stripe_mode', 'stripe_status', 'stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_currency', 'stripe_fee_percent',
            'bank_status', 'bank_name', 'bank_account_name', 'bank_account_number', 'bank_routing_number', 'bank_instructions',
            'mfs_manual_status', 'mfs_bkash_personal', 'mfs_nagad_personal', 'mfs_rocket_personal', 'mfs_upay_personal',
            'currency_code', 'currency_symbol', 'pgw_grace_period_days', 'pgw_auto_activate_license', 'pgw_pass_fee_to_tenant',
            'pgw_email_receipt_tenant', 'pgw_sms_receipt_tenant', 'pgw_sandbox_mode',
        ];
        $inputs = $request->only($allowedKeys);
        $request->validate([
            'bkash_mode' => ['nullable', Rule::in(['sandbox', 'live'])],
            'nagad_mode' => ['nullable', Rule::in(['sandbox', 'live'])],
            'ssl_mode' => ['nullable', Rule::in(['sandbox', 'live'])],
            'aamarpay_mode' => ['nullable', Rule::in(['sandbox', 'live'])],
            'shurjopay_mode' => ['nullable', Rule::in(['sandbox', 'live'])],
            'stripe_mode' => ['nullable', Rule::in(['test', 'live'])],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'currency_symbol' => ['nullable', 'string', 'max:8'],
            'pgw_grace_period_days' => ['nullable', 'integer', 'between:0,365'],
        ]);

        // Checkbox states to handle
        $checkboxes = [
            'bkash_status',
            'nagad_status',
            'ssl_status',
            'aamarpay_status',
            'shurjopay_status',
            'stripe_status',
            'bank_status',
            'mfs_manual_status',
            'pgw_auto_activate_license',
            'pgw_pass_fee_to_tenant',
            'pgw_sandbox_mode',
            'pgw_email_receipt_tenant',
            'pgw_sms_receipt_tenant',
        ];

        foreach ($checkboxes as $cb) {
            $inputs[$cb] = $request->has($cb) ? '1' : '0';
        }

        foreach ($inputs as $key => $value) {
            if (Setting::isSecureKey($key) && ($value === '' || $value === '••••••••••••')) {
                unset($inputs[$key]);
                continue;
            }
            Setting::set($key, (string)$value, 'payment_gateways', null);
        }

        return back()->with('success', 'Payment gateway configurations and policies saved successfully.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'gateway' => ['required', Rule::in(['bkash', 'nagad', 'sslcommerz', 'aamarpay', 'shurjopay', 'stripe'])],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999.99'],
        ]);

        $gateway = $request->input('gateway');
        $amount = (float)$request->input('amount');
        $settings = Setting::getGroup('payment_gateways');

        $startTime = microtime(true);
        $result = [
            'success' => true,
            'gateway' => $gateway,
            'amount' => $amount,
            'currency' => $settings['currency_code'] ?? 'BDT',
            'status_code' => 200,
            'latency_ms' => 0,
            'endpoint' => '',
            'mode' => 'sandbox',
            'details' => [],
            'message' => '',
        ];

        try {
            switch ($gateway) {
                case 'bkash':
                    $mode = $settings['bkash_mode'] ?? 'sandbox';
                    $url = $mode === 'live' 
                        ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' 
                        : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant';
                    
                    $appKey = $settings['bkash_app_key'] ?? '';
                    $appSecret = $settings['bkash_app_secret'] ?? '';

                    $result['endpoint'] = $url;
                    $result['mode'] = $mode;

                    if (empty($appKey) || empty($appSecret)) {
                        $result['success'] = false;
                        $result['status_code'] = 422;
                        $result['message'] = 'bKash credentials are not configured; no live test was performed.';
                    } else {
                        $response = Http::timeout(10)->withHeaders([
                            'username' => $settings['bkash_username'] ?? '',
                            'password' => $settings['bkash_password'] ?? '',
                        ])->post($url, [
                            'app_key' => $appKey,
                            'app_secret' => $appSecret,
                        ]);

                        $result['status_code'] = $response->status();
                        $result['details'] = $response->json() ?: $response->body();
                        $result['success'] = $response->successful();
                        $result['message'] = $response->successful() ? 'bKash Token Grant Successful' : 'bKash Handshake Failed';
                    }
                    break;

                case 'nagad':
                    $mode = $settings['nagad_mode'] ?? 'sandbox';
                    $url = $mode === 'live'
                        ? 'https://api.mynagad.com/api/dfs/check-out/initialize/'
                        : 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/check-out/initialize/';
                    
                    $result['endpoint'] = $url;
                    $result['mode'] = $mode;
                    $result['success'] = false;
                    $result['status_code'] = 501;
                    $result['message'] = 'Nagad live verification is not configured; no successful handshake was recorded.';
                    break;

                case 'sslcommerz':
                    $mode = $settings['ssl_mode'] ?? 'sandbox';
                    $url = $mode === 'live'
                        ? 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'
                        : 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                    
                    $storeId = $settings['ssl_store_id'] ?? '';
                    $storePass = $settings['ssl_store_password'] ?? '';

                    $result['endpoint'] = $url;
                    $result['mode'] = $mode;

                    if (empty($storeId) || empty($storePass)) {
                        $result['success'] = false;
                        $result['status_code'] = 422;
                        $result['message'] = 'SSLCommerz credentials are not configured; no live test was performed.';
                    } else {
                        $postData = [
                            'store_id' => $storeId,
                            'store_passwd' => $storePass,
                            'total_amount' => $amount,
                            'currency' => 'BDT',
                            'tran_id' => 'TEST_' . uniqid(),
                            'success_url' => url('/api/payment/sslcommerz/success'),
                            'fail_url' => url('/api/payment/sslcommerz/fail'),
                            'cancel_url' => url('/api/payment/sslcommerz/cancel'),
                            'cus_name' => 'SomitySoft Test ISP',
                            'cus_email' => 'tenant@somitysoft.com',
                            'cus_add1' => 'Dhaka',
                            'cus_phone' => '01700000000',
                            'shipping_method' => 'NO',
                            'product_name' => 'SaaS Subscription Plan',
                            'product_category' => 'Software',
                            'product_profile' => 'non-physical-goods',
                        ];

                        $response = Http::asForm()->timeout(10)->post($url, $postData);
                        $resData = $response->json();
                        $result['status_code'] = $response->status();
                        $result['details'] = $resData ?: $response->body();
                        $result['success'] = isset($resData['status']) && $resData['status'] === 'SUCCESS';
                        $result['message'] = $result['success'] ? 'SSLCommerz Session Initialized' : 'SSLCommerz Initialization Failed';
                    }
                    break;

                case 'aamarpay':
                    $mode = $settings['aamarpay_mode'] ?? 'sandbox';
                    $url = $mode === 'live'
                        ? 'https://secure.aamarpay.com/jsonpost.php'
                        : 'https://sandbox.aamarpay.com/jsonpost.php';
                    
                    $result['endpoint'] = $url;
                    $result['mode'] = $mode;
                    $result['success'] = false;
                    $result['status_code'] = 501;
                    $result['message'] = 'AamarPay live verification is not configured; no payment request was generated.';
                    break;

                case 'stripe':
                    $mode = $settings['stripe_mode'] ?? 'test';
                    $result['endpoint'] = 'https://api.stripe.com/v1/payment_intents';
                    $result['mode'] = $mode;
                    $result['success'] = false;
                    $result['status_code'] = 501;
                    $result['message'] = 'Stripe live verification is not configured; no PaymentIntent was created.';
                    break;

                default:
                    $result['success'] = false;
                    $result['message'] = 'Unsupported gateway provider selected.';
                    break;
            }
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['status_code'] = 500;
            report($e);
            $result['message'] = 'Gateway test failed. Check the server logs for details.';
            $result['details'] = [];
        }

        $result['latency_ms'] = round((microtime(true) - $startTime) * 1000);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('test_result', $result)->with('success', 'Test Handshake Completed: ' . $result['message']);
        } else {
            return back()->with('test_result', $result)->with('error', 'Test Handshake Failed: ' . $result['message']);
        }
    }
}
