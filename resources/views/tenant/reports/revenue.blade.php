@extends('tenant.layouts.app')

@section('title', 'Revenue & Growth Report - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .progress-bar-fill {
        transition: width 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="revenueReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Revenue &amp; Business Growth Analytics</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Quick Filter Indicator Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fas fa-calendar-days text-slate-500 text-[10px]"></i>
                <span>{{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.revenue.print', request()->all()) }}" 
               target="_blank" 
               class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>Print Statement</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.revenue.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Revenue (Collected) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Revenue</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    @currency($totalRevenue)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 2: Operating Expenses (OPEX) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Operating OPEX</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block">
                    @currency($periodExpenses)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 3: Net Operating Profit --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Net Profit ({{ round($profitMarginPct, 1) }}%)</span>
                <span class="text-[13px] font-bold font-mono {{ $netProfit >= 0 ? 'text-cyan-700' : 'text-rose-700' }} leading-tight block">
                    @currency($netProfit)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-scale-balanced"></i>
            </div>
        </div>

        {{-- Card 4: Contracted MRR --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Contracted MRR</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    @currency($totalMrr)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-arrows-rotate"></i>
            </div>
        </div>

        {{-- Card 5: ARPU (Avg Revenue/User) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">ARPU (Per User)</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    @currency($arpu)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-tag"></i>
            </div>
        </div>

        {{-- Card 6: MoM Revenue Growth --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">MoM Growth Rate</span>
                <span class="text-[13px] font-bold font-mono {{ $growthRatePct >= 0 ? 'text-emerald-700' : 'text-rose-700' }} leading-tight block">
                    {{ ($growthRatePct >= 0 ? '+' : '') . round($growthRatePct, 1) }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $growthRatePct >= 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-rose-200 bg-rose-50 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas {{ $growthRatePct >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (2-Row Day-to-Day Layout) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.reports.revenue') }}" class="space-y-2.5">
            {{-- Row 1: Period Preset, Date From, Date To, Revenue Stream Scope --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                {{-- Filter 1: Date Range Preset --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Period Preset</label>
                    <select name="preset" x-model="selectedPreset" @change="handlePeriodChange($event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="today" {{ $preset === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $preset === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $preset === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ $preset === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ $preset === 'this_year' ? 'selected' : '' }}>This Year ({{ date('Y') }})</option>
                        <option value="last_12_months" {{ $preset === 'last_12_months' ? 'selected' : '' }}>Last 12 Months</option>
                        <option value="all" {{ $preset === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="custom" {{ $preset === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                    </select>
                </div>

                {{-- Filter 2: Date From --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Date From</label>
                    <input type="date" name="date_from" x-model="dateFrom" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                </div>

                {{-- Filter 3: Date To --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Date To</label>
                    <input type="date" name="date_to" x-model="dateTo" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                </div>

                {{-- Filter 4: Revenue Stream Scope --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Revenue Stream</label>
                    <select name="stream" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all" {{ $stream === 'all' ? 'selected' : '' }}>All Business Streams (Combined)</option>
                        <option value="retail" {{ $stream === 'retail' ? 'selected' : '' }}>Retail Subscribers Billing</option>
                        <option value="wholesale" {{ $stream === 'wholesale' ? 'selected' : '' }}>Sub-ISP Reseller Wholesale</option>
                        <option value="recharge" {{ $stream === 'recharge' ? 'selected' : '' }}>Reseller Wallet Recharges</option>
                        <option value="gateway" {{ $stream === 'gateway' ? 'selected' : '' }}>Digital Payment Gateways (bKash/Nagad/SSL)</option>
                        <option value="direct" {{ $stream === 'direct' ? 'selected' : '' }}>Direct &amp; Other Incomes</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Coverage Zone, Package Profile, Action Buttons --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                {{-- Filter 5: Zone Selector --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Coverage Zone</label>
                    <select name="zone_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Coverage Zones</option>
                        @foreach($allZones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 6: Package Selector --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Internet Package</label>
                    <select name="package_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Internet Packages</option>
                        @foreach($allPackages as $p)
                            <option value="{{ $p->id }}" {{ (string)$packageId === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 7: Action Buttons (Strict Universal Standard) --}}
                <div class="flex items-end gap-1.5">
                    <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.reports.revenue') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER 12-MONTH HISTORICAL GROWTH TABLE (<table class="saas-table">) --}}
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-3.5 py-2.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div class="flex items-center gap-2">
                <i class="fas fa-table-columns text-slate-500 text-xs"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">12-Month Historical Growth Matrix</h2>
            </div>
            <span class="text-[11px] text-slate-500 font-medium">Month-over-Month (MoM) Financial &amp; Base Analytics</span>
        </div>

        <div class="overflow-x-auto min-h-[260px]">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-24">Month</th>
                        <th class="text-right">Retail</th>
                        <th class="text-right">Wholesale</th>
                        <th class="text-right">Direct</th>
                        <th class="text-right font-bold text-slate-900">Total Rev</th>
                        <th class="text-right text-rose-700">Expense</th>
                        <th class="text-right">Net Profit</th>
                        <th class="text-center w-16">Margin</th>
                        <th class="text-center w-20">MoM %</th>
                        <th class="text-center w-20">Subs</th>
                        <th class="text-right w-20">ARPU</th>
                        <th class="no-sort w-14 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyMatrix as $row)
                        <tr>
                            {{-- Month Label --}}
                            <td class="font-bold text-slate-800">
                                <span class="font-mono">{{ $row['month_label'] }}</span>
                            </td>

                            {{-- Retail Billing --}}
                            <td class="text-right font-mono font-medium text-slate-700">
                                @currency($row['retail_revenue'])
                            </td>

                            {{-- Wholesale Billing --}}
                            <td class="text-right font-mono font-medium text-slate-700">
                                @currency($row['wholesale_revenue'])
                            </td>

                            {{-- Direct Income --}}
                            <td class="text-right font-mono font-medium text-slate-700">
                                @currency($row['direct_revenue'])
                            </td>

                            {{-- Total Gross Revenue --}}
                            <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/30">
                                @currency($row['total_revenue'])
                            </td>

                            {{-- Operating OPEX Expenses --}}
                            <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">
                                @currency($row['total_expenses'])
                            </td>

                            {{-- Net Operating Profit --}}
                            <td class="text-right font-mono font-bold {{ $row['net_profit'] >= 0 ? 'text-cyan-800' : 'text-rose-600' }}">
                                @currency($row['net_profit'])
                            </td>

                            {{-- Profit Margin % --}}
                            <td class="text-center font-mono font-semibold text-slate-700">
                                <span class="px-1.5 py-0.5 rounded text-[10.5px] {{ $row['profit_margin_pct'] >= 30 ? 'bg-emerald-100 text-emerald-800' : ($row['profit_margin_pct'] >= 10 ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $row['profit_margin_pct'] }}%
                                </span>
                            </td>

                            {{-- MoM Growth % --}}
                            <td class="text-center font-mono font-bold">
                                @if($row['growth_rate_pct'] > 0)
                                    <span class="text-emerald-700 inline-flex items-center gap-0.5">
                                        <i class="fas fa-caret-up text-[10px]"></i> +{{ $row['growth_rate_pct'] }}%
                                    </span>
                                @elseif($row['growth_rate_pct'] < 0)
                                    <span class="text-rose-600 inline-flex items-center gap-0.5">
                                        <i class="fas fa-caret-down text-[10px]"></i> {{ $row['growth_rate_pct'] }}%
                                    </span>
                                @else
                                    <span class="text-slate-400">0.0%</span>
                                @endif
                            </td>

                            {{-- Active Base --}}
                            <td class="text-center font-mono font-semibold text-slate-800">
                                {{ number_format($row['active_subscribers']) }}
                            </td>

                            {{-- ARPU --}}
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($row['arpu'])
                            </td>

                            {{-- Action --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="openMonthModal({{ json_encode($row) }})"
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition cursor-pointer" 
                                        title="View Month Details">
                                    <i class="fas fa-circle-info text-[11px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-10 text-slate-400">
                                <i class="fas fa-chart-line text-3xl mb-2 text-slate-300 block"></i>
                                <span class="text-xs font-medium">No historical revenue data recorded for this ISP workspace.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. EXECUTIVE REVENUE BREAKDOWN GRIDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: Top Revenue-Generating Packages --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-cubes text-cyan-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Top Revenue Packages</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Share %</span>
            </div>

            <div class="space-y-2.5">
                @forelse($topPackages as $pkg)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800 truncate pr-2">{{ $pkg['name'] }}</span>
                            <span class="font-mono font-bold text-slate-900">@currency($pkg['monthly_revenue'])</span>
                        </div>
                        <div class="flex items-center justify-between text-[10.5px] text-slate-500">
                            <span>{{ $pkg['subscribers_count'] }} Active Users ({{ $pkg['speed'] }})</span>
                            <span class="font-mono font-semibold text-cyan-700">{{ $pkg['share_pct'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-cyan-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ min(100, $pkg['share_pct']) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No package analytics found.</p>
                @endforelse
            </div>
        </div>

        {{-- Card 2: Top Revenue Zones & Coverage Areas --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-location-dot text-indigo-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Top Coverage Zones</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">MRR Share</span>
            </div>

            <div class="space-y-2.5">
                @forelse($topZones as $z)
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block">{{ $z->name }}</span>
                            <span class="text-[10.5px] text-slate-500">{{ $z->customers_count }} Subscribers</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold text-indigo-700 block">@currency($z->total_mrr ?? 0)</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Monthly Yield</span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No zone data mapped yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Card 3: Payment Channel Share & Gateways --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-wallet text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Collection Channels</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Inflow</span>
            </div>

            <div class="space-y-2">
                @forelse($paymentChannels as $ch)
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-white border border-slate-200 flex items-center justify-center text-[10px] text-slate-600">
                                @if(strtoupper($ch->payment_method) === 'CASH')
                                    <i class="fas fa-money-bill text-emerald-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'BKASH')
                                    <i class="fas fa-mobile-screen text-pink-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'NAGAD')
                                    <i class="fas fa-mobile-screen text-orange-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'BANK')
                                    <i class="fas fa-building-columns text-blue-600"></i>
                                @else
                                    <i class="fas fa-credit-card text-slate-600"></i>
                                @endif
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 uppercase block">{{ $ch->payment_method }}</span>
                                <span class="text-[10px] text-slate-500">{{ $ch->count }} Transactions</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-emerald-700">@currency($ch->total)</span>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No collection logs in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 6. MONTH DETAILS POPUP MODAL (Natural Smart Modal) --}}
    <div x-show="selectedMonthModal !== null" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="selectedMonthModal = null" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Monthly Revenue Audit</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="selectedMonthModal?.month_label"></p>
                    </div>
                </div>
                <button type="button" @click="selectedMonthModal = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs" x-show="selectedMonthModal">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Revenue</span>
                        <span class="font-bold font-mono text-emerald-700 text-sm mt-0.5 block" x-text="formatCurrency(selectedMonthModal?.total_revenue)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Net Operating Profit</span>
                        <span class="font-bold font-mono text-cyan-800 text-sm mt-0.5 block" x-text="formatCurrency(selectedMonthModal?.net_profit)"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Retail Subscriber Collections:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="formatCurrency(selectedMonthModal?.retail_revenue)"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Sub-ISP Wholesale Billing:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="formatCurrency(selectedMonthModal?.wholesale_revenue)"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5">
                        <span class="text-slate-500">Direct / Other Income:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="formatCurrency(selectedMonthModal?.direct_revenue)"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs py-0.5 border-t border-slate-200 pt-1">
                        <span class="text-rose-600 font-semibold">Operating OPEX Expenses:</span>
                        <span class="font-mono font-bold text-rose-700" x-text="formatCurrency(selectedMonthModal?.total_expenses)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Profit Margin</span>
                        <span class="font-mono font-bold text-cyan-800 text-xs block" x-text="selectedMonthModal?.profit_margin_pct + '%'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Active Users</span>
                        <span class="font-mono font-bold text-slate-800 text-xs block" x-text="selectedMonthModal?.active_subscribers"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Monthly ARPU</span>
                        <span class="font-mono font-bold text-slate-800 text-xs block" x-text="formatCurrency(selectedMonthModal?.arpu)"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="selectedMonthModal = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close Audit
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function revenueReportManager() {
    return {
        selectedPreset: '{{ $preset }}',
        dateFrom: '{{ request("date_from", request("start_date", $from->toDateString())) }}',
        dateTo: '{{ request("date_to", request("end_date", $to->toDateString())) }}',
        selectedMonthModal: null,

        handlePeriodChange(val) {
            const now = new Date();
            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (val === 'today') {
                this.dateFrom = formatDate(now);
                this.dateTo = formatDate(now);
            } else if (val === 'yesterday') {
                const y = new Date(now);
                y.setDate(y.getDate() - 1);
                this.dateFrom = formatDate(y);
                this.dateTo = formatDate(y);
            } else if (val === 'this_week') {
                const day = now.getDay();
                const diffToSat = (day + 1) % 7; 
                const start = new Date(now);
                start.setDate(start.getDate() - diffToSat);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(now);
            } else if (val === 'this_month') {
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'last_month') {
                const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const end = new Date(now.getFullYear(), now.getMonth(), 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'this_quarter') {
                const qMonth = Math.floor(now.getMonth() / 3) * 3;
                const start = new Date(now.getFullYear(), qMonth, 1);
                const end = new Date(now.getFullYear(), qMonth + 3, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'this_year') {
                const start = new Date(now.getFullYear(), 0, 1);
                const end = new Date(now.getFullYear(), 11, 31);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'last_12_months') {
                const start = new Date(now.getFullYear() - 1, now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'all') {
                this.dateFrom = '2020-01-01';
                this.dateTo = formatDate(now);
            }
        },

        openMonthModal(row) {
            this.selectedMonthModal = row;
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
