<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\TenantRouter;
use App\Models\TenantActivityLog;
use App\Models\TenantAutomationSetting;
use App\Models\AutomationLog;
use App\Models\TenantCoverageZone;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantCustomerInvoiceController extends Controller
{
    /**
     * Get active tenant
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

        return $tenant ?? abort(404, 'No active tenant found.');
    }

    /**
     * Display listing of Customer Invoices & Bills
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();

        // Customer selection query for manual invoice modal (Select2 formatted)
        $custSelectQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->with('package:id,name,package_name')
            ->orderBy('name');
        if ($isResellerUser) {
            $custSelectQuery->where('reseller_id', $userResellerId);
        }
        $allCustomers = $custSelectQuery->get(['id', 'customer_id', 'name', 'username', 'phone', 'monthly_bill', 'package_id', 'package_name', 'reseller_id', 'zone'])
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'customer_id' => $c->customer_id ?: 'SO' . (1000 + $c->id),
                    'name' => $c->name,
                    'username' => $c->username ?: '--',
                    'phone' => $c->phone ?: '--',
                    'monthly_bill' => (float) ($c->monthly_bill ?: 0),
                    'package_name' => $c->package?->name ?: ($c->package_name ?: '10Mbps'),
                    'zone' => $c->zone ?: '--',
                ];
            });

        // Base Query
        $query = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->with([
                'customer.zone', 
                'customer.coverageZone', 
                'customer.collector',
                'reseller', 
                'package', 
                'creator',
                'payments.collector'
            ]);

        // 1. Reseller Scoping
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
            $selectedResellerId = (string) $userResellerId;
        } else {
            $selectedResellerId = $request->input('reseller_id', 'all');
            if ($selectedResellerId === 'isp') {
                $query->whereNull('reseller_id');
            } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
                $query->where('reseller_id', (int) $selectedResellerId);
            }
        }

        // 1b. Collector / Collected By Filter
        $collectorFilter = $request->input('collector_id', 'all');
        if (!empty($collectorFilter) && $collectorFilter !== 'all') {
            $query->where(function ($q) use ($collectorFilter) {
                $q->where('created_by', (int) $collectorFilter)
                  ->orWhereHas('payments', function ($pq) use ($collectorFilter) {
                      $pq->where('collected_by', (int) $collectorFilter);
                  })
                  ->orWhereHas('customer', function ($cq) use ($collectorFilter) {
                      $cq->where('assigned_collector_id', (int) $collectorFilter);
                  });
            });
        }

        // 2. Period & Date Range Filter (Day-to-day resolution)
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $selectedMonth = $request->input('month', 'all');

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
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if (empty($dateFrom) && empty($dateTo) && !empty($selectedMonth) && $selectedMonth !== 'all') {
            $query->where('billing_month', $selectedMonth);
        }

        // 3. Package Plan Filter
        $selectedPackageId = $request->input('package_id', 'all');
        if (!empty($selectedPackageId) && $selectedPackageId !== 'all') {
            $query->where('package_id', (int) $selectedPackageId);
        }

        // 4. Status Filter
        $statusFilter = $request->input('status', 'all');
        if ($statusFilter === 'due') {
            $query->where(function ($q) {
                $q->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
                  ->orWhere('due_amount', '>', 0);
            })->where('status', '!=', 'cancelled');
        } elseif ($statusFilter === 'paid') {
            $query->where('status', 'paid');
        } elseif ($statusFilter === 'unpaid') {
            $query->where('status', 'unpaid');
        } elseif ($statusFilter === 'partially_paid') {
            $query->where('status', 'partially_paid');
        } elseif ($statusFilter === 'overdue') {
            $query->where(function($q) {
                $q->where('status', 'overdue')
                  ->orWhere(function($sub) {
                      $sub->whereIn('status', ['unpaid', 'partially_paid'])
                          ->whereNotNull('due_date')
                          ->where('due_date', '<', Carbon::today());
                  });
            });
        } elseif ($statusFilter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        // 5. Search Filter
        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('customer_id', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('father_name', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 15, 20, 30, 50, 100, 250, 500])) {
            $perPage = 20;
        }

        $invoices = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // 6. Compute 6 KPI Stats (Scoped to Current Query Scope)
        $kpiBaseQuery = clone $query;
        $allKpiInvoices = $kpiBaseQuery->get();
        $totalInvoicesCount = $allKpiInvoices->count();
        $totalBilledAmount = (float) $allKpiInvoices->sum('total_payable');
        $totalPaidAmount = (float) $allKpiInvoices->sum('paid_amount');
        $totalDueAmount = (float) $allKpiInvoices->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->sum('due_amount');
        $unpaidOverdueCount = $allKpiInvoices->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->count();
        $collectionRate = $totalBilledAmount > 0 ? round(($totalPaidAmount / $totalBilledAmount) * 100, 1) : 0.0;

        // Distinct available billing months for filter dropdown
        $availableMonths = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->distinct()
            ->orderByDesc('billing_month')
            ->pluck('billing_month')
            ->toArray();

        $currentMonth = Carbon::now()->format('Y-m');
        if (!in_array($currentMonth, $availableMonths)) {
            array_unshift($availableMonths, $currentMonth);
        }

        // Field Collectors List for Filter
        $allCollectors = User::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->whereIn('role', ['isp_collector', 'collector', 'reseller_collector'])
                  ->orWhere('role', 'like', '%collector%');
            })
            ->orderBy('name')
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        // Check if there are any uninvoiced subscribers in the catch-up queue
        $catchupSettings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $catchupLeadDays = (int) ($catchupSettings->billing_generation_day ?: 3);
        $catchupTargetDate = Carbon::today()->addDays($catchupLeadDays);

        $uninvoicedQueueQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due', 'suspended', 'expired'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $catchupTargetDate)
            ->whereNotExists(function ($sub) use ($tenant) {
                $sub->select(DB::raw(1))
                    ->from('tenant_customer_invoices')
                    ->whereColumn('tenant_customer_invoices.customer_id', 'tenant_customers.id')
                    ->where('tenant_customer_invoices.tenant_id', $tenant->id)
                    ->where(function ($q) {
                        $q->whereRaw("tenant_customer_invoices.billing_month = DATE_FORMAT(tenant_customers.expiry_date, '%Y-%m')")
                          ->orWhereRaw("DATE(tenant_customer_invoices.due_date) = DATE(tenant_customers.expiry_date)");
                    });
            });

        if ($isResellerUser) {
            $uninvoicedQueueQuery->where('reseller_id', $userResellerId);
        }
        $uninvoicedQueueCount = $uninvoicedQueueQuery->count();

        return view('tenant.finance.invoices', compact(
            'tenant',
            'invoices',
            'allCustomers',
            'allResellers',
            'allPackages',
            'allCollectors',
            'collectorFilter',
            'availableMonths',
            'selectedMonth',
            'selectedResellerId',
            'selectedPackageId',
            'statusFilter',
            'period',
            'dateFrom',
            'dateTo',
            'search',
            'perPage',
            'currencySymbol',
            'isResellerUser',
            'userResellerId',
            'totalInvoicesCount',
            'totalBilledAmount',
            'totalPaidAmount',
            'totalDueAmount',
            'unpaidOverdueCount',
            'collectionRate',
            'uninvoicedQueueCount'
        ));
    }

    /**
     * Batch Automated Monthly Bill Generation
     */
    public function generateMonthlyInvoices(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $validated = $request->validate([
            'billing_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'due_date' => 'required|date',
            'reseller_id' => 'nullable|string',
        ]);

        $billingMonth = $validated['billing_month'];
        $dueDate = Carbon::parse($validated['due_date']);
        $issueDate = Carbon::parse($billingMonth . '-01');

        $scopeResellerId = $isResellerUser ? $userResellerId : $request->input('reseller_id');

        $customersQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due', 'suspended', 'expired']);

        if ($scopeResellerId === 'isp') {
            $customersQuery->whereNull('reseller_id');
        } elseif (!empty($scopeResellerId) && $scopeResellerId !== 'all') {
            $customersQuery->where('reseller_id', (int)$scopeResellerId);
        }

        $customers = $customersQuery->get();
        $generatedCount = 0;
        $skippedCount = 0;

        foreach ($customers as $cust) {
            // Check if invoice for this customer & billing month already exists
            $exists = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $cust->id)
                ->where('billing_month', $billingMonth)
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            $monthlyBill = (float) ($cust->monthly_bill ?: ($cust->package?->price ?? 0.00));
            $invoiceNo = TenantCustomerInvoice::generateInvoiceNo($tenant->id, $billingMonth);

            TenantCustomerInvoice::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $cust->reseller_id,
                'customer_id' => $cust->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $billingMonth,
                'package_id' => $cust->package_id,
                'package_name' => $cust->package?->name ?: ($cust->mikrotik_profile_name ?: ($cust->package_name ?: '10Mbps')),
                'amount' => $monthlyBill,
                'discount' => 0.00,
                'vat_tax' => 0.00,
                'total_payable' => $monthlyBill,
                'paid_amount' => 0.00,
                'due_amount' => $monthlyBill,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'unpaid',
                'auto_generated' => true,
                'notes' => "Automated monthly recurring bill for " . Carbon::createFromFormat('Y-m', $billingMonth)->format('F Y'),
                'created_by' => Auth::id() ?: 1,
            ]);

            $generatedCount++;
        }

        // Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'BATCH_INVOICE_GENERATED',
            'description' => "Generated {$generatedCount} invoices for billing month {$billingMonth} (Skipped: {$skippedCount}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'billing_month' => $billingMonth,
                'generated' => $generatedCount,
                'skipped' => $skippedCount,
            ],
        ]);

        $msg = "Successfully generated {$generatedCount} monthly invoices for {$billingMonth} ({$skippedCount} already existed).";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'generated_count' => $generatedCount,
                'skipped_count' => $skippedCount,
            ]);
        }

        return redirect()->route('tenant.finance.invoices', ['month' => $billingMonth])->with('success', $msg);
    }

    /**
     * Store a Single Custom Invoice
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $validated = $request->validate([
            'customer_id' => 'required|exists:tenant_customers,id',
            'billing_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'vat_tax' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $custQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $custQuery->where('reseller_id', $userResellerId);
        }
        $customer = $custQuery->findOrFail($validated['customer_id']);

        $amount = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0.00);
        $vatTax = (float) ($validated['vat_tax'] ?? 0.00);
        $totalPayable = max(0, ($amount + $vatTax) - $discount);

        $invoiceNo = TenantCustomerInvoice::generateInvoiceNo($tenant->id, $validated['billing_month']);

        $invoice = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $customer->reseller_id,
            'customer_id' => $customer->id,
            'invoice_no' => $invoiceNo,
            'billing_month' => $validated['billing_month'],
            'package_id' => $customer->package_id,
            'package_name' => $customer->package?->name ?: ($customer->mikrotik_profile_name ?: ($customer->package_name ?: 'Custom Invoice')),
            'amount' => $amount,
            'discount' => $discount,
            'vat_tax' => $vatTax,
            'total_payable' => $totalPayable,
            'paid_amount' => 0.00,
            'due_amount' => $totalPayable,
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::parse($validated['due_date']),
            'status' => 'unpaid',
            'auto_generated' => false,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id() ?: 1,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Invoice '{$invoice->invoice_no}' created successfully for {$customer->name}.",
                'invoice' => $invoice
            ]);
        }

        return redirect()->route('tenant.finance.invoices', ['month' => $invoice->billing_month])->with('success', "Invoice '{$invoice->invoice_no}' created successfully.");
    }

    /**
     * Show Details of a Single Invoice (JSON for Modal / Print)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $query = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->with(['customer', 'reseller', 'package', 'creator']);

        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        }

        $inv = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'billing_month' => $inv->billing_month,
                'formatted_month' => $inv->formatted_month,
                'customer_id' => $inv->customer_id,
                'customer_code' => $inv->customer?->customer_id ?? '--',
                'customer_name' => $inv->customer?->name ?? 'Unknown Customer',
                'customer_username' => $inv->customer?->username ?? '--',
                'customer_phone' => $inv->customer?->phone ?? '--',
                'customer_address' => $inv->customer?->address ?? '--',
                'customer_zone' => $inv->customer?->zone ?? '--',
                'reseller_name' => $inv->reseller?->name ?? 'ISP HQ Direct',
                'package_name' => $inv->package_name ?: ($inv->package?->name ?? 'Internet Service'),
                'amount' => (float) $inv->amount,
                'discount' => (float) $inv->discount,
                'vat_tax' => (float) $inv->vat_tax,
                'total_payable' => (float) $inv->total_payable,
                'paid_amount' => (float) $inv->paid_amount,
                'due_amount' => (float) $inv->due_amount,
                'issue_date' => $inv->issue_date?->format('d M Y') ?? '--',
                'due_date' => $inv->due_date?->format('d M Y') ?? '--',
                'paid_at' => $inv->paid_at?->format('d M Y, h:i A') ?? '--',
                'payment_method' => $inv->payment_method_name,
                'status' => $inv->status,
                'status_badge' => $inv->status_badge,
                'notes' => $inv->notes,
                'company_name' => $tenant->company_name ?? $tenant->name,
                'company_phone' => $tenant->phone ?? '',
                'company_email' => $tenant->email ?? '',
                'company_address' => $tenant->address ?? '',
            ]
        ]);
    }

    /**
     * Record Payment for an Invoice
     */
    public function recordPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $validated = $request->validate([
            'amount_paying' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,pos,online,bank,wallet',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $query = TenantCustomerInvoice::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        }
        $invoice = $query->with('customer')->findOrFail($id);
        $customer = $invoice->customer;

        $payingAmount = (float) $validated['amount_paying'];
        $discount = (float) ($validated['discount'] ?? 0.00);

        if ($discount > 0) {
            $invoice->discount = $invoice->discount + $discount;
            $invoice->total_payable = max(0, ($invoice->amount + $invoice->vat_tax) - $invoice->discount);
        }

        $newPaidAmount = $invoice->paid_amount + $payingAmount;
        $newDueAmount = max(0, $invoice->total_payable - $newPaidAmount);

        $invoice->paid_amount = $newPaidAmount;
        $invoice->due_amount = $newDueAmount;
        $invoice->paid_at = Carbon::now();
        $invoice->payment_method = $validated['payment_method'];
        $invoice->status = ($newDueAmount <= 0.00) ? 'paid' : 'partially_paid';
        $invoice->save();

        // Generate Money Receipt in TenantCustomerPayment
        $receiptNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);
        TenantCustomerPayment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => $receiptNo,
            'billing_month' => $invoice->billing_month ?: Carbon::now()->format('Y-m'),
            'amount' => $payingAmount,
            'discount' => $discount,
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'collected_by' => Auth::id() ?: 1,
            'status' => 'approved',
            'notes' => "Payment for Invoice #{$invoice->invoice_no}. " . ($validated['notes'] ?? ''),
            'paid_at' => Carbon::now(),
        ]);

        // Synchronize Customer Account Balance & Status
        $customer->due_amount = max(0, $customer->due_amount - ($payingAmount + $discount));
        if ($customer->due_amount <= 0 && in_array($customer->status, ['due', 'expired', 'suspended'])) {
            $customer->status = 'active';
            $customer->online_status = 'offline';
        }
        $customer->save();

        // If reactivated to active, sync to MikroTik RouterOS & RADIUS
        $mikrotikMsg = '';
        if ($customer->status === 'active') {
            try {
                $mikrotikApi = new MikrotikApiService();
                $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if ($syncRes['success']) {
                    $mikrotikMsg = ' & MikroTik Line Reactivated';
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            try {
                $radiusService = new RadiusService();
                $radiusService->syncCustomerSubscriber($customer);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        // Audit Trail
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'INVOICE_PAYMENT_COLLECTED',
            'description' => "Collected payment of ৳{$payingAmount} for invoice '{$invoice->invoice_no}' from {$customer->name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'amount' => $payingAmount,
                'discount' => $discount,
                'payment_method' => $validated['payment_method'],
                'receipt_no' => $receiptNo,
            ],
        ]);

        $msg = "Payment of ৳" . number_format($payingAmount, 2) . " received successfully for Invoice {$invoice->invoice_no}!{$mikrotikMsg}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'invoice' => $invoice,
                'receipt_no' => $receiptNo,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Cancel an Invoice
     */
    public function cancel(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $query = TenantCustomerInvoice::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        }
        $invoice = $query->findOrFail($id);

        $invoice->status = 'cancelled';
        $invoice->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'INVOICE_CANCELLED',
            'description' => "Cancelled invoice '{$invoice->invoice_no}'.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['invoice_id' => $invoice->id, 'invoice_no' => $invoice->invoice_no],
        ]);

        $msg = "Invoice '{$invoice->invoice_no}' has been cancelled.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete an Invoice
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $query = TenantCustomerInvoice::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        }
        $invoice = $query->findOrFail($id);

        if ($invoice->paid_amount > 0) {
            $errMsg = "Cannot delete invoice '{$invoice->invoice_no}' because payments have already been collected against it.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }

        $invNo = $invoice->invoice_no;
        $invoice->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Invoice '{$invNo}' deleted successfully."]);
        }

        return back()->with('success', "Invoice '{$invNo}' deleted successfully.");
    }

    /**
     * Display Billing Catch-Up & Uninvoiced Queue.
     * Identifies all subscribers needing a subscription invoice for their current/upcoming cycle.
     */
    public function billingCatchUp(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        // 1. Inputs & Filters
        $search = trim($request->input('search', ''));
        $partnerFilter = $request->input('partner_id', 'all');
        $zoneFilter = $request->input('zone_id', 'all');
        $overdueFilter = $request->input('overdue_range', 'all'); // upcoming, overdue_1_7, overdue_8_plus
        $targetDateType = $request->input('target_date_type', 'all'); // 'all', 'today', 'tomorrow', 'next_3_days', 'overdue', 'custom'
        $customDate = $request->input('custom_date', '');
        $packageFilter = $request->input('package_id', 'all');
        $perPage = $request->input('per_page', 20);

        $settings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $leadDays = (int) ($settings->billing_generation_day ?: 3);
        $targetDate = Carbon::today()->addDays($leadDays);

        // 2. Base Uninvoiced Query
        $baseQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due', 'suspended', 'expired'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $targetDate)
            ->whereNotExists(function ($sub) use ($tenant) {
                $sub->select(DB::raw(1))
                    ->from('tenant_customer_invoices')
                    ->whereColumn('tenant_customer_invoices.customer_id', 'tenant_customers.id')
                    ->where('tenant_customer_invoices.tenant_id', $tenant->id)
                    ->where(function ($q) {
                        $q->whereRaw("tenant_customer_invoices.billing_month = DATE_FORMAT(tenant_customers.expiry_date, '%Y-%m')")
                          ->orWhereRaw("DATE(tenant_customer_invoices.due_date) = DATE(tenant_customers.expiry_date)");
                    });
            });

        if ($isResellerUser) {
            $baseQuery->where('reseller_id', $userResellerId);
        }

        // 3. 6 KPI Cards Stats Calculation
        $totalUninvoicedCount = (clone $baseQuery)->count();
        $totalUnbilledAmount = (float) (clone $baseQuery)->sum(DB::raw("COALESCE(monthly_bill, 0)"));
        $todayExpiringCount = (clone $baseQuery)->whereDate('expiry_date', Carbon::today())->count();
        $alreadyOverdueCount = (clone $baseQuery)->whereDate('expiry_date', '<', Carbon::today())->count();
        $upcoming3DaysCount = (clone $baseQuery)->whereDate('expiry_date', '>', Carbon::today())->whereDate('expiry_date', '<=', Carbon::today()->addDays(3))->count();
        $directCount = (clone $baseQuery)->whereNull('reseller_id')->count();
        $partnerCount = (clone $baseQuery)->whereNotNull('reseller_id')->count();

        // Automation Cron Engine Health & Last Run
        $lastCronLog = AutomationLog::where('tenant_id', $tenant->id)->latest('id')->first();
        $lastCronRunText = $lastCronLog?->created_at ? $lastCronLog->created_at->diffForHumans() : 'No Cron Log';
        $isCronHealthy = $lastCronLog && $lastCronLog->created_at->diffInHours(now()) <= 26;

        // 4. Partner-Wise Breakdown Grouping
        $partnerBreakdownQuery = (clone $baseQuery)
            ->select('reseller_id', DB::raw('count(*) as count'), DB::raw('sum(COALESCE(monthly_bill, 0)) as amount'))
            ->groupBy('reseller_id')
            ->with('reseller')
            ->get();

        // 5. Apply User Filters for Table Display
        $query = (clone $baseQuery)->with(['package', 'zone', 'reseller']);

        // Explicit Target Date Filter
        if ($targetDateType === 'today') {
            $query->whereDate('expiry_date', Carbon::today());
        } elseif ($targetDateType === 'tomorrow') {
            $query->whereDate('expiry_date', Carbon::tomorrow());
        } elseif ($targetDateType === 'next_3_days') {
            $query->whereDate('expiry_date', '>', Carbon::today())
                  ->whereDate('expiry_date', '<=', Carbon::today()->addDays(3));
        } elseif ($targetDateType === 'overdue') {
            $query->whereDate('expiry_date', '<', Carbon::today());
        } elseif ($targetDateType === 'custom' && !empty($customDate)) {
            $query->whereDate('expiry_date', Carbon::parse($customDate)->toDateString());
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        if ($partnerFilter === 'direct') {
            $query->whereNull('reseller_id');
        } elseif ($partnerFilter !== 'all' && !empty($partnerFilter)) {
            $query->where('reseller_id', (int)$partnerFilter);
        }

        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', (int)$zoneFilter);
        }

        if ($packageFilter !== 'all' && !empty($packageFilter)) {
            $query->where('package_id', (int)$packageFilter);
        }

        if ($targetDateType === 'all' && $overdueFilter !== 'all') {
            if ($overdueFilter === 'upcoming') {
                $query->whereDate('expiry_date', '>', Carbon::today());
            } elseif ($overdueFilter === 'overdue_1_7') {
                $query->whereDate('expiry_date', '<=', Carbon::today())
                      ->whereDate('expiry_date', '>=', Carbon::today()->subDays(7));
            } elseif ($overdueFilter === 'overdue_8_plus') {
                $query->whereDate('expiry_date', '<', Carbon::today()->subDays(7));
            }
        }

        // Active filtered scope count & amount (for dynamic generate button)
        $filteredTargetCount = (clone $query)->count();
        $filteredTargetAmount = (float) (clone $query)->sum(DB::raw("COALESCE(monthly_bill, 0)"));

        // Order by oldest expiry date first
        $query->orderBy('expiry_date', 'asc');

        if ($perPage === 'all') {
            $total = (clone $query)->count();
            $subscribers = $query->paginate($total > 0 ? $total : 20)->withQueryString();
        } else {
            $perPageInt = in_array((int)$perPage, [15, 20, 30, 50, 100, 250, 500], true) ? (int)$perPage : 20;
            $subscribers = $query->paginate($perPageInt)->withQueryString();
        }

        // Auxiliary Filter Lists
        $resellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $zones = TenantCoverageZone::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $packages = TenantInternetPackage::where('tenant_id', $tenant->id)->orderBy('price')->get();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $generateBtnText = match($targetDateType) {
            'today' => "Generate Today's Bills (" . number_format($filteredTargetCount) . ")",
            'tomorrow' => "Generate Tomorrow's Bills (" . number_format($filteredTargetCount) . ")",
            'next_3_days' => "Generate Next 3 Days (" . number_format($filteredTargetCount) . ")",
            'overdue' => "Generate Overdue Catch-Up (" . number_format($filteredTargetCount) . ")",
            'custom' => "Generate for " . (!empty($customDate) ? Carbon::parse($customDate)->format('d M') : 'Date') . " (" . number_format($filteredTargetCount) . ")",
            default => "Generate All Catch-Up Invoices (" . number_format($filteredTargetCount) . ")",
        };

        $targetDateLabel = match($targetDateType) {
            'today' => "Today's Expiring Subscribers (" . Carbon::today()->format('d M Y') . ")",
            'tomorrow' => "Tomorrow's Expiring Subscribers (" . Carbon::tomorrow()->format('d M Y') . ")",
            'next_3_days' => "Next 3 Days Subscribers",
            'overdue' => "Past Overdue Subscribers",
            'custom' => "Subscribers Expiring on " . (!empty($customDate) ? Carbon::parse($customDate)->format('d M Y') : 'Selected Date'),
            default => "All Uninvoiced Queue",
        };

        return view('tenant.finance.billing_catchup', compact(
            'tenant',
            'subscribers',
            'totalUninvoicedCount',
            'totalUnbilledAmount',
            'todayExpiringCount',
            'alreadyOverdueCount',
            'upcoming3DaysCount',
            'filteredTargetCount',
            'filteredTargetAmount',
            'generateBtnText',
            'targetDateLabel',
            'directCount',
            'partnerCount',
            'lastCronRunText',
            'isCronHealthy',
            'partnerBreakdownQuery',
            'resellers',
            'zones',
            'packages',
            'currencySymbol',
            'search',
            'partnerFilter',
            'zoneFilter',
            'overdueFilter',
            'targetDateType',
            'customDate',
            'packageFilter',
            'perPage',
            'isResellerUser'
        ));
    }

    /**
     * Execute Batch or Single Catch-Up Invoicing
     */
    public function executeBillingCatchUp(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $customerIds = $request->input('customer_ids', []);
        $scope = $request->input('scope', 'selected'); // 'selected', 'partner', 'all', 'filtered'
        $targetResellerId = $request->input('partner_id');
        $targetDateType = $request->input('target_date_type', 'all');
        $customDate = $request->input('custom_date');

        $settings = TenantAutomationSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $leadDays = (int) ($settings->billing_generation_day ?: 3);
        $targetDate = Carbon::today()->addDays($leadDays);

        $query = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'due', 'suspended', 'expired'])
            ->whereNotNull('expiry_date');

        // Apply Target Date Criteria
        if ($targetDateType === 'today') {
            $query->whereDate('expiry_date', Carbon::today());
        } elseif ($targetDateType === 'tomorrow') {
            $query->whereDate('expiry_date', Carbon::tomorrow());
        } elseif ($targetDateType === 'next_3_days') {
            $query->whereDate('expiry_date', '>', Carbon::today())
                  ->whereDate('expiry_date', '<=', Carbon::today()->addDays(3));
        } elseif ($targetDateType === 'overdue') {
            $query->whereDate('expiry_date', '<', Carbon::today());
        } elseif ($targetDateType === 'custom' && !empty($customDate)) {
            $query->whereDate('expiry_date', Carbon::parse($customDate)->toDateString());
        } else {
            $query->whereDate('expiry_date', '<=', $targetDate);
        }

        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        } elseif ($scope === 'partner') {
            if ($targetResellerId === 'direct') {
                $query->whereNull('reseller_id');
            } elseif (!empty($targetResellerId) && $targetResellerId !== 'all') {
                $query->where('reseller_id', (int)$targetResellerId);
            }
        } elseif ($scope === 'selected' && !empty($customerIds)) {
            $query->whereIn('id', (array)$customerIds);
        }

        // Direct SQL filter to exclude already invoiced users for the cycle
        $query->whereNotExists(function ($sub) use ($tenant) {
            $sub->select(DB::raw(1))
                ->from('tenant_customer_invoices')
                ->whereColumn('tenant_customer_invoices.customer_id', 'tenant_customers.id')
                ->where('tenant_customer_invoices.tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->whereRaw("tenant_customer_invoices.billing_month = DATE_FORMAT(tenant_customers.expiry_date, '%Y-%m')")
                      ->orWhereRaw("DATE(tenant_customer_invoices.due_date) = DATE(tenant_customers.expiry_date)");
                });
        });

        $candidates = $query->with('package')->get();
        $generatedCount = 0;
        $skippedCount = 0;
        $totalBilled = 0.00;

        $radiusService = new RadiusService();

        foreach ($candidates as $customer) {
            $billingMonth = Carbon::parse($customer->expiry_date)->format('Y-m');
            $targetExpiryDate = Carbon::parse($customer->expiry_date);

            // Double check idempotency
            $exists = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->where(function ($q) use ($billingMonth, $targetExpiryDate) {
                    $q->where('billing_month', $billingMonth)
                      ->orWhereDate('due_date', $targetExpiryDate->toDateString());
                })
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            $monthlyBill = (float)($customer->monthly_bill > 0 ? $customer->monthly_bill : ($customer->package?->price ?? 0.00));
            if ($monthlyBill <= 0) {
                $skippedCount++;
                continue;
            }

            $invoiceNo = TenantCustomerInvoice::generateInvoiceNo($tenant->id, $billingMonth);

            TenantCustomerInvoice::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $customer->reseller_id,
                'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $billingMonth,
                'package_id' => $customer->package_id,
                'package_name' => $customer->package_display_name ?: ($customer->package?->name ?? 'Internet Package'),
                'amount' => $monthlyBill,
                'discount' => 0.00,
                'vat_tax' => 0.00,
                'total_payable' => $monthlyBill,
                'total_amount' => $monthlyBill,
                'paid_amount' => 0.00,
                'due_amount' => $monthlyBill,
                'issue_date' => Carbon::today()->toDateString(),
                'due_date' => $targetExpiryDate->toDateString(),
                'status' => 'unpaid',
                'auto_generated' => true,
                'notes' => 'Catch-up cycle subscription invoice.',
                'created_by' => Auth::id() ?: null,
            ]);

            $customer->due_amount = (float)($customer->due_amount ?? 0) + $monthlyBill;
            $customer->save();

            $generatedCount++;
            $totalBilled += $monthlyBill;

            // FreeRADIUS AAA Sync
            try {
                $radiusService->syncCustomerSubscriber($customer);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("FreeRADIUS sync warning for {$customer->username}: " . $e->getMessage());
            }
        }

        $targetLabel = match($targetDateType) {
            'today' => 'Today (' . Carbon::today()->format('d M') . ')',
            'tomorrow' => 'Tomorrow (' . Carbon::tomorrow()->format('d M') . ')',
            'next_3_days' => 'Next 3 Days',
            'overdue' => 'Already Overdue',
            'custom' => !empty($customDate) ? Carbon::parse($customDate)->format('d M Y') : 'Custom Date',
            default => 'All Catch-Up Queue',
        };

        // Activity Trail
        if ($generatedCount > 0) {
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: 'System',
                'event_type' => 'CATCHUP_BILLING_EXECUTED',
                'description' => "Executed Catch-Up Billing for [{$targetLabel}]: {$generatedCount} invoices created totaling ৳" . number_format($totalBilled, 2),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'generated_count' => $generatedCount,
                    'total_billed' => $totalBilled,
                    'target_label' => $targetLabel,
                    'scope' => $scope,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'generated_count' => $generatedCount,
            'skipped_count' => $skippedCount,
            'total_billed' => $totalBilled,
            'target_label' => $targetLabel,
            'message' => "Successfully generated {$generatedCount} catch-up invoices for [{$targetLabel}] totaling ৳" . number_format($totalBilled, 2) . "." . ($skippedCount > 0 ? " ({$skippedCount} skipped as already invoiced/zero bill)" : ""),
        ]);
    }
}
