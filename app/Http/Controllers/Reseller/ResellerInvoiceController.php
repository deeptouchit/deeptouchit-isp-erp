<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerInvoice;
use App\Models\TenantResellerWalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerInvoiceController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Reseller Wholesale Invoices & Billing Ledger.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $month = $request->input('month', 'all');
        $type = $request->input('type', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Scope builder
        $applyFilterScope = function ($q) use ($search, $status, $month, $type) {
            if ($search !== '') {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            if ($status !== 'all' && !empty($status)) {
                $q->where('payment_status', strtoupper($status));
            }

            if ($month !== 'all' && !empty($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $q->whereRaw("DATE_FORMAT(billing_month, '%Y-%m') = ?", [$month]);
            }

            if ($type !== 'all' && !empty($type)) {
                $q->where('type', strtoupper($type));
            }
        };

        // 1. Base Query
        $query = TenantResellerInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $applyFilterScope($query);

        $invoices = $query->latest('id')->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Metric Cards based on filtered base query (AGENTS.md Rule 2.B)
        $kpiBaseQuery = TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $applyFilterScope($kpiBaseQuery);

        $totalInvoiced = (float) (clone $kpiBaseQuery)->sum('amount');
        $totalPaid = (float) (clone $kpiBaseQuery)->sum('paid_amount');
        $totalDue = (float) (clone $kpiBaseQuery)->sum('due_amount');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $thisMonthBilled = (float) (clone $kpiBaseQuery)
            ->whereBetween('billing_month', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $paidCount = (int) (clone $kpiBaseQuery)->where('payment_status', 'PAID')->count();
        $dueCount = (int) (clone $kpiBaseQuery)->whereIn('payment_status', ['UNPAID', 'PARTIAL', 'OVERDUE'])->count();

        $stats = [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'this_month_billed' => $thisMonthBilled,
            'paid_count' => $paidCount,
            'due_count' => $dueCount,
            'unpaid_count' => $dueCount,
        ];

        // 3. Dropdowns
        $availableMonths = TenantResellerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('billing_month')
            ->selectRaw("DISTINCT DATE_FORMAT(billing_month, '%Y-%m') as m")
            ->orderByDesc('m')
            ->pluck('m')
            ->filter()
            ->values();

        $currencySymbol = '৳';

        return view('reseller.invoices.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'invoices',
            'stats',
            'search',
            'status',
            'month',
            'type',
            'perPage',
            'availableMonths',
            'currencySymbol'
        ));
    }

    /**
     * View Invoice details as JSON for natural soft modal.
     */
    public function show(int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->with(['creator'])
            ->findOrFail($id);

        $items = $invoice->item_details ?? [];
        if (empty($items)) {
            $items = [
                [
                    'item' => $invoice->type_label,
                    'description' => 'Wholesale monthly service fee for ' . ($invoice->billing_month ? $invoice->billing_month->format('F Y') : 'Current Period'),
                    'qty' => 1,
                    'unit' => 'Month',
                    'rate' => (float) $invoice->subtotal,
                    'total' => (float) $invoice->subtotal,
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'billing_month' => $invoice->billing_month ? $invoice->billing_month->format('F Y') : 'N/A',
                'type' => $invoice->type,
                'type_label' => $invoice->type_label,
                'subtotal' => (float) $invoice->subtotal,
                'discount' => (float) $invoice->discount,
                'vat_tax' => (float) $invoice->vat_tax,
                'amount' => (float) $invoice->amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'due_amount' => (float) $invoice->due_amount,
                'payment_status' => $invoice->payment_status,
                'payment_status_label' => $invoice->status_badge['label'] ?? $invoice->payment_status,
                'status_badge' => $invoice->status_badge,
                'payment_method' => $invoice->payment_method,
                'paid_at' => $invoice->paid_at ? $invoice->paid_at->format('d M Y, h:i A') : null,
                'period_start' => $invoice->period_start ? $invoice->period_start->format('d M Y') : null,
                'period_end' => $invoice->period_end ? $invoice->period_end->format('d M Y') : null,
                'due_date' => $invoice->due_date ? $invoice->due_date->format('d M Y') : null,
                'notes' => $invoice->notes,
                'items' => $items,
                'reseller_name' => $reseller->name,
                'reseller_code' => $reseller->code,
                'wallet_balance' => (float) $reseller->wallet_balance,
                'total_available' => (float) ($reseller->wallet_balance + $reseller->credit_limit),
                'tenant_name' => $tenant->company_name ?? $tenant->name,
                'tenant_phone' => $tenant->phone ?? $tenant->mobile ?? '',
                'tenant_email' => $tenant->email ?? '',
                'tenant_address' => $tenant->address ?? '',
            ],
        ]);
    }

    /**
     * Pay wholesale invoice instantly with Reseller Wallet balance.
     */
    public function payWithWallet(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        if ($invoice->payment_status === 'PAID' || $invoice->due_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Invoice #{$invoice->invoice_no} is already fully paid.",
            ], 422);
        }

        $dueToPay = (float) $invoice->due_amount;
        $availableBalance = (float) ($reseller->wallet_balance + $reseller->credit_limit);

        if ($availableBalance < $dueToPay) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient available balance. Required: ৳" . number_format($dueToPay, 2) . ", Available: ৳" . number_format($availableBalance, 2) . ". Please recharge your wallet first.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Deduct Reseller Wallet
            $balanceBefore = (float) $reseller->wallet_balance;
            $balanceAfter = $balanceBefore - $dueToPay;
            $reseller->update(['wallet_balance' => $balanceAfter]);

            // 2. Record Wallet Transaction
            $wtxId = TenantResellerWalletTransaction::generateTrxId($tenant->id);
            TenantResellerWalletTransaction::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'trx_id' => $wtxId,
                'type' => 'DEBIT',
                'amount' => $dueToPay,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => 'PREPAID_WALLET',
                'reference_no' => $invoice->invoice_no,
                'description' => "Settlement of Wholesale Invoice #{$invoice->invoice_no}",
                'created_by' => Auth::id(),
            ]);

            // 3. Update Invoice
            $newPaid = (float) $invoice->paid_amount + $dueToPay;
            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => 0.00,
                'payment_status' => 'PAID',
                'payment_method' => 'PREPAID_WALLET',
                'paid_at' => now(),
            ]);

            DB::commit();

            $msg = "Invoice #{$invoice->invoice_no} successfully paid ৳" . number_format($dueToPay, 2) . " using your wallet balance!";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'invoice' => $invoice,
                    'new_wallet_balance' => $balanceAfter,
                ]);
            }

            return redirect()->route('reseller.invoices.index')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Invoices to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $month = $request->input('month', 'all');
        $type = $request->input('type', 'all');

        $query = TenantResellerInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('payment_status', strtoupper($status));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(billing_month, '%Y-%m') = ?", [$month]);
        }

        if ($type !== 'all') {
            $query->where('type', strtoupper($type));
        }

        $invoices = $query->latest('id')->get();
        $filename = 'wholesale_invoices_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Invoice No',
                'Billing Month',
                'Type',
                'Subtotal',
                'Discount',
                'Vat / Tax',
                'Total Amount',
                'Paid Amount',
                'Due Amount',
                'Payment Status',
                'Payment Method',
                'Paid At',
                'Due Date',
                'Notes'
            ]);

            foreach ($invoices as $idx => $inv) {
                fputcsv($handle, [
                    $idx + 1,
                    $inv->invoice_no,
                    $inv->billing_month ? $inv->billing_month->format('F Y') : 'N/A',
                    $inv->type_label,
                    $inv->subtotal,
                    $inv->discount,
                    $inv->vat_tax,
                    $inv->amount,
                    $inv->paid_amount,
                    $inv->due_amount,
                    $inv->payment_status,
                    $inv->payment_method ?? 'N/A',
                    $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i') : 'N/A',
                    $inv->due_date ? $inv->due_date->format('Y-m-d') : 'N/A',
                    $inv->notes ?? ''
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print wholesale invoices statement report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $month = $request->input('month', 'all');
        $type = $request->input('type', 'all');

        $query = TenantResellerInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('payment_status', strtoupper($status));
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->whereRaw("DATE_FORMAT(billing_month, '%Y-%m') = ?", [$month]);
        }

        if ($type !== 'all') {
            $query->where('type', strtoupper($type));
        }

        $invoices = $query->latest('id')->get();

        $totalInvoiced = $invoices->sum('amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalDue = $invoices->sum('due_amount');
        $currencySymbol = '৳';

        return view('reseller.invoices.print_statement', compact(
            'authUser',
            'reseller',
            'tenant',
            'invoices',
            'totalInvoiced',
            'totalPaid',
            'totalDue',
            'currencySymbol',
            'month',
            'status',
            'type'
        ));
    }

    /**
     * Print individual official wholesale invoice document.
     */
    public function printInvoice(int $id): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->with(['creator'])
            ->findOrFail($id);

        $items = $invoice->item_details ?? [];
        if (empty($items)) {
            $items = [
                [
                    'item' => $invoice->type_label,
                    'description' => 'Wholesale monthly charge for ' . ($invoice->billing_month ? $invoice->billing_month->format('F Y') : 'Current Period'),
                    'qty' => 1,
                    'unit' => 'Month',
                    'rate' => (float) $invoice->subtotal,
                    'total' => (float) $invoice->subtotal,
                ]
            ];
        }

        $currencySymbol = '৳';

        return view('reseller.invoices.print_single', compact(
            'authUser',
            'reseller',
            'tenant',
            'invoice',
            'items',
            'currencySymbol'
        ));
    }
}
