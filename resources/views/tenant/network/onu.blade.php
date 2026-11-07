@extends('tenant.layouts.app')

@section('title', 'ONU & Optical Terminals - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $oltFilter = request('olt_id');
    $statusFilter = request('status');
    $onuTypeFilter = request('onu_type');
    $ponPortFilter = request('pon_port');
    $vendorFilter = request('vendor');
    $bindingFilter = request('binding');
    $distanceFilter = request('distance_range');
    $activeMoreCount = collect([$ponPortFilter, $vendorFilter, $bindingFilter, $distanceFilter])->filter()->count();
    $isMoreOpen = $activeMoreCount > 0;
    $onlineRatio = ($totalOnus > 0) ? round(($onlineOnus / $totalOnus) * 100, 1) : 0;
@endphp

<div class="space-y-3" 
     x-data="onuManager()" 
     @scroll.window="activeMenuOnu = null" 
     @resize.window="activeMenuOnu = null">
    
    <!-- Floating Global Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-xl border text-xs font-semibold"
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

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Total Count Badge + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-satellite-dish"></i>
            </div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">ONU &amp; Optical Terminals Directory</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 font-mono">
                    Total: {{ number_format($totalOnus) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <!-- Manual OLT & ONU Sync Button -->
            <button type="button" 
                    @click="syncAllOlts()" 
                    :disabled="syncingAll || linkingClients"
                    class="h-[34px] px-3.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-60"
                    title="Synchronize all live OLT hardware gateways and pull connected ONUs">
                <i class="fas fa-rotate text-xs" :class="{ 'fa-spin': syncingAll }"></i>
                <span x-text="syncingAll ? 'Syncing OLTs...' : 'Sync from OLTs'"></span>
            </button>

            <!-- Auto-Link Clients Button -->
            <button type="button" 
                    @click="autoLinkClients()" 
                    :disabled="syncingAll || linkingClients"
                    class="h-[34px] px-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-60"
                    title="Re-match all ONUs with existing customers via Router MAC, Calling Station ID, and PPPoE user">
                <i class="fas fa-link text-cyan-600 text-[11px]" :class="{ 'fa-spin': linkingClients }"></i>
                <span x-text="linkingClients ? 'Linking...' : 'Auto-Link Clients'"></span>
            </button>

            <!-- Link to OLT Gateways -->
            <a href="{{ route('tenant.network.olt') }}" 
               class="h-[34px] px-3.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-network-wired text-slate-500 text-xs"></i>
                <span>View OLT Gateways</span>
            </a>
        </div>
    </div>

    <!-- 2. Ultra-Compact KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total ONUs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">{{ number_format($totalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-boxes"></i>
            </div>
        </div>

        <!-- Card 2: Online Active -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-700">Online Active</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-600">{{ number_format($onlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Offline -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-700">Offline</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600">{{ number_format($offlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>

        <!-- Card 4: Critical Signal -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-700">Critical Optical</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600">{{ number_format($criticalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-radiation"></i>
            </div>
        </div>

        <!-- Card 5: Warning Signal -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-700">Warning Signal</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-600">{{ number_format($warningOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: Online Ratio -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-700">Online Ratio</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-600">{{ $onlineRatio }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        
        <form method="GET" action="{{ route('tenant.network.onu') }}" id="onuFilterForm" class="space-y-2.5">
            <!-- Tier 1: Primary Filter Bar (Uniform h-[34px]) -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none transition-colors duration-150"
                          :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           id="onuSearchInput"
                           x-model="searchQuery" 
                           placeholder="Search Name, ONU MAC, Router MAC, Port, Model..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" 
                           autocomplete="off" />
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400 hover:text-rose-500 transition cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                <!-- Primary Dropdown: OLT Filter -->
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="olt_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All OLT Gateways</option>
                        @foreach($olts as $o)
                            <option value="{{ $o->id }}" {{ (string)$oltFilter === (string)$o->id ? 'selected' : '' }}>
                                {{ $o->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Primary Dropdown: Status Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Statuses</option>
                        <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="good" {{ $statusFilter === 'good' ? 'selected' : '' }}>Good (≥ -20 dBm)</option>
                        <option value="warning" {{ $statusFilter === 'warning' ? 'selected' : '' }}>Warning (-21 to -26)</option>
                        <option value="critical" {{ $statusFilter === 'critical' ? 'selected' : '' }}>Critical (&lt; -26 dBm)</option>
                    </select>
                </div>

                <!-- Primary Dropdown: Type Filter (EPON / GPON / XPON) -->
                <div class="w-full md:w-32 flex-shrink-0">
                    <select name="onu_type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Types</option>
                        <option value="EPON" {{ $onuTypeFilter === 'EPON' ? 'selected' : '' }}>EPON</option>
                        <option value="GPON" {{ $onuTypeFilter === 'GPON' ? 'selected' : '' }}>GPON</option>
                        <option value="XPON" {{ $onuTypeFilter === 'XPON' ? 'selected' : '' }}>XPON</option>
                    </select>
                </div>

                <!-- Pagination Limit Selector -->
                <div class="w-full md:w-28 flex-shrink-0">
                    @php $perPageVal = (int)request('per_page', 30); @endphp
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                        <option value="15" {{ $perPageVal === 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ $perPageVal === 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ $perPageVal === 250 ? 'selected' : '' }}>250 / page</option>
                        <option value="500" {{ $perPageVal === 500 ? 'selected' : '' }}>500 / page</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 text-xs font-semibold flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'">
                    <i class="fas fa-sliders text-[10px]"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-150" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Strict Filter & Reset Buttons (Filter First, Reset Second) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.network.onu') }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filter Drawer -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- PON Port Filter -->
                <div class="flex-1 min-w-[140px]">
                    <select name="pon_port" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All PON Ports</option>
                        @foreach($distinctPonPorts ?? [] as $pp)
                            <option value="{{ $pp }}" {{ $ponPortFilter === $pp ? 'selected' : '' }}>
                                PON {{ $pp }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Vendor Filter -->
                <div class="flex-1 min-w-[140px]">
                    <select name="vendor" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Hardware Vendors</option>
                        @foreach($distinctVendors ?? [] as $v)
                            <option value="{{ $v }}" {{ $vendorFilter === $v ? 'selected' : '' }}>
                                {{ $v }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Binding Filter -->
                <div class="flex-1 min-w-[150px]">
                    <select name="binding" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Customer Bindings</option>
                        <option value="linked" {{ $bindingFilter === 'linked' ? 'selected' : '' }}>Linked to Customer</option>
                        <option value="unlinked" {{ $bindingFilter === 'unlinked' ? 'selected' : '' }}>Unassigned / Free</option>
                    </select>
                </div>

                <!-- Distance Range Filter -->
                <div class="flex-1 min-w-[150px]">
                    <select name="distance_range" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Fiber Distances</option>
                        <option value="under_500" {{ $distanceFilter === 'under_500' ? 'selected' : '' }}>&lt; 500m</option>
                        <option value="500_1000" {{ $distanceFilter === '500_1000' ? 'selected' : '' }}>500m - 1,000m</option>
                        <option value="1000_2000" {{ $distanceFilter === '1000_2000' ? 'selected' : '' }}>1,000m - 2,000m</option>
                        <option value="over_2000" {{ $distanceFilter === 'over_2000' ? 'selected' : '' }}>&gt; 2,000m</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strictly .saas-table Standard) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Master ONU &amp; Optical Terminals Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $onus->total() }} Terminals
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Client / ONU Name</th>
                        <th class="text-center font-semibold">Area</th>
                        <th class="text-center font-semibold">OLT Gateway</th>
                        <th class="text-center font-semibold">PON:ONU ID</th>
                        <th class="text-center font-semibold">ONU MAC</th>
                        <th class="text-center font-semibold">Router MAC</th>
                        <th class="text-center font-semibold">Type</th>
                        <th class="text-center font-semibold">Optical Power</th>
                        <th class="text-center font-semibold">Distance</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($onus as $index => $onu)
                        @php
                            $sig = $onu->signal_quality;
                            $onuType = strtoupper($onu->onu_type ?: 'EPON');
                            $typeBadge = match($onuType) {
                                'XPON' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'GPON' => 'bg-blue-50 text-blue-700 border-blue-200',
                                default => 'bg-cyan-50 text-cyan-700 border-cyan-200'
                            };
                            $routerMacDisplay = $onu->router_mac ?: ($onu->auto_router_mac ?: ($onu->customer->mac_address ?? null));
                            $clientNameDisplay = $onu->resolved_client_name ?: ($onu->customer->name ?? ($onu->name ?: 'Unnamed Terminal'));
                            $areaDisplay = $onu->resolved_area ?: ($onu->customer?->zone ?: ($onu->customer?->coverageZone?->name ?? null));
                            $pppoeUserDisplay = $onu->pppoe_username ?: ($onu->customer->username ?? null);
                            $customerId = $onu->customer_id ?: ($onu->linked_customer_id ?? null);
                            $onuPayload = [
                                'id' => $onu->id,
                                'olt_id' => $onu->olt_id,
                                'olt_name' => $onu->olt->name ?? 'OLT',
                                'olt_ip' => $onu->olt->ip_address ?? '',
                                'pon_port' => $onu->pon_port,
                                'onu_id' => $onu->onu_id,
                                'name' => $clientNameDisplay,
                                'area' => $areaDisplay,
                                'desc' => $onu->desc,
                                'mac_address' => $onu->mac_address,
                                'router_mac' => $routerMacDisplay ?: '',
                                'vendor' => $onu->vendor ?: 'VSOL',
                                'model' => $onu->model ?: '1GE ONU',
                                'onu_type' => $onuType,
                                'vlan_id' => $onu->vlan_id,
                                'vlan_mode' => $onu->vlan_mode ?: 'tag',
                                'service_mode' => $onu->service_mode ?: 'bridge',
                                'pppoe_username' => $pppoeUserDisplay,
                                'pppoe_password' => $onu->pppoe_password,
                                'lan1_state' => $onu->lan1_state ?: 'enable',
                                'wifi_ssid' => $onu->wifi_ssid,
                                'wifi_password' => $onu->wifi_password,
                                'catv_state' => $onu->catv_state ?: 'enable',
                                'bandwidth_profile' => $onu->bandwidth_profile,
                                'status' => $onu->status,
                                'rx_power_dbm' => $onu->rx_power_dbm,
                                'tx_power_dbm' => $onu->tx_power_dbm,
                                'distance_m' => $onu->distance_m,
                                'signal_label' => $sig['label'],
                                'signal_badge' => $sig['badge'],
                                'updated_at' => $onu->updated_at ? $onu->updated_at->diffForHumans() : 'Recently'
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Serial # (Center Align) -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $onus->firstItem() + $index }}
                            </td>

                            <!-- 2. Client / ONU Name (Left Align) -->
                            <td class="text-left whitespace-nowrap">
                                @if($customerId)
                                    <a href="{{ route('tenant.customers.show', $customerId) }}" 
                                       class="font-semibold text-slate-900 hover:text-cyan-600 transition inline-flex items-center gap-1.5"
                                       title="View Linked Customer Profile">
                                        <i class="fas fa-user-check text-[10px] text-emerald-500"></i>
                                        <span id="onu-name-{{ $onu->id }}">{{ $clientNameDisplay }}</span>
                                    </a>
                                @else
                                    <button type="button" 
                                            @click="openManageModal({{ Js::from($onuPayload) }}, 'config')" 
                                            class="font-semibold text-slate-900 hover:text-cyan-600 transition inline-flex items-center gap-1.5 cursor-pointer text-left"
                                            title="Click to Configure ONU Settings">
                                        <span id="onu-name-{{ $onu->id }}">{{ $clientNameDisplay }}</span>
                                    </button>
                                @endif
                            </td>

                            <!-- 3. Area (Left Align) -->
                            <td class="text-left whitespace-nowrap">
                                @if($areaDisplay)
                                    <span class="inline-flex items-center gap-1 text-slate-700 font-medium">
                                        <i class="fas fa-location-dot text-[9px] text-cyan-500"></i>
                                        <span>{{ $areaDisplay }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">--</span>
                                @endif
                            </td>

                            <!-- 4. OLT Gateway (Left Align) -->
                            <td class="text-left whitespace-nowrap">
                                @if($onu->olt)
                                    <a href="{{ route('tenant.network.olt.show', $onu->olt_id) }}" 
                                       class="inline-flex items-center gap-1 text-slate-700 hover:text-cyan-600 font-medium transition"
                                       title="View OLT Chassis Panel">
                                        <i class="fas fa-network-wired text-[9px] text-slate-400"></i>
                                        <span>{{ $onu->olt->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs">--</span>
                                @endif
                            </td>

                            <!-- 5. PON:ONU ID (Center Align, font-mono) -->
                            <td class="text-center font-mono whitespace-nowrap">
                                <a href="{{ route('tenant.network.olt.show', ['olt' => $onu->olt_id, 'pon_port' => $onu->pon_port]) }}" 
                                   class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition"
                                   title="View PON Port {{ $onu->pon_port }}">
                                    {{ $onu->pon_port }}:#{{ $onu->onu_id }}
                                </a>
                            </td>

                            <!-- 6. ONU MAC (Center Align, font-mono) -->
                            <td class="text-center font-mono text-slate-800 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $onu->mac_address ?: '--' }}</span>
                                    @if($onu->mac_address)
                                        <button type="button" 
                                                @click="copyToClipboard('{{ $onu->mac_address }}', 'ONU MAC')" 
                                                class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                                title="Copy ONU MAC">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    @endif
                                </span>
                            </td>

                            <!-- 7. Router MAC (Center Align, font-mono) -->
                            <td class="text-center font-mono whitespace-nowrap">
                                @if($routerMacDisplay)
                                    <span class="inline-flex items-center gap-1 text-slate-800">
                                        <span>{{ $routerMacDisplay }}</span>
                                        <button type="button" 
                                                @click="copyToClipboard('{{ $routerMacDisplay }}', 'Router MAC')" 
                                                class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                                title="Copy Router MAC">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">--</span>
                                @endif
                            </td>

                            <!-- 8. Type (Center Align) -->
                            <td class="text-center font-mono whitespace-nowrap">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] border {{ $typeBadge }}">
                                    {{ $onuType }}
                                </span>
                            </td>

                            <!-- 9. Optical Power (Center Align, font-mono) -->
                            <td class="text-center font-mono whitespace-nowrap">
                                @if($onu->rx_power_dbm !== null)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] border font-bold {{ $sig['badge'] }}">
                                        {{ $onu->rx_power_dbm }} dBm
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs inline-flex items-center gap-1">
                                        <span>--</span>
                                        @if($onu->status === 'online')
                                            <button type="button" 
                                                    @click="quickPollOptical({{ Js::from($onuPayload) }})"
                                                    class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer"
                                                    title="Poll live optical telemetry directly from OLT">
                                                <i class="fas fa-rotate"></i>
                                            </button>
                                        @endif
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Distance (Center Align, font-mono) -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $onu->distance_m !== null ? $onu->distance_m . 'm' : '--' }}
                            </td>

                            <!-- 11. Status (Center Align) -->
                            <td class="text-center whitespace-nowrap">
                                @if($onu->status === 'online')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Offline</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 12. Actions (Center Align, Floating 3-Dot) -->
                            <td class="w-10 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($onuPayload) }}, $event)" 
                                        class="w-6 h-6 mx-auto rounded-lg hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-10 text-center">
                                <div class="max-w-md mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-xs">
                                        <i class="fas fa-satellite-dish"></i>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800">No ONUs / Optical Terminals Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Adjust search filters or synchronize your OLT gateways to discover and link client ONUs.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.network.olt') }}" class="h-[34px] px-3.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-network-wired text-[10px]"></i>
                                            <span>View OLT Gateways</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($onus->hasPages() || $onus->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $onus->firstItem() ?? 0 }} to {{ $onus->lastItem() ?? 0 }} of {{ $onus->total() }} results
                </div>
                <div>
                    {{ $onus->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenuOnu !== null" 
         @click.away="activeMenuOnu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-48 bg-white rounded-lg shadow-xl border border-slate-200 py-0.5 text-xs divide-y divide-slate-100 font-normal"
         style="display: none;">
        
        <!-- Group 1: Configuration & Telemetry -->
        <div class="py-0.5">
            <button type="button" 
                    @click="configureFromMenu('config')" 
                    class="w-full px-2.5 py-1.5 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-sliders-h text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>Configure Settings</span>
            </button>

            <button type="button" 
                    @click="quickPollOptical(activeMenuOnu)" 
                    class="w-full px-2.5 py-1.5 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-arrows-rotate text-emerald-600 w-3.5 text-center text-[10px]"></i>
                <span>Poll Live Telemetry</span>
            </button>

            <button type="button" 
                    @click="configureFromMenu('optical')" 
                    class="w-full px-2.5 py-1.5 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-signal text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>View Optical Telemetry</span>
            </button>
        </div>

        <!-- Group 2: Remote Operations -->
        <div class="py-0.5">
            <button type="button" 
                    @click="rebootFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-amber-50 text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-power-off text-amber-600 w-3.5 text-center text-[10px]"></i>
                <span>Reboot Terminal</span>
            </button>

            <button type="button" 
                    @click="factoryResetFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-rotate-left text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Factory Reset ONU</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: ONU Configuration Modal -->
    <div x-show="manageModal.open" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeManageModal()" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <template x-if="manageModal.onu">
                <div>
                    <!-- Modal Header -->
                    <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                                <i class="fas fa-satellite-dish"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-slate-800">Configure ONU Terminal</h3>
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono uppercase bg-slate-100 text-slate-700 border border-slate-200"
                                          x-text="manageModal.onu.onu_type"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-500 font-normal">
                                    <span x-text="manageModal.onu.olt_name"></span> &bull; 
                                    Port <span class="font-mono text-cyan-700" x-text="manageModal.onu.pon_port + ':#' + manageModal.onu.onu_id"></span> &bull;
                                    ONU MAC <span class="font-mono text-slate-600" x-text="manageModal.onu.mac_address"></span>
                                    <template x-if="manageModal.onu.router_mac">
                                        <span> &bull; Router MAC <span class="font-mono text-cyan-700" x-text="manageModal.onu.router_mac"></span></span>
                                    </template>
                                </p>
                            </div>
                        </div>
                        
                        <button type="button" 
                                @click="closeManageModal()" 
                                class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Minimal Tab Bar -->
                    <div class="flex items-center gap-2 px-4 pt-2 bg-slate-50 border-b border-slate-200">
                        <button type="button" 
                                @click="manageModal.activeTab = 'config'" 
                                class="px-3 py-1.5 text-xs font-semibold border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'config' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-sliders-h text-[10px] mr-1"></i>
                            <span>Configuration</span>
                        </button>

                        <button type="button" 
                                @click="manageModal.activeTab = 'subscriber'" 
                                class="px-3 py-1.5 text-xs font-semibold border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'subscriber' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-user text-[10px] mr-1"></i>
                            <span>Subscriber Info</span>
                        </button>

                        <button type="button" 
                                @click="manageModal.activeTab = 'optical'" 
                                class="px-3 py-1.5 text-xs font-semibold border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'optical' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-signal text-[10px] mr-1"></i>
                            <span>Optical Status</span>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-4 max-h-[60vh] overflow-y-auto text-xs space-y-3">

                        <!-- TAB 1: Configuration -->
                        <div x-show="manageModal.activeTab === 'config'" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Service Mode -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Service / WAN Mode</label>
                                    <select x-model="manageModal.form.service_mode" 
                                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                        <option value="bridge">Bridge Mode</option>
                                        <option value="pppoe">PPPoE Router Mode</option>
                                        <option value="dhcp">DHCP Client Mode</option>
                                        <option value="static">Static IP Mode</option>
                                    </select>
                                </div>

                                <!-- VLAN ID -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">VLAN ID</label>
                                    <input type="number" 
                                           x-model="manageModal.form.vlan_id" 
                                           placeholder="e.g. 100" 
                                           min="1" max="4094"
                                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                                </div>

                                <!-- VLAN Mode -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">VLAN Mode</label>
                                    <select x-model="manageModal.form.vlan_mode" 
                                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                        <option value="tag">Tagged</option>
                                        <option value="untag">Untagged</option>
                                        <option value="transparent">Transparent</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>

                                <!-- LAN Port 1 -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">LAN Port 1 State</label>
                                    <select x-model="manageModal.form.lan1_state" 
                                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                        <option value="enable">Enabled</option>
                                        <option value="disable">Disabled</option>
                                    </select>
                                </div>

                                <!-- Bandwidth Profile -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Bandwidth Profile</label>
                                    <select x-model="manageModal.form.bandwidth_profile" 
                                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                        <option value="">Default (Unlimited)</option>
                                        <option value="10M">10 Mbps</option>
                                        <option value="20M">20 Mbps</option>
                                        <option value="30M">30 Mbps</option>
                                        <option value="50M">50 Mbps</option>
                                        <option value="100M">100 Mbps</option>
                                    </select>
                                </div>

                                <!-- CATV Control -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">CATV RF Port</label>
                                    <select x-model="manageModal.form.catv_state" 
                                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                        <option value="enable">Enabled</option>
                                        <option value="disable">Disabled</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Optional PPPoE Credentials -->
                            <div class="border-t border-slate-100 pt-3 space-y-2">
                                <span class="text-[11px] font-semibold text-slate-700 block">PPPoE Credentials (Optional)</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10.5px] text-slate-500 mb-1">Username</label>
                                        <input type="text" 
                                               x-model="manageModal.form.pppoe_username" 
                                               placeholder="client_pppoe_user" 
                                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                                    </div>
                                    <div>
                                        <label class="block text-[10.5px] text-slate-500 mb-1">Password</label>
                                        <input type="password" 
                                               x-model="manageModal.form.pppoe_password" 
                                               placeholder="••••••••" 
                                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                                    </div>
                                </div>
                            </div>

                            <!-- Optional WiFi SSID / Password -->
                            <div class="border-t border-slate-100 pt-3 space-y-2">
                                <span class="text-[11px] font-semibold text-slate-700 block">WiFi Access Point (Optional)</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10.5px] text-slate-500 mb-1">SSID Name</label>
                                        <input type="text" 
                                               x-model="manageModal.form.wifi_ssid" 
                                               placeholder="e.g. Home_WiFi_2.4G" 
                                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                                    </div>
                                    <div>
                                        <label class="block text-[10.5px] text-slate-500 mb-1">WPA Key / Password</label>
                                        <input type="text" 
                                               x-model="manageModal.form.wifi_password" 
                                               placeholder="Minimum 8 characters" 
                                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Subscriber Info -->
                        <div x-show="manageModal.activeTab === 'subscriber'" class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Terminal Description / Customer Name</label>
                                <input type="text" 
                                       x-model="manageModal.form.name" 
                                       placeholder="e.g. John Doe - Home Connection" 
                                       class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Client Wi-Fi Router WAN MAC Address</label>
                                <input type="text" 
                                       x-model="manageModal.form.router_mac" 
                                       placeholder="e.g. 50:D4:F7:AA:BB:CC" 
                                       class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Physical Address / Drop Cable Line</label>
                                <textarea x-model="manageModal.form.desc" 
                                          rows="3" 
                                          placeholder="e.g. TJ Box #4, Port 3, Fiber core 2 blue..." 
                                          class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition resize-none"></textarea>
                            </div>
                        </div>

                        <!-- TAB 3: Optical Status -->
                        <div x-show="manageModal.activeTab === 'optical'" class="space-y-3">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                                    <span class="text-[10px] text-slate-500 uppercase block font-medium">Rx Optical Power</span>
                                    <span class="text-sm font-mono font-bold block mt-1" 
                                          :class="manageModal.onu.rx_power_dbm < -26 ? 'text-rose-600' : (manageModal.onu.rx_power_dbm < -20 ? 'text-amber-600' : 'text-emerald-600')"
                                          x-text="manageModal.onu.rx_power_dbm !== null ? manageModal.onu.rx_power_dbm + ' dBm' : '--'"></span>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                                    <span class="text-[10px] text-slate-500 uppercase block font-medium">Tx Optical Power</span>
                                    <span class="text-sm font-mono font-bold text-slate-800 block mt-1" 
                                          x-text="manageModal.onu.tx_power_dbm !== null ? manageModal.onu.tx_power_dbm + ' dBm' : '--'"></span>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
                                    <span class="text-[10px] text-slate-500 uppercase block font-medium">Fiber Distance</span>
                                    <span class="text-sm font-mono font-bold text-indigo-700 block mt-1" 
                                          x-text="manageModal.onu.distance_m !== null ? manageModal.onu.distance_m + ' meters' : '--'"></span>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <span class="text-[11px] font-semibold text-slate-800 block">Signal Quality Assessment</span>
                                    <p class="text-[10.5px] text-slate-500 font-normal">
                                        Optical Rx should ideally be between -14 dBm and -24 dBm for stable GPON/EPON service.
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold border" 
                                      :class="manageModal.onu.signal_badge" 
                                      x-text="manageModal.onu.signal_label"></span>
                            </div>

                            <div class="pt-1 flex justify-center">
                                <button type="button" 
                                        @click="refreshOptical()" 
                                        :disabled="manageModal.refreshingOptical" 
                                        class="h-[34px] px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition cursor-pointer disabled:opacity-50">
                                    <i class="fas fa-arrows-rotate text-[11px]" :class="{ 'fa-spin': manageModal.refreshingOptical }"></i>
                                    <span x-text="manageModal.refreshingOptical ? 'Querying OLT...' : 'Poll Live Telemetry'"></span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" 
                                @click="closeManageModal()" 
                                class="h-[34px] px-3.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" 
                                @click="saveOnuConfig()" 
                                :disabled="manageModal.saving" 
                                class="h-[34px] px-4 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                            <i class="fas fa-check text-[10px]" :class="{ 'fa-spinner fa-spin': manageModal.saving }"></i>
                            <span x-text="manageModal.saving ? 'Pushing to OLT...' : 'Save Configuration'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function onuManager() {
    return {
        syncingAll: false,
        linkingClients: false,
        activeMenuOnu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        manageModal: {
            open: false,
            activeTab: 'config',
            saving: false,
            refreshingOptical: false,
            onu: null,
            form: {
                name: '',
                desc: '',
                router_mac: '',
                vlan_id: '',
                vlan_mode: 'tag',
                service_mode: 'bridge',
                pppoe_username: '',
                pppoe_password: '',
                lan1_state: 'enable',
                wifi_ssid: '',
                wifi_password: '',
                catv_state: 'enable',
                bandwidth_profile: ''
            }
        },

        toggleMenu(onuPayload, event) {
            if (this.activeMenuOnu && this.activeMenuOnu.id === onuPayload.id) {
                this.activeMenuOnu = null;
                return;
            }
            this.activeMenuOnu = onuPayload;

            let targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
            const dropdownHeight = 160;
            const spaceBelow = window.innerHeight - rect.bottom;
            const right = Math.max(10, window.innerWidth - rect.right);

            if (spaceBelow >= dropdownHeight) {
                this.menuPos = {
                    top: `${Math.round(rect.bottom) + 2}px`,
                    bottom: 'auto',
                    right: `${right}px`,
                    left: 'auto'
                };
            } else {
                this.menuPos = {
                    top: 'auto',
                    bottom: `${Math.round(window.innerHeight - rect.top) + 2}px`,
                    right: `${right}px`,
                    left: 'auto'
                };
            }
        },

        configureFromMenu(tab = 'config') {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;
            this.openManageModal(onu, tab);
        },

        rebootFromMenu() {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;
            this.confirmReboot(onu);
        },

        factoryResetFromMenu() {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;
            this.confirmFactoryReset(onu);
        },

        confirmReboot(onu) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reboot Terminal?',
                    html: `<div class="text-xs text-slate-600 mt-1">Send remote reboot command to ONU <strong class="text-slate-900 font-semibold">${onu.name || onu.mac_address}</strong> on port <span class="font-mono text-cyan-700">${onu.pon_port}:#${onu.onu_id}</span>?<br><span class="text-[11px] text-slate-400 mt-1 block">Subscriber will temporarily disconnect for ~45 seconds.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Yes, Reboot',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeReboot(onu.id);
                    }
                });
            } else if (confirm(`Reboot ONU ${onu.name || onu.mac_address}?`)) {
                this.executeReboot(onu.id);
            }
        },

        confirmFactoryReset(onu) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Factory Reset ONU?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to reset ONU <strong class="text-slate-900 font-semibold">${onu.name || onu.mac_address}</strong> to factory defaults?<br><span class="text-[11px] text-rose-500 mt-1 block">All custom VLAN, WAN, and WiFi configurations will be erased.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-rotate-left mr-1"></i> Yes, Reset to Defaults',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeFactoryReset(onu.id);
                    }
                });
            } else if (confirm(`Reset ONU ${onu.name} to factory defaults?`)) {
                this.executeFactoryReset(onu.id);
            }
        },

        async executeReboot(onuId) {
            try {
                const response = await fetch(`/admin/network/onu/${onuId}/reboot`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || 'ONU reboot command sent successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to reboot ONU.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while sending reboot command.', 'error');
            }
        },

        async executeFactoryReset(onuId) {
            try {
                const response = await fetch(`/admin/network/onu/${onuId}/factory-reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || 'Factory reset command sent successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to send factory reset command.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while sending factory reset.', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        copyToClipboard(text, label = 'MAC address') {
            if (!text) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(`${label} copied to clipboard!`, 'success');
                }).catch(() => {
                    this.showToast('Could not copy to clipboard.', 'error');
                });
            } else {
                this.showToast(`${label} copied!`, 'success');
            }
        },

        openManageModal(onuData, defaultTab = 'config') {
            this.manageModal.onu = { ...onuData };
            this.manageModal.activeTab = defaultTab;
            this.manageModal.form.name = onuData.name || '';
            this.manageModal.form.desc = onuData.desc || '';
            this.manageModal.form.router_mac = onuData.router_mac || '';
            this.manageModal.form.vlan_id = onuData.vlan_id || '';
            this.manageModal.form.vlan_mode = onuData.vlan_mode || 'tag';
            this.manageModal.form.service_mode = onuData.service_mode || 'bridge';
            this.manageModal.form.pppoe_username = onuData.pppoe_username || '';
            this.manageModal.form.pppoe_password = onuData.pppoe_password || '';
            this.manageModal.form.lan1_state = onuData.lan1_state || 'enable';
            this.manageModal.form.wifi_ssid = onuData.wifi_ssid || '';
            this.manageModal.form.wifi_password = onuData.wifi_password || '';
            this.manageModal.form.catv_state = onuData.catv_state || 'enable';
            this.manageModal.form.bandwidth_profile = onuData.bandwidth_profile || '';
            this.manageModal.open = true;
        },

        closeManageModal() {
            this.manageModal.open = false;
        },

        async saveOnuConfig() {
            if (!this.manageModal.onu) return;
            this.manageModal.saving = true;
            try {
                const response = await fetch(`/admin/network/onu/${this.manageModal.onu.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.manageModal.form.name,
                        desc: this.manageModal.form.desc,
                        router_mac: this.manageModal.form.router_mac || null,
                        vlan_id: this.manageModal.form.vlan_id || null,
                        vlan_mode: this.manageModal.form.vlan_mode,
                        service_mode: this.manageModal.form.service_mode,
                        pppoe_username: this.manageModal.form.pppoe_username,
                        pppoe_password: this.manageModal.form.pppoe_password,
                        lan1_state: this.manageModal.form.lan1_state,
                        wifi_ssid: this.manageModal.form.wifi_ssid,
                        wifi_password: this.manageModal.form.wifi_password,
                        catv_state: this.manageModal.form.catv_state,
                        bandwidth_profile: this.manageModal.form.bandwidth_profile
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.manageModal.onu = { ...this.manageModal.onu, ...this.manageModal.form };
                    this.showToast(data.message || 'Configuration saved successfully.', 'success');
                    
                    const nameEl = document.getElementById(`onu-name-${this.manageModal.onu.id}`);
                    if (nameEl) nameEl.textContent = this.manageModal.form.name;

                    this.closeManageModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Failed to save configuration.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while saving configuration.', 'error');
            } finally {
                this.manageModal.saving = false;
            }
        },

        async refreshOptical() {
            if (!this.manageModal.onu) return;
            this.manageModal.refreshingOptical = true;
            try {
                const response = await fetch(`/admin/network/onu/${this.manageModal.onu.id}/optical`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success && data.telemetry) {
                    if (data.telemetry.rx_power_dbm !== undefined) {
                        this.manageModal.onu.rx_power_dbm = data.telemetry.rx_power_dbm;
                    }
                    if (data.telemetry.tx_power_dbm !== undefined) {
                        this.manageModal.onu.tx_power_dbm = data.telemetry.tx_power_dbm;
                    }
                    if (data.telemetry.distance_m !== undefined) {
                        this.manageModal.onu.distance_m = data.telemetry.distance_m;
                    }

                    const rx = this.manageModal.onu.rx_power_dbm;
                    if (rx !== null && rx !== undefined) {
                        if (rx >= -20.0) {
                            this.manageModal.onu.signal_label = 'Good';
                            this.manageModal.onu.signal_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        } else if (rx >= -26.0 && rx < -20.0) {
                            this.manageModal.onu.signal_label = 'Warning';
                            this.manageModal.onu.signal_badge = 'bg-amber-50 text-amber-700 border-amber-200';
                        } else {
                            this.manageModal.onu.signal_label = 'Critical';
                            this.manageModal.onu.signal_badge = 'bg-rose-50 text-rose-700 border-rose-200';
                        }
                    }

                    this.showToast('Live telemetry updated.', 'success');
                } else {
                    this.showToast(data.message || 'Could not fetch live telemetry from OLT.', 'info');
                }
            } catch (e) {
                this.showToast('Network error while refreshing telemetry.', 'error');
            } finally {
                this.manageModal.refreshingOptical = false;
            }
        },

        async syncAllOlts() {
            if (this.syncingAll || this.linkingClients) return;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Synchronize All OLT Gateways?',
                    html: `
                        <div class="text-xs text-slate-600 mt-2 space-y-2 text-left">
                            <p>This will connect directly to all registered OLT hardware devices across the network and:</p>
                            <ul class="list-disc pl-5 space-y-1 text-slate-700 font-medium">
                                <li>Discover &amp; synchronize all connected client ONUs / ONT terminals</li>
                                <li>Fetch live optical Rx/Tx power (dBm), distance, and online status</li>
                                <li>Automatically pair terminals with customer accounts and router MACs</li>
                            </ul>
                            <p class="text-[11px] text-cyan-700 font-semibold pt-1">
                                <i class="fas fa-info-circle mr-1"></i> Multi-device hardware query may take 15 to 60 seconds depending on OLT response times.
                            </p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-rotate mr-1.5"></i> Yes, Start Sync',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeSyncAllOlts();
                    }
                });
            } else if (confirm('Synchronize all active OLT hardware devices and auto-link customer terminals now?')) {
                this.executeSyncAllOlts();
            }
        },

        async executeSyncAllOlts() {
            this.syncingAll = true;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Synchronizing OLT Hardware...',
                    html: `
                        <div class="py-4 text-center space-y-3">
                            <div class="inline-block animate-spin text-cyan-600 text-3xl">
                                <i class="fas fa-circle-notch"></i>
                            </div>
                            <p class="text-xs text-slate-600 font-medium">Connecting to OLTs, pulling optical telemetry &amp; pairing client accounts...</p>
                            <p class="text-[11px] text-slate-400">Please wait, do not close or reload this page.</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5'
                    }
                });
            } else {
                this.showToast('Connecting to OLT hardware & pulling live ONU telemetry...', 'info');
            }

            try {
                const response = await fetch('{{ route('tenant.network.onu.sync-all') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Synchronization Complete!',
                            html: `
                                <div class="text-xs text-slate-600 mt-2 text-left space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                    <p class="font-semibold text-slate-800">${data.message}</p>
                                    <div class="grid grid-cols-3 gap-2 pt-1 text-center font-mono">
                                        <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-2xs">
                                            <span class="text-[10px] text-slate-400 block font-sans">Total ONUs</span>
                                            <strong class="text-cyan-700 text-sm">${data.total_onus || 0}</strong>
                                        </div>
                                        <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-2xs">
                                            <span class="text-[10px] text-slate-400 block font-sans">Online</span>
                                            <strong class="text-emerald-600 text-sm">${data.online_onus || 0}</strong>
                                        </div>
                                        <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-2xs">
                                            <span class="text-[10px] text-slate-400 block font-sans">Linked Clients</span>
                                            <strong class="text-purple-600 text-sm">${data.linked_onus || 0}</strong>
                                        </div>
                                    </div>
                                </div>
                            `,
                            confirmButtonColor: '#0891b2',
                            confirmButtonText: '<i class="fas fa-check mr-1.5"></i> Done, Refresh View',
                            customClass: {
                                popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                                confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs'
                            }
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        this.showToast(data.message, 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync Encountered Problems',
                            text: data.message || 'One or more OLTs could not be reached.',
                            confirmButtonColor: '#e11d48',
                            customClass: {
                                popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                                confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                            }
                        });
                    } else {
                        this.showToast(data.message || 'OLT synchronization encountered errors.', 'error');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Communication Timeout',
                        text: 'Network timeout or connection error while contacting OLT hardware.',
                        confirmButtonColor: '#e11d48',
                        customClass: {
                            popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                            confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                        }
                    });
                } else {
                    this.showToast('Network timeout or communication error with OLT devices.', 'error');
                }
            } finally {
                this.syncingAll = false;
            }
        },

        async autoLinkClients() {
            if (this.syncingAll || this.linkingClients) return;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Auto-Link Client Accounts?',
                    html: `
                        <div class="text-xs text-slate-600 mt-2 space-y-2 text-left">
                            <p>This will evaluate all ONUs against registered customers and:</p>
                            <ul class="list-disc pl-5 space-y-1 text-slate-700 font-medium">
                                <li>Match via Router MAC, Calling Station ID (MikroTik radacct), and ONU MAC</li>
                                <li>Associate customers with their correct OLT &amp; PON port</li>
                                <li>Assign actual customer names and area zones to each terminal</li>
                            </ul>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-link mr-1.5"></i> Yes, Run Auto-Link',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeAutoLinkClients();
                    }
                });
            } else if (confirm('Run customer-ONU auto-linking now?')) {
                this.executeAutoLinkClients();
            }
        },

        async executeAutoLinkClients() {
            this.linkingClients = true;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Pairing Customer Profiles...',
                    html: `
                        <div class="py-4 text-center space-y-3">
                            <div class="inline-block animate-spin text-cyan-600 text-3xl">
                                <i class="fas fa-circle-notch"></i>
                            </div>
                            <p class="text-xs text-slate-600 font-medium">Evaluating router MACs, RADIUS sessions, and customer usernames...</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5'
                    }
                });
            } else {
                this.showToast('Evaluating and pairing ONUs with customer profiles...', 'info');
            }

            try {
                const response = await fetch('{{ route('tenant.network.onu.auto-link') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Auto-Link Completed!',
                            text: data.message,
                            confirmButtonColor: '#0891b2',
                            confirmButtonText: '<i class="fas fa-check mr-1.5"></i> Done, Refresh View',
                            customClass: {
                                popup: 'rounded-2xl shadow-2xl border border-slate-200 p-5',
                                confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs'
                            }
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        this.showToast(data.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Auto-Link Failed',
                            text: data.message || 'Auto-linking encountered an error.',
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        this.showToast(data.message || 'Auto-linking failed.', 'error');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An error occurred while communicating with the server.',
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    this.showToast('Network error while running customer pairing.', 'error');
                }
            } finally {
                this.linkingClients = false;
            }
        },

        async quickPollOptical(onu) {
            if (!onu) return;
            this.activeMenuOnu = null;
            this.showToast(`Querying OLT for ${onu.pon_port}:#${onu.onu_id} optical telemetry...`, 'info');

            try {
                const response = await fetch(`/admin/network/onu/${onu.id}/optical`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('Optical telemetry updated directly from OLT.', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    this.showToast(data.message || 'Could not fetch live telemetry from OLT.', 'error');
                }
            } catch (err) {
                this.showToast('Error communicating with OLT gateway.', 'error');
            }
        }
    };
}
</script>
@endpush
