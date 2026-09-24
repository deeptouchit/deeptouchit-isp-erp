@extends('tenant.layouts.app')

@section('title', 'MikroTik Gateways & Routers - ' . ($tenant->company_name ?? $tenant->name))

{{-- 1. Page Specific Stylesheets --}}
@push('styles')

@endpush

{{-- 2. Main Content Canvas --}}
@section('content')
@php
    $routersData = $routers->getCollection()->map(function($r) {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'ip_address' => $r->ip_address,
            'connection_type' => $r->connection_type ?: 'radius',
            'radius_secret' => $r->decrypted_radius_secret ?: ($r->decrypted_password ?: 'radius@123'),
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
        ];
    })->values();

    $radiusCount = \App\Models\TenantRouter::where('tenant_id', $tenant->id)->where('connection_type', 'radius')->count();
    $apiHybridCount = \App\Models\TenantRouter::where('tenant_id', $tenant->id)->whereIn('connection_type', ['api', 'hybrid'])->count();
@endphp

<div class="space-y-3" 
     x-data="mikrotikManager()" 
     @scroll.window="activeMenuRouter = null" 
     @resize.window="activeMenuRouter = null">

    <!-- Toast Notifications -->
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

    <!-- 1. Top Compact Header Bar (Strictly Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">MikroTik Gateways &amp; Routers</h2>
                <!-- Plan Quota Badge -->
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold border {{ $isQuotaReached ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-purple-50 text-purple-700 border-purple-200' }}"
                      title="Router quota allowed in your ISP plan">
                    <i class="fas fa-layer-group text-[9px]"></i>
                    <span>Plan: {{ $plan->name ?? 'Standard' }} ({{ $totalRouters }}/{{ $mikrotikQuota > 0 ? $mikrotikQuota : '∞' }})</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <!-- Topology Map -->
            @if(Route::has('tenant.network.map'))
                <a href="{{ route('tenant.network.map') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-project-diagram text-indigo-600 text-[11px]"></i>
                    <span>Topology Map</span>
                </a>
            @endif

            <!-- FreeRADIUS Script Helper -->
            <button type="button" 
                    @click="openGlobalScriptModal()"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs border border-slate-200 cursor-pointer">
                <i class="fas fa-terminal text-slate-600 text-[11px]"></i>
                <span>RADIUS Script</span>
            </button>

            <!-- Add FreeRADIUS Router Modal Button -->
            <button type="button" 
                    @click="openRadiusModal()" 
                    class="px-3.5 py-1.5 rounded-lg text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                <i class="fas fa-shield-halved text-[10px]"></i>
                <span>+ FreeRADIUS Router</span>
            </button>

            <!-- Add API Router Modal Button -->
            <button type="button" 
                    @click="openApiModal()" 
                    class="px-3.5 py-1.5 rounded-lg text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-cyan-600 hover:bg-cyan-700' }}">
                <i class="fas fa-code text-[10px]"></i>
                <span>+ API Router</span>
            </button>
        </div>
    </div>

    <!-- 2. Ultra-Compact KPI Summary Strip (Strictly 6 Cards, px-2.5 py-1.5, Label text-[9px], Value text-[13px] font-mono) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        <!-- Metric 1: Plan Quota -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Plan Quota</span>
                <span class="text-[13px] font-bold tracking-tight font-mono leading-tight block {{ $isQuotaReached ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ $totalRouters }} / {{ $mikrotikQuota > 0 ? $mikrotikQuota : '∞' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 {{ $isQuotaReached ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-blue-50 text-blue-600 border-blue-100' }} flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Metric 2: Online Fleet -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Online Fleet</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">{{ number_format($onlineRouters) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Metric 3: Offline / Alert -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">Offline / Down</span>
                <span class="text-[13px] font-bold {{ $offlineRouters > 0 ? 'text-rose-600' : 'text-slate-400' }} tracking-tight font-mono leading-tight block">
                    {{ number_format($offlineRouters) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Metric 4: RADIUS Mode -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-cyan-700 uppercase tracking-wider block truncate">RADIUS Mode</span>
                <span class="text-[13px] font-bold text-cyan-700 tracking-tight font-mono leading-tight block">{{ number_format($radiusCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Metric 5: API / Hybrid -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">API / Hybrid</span>
                <span class="text-[13px] font-bold text-purple-700 tracking-tight font-mono leading-tight block">{{ number_format($apiHybridCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-microchip"></i>
            </div>
        </div>

        <!-- Metric 6: FreeRADIUS IP -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">RADIUS IP</span>
                <span class="text-[13px] font-bold text-indigo-800 tracking-tight font-mono leading-tight block truncate">{{ $serverIp }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Strict Master Layout Standards) -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.mikrotik') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2.5">
            
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search router name, host IP, model..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- Connection Architecture -->
            <div>
                <select name="connection_type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Modes</option>
                    <option value="radius" {{ request('connection_type') === 'radius' ? 'selected' : '' }}>RADIUS AAA</option>
                    <option value="api" {{ request('connection_type') === 'api' ? 'selected' : '' }}>RouterOS API</option>
                    <option value="hybrid" {{ request('connection_type') === 'hybrid' ? 'selected' : '' }}>Hybrid (Both)</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Statuses</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>🟢 Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>🔴 Offline</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>🟡 Pending</option>
                </select>
            </div>

            <!-- State Filter -->
            <div>
                <select name="active" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All States</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Enabled Only</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Disabled Only</option>
                </select>
            </div>

            <!-- Per Page Pagination Limit -->
            <div>
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 / page</option>
                    <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25 / page</option>
                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100 / page</option>
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.network.mikrotik') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strictly UI_DESIGN.md Compliant - Single Line, NO BOLD TEXT) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Master RouterOS &amp; Gateway Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $routers->total() }} Gateways
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>Router Name</th>
                        <th>Host IP</th>
                        <th>Socket / Port</th>
                        <th>Hardware Model</th>
                        <th>Architecture</th>
                        <th>Uptime</th>
                        <th>State</th>
                        <th class="w-10 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($routers as $index => $router)
                        @php
                            $connBadge = $router->connection_badge;
                        @endphp
                        <tr class="{{ !$router->is_active ? 'opacity-70' : '' }}">
                            
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $routers->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if(!$router->is_active)
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </span>
                                @elseif($router->status === 'online')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @elseif($router->status === 'offline')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200" title="{{ $router->last_error }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Offline</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200" title="{{ $router->last_error }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>{{ ucfirst($router->status ?? 'pending') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Router Name -->
                            <td class="text-cyan-800">
                                <a href="{{ route('tenant.network.mikrotik.show', $router->id) }}" class="hover:underline">
                                    {{ $router->name }}
                                </a>
                            </td>

                            <!-- 4. Host IP -->
                            <td class="font-mono text-slate-800">
                                <span class="inline-flex items-center justify-center gap-1">
                                    <span>{{ $router->ip_address }}</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $router->ip_address }}', 'Host IP')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy IP">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 5. Socket / Port -->
                            <td class="font-mono text-slate-700">
                                @if($router->connection_type === 'api')
                                    <span class="text-blue-700 font-mono text-[11px]">{{ $router->api_port ?: 8728 }}</span>
                                @elseif($router->connection_type === 'hybrid')
                                    <span class="text-purple-700 font-mono text-[11px]">{{ $router->api_port ?: 8728 }}</span>
                                @else
                                    <span class="text-slate-400 font-mono text-[11px]">--</span>
                                @endif
                            </td>

                            <!-- 6. Hardware Model -->
                            <td class="text-slate-800 max-w-[150px] truncate" title="{{ $router->model }}">
                                {{ $router->model ?: 'MikroTik RouterOS' }}
                            </td>

                            <!-- 7. Architecture -->
                            <td>
                                <span class="inline-flex items-center justify-center gap-1 px-1.5 py-0.5 rounded text-[10px] border {{ $connBadge['class'] }}">
                                    <i class="fas {{ $connBadge['icon'] }} text-[8px]"></i>
                                    <span>{{ $connBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 8. Uptime -->
                            <td>
                                @if(!empty($router->uptime) && $router->status !== 'offline')
                                    <span class="inline-flex items-center justify-center gap-1 font-mono text-slate-700" title="System Uptime: {{ $router->uptime }}">
                                        <i class="far fa-clock text-slate-400 text-[10px]"></i>
                                        <span>{{ $router->formatted_uptime }}</span>
                                    </span>
                                @elseif($router->status === 'offline')
                                    <span class="text-rose-500 font-mono">Offline</span>
                                @else
                                    <span class="text-slate-400 font-mono">--</span>
                                @endif
                            </td>

                            <!-- 9. State -->
                            <td>
                                @if($router->is_active)
                                    <button type="button" 
                                            @click="toggleRouterStatus({{ $router->id }})" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer"
                                            title="Click to Disable Gateway">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </button>
                                @else
                                    <button type="button" 
                                            @click="toggleRouterStatus({{ $router->id }})" 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 transition cursor-pointer"
                                            title="Click to Enable Gateway">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </button>
                                @endif
                            </td>

                            <!-- 10. Actions (3-Dot Action Button triggering Floating Dropdown) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ $router->id }}, $event)" 
                                        class="router-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No MikroTik Routers Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Add your MikroTik edge router as a FreeRADIUS NAS client to automate subscriber bandwidth.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" @click="openRadiusModal()" 
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-shield-halved text-[10px]"></i>
                                            <span>+ FreeRADIUS Router</span>
                                        </button>
                                        <button type="button" @click="openApiModal()" 
                                                class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-code text-[10px]"></i>
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

        <!-- Pagination Footer -->
        @if($routers->hasPages() || $routers->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $routers->firstItem() ?? 0 }} to {{ $routers->lastItem() ?? 0 }} of {{ $routers->total() }} results
                </div>
                <div>
                    {{ $routers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Action Menu Floating Dropdown -->
    <div x-show="activeMenuRouter !== null" 
         @click.away="activeMenuRouter = null"
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
            <a :href="'/admin/network/mikrotik/' + activeMenuRouter?.id" 
               class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left text-[11px]">
                <i class="fas fa-chart-line text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>Live Telemetry &amp; Traffic</span>
            </a>

            <button type="button" 
                    @click="testFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-bolt text-emerald-600 w-3.5 text-center text-[10px]"></i>
                <span>Test API Connection</span>
            </button>

            <button type="button" 
                    @click="scriptFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-terminal text-blue-600 w-3.5 text-center text-[10px]"></i>
                <span>RADIUS Setup Script</span>
            </button>
        </div>

        <!-- Group 2: Configuration & State -->
        <div class="py-0.5">
            <button type="button" 
                    @click="toggleStatusFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-slate-50 text-slate-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas w-3.5 text-center text-[10px]" 
                   :class="activeMenuRouter?.is_active ? 'fa-toggle-on text-emerald-600' : 'fa-toggle-off text-slate-400'"></i>
                <span x-text="activeMenuRouter?.is_active ? 'Disable Gateway' : 'Enable Gateway'"></span>
            </button>

            <button type="button" 
                    @click="editRouterFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-500 w-3.5 text-center text-[10px]"></i>
                <span>Edit Router Settings</span>
            </button>
        </div>

        <!-- Group 3: Destructive Action -->
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete Router</span>
            </button>
        </div>
    </div>

    <!-- MODAL 1: FREERADIUS AAA GATEWAY MODAL (Add & Edit) -->
    <div x-show="radiusModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="radiusModal.open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <form :action="radiusModal.isEdit ? `/admin/network/mikrotik/${radiusModal.id}` : '{{ route('tenant.network.mikrotik.store') }}'" method="POST" class="space-y-0">
                @csrf
                <template x-if="radiusModal.isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="connection_type" value="radius">

                <!-- Modal Header -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800" x-text="radiusModal.isEdit ? 'Edit FreeRADIUS Gateway' : 'Add FreeRADIUS Gateway'"></h3>
                            <p class="text-[10.5px] text-slate-500 font-normal">PPPoE ও Hotspot অটো অথেন্টিকেশন ও বিলিং</p>
                        </div>
                    </div>
                    <button type="button" @click="radiusModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 space-y-3.5 text-xs">
                    <!-- Quota Alert Banner -->
                    @if($isQuotaReached)
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-2.5 text-rose-800 text-xs" x-show="!radiusModal.isEdit">
                            <i class="fas fa-triangle-exclamation text-rose-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <strong class="font-semibold block">Plan Router Quota Limit Reached ({{ $totalRouters }}/{{ $mikrotikQuota }})</strong>
                                <span class="text-[11px] text-rose-600">Your ISP subscription plan (<strong>{{ $plan->name ?? 'Standard' }}</strong>) allows up to {{ $mikrotikQuota }} MikroTik routers. To add more routers, please upgrade your subscription plan.</span>
                            </div>
                        </div>
                    @else
                        <div class="p-2.5 bg-purple-50/60 border border-purple-100 rounded-lg flex items-center justify-between text-xs" x-show="!radiusModal.isEdit">
                            <div class="flex items-center gap-1.5 text-purple-800">
                                <i class="fas fa-layer-group text-purple-600"></i>
                                <span><strong>Plan Quota:</strong> {{ $totalRouters }} of {{ $mikrotikQuota > 0 ? $mikrotikQuota : 'Unlimited' }} routers used ({{ $plan->name ?? 'Current Plan' }})</span>
                            </div>
                            <span class="text-[10.5px] font-semibold text-purple-700 bg-white px-2 py-0.5 rounded border border-purple-200">
                                {{ $quotaRemaining }} remaining
                            </span>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Router Name -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Router Name / Label <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="radiusModal.name" 
                                   required 
                                   placeholder="e.g. Core-BNG-CCR2004"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- Router IP -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Router IP Address <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="ip_address" 
                                   x-model="radiusModal.ip_address" 
                                   required 
                                   placeholder="e.g. 103.59.177.139"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Model -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Hardware Model (Optional)
                            </label>
                            <input type="text" 
                                   name="model" 
                                   x-model="radiusModal.model" 
                                   placeholder="e.g. CCR2004"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- RADIUS Secret -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                RADIUS Secret <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="radius_secret" 
                                   x-model="radiusModal.radius_secret" 
                                   required 
                                   placeholder="radius@123"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- CoA Port -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                CoA Disconnect Port
                            </label>
                            <input type="number" 
                                   name="coa_port" 
                                   x-model="radiusModal.coa_port" 
                                   placeholder="3799"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>
                    </div>

                    <!-- Live CLI Terminal Script Snippet -->
                    <div class="rounded-lg overflow-hidden border border-slate-800 bg-slate-900 mt-2">
                        <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-terminal text-cyan-400"></i>
                                <span>MikroTik Setup Script</span>
                            </span>
                            <button type="button" 
                                    @click="copyToClipboard(getRadiusModalScript(), 'Setup Script')" 
                                    class="text-cyan-400 hover:text-cyan-300 font-sans font-medium flex items-center gap-1 transition cursor-pointer">
                                <i class="fas fa-copy text-[10px]"></i>
                                <span>Copy Script</span>
                            </button>
                        </div>
                        <pre class="p-3 text-cyan-300 font-mono text-[10.5px] overflow-x-auto leading-relaxed selection:bg-cyan-900 selection:text-white" x-text="getRadiusModalScript()"></pre>
                    </div>

                    <!-- Status Toggle -->
                    <div class="pt-1 flex items-center justify-between">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="radiusModal.is_active" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                            <span class="text-xs font-medium text-slate-700">Enable this router gateway</span>
                        </label>
                        <span class="text-[10.5px] text-slate-400 font-mono">Server IP: {{ $serverIp }}</span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" @click="radiusModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="!radiusModal.isEdit && {{ $isQuotaReached ? 'true' : 'false' }}"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-check text-[10px]"></i>
                        <span x-text="radiusModal.isEdit ? 'Save Changes' : 'Save & Sync RADIUS'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: ROUTEROS API GATEWAY MODAL (Add & Edit) -->
    <div x-show="apiModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="apiModal.open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <form :action="apiModal.isEdit ? `/admin/network/mikrotik/${apiModal.id}` : '{{ route('tenant.network.mikrotik.store') }}'" method="POST" class="space-y-0">
                @csrf
                <template x-if="apiModal.isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="connection_type" value="api">

                <!-- Modal Header -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-code"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800" x-text="apiModal.isEdit ? 'Edit RouterOS API Gateway' : 'Add RouterOS API Gateway'"></h3>
                            <p class="text-[10.5px] text-slate-500 font-normal">সরাসরি RouterOS সকেট (Port 8728) সংযোগ ও লাইভ টেলিমেট্রি</p>
                        </div>
                    </div>
                    <button type="button" @click="apiModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Router Name -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Router Name / Label <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="apiModal.name" 
                                   required 
                                   placeholder="e.g. Core-BNG-CCR2004"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- Router IP -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Router IP Address <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="ip_address" 
                                   x-model="apiModal.ip_address" 
                                   required 
                                   placeholder="e.g. 103.59.177.139"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <!-- Model -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Model
                            </label>
                            <input type="text" 
                                   name="model" 
                                   x-model="apiModal.model" 
                                   placeholder="CCR2004"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- API Port -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                API Port <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   name="api_port" 
                                   x-model="apiModal.api_port" 
                                   required 
                                   placeholder="8728"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Username <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="username" 
                                   x-model="apiModal.username" 
                                   required 
                                   placeholder="admin"
                                   class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Password <span class="text-rose-500" x-show="!apiModal.isEdit">*</span>
                            </label>
                            <div class="relative">
                                <input :type="apiModal.showPassword ? 'text' : 'password'" 
                                       name="password" 
                                       x-model="apiModal.password" 
                                       :required="!apiModal.isEdit"
                                       :placeholder="apiModal.isEdit ? '••••••••' : 'Password'"
                                       class="w-full pl-2.5 pr-7 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                <button type="button" 
                                        @click="apiModal.showPassword = !apiModal.showPassword" 
                                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fas text-[9px]" :class="apiModal.showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SSL & Live Test Bar -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border-t border-slate-100">
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="use_ssl" value="1" x-model="apiModal.use_ssl" class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                                <span class="text-xs font-medium text-slate-700">SSL (Port 8729)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="apiModal.is_active" class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                                <span class="text-xs font-medium text-slate-700">Enable Gateway</span>
                            </label>
                        </div>

                        <button type="button" 
                                @click="testApiModalSocket()" 
                                :disabled="apiModal.testingSocket"
                                class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                            <i class="fas fa-spinner fa-spin text-cyan-600" x-show="apiModal.testingSocket"></i>
                            <i class="fas fa-bolt text-emerald-600" x-show="!apiModal.testingSocket"></i>
                            <span x-text="apiModal.testingSocket ? 'Testing...' : 'Test Socket Connection'"></span>
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" @click="apiModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check text-[10px]"></i>
                        <span x-text="apiModal.isEdit ? 'Update API Gateway' : 'Save API Gateway'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: LIVE SOCKET PROBE DIAGNOSTIC MODAL -->
    <div x-show="probeModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="probeModal.open = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">RouterOS Socket Diagnostic</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Target: ${probeModal.routerName}`"></p>
                    </div>
                </div>
                <button type="button" @click="probeModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3 text-xs">
                <!-- Loading State -->
                <div x-show="probeModal.loading" class="py-6 text-center space-y-2.5">
                    <div class="w-8 h-8 border-2 border-cyan-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-slate-700 font-medium text-xs">Testing Socket API Connection...</p>
                    <p class="text-slate-400 text-[10.5px]">Probing TCP handshake &amp; RouterOS credentials</p>
                </div>

                <!-- Results State -->
                <div x-show="!probeModal.loading && probeModal.result" class="space-y-3">
                    <!-- Status Banner -->
                    <div class="p-3 rounded-lg border flex items-center gap-2.5"
                         :class="probeModal.result?.success ? 'bg-emerald-50/80 text-emerald-900 border-emerald-200/80' : 'bg-rose-50/80 text-rose-900 border-rose-200/80'">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] flex-shrink-0"
                             :class="probeModal.result?.success ? 'bg-emerald-500' : 'bg-rose-500'">
                            <i class="fas" :class="probeModal.result?.success ? 'fa-check' : 'fa-xmark'"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="font-semibold text-xs block leading-tight truncate" x-text="probeModal.result?.message"></span>
                            <span class="text-[10.5px] opacity-80" x-text="`Latency: ${probeModal.result?.latency_ms || 0} ms`"></span>
                        </div>
                    </div>

                    <!-- Telemetry Details Grid -->
                    <div x-show="probeModal.result?.success" class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 font-mono">
                            <span class="text-[10px] text-slate-400 uppercase block font-sans font-medium">Identity</span>
                            <span class="font-semibold text-slate-800 text-xs truncate block" x-text="probeModal.result?.identity || '--'"></span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 font-mono">
                            <span class="text-[10px] text-slate-400 uppercase block font-sans font-medium">RouterOS</span>
                            <span class="font-semibold text-slate-800 text-xs truncate block" x-text="probeModal.result?.ros_version || '--'"></span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 font-mono">
                            <span class="text-[10px] text-slate-400 uppercase block font-sans font-medium">CPU Load</span>
                            <span class="font-semibold text-slate-800 text-xs truncate block" x-text="`${probeModal.result?.cpu_load || 0}%`"></span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 font-mono">
                            <span class="text-[10px] text-slate-400 uppercase block font-sans font-medium">Uptime</span>
                            <span class="font-semibold text-slate-800 text-xs truncate block" x-text="probeModal.result?.uptime || '--'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="testRouterConnection(probeModal.routerId)"
                        class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-medium text-xs border border-slate-200 shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-arrows-rotate text-[10px] text-slate-500"></i>
                    <span>Re-test</span>
                </button>
                <button type="button" @click="probeModal.open = false" class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-medium text-xs shadow-xs transition cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: MIKROTIK TERMINAL SCRIPT MODAL (FreeRADIUS AAA Setup) -->
    <div x-show="showScriptModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="showScriptModal = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">FreeRADIUS Terminal Script</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Router: ${activeRouterName}`"></p>
                    </div>
                </div>
                <button type="button" @click="showScriptModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-2.5 text-xs">
                <p class="text-slate-600 text-[11px] leading-relaxed">
                    Paste this script into your MikroTik Winbox/SSH terminal (<strong>New Terminal</strong>) to configure the FreeRADIUS AAA client:
                </p>

                <!-- Code Container with Copy Action -->
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
                    <pre class="p-3 text-cyan-300 font-mono text-[11px] overflow-x-auto leading-relaxed selection:bg-cyan-900 selection:text-white" x-text="activeScript"></pre>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="copyToClipboard(activeScript, 'RADIUS Script')" 
                        class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-copy text-[10px]"></i>
                    <span>Copy Script</span>
                </button>

                <button type="button" @click="showScriptModal = false" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-medium text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- 3. Page Specific Scripts --}}
@push('scripts')
<script>
function mikrotikManager() {
    return {
        activeMenuRouter: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        selectedRouterData: null,
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

        // FreeRADIUS Modal State
        radiusModal: {
            open: false,
            isEdit: false,
            id: null,
            name: '',
            ip_address: '',
            model: '',
            radius_secret: 'radius@123',
            coa_port: 3799,
            is_active: true
        },

        // RouterOS API Modal State
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
            this.radiusModal = {
                open: true,
                isEdit: false,
                id: null,
                name: '',
                ip_address: '',
                model: '',
                radius_secret: 'radius@123',
                coa_port: 3799,
                is_active: true
            };
        },

        openEditRadiusModal(router) {
            this.radiusModal = {
                open: true,
                isEdit: true,
                id: router.id,
                name: router.name || '',
                ip_address: router.ip_address || '',
                model: router.model || '',
                radius_secret: router.radius_secret || 'radius@123',
                coa_port: router.coa_port || 3799,
                is_active: !!router.is_active
            };
        },

        openApiModal() {
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

            return `# 1. FreeRADIUS Server যুক্ত করুন\n/radius add service=ppp,hotspot,login address=${sIp} secret="${sec}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS"\n\n# 2. Incoming CoA সক্রিয় করুন\n/radius incoming set accept=yes port=${coa}\n\n# 3. PPPoE AAA চালু করুন\n/ppp aaa set use-radius=yes accounting=yes interim-update=5m`;
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
                        confirmButtonColor: data.success ? '#0891b2' : '#e11d48',
                        customClass: { popup: 'rounded-2xl shadow-xl border border-slate-200 p-5' }
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

        toggleMenu(routerOrId, triggerElOrEvent) {
            const router = (typeof routerOrId === 'object' && routerOrId !== null)
                ? routerOrId 
                : this.routers.find(r => r.id == routerOrId);

            if (!router) {
                console.warn('Router not found for:', routerOrId);
                return;
            }

            if (this.activeMenuRouter && this.activeMenuRouter.id === router.id) {
                this.activeMenuRouter = null;
                return;
            }

            let targetEl = null;
            if (triggerElOrEvent instanceof HTMLElement) {
                targetEl = triggerElOrEvent;
            } else if (triggerElOrEvent && triggerElOrEvent.currentTarget instanceof HTMLElement) {
                targetEl = triggerElOrEvent.currentTarget;
            } else if (triggerElOrEvent && triggerElOrEvent.target) {
                targetEl = triggerElOrEvent.target.closest('button');
            }
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
            this.activeMenuRouter = router;
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

        syncFromMenu() {
            if (!this.activeMenuRouter) return;
            const routerId = this.activeMenuRouter.id;
            this.activeMenuRouter = null;
            this.syncRouterForm(routerId);
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

        openGlobalScriptModal() {
            this.activeRouterName = 'All Routers';
            this.activeScript = this.getMikrotikScript(this.serverIp, 'radius@123');
            this.showScriptModal = true;
        },

        openScriptModal(routerId) {
            const router = this.routers.find(r => r.id == routerId);
            if (router) {
                this.activeRouterName = router.name;
                this.activeScript = this.getMikrotikScript(router.ip_address, router.radius_secret);
            } else {
                this.activeRouterName = 'Router';
                this.activeScript = this.getMikrotikScript(this.serverIp, 'radius@123');
            }
            this.showScriptModal = true;
        },

        async testRouterConnection(routerId) {
            if (!routerId) return;
            const router = this.routers.find(r => r.id == routerId);
            this.probeModal.routerId = routerId;
            this.probeModal.routerName = router ? router.name : 'MikroTik Router';
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

        syncRouterForm(routerId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/network/mikrotik/${routerId}/sync`;
            form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
            document.body.appendChild(form);
            form.submit();
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
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete Gateway',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submitDeleteForm(routerId);
                    }
                });
            } else if (confirm(`Are you sure you want to delete router '${routerName}'? Associated RADIUS NAS clients and telemetry links will be detached.`)) {
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

        getMikrotikScript(ip, secret) {
            const sIp = this.serverIp || '103.59.177.136';
            const sec = secret || 'radius@123';
            return `/radius add service=ppp,hotspot,login address=${sIp} secret="${sec}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS"\n/radius incoming set accept=yes port=3799\n/ppp aaa set use-radius=yes accounting=yes interim-update=5m`;
        }
    };
}
</script>
@endpush
