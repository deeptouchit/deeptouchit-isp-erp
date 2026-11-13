@extends('tenant.layouts.app')

@section('title', __('Bulk Bill & Due Payments') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Bulk payment dedicated page tweaks */
    .saas-table tbody tr.is-selected {
        background-color: #f0fdf4 !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="bulkPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 leading-none">{{ __('Bulk Bill & Due Payments') }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Print Statement / Demand Sheet -->
            <button type="button" 
                    @click="printSelectedOrAll()" 
                    class="px-3 py-1.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="{{ __('Print Bulk Bill Demand & Due Statement') }}">
                <i class="fas fa-print text-[11px]"></i>
                <span>{{ __('Print Sheet') }}</span>
            </button>

            <!-- Back to Due & Expired Customers -->
            <a href="{{ route('tenant.customers.due') }}" 
               class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>{{ __('Due Customers') }}</span>
            </a>

            <!-- Customer Master -->
            <a href="{{ route('tenant.customers.index') }}" 
               class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>{{ __('All Customers') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Processing Mode -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Scope Mode') }}</span>
                <span class="text-[13px] font-bold text-slate-800 font-mono leading-tight block truncate">
                    {{ $mode === 'reseller' ? ($selectedReseller ? $selectedReseller->code ?: $selectedReseller->name : __('Reseller Scope')) : __('ISP Direct Retail') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas {{ $mode === 'reseller' ? 'fa-handshake' : 'fa-building' }}"></i>
            </div>
        </div>

        <!-- Metric 2: Filtered Subscribers -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-cyan-700 uppercase tracking-wider block truncate">{{ __('Scope Subscribers') }}</span>
                <span class="text-[13px] font-bold text-cyan-700 font-mono leading-tight block">{{ number_format($kpis['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 3: Total Gross Bill -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Total Gross Bill') }}</span>
                <span class="text-[13px] font-bold text-amber-700 font-mono leading-tight block">@currency($kpis['gross_bill'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Metric 4: Admin Share (Payable) -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Admin Share') }}</span>
                <span class="text-[13px] font-bold text-emerald-700 font-mono leading-tight block">@currency($kpis['admin_share'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Metric 5: Reseller Commission -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __('Reseller Profit') }}</span>
                <span class="text-[13px] font-bold text-indigo-700 font-mono leading-tight block">@currency($kpis['reseller_commission'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        <!-- Metric 6: Reseller Wallet -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-600 uppercase tracking-wider block truncate">{{ __('Reseller Wallet') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $kpis['reseller_available'] >= $kpis['admin_share'] ? 'text-slate-800' : 'text-rose-600' }}">
                    @currency($kpis['reseller_wallet'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- 3. Scope Mode Switcher & Filter Toolbar -->
    @php
        $activeMoreCount = collect([$zone, $packageId])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        
        <!-- Top Scope Mode Switcher & Quick Selection Bar -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200/80">
                <button type="button" 
                        @click="setMode('reseller')"
                        :class="mode === 'reseller' ? 'bg-white text-purple-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="px-3 py-1 rounded-md text-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-handshake text-[10px]"></i>
                    <span>{{ __('Reseller / Sub-ISP Wise') }}</span>
                </button>
                <button type="button" 
                        @click="setMode('direct')"
                        :class="mode === 'direct' ? 'bg-white text-cyan-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="px-3 py-1 rounded-md text-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-building text-[10px]"></i>
                    <span>{{ __('ISP Direct / Custom Scope') }}</span>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-500 font-medium">{{ __('Auto-Selection:') }}</span>
                <button type="button" 
                        @click="selectAllRows()" 
                        class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold border border-emerald-200 transition cursor-pointer flex items-center gap-1">
                    <i class="fas fa-check-double text-[10px]"></i>
                    <span x-text="i18n.selectAll.replace(':count', tableRows.length)"></span>
                </button>
                <button type="button" 
                        @click="clearAllSelections()" 
                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold border border-slate-200 transition cursor-pointer flex items-center gap-1">
                    <i class="fas fa-times text-[10px]"></i>
                    <span>{{ __('Deselect All') }}</span>
                </button>
            </div>
        </div>

        <!-- 2-Tier Filter Form (Strict AGENTS.md Standard & Uniform h-[34px]) -->
        <form method="GET" action="{{ route('tenant.customers.bulk-payments') }}" id="bulkPaymentFilterForm" class="space-y-2.5">
            <input type="hidden" name="mode" x-model="mode">

            <!-- Tier 1: Primary Filter Bar (Strict h-[34px] & Responsive Wrap) -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[180px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           id="bulkSearchInput"
                           x-model="searchQuery"
                           @input="filterRowsOnly($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="{{ __('Search Name, ID, Username, Phone, IP...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400"
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

                <!-- Reseller Select (Highlighted when Reseller Mode) -->
                <div class="w-full sm:w-52 md:w-56 flex-shrink-0" x-show="mode === 'reseller'">
                    <select name="reseller_id" 
                            x-model="selectedResellerId"
                            @change="onResellerChange($event)"
                            class="w-full h-[34px] px-2.5 bg-purple-50/60 border border-purple-200 rounded-lg text-xs font-semibold text-purple-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                        <option value="">{{ __('-- Choose Reseller --') }}</option>
                        @foreach($resellers as $res)
                            <option value="{{ $res->id }}" 
                                    data-commission="{{ (float)$res->commission_rate }}"
                                    data-wallet="{{ (float)$res->wallet_balance }}"
                                    data-credit="{{ (float)$res->credit_limit }}"
                                    {{ (string)$resellerId === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }}) - {{ __('Comm:') }} {{ (float)$res->commission_rate }}%
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Select -->
                <div class="w-full sm:w-40 md:w-44 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all_due" {{ $status === 'all_due' ? 'selected' : '' }}>{{ __('Pending Dues & Expired') }}</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('All Statuses') }}</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>🟢 {{ __('Active Only') }}</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>🔴 {{ __('Expired Only') }}</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>⛔ {{ __('Suspended Only') }}</option>
                        <option value="due" {{ $status === 'due' ? 'selected' : '' }}>🟡 {{ __('Due Only') }}</option>
                    </select>
                </div>

                <!-- Per Page Selector (per_page) -->
                <div class="w-full sm:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="20" {{ request('per_page', $perPage) == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ request('per_page', $perPage) == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                        <option value="250" {{ request('per_page', $perPage) == 250 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 250]) }}</option>
                        <option value="500" {{ request('per_page', $perPage) == 500 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 500]) }}</option>
                        <option value="all" {{ request('per_page', $perPage) === 'all' ? 'selected' : '' }}>{{ __('All Users') }}</option>
                    </select>
                </div>

                <!-- More Filters Accordion Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Area & Package Filters') }}">
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

                <!-- Filter & Reset Buttons (Strict AGENTS.md sequence: [Filter] then [Reset]) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 sm:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.customers.bulk-payments', ['mode' => $mode]) }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filters Drawer (Clean No-Label Toolbar Row) -->
            <div x-show="showMoreFilters" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">

                <!-- Zone Select -->
                <div class="flex-1 min-w-[160px]">
                    <select name="zone" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Zones / Areas') }}</option>
                        @foreach($zones as $z)
                            <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Select -->
                <div class="flex-1 min-w-[180px]">
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Packages') }}</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>
                                {{ $pkg->mikrotik_profile ?: ($pkg->name ?: $pkg->package_name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($mode === 'direct')
                <!-- Optional Reseller Selector when Direct Mode -->
                <div class="flex-1 min-w-[180px]">
                    <select name="reseller_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Reseller Scopes') }}</option>
                        @foreach($resellers as $res)
                            <option value="{{ $res->id }}" {{ (string)$resellerId === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </form>

        <!-- Reseller Information Banner (when Reseller is selected) -->
        @if($mode === 'reseller' && $selectedReseller)
            <div class="p-3 bg-purple-50/70 border border-purple-200 rounded-lg flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs flex-shrink-0">
                        {{ substr($selectedReseller->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-purple-950 flex items-center gap-1.5">
                            <span>{{ $selectedReseller->name }}</span>
                            <span class="px-1.5 py-0.2 rounded bg-purple-200 text-purple-800 text-[10px] font-mono">{{ $selectedReseller->code ?: 'RES-' . $selectedReseller->id }}</span>
                        </div>
                        <div class="text-[11px] text-purple-700">
                            <span>📞 {{ $selectedReseller->mobile ?: __('N/A') }}</span>
                            <span class="mx-1.5">|</span>
                            <span>{{ __('Billing Type:') }} <strong>{{ __(ucfirst($selectedReseller->billing_type ?? 'Prepaid')) }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap sm:ml-auto">
                    <!-- Commission Rate -->
                    <div class="px-2.5 py-1 bg-white rounded-md border border-purple-200 text-center shadow-2xs">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">{{ __('Commission Rate') }}</span>
                        <span class="text-xs font-bold text-purple-700 font-mono">{{ (float)$selectedReseller->commission_rate }}%</span>
                    </div>

                    <!-- Current Wallet Balance -->
                    <div class="px-2.5 py-1 bg-white rounded-md border border-purple-200 text-center shadow-2xs">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">{{ __('Wallet Balance') }}</span>
                        <span class="text-xs font-bold text-slate-900 font-mono">@currency($selectedReseller->wallet_balance)</span>
                    </div>

                    <!-- Credit Limit -->
                    <div class="px-2.5 py-1 bg-white rounded-md border border-purple-200 text-center shadow-2xs">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">{{ __('Credit Limit') }}</span>
                        <span class="text-xs font-bold text-slate-700 font-mono">@currency($selectedReseller->credit_limit)</span>
                    </div>

                    <!-- Total Available -->
                    <div class="px-2.5 py-1 bg-emerald-50 rounded-md border border-emerald-200 text-center shadow-2xs">
                        <span class="text-[9px] text-emerald-800 uppercase font-semibold block">{{ __('Total Available') }}</span>
                        <span class="text-xs font-bold text-emerald-700 font-mono">@currency($selectedReseller->total_available_balance)</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-8 text-center no-sort">
                            <input type="checkbox" 
                                   @change="toggleSelectAll($event)" 
                                   :checked="selectAll"
                                   class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer"
                                   title="{{ __('Select All Subscribers on this page') }}">
                        </th>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Zone') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th>{{ __('Expiry') }}</th>
                        <th class="text-right">{{ __('Bill') }}</th>
                        <th class="text-right">{{ __('Due') }}</th>
                        <th class="text-right">{{ __('Payable') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $c)
                        @php
                            $statusBadge = $c->status_badge;
                            $packageName = $c->mikrotik_profile_name ?: ($c->package ? ($c->package->mikrotik_profile ?: ($c->package->name ?: $c->package->package_name)) : ($c->package_name ?: 'default'));
                            $dueVal = (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
                        @endphp
                        <tr :class="{'is-selected': selectedIds.includes({{ $c->id }})}">
                            <!-- 0. Selection Checkbox -->
                            <td class="text-center">
                                <input type="checkbox" 
                                       :value="{{ $c->id }}" 
                                       :checked="selectedIds.includes({{ $c->id }})"
                                       @change="toggleRow({{ $c->id }}, {{ $dueVal }})"
                                       class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                            </td>

                            <!-- 1. Customer ID -->
                            <td class="font-mono text-cyan-800 font-semibold">
                                <a href="{{ route('tenant.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
                                    {{ $c->customer_id }}
                                </a>
                            </td>

                            <!-- 2. Name -->
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('tenant.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
                                    {{ $c->name }}
                                </a>
                            </td>

                            <!-- 3. Username -->
                            <td class="font-mono text-slate-700">
                                {{ $c->username }}
                            </td>

                            <!-- 4. Phone -->
                            <td class="font-mono text-slate-600">
                                {{ $c->phone ?: '--' }}
                            </td>

                            <!-- 5. Zone -->
                            <td class="text-slate-600">
                                {{ $c->zone ?: '--' }}
                            </td>

                            <!-- 6. Package -->
                            <td class="font-medium text-slate-800">
                                {{ $packageName }}
                            </td>

                            <!-- 7. Status Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $statusBadge['class'] }} shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 8. Expiry Date -->
                            <td class="font-mono text-slate-600">
                                {{ $c->expiry_date ? $c->expiry_date->translatedFormat('d M Y') : '--' }}
                            </td>

                            <!-- 9. Monthly Bill -->
                            <td class="font-mono text-right text-slate-700">
                                @currency($c->monthly_bill)
                            </td>

                            <!-- 10. Due Amount -->
                            <td class="font-mono text-right font-semibold {{ $c->due_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                @currency($c->due_amount)
                            </td>

                            <!-- 11. Total Payable Amount -->
                            <td class="font-mono text-right font-bold text-emerald-700 bg-emerald-50/30">
                                @currency($dueVal)
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-users-slash text-2xl text-slate-300 mb-2 block"></i>
                                {{ __('No subscribers found matching your bulk filter criteria.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50/80 border-t-2 border-slate-200 divide-y divide-slate-200/80 text-xs">
                    <!-- 1. Gross Bill -->
                    <tr>
                        <td colspan="11" class="text-right py-1.5 px-3 font-semibold text-slate-700" x-text="i18n.grossBillUsers.replace(':count', selectedIds.length)"></td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-amber-900">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedGrossBill.toFixed(2)"></span>
                        </td>
                    </tr>

                    <!-- 2. Reseller Profit -->
                    <tr>
                        <td colspan="11" class="text-right py-1.5 px-3 font-semibold text-indigo-800" x-text="i18n.resellerProfitRate.replace(':rate', resellerCommissionRate)"></td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-indigo-700">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                        </td>
                    </tr>

                    <!-- 3. Admin Share -->
                    <tr class="bg-emerald-50/50">
                        <td colspan="11" class="text-right py-1.5 px-3 font-bold text-emerald-900">
                            {{ __('Admin Share:') }}
                        </td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-emerald-800">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($subscribers->hasPages())
            <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-200">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>

    <!-- 5. TABLE BOTTOM LIVE FINANCIAL STRIP & SUBMISSION BOX -->
    <div class="bg-white rounded-md border border-slate-200 shadow-sm p-4 space-y-4">
        
        <!-- Live Financial Calculation Cards (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Card 1: Gross Total Bill -->
            <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-md">
                <span class="text-[10px] font-semibold text-amber-800 uppercase tracking-wider block">{{ __('1. Gross Bill') }}</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-amber-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedGrossBill.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-amber-700" x-text="i18n.usersCountParen.replace(':count', selectedIds.length)"></span>
                </div>
            </div>

            <!-- Card 2: Admin Share -->
            <div class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-md">
                <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider block">{{ __('2. Admin Share') }}</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-emerald-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-emerald-700">
                        {{ __('Net Payable') }}
                    </span>
                </div>
            </div>

            <!-- Card 3: Reseller Profit -->
            <div class="p-3 bg-indigo-50/80 border border-indigo-200 rounded-md">
                <span class="text-[10px] font-semibold text-indigo-800 uppercase tracking-wider block">{{ __('3. Reseller Profit') }}</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-indigo-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-indigo-700 font-mono" x-text="i18n.profitRateLabel.replace(':rate', resellerCommissionRate)"></span>
                </div>
            </div>

            <!-- Card 4: Reseller Wallet -->
            <div class="p-3 bg-purple-50/80 border border-purple-200 rounded-md" :class="{'ring-2 ring-rose-300 bg-rose-50/50': isWalletInsufficient && paymentMethod === 'reseller_wallet'}">
                <span class="text-[10px] font-semibold text-purple-800 uppercase tracking-wider block">{{ __('4. Reseller Wallet') }}</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono" :class="isWalletInsufficient ? 'text-rose-700' : 'text-purple-900'">
                        {{ $currencySymbol ?? '৳' }} <span x-text="resellerWalletBalance.toFixed(2)"></span>
                    </span>
                    <span class="text-[10px] font-medium" :class="isWalletInsufficient ? 'text-rose-600 font-bold' : 'text-purple-700'">
                        <span x-show="mode === 'reseller'" x-text="i18n.availBalance.replace(':currency', currencySymbol).replace(':amount', resellerTotalAvailable.toFixed(2))"></span>
                        <span x-show="mode !== 'reseller'">{{ __('ISP HQ') }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Warning Alert if Wallet Insufficient -->
        <div x-show="mode === 'reseller' && paymentMethod === 'reseller_wallet' && isWalletInsufficient" 
             x-transition 
             class="p-2.5 bg-rose-50 border border-rose-200 rounded-md text-xs text-rose-800 flex items-center gap-2">
            <i class="fas fa-triangle-exclamation text-rose-600"></i>
            <span x-text="i18n.walletInsufficientWarning.replaceAll(':currency', currencySymbol).replace(':available', resellerTotalAvailable.toFixed(2)).replace(':required', selectedAdminShare.toFixed(2))"></span>
        </div>

        <!-- Payment Method Selection & Execution Form -->
        <form @submit.prevent="confirmAndExecuteBulkPayment()" class="pt-3 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                
                <!-- Payment Method Select -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="paymentMethod" 
                            required
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs font-semibold focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <template x-if="mode === 'reseller'">
                            <option value="reseller_wallet">💼 {{ __('Reseller Wallet (Auto-Debit Admin Share)') }}</option>
                        </template>
                        <option value="cash">💵 {{ __('Cash in Hand') }}</option>
                        <option value="bkash">📱 {{ __('bKash') }}</option>
                        <option value="nagad">📱 {{ __('Nagad') }}</option>
                        <option value="rocket">📱 {{ __('Rocket') }}</option>
                        <option value="bank_transfer">🏦 {{ __('Bank Transfer') }}</option>
                        <option value="pos">💳 {{ __('Card / POS') }}</option>
                        <option value="online">🌐 {{ __('Online Gateway') }}</option>
                        <option value="other">⚙️ {{ __('Other') }}</option>
                    </select>
                </div>

                <!-- Billing Month -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">{{ __('Billing Month') }}</label>
                    <input type="text" 
                           x-model="billingMonth" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="{{ __('e.g. :month', ['month' => now()->translatedFormat('F Y')]) }}">
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">{{ __('Batch Remarks / Notes') }}</label>
                    <input type="text" 
                           x-model="notes" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="{{ __('Optional batch notes...') }}">
                </div>

                <!-- Submit Execution Button -->
                <div>
                    <button type="submit" 
                            :disabled="loading || selectedIds.length === 0 || (paymentMethod === 'reseller_wallet' && isWalletInsufficient)"
                            class="w-full px-4 py-2 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="loading" style="display: none;"></i>
                        <i class="fas fa-check-double text-xs" x-show="!loading"></i>
                        <span x-text="i18n.confirmAndProcess.replace(':count', selectedIds.length)"></span>
                    </button>
                </div>
            </div>

            <!-- Automations Checkboxes -->
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" 
                           x-model="extendValidity" 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                    <span>{{ __('Auto Extend Validity (+1 Month from expiry date)') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" 
                           x-model="reactivateLine" 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                    <span>{{ __('Auto Re-activate in MikroTik Router & FreeRADIUS') }}</span>
                </label>
            </div>
        </form>
    </div>

    <!-- 6. Toast Notification Floating Container -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 max-w-sm p-3 rounded-md shadow-xl text-xs font-medium border flex items-center gap-2"
         :class="toast.type === 'success' ? 'bg-emerald-900 text-white border-emerald-700' : 'bg-rose-900 text-white border-rose-700'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-triangle-exclamation text-rose-400'"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bulkPaymentManager() {
    const rawRows = @json($subscribers->map(fn($c) => [
        'id' => $c->id,
        'due' => (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0))
    ]));

    return {
        mode: @json($mode),
        selectedResellerId: @json($resellerId ?? ''),
        currencySymbol: @json($currencySymbol ?? '৳'),
        resellerCommissionRate: {{ (float)($kpis['commission_rate'] ?? 0) }},
        resellerWalletBalance: {{ (float)($kpis['reseller_wallet'] ?? 0) }},
        resellerCreditLimit: {{ (float)($kpis['reseller_credit'] ?? 0) }},
        resellerTotalAvailable: {{ (float)($kpis['reseller_available'] ?? 0) }},
        
        tableRows: rawRows,
        selectedIds: rawRows.map(r => r.id), // Auto-select rows by default for fast bulk payment
        selectedDueMap: rawRows.reduce((acc, cur) => { acc[cur.id] = cur.due; return acc; }, {}),
        selectAll: true,

        showMoreFilters: {{ ($isMoreOpen ?? false) ? 'true' : 'false' }},
        searchQuery: @json($search ?? ''),

        i18n: {
            selectAll: @json(__('Select All (:count)')),
            grossBillUsers: @json(__('Gross Bill (:count Users):')),
            resellerProfitRate: @json(__('Reseller Profit (:rate%):')),
            usersCountParen: @json(__('(:count Users)')),
            profitRateLabel: @json(__(':rate% Profit')),
            availBalance: @json(__('Avail: :currency :amount')),
            walletInsufficientWarning: @json(__('Warning: Reseller available balance (:currency :available) is less than Admin Share (:currency :required). Please top up reseller wallet or choose Cash/Online payment method.')),
            confirmAndProcess: @json(__('Confirm & Process (:count)')),
            noSubscribersTitle: @json(__('No Subscribers Selected')),
            noSubscribersText: @json(__('Please select at least one subscriber from the table for bulk payment.')),
            noSubscribersToast: @json(__('Please select at least one subscriber for bulk payment.')),
            insufficientWalletTitle: @json(__('Insufficient Reseller Wallet')),
            insufficientWalletText: @json(__('Reseller available balance is insufficient for admin share payment.')),
            insufficientWalletToast: @json(__('Reseller wallet balance is insufficient for admin share payment.')),
            totalSelected: @json(__('Total Selected:')),
            subscribersCount: @json(__(':count Subscribers')),
            grossTotalBill: @json(__('Gross Total Bill:')),
            adminShareDue: @json(__('Admin Share (Due):')),
            resellerProfit: @json(__('Reseller Profit:')),
            paymentMethodLabel: @json(__('Payment Method:')),
            billingMonthLabel: @json(__('Billing Month:')),
            autoExtendLine: @json(__('✓ Auto extend validity (+1 month)')),
            autoReactivateLine: @json(__('✓ Auto reactivate in MikroTik & FreeRADIUS')),
            confirmTitle: @json(__('Confirm Bulk Payment?')),
            confirmBtn: @json(__('Yes, Confirm & Process')),
            cancelBtn: @json(__('Cancel')),
            confirmFallback: @json(__("Process bulk payment for :count subscribers?\nGross Total: :currency:gross\nAdmin Share: :currency:admin\nMethod: :method")),
            batchSuccessTitle: @json(__('Batch Processed Successfully!')),
            processingFailedTitle: @json(__('Processing Failed')),
            processingFailedText: @json(__('Bulk payment execution failed.')),
            networkErrorTitle: @json(__('Network Error')),
            networkErrorText: @json(__('An unexpected network error occurred during bulk payment processing.')),
            networkErrorToast: @json(__('Network error during bulk payment processing.')),
            methodLabels: {
                reseller_wallet: @json(__('Reseller Wallet')),
                cash: @json(__('Cash in Hand')),
                bkash: @json(__('bKash')),
                nagad: @json(__('Nagad')),
                rocket: @json(__('Rocket')),
                bank_transfer: @json(__('Bank Transfer')),
                pos: @json(__('Card / POS')),
                online: @json(__('Online Gateway')),
                other: @json(__('Other'))
            }
        },

        clearLiveSearch() {
            this.searchQuery = '';
            const rows = document.querySelectorAll('.saas-table tbody tr:not(.no-record-row)');
            rows.forEach(row => { row.style.display = ''; });
            const form = document.getElementById('bulkPaymentFilterForm');
            if (form) {
                const input = form.querySelector('input[name="search"]');
                if (input) input.value = '';
                form.submit();
            }
        },

        filterRowsOnly(val) {
            const term = (val || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.saas-table tbody tr:not(.no-record-row)');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const matches = !term || text.includes(term);
                row.style.display = matches ? '' : 'none';
            });
        },

        submitSearchNow() {
            const form = document.getElementById('bulkPaymentFilterForm');
            if (form) form.submit();
        },

        paymentMethod: @json($mode === 'reseller' && $selectedReseller ? 'reseller_wallet' : 'cash'),
        billingMonth: @json(date('F Y')),
        notes: '',
        extendValidity: true,
        reactivateLine: true,
        loading: false,

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => { this.toast.show = false; }, 4000);
        },

        get selectedGrossBill() {
            let sum = 0;
            for (const id of this.selectedIds) {
                sum += (this.selectedDueMap[id] || 0);
            }
            return sum;
        },

        get selectedResellerCommission() {
            if (this.mode === 'reseller' && this.resellerCommissionRate > 0) {
                return (this.selectedGrossBill * this.resellerCommissionRate) / 100;
            }
            return 0.00;
        },

        get selectedAdminShare() {
            return Math.max(0, this.selectedGrossBill - this.selectedResellerCommission);
        },

        get isWalletInsufficient() {
            if (this.mode === 'reseller' && this.paymentMethod === 'reseller_wallet') {
                return this.resellerTotalAvailable < this.selectedAdminShare;
            }
            return false;
        },

        setMode(newMode) {
            this.mode = newMode;
            if (newMode === 'direct') {
                this.selectedResellerId = '';
                this.paymentMethod = 'cash';
                this.resellerCommissionRate = 0;
            }
            // Auto submit form with new mode
            const url = new URL(window.location.href);
            url.searchParams.set('mode', newMode);
            if (newMode === 'direct') {
                url.searchParams.delete('reseller_id');
            }
            window.location.href = url.toString();
        },

        onResellerChange(e) {
            const selectEl = e.target;
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt && opt.dataset) {
                this.resellerCommissionRate = parseFloat(opt.dataset.commission || 0);
                this.resellerWalletBalance = parseFloat(opt.dataset.wallet || 0);
                this.resellerCreditLimit = parseFloat(opt.dataset.credit || 0);
                this.resellerTotalAvailable = this.resellerWalletBalance + this.resellerCreditLimit;
            }
            selectEl.form.submit();
        },

        toggleSelectAll(e) {
            this.selectAll = e.target.checked;
            if (this.selectAll) {
                this.selectedIds = this.tableRows.map(r => r.id);
            } else {
                this.selectedIds = [];
            }
        },

        selectAllRows() {
            this.selectedIds = this.tableRows.map(r => r.id);
            this.selectAll = true;
        },

        clearAllSelections() {
            this.selectedIds = [];
            this.selectAll = false;
        },

        toggleRow(id, due) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
                this.selectAll = false;
            } else {
                this.selectedIds.push(id);
                if (this.selectedIds.length === this.tableRows.length) {
                    this.selectAll = true;
                }
            }
        },

        printSelectedOrAll() {
            let url = new URL("{{ route('tenant.customers.bulk-payments.print') }}", window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((val, key) => url.searchParams.set(key, val));

            // Only print the subscribers currently in this table / selected rows
            const targetIds = (this.selectedIds && this.selectedIds.length > 0)
                ? this.selectedIds
                : this.tableRows.map(r => r.id);

            if (targetIds && targetIds.length > 0) {
                url.searchParams.set('ids', targetIds.join(','));
            }
            window.open(url.toString(), '_blank');
        },

        async confirmAndExecuteBulkPayment() {
            if (this.selectedIds.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: this.i18n.noSubscribersTitle,
                        text: this.i18n.noSubscribersText,
                        confirmButtonColor: '#0891b2'
                    });
                } else {
                    this.showToast(this.i18n.noSubscribersToast, 'error');
                }
                return;
            }

            if (this.paymentMethod === 'reseller_wallet' && this.isWalletInsufficient) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.insufficientWalletTitle,
                        text: this.i18n.insufficientWalletText,
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    this.showToast(this.i18n.insufficientWalletToast, 'error');
                }
                return;
            }

            const count = this.selectedIds.length;
            const grossBill = this.selectedGrossBill.toFixed(2);
            const adminShare = this.selectedAdminShare.toFixed(2);
            const resellerComm = this.selectedResellerCommission.toFixed(2);
            const methodLabel = this.i18n.methodLabels[this.paymentMethod] || this.paymentMethod.replace(/_/g, ' ').toUpperCase();
            const curr = this.currencySymbol;

            let htmlContent = `
                <div class="text-left text-xs space-y-2.5 p-1">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-700 space-y-1.5 font-mono">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">${this.i18n.totalSelected}</span>
                            <strong class="text-slate-900 font-bold">${this.i18n.subscribersCount.replace(':count', count)}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">${this.i18n.grossTotalBill}</span>
                            <strong class="text-slate-900">${curr} ${grossBill}</strong>
                        </div>`;

            if (this.isResellerMode) {
                htmlContent += `
                        <div class="flex justify-between border-t border-slate-200 pt-1 text-cyan-700 font-bold">
                            <span class="font-sans">${this.i18n.adminShareDue}</span>
                            <span>${curr} ${adminShare}</span>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <span class="font-sans">${this.i18n.resellerProfit}</span>
                            <span>${curr} ${resellerComm}</span>
                        </div>`;
            }

            htmlContent += `
                        <div class="flex justify-between border-t border-slate-200 pt-1">
                            <span class="text-slate-500 font-sans">${this.i18n.paymentMethodLabel}</span>
                            <span class="font-bold text-slate-800">${methodLabel}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">${this.i18n.billingMonthLabel}</span>
                            <span class="font-bold text-slate-800">${this.billingMonth}</span>
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-500 space-y-0.5">
                        <p class="text-emerald-700 font-medium">${this.i18n.autoExtendLine}</p>
                        <p class="text-emerald-700 font-medium">${this.i18n.autoReactivateLine}</p>
                    </div>
                </div>
            `;

            let isConfirmed = false;
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: this.i18n.confirmTitle,
                    html: htmlContent,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-check-double mr-1"></i> ' + this.i18n.confirmBtn,
                    cancelButtonText: this.i18n.cancelBtn
                });
                isConfirmed = result.isConfirmed;
            } else {
                const confirmMsg = this.i18n.confirmFallback
                    .replace(':count', count)
                    .replaceAll(':currency', curr)
                    .replace(':gross', grossBill)
                    .replace(':admin', adminShare)
                    .replace(':method', methodLabel);
                isConfirmed = confirm(confirmMsg);
            }

            if (!isConfirmed) {
                return;
            }

            this.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.customers.bulk-payments.process') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_ids: this.selectedIds,
                        payment_method: this.paymentMethod,
                        billing_month: this.billingMonth,
                        notes: this.notes,
                        extend_validity: this.extendValidity,
                        reactivate_line: this.reactivateLine,
                        reseller_id: this.selectedResellerId || null
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: this.i18n.batchSuccessTitle,
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        this.showToast(data.message, 'success');
                    }
                    if (data.reseller_wallet_balance !== null) {
                        this.resellerWalletBalance = parseFloat(data.reseller_wallet_balance);
                        this.resellerTotalAvailable = this.resellerWalletBalance + this.resellerCreditLimit;
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: this.i18n.processingFailedTitle,
                            text: data.message || this.i18n.processingFailedText,
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        this.showToast(data.message || this.i18n.processingFailedText, 'error');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: this.i18n.networkErrorTitle,
                        text: this.i18n.networkErrorText,
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    this.showToast(this.i18n.networkErrorToast, 'error');
                }
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
