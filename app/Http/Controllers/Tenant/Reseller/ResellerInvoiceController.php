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

class ResellerInvoiceController extends Controller
{
    /**
     * Display Subscription Invoices & Receipts.
     */
    public function index(Request $request): View
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'Tenant record not found.');
        }

        $search = $request->input('search');
        $selectedResellerId = $request->input('reseller_id');
        $paymentStatus = $request->input('payment_status');
        $billingMonth = $request->input('billing_month');
        $invoiceType = $request->input('type');
        $perPage = (int) $request->input('per_page', 20);

        // 1. Resellers for filter dropdown
        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // 2. Query Invoices
        $query = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with(['reseller']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhere('prefix', 'like', "%{$search}%")
                         ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        if ($selectedResellerId) {
            $query->where('reseller_id', $selectedResellerId);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($billingMonth) {
            $monthDate = Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth();
            $query->whereDate('billing_month', $monthDate->toDateString());
        }

        if ($invoiceType) {
            $query->where('type', $invoiceType);
        }

        $invoices = $query->latest('id')->paginate($perPage)->withQueryString();

        // 3. Calculate 6-Card Metric KPIs
        $baseInvoices = TenantResellerInvoice::where('tenant_id', $tenant->id);
        
        $totalInvoiced = (float) $baseInvoices->sum('amount');
        $totalPaid = (float) $baseInvoices->sum('paid_amount');
        $totalDue = (float) $baseInvoices->sum('due_amount');
        $paidCount = TenantResellerInvoice::where('tenant_id', $tenant->id)->where('payment_status', 'PAID')->count();
        $dueCount = TenantResellerInvoice::where('tenant_id', $tenant->id)->whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $thisMonthBilled = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereBetween('billing_month', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return view('tenant.resellers.invoices', compact(
            'tenant',
            'invoices',
            'allResellers',
            'totalInvoiced',
            'totalPaid',
            'totalDue',
            'paidCount',
            'dueCount',
            'thisMonthBilled',
            'search',
            'selectedResellerId',
            'paymentStatus',
            'billingMonth',
            'invoiceType',
            'perPage'
        ));
    }

    /**
     * Record payment collection for an invoice.
     */
    public function recordPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:WALLET_DEDUCT,CASH,BANK,BKASH,NAGAD,ONLINE',
            'notes' => 'nullable|string|max:255',
        ]);

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->with('reseller')
            ->findOrFail($id);

        $payAmount = (float) $validated['amount'];
        $method = $validated['payment_method'];

        if ($invoice->payment_status === 'PAID') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Invoice is already fully paid.'], 422);
            }
            return back()->with('error', 'Invoice is already fully paid.');
        }

        if ($payAmount > (float) $invoice->due_amount) {
            $payAmount = (float) $invoice->due_amount;
        }

        $reseller = $invoice->reseller;

        // Wallet deduction check
        if ($method === 'WALLET_DEDUCT' && $reseller) {
            $available = (float) $reseller->wallet_balance + (float) $reseller->credit_limit;
            if ($available < $payAmount) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient wallet balance. Available: ৳{$available}, Required: ৳{$payAmount}",
                    ], 422);
                }
                return back()->with('error', "Insufficient wallet balance for {$reseller->name}.");
            }
        }

        DB::beginTransaction();
        try {
            if ($method === 'WALLET_DEDUCT' && $reseller) {
                $reseller->decrement('wallet_balance', $payAmount);
            }

            $newPaid = (float) $invoice->paid_amount + $payAmount;
            $newDue = max(0, (float) $invoice->amount - $newPaid);
            $newStatus = ($newDue <= 0.001) ? 'PAID' : 'PARTIAL';

            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'payment_status' => $newStatus,
                'payment_method' => $method,
                'paid_at' => now(),
                'notes' => $validated['notes'] ? ($invoice->notes . " | " . $validated['notes']) : $invoice->notes,
            ]);

            DB::commit();

            $msg = "Payment of ৳" . number_format($payAmount, 2) . " received for Invoice #{$invoice->invoice_no}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'invoice' => $invoice->fresh(),
                ]);
            }

            return redirect()->route('tenant.resellers.invoices')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete an invoice.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(403, 'Unauthorized.');
        }

        $invoice = TenantResellerInvoice::where('tenant_id', $tenant->id)->findOrFail($id);
        $invNo = $invoice->invoice_no;
        $invoice->delete();

        $msg = "Invoice '{$invNo}' deleted successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.resellers.invoices')->with('success', $msg);
    }
}
