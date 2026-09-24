<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantResellerInvoice;
use App\Models\TenantResellerWalletTransaction;
use App\Models\TenantActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantWholesaleBillingController extends Controller
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
     * Display listing of Reseller Wholesale Invoices & Billing
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        // Fetch all active resellers for filter dropdowns & modal selection
        $allResellersQuery = TenantReseller::where('tenant_id', $tenant->id)
            ->with(['bandwidthAllocation']);
            
        if ($isResellerUser) {
            $allResellersQuery->where('id', $userResellerId);
        }
        
        $allResellers = $allResellersQuery->orderBy('name')->get();

        // Base Query
        $query = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller.bandwidthAllocation', 'creator']);

        // 1. Reseller Scoping & Filter
        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
            $selectedResellerId = (string) $userResellerId;
        } else {
            $selectedResellerId = $request->input('reseller_id', 'all');
            if (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
                $query->where('reseller_id', (int) $selectedResellerId);
            }
        }

        // 2. Period & Date Range Filter (Day-to-day resolution)
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

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // 3. Billing Month Filter
        $selectedMonth = $request->input('month', 'all');
        if (!empty($selectedMonth) && $selectedMonth !== 'all') {
            $query->where(function($q) use ($selectedMonth) {
                $q->whereDate('billing_month', 'like', "{$selectedMonth}%")
                  ->orWhereDate('created_at', 'like', "{$selectedMonth}%");
            });
        }

        // 4. Invoice Type Filter
        $selectedType = $request->input('type', 'all');
        if (!empty($selectedType) && $selectedType !== 'all') {
            $query->where('type', $selectedType);
        }

        // 5. Payment Status Filter
        $selectedStatus = $request->input('status', 'all');
        if (!empty($selectedStatus) && $selectedStatus !== 'all') {
            $query->where('payment_status', $selectedStatus);
        }

        // 6. Search Bar Filter
        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('reseller', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('prefix', 'like', "%{$search}%")
                            ->orWhere('contact_person', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        // 7. Calculate 6 KPI Summary Cards (Based on Current Filter Scope)
        $kpiQuery = clone $query;
        $allMatchedInvoices = $kpiQuery->get();

        $totalInvoicedAmount = (float) $allMatchedInvoices->where('payment_status', '!=', 'CANCELLED')->sum('amount');
        $totalCollectedAmount = (float) $allMatchedInvoices->sum('paid_amount');
        $totalDueAmount = (float) $allMatchedInvoices->where('payment_status', '!=', 'CANCELLED')->sum('due_amount');
        $paidCount = $allMatchedInvoices->where('payment_status', 'PAID')->count();
        $unpaidCount = $allMatchedInvoices->whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count();
        $resellersCount = $allResellers->count();

        // 8. Paginated Results
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        $invoices = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        return view('tenant.finance.wholesale_billing', compact(
            'tenant',
            'invoices',
            'allResellers',
            'isResellerUser',
            'userResellerId',
            'selectedResellerId',
            'period',
            'dateFrom',
            'dateTo',
            'selectedMonth',
            'selectedType',
            'selectedStatus',
            'search',
            'perPage',
            'totalInvoicedAmount',
            'totalCollectedAmount',
            'totalDueAmount',
            'paidCount',
            'unpaidCount',
            'resellersCount'
        ));
    }

    /**
     * Create Custom Wholesale Invoice (Bandwidth Burst, Maintenance, ONU Hardware, Surcharge)
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'type' => 'required|in:BANDWIDTH_WHOLESALE,PANEL_SOFTWARE_FEE,MANUAL_CHARGE',
            'billing_month' => 'required|date_format:Y-m',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'vat_tax' => 'nullable|numeric|min:0',
            'payment_action' => 'required|in:DUE,WALLET_DEDUCT,CASH,BANK,BKASH,NAGAD',
            'notes' => 'nullable|string|max:500',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);

        // Calculate line item totals and subtotal
        $subtotal = 0.0;
        $formattedItems = [];

        foreach ($validated['items'] as $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $subtotal += $lineTotal;

            $formattedItems[] = [
                'description' => trim($item['description']),
                'qty' => $qty,
                'unit' => $item['unit'] ?? 'Unit',
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
            ];
        }

        $discount = (float) ($validated['discount'] ?? 0);
        $vatTax = (float) ($validated['vat_tax'] ?? 0);
        $totalAmount = max(0, round($subtotal - $discount + $vatTax, 2));

        $paymentAction = $validated['payment_action'];
        $monthDate = Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth();

        // Check wallet balance if paying via WALLET_DEDUCT
        if ($paymentAction === 'WALLET_DEDUCT' && $totalAmount > 0) {
            $availableBalance = (float) $reseller->wallet_balance + (float) $reseller->credit_limit;
            if ($availableBalance < $totalAmount) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient Sub-ISP balance. Available: " . number_format($availableBalance, 2) . ", Required: " . number_format($totalAmount, 2),
                    ], 422);
                }
                return back()->with('error', "Insufficient Sub-ISP wallet balance ({$reseller->name}).");
            }
        }

        DB::beginTransaction();
        try {
            $invoiceNo = TenantResellerInvoice::generateInvoiceNo($tenant->id);
            $isPaid = ($paymentAction !== 'DUE');
            $paidAmount = $isPaid ? $totalAmount : 0.00;
            $dueAmount = $isPaid ? 0.00 : $totalAmount;
            $paymentStatus = $isPaid ? 'PAID' : 'UNPAID';

            // Deduct from wallet if applicable
            if ($paymentAction === 'WALLET_DEDUCT' && $totalAmount > 0) {
                $balBefore = (float) $reseller->wallet_balance;
                $reseller->decrement('wallet_balance', $totalAmount);
                $balAfter = (float) $reseller->wallet_balance;

                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => TenantResellerWalletTransaction::generateTrxId($tenant->id),
                    'type' => 'DEBIT',
                    'amount' => $totalAmount,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'payment_method' => 'WALLET_DEDUCT',
                    'reference_no' => $invoiceNo,
                    'description' => "Auto debit for wholesale invoice {$invoiceNo} ({$validated['type']})",
                    'created_by' => $user?->id,
                ]);
            }

            $invoice = TenantResellerInvoice::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $monthDate->toDateString(),
                'type' => $validated['type'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'vat_tax' => $vatTax,
                'amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $isPaid ? $paymentAction : null,
                'paid_at' => $isPaid ? now() : null,
                'period_start' => $monthDate->toDateString(),
                'period_end' => $monthDate->copy()->endOfMonth()->toDateString(),
                'due_date' => $validated['due_date'] ? Carbon::parse($validated['due_date'])->toDateString() : $monthDate->copy()->addDays(10)->toDateString(),
                'notes' => $validated['notes'],
                'item_details' => $formattedItems,
                'created_by' => $user?->id,
            ]);

            DB::commit();

            $msg = "Wholesale invoice {$invoiceNo} created successfully for {$reseller->name}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'invoice' => $invoice,
                ]);
            }

            return redirect()->route('tenant.finance.wholesale-billing')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create invoice: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Batch Generate Monthly Wholesale Invoices for Sub-ISPs (Bandwidth Trunk + Panel Charges)
     */
    public function generateMonthlyBills(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        $validated = $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'reseller_id' => 'required|string', // 'all' or numeric ID
            'auto_deduct_wallet' => 'nullable|boolean',
            'due_date' => 'nullable|date',
        ]);

        $monthDate = Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth();
        $autoDeduct = !empty($validated['auto_deduct_wallet']);
        $dueDate = $validated['due_date'] ? Carbon::parse($validated['due_date'])->toDateString() : $monthDate->copy()->addDays(10)->toDateString();

        $resellerQuery = TenantReseller::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with(['bandwidthAllocation']);

        if ($validated['reseller_id'] !== 'all') {
            $resellerQuery->where('id', (int) $validated['reseller_id']);
        }

        $resellers = $resellerQuery->get();
        if ($resellers->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No active resellers found to bill.'], 422);
            }
            return back()->with('error', 'No active resellers found.');
        }

        $generatedCount = 0;
        $totalBilled = 0.0;
        $totalPaid = 0.0;

        DB::beginTransaction();
        try {
            foreach ($resellers as $reseller) {
                // Check if wholesale bandwidth invoice already generated for this month
                $alreadyBilled = TenantResellerInvoice::where('tenant_id', $tenant->id)
                    ->where('reseller_id', $reseller->id)
                    ->where('type', 'BANDWIDTH_WHOLESALE')
                    ->whereDate('billing_month', $monthDate->toDateString())
                    ->exists();

                if ($alreadyBilled) {
                    continue;
                }

                $bw = $reseller->bandwidthAllocation;
                $items = [];
                $subtotal = 0.0;

                // 1. Bandwidth line items
                if ($bw && $bw->status === 'ACTIVE') {
                    $rate = (float) ($bw->rate_per_mbps ?: 0);
                    $globalMbps = (float) ($bw->global_bandwidth_mbps ?: 0);
                    $bdixMbps = (float) ($bw->bdix_bandwidth_mbps ?: 0);
                    $ggcMbps = (float) ($bw->ggc_bandwidth_mbps ?: 0);
                    $fnaMbps = (float) ($bw->fna_bandwidth_mbps ?: 0);
                    $otherMbps = (float) ($bw->other_bandwidth_mbps ?: 0);
                    $fixedMonthly = (float) ($bw->monthly_bill_amount ?: 0);

                    if ($fixedMonthly > 0) {
                        $items[] = [
                            'description' => "Dedicated Wholesale Bandwidth Trunk ({$bw->formatted_total_bandwidth} - {$bw->allocation_type})",
                            'qty' => 1,
                            'unit' => 'Month',
                            'unit_price' => $fixedMonthly,
                            'total' => $fixedMonthly,
                        ];
                        $subtotal += $fixedMonthly;
                    } else {
                        if ($globalMbps > 0) {
                            $lineCost = round($globalMbps * $rate, 2);
                            $items[] = [
                                'description' => "Global Internet Bandwidth (CIR Dedicated 1:1)",
                                'qty' => $globalMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $rate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($bdixMbps > 0) {
                            $bdixRate = round($rate * 0.25, 2); // 25% of global rate or standard BDIX rate
                            $lineCost = round($bdixMbps * $bdixRate, 2);
                            $items[] = [
                                'description' => "BDIX National Peering Bandwidth",
                                'qty' => $bdixMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $bdixRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($ggcMbps > 0) {
                            $cdnRate = round($rate * 0.15, 2);
                            $lineCost = round($ggcMbps * $cdnRate, 2);
                            $items[] = [
                                'description' => "Google GGC / YouTube Peering Cache",
                                'qty' => $ggcMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $cdnRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($fnaMbps > 0) {
                            $cdnRate = round($rate * 0.15, 2);
                            $lineCost = round($fnaMbps * $cdnRate, 2);
                            $items[] = [
                                'description' => "Facebook FNA Peering Cache",
                                'qty' => $fnaMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $cdnRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                    }
                }

                // 2. Panel Software Charge line item
                $panelCharge = (float) ($reseller->monthly_panel_charge ?: 0);
                if ($panelCharge > 0) {
                    $items[] = [
                        'description' => "Reseller SaaS Portal License & Cloud Software Maintenance",
                        'qty' => 1,
                        'unit' => 'Month',
                        'unit_price' => $panelCharge,
                        'total' => $panelCharge,
                    ];
                    $subtotal += $panelCharge;
                }

                // If no billable items and no panel fee, skip
                if (empty($items) || $subtotal <= 0) {
                    continue;
                }

                $totalAmount = $subtotal;
                $invoiceNo = TenantResellerInvoice::generateInvoiceNo($tenant->id);

                // Check wallet debit
                $canDeduct = false;
                if ($autoDeduct && ((float)$reseller->wallet_balance + (float)$reseller->credit_limit) >= $totalAmount) {
                    $canDeduct = true;
                    $balBefore = (float) $reseller->wallet_balance;
                    $reseller->decrement('wallet_balance', $totalAmount);
                    $balAfter = (float) $reseller->wallet_balance;

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenant->id,
                        'reseller_id' => $reseller->id,
                        'trx_id' => TenantResellerWalletTransaction::generateTrxId($tenant->id),
                        'type' => 'DEBIT',
                        'amount' => $totalAmount,
                        'balance_before' => $balBefore,
                        'balance_after' => $balAfter,
                        'payment_method' => 'WALLET_DEDUCT',
                        'reference_no' => $invoiceNo,
                        'description' => "Monthly wholesale billing for " . $monthDate->format('F Y'),
                        'created_by' => $user?->id,
                    ]);
                    $totalPaid += $totalAmount;
                }

                TenantResellerInvoice::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $monthDate->toDateString(),
                    'type' => 'BANDWIDTH_WHOLESALE',
                    'subtotal' => $subtotal,
                    'discount' => 0.00,
                    'vat_tax' => 0.00,
                    'amount' => $totalAmount,
                    'paid_amount' => $canDeduct ? $totalAmount : 0.00,
                    'due_amount' => $canDeduct ? 0.00 : $totalAmount,
                    'payment_status' => $canDeduct ? 'PAID' : 'UNPAID',
                    'payment_method' => $canDeduct ? 'WALLET_DEDUCT' : null,
                    'paid_at' => $canDeduct ? now() : null,
                    'period_start' => $monthDate->toDateString(),
                    'period_end' => $monthDate->copy()->endOfMonth()->toDateString(),
                    'due_date' => $dueDate,
                    'notes' => "Monthly wholesale bandwidth & panel subscription for " . $monthDate->format('F Y'),
                    'item_details' => $items,
                    'created_by' => $user?->id,
                ]);

                $generatedCount++;
                $totalBilled += $totalAmount;
            }

            DB::commit();

            $msg = "Generated {$generatedCount} wholesale invoices for " . $monthDate->format('F Y') . " (Total: ৳" . number_format($totalBilled, 2) . ", Auto-Deducted: ৳" . number_format($totalPaid, 2) . ").";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'count' => $generatedCount,
                    'total_billed' => $totalBilled,
                ]);
            }

            return redirect()->route('tenant.finance.wholesale-billing')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Batch generation failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Batch generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show wholesale invoice details for viewing / printing
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller.bandwidthAllocation', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'billing_month' => $invoice->billing_month ? Carbon::parse($invoice->billing_month)->format('F Y') : '--',
                'type' => $invoice->type,
                'type_label' => $invoice->type_label,
                'subtotal' => (float) ($invoice->subtotal ?: $invoice->amount),
                'discount' => (float) ($invoice->discount ?: 0),
                'vat_tax' => (float) ($invoice->vat_tax ?: 0),
                'amount' => (float) $invoice->amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'due_amount' => (float) $invoice->due_amount,
                'payment_status' => $invoice->payment_status,
                'payment_method' => $invoice->payment_method ?: '--',
                'paid_at' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->format('d M, Y h:i A') : '--',
                'period_start' => $invoice->period_start ? Carbon::parse($invoice->period_start)->format('d M, Y') : '--',
                'period_end' => $invoice->period_end ? Carbon::parse($invoice->period_end)->format('d M, Y') : '--',
                'due_date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M, Y') : '--',
                'created_at' => $invoice->created_at->format('d M, Y h:i A'),
                'notes' => $invoice->notes ?: '--',
                'item_details' => $invoice->item_details ?: [],
                'status_badge' => $invoice->status_badge,
                'reseller' => [
                    'id' => $invoice->reseller?->id,
                    'name' => $invoice->reseller?->name,
                    'code' => $invoice->reseller?->code,
                    'prefix' => $invoice->reseller?->prefix,
                    'contact_person' => $invoice->reseller?->contact_person ?: '--',
                    'mobile' => $invoice->reseller?->mobile ?: '--',
                    'email' => $invoice->reseller?->email ?: '--',
                    'address' => $invoice->reseller?->address ?: '--',
                    'wallet_balance' => (float) ($invoice->reseller?->wallet_balance ?: 0),
                    'credit_limit' => (float) ($invoice->reseller?->credit_limit ?: 0),
                ],
                'creator' => $invoice->creator?->name ?: 'System Administrator',
            ],
            'tenant' => [
                'name' => $tenant->company_name ?? $tenant->name,
                'phone' => $tenant->phone ?? '--',
                'email' => $tenant->email ?? '--',
                'address' => $tenant->address ?? '--',
                'logo_url' => $tenant->logo_url ?? null,
            ],
        ]);
    }

    /**
     * Record Payment against Wholesale Invoice
     */
    public function recordPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)->findOrFail($id);
        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($invoice->reseller_id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . max(1, (float)$invoice->due_amount),
            'payment_method' => 'required|in:WALLET_DEDUCT,CASH,BANK,BKASH,NAGAD,CHEQUE,ONLINE',
            'transaction_ref' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $method = $validated['payment_method'];

        if ($invoice->payment_status === 'PAID') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This invoice is already fully paid.'], 422);
            }
            return back()->with('error', 'Invoice is already paid.');
        }

        // Wallet Balance Check
        if ($method === 'WALLET_DEDUCT') {
            $available = (float) $reseller->wallet_balance + (float) $reseller->credit_limit;
            if ($available < $amount) {
                $msg = "Insufficient Sub-ISP wallet balance. Available: " . number_format($available, 2) . ", Required: " . number_format($amount, 2) . ". Please select Cash, Bank, or MFS payment method, or pay up to available balance.";
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                    ], 422);
                }
                return back()->with('error', $msg);
            }
        }

        DB::beginTransaction();
        try {
            // Deduct wallet if selected
            if ($method === 'WALLET_DEDUCT' && $amount > 0) {
                $balBefore = (float) $reseller->wallet_balance;
                $reseller->decrement('wallet_balance', $amount);
                $balAfter = (float) $reseller->wallet_balance;

                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => TenantResellerWalletTransaction::generateTrxId($tenant->id),
                    'type' => 'DEBIT',
                    'amount' => $amount,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'payment_method' => 'WALLET_DEDUCT',
                    'reference_no' => $invoice->invoice_no,
                    'description' => "Payment for invoice {$invoice->invoice_no} (" . ($validated['transaction_ref'] ?? '') . ")",
                    'created_by' => $user?->id,
                ]);
            }

            $newPaid = (float)$invoice->paid_amount + $amount;
            $newDue = max(0, (float)$invoice->amount - $newPaid);
            $newStatus = ($newDue <= 0.001) ? 'PAID' : 'PARTIAL';

            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'payment_status' => $newStatus,
                'payment_method' => $method,
                'paid_at' => now(),
                'notes' => $validated['notes'] ? ($invoice->notes ? $invoice->notes . " | " . $validated['notes'] : $validated['notes']) : $invoice->notes,
            ]);

            DB::commit();

            $msg = "Payment of ৳" . number_format($amount, 2) . " received for invoice {$invoice->invoice_no}. Status: {$newStatus}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'invoice' => $invoice,
                ]);
            }

            return redirect()->route('tenant.finance.wholesale-billing')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Payment recording failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Payment recording failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel / Void Wholesale Invoice
     */
    public function cancel(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invoice->paid_amount > 0) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Cannot cancel invoice with paid amounts. Please refund/reverse first.'], 422);
            }
            return back()->with('error', 'Cannot cancel invoice with existing payments.');
        }

        $invoice->update([
            'payment_status' => 'CANCELLED',
            'notes' => ($invoice->notes ? $invoice->notes . " | " : '') . "Cancelled by " . (Auth::user()?->name ?: 'Admin') . " at " . now()->toDateTimeString(),
        ]);

        $msg = "Wholesale invoice {$invoice->invoice_no} has been cancelled.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.wholesale-billing')->with('success', $msg);
    }

    /**
     * Delete Invoice
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($invoice->payment_status === 'PAID') {
            return back()->with('error', 'Paid invoices cannot be deleted.');
        }

        $invoiceNo = $invoice->invoice_no;
        $invoice->delete();

        $msg = "Wholesale invoice {$invoiceNo} deleted successfully.";

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.wholesale-billing')->with('success', $msg);
    }

    /**
     * Export Wholesale Invoices to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $query = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller', 'creator']);

        if ($isResellerUser) {
            $query->where('reseller_id', $userResellerId);
        } else {
            $resellerId = $request->input('reseller_id');
            if (!empty($resellerId) && $resellerId !== 'all') {
                $query->where('reseller_id', (int) $resellerId);
            }
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

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $month = $request->input('month');
        if (!empty($month) && $month !== 'all') {
            $query->whereDate('billing_month', 'like', "{$month}%");
        }

        $type = $request->input('type');
        if (!empty($type) && $type !== 'all') {
            $query->where('type', $type);
        }

        $status = $request->input('status');
        if (!empty($status) && $status !== 'all') {
            $query->where('payment_status', $status);
        }

        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('reseller', fn($rq) => $rq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderBy('id', 'desc')->get();
        $fileName = 'reseller_wholesale_invoices_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice No',
                'Sub-ISP Name',
                'Sub-ISP Code',
                'Billing Month',
                'Invoice Type',
                'Subtotal',
                'Discount',
                'VAT/Tax',
                'Total Amount',
                'Paid Amount',
                'Due Amount',
                'Payment Status',
                'Payment Method',
                'Due Date',
                'Paid At',
                'Created By',
                'Notes',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->invoice_no,
                    $inv->reseller?->name ?? 'N/A',
                    $inv->reseller?->code ?? 'N/A',
                    $inv->billing_month ? Carbon::parse($inv->billing_month)->format('M Y') : 'N/A',
                    $inv->type_label,
                    $inv->subtotal,
                    $inv->discount,
                    $inv->vat_tax,
                    $inv->amount,
                    $inv->paid_amount,
                    $inv->due_amount,
                    $inv->payment_status,
                    $inv->payment_method ?? 'N/A',
                    $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : 'N/A',
                    $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Y-m-d H:i:s') : 'N/A',
                    $inv->creator?->name ?? 'System',
                    $inv->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
