@extends('tenant.layouts.app')

@section('title', __('Collector Daily Cash Handover') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Specialized Thermal POS & A4 Handover Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printableCollectorHandoverArea, #printableCollectorHandoverArea * {
            visibility: visible;
        }
        #printableCollectorHandoverArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="collectorHandoverManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 sm:px-4 sm:py-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.dashboard') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs" title="{{ __('Back to Dashboard') }}">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-sm shadow-2xs">
                <i class="fas fa-vault"></i>
            </div>
            <h1 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">
                {{ __('Collector Daily Cash Handover') }}
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Submit Handover Button -->
            <button type="button" 
                    @click="openSubmitModal()" 
                    class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-paper-plane text-xs"></i>
                <span>{{ __('Submit Cash Handover') }}</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Cash in Hand -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cash in Hand') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    @currency($stats['available_cash_in_hand'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Card 2: Pending Approval -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Pending Approval') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($stats['pending_approval'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <!-- Card 3: Approved in Vault -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Approved in Vault') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['total_approved_handed'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 4: Total Cash Closed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Cash') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    @currency($stats['total_cash_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 border border-slate-200 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        <!-- Card 5: Total Handovers Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Handover Count') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ number_format($stats['total_handovers_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Card 6: Today Collected -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Today Collected') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['today_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C: 2-Tier Architecture, Uniform h-[34px], Strict Filter then Reset Sequence) -->
    @php
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search ?? '') }}' }">
        <form method="GET" action="{{ url()->current() }}" id="handoverFilterForm">
            <!-- Tier 1: Primary Single Row -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors" 
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="{{ __('Search voucher #, notes, or collector name...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer p-0.5"
                            title="{{ __('Clear Search') }}">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                <!-- Collector Staff Filter -->
                <div class="w-full md:w-48 flex-shrink-0">
                    <select name="collector_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        @if($isAdmin)
                            <option value="all" {{ ($collectorFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Collector Staff') }}</option>
                            @foreach($allCollectors ?? [] as $c)
                                <option value="{{ $c->id }}" {{ (string) ($collectorFilter ?? '') === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->isCollector() ? __('Collector') : ($c->isIspAdmin() || $c->isOwner() ? __('Admin') : __(ucfirst($c->role ?? 'Staff'))) }})
                                </option>
                            @endforeach
                        @else
                            <option value="{{ $authUser->id }}" selected>{{ $authUser->name }} ({{ __('My Collections') }})</option>
                        @endif
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                        <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>{{ __('Pending Verification') }}</option>
                        <option value="approved" {{ ($statusFilter ?? '') === 'approved' ? 'selected' : '' }}>{{ __('Approved in Vault') }}</option>
                        <option value="rejected" {{ ($statusFilter ?? '') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 10]) }}</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Period, Shift & Specific Date Filters') }}">
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

                <!-- Strict Filter & Reset Button Sequence (AGENTS.md Rule 2.C - Far Right Aligned on md+) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ url()->current() }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filters Drawer -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <!-- Quick Period Preset -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <select name="period" 
                            id="collectorPeriodSelect"
                            onchange="handleCollectorPeriodChange(this.value)"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($period ?? '') === 'all' || empty($period) ? 'selected' : '' }}>{{ __('All Time Range') }}</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('Yesterday') }}</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('Last Month') }}</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>{{ __('Custom Date Range') }}</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <input type="date" 
                           name="date_from" 
                           id="collectorDateFromInput"
                           value="{{ $dateFrom }}" 
                           onchange="this.form.submit()"
                           title="{{ __('Date From') }}"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500">
                </div>

                <!-- Date To -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <input type="date" 
                           name="date_to" 
                           id="collectorDateToInput"
                           value="{{ $dateTo }}" 
                           onchange="this.form.submit()"
                           title="{{ __('Date To') }}"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500">
                </div>

                <!-- Partner / Reseller Filter -->
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="partner_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($partnerFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Partners / HQ') }}</option>
                        <option value="hq" {{ ($partnerFilter ?? '') === 'hq' ? 'selected' : '' }}>{{ __('ISP Core (HQ)') }}</option>
                        @foreach($allResellers ?? [] as $reseller)
                            <option value="{{ $reseller->id }}" {{ (string)($partnerFilter ?? '') === (string)$reseller->id ? 'selected' : '' }}>
                                {{ $reseller->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Shift Filter -->
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="shift" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($shiftFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Shifts') }}</option>
                        <option value="daily" {{ ($shiftFilter ?? '') === 'daily' ? 'selected' : '' }}>{{ __('Daily Closing') }}</option>
                        <option value="morning" {{ ($shiftFilter ?? '') === 'morning' ? 'selected' : '' }}>{{ __('Morning Shift') }}</option>
                        <option value="evening" {{ ($shiftFilter ?? '') === 'evening' ? 'selected' : '' }}>{{ __('Evening Shift') }}</option>
                        <option value="night" {{ ($shiftFilter ?? '') === 'night' ? 'selected' : '' }}>{{ __('Night Shift') }}</option>
                        <option value="full_day" {{ ($shiftFilter ?? '') === 'full_day' ? 'selected' : '' }}>{{ __('Full Day Closing') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (.saas-table Pure CSS System - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-center font-semibold">{{ __('Voucher #') }}</th>
                        <th class="text-center font-semibold">{{ __('Partner') }}</th>
                        <th class="text-center font-semibold">{{ __('Collector Staff') }}</th>
                        <th class="text-center font-semibold">{{ __('Collected') }}</th>
                        <th class="text-center font-semibold">{{ __('Handed Over') }}</th>
                        <th class="text-center font-semibold">{{ __('Remaining in Hand') }}</th>
                        <th class="text-center font-semibold">{{ __('Received By') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Date & Time') }}</th>
                        <th class="w-14 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($handovers as $index => $item)
                        @php
                            $badge = $item->status_badge;
                            $disc = $item->discrepancy_badge;
                            $partnerName = $item->collector?->reseller?->name ?? __('ISP Core (HQ)');
                            $systemCollected = (float) ($item->system_collected_amount > 0 ? $item->system_collected_amount : $item->handed_over_amount);
                            $handedOver = (float) $item->handed_over_amount;
                            $remainingInHand = max(0, $systemCollected - $handedOver);

                            $rowItemJson = [
                                'id' => $item->id,
                                'handover_no' => $item->handover_no,
                                'partner_name' => $partnerName,
                                'collector_id' => $item->collector_id,
                                'collector_name' => $item->collector?->name ?? __('Staff Collector'),
                                'system_collected_amount' => $systemCollected,
                                'handed_over_amount' => $handedOver,
                                'remaining_amount' => $remainingInHand,
                                'shortage_amount' => (float) $item->shortage_amount,
                                'excess_amount' => (float) $item->excess_amount,
                                'status' => $item->status,
                                'notes' => $item->notes ?? '',
                                'shift_type' => $item->shift_type ?? 'daily',
                            ];
                        @endphp
                        <tr>
                            <!-- 0. Row Index -->
                            <td class="text-center font-mono text-slate-500 whitespace-nowrap">
                                {{ $handovers->firstItem() + $index }}
                            </td>

                            <!-- 1. Voucher Number -->
                            <td class="text-center font-mono text-amber-800 font-bold whitespace-nowrap">
                                <button type="button" 
                                        @click="openVoucherModal({{ $item->id }})" 
                                        class="hover:underline hover:text-amber-600 cursor-pointer inline-flex items-center gap-1"
                                        title="{{ __('View & Print Voucher Details') }}">
                                    <i class="fas fa-file-invoice-dollar text-[10px] text-amber-500"></i>
                                    <span>{{ $item->handover_no }}</span>
                                </button>
                            </td>

                            <!-- 2. Partner (ISP Core HQ vs Franchise Partner) -->
                            <td class="text-left font-medium text-slate-800 whitespace-nowrap text-xs">
                                @if($item->collector?->reseller)
                                    <span class="inline-flex items-center gap-1 text-cyan-800 font-semibold">
                                        <i class="fas fa-handshake text-[10px] text-cyan-600"></i>
                                        <span>{{ $item->collector->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-600">
                                        <i class="fas fa-building text-[10px] text-slate-400"></i>
                                        <span>{{ __('ISP Core (HQ)') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Collector Staff -->
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap text-xs">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user text-[10px] text-slate-400"></i>
                                    <span>{{ $item->collector?->name ?? __('Unknown Collector') }}</span>
                                </div>
                            </td>

                            <!-- 4. Collected Amount -->
                            <td class="text-right font-mono font-medium text-slate-800 whitespace-nowrap text-xs">
                                @currency($systemCollected)
                            </td>

                            <!-- 5. Handed Over Amount -->
                            <td class="text-right font-mono font-bold text-emerald-700 whitespace-nowrap text-xs">
                                @currency($handedOver)
                            </td>

                            <!-- 6. Remaining in Hand -->
                            <td class="text-right font-mono whitespace-nowrap text-xs">
                                @if($remainingInHand > 0)
                                    <span class="inline-flex items-center gap-1 text-amber-700 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200" title="{{ __('Cash still in collector custody') }}">
                                        <i class="fas fa-wallet text-[9px] text-amber-500"></i>
                                        <span>@currency($remainingInHand)</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">
                                        @currency(0)
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Received / Verified By -->
                            <td class="text-left font-medium text-slate-700 whitespace-nowrap text-xs">
                                @if($item->verifier)
                                    <span class="inline-flex items-center gap-1 text-slate-800">
                                        <i class="fas fa-user-check text-[10px] text-emerald-600"></i>
                                        <span>{{ $item->verifier->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">{{ __('Pending Reception') }}</span>
                                @endif
                            </td>

                            <!-- 8. Status Badge -->
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $badge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 9. Date & Time -->
                            <td class="text-center font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ $item->created_at ? $item->created_at->translatedFormat('d M Y, h:i A') : ($item->handover_date ? $item->handover_date->translatedFormat('d M Y') : '—') }}
                            </td>

                            <!-- 10. Action Column (Rule 2.E 3-Dot Floating Action Menu) -->
                            <td class="text-center whitespace-nowrap no-sort">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($rowItemJson) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer mx-auto"
                                        title="{{ __('Handover Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[11px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 text-slate-400">
                                <i class="fas fa-vault text-2xl mb-1.5 block text-slate-300"></i>
                                <span class="text-xs font-medium">{{ __('No cash handover records found.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($handovers->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/50">
                {{ $handovers->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null" 
         class="fixed z-50 bg-white border border-slate-200 rounded-xl shadow-xl py-1.5 w-44 text-xs font-medium"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         style="display: none;">
        
        <div class="px-3 py-1 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono" 
             x-text="activeMenu ? activeMenu.handover_no : ''">
        </div>

        <!-- 1. View Details & Print -->
        <button type="button" 
                @click="openVoucherModal(activeMenu.id); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-amber-50 hover:text-amber-800 transition flex items-center gap-2 cursor-pointer">
            <i class="fas fa-file-invoice-dollar text-[11px] text-amber-500 w-4"></i>
            <span>{{ __('View & Print Details') }}</span>
        </button>

        <!-- 2. Approve (Admin Only & Pending Only) -->
        <template x-if="activeMenu && activeMenu.status === 'pending' && {{ $isAdmin ? 'true' : 'false' }}">
            <button type="button" 
                    @click="openApproveModal(activeMenu); activeMenu = null" 
                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-check-circle text-[11px] text-emerald-500 w-4"></i>
                <span>{{ __('Approve to Vault') }}</span>
            </button>
        </template>

        <!-- 3. Edit Handover (Admin or Owner) -->
        <template x-if="{{ $isAdmin ? 'true' : 'false' }} || (activeMenu && activeMenu.status === 'pending')">
            <button type="button" 
                    @click="openEditModal(activeMenu); activeMenu = null" 
                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-cyan-50 hover:text-cyan-700 transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-pen-to-square text-[11px] text-cyan-500 w-4"></i>
                <span>{{ __('Edit Handover') }}</span>
            </button>
        </template>

        <!-- 4. Delete Handover (Admin Only) -->
        <template x-if="{{ $isAdmin ? 'true' : 'false' }}">
            <div class="border-t border-slate-100 my-1"></div>
            <button type="button" 
                    @click="confirmDelete(activeMenu); activeMenu = null" 
                    class="w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50 transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-trash-can text-[11px] text-rose-500 w-4"></i>
                <span>{{ __('Delete Handover') }}</span>
            </button>
        </template>
    </div>

    <!-- 6. Production-Grade Natural Modal: Submit Cash Handover (AGENTS.md Rule 3) -->
    <div x-show="submitModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.away="submitModal.open = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-vault"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Submit Daily Cash Handover') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Close shift and submit physical cash to accounts') }}</p>
                    </div>
                </div>
                <button type="button" @click="submitModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ url()->current() }}" method="POST" class="p-4 space-y-3.5 text-xs">
                @csrf
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                    <span class="text-slate-600 font-medium">{{ __('Un-handed Cash in Hand:') }}</span>
                    <span class="font-bold font-mono text-amber-700">@currency($availableCashInHand)</span>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Handover Amount (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="amount" 
                           value="{{ $availableCashInHand }}" 
                           step="0.01" 
                           min="1" 
                           required 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-amber-500 font-mono font-bold text-slate-800 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Notes / Remarks') }}
                    </label>
                    <input type="text" 
                           name="notes" 
                           placeholder="{{ __('e.g. Daily shift collection handover') }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-amber-500 shadow-2xs">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" 
                            @click="submitModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ ($authUser->isOwner() || $authUser->isIspAdmin()) ? __('Approve & Transfer to Vault') : __('Submit Handover') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: Edit Handover -->
    <div x-show="editModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.away="if (!editModal.loading) editModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Edit Handover Voucher') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Adjust handover amount and closing notes') }}</p>
                    </div>
                </div>
                <button type="button" @click="editModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitEditHandover()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-2.5 rounded-lg bg-cyan-50 border border-cyan-200/80 space-y-1">
                        <div class="flex justify-between font-bold text-slate-800">
                            <span>{{ __('Voucher No:') }}</span>
                            <span class="font-mono text-cyan-800" x-text="editModal.handover?.handover_no"></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>{{ __('System Cash:') }}</span>
                            <span class="font-mono font-bold" x-text="currencySymbol + parseFloat(editModal.handover?.system_collected_amount || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Handed Amount (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="editModal.form.handed_over_amount" 
                               required 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 font-mono font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Shift Type') }}
                        </label>
                        <select x-model="editModal.form.shift_type" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500">
                            <option value="daily">{{ __('Daily Closing') }}</option>
                            <option value="morning">{{ __('Morning Shift') }}</option>
                            <option value="evening">{{ __('Evening Shift') }}</option>
                            <option value="night">{{ __('Night Shift') }}</option>
                            <option value="full_day">{{ __('Full Day Closing') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Notes / Remarks') }}
                        </label>
                        <input type="text" 
                               x-model="editModal.form.notes" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500">
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="editModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="editModal.loading" 
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas" :class="editModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="editModal.loading ? i18n.saving : i18n.saveChanges"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: Approve Handover -->
    <div x-show="approveModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden" 
             @click.away="if (!approveModal.loading) approveModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Verify & Approve to Vault') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Transfer physical cash into ISP main vault') }}</p>
                    </div>
                </div>
                <button type="button" @click="approveModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitApproveHandover()">
                <div class="p-4 space-y-3 text-xs">
                    <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-900 space-y-1">
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Voucher No:') }}</span>
                            <span class="font-mono text-emerald-800" x-text="approveModal.handover?.handover_no"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Partner:') }}</span>
                            <span class="text-slate-800" x-text="approveModal.handover?.partner_name || i18n.ispCoreHq"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Collector:') }}</span>
                            <span x-text="approveModal.handover?.collector_name"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Collected Amount:') }}</span>
                            <span class="font-mono text-slate-800" x-text="currencySymbol + parseFloat(approveModal.handover?.system_collected_amount || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Handed Over Cash:') }}</span>
                            <span class="font-mono text-emerald-700" x-text="currencySymbol + parseFloat(approveModal.handover?.handed_over_amount || 0).toFixed(2)"></span>
                        </div>
                        <template x-if="parseFloat(approveModal.handover?.remaining_amount || 0) > 0">
                            <div class="flex justify-between font-bold text-amber-700">
                                <span>{{ __('Remaining in Hand:') }}</span>
                                <span class="font-mono" x-text="currencySymbol + parseFloat(approveModal.handover?.remaining_amount || 0).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-slate-700 font-semibold text-[11px] mb-1">{{ __('Verification Remarks') }}</label>
                        <input type="text" 
                               x-model="approveModal.manager_remarks" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2 text-slate-800 focus:bg-white focus:border-emerald-500">
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="approveModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="approveModal.loading" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="approveModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="approveModal.loading ? i18n.approving : i18n.approveAndReceiveCash"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 9. Production-Grade Natural Modal: View / Print Handover Voucher (Dual Layout) -->
    <div x-show="voucherModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="voucherModal.open = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between no-print">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Cash Handover Voucher') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Official shift closing & vault transfer slip') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" 
                            @click="printVoucher()" 
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-xs cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>{{ __('Print') }}</span>
                    </button>

                    <button type="button" 
                            @click="voucherModal.open = false" 
                            class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Printable Voucher Area -->
            <div id="printableCollectorHandoverArea" class="p-5 text-xs text-slate-800 font-sans max-h-[75vh] overflow-y-auto">
                <template x-if="voucherModal.handover">
                    <div class="space-y-4">
                        
                        <!-- Company Header -->
                        <div class="text-center border-b border-dashed border-slate-300 pb-3">
                            <h2 class="text-base font-bold text-slate-900 tracking-tight" x-text="voucherModal.handover.company.name"></h2>
                            <p class="text-[10.5px] text-slate-500 mt-0.5" x-text="voucherModal.handover.company.address"></p>
                            <p class="text-[10.5px] text-slate-600 font-mono mt-0.5" x-text="i18n.helplinePrefix + voucherModal.handover.company.phone"></p>
                            <div class="mt-2 inline-block px-2.5 py-0.5 bg-slate-900 text-amber-400 font-mono font-bold text-[11px] rounded tracking-wider uppercase">
                                {{ __('Daily Cash Handover Voucher') }}
                            </div>
                        </div>

                        <!-- Meta Info Grid -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] font-mono border-b border-dashed border-slate-200 pb-2.5">
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase">{{ __('Voucher No') }}</span>
                                <span class="font-bold text-slate-900" x-text="voucherModal.handover.handover_no"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block text-[9px] uppercase">{{ __('Handover Date & Time') }}</span>
                                <span class="text-slate-800" x-text="voucherModal.handover.created_at_formatted"></span>
                            </div>
                        </div>

                        <!-- Collector & Shift Info -->
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">{{ __('Partner:') }}</span>
                                <span class="font-bold text-slate-900" x-text="voucherModal.handover.partner_name || i18n.ispCoreHq"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">{{ __('Collector Staff:') }}</span>
                                <span class="font-bold text-slate-900" x-text="voucherModal.handover.collector.name"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                                <span>{{ __('Shift Type:') }}</span>
                                <span class="font-medium text-slate-800" x-text="voucherModal.handover.shift_label"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                                <span>{{ __('Received By:') }}</span>
                                <span class="font-medium text-slate-800" x-text="voucherModal.handover.verifier?.name || i18n.pendingReception"></span>
                            </div>
                        </div>

                        <!-- Amount Breakdown -->
                        <div class="space-y-1.5 border-b border-dashed border-slate-200 pb-3 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>{{ __('System Cash Collection:') }}</span>
                                <span class="font-bold text-slate-800" x-text="voucherModal.handover.system_collected_formatted"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>{{ __('Handed Over Physical Cash:') }}</span>
                                <span class="font-bold text-emerald-700" x-text="voucherModal.handover.handed_over_formatted"></span>
                            </div>
                            <template x-if="voucherModal.handover.remaining_amount > 0">
                                <div class="flex items-center justify-between text-amber-700 font-bold">
                                    <span>{{ __('Remaining Cash in Hand:') }}</span>
                                    <span x-text="voucherModal.handover.remaining_formatted"></span>
                                </div>
                            </template>
                            <template x-if="voucherModal.handover.shortage_amount > 0">
                                <div class="flex items-center justify-between text-rose-700 font-bold">
                                    <span>{{ __('Shortage Amount:') }}</span>
                                    <span x-text="'-' + voucherModal.handover.shortage_formatted"></span>
                                </div>
                            </template>
                            <template x-if="voucherModal.handover.excess_amount > 0">
                                <div class="flex items-center justify-between text-indigo-700 font-bold">
                                    <span>{{ __('Excess Cash:') }}</span>
                                    <span x-text="'+' + voucherModal.handover.excess_formatted"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Receipts List -->
                        <template x-if="voucherModal.handover.receipts && voucherModal.handover.receipts.length > 0">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700">
                                    <span>{{ __('Locked Cash Receipts') }}</span>
                                    <span class="font-mono text-[10px]" x-text="i18n.itemsCount.replace(':count', voucherModal.handover.receipts.length)"></span>
                                </div>
                                <div class="max-h-40 overflow-y-auto space-y-1 bg-slate-50 p-2 rounded-lg border border-slate-200/80">
                                    <template x-for="r in voucherModal.handover.receipts" :key="r.id">
                                        <div class="flex items-center justify-between text-[10.5px] border-b border-slate-200/60 pb-1">
                                            <div>
                                                <span class="font-mono font-bold text-slate-900" x-text="r.invoice_no"></span>
                                                <span class="text-slate-500 block text-[9.5px]" x-text="r.customer_name + ' (' + r.customer_code + ')'"></span>
                                            </div>
                                            <div class="text-right font-mono">
                                                <span class="font-bold text-slate-800" x-text="r.amount_formatted"></span>
                                                <span class="text-slate-400 block text-[9px]" x-text="r.paid_time"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Signatures Zone -->
                        <div class="pt-6 border-t border-slate-200 text-center space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-[10px] font-mono">
                                <div>
                                    <div class="w-24 border-b border-slate-300 mb-1 mx-auto"></div>
                                    <span x-text="voucherModal.handover.collector.name"></span>
                                    <span class="block text-[8.5px] text-slate-400">{{ __('Collector Signature') }}</span>
                                </div>
                                <div>
                                    <div class="w-24 border-b border-slate-300 mb-1 mx-auto"></div>
                                    <span x-text="voucherModal.handover.verifier?.name || i18n.accountsManager"></span>
                                    <span class="block text-[8.5px] text-slate-400">{{ __('Received By / Vault Sign') }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 no-print">
                <button type="button" 
                        @click="voucherModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
                <button type="button" 
                        @click="printVoucher()" 
                        class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>{{ __('Print Voucher') }}</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function handleCollectorPeriodChange(val) {
    const fromInput = document.getElementById('collectorDateFromInput');
    const toInput = document.getElementById('collectorDateToInput');
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

function collectorHandoverManager() {
    return {
        currencySymbol: @json($currencySymbol ?? '৳'),
        i18n: {
            defaultApproveRemarks: @json(__('Verified physical cash and approved into vault.')),
            saving: @json(__('Saving...')),
            saveChanges: @json(__('Save Changes')),
            ispCoreHq: @json(__('ISP Core (HQ)')),
            approving: @json(__('Approving...')),
            approveAndReceiveCash: @json(__('Approve & Receive Cash')),
            helplinePrefix: @json(__('Helpline: ')),
            pendingReception: @json(__('Pending Reception')),
            itemsCount: @json(__(':count item(s)')),
            accountsManager: @json(__('Accounts Manager')),
            updatedTitle: @json(__('Updated!')),
            updatedText: @json(__('Handover voucher updated successfully.')),
            updateFailedTitle: @json(__('Update Failed')),
            updateFailedText: @json(__('Failed to update handover voucher.')),
            networkErrorTitle: @json(__('Network Error')),
            networkErrorText: @json(__('Failed to communicate with the server.')),
            approvedTitle: @json(__('Approved!')),
            approvedText: @json(__('Cash handover approved successfully into vault.')),
            approvalFailedTitle: @json(__('Approval Failed')),
            approvalFailedText: @json(__('Failed to approve handover voucher.')),
            deleteConfirmTitle: @json(__('Are you sure?')),
            deleteConfirmText: @json(__('Do you want to delete handover voucher ":no"? All associated cash receipts will be unlocked and returned to collector cash.')),
            yesDeleteVoucher: @json(__('Yes, Delete Voucher')),
            cancel: @json(__('Cancel')),
            deletedTitle: @json(__('Deleted!')),
            deletedText: @json(__('Handover voucher deleted successfully.')),
            deleteFailedTitle: @json(__('Delete Failed')),
            deleteFailedText: @json(__('Failed to delete handover.')),
            errorTitle: @json(__('Error')),
            loadVoucherFailed: @json(__('Failed to load voucher details.')),
        },
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

        submitModal: {
            open: false,
        },

        editModal: {
            open: false,
            loading: false,
            handover: null,
            form: {
                handed_over_amount: '',
                shift_type: 'daily',
                notes: '',
            }
        },

        approveModal: {
            open: false,
            loading: false,
            handover: null,
            manager_remarks: @json(__('Verified physical cash and approved into vault.'))
        },

        voucherModal: {
            open: false,
            loading: false,
            handover: null
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

        getBaseUrl() {
            return window.location.pathname.replace(/\/+$/, '');
        },

        openSubmitModal() {
            this.submitModal.open = true;
        },

        openEditModal(item) {
            this.editModal.handover = item;
            this.editModal.form = {
                handed_over_amount: parseFloat(item.handed_over_amount || 0).toFixed(2),
                shift_type: item.shift_type || 'daily',
                notes: item.notes || '',
            };
            this.editModal.open = true;
        },

        async submitEditHandover() {
            if (!this.editModal.handover?.id) return;
            this.editModal.loading = true;

            try {
                const res = await fetch(`${this.getBaseUrl()}/${this.editModal.handover.id}/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.editModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.editModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: this.i18n.updatedTitle,
                        text: data.message || this.i18n.updatedText,
                        timer: 1800,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-xl text-xs font-sans shadow-2xl' }
                    });
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.updateFailedTitle,
                        text: data.message || this.i18n.updateFailedText,
                        confirmButtonColor: '#0891b2',
                        customClass: { popup: 'rounded-xl text-xs font-sans' }
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: this.i18n.networkErrorTitle,
                    text: this.i18n.networkErrorText,
                    confirmButtonColor: '#0891b2',
                    customClass: { popup: 'rounded-xl text-xs font-sans' }
                });
            } finally {
                this.editModal.loading = false;
            }
        },

        openApproveModal(item) {
            this.approveModal.handover = item;
            this.approveModal.manager_remarks = this.i18n.defaultApproveRemarks;
            this.approveModal.open = true;
        },

        async submitApproveHandover() {
            if (!this.approveModal.handover?.id) return;
            this.approveModal.loading = true;

            try {
                const res = await fetch(`${this.getBaseUrl()}/${this.approveModal.handover.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_remarks: this.approveModal.manager_remarks })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.approveModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: this.i18n.approvedTitle,
                        text: data.message || this.i18n.approvedText,
                        timer: 1800,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-xl text-xs font-sans shadow-2xl' }
                    });
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.approvalFailedTitle,
                        text: data.message || this.i18n.approvalFailedText,
                        confirmButtonColor: '#0891b2',
                        customClass: { popup: 'rounded-xl text-xs font-sans' }
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: this.i18n.networkErrorTitle,
                    text: this.i18n.networkErrorText,
                    confirmButtonColor: '#0891b2',
                    customClass: { popup: 'rounded-xl text-xs font-sans' }
                });
            } finally {
                this.approveModal.loading = false;
            }
        },

        confirmDelete(item) {
            Swal.fire({
                title: this.i18n.deleteConfirmTitle,
                text: this.i18n.deleteConfirmText.replace(':no', item.handover_no),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: this.i18n.yesDeleteVoucher,
                cancelButtonText: this.i18n.cancel,
                customClass: {
                    popup: 'rounded-xl text-xs font-sans shadow-2xl'
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const res = await fetch(`${this.getBaseUrl()}/${item.id}/delete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: this.i18n.deletedTitle,
                                text: data.message || this.i18n.deletedText,
                                timer: 1800,
                                showConfirmButton: false,
                                customClass: { popup: 'rounded-xl text-xs font-sans shadow-2xl' }
                            });
                            setTimeout(() => window.location.reload(), 700);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: this.i18n.deleteFailedTitle,
                                text: data.message || this.i18n.deleteFailedText,
                                confirmButtonColor: '#0891b2',
                                customClass: { popup: 'rounded-xl text-xs font-sans' }
                            });
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: this.i18n.networkErrorTitle,
                            text: this.i18n.networkErrorText,
                            confirmButtonColor: '#0891b2',
                            customClass: { popup: 'rounded-xl text-xs font-sans' }
                        });
                    }
                }
            });
        },

        async openVoucherModal(id) {
            this.voucherModal.loading = true;
            this.voucherModal.handover = null;
            this.voucherModal.open = true;

            try {
                const res = await fetch(`${this.getBaseUrl()}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.voucherModal.handover = data.handover;
                } else {
                    this.voucherModal.open = false;
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.errorTitle,
                        text: data.message || this.i18n.loadVoucherFailed,
                        confirmButtonColor: '#0891b2',
                        customClass: { popup: 'rounded-xl text-xs font-sans' }
                    });
                }
            } catch (err) {
                this.voucherModal.open = false;
                Swal.fire({
                    icon: 'error',
                    title: this.i18n.networkErrorTitle,
                    text: this.i18n.loadVoucherFailed,
                    confirmButtonColor: '#0891b2',
                    customClass: { popup: 'rounded-xl text-xs font-sans' }
                });
            } finally {
                this.voucherModal.loading = false;
            }
        },

        printVoucher() {
            window.print();
        }
    };
}
</script>
@endpush
