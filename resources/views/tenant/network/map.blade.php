@extends('tenant.layouts.app')

@section('title', 'Network Topology Graph - ' . ($tenant->company_name ?? $tenant->name))

@section('content')
<div x-data="networkMapManager()" class="space-y-3">
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

    <!-- Tier 1: Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Network Topology Graph</h2>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 flex-shrink-0">

            <!-- Router Filter -->
            <select x-model="filters.router_id" 
                    @change="applyFilter()" 
                    class="py-1 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:ring-1 focus:ring-blue-500 font-normal">
                <option value="">All Routers</option>
                @foreach($allRouters as $r)
                    <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>

            <!-- OLT Filter -->
            <select x-model="filters.olt_id" 
                    @change="applyFilter()" 
                    class="py-1 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:ring-1 focus:ring-blue-500 font-normal">
                <option value="">All OLTs</option>
                @foreach($allOlts as $o)
                    <option value="{{ $o->id }}" {{ request('olt_id') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                @endforeach
            </select>

            <!-- Refresh Button -->
            <button type="button" 
                    @click="refreshData()" 
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 shadow-xs transition flex items-center gap-1 cursor-pointer">
                <i class="fas fa-arrows-rotate text-[11px]" :class="{'fa-spin text-blue-600': isRefreshing}"></i>
                <span>Refresh</span>
            </button>
        </div>
    </div>

    <!-- Tier 2: 6 Compact Summary Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
        <!-- Metric 1: Core Routers -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block truncate">Core Gateways</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight font-mono leading-none block">
                    {{ $onlineRouters }} <span class="text-xs text-slate-400 font-normal">/ {{ $totalRouters }}</span>
                </span>
            </div>
            <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Metric 2: OLT Hubs -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-purple-700 uppercase tracking-wider block truncate">OLT Distribution</span>
                <span class="text-base font-extrabold text-purple-600 tracking-tight font-mono leading-none block">
                    {{ $onlineOlts }} <span class="text-xs text-purple-400 font-normal">/ {{ $totalOlts }}</span>
                </span>
            </div>
            <div class="w-7 h-7 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-ethernet"></i>
            </div>
        </div>

        <!-- Metric 3: Total PON Ports -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Total PON Ports</span>
                <span class="text-base font-extrabold text-indigo-600 tracking-tight font-mono leading-none block">{{ number_format($totalPonPorts) }}</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs border border-indigo-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Metric 4: Total Customer ONUs -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total ONUs</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight font-mono leading-none block">{{ number_format($totalOnus) }}</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-slate-50 text-slate-600 flex items-center justify-center text-xs border border-slate-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 5: Online ONUs -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Online Active</span>
                <span class="text-base font-extrabold text-emerald-600 tracking-tight font-mono leading-none block">{{ number_format($onlineOnus) }}</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 6: Optical Alerts / LOS -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-rose-700 uppercase tracking-wider block truncate">Offline / LOS</span>
                <span class="text-base font-extrabold text-rose-600 tracking-tight font-mono leading-none block">{{ number_format($losOnus) }}</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-xs border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Tier 3: Main Visual Canvas (Topology Tree) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden relative">
        <!-- Canvas Toolbar -->
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
                    Hierarchical Network Topology Graph
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-200 text-slate-700 font-medium">
                    Live Status Monitor
                </span>
            </div>

            <!-- Optical Signal Color Legend -->
            <div class="flex items-center gap-3 text-[11px] text-slate-600 font-normal">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Good (0 to -20 dBm)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>Warning (-21 to -26 dBm)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span>Critical (-27 to -32 dBm)</span>
                </div>
            </div>
        </div>

        <!-- Interactive Topology Tree Graph -->
        <div class="p-6 bg-slate-50/60 min-h-[550px] overflow-x-auto relative select-none border-b border-slate-200">
            <!-- Background Topology Grid Pattern (Light) -->
            <div class="absolute inset-0 opacity-40 pointer-events-none" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px;"></div>

            <!-- Topology Hierarchy Container -->
            <div class="relative z-10 space-y-8 min-w-[700px]">
                <!-- LEVEL 1: ISP Internet Gateway -->
                <div class="flex justify-center">
                    <div class="px-4 py-2.5 rounded-xl bg-white text-slate-900 shadow-xs flex items-center gap-2.5 border-2 border-blue-500">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-medium text-blue-600 uppercase tracking-widest block leading-tight">Upstream Transit</span>
                            <span class="text-xs font-bold font-mono text-slate-900">ISP Internet Core Gateway</span>
                        </div>
                    </div>
                </div>

                <!-- Connector Line Level 1 to 2 -->
                <div class="w-0.5 h-6 bg-blue-300 mx-auto -my-4"></div>

                <!-- LEVEL 2: Core MikroTik Routers -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-center max-w-6xl mx-auto">
                    @forelse($topology as $routerNode)
                        <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-xs hover:border-blue-500 hover:shadow-md transition cursor-pointer"
                             @click="inspectNode('router', {{ json_encode($routerNode) }})">
                            <!-- Router Header -->
                            <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ $routerNode['name'] }}</h4>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ $routerNode['ip_address'] }}</span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium {{ $routerNode['status'] === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $routerNode['status'] === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ strtoupper($routerNode['status']) }}</span>
                                </span>
                            </div>

                            <!-- Router Quick Metrics -->
                            <div class="grid grid-cols-2 gap-2 mt-2 text-[10px] text-slate-700">
                                <div class="bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <span class="text-slate-500 block text-[9px]">CPU Load</span>
                                    <span class="font-mono font-bold text-blue-600">{{ $routerNode['cpu_load'] }}%</span>
                                </div>
                                <div class="bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <span class="text-slate-500 block text-[9px]">Connected OLTs</span>
                                    <span class="font-mono font-bold text-purple-600">{{ count($routerNode['olts']) }} OLTs</span>
                                </div>
                            </div>

                            <!-- LEVEL 3 & 4: Sub-Tree OLT Distribution & PON Ports -->
                            @if(!empty($routerNode['olts']))
                                <div class="mt-3 pt-2.5 border-t border-slate-100 space-y-2">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">OLT Distribution Hubs:</span>
                                    
                                    @foreach($routerNode['olts'] as $oltNode)
                                        <div class="bg-slate-50/90 rounded-lg p-2.5 border border-purple-200 hover:border-purple-400 transition"
                                             @click.stop="inspectNode('olt', {{ json_encode($oltNode) }})">
                                            <div class="flex items-center justify-between text-xs pb-1.5">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="fas fa-ethernet text-purple-600 text-[11px]"></i>
                                                    <span class="font-bold text-purple-900 text-[11px]">{{ $oltNode['name'] }}</span>
                                                </div>
                                                <span class="text-[10px] text-slate-500 font-mono">{{ $oltNode['ip_address'] }}</span>
                                            </div>

                                            <!-- PON Port Badges -->
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                @foreach($oltNode['pon_ports'] as $pon)
                                                    <div class="px-1.5 py-0.5 rounded bg-white text-[10px] font-mono border border-slate-200 hover:border-teal-500 hover:bg-teal-50/50 transition cursor-pointer flex items-center gap-1 shadow-2xs"
                                                         @click.stop="inspectNode('pon', {{ json_encode($pon) }}, {{ json_encode($oltNode) }})">
                                                        <span class="text-teal-700 font-semibold">{{ $pon['name'] }}</span>
                                                        <span class="text-slate-500 text-[9px]">({{ $pon['total_onus'] }} ONUs)</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-2 text-center p-2 rounded bg-slate-50 text-[10px] text-slate-400 italic border border-slate-100">
                                    No OLTs mapped to this router yet.
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12 text-slate-400">
                            <i class="fas fa-network-wired text-3xl text-slate-300 mb-2"></i>
                            <p class="text-xs text-slate-500">No routers or OLTs configured in this tenant workspace.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    <!-- Production-Grade Slide-Over Device Inspector Drawer -->
    <div x-show="drawer.open" 
         class="fixed inset-0 z-50 overflow-hidden" 
         style="display: none;"
         x-cloak
         @keydown.escape.window="closeDrawer()">
        
        <!-- Backdrop Overlay -->
        <div x-show="drawer.open"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeDrawer()" 
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"></div>

        <!-- Slide-Over Panel -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="drawer.open"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md sm:max-w-lg bg-white shadow-2xl border-l border-slate-200 flex flex-col justify-between"
                 @click.stop>
                
                <!-- Drawer Top Header -->
                <div class="p-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base shadow-sm shrink-0"
                             :class="drawer.iconBg || 'bg-blue-600 text-white'">
                            <i :class="drawer.iconClass || 'fas fa-server'"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-white truncate" x-text="drawer.title"></h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium"
                                      :class="drawer.status === 'online' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="drawer.status === 'online' ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                                    <span x-text="(drawer.status || 'online').toUpperCase()"></span>
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 truncate block mt-0.5" x-text="drawer.subtitle || drawer.typeBadge"></span>
                        </div>
                    </div>
                    <button type="button" 
                            @click="closeDrawer()" 
                            class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition flex items-center justify-center cursor-pointer shrink-0 ml-2"
                            title="Close Inspector (Esc)">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Drawer Body Scrollable Content -->
                <div class="p-4 space-y-4 overflow-y-auto flex-1 bg-slate-50/50 text-xs">
                    
                    <!-- KPI Metric Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="stat in drawer.stats" :key="stat.label">
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 shadow-2xs">
                                <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block truncate" x-text="stat.label"></span>
                                <span class="text-sm font-extrabold font-mono leading-tight block mt-0.5 truncate" :class="stat.color" x-text="stat.value"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Health Progress Ratio for PON / OLT -->
                    <div x-show="drawer.nodeType === 'pon' || drawer.nodeType === 'olt'" class="p-3 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-medium text-slate-700">Channel Efficiency & Online Ratio</span>
                            <span class="font-mono font-bold" :class="drawer.onlinePercent >= 80 ? 'text-emerald-600' : 'text-amber-600'" x-text="drawer.onlinePercent + '% Online'"></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-emerald-500 h-2 transition-all duration-500" :style="`width: ${drawer.onlinePercent}%`"></div>
                            <div class="bg-rose-400 h-2 transition-all duration-500" :style="`width: ${100 - drawer.onlinePercent}%`"></div>
                        </div>
                    </div>

                    <!-- Technical Specifications Table -->
                    <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">Device Technical Specs</span>
                            <span class="text-[10px] text-slate-400 font-mono">Live Configuration</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="(val, key) in drawer.details" :key="key">
                                <div class="px-3 py-2 flex items-center justify-between text-[11px] hover:bg-slate-50/50 transition">
                                    <span class="text-slate-500" x-text="key"></span>
                                    <div class="flex items-center gap-1.5 font-mono font-semibold text-slate-800">
                                        <span x-text="val"></span>
                                        <button type="button" 
                                                x-show="key.includes('IP') || key.includes('MAC')" 
                                                @click="copyToClipboard(val, key)"
                                                class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                                title="Copy to Clipboard">
                                            <i class="far fa-copy text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Connected Subscribers (ONUs) Section -->
                    <div x-show="drawer.items && drawer.items.length > 0" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 leading-tight">Connected Subscribers & ONTs</h4>
                                <span class="text-[10px] text-slate-500 font-normal">
                                    Showing <span class="font-bold text-slate-800" x-text="filteredDrawerItems.length"></span> of <span class="font-bold text-slate-800" x-text="drawer.items.length"></span> ONUs
                                </span>
                            </div>
                        </div>

                        <!-- Search and Filter Bar inside Drawer -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <!-- Live Search Input -->
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-2.5 top-2.5 text-slate-400 text-[10px]"></i>
                                <input type="text" 
                                       x-model="drawer.searchQuery" 
                                       placeholder="Search MAC, Name, Model..." 
                                       class="w-full pl-7 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-2xs" />
                                <button type="button" 
                                        x-show="drawer.searchQuery" 
                                        @click="drawer.searchQuery = ''" 
                                        class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>

                            <!-- Status Filter Buttons -->
                            <div class="flex items-center gap-1">
                                <button type="button" 
                                        @click="drawer.statusFilter = 'all'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    All
                                </button>
                                <button type="button" 
                                        @click="drawer.statusFilter = 'online'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'online' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    Online
                                </button>
                                <button type="button" 
                                        @click="drawer.statusFilter = 'offline'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'offline' ? 'bg-rose-600 text-white border-rose-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    Offline
                                </button>
                            </div>
                        </div>

                        <!-- Subscriber Cards List -->
                        <div class="space-y-1.5 max-h-72 overflow-y-auto pr-0.5">
                            <template x-for="item in filteredDrawerItems" :key="item.id || item.mac_address">
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200 hover:border-blue-300 hover:shadow-xs transition space-y-1.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full shrink-0" 
                                                  :class="item.status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-400'"></span>
                                            <span class="font-bold text-slate-900 text-xs truncate" x-text="item.name"></span>
                                            <span x-show="item.pon_name" class="px-1.5 py-0.2 bg-teal-50 text-teal-700 rounded text-[9px] font-mono border border-teal-200/60" x-text="item.pon_name"></span>
                                        </div>

                                        <!-- Optical Rx Power Badge with Project Custom Thresholds -->
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold border shrink-0"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border-emerald-200': item.rx_power_dbm !== null && item.rx_power_dbm >= -20,
                                                  'bg-amber-50 text-amber-700 border-amber-200': item.rx_power_dbm !== null && item.rx_power_dbm >= -26 && item.rx_power_dbm < -20,
                                                  'bg-rose-50 text-rose-700 border-rose-200': item.status !== 'online' || (item.rx_power_dbm !== null && item.rx_power_dbm < -26),
                                                  'bg-slate-100 text-slate-500 border-slate-200': item.rx_power_dbm === null
                                              }"
                                              x-text="item.rx_power_dbm !== null ? (item.rx_power_dbm + ' dBm') : (item.status === 'online' ? 'Online' : 'LOS')"></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 font-mono pt-1 border-t border-slate-100">
                                        <div class="flex items-center gap-1 truncate">
                                            <i class="fas fa-fingerprint text-[9px] text-slate-400"></i>
                                            <span class="truncate" x-text="item.mac_address || 'No MAC'"></span>
                                        </div>
                                        <div class="flex items-center justify-end gap-1 text-slate-600">
                                            <i class="fas fa-ruler-horizontal text-[9px] text-slate-400"></i>
                                            <span x-text="item.distance_m ? (item.distance_m + 'm') : '-'"></span>
                                        </div>
                                    </div>

                                    <!-- PPPoE / Details Subline -->
                                    <div x-show="item.pppoe_username || item.desc" class="flex items-center justify-between text-[10px] text-slate-600 bg-slate-50 px-2 py-1 rounded">
                                        <span class="truncate text-blue-700 font-medium" x-text="item.pppoe_username ? ('PPPoE: ' + item.pppoe_username) : (item.desc || '')"></span>
                                        <a :href="`/admin/network/onu?search=${encodeURIComponent(item.mac_address || item.name)}`" 
                                           class="text-blue-600 hover:text-blue-800 font-semibold shrink-0">
                                            Diagnose →
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <!-- Zero State for Search Filter -->
                            <div x-show="filteredDrawerItems.length === 0" class="p-6 text-center text-slate-400 bg-white rounded-lg border border-slate-200">
                                <i class="fas fa-search text-xl text-slate-300 mb-1"></i>
                                <p class="text-xs text-slate-600 font-medium">No matching subscribers found</p>
                                <span class="text-[10px] text-slate-400">Try adjusting your search keyword or status filter.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Sticky Footer Actions -->
                <div class="p-3.5 bg-white border-t border-slate-200 flex items-center justify-between gap-2">
                    <button type="button" 
                            @click="closeDrawer()" 
                            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer">
                        Close
                    </button>
                    <a :href="drawer.link || '#'" 
                       class="flex-1 py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 text-center">
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                        <span x-text="drawer.linkText || 'Configure in Module'"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Tier 4: Quick Network Alerts & Device Inventory Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Network Devices & Optical Status Inventory</h3>
            <span class="text-[11px] text-slate-500 font-normal">Active monitoring</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-700">
                        <th class="w-12 px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">#</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Device Type</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Device Name</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">IP / MAC Address</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Parent / Gateway</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Optical / CPU Load</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Status</th>
                        <th class="w-24 px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <!-- 1. Routers Rows -->
                    @foreach($routers as $rIndex => $r)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-2.5 py-1.5 text-center font-mono text-slate-500 text-[11px] font-normal border border-slate-200">R-{{ $rIndex + 1 }}</td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px] font-normal">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">Core Router</span>
                            </td>
                            <td class="px-2.5 py-1.5 text-slate-900 font-medium border border-slate-200 text-[11px]">{{ $r->name }}</td>
                            <td class="px-2.5 py-1.5 font-mono text-[11px] text-slate-800 border border-slate-200 text-center">{{ $r->ip_address }}</td>
                            <td class="px-2.5 py-1.5 text-slate-500 border border-slate-200 text-center text-[11px]">WAN Uplink</td>
                            <td class="px-2.5 py-1.5 font-mono text-center text-[11px] border border-slate-200 text-slate-700">CPU: {{ $r->cpu_load ?? 12 }}%</td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px]">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $r->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $r->status === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ ucfirst($r->status ?? 'online') }}</span>
                                </span>
                            </td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px]">
                                <a href="{{ route('tenant.network.mikrotik.show', $r->id) }}" class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200/60 transition text-[11px]">View</a>
                            </td>
                        </tr>
                    @endforeach

                    <!-- 2. OLTs Rows -->
                    @foreach($olts as $oIndex => $o)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-2.5 py-1.5 text-center font-mono text-slate-500 text-[11px] font-normal border border-slate-200">O-{{ $oIndex + 1 }}</td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px] font-normal">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200">OLT Distribution</span>
                            </td>
                            <td class="px-2.5 py-1.5 text-slate-900 font-medium border border-slate-200 text-[11px]">{{ $o->name }}</td>
                            <td class="px-2.5 py-1.5 font-mono text-[11px] text-slate-800 border border-slate-200 text-center">{{ $o->ip_address }}</td>
                            <td class="px-2.5 py-1.5 text-slate-700 border border-slate-200 text-center text-[11px]">{{ $o->router?->name ?? 'Standalone' }}</td>
                            <td class="px-2.5 py-1.5 font-mono text-center text-[11px] border border-slate-200 text-purple-700">{{ $o->total_pon_ports ?? 8 }} PON Ports</td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px]">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $o->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $o->status === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ ucfirst($o->status ?? 'online') }}</span>
                                </span>
                            </td>
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 text-[11px]">
                                <a href="{{ route('tenant.network.olt.show', $o->id) }}" class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 transition text-[11px]">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function networkMapManager() {
    return {
        isRefreshing: false,
        filters: {
            router_id: '{{ request("router_id", "") }}',
            olt_id: '{{ request("olt_id", "") }}'
        },
        toast: {
            show: false,
            message: '',
            type: 'success',
            timer: null
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timer) clearTimeout(this.toast.timer);
            this.toast.timer = setTimeout(() => { this.toast.show = false; }, 3500);
        },

        drawer: {
            open: false,
            title: '',
            subtitle: '',
            typeBadge: '',
            nodeType: '',
            status: 'online',
            iconBg: 'bg-blue-600 text-white',
            iconClass: 'fas fa-server',
            stats: [],
            onlinePercent: 100,
            details: {},
            items: [],
            searchQuery: '',
            statusFilter: 'all',
            link: '#',
            linkText: 'Configure in Module'
        },

        get filteredDrawerItems() {
            if (!this.drawer.items || !Array.isArray(this.drawer.items)) return [];
            let list = this.drawer.items;
            
            // Status Filter
            if (this.drawer.statusFilter === 'online') {
                list = list.filter(item => item.status === 'online');
            } else if (this.drawer.statusFilter === 'offline') {
                list = list.filter(item => item.status !== 'online');
            }

            // Search Query
            if (this.drawer.searchQuery && this.drawer.searchQuery.trim() !== '') {
                const q = this.drawer.searchQuery.toLowerCase().trim();
                list = list.filter(item => {
                    const name = (item.name || '').toLowerCase();
                    const mac = (item.mac_address || '').toLowerCase();
                    const pppoe = (item.pppoe_username || '').toLowerCase();
                    const desc = (item.desc || '').toLowerCase();
                    const pon = (item.pon_name || '').toLowerCase();
                    return name.includes(q) || mac.includes(q) || pppoe.includes(q) || desc.includes(q) || pon.includes(q);
                });
            }

            return list;
        },

        inspectNode(type, data, parentData = null) {
            this.drawer.open = true;
            this.drawer.searchQuery = '';
            this.drawer.statusFilter = 'all';
            this.drawer.nodeType = type;

            if (type === 'router') {
                let allRouterOnus = [];
                let totalOnus = 0;
                let onlineOnus = 0;

                if (data.olts && Array.isArray(data.olts)) {
                    data.olts.forEach(olt => {
                        if (olt.pon_ports && Array.isArray(olt.pon_ports)) {
                            olt.pon_ports.forEach(pon => {
                                if (pon.onus && Array.isArray(pon.onus)) {
                                    pon.onus.forEach(onu => {
                                        allRouterOnus.push(onu);
                                        totalOnus++;
                                        if (onu.status === 'online') onlineOnus++;
                                    });
                                }
                            });
                        }
                    });
                }

                const offlineOnus = totalOnus - onlineOnus;
                const onlineRatio = totalOnus > 0 ? Math.round((onlineOnus / totalOnus) * 100) : 100;

                this.drawer.title = data.name;
                this.drawer.subtitle = 'Core Gateway Router • ' + (data.model || 'MikroTik CCR/RB');
                this.drawer.status = data.status || 'online';
                this.drawer.iconBg = 'bg-blue-600 text-white';
                this.drawer.iconClass = 'fas fa-server';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: 'Connected OLTs', value: (data.olts ? data.olts.length : 0), color: 'text-blue-600' },
                    { label: 'Total ONUs', value: totalOnus, color: 'text-slate-800' },
                    { label: 'Online Active', value: onlineOnus, color: 'text-emerald-600' },
                    { label: 'CPU Load', value: (data.cpu_load || 12) + '%', color: 'text-amber-600' }
                ];
                this.drawer.details = {
                    'Device Name': data.name,
                    'Management IP': data.ip_address,
                    'Hardware Model': data.model || 'MikroTik RouterOS',
                    'CPU Load': (data.cpu_load || 12) + '%',
                    'System Uptime': data.uptime || 'Running',
                    'Connected OLTs': (data.olts ? data.olts.length : 0) + ' Devices',
                    'Operational Status': (data.status || 'online').toUpperCase()
                };
                this.drawer.items = allRouterOnus;
                this.drawer.link = `/admin/network/mikrotik/${data.id}`;
                this.drawer.linkText = 'Manage Router in Module';

            } else if (type === 'olt') {
                let allOltOnus = [];
                let totalOnus = data.total_onus || 0;
                let onlineOnus = data.online_onus || 0;

                if (data.pon_ports && Array.isArray(data.pon_ports)) {
                    data.pon_ports.forEach(pon => {
                        if (pon.onus && Array.isArray(pon.onus)) {
                            pon.onus.forEach(onu => {
                                allOltOnus.push(onu);
                            });
                        }
                    });
                    if (totalOnus === 0) totalOnus = allOltOnus.length;
                    if (onlineOnus === 0) onlineOnus = allOltOnus.filter(o => o.status === 'online').length;
                } else if (data.onus && Array.isArray(data.onus)) {
                    allOltOnus = data.onus;
                }

                const offlineOnus = totalOnus - onlineOnus;
                const onlineRatio = totalOnus > 0 ? Math.round((onlineOnus / totalOnus) * 100) : 0;

                this.drawer.title = data.name;
                this.drawer.subtitle = (data.brand || 'OLT') + ' ' + (data.model || '') + ' • Distribution Tier';
                this.drawer.status = data.status || 'online';
                this.drawer.iconBg = 'bg-purple-600 text-white';
                this.drawer.iconClass = 'fas fa-network-wired';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: 'Total ONUs', value: totalOnus, color: 'text-slate-800' },
                    { label: 'Online Active', value: onlineOnus, color: 'text-emerald-600' },
                    { label: 'Offline / LOS', value: offlineOnus, color: 'text-rose-600' },
                    { label: 'PON Ports', value: data.total_pon_ports || (data.pon_ports ? data.pon_ports.length : 8), color: 'text-purple-600' }
                ];
                this.drawer.details = {
                    'OLT Name': data.name,
                    'Management IP': data.ip_address,
                    'Brand & Model': (data.brand || '') + ' ' + (data.model || ''),
                    'Uplink Gateway': (data.router ? data.router.name : 'Core Router'),
                    'PON Capacity': (data.total_pon_ports || (data.pon_ports ? data.pon_ports.length : 8)) + ' Active Ports',
                    'Operational Status': (data.status || 'online').toUpperCase()
                };
                this.drawer.items = allOltOnus;
                this.drawer.link = `/admin/network/olt/${data.id}`;
                this.drawer.linkText = 'Inspect OLT & PONs';

            } else if (type === 'pon') {
                const totalOnus = data.total_onus || (data.onus ? data.onus.length : 0);
                const onlineOnus = data.online_onus !== undefined ? data.online_onus : (data.onus ? data.onus.filter(o => o.status === 'online').length : 0);
                const offlineOnus = data.offline_onus !== undefined ? data.offline_onus : (totalOnus - onlineOnus);
                const onlineRatio = totalOnus > 0 ? Math.round((onlineOnus / totalOnus) * 100) : 0;

                this.drawer.title = (parentData?.name ? (parentData.name + ' - ') : '') + data.name;
                this.drawer.subtitle = 'Optical Distribution Channel • ' + (parentData?.brand || 'PON Port');
                this.drawer.status = data.status || (onlineOnus > 0 ? 'online' : (totalOnus === 0 ? 'online' : 'offline'));
                this.drawer.iconBg = 'bg-teal-600 text-white';
                this.drawer.iconClass = 'fas fa-ethernet';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: 'Total ONUs', value: totalOnus, color: 'text-slate-800' },
                    { label: 'Online Active', value: onlineOnus, color: 'text-emerald-600' },
                    { label: 'Offline / LOS', value: offlineOnus, color: 'text-rose-600' },
                    { label: 'Efficiency', value: onlineRatio + '%', color: 'text-teal-600' }
                ];
                this.drawer.details = {
                    'Channel Name': data.name,
                    'Parent OLT': (parentData?.name || 'OLT Distribution'),
                    'OLT Management IP': (parentData?.ip_address || '-'),
                    'Total Subscribers': totalOnus + ' Endpoints',
                    'Online Active': onlineOnus + ' Active (' + onlineRatio + '%)',
                    'Port Technology': (parentData?.brand?.includes('EPON') ? 'EPON 1.25G' : 'GPON 2.5G / EPON')
                };
                this.drawer.items = data.onus || [];
                this.drawer.link = `/admin/network/onu?search=${encodeURIComponent(data.name)}`;
                this.drawer.linkText = 'View All Channel ONUs';
            }
        },

        closeDrawer() {
            this.drawer.open = false;
        },

        copyToClipboard(text, label = 'Value') {
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(`${label} copied to clipboard!`, 'success');
                });
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.showToast(`${label} copied to clipboard!`, 'success');
                } catch (err) {
                    this.showToast('Failed to copy', 'error');
                }
                textArea.remove();
            }
        },

        applyFilter() {
            const params = new URLSearchParams();
            if (this.filters.router_id) params.set('router_id', this.filters.router_id);
            if (this.filters.olt_id) params.set('olt_id', this.filters.olt_id);
            window.location.href = `{{ route('tenant.network.map') }}?${params.toString()}`;
        },

        async refreshData() {
            this.isRefreshing = true;
            try {
                setTimeout(() => {
                    this.isRefreshing = false;
                    this.showToast('Network topology data refreshed.', 'success');
                    window.location.reload();
                }, 700);
            } catch (e) {
                this.isRefreshing = false;
            }
        }
    };
}
</script>
@endsection
