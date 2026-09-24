@extends('tenant.layouts.app')

@section('title', 'Income & Expense Accounts - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden !important;
        }
        #printableVoucherArea, #printableVoucherArea * {
            visibility: visible !important;
        }
        #printableVoucherArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            padding: 20px !important;
            background: #ffffff !important;
            color: #000000 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="expenseAccountsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strict Rule: Title + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-1 border-b border-slate-200/80">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-sm shadow-2xs">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
            <h1 class="text-base font-bold text-slate-800 tracking-tight">Income &amp; Expense Accounts</h1>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" @click="openVoucherModal('EXPENSE')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                <i class="fas fa-minus-circle text-[11px]"></i>
                <span>Record Expense</span>
            </button>

            <button type="button" @click="openVoucherModal('INCOME')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                <i class="fas fa-plus-circle text-[11px]"></i>
                <span>Record Income</span>
            </button>

            <button type="button" @click="showCategoryModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer">
                <i class="fas fa-folder-tree text-indigo-600 text-[11px]"></i>
                <span>Chart of Accounts</span>
            </button>

            <a href="{{ route('tenant.finance.expenses.export', request()->all()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Expenses --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Expenses</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">@currency($totalExpenses)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-arrow-trend-down"></i>
            </div>
        </div>

        {{-- Card 2: Direct Incomes --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Direct Incomes</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($totalIncome)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-arrow-trend-up"></i>
            </div>
        </div>

        {{-- Card 3: Net Cash Flow --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Net Cash Flow</span>
                <span class="text-[13px] font-bold font-mono {{ $netCashFlow >= 0 ? 'text-cyan-800' : 'text-rose-700' }} leading-tight block truncate">
                    @currency($netCashFlow)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 border border-cyan-100 text-cyan-700 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        {{-- Card 4: Approved Vouchers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Approved Vouchers</span>
                <span class="text-[13px] font-bold font-mono text-teal-700 leading-tight block truncate">{{ number_format($approvedCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 5: Pending Approvals --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Pending Approval</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">{{ number_format($pendingCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 6: Chart Categories --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Account Heads</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">{{ number_format($categoriesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-folder-tree"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.finance.expenses') }}" class="space-y-2">
            <!-- Row 1: Search Box, Period Preset & Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2">
                <!-- Search Box -->
                <div class="md:col-span-4 relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Voucher #, Title, Payee, Ref..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Quick Period Preset -->
                <div class="md:col-span-2">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value)"
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition font-medium">
                        <option value="all" {{ ($period ?? '') === 'all' || empty($period) ? 'selected' : '' }}>All Dates</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-day"></i>
                        </span>
                        <input type="date" 
                               name="date_from" 
                               id="dateFromInput"
                               value="{{ $dateFrom }}" 
                               title="From Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>

                <!-- Date To -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-check"></i>
                        </span>
                        <input type="date" 
                               name="date_to" 
                               id="dateToInput"
                               value="{{ $dateTo }}" 
                               title="To Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>
            </div>

            <!-- Row 2: Type, Category, Method, Status, Month, Per Page & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 pt-1 border-t border-slate-100">
                <!-- Type Filter -->
                <div class="md:col-span-2">
                    <select name="type" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedType === 'all' ? 'selected' : '' }}>All Voucher Types</option>
                        <option value="EXPENSE" {{ $selectedType === 'EXPENSE' ? 'selected' : '' }}>Expense (Debit)</option>
                        <option value="INCOME" {{ $selectedType === 'INCOME' ? 'selected' : '' }}>Direct Income (Credit)</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="md:col-span-2">
                    <select name="category_id" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all">All Account Heads</option>
                        <optgroup label="Expense Heads">
                            @foreach($allCategories->where('type', 'EXPENSE') as $c)
                                <option value="{{ $c->id }}" {{ $selectedCategory == (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Income Heads">
                            @foreach($allCategories->where('type', 'INCOME') as $c)
                                <option value="{{ $c->id }}" {{ $selectedCategory == (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <!-- Method Filter -->
                <div class="md:col-span-2">
                    <select name="method" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedMethod === 'all' ? 'selected' : '' }}>All Methods</option>
                        <option value="CASH" {{ $selectedMethod === 'CASH' ? 'selected' : '' }}>Cash</option>
                        <option value="BANK" {{ $selectedMethod === 'BANK' ? 'selected' : '' }}>Bank Wire</option>
                        <option value="BKASH" {{ $selectedMethod === 'BKASH' ? 'selected' : '' }}>bKash</option>
                        <option value="NAGAD" {{ $selectedMethod === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                        <option value="CHEQUE" {{ $selectedMethod === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                        <option value="PETTY_CASH" {{ $selectedMethod === 'PETTY_CASH' ? 'selected' : '' }}>Petty Cash</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="md:col-span-1">
                    <select name="status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="APPROVED" {{ $selectedStatus === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                        <option value="PENDING" {{ $selectedStatus === 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="REJECTED" {{ $selectedStatus === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Month Filter -->
                <div class="md:col-span-2">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth !== 'all' ? $selectedMonth : '' }}" 
                           title="Billing Month"
                           class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                </div>

                <!-- Per Page -->
                <div class="md:col-span-1">
                    <select name="per_page" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <!-- Strict Action Buttons: [Filter] FIRST, [Reset] SECOND (AGENTS.md Rule 2.C) -->
                <div class="md:col-span-2 flex items-center justify-end gap-1.5">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.expenses') }}" 
                       class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1 cursor-pointer flex-shrink-0" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Master Compact Table (<table class="saas-table"> - Clean Single-Line Core Columns) --}}
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto min-h-[300px]">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-36">Voucher No</th>
                        <th class="w-48">Account Head</th>
                        <th>Description / Particulars</th>
                        <th class="text-right w-28">Amount</th>
                        <th class="text-center w-28">Status</th>
                        <th class="w-28">Date</th>
                        <th class="no-sort w-12 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $index => $v)
                        <tr>
                            {{-- Row index --}}
                            <td class="text-center font-mono text-slate-500">{{ $vouchers->firstItem() + $index }}</td>

                            {{-- Voucher No (Clickable to open View/Print Voucher Modal) --}}
                            <td>
                                <button type="button" @click="viewVoucher({{ $v->id }})" class="font-mono font-bold {{ $v->type === 'INCOME' ? 'text-emerald-700 hover:text-emerald-600' : 'text-rose-700 hover:text-rose-600' }} hover:underline cursor-pointer">
                                    {{ $v->voucher_no }}
                                </button>
                            </td>

                            {{-- Account Head / Category --}}
                            <td>
                                <span class="font-medium text-slate-800">{{ $v->category?->name ?? 'General Head' }}</span>
                            </td>

                            {{-- Description / Title --}}
                            <td class="truncate max-w-xs" title="{{ $v->title }}">
                                <span class="text-slate-900 font-semibold">{{ $v->title }}</span>
                            </td>

                            {{-- Amount (Color-coded by type) --}}
                            <td class="text-right font-mono font-bold {{ $v->type === 'INCOME' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $v->type === 'INCOME' ? '+' : '-' }}@currency($v->amount)
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $v->status_badge['class'] }}">
                                    <i class="fas {{ $v->status_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $v->status_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="font-mono text-slate-600 text-xs">
                                {{ $v->transaction_date ? \Carbon\Carbon::parse($v->transaction_date)->format('d M Y') : '--' }}
                            </td>

                            {{-- Action 3-Dot Button --}}
                            <td class="text-center">
                                <button type="button" @click.stop="toggleMenu({{ $v->toJson() }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-700 inline-flex items-center justify-center transition cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                <i class="fas fa-money-bill-transfer text-3xl mb-2 text-slate-300 block"></i>
                                <span class="text-xs font-medium">No income or expense vouchers found for the selected criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Pagination --}}
        @if($vouchers->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/60 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    Showing {{ $vouchers->firstItem() ?? 0 }} to {{ $vouchers->lastItem() ?? 0 }} of {{ $vouchers->total() }} vouchers
                </div>
                <div>
                    {{ $vouchers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Implementation) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 divide-y divide-slate-100 text-xs transition duration-100">
        
        {{-- Group 1: Diagnostics & Print --}}
        <div class="py-1">
            <button type="button" @click="viewVoucher(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-print w-3.5 text-cyan-600 text-[11px]"></i>
                <span>View &amp; Print Voucher</span>
            </button>
            <button type="button" @click="copyVoucherNo(activeMenu.voucher_no); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-copy w-3.5 text-slate-500 text-[11px]"></i>
                <span>Copy Voucher Number</span>
            </button>
        </div>

        {{-- Group 2: Management & Approval --}}
        <div class="py-1">
            <template x-if="activeMenu && activeMenu.status === 'PENDING'">
                <div>
                    <button type="button" @click="approveVoucher(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 flex items-center gap-2 text-emerald-700 font-semibold transition cursor-pointer">
                        <i class="fas fa-check w-3.5 text-emerald-600 text-[11px]"></i>
                        <span>Approve Voucher</span>
                    </button>
                    <button type="button" @click="rejectVoucher(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                        <i class="fas fa-ban w-3.5 text-rose-500 text-[11px]"></i>
                        <span>Reject Voucher</span>
                    </button>
                </div>
            </template>

            <button type="button" @click="deleteVoucher(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                <i class="fas fa-trash-can w-3.5 text-rose-500 text-[11px]"></i>
                <span>Delete Voucher</span>
            </button>
        </div>
    </div>

    {{-- MODAL 1: Record Expense / Income Voucher Modal --}}
    <div x-show="showVoucherModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showVoucherModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs flex-shrink-0"
                         :class="voucherForm.type === 'INCOME' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'">
                        <i :class="voucherForm.type === 'INCOME' ? 'fas fa-arrow-down-left' : 'fas fa-arrow-up-right'"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="voucherForm.type === 'INCOME' ? 'Record Direct Income / Credit Voucher' : 'Record Operating Expense / Debit Voucher'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Post verified cash/bank transaction into company accounts</p>
                    </div>
                </div>
                <button type="button" @click="showVoucherModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitVoucher()">
                <div class="p-4 space-y-3.5 text-xs">
                    {{-- Type Selector Tabs --}}
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-lg">
                        <button type="button" @click="voucherForm.type = 'EXPENSE'" 
                                class="py-1 text-center rounded-md font-bold text-xs transition cursor-pointer"
                                :class="voucherForm.type === 'EXPENSE' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'">
                            <i class="fas fa-minus-circle mr-1"></i> Expense (Debit)
                        </button>
                        <button type="button" @click="voucherForm.type = 'INCOME'" 
                                class="py-1 text-center rounded-md font-bold text-xs transition cursor-pointer"
                                :class="voucherForm.type === 'INCOME' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600 hover:text-slate-800'">
                            <i class="fas fa-plus-circle mr-1"></i> Direct Income (Credit)
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Category / Account Head --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Account Head <span class="text-rose-500">*</span></label>
                            <select x-model="voucherForm.category_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="">Select Account Head</option>
                                <template x-for="cat in filteredCategories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="1" x-model="voucherForm.amount" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono font-bold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>

                    {{-- Title / Particulars --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Description / Particulars <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="voucherForm.title" required placeholder="e.g. Monthly IIG bandwidth trunk payment to Summit" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                            <select x-model="voucherForm.payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="CASH">Cash in Hand</option>
                                <option value="BANK">Bank Wire / Transfer</option>
                                <option value="BKASH">bKash</option>
                                <option value="NAGAD">Nagad</option>
                                <option value="CHEQUE">Bank Cheque</option>
                                <option value="PETTY_CASH">Petty Cash</option>
                            </select>
                        </div>

                        {{-- Payee / Payer --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1" x-text="voucherForm.type === 'INCOME' ? 'Payer / Received From' : 'Payee / Paid To'"></label>
                            <input type="text" x-model="voucherForm.payee_payer" placeholder="e.g. Summit Comms / Landlord" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        {{-- Transaction Date --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Date <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="voucherForm.transaction_date" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Account / Bank Name --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Account / Vault Name</label>
                            <input type="text" x-model="voucherForm.account_name" placeholder="e.g. Main Cash Vault or City Bank A/C" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        {{-- Reference / Bill No --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Reference / Cheque / Bill #</label>
                            <input type="text" x-model="voucherForm.reference_no" placeholder="e.g. MR-8942 or Cheque #004" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Voucher Remarks / Notes</label>
                        <textarea x-model="voucherForm.notes" rows="2" placeholder="Optional audit explanation..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showVoucherModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" 
                            class="text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5"
                            :class="voucherForm.type === 'INCOME' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span x-text="voucherForm.type === 'INCOME' ? 'Post Income Voucher' : 'Post Expense Voucher'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Chart of Accounts & Categories Manager Modal --}}
    <div x-show="showCategoryModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showCategoryModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-folder-tree"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Chart of Accounts Heads</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Manage structured ISP income &amp; operating expense categories</p>
                    </div>
                </div>
                <button type="button" @click="showCategoryModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 text-xs overflow-y-auto flex-1">
                {{-- Quick Add Category Form --}}
                <form @submit.prevent="submitNewCategory()" class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2.5">
                    <span class="text-[11px] font-bold text-slate-800 block">Create New Account Head</span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="sm:col-span-2">
                            <input type="text" x-model="newCategoryForm.name" required placeholder="Category Name (e.g. Office Generator Diesel)" class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <select x-model="newCategoryForm.type" required class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2 py-1.5 focus:border-cyan-500 focus:outline-none">
                                <option value="EXPENSE">Expense Head</option>
                                <option value="INCOME">Income Head</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="isSubmitting" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] px-3 py-1 rounded-md transition cursor-pointer">
                            + Add Account Head
                        </button>
                    </div>
                </form>

                {{-- List Existing Categories --}}
                <div class="space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Active Chart of Accounts:</span>
                    <div class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-60 overflow-y-auto bg-white">
                        @foreach($allCategories as $cat)
                            <div class="p-2.5 flex items-center justify-between hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $cat->type === 'INCOME' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span class="font-semibold text-slate-800">{{ $cat->name }}</span>
                                    <span class="text-[10px] font-mono text-slate-400">({{ $cat->code }})</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $cat->type === 'INCOME' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $cat->type }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="showCategoryModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 3: Official Printable Debit / Credit Voucher Modal --}}
    <div x-show="showPrintModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showPrintModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0 no-print">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Official Financial Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Official signed debit / credit voucher document</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="window.print()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-[11px]"></i>
                        <span>Print Voucher</span>
                    </button>
                    <button type="button" @click="showPrintModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Printable Voucher Body --}}
            <div class="p-6 overflow-y-auto flex-1 bg-slate-50/30" id="printableVoucherArea">
                <template x-if="viewData">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-6 text-slate-800">
                        {{-- Top Corporate Header --}}
                        <div class="flex justify-between items-start pb-4 border-b border-slate-200">
                            <div>
                                <h2 class="text-lg font-black text-cyan-900 tracking-tight" x-text="viewData.tenant.name"></h2>
                                <p class="text-xs text-slate-600" x-text="viewData.tenant.address"></p>
                                <p class="text-xs text-slate-600">Phone: <span x-text="viewData.tenant.phone"></span></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400 block" x-text="viewData.voucher.type === 'INCOME' ? 'CREDIT / RECEIPT VOUCHER' : 'DEBIT / PAYMENT VOUCHER'"></span>
                                <span class="text-base font-black font-mono text-cyan-800 block" x-text="viewData.voucher.voucher_no"></span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border mt-1" :class="viewData.voucher.status_badge.class">
                                    <span x-text="viewData.voucher.status_badge.label"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Voucher Details Grid --}}
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Account Head:</span>
                                    <span class="font-bold text-slate-800" x-text="viewData.voucher.category_name"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500" x-text="viewData.voucher.type === 'INCOME' ? 'Received From:' : 'Paid To / Payee:'"></span>
                                    <span class="font-semibold text-slate-800" x-text="viewData.voucher.payee_payer"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Payment Method:</span>
                                    <span class="font-mono text-slate-800 uppercase" x-text="viewData.voucher.payment_method"></span>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Transaction Date:</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="viewData.voucher.transaction_date"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Reference / Cheque #:</span>
                                    <span class="font-mono text-slate-700" x-text="viewData.voucher.reference_no"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Bank / Account:</span>
                                    <span class="text-slate-700" x-text="viewData.voucher.account_name"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Particulars & Amount Banner --}}
                        <div class="p-4 rounded-xl border" :class="viewData.voucher.type === 'INCOME' ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/60 border-rose-200'">
                            <span class="text-[10px] font-bold uppercase tracking-wider block mb-1" :class="viewData.voucher.type === 'INCOME' ? 'text-emerald-800' : 'text-rose-800'">Voucher Particulars:</span>
                            <p class="font-semibold text-sm text-slate-900 mb-3" x-text="viewData.voucher.title"></p>
                            <div class="flex justify-between items-center border-t pt-2" :class="viewData.voucher.type === 'INCOME' ? 'border-emerald-200' : 'border-rose-200'">
                                <span class="font-bold text-xs" :class="viewData.voucher.type === 'INCOME' ? 'text-emerald-900' : 'text-rose-900'">Total Net Amount:</span>
                                <span class="font-mono font-black text-base" :class="viewData.voucher.type === 'INCOME' ? 'text-emerald-800' : 'text-rose-800'" x-text="formatCurrency(viewData.voucher.amount)"></span>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="text-xs text-slate-600">
                            <span class="font-semibold text-slate-700">Remarks / Notes: </span>
                            <span x-text="viewData.voucher.notes"></span>
                        </div>

                        {{-- Authorized Signatures --}}
                        <div class="pt-10 border-t border-slate-200 grid grid-cols-3 gap-6 text-center text-xs text-slate-500">
                            <div>
                                <div class="border-b border-slate-300 w-28 mx-auto mb-1"></div>
                                <span>Prepared By (<span x-text="viewData.voucher.creator_name"></span>)</span>
                            </div>
                            <div>
                                <div class="border-b border-slate-300 w-28 mx-auto mb-1"></div>
                                <span>Receiver / Payee</span>
                            </div>
                            <div>
                                <div class="border-b border-slate-300 w-28 mx-auto mb-1"></div>
                                <span>Authorized Manager</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function handlePeriodChange(val) {
        const fromInput = document.getElementById('dateFromInput');
        const toInput = document.getElementById('dateToInput');
        if (!fromInput || !toInput) return;

        const today = new Date();
        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        if (val === 'today') {
            fromInput.value = formatDate(today);
            toInput.value = formatDate(today);
        } else if (val === 'yesterday') {
            const y = new Date();
            y.setDate(y.getDate() - 1);
            fromInput.value = formatDate(y);
            toInput.value = formatDate(y);
        } else if (val === 'this_week') {
            const start = new Date(today);
            const day = start.getDay();
            const diff = start.getDate() - day + (day === 0 ? -6 : 1);
            start.setDate(diff);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'this_month') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'last_month') {
            const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const end = new Date(today.getFullYear(), today.getMonth(), 0);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'all') {
            fromInput.value = '';
            toInput.value = '';
        }
    }

    function expenseAccountsManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showVoucherModal: false,
            showCategoryModal: false,
            showPrintModal: false,
            isSubmitting: false,
            viewData: null,

            categories: @js($allCategories),

            voucherForm: {
                type: 'EXPENSE',
                category_id: '',
                title: '',
                amount: '',
                payment_method: 'CASH',
                account_name: 'Main Cash Vault',
                payee_payer: '',
                reference_no: '',
                transaction_date: '{{ date("Y-m-d") }}',
                notes: ''
            },

            newCategoryForm: {
                name: '',
                type: 'EXPENSE',
                monthly_budget: 0
            },

            get filteredCategories() {
                return this.categories.filter(c => c.type === this.voucherForm.type);
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 180;
                const right = Math.max(10, window.innerWidth - rect.right);
                let top = Math.round(rect.bottom) + 2;
                let bottom = 'auto';

                if (top + dropdownHeight > window.innerHeight) {
                    top = 'auto';
                    bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
                } else {
                    top = `${top}px`;
                }

                this.menuPos = {
                    top: top,
                    bottom: bottom,
                    right: `${right}px`,
                    left: 'auto'
                };
            },

            openVoucherModal(type = 'EXPENSE') {
                this.voucherForm = {
                    type: type,
                    category_id: '',
                    title: '',
                    amount: '',
                    payment_method: 'CASH',
                    account_name: 'Main Cash Vault',
                    payee_payer: '',
                    reference_no: '',
                    transaction_date: '{{ date("Y-m-d") }}',
                    notes: ''
                };
                this.showVoucherModal = true;
            },

            async submitVoucher() {
                this.isSubmitting = true;
                try {
                    const res = await fetch("{{ route('tenant.finance.expenses.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.voucherForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showVoucherModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Voucher Saved',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to post voucher.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error saving voucher: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitNewCategory() {
                this.isSubmitting = true;
                try {
                    const res = await fetch("{{ route('tenant.finance.expenses.category') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.newCategoryForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.categories.push(data.category);
                        this.newCategoryForm.name = '';
                        this.showCategoryModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Category Added',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to create category.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async viewVoucher(id) {
                try {
                    const url = "{{ url('admin/finance/expenses') }}/" + id;
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.viewData = data;
                        this.showPrintModal = true;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not load voucher data.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error fetching voucher: ' + e.message
                    });
                }
            },

            async approveVoucher(id) {
                const result = await Swal.fire({
                    title: 'Approve Voucher?',
                    text: 'Confirm approval and posting for this transaction voucher?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Approve Voucher',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl text-xs font-sans shadow-2xl border border-slate-200',
                        title: 'text-sm font-bold text-slate-800',
                        confirmButton: 'px-4 py-2 text-xs font-medium rounded-lg shadow-xs cursor-pointer',
                        cancelButton: 'px-4 py-2 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer'
                    }
                });

                if (!result.isConfirmed) return;

                try {
                    const url = "{{ url('admin/finance/expenses') }}/" + id + "/approve";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Voucher Approved',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Approval Failed',
                            text: data.message
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error approving voucher: ' + e.message
                    });
                }
            },

            async rejectVoucher(id) {
                const result = await Swal.fire({
                    title: 'Reject Voucher?',
                    text: 'Are you sure you want to reject this voucher transaction?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Reject',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl text-xs font-sans shadow-2xl border border-slate-200',
                        title: 'text-sm font-bold text-slate-800',
                        confirmButton: 'px-4 py-2 text-xs font-medium rounded-lg shadow-xs cursor-pointer',
                        cancelButton: 'px-4 py-2 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer'
                    }
                });

                if (!result.isConfirmed) return;

                try {
                    const url = "{{ url('admin/finance/expenses') }}/" + id + "/reject";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Voucher Rejected',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Rejection Failed',
                            text: data.message
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error rejecting voucher: ' + e.message
                    });
                }
            },

            async deleteVoucher(id) {
                const result = await Swal.fire({
                    title: 'Delete Voucher?',
                    text: 'Are you sure you want to permanently delete this voucher? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Permanently',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl text-xs font-sans shadow-2xl border border-slate-200',
                        title: 'text-sm font-bold text-slate-800',
                        confirmButton: 'px-4 py-2 text-xs font-medium rounded-lg shadow-xs cursor-pointer',
                        cancelButton: 'px-4 py-2 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer'
                    }
                });

                if (!result.isConfirmed) return;

                try {
                    const url = "{{ url('admin/finance/expenses') }}/" + id;
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Voucher Deleted',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: data.message
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error deleting voucher: ' + e.message
                    });
                }
            },

            copyVoucherNo(no) {
                navigator.clipboard.writeText(no);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Voucher number copied: ' + no,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            },

            formatNumber(num) {
                return (parseFloat(num) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            formatCurrency(amount) {
                const sym = '{{ $currencySymbol ?? "৳" }}';
                return sym + ' ' + this.formatNumber(amount);
            }
        };
    }
</script>
@endpush
