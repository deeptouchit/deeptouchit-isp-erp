<?php

namespace App\Http\Controllers\Tenant\Reseller;

use App\Http\Controllers\Controller;
use App\Models\TenantReseller;
use App\Models\TenantResellerInvoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResellerSubscriptionController extends Controller
{
    /**
     * Display Reseller Panel Licenses & Subscription Status.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $panelStatusFilter = $request->input('panel_status');
        $perPage = (int) $request->input('per_page', 20);

        // 1. Fetch All Resellers for filter dropdown & KPI sums
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Resellers for Licenses Grid
        $resellerQuery = TenantReseller::where('tenant_id', $tenant->id);

        if ($search) {
            $resellerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('prefix', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($selectedResellerId) {
            $resellerQuery->where('id', $selectedResellerId);
        }

        $today = Carbon::today();
        $soonThreshold = Carbon::today()->addDays(7);

        if ($panelStatusFilter === 'active') {
            $resellerQuery->where('panel_billing_status', 'ACTIVE')
                ->where(function ($q) use ($today) {
                    $q->whereNull('panel_expiry_date')->orWhere('panel_expiry_date', '>=', $today);
                });
        } elseif ($panelStatusFilter === 'expiring_soon') {
            $resellerQuery->where('panel_billing_status', 'ACTIVE')
                ->whereBetween('panel_expiry_date', [$today, $soonThreshold]);
        } elseif ($panelStatusFilter === 'expired') {
            $resellerQuery->where(function ($q) use ($today) {
                $q->where('panel_billing_status', 'EXPIRED')
                  ->orWhere('panel_expiry_date', '<', $today);
            });
        } elseif ($panelStatusFilter === 'grace_period') {
            $resellerQuery->where('panel_billing_status', 'GRACE_PERIOD');
        }

        $resellers = $resellerQuery->orderBy('panel_expiry_date', 'asc')->paginate($perPage)->withQueryString();

        // 3. Calculate 6-Card Metric KPIs
        $totalMonthlyMRR = (float) $allResellers->sum('monthly_panel_charge');
        $activePanelsCount = $allResellers->where('panel_billing_status', 'ACTIVE')
            ->filter(fn($r) => empty($r->panel_expiry_date) || Carbon::parse($r->panel_expiry_date)->gte($today))
            ->count();
        $expiringSoonCount = $allResellers->filter(function ($r) use ($today, $soonThreshold) {
            return $r->panel_billing_status === 'ACTIVE' &&
                   $r->panel_expiry_date &&
                   Carbon::parse($r->panel_expiry_date)->between($today, $soonThreshold);
        })->count();
        $expiredPanelsCount = $allResellers->filter(function ($r) use ($today) {
            return $r->panel_billing_status === 'EXPIRED' ||
                   ($r->panel_expiry_date && Carbon::parse($r->panel_expiry_date)->lt($today));
        })->count();
        $gracePeriodCount = $allResellers->where('panel_billing_status', 'GRACE_PERIOD')->count();
        $totalResellersCount = $allResellers->count();

        return view('tenant.resellers.subscriptions', compact(
            'tenant',
            'resellers',
            'allResellers',
            'totalMonthlyMRR',
            'activePanelsCount',
            'expiringSoonCount',
            'expiredPanelsCount',
            'gracePeriodCount',
            'totalResellersCount',
            'search',
            'selectedResellerId',
            'panelStatusFilter',
            'perPage'
        ));
    }

    /**
     * Manual Renewal / Software Subscription Extension for a Reseller.
     */
    public function renew(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'months' => 'required|integer|min:1|max:36',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:WALLET_DEDUCT,CASH,BANK,BKASH,NAGAD,DUE',
            'notes' => 'nullable|string|max:255',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)
            ->findOrFail($validated['reseller_id']);

        $months = (int) $validated['months'];
        $amount = (float) $validated['amount'];
        $method = $validated['payment_method'];

        // If paying via wallet, check balance
        if ($method === 'WALLET_DEDUCT') {
            $available = (float) $reseller->wallet_balance + (float) $reseller->credit_limit;
            if ($available < $amount) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient wallet balance. Available: ৳{$available}, Required: ৳{$amount}",
                    ], 422);
                }
                return back()->with('error', "Insufficient wallet balance for {$reseller->name}.");
            }
        }

        DB::beginTransaction();
        try {
            // Deduct from wallet if applicable
            if ($method === 'WALLET_DEDUCT' && $amount > 0) {
                $reseller->decrement('wallet_balance', $amount);
            }

            // Calculate new expiry date
            $currentExpiry = $reseller->panel_expiry_date ? Carbon::parse($reseller->panel_expiry_date) : Carbon::today();
            $baseDate = $currentExpiry->isPast() ? Carbon::today() : $currentExpiry;
            $newExpiry = $baseDate->copy()->addMonths($months);

            $periodStart = $baseDate->toDateString();
            $periodEnd = $newExpiry->toDateString();

            // Update Reseller Panel Status & Expiry
            $reseller->update([
                'panel_expiry_date' => $newExpiry->toDateString(),
                'panel_billing_status' => 'ACTIVE',
            ]);

            // Create Invoice Record
            $invoiceNo = TenantResellerInvoice::generateInvoiceNo($tenant->id);
            $isPaid = ($method !== 'DUE');

            $invoice = TenantResellerInvoice::create([
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => Carbon::today()->startOfMonth()->toDateString(),
                'type' => 'PANEL_SOFTWARE_FEE',
                'amount' => $amount,
                'paid_amount' => $isPaid ? $amount : 0.00,
                'due_amount' => $isPaid ? 0.00 : $amount,
                'payment_status' => $isPaid ? 'PAID' : 'UNPAID',
                'payment_method' => $method,
                'paid_at' => $isPaid ? now() : null,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'notes' => $validated['notes'] ?: "Software subscription renewed for {$months} month(s). Extended to {$periodEnd}.",
            ]);

            DB::commit();

            $msg = "Software subscription renewed for '{$reseller->name}' until " . $newExpiry->format('d M, Y') . " (Invoice: {$invoiceNo}).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'invoice' => $invoice,
                ]);
            }

            return redirect()->route('tenant.resellers.subscriptions')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Renewal failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Renewal failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk Generate Monthly Software Invoices for All Active Resellers.
     */
    public function generateMonthlyBills(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'auto_deduct_wallet' => 'nullable|boolean',
        ]);

        $monthDate = Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth();
        $autoDeduct = !empty($validated['auto_deduct_wallet']);

        $resellers = TenantReseller::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('monthly_panel_charge', '>', 0)
            ->get();

        $generatedCount = 0;
        $totalBilled = 0;

        DB::beginTransaction();
        try {
            foreach ($resellers as $reseller) {
                // Check if already billed for this month
                $alreadyBilled = TenantResellerInvoice::where('tenant_id', $tenant->id)
                    ->where('reseller_id', $reseller->id)
                    ->where('type', 'PANEL_SOFTWARE_FEE')
                    ->whereDate('billing_month', $monthDate->toDateString())
                    ->exists();

                if ($alreadyBilled) {
                    continue;
                }

                $charge = (float) $reseller->monthly_panel_charge;
                $invoiceNo = TenantResellerInvoice::generateInvoiceNo($tenant->id);

                $canDeduct = false;
                if ($autoDeduct && ((float)$reseller->wallet_balance + (float)$reseller->credit_limit) >= $charge) {
                    $canDeduct = true;
                    $reseller->decrement('wallet_balance', $charge);
                }

                TenantResellerInvoice::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $monthDate->toDateString(),
                    'type' => 'PANEL_SOFTWARE_FEE',
                    'amount' => $charge,
                    'paid_amount' => $canDeduct ? $charge : 0.00,
                    'due_amount' => $canDeduct ? 0.00 : $charge,
                    'payment_status' => $canDeduct ? 'PAID' : 'UNPAID',
                    'payment_method' => $canDeduct ? 'WALLET_DEDUCT' : null,
                    'paid_at' => $canDeduct ? now() : null,
                    'period_start' => $monthDate->toDateString(),
                    'period_end' => $monthDate->copy()->endOfMonth()->toDateString(),
                    'notes' => "Monthly software panel fee for " . $monthDate->format('F Y'),
                ]);

                $generatedCount++;
                $totalBilled += $charge;
            }

            DB::commit();

            $msg = "Generated {$generatedCount} software invoices for " . $monthDate->format('F Y') . " (Total: ৳" . number_format($totalBilled, 2) . ").";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'count' => $generatedCount,
                ]);
            }

            return redirect()->route('tenant.resellers.subscriptions')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Bulk billing failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Bulk billing failed: ' . $e->getMessage());
        }
    }

    /**
     * Update Monthly Software Fee or Grace Period for a Reseller.
     */
    public function updatePanelCharge(Request $request): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reseller_id' => 'required|exists:tenant_resellers,id',
            'monthly_panel_charge' => 'required|numeric|min:0',
            'panel_expiry_date' => 'nullable|date',
            'panel_billing_status' => 'nullable|in:ACTIVE,EXPIRED,GRACE_PERIOD',
        ]);

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($validated['reseller_id']);
        $reseller->update([
            'monthly_panel_charge' => $validated['monthly_panel_charge'],
            'panel_expiry_date' => $validated['panel_expiry_date'] ?? $reseller->panel_expiry_date,
            'panel_billing_status' => $validated['panel_billing_status'] ?? $reseller->panel_billing_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Panel subscription settings updated for '{$reseller->name}'!",
        ]);
    }

    /**
     * Toggle Panel Access Status (Active, Grace Period, Expired).
     */
    public function togglePanelStatus(Request $request, int $id): JsonResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $reseller = TenantReseller::where('tenant_id', $tenant->id)->findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['ACTIVE', 'EXPIRED', 'GRACE_PERIOD'])) {
            $newStatus = ($reseller->panel_billing_status === 'ACTIVE') ? 'EXPIRED' : 'ACTIVE';
        }

        $reseller->update(['panel_billing_status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Reseller '{$reseller->name}' panel status set to {$newStatus}.",
            'new_status' => $newStatus,
        ]);
    }
}
