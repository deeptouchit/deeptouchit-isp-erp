@extends('tenant.layouts.app')

@php
    $authUser = auth()->user();
    $currencySymbol = $currencySymbol ?? ($tenant->currency_symbol ?? '৳');
    $isDirectRoute = request()->routeIs('tenant.customers.direct*') || ($scopeFilter ?? '') === 'isp';
    $isDueRoute = request()->routeIs('tenant.customers.due*') || ($statusFilter ?? '') === 'due';
    $isOnlineRoute = request()->routeIs('tenant.customers.online*') || ($onlineFilter ?? '') === 'online';
    $isZonesRoute = request()->routeIs('tenant.customers.zones*');
    $isDisconnectedRoute = request()->routeIs('tenant.customers.disconnected*') || request()->routeIs('tenant.customers.archived*') || in_array(($statusFilter ?? ''), ['disconnected', 'archived']);

    if ($isDirectRoute) {
        $pageTitle = __('ISP Direct Retail Customers');
        $pageIcon = 'fa-building-user';
    } elseif ($isDueRoute) {
        $pageTitle = __('Due & Expired Customers');
        $pageIcon = 'fa-receipt';
    } elseif ($isOnlineRoute) {
        $pageTitle = __('Live Online PPPoE Sessions');
        $pageIcon = 'fa-globe';
    } elseif ($isZonesRoute) {
        $pageTitle = __('Customer Zones & Coverage Areas');
        $pageIcon = 'fa-location-dot';
    } elseif ($isDisconnectedRoute) {
        $pageTitle = __('Disconnected Subscribers');
        $pageIcon = 'fa-plug-circle-xmark';
    } else {
        $pageTitle = __('All Customers (Master Database)');
        $pageIcon = 'fa-users-line';
    }
@endphp

