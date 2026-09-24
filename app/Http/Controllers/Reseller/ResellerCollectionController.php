<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerCollectionController extends Controller
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
     * Display Collection Reports & Payment Ledger.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $collectorId = $request->input('collector_id', 'all');
        $method = $request->input('method', 'all');
        $status = $request->input('status', 'all');
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $month = $request->input('month', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Resolve Period Presets & Date Range
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
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $period = 'custom';
        } elseif ($month !== 'all' && !empty($month)) {
            $period = 'custom';
            $dateFrom = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
            $dateTo = Carbon::parse($month . '-01')->endOfMonth()->toDateString();
        } else {
            // Default initial load: This Month
            if (!$request->has('period') && !$request->has('date_from') && !$request->has('search') && !$request->has('collector_id') && !$request->has('status') && !$request->has('method')) {
                $period = 'this_month';
                $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                $dateTo = Carbon::now()->endOfMonth()->toDateString();
            } else {
                $period = $period ?: 'all';
            }
        }

        // Filter Scope Closure
        $applyFilterScope = function ($q) use ($search, $collectorId, $method, $status, $dateFrom, $dateTo) {
            if (!empty($search)) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                               ->orWhere('customer_id', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            if ($collectorId !== 'all' && !empty($collectorId)) {
                $q->where('collected_by', (int) $collectorId);
            }

            if ($method !== 'all' && !empty($method)) {
                $q->where('payment_method', $method);
            }

            if ($status !== 'all' && !empty($status)) {
                $q->where('status', $status);
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) BETWEEN ? AND ?", [$dateFrom, $dateTo]);
            } elseif (!empty($dateFrom)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) >= ?", [$dateFrom]);
            } elseif (!empty($dateTo)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) <= ?", [$dateTo]);
            }
        };

        // 1. Base Payments Query
        $query = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'collector'])
            ->latest('id');

        $applyFilterScope($query);

        $payments = $query->paginate($perPage)->withQueryString();

        // 2. Fetch Collectors for Dropdown
        $collectors = User::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->orderBy('name')
            ->get();

        // 3. Compute 6 KPI Metric Cards (AGENTS.md Rule 2.B)
        $kpiBaseQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $applyFilterScope($kpiBaseQuery);

        $totalCollected = (float) (clone $kpiBaseQuery)->whereIn('status', ['paid', 'completed', 'approved', 'active'])->sum('amount');
        $totalTransactions = (int) (clone $kpiBaseQuery)->count();
        $cashCollected = (float) (clone $kpiBaseQuery)->where('payment_method', 'cash')->whereIn('status', ['paid', 'completed', 'approved', 'active'])->sum('amount');
        $digitalCollected = (float) (clone $kpiBaseQuery)->whereIn('payment_method', ['bkash', 'nagad', 'rocket', 'bank_transfer', 'online', 'pos', 'bangla_qr', 'banglaqr'])->whereIn('status', ['paid', 'completed', 'approved', 'active'])->sum('amount');
        $pendingCount = (int) (clone $kpiBaseQuery)->where('status', 'pending')->count();
        $totalDiscount = (float) (clone $kpiBaseQuery)->sum('discount');

        $stats = [
            'total_collected' => $totalCollected,
            'cash_collected' => $cashCollected,
            'digital_collected' => $digitalCollected,
            'transactions_count' => $totalTransactions,
            'pending_count' => $pendingCount,
            'total_discount' => $totalDiscount,
        ];

        // 4. Collector Performance Breakdown Matrix
        $collectorMatrix = [];
        foreach ($collectors as $col) {
            $colPayments = (clone $kpiBaseQuery)->where('collected_by', $col->id);
            $colSum = (float) $colPayments->sum('amount');
            $colCount = (int) $colPayments->count();
            $commRate = (float) ($col->collection_commission_rate ?? 0);
            $commissionAmount = $commRate > 0 ? round(($colSum * $commRate) / 100, 2) : 0.0;

            $collectorMatrix[] = [
                'collector' => $col,
                'total_collected' => $colSum,
                'transactions_count' => $colCount,
                'commission_rate' => $commRate,
                'commission_amount' => $commissionAmount,
            ];
        }

        // Available billing months for filter dropdown
        $availableMonths = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->selectRaw("DISTINCT DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') as month_code")
            ->orderByDesc('month_code')
            ->pluck('month_code')
            ->toArray();

        if (empty($availableMonths)) {
            $availableMonths = [now()->format('Y-m')];
        }

        $currencySymbol = '৳';

        return view('reseller.collections.index', compact(
            'authUser',
            'reseller',
            'tenant',
            'payments',
            'collectors',
            'stats',
            'collectorMatrix',
            'availableMonths',
            'search',
            'collectorId',
            'method',
            'status',
            'period',
            'dateFrom',
            'dateTo',
            'month',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Get Single Payment Details / Money Receipt data for modal or print view.
     */
    public function receipt(Request $request, int $id): JsonResponse|View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();

        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->with(['customer.package', 'collector'])
            ->findOrFail($id);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'receipt_no' => $payment->invoice_no,
                'customer_name' => $payment->customer?->name,
                'customer_id' => $payment->customer?->customer_id,
                'username' => $payment->customer?->username,
                'mobile' => $payment->customer?->mobile ?? $payment->customer?->phone,
                'address' => $payment->customer?->address,
                'package_name' => $payment->customer?->package_display_name,
                'amount' => (float) $payment->amount,
                'discount' => (float) $payment->discount,
                'billing_month' => $payment->formatted_month ?? $payment->billing_month,
                'payment_method' => $payment->payment_method_name ?? $payment->payment_method,
                'collector_name' => $payment->collector?->name ?? 'Office Counter',
                'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A'),
                'notes' => $payment->notes,
            ]);
        }

        $currencySymbol = '৳';

        return view('reseller.collections.receipt', compact(
            'authUser',
            'reseller',
            'tenant',
            'payment',
            'currencySymbol'
        ));
    }

    /**
     * Helper to resolve filters for query scoping.
     */
    protected function resolveFilterScope(Request $request): array
    {
        $search = trim($request->input('search', ''));
        $collectorId = $request->input('collector_id', 'all');
        $method = $request->input('method', 'all');
        $status = $request->input('status', 'all');
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $month = $request->input('month', 'all');

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
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $period = 'custom';
        } elseif ($month !== 'all' && !empty($month)) {
            $period = 'custom';
            $dateFrom = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
            $dateTo = Carbon::parse($month . '-01')->endOfMonth()->toDateString();
        }

        return [$search, $collectorId, $method, $status, $period, $dateFrom, $dateTo, $month];
    }

    /**
     * Export Collection Statement as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$search, $collectorId, $method, $status, $period, $dateFrom, $dateTo, $month] = $this->resolveFilterScope($request);

        $fileName = 'reseller_collections_' . ($dateFrom ? $dateFrom . '_to_' . ($dateTo ?: $dateFrom) : date('Ymd_His')) . '.csv';

        $query = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'collector'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                           ->orWhere('customer_id', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%")
                           ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($collectorId !== 'all' && !empty($collectorId)) {
            $query->where('collected_by', (int) $collectorId);
        }

        if ($method !== 'all' && !empty($method)) {
            $query->where('payment_method', $method);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) BETWEEN ? AND ?", [$dateFrom, $dateTo]);
        } elseif (!empty($dateFrom)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) >= ?", [$dateFrom]);
        } elseif (!empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) <= ?", [$dateTo]);
        }

        $payments = $query->get();

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Receipt No', 'Customer Name', 'Customer ID', 'Package', 'Collector', 'Method', 'Status', 'Amount', 'Discount', 'Month', 'Date']);

            foreach ($payments as $index => $p) {
                fputcsv($handle, [
                    $index + 1,
                    $p->invoice_no,
                    $p->customer?->name ?? 'N/A',
                    $p->customer?->customer_id ?? 'N/A',
                    $p->customer?->package_display_name ?? 'N/A',
                    $p->collector?->name ?? 'Counter',
                    $p->payment_method_name,
                    ucfirst($p->status ?? 'paid'),
                    $p->amount,
                    $p->discount,
                    $p->formatted_month,
                    $p->paid_at ? $p->paid_at->format('Y-m-d H:i') : $p->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Printable Collection Statement & Audit Report.
     */
    public function printReport(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        [$search, $collectorId, $method, $status, $period, $dateFrom, $dateTo, $month] = $this->resolveFilterScope($request);

        $query = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer.package', 'collector'])
            ->latest('id');

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                           ->orWhere('customer_id', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%")
                           ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($collectorId !== 'all' && !empty($collectorId)) {
            $query->where('collected_by', (int) $collectorId);
        }

        if ($method !== 'all' && !empty($method)) {
            $query->where('payment_method', $method);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) BETWEEN ? AND ?", [$dateFrom, $dateTo]);
        } elseif (!empty($dateFrom)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) >= ?", [$dateFrom]);
        } elseif (!empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) <= ?", [$dateTo]);
        }

        $payments = $query->get();
        $totalAmount = (float) $payments->whereIn('status', ['paid', 'completed', 'approved', 'active'])->sum('amount');
        $totalDiscount = (float) $payments->sum('discount');

        $periodLabel = 'All Records';
        if (!empty($dateFrom) && !empty($dateTo)) {
            $periodLabel = ($dateFrom === $dateTo) 
                ? Carbon::parse($dateFrom)->format('d M Y') 
                : Carbon::parse($dateFrom)->format('d M Y') . ' — ' . Carbon::parse($dateTo)->format('d M Y');
        } elseif (!empty($dateFrom)) {
            $periodLabel = 'From ' . Carbon::parse($dateFrom)->format('d M Y');
        } elseif (!empty($dateTo)) {
            $periodLabel = 'Up to ' . Carbon::parse($dateTo)->format('d M Y');
        }

        $currencySymbol = '৳';

        return view('reseller.collections.print', compact(
            'authUser',
            'reseller',
            'tenant',
            'payments',
            'totalAmount',
            'totalDiscount',
            'periodLabel',
            'dateFrom',
            'dateTo',
            'currencySymbol'
        ));
    }

    /**
     * Reseller Approve Pending Payment (e.g. Bangla QR / Offline Payment)
     */
    public function approvePayment(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $payment = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer'])
            ->findOrFail($id);

        if (in_array(strtolower($payment->status), ['paid', 'completed', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => "Payment #{$payment->invoice_no} has already been approved and settled."
            ], 422);
        }

        $customer = $payment->customer;
        $amount = (float) $payment->amount;
        $discount = (float) ($payment->discount ?? 0);
        $totalCredited = $amount + $discount;

        DB::transaction(function () use ($tenantId, $reseller, $payment, $customer, $amount, $discount, $totalCredited, $authUser) {
            // 1. Mark Payment as Paid
            $payment->status = 'paid';
            $payment->paid_at = Carbon::now();
            $payment->save();

            // 2. Adjust Customer Due & Validity
            if ($customer) {
                $customer->due_amount = max(0, (float) $customer->due_amount - $totalCredited);

                // Extend validity
                $currentExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
                    ? Carbon::parse($customer->expiry_date)
                    : Carbon::now();
                $customer->expiry_date = $currentExpiry->addMonth();

                // Restore active status
                if (in_array($customer->status, ['due', 'expired', 'disabled', 'suspended', 'inactive'])) {
                    $customer->status = 'active';
                }
                $customer->mikrotik_status = 'enabled';
                $customer->save();
            }

            // 3. Credit Reseller Wallet Commission (if applicable for Bangla QR)
            if ($reseller && in_array(strtolower($payment->payment_method), ['bangla_qr', 'banglaqr'])) {
                $commissionRate = (float) ($reseller->commission_rate ?? 0);
                $resellerCommission = round(($amount * $commissionRate) / 100, 2);

                if ($resellerCommission > 0) {
                    $balanceBefore = (float) $reseller->wallet_balance;
                    $reseller->increment('wallet_balance', $resellerCommission);
                    $reseller->refresh();

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenantId,
                        'reseller_id' => $reseller->id,
                        'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                        'type' => 'CREDIT',
                        'amount' => $resellerCommission,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $reseller->wallet_balance,
                        'payment_method' => 'BANGLA_QR',
                        'description' => "Commission credited for approved Bangla QR payment '{$payment->invoice_no}' for '{$customer?->username}' ({$customer?->name}). Bill: ৳" . number_format($amount, 2) . ", Commission ({$commissionRate}%): ৳" . number_format($resellerCommission, 2) . ".",
                        'created_by' => $authUser->id,
                    ]);
                }
            }

            // 4. Settle open Invoice
            try {
                if ($customer) {
                    $invoice = TenantCustomerInvoice::where('tenant_id', $tenantId)
                        ->where('customer_id', $customer->id)
                        ->whereIn('status', ['unpaid', 'partial', 'overdue', 'partially_paid'])
                        ->orderBy('due_date', 'asc')
                        ->first();

                    if ($invoice) {
                        if ($amount >= $invoice->total_payable) {
                            $invoice->paid_amount = $invoice->total_payable;
                            $invoice->due_amount = 0.00;
                            $invoice->status = 'paid';
                        } else {
                            $invoice->paid_amount = (float)$invoice->paid_amount + $amount;
                            $invoice->due_amount = max(0, (float)$invoice->total_payable - (float)$invoice->paid_amount);
                            $invoice->status = 'partially_paid';
                        }
                        $invoice->paid_at = Carbon::now();
                        $invoice->payment_method = $payment->payment_method;
                        $invoice->save();
                    }
                }
            } catch (\Exception $e) {}

            // 5. Activity Log
            TenantActivityLog::create([
                'tenant_id' => $tenantId,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'PAYMENT_APPROVED_BY_RESELLER',
                'description' => "Reseller {$reseller?->name} approved payment '{$payment->invoice_no}' of ৳" . number_format($amount, 2) . " for customer '{$customer?->name}'. Line activated.",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'customer_id' => $customer?->id,
                    'amount' => $amount,
                ],
            ]);
        });

        // 6. Sync to MikroTik & FreeRADIUS
        $syncNotes = '';
        if ($customer) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->syncCustomerToMikrotik($customer);
                $syncNotes .= ' & MikroTik Synced';
            } catch (\Exception $e) {}

            try {
                $radiusService = new RadiusService();
                $radiusService->syncCustomerSubscriber($customer);
                $syncNotes .= ' & FreeRADIUS Active';
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => "Payment #{$payment->invoice_no} (৳" . number_format($amount, 2) . ") approved successfully! Subscriber line is now active{$syncNotes}.",
            'status' => 'paid',
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Reseller Reject Pending Payment
     */
    public function rejectPayment(Request $request, int $id): JsonResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $payment = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->findOrFail($id);

        $reason = $request->input('reason', 'Payment verification failed or TrxID invalid.');

        $payment->status = 'void';
        $payment->notes = trim(($payment->notes ? $payment->notes . ' | ' : '') . "Rejected: " . $reason);
        $payment->save();

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'PAYMENT_REJECTED_BY_RESELLER',
            'description' => "Reseller {$reseller?->name} rejected payment '{$payment->invoice_no}' of ৳" . number_format($payment->amount, 2) . ". Reason: {$reason}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Payment '{$payment->invoice_no}' has been rejected."
        ]);
    }
}
