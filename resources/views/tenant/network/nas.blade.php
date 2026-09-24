@extends('tenant.layouts.app')

@section('title', 'NAS / Network Access Servers - ' . ($tenant->company_name ?? $tenant->name))

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
@endphp

<div class="space-y-3" 
     x-data="nasManager()"
     @scroll.window="activeMenuNas = null" 
     @resize.window="activeMenuNas = null">

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

    <!-- 1. Top Compact Header Bar (Strictly Icon + Title + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">NAS / Network Access Servers</h2>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <!-- FreeRADIUS clients.conf Modal Trigger -->
            <button type="button" 
                    @click="showConfigModal = true"
                    class="px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-code text-violet-600 text-[11px]"></i>
                <span>clients.conf</span>
            </button>

            <!-- Add NAS Gateway Button -->
            <button type="button" 
                    @click="openAddModal()"
                    class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add NAS Gateway</span>
            </button>
        </div>
    </div>

    <!-- 2. Ultra-Compact KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Gateways -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Gateways</span>
                <span class="text-[13px] font-bold text-slate-800 tracking-tight font-mono leading-tight block">{{ number_format($totalNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-violet-50 text-violet-600 flex items-center justify-center text-[10px] border border-violet-100 flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Metric 2: Active Links -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Active Links</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">{{ number_format($activeNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Metric 3: Disabled -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Disabled</span>
                <span class="text-[13px] font-bold text-slate-500 tracking-tight font-mono leading-tight block">{{ number_format($disabledNas) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-ban"></i>
            </div>
        </div>

        <!-- Metric 4: RADIUS Port -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">RADIUS Port</span>
                <span class="text-[13px] font-bold text-blue-700 tracking-tight font-mono leading-tight block">1812/UDP</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
        </div>

        <!-- Metric 5: CoA / PoD Port -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">CoA / PoD Port</span>
                <span class="text-[13px] font-bold text-purple-700 tracking-tight font-mono leading-tight block">3799/UDP</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <!-- Metric 6: Fleet Linked -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Fleet Linked</span>
                <span class="text-[13px] font-bold text-indigo-700 tracking-tight font-mono leading-tight block">{{ $linkedRoutersCount }}/{{ $tenantRouters->count() }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.nas') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2.5">
            
            <!-- Search Keyword (col-span-2) -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search identifier, host IP, notes..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- Linked MikroTik Router Filter -->
            <div>
                <select name="router_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Fleet Routers</option>
                    @foreach($tenantRouters as $r)
                        <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->ip_address }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Vendor / Platform Filter -->
            <div>
                <select name="type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Types</option>
                    <option value="mikrotik" {{ request('type') === 'mikrotik' ? 'selected' : '' }}>MikroTik RouterOS</option>
                    <option value="cisco" {{ request('type') === 'cisco' ? 'selected' : '' }}>Cisco IOS</option>
                    <option value="juniper" {{ request('type') === 'juniper' ? 'selected' : '' }}>Juniper JunOS</option>
                    <option value="huawei" {{ request('type') === 'huawei' ? 'selected' : '' }}>Huawei VRP</option>
                    <option value="accel-ppp" {{ request('type') === 'accel-ppp' ? 'selected' : '' }}>Accel-PPP BRAS</option>
                    <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Generic RADIUS</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="active" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All States</option>
                    <option value="1" {{ request('active') === '1' || request('active') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="0" {{ request('active') === '0' || request('active') === 'disabled' ? 'selected' : '' }}>Disabled Only</option>
                </select>
            </div>

            <!-- Per Page -->
            <div>
                @php $perPageVal = (int)request('per_page', 20); @endphp
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="10" {{ $perPageVal === 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
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
                <a href="{{ route('tenant.network.nas') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strictly .saas-table Standard) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>FreeRADIUS 3.0 NAS Gateway Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $nasList->total() }} Gateways
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Shortname / Identifier</th>
                        <th>NAS IP / Host</th>
                        <th>Platform</th>
                        <th class="text-center">Auth Port</th>
                        <th class="text-center">CoA Port</th>
                        <th>Linked Fleet Router</th>
                        <th>Shared Secret</th>
                        <th class="text-center">Status</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
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
                            
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $nasList->firstItem() + $index }}
                            </td>

                            <!-- 2. Shortname / Identifier -->
                            <td class="text-cyan-800">
                                <button type="button" 
                                        @click="openDetailsModal({{ $nas->id }})" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $nas->shortname }}
                                </button>
                            </td>

                            <!-- 3. NAS IP / Host -->
                            <td class="font-mono text-slate-800">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $nas->nasname }}</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $nas->nasname }}', 'NAS IP')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy IP">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 4. Platform -->
                            <td>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] border {{ $vendorBadge['class'] }}">
                                    <i class="fas {{ $vendorBadge['icon'] }} text-[8px]"></i>
                                    <span>{{ $vendorBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 5. Auth Port -->
                            <td class="text-center font-mono text-slate-700">
                                {{ $nas->ports ?: 1812 }}
                            </td>

                            <!-- 6. CoA Port -->
                            <td class="text-center font-mono text-purple-700">
                                {{ $nas->coa_port ?: 3799 }}
                            </td>

                            <!-- 7. Linked Fleet Router -->
                            <td class="text-slate-700">
                                @if($nas->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $nas->router_id) }}" 
                                       class="text-cyan-700 hover:underline inline-flex items-center gap-1">
                                        <i class="fas fa-network-wired text-[9px] text-slate-400"></i>
                                        <span>{{ $nas->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 8. Shared Secret -->
                            <td class="font-mono text-slate-600">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="text-slate-500">••••••••</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $decryptedSecret }}', 'Shared Secret')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy Secret">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 9. Status -->
                            <td class="text-center">
                                @if($nas->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Actions (3-Dot Action Button triggering Floating Dropdown) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from([
                                             'id' => $nas->id,
                                             'shortname' => $nas->shortname,
                                             'nasname' => $nas->nasname,
                                             'is_active' => (bool)$nas->is_active,
                                             'secret' => $decryptedSecret,
                                        ]) }}, $event)" 
                                        class="w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 mx-auto flex items-center justify-center text-base border border-violet-100">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No NAS Gateways Registered</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Register your MikroTik, Cisco, or Juniper BRAS as RADIUS NAS clients to automate AAA authentication and session disconnect.</p>
                                    <div class="pt-1">
                                        <button type="button" 
                                                @click="openAddModal()" 
                                                class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Add NAS Gateway</span>
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
        @if($nasList->hasPages() || $nasList->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $nasList->firstItem() ?? 0 }} to {{ $nasList->lastItem() ?? 0 }} of {{ $nasList->total() }} results
                </div>
                <div>
                    {{ $nasList->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- GLOBAL FLOATING 3-DOT ACTION MENU (Unclipped & Anchored) -->
    <div x-show="activeMenuNas !== null"
         @click.away="activeMenuNas = null"
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
                    @click="detailsFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-circle-info text-emerald-600 w-3.5 text-center text-[10px]"></i>
                <span>View Details</span>
            </button>

            <!-- Test CoA -->
            <button type="button" 
                    @click="coaFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-bolt text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>Test CoA Socket</span>
            </button>

            <!-- Copy Secret -->
            <button type="button" 
                    @click="copySecretFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-key text-blue-600 w-3.5 text-center text-[10px]"></i>
                <span>Copy Shared Secret</span>
            </button>
        </div>

        <!-- Group 2: Configuration & Management -->
        <div class="py-0.5">
            <!-- Toggle Status -->
            <button type="button" 
                    @click="toggleFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-power-off text-indigo-600 w-3.5 text-center text-[10px]"></i>
                <span x-text="selectedNasData?.is_active ? 'Disable Gateway' : 'Enable Gateway'"></span>
            </button>

            <!-- Edit NAS -->
            <button type="button" 
                    @click="editFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-600 w-3.5 text-center text-[10px]"></i>
                <span>Edit Gateway</span>
            </button>

            <!-- Delete NAS -->
            <button type="button" 
                    @click="deleteFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-trash-can text-rose-600 w-3.5 text-center text-[10px]"></i>
                <span>Delete Gateway</span>
            </button>
        </div>
    </div>

    <!-- MODAL 1: FreeRADIUS clients.conf SNIPPET MODAL -->
    <div x-show="showConfigModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showConfigModal = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden">
            
            <!-- Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-code"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">FreeRADIUS clients.conf Configuration</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Automated NAS configuration block for all enabled gateways</p>
                    </div>
                </div>
                <button type="button" @click="showConfigModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <p class="text-slate-600 text-[11px]">
                    Append this block to your FreeRADIUS server at <code class="font-mono text-slate-800 bg-slate-100 px-1 py-0.5 rounded border border-slate-200">/etc/freeradius/3.0/clients.conf</code> and reload the daemon:
                </p>

                <div class="relative">
                    <pre class="p-3.5 rounded-lg bg-slate-900 text-cyan-300 font-mono text-[11px] overflow-x-auto max-h-72 leading-relaxed border border-slate-800 select-all">{{ $freeradiusSnippet }}</pre>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="copyToClipboard(`{{ addslashes($freeradiusSnippet) }}`, 'Configuration Snippet')" 
                        class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-medium text-xs border border-slate-200 shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-copy text-xs text-slate-500"></i>
                    <span>Copy to Clipboard</span>
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showConfigModal = false" class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        Close
                    </button>
                    <a href="{{ route('tenant.network.nas.config') }}" 
                       class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-download text-[10px]"></i>
                        <span>Download .conf</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: ADD NAS GATEWAY MODAL -->
    <div x-show="showAddModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showAddModal = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Add RADIUS NAS Gateway</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Register a new access server for AAA authentication &amp; CoA</p>
                    </div>
                </div>
                <button type="button" @click="showAddModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('tenant.network.nas.store') }}" class="p-4 space-y-3.5 text-xs">
                @csrf

                <!-- Quick Auto-fill from MikroTik Fleet -->
                @if($tenantRouters->isNotEmpty())
                    <div class="p-3 bg-cyan-50/70 border border-cyan-100 rounded-lg space-y-1">
                        <label class="block text-[11px] font-semibold text-cyan-900 flex items-center gap-1.5">
                            <i class="fas fa-magic text-cyan-600"></i>
                            <span>Quick Auto-Fill from MikroTik Fleet:</span>
                        </label>
                        <select x-model="addForm.router_id" 
                                name="router_id" 
                                @change="onRouterSelect($event.target.value)"
                                class="w-full text-xs py-1.5 px-2.5 bg-white border border-cyan-200 rounded-md focus:outline-none focus:border-cyan-500 font-normal">
                            <option value="">-- Or enter manual NAS parameters below --</option>
                            @foreach($tenantRouters as $router)
                                <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Shortname -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Shortname / Identifier <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="shortname" 
                               x-model="addForm.shortname" 
                               required 
                               placeholder="e.g. CORE-BRAS-01" 
                               class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                        <span class="text-[10px] text-slate-400">Unique identifier used in clients.conf</span>
                    </div>

                    <!-- IP Address / Hostname -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            NAS IP Address / Host <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="nasname" 
                               x-model="addForm.nasname" 
                               required 
                               placeholder="e.g. 10.70.0.3 or 192.168.88.1" 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400">Source IP that communicates with RADIUS</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Vendor Type -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Platform <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" 
                                x-model="addForm.type" 
                                required 
                                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                            <option value="mikrotik">MikroTik RouterOS</option>
                            <option value="cisco">Cisco IOS</option>
                            <option value="juniper">Juniper JunOS</option>
                            <option value="huawei">Huawei VRP</option>
                            <option value="accel-ppp">Accel-PPP BRAS</option>
                            <option value="other">Generic RFC RADIUS</option>
                        </select>
                    </div>

                    <!-- Auth Port -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Auth Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="ports" 
                               x-model="addForm.ports" 
                               min="1" 
                               max="65535" 
                               required 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400">Default: 1812</span>
                    </div>

                    <!-- CoA Port -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            CoA / PoD Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="coa_port" 
                               x-model="addForm.coa_port" 
                               min="1" 
                               max="65535" 
                               required 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400">Default: 3799</span>
                    </div>
                </div>

                <!-- Shared Secret -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">
                            RADIUS Shared Secret <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" 
                                @click="generateRandomSecret('add')"
                                class="text-[10px] text-cyan-700 hover:text-cyan-900 font-medium cursor-pointer flex items-center gap-1">
                            <i class="fas fa-key text-[9px]"></i>
                            <span>Generate Secret</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input :type="showAddSecret ? 'text' : 'password'" 
                               name="secret" 
                               x-model="addForm.secret" 
                               required 
                               placeholder="Enter or generate secret" 
                               class="w-full pl-3 pr-8 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                        <button type="button" 
                                @click="showAddSecret = !showAddSecret"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fas" :class="showAddSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Description / Notes -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Description / Location Notes
                    </label>
                    <input type="text" 
                           name="description" 
                           x-model="addForm.description" 
                           placeholder="e.g. Core Pop 01 - Aggregation Switch Uplink" 
                           class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                </div>

                <!-- Active Toggle Checkbox -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" 
                           id="add_is_active" 
                           name="is_active" 
                           value="1" 
                           checked 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <label for="add_is_active" class="text-xs text-slate-700 font-medium cursor-pointer">
                        Enable Immediately for FreeRADIUS Authentication
                    </label>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" 
                            @click="showAddModal = false" 
                            class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Save NAS Gateway</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: EDIT NAS GATEWAY MODAL -->
    <div x-show="showEditModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showEditModal = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit NAS Gateway</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Editing: ${editNas.shortname}`"></p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <form method="POST" :action="`/admin/network/nas/${editNas.id}`" class="p-4 space-y-3.5 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Shortname -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Shortname / Identifier <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="shortname" 
                               x-model="editNas.shortname" 
                               required 
                               class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                    </div>

                    <!-- IP Address / Hostname -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            NAS IP Address / Host <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="nasname" 
                               x-model="editNas.nasname" 
                               required 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Vendor Type -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Platform <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" 
                                x-model="editNas.type" 
                                required 
                                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                            <option value="mikrotik">MikroTik RouterOS</option>
                            <option value="cisco">Cisco IOS</option>
                            <option value="juniper">Juniper JunOS</option>
                            <option value="huawei">Huawei VRP</option>
                            <option value="accel-ppp">Accel-PPP BRAS</option>
                            <option value="other">Generic RFC RADIUS</option>
                        </select>
                    </div>

                    <!-- Auth Port -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Auth Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="ports" 
                               x-model="editNas.ports" 
                               min="1" 
                               max="65535" 
                               required 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                    </div>

                    <!-- CoA Port -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            CoA / PoD Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="coa_port" 
                               x-model="editNas.coa_port" 
                               min="1" 
                               max="65535" 
                               required 
                               class="w-full px-3 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                    </div>
                </div>

                <!-- Shared Secret (Optional change) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">
                            RADIUS Shared Secret (Leave blank to keep existing)
                        </label>
                        <button type="button" 
                                @click="generateRandomSecret('edit')"
                                class="text-[10px] text-cyan-700 hover:text-cyan-900 font-medium cursor-pointer flex items-center gap-1">
                            <i class="fas fa-key text-[9px]"></i>
                            <span>Generate New Secret</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input :type="showEditSecret ? 'text' : 'password'" 
                               name="secret" 
                               x-model="editNas.secret" 
                               placeholder="•••••••••••• (Unchanged)" 
                               class="w-full pl-3 pr-8 py-1.5 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition">
                        <button type="button" 
                                @click="showEditSecret = !showEditSecret"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fas" :class="showEditSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Link to Fleet Router -->
                @if($tenantRouters->isNotEmpty())
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Linked MikroTik Fleet Gateway
                        </label>
                        <select name="router_id" 
                                x-model="editNas.router_id" 
                                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                            <option value="">-- No Linked Fleet Router (Standalone) --</option>
                            @foreach($tenantRouters as $router)
                                <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Description / Notes -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Description / Location Notes
                    </label>
                    <input type="text" 
                           name="description" 
                           x-model="editNas.description" 
                           class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-cyan-500 focus:bg-white transition font-normal">
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" 
                           id="edit_is_active" 
                           name="is_active" 
                           value="1" 
                           :checked="editNas.is_active" 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <label for="edit_is_active" class="text-xs text-slate-700 font-medium cursor-pointer">
                        Active in FreeRADIUS Authentication
                    </label>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" 
                            @click="showEditModal = false" 
                            class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Update NAS Gateway</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: NAS DETAILS MODAL -->
    <div x-show="showDetailsModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showDetailsModal = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             x-show="viewNas">
            
            <!-- Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-server"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="viewNas ? viewNas.shortname : 'NAS Gateway'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-mono font-normal" x-text="viewNas ? viewNas.nasname : ''"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs" x-show="viewNas">
                <!-- Status & Platform Quick Grid -->
                <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Status</span>
                        <span class="font-semibold text-xs" :class="viewNas?.is_active ? 'text-emerald-600' : 'text-rose-600'" x-text="viewNas?.is_active ? 'Online' : 'Disabled'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Platform</span>
                        <span class="font-semibold text-xs text-slate-800 uppercase" x-text="viewNas?.type || '--'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase block font-medium">Auth Port</span>
                        <span class="font-semibold text-xs text-slate-800 font-mono" x-text="viewNas?.ports || 1812"></span>
                    </div>
                </div>

                <!-- Parameters list -->
                <div class="space-y-2 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">NAS IP Address:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="viewNas?.nasname"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/60">
                        <span class="text-slate-500">CoA / PoD Port:</span>
                        <span class="font-mono font-semibold text-purple-700" x-text="viewNas?.coa_port || 3799"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/60">
                        <span class="text-slate-500">Shared Secret:</span>
                        <div class="flex items-center gap-1.5 font-mono text-slate-700">
                            <span x-text="showViewSecret ? (viewNas?.secret || 'radius@123') : '••••••••••••'"></span>
                            <button type="button" @click="showViewSecret = !showViewSecret" class="text-slate-400 hover:text-slate-700 text-xs cursor-pointer">
                                <i class="fas" :class="showViewSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-200/60" x-show="viewNas?.router_name">
                        <span class="text-slate-500">Linked Fleet Router:</span>
                        <span class="text-cyan-700 font-medium" x-text="viewNas ? `${viewNas.router_name} (${viewNas.router_ip})` : ''"></span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80" x-show="viewNas?.description">
                    <span class="text-[10px] text-slate-400 uppercase block font-medium mb-0.5">Notes</span>
                    <p class="text-slate-700 text-xs" x-text="viewNas?.description"></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="testCoaSocket(viewNas?.id)" 
                        :disabled="testingCoaId === viewNas?.id"
                        class="px-2.5 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium text-xs border border-purple-200 transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas" :class="testingCoaId === viewNas?.id ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                    <span>Test CoA</span>
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="showDetailsModal = false; openEditModal(viewNas?.id)" 
                            class="px-3 py-1.5 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-cyan-700 border border-cyan-200 font-medium text-xs transition cursor-pointer">
                        Edit
                    </button>
                    <button type="button" @click="showDetailsModal = false" class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        Close
                    </button>
                </div>
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
        selectedNasData: null,
        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        showConfigModal: false,
        showAddModal: false,
        showEditModal: false,
        showDetailsModal: false,
        showAddSecret: false,
        showEditSecret: false,
        showViewSecret: false,
        viewNas: null,
        testingCoaId: null,
        routers: @json($routersJson),
        nasList: @json($nasCollection),

        addForm: {
            shortname: '',
            nasname: '',
            type: 'mikrotik',
            secret: '',
            ports: 1812,
            coa_port: 3799,
            server: '',
            community: '',
            router_id: '',
            is_active: true,
            description: ''
        },

        editNas: {
            id: null,
            shortname: '',
            nasname: '',
            type: 'mikrotik',
            ports: 1812,
            coa_port: 3799,
            server: '',
            community: '',
            router_id: '',
            is_active: true,
            description: '',
            secret: ''
        },

        toggleMenu(nas, event) {
            if (this.activeMenuNas?.id === nas.id) {
                this.activeMenuNas = null;
                return;
            }
            this.activeMenuNas = nas;
            this.selectedNasData = nas;
            
            const rect = event.currentTarget.getBoundingClientRect();
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

        detailsFromMenu() {
            if (!this.selectedNasData) return;
            const id = this.selectedNasData.id;
            this.activeMenuNas = null;
            this.openDetailsModal(id);
        },

        coaFromMenu() {
            if (!this.selectedNasData) return;
            const id = this.selectedNasData.id;
            this.activeMenuNas = null;
            this.testCoaSocket(id);
        },

        copySecretFromMenu() {
            if (!this.selectedNasData) return;
            const secret = this.selectedNasData.secret;
            this.activeMenuNas = null;
            this.copyToClipboard(secret, 'Shared Secret');
        },

        toggleFromMenu() {
            if (!this.selectedNasData) return;
            const id = this.selectedNasData.id;
            this.activeMenuNas = null;
            this.toggleNasStatus(id);
        },

        editFromMenu() {
            if (!this.selectedNasData) return;
            const id = this.selectedNasData.id;
            this.activeMenuNas = null;
            this.openEditModal(id);
        },

        deleteFromMenu() {
            if (!this.selectedNasData) return;
            const id = this.selectedNasData.id;
            const name = this.selectedNasData.shortname;
            this.activeMenuNas = null;
            this.confirmDeleteNas(id, name);
        },

        openAddModal() {
            this.addForm = {
                shortname: '',
                nasname: '',
                type: 'mikrotik',
                secret: '',
                ports: 1812,
                coa_port: 3799,
                server: '',
                community: '',
                router_id: '',
                is_active: true,
                description: ''
            };
            this.showAddSecret = false;
            this.showAddModal = true;
        },

        openEditModal(nasId) {
            const nas = this.nasList.find(n => n.id === nasId);
            if (nas) {
                this.editNas = {
                    id: nas.id,
                    shortname: nas.shortname,
                    nasname: nas.nasname,
                    type: nas.type,
                    ports: nas.ports,
                    coa_port: nas.coa_port,
                    server: nas.server || '',
                    community: nas.community || '',
                    router_id: nas.router_id || '',
                    is_active: !!nas.is_active,
                    description: nas.description || '',
                    secret: ''
                };
            }
            this.showEditSecret = false;
            this.showEditModal = true;
        },

        openDetailsModal(nasId) {
            this.viewNas = this.nasList.find(n => n.id === nasId) || null;
            this.showViewSecret = false;
            this.showDetailsModal = true;
        },

        onRouterSelect(routerId) {
            if (!routerId) return;
            const router = this.routers.find(r => r.id == routerId);
            if (router) {
                this.addForm.shortname = router.name;
                this.addForm.nasname = router.ip;
                this.addForm.type = 'mikrotik';
                if (router.radius_secret) {
                    this.addForm.secret = router.radius_secret;
                } else if (!this.addForm.secret) {
                    this.generateRandomSecret('add');
                }
                this.showToast(`Imported parameters from '${router.name}'`, 'info');
            }
        },

        generateRandomSecret(target = 'add') {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#%^&*';
            let secret = '';
            for (let i = 0; i < 20; i++) {
                secret += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            if (target === 'add') {
                this.addForm.secret = secret;
                this.showAddSecret = true;
            } else {
                this.editNas.secret = secret;
                this.showEditSecret = true;
            }
            this.showToast('Secure 20-character secret generated.', 'success');
        },

        async testCoaSocket(nasId) {
            this.testingCoaId = nasId;
            try {
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
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'CoA connection check failed.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while testing CoA reachability.', 'error');
            } finally {
                this.testingCoaId = null;
            }
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
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast('Failed to toggle status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while updating NAS status.', 'error');
            }
        },

        confirmDeleteNas(nasId, shortname) {
            this.activeMenuNas = null;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete NAS Client?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete NAS client <strong class="text-slate-900 font-semibold">${shortname}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">FreeRADIUS authentication for this gateway will stop immediately.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete NAS',
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
                        form.action = `/admin/network/nas/${nasId}`;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete NAS client '${shortname}'? FreeRADIUS authentication for this gateway will stop immediately.`)) {
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
