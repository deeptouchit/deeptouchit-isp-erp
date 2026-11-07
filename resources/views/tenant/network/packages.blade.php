@extends('tenant.layouts.app')

@section('title', 'Internet Packages & Plans - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $isMoreOpen = request()->filled('ip_pool_id') || request()->filled('active');
    $activeMoreCount = (request()->filled('ip_pool_id') ? 1 : 0) + (request()->filled('active') ? 1 : 0);
@endphp

<div class="space-y-3" 
     x-data="packagesIndexManager()"
     @scroll.window="activeMenuPkg = null" 
     @resize.window="activeMenuPkg = null">

    <!-- Toast Notification -->
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

    <!-- 1. Top Header Bar (Icon + Title + Total Count Badge + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Internet Packages &amp; Bandwidth Plans</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 font-mono">
                    Total: {{ number_format($totalPackages) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <!-- Import from MikroTik Button -->
            <button type="button" 
                    @click="showImportModal = true"
                    class="h-[34px] px-3 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-cloud-arrow-down text-purple-600 text-[11px]"></i>
                <span>Import from MikroTik</span>
            </button>

            <!-- Routers Fleet Button -->
            <a href="{{ route('tenant.network.mikrotik') }}"
               class="h-[34px] px-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-server text-slate-500 text-[11px]"></i>
                <span>MikroTik Routers</span>
            </a>

            <!-- Add Package Button -->
            <a href="{{ route('tenant.network.packages.create') }}"
               class="h-[34px] px-3.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add Package</span>
            </a>
        </div>
    </div>

    <!-- 2. Ultra-Compact KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Plans</span>
                <span class="text-[13px] font-bold text-slate-800 tracking-tight font-mono leading-tight block">{{ number_format($totalPackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-box"></i>
            </div>
        </div>

        <!-- Metric 2: Active Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Active Plans</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">{{ number_format($activePackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 3: PPPoE Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">PPPoE Plans</span>
                <span class="text-[13px] font-bold text-blue-700 tracking-tight font-mono leading-tight block">{{ number_format($pppoePackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Metric 4: Hotspot Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Hotspot Plans</span>
                <span class="text-[13px] font-bold text-amber-700 tracking-tight font-mono leading-tight block">{{ number_format($hotspotPackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-wifi"></i>
            </div>
        </div>

        <!-- Metric 5: Avg Price -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">Avg Monthly Price</span>
                <span class="text-[13px] font-bold text-purple-700 tracking-tight font-mono leading-tight block">@currency($avgPrice)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-tag"></i>
            </div>
        </div>

        <!-- Metric 6: Fleet Synced -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Fleet Synced</span>
                <span class="text-[13px] font-bold text-indigo-700 tracking-tight font-mono leading-tight block">{{ number_format($syncedPackages) }}/{{ $totalPackages }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-cloud-arrow-up"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        
        <form action="{{ route('tenant.network.packages') }}" method="GET" class="space-y-2.5">
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
                           x-model="searchQuery" 
                           placeholder="Search package name, profile, IP, notes..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400 hover:text-rose-500 transition cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                <!-- Primary Dropdown: Router Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="router_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Fleet Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Primary Dropdown: Service Type Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="service_type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Services</option>
                        <option value="pppoe" {{ request('service_type') === 'pppoe' ? 'selected' : '' }}>PPPoE Profile</option>
                        <option value="hotspot" {{ request('service_type') === 'hotspot' ? 'selected' : '' }}>Hotspot Profile</option>
                        <option value="static" {{ request('service_type') === 'static' ? 'selected' : '' }}>Static IP</option>
                    </select>
                </div>

                <!-- Pagination Limit Selector -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                        <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 text-xs font-semibold flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'">
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
                    <a href="{{ route('tenant.network.packages') }}" 
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
                
                <!-- IP Pool Filter -->
                <div class="w-full sm:w-56">
                    <select name="ip_pool_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All IP Pools</option>
                        @foreach($ipPools as $p)
                            <option value="{{ $p->id }}" {{ (string)request('ip_pool_id') === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} {{ $p->addresses_display && $p->addresses_display !== '-' ? '('.$p->addresses_display.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-44">
                    <select name="active" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active Plans</option>
                        <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Disabled Plans</option>
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
                <span>Bandwidth Packages &amp; Profile Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $packages->total() }} Packages
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Package Name</th>
                        <th class="text-center font-semibold">Bandwidth (Down/Up)</th>
                        <th class="text-center font-semibold">Monthly Price</th>
                        <th class="text-center font-semibold">Service Type</th>
                        <th class="text-center font-semibold">Fleet Router / IP Pool</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($packages as $index => $pkg)
                        <tr class="{{ !$pkg->is_active ? 'opacity-70' : '' }}">
                            
                            <!-- 1. Serial # (Center Align) -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $packages->firstItem() + $index }}
                            </td>

                            <!-- 2. Package Name (Left Align) -->
                            <td class="text-left whitespace-nowrap">
                                <a href="{{ route('tenant.network.packages.edit', $pkg->id) }}"
                                   class="font-semibold text-slate-900 hover:text-cyan-600 transition inline-flex items-center gap-1.5"
                                   title="Click to Edit Package">
                                    <span>{{ $pkg->package_name ?: $pkg->name }}</span>
                                    @if($pkg->code)
                                        <span class="text-[10px] font-mono text-slate-400 font-normal">({{ $pkg->code }})</span>
                                    @endif
                                </a>
                            </td>

                            <!-- 3. Bandwidth Speeds (Center Align) -->
                            <td class="text-center font-mono whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-700 border border-slate-200">
                                    <i class="fas fa-arrow-down text-cyan-600 text-[9px]"></i>
                                    <span class="font-bold text-cyan-800">{{ $pkg->download_speed ?: '0' }}M</span>
                                    <span class="text-slate-400">/</span>
                                    <i class="fas fa-arrow-up text-purple-600 text-[9px]"></i>
                                    <span class="font-bold text-purple-800">{{ $pkg->upload_speed ?: '0' }}M</span>
                                </span>
                            </td>

                            <!-- 4. Monthly Price (Right Align - Accounting Standard) -->
                            <td class="text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                @currency($pkg->price)
                            </td>

                            <!-- 5. Service Type (Center Align) -->
                            <td class="text-center whitespace-nowrap">
                                @if($pkg->service_type === 'pppoe')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fas fa-network-wired text-[8px]"></i>
                                        <span>PPPoE</span>
                                    </span>
                                @elseif($pkg->service_type === 'hotspot')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-wifi text-[8px]"></i>
                                        <span>Hotspot</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        <span>Static IP</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Fleet Router / IP Pool (Left Align) -->
                            <td class="text-left whitespace-nowrap">
                                <div class="flex items-center gap-1.5 flex-nowrap">
                                    @if($pkg->router)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-cyan-50 text-cyan-700 border border-cyan-200">
                                            <i class="fas fa-server text-[8px]"></i>
                                            <span>{{ $pkg->router->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">All Routers</span>
                                    @endif

                                    @if($pkg->ipPool || $pkg->remote_address)
                                        <span class="text-slate-300">/</span>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                                            <i class="fas fa-layer-group text-[8px]"></i>
                                            <span>{{ $pkg->remote_address ?: $pkg->ipPool?->name }}</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 7. Status (Center Align) -->
                            <td class="text-center whitespace-nowrap">
                                @if($pkg->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Actions (Center Align, Floating 3-Dot) -->
                            <td class="w-10 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($pkg) }}, $event)" 
                                        class="w-6 h-6 mx-auto rounded-lg hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center">
                                <div class="max-w-md mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-xs">
                                        <i class="fas fa-boxes-stacked"></i>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800">No Internet Packages Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Create your first bandwidth package or import existing user profiles directly from your MikroTik fleet.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.network.packages.create') }}" 
                                           class="h-[34px] px-3.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Add Package</span>
                                        </a>
                                        <button type="button" 
                                                @click="showImportModal = true"
                                                class="h-[34px] px-3 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-cloud-arrow-down text-purple-600 text-[10px]"></i>
                                            <span>Import Profiles</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($packages->hasPages() || $packages->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $packages->firstItem() ?? 0 }} to {{ $packages->lastItem() ?? 0 }} of {{ $packages->total() }} results
                </div>
                <div>
                    {{ $packages->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- GLOBAL FLOATING 3-DOT ACTION MENU (Unclipped & Anchored) -->
    <div x-show="activeMenuPkg !== null"
         @click.away="activeMenuPkg = null"
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
        
        <!-- Group 1: Diagnostics & Telemetry -->
        <div class="py-0.5">
            <!-- View Details -->
            <button type="button" 
                    @click="viewDetailsFromMenu()"
                    class="w-full px-2.5 py-1.5 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-eye text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>View Details</span>
            </button>

            <!-- Sync to Router -->
            <button type="button" 
                    @click="syncFromMenu()"
                    :disabled="!activeMenuPkg?.router_id || syncingPkgId === activeMenuPkg?.id"
                    class="w-full px-2.5 py-1.5 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed text-[11px]">
                <i class="fas" :class="syncingPkgId === activeMenuPkg?.id ? 'fa-spinner fa-spin text-cyan-600' : 'fa-arrows-rotate text-cyan-600 w-3.5 text-center text-[10px]'"></i>
                <span>Sync to Router</span>
            </button>

            <!-- Copy Profile Name -->
            <button type="button" 
                    @click="copyProfileFromMenu()"
                    class="w-full px-2.5 py-1.5 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-copy text-blue-600 w-3.5 text-center text-[10px]"></i>
                <span>Copy Profile Name</span>
            </button>
        </div>

        <!-- Group 2: Configuration & Management -->
        <div class="py-0.5">
            <!-- Toggle Status -->
            <button type="button" 
                    @click="toggleFromMenu()"
                    class="w-full px-2.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-indigo-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-power-off text-indigo-600 w-3.5 text-center text-[10px]"></i>
                <span x-text="activeMenuPkg?.is_active ? 'Disable Package' : 'Enable Package'"></span>
            </button>

            <!-- Edit Package (Dedicated Page) -->
            <a :href="'/admin/network/packages/' + activeMenuPkg?.id + '/edit'"
               class="w-full px-2.5 py-1.5 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-600 w-3.5 text-center text-[10px]"></i>
                <span>Edit Package</span>
            </a>

            <!-- Delete Package -->
            <button type="button" 
                    @click="deleteFromMenu()"
                    class="w-full px-2.5 py-1.5 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-trash-can text-rose-600 w-3.5 text-center text-[10px]"></i>
                <span>Delete Package</span>
            </button>
        </div>
    </div>

    <!-- DETAILS MODAL (View Details Modal) -->
    <div x-show="showDetailsModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="showDetailsModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900" x-text="selectedPkg?.package_name || selectedPkg?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'Profile: ' + (selectedPkg?.name || selectedPkg?.mikrotik_profile || '--')"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- Grid of Details -->
                <div class="grid grid-cols-2 gap-2.5 p-3 bg-slate-50 rounded-lg border border-slate-200/70">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Service Type</span>
                        <span class="font-semibold text-slate-800 uppercase" x-text="selectedPkg?.service_type || 'PPPoE'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Monthly Price</span>
                        <span class="font-mono font-bold text-cyan-800" x-text="'৳ ' + Number(selectedPkg?.price || 0).toFixed(2)"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Download Speed</span>
                        <span class="font-mono font-bold text-cyan-700" x-text="(selectedPkg?.download_speed || '0') + ' Mbps'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Upload Speed</span>
                        <span class="font-mono font-bold text-purple-700" x-text="(selectedPkg?.upload_speed || '0') + ' Mbps'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Validity Period</span>
                        <span class="font-mono text-slate-700" x-text="(selectedPkg?.validity_days || '30') + ' Days'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Status</span>
                        <span class="font-medium" :class="selectedPkg?.is_active ? 'text-emerald-700' : 'text-rose-700'" x-text="selectedPkg?.is_active ? 'Active' : 'Disabled'"></span>
                    </div>
                </div>

                <!-- Network Policies -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/70 space-y-1.5 font-mono text-[11px]">
                    <div class="flex justify-between">
                        <span class="text-slate-500">RouterOS Profile:</span>
                        <span class="font-semibold text-indigo-900" x-text="selectedPkg?.name || '--'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Local Address (GW):</span>
                        <span class="text-slate-800" x-text="selectedPkg?.local_address || '--'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Remote Address / Pool:</span>
                        <span class="text-purple-800" x-text="selectedPkg?.remote_address || '--'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Only One Session:</span>
                        <span class="text-slate-700 uppercase" x-text="selectedPkg?.only_one || 'default'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Address List:</span>
                        <span class="text-slate-700" x-text="selectedPkg?.address_list || '--'"></span>
                    </div>
                </div>

                <!-- Speeds Matrix -->
                <div class="grid grid-cols-3 gap-2 text-center text-[10.5px]">
                    <div class="p-2 rounded bg-slate-50 border border-slate-200">
                        <span class="text-slate-400 block">Facebook</span>
                        <span class="font-mono font-bold text-slate-700" x-text="(selectedPkg?.facebook_speed || '0') + ' M'"></span>
                    </div>
                    <div class="p-2 rounded bg-slate-50 border border-slate-200">
                        <span class="text-slate-400 block">YouTube</span>
                        <span class="font-mono font-bold text-slate-700" x-text="(selectedPkg?.youtube_speed || '0') + ' M'"></span>
                    </div>
                    <div class="p-2 rounded bg-slate-50 border border-slate-200">
                        <span class="text-slate-400 block">BDIX</span>
                        <span class="font-mono font-bold text-slate-700" x-text="(selectedPkg?.bdix_speed || '0') + ' M'"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <a :href="'/admin/network/packages/' + selectedPkg?.id + '/edit'"
                   class="h-[30px] px-3 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-pen-to-square text-[10px]"></i>
                    <span>Edit Package</span>
                </a>
                <button type="button" @click="showDetailsModal = false" class="h-[30px] px-3.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- IMPORT FROM MIKROTIK MODAL -->
    <div x-show="showImportModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="showImportModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Import Profiles from MikroTik</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Scan PPP &amp; Hotspot user profiles</p>
                    </div>
                </div>
                <button type="button" @click="showImportModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('tenant.network.packages.import-mikrotik') }}" method="POST" class="p-4 space-y-3.5 text-xs">
                @csrf
                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select MikroTik Router <span class="text-rose-500">*</span>
                    </label>
                    <select name="router_id" required class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">Select a router...</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Profiles to Import <span class="text-rose-500">*</span>
                    </label>
                    <select name="import_type" required class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="both">Both PPPoE &amp; Hotspot Profiles</option>
                        <option value="pppoe">PPPoE Profiles (/ppp/profile) Only</option>
                        <option value="hotspot">Hotspot Profiles (/ip/hotspot/user/profile) Only</option>
                    </select>
                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 -mx-4 -mb-4 flex items-center justify-end gap-2">
                    <button type="button" @click="showImportModal = false" class="h-[34px] px-3.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] px-4 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-cloud-arrow-down text-[10px]"></i>
                        <span>Start Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function packagesIndexManager() {
    return {
        showImportModal: false,
        showDetailsModal: false,
        selectedPkg: null,
        activeMenuPkg: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        syncingPkgId: null,
        toast: {
            show: false,
            message: '',
            type: 'success'
        },

        toggleMenu(pkg, event) {
            if (this.activeMenuPkg?.id === pkg.id) {
                this.activeMenuPkg = null;
                return;
            }
            this.activeMenuPkg = pkg;
            
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

        viewDetailsFromMenu() {
            if (!this.activeMenuPkg) return;
            this.selectedPkg = this.activeMenuPkg;
            this.activeMenuPkg = null;
            this.showDetailsModal = true;
        },

        syncFromMenu() {
            if (!this.activeMenuPkg) return;
            const pkg = this.activeMenuPkg;
            this.activeMenuPkg = null;
            this.syncPackageToRouter(pkg);
        },

        copyProfileFromMenu() {
            if (!this.activeMenuPkg) return;
            const profileName = this.activeMenuPkg.name || this.activeMenuPkg.mikrotik_profile || '';
            this.activeMenuPkg = null;
            this.copyToClipboard(profileName, 'Profile Name');
        },

        toggleFromMenu() {
            if (!this.activeMenuPkg) return;
            const pkgId = this.activeMenuPkg.id;
            this.activeMenuPkg = null;
            this.togglePackageStatus(pkgId);
        },

        deleteFromMenu() {
            if (!this.activeMenuPkg) return;
            const pkg = this.activeMenuPkg;
            this.activeMenuPkg = null;
            this.confirmDeletePackage(pkg.id, pkg.name || pkg.package_name);
        },

        async syncPackageToRouter(pkg) {
            this.syncingPkgId = pkg.id;
            try {
                const res = await fetch(`/admin/network/packages/${pkg.id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message || 'Package synced to MikroTik successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Sync to MikroTik failed.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while syncing package to router.', 'error');
            } finally {
                this.syncingPkgId = null;
            }
        },

        async togglePackageStatus(pkgId) {
            try {
                const res = await fetch(`/admin/network/packages/${pkgId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, data.is_active ? 'success' : 'info');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast('Failed to toggle package status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while toggling package status.', 'error');
            }
        },

        confirmDeletePackage(id, name) {
            this.activeMenuPkg = null;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Bandwidth Package?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete package <strong class="text-slate-900 font-semibold">${name}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">This will remove the package and profile from MikroTik and RADIUS systems.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete Package',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/admin/network/packages/${id}`;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete package '${name}'? This will remove the package and profile from MikroTik and RADIUS.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/network/packages/${id}`;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },

        copyToClipboard(text, label = 'Copied') {
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

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        }
    };
}
</script>
@endpush
