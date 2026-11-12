@extends('tenant.layouts.app')

@section('title', __('Financial Statements & General Ledger') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS if needed --}}
@endpush

@section('content')
<div class="space-y-3" x-data="financialLedgerManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-book-open"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Financial Statements & General Ledger') }}</h1>
        </div>
        
        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.finance.ledger.export', request()->query()) }}" 
               class="h-[34px] py-0 px-3 inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition">
                <i class="fas fa-file-csv text-emerald-600 text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <a href="{{ route('tenant.finance.ledger.print', request()->query()) }}" 
               target="_blank" 
               class="h-[34px] py-0 px-3.5 inline-flex items-center gap-1.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition">
                <i class="fas fa-print text-[11px]"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (6 CARDS STRICTLY - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Gross Revenue / Inflow --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-1.5">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Gross Revenue') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Upstream COGS') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Gross Margin') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Operating OPEX') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Net Profit') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Cash & Bank Net') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800 truncate">
                    @currency($kpi['cash_net'] + $kpi['bank_net'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-slate-100 text-slate-700 border-slate-200">
                <i class="fas fa-vault"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search, Statement Tabs & Customer-Standard 2-Tier Filter System (AGENTS.md Rule 2.C) --}}
    @php
        $activeMoreCount = 0;
        if (!empty(request('method')) && request('method') !== 'all') $activeMoreCount++;
        if (request()->filled('date_from')) $activeMoreCount++;
        if (request()->filled('date_to')) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;
    @endphp

    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        {{-- Statement Tab Switcher --}}
        <div class="flex items-center gap-1 border-b border-slate-100 pb-2 overflow-x-auto">
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'ledger'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'ledger' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-list-ol text-[11px]"></i>
                <span>{{ __('General Ledger') }}</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'pnl'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'pnl' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-file-invoice-dollar text-[11px]"></i>
                <span>{{ __('Profit & Loss Statement') }}</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'cashbook'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'cashbook' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-vault text-[11px]"></i>
                <span>{{ __('Cash & Bank Summary') }}</span>
            </a>
            <a href="{{ route('tenant.finance.ledger', array_merge(request()->query(), ['tab' => 'heads'])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'heads' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'text-slate-600 hover:bg-slate-50 border border-transparent' }}">
                <i class="fas fa-tags text-[11px]"></i>
                <span>{{ __('Account Heads Summary') }}</span>
            </a>
        </div>

        {{-- 2-Tier Filter Form --}}
        <form method="GET" action="{{ route('tenant.finance.ledger') }}" id="ledgerFilterForm" class="space-y-2">
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- 1st Tier: Primary Single Row (Strict Uniform Height h-[34px]) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none transition-colors duration-150"
                          :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="{{ __('Search voucher, receipt, client, narrative...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs placeholder-slate-400 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer transition">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Reporting Period Preset --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value); this.form.submit();"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                        <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>{{ __('Yesterday') }}</option>
                        <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                        <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>{{ __('Last Month') }}</option>
                        <option value="this_quarter" {{ $period === 'this_quarter' ? 'selected' : '' }}>{{ __('This Quarter') }}</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>{{ __('This Fiscal Year') }}</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>{{ __('Custom Range') }}</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('All Time') }}</option>
                    </select>
                </div>

                {{-- Pagination Selector (per_page) --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="15" {{ request('per_page', '20') == '15' ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ request('per_page', '20') == '30' ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                    </select>
                </div>

                {{-- "More Filters" Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-3 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer flex-shrink-0"
                        :class="showMoreFilters || {{ $isMoreOpen ? 'true' : 'false' }} ? 'bg-cyan-50/80 border-cyan-300 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'">
                    <i class="fas fa-sliders text-[11px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center ml-0.5">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] ml-0.5 transition-transform duration-150"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Action Buttons Sequence: [Filter] FIRST, [Reset] SECOND (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.finance.ledger', ['tab' => $tab]) }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer flex-shrink-0" 
                       title="{{ __('Reset All Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-2">

                {{-- Channel / Method --}}
                <div>
                    <select name="method" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all">{{ __('All Channels / Methods') }}</option>
                        <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>{{ __('Cash') }}</option>
                        <option value="bank" {{ request('method') === 'bank' ? 'selected' : '' }}>{{ __('Bank Wire / Transfer') }}</option>
                        <option value="bkash" {{ request('method') === 'bkash' ? 'selected' : '' }}>{{ __('bKash') }}</option>
                        <option value="nagad" {{ request('method') === 'nagad' ? 'selected' : '' }}>{{ __('Nagad') }}</option>
                        <option value="rocket" {{ request('method') === 'rocket' ? 'selected' : '' }}>{{ __('Rocket') }}</option>
                        <option value="cheque" {{ request('method') === 'cheque' ? 'selected' : '' }}>{{ __('Bank Cheque') }}</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <input type="date" 
                           name="date_from" 
                           id="dateFromInput"
                           value="{{ $dateFrom ?? ($startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : '') }}" 
                           title="{{ __('From Date') }}"
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                </div>

                {{-- Date To --}}
                <div>
                    <input type="date" 
                           name="date_to" 
                           id="dateToInput"
                           value="{{ $dateTo ?? ($endDate ? \Carbon\Carbon::parse($endDate)->format('Y-m-d') : '') }}" 
                           title="{{ __('To Date') }}"
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
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
                <span><strong>{{ __('Reporting Period:') }}</strong> {{ $periodLabel }}</span>
            </div>
            <div class="font-mono">
                {{ __('Opening Balance:') }} <strong class="text-cyan-800">@currency($openingBalance['net'])</strong>
            </div>
        </div>

        {{-- Master Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="w-32 text-center font-semibold">{{ __('DATE & TIME') }}</th>
                            <th class="w-32 text-center font-semibold">{{ __('VOUCHER / REF') }}</th>
                            <th class="w-44 text-center font-semibold">{{ __('ACCOUNT HEAD') }}</th>
                            <th class="text-center font-semibold">{{ __('PARTICULARS / DESCRIPTION') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('DEBIT (OUT -)') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('CREDIT (IN +)') }}</th>
                            <th class="w-32 text-center font-semibold">{{ __('BALANCE') }}</th>
                            <th class="w-12 text-center font-semibold no-sort">{{ __('ACTION') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginator as $index => $item)
                            <tr>
                                {{-- 1. Index --}}
                                <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                    {{ $paginator->firstItem() + $index }}
                                </td>

                                {{-- 2. Date & Time --}}
                                <td class="text-center font-mono text-slate-600 text-xs whitespace-nowrap">
                                    {{ $item['date'] }}
                                </td>

                                {{-- 3. Voucher / Ref No --}}
                                <td class="text-center whitespace-nowrap">
                                    <button type="button" 
                                            @click="viewEntry(@js($item))" 
                                            class="font-mono font-bold text-cyan-700 hover:text-cyan-900 hover:underline cursor-pointer">
                                        {{ $item['ref_no'] }}
                                    </button>
                                </td>

                                {{-- 4. Account Head --}}
                                <td class="text-left font-medium text-slate-800 whitespace-nowrap">
                                    {{ $item['category'] }}
                                </td>

                                {{-- 5. Particulars / Narrative --}}
                                <td class="text-left text-slate-600 truncate max-w-xs whitespace-nowrap" title="{{ $item['description'] }}">
                                    {{ $item['description'] }}
                                </td>

                                {{-- 6. Debit (Expense / Outflow) --}}
                                <td class="text-right font-mono font-semibold text-rose-600 whitespace-nowrap">
                                    @if($item['type'] === 'debit')
                                        -@currency($item['amount'])
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- 7. Credit (Revenue / Inflow) --}}
                                <td class="text-right font-mono font-semibold text-emerald-600 whitespace-nowrap">
                                    @if($item['type'] === 'credit')
                                        +@currency($item['amount'])
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- 8. Running Balance --}}
                                <td class="text-right font-mono font-bold whitespace-nowrap {{ $item['running_balance'] >= 0 ? 'text-slate-800' : 'text-rose-700' }}">
                                    @currency($item['running_balance'])
                                </td>

                                {{-- 9. Action (3-Dot Menu) --}}
                                <td class="text-center whitespace-nowrap">
                                    <button type="button" 
                                            @click="toggleMenu(@js($item), $event)" 
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-700 inline-flex items-center justify-center transition cursor-pointer">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-receipt text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Ledger Transactions Found') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No financial ledger entries recorded matching the selected criteria.') }}</p>
                                        </div>
                                    </div>
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
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('A. Revenue & Incomes') }}</span>
                    </div>
                    <span class="text-xs font-bold font-mono text-emerald-700">@currency($pnl['total_gross_revenue'])</span>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-600">{{ __('1. Customer Subscriptions') }}</span>
                        <span class="font-mono font-bold text-slate-800">@currency($pnl['retail_revenue'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-600">{{ __('2. Reseller Wholesale') }}</span>
                        <span class="font-mono font-bold text-slate-800">@currency($pnl['wholesale_revenue'])</span>
                    </div>
                    @foreach($pnl['direct_income_groups'] as $headName => $amount)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-600">{{ $headName }}</span>
                            <span class="font-mono font-bold text-slate-800">@currency($amount)</span>
                        </div>
                    @endforeach
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-emerald-50/50 p-2.5 rounded-lg border border-emerald-200/60">
                        <span class="text-emerald-900 uppercase">{{ __('Gross Revenue (A)') }}</span>
                        <span class="font-mono text-emerald-700 text-sm">@currency($pnl['total_gross_revenue'])</span>
                    </div>
                </div>
            </div>

            {{-- Section B: Cost of Goods Sold (COGS - Upstream Bandwidth) --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="bg-indigo-50/70 border-b border-indigo-100 px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-tower-broadcast text-indigo-600 text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('B. Upstream COGS') }}</span>
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
                        <div class="py-4 text-center text-slate-400">{{ __('No upstream carrier disbursements in this period.') }}</div>
                    @endforelse
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-indigo-50/50 p-2.5 rounded-lg border border-indigo-200/60">
                        <span class="text-indigo-900 uppercase">{{ __('Total COGS (B)') }}</span>
                        <span class="font-mono text-indigo-700 text-sm">@currency($pnl['total_cogs'])</span>
                    </div>
                </div>
            </div>

            {{-- Section C: Operating Expenses (OPEX) --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="bg-rose-50/70 border-b border-rose-100 px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle-arrow-up text-rose-600 text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('C. Operating OPEX') }}</span>
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
                        <div class="py-4 text-center text-slate-400">{{ __('No operational expenses recorded in this period.') }}</div>
                    @endforelse
                    <div class="pt-2 flex items-center justify-between font-bold text-xs bg-rose-50/50 p-2.5 rounded-lg border border-rose-200/60">
                        <span class="text-rose-900 uppercase">{{ __('Total OPEX (C)') }}</span>
                        <span class="font-mono text-rose-700 text-sm">@currency($pnl['total_expenses'])</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Net Profit Summary Banner --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('Gross Profit (A - B)') }}</span>
                    <span class="text-base font-bold font-mono text-cyan-700">
                        @currency($pnl['gross_profit']) <span class="text-xs font-normal text-slate-500">({{ $pnl['gross_margin'] }}%)</span>
                    </span>
                </div>
                <div class="border-l border-slate-200 pl-6">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('Net Operating Profit (A - B - C)') }}</span>
                    <span class="text-lg font-bold font-mono {{ $pnl['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        @currency($pnl['net_profit']) <span class="text-xs font-normal text-slate-500">({{ $pnl['profit_margin'] }}%)</span>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.finance.ledger.print', array_merge(request()->query(), ['tab' => 'pnl'])) }}" 
                   target="_blank" 
                   class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition shadow-2xs">
                    <i class="fas fa-print mr-1"></i> {{ __('Print P&L Statement') }}
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
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Physical Cash Book') }}</h3>
                    </div>
                    <span class="font-mono font-bold text-xs {{ $cashbook['cash_net'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        @currency($cashbook['cash_net'])
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">{{ __('Total Cash Inflow (Payments + OTC)') }}</span>
                        <span class="font-mono font-bold text-emerald-600">+@currency($cashbook['cash_in'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">{{ __('Total Cash Outflow (Disbursements)') }}</span>
                        <span class="font-mono font-bold text-rose-600">-@currency($cashbook['cash_out'])</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-amber-50/60 border border-amber-200 flex items-center justify-between font-bold">
                        <span class="text-amber-900">{{ __('Net Physical Cash in Hand:') }}</span>
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
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Bank & MFS Book') }}</h3>
                    </div>
                    <span class="font-mono font-bold text-xs {{ $cashbook['bank_net'] >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                        @currency($cashbook['bank_net'])
                    </span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">{{ __('Total Bank & MFS Deposits (Wholesale + Online)') }}</span>
                        <span class="font-mono font-bold text-emerald-600">+@currency($cashbook['bank_in'])</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-600">{{ __('Total Bank Disbursements (Bandwidth, Rent)') }}</span>
                        <span class="font-mono font-bold text-rose-600">-@currency($cashbook['bank_out'])</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-cyan-50/60 border border-cyan-200 flex items-center justify-between font-bold">
                        <span class="text-cyan-900">{{ __('Net Bank & MFS Position:') }}</span>
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
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="w-28 text-center font-semibold">{{ __('HEAD CODE') }}</th>
                            <th class="text-center font-semibold">{{ __('ACCOUNT HEAD TITLE') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('TYPE') }}</th>
                            <th class="w-32 text-center font-semibold">{{ __('DEBIT (EXPENSE)') }}</th>
                            <th class="w-32 text-center font-semibold">{{ __('CREDIT (REVENUE)') }}</th>
                            <th class="w-32 text-center font-semibold">{{ __('NET TOTAL') }}</th>
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
                                        {{ $h['type'] === 'income' ? __('Income') : __('Expense') }}
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
                                <td colspan="7" class="text-center py-6 text-slate-400">{{ __('No account heads registered.') }}</td>
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
            <span>{{ __('View Details') }}</span>
        </button>

        <button type="button" 
                @click="copyRef(activeMenu); activeMenu = null" 
                class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2">
            <i class="fas fa-copy text-slate-500 w-3.5"></i>
            <span>{{ __('Copy Reference') }}</span>
        </button>

        <a :href="'{{ route('tenant.finance.ledger.print') }}?search=' + encodeURIComponent(activeMenu?.ref_no || '')" 
           target="_blank" 
           @click="activeMenu = null"
           class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2">
            <i class="fas fa-print text-blue-600 w-3.5"></i>
            <span>{{ __('Print Entry') }}</span>
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
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Ledger Entry Details') }}</h3>
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
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Ledger Classification') }}</span>
                        <span class="font-bold text-slate-800 text-xs mt-0.5 block" x-text="selectedEntry?.category"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Amount') }}</span>
                        <span class="font-bold font-mono text-sm mt-0.5 block" 
                              :class="selectedEntry?.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'" 
                              x-text="(selectedEntry?.type === 'credit' ? '+' : '-') + @js($currencySymbol ?? '৳') + ' ' + Number(selectedEntry?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">{{ __('Party / Subscriber / Payee:') }}</span>
                        <span class="font-semibold text-slate-800" x-text="selectedEntry?.party"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">{{ __('Payment Channel:') }}</span>
                        <span class="font-mono uppercase font-bold text-slate-700" x-text="selectedEntry?.payment_method"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">{{ __('Posting Date & Time:') }}</span>
                        <span class="font-mono text-slate-700" x-text="selectedEntry?.date"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">{{ __('Transaction Status:') }}</span>
                        <span class="font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800" x-text="selectedEntry?.status"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">{{ __('Particulars / Narrative Note') }}</label>
                    <p class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-700 text-xs font-normal" x-text="selectedEntry?.description"></p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <a :href="'{{ route('tenant.finance.ledger.print') }}?search=' + encodeURIComponent(selectedEntry?.ref_no || '')" 
                   target="_blank" 
                   class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-print"></i>
                    <span>{{ __('Print This Entry') }}</span>
                </a>
                <button type="button" @click="showEntryModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                    {{ __('Close') }}
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
                    title: @json(__('Reference :ref copied to clipboard!')).replace(':ref', entry.ref_no),
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
