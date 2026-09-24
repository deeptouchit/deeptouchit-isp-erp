<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomerPayment;
use App\Models\TenantResellerInvoice;
use App\Models\TenantUpstreamPayment;
use App\Models\TenantExpenseTransaction;
use App\Models\TenantExpenseCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TenantFinancialLedgerController extends Controller
{
    /**
     * Resolve the active Tenant.
     */
    protected function getTenant()
    {
        $tenantId = session('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::first();

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display Financial Statements & General Ledger.
     */
    public function index(Request $request)
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        // 1. Resolve Date Range Filters
        $period = $request->get('period', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $tab = $request->get('tab', 'ledger'); // ledger, pnl, cashbook, heads
        $method = $request->get('method', 'all');
        $search = trim($request->get('search', ''));

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        // 2. Compute Opening Balances (Prior to $startDate)
        $openingBalance = $this->computeBalanceBefore($tenant->id, $startDate);

        // 3. Fetch Unified Ledger Entries within the Date Range
        $entries = $this->fetchUnifiedLedgerEntries($tenant->id, $startDate, $endDate, $method, $search);

        // 4. Calculate Running Balances
        $runningBalance = $openingBalance['net'];
        foreach ($entries as &$entry) {
            if ($entry['type'] === 'credit') {
                $runningBalance += $entry['amount'];
            } else {
                $runningBalance -= $entry['amount'];
            }
            $entry['running_balance'] = $runningBalance;
        }
        unset($entry);

        // 5. Compute KPI Summary Cards (Current Period)
        $kpi = $this->computeKpiSummary($tenant->id, $startDate, $endDate);

        // 6. Compute Statements Breakdown
        $pnl = $this->computeProfitAndLossStatement($tenant->id, $startDate, $endDate);
        $cashbook = $this->computeCashAndBankSummary($tenant->id, $startDate, $endDate, $openingBalance);
        $headSummary = $this->computeHeadWiseSummary($tenant->id, $startDate, $endDate);

        // 7. Paginate or Slice Ledger for Web Table
        $perPage = (int) $request->get('per_page', 20);
        $currentPage = (int) $request->get('page', 1);
        $totalEntries = count($entries);
        
        $paginatedData = array_slice($entries, ($currentPage - 1) * $perPage, $perPage);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedData,
            $totalEntries,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('tenant.finance.ledger', compact(
            'tenant',
            'currencySymbol',
            'period',
            'periodLabel',
            'startDate',
            'endDate',
            'dateFrom',
            'dateTo',
            'tab',
            'method',
            'search',
            'openingBalance',
            'paginator',
            'kpi',
            'pnl',
            'cashbook',
            'headSummary'
        ));
    }

    /**
     * Export Statement / Ledger as CSV.
     */
    public function exportCsv(Request $request)
    {
        $tenant = $this->getTenant();
        $period = $request->get('period', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $method = $request->get('method', 'all');
        $search = trim($request->get('search', ''));

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period, $dateFrom, $dateTo);
        $openingBalance = $this->computeBalanceBefore($tenant->id, $startDate);
        $entries = $this->fetchUnifiedLedgerEntries($tenant->id, $startDate, $endDate, $method, $search);

        $filename = 'financial_ledger_' . strtolower(str_replace(' ', '_', $tenant->name)) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($entries, $openingBalance, $tenant, $periodLabel) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [$tenant->company_name ?? $tenant->name, 'FINANCIAL STATEMENT & GENERAL LEDGER']);
            fputcsv($file, ['Reporting Period', $periodLabel]);
            fputcsv($file, ['Opening Balance', number_format($openingBalance['net'], 2)]);
            fputcsv($file, []);
            fputcsv($file, ['#', 'Date & Time', 'Voucher / Ref No', 'Account Head', 'Particulars / Narrative', 'Channel', 'Debit (Expense)', 'Credit (Revenue)', 'Running Balance', 'Status']);

            $index = 1;
            $currentRun = $openingBalance['net'];
            foreach ($entries as $e) {
                $debit = $e['type'] === 'debit' ? $e['amount'] : 0;
                $credit = $e['type'] === 'credit' ? $e['amount'] : 0;
                $currentRun += ($credit - $debit);

                fputcsv($file, [
                    $index++,
                    $e['date'],
                    $e['ref_no'],
                    $e['category'],
                    $e['description'],
                    strtoupper($e['payment_method']),
                    $debit > 0 ? number_format($debit, 2) : '-',
                    $credit > 0 ? number_format($credit, 2) : '-',
                    number_format($currentRun, 2),
                    ucfirst($e['status']),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display Dedicated Full Page Print View for Financial Statements & Ledger.
     */
    public function printStatement(Request $request)
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $period = $request->get('period', 'this_month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $tab = $request->get('tab', 'ledger');
        $method = $request->get('method', 'all');
        $search = trim($request->get('search', ''));

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period, $dateFrom, $dateTo);
        $openingBalance = $this->computeBalanceBefore($tenant->id, $startDate);
        $entries = $this->fetchUnifiedLedgerEntries($tenant->id, $startDate, $endDate, $method, $search);

        $runningBalance = $openingBalance['net'];
        foreach ($entries as &$entry) {
            if ($entry['type'] === 'credit') {
                $runningBalance += $entry['amount'];
            } else {
                $runningBalance -= $entry['amount'];
            }
            $entry['running_balance'] = $runningBalance;
        }
        unset($entry);

        $kpi = $this->computeKpiSummary($tenant->id, $startDate, $endDate);
        $pnl = $this->computeProfitAndLossStatement($tenant->id, $startDate, $endDate);
        $cashbook = $this->computeCashAndBankSummary($tenant->id, $startDate, $endDate, $openingBalance);
        $headSummary = $this->computeHeadWiseSummary($tenant->id, $startDate, $endDate);

        return view('tenant.finance.ledger-print', compact(
            'tenant',
            'currencySymbol',
            'period',
            'periodLabel',
            'startDate',
            'endDate',
            'tab',
            'method',
            'search',
            'openingBalance',
            'entries',
            'kpi',
            'pnl',
            'cashbook',
            'headSummary'
        ));
    }

    /**
     * Resolve Date Range and Friendly Label.
     */
    protected function resolveDateRange($period, $dateFrom = null, $dateTo = null)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Today (' . $now->format('d M, Y') . ')';
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                $label = 'Yesterday (' . $start->format('d M, Y') . ')';
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek()->startOfDay();
                $end = $now->copy()->endOfWeek()->endOfDay();
                $label = 'This Week (' . $start->format('d M') . ' - ' . $end->format('d M, Y') . ')';
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                $label = 'Last Month (' . $start->format('F Y') . ')';
                break;

            case 'this_quarter':
                $start = $now->copy()->firstOfQuarter()->startOfDay();
                $end = $now->copy()->lastOfQuarter()->endOfDay();
                $label = 'This Quarter (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
                break;

            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $label = 'This Fiscal Year (' . $start->format('Y') . ')';
                break;

            case 'custom':
                $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->startOfMonth();
                $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : $now->copy()->endOfDay();
                $label = 'Custom Period (' . $start->format('d M Y') . ' to ' . $end->format('d M Y') . ')';
                break;

            case 'all':
                $start = Carbon::create(2020, 1, 1)->startOfDay();
                $end = $now->copy()->addYear()->endOfDay();
                $label = 'All Time History';
                break;

            case 'this_month':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = 'This Month (' . $now->format('F Y') . ')';
                break;
        }

        return [$start, $end, $label];
    }

    /**
     * Compute cumulative balances prior to a given date.
     */
    protected function computeBalanceBefore($tenantId, Carbon $startDate)
    {
        $startStr = $startDate->toDateTimeString();

        // 1. Prior Customer Payments (Credits)
        $customerCredits = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr) {
                $q->where('paid_at', '<', $startStr)
                  ->orWhere(function ($sub) use ($startStr) {
                      $sub->whereNull('paid_at')->where('created_at', '<', $startStr);
                  });
            })
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        // 2. Prior Reseller Wholesale (Credits)
        $wholesaleCredits = TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr) {
                $q->where('paid_at', '<', $startStr)
                  ->orWhere(function ($sub) use ($startStr) {
                      $sub->whereNull('paid_at')->where('created_at', '<', $startStr);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED'])
            ->sum('paid_amount');

        // 3. Prior Direct Expenses & Incomes
        $priorExpenseTransactions = TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->where('transaction_date', '<', $startStr)
            ->whereIn('status', ['approved', 'APPROVED'])
            ->get(['type', 'amount', 'payment_method']);

        $expenseDebits = $priorExpenseTransactions->filter(fn($t) => strtolower($t->type) === 'expense')->sum('amount');
        $directIncomeCredits = $priorExpenseTransactions->filter(fn($t) => strtolower($t->type) === 'income')->sum('amount');

        // 4. Prior Upstream Carrier Disbursements (Debits - COGS)
        $upstreamDebits = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->where('paid_at', '<', $startStr)
            ->sum('amount');

        $totalCredits = $customerCredits + $wholesaleCredits + $directIncomeCredits;
        $totalDebits = $expenseDebits + $upstreamDebits;

        return [
            'credits' => (float) $totalCredits,
            'debits' => (float) $totalDebits,
            'net' => (float) ($totalCredits - $totalDebits),
        ];
    }

    /**
     * Fetch and merge all unified ledger entries.
     */
    protected function fetchUnifiedLedgerEntries($tenantId, Carbon $startDate, Carbon $endDate, $method = 'all', $search = '')
    {
        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();
        $entries = [];

        // 1. Customer Bill Payments (Credits)
        $paymentQuery = TenantCustomerPayment::with('customer')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED']);

        if ($method !== 'all') {
            $paymentQuery->whereRaw('LOWER(payment_method) = ?', [strtolower($method)]);
        }

        $payments = $paymentQuery->get();
        foreach ($payments as $p) {
            $custName = $p->customer->name ?? 'Customer';
            $desc = 'Customer Bill Collection - ' . $custName . ($p->billing_month ? ' (' . $p->billing_month . ')' : '');
            
            if ($search && !str_contains(strtolower($desc . ' ' . $p->invoice_no . ' ' . $p->transaction_id), strtolower($search))) {
                continue;
            }

            $dateObj = $p->paid_at ?? $p->created_at;

            $entries[] = [
                'id' => 'PAY-' . $p->id,
                'raw_id' => $p->id,
                'source' => 'customer_payment',
                'type' => 'credit',
                'date' => $dateObj ? $dateObj->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                'sort_timestamp' => $dateObj ? $dateObj->timestamp : now()->timestamp,
                'ref_no' => $p->invoice_no ?? ('REC-' . str_pad($p->id, 5, '0', STR_PAD_LEFT)),
                'category' => 'Customer Subscriptions',
                'description' => $desc,
                'party' => $custName,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method ?? 'cash',
                'status' => $p->status ?? 'paid',
                'channel_badge' => $this->getChannelBadge($p->payment_method),
            ];
        }

        // 2. Reseller Wholesale Billing Payments (Credits)
        $wholesaleQuery = TenantResellerInvoice::with('reseller')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED']);

        $wholesales = $wholesaleQuery->get();
        foreach ($wholesales as $w) {
            $resellerName = $w->reseller->name ?? 'Sub-ISP Partner';
            $monthStr = $w->billing_month ? (is_string($w->billing_month) ? $w->billing_month : $w->billing_month->format('M Y')) : 'Trunk';
            $desc = 'Wholesale Reseller Revenue - ' . $resellerName . ' (' . $monthStr . ')';

            if ($search && !str_contains(strtolower($desc . ' ' . $w->invoice_no), strtolower($search))) {
                continue;
            }

            $dateObj = $w->paid_at ?? $w->created_at;

            $entries[] = [
                'id' => 'WS-' . $w->id,
                'raw_id' => $w->id,
                'source' => 'wholesale_billing',
                'type' => 'credit',
                'date' => $dateObj ? $dateObj->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
                'sort_timestamp' => $dateObj ? $dateObj->timestamp : now()->timestamp,
                'ref_no' => $w->invoice_no ?? ('WS-' . str_pad($w->id, 5, '0', STR_PAD_LEFT)),
                'category' => 'Reseller Wholesale',
                'description' => $desc,
                'party' => $resellerName,
                'amount' => (float) $w->paid_amount,
                'payment_method' => $w->payment_method ?? 'bank',
                'status' => 'paid',
                'channel_badge' => $this->getChannelBadge($w->payment_method ?? 'bank'),
            ];
        }

        // 3. Upstream Carrier Bandwidth & NTTN Disbursements (Debits - COGS)
        $upstreamQuery = TenantUpstreamPayment::with('provider')
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr]);

        if ($method !== 'all') {
            $upstreamQuery->whereRaw('LOWER(payment_method) = ?', [strtolower($method)]);
        }

        $upstreamPayments = $upstreamQuery->get();
        foreach ($upstreamPayments as $up) {
            $carrierName = $up->provider->name ?? 'Upstream Carrier';
            $desc = 'Upstream Bandwidth & NTTN Disbursement - ' . $carrierName . ($up->cheque_no ? ' (Cheque: ' . $up->cheque_no . ')' : '');

            if ($search && !str_contains(strtolower($desc . ' ' . $up->voucher_no . ' ' . $carrierName), strtolower($search))) {
                continue;
            }

            $dateObj = $up->paid_at ? Carbon::parse($up->paid_at) : $up->created_at;

            $entries[] = [
                'id' => 'UPV-' . $up->id,
                'raw_id' => $up->id,
                'source' => 'upstream_payment',
                'type' => 'debit',
                'date' => $dateObj->format('Y-m-d H:i'),
                'sort_timestamp' => $dateObj->timestamp,
                'ref_no' => $up->voucher_no ?? ('UPV-' . str_pad($up->id, 5, '0', STR_PAD_LEFT)),
                'category' => 'Upstream Bandwidth & NTTN (COGS)',
                'description' => $desc,
                'party' => $carrierName,
                'amount' => (float) $up->amount,
                'payment_method' => strtolower($up->payment_method ?? 'bank'),
                'status' => 'paid',
                'channel_badge' => $this->getChannelBadge($up->payment_method),
            ];
        }

        // 4. Operational Expenses & Direct Incomes (Debits & Credits)
        $expenseQuery = TenantExpenseTransaction::with('category')
            ->where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->whereNotIn('status', ['rejected', 'REJECTED', 'cancelled', 'CANCELLED']);

        if ($method !== 'all') {
            $expenseQuery->whereRaw('LOWER(payment_method) = ?', [strtolower($method)]);
        }

        $expenses = $expenseQuery->get();
        foreach ($expenses as $e) {
            $isExpense = strtolower($e->type) === 'expense';
            $catName = $e->category->name ?? ($isExpense ? 'Operational Expense' : 'Direct Income');
            $desc = $e->title . ($e->payee_payer ? ' (' . $e->payee_payer . ')' : '');

            if ($search && !str_contains(strtolower($desc . ' ' . $e->voucher_no . ' ' . $catName), strtolower($search))) {
                continue;
            }

            $dateObj = Carbon::parse($e->transaction_date);

            $entries[] = [
                'id' => 'EXP-' . $e->id,
                'raw_id' => $e->id,
                'source' => 'expense_voucher',
                'type' => $isExpense ? 'debit' : 'credit',
                'date' => $dateObj->format('Y-m-d H:i'),
                'sort_timestamp' => $dateObj->timestamp,
                'ref_no' => $e->voucher_no ?? ('VOU-' . str_pad($e->id, 5, '0', STR_PAD_LEFT)),
                'category' => $catName,
                'description' => $desc,
                'party' => $e->payee_payer ?? 'Self/Company',
                'amount' => (float) $e->amount,
                'payment_method' => strtolower($e->payment_method ?? 'cash'),
                'status' => strtolower($e->status ?? 'approved'),
                'channel_badge' => $this->getChannelBadge($e->payment_method),
            ];
        }

        // Sort chronological (Oldest to newest for running ledger)
        usort($entries, function ($a, $b) {
            return $a['sort_timestamp'] <=> $b['sort_timestamp'];
        });

        return $entries;
    }

    /**
     * Compute 6 KPI Summary Cards.
     */
    protected function computeKpiSummary($tenantId, Carbon $startDate, Carbon $endDate)
    {
        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();

        // 1. Total Customer Collections
        $customerCollections = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        // 2. Total Wholesale Collections
        $wholesaleCollections = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED'])
            ->sum('paid_amount');

        // 3. Direct Incomes & Operating Expenses
        $expTransactions = TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->get(['type', 'amount', 'payment_method']);

        $directIncomes = (float) $expTransactions->filter(fn($t) => strtolower($t->type) === 'income')->sum('amount');
        $operatingExpenses = (float) $expTransactions->filter(fn($t) => strtolower($t->type) === 'expense')->sum('amount');

        // 4. Upstream Carrier Bandwidth Disbursements (COGS)
        $upstreamDisbursements = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->sum('amount');

        $grossRevenue = $customerCollections + $wholesaleCollections + $directIncomes;
        $totalOutflow = $operatingExpenses + $upstreamDisbursements;
        $netProfit = $grossRevenue - $totalOutflow;
        $profitMargin = $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 1) : 0;
        $grossMargin = $grossRevenue > 0 ? round((($grossRevenue - $upstreamDisbursements) / $grossRevenue) * 100, 1) : 0;

        // Cash vs Bank Inflow/Outflow
        $cashCustIn = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        $cashInc = (float) $expTransactions->filter(fn($t) => strtolower($t->type) === 'income' && strtolower($t->payment_method) === 'cash')->sum('amount');
        $cashIn = $cashCustIn + $cashInc;

        $cashExpOut = (float) $expTransactions->filter(fn($t) => strtolower($t->type) === 'expense' && strtolower($t->payment_method) === 'cash')->sum('amount');
        $cashUpstreamOut = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->sum('amount');
        $cashOut = $cashExpOut + $cashUpstreamOut;
        $cashNet = $cashIn - $cashOut;

        $bankIn = ($grossRevenue - $cashIn);
        $bankExpOut = (float) $expTransactions->filter(fn($t) => strtolower($t->type) === 'expense' && strtolower($t->payment_method) !== 'cash')->sum('amount');
        $bankUpstreamOut = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->whereRaw('LOWER(payment_method) != ?', ['cash'])
            ->sum('amount');
        $bankOut = $bankExpOut + $bankUpstreamOut;
        $bankNet = $bankIn - $bankOut;

        $pendingApprovals = TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->whereIn('status', ['pending', 'PENDING'])
            ->count();

        return [
            'gross_revenue' => $grossRevenue,
            'upstream_cogs' => $upstreamDisbursements,
            'operating_expenses' => $operatingExpenses,
            'total_outflow' => $totalOutflow,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
            'gross_margin' => $grossMargin,
            'cash_net' => $cashNet,
            'bank_net' => $bankNet,
            'pending_approvals' => $pendingApprovals,
        ];
    }

    /**
     * Compute Statement of Profit & Loss (Income Statement with ISP COGS).
     */
    protected function computeProfitAndLossStatement($tenantId, Carbon $startDate, Carbon $endDate)
    {
        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();

        // 1. Revenue Streams
        $retailRevenue = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        $wholesaleRevenue = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED'])
            ->sum('paid_amount');

        $directIncomes = TenantExpenseTransaction::with('category')
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['income', 'INCOME'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->get();

        $directIncomeGroups = [];
        $totalDirectIncome = 0;
        foreach ($directIncomes as $inc) {
            $name = $inc->category->name ?? 'Other Operating Revenue';
            $directIncomeGroups[$name] = ($directIncomeGroups[$name] ?? 0) + (float) $inc->amount;
            $totalDirectIncome += (float) $inc->amount;
        }

        $totalGrossRevenue = $retailRevenue + $wholesaleRevenue + $totalDirectIncome;

        // 2. Cost of Goods Sold (COGS - Upstream Bandwidth & Transmission)
        $upstreamPayments = TenantUpstreamPayment::with('provider')
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->get();

        $cogsGroups = [];
        $totalCogs = 0;
        foreach ($upstreamPayments as $up) {
            $cName = $up->provider->name ?? 'Upstream Bandwidth';
            $cogsGroups[$cName] = ($cogsGroups[$cName] ?? 0) + (float) $up->amount;
            $totalCogs += (float) $up->amount;
        }

        $grossProfit = $totalGrossRevenue - $totalCogs;
        $grossMarginPercent = $totalGrossRevenue > 0 ? round(($grossProfit / $totalGrossRevenue) * 100, 1) : 0;

        // 3. Operating Expenses Streams (OPEX)
        $expenses = TenantExpenseTransaction::with('category')
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['expense', 'EXPENSE'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->get();

        $expenseGroups = [];
        $totalExpenses = 0;
        foreach ($expenses as $exp) {
            $name = $exp->category->name ?? 'General & Admin Expense';
            $expenseGroups[$name] = ($expenseGroups[$name] ?? 0) + (float) $exp->amount;
            $totalExpenses += (float) $exp->amount;
        }

        $netOperatingProfit = $grossProfit - $totalExpenses;
        $netProfitMargin = $totalGrossRevenue > 0 ? round(($netOperatingProfit / $totalGrossRevenue) * 100, 1) : 0;

        return [
            'retail_revenue' => $retailRevenue,
            'wholesale_revenue' => $wholesaleRevenue,
            'direct_income_groups' => $directIncomeGroups,
            'total_direct_income' => $totalDirectIncome,
            'total_gross_revenue' => $totalGrossRevenue,
            'cogs_groups' => $cogsGroups,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMarginPercent,
            'expense_groups' => $expenseGroups,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netOperatingProfit,
            'profit_margin' => $netProfitMargin,
        ];
    }

    /**
     * Compute Cashbook and Bank Summary.
     */
    protected function computeCashAndBankSummary($tenantId, Carbon $startDate, Carbon $endDate, $openingBalance)
    {
        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();

        // 1. Cash Inflows & Outflows
        $cashIn = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        $cashIncome = (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereIn('type', ['income', 'INCOME'])
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->sum('amount');

        $totalCashIn = $cashIn + $cashIncome;

        $cashExpOut = (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereIn('type', ['expense', 'EXPENSE'])
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->sum('amount');

        $cashUpstreamOut = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->whereRaw('LOWER(payment_method) = ?', ['cash'])
            ->sum('amount');

        $totalCashOut = $cashExpOut + $cashUpstreamOut;

        // 2. Bank & Digital MFS Inflows & Outflows
        $bankIn = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereRaw('LOWER(payment_method) != ?', ['cash'])
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount') + (float) TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED'])
            ->sum('paid_amount') + (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereIn('type', ['income', 'INCOME'])
            ->whereRaw('LOWER(payment_method) != ?', ['cash'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->sum('amount');

        $bankExpOut = (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
            ->whereIn('type', ['expense', 'EXPENSE'])
            ->whereRaw('LOWER(payment_method) != ?', ['cash'])
            ->whereIn('status', ['approved', 'APPROVED'])
            ->whereBetween('transaction_date', [$startStr, $endStr])
            ->sum('amount');

        $bankUpstreamOut = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->whereRaw('LOWER(payment_method) != ?', ['cash'])
            ->sum('amount');

        $totalBankOut = $bankExpOut + $bankUpstreamOut;

        return [
            'cash_in' => $totalCashIn,
            'cash_out' => $totalCashOut,
            'cash_net' => $totalCashIn - $totalCashOut,
            'bank_in' => $bankIn,
            'bank_out' => $totalBankOut,
            'bank_net' => $bankIn - $totalBankOut,
            'total_net' => ($totalCashIn + $bankIn) - ($totalCashOut + $totalBankOut),
        ];
    }

    /**
     * Compute Chart of Accounts Head-wise summary.
     */
    protected function computeHeadWiseSummary($tenantId, Carbon $startDate, Carbon $endDate)
    {
        $startStr = $startDate->toDateTimeString();
        $endStr = $endDate->toDateTimeString();

        $heads = [];

        // 1. Customer Subscriptions Head
        $subSum = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->whereNotIn('status', ['void', 'VOID', 'cancelled', 'CANCELLED'])
            ->sum('amount');

        $heads[] = [
            'code' => 'INC-RETAIL',
            'name' => 'Customer Retail Subscriptions',
            'type' => 'income',
            'debit' => 0,
            'credit' => $subSum,
            'net' => $subSum,
        ];

        // 2. Wholesale Billing Head
        $wsSum = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->whereBetween('paid_at', [$startStr, $endStr])
                  ->orWhere(function ($sub) use ($startStr, $endStr) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startStr, $endStr]);
                  });
            })
            ->where('paid_amount', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'CANCELLED'])
            ->sum('paid_amount');

        $heads[] = [
            'code' => 'INC-WHOLESALE',
            'name' => 'Reseller Wholesale Billing',
            'type' => 'income',
            'debit' => 0,
            'credit' => $wsSum,
            'net' => $wsSum,
        ];

        // 3. Upstream Bandwidth COGS Head
        $upstreamSum = (float) TenantUpstreamPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startStr, $endStr])
            ->sum('amount');

        if ($upstreamSum > 0) {
            $heads[] = [
                'code' => 'EXP-UPSTREAM-COGS',
                'name' => 'Upstream Bandwidth & Transmission (COGS)',
                'type' => 'expense',
                'debit' => $upstreamSum,
                'credit' => 0,
                'net' => -$upstreamSum,
            ];
        }

        // 4. Category heads from Expense Transactions
        $categories = TenantExpenseCategory::where('tenant_id', $tenantId)->get();

        foreach ($categories as $cat) {
            $txns = TenantExpenseTransaction::where('tenant_id', $tenantId)
                ->where('category_id', $cat->id)
                ->whereIn('status', ['approved', 'APPROVED'])
                ->whereBetween('transaction_date', [$startStr, $endStr])
                ->get();

            if ($txns->isEmpty()) {
                continue;
            }

            $isExpense = strtolower($cat->type) === 'expense';
            $debit = $isExpense ? (float) $txns->sum('amount') : 0;
            $credit = !$isExpense ? (float) $txns->sum('amount') : 0;

            $heads[] = [
                'code' => $cat->code ?? ('ACC-' . $cat->id),
                'name' => $cat->name,
                'type' => strtolower($cat->type),
                'debit' => $debit,
                'credit' => $credit,
                'net' => $credit - $debit,
            ];
        }

        return $heads;
    }

    /**
     * Resolve badge styling for payment channels.
     */
    protected function getChannelBadge($method)
    {
        $map = [
            'cash' => ['label' => 'Cash', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fas fa-money-bill-wave'],
            'bank' => ['label' => 'Bank Trf', 'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'icon' => 'fas fa-building-columns'],
            'bkash' => ['label' => 'bKash', 'class' => 'bg-pink-50 text-pink-700 border-pink-200', 'icon' => 'fas fa-mobile-screen'],
            'nagad' => ['label' => 'Nagad', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fas fa-mobile-screen'],
            'rocket' => ['label' => 'Rocket', 'class' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'fas fa-mobile-screen'],
            'cheque' => ['label' => 'Cheque', 'class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fas fa-money-check'],
            'online' => ['label' => 'Online PGW', 'class' => 'bg-violet-50 text-violet-700 border-violet-200', 'icon' => 'fas fa-globe'],
        ];

        return $map[strtolower($method ?? 'cash')] ?? ['label' => ucfirst($method ?? 'Cash'), 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fas fa-wallet'];
    }
}
