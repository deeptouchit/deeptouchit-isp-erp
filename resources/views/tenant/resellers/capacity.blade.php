@extends('tenant.layouts.app')

@section('title', 'Gateway Capacity - ' . ($tenant->company_name ?? $tenant->name))

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
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Gateway Capacity &amp; Distribution</h1>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">
                {{ $routers->total() }} Routers
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-network-wired text-xs"></i>
                <span>Bandwidth Allocation</span>
            </a>
            <a href="{{ route('tenant.resellers.bandwidth-plans') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-layer-group text-[11px] text-cyan-600"></i>
                <span>Rate Plans</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Allocated Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Allocated</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">
                    {{ $totalAllocatedMbps >= 1000 ? round($totalAllocatedMbps/1000, 2) . ' Gbps' : round($totalAllocatedMbps, 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Current Live Usage -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Live Traffic</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">
                    {{ $totalCurrentUsage >= 1000 ? round($totalCurrentUsage/1000, 2) . ' Gbps' : round($totalCurrentUsage, 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-chart-area"></i>
            </div>
        </div>

        <!-- Card 3: Wholesale MRR -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Wholesale MRR</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($totalWholesaleMRR)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Card 4: Avg Gateway Load -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Avg Load</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ $avgUtilization }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Card 5: Core Gateways Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Core Routers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalRoutersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 6: Online Gateways -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Online Routers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($onlineRoutersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Uniform h-[34px] - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.resellers.capacity') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            
            <!-- Intelligent Live Search Box -->
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors pointer-events-none"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="search" 
                       name="search" 
                       x-model="searchQuery"
                       autocomplete="off"
                       placeholder="Search gateway name, IP, model..." 
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
                    class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-40 flex-shrink-0">
                <option value="">All Statuses</option>
                <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
            </select>

            <!-- Pagination Selector (per_page) -->
            <select name="per_page" 
                    onchange="this.form.submit()" 
                    class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-28 flex-shrink-0">
                <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / page</option>
                <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / page</option>
                <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>30 / page</option>
                <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / page</option>
                <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / page</option>
            </select>

            <!-- Filter & Reset Buttons (Strict Sequence - AGENTS.md Rule 2.C) -->
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.capacity') }}" 
                   class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
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
                        <th class="text-center font-semibold">Core Gateway Router</th>
                        <th class="text-center font-semibold">IP Address</th>
                        <th class="text-center font-semibold">Connected Partners</th>
                        <th class="w-28 text-center font-semibold">Allocated Capacity</th>
                        <th class="w-28 text-center font-semibold">Live Traffic</th>
                        <th class="w-24 text-center font-semibold">Load</th>
                        <th class="w-28 text-center font-semibold">Wholesale MRR</th>
                        <th class="w-24 text-center font-semibold">Status</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
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

                            <!-- 2. Core Gateway Router (Left Align) -->
                            <td class="text-left font-semibold text-cyan-800 whitespace-nowrap">
                                <button type="button" 
                                        @click="viewRouterBreakdown({{ Js::from($rtr) }}, {{ Js::from($m['resellers']) }})" 
                                        class="hover:underline text-cyan-700 hover:text-cyan-800 font-semibold cursor-pointer text-left truncate max-w-[200px] inline-block align-middle">
                                    {{ $rtr->name }}
                                </button>
                            </td>

                            <!-- 3. IP Address (Center Align, Mono) -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $rtr->ip_address }}
                            </td>

                            <!-- 4. Connected Resellers (Center Align) -->
                            <td class="text-center font-mono font-medium text-cyan-800 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-cyan-50 border border-cyan-200 text-xs">
                                    {{ $m['reseller_count'] }} Partners
                                </span>
                            </td>

                            <!-- 5. Allocated Capacity (Right Align, Mono) -->
                            <td class="text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ $m['total_allocated'] >= 1000 ? round($m['total_allocated']/1000, 2) . ' Gbps' : round($m['total_allocated'], 0) . ' Mbps' }}
                            </td>

                            <!-- 6. Current Live Usage (Right Align, Mono) -->
                            <td class="text-right font-mono text-blue-700 font-bold whitespace-nowrap">
                                {{ $m['current_usage'] >= 1000 ? round($m['current_usage']/1000, 2) . ' Gbps' : round($m['current_usage'], 0) . ' Mbps' }}
                            </td>

                            <!-- 7. Gateway Load (Center Align) -->
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

                            <!-- 8. Wholesale MRR (Right Align, Currency Standard) -->
                            <td class="text-right font-mono font-bold text-cyan-800 whitespace-nowrap">
                                @currency($m['mrr'])
                            </td>

                            <!-- 9. Status (Center Align, Single-Line Badge) -->
                            <td class="text-center whitespace-nowrap">
                                @if($rtr->status === 'online')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Offline</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Action (3-Dot) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($rtr) }}, {{ Js::from($m['resellers']) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-cyan-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="Actions">
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
                                    <h3 class="text-xs font-semibold text-slate-800">No Gateway Routers Found</h3>
                                    <p class="text-[11px] text-slate-500">Configure MikroTik Routers under Network section to view wholesale distribution capacity.</p>
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
                    Showing <span class="font-bold text-slate-800">{{ $routers->firstItem() }}</span> to <span class="font-bold text-slate-800">{{ $routers->lastItem() }}</span> of <span class="font-bold text-slate-800">{{ $routers->total() }}</span> entries
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
                <span>View Reseller List</span>
            </button>
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-plus text-cyan-600 w-3.5 text-[11px]"></i>
                <span>Allocate Bandwidth</span>
            </a>
        </div>

        <div class="py-0.5">
            <a :href="`/admin/network/mikrotik/${activeMenu?.id}/edit`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-sliders text-slate-500 w-3.5 text-[11px]"></i>
                <span>Router Configuration</span>
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
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
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
                                <th class="text-center font-semibold">Reseller Partner</th>
                                <th class="text-center font-semibold">Allocated</th>
                                <th class="text-center font-semibold">Live Traffic</th>
                                <th class="text-center font-semibold">Load</th>
                                <th class="text-center font-semibold">Monthly Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in breakdownModal.resellers" :key="item.id">
                                <tr>
                                    <td class="text-left font-medium text-slate-800 whitespace-nowrap" x-text="item.reseller?.name || 'N/A'"></td>
                                    <td class="text-right font-mono font-bold text-cyan-800 whitespace-nowrap" x-text="item.total_bandwidth_mbps + ' Mbps'"></td>
                                    <td class="text-right font-mono text-blue-700 whitespace-nowrap" x-text="item.current_usage_mbps + ' Mbps'"></td>
                                    <td class="text-center font-mono whitespace-nowrap" x-text="item.utilization_percent + '%'"></td>
                                    <td class="text-right font-mono text-slate-800 whitespace-nowrap" x-text="'{{ $currencySymbol ?? '৳' }} ' + Number(item.monthly_bill_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                            <template x-if="!breakdownModal.resellers || breakdownModal.resellers.length === 0">
                                <tr>
                                    <td colspan="5" class="text-center py-6 text-slate-400">
                                        No resellers currently provisioned on this gateway router.
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
                    Close
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

        breakdownModal: {
            open: false,
            router: null,
            resellers: []
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
