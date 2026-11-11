@extends('tenant.layouts.app')

@section('title', __('Reseller Panel Licenses') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="panelLicensesManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- Floating Global Toast Notification --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-3.5 py-2 rounded-xl shadow-lg border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Actions ONLY - No Subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs shrink-0">
                <i class="fas fa-key"></i>
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight whitespace-nowrap">{{ __('Panel Licenses') }}</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __(':count Tracked', ['count' => $totalResellersCount]) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap sm:flex-nowrap shrink-0">
            {{-- Link to Subscription Invoices --}}
            <a href="{{ route('tenant.resellers.invoices') }}" 
               class="h-[34px] bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5"
               title="{{ __('View Subscription Billing Invoices') }}">
                <i class="fas fa-file-invoice-dollar text-slate-500 text-[10px]"></i>
                <span class="hidden md:inline">{{ __('Invoices') }}</span>
            </a>

            {{-- Generate Monthly Bills Button --}}
            <button type="button" 
                    @click="openBulkBillModal()" 
                    class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-wand-magic-sparkles text-cyan-600 text-[10px]"></i>
                <span>{{ __('Generate Invoices') }}</span>
            </button>

            {{-- Renew / Extend License Button --}}
            <button type="button" 
                    @click="openRenewModal()" 
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>{{ __('Renew License') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strict AGENTS.md Rule 2.B: Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Software Monthly MRR --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Monthly MRR') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">@currency($totalMonthlyMRR)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-laptop-code"></i>
            </div>
        </div>

        {{-- Card 2: Active Licenses --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">{{ __('Active Licenses') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($activePanelsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Expiring Soon --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">{{ __('Expiring (≤ 7d)') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">{{ number_format($expiringSoonCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 4: Expired Panels --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">{{ __('Expired Panels') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700 truncate">{{ number_format($expiredPanelsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 5: Grace Period --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">{{ __('Grace Period') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">{{ number_format($gracePeriodCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        {{-- Card 6: Total Resellers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Tracked') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800 truncate">{{ number_format($totalResellersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-100 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-handshake"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Strict Customer-Standard 2-Tier Filter System) --}}
    <form method="GET" action="{{ route('tenant.resellers.subscriptions') }}" 
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
          x-data="{ 
              showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
              searchQuery: '{{ addslashes(request('search', '')) }}' 
          }">
        
        {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] controls) --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            {{-- Intelligent Live Search Box --}}
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors duration-200"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery" 
                       placeholder="{{ __('Search Reseller Name, Code, Prefix, Contact, Mobile...') }}" 
                       value="{{ request('search') }}"
                       autocomplete="off"
                       class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                <button type="button" 
                        x-show="searchQuery && searchQuery.length > 0" 
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                        style="display: none;">
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            </div>

            {{-- Primary Filter: Panel Status --}}
            <select name="panel_status" 
                    class="h-[34px] w-full md:w-44 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="">{{ __('All Panel Statuses') }}</option>
                <option value="active" {{ request('panel_status') === 'active' ? 'selected' : '' }}>{{ __('Active Licenses') }}</option>
                <option value="expiring_soon" {{ request('panel_status') === 'expiring_soon' ? 'selected' : '' }}>{{ __('Expiring Soon (≤ 7d)') }}</option>
                <option value="expired" {{ request('panel_status') === 'expired' ? 'selected' : '' }}>{{ __('Expired Panels') }}</option>
                <option value="grace_period" {{ request('panel_status') === 'grace_period' ? 'selected' : '' }}>{{ __('Grace Period') }}</option>
            </select>

            {{-- Pagination Selector (per_page) --}}
            @php $perPageVal = (int)request('per_page', 20); @endphp
            <select name="per_page" 
                    class="h-[34px] w-full md:w-28 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="10" {{ $perPageVal === 10 ? 'selected' : '' }}>10 / {{ __('page') }}</option>
                <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / {{ __('page') }}</option>
                <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / {{ __('page') }}</option>
                <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / {{ __('page') }}</option>
            </select>

            {{-- More Filters Accordion Toggle Button --}}
            <button type="button" 
                    @click="showMoreFilters = !showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                    :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50/70 border-cyan-200 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                <span>{{ __('More Filters') }}</span>
                @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                @endif
                <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
            </button>

            {{-- Strict Filter & Reset Button Sequence (Filter First, Reset Second - Always Visible) --}}
            <div class="flex items-center gap-1.5 shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>{{ __('Filter') }}</span>
                </button>

                <a href="{{ route('tenant.resellers.subscriptions') }}" 
                   class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>{{ __('Reset') }}</span>
                </a>
            </div>
        </div>

        {{-- Tier 2: Secondary Filters Drawer --}}
        <div x-show="showMoreFilters" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            
            {{-- Reseller Dropdown Filter --}}
            <div class="w-full sm:w-auto">
                <select name="reseller_id" 
                        class="h-[34px] w-full sm:w-56 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Resellers') }}</option>
                    @foreach($allResellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Billing Model Filter --}}
            <div class="w-full sm:w-auto">
                <select name="billing_type" 
                        class="h-[34px] w-full sm:w-48 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Billing Models') }}</option>
                    <option value="PREPAID_WALLET" {{ request('billing_type') === 'PREPAID_WALLET' ? 'selected' : '' }}>{{ __('Prepaid Wallet') }}</option>
                    <option value="POSTPAID_MONTHLY" {{ request('billing_type') === 'POSTPAID_MONTHLY' ? 'selected' : '' }}>{{ __('Postpaid Monthly') }}</option>
                    <option value="BANDWIDTH_WHOLESALE" {{ request('billing_type') === 'BANDWIDTH_WHOLESALE' ? 'selected' : '' }}>{{ __('Bandwidth Wholesale') }}</option>
                </select>
            </div>
        </div>
    </form>

    {{-- 4. Master Compact Table (Pure CSS System Compliant - Strict AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                <i class="fas fa-key text-slate-400 text-xs"></i>
                <span>{{ __('Panel Licenses & Software Subscriptions') }}</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                {{ __('Total :count Records', ['count' => $resellers->total()]) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table w-full">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('License Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Code') }}</th>
                        <th class="text-center font-semibold">{{ __('Reseller Name') }}</th>
                        <th class="text-center font-semibold">{{ __('Prefix') }}</th>
                        <th class="text-center font-semibold">{{ __('Billing Model') }}</th>
                        <th class="text-center font-semibold">{{ __('Monthly Panel Fee') }}</th>
                        <th class="text-center font-semibold">{{ __('Expiry Date') }}</th>
                        <th class="text-center font-semibold">{{ __('Days Remaining') }}</th>
                        <th class="text-center font-semibold">{{ __('Wallet Balance') }}</th>
                        <th class="w-10 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $index => $res)
                        @php
                            $today = \Carbon\Carbon::today();
                            $expiryDate = $res->panel_expiry_date ? \Carbon\Carbon::parse($res->panel_expiry_date) : null;
                            $daysLeft = $expiryDate ? $today->diffInDays($expiryDate, false) : null;
                            $billingBadge = $res->billing_badge;
                            $panelBadge = $res->panel_badge;

                            $resPayload = [
                                'id' => $res->id,
                                'name' => $res->name,
                                'code' => $res->code,
                                'prefix' => $res->prefix,
                                'contact_person' => $res->contact_person,
                                'mobile' => $res->mobile,
                                'monthly_panel_charge' => (float)$res->monthly_panel_charge,
                                'panel_expiry_date' => $res->panel_expiry_date ? substr((string)$res->panel_expiry_date, 0, 10) : null,
                                'panel_billing_status' => $res->panel_billing_status,
                                'wallet_balance' => (float)$res->wallet_balance,
                                'billing_type' => $res->billing_type,
                            ];
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $resellers->firstItem() + $index }}
                            </td>

                            {{-- 2. License Status --}}
                            <td class="text-center whitespace-nowrap">
                                @if($res->panel_billing_status === 'ACTIVE' && ($expiryDate === null || $daysLeft >= 0))
                                    @if($daysLeft !== null && $daysLeft <= 7)
                                        <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>{{ __('Expiring Soon') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ __('Active') }}</span>
                                        </span>
                                    @endif
                                @elseif($res->panel_billing_status === 'GRACE_PERIOD')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                        <span>{{ __('Grace Period') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>{{ __('Expired') }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 3. Code --}}
                            <td class="text-center font-mono text-cyan-800 font-semibold whitespace-nowrap">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($resPayload) }})" 
                                        class="hover:underline cursor-pointer">
                                    {{ $res->code }}
                                </button>
                            </td>

                            {{-- 4. Reseller Name --}}
                            <td class="text-left text-slate-800 font-medium whitespace-nowrap">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($resPayload) }})" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $res->name }}
                                </button>
                            </td>

                            {{-- 5. Prefix --}}
                            <td class="text-center font-mono text-purple-700 whitespace-nowrap">
                                {{ $res->prefix ? $res->prefix . '_*' : '--' }}
                            </td>

                            {{-- 6. Billing Model --}}
                            <td class="text-center font-mono text-[10px] whitespace-nowrap">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $billingBadge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ __($billingBadge['label'] ?? $res->billing_type) }}
                                </span>
                            </td>

                            {{-- 7. Monthly Panel Fee --}}
                            <td class="text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                @currency($res->monthly_panel_charge)
                            </td>

                            {{-- 8. Expiry Date --}}
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                @if($expiryDate)
                                    <span>{{ $expiryDate->format('d M, Y') }}</span>
                                @else
                                    <span class="text-slate-400">{{ __('Unlimited / Unset') }}</span>
                                @endif
                            </td>

                            {{-- 9. Days Remaining --}}
                            <td class="text-center font-mono font-semibold whitespace-nowrap">
                                @if($expiryDate)
                                    @if($daysLeft > 7)
                                        <span class="text-emerald-600">{{ $daysLeft }} {{ __('days') }}</span>
                                    @elseif($daysLeft >= 0)
                                        <span class="text-amber-600">{{ $daysLeft }} {{ __('days left') }}</span>
                                    @else
                                        <span class="text-rose-600">{{ __(':days days overdue', ['days' => abs($daysLeft)]) }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            {{-- 10. Wallet Balance --}}
                            <td class="text-right font-mono text-emerald-700 font-bold whitespace-nowrap">
                                @currency($res->wallet_balance)
                            </td>

                            {{-- 11. Actions (3-Dot Action Button) --}}
                            <td class="w-10 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($resPayload) }}, $event)" 
                                        class="lic-action-btn w-6 h-6 mx-auto rounded-md hover:bg-slate-100 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-2xs">
                                        <i class="fas fa-key"></i>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800">{{ __('No Panel Licenses Found') }}</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">{{ __('Add resellers under Reseller Directory to manage licenses and software billing.') }}</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.resellers.index') }}" 
                                           class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-handshake text-[10px]"></i>
                                            <span>{{ __('Open Reseller Directory') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Bar --}}
        @if($resellers->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-[11px] text-slate-500 font-normal">
                    {{ __('Showing :first to :last of :total licenses', ['first' => $resellers->firstItem(), 'last' => $resellers->lastItem(), 'total' => $resellers->total()]) }}
                </span>
                <div>
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Global Floating 3-Dot Action Menu (Strict AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 text-xs divide-y divide-slate-100 font-normal"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         style="display: none;">

        {{-- Group 1: Subscription Actions --}}
        <div class="py-0.5">
            <button type="button" 
                    @click="openRenewModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-cyan-700 flex items-center gap-2 transition cursor-pointer text-[11px]">
                <i class="fas fa-arrows-rotate w-3.5 text-cyan-600 text-[10px]"></i>
                <span>{{ __('Renew License') }}</span>
            </button>
            <button type="button" 
                    @click="openEditSettingsModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-cyan-700 flex items-center gap-2 transition cursor-pointer text-[11px]">
                <i class="fas fa-gear w-3.5 text-slate-400 text-[10px]"></i>
                <span>{{ __('Edit License Settings') }}</span>
            </button>
            <a :href="`{{ route('tenant.resellers.invoices') }}?reseller_id=${activeMenu?.id || ''}`" 
               class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-cyan-700 flex items-center gap-2 transition cursor-pointer text-[11px]">
                <i class="fas fa-file-invoice-dollar w-3.5 text-slate-400 text-[10px]"></i>
                <span>{{ __('View Reseller Invoices') }}</span>
            </a>
        </div>

        {{-- Group 2: Access & Diagnostics --}}
        <div class="py-0.5">
            <button type="button" 
                    @click="togglePanelStatus(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer text-[11px]"
                    :class="activeMenu?.panel_billing_status === 'ACTIVE' ? 'hover:text-rose-600' : 'hover:text-emerald-600'">
                <i class="fas w-3.5 text-[10px]" :class="activeMenu?.panel_billing_status === 'ACTIVE' ? 'fa-pause text-rose-500' : 'fa-play text-emerald-500'"></i>
                <span x-text="activeMenu?.panel_billing_status === 'ACTIVE' ? i18n.setExpiredBlock : i18n.activatePanelAccess"></span>
            </button>
            <button type="button" 
                    @click="openDetailsModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-cyan-700 flex items-center gap-2 transition cursor-pointer text-[11px]">
                <i class="fas fa-circle-info w-3.5 text-slate-400 text-[10px]"></i>
                <span>{{ __('Inspect License Specs') }}</span>
            </button>
        </div>
    </div>

    {{-- 6. MODAL 1: Renew / Extend Software Subscription --}}
    <div x-show="modals.renew" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="modals.renew = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-arrows-rotate"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Renew Reseller License') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Extend panel software subscription and generate invoice') }}</p>
                    </div>
                </div>
                <button type="button" @click="modals.renew = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Modal Form Body --}}
            <div class="p-4 space-y-3.5 text-xs">
                {{-- Reseller Selection --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Select Reseller') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="renewForm.reseller_id" 
                            @change="onResellerChange()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        <option value="">{{ __('-- Choose Reseller --') }}</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}" 
                                    data-fee="{{ $r->monthly_panel_charge }}"
                                    data-expiry="{{ $r->panel_expiry_date }}"
                                    data-wallet="{{ $r->wallet_balance }}"
                                    data-credit="{{ $r->credit_limit }}">
                                {{ $r->name }} ({{ $r->code }}) • {{ __('Fee') }}: @currency($r->monthly_panel_charge)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reseller Summary Card if Selected --}}
                <div x-show="selectedResellerMeta" class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Current Expiry') }}:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedResellerMeta?.expiry || i18n.unsetUnlimited"></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Monthly Panel Fee') }}:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatCurrency(selectedResellerMeta?.fee || 0)"></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Wallet Available') }}:</span>
                        <span class="font-mono font-semibold text-emerald-600" x-text="formatCurrency(selectedResellerMeta?.wallet || 0)"></span>
                    </div>
                </div>

                {{-- Duration in Months --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Renewal Months') }} <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="renewForm.months" 
                                @change="recalcRenewAmount()"
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="1">{{ __('1 Month') }}</option>
                            <option value="2">{{ __('2 Months') }}</option>
                            <option value="3">{{ __('3 Months (Quarterly)') }}</option>
                            <option value="6">{{ __('6 Months (Half-Yearly)') }}</option>
                            <option value="12">{{ __('12 Months (1 Year)') }}</option>
                            <option value="24">{{ __('24 Months (2 Years)') }}</option>
                        </select>
                    </div>

                    {{-- Total Amount --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Total Bill') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               x-model="renewForm.amount" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-900 focus:bg-white focus:border-cyan-500 outline-hidden transition" />
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Settlement Method') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="renewForm.payment_method" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        <option value="WALLET_DEDUCT">{{ __('Deduct from Reseller Wallet') }}</option>
                        <option value="CASH">{{ __('Paid in Cash') }}</option>
                        <option value="BKASH">{{ __('bKash Merchant / Personal') }}</option>
                        <option value="NAGAD">{{ __('Nagad Payment') }}</option>
                        <option value="BANK">{{ __('Bank Transfer / Deposit') }}</option>
                        <option value="DUE">{{ __('Mark as Due Invoice') }}</option>
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Reference / Note') }}</label>
                    <input type="text" 
                           x-model="renewForm.notes" 
                           placeholder="{{ __('e.g. Special software license renewal voucher') }}" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.renew = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        @click="submitRenew()" 
                        :disabled="saving"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': saving }"></i>
                    <span x-text="saving ? i18n.processing : i18n.confirmRenewal"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- 7. MODAL 2: Edit License Settings (Monthly Fee & Expiry) --}}
    <div x-show="modals.editSettings" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="modals.editSettings = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-gear"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Edit License Terms') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="editSettingsForm.resellerName"></p>
                    </div>
                </div>
                <button type="button" @click="modals.editSettings = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Modal Form Body --}}
            <div class="p-4 space-y-3.5 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Monthly Panel Fee') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" min="0" 
                           x-model="editSettingsForm.monthly_panel_charge" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-900 focus:bg-white focus:border-cyan-500 outline-hidden transition" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Panel Expiry Date') }}
                    </label>
                    <input type="date" 
                           x-model="editSettingsForm.panel_expiry_date" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Billing Status') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="editSettingsForm.panel_billing_status" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        <option value="ACTIVE">{{ __('ACTIVE (Authorized Access)') }}</option>
                        <option value="EXPIRED">{{ __('EXPIRED (Suspended Access)') }}</option>
                        <option value="GRACE_PERIOD">{{ __('GRACE_PERIOD (Temporary Warning)') }}</option>
                    </select>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.editSettings = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        @click="submitEditSettings()" 
                        :disabled="saving"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': saving }"></i>
                    <span x-text="saving ? i18n.saving : i18n.saveSettings"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- 8. MODAL 3: Bulk Generate Monthly Invoices --}}
    <div x-show="modals.bulkBill" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="modals.bulkBill = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Generate Monthly Invoices') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Automate monthly software licensing bills for resellers') }}</p>
                    </div>
                </div>
                <button type="button" @click="modals.bulkBill = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Modal Form Body --}}
            <div class="p-4 space-y-3.5 text-xs">
                <div class="p-3 bg-cyan-50/60 rounded-lg border border-cyan-100 text-slate-700 text-xs space-y-1">
                    <div class="font-semibold text-cyan-900 flex items-center gap-1.5">
                        <i class="fas fa-circle-info text-cyan-600"></i>
                        <span>{{ __('Automated Billing Logic') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600">{{ __('This action scans all resellers with active monthly panel fees (> 0) and creates a monthly license invoice for the selected billing month.') }}</p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Billing Month') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="month" 
                           x-model="bulkBillForm.billing_month" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-900 focus:bg-white focus:border-cyan-500 outline-hidden transition" />
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.bulkBill = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        @click="submitBulkBill()" 
                        :disabled="saving"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-wand-magic-sparkles text-[10px]" :class="{ 'fa-spin fa-spinner': saving }"></i>
                    <span x-text="saving ? i18n.generating : i18n.generateInvoices"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- 9. MODAL 4: Reseller License Details --}}
    <div x-show="modals.details" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="modals.details = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800" x-text="detailsItem?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-mono font-normal" x-text="detailsItem?.code"></p>
                    </div>
                </div>
                <button type="button" @click="modals.details = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-2 text-xs">
                <div class="bg-slate-50 rounded-lg border border-slate-200 divide-y divide-slate-100">
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Contact Person') }}:</span>
                        <span class="font-medium text-slate-800" x-text="detailsItem?.contact_person || '--'"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Mobile Phone') }}:</span>
                        <span class="font-mono text-slate-800" x-text="detailsItem?.mobile || '--'"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('PPPoE Prefix') }}:</span>
                        <span class="font-mono text-purple-700" x-text="detailsItem?.prefix ? (detailsItem.prefix + '_*') : '--'"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Monthly Panel Fee') }}:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatCurrency(detailsItem?.monthly_panel_charge || 0)"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('License Expiry Date') }}:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="detailsItem?.panel_expiry_date || i18n.unsetUnlimited"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Billing Model') }}:</span>
                        <span class="font-mono text-slate-800" x-text="billingTypeLabel(detailsItem?.billing_type)"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Wallet Balance') }}:</span>
                        <span class="font-mono font-bold text-emerald-600" x-text="formatCurrency(detailsItem?.wallet_balance || 0)"></span>
                    </div>
                    <div class="px-3 py-2 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500">{{ __('Panel Access Status') }}:</span>
                        <span class="font-mono font-semibold" 
                              :class="detailsItem?.panel_billing_status === 'ACTIVE' ? 'text-emerald-600' : 'text-rose-600'" 
                              x-text="panelStatusLabel(detailsItem?.panel_billing_status)"></span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="modals.details = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function panelLicensesManager() {
    return {
        currencySymbol: @js($currencySymbol ?? '৳'),
        currencyPosition: @js($currencyPosition ?? 'left'),
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '10px', left: 'auto' },
        saving: false,
        toast: { show: false, message: '', type: 'success' },
        modals: {
            renew: false,
            editSettings: false,
            bulkBill: false,
            details: false
        },
        selectedResellerMeta: null,
        detailsItem: null,
        renewForm: {
            reseller_id: '',
            months: 1,
            amount: 0,
            payment_method: 'WALLET_DEDUCT',
            notes: ''
        },
        editSettingsForm: {
            reseller_id: null,
            resellerName: '',
            monthly_panel_charge: 0,
            panel_expiry_date: '',
            panel_billing_status: 'ACTIVE'
        },
        bulkBillForm: {
            billing_month: '{{ date('Y-m') }}'
        },
        i18n: {
            setExpiredBlock: @js(__('Set to Expired / Block')),
            activatePanelAccess: @js(__('Activate Panel Access')),
            unsetUnlimited: @js(__('Unset / Unlimited')),
            processing: @js(__('Processing...')),
            confirmRenewal: @js(__('Confirm Renewal')),
            saving: @js(__('Saving...')),
            saveSettings: @js(__('Save Settings')),
            generating: @js(__('Generating...')),
            generateInvoices: @js(__('Generate Invoices')),
            selectResellerError: @js(__('Please select a reseller.')),
            licenseRenewedSuccess: @js(__('License renewed successfully!')),
            renewError: @js(__('Error renewing license.')),
            renewNetworkError: @js(__('Network error while processing renewal.')),
            settingsSavedSuccess: @js(__('Settings saved successfully!')),
            settingsSaveError: @js(__('Error saving settings.')),
            settingsNetworkError: @js(__('Network error while saving settings.')),
            invoicesGeneratedSuccess: @js(__('Invoices generated successfully!')),
            invoicesGenerateError: @js(__('Error generating invoices.')),
            invoicesNetworkError: @js(__('Network error generating bills.')),
            statusUpdatedSuccess: @js(__('Status updated!')),
            statusUpdateError: @js(__('Error updating status.')),
            statusNetworkError: @js(__('Network error toggling status.')),
            prepaidWallet: @js(__('Prepaid Wallet')),
            postpaidMonthly: @js(__('Postpaid Monthly')),
            bandwidthWholesale: @js(__('Bandwidth Wholesale')),
            statusActive: @js(__('ACTIVE')),
            statusExpired: @js(__('EXPIRED')),
            statusGracePeriod: @js(__('GRACE_PERIOD'))
        },

        init() {
            const flashSuccess = @json(session('success'));
            const flashError = @json(session('error'));
            if (flashSuccess) {
                this.showToast(flashSuccess, 'success');
            } else if (flashError) {
                this.showToast(flashError, 'error');
            }
        },

        formatCurrency(amount) {
            const val = parseFloat(amount) || 0;
            const formatted = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return this.currencyPosition === 'right'
                ? `${formatted} ${this.currencySymbol}`
                : `${this.currencySymbol} ${formatted}`;
        },

        billingTypeLabel(type) {
            if (type === 'PREPAID_WALLET') return this.i18n.prepaidWallet;
            if (type === 'POSTPAID_MONTHLY') return this.i18n.postpaidMonthly;
            if (type === 'BANDWIDTH_WHOLESALE') return this.i18n.bandwidthWholesale;
            return type || '--';
        },

        panelStatusLabel(status) {
            if (status === 'ACTIVE') return this.i18n.statusActive;
            if (status === 'EXPIRED') return this.i18n.statusExpired;
            if (status === 'GRACE_PERIOD') return this.i18n.statusGracePeriod;
            return status || '--';
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        toggleMenu(item, event) {
            if (this.activeMenu && this.activeMenu.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;

            const targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
            const dropdownHeight = 200;
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

        openRenewModal(item = null) {
            if (item) {
                this.renewForm.reseller_id = String(item.id);
                this.selectedResellerMeta = {
                    fee: item.monthly_panel_charge,
                    expiry: item.panel_expiry_date,
                    wallet: item.wallet_balance
                };
                this.renewForm.months = 1;
                this.renewForm.amount = item.monthly_panel_charge || 0;
            } else {
                this.renewForm.reseller_id = '';
                this.selectedResellerMeta = null;
                this.renewForm.months = 1;
                this.renewForm.amount = 0;
            }
            this.renewForm.payment_method = 'WALLET_DEDUCT';
            this.renewForm.notes = '';
            this.modals.renew = true;
        },

        onResellerChange() {
            const selectEl = document.querySelector('select[x-model="renewForm.reseller_id"]');
            if (!selectEl) return;
            const opt = selectEl.options[selectEl.selectedIndex];
            if (!opt || !opt.value) {
                this.selectedResellerMeta = null;
                this.renewForm.amount = 0;
                return;
            }
            const fee = parseFloat(opt.getAttribute('data-fee') || 0);
            const expiry = opt.getAttribute('data-expiry') || '';
            const wallet = parseFloat(opt.getAttribute('data-wallet') || 0);
            this.selectedResellerMeta = { fee, expiry, wallet };
            this.recalcRenewAmount();
        },

        recalcRenewAmount() {
            const fee = this.selectedResellerMeta?.fee || 0;
            const months = parseInt(this.renewForm.months) || 1;
            this.renewForm.amount = (fee * months).toFixed(2);
        },

        async submitRenew() {
            if (!this.renewForm.reseller_id) {
                this.showToast(this.i18n.selectResellerError, 'error');
                return;
            }
            this.saving = true;
            try {
                const res = await fetch(`{{ route('tenant.resellers.subscriptions.renew') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.renewForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || this.i18n.licenseRenewedSuccess, 'success');
                    this.modals.renew = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.renewError, 'error');
                }
            } catch (e) {
                this.showToast(this.i18n.renewNetworkError, 'error');
            } finally {
                this.saving = false;
            }
        },

        openEditSettingsModal(item) {
            this.editSettingsForm.reseller_id = item.id;
            this.editSettingsForm.resellerName = `${item.name} (${item.code})`;
            this.editSettingsForm.monthly_panel_charge = item.monthly_panel_charge || 0;
            this.editSettingsForm.panel_expiry_date = item.panel_expiry_date ? String(item.panel_expiry_date).substring(0, 10) : '';
            this.editSettingsForm.panel_billing_status = item.panel_billing_status || 'ACTIVE';
            this.modals.editSettings = true;
        },

        async submitEditSettings() {
            this.saving = true;
            try {
                const res = await fetch(`{{ route('tenant.resellers.subscriptions.update-charge') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.editSettingsForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || this.i18n.settingsSavedSuccess, 'success');
                    this.modals.editSettings = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.settingsSaveError, 'error');
                }
            } catch (e) {
                this.showToast(this.i18n.settingsNetworkError, 'error');
            } finally {
                this.saving = false;
            }
        },

        openBulkBillModal() {
            this.bulkBillForm.billing_month = '{{ date('Y-m') }}';
            this.modals.bulkBill = true;
        },

        async submitBulkBill() {
            this.saving = true;
            try {
                const res = await fetch(`{{ route('tenant.resellers.subscriptions.generate-bills') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.bulkBillForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || this.i18n.invoicesGeneratedSuccess, 'success');
                    this.modals.bulkBill = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.invoicesGenerateError, 'error');
                }
            } catch (e) {
                this.showToast(this.i18n.invoicesNetworkError, 'error');
            } finally {
                this.saving = false;
            }
        },

        async togglePanelStatus(item) {
            const newStatus = item.panel_billing_status === 'ACTIVE' ? 'EXPIRED' : 'ACTIVE';
            try {
                const res = await fetch(`/admin/resellers/subscriptions/${item.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || this.i18n.statusUpdatedSuccess, 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(data.message || this.i18n.statusUpdateError, 'error');
                }
            } catch (e) {
                this.showToast(this.i18n.statusNetworkError, 'error');
            }
        },

        openDetailsModal(item) {
            this.detailsItem = item;
            this.modals.details = true;
        }
    };
}
</script>
@endpush
