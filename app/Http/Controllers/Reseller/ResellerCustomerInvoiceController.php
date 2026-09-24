<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Services\Network\MikrotikApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerCustomerInvoiceController extends Controller
{
    /**
     * Resolve active authenticated Reseller and Tenant.
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
     * Display Customer Invoices List.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $month = $request->input('month', 'all');
        $packageId = $request->input('package_id', 'all');
        $datePreset = $request->input('date_preset', 'all');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Resolve Date Preset Ranges
        if ($datePreset === 'today') {
            $fromDate = Carbon::today()->toDateString();
            $toDate = Carbon::today()->toDateString();
        } elseif ($datePreset === 'yesterday') {
            $fromDate = Carbon::yesterday()->toDateString();
            $toDate = Carbon::yesterday()->toDateString();
        } elseif ($datePreset === 'this_week') {
            $fromDate = Carbon::now()->startOfWeek()->toDateString();
            $toDate = Carbon::now()->endOfWeek()->toDateString();
        } elseif ($datePreset === 'this_month') {
            $fromDate = Carbon::now()->startOfMonth()->toDateString();
            $toDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($datePreset === 'last_month') {
            $fromDate = Carbon::now()->subMonth()->startOfMonth()->toDateString();
            $toDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        }

        // 1. Base Query
        $query = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'package'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_id', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'due' || $status === 'unpaid') {
                $query->whereIn('status', ['unpaid', 'due', 'overdue']);
            } else {
                $query->where('status', strtolower($status));
            }
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->where('billing_month', $month);
        }

        if ($packageId !== 'all' && !empty($packageId)) {
            $query->where('package_id', (int) $packageId);
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay()
            ]);
        } elseif (!empty($fromDate)) {
            $query->where('created_at', '>=', Carbon::parse($fromDate)->startOfDay());
        } elseif (!empty($toDate)) {
            $query->where('created_at', '<=', Carbon::parse($toDate)->endOfDay());
        }

        // 2. Dropdown Datasets
        $packages = TenantInternetPackage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('package_name')
            ->get();

        $months = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereNotNull('billing_month')
            ->distinct('billing_month')
            ->orderByDesc('billing_month')
            ->pluck('billing_month')
            ->values();

        // 3. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $kpiBase = (clone $query);

        $totalInvoicesCount = (int) (clone $kpiBase)->count();
        $totalBilled = (float) (clone $kpiBase)->sum('total_payable');
        $totalPaid = (float) (clone $kpiBase)->sum('paid_amount');
        $totalDue = (float) (clone $kpiBase)->sum('due_amount');
        $paidCount = (int) (clone $kpiBase)->where('status', 'paid')->count();
        $unpaidCount = (int) (clone $kpiBase)->whereIn('status', ['unpaid', 'due', 'partial', 'partially_paid', 'overdue'])->count();

        $stats = [
            'total_invoices' => $totalInvoicesCount,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'paid_invoices' => $paidCount,
            'unpaid_invoices' => $unpaidCount,
        ];

        $invoices = $query->paginate($perPage)->withQueryString();

        return view('reseller.customer_invoices.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'invoices',
            'packages',
            'months',
            'stats',
            'search',
            'status',
            'month',
            'packageId',
            'datePreset',
            'fromDate',
            'toDate',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Show Invoice details (JSON response for modal, View for web).
     */
    public function show(Request $request, int $id): JsonResponse|View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'package', 'creator'])
            ->findOrFail($id);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'billing_month' => $invoice->billing_month,
                    'customer_id' => $invoice->customer?->customer_id ?? 'N/A',
                    'customer_name' => $invoice->customer?->name ?? 'N/A',
                    'customer_phone' => $invoice->customer?->phone ?? 'N/A',
                    'customer_username' => $invoice->customer?->username ?? 'N/A',
                    'package_name' => $invoice->package?->name ?? ($invoice->package_name ?? 'N/A'),
                    'amount' => (float) $invoice->amount,
                    'discount' => (float) $invoice->discount,
                    'vat_tax' => (float) $invoice->vat_tax,
                    'total_payable' => (float) $invoice->total_payable,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'due_amount' => (float) $invoice->due_amount,
                    'status' => $invoice->status,
                    'issue_date' => $invoice->issue_date ? Carbon::parse($invoice->issue_date)->format('d M Y') : 'N/A',
                    'due_date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A',
                    'paid_at' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->format('d M Y, h:i A') : 'N/A',
                    'payment_method' => $invoice->payment_method ?? 'N/A',
                    'notes' => $invoice->notes,
                ],
            ]);
        }

        $currencySymbol = '৳';

        return view('reseller.customer_invoices.show', compact('authUser', 'reseller', 'tenant', 'invoice', 'currencySymbol'));
    }

    /**
     * Mark customer invoice as paid & record payment.
     */
    public function markPaid(Request $request, int $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with('customer')
            ->findOrFail($id);

        if ($invoice->status === 'paid') {
            $msg = "Invoice #{$invoice->invoice_no} is already paid.";
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg]) : back()->with('info', $msg);
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank_transfer,pos,online',
            'notes' => 'nullable|string|max:250',
        ]);

        $paidAmount = (float) $validated['paid_amount'];
        $customer = $invoice->customer;

        DB::transaction(function () use ($tenantId, $resellerId, $invoice, $customer, $paidAmount, $validated, $authUser) {
            $newPaid = (float) $invoice->paid_amount + $paidAmount;
            $newDue = max(0, (float) $invoice->total_payable - $newPaid);
            $newStatus = ($newDue <= 0) ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => $newStatus,
                'paid_at' => now(),
                'payment_method' => $validated['payment_method'],
            ]);

            // Create Customer Payment Record
            TenantCustomerPayment::create([
                'tenant_id' => $tenantId,
                'reseller_id' => $resellerId,
                'customer_id' => $customer?->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'billing_month' => $invoice->billing_month,
                'amount' => $paidAmount,
                'discount' => (float) $invoice->discount,
                'payment_method' => $validated['payment_method'],
                'collected_by' => $authUser->id,
                'status' => 'paid',
                'paid_at' => now(),
                'notes' => $validated['notes'] ?? "Invoice payment #{$invoice->invoice_no}",
            ]);

            // Adjust customer account due & status
            if ($customer) {
                $customerDue = max(0, (float) $customer->due_amount - $paidAmount);
                $customer->due_amount = $customerDue;
                if ($customer->status === 'due' && $customerDue <= 0) {
                    $customer->status = 'active';
                    $customer->mikrotik_status = 'enabled';
                }
                $customer->save();
            }
        });

        // Re-sync MikroTik if customer became active
        if ($customer && $customer->status === 'active') {
            try {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->syncCustomerToMikrotik($customer);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        $message = "Invoice #{$invoice->invoice_no} payment of ৳" . number_format($paidAmount, 2) . " processed successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'invoice' => $invoice->fresh(),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Print Individual Customer Billing Invoice (A4).
     */
    public function printInvoice(Request $request, int $id): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'package', 'creator'])
            ->findOrFail($id);

        $currencySymbol = '৳';

        return view('reseller.customer_invoices.print', compact('authUser', 'reseller', 'tenant', 'invoice', 'currencySymbol'));
    }

    /**
     * Print Invoices Summary Matrix Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $query = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer', 'package'])
            ->latest('id');

        $status = $request->input('status', 'all');
        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $month = $request->input('month', 'all');
        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $query->where('billing_month', $month);
        }

        $invoices = $query->get();
        $currencySymbol = '৳';

        return view('reseller.customer_invoices.print_report', compact(
            'authUser',
            'reseller',
            'tenant',
            'invoices',
            'currencySymbol',
            'status',
            'month'
        ));
    }

    /**
     * Export Invoices to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $query = TenantCustomerInvoice::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer', 'package'])
            ->latest('id');

        $invoices = $query->get();
        $filename = 'customer_invoices_' . ($reseller->code ?? 'RES') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                '#',
                'Invoice No',
                'Billing Month',
                'Customer ID',
                'Customer Name',
                'Phone',
                'Package Profile',
                'Bill Amount (BDT)',
                'Discount (BDT)',
                'Total Payable (BDT)',
                'Paid Amount (BDT)',
                'Due Amount (BDT)',
                'Status',
                'Issue Date',
                'Paid Date'
            ]);

            foreach ($invoices as $idx => $inv) {
                fputcsv($handle, [
                    $idx + 1,
                    $inv->invoice_no,
                    $inv->billing_month,
                    $inv->customer?->customer_id ?? 'N/A',
                    $inv->customer?->name ?? 'N/A',
                    $inv->customer?->phone ?? 'N/A',
                    $inv->package?->name ?? ($inv->package_name ?? 'N/A'),
                    (float) $inv->amount,
                    (float) $inv->discount,
                    (float) $inv->total_payable,
                    (float) $inv->paid_amount,
                    (float) $inv->due_amount,
                    ucfirst($inv->status),
                    $inv->issue_date ? Carbon::parse($inv->issue_date)->format('Y-m-d') : 'N/A',
                    $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Y-m-d') : 'N/A'
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
