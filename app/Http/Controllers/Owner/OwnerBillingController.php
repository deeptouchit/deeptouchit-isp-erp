<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantWallet;
use App\Models\TenantWalletTransaction;
use App\Models\Setting;
use App\Services\Billing\BillingReminderService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\RenewalService;
use App\Services\Billing\SubscriptionService;
use App\Services\Billing\SuspensionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\SaasInvoiceMail;

class OwnerBillingController extends Controller
{
    /**
     * Display comprehensive platform financials, subscriptions, invoices, payment transactions & wallets.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $activeTab = $request->get('tab', 'invoices');

        // 1. Calculate Financial Key Metrics
        $totalRevenue = (float) SaasInvoice::where('status', 'paid')->sum('amount');
        
        $thisMonthRevenue = (float) SaasInvoice::where('status', 'paid')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                  });
            })
            ->sum('amount');

        $pendingRevenue = (float) SaasInvoice::where('status', 'unpaid')->sum('amount');

        $overdueCount = SaasInvoice::where('status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today->toDateString())
            ->count();

        $totalInvoicesCount = SaasInvoice::count();

        $estimatedMrr = (float) Tenant::where('tenants.status', 'active')
            ->join('saas_plans', 'tenants.saas_plan_id', '=', 'saas_plans.id')
            ->sum('saas_plans.monthly_price');

        // Subscription lifecycle metrics
        $activeSubsCount = TenantSubscription::where('status', 'active')->count();
        $gracePeriodCount = TenantSubscription::where('status', 'grace_period')->count();
        $suspendedSubsCount = TenantSubscription::where('status', 'suspended')->count();
        $trialSubsCount = TenantSubscription::where('status', 'trial')->count();
        $autoRenewCount = TenantSubscription::where('auto_renew', true)->count();

        // 2. Query Builder for Invoices
        $query = SaasInvoice::with(['tenant', 'plan', 'items']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('trx_id', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->where('status', 'unpaid')
                      ->whereNotNull('due_date')
                      ->where('due_date', '<', $today->toDateString());
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Date Filtering
        if ($request->filled('date_preset')) {
            switch ($request->date_preset) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'this_month':
                    $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;
                case 'last_month':
                    $query->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
                    break;
                case 'this_year':
                    $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
                    break;
            }
        } elseif ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay(),
            ]);
        }

        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount', 'asc');
                break;
            case 'due_date_asc':
                $query->orderByRaw('due_date IS NULL, due_date ASC');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $perPage = in_array((int)$request->get('per_page', 15), [10, 15, 25, 50, 100]) ? (int)$request->get('per_page', 15) : 15;
        $invoices = $query->paginate($perPage, ['*'], 'invoices_page')->withQueryString();

        // 3. Subscriptions Query
        $subQuery = TenantSubscription::with(['tenant', 'plan'])->latest();
        if ($request->filled('sub_status')) {
            $subQuery->where('status', $request->sub_status);
        }
        $subscriptions = $subQuery->paginate(15, ['*'], 'subs_page')->withQueryString();

        // 4. Payment Transactions Query
        $trxQuery = PaymentTransaction::with(['tenant', 'invoice'])->latest();
        if ($request->filled('trx_gateway')) {
            $trxQuery->where('gateway', $request->trx_gateway);
        }
        if ($request->filled('trx_status')) {
            $trxQuery->where('status', $request->trx_status);
        }
        $transactions = $trxQuery->paginate(15, ['*'], 'trx_page')->withQueryString();

        // 5. Tenant Wallets Query
        $wallets = TenantWallet::with(['tenant', 'transactions' => fn($q) => $q->latest()->take(5)])
            ->orderByDesc('balance')
            ->get();

        $tenants = Tenant::orderBy('name')->get();
        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('owner.billing.index', compact(
            'activeTab',
            'invoices',
            'subscriptions',
            'transactions',
            'wallets',
            'tenants',
            'plans',
            'totalRevenue',
            'thisMonthRevenue',
            'pendingRevenue',
            'overdueCount',
            'totalInvoicesCount',
            'estimatedMrr',
            'activeSubsCount',
            'gracePeriodCount',
            'suspendedSubsCount',
            'trialSubsCount',
            'autoRenewCount'
        ));
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'saas_plan_id' => ['nullable', 'exists:saas_plans,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'trx_id' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:paid,unpaid'],
            'paid_at' => ['nullable', 'date'],
            'auto_extend_tenant' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $planId = $validated['saas_plan_id'] ?: $tenant->saas_plan_id;
        $plan = SaasPlan::find($planId);

        // Generate Unique Invoice Number
        $invoicePrefix = Setting::get('invoice_prefix', 'INV-');
        $invoiceNo = $invoicePrefix . date('Ym') . '-' . strtoupper(Str::random(4)) . rand(10, 99);
        $isPaid = $validated['status'] === 'paid';
        $paidAt = $isPaid ? (!empty($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now()) : null;

        $invoice = SaasInvoice::create([
            'tenant_id' => $tenant->id,
            'saas_plan_id' => $planId,
            'tenant_subscription_id' => $tenant->activeSubscription?->id,
            'invoice_no' => $invoiceNo,
            'amount' => $validated['amount'],
            'subtotal' => $validated['amount'],
            'paid_amount' => $isPaid ? $validated['amount'] : 0,
            'due_amount' => $isPaid ? 0 : $validated['amount'],
            'status' => $validated['status'],
            'payment_method' => $validated['payment_method'] ?? 'Manual',
            'trx_id' => $validated['trx_id'] ?? null,
            'due_date' => $validated['due_date'] ?? now()->addDays(7)->format('Y-m-d'),
            'paid_at' => $paidAt,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

        // Add line item
        $invoice->items()->create([
            'description' => ($plan->name ?? 'Standard Plan') . ' Subscription',
            'quantity' => 1,
            'unit_price' => $validated['amount'],
            'total_price' => $validated['amount'],
        ]);

        // If requested, extend tenant's validity by 1 month
        if ($isPaid && $request->filled('auto_extend_tenant')) {
            $activeSub = $tenant->activeSubscription;
            if ($activeSub) {
                app(RenewalService::class)->renewSubscription($activeSub);
            } else {
                $currentExpiry = $tenant->subscription_expires_at ? Carbon::parse($tenant->subscription_expires_at) : now();
                $baseDate = $currentExpiry->isPast() ? now() : $currentExpiry;
                $tenant->update([
                    'subscription_expires_at' => $baseDate->copy()->addMonth()->format('Y-m-d'),
                    'status' => 'active',
                ]);
            }
        }

        return redirect()->route('owner.billing.index', ['tab' => 'invoices'])->with('success', "Invoice #{$invoice->invoice_no} created successfully.");
    }

    /**
     * Run the automated billing lifecycle engine on-demand.
     */
    public function runEngine(Request $request)
    {
        $invService = app(InvoiceService::class);
        $renewalService = app(RenewalService::class);
        $suspensionService = app(SuspensionService::class);
        $reminderService = app(BillingReminderService::class);

        // 1. Generate Invoices
        $invoicesCreated = $invService->processUpcomingInvoices();

        // 2. Process Auto Renewals
        $renewalsProcessed = $renewalService->processDueAutoRenewals();

        // 3. Enforce Grace Period and Suspensions
        $suspendedCount = $suspensionService->enforceGracePeriodsAndSuspensions();

        // 4. Send Reminders
        $remindersCount = $reminderService->dispatchAllReminders();

        $message = "Billing Engine executed successfully! Generated {$invoicesCreated} invoices, processed {$renewalsProcessed} renewals, evaluated {$suspendedCount} suspensions, and queued {$remindersCount} reminders.";

        return back()->with('success', $message);
    }

