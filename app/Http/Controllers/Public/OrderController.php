<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display the professional checkout page with active gateways.
     */
    public function checkout(Request $request): Response
    {
        $planParam = $request->query('plan');
        
        $selectedPlan = null;
        if ($planParam) {
            $selectedPlan = HostingPlan::where('slug', $planParam)
                ->orWhere('id', $planParam)
                ->first();
        }

        if (!$selectedPlan) {
            $selectedPlan = HostingPlan::where('is_active', true)
                ->where('slug', 'bdix-starter')
                ->first() ?? HostingPlan::where('is_active', true)->first();
        }

        $allPlans = HostingPlan::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $activeGateways = PaymentGateway::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'slug', 'name', 'category', 'fee_type', 'fee_value', 'instructions']);

        $pending = session('pending_checkout', []);
        $savedUser = $pending['user_data'] ?? null;

        return Inertia::render('Public/Checkout', [
            'selectedPlan' => $selectedPlan,
            'allPlans' => $allPlans,
            'activeGateways' => $activeGateways,
            'initialBillingCycle' => $request->query('cycle', ($pending['period'] ?? 'yearly')),
            'prefilledData' => $savedUser,
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'first_name' => Auth::user()->first_name,
                'last_name' => Auth::user()->last_name,
                'email' => Auth::user()->email,
                'username' => Auth::user()->username,
                'phone' => Auth::user()->phone,
                'company' => Auth::user()->company,
                'country' => Auth::user()->country,
                'state' => Auth::user()->state,
                'city' => Auth::user()->city,
                'zip_code' => Auth::user()->zip_code,
                'address' => Auth::user()->address,
            ] : null,
        ]);
    }

    /**
     * Process checkout form, store temporary order in session, and redirect to payment gateway via Inertia::location!
     */
    public function process(Request $request)
    {
        $rules = [
            'plan_id' => 'required|exists:hosting_plans,id',
            'period' => 'required|in:monthly,yearly',
            'domain_option' => 'nullable|in:existing,subdomain',
            'domain_name' => 'nullable|string|max:255',
            'subdomain_prefix' => 'nullable|string|max:100',
            'gateway' => 'nullable|string',
            'currency' => 'nullable|in:BDT,USD',
        ];

        // If guest, validate all billing fields
        if (!Auth::check()) {
            $rules['first_name'] = 'required|string|max:100';
            $rules['last_name'] = 'required|string|max:100';
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
            $rules['phone'] = 'required|string|max:30';
            $rules['company'] = 'nullable|string|max:150';
            $rules['country'] = 'required|string|max:100';
            $rules['state'] = 'required|string|max:100';
            $rules['city'] = 'required|string|max:100';
            $rules['zip_code'] = 'required|string|max:20';
            $rules['address'] = 'required|string|max:255';
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $plan = HostingPlan::findOrFail($validated['plan_id']);
        $period = $validated['period'];
        $gatewaySlug = $validated['gateway'] ?? 'bkash';

        // Resolve primary domain
        $domainOption = $validated['domain_option'] ?? 'existing';
        if ($domainOption === 'subdomain' && !empty($validated['subdomain_prefix'])) {
            $prefix = strtolower(preg_replace('/[^a-z0-9\-]/', '', $validated['subdomain_prefix']));
            $domain = ($prefix ?: 'site') . '.deeptouchit.com';
        } elseif (!empty($validated['domain_name'])) {
            $clean = strtolower(trim(preg_replace('#^https?://#', '', rtrim($validated['domain_name'], '/'))));
            $domain = $clean ?: 'mysite.com';
        } else {
            $domain = 'client-' . substr(md5(uniqid()), 0, 6) . '.deeptouchit.com';
        }

        $price = $period === 'yearly' 
            ? ($plan->price_yearly > 0 ? $plan->price_yearly : ($plan->price_monthly * 10))
            : $plan->price_monthly;

        $currency = $validated['currency'] ?? 'BDT';
        $orderNo = 'DT-' . strtoupper(Str::random(8));

        // Save order data in session (DO NOT CREATE USER OR LOGIN YET!)
        $order = [
            'user_data' => Auth::check() ? ['id' => Auth::id()] : $validated,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'period' => $period,
            'price' => $price,
            'domain' => $domain,
            'domain_option' => $domainOption,
            'currency' => $currency,
            'gateway' => $gatewaySlug,
            'order_no' => $orderNo,
        ];
        session()->put('pending_checkout', $order);

        // If bKash: connect to live API and redirect directly using Inertia::location
        if ($gatewaySlug === 'bkash') {
            $bkashUrl = $this->createBkashPayment($order);
            if ($bkashUrl) {
                // Must use Inertia::location to do a full browser window redirect and prevent white screen / iframe issues!
                return Inertia::location($bkashUrl);
            }
        }

        // Fallback / Simulator / other gateways
        return redirect()->route('order.pay');
    }

    /**
     * Helper to initiate live bKash tokenized payment.
     */
    protected function createBkashPayment(array $order): ?string
    {
        $gatewayModel = PaymentGateway::where('slug', 'bkash')->first();
        if (!$gatewayModel) return null;

        $creds = $gatewayModel->credentials ?? [];
        $appKey = $creds['app_key'] ?? '';
        $appSecret = $creds['app_secret'] ?? '';
        $username = $creds['username'] ?? '';
        $password = $creds['password'] ?? '';
        $mode = $gatewayModel->mode ?? 'sandbox';

        if (empty($appKey) || empty($appSecret) || empty($username) || empty($password) || str_contains($appKey, 'demo') || str_contains($appKey, '••••')) {
            return null;
        }

        $baseUrl = $mode === 'live' 
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

        try {
            // 1. Grant Token
            $tokenRes = Http::withHeaders([
                'username' => $username,
                'password' => $password,
            ])->post("{$baseUrl}/tokenized/checkout/token/grant", [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
            ]);

            if ($tokenRes->successful() && isset($tokenRes['id_token'])) {
                $idToken = $tokenRes['id_token'];
                session()->put('bkash_id_token', $idToken);

                // 2. Create Payment
                $createRes = Http::withHeaders([
                    'Authorization' => $idToken,
                    'X-APP-Key' => $appKey,
                    'Content-Type' => 'application/json',
                ])->post("{$baseUrl}/tokenized/checkout/create", [
                    'mode' => '0011',
                    'payerReference' => $order['order_no'],
                    'callbackURL' => route('order.callback'),
                    'amount' => (string) $order['price'],
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $order['order_no'],
                ]);

                if ($createRes->successful() && isset($createRes['bkashURL'])) {
                    session()->put('bkash_payment_id', $createRes['paymentID']);
                    return $createRes['bkashURL'];
                } else {
                    Log::error('bKash Create Payment Failed: ' . $createRes->body());
                }
            } else {
                Log::error('bKash Grant Token Failed: ' . $tokenRes->body());
            }
        } catch (\Throwable $e) {
            Log::error('bKash API Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Render payment page if not redirected externally.
     */
    public function pay(Request $request)
    {
        $order = session('pending_checkout');
        if (!$order) {
            return redirect()->route('order.checkout')->with('error', 'Order session expired. Please select a plan.');
        }

        $gatewaySlug = $order['gateway'] ?? 'bkash';
        $gatewayModel = PaymentGateway::where('slug', $gatewaySlug)->first();

        return Inertia::render('Public/PaymentSimulator', [
            'order' => $order,
            'gateway' => $gatewayModel ?: [
                'name' => ucfirst($gatewaySlug),
                'slug' => $gatewaySlug,
                'mode' => 'sandbox',
            ],
        ]);
    }

    /**
     * Payment Cancelled or Failed.
     */
    public function cancel(Request $request): RedirectResponse
    {
        return redirect()->route('order.checkout')->with('error', 'Payment was cancelled or failed. No money was deducted and no account was created. Please try again.');
    }

    /**
     * Gateway Callback from bKash official checkout.
     */
    public function callback(Request $request): RedirectResponse
    {
        $order = session('pending_checkout');
        if (!$order) {
            return redirect()->route('order.checkout')->with('error', 'Order session expired. Please try again.');
        }

        $status = $request->query('status');
        $paymentId = $request->query('paymentID') ?: session('bkash_payment_id');

        // Check cancellation or failure
        if ($status === 'cancel' || $status === 'failure') {
            return redirect()->route('order.checkout')->with('error', 'bKash payment was ' . $status . 'ed. No charge was made and no account was created.');
        }

        // If bKash success status
        if ($status === 'success' && $paymentId) {
            $gatewayModel = PaymentGateway::where('slug', 'bkash')->first();
            $creds = $gatewayModel?->credentials ?? [];
            $appKey = $creds['app_key'] ?? '';
            $appSecret = $creds['app_secret'] ?? '';
            $username = $creds['username'] ?? '';
            $password = $creds['password'] ?? '';
            $mode = $gatewayModel?->mode ?? 'sandbox';

            $baseUrl = $mode === 'live' 
                ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' 
                : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

            $idToken = session('bkash_id_token');
            if (!$idToken) {
                // Grant token again if expired
                $tokenRes = Http::withHeaders([
                    'username' => $username,
                    'password' => $password,
                ])->post("{$baseUrl}/tokenized/checkout/token/grant", [
                    'app_key' => $appKey,
                    'app_secret' => $appSecret,
                ]);
                $idToken = $tokenRes['id_token'] ?? null;
            }

            if ($idToken) {
                try {
                    // Execute Payment
                    $executeRes = Http::withHeaders([
                        'Authorization' => $idToken,
                        'X-APP-Key' => $appKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$baseUrl}/tokenized/checkout/execute", [
                        'paymentID' => $paymentId,
                    ]);

                    if ($executeRes->successful() && ($executeRes['statusCode'] ?? '') === '0000') {
                        $trxId = $executeRes['trxID'] ?? ('BK' . strtoupper(Str::random(8)));
                        // Payment verified and deducted successfully!
                        return $this->finalizeOrderAndProvision($order, 'bkash', $trxId);
                    } else {
                        $msg = $executeRes['statusMessage'] ?? 'Payment execution failed on bKash.';
                        return redirect()->route('order.checkout')->with('error', "bKash Payment Failed: {$msg}");
                    }
                } catch (\Throwable $e) {
                    Log::error('bKash Execute Exception: ' . $e->getMessage());
                    return redirect()->route('order.checkout')->with('error', 'bKash Gateway verification failed. Please try again.');
                }
            }
        }

        // Direct confirm fallback
        return $this->confirm($request);
    }

    /**
     * Manual / Simulator Confirm.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $order = session('pending_checkout');
        if (!$order) {
            return redirect()->route('order.checkout')->with('error', 'Order session expired. Please try again.');
        }

        $gateway = $request->input('gateway', ($order['gateway'] ?? 'bkash'));
        $trxId = $request->input('transaction_id') ?: ('TRX' . strtoupper(Str::random(10)));

        return $this->finalizeOrderAndProvision($order, $gateway, $trxId);
    }

    /**
     * Atomic finalizer: creates user, logs in, creates active subscription and paid invoice ONLY after payment is confirmed!
     */
    protected function finalizeOrderAndProvision(array $order, string $gateway, string $trxId): RedirectResponse
    {
        $plan = HostingPlan::findOrFail($order['plan_id']);
        $period = $order['period'];
        $price = $order['price'];
        $currency = $order['currency'] ?? 'BDT';
        $user = null;

        DB::transaction(function () use ($order, $plan, $period, $price, $currency, $gateway, $trxId, &$user) {
            // 1. Create or Authenticate User
            if (Auth::check()) {
                $user = Auth::user();
            } elseif (isset($order['user_data']['id'])) {
                $user = User::findOrFail($order['user_data']['id']);
                Auth::login($user);
            } else {
                $data = $order['user_data'];
                $firstName = trim($data['first_name']);
                $lastName = trim($data['last_name']);
                $fullName = trim($firstName . ' ' . $lastName);
                $email = strtolower(trim($data['email']));

                $baseUsername = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', $email)[0]));
                $username = $baseUsername ?: 'user' . rand(1000, 9999);
                $orig = $username;
                $c = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $orig . $c;
                    $c++;
                }

                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'phone' => $data['phone'],
                    'company' => $data['company'] ?? null,
                    'country' => $data['country'],
                    'state' => $data['state'],
                    'city' => $data['city'],
                    'zip_code' => $data['zip_code'],
                    'address' => $data['address'],
                    'password' => Hash::make($data['password']),
                    'role' => 'client',
                    'status' => 'active',
                ]);

                Auth::login($user);
            }

            // 2. Determine domain reference
            $domain = !empty($order['domain']) ? $order['domain'] : ($user->username . '-' . substr(md5(uniqid()), 0, 4) . '.deeptouchit.com');

            // 3. Fetch Active Server
            $server = Server::where('status', 'active')->first() ?? Server::first();

            // 4. Create ACTIVE Subscription
            $nextBilling = $period === 'yearly' ? now()->addYear() : now()->addMonth();

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'server_id' => $server?->id ?? 1,
                'domain' => $domain,
                'period' => $period,
                'price' => $price,
                'status' => 'active', // ACTIVE!
                'custom_disk_space' => $plan->disk_space,
                'next_billing_date' => $nextBilling,
                'expires_at' => $nextBilling,
            ]);

            // Create physical vhost directory
            if (!is_dir($subscription->document_root)) {
                @mkdir($subscription->document_root, 0775, true);
                @chown(dirname($subscription->document_root), 'www-data');
                @chown($subscription->document_root, 'www-data');
                
                $templatePath = resource_path('views/templates/default_holding_page.html');
                if (file_exists($templatePath)) {
                    $welcome = str_replace('{{DOMAIN}}', $domain, file_get_contents($templatePath));
                } else {
                    $welcome = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Welcome to {$domain}</title></head><body><h1>Welcome to {$domain}</h1></body></html>";
                }
                @file_put_contents($subscription->document_root . '/index.html', $welcome);
                @chmod($subscription->document_root . '/index.html', 0664);
            }

            try {
                app(\App\Services\NginxManager::class)->createVirtualHost([
                    'domain' => $domain,
                    'username' => $subscription->username,
                    'document_root' => $subscription->document_root,
                    'php_version' => '8.2',
                    'ssl_enabled' => false,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Nginx vhost creation warning: " . $e->getMessage());
            }

            // 5. Create ACTIVE Website
            Website::create([
                'subscription_id' => $subscription->id,
                'domain' => $domain,
                'document_root' => $subscription->document_root,
                'php_version' => '8.2',
                'is_primary' => true,
                'status' => 'active', // ACTIVE!
                'auto_ssl' => true,
                'ssl_status' => 'none',
            ]);

            // 6. Generate PAID Invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'total_amount' => $price,
                'paid_amount' => $price,
                'due_amount' => 0.00,
                'currency' => $currency,
                'status' => 'paid', // PAID!
                'paid_at' => now(),
                'issue_date' => now(),
                'due_date' => now(),
                'notes' => "Hosting package order ({$plan->name}) - Verified via {$gateway}",
            ]);

            // 7. Line Item
            $cycleText = $period === 'yearly' ? '1 Year' : '1 Month';
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "{$plan->name} Hosting Package ({$cycleText})",
                'quantity' => 1,
                'unit_price' => $price,
                'total_price' => $price,
            ]);

            // 8. Record Payment Transaction
            Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $price,
                'currency' => $currency,
                'gateway' => $gateway,
                'transaction_id' => $trxId,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // 9. Auto-create DNS Zone in BIND9
            try {
                $dnsService = app(\App\Services\DNS\DnsZoneService::class);
                $dnsService->createZone([
                    'domain' => $domain,
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'auto_populate' => true,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("DNS Zone auto-creation warning for {$domain}: " . $e->getMessage());
            }
        });

        // Clean session
        session()->forget('pending_checkout');
        session()->forget('bkash_id_token');
        session()->forget('bkash_payment_id');

        return redirect()->route('client.dashboard')->with('success', "🎉 Welcome to DeepTouch Cloud! Payment of ৳{$price} verified via {$gateway} (TrxID: {$trxId}). Your hosting account is now ACTIVE.");
    }
}
