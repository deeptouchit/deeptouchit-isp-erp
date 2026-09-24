<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Initiate payment for the invoice.
     */
    public function initiate(Request $request, Invoice $invoice)
    {
        // Don't re-pay if already paid
        if ($invoice->status === 'paid') {
            return redirect()->route('client.dashboard')->with('success', 'This invoice has already been paid.');
        }

        $gatewaySlug = $request->input('gateway', 'bkash');
        $gatewayModel = PaymentGateway::where('slug', $gatewaySlug)->first();

        // 1. bKash Tokenized Checkout
        if ($gatewaySlug === 'bkash') {
            $creds = $gatewayModel?->credentials ?? [];
            $appKey = $creds['app_key'] ?? '';
            $appSecret = $creds['app_secret'] ?? '';
            $username = $creds['username'] ?? '';
            $password = $creds['password'] ?? '';
            $mode = $gatewayModel?->mode ?? 'sandbox';

            // Check if real live/sandbox credentials are configured
            if (!empty($appKey) && !empty($appSecret) && !empty($username) && !empty($password) && !str_contains($appKey, 'demo') && !str_contains($appKey, '••••')) {
                $baseUrl = $mode === 'live' 
                    ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' 
                    : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

                try {
                    $tokenRes = Http::withHeaders([
                        'username' => $username,
                        'password' => $password,
                    ])->post("{$baseUrl}/tokenized/checkout/token/grant", [
                        'app_key' => $appKey,
                        'app_secret' => $appSecret,
                    ]);

                    if ($tokenRes->successful() && isset($tokenRes['id_token'])) {
                        $idToken = $tokenRes['id_token'];
                        $createRes = Http::withHeaders([
                            'Authorization' => $idToken,
                            'X-APP-Key' => $appKey,
                            'Content-Type' => 'application/json',
                        ])->post("{$baseUrl}/tokenized/checkout/create", [
                            'mode' => '0011',
                            'payerReference' => $invoice->invoice_no,
                            'callbackURL' => route('billing.callback', ['invoice' => $invoice->id, 'gateway' => 'bkash']),
                            'amount' => (string) $invoice->due_amount,
                            'currency' => 'BDT',
                            'intent' => 'sale',
                            'merchantInvoiceNumber' => $invoice->invoice_no,
                        ]);

                        if ($createRes->successful() && isset($createRes['bkashURL'])) {
                            return redirect()->away($createRes['bkashURL']);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('bKash API Gateway Error: ' . $e->getMessage());
                }
            }

            // Interactive bKash PGW Portal / Payment Page
            return Inertia::render('Public/PaymentSimulator', [
                'invoice' => $invoice->load('items', 'user', 'subscription.plan'),
                'gateway' => $gatewayModel ?: [
                    'name' => 'bKash Merchant PGW',
                    'slug' => 'bkash',
                    'mode' => $gatewayModel?->mode ?? 'sandbox',
                ],
            ]);
        }

        // 2. SSLCommerz Multi-Card
        if ($gatewaySlug === 'sslcommerz') {
            $creds = $gatewayModel?->credentials ?? [];
            $storeId = $creds['store_id'] ?? '';
            $storePassword = $creds['store_password'] ?? '';
            $mode = $gatewayModel?->mode ?? 'sandbox';

            if (!empty($storeId) && !empty($storePassword) && !str_contains($storeId, 'live') && !str_contains($storeId, '••••')) {
                $postData = [
                    'store_id' => $storeId,
                    'store_passwd' => $storePassword,
                    'total_amount' => $invoice->due_amount,
                    'currency' => $invoice->currency ?: 'BDT',
                    'tran_id' => 'TXN-' . $invoice->id . '-' . time(),
                    'success_url' => route('billing.callback', ['invoice' => $invoice->id, 'gateway' => 'sslcommerz']),
                    'fail_url' => route('billing.invoice.show', $invoice->id),
                    'cancel_url' => route('billing.invoice.show', $invoice->id),
                    'cus_name' => $invoice->user?->name ?: 'Client',
                    'cus_email' => $invoice->user?->email,
                    'cus_phone' => $invoice->user?->phone ?: '01700000000',
                ];

                $directApiUrl = $mode === 'live' 
                    ? "https://seamless-epay.sslcommerz.com/gwprocess/v4/api.php" 
                    : "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";

                try {
                    $response = Http::asForm()->post($directApiUrl, $postData);
                    $sslcz = $response->json();
                    if (!empty($sslcz['GatewayPageURL'])) {
                        return redirect()->away($sslcz['GatewayPageURL']);
                    }
                } catch (\Throwable $e) {
                    Log::error('SSLCommerz Error: ' . $e->getMessage());
                }
            }

            return Inertia::render('Public/PaymentSimulator', [
                'invoice' => $invoice->load('items', 'user', 'subscription.plan'),
                'gateway' => $gatewayModel ?: [
                    'name' => 'SSLCommerz Gateway',
                    'slug' => 'sslcommerz',
                    'mode' => 'sandbox',
                ],
            ]);
        }

        // 3. Fallback / Other Gateways
        return Inertia::render('Public/PaymentSimulator', [
            'invoice' => $invoice->load('items', 'user', 'subscription.plan'),
            'gateway' => $gatewayModel ?: [
                'name' => ucfirst($gatewaySlug),
                'slug' => $gatewaySlug,
                'mode' => 'sandbox',
            ],
        ]);
    }

    /**
     * Process Gateway Return Callback.
     */
    public function callback(Request $request, Invoice $invoice)
    {
        $gateway = $request->query('gateway', 'bkash');
        return $this->confirm($request, $invoice);
    }

    /**
     * Complete and activate subscription upon payment confirmation.
     */
    public function confirm(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('client.dashboard')->with('success', 'Your subscription is already active.');
        }

        $gateway = $request->input('gateway', 'bkash');
        $trxId = $request->input('transaction_id') ?: ('TRX' . strtoupper(Str::random(10)));

        DB::transaction(function () use ($invoice, $gateway, $trxId) {
            // 1. Mark Invoice as Paid
            $invoice->update([
                'status' => 'paid',
                'paid_amount' => $invoice->total_amount,
                'due_amount' => 0.00,
                'paid_at' => now(),
            ]);

            // 2. Record Payment
            Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency ?: 'BDT',
                'gateway' => $gateway,
                'transaction_id' => $trxId,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // 3. Activate Subscription & Website upon payment!
            if ($invoice->subscription_id) {
                $subscription = $invoice->subscription;
                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        'next_billing_date' => $subscription->period === 'yearly' ? now()->addYear() : now()->addMonth(),
                        'expires_at' => $subscription->period === 'yearly' ? now()->addYear() : now()->addMonth(),
                    ]);

                    Website::where('subscription_id', $subscription->id)->update([
                        'status' => 'active',
                    ]);
                }
            }
        });

        return redirect()->route('client.dashboard')->with('success', "🎉 Payment of ৳{$invoice->total_amount} received successfully! Your hosting package is now fully ACTIVE.");
    }
}
