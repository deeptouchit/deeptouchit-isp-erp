@extends('tenant.layouts.app')

@section('title', 'Financial Statements & General Ledger - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS if needed --}}
@endpush

@section('content')
<div class="space-y-3" x-data="financialLedgerManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (Title + Action Buttons ONLY) --}}
    <div class="bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-xs shadow-2xs">
                <i class="fas fa-book-open"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Financial Statements &amp; General Ledger</h1>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.finance.ledger.export', request()->query()) }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs flex items-center gap-1.5 transition shadow-2xs">
                <i class="fas fa-file-csv text-emerald-600"></i>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('tenant.finance.ledger.print', request()->query()) }}" 
               target="_blank" 
               class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs flex items-center gap-1.5 transition shadow-2xs">
                <i class="fas fa-print"></i>
                <span>Print Statement</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (6 CARDS STRICTLY - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Gross Revenue / Inflow --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Gross Revenue</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-600 truncate">
                    @currency($kpi['gross_revenue'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-emerald-50 text-emerald-600 border-emerald-100">
                <i class="fas fa-arrow-down-left"></i>
            </div>
        </div>

        {{-- Card 2: Upstream Bandwidth COGS --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Upstream COGS</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">
                    @currency($kpi['upstream_cogs'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-indigo-50 text-indigo-600 border-indigo-100">
                <i class="fas fa-tower-broadcast"></i>
            </div>
        </div>

        {{-- Card 3: Gross Profit Margin % --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Gross Margin</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-700 truncate">
                    {{ $kpi['gross_margin'] }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-cyan-50 text-cyan-600 border-cyan-100">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        {{-- Card 4: Operating Expenses (OPEX) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Operating OPEX</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600 truncate">
                    @currency($kpi['operating_expenses'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-rose-50 text-rose-600 border-rose-100">
                <i class="fas fa-arrow-up-right"></i>
            </div>
        </div>

        {{-- Card 5: Net Profit (P&L) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Net Profit</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $kpi['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} truncate">
                    @currency($kpi['net_profit'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center {{ $kpi['net_profit'] >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' }}">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        {{-- Card 6: Liquid Cash & Bank Position --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Cash &amp; Bank Net</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800 truncate">
                    @currency($kpi['cash_net'] + $kpi['bank_net'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-slate-100 text-slate-700 border-slate-200">
                <i class="fas fa-vault"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH, STATEMENT TABS & FILTER BAR --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
        {{-- Statement Tab Switcher --}}
        <div class="flex items-center gap-1 border-b border-slate-100 pb-2 overflow-x-auto">
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'ledger'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'ledger' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-list-ol text-[11px]"></i>
                <span>General Ledger</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'pnl'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'pnl' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-file-invoice-dollar text-[11px]"></i>
                <span>Profit &amp; Loss Statement</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'cashbook'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'cashbook' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-vault text-[11px]"></i>
                <span>Cash &amp; Bank Summary</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'heads'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'heads' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-tags text-[11px]"></i>
                <span>Account Heads Summary</span>
            </a>
        </div>

        {{-- Filter Row --}}
        <form method="GET" action="{{ route('tenant.finance.ledger') }}" class="space-y-2">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <!-- Row 1: Search Box, Period Preset & Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2">
                <!-- Search Box -->
                <div class="md:col-span-4 relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search voucher, receipt, client, narrative..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Period Filter -->
                <div class="md:col-span-2">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value)"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 transition font-medium">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ $period === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Fiscal Year</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
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
                               value="{{ $dateFrom ?? ($startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : '') }}" 
                               title="From Date"
                               class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 transition">
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
                               value="{{ $dateTo ?? ($endDate ? \Carbon\Carbon::parse($endDate)->format('Y-m-d') : '') }}" 
                               title="To Date"
                               class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>
            </div>

            <!-- Row 2: Method / Channel, Per Page & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 pt-1 border-t border-slate-100">
                <!-- Payment Method -->
                <div class="md:col-span-5">
                    <select name="method" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all">All Channels / Methods</option>
                        <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank" {{ request('method') === 'bank' ? 'selected' : '' }}>Bank Wire / Cheque</option>
                        <option value="bkash" {{ request('method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                        <option value="nagad" {{ request('method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                        <option value="rocket" {{ request('method') === 'rocket' ? 'selected' : '' }}>Rocket</option>
                        <option value="cheque" {{ request('method') === 'cheque' ? 'selected' : '' }}>Bank Cheque</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="md:col-span-3">
                    <select name="per_page" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 per page</option>
                        <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 per page</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 per page</option>
                    </select>
                </div>

                <!-- Strict Action Buttons: [Filter] FIRST, [Reset] SECOND (AGENTS.md Rule 2.C) -->
                <div class="md:col-span-4 flex items-center justify-end gap-1.5">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.ledger', ['tab' => $tab]) }}" 
                       class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1 cursor-pointer flex-shrink-0" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TAB 1: GENERAL LEDGER (DEFAULT) --}}
    @if($tab === 'ledger')
        {{-- Opening Balance Alert Bar --}}
        <div class="bg-cyan-50/70 border border-cyan-200 rounded-lg px-3 py-2 flex items-center justify-between text-xs text-cyan-900">
            <div class="flex items-center gap-2">
                <i class="fas fa-info-circle text-cyan-600"></i>
                <span><strong>Reporting Period:</strong> {{ $periodLabel }}</span>
            </div>
            <div class="font-mono">
                Opening Balance: <strong class="text-cyan-800">@currency($openingBalance['net'])</strong>
            </div>
        </div>

        {{-- Master Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th class="w-32">Date &amp; Time</th>
                            <th class="w-32">Voucher / Ref</th>
                            <th>Account Head</th>
                            <th>Particulars / Description</th>
                            <th class="text-right w-28">Debit (Out -)</th>
                            <th class="text-right w-28">Credit (In +)</th>
                            <th class="text-right w-32">Balance</th>
                            <th class="w-12 text-center no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginator as $index => $item)
                            <tr>
                                {{-- 1. Index --}}
                                <td class="text-center font-mono text-slate-400">
                                    {{ $paginator->firstItem() + $index }}
                                </td>

                                {{-- 2. Date & Time --}}
                                <td class="font-mono text-slate-600">
                                    {{ $item['date'] }}
                                </td>

                                {{-- 3. Voucher / Ref No --}}
                                <td>
                                    <button type="button" 
                                            @click="viewEntry(@js($item))" 
                                            class="font-mono font-bold text-cyan-700 hover:text-cyan-900 hover:underline">
                                        {{ $item['ref_no'] }}
                                    </button>
                                </td>

                                {{-- 4. Account Head --}}
                                <td class="font-medium text-slate-800">
                                    {{ $item['category'] }}
                                </td>

                                {{-- 5. Particulars / Narrative --}}
                                <td class="text-slate-600 truncate max-w-xs" title="{{ $item['description'] }}">
                                    {{ $item['description'] }}
                                </td>

                                {{-- 6. Debit (Expense / Outflow) --}}
                                <td class="text-right font-mono font-semibold text-rose-600">
                                    @if($item['type'] === 'debit')
                                        -@currency($item['amount'])
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- 7. Credit (Revenue / Inflow) --}}
                                <td class="text-right font-mono font-semibold text-emerald-600">
                                    @if($item['type'] === 'credit')
                                        +@currency($item['amount'])
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- 8. Running Balance --}}
                                <td class="text-right font-mono font-bold {{ $item['running_balance'] >= 0 ? 'text-slate-800' : 'text-rose-700' }}">
                                    @currency($item['running_balance'])
                                </td>

                                {{-- 9. Action (3-Dot Menu) --}}
                                <td class="text-center">
                                    <button type="button" 
                                            @click="toggleMenu(@js($item), $event)" 
                                            class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-8 text-slate-400">
                                    <i class="fas fa-receipt text-2xl mb-1 block text-slate-300"></i>
                                    No financial ledger transactions recorded in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Bar --}}
            @if($paginator->hasPages())
                <div class="px-4 py-2 border-t border-slate-100 bg-slate-50/50">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 2: STATEMENT OF PROFIT & LOSS (INCOME STATEMENT WITH COGS) --}}
    @if($tab === 'pnl')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- Section A: Revenue Stream --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="bg-emerald-50/70 border-b border-emerald-100 px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle-arrow-down text-emerald-600 text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">A. Revenue &amp; Incomes</span>
                    </div>
                    <span class="text-xs font-bold font-mono text-emerald-700">@currency($pnl['total_gross_revenue'])</span>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-600">1. Customer Subscriptions</span>
                        <span class="font-mono font-bold text-slate-800">@currency($pnl['retail_revenue'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-600">2. Reseller Wholesale</span>
                        <span class="font-mono font-bold text-slate-800">@currency($pnl['wholesale_revenue'])</span>
                    </div>
                    @foreach($pnl['direct_income_groups'] as $headName => $amount)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-600">{{ $headName }}</span>
                            <span class="font-mono font-bold text-slate-800">@currency($amount)</span>
                        </div>
                    @endforeach
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-emerald-50/50 p-2.5 rounded-lg border border-emerald-200/60">
                        <span class="text-emerald-900 uppercase">Gross Revenue (A)</span>
                        <span class="font-mono text-emerald-700 text-sm">@currency($pnl['total_gross_revenue'])</span>
                    </div>
                </div>
            </div>

            {{-- Section B: Cost of Goods Sold (COGS - Upstream Bandwidth) --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="bg-indigo-50/70 border-b border-indigo-100 px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-tower-broadcast text-indigo-600 text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">B. Upstream COGS</span>
                    </div>
                    <span class="text-xs font-bold font-mono text-indigo-700">@currency($pnl['total_cogs'])</span>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    @forelse($pnl['cogs_groups'] as $carrierName => $amount)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-600">{{ $carrierName }}</span>
                            <span class="font-mono font-bold text-indigo-700">@currency($amount)</span>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400">No upstream carrier disbursements in this period.</div>
                    @endforelse
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-indigo-50/50 p-2.5 rounded-lg border border-indigo-200/60">
                        <span class="text-indigo-900 uppercase">Total COGS (B)</span>
                        <span class="font-mono text-indigo-700 text-sm">@currency($pnl['total_cogs'])</span>
                    </div>
                </div>
            </div>

            {{-- Section C: Operating Expenses (OPEX) --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="bg-rose-50/70 border-b border-rose-100 px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle-arrow-up text-rose-600 text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">C. Operating OPEX</span>
                    </div>
                    <span class="text-xs font-bold font-mono text-rose-700">@currency($pnl['total_expenses'])</span>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    @forelse($pnl['expense_groups'] as $headName => $amount)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-600">{{ $headName }}</span>
                            <span class="font-mono font-bold text-rose-600">@currency($amount)</span>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400">No operational expenses recorded in this period.</div>
                    @endforelse
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-rose-50/50 p-2.5 rounded-lg border border-rose-200/60">
                        <span class="text-rose-900 uppercase">Total OPEX (C)</span>
                        <span class="font-mono text-rose-700 text-sm">@currency($pnl['total_expenses'])</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Net Profit Summary Banner --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Gross Profit (A - B)</span>
                    <span class="text-base font-bold font-mono text-cyan-700">
                        @currency($pnl['gross_profit']) <span class="text-xs font-normal text-slate-500">({{ $pnl['gross_margin'] }}%)</span>
                    </span>
                </div>
                <div class="border-l border-slate-200 pl-6">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Net Operating Profit (A - B - C)</span>
                    <span class="text-lg font-bold font-mono {{ $pnl['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        @currency($pnl['net_profit']) <span class="text-xs font-normal text-slate-500">({{ $pnl['profit_margin'] }}%)</span>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.finance.ledger.print', array_merge(request()->query(), ['tab' => 'pnl'])) }}" 
                   target="_blank" 
                   class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition shadow-2xs">
                    <i class="fas fa-print mr-1"></i> Print P&amp;L Statement
                </a>
            </div>
        </div>
    @endif

    {{-- TAB 3: CASH & BANK BOOK SUMMARY --}}
    @if($tab === 'cashbook')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {{-- 1. Cash In Hand Book --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-xs">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-800">Physical Cash Book</h3>
                    </div>
                    <span class="font-mono font-bold text-xs {{ $cashbook['cash_net'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        @currency($cashbook['cash_net'])
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">Total Cash Inflow (Payments + OTC)</span>
                        <span class="font-mono font-bold text-emerald-600">+@currency($cashbook['cash_in'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">Total Cash Outflow (Disbursements)</span>
                        <span class="font-mono font-bold text-rose-600">-@currency($cashbook['cash_out'])</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-amber-50/60 border border-amber-200 flex items-center justify-between font-bold">
                        <span class="text-amber-900">Net Physical Cash in Hand:</span>
                        <span class="font-mono text-amber-800 text-sm">@currency($cashbook['cash_net'])</span>
                    </div>
                </div>
            </div>

            {{-- 2. Bank & Digital MFS Accounts Book --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-200 flex items-center justify-center text-xs">
                            <i class="fas fa-building-columns"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-800">Bank &amp; MFS Book</h3>
                    </div>
                    <span class="font-mono font-bold text-xs {{ $cashbook['bank_net'] >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                        @currency($cashbook['bank_net'])
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">Total Bank &amp; MFS Deposits (Wholesale + Online)</span>
                        <span class="font-mono font-bold text-emerald-600">+@currency($cashbook['bank_in'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">Total Bank Disbursements (Bandwidth, Rent)</span>
                        <span class="font-mono font-bold text-rose-600">-@currency($cashbook['bank_out'])</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-cyan-50/60 border border-cyan-200 flex items-center justify-between font-bold">
                        <span class="text-cyan-900">Net Bank &amp; MFS Position:</span>
                        <span class="font-mono text-cyan-800 text-sm">@currency($cashbook['bank_net'])</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 4: ACCOUNT HEADS SUMMARY --}}
    @if($tab === 'heads')
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th class="w-28">Head Code</th>
                            <th>Account Head Title</th>
                            <th class="w-24">Type</th>
                            <th class="text-right w-32">Debit (Expense)</th>
                            <th class="text-right w-32">Credit (Revenue)</th>
                            <th class="text-right w-32">Net Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($headSummary as $index => $h)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $index + 1 }}</td>
                                <td class="font-mono font-bold text-slate-700">{{ $h['code'] }}</td>
                                <td class="font-medium text-slate-800">{{ $h['name'] }}</td>
                                <td>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $h['type'] === 'income' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $h['type'] }}
                                    </span>
                                </td>
                                <td class="text-right font-mono font-semibold text-rose-600">
                                    {{ $h['debit'] > 0 ? '' : '-' }}@if($h['debit'] > 0)@currency($h['debit'])@endif
                                </td>
                                <td class="text-right font-mono font-semibold text-emerald-600">
                                    {{ $h['credit'] > 0 ? '' : '-' }}@if($h['credit'] > 0)@currency($h['credit'])@endif
                                </td>
                                <td class="text-right font-mono font-bold {{ $h['net'] >= 0 ? 'text-slate-800' : 'text-rose-700' }}">
                                    @currency($h['net'])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-slate-400">No account heads registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- 5. FLOATING ACTION DROPDOWN MENU --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null" 
         class="fixed z-50 bg-white rounded-lg shadow-xl border border-slate-200 py-1 w-44 text-xs"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        <button type="button" 
                @click="viewEntry(activeMenu); activeMenu = null" 
                class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2">
            <i class="fas fa-eye text-cyan-600 w-3.5"></i>
            <span>View Details</span>
        </button>

        <button type="button" 
                @click="copyRef(activeMenu); activeMenu = null" 
                class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2">
            <i class="fas fa-copy text-slate-500 w-3.5"></i>
            <span>Copy Reference</span>
        </button>

        <a :href="'{{ route('tenant.finance.ledger.print') }}?search=' + encodeURIComponent(activeMenu?.ref_no || '')" 
           target="_blank" 
           @click="activeMenu = null"
           class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2">
            <i class="fas fa-print text-blue-600 w-3.5"></i>
            <span>Print Entry</span>
        </a>
    </div>

    {{-- 6. VIEW ENTRY DETAILS MODAL --}}
    <div x-show="showEntryModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.outside="showEntryModal = false">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Ledger Entry Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="selectedEntry?.ref_no"></p>
                    </div>
                </div>
                <button type="button" @click="showEntryModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs" x-show="selectedEntry">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Ledger Classification</span>
                        <span class="font-bold text-slate-800 text-xs mt-0.5 block" x-text="selectedEntry?.category"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Amount</span>
                        <span class="font-bold font-mono text-sm mt-0.5 block" 
                              :class="selectedEntry?.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'" 
                              x-text="(selectedEntry?.type === 'credit' ? '+' : '-') + '{{ $currencySymbol }} ' + Number(selectedEntry?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Party / Subscriber / Payee:</span>
                        <span class="font-semibold text-slate-800" x-text="selectedEntry?.party"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Payment Channel:</span>
                        <span class="font-mono uppercase font-bold text-slate-700" x-text="selectedEntry?.payment_method"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Posting Date &amp; Time:</span>
                        <span class="font-mono text-slate-700" x-text="selectedEntry?.date"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Transaction Status:</span>
                        <span class="font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800" x-text="selectedEntry?.status"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Particulars / Narrative Note</label>
                    <p class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-700 text-xs font-normal" x-text="selectedEntry?.description"></p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <a :href="'{{ route('tenant.finance.ledger.print') }}?search=' + encodeURIComponent(selectedEntry?.ref_no || '')" 
                   target="_blank" 
                   class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-print"></i>
                    <span>Print This Entry</span>
                </a>
                <button type="button" @click="showEntryModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                    Close
                </button>
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
    } else if (val === 'this_quarter') {
        const quarterMonth = Math.floor(today.getMonth() / 3) * 3;
        const start = new Date(today.getFullYear(), quarterMonth, 1);
        const end = new Date(today.getFullYear(), quarterMonth + 3, 0);
        fromInput.value = formatDate(start);
        toInput.value = formatDate(end);
    } else if (val === 'this_year') {
        const start = new Date(today.getFullYear(), 0, 1);
        const end = new Date(today.getFullYear(), 11, 31);
        fromInput.value = formatDate(start);
        toInput.value = formatDate(end);
    } else if (val === 'all') {
        fromInput.value = '';
        toInput.value = '';
    }
}

function financialLedgerManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        showEntryModal: false,
        selectedEntry: null,

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 120;
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

        viewEntry(entry) {
            this.selectedEntry = entry;
            this.showEntryModal = true;
        },

        copyRef(entry) {
            if (entry?.ref_no) {
                navigator.clipboard.writeText(entry.ref_no);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Reference ${entry.ref_no} copied to clipboard!`,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            }
        }
    };
}
</script>
@endpush
