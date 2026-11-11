@extends('tenant.layouts.app')

@section('title', __('Network Topology & GIS Map') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .topology-canvas {
        background-color: #f8fafc;
        background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
        background-size: 20px 20px;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="networkMapManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- Toast Notification Banner --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-3.5 py-2 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-cyan-50 text-cyan-800 border-cyan-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-cyan-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Count Badge + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight whitespace-nowrap">{{ __('Network Topology & GIS Map') }}</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __(':count Nodes', ['count' => $totalRouters + $totalOlts]) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap sm:flex-nowrap shrink-0">
            {{-- Refresh Button --}}
            <button type="button" 
                    @click="refreshData()" 
                    class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                <i class="fas fa-arrows-rotate text-[11px]" :class="{'fa-spin text-cyan-600': isRefreshing}"></i>
                <span>{{ __('Refresh') }}</span>
            </button>

            {{-- Quick Links to Modules --}}
            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="h-[34px] bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5">
                <i class="fas fa-server text-blue-600 text-[10px]"></i>
                <span class="hidden md:inline">{{ __('Routers') }}</span>
            </a>

            <a href="{{ route('tenant.network.olt') }}" 
               class="h-[34px] bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5">
                <i class="fas fa-ethernet text-purple-600 text-[10px]"></i>
                <span class="hidden md:inline">{{ __('OLTs') }}</span>
            </a>

            <a href="{{ route('tenant.network.onu') }}" 
               class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-white text-[10px]"></i>
                <span>{{ __('ONUs') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strict AGENTS.md Rule 2.B: Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Core Gateways --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Core Gateways') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    {{ $onlineRouters }} <span class="text-[10px] text-slate-400 font-normal">/ {{ $totalRouters }}</span>
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 2: OLT Distribution --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('OLT Distribution') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-600 leading-tight block truncate">
                    {{ $onlineOlts }} <span class="text-[10px] text-purple-400 font-normal">/ {{ $totalOlts }}</span>
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-ethernet"></i>
            </div>
        </div>

        {{-- Card 3: Total PON Ports --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __('Total PON Ports') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">{{ number_format($totalPonPorts) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 4: Total ONUs --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Total ONUs') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">{{ number_format($totalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 border border-slate-200 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 5: Online Active --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Online Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($onlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        {{-- Card 6: Offline / LOS --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">{{ __('Offline / LOS') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($losOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-[10px] shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Strict Customer-Standard 2-Tier Filter System) --}}
    <form method="GET" action="{{ route('tenant.network.map') }}" 
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
          x-data="{ 
              showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
              searchQuery: '{{ addslashes(request('search', '')) }}' 
          }">
        
        {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] controls) --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            {{-- Intelligent Live Search Box --}}
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors duration-200"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery" 
                       placeholder="{{ __('Search device, IP, MAC or PPPoE username...') }}" 
                       value="{{ request('search') }}"
                       class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                <button type="button" 
                        x-show="searchQuery && searchQuery.length > 0" 
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                        style="display: none;">
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            </div>

            {{-- Primary Filter: Router Filter --}}
            <select name="router_id" 
                    class="h-[34px] w-full md:w-44 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="">{{ __('All Core Routers') }}</option>
                @foreach($allRouters as $r)
                    <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>

            {{-- View Mode Toggle Switcher --}}
            <div class="inline-flex h-[34px] p-0.5 bg-slate-100 rounded-lg border border-slate-200/80 shrink-0">
                <button type="button" 
                        @click="activeTab = 'tree'"
                        class="h-[30px] px-2.5 rounded-md text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                        :class="activeTab === 'tree' ? 'bg-white text-cyan-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                    <i class="fas fa-sitemap text-[11px]"></i>
                    <span class="hidden sm:inline">{{ __('Topology Tree') }}</span>
                </button>
                <button type="button" 
                        @click="activeTab = 'inventory'"
                        class="h-[30px] px-2.5 rounded-md text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                        :class="activeTab === 'inventory' ? 'bg-white text-cyan-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                    <i class="fas fa-list-check text-[11px]"></i>
                    <span class="hidden sm:inline">{{ __('Device Inventory') }}</span>
                </button>
            </div>

            {{-- More Filters Accordion Toggle Button --}}
            <button type="button" 
                    @click="showMoreFilters = !showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                    :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50/70 border-cyan-200 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                <span>{{ __('More Filters') }}</span>
                @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                @endif
                <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
            </button>

            {{-- Strict Filter & Reset Button Sequence (Filter First, Reset Second - Always Visible) --}}
            <div class="flex items-center gap-1.5 shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>{{ __('Filter') }}</span>
                </button>

                <a href="{{ route('tenant.network.map') }}" 
                   class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>{{ __('Reset') }}</span>
                </a>
            </div>
        </div>

        {{-- Tier 2: Secondary Filters Drawer --}}
        <div x-show="showMoreFilters" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            
            {{-- OLT Selector --}}
            <div class="w-full sm:w-auto">
                <select name="olt_id" 
                        class="h-[34px] w-full sm:w-52 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Distribution OLTs') }}</option>
                    @foreach($allOlts as $o)
                        <option value="{{ $o->id }}" {{ request('olt_id') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Operational Status --}}
            <div class="w-full sm:w-auto">
                <select name="status" 
                        class="h-[34px] w-full sm:w-36 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>{{ __('Online') }}</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>{{ __('Offline') }}</option>
                </select>
            </div>
        </div>
    </form>

    {{-- 4. View 1: Interactive Hierarchical Topology Tree Graph --}}
    <div x-show="activeTab === 'tree'" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden relative">
        {{-- Topology Header & Legend Bar --}}
        <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 min-w-0">
                <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
                    {{ __('Hierarchical Network Topology Graph') }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-200 text-slate-700 font-medium">
                    {{ __('Live Status') }}
                </span>
            </div>

            {{-- Optical Signal Color Legend --}}
            <div class="flex items-center gap-3 text-[11px] text-slate-600 font-normal">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>{{ __('Good (0 to -20 dBm)') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>{{ __('Warning (-21 to -26 dBm)') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span>{{ __('Critical (-27 to -32 dBm)') }}</span>
                </div>
            </div>
        </div>

        {{-- Topology Canvas Body --}}
        <div class="p-6 topology-canvas overflow-x-auto relative select-none">
            <div class="relative z-10 space-y-8 min-w-[700px]">
                {{-- LEVEL 1: ISP Internet Gateway --}}
                <div class="flex justify-center">
                    <div class="px-4 py-2.5 rounded-xl bg-white text-slate-900 shadow-xs flex items-center gap-2.5 border-2 border-cyan-600">
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm border border-cyan-100">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div>
                            <span class="text-[9px] font-medium text-cyan-600 uppercase tracking-widest block leading-tight">{{ __('Upstream Transit') }}</span>
                            <span class="text-xs font-bold font-mono text-slate-900">{{ __('ISP Internet Core Gateway') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Connector Line Level 1 to 2 --}}
                <div class="w-0.5 h-6 bg-cyan-300 mx-auto -my-4"></div>

                {{-- LEVEL 2: Core MikroTik Routers --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 justify-center max-w-6xl mx-auto">
                    @forelse($topology as $routerNode)
                        <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs hover:border-cyan-500 hover:shadow-xs transition cursor-pointer"
                             @click="inspectNode('router', {{ json_encode($routerNode) }})">
                            {{-- Router Header --}}
                            <div class="flex items-start justify-between gap-2 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs shrink-0">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-slate-900 leading-tight truncate">{{ $routerNode['name'] }}</h4>
                                        <span class="text-[10px] text-slate-500 font-mono block truncate">{{ $routerNode['ip_address'] }}</span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium {{ $routerNode['status'] === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $routerNode['status'] === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ $routerNode['status'] === 'online' ? __('ONLINE') : __('OFFLINE') }}</span>
                                </span>
                            </div>

                            {{-- Router Quick Metrics --}}
                            <div class="grid grid-cols-2 gap-2 mt-2 text-[10px] text-slate-700">
                                <div class="bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <span class="text-slate-500 block text-[9px]">{{ __('CPU Load') }}</span>
                                    <span class="font-mono font-bold text-blue-600">{{ $routerNode['cpu_load'] }}%</span>
                                </div>
                                <div class="bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <span class="text-slate-500 block text-[9px]">{{ __('Connected OLTs') }}</span>
                                    <span class="font-mono font-bold text-purple-600">{{ __(':count OLTs', ['count' => count($routerNode['olts'])]) }}</span>
                                </div>
                            </div>

                            {{-- LEVEL 3 & 4: Sub-Tree OLT Distribution & PON Ports --}}
                            @if(!empty($routerNode['olts']))
                                <div class="mt-3 pt-2 border-t border-slate-100 space-y-2">
                                    <span class="text-[9px] font-semibold text-slate-500 uppercase tracking-wider block">{{ __('Connected OLT Hubs:') }}</span>
                                    
                                    @foreach($routerNode['olts'] as $oltNode)
                                        <div class="bg-slate-50/90 rounded-lg p-2.5 border border-purple-200 hover:border-purple-400 transition"
                                             @click.stop="inspectNode('olt', {{ json_encode($oltNode) }})">
                                            <div class="flex items-center justify-between text-xs pb-1.5">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <i class="fas fa-ethernet text-purple-600 text-[11px] shrink-0"></i>
                                                    <span class="font-bold text-purple-900 text-[11px] truncate">{{ $oltNode['name'] }}</span>
                                                </div>
                                                <span class="text-[10px] text-slate-500 font-mono shrink-0">{{ $oltNode['ip_address'] }}</span>
                                            </div>

                                            {{-- PON Port Badges --}}
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($oltNode['pon_ports'] as $pon)
                                                    <div class="px-1.5 py-0.5 rounded bg-white text-[10px] font-mono border border-slate-200 hover:border-cyan-500 hover:bg-cyan-50/50 transition cursor-pointer flex items-center gap-1 shadow-2xs"
                                                         @click.stop="inspectNode('pon', {{ json_encode($pon) }}, {{ json_encode($oltNode) }})">
                                                        <span class="text-cyan-800 font-semibold">{{ $pon['name'] }}</span>
                                                        <span class="text-slate-500 text-[9px]">({{ $pon['total_onus'] }})</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-2 text-center p-2 rounded bg-slate-50 text-[10px] text-slate-400 italic border border-slate-100">
                                    {{ __('No OLTs mapped to this gateway router.') }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full text-center py-10 bg-white rounded-xl border border-slate-200 shadow-2xs">
                            <i class="fas fa-network-wired text-3xl text-slate-300 mb-2"></i>
                            <h3 class="text-xs font-bold text-slate-700">{{ __('No Network Devices Found') }}</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ __('Please add or configure routers and OLTs to view topology.') }}</p>
                            <div class="mt-3 flex items-center justify-center gap-2">
                                <a href="{{ route('tenant.network.mikrotik.create') }}" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                                    + {{ __('Add Core Router') }}
                                </a>
                                <a href="{{ route('tenant.network.olt.create') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200">
                                    + {{ __('Add OLT Hub') }}
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- 5. View 2: Master Compact Table (.saas-table Strict AGENTS.md Rule 2.D) --}}
    <div x-show="activeTab === 'inventory'" 
         x-cloak 
         class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        
        <div class="px-3.5 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Network Devices & Optical Status Inventory') }}</h3>
            <span class="text-[11px] text-slate-500 font-normal">{{ __('Active inventory: :count Nodes', ['count' => count($routers) + count($olts)]) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table w-full">
                <thead>
                    <tr>
                        <th class="w-12 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('Device Type') }}</th>
                        <th class="text-center font-semibold">{{ __('Device Name') }}</th>
                        <th class="text-center font-semibold">{{ __('IP / MAC Address') }}</th>
                        <th class="text-center font-semibold">{{ __('Parent / Gateway') }}</th>
                        <th class="text-center font-semibold">{{ __('Metrics / Capacity') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-24 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 1. Routers Rows --}}
                    @foreach($routers as $rIndex => $r)
                        <tr>
                            <td class="text-center font-mono text-slate-500">R-{{ $rIndex + 1 }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">{{ __('Core Router') }}</span>
                            </td>
                            <td class="text-left font-semibold text-slate-900 whitespace-nowrap">{{ $r->name }}</td>
                            <td class="text-center font-mono text-slate-800 whitespace-nowrap">{{ $r->ip_address }}</td>
                            <td class="text-center text-slate-500 whitespace-nowrap">{{ __('WAN Uplink') }}</td>
                            <td class="text-right font-mono text-slate-700 whitespace-nowrap">{{ __('CPU') }}: {{ $r->cpu_load ?? 12 }}%</td>
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $r->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $r->status === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ ($r->status ?? 'online') === 'online' ? __('Online') : __('Offline') }}</span>
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.network.mikrotik.show', $r->id) }}" 
                                   class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition text-[11px] font-semibold">
                                    {{ __('Inspect') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    {{-- 2. OLTs Rows --}}
                    @foreach($olts as $oIndex => $o)
                        <tr>
                            <td class="text-center font-mono text-slate-500">O-{{ $oIndex + 1 }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200 whitespace-nowrap">{{ __('OLT Distribution') }}</span>
                            </td>
                            <td class="text-left font-semibold text-slate-900 whitespace-nowrap">{{ $o->name }}</td>
                            <td class="text-center font-mono text-slate-800 whitespace-nowrap">{{ $o->ip_address }}</td>
                            <td class="text-center text-slate-700 whitespace-nowrap">{{ $o->router?->name ?? __('Standalone Hub') }}</td>
                            <td class="text-right font-mono text-purple-700 whitespace-nowrap">{{ __(':count PON Ports', ['count' => $o->total_pon_ports ?? 8]) }}</td>
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium {{ $o->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $o->status === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ ($o->status ?? 'online') === 'online' ? __('Online') : __('Offline') }}</span>
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.network.olt.show', $o->id) }}" 
                                   class="px-2.5 py-1 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition text-[11px] font-semibold">
                                    {{ __('Inspect') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if(count($routers) === 0 && count($olts) === 0)
                        <tr>
                            <td colspan="8" class="text-center py-8">
                                <div class="max-w-xs mx-auto text-center space-y-1">
                                    <i class="fas fa-network-wired text-2xl text-slate-300"></i>
                                    <p class="text-xs font-semibold text-slate-700">{{ __('No Network Devices Configured') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('Add a core router or OLT to view inventory.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6. Production-Grade Slide-Over Device Inspector Drawer --}}
    <div x-show="drawer.open" 
         class="fixed inset-0 z-50 overflow-hidden" 
         style="display: none;"
         x-cloak
         @keydown.escape.window="closeDrawer()">
        
        {{-- Backdrop Overlay --}}
        <div x-show="drawer.open"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeDrawer()" 
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"></div>

        {{-- Slide-Over Panel --}}
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
                
                {{-- Drawer Top Header --}}
                <div class="p-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base shadow-sm shrink-0"
                             :class="drawer.iconBg || 'bg-cyan-600 text-white'">
                            <i :class="drawer.iconClass || 'fas fa-server'"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-white truncate" x-text="drawer.title"></h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium"
                                      :class="drawer.status === 'online' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="drawer.status === 'online' ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                                    <span x-text="drawer.status === 'offline' ? i18n.statusOfflineUpper : i18n.statusOnlineUpper"></span>
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 truncate block mt-0.5" x-text="drawer.subtitle || drawer.typeBadge"></span>
                        </div>
                    </div>
                    <button type="button" 
                            @click="closeDrawer()" 
                            class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition flex items-center justify-center cursor-pointer shrink-0 ml-2"
                            title="{{ __('Close Inspector (Esc)') }}">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                {{-- Drawer Body Scrollable Content --}}
                <div class="p-4 space-y-4 overflow-y-auto flex-1 bg-slate-50/50 text-xs">
                    
                    {{-- KPI Metric Cards Grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="stat in drawer.stats" :key="stat.label">
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 shadow-2xs">
                                <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block truncate" x-text="stat.label"></span>
                                <span class="text-sm font-extrabold font-mono leading-tight block mt-0.5 truncate" :class="stat.color" x-text="stat.value"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Channel Health Progress Ratio for PON / OLT --}}
                    <div x-show="drawer.nodeType === 'pon' || drawer.nodeType === 'olt'" class="p-3 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-medium text-slate-700">{{ __('Channel Efficiency & Online Ratio') }}</span>
                            <span class="font-mono font-bold" :class="drawer.onlinePercent >= 80 ? 'text-emerald-600' : 'text-amber-600'" x-text="i18n.percentOnline.replace('__PCT__', drawer.onlinePercent)"></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-emerald-500 h-2 transition-all duration-500" :style="`width: ${drawer.onlinePercent}%`"></div>
                            <div class="bg-rose-400 h-2 transition-all duration-500" :style="`width: ${100 - drawer.onlinePercent}%`"></div>
                        </div>
                    </div>

                    {{-- Technical Specifications Table --}}
                    <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">{{ __('Device Technical Specs') }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ __('Live State') }}</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="(val, key) in drawer.details" :key="key">
                                <div class="px-3 py-2 flex items-center justify-between text-[11px] hover:bg-slate-50/50 transition">
                                    <span class="text-slate-500" x-text="key"></span>
                                    <div class="flex items-center gap-1.5 font-mono font-semibold text-slate-800">
                                        <span x-text="val"></span>
                                        <button type="button" 
                                                x-show="(drawer.copyableKeys && drawer.copyableKeys.includes(key)) || key.includes('IP') || key.includes('MAC')" 
                                                @click="copyToClipboard(val, key)"
                                                class="text-slate-400 hover:text-cyan-600 cursor-pointer"
                                                title="{{ __('Copy to Clipboard') }}">
                                            <i class="far fa-copy text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Connected Subscribers (ONUs) Section --}}
                    <div x-show="drawer.items && drawer.items.length > 0" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ __('Connected Subscribers & ONTs') }}</h4>
                                <span class="text-[10px] text-slate-500 font-normal" x-html="formatShowingDrawerOnus()"></span>
                            </div>
                        </div>

                        {{-- Search and Filter Bar inside Drawer --}}
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            {{-- Live Search Input --}}
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-2.5 top-2.5 text-slate-400 text-[10px]"></i>
                                <input type="text" 
                                       x-model="drawer.searchQuery" 
                                       placeholder="{{ __('Search MAC, Name, Model...') }}" 
                                       class="w-full pl-7 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 shadow-2xs" />
                                <button type="button" 
                                        x-show="drawer.searchQuery" 
                                        @click="drawer.searchQuery = ''" 
                                        class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>

                            {{-- Status Filter Buttons --}}
                            <div class="flex items-center gap-1">
                                <button type="button" 
                                        @click="drawer.statusFilter = 'all'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    {{ __('All') }}
                                </button>
                                <button type="button" 
                                        @click="drawer.statusFilter = 'online'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'online' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    {{ __('Online') }}
                                </button>
                                <button type="button" 
                                        @click="drawer.statusFilter = 'offline'"
                                        class="px-2 py-1 rounded text-[10px] font-medium transition cursor-pointer border"
                                        :class="drawer.statusFilter === 'offline' ? 'bg-rose-600 text-white border-rose-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'">
                                    {{ __('Offline') }}
                                </button>
                            </div>
                        </div>

                        {{-- Subscriber Cards List --}}
                        <div class="space-y-1.5 max-h-72 overflow-y-auto pr-0.5">
                            <template x-for="item in filteredDrawerItems" :key="item.id || item.mac_address">
                                <div class="p-2.5 rounded-lg bg-white border border-slate-200 hover:border-cyan-300 hover:shadow-xs transition space-y-1.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full shrink-0" 
                                                  :class="item.status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-400'"></span>
                                            <span class="font-bold text-slate-900 text-xs truncate" x-text="item.name"></span>
                                            <span x-show="item.pon_name" class="px-1.5 py-0.2 bg-cyan-50 text-cyan-700 rounded text-[9px] font-mono border border-cyan-200/60" x-text="item.pon_name"></span>
                                        </div>

                                        {{-- Optical Rx Power Badge --}}
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold border shrink-0"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-700 border-emerald-200': item.rx_power_dbm !== null && item.rx_power_dbm >= -20,
                                                  'bg-amber-50 text-amber-700 border-amber-200': item.rx_power_dbm !== null && item.rx_power_dbm >= -26 && item.rx_power_dbm < -20,
                                                  'bg-rose-50 text-rose-700 border-rose-200': item.status !== 'online' || (item.rx_power_dbm !== null && item.rx_power_dbm < -26),
                                                  'bg-slate-100 text-slate-500 border-slate-200': item.rx_power_dbm === null
                                              }"
                                              x-text="item.rx_power_dbm !== null ? (item.rx_power_dbm + ' dBm') : (item.status === 'online' ? i18n.online : i18n.los)"></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 font-mono pt-1 border-t border-slate-100">
                                        <div class="flex items-center gap-1 truncate">
                                            <i class="fas fa-fingerprint text-[9px] text-slate-400"></i>
                                            <span class="truncate" x-text="item.mac_address || i18n.noMac"></span>
                                        </div>
                                        <div class="flex items-center justify-end gap-1 text-slate-600">
                                            <i class="fas fa-ruler-horizontal text-[9px] text-slate-400"></i>
                                            <span x-text="item.distance_m ? (item.distance_m + 'm') : '-'"></span>
                                        </div>
                                    </div>

                                    {{-- PPPoE / Details Subline --}}
                                    <div x-show="item.pppoe_username || item.desc" class="flex items-center justify-between text-[10px] text-slate-600 bg-slate-50 px-2 py-1 rounded">
                                        <span class="truncate text-cyan-800 font-medium" x-text="item.pppoe_username ? (i18n.pppoePrefix + item.pppoe_username) : (item.desc || '')"></span>
                                        <a :href="`/admin/network/onu?search=${encodeURIComponent(item.mac_address || item.name)}`" 
                                           class="text-cyan-600 hover:text-cyan-800 font-semibold shrink-0">
                                            {{ __('Diagnose') }} →
                                        </a>
                                    </div>
                                </div>
                            </template>

                            {{-- Zero State for Search Filter --}}
                            <div x-show="filteredDrawerItems.length === 0" class="p-6 text-center text-slate-400 bg-white rounded-lg border border-slate-200">
                                <i class="fas fa-search text-xl text-slate-300 mb-1"></i>
                                <p class="text-xs text-slate-600 font-medium">{{ __('No matching subscribers found') }}</p>
                                <span class="text-[10px] text-slate-400">{{ __('Try adjusting your search keyword or status filter.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Drawer Sticky Footer Actions --}}
                <div class="p-3.5 bg-white border-t border-slate-200 flex items-center justify-between gap-2">
                    <button type="button" 
                            @click="closeDrawer()" 
                            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer">
                        {{ __('Close') }}
                    </button>
                    <a :href="drawer.link || '#'" 
                       class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 text-center">
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                        <span x-text="drawer.linkText || i18n.configureInModule"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function networkMapManager() {
    return {
        i18n: {
            statusOnlineUpper: @js(__('ONLINE')),
            statusOfflineUpper: @js(__('OFFLINE')),
            percentOnline: @js(__(':pct% Online', ['pct' => '__PCT__'])),
            showingDrawerOnus: @js(__('Showing :filtered of :total ONUs', ['filtered' => '__FILTERED__', 'total' => '__TOTAL__'])),
            online: @js(__('Online')),
            los: @js(__('LOS')),
            noMac: @js(__('No MAC')),
            pppoePrefix: @js(__('PPPoE') . ': '),
            configureInModule: @js(__('Configure in Module')),
            coreGatewayRouter: @js(__('Core Gateway Router')),
            connectedOlts: @js(__('Connected OLTs')),
            totalOnus: @js(__('Total ONUs')),
            onlineActive: @js(__('Online Active')),
            cpuLoad: @js(__('CPU Load')),
            deviceName: @js(__('Device Name')),
            managementIp: @js(__('Management IP')),
            hardwareModel: @js(__('Hardware Model')),
            systemUptime: @js(__('System Uptime')),
            running: @js(__('Running')),
            devicesCount: @js(__(':count Devices', ['count' => '__COUNT__'])),
            operationalStatus: @js(__('Operational Status')),
            manageRouterInModule: @js(__('Manage Router in Module')),
            distributionTier: @js(__('Distribution Tier')),
            offlineLos: @js(__('Offline / LOS')),
            ponPorts: @js(__('PON Ports')),
            oltName: @js(__('OLT Name')),
            brandAndModel: @js(__('Brand & Model')),
            uplinkGateway: @js(__('Uplink Gateway')),
            coreRouter: @js(__('Core Router')),
            ponCapacity: @js(__('PON Capacity')),
            activePortsCount: @js(__(':count Active Ports', ['count' => '__COUNT__'])),
            inspectOltAndPons: @js(__('Inspect OLT & PONs')),
            opticalDistributionChannel: @js(__('Optical Distribution Channel')),
            ponPort: @js(__('PON Port')),
            efficiency: @js(__('Efficiency')),
            channelName: @js(__('Channel Name')),
            parentOlt: @js(__('Parent OLT')),
            oltDistribution: @js(__('OLT Distribution')),
            oltManagementIp: @js(__('OLT Management IP')),
            totalSubscribers: @js(__('Total Subscribers')),
            endpointsCount: @js(__(':count Endpoints', ['count' => '__COUNT__'])),
            activePercent: @js(__(':count Active (:pct%)', ['count' => '__COUNT__', 'pct' => '__PCT__'])),
            portTechnology: @js(__('Port Technology')),
            viewAllChannelOnus: @js(__('View All Channel ONUs')),
            valueLabel: @js(__('Value')),
            copiedToClipboard: @js(__(':label copied to clipboard!', ['label' => '__LABEL__'])),
            failedToCopy: @js(__('Failed to copy')),
            topologyRefreshed: @js(__('Network topology data refreshed.'))
        },

        activeTab: 'tree',
        isRefreshing: false,
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
            iconBg: 'bg-cyan-600 text-white',
            iconClass: 'fas fa-server',
            stats: [],
            onlinePercent: 100,
            details: {},
            copyableKeys: [],
            items: [],
            searchQuery: '',
            statusFilter: 'all',
            link: '#',
            linkText: ''
        },

        formatShowingDrawerOnus() {
            const filtered = `<span class="font-bold text-slate-800">${this.filteredDrawerItems.length}</span>`;
            const total = `<span class="font-bold text-slate-800">${(this.drawer.items || []).length}</span>`;
            return this.i18n.showingDrawerOnus
                .replace('__FILTERED__', filtered)
                .replace('__TOTAL__', total);
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
                this.drawer.subtitle = this.i18n.coreGatewayRouter + ' • ' + (data.model || 'MikroTik CCR/RB');
                this.drawer.status = data.status || 'online';
                this.drawer.iconBg = 'bg-blue-600 text-white';
                this.drawer.iconClass = 'fas fa-server';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: this.i18n.connectedOlts, value: (data.olts ? data.olts.length : 0), color: 'text-blue-600' },
                    { label: this.i18n.totalOnus, value: totalOnus, color: 'text-slate-800' },
                    { label: this.i18n.onlineActive, value: onlineOnus, color: 'text-emerald-600' },
                    { label: this.i18n.cpuLoad, value: (data.cpu_load || 12) + '%', color: 'text-amber-600' }
                ];
                this.drawer.copyableKeys = [this.i18n.managementIp];
                this.drawer.details = {
                    [this.i18n.deviceName]: data.name,
                    [this.i18n.managementIp]: data.ip_address,
                    [this.i18n.hardwareModel]: data.model || 'MikroTik RouterOS',
                    [this.i18n.cpuLoad]: (data.cpu_load || 12) + '%',
                    [this.i18n.systemUptime]: data.uptime || this.i18n.running,
                    [this.i18n.connectedOlts]: this.i18n.devicesCount.replace('__COUNT__', (data.olts ? data.olts.length : 0)),
                    [this.i18n.operationalStatus]: (data.status === 'offline' ? this.i18n.statusOfflineUpper : this.i18n.statusOnlineUpper)
                };
                this.drawer.items = allRouterOnus;
                this.drawer.link = `/admin/network/mikrotik/${data.id}`;
                this.drawer.linkText = this.i18n.manageRouterInModule;

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
                this.drawer.subtitle = (data.brand || 'OLT') + ' ' + (data.model || '') + ' • ' + this.i18n.distributionTier;
                this.drawer.status = data.status || 'online';
                this.drawer.iconBg = 'bg-purple-600 text-white';
                this.drawer.iconClass = 'fas fa-network-wired';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: this.i18n.totalOnus, value: totalOnus, color: 'text-slate-800' },
                    { label: this.i18n.onlineActive, value: onlineOnus, color: 'text-emerald-600' },
                    { label: this.i18n.offlineLos, value: offlineOnus, color: 'text-rose-600' },
                    { label: this.i18n.ponPorts, value: data.total_pon_ports || (data.pon_ports ? data.pon_ports.length : 8), color: 'text-purple-600' }
                ];
                this.drawer.copyableKeys = [this.i18n.managementIp];
                this.drawer.details = {
                    [this.i18n.oltName]: data.name,
                    [this.i18n.managementIp]: data.ip_address,
                    [this.i18n.brandAndModel]: (data.brand || '') + ' ' + (data.model || ''),
                    [this.i18n.uplinkGateway]: (data.router ? data.router.name : this.i18n.coreRouter),
                    [this.i18n.ponCapacity]: this.i18n.activePortsCount.replace('__COUNT__', (data.total_pon_ports || (data.pon_ports ? data.pon_ports.length : 8))),
                    [this.i18n.operationalStatus]: (data.status === 'offline' ? this.i18n.statusOfflineUpper : this.i18n.statusOnlineUpper)
                };
                this.drawer.items = allOltOnus;
                this.drawer.link = `/admin/network/olt/${data.id}`;
                this.drawer.linkText = this.i18n.inspectOltAndPons;

            } else if (type === 'pon') {
                const totalOnus = data.total_onus || (data.onus ? data.onus.length : 0);
                const onlineOnus = data.online_onus !== undefined ? data.online_onus : (data.onus ? data.onus.filter(o => o.status === 'online').length : 0);
                const offlineOnus = data.offline_onus !== undefined ? data.offline_onus : (totalOnus - onlineOnus);
                const onlineRatio = totalOnus > 0 ? Math.round((onlineOnus / totalOnus) * 100) : 0;

                this.drawer.title = (parentData?.name ? (parentData.name + ' - ') : '') + data.name;
                this.drawer.subtitle = this.i18n.opticalDistributionChannel + ' • ' + (parentData?.brand || this.i18n.ponPort);
                this.drawer.status = data.status || (onlineOnus > 0 ? 'online' : (totalOnus === 0 ? 'online' : 'offline'));
                this.drawer.iconBg = 'bg-cyan-600 text-white';
                this.drawer.iconClass = 'fas fa-ethernet';
                this.drawer.onlinePercent = onlineRatio;
                this.drawer.stats = [
                    { label: this.i18n.totalOnus, value: totalOnus, color: 'text-slate-800' },
                    { label: this.i18n.onlineActive, value: onlineOnus, color: 'text-emerald-600' },
                    { label: this.i18n.offlineLos, value: offlineOnus, color: 'text-rose-600' },
                    { label: this.i18n.efficiency, value: onlineRatio + '%', color: 'text-cyan-600' }
                ];
                this.drawer.copyableKeys = [this.i18n.oltManagementIp];
                this.drawer.details = {
                    [this.i18n.channelName]: data.name,
                    [this.i18n.parentOlt]: (parentData?.name || this.i18n.oltDistribution),
                    [this.i18n.oltManagementIp]: (parentData?.ip_address || '-'),
                    [this.i18n.totalSubscribers]: this.i18n.endpointsCount.replace('__COUNT__', totalOnus),
                    [this.i18n.onlineActive]: this.i18n.activePercent.replace('__COUNT__', onlineOnus).replace('__PCT__', onlineRatio),
                    [this.i18n.portTechnology]: (parentData?.brand?.includes('EPON') ? 'EPON 1.25G' : 'GPON 2.5G / EPON')
                };
                this.drawer.items = data.onus || [];
                this.drawer.link = `/admin/network/onu?search=${encodeURIComponent(data.name)}`;
                this.drawer.linkText = this.i18n.viewAllChannelOnus;
            }
        },

        closeDrawer() {
            this.drawer.open = false;
        },

        copyToClipboard(text, label = null) {
            if (!text) return;
            const displayLabel = label || this.i18n.valueLabel;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(this.i18n.copiedToClipboard.replace('__LABEL__', displayLabel), 'success');
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
                    this.showToast(this.i18n.copiedToClipboard.replace('__LABEL__', displayLabel), 'success');
                } catch (err) {
                    this.showToast(this.i18n.failedToCopy, 'error');
                }
                textArea.remove();
            }
        },

        async refreshData() {
            this.isRefreshing = true;
            try {
                setTimeout(() => {
                    this.isRefreshing = false;
                    this.showToast(this.i18n.topologyRefreshed, 'success');
                    window.location.reload();
                }, 700);
            } catch (e) {
                this.isRefreshing = false;
            }
        }
    };
}
</script>
@endpush
