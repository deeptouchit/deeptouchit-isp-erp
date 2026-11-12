@extends('tenant.layouts.app')

@section('title', __('Collection & Due Report - :company', ['company' => ($tenant->company_name ?? $tenant->name)]))

@push('styles')
<style>
    .progress-bar-fill {
        transition: width 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="collectionReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Collection & Due Breakdown Analytics') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Quick Filter Indicator Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fas fa-calendar-days text-slate-500 text-[10px]"></i>
                <span>{{ $from->translatedFormat('d M Y') }} - {{ $to->translatedFormat('d M Y') }}</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.collection.print', request()->all()) }}" 
               target="_blank" 
               class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.collection.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Billed Amount --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Billed') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    @currency($totalBilled)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 2: Total Collected (Received) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Collected') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($totalCollected)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        {{-- Card 3: Total Outstanding Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Outstanding Due') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">
                    @currency($totalDue)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: Collection Efficiency % --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Recovery Rate') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ $collectionEfficiency }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>

        {{-- Card 5: Due Subscribers Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    {{ number_format($dueSubscribersCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-slash"></i>
            </div>
        </div>

        {{-- Card 6: Discounts / Waivers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Discounts') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($totalDiscount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-tags"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    @php
        $activeMoreCount = 0;
        if (!empty($collectorId) && $collectorId !== 'all') $activeMoreCount++;
        if (!empty($packageId) && $packageId !== 'all') $activeMoreCount++;
        if (!empty($statusFilter) && $statusFilter !== 'all') $activeMoreCount++;
        if ($preset === 'custom' || (!empty(request('date_from')) || !empty(request('date_to')))) $activeMoreCount++;
        if (!empty(request('per_page')) && request('per_page') != 20) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;
    @endphp

    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
             selectedPreset: '{{ $preset }}',
             dateFrom: '{{ request('date_from', request('start_date', $from->toDateString())) }}',
             dateTo: '{{ request('date_to', request('end_date', $to->toDateString())) }}',
             searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.reports.collection') }}" class="space-y-2.5">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- 1st Tier: Primary Single Row (Uniform h-[34px]) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <i class="fas fa-magnifying-glass text-xs" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    </div>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="{{ __('Search customer, ID, phone, username...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer"
                            title="{{ __('Clear search') }}">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                {{-- Period Preset Selector --}}
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="preset" 
                            x-model="selectedPreset" 
                            @change="handlePeriodChange($event.target.value); $nextTick(() => $el.closest('form').submit())"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="today" {{ $preset === 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                        <option value="yesterday" {{ $preset === 'yesterday' ? 'selected' : '' }}>{{ __('Yesterday') }}</option>
                        <option value="this_week" {{ $preset === 'this_week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                        <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                        <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>{{ __('Last Month') }}</option>
                        <option value="this_quarter" {{ $preset === 'this_quarter' ? 'selected' : '' }}>{{ __('This Quarter') }}</option>
                        <option value="this_year" {{ $preset === 'this_year' ? 'selected' : '' }}>{{ __('This Year (:year)', ['year' => date('Y')]) }}</option>
                        <option value="last_12_months" {{ $preset === 'last_12_months' ? 'selected' : '' }}>{{ __('Last 12 Months') }}</option>
                        <option value="all" {{ $preset === 'all' ? 'selected' : '' }}>{{ __('All Time') }}</option>
                        <option value="custom" {{ $preset === 'custom' ? 'selected' : '' }}>{{ __('Custom Date Range') }}</option>
                    </select>
                </div>

                {{-- Coverage Zone --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="zone_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all">{{ __('All Coverage Zones') }}</option>
                        @foreach($allZones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Per Page Selector --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                    </select>
                </div>

                {{-- More Filters Accordion Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center font-mono">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas fa-chevron-down text-[9px] transition-transform duration-200" :class="showMoreFilters ? 'rotate-180 text-cyan-600' : 'text-slate-400'"></i>
                </button>

                {{-- Strict Action Buttons Sequence: [Filter] FIRST, [Reset] SECOND --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.reports.collection', ['tab' => $activeTab]) }}" 
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
                 class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2">

                {{-- Collection Stream Scope --}}
                <div>
                    <select name="stream" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all" {{ ($stream ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Collection Streams') }}</option>
                        <option value="retail" {{ ($stream ?? 'all') === 'retail' ? 'selected' : '' }}>{{ __('Retail Subscribers') }}</option>
                        <option value="wholesale" {{ ($stream ?? 'all') === 'wholesale' ? 'selected' : '' }}>{{ __('Sub-ISP Wholesale') }}</option>
                        <option value="gateway" {{ ($stream ?? 'all') === 'gateway' ? 'selected' : '' }}>{{ __('Payment Gateways') }}</option>
                        <option value="direct" {{ ($stream ?? 'all') === 'direct' ? 'selected' : '' }}>{{ __('Direct Incomes') }}</option>
                    </select>
                </div>

                {{-- Collector Staff --}}
                <div>
                    <select name="collector_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all">{{ __('All Collectors') }}</option>
                        @foreach($allCollectors as $col)
                            <option value="{{ $col->id }}" {{ (string)$collectorId === (string)$col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Internet Package --}}
                <div>
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all">{{ __('All Packages') }}</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>{{ $pkg->name ?: $pkg->package_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Account Status --}}
                <div>
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs cursor-pointer">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                        <option value="due" {{ $statusFilter === 'due' ? 'selected' : '' }}>🔴 {{ __('With Dues Only') }}</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>🟢 {{ __('Active Subscribers') }}</option>
                        <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>🟡 {{ __('Suspended Accounts') }}</option>
                        <option value="disconnected" {{ $statusFilter === 'disconnected' ? 'selected' : '' }}>⚪ {{ __('Disconnected') }}</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <input type="date" 
                           name="date_from" 
                           x-model="dateFrom" 
                           title="{{ __('From Date') }}"
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs font-mono">
                </div>

                {{-- Date To --}}
                <div>
                    <input type="date" 
                           name="date_to" 
                           x-model="dateTo" 
                           title="{{ __('To Date') }}"
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs font-mono">
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER MULTI-DIMENSIONAL BREAKDOWN TABS & TABLES --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Tabs Header Bar --}}
        <div class="px-3.5 py-2 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-1 overflow-x-auto">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'zones']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'zones' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-location-dot text-[10px]"></i>
                    <span>{{ __('Zones') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'zones' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($zoneMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'collectors']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'collectors' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-user-tie text-[10px]"></i>
                    <span>{{ __('Collectors') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'collectors' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($collectorMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'packages']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'packages' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-cubes text-[10px]"></i>
                    <span>{{ __('Packages') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'packages' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($packageMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'resellers']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'resellers' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-network-wired text-[10px]"></i>
                    <span>{{ __('Resellers') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'resellers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($wholesaleMatrix ?? []) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'gateways']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'gateways' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-credit-card text-[10px]"></i>
                    <span>{{ __('Gateways') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'gateways' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($gatewayMatrix ?? []) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'customers']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'customers' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-users text-[10px]"></i>
                    <span>{{ __('Subscribers Ledger') }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'customers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ $customerRecords->total() }}</span>
                </a>
            </div>

            @if($activeTab === 'customers' && !empty($search))
                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                    <span>{!! __('Filtering by: :term', ['term' => '<strong class="text-cyan-700">"' . e($search) . '"</strong>']) !!}</span>
                    <a href="{{ route('tenant.reports.collection', array_merge(request()->except('search'), ['tab' => 'customers'])) }}" class="text-rose-600 hover:underline text-[11px] font-semibold">
                        <i class="fas fa-times-circle"></i> {{ __('Clear') }}
                    </a>
                </div>
            @endif
        </div>

        {{-- TAB CONTENT 1: ZONE BREAKDOWN --}}
        @if($activeTab === 'zones')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">{{ __('Zone Name') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Subscribers') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Active') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Total Billed') }}</th>
                            <th class="w-28 text-center font-semibold text-emerald-700">{{ __('Collected') }}</th>
                            <th class="w-28 text-center font-semibold text-rose-700">{{ __('Total Due') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Recovery %') }}</th>
                            <th class="no-sort w-16 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zoneMatrix as $idx => $z)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-left font-bold text-slate-800">{{ $z['name'] }}</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $z['subscribers_count'] }}</td>
                                <td class="text-center font-mono font-semibold text-emerald-700">{{ $z['active_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($z['total_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($z['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($z['total_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $z['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($z['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $z['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'zone_id' => $z['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="{{ __('View Zone Customers') }}">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-location-dot text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Zone Data') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No zone breakdown data recorded in this workspace.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'zones']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 2: COLLECTOR PERFORMANCE --}}
        @if($activeTab === 'collectors')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">{{ __('Collector Staff') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Designation') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Assigned Users') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Target Billed') }}</th>
                            <th class="w-28 text-center font-semibold text-emerald-700">{{ __('Collected') }}</th>
                            <th class="w-28 text-center font-semibold text-rose-700">{{ __('Pending Due') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Recovery %') }}</th>
                            <th class="no-sort w-16 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collectorMatrix as $idx => $col)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-left font-bold text-slate-800">{{ $col['name'] }}</td>
                                <td class="text-center text-slate-600 text-xs">{{ $col['role'] }}</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $col['assigned_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($col['target_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($col['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($col['outstanding_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $col['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($col['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $col['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'collector_id' => $col['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="{{ __('View Assigned Customers') }}">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-user-tie text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Collectors Found') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No billing collector staff found in this workspace.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'collectors']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 3: PACKAGE BREAKDOWN --}}
        @if($activeTab === 'packages')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">{{ __('Package Name') }}</th>
                            <th class="w-20 text-center font-semibold">{{ __('Speed') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Price') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Subscribers') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Monthly Bill') }}</th>
                            <th class="w-28 text-center font-semibold text-emerald-700">{{ __('Collected') }}</th>
                            <th class="w-28 text-center font-semibold text-rose-700">{{ __('Total Due') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Recovery %') }}</th>
                            <th class="no-sort w-16 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packageMatrix as $idx => $pkg)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-left font-bold text-slate-800">{{ $pkg['name'] }}</td>
                                <td class="text-center font-mono text-cyan-700 font-semibold">{{ $pkg['speed'] }}</td>
                                <td class="text-right font-mono text-slate-700">@currency($pkg['price'])</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $pkg['subscribers_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($pkg['total_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($pkg['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($pkg['total_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $pkg['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($pkg['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $pkg['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'package_id' => $pkg['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="{{ __('View Package Subscribers') }}">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-cubes text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Packages Found') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No package analytics found in this workspace.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'packages']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 4: SUB-ISP WHOLESALE RESELLERS --}}
        @if($activeTab === 'resellers')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">{{ __('Reseller / Sub-ISP') }}</th>
                            <th class="w-20 text-center font-semibold">{{ __('Type') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Wallet') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Period Billed') }}</th>
                            <th class="w-28 text-center font-semibold text-emerald-700">{{ __('Collected') }}</th>
                            <th class="w-28 text-center font-semibold text-rose-700">{{ __('Total Due') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Recovery %') }}</th>
                            <th class="no-sort w-16 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wholesaleMatrix ?? [] as $idx => $res)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-left font-bold text-slate-800">
                                    <a href="{{ url('admin/resellers/' . $res['id']) }}" class="text-cyan-800 hover:underline">
                                        {{ $res['name'] }}
                                    </a>
                                    <span class="text-[10px] text-slate-400 block font-mono">{{ $res['code'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $res['billing_type'] === 'POSTPAID' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $res['billing_type'] === 'POSTPAID' ? __('Postpaid') : __('Prepaid') }}
                                    </span>
                                </td>
                                <td class="text-right font-mono text-slate-700">@currency($res['wallet_balance'])</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($res['period_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($res['period_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($res['outstanding_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $res['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($res['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $res['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ url('admin/finance/wholesale-billing?search=' . urlencode($res['name'])) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="{{ __('View Wholesale Billing') }}">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-network-wired text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Resellers Found') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No Sub-ISP reseller accounts found in this period.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'resellers']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 5: PAYMENT GATEWAYS BREAKDOWN --}}
        @if($activeTab === 'gateways')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">{{ __('Payment Channel / Gateway') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Category') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Transactions') }}</th>
                            <th class="w-36 text-center font-semibold text-emerald-700">{{ __('Total Inflow') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Share %') }}</th>
                            <th class="no-sort w-16 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gatewayMatrix ?? [] as $idx => $gw)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-[11px] text-slate-600">
                                            @if($gw['method_key'] === 'cash')
                                                <i class="fas fa-money-bill text-emerald-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'bkash'))
                                                <i class="fas fa-mobile-screen text-pink-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'nagad'))
                                                <i class="fas fa-mobile-screen text-orange-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'rocket'))
                                                <i class="fas fa-mobile-screen text-purple-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'ssl') || str_contains($gw['method_key'], 'shurjopay'))
                                                <i class="fas fa-credit-card text-blue-600"></i>
                                            @else
                                                <i class="fas fa-wallet text-slate-600"></i>
                                            @endif
                                        </div>
                                        <span>{{ $gw['channel_name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $gw['is_gateway'] ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $gw['is_gateway'] ? __('DIGITAL PGW') : __('MANUAL / CASH') }}
                                    </span>
                                </td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ number_format($gw['txn_count']) }}</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($gw['total_amount'])</td>
                                <td class="text-center font-mono font-bold text-slate-700">
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-800">
                                        {{ $gw['share_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ url('admin/finance/gateway-transactions?gateway=' . urlencode($gw['method_key'])) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="{{ __('View Transactions') }}">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-credit-card text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Inflow Records') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No payment inflow records logged in this period.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'gateways']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 6: SUBSCRIBERS DUE LEDGER --}}
        @if($activeTab === 'customers')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-24 text-center font-semibold">{{ __('Customer ID') }}</th>
                            <th class="text-center font-semibold">{{ __('Subscriber Name') }}</th>
                            <th class="text-center font-semibold">{{ __('PPPoE User') }}</th>
                            <th class="text-center font-semibold">{{ __('Zone') }}</th>
                            <th class="text-center font-semibold">{{ __('Package') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Monthly Bill') }}</th>
                            <th class="w-24 text-center font-semibold text-rose-700">{{ __('Due Amount') }}</th>
                            <th class="w-24 text-center font-semibold">{{ __('Status') }}</th>
                            <th class="w-28 text-center font-semibold">{{ __('Collector') }}</th>
                            <th class="no-sort w-14 text-center font-semibold">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerRecords as $cust)
                            <tr>
                                <td class="text-center font-mono font-bold text-slate-800">
                                    <a href="{{ route('tenant.customers.show', $cust->id) }}" class="text-cyan-700 hover:underline">
                                        {{ $cust->customer_id ?? ('CUST-' . $cust->id) }}
                                    </a>
                                </td>
                                <td class="text-left font-semibold text-slate-800">{{ $cust->name }}</td>
                                <td class="text-left font-mono text-slate-600">{{ $cust->username }}</td>
                                <td class="text-left text-slate-600">{{ $cust->coverageZone?->name ?? __('Default Area') }}</td>
                                <td class="text-left text-slate-600">{{ $cust->package?->name ?? $cust->package_name }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($cust->monthly_bill)</td>
                                <td class="text-right font-mono font-bold {{ $cust->due_amount > 0 ? 'text-rose-700 bg-rose-50/30' : 'text-slate-400' }}">
                                    @currency($cust->due_amount)
                                </td>
                                <td class="text-center">
                                    @if(in_array($cust->status, ['active', 'online']))
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">{{ __('Active') }}</span>
                                    @elseif($cust->status === 'due')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase">{{ __('Due') }}</span>
                                    @elseif($cust->status === 'suspended')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase">{{ __('Suspended') }}</span>
                                    @elseif($cust->status === 'disconnected')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">{{ __('Disconnected') }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">{{ __(ucfirst((string) $cust->status)) }}</span>
                                    @endif
                                </td>
                                <td class="text-left text-slate-600 truncate">{{ $cust->collector?->name ?? __('Unassigned') }}</td>
                                <td class="text-center">
                                    <button type="button" 
                                            @click="toggleMenu({{ json_encode($cust) }}, $event)"
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 inline-flex items-center justify-center transition cursor-pointer">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                            <i class="fas fa-users-slash text-slate-400"></i>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-xs font-semibold text-slate-700">{{ __('No Subscribers Found') }}</h3>
                                            <p class="text-[11px] text-slate-400 font-normal">{{ __('No subscriber records found matching current filters.') }}</p>
                                        </div>
                                        <div class="pt-1">
                                            <a href="{{ route('tenant.reports.collection', ['tab' => 'customers']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>{{ __('Reset Filters') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customerRecords->hasPages())
                <div class="px-3.5 py-2.5 border-t border-slate-200 bg-slate-50/50">
                    {{ $customerRecords->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- 5. EXECUTIVE REVENUE & DUE ANALYTICS GRIDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: Aging Dues Analysis --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-hourglass-half text-amber-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Aging Dues Analysis') }}</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">{{ __('Aging Risk') }}</span>
            </div>

            <div class="space-y-2.5">
                @foreach($agingAnalysis as $bracket)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">{{ $bracket['label'] }}</span>
                            <span class="font-mono font-bold text-slate-900">@currency($bracket['amount'])</span>
                        </div>
                        <div class="flex items-center justify-between text-[10.5px] text-slate-500">
                            <span>{{ __(':count Subscribers', ['count' => $bracket['count']]) }}</span>
                            <span class="font-mono font-semibold">{{ $bracket['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-{{ $bracket['color'] }}-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ min(100, $bracket['percentage']) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 2: Top 5 Highest Due Defaulters --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-rose-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Top Due Defaulters') }}</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-rose-600">{{ __('Urgent Followup') }}</span>
            </div>

            <div class="space-y-2">
                @forelse($topDefaulters as $def)
                    <div class="p-2 bg-rose-50/40 rounded-lg border border-rose-200/60 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <a href="{{ route('tenant.customers.show', $def->id) }}" class="font-bold text-slate-800 hover:text-cyan-700 block truncate">
                                {{ $def->name }}
                            </a>
                            <span class="text-[10.5px] text-slate-500 block truncate font-mono">
                                {{ $def->phone }} &bull; {{ $def->coverageZone?->name ?? __('Default Area') }}
                            </span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono font-bold text-rose-700 block">@currency($def->due_amount)</span>
                            <button type="button" 
                                    @click="sendPaymentReminder({{ json_encode($def) }})"
                                    class="text-[10px] text-cyan-700 hover:underline font-semibold cursor-pointer">
                                <i class="fas fa-bell text-[9px]"></i> {{ __('Remind') }}
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">{{ __('No subscribers with overdue balances.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Card 3: Collection Inflow Channels --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-wallet text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Collection Inflow Channels') }}</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">{{ __('Total Yield') }}</span>
            </div>

            <div class="space-y-2">
                @forelse($paymentChannels as $ch)
                    @php
                        $methodSlug = strtolower((string) $ch->payment_method);
                        $methodLabel = match ($methodSlug) {
                            'cash' => __('Cash'),
                            'bkash' => __('bKash'),
                            'bkash_merchant' => __('bKash Merchant'),
                            'nagad' => __('Nagad'),
                            'nagad_merchant' => __('Nagad Merchant'),
                            'rocket' => __('Rocket'),
                            'bank' => __('Bank'),
                            'sslcommerz' => __('SSLCommerz'),
                            'shurjopay' => __('ShurjoPay'),
                            'upay' => __('Upay'),
                            'aamarpay' => __('AamarPay'),
                            'stripe' => __('Stripe'),
                            'online', 'online_pgw' => __('Online Payment'),
                            'pos' => __('POS'),
                            default => __(ucwords(str_replace('_', ' ', (string) ($ch->payment_method ?: 'Cash')))),
                        };
                    @endphp
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
                                <span class="font-bold text-slate-800 uppercase block">{{ $methodLabel }}</span>
                                <span class="text-[10px] text-slate-500">{{ __(':count Payments', ['count' => $ch->count]) }}</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-emerald-700">@currency($ch->total)</span>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">{{ __('No collection logs in this period.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 6. GLOBAL FLOATING 3-DOT ACTION MENU --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        <div class="py-1">
            <a :href="activeMenu ? '{{ url('admin/customers') }}/' + activeMenu.id : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-user-circle text-cyan-600 text-[11px] w-4"></i>
                <span>{{ __('View Profile') }}</span>
            </a>
            <a :href="activeMenu ? '{{ url('admin/finance/invoices') }}?search=' + activeMenu.username : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-file-invoice text-indigo-600 text-[11px] w-4"></i>
                <span>{{ __('View Invoices') }}</span>
            </a>
            <a :href="activeMenu ? '{{ url('admin/finance/payments') }}?search=' + activeMenu.username : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-receipt text-emerald-600 text-[11px] w-4"></i>
                <span>{{ __('Payment History') }}</span>
            </a>
        </div>

        <div class="py-1">
            <button type="button" 
                    @click="sendPaymentReminder(activeMenu); activeMenu = null;"
                    class="w-full text-left flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                <i class="fas fa-paper-plane text-amber-600 text-[11px] w-4"></i>
                <span>{{ __('Send SMS Reminder') }}</span>
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const collectionReportI18n = {
    sendPaymentReminderTitle: @json(__('Send Payment Reminder?')),
    sendReminderHtml: @json(__('Send an instant SMS & WhatsApp billing reminder to subscriber :name (:phone)?')),
    noPhone: @json(__('No phone')),
    outstandingBalance: @json(__('Outstanding Balance')),
    sendReminderBtn: @json(__('Send Reminder')),
    cancelBtn: @json(__('Cancel')),
    reminderQueued: @json(__('Payment reminder queued for :name')),
};

function collectionReportManager() {
    return {
        selectedPreset: '{{ $preset }}',
        dateFrom: '{{ request("date_from", request("start_date", $from->toDateString())) }}',
        dateTo: '{{ request("date_to", request("end_date", $to->toDateString())) }}',
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },

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

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 160;
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

        sendPaymentReminder(cust) {
            if (!cust) return;
            const sym = @json($currencySymbol ?? '৳');
            const dueText = sym + ' ' + (parseFloat(cust.due_amount) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
            const phoneText = cust.phone || collectionReportI18n.noPhone;
            const msgHtml = collectionReportI18n.sendReminderHtml
                .replace(':name', `<strong>${cust.name}</strong>`)
                .replace(':phone', phoneText);
            
            Swal.fire({
                title: collectionReportI18n.sendPaymentReminderTitle,
                html: `<div class="text-left text-xs text-slate-600 space-y-2">
                        <p>${msgHtml}</p>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">${collectionReportI18n.outstandingBalance}</span>
                            <span class="font-mono font-bold text-rose-700 text-sm block">${dueText}</span>
                        </div>
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-paper-plane mr-1"></i> ' + collectionReportI18n.sendReminderBtn,
                cancelButtonText: collectionReportI18n.cancelBtn
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: collectionReportI18n.reminderQueued.replace(':name', cust.name),
                        showConfirmButton: false,
                        timer: 2500
                    });
                }
            });
        }
    };
}
</script>
@endpush
