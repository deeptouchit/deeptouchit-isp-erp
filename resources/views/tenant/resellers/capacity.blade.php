@extends('tenant.layouts.app')

@section('title', __('Gateway Capacity') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="gatewayCapacityManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">{{ __('Gateway Capacity & Distribution') }}</h1>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">
                {{ __(':count Routers', ['count' => $routers->total()]) }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-network-wired text-xs"></i>
                <span>{{ __('Bandwidth Allocation') }}</span>
            </a>
            <a href="{{ route('tenant.resellers.bandwidth-plans') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-layer-group text-[11px] text-cyan-600"></i>
                <span>{{ __('Rate Plans') }}</span>
            </a>
            <a href="{{ route('tenant.network.mikrotik.create') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs text-slate-600"></i>
                <span>{{ __('Add Router') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Allocated Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Allocated') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">
                    {{ $totalAllocatedMbps >= 1000 ? round($totalAllocatedMbps/1000, 2) . ' ' . __('Gbps') : round($totalAllocatedMbps, 0) . ' ' . __('Mbps') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Current Live Usage -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Live Traffic') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">
                    {{ $totalCurrentUsage >= 1000 ? round($totalCurrentUsage/1000, 2) . ' ' . __('Gbps') : round($totalCurrentUsage, 0) . ' ' . __('Mbps') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-chart-area"></i>
            </div>
        </div>

        <!-- Card 3: Wholesale MRR -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Wholesale MRR') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($totalWholesaleMRR)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Card 4: Avg Gateway Load -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Avg Load') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ $avgUtilization }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Card 5: Core Gateways Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Core Routers') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalRoutersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 6: Online Gateways -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Online Routers') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($onlineRoutersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.resellers.capacity') }}" class="space-y-2.5">
            
            <!-- Tier 1: Primary Filter Bar (Uniform h-[34px] Row) -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors pointer-events-none"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="search" 
                           name="search" 
                           x-model="searchQuery"
                           autocomplete="off"
                           placeholder="{{ __('Search gateway name, IP, model...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 text-xs cursor-pointer p-0.5">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <!-- Status Filter -->
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-36 flex-shrink-0">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>{{ __('Online') }}</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>{{ __('Offline') }}</option>
                </select>

                <!-- Pagination Selector (per_page) -->
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-28 flex-shrink-0">
                    <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / {{ __('page') }}</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / {{ __('page') }}</option>
                    <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>30 / {{ __('page') }}</option>
                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / {{ __('page') }}</option>
                    <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / {{ __('page') }}</option>
                </select>

                <!-- Accordion Toggle Button for Tier-2 Secondary Filters -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <i class="fas fa-sliders text-slate-500 text-[11px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] ml-0.5 transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Filter & Reset Buttons (Strict Sequence - AGENTS.md Rule 2.C) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.resellers.capacity') }}" 
                       class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filter Drawer (Expandable) -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- Secondary Filter: Load Level -->
                <div class="w-full sm:w-auto min-w-[200px]">
                    <select name="load" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                        <option value="">{{ __('All Load Levels') }}</option>
                        <option value="high" {{ request('load') === 'high' ? 'selected' : '' }}>{{ __('High Load (≥ 85%)') }}</option>
                        <option value="moderate" {{ request('load') === 'moderate' ? 'selected' : '' }}>{{ __('Moderate Load (50% ~ 84%)') }}</option>
                        <option value="low" {{ request('load') === 'low' ? 'selected' : '' }}>{{ __('Low Load (< 50%)') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="w-24 text-center font-semibold">{{ __('Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Core Gateway Router') }}</th>
                        <th class="w-32 text-center font-semibold">{{ __('IP Address') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Connected Partners') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Allocated Capacity') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Live Traffic') }}</th>
                        <th class="w-24 text-center font-semibold">{{ __('Load') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Wholesale MRR') }}</th>
                        <th class="w-10 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routers as $index => $rtr)
                        @php
                            $m = $routerMetrics[$rtr->id] ?? [
                                'reseller_count' => 0,
                                'total_allocated' => 0,
                                'current_usage' => 0,
                                'utilization' => 0,
                                'mrr' => 0,
                                'resellers' => collect(),
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ ($routers->currentPage() - 1) * $routers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Status (Center Align, Single-Line Badge) -->
                            <td class="text-center whitespace-nowrap">
                                @if($rtr->status === 'online')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ __('Online') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>{{ __('Offline') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Core Gateway Router (Left Align) -->
                            <td class="text-left font-semibold text-cyan-800 whitespace-nowrap">
                                <button type="button" 
                                        @click="viewRouterBreakdown({{ Js::from($rtr) }}, {{ Js::from($m['resellers']) }})" 
                                        class="hover:underline text-cyan-700 hover:text-cyan-800 font-semibold cursor-pointer text-left truncate max-w-[200px] inline-block align-middle">
                                    {{ $rtr->name }}
                                </button>
                            </td>

                            <!-- 4. IP Address (Center Align, Mono) -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $rtr->ip_address }}
                            </td>

                            <!-- 5. Connected Resellers (Center Align) -->
                            <td class="text-center font-mono font-medium text-cyan-800 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-cyan-50 border border-cyan-200 text-xs">
                                    {{ __(':count Partners', ['count' => $m['reseller_count']]) }}
                                </span>
                            </td>

                            <!-- 6. Allocated Capacity (Right Align, Mono) -->
                            <td class="text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ $m['total_allocated'] >= 1000 ? round($m['total_allocated']/1000, 2) . ' ' . __('Gbps') : round($m['total_allocated'], 0) . ' ' . __('Mbps') }}
                            </td>

                            <!-- 7. Current Live Usage (Right Align, Mono) -->
                            <td class="text-right font-mono text-blue-700 font-bold whitespace-nowrap">
                                {{ $m['current_usage'] >= 1000 ? round($m['current_usage']/1000, 2) . ' ' . __('Gbps') : round($m['current_usage'], 0) . ' ' . __('Mbps') }}
                            </td>

                            <!-- 8. Gateway Load (Center Align) -->
                            <td class="text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 font-mono text-xs">
                                    <div class="w-12 h-1.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                        <div class="h-full rounded-full {{ $m['utilization'] >= 85 ? 'bg-rose-500' : ($m['utilization'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                             style="width: {{ min(100, $m['utilization']) }}%"></div>
                                    </div>
                                    <span class="{{ $m['utilization'] >= 85 ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                        {{ $m['utilization'] }}%
                                    </span>
                                </div>
                            </td>

                            <!-- 9. Wholesale MRR (Right Align, Currency Standard) -->
                            <td class="text-right font-mono font-bold text-cyan-800 whitespace-nowrap">
                                @currency($m['mrr'])
                            </td>

                            <!-- 10. Action (3-Dot) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($rtr) }}, {{ Js::from($m['resellers']) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-cyan-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">{{ __('No Gateway Routers Found') }}</h3>
                                    <p class="text-[11px] text-slate-500">{{ __('Configure MikroTik Routers under Network section to view wholesale distribution capacity.') }}</p>
                                    <div class="pt-1">
                                        <a href="{{ route('tenant.network.mikrotik.create') }}" 
                                           class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-xs"></i>
                                            <span>{{ __('Add Core Router') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($routers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    {{ __('Showing :first to :last of :total gateways', ['first' => $routers->firstItem() ?? 0, 'last' => $routers->lastItem() ?? 0, 'total' => $routers->total()]) }}
                </div>
                <div>
                    {{ $routers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; const list = activeResellers; activeMenu = null; viewRouterBreakdown(r, list)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-list text-cyan-600 w-3.5 text-[11px]"></i>
                <span>{{ __('View Reseller List') }}</span>
            </button>
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-plus text-cyan-600 w-3.5 text-[11px]"></i>
                <span>{{ __('Allocate Bandwidth') }}</span>
            </a>
        </div>

        <div class="py-0.5">
            <a :href="`/admin/network/mikrotik/${activeMenu?.id}/edit`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-sliders text-slate-500 w-3.5 text-[11px]"></i>
                <span>{{ __('Router Configuration') }}</span>
            </a>
        </div>
    </div>

    <!-- 6. Modals (Soft Natural Modals - AGENTS.md Rule 3) -->
    <!-- Modal 1: Router Reseller Allocation Breakdown Modal -->
    <div x-show="breakdownModal.open" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="breakdownModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-slate-800" x-text="breakdownModal.router?.name"></h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-200 text-slate-700 font-semibold" x-text="breakdownModal.router?.ip_address"></span>
                    </div>
                </div>
                <button type="button" @click="breakdownModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Table -->
            <div class="p-4 space-y-3 text-xs">
                <div class="overflow-x-auto max-h-72 overflow-y-auto border border-slate-200 rounded-lg">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="text-center font-semibold">{{ __('Reseller Partner') }}</th>
                                <th class="text-center font-semibold">{{ __('Allocated') }}</th>
                                <th class="text-center font-semibold">{{ __('Live Traffic') }}</th>
                                <th class="text-center font-semibold">{{ __('Load') }}</th>
                                <th class="text-center font-semibold">{{ __('Monthly Bill') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in breakdownModal.resellers" :key="item.id">
                                <tr>
                                    <td class="text-left font-medium text-slate-800 whitespace-nowrap" x-text="item.reseller?.name || i18n.na"></td>
                                    <td class="text-right font-mono font-bold text-cyan-800 whitespace-nowrap" x-text="item.total_bandwidth_mbps + ' ' + i18n.mbps"></td>
                                    <td class="text-right font-mono text-blue-700 whitespace-nowrap" x-text="item.current_usage_mbps + ' ' + i18n.mbps"></td>
                                    <td class="text-center font-mono whitespace-nowrap" x-text="item.utilization_percent + '%'"></td>
                                    <td class="text-right font-mono text-slate-800 whitespace-nowrap" x-text="formatCurrency(item.monthly_bill_amount)"></td>
                                </tr>
                            </template>
                            <template x-if="!breakdownModal.resellers || breakdownModal.resellers.length === 0">
                                <tr>
                                    <td colspan="5" class="text-center py-6 text-slate-400">
                                        {{ __('No resellers currently provisioned on this gateway router.') }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="breakdownModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function gatewayCapacityManager() {
    return {
        activeMenu: null,
        activeResellers: [],
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        currencySymbol: @json($currencySymbol ?? '৳'),
        i18n: {
            na: @json(__('N/A')),
            mbps: @json(__('Mbps'))
        },

        breakdownModal: {
            open: false,
            router: null,
            resellers: []
        },

        formatCurrency(val, decimals = 2) {
            const num = Number(val || 0);
            return `${this.currencySymbol} ${num.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}`;
        },

        toggleMenu(item, resellersList, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            this.activeResellers = resellersList || [];
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 140;
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

        viewRouterBreakdown(router, resellersList) {
            this.breakdownModal.router = router;
            this.breakdownModal.resellers = resellersList || [];
            this.breakdownModal.open = true;
        }
    }
}
</script>
@endpush
