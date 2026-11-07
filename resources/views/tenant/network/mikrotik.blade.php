@extends('tenant.layouts.app')

@section('title', 'MikroTik Gateways & Routers - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
@php
    $routersData = $routers->getCollection()->map(function($r) {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'ip_address' => $r->ip_address,
            'connection_type' => $r->connection_type ?: 'radius',
            'radius_secret' => $r->decrypted_radius_secret ?: ($r->decrypted_password ?: 'radius@123'),
            'password' => $r->decrypted_password ?: '',
            'coa_port' => $r->coa_port ?: 3799,
            'api_port' => $r->api_port ?: 8728,
            'username' => $r->username ?: 'admin',
            'use_ssl' => (bool)$r->use_ssl,
            'model' => $r->model ?: '',
            'ros_version' => $r->ros_version ?: 'v7.x',
            'cpu_load' => $r->cpu_load ?? 0,
            'uptime' => $r->uptime ?: '--',
            'free_memory' => $r->free_memory ? round($r->free_memory / (1024*1024)) . ' MB' : '--',
            'total_memory' => $r->total_memory ? round($r->total_memory / (1024*1024)) . ' MB' : '--',
            'is_active' => (bool)$r->is_active,
            'status' => $r->status,
            'olts_count' => $r->olts_count ?? 0,
            'nas_count' => $r->nas_clients_count ?? 0,
        ];
    })->values();

    $radiusCount = \App\Models\TenantRouter::where('tenant_id', $tenant->id)->where('connection_type', 'radius')->count();
    $apiHybridCount = \App\Models\TenantRouter::where('tenant_id', $tenant->id)->whereIn('connection_type', ['api', 'hybrid'])->count();
    $isMoreOpen = request()->filled('active');
    $activeMoreCount = request()->filled('active') ? 1 : 0;
@endphp

