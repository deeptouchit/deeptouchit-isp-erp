<?php

namespace App\Http\Controllers\Reseller;

use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerCollectorController
{
    private const PAGE_SIZES = [15, 20, 30, 50, 100, 250, 500];
    private const OPEN_INVOICES = ['unpaid', 'partial', 'overdue', 'partially_paid'];
    private const PAID_STATES = ['paid', 'completed', 'approved', 'active'];

    private function customers(User $collector): Builder
    {
        return TenantCustomer::where('tenant_id', $collector->tenant_id)
            ->where('reseller_id', $collector->reseller_id)
            ->where('assigned_collector_id', $collector->id);
    }

    private function filteredCustomers(Request $request, array &$filters): Builder
    {
        $collector = $request->user();
        $search = $request->query('search');
        $status = $request->query('status');
        $minimum = $request->query('minimum_due');
        $connection = $request->query('connection_type');
        $filters = [
            'search' => is_string($search) ? mb_substr(trim($search), 0, 100) : '',
            'status' => in_array($status, ['due', 'active', 'expired'], true) ? $status : 'all',
            'minimum_due' => is_scalar($minimum) && is_numeric($minimum) && $minimum >= 0 ? (float) $minimum : null,
            'connection_type' => in_array($connection, ['pppoe', 'static_ip', 'hotspot'], true) ? $connection : null,
        ];

        return $this->customers($collector)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)
                    ->orWhere('customer_id', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->when($filters['status'] !== 'all', fn ($query) => $filters['status'] === 'due'
                ? $query->where('due_amount', '>', 0) : $query->where('status', $filters['status']))
            ->when($filters['minimum_due'] !== null, fn ($query) => $query->where('due_amount', '>=', $filters['minimum_due']))
            ->when($filters['connection_type'], fn ($query, $value) => $query->where('connection_type', $value))
            ->orderBy('name')->orderBy('id');
    }

    private function payments(User $collector): Builder
    {
        return TenantCustomerPayment::where('tenant_id', $collector->tenant_id)
            ->where('reseller_id', $collector->reseller_id)->where('collected_by', $collector->id);
    }

    public function dashboard(Request $request): View
    {
        $collector = $request->user();
        $assigned = $this->customers($collector);
        $paid = $this->payments($collector)->whereIn('status', self::PAID_STATES);
        $today = (clone $paid)->whereDate(DB::raw('COALESCE(paid_at, created_at)'), today());
        $due = (clone $assigned)->where('due_amount', '>', 0);
        $stats = [
            'customers' => (clone $assigned)->count(),
            'due' => (clone $due)->count(),
            'today' => (clone $today)->sum('amount'),
            'receipts' => (clone $today)->count(),
            'cash' => (clone $paid)->where('payment_method', 'cash')->whereNull('handover_id')->sum('amount'),
            'handovers' => TenantDailyCashHandover::where('tenant_id', $collector->tenant_id)
                ->where('reseller_id', $collector->reseller_id)->where('collector_id', $collector->id)
                ->where('status', 'pending')->count(),
        ];
        $customers = $due->with('package')->orderByDesc('due_amount')->orderBy('id')->limit(5)->get();

        return view('reseller.collector.dashboard', compact('stats', 'customers'));
    }

    public function index(Request $request): View
    {
        $filters = [];
        $query = $this->filteredCustomers($request, $filters);
        $perPage = $request->integer('per_page', 20);
        $pageSizes = self::PAGE_SIZES;
        $perPage = in_array($perPage, $pageSizes, true) ? $perPage : 20;
        $customers = $query->with('package')->paginate($perPage)->withQueryString();
        $pool = $this->customers($request->user());
        $stats = [
            'customers' => (clone $pool)->count(),
            'due' => (clone $pool)->where('due_amount', '>', 0)->count(),
            'active' => (clone $pool)->where('status', 'active')->count(),
            'expired' => (clone $pool)->where('status', 'expired')->count(),
            'outstanding' => (clone $pool)->sum('due_amount'),
            'matches' => $customers->total(),
        ];

        return view('reseller.collector.customers', compact('customers', 'filters', 'pageSizes', 'perPage', 'stats'));
    }

    public function show(Request $request, int $id): View|JsonResponse
    {
        $customer = $this->customers($request->user())->with('package')->findOrFail($id);
        $due = $this->outstanding($customer);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'customer' => [
                'id' => $customer->id, 'name' => $customer->name, 'customer_id' => $customer->customer_id,
                'phone' => $customer->phone, 'due_amount' => $due, 'status' => $customer->status,
            ]]);
        }

        $requestKey = (string) Str::uuid();

        return view('reseller.collector.customer', compact('customer', 'due', 'requestKey'));
    }

    public function collect(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $collector = $request->user();
        $this->customers($collector)->findOrFail($id);
        $input = $request->validate([
            'request_key' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'payment_method' => ['sometimes', 'in:cash'],
        ]);
        $amount = (float) $input['amount'];
        $key = hash('sha256', implode(':', ['collector-cash', $collector->id, $id, $input['request_key']]));

        $payment = DB::transaction(function () use ($collector, $id, $amount, $key) {
            $reseller = TenantReseller::where('tenant_id', $collector->tenant_id)->lockForUpdate()->findOrFail($collector->reseller_id);
            $customer = $this->customers($collector)->lockForUpdate()->findOrFail($id);
            $existing = $this->payments($collector)->where('request_key', $key)->first();
            if ($existing) {
                if ((float) $existing->amount !== $amount) {
                    throw ValidationException::withMessages(['amount' => __('This payment request was already used with a different amount.')]);
                }

                return $existing;
            }

            $invoices = $this->invoices($customer)->lockForUpdate()->get();
            $outstanding = max(0, (float) $customer->due_amount, (float) $invoices->sum('due_amount'));
            if ($amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => __('The amount exceeds the outstanding balance.')]);
            }

            $cost = round($reseller->calculateResellerCost($amount), 2);
            if ($cost > (float) $reseller->total_available_balance) {
                throw ValidationException::withMessages(['amount' => __('The reseller wallet has insufficient available balance.')]);
            }

            $receipt = 'REC-C-' . now()->format('Ym') . '-' . strtoupper(Str::random(12));
            $payment = $this->payments($collector)->create([
                'tenant_id' => $collector->tenant_id, 'reseller_id' => $reseller->id,
                'customer_id' => $customer->id, 'invoice_id' => $invoices->count() === 1 ? $invoices->first()->id : null,
                'invoice_no' => $receipt, 'billing_month' => now()->format('Y-m'),
                'request_key' => $key, 'amount' => $amount, 'discount' => 0,
                'payment_method' => 'cash', 'collected_by' => $collector->id, 'status' => 'paid', 'paid_at' => now(),
            ]);

            $remaining = $amount;
            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }
                $applied = min($remaining, (float) $invoice->due_amount);
                $left = round((float) $invoice->due_amount - $applied, 2);
                $invoice->update([
                    'paid_amount' => round((float) $invoice->paid_amount + $applied, 2),
                    'due_amount' => $left, 'status' => $left > 0 ? 'partial' : 'paid',
                    'paid_at' => now(), 'payment_method' => 'cash',
                ]);
                $remaining = round($remaining - $applied, 2);
            }

            $customer->update(['due_amount' => round($outstanding - $amount, 2)]);
            if ($cost > 0) {
                $before = (float) $reseller->wallet_balance;
                $reseller->decrement('wallet_balance', $cost);
                TenantResellerWalletTransaction::create([
                    'tenant_id' => $collector->tenant_id, 'reseller_id' => $reseller->id,
                    'trx_id' => 'TRX-' . strtoupper(Str::random(16)), 'type' => 'DEBIT', 'amount' => $cost,
                    'balance_before' => $before, 'balance_after' => $reseller->wallet_balance,
                    'payment_method' => 'CASH', 'reference_no' => $receipt, 'reference_id' => $payment->id,
                    'reference_type' => TenantCustomerPayment::class, 'created_by' => $collector->id,
                ]);
            }

            TenantActivityLog::create([
                'tenant_id' => $collector->tenant_id, 'actor_type' => 'reseller', 'actor_id' => $collector->id,
                'actor_name' => $collector->name, 'event_type' => 'collector_cash_payment',
                'description' => "Cash payment recorded. Receipt {$receipt}.",
            ]);

            return $payment;
        }, 3);

        $receiptUrl = route('reseller.collections.receipt', $payment->id);
        return $request->expectsJson()
            ? response()->json(['success' => true, 'invoice_no' => $payment->invoice_no, 'receipt_url' => $receiptUrl])
            : redirect($receiptUrl)->with('success', __('Cash payment recorded.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [];
        $customers = $this->filteredCustomers($request, $filters)->cursor();
        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Customer ID', 'Customer', 'Phone', 'Due Amount', 'Status'], escape: '');
            foreach ($customers as $index => $customer) {
                $row = [$index + 1, $customer->customer_id, $customer->name, $customer->phone, (float) $customer->due_amount, $customer->status];
                $row = array_map(fn ($value) => is_string($value) && preg_match('/^(?:\s*[=+@-]|[\t\r\n])/', $value) ? "'" . $value : $value, $row);
                fputcsv($handle, $row, escape: '');
            }
            fclose($handle);
        }, 'assigned-customers-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request): View
    {
        $filters = [];
        $customers = $this->filteredCustomers($request, $filters)->get();
        return view('reseller.collector.print', compact('customers'));
    }

    private function invoices(TenantCustomer $customer): Builder
    {
        return TenantCustomerInvoice::where('tenant_id', $customer->tenant_id)->where('customer_id', $customer->id)
            ->whereIn('status', self::OPEN_INVOICES)->where('due_amount', '>', 0)->orderBy('due_date')->orderBy('id');
    }

    private function outstanding(TenantCustomer $customer): float
    {
        return max(0, (float) $customer->due_amount, (float) $this->invoices($customer)->sum('due_amount'));
    }
}