    /**
     * Manually renew a tenant subscription.
     */
    public function renewSubscription(Request $request, TenantSubscription $subscription)
    {
        $renewed = app(RenewalService::class)->renewSubscription($subscription);

        return back()->with('success', "Subscription for {$subscription->tenant->name} renewed until {$renewed->current_period_end->format('d M, Y')}.");
    }

    /**
     * Update status of a tenant subscription.
     */
    public function updateSubscriptionStatus(Request $request, TenantSubscription $subscription)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:trial,active,grace_period,past_due,suspended,expired,cancelled'],
            'auto_renew' => ['nullable', 'boolean'],
        ]);

        $subscription->update([
            'status' => $validated['status'],
            'auto_renew' => $request->has('auto_renew'),
            'suspended_at' => $validated['status'] === 'suspended' ? now() : null,
        ]);

        if ($subscription->tenant) {
            $subscription->tenant->update([
                'status' => in_array($validated['status'], ['active', 'trial', 'grace_period']) ? 'active' : 'suspended',
            ]);
        }

        return back()->with('success', "Subscription status updated to " . strtoupper($validated['status']) . ".");
    }

    /**
     * Adjust tenant prepaid wallet balance (Credit / Debit).
     */
    public function adjustWallet(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $wallet = TenantWallet::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['balance' => 0, 'currency' => 'BDT']
        );

        $amount = (float) $validated['amount'];
        $type = $validated['type'];

        if ($type === 'debit' && $wallet->balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance for debit adjustment.');
        }

        $newBalance = $type === 'credit' ? ($wallet->balance + $amount) : ($wallet->balance - $amount);
        $wallet->balance = $newBalance;
        $wallet->save();

        TenantWalletTransaction::create([
            'wallet_id' => $wallet->id,
            'tenant_id' => $tenant->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $validated['description'],
            'reference' => 'ADJ-' . strtoupper(Str::random(6)),
        ]);

        return back()->with('success', "Wallet {$type} adjustment of ৳" . number_format($amount, 2) . " processed successfully.");
    }

    /**
     * Toggle the paid/unpaid status of an invoice.
     */
    public function toggleStatus(SaasInvoice $invoice)
    {
        $newStatus = $invoice->status === 'paid' ? 'unpaid' : 'paid';
        $paidAt = $newStatus === 'paid' ? now() : null;

        $invoice->update([
            'status' => $newStatus,
            'paid_at' => $paidAt,
            'due_amount' => $newStatus === 'paid' ? 0 : $invoice->amount,
            'paid_amount' => $newStatus === 'paid' ? $invoice->amount : 0,
        ]);

        $statusLabel = $newStatus === 'paid' ? 'marked as Paid' : 'marked as Unpaid';
        return back()->with('success', "Invoice #{$invoice->invoice_no} has been {$statusLabel}.");
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(SaasInvoice $invoice)
    {
        $invoiceNo = $invoice->invoice_no;
        $invoice->delete();

        return redirect()->route('owner.billing.index', ['tab' => 'invoices'])->with('success', "Invoice #{$invoiceNo} deleted successfully.");
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, SaasInvoice $invoice)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:paid,unpaid,cancelled'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'trx_id' => ['nullable', 'string', 'max:100'],
        ]);

        $isPaid = $validated['status'] === 'paid';
        $amount = (float)$validated['amount'];
        $paidAt = $isPaid ? (!empty($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : ($invoice->paid_at ?: now())) : null;

        $invoice->update([
            'amount' => $amount,
            'subtotal' => $amount,
            'status' => $validated['status'],
            'due_date' => !empty($validated['due_date']) ? Carbon::parse($validated['due_date'])->toDateString() : $invoice->due_date,
            'paid_at' => $paidAt,
            'payment_method' => $validated['payment_method'] ?? $invoice->payment_method,
            'trx_id' => $validated['trx_id'] ?? $invoice->trx_id,
            'paid_amount' => $isPaid ? $amount : 0,
            'due_amount' => $isPaid ? 0 : $amount,
        ]);

        if ($request->input('redirect_to') === 'show') {
            return redirect()->route('owner.invoices.show', $invoice)
                ->with('success', "Invoice #{$invoice->invoice_no} updated successfully.");
        }

        return redirect()->route('owner.billing.index', ['tab' => 'invoices'])
            ->with('success', "Invoice #{$invoice->invoice_no} updated successfully.");
    }

    /**
     * Display dedicated full-page details of a specific SaaS invoice.
     */
    public function show(SaasInvoice $invoice)
    {
        $invoice->load(['tenant.plan', 'tenant.users', 'plan', 'subscription', 'items', 'payments']);
        $amountInWords = $this->numberToWords((float)$invoice->amount);

        return view('owner.invoices.show', compact('invoice', 'amountInWords'));
    }

    /**
     * Display a professional, enterprise-grade A4 printable invoice voucher.
     */
    public function printInvoice(SaasInvoice $invoice)
    {
        $invoice->load(['tenant', 'plan', 'subscription', 'items', 'payments']);
        $amountInWords = $this->numberToWords((float)$invoice->amount);

        return view('owner.invoices.print', compact('invoice', 'amountInWords'));
    }

    /**
     * Convert numeric currency amount to words (South Asian / Bangladeshi Taka standard).
     */
    private function numberToWords(float $number): string
    {
        $no = (int) floor($number);
        $decimal = (int) round(($number - $no) * 100);
        if ($no == 0 && $decimal == 0) {
            return 'Zero Taka Only';
        }

        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];

        $convertBelowHundred = function ($n) use ($words) {
            if ($n < 20) return $words[$n];
            return trim($words[((int)($n / 10)) * 10] . ' ' . $words[$n % 10]);
        };

        $convertBelowThousand = function ($n) use ($convertBelowHundred, $words) {
            $h = (int)($n / 100);
            $rem = $n % 100;
            $res = '';
            if ($h > 0) {
                $res .= $words[$h] . ' Hundred';
                if ($rem > 0) $res .= ' ';
            }
            if ($rem > 0) {
                $res .= $convertBelowHundred($rem);
            }
            return trim($res);
        };

        $parts = [];

        // Crores (1,00,00,000)
        $crore = (int)($no / 10000000);
        $no %= 10000000;
        if ($crore > 0) {
            $parts[] = $convertBelowThousand($crore) . ' Crore';
        }

        // Lakhs (1,00,000)
        $lakh = (int)($no / 100000);
        $no %= 100000;
        if ($lakh > 0) {
            $parts[] = $convertBelowHundred($lakh) . ' Lakh';
        }

        // Thousands (1,000)
        $thousand = (int)($no / 1000);
        $no %= 1000;
        if ($thousand > 0) {
            $parts[] = $convertBelowHundred($thousand) . ' Thousand';
        }

        // Hundreds and remaining
        if ($no > 0) {
            $parts[] = $convertBelowThousand($no);
        }

        $result = implode(' ', array_filter($parts)) . ' Taka';

        if ($decimal > 0) {
            $result .= ' and ' . $convertBelowHundred($decimal) . ' Paisa';
        }

        return trim($result . ' Only');
    }

    /**
     * Send official SaaS invoice email to tenant client.
     */
    public function sendInvoiceEmail(Request $request, SaasInvoice $invoice)
    {
        $invoice->load(['tenant.users', 'plan', 'subscription', 'items', 'payments']);

        $tenant = $invoice->tenant;
        if (!$tenant) {
            return back()->with('error', 'Unable to send email: Associated tenant record was not found.');
        }

        // Determine recipient email (official tenant email first, fallback to admin user account)
        $recipientEmail = $tenant->email;
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $admin = $tenant->users()->where('role', 'isp_admin')->first()
                ?? $tenant->users()->where('role', 'admin')->first()
                ?? $tenant->users()->first();
            $recipientEmail = $admin?->email;
        }

        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', "No valid recipient email address found for tenant '{$tenant->name}'. Please update client contact info.");
        }

        // Retrieve billing email & platform settings
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $billingEmail = !empty($settings['billing_email']) 
            ? $settings['billing_email'] 
            : (!empty($settings['company_email']) ? $settings['company_email'] : 'billing@somitysoft.com');

        $appName = $settings['app_name'] ?? 'SomitySoft SaaS';

        // Dynamically configure outgoing mailer
        $mailer = $settings['mail_mailer'] ?? config('mail.default', 'smtp');
        $host = $settings['mail_host'] ?? config('mail.mailers.smtp.host', 'mail.somitysoft.com');
        $port = (int)($settings['mail_port'] ?? config('mail.mailers.smtp.port', 587));
        $encryption = $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'tls');
        $username = $settings['mail_username'] ?? config('mail.mailers.smtp.username', '');
        $password = $settings['mail_password'] ?? config('mail.mailers.smtp.password', '');
        $timeout = (int)($settings['mail_timeout'] ?? 15);

        // When connecting from this same server to somitysoft.com/mail.somitysoft.com,
        // use local loopback 'deeptouchcloud' to bypass router hairpin NAT timeout and match local SSL cert
        $connectHost = in_array(strtolower(trim($host)), ['mail.somitysoft.com', 'somitysoft.com', 'localhost', '127.0.0.1', '103.59.177.138', '103.59.177.139'])
            ? 'deeptouchcloud'
            : $host;

        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.host', $connectHost);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.timeout', $timeout);
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        Config::set('mail.from.address', $billingEmail);
        Config::set('mail.from.name', $appName . ' Billing');

        try {
            Mail::to($recipientEmail)->send(new SaasInvoiceMail($invoice, $billingEmail, $settings));

            return back()->with('success', "Invoice #{$invoice->invoice_no} has been successfully sent to {$recipientEmail} from {$billingEmail}.");
        } catch (\Throwable $e) {
            Log::error("Invoice email delivery failed for #{$invoice->invoice_no}: " . $e->getMessage());
            return back()->with('error', "Failed to dispatch email from {$billingEmail}: " . $e->getMessage());
        }
    }
}
