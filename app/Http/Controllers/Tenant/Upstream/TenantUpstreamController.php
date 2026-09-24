<?php

namespace App\Http\Controllers\Tenant\Upstream;

use App\Http\Controllers\Controller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantRouter;
use App\Models\TenantUpstreamInvoice;
use App\Models\TenantUpstreamLink;
use App\Models\TenantUpstreamPayment;
use App\Models\TenantUpstreamProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantUpstreamController extends Controller
{
    /**
     * Get the active tenant instance.
     */
    protected function getTenant()
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Display Upstream Providers & Carrier Accounting Dashboard.
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        $search = $request->input('search');
        $carrierType = $request->input('carrier_type');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $query = TenantUpstreamProvider::where('tenant_id', $tenant->id)
            ->with(['links.router', 'invoices', 'payments']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('bank_account_no', 'like', "%{$search}%");
            });
        }

        if ($carrierType) {
            $query->where('carrier_type', $carrierType);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === '1' || $status === 'active');
        }

        $providers = $query->latest('id')->paginate($perPage)->withQueryString();

        // Routers for selection
        $routers = TenantRouter::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'ip_address']);

        // All active links for KPI calculation
        $allLinks = TenantUpstreamLink::where('tenant_id', $tenant->id)
            ->where('status', 'ACTIVE')
            ->get();

        $totalUpstreamMbps = (float) $allLinks->sum('total_mbps');
        $globalMbps = (float) $allLinks->sum('global_mbps');
        $peeringCacheMbps = (float) ($allLinks->sum('bdix_mbps') + $allLinks->sum('cdn_mbps') + $allLinks->sum('ggc_mbps') + $allLinks->sum('fna_mbps') + $allLinks->sum('other_mbps'));
        $monthlyCarrierCost = (float) $allLinks->sum('est_monthly_bill');

        $carrierTotalDue = (float) TenantUpstreamInvoice::where('tenant_id', $tenant->id)->sum('due_amount');

        // Wholesale Selling MRR to calculate gross margin
        $wholesaleSellingMrr = (float) TenantResellerBandwidth::where('tenant_id', $tenant->id)->sum('monthly_bill_amount');
        $grossMargin = $wholesaleSellingMrr - $monthlyCarrierCost;

        // Recent Invoices & Payments for drawers / quick tabs
        $recentInvoices = TenantUpstreamInvoice::where('tenant_id', $tenant->id)
            ->with(['provider', 'link'])
            ->latest('id')
            ->take(15)
            ->get();

        $recentPayments = TenantUpstreamPayment::where('tenant_id', $tenant->id)
            ->with(['provider', 'invoice'])
            ->latest('id')
            ->take(15)
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.upstream.index', compact(
            'tenant',
            'providers',
            'routers',
            'totalUpstreamMbps',
            'globalMbps',
            'peeringCacheMbps',
            'monthlyCarrierCost',
            'carrierTotalDue',
            'grossMargin',
            'recentInvoices',
            'recentPayments',
            'currencySymbol'
        ));
    }

    /**
     * Store a new Upstream Carrier Provider and optional initial link.
     */
    public function storeProvider(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'carrier_type' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:255',
            'routing_no' => 'nullable|string|max:50',
            'notes' => 'nullable|string',

            // Link details
            'router_id' => 'nullable|exists:tenant_routers,id',
            'link_name' => 'nullable|string|max:255',
            'interface_port' => 'nullable|string|max:100',
            'circuit_id' => 'nullable|string|max:100',
            'global_mbps' => 'nullable|numeric|min:0',
            'bdix_mbps' => 'nullable|numeric|min:0',
            'cdn_mbps' => 'nullable|numeric|min:0',
            'ggc_mbps' => 'nullable|numeric|min:0',
            'fna_mbps' => 'nullable|numeric|min:0',
            'other_mbps' => 'nullable|numeric|min:0',
            'global_rate' => 'nullable|numeric|min:0',
            'bdix_rate' => 'nullable|numeric|min:0',
            'cdn_rate' => 'nullable|numeric|min:0',
            'ggc_rate' => 'nullable|numeric|min:0',
            'fna_rate' => 'nullable|numeric|min:0',
            'other_rate' => 'nullable|numeric|min:0',
            'monthly_transmission_cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($tenant, $validated) {
            $provider = TenantUpstreamProvider::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'carrier_type' => $validated['carrier_type'] ?? 'Upstream Carrier',
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'routing_no' => $validated['routing_no'] ?? null,
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create Link if link info provided
            $linkName = $validated['link_name'] ?? ($validated['name'] . ' Main Delivery');
            $link = new TenantUpstreamLink([
                'tenant_id' => $tenant->id,
                'provider_id' => $provider->id,
                'router_id' => $validated['router_id'] ?? null,
                'link_name' => $linkName,
                'interface_port' => $validated['interface_port'] ?? null,
                'circuit_id' => $validated['circuit_id'] ?? null,
                'global_mbps' => (float) ($validated['global_mbps'] ?? 0),
                'bdix_mbps' => (float) ($validated['bdix_mbps'] ?? 0),
                'cdn_mbps' => (float) ($validated['cdn_mbps'] ?? 0),
                'ggc_mbps' => (float) ($validated['ggc_mbps'] ?? 0),
                'fna_mbps' => (float) ($validated['fna_mbps'] ?? 0),
                'other_mbps' => (float) ($validated['other_mbps'] ?? 0),
                'global_rate' => (float) ($validated['global_rate'] ?? 0),
                'bdix_rate' => (float) ($validated['bdix_rate'] ?? 0),
                'cdn_rate' => (float) ($validated['cdn_rate'] ?? 0),
                'ggc_rate' => (float) ($validated['ggc_rate'] ?? 0),
                'fna_rate' => (float) ($validated['fna_rate'] ?? 0),
                'other_rate' => (float) ($validated['other_rate'] ?? 0),
                'monthly_transmission_cost' => (float) ($validated['monthly_transmission_cost'] ?? 0),
                'status' => 'ACTIVE',
            ]);
            $link->recalculateTotals();
            $link->save();
        });

        return back()->with('success', "Upstream Carrier '{$validated['name']}' added successfully.");
    }

    /**
     * Update an Upstream Carrier Provider and Link.
     */
    public function updateProvider(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $provider = TenantUpstreamProvider::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'carrier_type' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:255',
            'routing_no' => 'nullable|string|max:50',
            'notes' => 'nullable|string',

            // Link updates
            'link_id' => 'nullable|exists:tenant_upstream_links,id',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'link_name' => 'nullable|string|max:255',
            'interface_port' => 'nullable|string|max:100',
            'circuit_id' => 'nullable|string|max:100',
            'global_mbps' => 'nullable|numeric|min:0',
            'bdix_mbps' => 'nullable|numeric|min:0',
            'cdn_mbps' => 'nullable|numeric|min:0',
            'ggc_mbps' => 'nullable|numeric|min:0',
            'fna_mbps' => 'nullable|numeric|min:0',
            'other_mbps' => 'nullable|numeric|min:0',
            'global_rate' => 'nullable|numeric|min:0',
            'bdix_rate' => 'nullable|numeric|min:0',
            'cdn_rate' => 'nullable|numeric|min:0',
            'ggc_rate' => 'nullable|numeric|min:0',
            'fna_rate' => 'nullable|numeric|min:0',
            'other_rate' => 'nullable|numeric|min:0',
            'monthly_transmission_cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($tenant, $provider, $validated) {
            $provider->update([
                'name' => $validated['name'],
                'carrier_type' => $validated['carrier_type'] ?? $provider->carrier_type ?? 'Upstream Carrier',
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'routing_no' => $validated['routing_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $link = null;
            if (!empty($validated['link_id'])) {
                $link = TenantUpstreamLink::where('tenant_id', $tenant->id)
                    ->where('provider_id', $provider->id)
                    ->find($validated['link_id']);
            }

            if (!$link) {
                $link = $provider->links()->first();
            }

            if (!$link) {
                $link = new TenantUpstreamLink([
                    'tenant_id' => $tenant->id,
                    'provider_id' => $provider->id,
                ]);
            }

            $link->router_id = $validated['router_id'] ?? $link->router_id;
            $link->link_name = $validated['link_name'] ?? ($link->link_name ?: ($validated['name'] . ' Main Delivery'));
            $link->interface_port = $validated['interface_port'] ?? $link->interface_port;
            $link->circuit_id = $validated['circuit_id'] ?? $link->circuit_id;
            $link->global_mbps = (float) ($validated['global_mbps'] ?? 0);
            $link->bdix_mbps = (float) ($validated['bdix_mbps'] ?? 0);
            $link->cdn_mbps = (float) ($validated['cdn_mbps'] ?? 0);
            $link->ggc_mbps = (float) ($validated['ggc_mbps'] ?? 0);
            $link->fna_mbps = (float) ($validated['fna_mbps'] ?? 0);
            $link->other_mbps = (float) ($validated['other_mbps'] ?? 0);
            $link->global_rate = (float) ($validated['global_rate'] ?? 0);
            $link->bdix_rate = (float) ($validated['bdix_rate'] ?? 0);
            $link->cdn_rate = (float) ($validated['cdn_rate'] ?? 0);
            $link->ggc_rate = (float) ($validated['ggc_rate'] ?? 0);
            $link->fna_rate = (float) ($validated['fna_rate'] ?? 0);
            $link->other_rate = (float) ($validated['other_rate'] ?? 0);
            $link->monthly_transmission_cost = (float) ($validated['monthly_transmission_cost'] ?? 0);
            $link->recalculateTotals();
            $link->save();
        });

        return back()->with('success', "Upstream Carrier '{$provider->name}' updated successfully.");
    }

    /**
     * Toggle Carrier Active/Inactive Status (AGENTS.md Rule 5 compliant).
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $provider = TenantUpstreamProvider::where('tenant_id', $tenant->id)->findOrFail($id);
        $provider->is_active = !$provider->is_active;
        $provider->save();

        $stateText = $provider->is_active ? 'enabled' : 'disabled';

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $provider->is_active,
                'message' => "Carrier '{$provider->name}' is now {$stateText}.",
            ]);
        }

        return back()->with('success', "Carrier '{$provider->name}' has been {$stateText} successfully.");
    }

    /**
     * Store a Carrier Monthly Bill / Invoice.
     */
    public function storeInvoice(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'provider_id' => 'required|exists:tenant_upstream_providers,id',
            'link_id' => 'nullable|exists:tenant_upstream_links,id',
            'invoice_no' => 'required|string|max:100',
            'billing_month' => 'required|date',
            'bandwidth_cost' => 'required|numeric|min:0',
            'transmission_cost' => 'nullable|numeric|min:0',
            'vat_tax' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $bwCost = (float) $validated['bandwidth_cost'];
        $transCost = (float) ($validated['transmission_cost'] ?? 0);
        $vatTax = (float) ($validated['vat_tax'] ?? 0);
        $other = (float) ($validated['other_charges'] ?? 0);

        $totalAmount = round($bwCost + $transCost + $vatTax + $other, 2);

        TenantUpstreamInvoice::create([
            'tenant_id' => $tenant->id,
            'provider_id' => $validated['provider_id'],
            'link_id' => $validated['link_id'] ?? null,
            'invoice_no' => $validated['invoice_no'],
            'billing_month' => $validated['billing_month'],
            'bandwidth_cost' => $bwCost,
            'transmission_cost' => $transCost,
            'vat_tax' => $vatTax,
            'other_charges' => $other,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'due_amount' => $totalAmount,
            'payment_status' => 'UNPAID',
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Carrier Bill '{$validated['invoice_no']}' recorded successfully.");
    }

    /**
     * Record a Payment Disbursement to Upstream Carrier.
     */
    public function storePayment(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'provider_id' => 'required|exists:tenant_upstream_providers,id',
            'invoice_id' => 'nullable|exists:tenant_upstream_invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:BANK_TRANSFER,CHEQUE,RTGS,CASH,OTHER',
            'bank_name' => 'nullable|string|max:255',
            'cheque_no' => 'nullable|string|max:100',
            'transaction_ref' => 'nullable|string|max:100',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $paymentAmount = (float) $validated['amount'];

        DB::transaction(function () use ($tenant, $validated, $paymentAmount) {
            // Generate Voucher Number: UPV-YYYYMM-001
            $monthPrefix = date('Ym', strtotime($validated['paid_at']));
            $countThisMonth = TenantUpstreamPayment::where('tenant_id', $tenant->id)
                ->where('voucher_no', 'like', "UPV-{$monthPrefix}-%")
                ->count();
            $voucherNo = sprintf("UPV-%s-%03d", $monthPrefix, $countThisMonth + 1);

            $payment = TenantUpstreamPayment::create([
                'tenant_id' => $tenant->id,
                'provider_id' => $validated['provider_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'voucher_no' => $voucherNo,
                'amount' => $paymentAmount,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'cheque_no' => $validated['cheque_no'] ?? null,
                'transaction_ref' => $validated['transaction_ref'] ?? null,
                'paid_at' => $validated['paid_at'],
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // If an invoice is specified, update invoice balance
            if (!empty($validated['invoice_id'])) {
                $invoice = TenantUpstreamInvoice::where('tenant_id', $tenant->id)->find($validated['invoice_id']);
                if ($invoice) {
                    $invoice->paid_amount = min((float) $invoice->total_amount, (float) $invoice->paid_amount + $paymentAmount);
                    $invoice->due_amount = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
                    if ($invoice->due_amount <= 0) {
                        $invoice->payment_status = 'PAID';
                    } elseif ($invoice->paid_amount > 0) {
                        $invoice->payment_status = 'PARTIAL';
                    }
                    $invoice->save();
                }
            }
        });

        return back()->with('success', "Payment voucher generated & recorded successfully.");
    }

    /**
     * Printable Bank Payment Voucher.
     */
    public function voucher(Request $request, $id): View
    {
        $tenant = $this->getTenant();
        $payment = TenantUpstreamPayment::where('tenant_id', $tenant->id)
            ->with(['provider', 'invoice', 'creator'])
            ->findOrFail($id);

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.upstream.voucher', compact('tenant', 'payment', 'currencySymbol'));
    }

    /**
     * Delete an Upstream Carrier Provider.
     */
    public function destroyProvider(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $provider = TenantUpstreamProvider::where('tenant_id', $tenant->id)->findOrFail($id);

        $invoicesCount = $provider->invoices()->count();
        if ($invoicesCount > 0) {
            $msg = "Cannot delete carrier '{$provider->name}' because it has {$invoicesCount} billing invoices recorded.";
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $providerName = $provider->name;
        $provider->delete();

        $msg = "Carrier '{$providerName}' deleted successfully.";
        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Display Carrier Monthly Invoices / Bills list.
     */
    public function invoices(Request $request): View
    {
        $tenant = $this->getTenant();

        $search = $request->input('search');
        $providerId = $request->input('provider_id');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $query = TenantUpstreamInvoice::where('tenant_id', $tenant->id)
            ->with(['provider', 'link', 'payments']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('provider', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        if ($status) {
            $query->where('payment_status', strtoupper($status));
        }

        $invoices = $query->latest('id')->paginate($perPage)->withQueryString();

        // Providers list with links for filtering, modal and auto-calculating upstream bandwidth
        $providers = TenantUpstreamProvider::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['links.router'])
            ->get();

        // 6 KPI Metrics
        $allInvoices = TenantUpstreamInvoice::where('tenant_id', $tenant->id)->get();
        $totalInvoicesCount = $allInvoices->count();
        $totalBilledAmount = (float) $allInvoices->sum('total_amount');
        $totalPaidAmount = (float) $allInvoices->sum('paid_amount');
        $totalDueBalance = (float) $allInvoices->sum('due_amount');
        $paidInvoicesCount = $allInvoices->where('payment_status', 'PAID')->count();
        $unpaidInvoicesCount = $allInvoices->whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.upstream.invoices', compact(
            'tenant',
            'invoices',
            'providers',
            'totalInvoicesCount',
            'totalBilledAmount',
            'totalPaidAmount',
            'totalDueBalance',
            'paidInvoicesCount',
            'unpaidInvoicesCount',
            'currencySymbol'
        ));
    }

    /**
     * Delete an Upstream Carrier Invoice.
     */
    public function destroyInvoice(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $invoice = TenantUpstreamInvoice::where('tenant_id', $tenant->id)->findOrFail($id);

        $invoiceNo = $invoice->invoice_no;
        $invoice->delete();

        $msg = "Carrier Bill '{$invoiceNo}' deleted successfully.";
        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Display Payment Vouchers and Disbursements list.
     */
    public function payments(Request $request): View
    {
        $tenant = $this->getTenant();

        $search = $request->input('search');
        $providerId = $request->input('provider_id');
        $method = $request->input('payment_method');
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $query = TenantUpstreamPayment::where('tenant_id', $tenant->id)
            ->with(['provider', 'invoice', 'creator']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                  ->orWhere('cheque_no', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('transaction_ref', 'like', "%{$search}%")
                  ->orWhereHas('provider', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        if ($method) {
            $query->where('payment_method', $method);
        }

        $payments = $query->latest('id')->paginate($perPage)->withQueryString();

        $providers = TenantUpstreamProvider::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'carrier_type']);

        // 6 KPI Metrics
        $allPayments = TenantUpstreamPayment::where('tenant_id', $tenant->id)->get();
        $totalDisbursed = (float) $allPayments->sum('amount');
        $totalVouchersCount = $allPayments->count();
        $bankTransferTotal = (float) $allPayments->where('payment_method', 'BANK_TRANSFER')->sum('amount');
        $chequeRtgsTotal = (float) $allPayments->whereIn('payment_method', ['CHEQUE', 'RTGS'])->sum('amount');
        $cashTotal = (float) $allPayments->where('payment_method', 'CASH')->sum('amount');
        $thisMonthTotal = (float) $allPayments->where('paid_at', '>=', now()->startOfMonth())->sum('amount');

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.upstream.payments', compact(
            'tenant',
            'payments',
            'providers',
            'totalDisbursed',
            'totalVouchersCount',
            'bankTransferTotal',
            'chequeRtgsTotal',
            'cashTotal',
            'thisMonthTotal',
            'currencySymbol'
        ));
    }

    /**
     * Delete a Payment Voucher.
     */
    public function destroyPayment(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $payment = TenantUpstreamPayment::where('tenant_id', $tenant->id)->findOrFail($id);

        $voucherNo = $payment->voucher_no;
        $amount = (float) $payment->amount;
        $invoiceId = $payment->invoice_id;

        DB::transaction(function () use ($tenant, $payment, $amount, $invoiceId) {
            if ($invoiceId) {
                $invoice = TenantUpstreamInvoice::where('tenant_id', $tenant->id)->find($invoiceId);
                if ($invoice) {
                    $invoice->paid_amount = max(0, (float) $invoice->paid_amount - $amount);
                    $invoice->due_amount = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
                    if ($invoice->paid_amount <= 0) {
                        $invoice->payment_status = 'UNPAID';
                    } elseif ($invoice->due_amount > 0) {
                        $invoice->payment_status = 'PARTIAL';
                    }
                    $invoice->save();
                }
            }
            $payment->delete();
        });

        $msg = "Payment Voucher '{$voucherNo}' deleted and invoice adjusted.";
        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
