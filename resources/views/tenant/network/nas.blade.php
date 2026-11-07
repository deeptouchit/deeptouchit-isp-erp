@extends('tenant.layouts.app')

@section('title', 'NAS / RADIUS Gateways - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $routersJson = $tenantRouters->map(function($r) {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'ip' => $r->ip_address,
            'radius_secret' => $r->decrypted_radius_secret ?? '',
        ];
    })->values();

    $nasCollection = $nasList->getCollection()->map(function($n) {
        return [
            'id' => $n->id,
            'shortname' => $n->shortname,
            'nasname' => $n->nasname,
            'type' => $n->type,
            'ports' => $n->ports ?: 1812,
            'coa_port' => $n->coa_port ?: 3799,
            'server' => $n->server ?? '',
            'community' => $n->community ?? '',
            'router_id' => $n->router_id ?? '',
            'router_name' => $n->router ? $n->router->name : '',
            'router_ip' => $n->router ? $n->router->ip_address : '',
            'is_active' => (bool)$n->is_active,
            'secret' => $n->decrypted_secret ?: '',
            'description' => $n->description ?? '',
        ];
    })->values();

    $isMoreOpen = request()->filled('active');
    $activeMoreCount = request()->filled('active') ? 1 : 0;
@endphp

<div class="space-y-3" 
     x-data="nasManager()"
     @scroll.window="activeMenuNas = null" 
     @resize.window="activeMenuNas = null">

    <!-- Toast Notification -->
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

    <!-- 1. Top Header Bar (Strictly Icon + Title + Actions ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">NAS &amp; RADIUS Clients</h1>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-full font-mono">{{ number_format($nasList->total()) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" 
                    @click="showConfigModal = true"
                    class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
                    title="View FreeRADIUS clients.conf">
                <i class="fas fa-file-code text-violet-600 text-[11px]"></i>
                <span>clients.conf</span>
            </button>

            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="MikroTik Routers">
                <i class="fas fa-server text-cyan-600 text-[11px]"></i>
                <span>Routers</span>
            </a>

            <!-- Dedicated Add Page -->
            <a href="{{ route('tenant.network.nas.create') }}"
               class="h-[34px] px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>+ Add NAS Gateway</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Gateways -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Gateways</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-violet-50 text-violet-600 border-violet-100 flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Active Links -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Active Links</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-600 truncate">{{ number_format($activeNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Card 3: Disabled -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Disabled</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-500 truncate">{{ number_format($disabledNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-100 text-slate-500 border-slate-200 flex items-center justify-center">
                <i class="fas fa-ban"></i>
            </div>
        </div>

        <!-- Card 4: RADIUS Port -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Auth Port</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">1812/UDP</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-key"></i>
            </div>
        </div>

        <!-- Card 5: CoA / PoD Port -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">CoA / PoD Port</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">3799/UDP</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <!-- Card 6: Fleet Linked -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Fleet Linked</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">{{ $linkedRoutersCount }}/{{ $tenantRouters->count() }}</span>
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
        <form action="{{ route('tenant.network.nas') }}" method="GET">
            <!-- Row 1: Primary Filters -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Input with 1-Click Clear -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search identifier, host IP, description..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Linked MikroTik Router Filter -->
                <div class="w-full md:w-52 flex-shrink-0">
                    <select name="router_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Fleet Routers</option>
                        @foreach($tenantRouters as $r)
                            <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Vendor / Platform Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Platforms</option>
                        <option value="mikrotik" {{ request('type') === 'mikrotik' ? 'selected' : '' }}>MikroTik RouterOS</option>
                        <option value="cisco" {{ request('type') === 'cisco' ? 'selected' : '' }}>Cisco IOS</option>
                        <option value="juniper" {{ request('type') === 'juniper' ? 'selected' : '' }}>Juniper JunOS</option>
                        <option value="huawei" {{ request('type') === 'huawei' ? 'selected' : '' }}>Huawei VRP</option>
                        <option value="accel-ppp" {{ request('type') === 'accel-ppp' ? 'selected' : '' }}>Accel-PPP BRAS</option>
                        <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Generic RADIUS</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-28 flex-shrink-0">
                    @php $perPageVal = (int)request('per_page', 20); @endphp
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="10" {{ $perPageVal === 10 ? 'selected' : '' }}>10 / page</option>
                        <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ $perPageVal === 250 ? 'selected' : '' }}>250 / page</option>
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

                    <a href="{{ route('tenant.network.nas') }}" 
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
                
                <!-- Status Filter -->
                <div class="w-full sm:w-48 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Gateway State</label>
                    <select name="active" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All States</option>
                        <option value="1" {{ request('active') === '1' || request('active') === 'active' ? 'selected' : '' }}>Active Gateways Only</option>
                        <option value="0" {{ request('active') === '0' || request('active') === 'disabled' ? 'selected' : '' }}>Disabled Gateways Only</option>
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
                        <th class="text-center font-semibold">Shortname / Identifier</th>
                        <th class="text-center font-semibold">NAS IP &amp; Ports</th>
                        <th class="text-center font-semibold">Platform</th>
                        <th class="text-center font-semibold">Linked Fleet Router</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nasList as $index => $nas)
                        @php
                            $vendorBadge = match($nas->type) {
                                'mikrotik' => ['class' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'icon' => 'fa-server', 'label' => 'MikroTik'],
                                'cisco' => ['class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fa-network-wired', 'label' => 'Cisco IOS'],
                                'juniper' => ['class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-diagram-project', 'label' => 'Juniper'],
                                'huawei' => ['class' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'fa-hdd', 'label' => 'Huawei'],
                                'accel-ppp' => ['class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-bolt', 'label' => 'Accel-PPP'],
                                default => ['class' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'fa-terminal', 'label' => 'Generic'],
                            };
                            $decryptedSecret = $nas->decrypted_secret ?: 'radius@123';
                        @endphp
                        <tr class="{{ !$nas->is_active ? 'opacity-70' : '' }}">
                            
                            <!-- 1. Serial No -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $nasList->firstItem() + $index }}
                            </td>

                            <!-- 2. Shortname / Identifier (Click to View Details) -->
                            <td class="text-left font-bold">
                                <button type="button" 
                                        @click="openDetailsModal({{ $nas->id }})" 
                                        class="text-slate-800 hover:text-cyan-700 hover:underline cursor-pointer">
                                    {{ $nas->shortname }}
                                </button>
                            </td>

                            <!-- 3. NAS IP & Ports -->
                            <td class="text-center font-mono">
                                <div class="inline-flex items-center gap-1.5 flex-nowrap">
                                    <span class="font-semibold text-slate-800">{{ $nas->nasname }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] bg-slate-100 text-slate-600 border border-slate-200" title="Auth: {{ $nas->ports ?: 1812 }} | CoA: {{ $nas->coa_port ?: 3799 }}">
                                        :{{ $nas->ports ?: 1812 }}
                                    </span>
                                </div>
                            </td>

                            <!-- 4. Platform Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $vendorBadge['class'] }}">
                                    <i class="fas {{ $vendorBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $vendorBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 5. Linked Fleet Router -->
                            <td class="text-left font-medium text-slate-700">
                                @if($nas->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $nas->router_id) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1 font-semibold">
                                        <i class="fas fa-network-wired text-[10px] text-indigo-400"></i>
                                        <span>{{ $nas->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-normal">--</span>
                                @endif
                            </td>

                            <!-- 6. Status -->
                            <td class="text-center">
                                @if($nas->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode([
                                            'id' => $nas->id,
                                            'shortname' => $nas->shortname,
                                            'nasname' => $nas->nasname,
                                            'type' => $nas->type,
                                            'ports' => $nas->ports ?: 1812,
                                            'coa_port' => $nas->coa_port ?: 3799,
                                            'server' => $nas->server ?? '',
                                            'community' => $nas->community ?? '',
                                            'router_id' => $nas->router_id ?? '',
                                            'router_name' => $nas->router ? $nas->router->name : '',
                                            'router_ip' => $nas->router ? $nas->router->ip_address : '',
                                            'is_active' => (bool)$nas->is_active,
                                            'secret' => $decryptedSecret,
                                            'description' => $nas->description ?? '',
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
                                        <i class="fas fa-shield-alt text-base"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700">No FreeRADIUS Gateways Found</span>
                                    <p class="text-[11px] text-slate-400 max-w-sm">No NAS clients match your filter criteria or no gateways have been configured yet.</p>
                                    <a href="{{ route('tenant.network.nas.create') }}" 
                                       class="mt-2 h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                        <i class="fas fa-plus text-xs"></i>
                                        <span>Register NAS Gateway</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($nasList->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    Showing <span class="font-semibold text-slate-700">{{ $nasList->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $nasList->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $nasList->total() }}</span> gateways
                </div>
                <div>
                    {{ $nasList->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenuNas !== null" 
         x-cloak
         @click.away="activeMenuNas = null"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 space-y-0.5">
        
        <!-- Group 1: Diagnostics & Telemetry -->
        <button type="button" 
                @click="openDetailsModal(activeMenuNas?.id)" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-circle-info w-4 text-cyan-600 text-[11px]"></i>
            <span>View Details</span>
        </button>

        <button type="button" 
                @click="testCoaFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-bolt w-4 text-purple-600 text-[11px]"></i>
            <span>Test CoA Socket</span>
        </button>

        <button type="button" 
                @click="copyToClipboard(activeMenuNas?.secret || '', 'Shared Secret')" 
                class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-key w-4 text-amber-500 text-[11px]"></i>
            <span>Copy Shared Secret</span>
        </button>

        <button type="button" 
                @click="copyToClipboard(activeMenuNas?.nasname || '', 'NAS IP Address')" 
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
               :class="activeMenuNas?.is_active ? 'fa-toggle-on text-emerald-600' : 'fa-toggle-off text-slate-400'"></i>
            <span x-text="activeMenuNas?.is_active ? 'Disable Gateway' : 'Enable Gateway'"></span>
        </button>

        <a :href="'/admin/network/nas/' + activeMenuNas?.id + '/edit'" 
           class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-pen-to-square w-4 text-amber-500 text-[11px]"></i>
            <span>Edit Gateway</span>
        </a>

        <button type="button" 
                @click="deleteFromMenu()" 
                class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Gateway</span>
        </button>
    </div>

    <!-- MODAL 1: VIEW NAS DETAILS MODAL (Rule 3) -->
    <div x-show="showDetailsModal" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showDetailsModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="viewNas?.shortname || 'NAS Gateway Details'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Host: ${viewNas?.nasname || ''}`"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto" x-show="viewNas">
                <!-- Status & Architecture 3 Metric Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Gateway State</span>
                        <span class="text-sm font-bold block mt-0.5" 
                              :class="viewNas?.is_active ? 'text-emerald-600' : 'text-slate-500'" 
                              x-text="viewNas?.is_active ? 'ACTIVE' : 'DISABLED'">
                        </span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Auth Port</span>
                        <span class="text-sm font-mono font-bold text-blue-700 block mt-0.5" x-text="viewNas?.ports || 1812"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">CoA Port</span>
                        <span class="text-sm font-mono font-bold text-purple-700 block mt-0.5" x-text="viewNas?.coa_port || 3799"></span>
                    </div>
                </div>

                <!-- Network Properties Grid -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11.5px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">FreeRADIUS Client Properties</span>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">NAS IP Address:</span>
                            <span class="font-mono font-bold text-slate-800" x-text="viewNas?.nasname"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Platform Type:</span>
                            <span class="font-semibold text-slate-800 uppercase" x-text="viewNas?.type"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">RADIUS Shared Secret:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="viewNas?.secret || '••••••••'"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="viewNas?.router_name">
                            <span class="text-slate-500">Linked Fleet Router:</span>
                            <span class="font-semibold text-indigo-700" x-text="viewNas?.router_name"></span>
                        </div>
                    </div>
                </div>

                <template x-if="viewNas?.description">
                    <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200 text-[11px] text-amber-900">
                        <span class="font-bold block mb-0.5">Notes / Description:</span>
                        <span x-text="viewNas?.description"></span>
                    </div>
                </template>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-2">
                    <button type="button" 
                            @click="triggerCoaTest(viewNas?.id)" 
                            class="h-[34px] px-3 bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-bolt text-xs"></i>
                        <span>Test CoA Socket</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <a :href="'/admin/network/nas/' + viewNas?.id + '/edit'" 
                           class="h-[34px] px-3.5 bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-pen-to-square text-xs"></i>
                            <span>Edit Gateway</span>
                        </a>

                        <button type="button" 
                                @click="showDetailsModal = false" 
                                class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: FREERADIUS CLIENTS.CONF PREVIEW MODAL (Rule 3) -->
    <div x-show="showConfigModal" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showConfigModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-code"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">FreeRADIUS clients.conf Preview</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Active client configurations synchronized to the RADIUS engine.</p>
                    </div>
                </div>
                <button type="button" @click="showConfigModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-2.5 text-xs">
                <div class="rounded-lg overflow-hidden border border-slate-800 bg-slate-900">
                    <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>/etc/freeradius/3.0/clients.conf</span>
                        <button type="button" 
                                @click="copyToClipboard(configSnippet, 'clients.conf')" 
                                class="text-cyan-400 hover:text-cyan-300 font-sans font-medium flex items-center gap-1 transition cursor-pointer">
                            <i class="fas fa-copy text-[10px]"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                    <pre class="p-3 text-cyan-300 font-mono text-[11px] overflow-x-auto leading-relaxed max-h-60 selection:bg-cyan-900" x-text="configSnippet"></pre>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('tenant.network.nas.config') }}" 
                   class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-download text-[10px]"></i>
                    <span>Download File</span>
                </a>

                <button type="button" @click="showConfigModal = false" class="h-[34px] px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function nasManager() {
    return {
        activeMenuNas: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        showConfigModal: false,
        showDetailsModal: false,
        viewNas: null,
        configSnippet: @json($freeradiusSnippet),
        nasList: @json($nasCollection),

        openDetailsModal(nasId) {
            this.activeMenuNas = null;
            const nas = this.nasList.find(n => n.id == nasId);
            if (nas) {
                this.viewNas = nas;
                this.showDetailsModal = true;
            }
        },

        toggleMenu(nas, event) {
            if (this.activeMenuNas?.id === nas.id) {
                this.activeMenuNas = null;
                return;
            }
            this.activeMenuNas = nas;
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

        testCoaFromMenu() {
            if (!this.activeMenuNas) return;
            const nasId = this.activeMenuNas.id;
            this.activeMenuNas = null;
            this.triggerCoaTest(nasId);
        },

        async triggerCoaTest(nasId) {
            try {
                this.showToast('Sending CoA / PoD packet test...', 'info');
                const res = await fetch(`/admin/network/nas/${nasId}/test-coa`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'CoA Test Completed',
                        text: data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'CoA Socket Response',
                        text: data.message || 'No ACK received from NAS on port 3799'
                    });
                }
            } catch (e) {
                this.showToast('Network error testing CoA socket.', 'error');
            }
        },

        toggleStatusFromMenu() {
            if (!this.activeMenuNas) return;
            const nasId = this.activeMenuNas.id;
            this.activeMenuNas = null;
            this.toggleNasStatus(nasId);
        },

        async toggleNasStatus(nasId) {
            try {
                const res = await fetch(`/admin/network/nas/${nasId}/toggle-status`, {
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
                    this.showToast('Failed to update NAS status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while updating NAS status.', 'error');
            }
        },

        deleteFromMenu() {
            if (!this.activeMenuNas) return;
            const nasId = this.activeMenuNas.id;
            const nasName = this.activeMenuNas.shortname;
            this.activeMenuNas = null;
            this.confirmDeleteNas(nasId, nasName);
        },

        confirmDeleteNas(nasId, nasName) {
            this.activeMenuNas = null;
            Swal.fire({
                title: 'Delete NAS Gateway?',
                html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete NAS client <strong class="text-slate-900 font-semibold">${nasName}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">Authentication requests from this IP will be rejected by FreeRADIUS.</span></div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete Gateway',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/network/nas/${nasId}`;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
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
            this.activeMenuNas = null;
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
        }
    };
}

// Trigger SweetAlert2 for server-side flash sessions
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: "{{ session('success') }}",
            timer: 2500,
            showConfirmButton: false
        });
    @endif
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: "{{ session('error') }}",
            confirmButtonColor: '#e11d48'
        });
    @endif
});
</script>
@endpush
