@extends('tenant.layouts.app')

@section('title', __('Billing Catch-Up & Uninvoiced Queue') . ' - ' . ($tenant->company_name ?? $tenant->name ?? __('ISP Management')))

@section('content')
<div class="space-y-3" x-data="billingCatchUpManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.finance.invoices') }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs cursor-pointer"
               title="{{ __('Back to Customer Invoices') }}">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Billing Catch-Up & Uninvoiced Queue') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            {{-- Quick Rescan Button --}}
            <button type="button" 
                    @click="window.location.reload()" 
                    class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="{{ __('Rescan & Refresh Subscriber Queue') }}">
                <i class="fas fa-arrows-rotate text-cyan-600 text-[11px]"></i>
                <span>{{ __('Refresh Scan') }}</span>
            </button>

            {{-- Batch Generate Selected Invoices --}}
            <button type="button" 
                    x-show="selectedIds.length > 0" 
                    x-cloak
                    @click="confirmBatchGenerate('selected')"
                    :disabled="isGenerating"
                    class="h-[34px] py-0 px-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas" :class="isGenerating ? 'fa-spinner fa-spin' : 'fa-check-double'"></i>
                <span x-text="isGenerating ? i18n.generating : i18n.generateSelected.replace(':count', selectedIds.length)"></span>
            </button>

            {{-- Batch Generate Targeted Scope Invoices --}}
            @if($filteredTargetCount > 0)
            <button type="button" 
                    @click="confirmBatchGenerate('filtered')"
                    :disabled="isGenerating"
                    class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer"
                    title="{{ __('Generate Invoices for Current Target Scope: :scope', ['scope' => $targetDateLabel]) }}">
                <i class="fas" :class="isGenerating ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                <span x-text="isGenerating ? i18n.processing : targetGenerateBtnText"></span>
            </button>
            @endif
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div id="catchupKpiStrip" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Uninvoiced Queue --}}
        <div @click="filterByTargetDate('all')" 
             class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-slate-300 shadow-xs flex items-center justify-between cursor-pointer transition {{ $targetDateType === 'all' ? 'ring-2 ring-cyan-500/20 bg-cyan-50/20' : '' }}"
             title="{{ __('Click to view all uninvoiced subscribers') }}">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Queue') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">{{ number_format($totalUninvoicedCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

        {{-- Card 2: Expires Today (Direct Click Filter) --}}
        <div @click="filterByTargetDate('today')" 
             class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-amber-400 shadow-xs flex items-center justify-between cursor-pointer transition {{ $targetDateType === 'today' ? 'ring-2 ring-amber-500 bg-amber-50/40' : '' }}"
             title="{{ __('Click to filter subscribers expiring today') }}">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-bold uppercase tracking-wider text-amber-700 block truncate">{{ __('Expires Today') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">{{ number_format($todayExpiringCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 3: Already Overdue --}}
        <div @click="filterByTargetDate('overdue')" 
             class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-rose-400 shadow-xs flex items-center justify-between cursor-pointer transition {{ $targetDateType === 'overdue' ? 'ring-2 ring-rose-500 bg-rose-50/40' : '' }}"
             title="{{ __('Click to filter past overdue subscribers') }}">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-bold uppercase tracking-wider text-rose-700 block truncate">{{ __('Already Overdue') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($alreadyOverdueCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: Upcoming 1-3 Days --}}
        <div @click="filterByTargetDate('next_3_days')" 
             class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-cyan-400 shadow-xs flex items-center justify-between cursor-pointer transition {{ $targetDateType === 'next_3_days' ? 'ring-2 ring-cyan-500 bg-cyan-50/40' : '' }}"
             title="{{ __('Click to filter subscribers expiring in next 1 to 3 days') }}">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-cyan-800 block truncate">{{ __('Upcoming (1-3d)') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">{{ number_format($upcoming3DaysCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        {{-- Card 5: Unbilled Volume --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Unbilled Volume') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($totalUnbilledAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        {{-- Card 6: Engine Health Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cron Health') }}</span>
                <span class="text-[12px] font-bold leading-tight block truncate {{ $isCronHealthy ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $isCronHealthy ? '🟢 ' . __('Healthy') : '⚠️ ' . __('Attention') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $isCronHealthy ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-amber-200 bg-amber-50 text-amber-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-heart-pulse"></i>
            </div>
        </div>
    </div>

    {{-- PARTNER-WISE BREAKDOWN STRIP --}}
    @if(count($partnerBreakdownQuery) > 0)
    <div class="bg-white px-3 py-2 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fas fa-layer-group text-cyan-600"></i>
                <span>{{ __('Partner-Wise Uninvoiced Breakdown') }}</span>
            </span>
            @if($partnerFilter !== 'all')
                <button type="button" @click="filterByPartner('all')" class="text-[10px] text-cyan-600 hover:text-cyan-800 font-medium">
                    {{ __('Show All Partners') }}
                </button>
            @endif
        </div>
        <div class="flex items-center gap-2 overflow-x-auto pb-0.5 text-xs">
            @foreach($partnerBreakdownQuery as $pb)
                @php
                    $isDirect = empty($pb->reseller_id);
                    $partnerName = $isDirect ? __('Direct (HQ)') : ($pb->reseller?->name ?: __('Partner #:id', ['id' => $pb->reseller_id]));
                    $partnerCode = $isDirect ? 'DIRECT' : ($pb->reseller?->code ?: 'RES');
                    $filterVal = $isDirect ? 'direct' : (string)$pb->reseller_id;
                    $isSelected = ((string)$partnerFilter === $filterVal);
                @endphp
                <div class="px-2.5 py-1 rounded-lg border transition inline-flex items-center gap-2 flex-shrink-0 shadow-2xs {{ $isSelected ? 'border-cyan-400 bg-cyan-50/70 ring-1 ring-cyan-300' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100' }}">
                    <button type="button" 
                            @click="filterByPartner('{{ $filterVal }}')" 
                            class="flex items-center gap-1.5 cursor-pointer text-left">
                        <i class="fas {{ $isDirect ? 'fa-building text-slate-500' : 'fa-handshake text-purple-600' }} text-[10px]"></i>
                        <span class="font-semibold text-slate-800 text-[11px]">{{ $partnerName }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-white text-slate-700 border border-slate-200">
                            {{ $pb->count }}
                        </span>
                        <span class="font-mono text-cyan-800 font-bold text-[10.5px]">@currency($pb->amount)</span>
                    </button>
                    <button type="button" 
                            @click="confirmBatchGenerate('partner', '{{ $filterVal }}', {{ Js::from($partnerName) }}, {{ $pb->count }})"
                            title="{{ __('Generate All Invoices for :partner', ['partner' => $partnerName]) }}"
                            class="w-5 h-5 rounded bg-cyan-600 hover:bg-cyan-700 text-white flex items-center justify-center text-[9px] transition cursor-pointer">
                        <i class="fas fa-bolt"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Architecture & Uniform h-[34px] - AGENTS.md Rule 2.C) --}}
    @php
        $activeMoreCount = collect([
            $overdueFilter !== 'all' ? $overdueFilter : null, 
            $packageFilter !== 'all' ? $packageFilter : null,
            $zoneFilter !== 'all' ? $zoneFilter : null,
            ($targetDateType === 'custom' || !empty($customDate)) ? 'custom' : null
        ])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('tenant.finance.billing-catchup') }}" id="catchupFilterForm" @submit.prevent="submitSearchNow()" class="space-y-2.5">
            
            {{-- Tier 1: Primary Filter Bar (Strictly 1 Single Row on md+ screens - AGENTS.md Rule 2.C) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                
                {{-- Intelligent Live Search Box (Instant Client-Side & Debounced Server Search) --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-spinner fa-spin absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-cyan-600 pointer-events-none"
                       x-show="isSearching" x-cloak></i>
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"
                       x-show="!isSearching"></i>
                    <input type="text" 
                           name="search" 
                           id="catchupSearchInput"
                           x-model="searchQuery"
                           @input="handleLiveSearch($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="{{ __('Search Username, Name, Phone, ID...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400 font-medium"
                           autocomplete="off">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="clearLiveSearch()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-xs transition cursor-pointer"
                            title="{{ __('Clear Search') }}">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Primary Dropdown 1: Target Expiry Date Selector --}}
                <div class="w-full md:w-52 flex-shrink-0">
                    <select name="target_date_type" 
                            id="targetDateTypeSelect"
                            x-model="targetDateType"
                            @change="$nextTick(() => { 
                                if (targetDateType === 'custom') { 
                                    showMoreFilters = true; 
                                } else { 
                                    $el.closest('form').submit(); 
                                } 
                            })"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-semibold text-slate-800 cursor-pointer">
                        <option value="all" {{ $targetDateType === 'all' ? 'selected' : '' }}>🌐 {{ __('All Queue (:count)', ['count' => number_format($totalUninvoicedCount)]) }}</option>
                        <option value="today" {{ $targetDateType === 'today' ? 'selected' : '' }}>⚡ {{ __('Expires Today (:count)', ['count' => number_format($todayExpiringCount)]) }}</option>
                        <option value="tomorrow" {{ $targetDateType === 'tomorrow' ? 'selected' : '' }}>⏳ {{ __('Expires Tomorrow') }}</option>
                        <option value="next_3_days" {{ $targetDateType === 'next_3_days' ? 'selected' : '' }}>📅 {{ __('Next 3 Days (:count)', ['count' => number_format($upcoming3DaysCount)]) }}</option>
                        <option value="overdue" {{ $targetDateType === 'overdue' ? 'selected' : '' }}>⚠️ {{ __('Already Overdue (:count)', ['count' => number_format($alreadyOverdueCount)]) }}</option>
                        <option value="custom" {{ $targetDateType === 'custom' ? 'selected' : '' }}>🗓️ {{ __('Pick Specific Date...') }}</option>
                    </select>
                </div>

                {{-- Primary Dropdown 2: Partner Filter (if not reseller) --}}
                @if(!$isResellerUser)
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="partner_id" 
                            id="partnerFilterSelect"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">{{ __('All Partners / Scope') }}</option>
                        <option value="direct" {{ $partnerFilter === 'direct' ? 'selected' : '' }}>🏢 {{ __('Direct (No Partner)') }}</option>
                        @foreach($resellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ (string)$partnerFilter === (string)$reseller->id ? 'selected' : '' }}>
                                🤝 {{ $reseller->name }} ({{ $reseller->code ?: __('Reseller') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="15" {{ request('per_page', $perPage) == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ request('per_page', $perPage) == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ request('per_page', $perPage) == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                        <option value="all" {{ request('per_page', $perPage) === 'all' ? 'selected' : '' }}>{{ __('All Queue') }}</option>
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Zone, Package & Custom Date Filters') }}">
                    <i class="fas fa-sliders-h text-[10px]"></i>
                    <span>{{ __('More Filters') }}</span>
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
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.finance.billing-catchup') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
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
                
                {{-- Custom Date Picker (Visible when custom chosen) --}}
                <div x-show="targetDateType === 'custom'" x-cloak class="w-full sm:w-44 flex-shrink-0">
                    <input type="date" 
                           name="custom_date" 
                           value="{{ $customDate }}"
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2.5 bg-white border border-cyan-400 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden"
                           title="{{ __('Select exact expiry date to target') }}">
                </div>

                {{-- Zone / Area Filter --}}
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="zone_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">{{ __('All Zones / Areas') }}</option>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneFilter === (string)$z->id ? 'selected' : '' }}>
                                {{ $z->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Package Filter --}}
                <div class="w-full sm:w-48 flex-shrink-0">
                    <select name="package_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">{{ __('All Internet Packages') }}</option>
                        @foreach($packages as $p)
                            <option value="{{ $p->id }}" {{ (string)$packageFilter === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} (@currency($p->price))
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Overdue / Expiry Range Secondary Filter --}}
                <div class="w-full sm:w-48 flex-shrink-0">
                    <select name="overdue_range" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">{{ __('All Overdue Ranges') }}</option>
                        <option value="upcoming" {{ $overdueFilter === 'upcoming' ? 'selected' : '' }}>⏳ {{ __('Upcoming (Due in 1-3 Days)') }}</option>
                        <option value="overdue_1_7" {{ $overdueFilter === 'overdue_1_7' ? 'selected' : '' }}>⚠️ {{ __('Overdue (1 to 7 Days)') }}</option>
                        <option value="overdue_8_plus" {{ $overdueFilter === 'overdue_8_plus' ? 'selected' : '' }}>🚨 {{ __('Critical Overdue (8+ Days)') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System - AGENTS.md Rule 2.D) --}}
    <div id="catchupTableCard" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-8 text-center no-sort">
                            <input type="checkbox" 
                                   @change="toggleSelectAll($event.target.checked)" 
                                   :checked="isAllSelected"
                                   class="rounded text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                        </th>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-center font-semibold">{{ __('Customer ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Customer Name') }}</th>
                        <th class="text-center font-semibold">{{ __('Mobile Number') }}</th>
                        <th class="text-center font-semibold">{{ __('Area') }}</th>
                        <th class="text-center font-semibold">{{ __('Partner') }}</th>
                        <th class="text-center font-semibold">{{ __('Package') }}</th>
                        <th class="text-center font-semibold">{{ __('Monthly Bill') }}</th>
                        <th class="text-center font-semibold">{{ __('Expiry Date') }}</th>
                        <th class="text-center font-semibold">{{ __('Cycle Status') }}</th>
                        <th class="w-28 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="searchQuery && totalMatchingRows === 0" x-cloak class="no-record-row">
                        <td colspan="12" class="text-center py-6 text-slate-500 bg-slate-50/50">
                            <i class="fas fa-spinner fa-spin text-cyan-600 mr-1.5" x-show="isSearching"></i>
                            <i class="fas fa-search text-slate-400 mr-1.5" x-show="!isSearching"></i>
                            <span class="text-xs font-medium">{{ __('No matching uninvoiced subscribers found on this page.') }}</span>
                        </td>
                    </tr>
                    @forelse($subscribers as $index => $customer)
                        @php
                            $bill = (float)($customer->monthly_bill > 0 ? $customer->monthly_bill : ($customer->package?->price ?? 0.00));
                            $expiry = $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date) : null;
                            $daysDiff = $expiry ? \Carbon\Carbon::today()->diffInDays($expiry, false) : 0;
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            {{-- Checkbox --}}
                            <td class="text-center whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $customer->id }}" 
                                       @change="toggleSelect({{ $customer->id }})" 
                                       :checked="selectedIds.includes({{ $customer->id }})"
                                       class="rounded text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                            </td>

                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-500 whitespace-nowrap">{{ $subscribers->firstItem() + $index }}</td>

                            {{-- 2. Customer ID --}}
                            <td class="text-center font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                <a href="{{ route('tenant.customers.show', $customer->id) }}" class="hover:underline hover:text-cyan-700">
                                    {{ $customer->customer_id ?: $customer->username }}
                                </a>
                            </td>

                            {{-- 3. Customer Name --}}
                            <td class="text-left whitespace-nowrap font-medium text-slate-900 text-xs">
                                {{ $customer->name ?: $customer->username }}
                            </td>

                            {{-- 4. Mobile Number --}}
                            <td class="text-center whitespace-nowrap font-mono text-xs text-slate-700">
                                {{ $customer->phone ?: '—' }}
                            </td>

                            {{-- 5. Area --}}
                            <td class="text-left whitespace-nowrap font-medium text-slate-700 text-xs">
                                {{ (is_object($customer->zone) ? ($customer->zone->name ?? '') : $customer->zone) ?: ($customer->coverageZone?->name ?: ($customer->address ?: '—')) }}
                            </td>

                            {{-- 6. Partner --}}
                            <td class="text-center font-mono whitespace-nowrap text-xs">
                                @if($customer->reseller)
                                    <span class="inline-flex items-center gap-1 text-purple-800 font-semibold" title="{{ $customer->reseller->name }}">
                                        <i class="fas fa-handshake text-purple-600 text-[10px]"></i>
                                        <span>{{ $customer->reseller->code ?: $customer->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-medium" title="{{ __('HQ Direct Retail') }}">
                                        <i class="fas fa-building text-slate-400 text-[10px]"></i>
                                        <span>{{ __('Direct') }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 7. Package --}}
                            <td class="text-left whitespace-nowrap font-medium text-slate-800 text-xs">
                                {{ $customer->package_display_name }}
                            </td>

                            {{-- 8. Monthly Bill --}}
                            <td class="text-right whitespace-nowrap font-mono font-bold text-xs text-slate-900">
                                @currency($bill)
                            </td>

                            {{-- 9. Expiry Date --}}
                            <td class="text-center whitespace-nowrap font-mono text-xs text-slate-700">
                                {{ $expiry ? $expiry->translatedFormat('d M Y') : '—' }}
                            </td>

                            {{-- 10. Cycle Status / Overdue Days --}}
                            <td class="text-center whitespace-nowrap">
                                @if($daysDiff < 0)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>{{ __('Overdue (:days d)', ['days' => abs($daysDiff)]) }}</span>
                                    </span>
                                @elseif($daysDiff === 0)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>{{ __('Expires Today') }}</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                        <span>{{ __('Due in :days d', ['days' => $daysDiff]) }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 11. Action: 1-Click Generate Bill --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="generateSingle({{ $customer->id }}, {{ Js::from($customer->name ?: $customer->username) }}, {{ $bill }})"
                                        class="h-[28px] px-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer"
                                        title="{{ __('Generate Invoice Now for this Customer') }}">
                                    <i class="fas fa-file-invoice-dollar text-[10px]"></i>
                                    <span>{{ __('+ Bill') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="12" class="text-center py-10 text-slate-400">
                                <i class="fas fa-circle-check text-3xl mb-2 block text-emerald-500"></i>
                                <span class="font-semibold text-slate-700 text-xs">{{ __('All Subscribers Are 100% Invoiced!') }}</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ __('There are no missing or uninvoiced subscribers in the selected scope. The automated billing engine is completely up to date.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($subscribers->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/60">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function billingCatchUpManager() {
    return {
        activeMenu: null,
        showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }},
        searchQuery: @json($search ?? ''),
        targetDateType: @json($targetDateType),
        customDate: @json($customDate),
        targetDateLabel: @json($targetDateLabel),
        targetGenerateBtnText: @json($generateBtnText),
        currencySymbol: @json($currencySymbol ?? '৳'),
        i18n: {
            generating: @json(__('Generating...')),
            processing: @json(__('Processing...')),
            generateSelected: @json(__('Generate Selected (:count)')),
            confirmSingleTitle: @json(__('Generate Subscription Invoice?')),
            confirmSingleHtml: @json(__('Generate monthly subscription bill of :amount for :name?')),
            confirmSingleBtn: @json(__('Yes, Generate Bill')),
            cancelBtn: @json(__('Cancel')),
            invoiceGeneratedTitle: @json(__('Invoice Generated!')),
            failedTitle: @json(__('Failed')),
            couldNotGenerate: @json(__('Could not generate invoice.')),
            errorTitle: @json(__('Error')),
            networkErrorGenerating: @json(__('Network error generating invoice.')),
            batchTitle: @json(__('Execute Billing Catch-Up?')),
            batchSelectedHtml: @json(__('Generate subscription invoices for :count selected subscribers?')),
            batchPartnerHtml: @json(__('Generate subscription invoices for all :count uninvoiced subscribers of :partner?')),
            targetDateScope: @json(__('Target Date Scope:')),
            subscribersToBill: @json(__('Subscribers to Bill:')),
            subscribersCount: @json(__(':count Subscribers')),
            estimatedTotal: @json(__('Estimated Total:')),
            batchConfirmText: @json(__('Are you sure you want to generate subscription invoices for these subscribers?')),
            batchConfirmBtn: @json(__('Yes, Generate Invoices')),
            catchupCompletedTitle: @json(__('Catch-Up Completed!')),
            executionFailedTitle: @json(__('Execution Failed')),
            batchFailedMsg: @json(__('Batch generation encountered an error.')),
            networkErrorBatch: @json(__('Network error during batch invoicing.'))
        },
        filteredCount: {{ $filteredTargetCount }},
        filteredAmount: {{ $filteredTargetAmount }},
        searchDebounceTimer: null,
        totalMatchingRows: null,
        isSearching: false,
        isGenerating: false,
        selectedIds: [],
        pageCustomerIds: @json($subscribers->pluck('id')),

        filterByTargetDate(type) {
            this.targetDateType = type;
            const form = document.getElementById('catchupFilterForm');
            if (form) {
                const select = form.querySelector('#targetDateTypeSelect');
                if (select) {
                    select.value = type;
                }
                form.submit();
            }
        },

        get isAllSelected() {
            if (this.pageCustomerIds.length === 0) return false;
            return this.pageCustomerIds.every(id => this.selectedIds.includes(id));
        },

        toggleSelect(id) {
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter(x => x !== id);
            } else {
                this.selectedIds.push(id);
            }
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.pageCustomerIds.forEach(id => {
                    if (!this.selectedIds.includes(id)) {
                        this.selectedIds.push(id);
                    }
                });
            } else {
                this.selectedIds = this.selectedIds.filter(id => !this.pageCustomerIds.includes(id));
            }
        },

        filterByPartner(partnerVal) {
            const form = document.getElementById('catchupFilterForm');
            if (form) {
                const partnerSelect = form.querySelector('#partnerFilterSelect');
                if (partnerSelect) {
                    partnerSelect.value = partnerVal;
                }
                form.submit();
            }
        },

        handleLiveSearch(val) {
            this.searchQuery = val;
            this.filterRowsOnly(val);
        },

        filterRowsOnly(val) {
            const term = (val || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.saas-table tbody tr:not(.no-record-row)');
            let matchCount = 0;
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const matches = !term || text.includes(term);
                row.style.display = matches ? '' : 'none';
                if (matches) matchCount++;
            });
            this.totalMatchingRows = term ? matchCount : null;
        },

        async fetchData() {
            const form = document.getElementById('catchupFilterForm');
            if (!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const url = `${form.action}?${params.toString()}`;

            this.isSearching = true;
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newTable = doc.querySelector('#catchupTableCard');
                    const curTable = document.querySelector('#catchupTableCard');
                    if (newTable && curTable) {
                        curTable.innerHTML = newTable.innerHTML;
                    }

                    const newKpi = doc.querySelector('#catchupKpiStrip');
                    const curKpi = document.querySelector('#catchupKpiStrip');
                    if (newKpi && curKpi) {
                        curKpi.innerHTML = newKpi.innerHTML;
                    }

                    this.filterRowsOnly(this.searchQuery);
                    window.history.replaceState({}, '', url);
                }
            } catch (err) {
                console.error('Fetch catch-up error:', err);
            } finally {
                this.isSearching = false;
            }
        },

        clearLiveSearch() {
            this.searchQuery = '';
            clearTimeout(this.searchDebounceTimer);
            const input = document.getElementById('catchupSearchInput');
            if (input) input.value = '';
            this.filterRowsOnly('');
            this.fetchData();
        },

        submitSearchNow() {
            clearTimeout(this.searchDebounceTimer);
            this.fetchData();
        },

        async generateSingle(customerId, customerName, billAmount) {
            const sym = this.currencySymbol;
            const amountHtml = `<b>${sym}${billAmount.toFixed(2)}</b>`;
            const nameHtml = `<b>${customerName}</b>`;
            const result = await Swal.fire({
                title: this.i18n.confirmSingleTitle,
                html: this.i18n.confirmSingleHtml
                    .replace(':amount', amountHtml)
                    .replace(':name', nameHtml),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: this.i18n.confirmSingleBtn,
                cancelButtonText: this.i18n.cancelBtn
            });

            if (!result.isConfirmed) return;

            this.isGenerating = true;
            try {
                const res = await fetch(`{{ route('tenant.finance.billing-catchup.execute') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        scope: 'selected',
                        customer_ids: [customerId]
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: this.i18n.invoiceGeneratedTitle,
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    Swal.fire(this.i18n.failedTitle, data.message || this.i18n.couldNotGenerate, 'error');
                }
            } catch (err) {
                Swal.fire(this.i18n.errorTitle, this.i18n.networkErrorGenerating, 'error');
            } finally {
                this.isGenerating = false;
            }
        },

        async confirmBatchGenerate(scope, partnerId = null, partnerName = '', partnerCount = 0) {
            const title = this.i18n.batchTitle;
            let htmlText = '';
            let payload = {
                scope: scope,
                target_date_type: this.targetDateType,
                custom_date: this.customDate
            };

            const sym = this.currencySymbol;
            if (scope === 'selected') {
                htmlText = this.i18n.batchSelectedHtml.replace(':count', `<b>${this.selectedIds.length}</b>`);
                payload.customer_ids = this.selectedIds;
            } else if (scope === 'partner') {
                htmlText = this.i18n.batchPartnerHtml
                    .replace(':count', `<b>${partnerCount}</b>`)
                    .replace(':partner', `<b>${partnerName}</b>`);
                payload.partner_id = partnerId;
            } else {
                const countLabel = this.i18n.subscribersCount.replace(':count', this.filteredCount);
                htmlText = `<div class="text-left space-y-1.5 p-2 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex justify-between text-xs"><span class="text-slate-500">${this.i18n.targetDateScope}</span><b class="text-cyan-700 font-semibold">${this.targetDateLabel}</b></div>
                    <div class="flex justify-between text-xs"><span class="text-slate-500">${this.i18n.subscribersToBill}</span><b class="font-mono text-slate-800">${countLabel}</b></div>
                    <div class="flex justify-between text-xs"><span class="text-slate-500">${this.i18n.estimatedTotal}</span><b class="font-mono text-emerald-600 font-bold">${sym}${this.filteredAmount.toLocaleString()}</b></div>
                </div>
                <p class="text-xs text-slate-600 mt-2">${this.i18n.batchConfirmText}</p>`;
            }

            const result = await Swal.fire({
                title: title,
                html: htmlText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: this.i18n.batchConfirmBtn,
                cancelButtonText: this.i18n.cancelBtn
            });

            if (!result.isConfirmed) return;

            this.isGenerating = true;
            try {
                const res = await fetch(`{{ route('tenant.finance.billing-catchup.execute') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: this.i18n.catchupCompletedTitle,
                        text: data.message,
                        confirmButtonColor: '#0891b2'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(this.i18n.executionFailedTitle, data.message || this.i18n.batchFailedMsg, 'error');
                }
            } catch (err) {
                Swal.fire(this.i18n.errorTitle, this.i18n.networkErrorBatch, 'error');
            } finally {
                this.isGenerating = false;
            }
        }
    };
}
</script>
@endpush

