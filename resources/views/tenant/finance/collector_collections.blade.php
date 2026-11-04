@extends('tenant.layouts.app')

@section('title', 'Collector Collections - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printableReportArea, #printableReportArea * {
            visibility: visible;
        }
        #printableReportArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 15px;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="collectorCollectionsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">
                Collector Collection Summary
            </h1>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fas fa-calendar-alt text-cyan-600 text-[9px]"></i>
                <span>{{ $periodLabel }}</span>
            </span>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            {{-- Print Summary Statement --}}
            <button type="button" 
                    @click="window.print()" 
                    class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-3 rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Print Summary Statement">
                <i class="fas fa-print text-cyan-600 text-xs"></i>
                <span class="hidden sm:inline">Print</span>
            </button>

            {{-- Export CSV Stream --}}
            <a href="{{ route('tenant.finance.collector-collections.export', request()->query()) }}" 
               class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3.5 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer"
               title="Export Summary CSV">
                <i class="fas fa-file-csv text-xs"></i>
                <span class="hidden sm:inline">Export</span> CSV
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div id="collectorKpiStrip" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Collected in Scope --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">@currency($filteredTotalAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 2: Today's Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Today's Total</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">@currency($todayCollections)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        {{-- Card 3: This Month Total --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">This Month</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">@currency($thisMonthCollections)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>

        {{-- Card 4: Total Receipts Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Receipts</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">{{ number_format($filteredTotalCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 5: Active Collectors --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Collectors</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">{{ number_format($activeCollectorsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
        </div>

        {{-- Card 6: Cash Collected --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Cash Collection</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">@currency($filteredCashAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Architecture & Uniform h-[34px] - AGENTS.md Rule 2.C) --}}
    @php
        $activeMoreCount = collect([
            $collectorFilter !== 'all' ? $collectorFilter : null,
            !empty($dateFrom) ? $dateFrom : null,
            !empty($dateTo) ? $dateTo : null,
            ($period === 'monthly' && $selectedMonth !== \Carbon\Carbon::now()->format('Y-m')) ? $selectedMonth : null,
            ($period === 'yearly' && $selectedYear !== \Carbon\Carbon::now()->format('Y')) ? $selectedYear : null,
        ])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('tenant.finance.collector-collections') }}" id="collectorFilterForm" @submit.prevent="submitSearchNow()" class="space-y-2.5">
            
            {{-- Tier 1: Primary Filter Bar (Strictly 1 Single Row on md+ screens - AGENTS.md Rule 2.C) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                
                {{-- Intelligent Live Search Box (Client-Side & Server Filter) --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-spinner fa-spin absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-cyan-600 pointer-events-none"
                       x-show="isSearching" x-cloak></i>
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"
                       x-show="!isSearching"></i>
                    <input type="text" 
                           name="search" 
                           id="collectorSearchInput"
                           x-model="searchQuery"
                           @input="handleLiveSearch($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="Search Collector, Partner, Phone..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400 font-medium"
                           autocomplete="off">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="clearLiveSearch()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-xs transition cursor-pointer"
                            title="Clear Search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Primary Dropdown 1: Period Selection (Daily / Monthly / Yearly / All) --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="period" 
                            id="periodSelect"
                            x-model="periodType"
                            @change="$nextTick(() => { 
                                if (periodType === 'custom' || periodType === 'daily') { 
                                    showMoreFilters = true; 
                                } else { 
                                    $el.closest('form').submit(); 
                                } 
                            })"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-semibold text-slate-800 cursor-pointer">
                        <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                        <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                        <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                    </select>
                </div>

                {{-- Primary Dropdown 2: Partner / Reseller Scope --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="partner_id" 
                            id="partnerFilterSelect"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">All Partners / Scope</option>
                        <option value="direct" {{ $partnerFilter === 'direct' ? 'selected' : '' }}>🏢 Direct HQ</option>
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ (string)$partnerFilter === (string)$reseller->id ? 'selected' : '' }}>
                                🤝 {{ $reseller->name }} ({{ $reseller->code ?: 'Partner' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="15" {{ request('per_page', $perPage) == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ request('per_page', $perPage) == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ request('per_page', $perPage) == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="Toggle Specific Dates, Month & Collector Filters">
                    <i class="fas fa-sliders-h text-[10px]"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-cyan-600 text-white leading-none">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Sequence (Strict AGENTS.md Rule 2.C - Far Right Aligned on md+) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.collector-collections') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer (AGENTS.md Rule 2.C) --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Date From Picker (For Daily / Custom) --}}
                <div x-show="periodType === 'daily' || periodType === 'custom'" x-cloak class="w-full sm:w-40 flex-shrink-0">
                    <input type="date" 
                           name="date_from" 
                           value="{{ $dateFrom }}"
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500"
                           title="Date From">
                </div>

                {{-- Date To Picker (For Custom Range) --}}
                <div x-show="periodType === 'custom'" x-cloak class="w-full sm:w-40 flex-shrink-0">
                    <input type="date" 
                           name="date_to" 
                           value="{{ $dateTo }}"
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500"
                           title="Date To">
                </div>

                {{-- Month Selector (For Monthly) --}}
                <div x-show="periodType === 'monthly'" x-cloak class="w-full sm:w-40 flex-shrink-0">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth }}"
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500"
                           title="Select Billing Month">
                </div>

                {{-- Year Selector (For Yearly) --}}
                <div x-show="periodType === 'yearly'" x-cloak class="w-full sm:w-36 flex-shrink-0">
                    <select name="year" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        @for($y = (int)date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Collector Scoping --}}
                <div class="w-full sm:w-48 flex-shrink-0">
                    <select name="collector_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">All Collectors</option>
                        <option value="direct" {{ $collectorFilter === 'direct' ? 'selected' : '' }}>Direct / System</option>
                        @foreach($allCollectors as $collector)
                            <option value="{{ $collector->id }}" {{ (string)$collectorFilter === (string)$collector->id ? 'selected' : '' }}>
                                {{ $collector->name }} ({{ $collector->phone ?: 'Staff' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System - AGENTS.md Rule 2.D) --}}
    <div id="printableReportArea" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-left font-semibold">Collector Name</th>
                        <th class="text-left font-semibold">Partner / Scope</th>
                        <th class="text-left font-semibold">Billing Period</th>
                        <th class="text-center font-semibold">Total Receipts</th>
                        <th class="text-right font-semibold">Total Collected</th>
                        <th class="w-28 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="searchQuery && totalMatchingRows === 0" x-cloak class="no-record-row">
                        <td colspan="7" class="text-center py-6 text-slate-500 bg-slate-50/50">
                            <i class="fas fa-spinner fa-spin text-cyan-600 mr-1.5" x-show="isSearching"></i>
                            <i class="fas fa-search text-slate-400 mr-1.5" x-show="!isSearching"></i>
                            <span class="text-xs font-medium">No matching collectors found for this filter scope.</span>
                        </td>
                    </tr>
                    @forelse($rows as $index => $row)
                        @php
                            $rowNum = $rows->firstItem() ? ($rows->firstItem() + $index) : ($index + 1);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors" data-search-text="{{ strtolower($row->collector_name . ' ' . $row->collector_phone . ' ' . $row->partner_name) }}">
                            {{-- 1. Index --}}
                            <td class="w-10 text-center font-mono text-xs text-slate-500">
                                {{ $rowNum }}
                            </td>

                            {{-- 2. Collector Name --}}
                            <td class="font-medium text-slate-800 text-xs whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <span class="font-semibold text-slate-900">{{ $row->collector_name }}</span>
                                    @if(!empty($row->collector_phone))
                                        <span class="text-[10px] text-slate-400 font-mono">({{ $row->collector_phone }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. Partner --}}
                            <td class="whitespace-nowrap text-xs font-mono">
                                @if($row->reseller_id)
                                    <span class="inline-flex items-center gap-1 text-cyan-800 font-semibold" title="{{ $row->partner_name }}">
                                        <i class="fas fa-handshake text-cyan-600 text-[10px]"></i>
                                        <span>{{ $row->partner_code ?: $row->partner_name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-medium" title="Direct Retail HQ">
                                        <i class="fas fa-building text-slate-400 text-[10px]"></i>
                                        <span>Direct HQ</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 4. Period / Month --}}
                            <td class="whitespace-nowrap text-xs font-medium text-slate-700">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200/80 font-mono text-[11px]">
                                    <i class="fas fa-calendar-day text-slate-400 text-[9px]"></i>
                                    <span>{{ $periodLabel }}</span>
                                </span>
                            </td>

                            {{-- 5. Total Count --}}
                            <td class="text-center whitespace-nowrap font-mono font-bold text-xs text-slate-800">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 border border-slate-200/90">
                                    {{ number_format($row->total_count) }}
                                </span>
                            </td>

                            {{-- 6. Total Amount --}}
                            <td class="text-right whitespace-nowrap font-mono font-bold text-xs text-emerald-600">
                                @currency($row->total_amount)
                            </td>

                            {{-- 7. Action: View Details Full Page --}}
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.finance.collector-collections.show', array_merge(['collector' => $row->collector_id], request()->query(), ['reseller_id' => $row->reseller_id ?: 0])) }}" 
                                   class="h-[28px] px-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer"
                                   title="View Itemized Receipts List for this Collector">
                                    <i class="fas fa-list-check text-[10px]"></i>
                                    <span>View Details</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="fas fa-inbox text-2xl mb-1.5 block text-slate-300"></i>
                                <span class="font-medium text-slate-600 text-xs">No collections found for this selected period and filters.</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Try changing the period or clearing search filters to see all historical records.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($rows->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/60 no-print">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function collectorCollectionsManager() {
    return {
        showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }},
        searchQuery: @json($search ?? ''),
        periodType: '{{ $period }}',
        searchDebounceTimer: null,
        totalMatchingRows: null,
        isSearching: false,

        handleLiveSearch(query) {
            this.searchQuery = query;
            const q = query.trim().toLowerCase();

            // Client-side instant filter on table rows
            const rows = document.querySelectorAll('tbody tr[data-search-text]');
            let matched = 0;

            rows.forEach(row => {
                const text = row.getAttribute('data-search-text') || '';
                if (!q || text.includes(q)) {
                    row.style.display = '';
                    matched++;
                } else {
                    row.style.display = 'none';
                }
            });

            this.totalMatchingRows = matched;

            // Debounced server-side submit for pagination accuracy
            clearTimeout(this.searchDebounceTimer);
            this.isSearching = true;
            this.searchDebounceTimer = setTimeout(() => {
                this.isSearching = false;
                if (q.length === 0 || q.length >= 2) {
                    const form = document.getElementById('collectorFilterForm');
                    if (form) form.submit();
                }
            }, 600);
        },

        submitSearchNow() {
            clearTimeout(this.searchDebounceTimer);
            this.isSearching = false;
            const form = document.getElementById('collectorFilterForm');
            if (form) form.submit();
        },

        clearLiveSearch() {
            this.searchQuery = '';
            const input = document.getElementById('collectorSearchInput');
            if (input) input.value = '';
            
            const rows = document.querySelectorAll('tbody tr[data-search-text]');
            rows.forEach(row => row.style.display = '');
            this.totalMatchingRows = null;

            this.$nextTick(() => {
                const form = document.getElementById('collectorFilterForm');
                if (form) form.submit();
            });
        }
    };
}
</script>
@endpush

