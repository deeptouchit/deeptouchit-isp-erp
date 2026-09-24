<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantExpenseCategory;
use App\Models\TenantExpenseTransaction;
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

class TenantExpenseAccountController extends Controller
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
     * Display listing of Income & Expense Accounts
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        // Ensure default ISP categories exist
        if (TenantExpenseCategory::where('tenant_id', $tenant->id)->count() === 0) {
            TenantExpenseCategory::seedDefaultCategories($tenant->id);
        }

        $allCategories = TenantExpenseCategory::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Base Query
        $query = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->with(['category', 'creator', 'approver']);

        // 1. Transaction Type Filter (Expense / Income / All)
        $selectedType = $request->input('type', 'all');
        if (!empty($selectedType) && $selectedType !== 'all') {
            $query->where('type', strtoupper($selectedType));
        }

        // 2. Category Filter
        $selectedCategory = $request->input('category_id', 'all');
        if (!empty($selectedCategory) && $selectedCategory !== 'all') {
            $query->where('category_id', (int) $selectedCategory);
        }

        // 3. Payment Method Filter
        $selectedMethod = $request->input('method', 'all');
        if (!empty($selectedMethod) && $selectedMethod !== 'all') {
            $query->where('payment_method', strtoupper($selectedMethod));
        }

        // 4. Status Filter
        $selectedStatus = $request->input('status', 'all');
        if (!empty($selectedStatus) && $selectedStatus !== 'all') {
            $query->where('status', strtoupper($selectedStatus));
        }

        // 5. Period & Date Range Filter (Day-to-day resolution)
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
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        // 6. Month Filter
        $selectedMonth = $request->input('month', 'all');
        if (!empty($selectedMonth) && $selectedMonth !== 'all') {
            $query->whereDate('transaction_date', 'like', "{$selectedMonth}%");
        }

        // 7. Search Bar Filter
        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('payee_payer', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        // 8. Calculate 6 KPI Summary Cards
        $kpiQuery = clone $query;
        $matchedTransactions = $kpiQuery->get();

        $totalExpenses = (float) $matchedTransactions->where('type', 'EXPENSE')->where('status', '!=', 'REJECTED')->sum('amount');
        $totalIncome = (float) $matchedTransactions->where('type', 'INCOME')->where('status', '!=', 'REJECTED')->sum('amount');
        $netCashFlow = $totalIncome - $totalExpenses;
        $approvedCount = $matchedTransactions->where('status', 'APPROVED')->count();
        $pendingCount = $matchedTransactions->where('status', 'PENDING')->count();
        $categoriesCount = $allCategories->count();

        // 9. Paginated Results
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        $vouchers = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        return view('tenant.finance.expenses', compact(
            'tenant',
            'vouchers',
            'allCategories',
            'selectedType',
            'selectedCategory',
            'selectedMethod',
            'selectedStatus',
            'period',
            'dateFrom',
            'dateTo',
            'selectedMonth',
            'search',
            'perPage',
            'totalExpenses',
            'totalIncome',
            'netCashFlow',
            'approvedCount',
            'pendingCount',
            'categoriesCount'
        ));
    }

    /**
     * Store Expense or Income Voucher
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();

        $validated = $request->validate([
            'type' => 'required|in:EXPENSE,INCOME',
            'category_id' => 'required|exists:tenant_expense_categories,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:CASH,BANK,BKASH,NAGAD,CHEQUE,PETTY_CASH',
            'account_name' => 'nullable|string|max:100',
            'payee_payer' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $voucherNo = TenantExpenseTransaction::generateVoucherNo($tenant->id, $validated['type']);

            $voucher = TenantExpenseTransaction::create([
                'tenant_id' => $tenant->id,
                'category_id' => $validated['category_id'],
                'voucher_no' => $voucherNo,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'account_name' => $validated['account_name'],
                'payee_payer' => $validated['payee_payer'],
                'reference_no' => $validated['reference_no'],
                'transaction_date' => $validated['transaction_date'],
                'status' => 'APPROVED',
                'created_by' => $user?->id,
                'approved_by' => $user?->id,
                'notes' => $validated['notes'],
            ]);

            DB::commit();

            $typeName = $validated['type'] === 'INCOME' ? 'Income Voucher' : 'Expense Voucher';
            $msg = "{$typeName} {$voucherNo} (৳" . number_format($validated['amount'], 2) . ") posted successfully.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'voucher' => $voucher,
                ]);
            }

            return redirect()->route('tenant.finance.expenses')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save voucher: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save voucher: ' . $e->getMessage());
        }
    }

    /**
     * Store new Chart of Accounts Category
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:EXPENSE,INCOME',
            'code' => 'nullable|string|max:30',
            'monthly_budget' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $category = TenantExpenseCategory::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'code' => $validated['code'] ?: ($validated['type'] === 'INCOME' ? 'INC-' : 'EXP-') . rand(10, 99),
            'monthly_budget' => $validated['monthly_budget'] ?? 0.00,
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Account category '{$category->name}' created successfully.",
            'category' => $category,
        ]);
    }

    /**
     * Show voucher details for print modal & audit
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->with(['category', 'creator', 'approver'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'voucher' => [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'type' => $voucher->type,
                'type_badge' => $voucher->type_badge,
                'category_name' => $voucher->category?->name ?? 'General',
                'category_code' => $voucher->category?->code ?? '--',
                'title' => $voucher->title,
                'amount' => (float) $voucher->amount,
                'payment_method' => $voucher->payment_method,
                'account_name' => $voucher->account_name ?: '--',
                'payee_payer' => $voucher->payee_payer ?: '--',
                'reference_no' => $voucher->reference_no ?: '--',
                'transaction_date' => $voucher->transaction_date ? Carbon::parse($voucher->transaction_date)->format('d M, Y') : '--',
                'status' => $voucher->status,
                'status_badge' => $voucher->status_badge,
                'creator_name' => $voucher->creator?->name ?: 'Administrator',
                'approver_name' => $voucher->approver?->name ?: ($voucher->status === 'APPROVED' ? 'Administrator' : '--'),
                'notes' => $voucher->notes ?: '--',
                'created_at' => $voucher->created_at->format('d M, Y h:i A'),
            ],
            'tenant' => [
                'name' => $tenant->company_name ?? $tenant->name,
                'phone' => $tenant->phone ?? '--',
                'email' => $tenant->email ?? '--',
                'address' => $tenant->address ?? '--',
            ],
        ]);
    }

    /**
     * Approve Pending Voucher
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $voucher->update([
            'status' => 'APPROVED',
            'approved_by' => Auth::id(),
        ]);

        $msg = "Voucher {$voucher->voucher_no} has been approved.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Reject Voucher
     */
    public function reject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $voucher->update([
            'status' => 'REJECTED',
            'approved_by' => Auth::id(),
        ]);

        $msg = "Voucher {$voucher->voucher_no} has been rejected.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Delete Voucher
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $voucher = TenantExpenseTransaction::where('tenant_id', $tenant->id)->findOrFail($id);

        $vNo = $voucher->voucher_no;
        $voucher->delete();

        $msg = "Voucher {$vNo} deleted successfully.";

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('tenant.finance.expenses')->with('success', $msg);
    }

    /**
     * Export Expenses & Incomes to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();

        $query = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->with(['category', 'creator', 'approver']);

        $type = $request->input('type');
        if (!empty($type) && $type !== 'all') {
            $query->where('type', strtoupper($type));
        }

        $categoryId = $request->input('category_id');
        if (!empty($categoryId) && $categoryId !== 'all') {
            $query->where('category_id', (int) $categoryId);
        }

        $method = $request->input('method');
        if (!empty($method) && $method !== 'all') {
            $query->where('payment_method', strtoupper($method));
        }

        $status = $request->input('status');
        if (!empty($status) && $status !== 'all') {
            $query->where('status', strtoupper($status));
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
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        $month = $request->input('month');
        if (!empty($month) && $month !== 'all') {
            $query->whereDate('transaction_date', 'like', "{$month}%");
        }

        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('payee_payer', 'like', "%{$search}%");
            });
        }

        $vouchers = $query->orderBy('transaction_date', 'desc')->get();
        $fileName = 'income_expense_vouchers_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($vouchers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Voucher No',
                'Type',
                'Date',
                'Category',
                'Account / Head',
                'Description / Title',
                'Payee / Payer',
                'Amount (BDT)',
                'Payment Method',
                'Account Name',
                'Reference No',
                'Status',
                'Created By',
                'Approved By',
                'Notes',
            ]);

            foreach ($vouchers as $v) {
                fputcsv($handle, [
                    $v->voucher_no,
                    $v->type,
                    $v->transaction_date ? Carbon::parse($v->transaction_date)->format('Y-m-d') : '',
                    $v->category?->name ?? 'General',
                    $v->category?->code ?? '',
                    $v->title,
                    $v->payee_payer ?? '',
                    $v->amount,
                    $v->payment_method,
                    $v->account_name ?? '',
                    $v->reference_no ?? '',
                    $v->status,
                    $v->creator?->name ?? 'System',
                    $v->approver?->name ?? '',
                    $v->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
