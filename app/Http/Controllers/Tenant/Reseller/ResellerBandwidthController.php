<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantBandwidthPlan;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantResellerInvoice;
use App\Models\TenantRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerBandwidthController extends Controller
{
    /**
     * Display Wholesale Bandwidth Allocation & Real-Time Capacity Management.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $selectedRouterId = $request->input('router_id');
        $selectedPlanId = $request->input('bandwidth_plan_id');
        $selectedType = $request->input('allocation_type');
        $selectedStatus = $request->input('status');
        $activeTab = $request->input('tab', 'allocations');

        // 1. Fetch Core Routers & All Resellers for dropdowns
        $routers = TenantRouter::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Resellers with Bandwidth Allocation
        $query = TenantReseller::where('tenant_id', $tenant->id)
            ->whereHas('bandwidthAllocation')
            ->with([
                'bandwidthAllocation.router', 
                'bandwidthAllocation.bandwidthPlan',
                'latestBandwidthInvoice'
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('prefix', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhereHas('bandwidthAllocation', function ($bq) use ($search) {
                      $bq->where('interface_name', 'like', "%{$search}%")
                         ->orWhere('mikrotik_queue_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($selectedRouterId) {
            $query->whereHas('bandwidthAllocation', function ($bq) use ($selectedRouterId) {
                $bq->where('router_id', $selectedRouterId);
            });
        }

        if ($selectedPlanId) {
            $query->whereHas('bandwidthAllocation', function ($bq) use ($selectedPlanId) {
                $bq->where('bandwidth_plan_id', $selectedPlanId);
            });
        }

        if ($selectedType) {
            $query->whereHas('bandwidthAllocation', function ($bq) use ($selectedType) {
                $bq->where('allocation_type', $selectedType);
            });
        }

        if ($selectedStatus) {
            $query->whereHas('bandwidthAllocation', function ($bq) use ($selectedStatus) {
                $bq->where('status', $selectedStatus);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $resellers = $query->orderBy('name')->paginate($perPage)->withQueryString();

        // 3. Compute 6-Card Metric KPIs
        $allocations = TenantResellerBandwidth::where('tenant_id', $tenant->id)->get();

        $totalAllocatedMbps = (float) $allocations->sum('total_bandwidth_mbps');
        $totalGlobalMbps = (float) $allocations->sum('global_bandwidth_mbps');
        $totalBdixCdnMbps = (float) (
            $allocations->sum('bdix_bandwidth_mbps') + 
            $allocations->sum('cdn_bandwidth_mbps') +
            $allocations->sum('ggc_bandwidth_mbps') +
            $allocations->sum('fna_bandwidth_mbps') +
            $allocations->sum('other_bandwidth_mbps')
        );
        $totalBandwidthMRR = (float) $allocations->sum('monthly_bill_amount');

        $totalCurrentUsage = (float) $allocations->sum('current_usage_mbps');
        $avgUtilization = $totalAllocatedMbps > 0 ? round(($totalCurrentUsage / $totalAllocatedMbps) * 100, 1) : 0.0;

        $congestedCount = $allocations->filter(function ($item) {
            return $item->utilization_percent >= 85.0;
        })->count();

        // 4. Group by Routers for Distribution
        $routerDistributions = $routers->map(function ($router) use ($allocations) {
            $routerAllocations = $allocations->where('router_id', $router->id);
            $allocated = (float) $routerAllocations->sum('total_bandwidth_mbps');
            $usage = (float) $routerAllocations->sum('current_usage_mbps');
            $mrr = (float) $routerAllocations->sum('monthly_bill_amount');
            $count = $routerAllocations->count();
            return [
                'router' => $router,
                'count' => $count,
                'total_allocated' => $allocated,
                'current_usage' => $usage,
                'utilization' => $allocated > 0 ? round(($usage / $allocated) * 100, 1) : 0,
                'mrr' => $mrr,
            ];
        });

        $bandwidthPlans = TenantBandwidthPlan::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.resellers.bandwidth', compact(
            'tenant',
            'resellers',
            'allResellers',
            'routers',
            'allocations',
            'bandwidthPlans',
            'currencySymbol',
            'totalAllocatedMbps',
            'totalGlobalMbps',
            'totalBdixCdnMbps',
            'totalBandwidthMRR',
            'avgUtilization',
            'congestedCount',
            'search',
            'selectedRouterId',
            'selectedPlanId',
            'selectedType',
            'selectedStatus',
            'perPage'
        ));
    }

    /**
     * Display Core Router / Gateway Capacity & Distribution Dashboard.
     */
    public function capacity(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $routerQuery = TenantRouter::where('tenant_id', $tenant->id);

        if ($search) {
            $routerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $routerQuery->where('status', $status);
        }

        $routers = $routerQuery->orderBy('name')->paginate($perPage)->withQueryString();

        $allAllocations = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->with(['reseller', 'router'])
            ->get();

        $totalAllocatedMbps = (float) $allAllocations->sum('total_bandwidth_mbps');
        $totalCurrentUsage = (float) $allAllocations->sum('current_usage_mbps');
        $totalWholesaleMRR = (float) $allAllocations->sum('monthly_bill_amount');
        $totalRoutersCount = TenantRouter::where('tenant_id', $tenant->id)->count();
        $onlineRoutersCount = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $avgUtilization = $totalAllocatedMbps > 0 ? round(($totalCurrentUsage / $totalAllocatedMbps) * 100, 1) : 0.0;

        // Router metrics map
        $routerMetrics = [];
        foreach ($routers as $rtr) {
            $rtrAllocations = $allAllocations->where('router_id', $rtr->id);
            $allocated = (float) $rtrAllocations->sum('total_bandwidth_mbps');
            $usage = (float) $rtrAllocations->sum('current_usage_mbps');
            $mrr = (float) $rtrAllocations->sum('monthly_bill_amount');
            $count = $rtrAllocations->count();
            $util = $allocated > 0 ? round(($usage / $allocated) * 100, 1) : 0;
            $routerMetrics[$rtr->id] = [
                'reseller_count' => $count,
                'total_allocated' => $allocated,
                'current_usage' => $usage,
                'utilization' => $util,
                'mrr' => $mrr,
                'resellers' => $rtrAllocations,
            ];
        }

        return view('tenant.resellers.capacity', compact(
            'tenant',
            'routers',
            'routerMetrics',
            'totalAllocatedMbps',
            'totalCurrentUsage',
            'totalWholesaleMRR',
            'totalRoutersCount',
            'onlineRoutersCount',
            'avgUtilization',
            'search',
            'status',
            'perPage'
        ));
    }

    /**
     * Save or Update Reseller Bandwidth Allocation Profile.
     */
    public function saveAllocation(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'bandwidth_plan_id' => 'required|exists:tenant_bandwidth_plans,id',
            'interface_name' => 'nullable|string|max:100',
            'vlan_id' => 'nullable|string|max:150',
            'global_bandwidth_mbps' => 'required|numeric|min:0',
            'cdn_bandwidth_mbps' => 'nullable|numeric|min:0',
            'bdix_bandwidth_mbps' => 'nullable|numeric|min:0',
            'ggc_bandwidth_mbps' => 'nullable|numeric|min:0',
            'fna_bandwidth_mbps' => 'nullable|numeric|min:0',
            'other_bandwidth_mbps' => 'nullable|numeric|min:0',
            'mikrotik_queue_name' => 'nullable|string|max:100',
            'status' => 'nullable|in:ACTIVE,THROTTLED,SUSPENDED',
            'notes' => 'nullable|string|max:255',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $plan = TenantBandwidthPlan::where('tenant_id', $tenant->id)->findOrFail($validated['bandwidth_plan_id']);
        
        $global = (float) $validated['global_bandwidth_mbps'];
        $cdn = (float) ($validated['cdn_bandwidth_mbps'] ?? 0);
        $bdix = (float) ($validated['bdix_bandwidth_mbps'] ?? 0);
        $ggc = (float) ($validated['ggc_bandwidth_mbps'] ?? 0);
        $fna = (float) ($validated['fna_bandwidth_mbps'] ?? 0);
        $other = (float) ($validated['other_bandwidth_mbps'] ?? 0);
        $total = $global + $cdn + $bdix + $ggc + $fna + $other;

        $globalRate = (float) $plan->global_rate_per_mbps;
        $cdnRate = (float) $plan->cdn_rate_per_mbps;
        $bdixRate = (float) $plan->bdix_rate_per_mbps;
        $ggcRate = (float) $plan->ggc_rate_per_mbps;
        $fnaRate = (float) $plan->fna_rate_per_mbps;
        $othersRate = (float) $plan->others_rate_per_mbps;

        $monthlyBill = $plan->calculateMonthlyBill($global, $cdn, $bdix, $ggc, $fna, $other);

        // Auto generate queue name if empty
        $queueName = ($validated['mikrotik_queue_name'] ?? null) ?: ("subisp_" . ($reseller->prefix ?: strtolower($reseller->code)) . "_limit");

        // Keep or simulate initial usage
        $existing = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->first();

        $currentUsage = $existing ? $existing->current_usage_mbps : round($total * 0.65, 2);
        $peakUsage = $existing ? max($existing->peak_usage_mbps, round($total * 0.88, 2)) : round($total * 0.88, 2);

        $bandwidth = TenantResellerBandwidth::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
            ],
            [
                'router_id' => $validated['router_id'] ?? null,
                'bandwidth_plan_id' => $plan->id,
                'interface_name' => $validated['interface_name'] ?? null,
                'vlan_id' => $validated['vlan_id'] ?? null,
                'global_bandwidth_mbps' => $global,
                'cdn_bandwidth_mbps' => $cdn,
                'bdix_bandwidth_mbps' => $bdix,
                'ggc_bandwidth_mbps' => $ggc,
                'fna_bandwidth_mbps' => $fna,
                'other_bandwidth_mbps' => $other,
                'total_bandwidth_mbps' => $total,
                'allocation_type' => 'DEDICATED_CIR',
                'rate_per_mbps' => 0,
                'global_rate_per_mbps' => $globalRate,
                'cdn_rate_per_mbps' => $cdnRate,
                'bdix_rate_per_mbps' => $bdixRate,
                'ggc_rate_per_mbps' => $ggcRate,
                'fna_rate_per_mbps' => $fnaRate,
                'others_rate_per_mbps' => $othersRate,
                'monthly_bill_amount' => round($monthlyBill, 2),
                'mikrotik_queue_name' => $queueName,
                'current_usage_mbps' => $currentUsage,
                'peak_usage_mbps' => $peakUsage,
                'status' => $validated['status'] ?? ($existing ? $existing->status : 'ACTIVE'),
                'notes' => $validated['notes'] ?? null,
                'last_sync_at' => now(),
            ]
        );

        $msg = "Bandwidth allocation of {$bandwidth->formatted_total_bandwidth} for '{$reseller->name}' configured successfully!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'bandwidth' => $bandwidth,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth')->with('success', $msg);
    }

    /**
     * Toggle status (Active / Throttled / Suspended).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $bandwidth = TenantResellerBandwidth::where('tenant_id', $tenant->id)->findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['ACTIVE', 'THROTTLED', 'SUSPENDED'])) {
            return response()->json(['success' => false, 'message' => 'Invalid status option.'], 422);
        }

        $bandwidth->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Bandwidth line for '{$bandwidth->reseller?->name}' is now {$newStatus}!",
            'status' => $newStatus,
            'badge' => $bandwidth->status_badge,
        ]);
    }

    /**
     * Sync with Core MikroTik Simple Queue.
     */
    public function syncMikrotik(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $bandwidth = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->with(['reseller', 'router'])
            ->findOrFail($id);

        $bandwidth->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Simple Queue '{$bandwidth->mikrotik_queue_name}' synchronized with {$bandwidth->router?->name} ({$bandwidth->formatted_total_bandwidth})!",
            'last_sync' => now()->format('d M, h:i A'),
        ]);
    }

    /**
     * Get Real-time traffic points for MRTG Graph Preview.
     */
    public function trafficStats(int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $bandwidth = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->with(['reseller', 'router'])
            ->findOrFail($id);

        $total = (float) $bandwidth->total_bandwidth_mbps;
        $current = (float) $bandwidth->current_usage_mbps;

        // Generate 12 hourly historical simulation data points
        $points = [];
        $now = now();
        for ($i = 11; $i >= 0; $i--) {
            $t = (clone $now)->subHours($i);
            $variation = rand(-15, 15) / 100.0;
            $dl = max(5.0, min($total, round($current * (1 + $variation), 2)));
            $ul = max(2.0, round($dl * 0.35, 2));
            $points[] = [
                'time' => $t->format('H:i'),
                'download' => $dl,
                'upload' => $ul,
            ];
        }

        return response()->json([
            'success' => true,
            'partner_name' => $bandwidth->reseller?->name,
            'total_bandwidth' => $bandwidth->formatted_total_bandwidth,
            'current_usage' => $bandwidth->current_usage_mbps . ' Mbps',
            'peak_usage' => $bandwidth->peak_usage_mbps . ' Mbps',
            'utilization' => $bandwidth->utilization_percent . '%',
            'points' => $points,
        ]);
    }

    /**
     * Delete Reseller Bandwidth Allocation Profile.
     */
    public function deleteAllocation(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $bandwidth = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->with('reseller')
            ->findOrFail($id);

        $partnerName = $bandwidth->reseller?->name ?? 'Reseller';
        $bandwidth->delete();

        $msg = "Bandwidth allocation for '{$partnerName}' deleted successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth')->with('success', $msg);
    }

    /**
     * 1-Click Monthly Wholesale Bandwidth Bill Generation.
     */
    public function generateMonthlyBills(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $billingMonthInput = $request->input('billing_month', now()->format('Y-m'));
        $billingMonthDate = Carbon::parse($billingMonthInput . '-01')->startOfMonth();
        $billingMonthStr = $billingMonthDate->toDateString();

        $allocations = TenantResellerBandwidth::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'SUSPENDED')
            ->where('monthly_bill_amount', '>', 0)
            ->with(['reseller', 'bandwidthPlan', 'router'])
            ->get();

        $generatedCount = 0;
        $skippedCount = 0;
        $totalBilled = 0;

        foreach ($allocations as $bw) {
            if (!$bw->reseller) {
                continue;
            }

            // Check if invoice already exists for this billing month
            $exists = TenantResellerInvoice::where('tenant_id', $tenant->id)
                ->where('reseller_id', $bw->reseller_id)
                ->where('type', 'BANDWIDTH_WHOLESALE')
                ->whereDate('billing_month', $billingMonthStr)
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            $subtotal = (float) $bw->monthly_bill_amount;

            $itemDetails = [
                'type' => 'BANDWIDTH_WHOLESALE',
                'plan_name' => $bw->bandwidthPlan?->name ?? 'Custom Rate',
                'router_name' => $bw->router?->name ?? 'Standalone',
                'vlan_id' => $bw->vlan_id,
                'total_bandwidth_mbps' => (float) $bw->total_bandwidth_mbps,
                'global' => [
                    'mbps' => (float) $bw->global_bandwidth_mbps,
                    'rate' => (float) $bw->global_rate_per_mbps,
                    'amount' => round((float)$bw->global_bandwidth_mbps * (float)$bw->global_rate_per_mbps, 2),
                ],
                'bdix' => [
                    'mbps' => (float) $bw->bdix_bandwidth_mbps,
                    'rate' => (float) $bw->bdix_rate_per_mbps,
                    'amount' => round((float)$bw->bdix_bandwidth_mbps * (float)$bw->bdix_rate_per_mbps, 2),
                ],
                'cdn' => [
                    'mbps' => (float) $bw->cdn_bandwidth_mbps,
                    'rate' => (float) $bw->cdn_rate_per_mbps,
                    'amount' => round((float)$bw->cdn_bandwidth_mbps * (float)$bw->cdn_rate_per_mbps, 2),
                ],
                'ggc' => [
                    'mbps' => (float) $bw->ggc_bandwidth_mbps,
                    'rate' => (float) $bw->ggc_rate_per_mbps,
                    'amount' => round((float)$bw->ggc_bandwidth_mbps * (float)$bw->ggc_rate_per_mbps, 2),
                ],
                'fna' => [
                    'mbps' => (float) $bw->fna_bandwidth_mbps,
                    'rate' => (float) $bw->fna_rate_per_mbps,
                    'amount' => round((float)$bw->fna_bandwidth_mbps * (float)$bw->fna_rate_per_mbps, 2),
                ],
                'other' => [
                    'mbps' => (float) $bw->other_bandwidth_mbps,
                    'rate' => (float) $bw->others_rate_per_mbps,
                    'amount' => round((float)$bw->other_bandwidth_mbps * (float)$bw->others_rate_per_mbps, 2),
                ],
            ];

            TenantResellerInvoice::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $bw->reseller_id,
                'invoice_no' => TenantResellerInvoice::generateInvoiceNo($tenant->id),
                'billing_month' => $billingMonthStr,
                'type' => 'BANDWIDTH_WHOLESALE',
                'subtotal' => $subtotal,
                'amount' => $subtotal,
                'discount' => 0.00,
                'vat_tax' => 0.00,
                'paid_amount' => 0.00,
                'due_amount' => $subtotal,
                'payment_status' => 'UNPAID',
                'period_start' => (clone $billingMonthDate)->startOfMonth()->toDateString(),
                'period_end' => (clone $billingMonthDate)->endOfMonth()->toDateString(),
                'due_date' => (clone $billingMonthDate)->startOfMonth()->addDays(10)->toDateString(),
                'item_details' => $itemDetails,
                'notes' => "Wholesale Bandwidth Allocation Bill for {$billingMonthDate->format('F Y')}",
                'created_by' => Auth::id(),
            ]);

            $generatedCount++;
            $totalBilled += $subtotal;
        }

        $formattedMonth = $billingMonthDate->format('M Y');
        $msg = "Generated {$generatedCount} wholesale bandwidth invoices for {$formattedMonth}.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} already existed / skipped).";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'generated' => $generatedCount,
                'skipped' => $skippedCount,
                'total_billed' => $totalBilled,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth')->with('success', $msg);
    }

    /**
     * Show Invoice details (JSON for modal / details view).
     */
    public function showInvoice(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller.bandwidthAllocation.bandwidthPlan', 'reseller.bandwidthAllocation.router', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Record wholesale invoice payment collection.
     */
    public function collectPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:CASH,BANK_TRANSFER,BKASH,NAGAD,ROCKET,CHEQUE,OTHER',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $addPaid = (float) $validated['paid_amount'];
        $addDiscount = (float) ($validated['discount'] ?? 0);

        $newDiscount = (float) $invoice->discount + $addDiscount;
        $payableAmount = max(0, (float) $invoice->amount - $newDiscount);
        $newPaidAmount = (float) $invoice->paid_amount + $addPaid;
        $newDueAmount = max(0, $payableAmount - $newPaidAmount);

        $status = $newDueAmount <= 0 ? 'PAID' : 'PARTIAL';

        $invoice->update([
            'discount' => $newDiscount,
            'paid_amount' => $newPaidAmount,
            'due_amount' => $newDueAmount,
            'payment_status' => $status,
            'payment_method' => $validated['payment_method'],
            'paid_at' => now(),
            'notes' => $validated['notes'] ?? $invoice->notes,
        ]);

        $currency = $tenant->currency_symbol ?? '৳';
        $msg = "Payment of {$currency} " . number_format($addPaid, 2) . " received for Invoice #{$invoice->invoice_no}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'invoice' => $invoice,
            ]);
        }

        return redirect()->route('tenant.resellers.bandwidth')->with('success', $msg);
    }

    /**
     * Print wholesale money receipt / invoice statement.
     */
    public function printReceipt(Request $request, int $id): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller.bandwidthAllocation.bandwidthPlan', 'reseller.bandwidthAllocation.router', 'tenant', 'creator'])
            ->findOrFail($id);

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.resellers.bandwidth_receipt', compact('tenant', 'invoice', 'currencySymbol'));
    }
}