<div class="space-y-3" 
     x-data="mikrotikManager()" 
     @scroll.window="activeMenuRouter = null" 
     @resize.window="activeMenuRouter = null">

    <!-- Toast Notifications -->
    <div x-show="toast.show" 
         x-cloak
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
         }">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">MikroTik Gateways &amp; Routers</h1>
                <!-- Plan Quota Badge -->
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border {{ $isQuotaReached ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-purple-50 text-purple-700 border-purple-200' }}"
                      title="Router quota allowed in your ISP plan">
                    <i class="fas fa-layer-group text-[9px]"></i>
                    <span>Plan: {{ $plan->name ?? 'Standard' }} ({{ $totalRouters }}/{{ $mikrotikQuota > 0 ? $mikrotikQuota : '∞' }})</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            @if(Route::has('tenant.network.map'))
                <a href="{{ route('tenant.network.map') }}" 
                   class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-project-diagram text-indigo-600 text-[11px]"></i>
                    <span>Topology Map</span>
                </a>
            @endif

            <button type="button" 
                    @click="openGlobalScriptModal()"
                    class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
                    title="Generate FreeRADIUS client script">
                <i class="fas fa-terminal text-slate-600 text-[11px]"></i>
                <span>RADIUS Script</span>
            </button>

            <button type="button" 
                    @click="openRadiusModal()" 
                    class="h-[34px] px-3 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                <i class="fas fa-shield-halved text-[10px]"></i>
                <span>+ FreeRADIUS Router</span>
            </button>

            <button type="button" 
                    @click="openApiModal()" 
                    class="h-[34px] px-3 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-cyan-600 hover:bg-cyan-700' }}">
                <i class="fas fa-code text-[10px]"></i>
                <span>+ API Router</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Plan Quota -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Plan Quota</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $isQuotaReached ? 'text-rose-600' : 'text-slate-800' }} truncate">
                    {{ $totalRouters }} / {{ $mikrotikQuota > 0 ? $mikrotikQuota : '∞' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 {{ $isQuotaReached ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-blue-50 text-blue-600 border-blue-100' }} flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Online Fleet -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Online Fleet</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-600 truncate">{{ number_format($onlineRouters) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Card 3: Offline / Down -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Offline / Down</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $offlineRouters > 0 ? 'text-rose-600' : 'text-slate-400' }} truncate">
                    {{ number_format($offlineRouters) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 4: RADIUS Mode -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">RADIUS AAA</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-700 truncate">{{ number_format($radiusCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Card 5: API / Hybrid -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">API / Hybrid</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">{{ number_format($apiHybridCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-microchip"></i>
            </div>
        </div>

        <!-- Card 6: RADIUS Server IP -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">RADIUS Host IP</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-800 truncate">{{ $serverIp }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
            showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
            searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form action="{{ route('tenant.network.mikrotik') }}" method="GET">
            <!-- Row 1: Primary Filters -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Input with 1-Click Clear -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search router name, host IP, hardware model..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Connection Architecture Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="connection_type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Modes</option>
                        <option value="radius" {{ request('connection_type') === 'radius' ? 'selected' : '' }}>RADIUS AAA</option>
                        <option value="api" {{ request('connection_type') === 'api' ? 'selected' : '' }}>RouterOS API</option>
                        <option value="hybrid" {{ request('connection_type') === 'hybrid' ? 'selected' : '' }}>Hybrid (Both)</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Statuses</option>
                        <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10 / page</option>
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
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600'">
                    <i class="fas fa-sliders text-[11px]"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                        {{ $activeMoreCount }}
                    </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-150"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Filter & Reset Button Sequence (Strictly Filter Cyan first, Reset Slate second, Always visible) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>

                    <a href="{{ route('tenant.network.mikrotik') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset All Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            <!-- Row 2: Secondary Filters Drawer -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- State Filter (Enabled / Disabled) -->
                <div class="w-full sm:w-48 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Gateway State</label>
                    <select name="active" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All States</option>
                        <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Enabled Gateways Only</option>
                        <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Disabled Gateways Only</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strict Minimal Core Columns - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="text-center font-semibold">Router Name</th>
                        <th class="text-center font-semibold">Host IP &amp; Port</th>
                        <th class="text-center font-semibold">Architecture</th>
                        <th class="text-center font-semibold">Hardware &amp; ROS</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $index => $router)
                        @php
                            $connBadge = $router->connection_badge;
                        @endphp
                        <tr class="{{ !$router->is_active ? 'opacity-70' : '' }}">
                            
                            <!-- 1. Serial No -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $routers->firstItem() + $index }}
                            </td>

                            <!-- 2. Status Badge -->
                            <td class="text-center">
                                @if(!$router->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </span>
                                @elseif($router->status === 'online')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @elseif($router->status === 'offline')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $router->last_error }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Offline</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="{{ $router->last_error }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>{{ ucfirst($router->status ?? 'pending') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Router Name (Clickable to View Details) -->
                            <td class="text-left font-bold">
                                <button type="button" 
                                        @click="viewRouterDetails({{ json_encode([
                                            'id' => $router->id,
                                            'name' => $router->name,
                                            'ip_address' => $router->ip_address,
                                            'connection_type' => $router->connection_type ?: 'radius',
                                            'radius_secret' => $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radius@123'),
                                            'password' => $router->decrypted_password ?: '',
                                            'coa_port' => $router->coa_port ?: 3799,
                                            'api_port' => $router->api_port ?: 8728,
                                            'username' => $router->username ?: 'admin',
                                            'use_ssl' => (bool)$router->use_ssl,
                                            'model' => $router->model ?: 'MikroTik RouterBOARD',
                                            'ros_version' => $router->ros_version ?: 'v7.x',
                                            'cpu_load' => $router->cpu_load ?? 0,
                                            'uptime' => $router->uptime ?: '--',
                                            'free_memory' => $router->free_memory ? round($router->free_memory / (1024*1024)) . ' MB' : '--',
                                            'total_memory' => $router->total_memory ? round($router->total_memory / (1024*1024)) . ' MB' : '--',
                                            'is_active' => (bool)$router->is_active,
                                            'status' => $router->status,
                                            'olts_count' => $router->olts_count ?? 0,
                                            'nas_count' => $router->nas_clients_count ?? 0,
                                        ]) }})"
                                        class="text-slate-800 hover:text-cyan-700 hover:underline cursor-pointer">
                                    {{ $router->name }}
                                </button>
                            </td>

                            <!-- 4. Host IP & Port -->
                            <td class="text-center font-mono">
                                <div class="inline-flex items-center gap-1.5 flex-nowrap">
                                    <span class="font-semibold text-slate-800">{{ $router->ip_address }}</span>
                                    @if($router->connection_type === 'api' || $router->connection_type === 'hybrid')
                                        <span class="px-1.5 py-0.5 rounded text-[9.5px] bg-slate-100 text-slate-600 border border-slate-200">
                                            :{{ $router->api_port ?: 8728 }}
                                        </span>
                                    @elseif($router->coa_port)
                                        <span class="px-1.5 py-0.5 rounded text-[9.5px] bg-slate-100 text-slate-600 border border-slate-200" title="CoA Port">
                                            :{{ $router->coa_port }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 5. Architecture Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $connBadge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    <i class="fas {{ $connBadge['icon'] ?? 'fa-server' }} text-[9px]"></i>
                                    <span>{{ $connBadge['label'] ?? ucfirst($router->connection_type) }}</span>
                                </span>
                            </td>

                            <!-- 6. Hardware & ROS -->
                            <td class="text-left font-medium text-slate-700">
                                <div class="flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                                    <span>{{ $router->model ?: 'RouterBOARD' }}</span>
                                    @if($router->ros_version)
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        {{ $router->ros_version }}
                                    </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 7. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode([
                                            'id' => $router->id,
                                            'name' => $router->name,
                                            'ip_address' => $router->ip_address,
                                            'connection_type' => $router->connection_type ?: 'radius',
                                            'radius_secret' => $router->decrypted_radius_secret ?: ($router->decrypted_password ?: 'radius@123'),
                                            'password' => $router->decrypted_password ?: '',
                                            'coa_port' => $router->coa_port ?: 3799,
                                            'api_port' => $router->api_port ?: 8728,
                                            'username' => $router->username ?: 'admin',
                                            'use_ssl' => (bool)$router->use_ssl,
                                            'model' => $router->model ?: 'MikroTik RouterBOARD',
                                            'ros_version' => $router->ros_version ?: 'v7.x',
                                            'cpu_load' => $router->cpu_load ?? 0,
                                            'uptime' => $router->uptime ?: '--',
                                            'free_memory' => $router->free_memory ? round($router->free_memory / (1024*1024)) . ' MB' : '--',
                                            'total_memory' => $router->total_memory ? round($router->total_memory / (1024*1024)) . ' MB' : '--',
                                            'is_active' => (bool)$router->is_active,
                                            'status' => $router->status,
                                            'olts_count' => $router->olts_count ?? 0,
                                            'nas_count' => $router->nas_clients_count ?? 0,
                                        ]) }}, $event)"
                                        class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <i class="fas fa-server text-base"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700">No MikroTik Gateways Found</span>
                                    <p class="text-[11px] text-slate-400 max-w-sm">No routers match your filter criteria or no gateways have been configured yet.</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <button type="button" 
                                                @click="openRadiusModal()" 
                                                class="h-[34px] px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-shield-halved text-xs"></i>
                                            <span>+ FreeRADIUS Router</span>
                                        </button>
                                        <button type="button" 
                                                @click="openApiModal()" 
                                                class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-code text-xs"></i>
                                            <span>+ API Router</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($routers->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    Showing <span class="font-semibold text-slate-700">{{ $routers->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $routers->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $routers->total() }}</span> gateways
                </div>
                <div>
                    {{ $routers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenuRouter !== null" 
         x-cloak
         @click.away="activeMenuRouter = null"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 space-y-0.5">
        
        <!-- Group 1: Diagnostics & Telemetry -->
        <button type="button" 
                @click="viewRouterDetails(activeMenuRouter)" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-circle-info w-4 text-cyan-600 text-[11px]"></i>
            <span>View Details</span>
        </button>

        <a :href="'/admin/network/mikrotik/' + activeMenuRouter?.id" 
           class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left">
            <i class="fas fa-chart-line w-4 text-indigo-500 text-[11px]"></i>
            <span>Live Telemetry &amp; Traffic</span>
        </a>

        <button type="button" 
                @click="testFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-bolt w-4 text-emerald-600 text-[11px]"></i>
            <span>Test Connection Socket</span>
        </button>

        <button type="button" 
                @click="scriptFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-terminal w-4 text-blue-600 text-[11px]"></i>
            <span>RADIUS Setup Script</span>
        </button>

        <button type="button" 
                @click="copyToClipboard(activeMenuRouter?.radius_secret || '', 'Shared Secret')" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-key w-4 text-amber-500 text-[11px]"></i>
            <span>Copy Shared Secret</span>
        </button>

        <button type="button" 
                @click="copyToClipboard(activeMenuRouter?.ip_address || '', 'Host IP')" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-copy w-4 text-slate-500 text-[11px]"></i>
            <span>Copy Host IP</span>
        </button>

        <div class="border-t border-slate-100 my-0.5"></div>

        <!-- Group 2: Configuration & Management -->
        <button type="button" 
                @click="toggleStatusFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas w-4 text-center text-[11px]" 
               :class="activeMenuRouter?.is_active ? 'fa-toggle-on text-emerald-600' : 'fa-toggle-off text-slate-400'"></i>
            <span x-text="activeMenuRouter?.is_active ? 'Disable Gateway' : 'Enable Gateway'"></span>
        </button>

        <button type="button" 
                @click="editRouterFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-pen-to-square w-4 text-amber-500 text-[11px]"></i>
            <span>Edit Configuration</span>
        </button>

        <button type="button" 
                @click="deleteFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Gateway</span>
        </button>
    </div>

    <!-- MODAL 1: FREERADIUS AAA GATEWAY MODAL (Add & Edit) -->
    <div x-show="radiusModal.open" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="radiusModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="radiusModal.isEdit ? 'Edit FreeRADIUS Gateway Configuration' : 'Add FreeRADIUS MikroTik Gateway'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Standard AAA architecture for PPPoE and Hotspot subscriber billing.</p>
                    </div>
                </div>
                <button type="button" @click="radiusModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form :action="radiusModal.isEdit ? `/admin/network/mikrotik/${radiusModal.id}` : '{{ route('tenant.network.mikrotik.store') }}'" 
                  method="POST" 
                  class="p-4 space-y-3 max-h-[78vh] overflow-y-auto">
                @csrf
                <template x-if="radiusModal.isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="connection_type" value="radius">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Router Name / Identity <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               x-model="radiusModal.name" 
                               required 
                               placeholder="e.g. Core-CCR2004-DHAKA" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Hardware Model
                        </label>
                        <input type="text" 
                               name="model" 
                               x-model="radiusModal.model" 
                               placeholder="e.g. CCR2004-16G-2S+" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Router IP Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="ip_address" 
                               x-model="radiusModal.ip_address" 
                               required 
                               placeholder="e.g. 103.59.177.10" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            CoA / Disconnect Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="coa_port" 
                               x-model="radiusModal.coa_port" 
                               required 
                               placeholder="3799" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        RADIUS Shared Secret <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="radiusModal.showRadiusSecret ? 'text' : 'password'" 
                               name="radius_secret" 
                               x-model="radiusModal.radius_secret" 
                               required 
                               placeholder="radius@123" 
                               class="w-full h-[34px] pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <button type="button" 
                                @click="radiusModal.showRadiusSecret = !radiusModal.showRadiusSecret" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fas text-xs" :class="radiusModal.showRadiusSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- RouterOS Quick Script Snippet in Modal -->
                <div class="rounded-lg overflow-hidden border border-slate-800 bg-slate-900">
                    <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>RouterOS Quick Setup Script</span>
                        <button type="button" 
                                @click="copyToClipboard(getRadiusModalScript(), 'RADIUS Script')" 
                                class="text-cyan-400 hover:text-cyan-300 font-sans font-medium flex items-center gap-1 transition cursor-pointer">
                            <i class="fas fa-copy text-[10px]"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                    <pre class="p-2.5 text-cyan-300 font-mono text-[10.5px] overflow-x-auto leading-relaxed max-h-24 selection:bg-cyan-900" x-text="getRadiusModalScript()"></pre>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" 
                           id="radius_is_active" 
                           name="is_active" 
                           value="1" 
                           x-model="radiusModal.is_active" 
                           class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                    <label for="radius_is_active" class="text-xs text-slate-700 font-medium cursor-pointer">
                        Enable this gateway immediately for active AAA authentication
                    </label>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="radiusModal.open = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="h-[34px] px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        <span x-text="radiusModal.isEdit ? 'Update Gateway' : 'Save Gateway'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: ROUTEROS API GATEWAY MODAL (Add & Edit) -->
    <div x-show="apiModal.open" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="apiModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-code"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="apiModal.isEdit ? 'Edit RouterOS API Gateway' : 'Connect MikroTik via RouterOS API'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Direct RouterOS API socket communication for live telemetry &amp; queues.</p>
                    </div>
                </div>
                <button type="button" @click="apiModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form :action="apiModal.isEdit ? `/admin/network/mikrotik/${apiModal.id}` : '{{ route('tenant.network.mikrotik.store') }}'" 
                  method="POST" 
                  class="p-4 space-y-3 max-h-[78vh] overflow-y-auto">
                @csrf
                <template x-if="apiModal.isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="connection_type" value="api">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Router Name / Identity <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               x-model="apiModal.name" 
                               required 
                               placeholder="e.g. Edge-Router-Chittagong" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Hardware Model
                        </label>
                        <input type="text" 
                               name="model" 
                               x-model="apiModal.model" 
                               placeholder="e.g. RB4011iGS+" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Router IP Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="ip_address" 
                               x-model="apiModal.ip_address" 
                               required 
                               placeholder="e.g. 192.168.88.1" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            RouterOS API Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="api_port" 
                               x-model="apiModal.api_port" 
                               required 
                               placeholder="8728" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            API Username <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="username" 
                               x-model="apiModal.username" 
                               required 
                               placeholder="admin" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            API Password <span class="text-rose-500" x-show="!apiModal.isEdit">*</span>
                        </label>
                        <div class="relative">
                            <input :type="apiModal.showPassword ? 'text' : 'password'" 
                                   name="password" 
                                   x-model="apiModal.password" 
                                   :required="!apiModal.isEdit" 
                                   placeholder="••••••••" 
                                   class="w-full h-[34px] pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <button type="button" 
                                    @click="apiModal.showPassword = !apiModal.showPassword" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fas text-xs" :class="apiModal.showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" 
                               name="use_ssl" 
                               value="1" 
                               x-model="apiModal.use_ssl" 
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                        <span class="text-xs text-slate-700 font-medium">Use SSL / TLS (Port 8729)</span>
                    </label>

                    <button type="button" 
                            @click="testApiModalSocket()" 
                            :disabled="apiModal.testingSocket"
                            class="h-[30px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-[11px] font-semibold transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-cyan-600" x-show="apiModal.testingSocket"></i>
                        <i class="fas fa-bolt text-emerald-600" x-show="!apiModal.testingSocket"></i>
                        <span>Test API Socket</span>
                    </button>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="apiModal.open = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="h-[34px] px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        <span x-text="apiModal.isEdit ? 'Update Gateway' : 'Connect Gateway'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: VIEW GATEWAY DETAILS MODAL (Rule 3) -->
    <div x-show="detailsModal.open" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="detailsModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-server"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="detailsModal.data?.name || 'MikroTik Gateway Details'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`${detailsModal.data?.model || 'RouterBOARD'} • ROS ${detailsModal.data?.ros_version || 'v7.x'}`"></p>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto">
                <!-- Status & Telemetry 3 Metric Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Fleet Status</span>
                        <span class="text-sm font-bold block mt-0.5" 
                              :class="detailsModal.data?.status === 'online' ? 'text-emerald-600' : 'text-rose-600'" 
                              x-text="detailsModal.data?.is_active ? (detailsModal.data?.status?.toUpperCase() || 'OFFLINE') : 'DISABLED'">
                        </span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">CPU Load</span>
                        <span class="text-sm font-mono font-bold text-slate-800 block mt-0.5" x-text="`${detailsModal.data?.cpu_load || 0}%`"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">System Uptime</span>
                        <span class="text-sm font-mono font-bold text-slate-800 block mt-0.5 truncate" x-text="detailsModal.data?.uptime || '--'"></span>
                    </div>
                </div>

                <!-- Network Properties Grid -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11.5px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Gateway Network Configuration</span>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Host IP Address:</span>
                            <span class="font-mono font-bold text-slate-800" x-text="detailsModal.data?.ip_address"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Architecture Mode:</span>
                            <span class="font-semibold text-slate-800 uppercase" x-text="detailsModal.data?.connection_type"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsModal.data?.connection_type === 'api' || detailsModal.data?.connection_type === 'hybrid'">
                            <span class="text-slate-500">RouterOS API Port:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsModal.data?.api_port || 8728"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsModal.data?.coa_port">
                            <span class="text-slate-500">CoA / Disconnect Port:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsModal.data?.coa_port"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsModal.data?.free_memory">
                            <span class="text-slate-500">Free / Total Memory:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`${detailsModal.data?.free_memory} / ${detailsModal.data?.total_memory}`"></span>
                        </div>
                    </div>
                </div>

                <!-- Associated Downstream Devices -->
                <div class="grid grid-cols-2 gap-2 text-[11.5px] p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-slate-400 block font-medium">Associated OLTs:</span>
                        <span class="font-bold text-slate-800" x-text="`${detailsModal.data?.olts_count || 0} Devices`"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">RADIUS NAS Clients:</span>
                        <span class="font-bold text-slate-800" x-text="`${detailsModal.data?.nas_count || 0} Clients`"></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-2">
                    <button type="button" 
                            @click="testRouterConnection(detailsModal.data?.id)" 
                            class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-bolt text-xs"></i>
                        <span>Test Socket</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="detailsModal.open = false" 
                                class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                            Close
                        </button>
                        <a :href="'/admin/network/mikrotik/' + detailsModal.data?.id" 
                           class="h-[34px] px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-chart-line text-xs"></i>
                            <span>Live Telemetry</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 4: SOCKET TEST / PROBE MODAL (Rule 3) -->
    <div x-show="probeModal.open" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="probeModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Connection Socket Diagnostics</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="probeModal.routerName"></p>
                    </div>
                </div>
                <button type="button" @click="probeModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3">
                <div x-show="probeModal.loading" class="py-6 text-center space-y-2.5">
                    <i class="fas fa-circle-notch fa-spin text-2xl text-cyan-600"></i>
                    <p class="text-xs font-medium text-slate-700">Testing connection socket...</p>
                    <p class="text-[11px] text-slate-400">Pinging IP and handshaking with RouterOS API</p>
                </div>

                <div x-show="!probeModal.loading && probeModal.result" class="space-y-3">
                    <div class="p-3 rounded-lg border text-xs" 
                         :class="probeModal.result?.success ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <i class="fas text-sm" :class="probeModal.result?.success ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600'"></i>
                            <span x-text="probeModal.result?.success ? 'Socket Handshake Established!' : 'Connection Socket Failed'"></span>
                        </div>
                        <p class="text-[11.5px]" x-text="probeModal.result?.message"></p>
                    </div>

                    <div x-show="probeModal.result?.success" class="grid grid-cols-2 gap-2 text-[11px] p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <div>
                            <span class="text-slate-400 block font-medium">RouterOS Version:</span>
                            <span class="font-mono font-bold text-slate-800" x-text="probeModal.result?.ros_version || 'v7.x'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Model / Architecture:</span>
                            <span class="font-semibold text-slate-800" x-text="probeModal.result?.model || 'RouterBOARD'"></span>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end">
                    <button type="button" @click="probeModal.open = false" class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Close Diagnostics
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: FREERADIUS CLIENT SETUP SCRIPT MODAL (Rule 3) -->
    <div x-show="showScriptModal" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showScriptModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">FreeRADIUS Client Terminal Script</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="activeRouterName"></p>
                    </div>
                </div>
                <button type="button" @click="showScriptModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-2.5 text-xs">
                <p class="text-slate-600 text-[11px] leading-relaxed">
                    Paste this script into your MikroTik Winbox/SSH terminal (<strong>New Terminal</strong>) to configure the FreeRADIUS AAA client:
                </p>

                <div class="rounded-lg overflow-hidden border border-slate-800 bg-slate-900">
                    <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>RouterOS Script</span>
                        <button type="button" 
                                @click="copyToClipboard(activeScript, 'RADIUS Script')" 
                                class="text-cyan-400 hover:text-cyan-300 font-sans font-medium flex items-center gap-1 transition cursor-pointer">
                            <i class="fas fa-copy text-[10px]"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                    <pre class="p-3 text-cyan-300 font-mono text-[11px] overflow-x-auto leading-relaxed selection:bg-cyan-900" x-text="activeScript"></pre>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="copyToClipboard(activeScript, 'RADIUS Script')" 
                        class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-copy text-[10px]"></i>
                    <span>Copy Script</span>
                </button>

                <button type="button" @click="showScriptModal = false" class="h-[34px] px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function mikrotikManager() {
    return {
        activeMenuRouter: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        showScriptModal: false,
        activeScript: '',
        activeRouterName: '',
        serverIp: '{{ $serverIp ?? "103.59.177.136" }}',
        routers: @json($routersData),

        detailsModal: {
            open: false,
            data: null
        },

        radiusModal: {
            open: false,
            isEdit: false,
            id: null,
            name: '',
            ip_address: '',
            model: '',
            radius_secret: 'radius@123',
            coa_port: 3799,
            is_active: true,
            showRadiusSecret: false
        },

        apiModal: {
            open: false,
            isEdit: false,
            id: null,
            name: '',
            ip_address: '',
            model: '',
            api_port: 8728,
            username: 'admin',
            password: '',
            use_ssl: false,
            is_active: true,
            showPassword: false,
            testingSocket: false
        },

        probeModal: {
            open: false,
            loading: false,
            routerId: null,
            routerName: '',
            result: null
        },

        openRadiusModal() {
            this.activeMenuRouter = null;
            this.radiusModal = {
                open: true,
                isEdit: false,
                id: null,
                name: '',
                ip_address: '',
                model: '',
                radius_secret: 'radius@123',
                coa_port: 3799,
                is_active: true,
                showRadiusSecret: false
            };
        },

        openEditRadiusModal(router) {
            this.activeMenuRouter = null;
            this.radiusModal = {
                open: true,
                isEdit: true,
                id: router.id,
                name: router.name || '',
                ip_address: router.ip_address || '',
                model: router.model || '',
                radius_secret: router.radius_secret || 'radius@123',
                coa_port: router.coa_port || 3799,
                is_active: !!router.is_active,
                showRadiusSecret: false
            };
        },

        openApiModal() {
            this.activeMenuRouter = null;
            this.apiModal = {
                open: true,
                isEdit: false,
                id: null,
                name: '',
                ip_address: '',
                model: '',
                api_port: 8728,
                username: 'admin',
                password: '',
                use_ssl: false,
                is_active: true,
                showPassword: false,
                testingSocket: false
            };
        },

        openEditApiModal(router) {
            this.activeMenuRouter = null;
            this.apiModal = {
                open: true,
                isEdit: true,
                id: router.id,
                name: router.name || '',
                ip_address: router.ip_address || '',
                model: router.model || '',
                api_port: router.api_port || 8728,
                username: router.username || 'admin',
                password: '',
                use_ssl: !!router.use_ssl,
                is_active: !!router.is_active,
                showPassword: false,
                testingSocket: false
            };
        },

        viewRouterDetails(router) {
            this.activeMenuRouter = null;
            this.detailsModal.data = router;
            this.detailsModal.open = true;
        },

        editRouterFromMenu() {
            if (!this.activeMenuRouter) return;
            const router = this.activeMenuRouter;
            this.activeMenuRouter = null;

            if (router.connection_type === 'api') {
                this.openEditApiModal(router);
            } else {
                this.openEditRadiusModal(router);
            }
        },

        getRadiusModalScript() {
            const sIp = this.serverIp || '103.59.177.136';
            const sec = this.radiusModal.radius_secret || 'radius@123';
            const coa = this.radiusModal.coa_port || 3799;

            return `/radius add service=ppp,hotspot,login address=${sIp} secret="${sec}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS"\n/radius incoming set accept=yes port=${coa}\n/ppp aaa set use-radius=yes accounting=yes interim-update=5m`;
        },

        getMikrotikScript(ip, secret) {
            const sIp = this.serverIp || '103.59.177.136';
            const sec = secret || 'radius@123';

            return `# 1. Add FreeRADIUS Server\n/radius add service=ppp,hotspot,login address=${sIp} secret="${sec}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS"\n\n# 2. Enable CoA / Disconnect Requests\n/radius incoming set accept=yes port=3799\n\n# 3. Enable PPPoE RADIUS AAA Accounting\n/ppp aaa set use-radius=yes accounting=yes interim-update=5m`;
        },

        async testApiModalSocket() {
            if (!this.apiModal.ip_address) {
                alert('Please enter Router IP Address first.');
                return;
            }

            this.apiModal.testingSocket = true;

            try {
                const res = await fetch(`{{ route('tenant.network.mikrotik.test') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ip_address: this.apiModal.ip_address,
                        api_port: this.apiModal.api_port || 8728,
                        username: this.apiModal.username || 'admin',
                        password: this.apiModal.password || '',
                        use_ssl: this.apiModal.use_ssl
                    })
                });
                const data = await res.json();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Connection Successful!' : 'Connection Failed',
                        html: `<div class='text-xs text-slate-600 mt-1'>${data.message}` + 
                              (data.ros_version ? `<br><span class='font-mono font-bold text-cyan-800'>RouterOS: ${data.ros_version} (${data.model || ''})</span>` : '') + 
                              `</div>`,
                        confirmButtonColor: data.success ? '#0891b2' : '#e11d48'
                    });
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Network communication error while testing socket.');
            } finally {
                this.apiModal.testingSocket = false;
            }
        },

        toggleMenu(router, event) {
            if (this.activeMenuRouter?.id === router.id) {
                this.activeMenuRouter = null;
                return;
            }
            this.activeMenuRouter = router;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 240;
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

        testFromMenu() {
            if (!this.activeMenuRouter) return;
            const routerId = this.activeMenuRouter.id;
            this.activeMenuRouter = null;
            this.testRouterConnection(routerId);
        },

        scriptFromMenu() {
            if (!this.activeMenuRouter) return;
            const routerId = this.activeMenuRouter.id;
            this.activeMenuRouter = null;
            this.openScriptModal(routerId);
        },

        toggleStatusFromMenu() {
            if (!this.activeMenuRouter) return;
            const routerId = this.activeMenuRouter.id;
            this.activeMenuRouter = null;
            this.toggleRouterStatus(routerId);
        },

        async toggleRouterStatus(routerId) {
            try {
                const res = await fetch(`/admin/network/mikrotik/${routerId}/toggle-status`, {
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
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    this.showToast('Failed to update router status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while updating router status.', 'error');
            }
        },

        deleteFromMenu() {
            if (!this.activeMenuRouter) return;
            const routerId = this.activeMenuRouter.id;
            const routerName = this.activeMenuRouter.name;
            this.activeMenuRouter = null;
            this.confirmDeleteRouter(routerId, routerName);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        copyToClipboard(text, label = 'Copied') {
            this.activeMenuRouter = null;
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(`${label} copied to clipboard!`, 'success');
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    this.showToast(`${label} copied to clipboard!`, 'success');
                } catch (e) {
                    alert('Failed to copy to clipboard.');
                }
                document.body.removeChild(ta);
            }
        },

        openGlobalScriptModal() {
            this.activeRouterName = 'All MikroTik Routers';
            this.activeScript = this.getMikrotikScript(this.serverIp, 'radius@123');
            this.showScriptModal = true;
        },

        openScriptModal(routerId) {
            const router = this.routers.find(r => r.id == routerId);
            if (router) {
                this.activeRouterName = router.name;
                this.activeScript = this.getMikrotikScript(router.ip_address, router.radius_secret);
            } else {
                this.activeRouterName = 'MikroTik Router';
                this.activeScript = this.getMikrotikScript(this.serverIp, 'radius@123');
            }
            this.showScriptModal = true;
        },

        async testRouterConnection(routerId) {
            if (!routerId) return;
            const router = this.routers.find(r => r.id == routerId);
            this.probeModal.routerId = routerId;
            this.probeModal.routerName = router ? router.name : 'MikroTik Gateway';
            this.probeModal.loading = true;
            this.probeModal.result = null;
            this.probeModal.open = true;
            this.activeMenuRouter = null;

            try {
                const res = await fetch(`{{ route('tenant.network.mikrotik.test') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        router_id: routerId,
                        ip_address: router ? router.ip_address : '',
                        api_port: router ? (router.api_port || 8728) : 8728,
                        username: router ? (router.username || 'admin') : 'admin',
                        password: router ? (router.password || '') : '',
                        use_ssl: router ? !!router.use_ssl : false
                    })
                });
                const data = await res.json();
                this.probeModal.result = data;
            } catch (err) {
                this.probeModal.result = {
                    success: false,
                    message: 'Network error or timeout connecting to RouterOS API.'
                };
            } finally {
                this.probeModal.loading = false;
            }
        },

        confirmDeleteRouter(routerId, routerName) {
            this.activeMenuRouter = null;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete MikroTik Gateway?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete gateway <strong class="text-slate-900 font-semibold">${routerName}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">Associated RADIUS NAS clients and telemetry links will be detached.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Gateway',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submitDeleteForm(routerId);
                    }
                });
            } else if (confirm(`Are you sure you want to delete router '${routerName}'?`)) {
                this.submitDeleteForm(routerId);
            }
        },

        submitDeleteForm(routerId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/network/mikrotik/${routerId}`;
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
