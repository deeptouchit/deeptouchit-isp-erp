@extends('tenant.layouts.app')

@section('title', __('Online Gateway Transactions') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="gatewayTransactionsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Online Gateway Transactions') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <div class="hidden sm:flex items-center gap-1.5 h-[34px] px-2.5 bg-slate-100 rounded-lg border border-slate-200 text-[11px] text-slate-600 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ __('PGWs Active') }}</span>
            </div>

            <a href="{{ route('tenant.finance.gateway-transactions.export', request()->all()) }}" class="h-[34px] py-0 px-3 inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Success Volume --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Success Volume') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($totalSuccessVolume)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 2: Cleared Transactions Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cleared Count') }}</span>
                <span class="text-[13px] font-bold font-mono text-teal-700 leading-tight block truncate">{{ number_format($successCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 3: Gateway Fees --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('PGW Fees') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">@currency($totalGatewayFees)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        {{-- Card 4: Net Revenue Settled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Net Settled') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">@currency($netSettledRevenue)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 border border-cyan-100 text-cyan-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 5: In-Flight / Pending --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('In-Flight / Pending') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">{{ number_format($pendingCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 6: Failed / Dropped --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Failed / Dropped') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">{{ number_format($failedCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ ($isMoreOpen ?? false) ? 'true' : 'false' }}, 
             searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.finance.gateway-transactions') }}" class="space-y-2.5">
            <!-- Tier 1: Primary Filter Bar (Single Uniform Line on md+) -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors pointer-events-none"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="{{ __('Search TrxID, Gateway Ref, Payer phone/name...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <!-- 1-Click Clear Button -->
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 text-xs transition cursor-pointer p-0.5">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <!-- Primary Dropdown 1: Gateway Channel Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="gateway" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all">{{ __('All Gateways') }}</option>
                        <option value="bkash" {{ $selectedGateway === 'bkash' ? 'selected' : '' }}>{{ __('bKash PGW') }}</option>
                        <option value="nagad" {{ $selectedGateway === 'nagad' ? 'selected' : '' }}>{{ __('Nagad Direct') }}</option>
                        <option value="rocket" {{ $selectedGateway === 'rocket' ? 'selected' : '' }}>{{ __('Rocket (DBBL)') }}</option>
                        <option value="sslcommerz" {{ $selectedGateway === 'sslcommerz' ? 'selected' : '' }}>{{ __('SSLCommerz') }}</option>
                        <option value="shurjopay" {{ $selectedGateway === 'shurjopay' ? 'selected' : '' }}>{{ __('ShurjoPay') }}</option>
                        <option value="stripe" {{ $selectedGateway === 'stripe' ? 'selected' : '' }}>{{ __('Stripe') }}</option>
                    </select>
                </div>

                <!-- Primary Dropdown 2: Status Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Statuses') }}</option>
                        <option value="SUCCESS" {{ ($selectedStatus ?? '') === 'SUCCESS' ? 'selected' : '' }}>{{ __('Success / Cleared') }}</option>
                        <option value="PENDING" {{ ($selectedStatus ?? '') === 'PENDING' ? 'selected' : '' }}>{{ __('Pending / Processing') }}</option>
                        <option value="FAILED" {{ ($selectedStatus ?? '') === 'FAILED' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                        <option value="CANCELLED" {{ ($selectedStatus ?? '') === 'CANCELLED' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                        <option value="REFUNDED" {{ ($selectedStatus ?? '') === 'REFUNDED' ? 'selected' : '' }}>{{ __('Refunded') }}</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                    </select>
                </div>

                <!-- "More Filters" Accordion Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount ?? 0 }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Purpose, Period & Date Range Filters') }}">
                    <i class="fas fa-sliders-h text-[10px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if(($activeMoreCount ?? 0) > 0)
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
                    <a href="{{ route('tenant.finance.gateway-transactions') }}" 
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
                
                <!-- Purpose / Category Filter -->
                <div class="w-full sm:w-48 flex-shrink-0">
                    <select name="purpose" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($selectedPurpose ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Categories') }}</option>
                        <option value="CUSTOMER_BILL" {{ ($selectedPurpose ?? '') === 'CUSTOMER_BILL' ? 'selected' : '' }}>{{ __('Subscriber Monthly Bill') }}</option>
                        <option value="CUSTOMER_RECHARGE" {{ ($selectedPurpose ?? '') === 'CUSTOMER_RECHARGE' ? 'selected' : '' }}>{{ __('Prepaid Recharge') }}</option>
                        <option value="RESELLER_TOPUP" {{ ($selectedPurpose ?? '') === 'RESELLER_TOPUP' ? 'selected' : '' }}>{{ __('Sub-ISP Wallet Recharge') }}</option>
                        <option value="RESELLER_INVOICE" {{ ($selectedPurpose ?? '') === 'RESELLER_INVOICE' ? 'selected' : '' }}>{{ __('Wholesale Bandwidth Invoice') }}</option>
                        <option value="MANUAL" {{ ($selectedPurpose ?? '') === 'MANUAL' ? 'selected' : '' }}>{{ __('Manual Web Payment') }}</option>
                    </select>
                </div>

                <!-- Quick Period Preset -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value)"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all" {{ ($period ?? '') === 'all' || empty($period) ? 'selected' : '' }}>{{ __('All Dates') }}</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('Yesterday') }}</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('Last Month') }}</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>{{ __('Custom Range') }}</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="w-full sm:w-36 flex-shrink-0">
                    <input type="date" 
                           name="date_from" 
                           id="dateFromInput"
                           value="{{ $dateFrom }}" 
                           onchange="this.form.submit()"
                           title="{{ __('From Date') }}"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500">
                </div>

                <!-- Date To -->
                <div class="w-full sm:w-36 flex-shrink-0">
                    <input type="date" 
                           name="date_to" 
                           id="dateToInput"
                           value="{{ $dateTo }}" 
                           onchange="this.form.submit()"
                           title="{{ __('To Date') }}"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500">
                </div>

                <!-- Billing Month -->
                <div class="w-full sm:w-36 flex-shrink-0">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth !== 'all' ? $selectedMonth : '' }}" 
                           onchange="this.form.submit()"
                           title="{{ __('Billing Month') }}"
                           class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500">
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-center font-semibold">{{ __('Transaction ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Channel') }}</th>
                        <th class="text-center font-semibold">{{ __('Party / Customer') }}</th>
                        <th class="text-center font-semibold">{{ __('Category / Purpose') }}</th>
                        <th class="text-center font-semibold">{{ __('Gross Amount') }}</th>
                        <th class="text-center font-semibold">{{ __('Net Settled') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Date & Time') }}</th>
                        <th class="w-14 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                        <tr>
                            {{-- 0. Row index --}}
                            <td class="text-center font-mono text-slate-500 whitespace-nowrap">{{ $transactions->firstItem() + $index }}</td>

                            {{-- 1. Transaction ID (Clickable to open View Details Modal) --}}
                            <td class="text-center font-mono font-bold text-cyan-800 whitespace-nowrap text-xs">
                                <button type="button" 
                                        @click="viewDetails({{ $trx->id }})" 
                                        class="hover:text-cyan-600 hover:underline cursor-pointer inline-flex items-center gap-1"
                                        title="{{ __('View Full Transaction Details') }}">
                                    <i class="fas fa-receipt text-[10px] text-cyan-600"></i>
                                    <span>{{ $trx->transaction_id }}</span>
                                </button>
                            </td>

                            {{-- 2. Gateway Channel Badge (Center Align) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-semibold border {{ $trx->gateway_badge['class'] }}">
                                    <i class="fas {{ $trx->gateway_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $trx->gateway_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- 3. Subscriber / Reseller Party (Left Align Textual) --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap text-xs">
                                @if($trx->customer)
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-user text-[10px] text-slate-400"></i>
                                        <span>{{ $trx->customer->name }}</span>
                                    </div>
                                @elseif($trx->reseller)
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-network-wired text-[10px] text-purple-600"></i>
                                        <span class="text-purple-800">{{ $trx->reseller->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-500 italic">{{ __('Direct Web User') }}</span>
                                @endif
                            </td>

                            {{-- 4. Category / Purpose (Left Align Textual) --}}
                            <td class="text-left font-medium text-slate-600 whitespace-nowrap text-xs">
                                {{ $trx->purpose_label }}
                            </td>

                            {{-- 5. Gross Amount (Right Align - Accounting Standard) --}}
                            <td class="text-right font-mono font-bold text-slate-900 whitespace-nowrap text-xs">
                                @currency($trx->amount)
                            </td>

                            {{-- 6. Net Settled (Right Align - Accounting Standard) --}}
                            <td class="text-right font-mono font-semibold text-emerald-700 whitespace-nowrap text-xs">
                                @currency($trx->net_amount ?: $trx->amount)
                            </td>

                            {{-- 7. Status Badge (Center Align) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $trx->status_badge['class'] }}">
                                    <i class="fas {{ $trx->status_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $trx->status_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- 8. Date & Time (Center Align) --}}
                            <td class="text-center font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ $trx->created_at->translatedFormat('d M Y, h:i A') }}
                            </td>

                            {{-- 9. Action 3-Dot Button (Center Align) --}}
                            <td class="text-center whitespace-nowrap no-sort">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ $trx->toJson() }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 flex items-center justify-center transition cursor-pointer mx-auto"
                                        title="{{ __('Transaction Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[11px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-10 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center mx-auto text-lg shadow-2xs">
                                        <i class="fas fa-receipt text-slate-400"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <h3 class="text-xs font-semibold text-slate-700">{{ __('No Gateway Transactions Found') }}</h3>
                                        <p class="text-[11px] text-slate-400 font-normal">{{ __('No digital gateway payments match your selected filter criteria.') }}</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Pagination --}}
        @if($transactions->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/60 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    {{ __('Showing :from to :to of :total transactions', ['from' => $transactions->firstItem() ?? 0, 'to' => $transactions->lastItem() ?? 0, 'total' => $transactions->total()]) }}
                </div>
                <div>
                    {{ $transactions->links() }}
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
        
        {{-- Group 1: Diagnostics & Audit --}}
        <div class="py-1">
            <button type="button" @click="viewDetails(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-eye w-3.5 text-cyan-600 text-[11px]"></i>
                <span>{{ __('View Full Details') }}</span>
            </button>
            <button type="button" @click="copyTrxId(activeMenu.transaction_id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-copy w-3.5 text-slate-500 text-[11px]"></i>
                <span>{{ __('Copy Internal Trx ID') }}</span>
            </button>
            <template x-if="activeMenu && activeMenu.gateway_trx_id">
                <button type="button" @click="copyTrxId(activeMenu.gateway_trx_id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                    <i class="fas fa-receipt w-3.5 text-indigo-500 text-[11px]"></i>
                    <span>{{ __('Copy Gateway TrxID') }}</span>
                </button>
            </template>
        </div>

        {{-- Group 2: Verification & Reconciliation --}}
        <div class="py-1">
            <template x-if="activeMenu && (activeMenu.status === 'PENDING' || activeMenu.status === 'FAILED')">
                <button type="button" @click="verifyTransaction(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 flex items-center gap-2 text-emerald-700 font-semibold transition cursor-pointer">
                    <i class="fas fa-rotate w-3.5 text-emerald-600 text-[11px]"></i>
                    <span>{{ __('Verify & Re-query PGW') }}</span>
                </button>
            </template>

            <template x-if="activeMenu && activeMenu.status === 'SUCCESS'">
                <button type="button" @click="openRefundModal(activeMenu); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                    <i class="fas fa-rotate-left w-3.5 text-rose-500 text-[11px]"></i>
                    <span>{{ __('Process Refund / Reversal') }}</span>
                </button>
            </template>
        </div>
    </div>

    {{-- MODAL 1: Full Transaction Telemetry & Detailed Audit Modal --}}
    <div x-show="showDetailModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showDetailModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-waveform"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Online Gateway Transaction Details') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Real-time gateway payload, fee settlement & party audit') }}</p>
                    </div>
                </div>
                <button type="button" @click="showDetailModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 text-xs overflow-y-auto flex-1">
                <template x-if="detailData">
                    <div class="space-y-4">
                        {{-- 3-Column Financial Metric Cards --}}
                        <div class="grid grid-cols-3 gap-2.5">
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-slate-500 block">{{ __('Gross Amount') }}</span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="formatCurrency(detailData.transaction.amount)"></span>
                            </div>
                            <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-amber-700 block">{{ __('Gateway Fee') }}</span>
                                <span class="text-sm font-bold font-mono text-amber-800 block" x-text="formatCurrency(detailData.transaction.fee_amount)"></span>
                            </div>
                            <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-emerald-700 block">{{ __('Net Settlement') }}</span>
                                <span class="text-sm font-bold font-mono text-emerald-800 block" x-text="formatCurrency(detailData.transaction.net_amount)"></span>
                            </div>
                        </div>

                        {{-- Metadata Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Internal Trx ID:') }}</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="detailData.transaction.transaction_id"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Gateway Trx ID:') }}</span>
                                    <span class="font-mono font-semibold text-slate-800" x-text="detailData.transaction.gateway_trx_id"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Gateway Channel:') }}</span>
                                    <span class="font-semibold text-slate-800" x-text="detailData.transaction.gateway_badge?.label || detailData.transaction.gateway"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Category / Purpose:') }}</span>
                                    <span class="text-slate-800 font-medium" x-text="detailData.transaction.purpose_label"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Reference / Invoice:') }}</span>
                                    <span class="font-mono font-semibold text-slate-700" x-text="detailData.transaction.reference_id"></span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Payment Status:') }}</span>
                                    <span class="font-bold uppercase" :class="detailData.transaction.status === 'SUCCESS' ? 'text-emerald-700' : (detailData.transaction.status === 'PENDING' ? 'text-amber-700' : 'text-rose-700')" x-text="detailData.transaction.status_badge?.label || detailData.transaction.status"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Payer Account / Phone:') }}</span>
                                    <span class="font-mono text-slate-800 font-semibold" x-text="detailData.transaction.payer_account"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('IP Address:') }}</span>
                                    <span class="font-mono text-slate-700" x-text="detailData.transaction.ip_address"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Timestamp:') }}</span>
                                    <span class="font-mono text-slate-700" x-text="detailData.transaction.created_at"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">{{ __('Gateway Message:') }}</span>
                                    <span class="text-slate-700 truncate max-w-[180px]" x-text="detailData.transaction.status_message" :title="detailData.transaction.status_message"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Linked Subscriber / Reseller Card --}}
                        <template x-if="detailData.transaction.customer">
                            <div class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-200/80">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-800 block mb-1">{{ __('Subscriber Details:') }}</span>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs" x-text="detailData.transaction.customer.name"></p>
                                        <p class="text-[11px] text-slate-600">{{ __('ID:') }} <span class="font-mono font-semibold" x-text="detailData.transaction.customer.customer_id"></span> | {{ __('Phone:') }} <span x-text="detailData.transaction.customer.phone"></span></p>
                                        <p class="text-[11px] text-slate-500" x-text="detailData.transaction.customer.address"></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-100 text-cyan-800">{{ __('Direct Subscriber') }}</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="detailData.transaction.reseller">
                            <div class="p-3 bg-purple-50/50 rounded-lg border border-purple-200/80">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800 block mb-1">{{ __('Sub-ISP Partner Details:') }}</span>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs" x-text="detailData.transaction.reseller.name"></p>
                                        <p class="text-[11px] text-slate-600">{{ __('Code:') }} <span class="font-mono font-semibold" x-text="detailData.transaction.reseller.code"></span> | {{ __('Phone:') }} <span x-text="detailData.transaction.reseller.mobile"></span></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-800">{{ __('Sub-ISP Partner') }}</span>
                                </div>
                            </div>
                        </template>

                        {{-- Raw Gateway JSON Log --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('Gateway API Payload & Webhook Telemetry:') }}</span>
                                <button type="button" @click="copyPayload()" class="text-cyan-600 hover:text-cyan-700 text-[10.5px] font-semibold inline-flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-copy"></i> {{ __('Copy JSON') }}
                                </button>
                            </div>
                            <pre class="bg-slate-900 text-cyan-300 font-mono text-[11px] p-3.5 rounded-lg border border-slate-800 overflow-x-auto max-h-48 whitespace-pre-wrap select-all" x-text="JSON.stringify(detailData.transaction.gateway_payload || { status: detailData.transaction.status, message: detailData.transaction.status_message, channel: detailData.transaction.gateway, trx_id: detailData.transaction.gateway_trx_id }, null, 2)"></pre>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                <template x-if="detailData && (detailData.transaction.status === 'PENDING' || detailData.transaction.status === 'FAILED')">
                    <button type="button" @click="verifyTransaction(detailData.transaction.id); showDetailModal = false;" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-rotate text-[10px]"></i>
                        <span>{{ __('Re-query Gateway API') }}</span>
                    </button>
                </template>

                <template x-if="detailData && detailData.transaction.status === 'SUCCESS'">
                    <button type="button" @click="openRefundModal(detailData.transaction); showDetailModal = false;" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-medium text-xs px-3.5 py-1.5 rounded-lg transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Refund Transaction') }}</span>
                    </button>
                </template>

                <button type="button" @click="showDetailModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Process Refund / Reversal Modal --}}
    <div x-show="showRefundModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showRefundModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Process Transaction Refund') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Reverse cleared transaction and record audit justification') }}</p>
                    </div>
                </div>
                <button type="button" @click="showRefundModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitRefund()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-3 bg-rose-50/60 rounded-lg border border-rose-200/80 space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-rose-700">{{ __('Transaction ID:') }}</span>
                            <span class="font-mono font-bold text-slate-900" x-text="refundForm.transaction_id"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-rose-700">{{ __('Refund Amount:') }}</span>
                            <span class="font-mono font-bold text-rose-800" x-text="formatCurrency(refundForm.amount)"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Reason for Refund / Reversal') }} <span class="text-rose-500">*</span></label>
                        <textarea x-model="refundForm.reason" rows="3" required placeholder="{{ __('e.g. Duplicate customer payment charged via bKash gateway...') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showRefundModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>{{ __('Confirm Refund') }}</span>
                    </button>
                </div>
            </form>
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

    function gatewayTransactionsManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showDetailModal: false,
            showRefundModal: false,
            isSubmitting: false,
            detailData: null,

            i18n: {
                errorTitle: @json(__('Error')),
                fetchDetailsError: @json(__('Could not fetch transaction details.')),
                genericError: @json(__('Error: :error')),
                verifyConfirmTitle: @json(__('Verify Transaction Status?')),
                verifyConfirmText: @json(__('Re-query authoritative Payment Gateway for live clearance status?')),
                verifyConfirmBtn: @json(__('Yes, Verify Now')),
                cancelBtn: @json(__('Cancel')),
                statusVerifiedTitle: @json(__('Status Verified')),
                verificationResultTitle: @json(__('Verification Result')),
                verifyFailedText: @json(__('Transaction could not be verified.')),
                errorVerifying: @json(__('Error verifying transaction: :error')),
                refundProcessedTitle: @json(__('Refund Processed')),
                refundFailedTitle: @json(__('Refund Failed')),
                refundFailedText: @json(__('Refund processing failed.')),
                errorRefunding: @json(__('Error processing refund: :error')),
                trxCopied: @json(__('Transaction ID copied: :id')),
                payloadCopied: @json(__('Gateway JSON payload copied to clipboard!'))
            },

            refundForm: {
                id: null,
                transaction_id: '',
                amount: 0,
                reason: ''
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

            async viewDetails(id) {
                try {
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + id;
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.detailData = data;
                        this.showDetailModal = true;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: this.i18n.errorTitle,
                            text: this.i18n.fetchDetailsError
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.errorTitle,
                        text: this.i18n.genericError.replace(':error', e.message)
                    });
                }
            },

            async verifyTransaction(id) {
                const result = await Swal.fire({
                    title: this.i18n.verifyConfirmTitle,
                    text: this.i18n.verifyConfirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: this.i18n.verifyConfirmBtn,
                    cancelButtonText: this.i18n.cancelBtn,
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
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + id + "/verify";
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
                            title: this.i18n.statusVerifiedTitle,
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: this.i18n.verificationResultTitle,
                            text: data.message || this.i18n.verifyFailedText
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.errorTitle,
                        text: this.i18n.errorVerifying.replace(':error', e.message)
                    });
                }
            },

            openRefundModal(trx) {
                this.refundForm = {
                    id: trx.id,
                    transaction_id: trx.transaction_id,
                    amount: parseFloat(trx.amount) || 0,
                    reason: ''
                };
                this.showRefundModal = true;
            },

            async submitRefund() {
                this.isSubmitting = true;
                try {
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + this.refundForm.id + "/refund";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reason: this.refundForm.reason })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showRefundModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: this.i18n.refundProcessedTitle,
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: this.i18n.refundFailedTitle,
                            text: data.message || this.i18n.refundFailedText
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.errorTitle,
                        text: this.i18n.errorRefunding.replace(':error', e.message)
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            copyTrxId(id) {
                navigator.clipboard.writeText(id);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: this.i18n.trxCopied.replace(':id', id),
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            },

            copyPayload() {
                if (this.detailData?.transaction?.gateway_payload) {
                    navigator.clipboard.writeText(JSON.stringify(this.detailData.transaction.gateway_payload, null, 2));
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: this.i18n.payloadCopied,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'rounded-xl text-xs font-sans shadow-lg'
                        }
                    });
                }
            },

            formatNumber(num) {
                return (parseFloat(num) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            formatCurrency(amount) {
                const sym = @json($currencySymbol ?? '৳');
                return sym + ' ' + this.formatNumber(amount);
            }
        };
    }
</script>
@endpush