@section('title', $pageTitle . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" 
     x-data="customerMasterManager()" 
     @scroll.window="activeMenu = null" 
     @resize.window="activeMenu = null">
    
    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas {{ $pageIcon }}"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
        </div>

        <div class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-center gap-2 w-full sm:w-auto flex-shrink-0">
            <!-- Refresh Live Sessions (MikroTik + FreeRADIUS) -->
            <button type="button" 
                    @click="syncOnlineSessions()"
                    :disabled="isSyncing"
                    class="px-2.5 py-1.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-emerald-200 cursor-pointer disabled:opacity-50"
                    title="{{ __('Query MikroTik and FreeRADIUS for live active PPPoE sessions') }}">
                <i class="fas fa-satellite-dish text-emerald-600 text-[11px]" :class="{'fa-spin': isSyncing}"></i>
                <span class="truncate" x-text="isSyncing ? i18n.checking : i18n.refreshSessions">{{ __('Refresh Sessions') }}</span>
            </button>

            @if($isOnlineRoute)
                <!-- Real-time Live RX/TX Streaming Monitor Toggle Button -->
                <button type="button" 
                        @click="toggleTableLiveStream()"
                        class="px-3 py-1.5 rounded-md font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border cursor-pointer select-none"
                        :class="isTableLiveStreaming 
                            ? 'bg-emerald-50 text-emerald-800 border-emerald-300 ring-2 ring-emerald-400/40 hover:bg-emerald-100' 
                            : 'bg-purple-50 hover:bg-purple-100 text-purple-700 border-purple-200'"
                        title="{{ __('Click to toggle continuous real-time RX / TX bandwidth monitoring (Live 2s refresh)') }}">
                    <span class="w-2 h-2 rounded-full" 
                          :class="isTableLiveStreaming ? 'bg-emerald-500 animate-ping' : 'bg-purple-500'"></span>
                    <i class="fas fa-chart-line text-[11px]" :class="isTableLiveStreaming ? 'text-emerald-700' : 'text-purple-600'"></i>
                    <span x-text="isTableLiveStreaming ? i18n.liveRealtimeOn : i18n.checkSpeedsRxTx">{{ __('Check Speeds (RX/TX)') }}</span>
                </button>
            @endif

            @if(!$authUser || !$authUser->isCollector())
            <!-- Two-Way Smart Sync (MikroTik <-> Software <-> FreeRADIUS) -->
            <button type="button" 
                    @click="syncAllMikrotik()"
                    :disabled="isSyncing"
                    class="px-2.5 py-1.5 rounded-md bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-cyan-200 cursor-pointer disabled:opacity-50"
                    title="{{ __('Intelligent Two-Way Sync between MikroTik, Software DB & FreeRADIUS') }}">
                <i class="fas fa-arrows-rotate text-cyan-600 text-[11px]" :class="{'fa-spin': isSyncing}"></i>
                <span class="truncate" x-text="isSyncing ? i18n.syncing : i18n.twoWaySmartSync">{{ __('2-Way Smart Sync') }}</span>
            </button>

            <!-- Dedicated Import from MikroTik Router Button -->
            <button type="button" 
                    @click="openMikrotikImportModal()"
                    class="px-2.5 py-1.5 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-indigo-200 cursor-pointer"
                    title="{{ __('Import / Pull existing subscriber records from MikroTik /ppp/secret') }}">
                <i class="fas fa-network-wired text-indigo-600 text-[11px]"></i>
                <span class="truncate">{{ __('Import Router') }}</span>
            </button>

            @if($isDueRoute)
                <!-- Direct Bulk Payments Page Link -->
                <a href="{{ route('tenant.customers.bulk-payments') }}" 
                   class="px-3 py-1.5 rounded-md bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-layer-group text-[11px]"></i>
                    <span>{{ __('Bulk Payments') }}</span>
                </a>
            @elseif(!$isOnlineRoute && !$isDisconnectedRoute)
                <!-- Bulk Import CSV -->
                <button type="button" 
                        @click="openImportModal()"
                        class="px-2.5 py-1.5 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-amber-200 cursor-pointer"
                        title="{{ __('Bulk import subscriber records from CSV/Excel') }}">
                    <i class="fas fa-file-arrow-up text-amber-600 text-[11px]"></i>
                    <span>{{ __('Import CSV') }}</span>
                </button>

                <!-- Export Database Modal -->
                <button type="button" 
                        @click="openExportModal()"
                        class="px-2.5 py-1.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-emerald-200 cursor-pointer"
                        title="{{ __('Export customer records to Excel CSV with presets and filters') }}">
                    <i class="fas fa-file-arrow-down text-emerald-600 text-[11px]"></i>
                    <span>{{ __('Export Data') }}</span>
                </button>

                <!-- Onboard New Customer Button (Dedicated Full Page) -->
                <a href="{{ route('tenant.customers.create') }}" 
                   class="col-span-2 sm:col-span-1 px-3.5 py-1.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-user-plus text-[11px]"></i>
                    <span>{{ __('+ Onboard Customer') }}</span>
                </a>
            @endif
            @endif
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Subscribers -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Subscribers') }}</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalCustomers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Live Online Sessions -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Online Sessions') }}</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($onlineSessionsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Metric 3: ISP Direct Retail -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">{{ __('ISP Retail') }}</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($ispDirectCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Metric 4: Sub-ISP Franchise Users -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('Sub-ISP Users') }}</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($resellerCustomersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-handshake"></i>
            </div>
        </div>

        <!-- Metric 5: Due Accounts -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Due Accounts') }}</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block">{{ number_format($dueAccountsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0" title="{{ __('Total Due: :amount', ['amount' => ($currencySymbol ?? '৳') . ' ' . number_format((float)($totalDueAmount ?? 0), 2)]) }}">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Metric 6: Expired / Auto-cut -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">{{ __('Expired / Auto-cut') }}</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($expiredAccountsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    @if($isZonesRoute && !empty($zoneStats) && $zoneStats->count() > 0)
        <!-- Zone Coverage & Performance Matrix -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-location-dot text-cyan-600"></i>
                    <span>{{ $zoneStats->count() === 1 ? __('Coverage Zones & Area Breakdown (:count Zone Configured)', ['count' => 1]) : __('Coverage Zones & Area Breakdown (:count Zones Configured)', ['count' => $zoneStats->count()]) }}</span>
                </span>
                @if($zoneFilter)
                    <a href="{{ route('tenant.customers.zones') }}" class="text-[11px] text-cyan-600 hover:text-cyan-700 font-semibold inline-flex items-center gap-1">
                        <i class="fas fa-filter-circle-xmark"></i>
                        <span>{{ __('Clear Zone Filter (Viewing: :zone)', ['zone' => $zoneFilter]) }}</span>
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                @foreach($zoneStats as $zs)
                    @php
                        $isSelectedZone = ($zoneFilter === $zs->zone);
                    @endphp
                    <a href="{{ route('tenant.customers.zones', ['zone' => $zs->zone]) }}" 
                       class="p-2.5 rounded-lg border transition block shadow-2xs {{ $isSelectedZone ? 'border-cyan-400 bg-cyan-50/70 ring-2 ring-cyan-400' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100/80 hover:border-slate-300' }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] font-bold text-slate-900 truncate flex items-center gap-1">
                                <i class="fas fa-map-pin text-[10px] text-cyan-600"></i>
                                <span>{{ $zs->zone }}</span>
                            </span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-white text-cyan-700 border border-slate-200">
                                {{ $zs->total_subscribers === 1 ? __(':count User', ['count' => 1]) : __(':count Users', ['count' => $zs->total_subscribers]) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-1 text-[10px] pt-1.5 border-t border-slate-200/80">
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase font-semibold">{{ __('Active') }}</span>
                                <span class="font-bold text-emerald-600 font-mono">{{ $zs->active_count }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase font-semibold">{{ __('MRR') }}</span>
                                <span class="font-bold text-slate-800 font-mono">@currency($zs->total_mrr)</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase font-semibold">{{ __('Due') }}</span>
                                <span class="font-bold {{ $zs->total_due > 0 ? 'text-amber-600' : 'text-slate-400' }} font-mono">@currency($zs->total_due)</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 3. Search & Multi-Filter Toolbar -->
    @php
        $activeMoreCount = collect([$scopeFilter, $selectedResellerId, $onlineFilter, $connectionType, $routerFilter ?? null, $oltFilter ?? null, $ponFilter ?? null])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }} }">
        <form method="GET" action="{{ url()->current() }}" id="customerFilterForm" class="space-y-2.5">
            <!-- Primary Filter Bar (Always visible responsive row) -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- Search Text (Instant Live Search + Full DB Lookup) -->
                <div class="relative flex-1 min-w-[180px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           id="customerSearchInput"
                           x-model="searchQuery"
                           @input="handleLiveSearch($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="{{ __('Live Search Name, ID, Username, Phone, IP...') }}" 
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

                <!-- Status Filter -->
                <div class="w-full sm:w-32 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="due" {{ $statusFilter === 'due' ? 'selected' : '' }}>{{ __('Due') }}</option>
                        <option value="expired" {{ $statusFilter === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                        <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                        <option value="disabled" {{ $statusFilter === 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                        <option value="disconnected" {{ in_array($statusFilter, ['disconnected', 'archived']) ? 'selected' : '' }}>{{ __('Disconnected') }}</option>
                    </select>
                </div>

                <!-- Zone Filter -->
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="zone" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Zones') }}</option>
                        @foreach($distinctZones as $z)
                            <option value="{{ $z }}" {{ $zoneFilter === $z ? 'selected' : '' }}>
                                {{ $z }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Filter -->
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Packages') }}</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageFilter === (string)$pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name ?: ($pkg->mikrotik_profile ?: ($pkg->package_name ?: __('Package #:id', ['id' => $pkg->id]))) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div class="w-full sm:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                        <option value="250" {{ request('per_page', 20) == 250 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 250]) }}</option>
                        <option value="500" {{ request('per_page', 20) == 500 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 500]) }}</option>
                        <option value="1000" {{ request('per_page', 20) == 1000 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 1000]) }}</option>
                        <option value="2000" {{ request('per_page', 20) == 2000 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 2000]) }}</option>
                        <option value="all" {{ request('per_page') === 'all' ? 'selected' : '' }}>{{ __('All Subscribers') }}</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Advanced Filters') }}">
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
                    <a href="{{ url()->current() }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Advanced / Secondary Filters Drawer (Clean No-Label Toolbar Row) -->
            <div x-show="showMoreFilters" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">

                @php
                    $authUser = auth()->user();
                @endphp

                @if(!$authUser || !$authUser->isCollector())
                <!-- Scope Filter -->
                <div class="flex-1 min-w-[130px]">
                    <select name="scope" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Scopes') }}</option>
                        <option value="isp" {{ $scopeFilter === 'isp' ? 'selected' : '' }}>{{ __('ISP Direct') }}</option>
                        <option value="reseller" {{ $scopeFilter === 'reseller' ? 'selected' : '' }}>{{ __('Sub-ISP') }}</option>
                    </select>
                </div>

                <!-- Sub-ISP Partner Filter -->
                <div class="flex-1 min-w-[150px]">
                    <select name="reseller_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Partners') }}</option>
                        @foreach($allResellers as $reseller)
                            <option value="{{ $reseller->id }}" {{ (string)$selectedResellerId === (string)$reseller->id ? 'selected' : '' }}>
                                {{ $reseller->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Live Session Filter -->
                <div class="flex-1 min-w-[120px]">
                    <select name="online_status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Sessions') }}</option>
                        <option value="online" {{ ($onlineFilter ?? '') === 'online' ? 'selected' : '' }}>🟢 {{ __('Online') }}</option>
                        <option value="offline" {{ ($onlineFilter ?? '') === 'offline' ? 'selected' : '' }}>⚪ {{ __('Offline') }}</option>
                    </select>
                </div>

                <!-- Connection Type Filter -->
                <div class="flex-1 min-w-[130px]">
                    <select name="connection_type" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Connection Types') }}</option>
                        <option value="pppoe" {{ ($connectionType ?? '') === 'pppoe' ? 'selected' : '' }}>{{ __('PPPoE') }}</option>
                        <option value="static_ip" {{ ($connectionType ?? '') === 'static_ip' ? 'selected' : '' }}>{{ __('Static IP') }}</option>
                        <option value="hotspot" {{ ($connectionType ?? '') === 'hotspot' ? 'selected' : '' }}>{{ __('Hotspot') }}</option>
                    </select>
                </div>

                <!-- Router Filter -->
                <div class="flex-1 min-w-[140px]">
                    <select name="router_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Routers') }}</option>
                        @foreach($allRouters as $rtr)
                            <option value="{{ $rtr->id }}" {{ ($routerFilter ?? request('router_id')) == $rtr->id ? 'selected' : '' }}>
                                {{ $rtr->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- OLT Filter -->
                <div class="flex-1 min-w-[130px]">
                    <select name="olt_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All OLTs') }}</option>
                        @foreach($allOlts as $olt)
                            <option value="{{ $olt->id }}" {{ (string)($oltFilter ?? '') === (string)$olt->id ? 'selected' : '' }}>
                                {{ $olt->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- PON Port Filter -->
                <div class="flex-1 min-w-[130px]">
                    <select name="pon_port" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All PON Ports') }}</option>
                        @foreach($distinctPonPorts as $pon)
                            <option value="{{ $pon }}" {{ ($ponFilter ?? '') === $pon ? 'selected' : '' }}>
                                {{ __('PON: :port', ['port' => $pon]) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th>{{ __('ID') }}</th>
                        <th class="w-20 text-center">{{ __('Live') }}</th>
                        @if(!$isOnlineRoute)
                            <th class="w-20 text-center">{{ __('MikroTik') }}</th>
                        @endif
                        <th class="text-center">{{ __('Status') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Mobile') }}</th>
                        <th>{{ __('PPPoE') }}</th>
                        <th>{{ __('Zone') }}</th>
                        <th>{{ __('Partner') }}</th>
                        <th>{{ __('Package') }}</th>
                        @if($isOnlineRoute)
                            <th class="text-right">{{ __('Download (RX)') }}</th>
                            <th class="text-right">{{ __('Upload (TX)') }}</th>
                        @else
                            <th>{{ __('Bill') }}</th>
                        @endif
                        @if($isDueRoute)
                            <th class="text-right">{{ __('Due Amount') }}</th>
                        @endif
                        <th>{{ __('Expiry Date') }}</th>
                        <th class="text-center">{{ __('Validity') }}</th>
                        <th class="w-14 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $c)
                        @php
                            $statusBadge = $c->status_badge;
                            $packageName = $c->package_display_name;
                            $dueAmountVal = (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
                            $expiryBadge = $c->expiry_days_badge;
                            $rawCustomerData = [
                                'id' => $c->id,
                                'customer_id' => $c->customer_id,
                                'name' => $c->name,
                                'username' => $c->username,
                                'monthly_bill' => (float)$c->monthly_bill,
                                'due_amount' => (float)$c->due_amount,
                                'due_payable' => $dueAmountVal,
                                'expiry_date' => $c->expiry_date ? $c->expiry_date->format('d M Y') : '--',
                                'expiry_raw' => $c->expiry_date ? $c->expiry_date->format('Y-m-d') : '',
                                'status' => $c->status,
                                'effective_status' => $c->effective_status,
                                'status_label' => $statusBadge['label'],
                                'online_status' => $c->online_status,
                                'phone' => $c->phone,
                                'zone' => $c->zone ?: '--',
                                'package_name' => $packageName,
                                'reseller_name' => $c->reseller ? ($c->reseller->code ?: $c->reseller->name) : ($tenant->company_code ?? 'TDC'),
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Customer ID -->
                            <td class="font-mono text-cyan-800 font-semibold">
                                {{ $c->customer_id }}
                            </td>

                            <!-- 2. Live Status (Real-time Online / Offline) -->
                            <td class="text-center whitespace-nowrap">
                                @php
                                    $isLiveOnline = ($c->online_status === 'online');
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border shadow-2xs whitespace-nowrap {{ $isLiveOnline ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isLiveOnline ? 'bg-emerald-500 ring-2 ring-emerald-300 animate-pulse' : 'bg-rose-500 ring-2 ring-rose-200' }}"></span>
                                    <span>{{ $isLiveOnline ? __('Online') : __('Offline') }}</span>
                                </span>
                            </td>

                            <!-- 3. MikroTik (Direct Real-time MikroTik /ppp/secret State) -->
                            @if(!$isOnlineRoute)
                                <td class="text-center whitespace-nowrap">
                                    @php
                                        $initialMikrotik = $c->mikrotik_status ?: 'enabled';
                                        $isMktEnabled = ($initialMikrotik === 'enabled');
                                    @endphp
                                    @if(!$authUser || !$authUser->isCollector())
                                    <button type="button" 
                                            @click="toggleMikrotikState({{ $c->id }}, '{{ addslashes($c->username) }}', '{{ $initialMikrotik }}', $event)" 
                                            data-mikrotik-state="{{ $initialMikrotik }}"
                                            title="{{ $isMktEnabled ? __('MikroTik Secret: ENABLED (Click to Disable)') : __('MikroTik Secret: DISABLED (Click to Enable)') }}"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs whitespace-nowrap {{ $isMktEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isMktEnabled ? 'bg-emerald-500 ring-2 ring-emerald-300' : 'bg-rose-500' }}"></span>
                                        <span>{{ $isMktEnabled ? __('Enabled') : __('Disabled') }}</span>
                                    </button>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border shadow-2xs whitespace-nowrap {{ $isMktEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isMktEnabled ? 'bg-emerald-500 ring-2 ring-emerald-300' : 'bg-rose-500' }}"></span>
                                        <span>{{ __(ucfirst($initialMikrotik)) }}</span>
                                    </span>
                                    @endif
                                </td>
                            @endif

                            <!-- Status (Active / Expired / Suspended / Due) -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $statusBadge['class'] }} shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 4. Name -->
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('tenant.customers.show', $c->id) }}" class="hover:underline hover:text-cyan-600">
                                    {{ $c->name }}
                                </a>
                            </td>

                            <!-- 5. Mobile -->
                            <td class="font-mono text-slate-700">
                                {{ $c->phone }}
                            </td>

                            <!-- 6. PPPoE Username -->
                            <td class="font-mono text-cyan-900 font-semibold">
                                {{ $c->username }}
                            </td>

                            <!-- Zone -->
                            <td class="text-slate-700">
                                {{ $c->zone ?: ($c->coverageZone?->name ?? '--') }}
                            </td>

                            <!-- 7. Partner / Scope -->
                            <td class="text-center font-mono">
                                @if($c->reseller)
                                    <span class="inline-flex items-center gap-1 text-indigo-700 font-semibold" title="{{ $c->reseller->name }}">
                                        <i class="fas fa-handshake text-indigo-500 text-[10px]"></i>
                                        <span>{{ $c->reseller->code ?: ($c->reseller->prefix ?: $c->reseller->name) }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-purple-700 font-semibold" title="{{ __(':company (Direct Retail)', ['company' => $tenant->company_name ?? $tenant->name]) }}">
                                        <i class="fas fa-building text-purple-500 text-[10px]"></i>
                                        <span>{{ $tenant->company_code ?? 'TDC' }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Package Name -->
                            <td class="text-slate-800 text-center">
                                {{ $packageName }}
                            </td>

                            @if($isOnlineRoute)
                                <!-- Download (RX) Column -->
                                <td class="text-right font-mono text-emerald-700 font-semibold whitespace-nowrap">
                                    <span x-show="liveTrafficMap['{{ $c->id }}']" 
                                          class="inline-flex items-center gap-1" 
                                          :title="i18n.totalRxTooltip.replace(':value', liveTrafficMap['{{ $c->id }}']?.total_rx_human || '0 B')">
                                        <i class="fas fa-arrow-down text-[9px] text-emerald-500" :class="{'animate-pulse text-emerald-600': isTableLiveStreaming}"></i>
                                        <span x-text="liveTrafficMap['{{ $c->id }}']?.rx_human || '0.00 Mbps'">0.00 Mbps</span>
                                    </span>
                                    <button x-show="!liveTrafficMap['{{ $c->id }}']" 
                                            type="button" 
                                            @click="fetchSingleRowTraffic({{ $c->id }}, '{{ $c->username }}')"
                                            :disabled="loadingTrafficMap['{{ $c->id }}']"
                                            class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 cursor-pointer inline-flex items-center gap-1 transition shadow-2xs">
                                        <i class="fas fa-arrow-down text-[8px]" :class="{'fa-spin': loadingTrafficMap['{{ $c->id }}']}"></i>
                                        <span x-text="loadingTrafficMap['{{ $c->id }}'] ? '...' : '0.00 Mbps'">0.00 Mbps</span>
                                    </button>
                                </td>

                                <!-- Upload (TX) Column -->
                                <td class="text-right font-mono text-purple-700 font-semibold whitespace-nowrap">
                                    <span x-show="liveTrafficMap['{{ $c->id }}']" 
                                          class="inline-flex items-center gap-1" 
                                          :title="i18n.totalTxTooltip.replace(':value', liveTrafficMap['{{ $c->id }}']?.total_tx_human || '0 B')">
                                        <i class="fas fa-arrow-up text-[9px] text-purple-500" :class="{'animate-pulse text-purple-600': isTableLiveStreaming}"></i>
                                        <span x-text="liveTrafficMap['{{ $c->id }}']?.tx_human || '0.00 Mbps'">0.00 Mbps</span>
                                    </span>
                                    <button x-show="!liveTrafficMap['{{ $c->id }}']" 
                                            type="button" 
                                            @click="fetchSingleRowTraffic({{ $c->id }}, '{{ $c->username }}')"
                                            :disabled="loadingTrafficMap['{{ $c->id }}']"
                                            class="px-1.5 py-0.5 rounded text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 cursor-pointer inline-flex items-center gap-1 transition shadow-2xs">
                                        <i class="fas fa-arrow-up text-[8px]" :class="{'fa-spin': loadingTrafficMap['{{ $c->id }}']}"></i>
                                        <span x-text="loadingTrafficMap['{{ $c->id }}'] ? '...' : '0.00 Mbps'">0.00 Mbps</span>
                                    </button>
                                </td>
                            @else
                                <!-- 9. Monthly Bill -->
                                <td class="text-right font-mono text-slate-800">
                                    @currency($c->monthly_bill)
                                </td>
                            @endif

                            @if($isDueRoute)
                                <!-- Due Amount Column (Prominently Highlighted for Due Page) -->
                                <td class="text-right font-mono font-bold {{ $c->due_amount > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                    @currency($c->due_amount > 0 ? $c->due_amount : $c->monthly_bill)
                                </td>
                            @endif

                            <!-- 11. Expiry Date -->
                            <td class="font-mono text-slate-600">
                                {{ $c->expiry_date ? $c->expiry_date->format('d M Y') : '--' }}
                            </td>

                            <!-- 12. Validity / Remaining / Overdue -->
                            <td class="text-center font-mono whitespace-nowrap" data-sort-value="{{ $expiryBadge['days'] !== null ? $expiryBadge['days'] : 99999 }}">
                                @if($expiryBadge['state'] === 'none')
                                    <span class="text-slate-400">--</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] border {{ $expiryBadge['class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $expiryBadge['dot'] }}"></span>
                                        <span>{{ __($expiryBadge['label']) }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 13. Action (3-Dot Floating Action Menu) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($rawCustomerData) }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="{{ $isDueRoute ? 15 : 14 }}" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-users-slash text-2xl text-slate-300 mb-2 block"></i>
                                {{ __('No customer or subscriber records found. (Database is empty)') }}
                            </td>
                        </tr>
                    @endforelse
                    <!-- Instant Live Search No Match Indicator -->
                    <tr x-show="searchQuery && totalMatchingRows === 0" x-cloak class="no-record-row">
                        <td colspan="{{ $isDueRoute ? 15 : 14 }}" class="px-4 py-8 text-center text-slate-400 text-xs bg-slate-50/50">
                            <i class="fas fa-magnifying-glass text-xl text-slate-300 mb-2 block"></i>
                            <span>{{ __('No subscribers matched your search on this page.') }}</span>
                            <span class="block text-[11px] text-slate-400 mt-1">{{ __('Press Enter to query the entire database...') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($customers->hasPages())
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (Fixed z-50 with BoundingClientRect Positioning) -->
    <div x-show="activeMenu !== null" 
         x-cloak
         @click.outside="activeMenu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 text-left text-xs divide-y divide-slate-100">
        
        <div class="py-1">
            <!-- View Full Profile (Dedicated Page) -->
            <a :href="'{{ url('admin/customers') }}/' + (activeMenu ? activeMenu.id : '')" 
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-arrow-up-right-from-square text-cyan-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View Full Profile') }}</span>
            </a>

            <!-- Receive Bill Payment (Direct Pay Modal) -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openSinglePayModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-money-bill-wave text-emerald-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Receive Bill Payment') }}</span>
            </button>

            @if(!$authUser || !$authUser->isCollector())
            <!-- Quick Renew Line -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openRenewModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-bolt text-teal-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Quick Renew Line') }}</span>
            </button>

            <!-- Edit Subscriber (Dedicated Full Page) -->
            <a :href="'{{ url('admin/customers') }}/' + (activeMenu ? activeMenu.id : '') + '/edit'" 
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-pen-to-square text-indigo-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Edit Subscriber') }}</span>
            </a>

            <!-- Sync to MikroTik & RADIUS -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; syncSingleMikrotik(item.id, item.name)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-arrows-rotate text-blue-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Sync MikroTik & RADIUS') }}</span>
            </button>

            <!-- Kick Active Session -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; confirmKickSession(item.id, item.name)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-plug-circle-xmark text-rose-500 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Kick Active Session') }}</span>
            </button>

            <!-- Ping Test IP -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; runPingTest(item.id, item.name, item.username)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-cyan-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-terminal text-cyan-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Ping Test IP') }}</span>
            </button>
            @endif
        </div>

        @if(!$authUser || !$authUser->isCollector())
        <div class="py-1">
            <!-- Enable / Disable Subscriber -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; toggleStatus(item.id)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 transition text-left cursor-pointer"
                    :class="activeMenu?.status === 'active' ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800'">
                <i class="fas w-3.5 text-center text-[11px]" :class="activeMenu?.status === 'active' ? 'fa-toggle-off text-rose-500' : 'fa-toggle-on text-emerald-600'"></i>
                <span x-text="activeMenu?.status === 'active' ? i18n.disableSubscriber : i18n.enableSubscriber"></span>
            </button>

            <!-- Delete Customer -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; confirmDelete(item.id, item.name)" 
                    class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-trash-can text-rose-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Delete Subscriber') }}</span>
            </button>
        </div>
        @endif
    </div>



    <!-- ========================================================================= -->
    <!-- PRODUCTION-GRADE NATURAL MODALS                                           -->
    <!-- ========================================================================= -->

    <!-- MODAL 0A: DIRECT SINGLE BILL PAYMENT MODAL (Natural White Production Grade) -->
    <div x-show="payModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!payModal.loading) payModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Natural Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800">{{ __('Receive Customer Bill Payment') }}</h4>
                        <p class="text-[10.5px] text-slate-500 font-normal">
                            {{ __('Subscriber:') }} <span class="font-mono font-bold text-slate-700" x-text="payModal.username || i18n.client"></span> &bull; 
                            {{ __('ID:') }} <span class="font-mono text-slate-600" x-text="payModal.customer_code || ('CUST-' + payModal.customer_id)"></span>
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="payModal.open = false" 
                        :disabled="payModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitSinglePayment()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">

                    <!-- 1. Customer Financial Snapshot Card -->
                    <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">{{ __('Current Due Amount') }}</span>
                            <div class="text-base font-bold font-mono flex items-baseline gap-1" :class="Number(payModal.due_amount) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                <span class="text-xs font-sans opacity-70">{{ $currencySymbol ?? '৳' }}</span>
                                <span x-text="Number(payModal.due_amount || 0).toFixed(2)"></span>
                            </div>
                            <div class="text-[10px] text-slate-500">
                                {{ __('Monthly Plan:') }} <strong class="text-slate-700 font-mono inline-flex items-baseline gap-0.5"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(payModal.monthly_bill || 0).toFixed(2)"></span></strong>
                            </div>
                        </div>

                        <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                            <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">{{ __('Expiry Status') }}</span>
                            <div class="text-xs font-bold text-slate-800">
                                <span x-text="payModal.expiry_date || '--'"></span>
                            </div>
                            <div class="text-[10px]">
                                <span class="text-slate-500 font-sans" x-text="payModal.customer_name"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Payment Type / Mode Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Payment Mode') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Mode 1: Due / Regular Bill -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'due' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="due" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-file-invoice text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">{{ __('Due / Regular') }}</span>
                                <span class="text-[9.5px] text-slate-500">{{ __('Due / Monthly Bill') }}</span>
                            </label>

                            <!-- Mode 2: Advance Multi-Month Recharge -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'advance' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="advance" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-calendar-plus text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">{{ __('Advance Pay') }}</span>
                                <span class="text-[9.5px] text-slate-500">{{ __('Advance Recharge') }}</span>
                            </label>

                            <!-- Mode 3: Custom Amount -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'custom' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="custom" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-coins text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">{{ __('Custom Amount') }}</span>
                                <span class="text-[9.5px] text-slate-500">{{ __('Partial / Custom') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Advance Multi-Month Selector (When mode == advance) -->
                    <template x-if="payModal.payment_mode === 'advance'">
                        <div class="space-y-1.5 p-2.5 rounded-lg bg-teal-50/40 border border-teal-100">
                            <label class="block text-teal-900 font-semibold text-[11px]">
                                {{ __('Select Advance Duration') }}
                            </label>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <template x-for="m in [1, 2, 3, 6, 12]" :key="m">
                                    <button type="button" 
                                            @click="setAdvanceMonths(m)"
                                            class="px-2.5 py-1 rounded-md text-xs font-semibold font-mono transition border cursor-pointer"
                                            :class="payModal.months === m ? 'bg-teal-600 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'">
                                        <span x-text="m === 12 ? i18n.oneYear12m : (m === 1 ? i18n.oneMonth : i18n.nMonths.replace(':count', m))"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- 4. Amount & Discount Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Received Amount -->
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Collected Amount') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                                    {{ $currencySymbol ?? '৳' }}
                                </div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       required
                                       x-model="payModal.amount" 
                                       placeholder="0.00"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            </div>
                        </div>

                        <!-- Discount / Waiver -->
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Special Discount') }}
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                                    {{ $currencySymbol ?? '৳' }}
                                </div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       x-model="payModal.discount" 
                                       placeholder="0.00"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Payment Channel Selection -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                            <!-- Cash -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'cash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'cash' ? 'bg-teal-50 border-teal-500 ring-1 ring-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-money-bill text-emerald-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Cash') }}</span>
                            </button>

                            <!-- bKash -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'bkash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'bkash' ? 'bg-pink-50 border-pink-500 ring-1 ring-pink-400 text-pink-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-mobile-alt text-pink-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('bKash') }}</span>
                            </button>

                            <!-- Nagad -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'nagad'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'nagad' ? 'bg-orange-50 border-orange-500 ring-1 ring-orange-400 text-orange-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-wallet text-orange-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Nagad') }}</span>
                            </button>

                            <!-- Rocket -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'rocket'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'rocket' ? 'bg-purple-50 border-purple-500 ring-1 ring-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-rocket text-purple-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Rocket') }}</span>
                            </button>

                            <!-- Bank -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'bank_transfer'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'bank_transfer' ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-400 text-blue-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-university text-blue-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Bank') }}</span>
                            </button>

                            <!-- Card / POS -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'pos'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'pos' ? 'bg-indigo-50 border-indigo-500 ring-1 ring-indigo-400 text-indigo-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-credit-card text-indigo-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('POS / Card') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- 6. Billing Month & Trx ID Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Billing Month') }}
                            </label>
                            <input type="text" 
                                   x-model="payModal.billing_month" 
                                   placeholder="{{ __('e.g. :month', ['month' => date('F Y')]) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Trx ID / Bank Ref') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                            </label>
                            <input type="text" 
                                   x-model="payModal.transaction_id" 
                                   placeholder="{{ __('e.g. 9J28KLM...') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-teal-500 transition">
                        </div>
                    </div>

                    <!-- 7. Remarks / Note -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Payment Note / Remarks') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                        </label>
                        <input type="text" 
                               x-model="payModal.notes" 
                               placeholder="{{ __('e.g. Collected by Area Agent / Counter...') }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                    </div>

                    <!-- 8. Options & Switches -->
                    <div class="space-y-2 pt-1 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="payModal.extend_validity" 
                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-slate-700 text-[11px] font-medium">
                                {{ __('Extend package validity / expiry date automatically') }}
                            </span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="payModal.reactivate_line" 
                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-slate-700 text-[11px] font-medium">
                                {{ __('Auto-reactivate line in MikroTik & FreeRADIUS if currently expired/suspended') }}
                            </span>
                        </label>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="payModal.open = false" 
                            :disabled="payModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        {{ __('Cancel') }}
                    </button>

                    <button type="submit" 
                            :disabled="payModal.loading || !payModal.amount || Number(payModal.amount) <= 0"
                            class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="payModal.loading ? 'fa-spinner fa-spin' : 'fa-receipt'"></i>
                        <span x-text="payModal.loading ? i18n.processing : i18n.confirmCollectPayment"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- MODAL 1: QUICK RENEW / RECHARGE MODAL -->
    <div x-show="renewModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="renewModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Instant Subscription Renewal') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Extend validity & update billing ledger') }}</p>
                    </div>
                </div>
                <button type="button" @click="renewModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitRenewal()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800" x-text="renewModal.name"></span>
                            <span class="font-mono font-bold text-emerald-600">{{ $currencySymbol ?? '৳' }}<span x-text="renewModal.monthly_bill"></span>/{{ __('mo') }}</span>
                        </div>
                        <span class="text-[10.5px] text-slate-500 block mt-0.5">{{ __('Current Expiry:') }} <span class="font-medium text-slate-700" x-text="renewModal.expiry_date"></span></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Billing Duration') }}</label>
                            <select x-model="renewModal.months" 
                                    @change="renewModal.amount_paid = renewModal.monthly_bill * renewModal.months"
                                    class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-emerald-500 focus:outline-hidden transition">
                                <option value="1">{{ __('1 Month (30 Days)') }}</option>
                                <option value="2">{{ __('2 Months (60 Days)') }}</option>
                                <option value="3">{{ __('3 Months (Quarterly)') }}</option>
                                <option value="6">{{ __('6 Months (Half-Yearly)') }}</option>
                                <option value="12">{{ __('12 Months (Yearly)') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Collected Amount (:currency)', ['currency' => $currencySymbol ?? '৳']) }}</label>
                            <input type="number" 
                                   step="0.01" 
                                   x-model="renewModal.amount_paid" 
                                   required
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold focus:bg-white focus:border-emerald-500 focus:outline-hidden transition">
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="renewModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="renewModal.loading"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="renewModal.loading" style="display: none;"></i>
                        <span>{{ __('Confirm & Renew Line') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: PRODUCTION-GRADE REAL-TIME ICMP PING DIAGNOSTICS -->
    <div x-show="pingModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!pingModal.loading) pingModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('ICMP Ping Diagnostics') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Real-time gateway packet telemetry & latency test') }}</p>
                    </div>
                </div>
                <button type="button" @click="pingModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs text-slate-800">
                <!-- Subscriber & Gateway Info Banner -->
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-900 truncate" x-text="pingModal.name"></span>
                            <span class="text-[10.5px] font-mono text-cyan-700 font-semibold" x-text="'(' + pingModal.username + ')'"></span>
                        </div>
                        <div class="text-[10.5px] text-slate-500 mt-0.5 flex items-center gap-2">
                            <span>{{ __('Gateway:') }} <span class="font-medium text-slate-700 font-mono" x-text="pingModal.gateway || i18n.detecting"></span></span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-[9px] uppercase font-semibold text-slate-400 block">{{ __('Target IP') }}</span>
                        <span class="font-mono font-bold text-cyan-700 text-xs" x-text="pingModal.ip || i18n.detectingIp"></span>
                    </div>
                </div>

                <!-- KPI Latency & Packet Loss Strip (4 Cards) -->
                <div class="grid grid-cols-4 gap-2">
                    <!-- Sent -->
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                        <span class="text-[9px] font-semibold text-slate-400 uppercase block">{{ __('Transmitted') }}</span>
                        <span class="text-xs font-mono font-bold text-slate-800" x-text="i18n.pktsCount.replace(':count', pingModal.sent)"></span>
                    </div>
                    <!-- Received -->
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                        <span class="text-[9px] font-semibold text-slate-400 uppercase block">{{ __('Received') }}</span>
                        <span class="text-xs font-mono font-bold" :class="pingModal.received > 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="i18n.pktsCount.replace(':count', pingModal.received)"></span>
                    </div>
                    <!-- Packet Loss -->
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                        <span class="text-[9px] font-semibold text-slate-400 uppercase block">{{ __('Packet Loss') }}</span>
                        <span class="text-xs font-mono font-bold" :class="pingModal.loss_percent === 0 ? 'text-emerald-600' : (pingModal.loss_percent < 50 ? 'text-amber-600' : 'text-rose-600')" x-text="pingModal.loss_percent + '%'"></span>
                    </div>
                    <!-- Avg Latency -->
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                        <span class="text-[9px] font-semibold text-slate-400 uppercase block">{{ __('Avg Latency') }}</span>
                        <span class="text-xs font-mono font-bold text-cyan-700" x-text="pingModal.rtt_avg"></span>
                    </div>
                </div>

                <!-- Live Interactive Terminal Output Console -->
                <div class="rounded-lg overflow-hidden border border-slate-800 shadow-inner">
                    <div class="bg-slate-900 px-3 py-1.5 flex items-center justify-between border-b border-slate-800/80 text-[10.5px]">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <span class="font-mono text-slate-400">console &gt; ping -c <span x-text="pingModal.count"></span> <span x-text="pingModal.ip || 'target'"></span></span>
                        </div>
                        <button type="button" 
                                @click="copyPingOutput()" 
                                title="{{ __('Copy ping console output') }}"
                                class="text-slate-400 hover:text-cyan-300 font-mono text-[10px] transition cursor-pointer inline-flex items-center gap-1">
                            <i class="fas fa-copy text-[9px]"></i>
                            <span>{{ __('Copy') }}</span>
                        </button>
                    </div>
                    <div class="bg-slate-950 p-3 font-mono text-[11px] leading-relaxed max-h-[160px] overflow-y-auto space-y-1 select-text">
                        <template x-if="pingModal.loading">
                            <div class="flex items-center gap-2 text-cyan-400 py-4 justify-center">
                                <i class="fas fa-circle-notch fa-spin text-sm"></i>
                                <span class="animate-pulse">{{ __('Transmitting ICMP echo requests through MikroTik gateway...') }}</span>
                            </div>
                        </template>
                        <template x-if="!pingModal.loading && pingModal.lines.length === 0">
                            <div class="text-slate-500 italic py-3 text-center">
                                {{ __("Ready to ping target host. Click 'Send Ping' below.") }}
                            </div>
                        </template>
                        <template x-if="!pingModal.loading && pingModal.lines.length > 0">
                            <div>
                                <template x-for="(line, idx) in pingModal.lines" :key="idx">
                                    <div :class="line.includes('Destination Host Unreachable') || line.includes('timeout') || line.includes('loss') ? 'text-rose-400' : 'text-emerald-400'" x-text="line"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Ping Execution Options -->
                <div class="flex items-center justify-between gap-2 pt-0.5">
                    <div class="flex items-center gap-2">
                        <label class="text-[11px] font-medium text-slate-600">{{ __('Packet Count:') }}</label>
                        <select x-model.number="pingModal.count" 
                                :disabled="pingModal.loading"
                                class="px-2 py-1 bg-slate-50 border border-slate-200 rounded text-xs font-mono font-medium focus:bg-white focus:border-cyan-500">
                            <option value="3">{{ __(':count Packets', ['count' => 3]) }}</option>
                            <option value="4">{{ __(':count Packets', ['count' => 4]) }}</option>
                            <option value="5">{{ __(':count Packets', ['count' => 5]) }}</option>
                            <option value="10">{{ __(':count Packets', ['count' => 10]) }}</option>
                        </select>
                    </div>

                    <div class="text-right">
                        <template x-if="!pingModal.loading && pingModal.sent > 0">
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold" :class="pingModal.success ? 'text-emerald-700' : 'text-rose-700'">
                                <i class="fas" :class="pingModal.success ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-rose-500'"></i>
                                <span x-text="pingModal.success ? i18n.hostReachable : i18n.hostUnreachable"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="pingModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    {{ __('Close') }}
                </button>
                <button type="button" 
                        @click="executePing()" 
                        :disabled="pingModal.loading"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-spinner fa-spin text-xs" x-show="pingModal.loading" style="display: none;"></i>
                    <i class="fas fa-play text-xs" x-show="!pingModal.loading"></i>
                    <span x-text="pingModal.loading ? i18n.pingingHost : (pingModal.sent > 0 ? i18n.rePingTarget : i18n.sendPing)"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: PRODUCTION-GRADE BULK CSV IMPORT & MIKROTIK PROVISIONING -->
    <div x-show="importModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden"
             @click.away="if (!importModal.loading) importModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-arrow-up"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Bulk Customer Import (CSV / Excel)') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Batch onboarding with automatic PPPoE provisioning & ledger binding') }}</p>
                    </div>
                </div>
                <button type="button" @click="if (!importModal.loading) importModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3.5 text-xs text-slate-800 max-h-[75vh] overflow-y-auto">
                
                <!-- Step 1: Download Standard Template Banner -->
                <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200/70 flex items-center justify-between gap-3">
                    <div class="space-y-0.5 min-w-0">
                        <span class="font-semibold text-amber-900 block">{{ __('Download Standard CSV Template') }}</span>
                        <p class="text-[10.5px] text-amber-800/80">{{ __('Pre-formatted header columns for PPPoE credentials, billing rate, IP, ONU MAC & router assignment.') }}</p>
                    </div>
                    <a href="{{ route('tenant.customers.import-template') }}" 
                       class="px-3 py-1.5 rounded-lg bg-white hover:bg-amber-100 text-amber-800 font-semibold text-xs border border-amber-300 shadow-2xs transition inline-flex items-center gap-1.5 flex-shrink-0">
                        <i class="fas fa-download text-[11px] text-amber-600"></i>
                        <span>{{ __('Download Sample') }}</span>
                    </a>
                </div>

                <!-- Step 2: Drag & Drop File Upload Dropzone -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-medium text-slate-700">{{ __('Upload CSV File') }} <span class="text-rose-500">*</span></label>
                    <div class="border-2 border-dashed rounded-xl p-4 text-center transition cursor-pointer"
                         :class="importModal.file ? 'border-amber-400 bg-amber-50/30' : 'border-slate-300 hover:border-amber-400 bg-slate-50/50 hover:bg-amber-50/20'"
                         @click="$refs.csvFileInput.click()">
                        <input type="file" 
                               x-ref="csvFileInput" 
                               @change="onImportFileSelected($event)" 
                               accept=".csv,.txt" 
                               class="hidden">
                        
                        <template x-if="!importModal.file">
                            <div class="space-y-1">
                                <i class="fas fa-cloud-arrow-up text-2xl text-slate-400 mb-1"></i>
                                <div class="font-semibold text-slate-700">{{ __('Click to browse or drag & drop CSV file here') }}</div>
                                <div class="text-[10px] text-slate-400">{{ __('Supported format: CSV (UTF-8) up to 10MB') }}</div>
                            </div>
                        </template>

                        <template x-if="importModal.file">
                            <div class="flex items-center justify-between bg-white p-2 rounded-lg border border-amber-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <i class="fas fa-file-csv text-xl text-emerald-600 flex-shrink-0"></i>
                                    <div class="text-left min-w-0">
                                        <span class="font-semibold text-slate-800 block truncate" x-text="importModal.fileName"></span>
                                        <span class="text-[10px] text-slate-400" x-text="importModal.fileSize"></span>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click.stop="importModal.file = null; importModal.fileName = ''; importModal.fileSize = '';" 
                                        class="p-1 rounded text-rose-500 hover:bg-rose-50 transition cursor-pointer">
                                    <i class="fas fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Step 3: Batch Configuration Parameters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Default Gateway Router') }}</label>
                        <select x-model="importModal.default_router_id" 
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-amber-500 focus:outline-hidden transition">
                            <option value="">-- {{ __('Auto-detect / Core Router') }} --</option>
                            @foreach($allRouters as $rtr)
                                <option value="{{ $rtr->id }}">{{ $rtr->name }} ({{ $rtr->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Default Scope / Reseller') }}</label>
                        <select x-model="importModal.default_reseller_id" 
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-amber-500 focus:outline-hidden transition">
                            <option value="">{{ __(':company Direct Retail (:code)', ['company' => $tenant->company_name ?? __('ISP'), 'code' => $tenant->company_code ?? 'TDC']) }}</option>
                            @foreach($allResellers as $r)
                                <option value="{{ $r->id }}">{{ __('Sub-ISP: :name (:code)', ['name' => $r->name, 'code' => $r->code]) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Duplicate Username Policy') }}</label>
                        <select x-model="importModal.duplicate_action" 
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-amber-500 focus:outline-hidden transition">
                            <option value="skip">{{ __('Skip Duplicate PPPoE Usernames') }}</option>
                            <option value="update">{{ __('Overwrite & Update Existing Records') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="importModal.sync_mikrotik" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 w-4 h-4">
                            <span class="text-xs font-semibold text-slate-700">{{ __('Auto-Provision to MikroTik (/ppp/secret)') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Step 4: Live Execution Telemetry & Terminal Console -->
                <template x-if="importModal.loading || importModal.logs.length > 0">
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <!-- KPI Strip -->
                        <div class="grid grid-cols-4 gap-2">
                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                                <span class="text-[9px] font-semibold text-slate-400 uppercase block">{{ __('Total Rows') }}</span>
                                <span class="text-xs font-mono font-bold text-slate-800" x-text="importModal.total_rows"></span>
                            </div>
                            <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-center">
                                <span class="text-[9px] font-semibold text-emerald-700 uppercase block">{{ __('Created') }}</span>
                                <span class="text-xs font-mono font-bold text-emerald-700" x-text="importModal.imported_count"></span>
                            </div>
                            <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-center">
                                <span class="text-[9px] font-semibold text-amber-700 uppercase block">{{ __('Updated / Skipped') }}</span>
                                <span class="text-xs font-mono font-bold text-amber-700" x-text="(importModal.updated_count + importModal.skipped_count)"></span>
                            </div>
                            <div class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-center">
                                <span class="text-[9px] font-semibold text-rose-700 uppercase block">{{ __('Errors') }}</span>
                                <span class="text-xs font-mono font-bold text-rose-700" x-text="importModal.errors_count"></span>
                            </div>
                        </div>

                        <!-- Terminal Output Screen -->
                        <div class="rounded-lg overflow-hidden border border-slate-800 shadow-inner">
                            <div class="bg-slate-900 px-3 py-1.5 flex items-center justify-between border-b border-slate-800/80 text-[10.5px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="font-mono text-slate-400">import_engine &gt; execution_log</span>
                                </div>
                                <button type="button" 
                                        @click="copyImportLogs()" 
                                        class="text-slate-400 hover:text-amber-300 font-mono text-[10px] transition cursor-pointer inline-flex items-center gap-1">
                                    <i class="fas fa-copy text-[9px]"></i>
                                    <span>{{ __('Copy Log') }}</span>
                                </button>
                            </div>
                            <div class="bg-slate-950 p-3 font-mono text-[11px] leading-relaxed max-h-[140px] overflow-y-auto space-y-1 select-text">
                                <template x-if="importModal.loading">
                                    <div class="flex items-center gap-2 text-amber-400 py-2 justify-center">
                                        <i class="fas fa-circle-notch fa-spin text-sm"></i>
                                        <span class="animate-pulse">{{ __('Parsing CSV records, validating profiles & provisioning MikroTik...') }}</span>
                                    </div>
                                </template>
                                <template x-for="(log, idx) in importModal.logs" :key="idx">
                                    <div :class="log.includes('Skipped') ? 'text-amber-400' : (log.includes('Error') || log.includes('error') ? 'text-rose-400' : 'text-emerald-400')" x-text="log"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <template x-if="importModal.completed">
                    <button type="button" 
                            @click="window.location.reload()" 
                            class="text-cyan-700 hover:text-cyan-800 font-semibold text-xs inline-flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-arrows-rotate text-[10px]"></i>
                        <span>{{ __('Reload Customer Table') }}</span>
                    </button>
                </template>
                <template x-if="!importModal.completed">
                    <div></div>
                </template>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="importModal.open = false" 
                            :disabled="importModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition disabled:opacity-50">
                        <span x-text="importModal.completed ? i18n.closeWindow : i18n.cancel"></span>
                    </button>
                    <button type="button" 
                            @click="submitBulkImport()" 
                            :disabled="importModal.loading || !importModal.file"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="importModal.loading" style="display: none;"></i>
                        <i class="fas fa-file-arrow-up text-xs" x-show="!importModal.loading"></i>
                        <span x-text="importModal.loading ? i18n.importingSubscribers : i18n.executeBulkImport"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: PRODUCTION-GRADE SUBSCRIBER EXPORT MODAL -->
    <div x-show="exportModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="exportModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-arrow-down"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Export Customer Master Database') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Download structured records formatted for Excel & Google Sheets') }}</p>
                    </div>
                </div>
                <button type="button" @click="exportModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Export Body -->
            <div class="p-4 space-y-3.5 text-xs text-slate-800">
                
                <!-- Preset Selection -->
                <div class="space-y-2">
                    <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">{{ __('Select Export Preset') }}</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        
                        <!-- Preset 1: Full Master Dump -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'master' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'master'">
                            <i class="fas fa-database text-emerald-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">{{ __('Master Dump') }}</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">{{ __('All 22+ columns (Profiles, Billing, IP, MikroTik & ONU)') }}</span>
                            </div>
                        </div>

                        <!-- Preset 2: Billing & Ledger -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'billing' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'billing'">
                            <i class="fas fa-file-invoice-dollar text-indigo-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">{{ __('Billing & Ledger') }}</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">{{ __('Monthly bills, due amounts, wallet balance & expiry') }}</span>
                            </div>
                        </div>

                        <!-- Preset 3: Technical & MikroTik -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'technical' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'technical'">
                            <i class="fas fa-network-wired text-cyan-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">{{ __('Technical & Router') }}</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">{{ __('PPPoE passwords, Framed IP, ONU MAC, Router & OLT') }}</span>
                            </div>
                        </div>

                        <!-- Preset 4: Contact Directory -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'contacts' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'contacts'">
                            <i class="fas fa-address-book text-amber-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">{{ __('Contacts Directory') }}</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">{{ __('Names, mobile phones, email, NID & address') }}</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Scope & Filter Adjustments -->
                <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Subscriber Scope') }}</label>
                        <select x-model="exportModal.scope" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-emerald-500">
                            <option value="">{{ __('All Subscribers (:code + Resellers)', ['code' => $tenant->company_code ?? 'TDC']) }}</option>
                            <option value="isp">{{ __('ISP Direct Retail Only') }}</option>
                            <option value="all_resellers">{{ __('Sub-ISP Franchise Only') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Account Status') }}</label>
                        <select x-model="exportModal.status" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-emerald-500">
                            <option value="all">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active Only') }}</option>
                            <option value="due">{{ __('Due Only') }}</option>
                            <option value="expired">{{ __('Expired Only') }}</option>
                            <option value="suspended">{{ __('Suspended Only') }}</option>
                        </select>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-[10.5px] text-slate-600 flex items-center gap-2">
                    <i class="fas fa-circle-info text-cyan-600 text-xs flex-shrink-0"></i>
                    <span>{{ __('Exported files automatically include UTF-8 BOM encoding for seamless display in Microsoft Excel and Google Sheets without font distortion.') }}</span>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="exportModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        @click="triggerExport()" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-arrow-down text-xs"></i>
                    <span>{{ __('Download CSV') }}</span>
                </button>
            </div>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- MODAL 6: MIKROTIK ROUTER SUBSCRIBER IMPORT MODAL (Natural White Production Grade) -->
    <!-- ========================================================================= -->
    <div x-show="mikrotikImportModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="if (!mikrotikImportModal.loading) mikrotikImportModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Import Customers from MikroTik') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">
                            {{ __('Pull /ppp/secret records and sync them into ISP ERP & FreeRADIUS') }}
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="mikrotikImportModal.open = false" 
                        :disabled="mikrotikImportModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- Select Router -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Select MikroTik Router') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="mikrotikImportModal.router_id" 
                            @change="fetchRouterPreview()"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 transition">
                        <option value="">-- {{ __('Choose Router') }} --</option>
                        @foreach($allRouters as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Live Preview State -->
                <div x-show="mikrotikImportModal.previewing" class="py-3 text-center text-slate-500 text-xs">
                    <i class="fas fa-spinner fa-spin mr-1.5 text-indigo-600"></i> {{ __('Querying /ppp/secret from RouterOS API...') }}
                </div>

                <div x-show="mikrotikImportModal.error_message" class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-xs flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-rose-500"></i>
                    <span x-text="mikrotikImportModal.error_message"></span>
                </div>

                <!-- Preview Metric Cards Strip (If data loaded) -->
                <div x-show="mikrotikImportModal.preview_data && !mikrotikImportModal.previewing" class="space-y-2.5">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                            <span class="text-[9px] uppercase tracking-wider text-slate-500 font-medium block">{{ __('On Router') }}</span>
                            <span class="text-sm font-bold font-mono text-slate-800" x-text="mikrotikImportModal.preview_data?.total_secrets || 0"></span>
                        </div>
                        <div class="p-2 bg-emerald-50/70 rounded-lg border border-emerald-200/80 text-center">
                            <span class="text-[9px] uppercase tracking-wider text-emerald-600 font-semibold block">{{ __('New To Import') }}</span>
                            <span class="text-sm font-bold font-mono text-emerald-700" x-text="mikrotikImportModal.preview_data?.new_count || 0"></span>
                        </div>
                        <div class="p-2 bg-slate-100 rounded-lg border border-slate-200 text-center">
                            <span class="text-[9px] uppercase tracking-wider text-slate-600 font-medium block">{{ __('Already in DB') }}</span>
                            <span class="text-sm font-bold font-mono text-slate-700" x-text="mikrotikImportModal.preview_data?.existing_count || 0"></span>
                        </div>
                    </div>

                    <!-- Compact Preview List -->
                    <template x-if="mikrotikImportModal.preview_data?.preview_items?.length > 0">
                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200/80 space-y-1.5">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-600 flex items-center justify-between">
                                <span>{{ __('Sample New Users Detected') }}</span>
                                <span class="font-mono text-slate-500" x-text="i18n.showingCount.replace(':count', mikrotikImportModal.preview_data.preview_items.length)"></span>
                            </div>
                            <div class="max-h-28 overflow-y-auto space-y-1 pr-1">
                                <template x-for="item in mikrotikImportModal.preview_data.preview_items" :key="item.username">
                                    <div class="flex items-center justify-between bg-white px-2 py-1 rounded border border-slate-200/60 text-[11px]">
                                        <div class="flex items-center gap-1.5 font-mono font-medium text-slate-800">
                                            <i class="fas fa-user text-[9px] text-slate-400"></i>
                                            <span x-text="item.username"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[10px]">
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" x-text="item.profile"></span>
                                            <span class="text-slate-400 truncate max-w-[120px]" x-text="item.comment || ''"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Configuration Options -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Fallback Package (If profile unmatched)') }}
                        </label>
                        <select x-model="mikrotikImportModal.default_package_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500">
                            <option value="">-- {{ __('Auto Match Profile') }} --</option>
                            @foreach($allPackages as $pkg)
                                <option value="{{ $pkg->id }}">{{ $pkg->name }} (@currency($pkg->price))</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Default Coverage Zone') }}
                        </label>
                        <select x-model="mikrotikImportModal.zone_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500">
                            <option value="">-- {{ __('Unassigned') }} --</option>
                            @foreach($distinctZones as $z)
                                <option value="{{ is_object($z) ? $z->id : $z }}">{{ is_object($z) ? $z->name : $z }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Update Existing Checkbox -->
                <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200/80 cursor-pointer select-none">
                    <input type="checkbox" x-model="mikrotikImportModal.update_existing" class="rounded text-cyan-600 focus:ring-cyan-500 w-3.5 h-3.5">
                    <span class="text-[11px] text-slate-700">{{ __('Update password, profile & router for existing customers') }}</span>
                </label>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="mikrotikImportModal.open = false" 
                        :disabled="mikrotikImportModal.loading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" 
                        @click="submitMikrotikImport()" 
                        :disabled="mikrotikImportModal.loading || !mikrotikImportModal.router_id || mikrotikImportModal.previewing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-file-import text-xs" :class="{'fa-spin': mikrotikImportModal.loading}"></i>
                    <span x-text="mikrotikImportModal.loading ? i18n.importingRecords : i18n.startImportToDatabase"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function customerMasterManager() {
    return {
        i18n: {
            checking: @json(__('Checking...')),
            refreshSessions: @json(__('Refresh Sessions')),
            liveRealtimeOn: @json(__('Live Real-time: ON')),
            checkSpeedsRxTx: @json(__('Check Speeds (RX/TX)')),
            syncing: @json(__('Syncing...')),
            twoWaySmartSync: @json(__('2-Way Smart Sync')),
            enabled: @json(__('Enabled')),
            disabled: @json(__('Disabled')),
            mikrotikSecretEnabledTooltip: @json(__('MikroTik Secret: ENABLED (Click to Disable)')),
            mikrotikSecretDisabledTooltip: @json(__('MikroTik Secret: DISABLED (Click to Enable)')),
            enableSubscriber: @json(__('Enable Subscriber')),
            disableSubscriber: @json(__('Disable Subscriber')),
            client: @json(__('Client')),
            totalRxTooltip: @json(__('Total Rx: :value')),
            totalTxTooltip: @json(__('Total Tx: :value')),
            oneYear12m: @json(__('1 Year (12m)')),
            oneMonth: @json(__(':count Month', ['count' => 1])),
            nMonths: @json(__(':count Months')),
            processing: @json(__('Processing...')),
            confirmCollectPayment: @json(__('Confirm & Collect Payment')),
            detecting: @json(__('Detecting...')),
            detectingIp: @json(__('Detecting IP...')),
            pktsCount: @json(__(':count pkts')),
            hostReachable: @json(__('Host Reachable')),
            hostUnreachable: @json(__('Host Unreachable')),
            pingingHost: @json(__('Pinging Host...')),
            rePingTarget: @json(__('Re-Ping Target')),
            sendPing: @json(__('Send Ping')),
            closeWindow: @json(__('Close Window')),
            cancel: @json(__('Cancel')),
            importingSubscribers: @json(__('Importing Subscribers...')),
            executeBulkImport: @json(__('Execute Bulk Import')),
            showingCount: @json(__('Showing :count')),
            importingRecords: @json(__('Importing Records...')),
            startImportToDatabase: @json(__('Start Import to Database')),
            liveStatusSynced: @json(__('Live status synced: :count active sessions online.')),
            paymentFailed: @json(__('Payment collection failed.')),
            paymentNetworkError: @json(__('Network error while processing payment.')),
            renewalFailed: @json(__('Renewal failed.')),
            renewalNetworkError: @json(__('Network error during renewal.')),
            mikrotikToggleFailed: @json(__('Failed to toggle MikroTik state.')),
            mikrotikToggleNetworkError: @json(__('Network error toggling MikroTik status.')),
            statusToggleFailed: @json(__('Failed to toggle status.')),
            statusToggleNetworkError: @json(__('Network error while updating status.')),
            syncingCustomer: @json(__('Syncing :name to MikroTik Router...')),
            customerFallback: @json(__('customer')),
            syncMikrotikFailed: @json(__('Failed to sync to MikroTik.')),
            syncMikrotikNetworkError: @json(__('Network error syncing to MikroTik.')),
            initiatingTwoWaySync: @json(__('Initiating 2-Way Smart Sync (MikroTik <-> Software <-> FreeRADIUS)...')),
            twoWaySyncFailed: @json(__('Two-way smart sync failed.')),
            twoWaySyncNetworkError: @json(__('Network error during two-way smart sync.')),
            routerConnectFailed: @json(__('Failed to connect to router.')),
            routerPreviewNetworkError: @json(__('Network error fetching router preview.')),
            selectMikrotikRouter: @json(__('Please select a MikroTik router.')),
            importFailed: @json(__('Import failed.')),
            mikrotikImportNetworkError: @json(__('Network error during MikroTik import.')),
            confirmDeleteCustomer: @json(__("Are you sure you want to delete customer ':name'? This action cannot be undone.")),
            deleteCustomerFailed: @json(__('Failed to delete customer.')),
            deleteCustomerNetworkError: @json(__('Network error while deleting customer.')),
            confirmKickSession: @json(__("Disconnect live PPPoE session for ':name'? This will terminate active session on MikroTik.")),
            terminatingSession: @json(__('Terminating active session for :name...')),
            kickSessionFailed: @json(__('Failed to disconnect session.')),
            kickSessionNetworkError: @json(__('Network error during session disconnect.')),
            hostUnreachableMsg: @json(__('Host unreachable.')),
            pingGatewayError: @json(__('Error: Failed to communicate with router gateway.')),
            pingNetworkError: @json(__('Network error during ping test.')),
            noTerminalOutputToCopy: @json(__('No terminal output to copy.')),
            pingOutputCopied: @json(__('Ping diagnostics output copied to clipboard!')),
            copyOutputFailed: @json(__('Failed to copy output.')),
            selectValidCsv: @json(__('Please select a valid CSV file to import.')),
            startingCsvImport: @json(__('Starting CSV import & parsing...')),
            csvImportFailedCheckLog: @json(__('CSV Import failed. Please check log for errors.')),
            csvImportNetworkError: @json(__('Network or server error occurred during CSV import.')),
            bulkImportNetworkError: @json(__('Network error during bulk import.')),
            noLogsToCopy: @json(__('No logs to copy.')),
            importLogsCopied: @json(__('Import logs copied to clipboard!')),
            startingCsvExport: @json(__('Starting CSV export download...')),
            queryingActiveSessions: @json(__('Querying active PPPoE sessions from MikroTik & FreeRADIUS...')),
            liveSpeedPaused: @json(__('Live real-time speed monitoring paused.')),
            liveSpeedActive: @json(__('Real-time RX/TX monitoring active! Updating live from MikroTik...'))
        },
        currencySymbol: @json($currencySymbol ?? '৳'),
        isSyncing: false,
        activeMenu: null,
        mikrotikImportModal: {
            open: false,
            loading: false,
            previewing: false,
            router_id: '',
            default_package_id: '',
            zone_id: '',
            update_existing: false,
            preview_data: null,
            error_message: ''
        },
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        onlineMap: {},
        mikrotikMap: {},
        pollInterval: null,
        liveTrafficMap: {},
        loadingTrafficMap: {},
        isTrafficBatchLoading: false,
        isTableLiveStreaming: false,
        tableLiveStreamTimer: null,
        visibleCustomerIds: @json($customers->pluck('id')),
        searchQuery: @json($search ?? ''),
        searchDebounceTimer: null,
        totalMatchingRows: null,

        handleLiveSearch(val) {
            this.filterRowsOnly(val);

            // Debounced full database search across all pages
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                const form = document.getElementById('customerFilterForm');
                if (form) {
                    form.submit();
                }
            }, 600);
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

        clearLiveSearch() {
            this.searchQuery = '';
            clearTimeout(this.searchDebounceTimer);
            const rows = document.querySelectorAll('.saas-table tbody tr:not(.no-record-row)');
            rows.forEach(row => {
                row.style.display = '';
            });
            this.totalMatchingRows = null;
            const form = document.getElementById('customerFilterForm');
            if (form) {
                const input = form.querySelector('input[name="search"]');
                if (input) input.value = '';
                form.submit();
            }
        },

        submitSearchNow() {
            clearTimeout(this.searchDebounceTimer);
            const form = document.getElementById('customerFilterForm');
            if (form) {
                form.submit();
            }
        },

        init() {
            if (this.searchQuery) {
                this.$nextTick(() => {
                    this.filterRowsOnly(this.searchQuery);
                });
            }
            // Real-time automatic session polling from MikroTik/FreeRADIUS in background
            this.pollLiveSessions(false);
            this.pollInterval = setInterval(() => {
                this.pollLiveSessions(false);
            }, 15000);

            // Auto-fetch live traffic on Online Sessions page
            if (@json($isOnlineRoute)) {
                this.$nextTick(() => {
                    this.fetchAllVisibleTraffic();
                });
            }
        },

        getOnlineStatus(username, fallback) {
            if (this.onlineMap[username] !== undefined) {
                return this.onlineMap[username];
            }
            return fallback || 'offline';
        },

        getMikrotikState(username, fallback) {
            if (this.mikrotikMap[username] !== undefined) {
                return this.mikrotikMap[username];
            }
            return fallback || 'enabled';
        },

        async pollLiveSessions(showNotification = false) {
            try {
                const res = await fetch(`{{ route('tenant.customers.sync-online') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (Array.isArray(data.online_usernames)) {
                        const newMap = {};
                        data.online_usernames.forEach(u => {
                            newMap[u] = 'online';
                        });
                        this.onlineMap = newMap;
                    }
                    if (data.mikrotik_status_map && typeof data.mikrotik_status_map === 'object') {
                        this.mikrotikMap = Object.assign({}, this.mikrotikMap, data.mikrotik_status_map);
                    }
                    if (showNotification) {
                        this.showToast(this.i18n.liveStatusSynced.replace(':count', data.active_count || 0), 'success');
                    }
                }
            } catch (err) {
                // Background poll silent
            }
        },

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },



        payModal: {
            open: false,
            loading: false,
            customer_id: null,
            customer_code: '',
            customer_name: '',
            username: '',
            monthly_bill: 0,
            due_amount: 0,
            expiry_date: '--',
            payment_mode: 'due',
            amount: 0,
            discount: 0,
            months: 1,
            payment_method: 'cash',
            transaction_id: '',
            billing_month: @json(date("F Y")),
            notes: '',
            extend_validity: true,
            reactivate_line: true
        },

        openSinglePayModal(cust) {
            this.activeMenu = null;
            this.payModal.customer_id = cust.id;
            this.payModal.customer_code = cust.customer_id || ('CUST-' + cust.id);
            this.payModal.customer_name = cust.name;
            this.payModal.username = cust.username;
            this.payModal.monthly_bill = parseFloat(cust.monthly_bill || 0);
            this.payModal.due_amount = parseFloat(cust.due_amount || 0);
            this.payModal.expiry_date = cust.expiry_date || '--';
            this.payModal.payment_mode = (this.payModal.due_amount > 0) ? 'due' : 'advance';
            this.payModal.discount = 0;
            this.payModal.months = 1;
            this.payModal.payment_method = 'cash';
            this.payModal.transaction_id = '';
            this.payModal.billing_month = @json(date("F Y"));
            this.payModal.notes = '';
            this.payModal.extend_validity = true;
            this.payModal.reactivate_line = true;
            this.onPaymentModeChange();
            this.payModal.open = true;
        },

        onPaymentModeChange() {
            if (this.payModal.payment_mode === 'due') {
                this.payModal.amount = (this.payModal.due_amount > 0) ? this.payModal.due_amount : (this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0);
                this.payModal.discount = 0;
                this.payModal.months = 1;
            } else if (this.payModal.payment_mode === 'advance') {
                this.payModal.months = 1;
                this.payModal.amount = this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0;
                this.payModal.discount = 0;
            } else if (this.payModal.payment_mode === 'custom') {
                this.payModal.amount = (this.payModal.due_amount > 0) ? this.payModal.due_amount : (this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0);
            }
        },

        setAdvanceMonths(m) {
            this.payModal.months = m;
            this.payModal.amount = (this.payModal.monthly_bill * parseInt(m || 1)).toFixed(2);
        },

        async submitSinglePayment() {
            this.payModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${this.payModal.customer_id}/pay-bill`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_mode: this.payModal.payment_mode,
                        amount: this.payModal.amount,
                        discount: this.payModal.discount || 0,
                        months: this.payModal.months || 1,
                        payment_method: this.payModal.payment_method,
                        transaction_id: this.payModal.transaction_id,
                        billing_month: this.payModal.billing_month,
                        notes: this.payModal.notes,
                        extend_validity: this.payModal.extend_validity,
                        reactivate_line: this.payModal.reactivate_line
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.payModal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || this.i18n.paymentFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.paymentNetworkError, 'error');
            } finally {
                this.payModal.loading = false;
            }
        },



        renewModal: {
            open: false,
            loading: false,
            id: null,
            name: '',
            monthly_bill: 0,
            expiry_date: '',
            months: 1,
            amount_paid: 0
        },

        pingModal: {
            open: false,
            loading: false,
            customerId: null,
            name: '',
            username: '',
            ip: '',
            gateway: '',
            count: 4,
            sent: 0,
            received: 0,
            loss_percent: 0,
            rtt_min: '--',
            rtt_avg: '--',
            rtt_max: '--',
            lines: [],
            success: false,
            message: ''
        },

        importModal: {
            open: false,
            loading: false,
            file: null,
            fileName: '',
            fileSize: '',
            default_router_id: '',
            default_reseller_id: '',
            duplicate_action: 'skip',
            sync_mikrotik: true,
            total_rows: 0,
            imported_count: 0,
            updated_count: 0,
            skipped_count: 0,
            errors_count: 0,
            logs: [],
            completed: false
        },

        exportModal: {
            open: false,
            preset: 'master',
            scope: @json(request('scope', '')),
            status: @json(request('status', 'all')),
            online_status: @json(request('online_status', 'all')),
            search: @json(request('search', '')),
            reseller_id: @json(request('reseller_id', '')),
            package_id: @json(request('package_id', '')),
            zone: @json(request('zone', ''))
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },

        toggleMenu(item, event) {
            if (event && event.stopPropagation) {
                event.stopPropagation();
            }
            if (this.activeMenu && this.activeMenu.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const btn = event ? (event.currentTarget || (event.target ? event.target.closest('button') : null)) : null;
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const dropdownHeight = 340;
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

        openRenewModal(cust) {
            this.renewModal.id = cust.id;
            this.renewModal.name = cust.name;
            this.renewModal.monthly_bill = parseFloat(cust.monthly_bill);
            this.renewModal.expiry_date = cust.expiry_date || '--';
            this.renewModal.months = 1;
            this.renewModal.amount_paid = parseFloat(cust.monthly_bill);
            this.renewModal.open = true;
        },

        async submitRenewal() {
            this.renewModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${this.renewModal.id}/renew`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        months: this.renewModal.months,
                        amount_paid: this.renewModal.amount_paid
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.renewModal.open = false;
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || this.i18n.renewalFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.renewalNetworkError, 'error');
            } finally {
                this.renewModal.loading = false;
            }
        },

        async toggleMikrotikState(id, username, fallback, event) {
            const btn = event ? event.currentTarget : null;
            const isCurrentlyEnabled = btn && btn.dataset.mikrotikState ? (btn.dataset.mikrotikState === 'enabled') : (fallback === 'enabled');
            const nextState = isCurrentlyEnabled ? 'disabled' : 'enabled';
            
            // Immediate In-Place Optimistic UI Update on button
            if (btn) {
                btn.dataset.mikrotikState = nextState;
                btn.title = nextState === 'enabled' ? this.i18n.mikrotikSecretEnabledTooltip : this.i18n.mikrotikSecretDisabledTooltip;
                btn.className = `inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs whitespace-nowrap ${nextState === 'enabled' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'}`;
                const dot = btn.querySelector('span:first-child');
                if (dot) dot.className = `w-1.5 h-1.5 rounded-full ${nextState === 'enabled' ? 'bg-emerald-500 ring-2 ring-emerald-300' : 'bg-rose-500'}`;
                const txt = btn.querySelector('span:last-child');
                if (txt) txt.textContent = nextState === 'enabled' ? this.i18n.enabled : this.i18n.disabled;
            }

            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${id}/toggle-mikrotik`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    if (btn) {
                        const prev = isCurrentlyEnabled ? 'enabled' : 'disabled';
                        btn.dataset.mikrotikState = prev;
                        btn.title = prev === 'enabled' ? this.i18n.mikrotikSecretEnabledTooltip : this.i18n.mikrotikSecretDisabledTooltip;
                        btn.className = `inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs whitespace-nowrap ${prev === 'enabled' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'}`;
                        const dot = btn.querySelector('span:first-child');
                        if (dot) dot.className = `w-1.5 h-1.5 rounded-full ${prev === 'enabled' ? 'bg-emerald-500 ring-2 ring-emerald-300' : 'bg-rose-500'}`;
                        const txt = btn.querySelector('span:last-child');
                        if (txt) txt.textContent = prev === 'enabled' ? this.i18n.enabled : this.i18n.disabled;
                    }
                    this.showToast(data.message || this.i18n.mikrotikToggleFailed, 'error');
                }
            } catch (err) {
                if (btn) {
                    const prev = isCurrentlyEnabled ? 'enabled' : 'disabled';
                    btn.dataset.mikrotikState = prev;
                    btn.title = prev === 'enabled' ? this.i18n.mikrotikSecretEnabledTooltip : this.i18n.mikrotikSecretDisabledTooltip;
                    btn.className = `inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs whitespace-nowrap ${prev === 'enabled' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'}`;
                    const dot = btn.querySelector('span:first-child');
                    if (dot) dot.className = `w-1.5 h-1.5 rounded-full ${prev === 'enabled' ? 'bg-emerald-500 ring-2 ring-emerald-300' : 'bg-rose-500'}`;
                    const txt = btn.querySelector('span:last-child');
                    if (txt) txt.textContent = prev === 'enabled' ? this.i18n.enabled : this.i18n.disabled;
                }
                this.showToast(this.i18n.mikrotikToggleNetworkError, 'error');
            }
        },

        async toggleStatus(id) {
            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.statusToggleFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.statusToggleNetworkError, 'error');
            }
        },

        async syncSingleMikrotik(id, name = '') {
            try {
                this.showToast(this.i18n.syncingCustomer.replace(':name', name || this.i18n.customerFallback), 'info');
                const res = await fetch(`{{ url('/admin/customers') }}/${id}/sync-mikrotik`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || this.i18n.syncMikrotikFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.syncMikrotikNetworkError, 'error');
            }
        },

        async syncAllMikrotik() {
            this.isSyncing = true;
            try {
                this.showToast(this.i18n.initiatingTwoWaySync, 'info');
                const res = await fetch(`{{ route('tenant.customers.two-way-sync') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1400);
                } else {
                    this.showToast(data.message || this.i18n.twoWaySyncFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.twoWaySyncNetworkError, 'error');
            } finally {
                this.isSyncing = false;
            }
        },

        openMikrotikImportModal() {
            this.activeMenu = null;
            this.mikrotikImportModal.open = true;
            this.mikrotikImportModal.router_id = @json($allRouters->first()?->id ?? '');
            this.mikrotikImportModal.preview_data = null;
            this.mikrotikImportModal.error_message = '';
            if (this.mikrotikImportModal.router_id) {
                this.fetchRouterPreview();
            }
        },

        async fetchRouterPreview() {
            if (!this.mikrotikImportModal.router_id) return;
            this.mikrotikImportModal.previewing = true;
            this.mikrotikImportModal.error_message = '';
            this.mikrotikImportModal.preview_data = null;
            try {
                const res = await fetch(`{{ route('tenant.customers.mikrotik-import-preview') }}?router_id=${this.mikrotikImportModal.router_id}`);
                const data = await res.json();
                if (data.success) {
                    this.mikrotikImportModal.preview_data = data;
                } else {
                    this.mikrotikImportModal.error_message = data.message || this.i18n.routerConnectFailed;
                }
            } catch (err) {
                this.mikrotikImportModal.error_message = this.i18n.routerPreviewNetworkError;
            } finally {
                this.mikrotikImportModal.previewing = false;
            }
        },

        async submitMikrotikImport() {
            if (!this.mikrotikImportModal.router_id) {
                this.showToast(this.i18n.selectMikrotikRouter, 'error');
                return;
            }
            this.mikrotikImportModal.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.customers.import-from-mikrotik') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        router_id: this.mikrotikImportModal.router_id,
                        default_package_id: this.mikrotikImportModal.default_package_id || null,
                        zone_id: this.mikrotikImportModal.zone_id || null,
                        update_existing: this.mikrotikImportModal.update_existing
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.mikrotikImportModal.open = false;
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || this.i18n.importFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.mikrotikImportNetworkError, 'error');
            } finally {
                this.mikrotikImportModal.loading = false;
            }
        },

        async confirmDelete(id, name) {
            if (!confirm(this.i18n.confirmDeleteCustomer.replace(':name', name))) {
                return;
            }
            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || this.i18n.deleteCustomerFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.deleteCustomerNetworkError, 'error');
            }
        },

        async confirmKickSession(id, name) {
            if (!confirm(this.i18n.confirmKickSession.replace(':name', name))) {
                return;
            }
            const currentItem = this.activeMenu;
            if (currentItem && currentItem.username) {
                this.onlineMap[currentItem.username] = 'offline';
            }
            try {
                this.showToast(this.i18n.terminatingSession.replace(':name', name), 'info');
                const res = await fetch(`{{ url('/admin/customers') }}/${id}/kick-session`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    if (currentItem && currentItem.username) {
                        this.onlineMap[currentItem.username] = 'offline';
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || this.i18n.kickSessionFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.kickSessionNetworkError, 'error');
            }
        },

        runPingTest(id, name, username) {
            this.pingModal.customerId = id;
            this.pingModal.name = name;
            this.pingModal.username = username;
            this.pingModal.ip = '';
            this.pingModal.gateway = '';
            this.pingModal.count = 4;
            this.pingModal.sent = 0;
            this.pingModal.received = 0;
            this.pingModal.loss_percent = 0;
            this.pingModal.rtt_min = '--';
            this.pingModal.rtt_avg = '--';
            this.pingModal.rtt_max = '--';
            this.pingModal.lines = [];
            this.pingModal.success = false;
            this.pingModal.message = '';
            this.pingModal.open = true;

            // Trigger real-time ping diagnostic run
            this.executePing();
        },

        async executePing() {
            if (!this.pingModal.customerId) return;
            this.pingModal.loading = true;
            this.pingModal.lines = [];
            try {
                const res = await fetch(`{{ url('/admin/customers') }}/${this.pingModal.customerId}/ping`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        count: this.pingModal.count || 4
                    })
                });
                const data = await res.json();
                if (data) {
                    this.pingModal.ip = data.ip || this.pingModal.ip;
                    this.pingModal.gateway = data.gateway || this.pingModal.gateway;
                    this.pingModal.sent = data.sent || 0;
                    this.pingModal.received = data.received || 0;
                    this.pingModal.loss_percent = data.loss_percent ?? 0;
                    this.pingModal.rtt_min = data.rtt_min || '--';
                    this.pingModal.rtt_avg = data.rtt_avg || '--';
                    this.pingModal.rtt_max = data.rtt_max || '--';
                    this.pingModal.lines = Array.isArray(data.lines) ? data.lines : [];
                    this.pingModal.success = !!data.success;
                    this.pingModal.message = data.message || '';

                    if (data.success) {
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || this.i18n.hostUnreachableMsg, 'error');
                    }
                }
            } catch (err) {
                this.pingModal.lines = [this.i18n.pingGatewayError];
                this.showToast(this.i18n.pingNetworkError, 'error');
            } finally {
                this.pingModal.loading = false;
            }
        },

        copyPingOutput() {
            if (!this.pingModal.lines || this.pingModal.lines.length === 0) {
                this.showToast(this.i18n.noTerminalOutputToCopy, 'info');
                return;
            }
            const text = this.pingModal.lines.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                this.showToast(this.i18n.pingOutputCopied, 'success');
            }).catch(() => {
                this.showToast(this.i18n.copyOutputFailed, 'error');
            });
        },

        openImportModal() {
            this.activeMenu = null;
            this.importModal.open = true;
            this.importModal.loading = false;
            this.importModal.file = null;
            this.importModal.fileName = '';
            this.importModal.fileSize = '';
            this.importModal.total_rows = 0;
            this.importModal.imported_count = 0;
            this.importModal.updated_count = 0;
            this.importModal.skipped_count = 0;
            this.importModal.errors_count = 0;
            this.importModal.logs = [];
            this.importModal.completed = false;
        },

        onImportFileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.importModal.file = file;
                this.importModal.fileName = file.name;
                this.importModal.fileSize = (file.size / 1024).toFixed(1) + ' KB';
            }
        },

        async submitBulkImport() {
            if (!this.importModal.file) {
                this.showToast(this.i18n.selectValidCsv, 'error');
                return;
            }

            this.importModal.loading = true;
            this.importModal.logs = [this.i18n.startingCsvImport];
            this.importModal.completed = false;

            const formData = new FormData();
            formData.append('file', this.importModal.file);
            if (this.importModal.default_router_id) {
                formData.append('default_router_id', this.importModal.default_router_id);
            }
            if (this.importModal.default_reseller_id) {
                formData.append('default_reseller_id', this.importModal.default_reseller_id);
            }
            formData.append('duplicate_action', this.importModal.duplicate_action);
            formData.append('sync_mikrotik', this.importModal.sync_mikrotik ? '1' : '0');

            try {
                const res = await fetch(`{{ route('tenant.customers.import') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.importModal.total_rows = data.total_rows;
                    this.importModal.imported_count = data.imported_count;
                    this.importModal.updated_count = data.updated_count;
                    this.importModal.skipped_count = data.skipped_count;
                    this.importModal.errors_count = data.errors_count || 0;
                    this.importModal.logs = data.logs || [];
                    this.importModal.completed = true;
                    this.showToast(data.message, 'success');
                } else {
                    this.importModal.logs = data.logs || [data.message || this.i18n.importFailed];
                    this.showToast(data.message || this.i18n.csvImportFailedCheckLog, 'error');
                }
            } catch (err) {
                this.importModal.logs = [this.i18n.csvImportNetworkError];
                this.showToast(this.i18n.bulkImportNetworkError, 'error');
            } finally {
                this.importModal.loading = false;
            }
        },

        copyImportLogs() {
            if (!this.importModal.logs || this.importModal.logs.length === 0) {
                this.showToast(this.i18n.noLogsToCopy, 'info');
                return;
            }
            const text = this.importModal.logs.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                this.showToast(this.i18n.importLogsCopied, 'success');
            });
        },

        openExportModal() {
            this.activeMenu = null;
            this.exportModal.open = true;
        },

        triggerExport() {
            const params = new URLSearchParams();
            params.set('preset', this.exportModal.preset);
            if (this.exportModal.scope) params.set('scope', this.exportModal.scope);
            if (this.exportModal.status && this.exportModal.status !== 'all') params.set('status', this.exportModal.status);
            if (this.exportModal.online_status && this.exportModal.online_status !== 'all') params.set('online_status', this.exportModal.online_status);
            if (this.exportModal.reseller_id) params.set('reseller_id', this.exportModal.reseller_id);
            if (this.exportModal.package_id) params.set('package_id', this.exportModal.package_id);
            if (this.exportModal.zone) params.set('zone', this.exportModal.zone);
            if (this.exportModal.search) params.set('search', this.exportModal.search);

            const url = `{{ route('tenant.customers.export') }}?` + params.toString();
            window.location.href = url;
            this.exportModal.open = false;
            this.showToast(this.i18n.startingCsvExport, 'success');
        },

        async syncOnlineSessions() {
            this.isSyncing = true;
            this.showToast(this.i18n.queryingActiveSessions, 'info');
            await this.pollLiveSessions(true);
            if (@json($isOnlineRoute)) {
                await this.fetchAllVisibleTraffic();
            }
            this.isSyncing = false;
        },

        /* -------------------------------------------------------------
         * Batch & Row Real-time Traffic Query Methods
         * ----------------------------------------------------------- */
        toggleTableLiveStream() {
            if (this.isTableLiveStreaming) {
                this.stopTableLiveStream();
                this.showToast(this.i18n.liveSpeedPaused, 'info');
            } else {
                this.startTableLiveStream();
                this.showToast(this.i18n.liveSpeedActive, 'success');
            }
        },

        startTableLiveStream() {
            this.stopTableLiveStream();
            this.isTableLiveStreaming = true;
            this.fetchAllVisibleTraffic(true);
            this.tableLiveStreamTimer = setInterval(() => {
                this.fetchAllVisibleTraffic(true);
            }, 1000);
        },

        stopTableLiveStream() {
            this.isTableLiveStreaming = false;
            if (this.tableLiveStreamTimer) {
                clearInterval(this.tableLiveStreamTimer);
                this.tableLiveStreamTimer = null;
            }
        },

        async fetchAllVisibleTraffic(isSilent = false) {
            if (!this.visibleCustomerIds || this.visibleCustomerIds.length === 0) return;
            if (!isSilent) {
                this.isTrafficBatchLoading = true;
            }
            try {
                const res = await fetch(`{{ route('tenant.customers.batch-traffic') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.visibleCustomerIds })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    this.liveTrafficMap = Object.assign({}, this.liveTrafficMap, data.data);
                }
            } catch (err) {
                // Silent catch
            } finally {
                if (!isSilent) {
                    this.isTrafficBatchLoading = false;
                }
            }
        },

        async fetchSingleRowTraffic(customerId, username) {
            this.loadingTrafficMap[customerId] = true;
            try {
                const res = await fetch(`{{ url('admin/customers') }}/${customerId}/traffic`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(r => r.json());

                if (res && res.success) {
                    this.liveTrafficMap[customerId] = res;
                }
            } catch (err) {
            } finally {
                this.loadingTrafficMap[customerId] = false;
            }
        }
    };
}
</script>
@endpush
