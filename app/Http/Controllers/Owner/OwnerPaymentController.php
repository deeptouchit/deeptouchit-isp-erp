<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerPaymentController extends Controller
{
    /**
     * Display a listing of completed payments and settlement records.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Payment Financial Stats
        $totalPaidAmount = (float) SaasInvoice::where('status', 'paid')->sum('amount');
        $thisMonthPaidAmount = (float) SaasInvoice::where('status', 'paid')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                      $sub->whereNull('paid_at')->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                  });
            })->sum('amount');
        $todayPaidAmount = (float) SaasInvoice::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('amount');
        $totalPaidCount = SaasInvoice::where('status', 'paid')->count();

        // 2. Query Builder
        $query = SaasInvoice::with(['tenant', 'plan'])->where('status', 'paid');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('trx_id', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_preset')) {
            switch ($request->date_preset) {
                case 'today':
                    $query->whereDate('paid_at', Carbon::today());
                    break;
                case 'this_month':
                    $query->whereBetween('paid_at', [$startOfMonth, $endOfMonth]);
                    break;
                case 'last_month':
                    $query->whereBetween('paid_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
                    break;
            }
        } elseif ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('paid_at', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay(),
            ]);
        }

        $payments = $query->latest('paid_at')->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        return view('owner.payments.index', compact(
            'payments',
            'tenants',
            'totalPaidAmount',
            'thisMonthPaidAmount',
            'todayPaidAmount',
            'totalPaidCount'
        ));
    }
}
