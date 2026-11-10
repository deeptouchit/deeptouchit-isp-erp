<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomerPayment;
use App\Models\TenantExpenseCategory;
use App\Models\TenantExpenseTransaction;
use App\Models\TenantResellerInvoice;
use App\Models\TenantUpstreamPayment;
use App\Models\TenantActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantExpenseAccountController extends Controller
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
     * Display listing of Unified Income & Expense Accounts
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        // Ensure default ISP categories exist
        if (TenantExpenseCategory::where('tenant_id', $tenant->id)->count() === 0) {
            TenantExpenseCategory::seedDefaultCategories($tenant->id);
        }

        $allCategories = TenantExpenseCategory::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // 1. Transaction Type Filter
        $selectedType = $request->input('type', 'all');

        // 2. Category Filter
        $selectedCategory = $request->input('category_id', 'all');

        // 3. Payment Method Filter
        $selectedMethod = $request->input('method', 'all');

        // 4. Status Filter
        $selectedStatus = $request->input('status', 'all');

        // 5. Period & Date Range Filter
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
        } elseif (!empty($selectedMonth) && $selectedMonth !== 'all') {
            try {
                $monthCarbon = Carbon::parse($selectedMonth . '-01');
                $dateFrom = $monthCarbon->copy()->startOfMonth()->toDateString();
                $dateTo = $monthCarbon->copy()->endOfMonth()->toDateString();
            } catch (\Throwable $e) {}
        }

        // 6. Search Bar Filter
        $search = trim($request->input('search', ''));

        // 7. Calculate 6 Core KPI Summary Cards (Fast Database Aggregations)
        // 7a. Operating Expenses & Direct Manual Incomes
        $expBaseQ = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['rejected', 'REJECTED', 'cancelled', 'CANCELLED']);
        if ($dateFrom) $expBaseQ->whereDate('transaction_date', '>=', $dateFrom);
        if ($dateTo) $expBaseQ->whereDate('transaction_date', '<=', $dateTo);
        if ($selectedMethod !== 'all') $expBaseQ->where('payment_method', strtoupper($selectedMethod));

        $directExpenseSum = (float) (clone $expBaseQ)->where('type', 'EXPENSE')->sum('amount');
        $directIncomeSum = (float) (clone $expBaseQ)->where('type', 'INCOME')->sum('amount');

        // 7b. Upstream Bandwidth & Carrier Disbursements (COGS Expense)
        $upstreamBaseQ = TenantUpstreamPayment::where('tenant_id', $tenant->id);
        if ($dateFrom) $upstreamBaseQ->whereDate('paid_at', '>=', $dateFrom);
        if ($dateTo) $upstreamBaseQ->whereDate('paid_at', '<=', $dateTo);
        if ($selectedMethod !== 'all') $upstreamBaseQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
        $upstreamDisbursements = (float) $upstreamBaseQ->sum('amount');

        // Total Expenses
        $totalExpenses = $directExpenseSum + $upstreamDisbursements;

        // 7c. Retail Customer Subscription Collections (Main ISP Revenue Stream)
        $custPayBaseQ = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED']);
        if ($dateFrom) {
            $custPayBaseQ->where(function ($q) use ($dateFrom) {
                $q->whereDate('paid_at', '>=', $dateFrom)
                  ->orWhere(function ($sub) use ($dateFrom) {
                      $sub->whereNull('paid_at')->whereDate('created_at', '>=', $dateFrom);
                  });
            });
        }
        if ($dateTo) {
            $custPayBaseQ->where(function ($q) use ($dateTo) {
                $q->whereDate('paid_at', '<=', $dateTo)
                  ->orWhere(function ($sub) use ($dateTo) {
                      $sub->whereNull('paid_at')->whereDate('created_at', '<=', $dateTo);
                  });
            });
        }
        if ($selectedMethod !== 'all') {
            $custPayBaseQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
        }
        $customerCollections = (float) $custPayBaseQ->sum('amount');

        // 7d. Reseller / Sub-ISP Wholesale Collections
        $wholesaleBaseQ = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED']);
        if ($dateFrom) {
            $wholesaleBaseQ->where(function ($q) use ($dateFrom) {
                $q->whereDate('paid_at', '>=', $dateFrom)
                  ->orWhere(function ($sub) use ($dateFrom) {
                      $sub->whereNull('paid_at')->whereDate('created_at', '>=', $dateFrom);
                  });
            });
        }
        if ($dateTo) {
            $wholesaleBaseQ->where(function ($q) use ($dateTo) {
                $q->whereDate('paid_at', '<=', $dateTo)
                  ->orWhere(function ($sub) use ($dateTo) {
                      $sub->whereNull('paid_at')->whereDate('created_at', '<=', $dateTo);
                  });
            });
        }
        if ($selectedMethod !== 'all') {
            $wholesaleBaseQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
        }
        $wholesaleCollections = (float) $wholesaleBaseQ->sum('paid_amount');

        // KPI Summary Aggregates (Strictly 6 Metrics)
        $wholesaleAndDirect = $wholesaleCollections + $directIncomeSum;
        $grossTotalIncome = $customerCollections + $wholesaleAndDirect;
        $realNetCashFlow = $grossTotalIncome - $totalExpenses;
        $categoriesCount = $allCategories->count();

        // 8. Build Unified Stream for the Master Table
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        $page = (int) $request->input('page', 1);

        $entries = collect();

        // Stream A: Manual Vouchers (Expense & Direct Income)
        $needVouchers = in_array($selectedType, ['all', 'EXPENSE', 'INCOME', 'DIRECT_INCOME']);
        if ($needVouchers) {
            $vQ = TenantExpenseTransaction::where('tenant_id', $tenant->id)
                ->with(['category', 'creator', 'approver']);
            if ($selectedType === 'EXPENSE') $vQ->where('type', 'EXPENSE');
            if ($selectedType === 'DIRECT_INCOME') $vQ->where('type', 'INCOME');
            if ($selectedCategory !== 'all') $vQ->where('category_id', (int) $selectedCategory);
            if ($selectedMethod !== 'all') $vQ->where('payment_method', strtoupper($selectedMethod));
            if ($selectedStatus !== 'all') $vQ->where('status', strtoupper($selectedStatus));
            if ($dateFrom) $vQ->whereDate('transaction_date', '>=', $dateFrom);
            if ($dateTo) $vQ->whereDate('transaction_date', '<=', $dateTo);
            if ($search) {
                $vQ->where(function ($q) use ($search) {
                    $q->where('voucher_no', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('payee_payer', 'like', "%{$search}%")
                      ->orWhere('reference_no', 'like', "%{$search}%")
                      ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
                });
            }

            foreach ($vQ->get() as $v) {
                $isExp = strtoupper($v->type) === 'EXPENSE';
                $entries->push((object)[
                    'id' => $v->id,
                    'raw_id' => $v->id,
                    'source' => 'voucher',
                    'voucher_no' => $v->voucher_no,
                    'type' => $v->type,
                    'category_name' => $v->category?->name ?? ($isExp ? 'Operational Expense' : 'Direct Income'),
                    'title' => $v->title . ($v->payee_payer ? ' (' . $v->payee_payer . ')' : ''),
                    'amount' => (float) $v->amount,
                    'payment_method' => $v->payment_method,
                    'status' => $v->status,
                    'status_badge' => $v->status_badge,
                    'transaction_date' => $v->transaction_date ? Carbon::parse($v->transaction_date)->format('Y-m-d') : null,
                    'sort_timestamp' => $v->transaction_date ? Carbon::parse($v->transaction_date)->timestamp : $v->created_at->timestamp,
                    'is_voucher' => true,
                    'original' => $v,
                ]);
            }
        }

        // Stream B: Retail Customer Subscription Collections
        $needCustomer = in_array($selectedType, ['all', 'INCOME', 'CUSTOMER_COLLECTION']) && ($selectedCategory === 'all');
        if ($needCustomer) {
            $pQ = TenantCustomerPayment::with(['customer.package', 'collector'])
                ->where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED']);
            if ($dateFrom) {
                $pQ->where(function ($q) use ($dateFrom) {
                    $q->whereDate('paid_at', '>=', $dateFrom)
                      ->orWhere(function ($sub) use ($dateFrom) {
                          $sub->whereNull('paid_at')->whereDate('created_at', '>=', $dateFrom);
                      });
                });
            }
            if ($dateTo) {
                $pQ->where(function ($q) use ($dateTo) {
                    $q->whereDate('paid_at', '<=', $dateTo)
                      ->orWhere(function ($sub) use ($dateTo) {
                          $sub->whereNull('paid_at')->whereDate('created_at', '<=', $dateTo);
                      });
                });
            }
            if ($selectedMethod !== 'all') {
                $pQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
            }
            if ($search) {
                $pQ->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }

            foreach ($pQ->get() as $p) {
                $custName = $p->customer->name ?? 'Customer';
                $pkgName = $p->customer?->package?->name ?? '';
                $desc = 'Monthly Subscription - ' . $custName . ($pkgName ? ' [' . $pkgName . ']' : '') . ($p->billing_month ? ' (' . $p->billing_month . ')' : '');
                $dateObj = $p->paid_at ?? $p->created_at;

                $entries->push((object)[
                    'id' => $p->id,
                    'raw_id' => $p->id,
                    'source' => 'customer_payment',
                    'voucher_no' => $p->invoice_no ?: ('REC-' . $p->id),
                    'type' => 'INCOME',
                    'category_name' => 'Customer Subscription',
                    'title' => $desc,
                    'amount' => (float) $p->amount,
                    'payment_method' => strtoupper($p->payment_method ?: 'CASH'),
                    'status' => strtoupper($p->status ?: 'PAID'),
                    'status_badge' => [
                        'label' => 'Paid',
                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'icon' => 'fa-check-circle',
                    ],
                    'transaction_date' => $dateObj ? $dateObj->format('Y-m-d') : null,
                    'sort_timestamp' => $dateObj ? $dateObj->timestamp : 0,
                    'is_voucher' => false,
                    'original' => $p,
                ]);
            }
        }

        // Stream C: Reseller / Sub-ISP Wholesale Collections
        $needWholesale = in_array($selectedType, ['all', 'INCOME', 'WHOLESALE']) && ($selectedCategory === 'all');
        if ($needWholesale) {
            $wQ = TenantResellerInvoice::with('reseller')
                ->where('tenant_id', $tenant->id)
                ->where('paid_amount', '>', 0)
                ->whereNotIn('payment_status', ['cancelled', 'CANCELLED']);
            if ($dateFrom) {
                $wQ->where(function ($q) use ($dateFrom) {
                    $q->whereDate('paid_at', '>=', $dateFrom)
                      ->orWhere(function ($sub) use ($dateFrom) {
                          $sub->whereNull('paid_at')->whereDate('created_at', '>=', $dateFrom);
                      });
                });
            }
            if ($dateTo) {
                $wQ->where(function ($q) use ($dateTo) {
                    $q->whereDate('paid_at', '<=', $dateTo)
                      ->orWhere(function ($sub) use ($dateTo) {
                          $sub->whereNull('paid_at')->whereDate('created_at', '<=', $dateTo);
                      });
                });
            }
            if ($selectedMethod !== 'all') {
                $wQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
            }
            if ($search) {
                $wQ->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                      ->orWhereHas('reseller', fn($rq) => $rq->where('name', 'like', "%{$search}%"));
                });
            }

            foreach ($wQ->get() as $w) {
                $resName = $w->reseller->name ?? 'Sub-ISP Partner';
                $monthStr = $w->billing_month ? (is_string($w->billing_month) ? $w->billing_month : $w->billing_month->format('M Y')) : 'Trunk';
                $dateObj = $w->paid_at ?? $w->created_at;

                $entries->push((object)[
                    'id' => $w->id,
                    'raw_id' => $w->id,
                    'source' => 'wholesale',
                    'voucher_no' => $w->invoice_no ?: ('WS-' . $w->id),
                    'type' => 'INCOME',
                    'category_name' => 'Reseller Wholesale',
                    'title' => 'Wholesale Bandwidth - ' . $resName . ' (' . $monthStr . ')',
                    'amount' => (float) $w->paid_amount,
                    'payment_method' => strtoupper($w->payment_method ?: 'BANK'),
                    'status' => strtoupper($w->payment_status ?: 'PAID'),
                    'status_badge' => [
                        'label' => 'Paid',
                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'icon' => 'fa-check-circle',
                    ],
                    'transaction_date' => $dateObj ? (is_string($dateObj) ? Carbon::parse($dateObj)->format('Y-m-d') : $dateObj->format('Y-m-d')) : null,
                    'sort_timestamp' => $dateObj ? (is_string($dateObj) ? Carbon::parse($dateObj)->timestamp : $dateObj->timestamp) : 0,
                    'is_voucher' => false,
                    'original' => $w,
                ]);
            }
        }

        // Stream D: Upstream Bandwidth & Carrier Disbursements (COGS Expense)
        $needUpstream = in_array($selectedType, ['all', 'EXPENSE']) && ($selectedCategory === 'all');
        if ($needUpstream) {
            $uQ = TenantUpstreamPayment::with('provider')
                ->where('tenant_id', $tenant->id);
            if ($dateFrom) $uQ->whereDate('paid_at', '>=', $dateFrom);
            if ($dateTo) $uQ->whereDate('paid_at', '<=', $dateTo);
            if ($selectedMethod !== 'all') $uQ->whereRaw('LOWER(payment_method) = ?', [strtolower($selectedMethod)]);
            if ($search) {
                $uQ->where(function ($q) use ($search) {
                    $q->where('voucher_no', 'like', "%{$search}%")
                      ->orWhereHas('provider', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            }

            foreach ($uQ->get() as $u) {
                $carrierName = $u->provider->name ?? 'Upstream Carrier';
                $dateObj = $u->paid_at ? Carbon::parse($u->paid_at) : $u->created_at;

                $entries->push((object)[
                    'id' => $u->id,
                    'raw_id' => $u->id,
                    'source' => 'upstream_payment',
                    'voucher_no' => $u->voucher_no ?: ('UPV-' . $u->id),
                    'type' => 'EXPENSE',
                    'category_name' => 'Upstream Bandwidth & NTTN',
                    'title' => 'Upstream Carrier Payment - ' . $carrierName . ($u->cheque_no ? ' (Cheque: ' . $u->cheque_no . ')' : ''),
                    'amount' => (float) $u->amount,
                    'payment_method' => strtoupper($u->payment_method ?: 'BANK'),
                    'status' => 'PAID',
                    'status_badge' => [
                        'label' => 'Paid',
                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'icon' => 'fa-check-circle',
                    ],
                    'transaction_date' => $dateObj ? $dateObj->format('Y-m-d') : null,
                    'sort_timestamp' => $dateObj ? $dateObj->timestamp : 0,
                    'is_voucher' => false,
                    'original' => $u,
                ]);
            }
        }

        // Sort unified stream: newest first
        $sortedEntries = $entries->sortByDesc('sort_timestamp')->values();

        // Paginate using LengthAwarePaginator
        $totalItems = $sortedEntries->count();
        $slice = $sortedEntries->slice(($page - 1) * $perPage, $perPage)->values();
        $vouchers = new LengthAwarePaginator(
            $slice,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.finance.expenses', compact(
            'tenant',
            'currencySymbol',
            'vouchers',
            'allCategories',
            'selectedType',
            'selectedCategory',
            'selectedMethod',
            'selectedStatus',
            'period',
            'dateFrom',
            'dateTo',
            'selectedMonth',
            'search',
            'perPage',
            'totalExpenses',
            'customerCollections',
            'wholesaleAndDirect',
            'grossTotalIncome',
            'realNetCashFlow',
            'categoriesCount'
        ));
    }

    /**
     * Store Expense or Income Voucher
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        $validated = $request->validate([
            'type' => 'required|in:EXPENSE,INCOME',
            'category_id' => 'required|exists:tenant_expense_categories,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:CASH,BANK,BKASH,NAGAD,CHEQUE,PETTY_CASH',
            'account_name' => 'nullable|string|max:100',
            'payee_payer' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $voucherNo = TenantExpenseTransaction::generateVoucherNo($tenant->id, $validated['type']);

            $voucher = TenantExpenseTransaction::create([
                'tenant_id' => $tenant->id,
                'category_id' => $validated['category_id'],
                'voucher_no' => $voucherNo,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'account_name' => $validated['account_name'],
                'payee_payer' => $validated['payee_payer'],
                'reference_no' => $validated['reference_no'],
                'transaction_date' => $validated['transaction_date'],
                'status' => 'APPROVED',
                'created_by' => $user?->id,
                'approved_by' => $user?->id,
                'notes' => $validated['notes'],
            ]);

            DB::commit();

            $typeName = $validated['type'] === 'INCOME' ? 'Income Voucher' : 'Expense Voucher';
            $msg = "{$typeName} {$voucherNo} (৳" . number_format($validated['amount'], 2) . ") posted successfully.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'voucher' => $voucher,
                ]);
            }

            return redirect()->route('tenant.finance.expenses')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save voucher: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save voucher: ' . $e->getMessage());
        }
    }

    /**
     * Store new Chart of Accounts Category
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:EXPENSE,INCOME',
            'code' => 'nullable|string|max:30',
            'monthly_budget' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $category = TenantExpenseCategory::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'code' => $validated['code'] ?: ($validated['type'] === 'INCOME' ? 'INC-' : 'EXP-') . rand(10, 99),
            'monthly_budget' => $validated['monthly_budget'] ?? 0.00,
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Account category '{$category->name}' created successfully.",
            'category' => $category,
        ]);
    }

    /**
     * Show voucher/receipt details for print modal & audit
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $source = $request->input('source', 'voucher');

        // Case A: Retail Customer Payment Receipt
        if ($source === 'customer_payment') {
            $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->with(['customer.package', 'collector'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'voucher' => [
                    'id' => $payment->id,
                    'source' => 'customer_payment',
                    'voucher_no' => $payment->invoice_no ?: ('REC-' . $payment->id),
                    'type' => 'INCOME',
                    'type_badge' => ['label' => 'Customer Bill', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-users'],
                    'category_name' => 'Retail Customer Subscription',
                    'category_code' => 'SUB-REV',
                    'title' => 'Internet Bill Collection - ' . ($payment->customer?->name ?? 'Customer') . ($payment->billing_month ? ' (' . $payment->billing_month . ')' : ''),
                    'amount' => (float) $payment->amount,
                    'payment_method' => strtoupper($payment->payment_method ?: 'CASH'),
                    'account_name' => 'Customer Collection Counter',
                    'payee_payer' => $payment->customer?->name ?? 'Customer',
                    'reference_no' => $payment->transaction_id ?: ($payment->customer?->username ?? '--'),
                    'transaction_date' => $payment->paid_at ? $payment->paid_at->format('d M, Y') : ($payment->created_at ? $payment->created_at->format('d M, Y') : '--'),
                    'status' => strtoupper($payment->status ?: 'PAID'),
                    'status_badge' => ['label' => 'Paid', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-check-circle'],
                    'creator_name' => $payment->collector?->name ?: 'Billing Counter',
                    'approver_name' => 'Auto-Verified',
                    'notes' => 'Subscription Collection for Package: ' . ($payment->customer?->package?->name ?? 'Internet Service'),
                    'created_at' => $payment->created_at ? $payment->created_at->format('d M, Y h:i A') : '--',
                ],
                'tenant' => [
                    'name' => $tenant->company_name ?? $tenant->name,
                    'phone' => $tenant->phone ?? '--',
                    'email' => $tenant->email ?? '--',
                    'address' => $tenant->address ?? '--',
                ],
            ]);
        }

        // Case B: Sub-ISP Wholesale Invoice
        if ($source === 'wholesale') {
            $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
                ->with('reseller')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'voucher' => [
                    'id' => $invoice->id,
                    'source' => 'wholesale',
                    'voucher_no' => $invoice->invoice_no ?: ('WS-' . $invoice->id),
                    'type' => 'INCOME',
                    'type_badge' => ['label' => 'Wholesale Billing', 'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'icon' => 'fa-network-wired'],
                    'category_name' => 'Sub-ISP Wholesale Billing',
                    'category_code' => 'WS-REV',
                    'title' => 'Wholesale Bandwidth Revenue - ' . ($invoice->reseller?->name ?? 'Reseller'),
                    'amount' => (float) ($invoice->paid_amount ?: $invoice->total_amount),
                    'payment_method' => strtoupper($invoice->payment_method ?: 'BANK'),
                    'account_name' => 'Corporate Bank Vault',
                    'payee_payer' => $invoice->reseller?->name ?? 'Sub-ISP Partner',
                    'reference_no' => $invoice->invoice_no ?? '--',
                    'transaction_date' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->format('d M, Y') : ($invoice->created_at ? $invoice->created_at->format('d M, Y') : '--'),
                    'status' => strtoupper($invoice->payment_status ?: 'PAID'),
                    'status_badge' => ['label' => 'Paid', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-check-circle'],
                    'creator_name' => 'Automated Billing Engine',
                    'approver_name' => 'Finance Dept',
                    'notes' => 'Wholesale monthly settlement invoice',
                    'created_at' => $invoice->created_at ? $invoice->created_at->format('d M, Y h:i A') : '--',
                ],
                'tenant' => [
                    'name' => $tenant->company_name ?? $tenant->name,
                    'phone' => $tenant->phone ?? '--',
                    'email' => $tenant->email ?? '--',
                    'address' => $tenant->address ?? '--',
                ],
            ]);
        }

        // Case C: Upstream Carrier Disbursement
        if ($source === 'upstream_payment') {
            $up = TenantUpstreamPayment::where('tenant_id', $tenant->id)
                ->with('provider')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'voucher' => [
                    'id' => $up->id,
                    'source' => 'upstream_payment',
                    'voucher_no' => $up->voucher_no ?: ('UPV-' . $up->id),
                    'type' => 'EXPENSE',
                    'type_badge' => ['label' => 'Upstream COGS', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'fa-network-wired'],
                    'category_name' => 'Upstream Bandwidth & NTTN',
                    'category_code' => 'COGS-01',
                    'title' => 'Upstream Carrier Payment - ' . ($up->provider?->name ?? 'Carrier'),
                    'amount' => (float) $up->amount,
                    'payment_method' => strtoupper($up->payment_method ?: 'BANK'),
                    'account_name' => 'Corporate Bank Vault',
                    'payee_payer' => $up->provider?->name ?? 'Carrier',
                    'reference_no' => $up->cheque_no ?: '--',
                    'transaction_date' => $up->paid_at ? Carbon::parse($up->paid_at)->format('d M, Y') : ($up->created_at ? $up->created_at->format('d M, Y') : '--'),
                    'status' => 'PAID',
                    'status_badge' => ['label' => 'Paid', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-check-circle'],
                    'creator_name' => 'Finance Dept',
                    'approver_name' => 'Management',
                    'notes' => 'Carrier bandwidth trunk settlement',
                    'created_at' => $up->created_at ? $up->created_at->format('d M, Y h:i A') : '--',
                ],
                'tenant' => [
                    'name' => $tenant->company_name ?? $tenant->name,
                    'phone' => $tenant->phone ?? '--',
                    'email' => $tenant->email ?? '--',
                    'address' => $tenant->address ?? '--',
                ],
            ]);
        }

        // Default Case: Operating Expense / Direct Income Voucher
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->with(['category', 'creator', 'approver'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'voucher' => [
                'id' => $voucher->id,
                'source' => 'voucher',
                'voucher_no' => $voucher->voucher_no,
                'type' => $voucher->type,
                'type_badge' => $voucher->type_badge,
                'category_name' => $voucher->category?->name ?? 'General',
                'category_code' => $voucher->category?->code ?? '--',
                'title' => $voucher->title,
                'amount' => (float) $voucher->amount,
                'payment_method' => $voucher->payment_method,
                'account_name' => $voucher->account_name ?: '--',
                'payee_payer' => $voucher->payee_payer ?: '--',
                'reference_no' => $voucher->reference_no ?: '--',
                'transaction_date' => $voucher->transaction_date ? Carbon::parse($voucher->transaction_date)->format('d M, Y') : '--',
                'status' => $voucher->status,
                'status_badge' => $voucher->status_badge,
                'creator_name' => $voucher->creator?->name ?: 'Administrator',
                'approver_name' => $voucher->approver?->name ?: ($voucher->status === 'APPROVED' ? 'Administrator' : '--'),
                'notes' => $voucher->notes ?: '--',
                'created_at' => $voucher->created_at ? $voucher->created_at->format('d M, Y h:i A') : '--',
            ],
            'tenant' => [
                'name' => $tenant->company_name ?? $tenant->name,
                'phone' => $tenant->phone ?? '--',
                'email' => $tenant->email ?? '--',
                'address' => $tenant->address ?? '--',
            ],
        ]);
    }

    /**
     * Approve Pending Voucher
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $voucher->update([
            'status' => 'APPROVED',
            'approved_by' => Auth::id(),
        ]);

        $msg = "Voucher {$voucher->voucher_no} has been approved.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Reject Voucher
     */
    public function reject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $voucher->update([
            'status' => 'REJECTED',
            'approved_by' => Auth::id(),
        ]);

        $msg = "Voucher {$voucher->voucher_no} has been rejected.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Delete Voucher
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $vNo = $voucher->voucher_no;
        $voucher->delete();

        $msg = "Voucher {$vNo} deleted successfully.";

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Export Expenses & Incomes to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();

        $query = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->with(['category', 'creator', 'approver']);

        $type = $request->input('type');
        if (!empty($type) && $type !== 'all' && in_array(strtoupper($type), ['EXPENSE', 'INCOME'])) {
            $query->where('type', strtoupper($type));
        }

        $categoryId = $request->input('category_id');
        if (!empty($categoryId) && $categoryId !== 'all') {
            $query->where('category_id', (int) $categoryId);
        }

        $method = $request->input('method');
        if (!empty($method) && $method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

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

        if ($dateFrom) $query->whereDate('transaction_date', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('transaction_date', '<=', $dateTo);

        $vouchers = $query->orderBy('transaction_date', 'desc')->get();
        $fileName = 'income_expense_vouchers_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($vouchers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Voucher No',
                'Type',
                'Date',
                'Category',
                'Account / Head',
                'Description / Title',
                'Payee / Payer',
                'Amount (BDT)',
                'Payment Method',
                'Account Name',
                'Reference No',
                'Status',
                'Created By',
                'Approved By',
                'Notes',
            ]);

            foreach ($vouchers as $v) {
                fputcsv($handle, [
                    $v->voucher_no,
                    $v->type,
                    $v->transaction_date ? Carbon::parse($v->transaction_date)->format('Y-m-d') : '',
                    $v->category?->name ?? 'General',
                    $v->category?->code ?? '',
                    $v->title,
                    $v->payee_payer ?? '',
                    $v->amount,
                    $v->payment_method,
                    $v->account_name ?? '',
                    $v->reference_no ?? '',
                    $v->status,
                    $v->creator?->name ?? 'System',
                    $v->approver?->name ?? '',
                    $v->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
