@extends('tenant.layouts.app')

@section('title', 'IP Pools & Subnets - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ipPoolsManager()" @scroll.window="activeMenuPool = null" @resize.window="activeMenuPool = null">
    
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

    <!-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Count Badge + Action Buttons ONLY - NO Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">IP Pools &amp; Subnet Management</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                    {{ number_format($totalPools) }} Pools
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <!-- Sync from MikroTik Button -->
            <button type="button" 
                    @click="openImportModal()" 
                    class="h-[34px] px-3 py-0 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-rotate text-purple-600 text-xs" :class="{ 'fa-spin': importModal.syncing }"></i>
                <span>Sync from MikroTik</span>
            </button>

            <!-- Link to MikroTik Routers -->
            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="h-[34px] px-3 py-0 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-server text-slate-400 text-xs"></i>
                <span>View Routers</span>
            </a>

            <!-- Standalone Create IP Pool Page Link (AGENTS.md Compliance) -->
            <a href="{{ route('tenant.network.ip-pools.create') }}" 
               class="h-[34px] px-3.5 py-0 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Create IP Pool</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Configured Pools -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Pools</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">{{ number_format($totalPools) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        <!-- Card 2: Total IP Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Total Capacity</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">{{ number_format($totalIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 3: Available Free IPs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Available Free</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($freeIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 4: Assigned / Leased -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">Assigned / Used</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700">{{ number_format($usedIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Card 5: Static Public / CGNAT -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">Public / CGNAT</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">{{ number_format($staticPublicIps + $cgnatIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Card 6: Pool Utilization -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-600">Pool Utilization</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $utilizationPercent >= 90 ? 'text-rose-700' : ($utilizationPercent >= 70 ? 'text-amber-700' : 'text-emerald-700') }}">
                    {{ $utilizationPercent }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- 3. Customer-Standard 2-Tier Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C - Strict Uniform Height h-[34px] Policy) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form action="{{ route('tenant.network.ip-pools') }}" method="GET" class="space-y-2.5">
            
            <!-- Tier 1: Primary Filter Bar (Uniform h-[34px]) -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Box with Dynamic Icon & 1-Click Clear -->
                <div class="relative flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-xs transition"
                          :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="Search pool name, range, subnet, gateway..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                    <!-- 1-Click Clear Button -->
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Primary Dropdown: Router -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="router_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="">All Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Primary Dropdown: Pool Type -->
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="pool_type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="">All Pool Types</option>
                        <option value="pppoe" {{ request('pool_type') === 'pppoe' ? 'selected' : '' }}>PPPoE Dynamic</option>
                        <option value="cgnat" {{ request('pool_type') === 'cgnat' ? 'selected' : '' }}>CGNAT Block</option>
                        <option value="static_public" {{ request('pool_type') === 'static_public' ? 'selected' : '' }}>Static Public</option>
                        <option value="dhcp" {{ request('pool_type') === 'dhcp' ? 'selected' : '' }}>DHCP Hotspot</option>
                        <option value="ipv6" {{ request('pool_type') === 'ipv6' ? 'selected' : '' }}>IPv6 Prefix</option>
                        <option value="vpn" {{ request('pool_type') === 'vpn' ? 'selected' : '' }}>VPN Pool</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-28 flex-shrink-0">
                    @php $perPageVal = (int)request('per_page', 30); @endphp
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="15" {{ $perPageVal === 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="30" {{ $perPageVal === 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="200" {{ $perPageVal === 200 ? 'selected' : '' }}>200 / page</option>
                    </select>
                </div>

                <!-- More Filters Accordion Toggle Button with Badge & Chevron -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 text-xs font-semibold transition flex items-center justify-between gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50/70 border-cyan-200 text-cyan-800' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'">
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-sliders text-[11px]" :class="showMoreFilters ? 'text-cyan-600' : 'text-slate-400'"></i>
                        <span>More</span>
                    </span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-cyan-600 text-white">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[10px] transition-transform duration-200"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Strict Sequence: [Filter] then [Reset] Button at Right (AGENTS.md Compliance) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.network.ip-pools') }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset All Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filter Drawer (Expandable) -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- Status Filter -->
                <div class="w-full sm:w-44">
                    <select name="status" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        <option value="exhausted" {{ request('status') === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                    </select>
                </div>

                <!-- IP Version Filter -->
                <div class="w-full sm:w-40">
                    <select name="ip_version" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="">All IP Versions</option>
                        <option value="ipv4" {{ request('ip_version') === 'ipv4' ? 'selected' : '' }}>IPv4 Subnets</option>
                        <option value="ipv6" {{ request('ip_version') === 'ipv6' ? 'selected' : '' }}>IPv6 Prefixes</option>
                    </select>
                </div>

                <!-- MikroTik Sync State Filter -->
                <div class="w-full sm:w-48">
                    <select name="is_sync" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal">
                        <option value="">All Sync States</option>
                        <option value="1" {{ request('is_sync') === '1' ? 'selected' : '' }}>Synced to MikroTik</option>
                        <option value="0" {{ request('is_sync') === '0' ? 'selected' : '' }}>Local / Unsynced</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strict AGENTS.md Rule 2.D - Centered Headers, Single Line, Natural Empty State) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Master IP Pool &amp; Subnet Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $pools->total() }} Pools
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="text-center font-semibold">Router</th>
                        <th class="text-center font-semibold">Pool Name</th>
                        <th class="text-center font-semibold">Type</th>
                        <th class="text-center font-semibold">Addresses / Range</th>
                        <th class="text-center font-semibold">Total IPs</th>
                        <th class="text-center font-semibold">Next Pool</th>
                        <th class="text-center font-semibold">Comment</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($pools as $index => $pool)
                        @php
                            $poolPayload = [
                                'id' => $pool->id,
                                'name' => $pool->name,
                                'pool_type' => $pool->pool_type,
                                'pool_type_label' => $pool->type_badge['label'] ?? ucfirst($pool->pool_type),
                                'ip_version' => $pool->ip_version,
                                'range_start' => $pool->range_start,
                                'range_end' => $pool->range_end,
                                'addresses_display' => $pool->addresses_display,
                                'cidr_subnet' => $pool->cidr_subnet,
                                'gateway' => $pool->gateway,
                                'dns_primary' => $pool->dns_primary,
                                'dns_secondary' => $pool->dns_secondary,
                                'next_pool' => $pool->next_pool,
                                'router_id' => $pool->router_id,
                                'router_name' => $pool->router?->name ?? 'Global',
                                'router_ip' => $pool->router?->ip_address ?? '',
                                'vlan_id' => $pool->vlan_id,
                                'status' => $pool->status,
                                'description' => $pool->description,
                                'is_sync_mikrotik' => (bool)$pool->is_sync_mikrotik,
                                'total_ips' => (int)$pool->total_ips,
                                'used_ips' => (int)$pool->used_ips,
                                'free_ips' => (int)$pool->free_ips,
                                'utilization_percent' => (float)$pool->utilization_percent,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index (#): Center Align -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $pools->firstItem() + $index }}
                            </td>

                            <!-- 2. Status: Center Align -->
                            <td class="text-center">
                                @if($pool->status === 'active')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @elseif($pool->status === 'exhausted')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Exhausted</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Router: Center Align -->
                            <td class="text-center">
                                @if($pool->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $pool->router_id) }}" 
                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-[10px] font-mono transition" 
                                       title="{{ $pool->router->ip_address }}">
                                        <i class="fas fa-server text-[8px] text-indigo-500"></i>
                                        <span class="truncate max-w-[110px]">{{ $pool->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono text-[10px]">Global</span>
                                @endif
                            </td>

                            <!-- 4. Pool Name: Left Align (Textual) -->
                            <td class="text-left text-cyan-800">
                                <a href="{{ route('tenant.network.ip-pools.edit', $pool->id) }}" 
                                   class="hover:underline font-medium cursor-pointer" 
                                   title="Click to Edit">
                                    {{ $pool->name }}
                                </a>
                            </td>

                            <!-- 5. Type: Center Align -->
                            <td class="text-center font-mono text-[10px]">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $pool->type_badge['badge'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ $pool->type_badge['label'] ?? ucfirst($pool->pool_type) }}
                                </span>
                            </td>

                            <!-- 6. Addresses / Range: Center Align (Code) -->
                            <td class="text-center font-mono text-slate-800">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $pool->addresses_display }}</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $pool->addresses_display }}', 'Addresses')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy Range">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 7. Total IPs: Right Align (Accounting Numeric) -->
                            <td class="text-right font-mono text-slate-700">
                                <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px]">
                                    {{ number_format($pool->total_ips) }} IPs
                                </span>
                            </td>

                            <!-- 8. Next Pool: Center Align -->
                            <td class="text-center font-mono text-slate-700">
                                @if($pool->next_pool)
                                    <span class="text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 text-[10px]">{{ $pool->next_pool }}</span>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 9. Comment: Left Align (Textual) -->
                            <td class="text-left text-slate-600 max-w-[140px] truncate" title="{{ $pool->description }}">
                                {{ $pool->description ?: '--' }}
                            </td>

                            <!-- 10. Action: Center Align (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($poolPayload) }}, $event)" 
                                        class="pool-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-2xs">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No IP Pools Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">
                                        Create your first IP pool for PPPoE dynamic assignment, CGNAT subnets, or static public blocks.
                                    </p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.network.ip-pools.create') }}" 
                                           class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Create IP Pool</span>
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
        @if($pools->hasPages() || $pools->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $pools->firstItem() ?? 0 }} to {{ $pools->lastItem() ?? 0 }} of {{ $pools->total() }} results
                </div>
                <div>
                    {{ $pools->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (Strict AGENTS.md Rule 2.E - getBoundingClientRect, fixed z-50) -->
    <div x-show="activeMenuPool !== null" 
         @click.away="activeMenuPool = null"
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
        
        <!-- Group 1: Diagnostics & Details -->
        <div class="py-0.5">
            <button type="button" 
                    @click="viewDetailsFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-eye text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>View Details</span>
            </button>

            <button type="button" 
                    @click="editFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-500 w-3.5 text-center text-[10px]"></i>
                <span>Edit Configuration</span>
            </button>
        </div>

        <!-- Group 2: MikroTik Sync & Status Management -->
        <div class="py-0.5">
            <button type="button" 
                    @click="syncFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-rotate text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>Push to MikroTik</span>
            </button>

            <button type="button" 
                    @click="toggleStatusFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-slate-50 text-slate-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas w-3.5 text-center text-[10px]" :class="activeMenuPool?.status === 'active' ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500'"></i>
                <span x-text="activeMenuPool?.status === 'active' ? 'Disable IP Pool' : 'Enable IP Pool'"></span>
            </button>
        </div>

        <!-- Group 3: Destructive Action -->
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete IP Pool</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: View Details Modal (Read-Only Inspection) -->
    <div x-show="detailsModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeDetailsModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.pool?.name || 'IP Pool Details'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Subnet specifications, utilization metrics &amp; gateway telemetry</p>
                    </div>
                </div>
                <button type="button" @click="closeDetailsModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Details Body -->
            <div class="p-4 space-y-4 max-h-[75vh] overflow-y-auto text-xs" x-if="detailsModal.pool">
                
                <!-- Metrics Summary Strip -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                        <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block">Total Capacity</span>
                        <span class="text-sm font-bold font-mono text-slate-800" x-text="(detailsModal.pool.total_ips || 0).toLocaleString()"></span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-indigo-50 border border-indigo-200/80 text-center">
                        <span class="text-[9px] font-medium uppercase tracking-wider text-indigo-600 block">Assigned / Used</span>
                        <span class="text-sm font-bold font-mono text-indigo-700" x-text="(detailsModal.pool.used_ips || 0).toLocaleString()"></span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200/80 text-center">
                        <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-600 block">Available Free</span>
                        <span class="text-sm font-bold font-mono text-emerald-700" x-text="(detailsModal.pool.free_ips || 0).toLocaleString()"></span>
                    </div>
                </div>

                <!-- Utilization Bar -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 font-medium">Pool Utilization</span>
                        <span class="font-mono font-bold text-slate-800" x-text="detailsModal.pool.utilization_percent + '%'"></span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300"
                             :style="{ width: `${Math.min(100, detailsModal.pool.utilization_percent || 0)}%` }"
                             :class="{
                                 'bg-emerald-500': (detailsModal.pool.utilization_percent || 0) < 70,
                                 'bg-amber-500': (detailsModal.pool.utilization_percent || 0) >= 70 && (detailsModal.pool.utilization_percent || 0) < 90,
                                 'bg-rose-500': (detailsModal.pool.utilization_percent || 0) >= 90
                             }"></div>
                    </div>
                </div>

                <!-- Specs Grid -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Addresses / Range</span>
                        <span class="font-mono text-slate-800 font-semibold" x-text="detailsModal.pool.addresses_display"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Pool Type</span>
                        <span class="font-medium text-slate-800" x-text="detailsModal.pool.pool_type_label"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Router Gateway</span>
                        <span class="font-medium text-slate-800" x-text="detailsModal.pool.router_name + (detailsModal.pool.router_ip ? ` (${detailsModal.pool.router_ip})` : '')"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Status</span>
                        <span class="font-medium capitalize" 
                              :class="detailsModal.pool.status === 'active' ? 'text-emerald-700' : 'text-slate-600'" 
                              x-text="detailsModal.pool.status"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Gateway IP</span>
                        <span class="font-mono text-slate-800" x-text="detailsModal.pool.gateway || '--'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">CIDR Subnet</span>
                        <span class="font-mono text-slate-800" x-text="detailsModal.pool.cidr_subnet || '--'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">DNS Servers</span>
                        <span class="font-mono text-slate-800" x-text="(detailsModal.pool.dns_primary || '--') + ' / ' + (detailsModal.pool.dns_secondary || '--')"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-400 block uppercase">Next Overflow Pool</span>
                        <span class="font-mono text-purple-700" x-text="detailsModal.pool.next_pool || '--'"></span>
                    </div>
                </div>

                <!-- Description / Comment -->
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-400 block uppercase mb-0.5">Comment / Description</span>
                    <p class="text-slate-700 font-normal leading-relaxed" x-text="detailsModal.pool.description || 'No comment provided.'"></p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeDetailsModal()" 
                        class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
                <a :href="detailsModal.pool ? `/admin/network/ip-pools/${detailsModal.pool.id}/edit` : '#'" 
                   class="h-[34px] px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-pen-to-square text-[10px]"></i>
                    <span>Edit Pool</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: Sync / Import from MikroTik Modal -->
    <div x-show="importModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeImportModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-rotate"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Sync from MikroTik Router</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Pull and synchronize /ip/pool configurations</p>
                    </div>
                </div>
                <button type="button" @click="closeImportModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <p class="text-slate-600 leading-relaxed font-normal">
                    Select a MikroTik router below to pull all configured IP Pools (<code class="bg-slate-100 px-1 py-0.5 rounded text-purple-700 font-mono text-[11px]">/ip/pool</code>) and live active client allocations directly into the database.
                </p>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select MikroTik Router <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="importModal.router_id" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-purple-50/60 border border-purple-100 rounded-lg text-purple-900 space-y-1">
                    <span class="font-semibold block text-[11px]">✨ Automatic Sync Capabilities:</span>
                    <ul class="list-disc list-inside text-[10.5px] space-y-0.5 text-purple-800/90 font-normal">
                        <li>Imports IP Pool names, ranges, and comments.</li>
                        <li>Reads live allocated IP count (<code class="font-mono">/ip/pool/used</code>).</li>
                        <li>Updates matching pools without duplicates.</li>
                    </ul>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeImportModal()" 
                        class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="syncFromMikrotik()" 
                        :disabled="importModal.syncing"
                        class="h-[34px] bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs px-4 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-rotate text-[10px]" :class="{ 'fa-spin': importModal.syncing }"></i>
                    <span x-text="importModal.syncing ? 'Pulling from Router...' : 'Start Sync'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ipPoolsManager() {
    return {
        activeMenuPool: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        detailsModal: {
            open: false,
            pool: null
        },

        importModal: {
            open: false,
            router_id: '{{ $routers->first()?->id ?? "" }}',
            syncing: false
        },

        toggleMenu(pool, event) {
            if (this.activeMenuPool && this.activeMenuPool.id === pool.id) {
                this.activeMenuPool = null;
                return;
            }
            this.activeMenuPool = pool;

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

        viewDetailsFromMenu() {
            if (!this.activeMenuPool) return;
            this.detailsModal.pool = this.activeMenuPool;
            this.activeMenuPool = null;
            this.detailsModal.open = true;
        },

        closeDetailsModal() {
            this.detailsModal.open = false;
            this.detailsModal.pool = null;
        },

        editFromMenu() {
            if (!this.activeMenuPool) return;
            const poolId = this.activeMenuPool.id;
            this.activeMenuPool = null;
            window.location.href = `/admin/network/ip-pools/${poolId}/edit`;
        },

        syncFromMenu() {
            if (!this.activeMenuPool) return;
            const poolId = this.activeMenuPool.id;
            this.activeMenuPool = null;
            this.syncMikrotik(poolId);
        },

        async toggleStatusFromMenu() {
            if (!this.activeMenuPool) return;
            const pool = this.activeMenuPool;
            this.activeMenuPool = null;
            try {
                const response = await fetch(`/admin/network/ip-pools/${pool.id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Failed to update status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while updating pool status.', 'error');
            }
        },

        deleteFromMenu() {
            if (!this.activeMenuPool) return;
            const pool = this.activeMenuPool;
            this.activeMenuPool = null;
            this.confirmDelete(pool.id, pool.name);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        copyToClipboard(text, label = 'Text') {
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

        confirmDelete(id, name) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete IP Pool?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete IP pool <strong class="text-slate-900 font-semibold">${name}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">Assigned IP allocations and dynamic bindings will be removed.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete Pool',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeDelete(id);
                    }
                });
            } else if (confirm(`Are you sure you want to delete IP pool '${name}'?`)) {
                this.executeDelete(id);
            }
        },

        async executeDelete(id) {
            try {
                const response = await fetch(`/admin/network/ip-pools/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Failed to delete IP pool.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while deleting IP pool.', 'error');
            }
        },

        openImportModal() {
            this.importModal.open = true;
        },

        closeImportModal() {
            this.importModal.open = false;
        },

        async syncFromMikrotik() {
            if (!this.importModal.router_id) {
                this.showToast('Please select a MikroTik router first.', 'error');
                return;
            }
            this.importModal.syncing = true;
            try {
                const response = await fetch('/admin/network/ip-pools/import-mikrotik', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ router_id: this.importModal.router_id })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.closeImportModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Failed to sync from MikroTik.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while communicating with MikroTik.', 'error');
            } finally {
                this.importModal.syncing = false;
            }
        },

        async syncMikrotik(id) {
            try {
                const response = await fetch(`/admin/network/ip-pools/${id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to sync with MikroTik.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while syncing with MikroTik.', 'error');
            }
        }
    };
}
</script>
@endpush
