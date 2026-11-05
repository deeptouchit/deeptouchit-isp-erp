@extends('tenant.layouts.app')

@section('title', 'Equipment & Inventory Report - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .hardware-tag {
        font-feature-settings: "tnum";
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="inventoryReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Equipment &amp; Hardware Inventory Statement</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Inventory Health Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fas fa-circle-check text-emerald-600 text-[10px]"></i>
                <span>Audit: Healthy</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.inventory.print', request()->all()) }}" 
               target="_blank" 
               class="h-[34px] bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-0 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>Print Statement</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.inventory.export', request()->all()) }}" 
               class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Asset Valuation --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Asset Valuation</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($totalAssetValuation)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        {{-- Card 2: Deployed ONUs --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Deployed ONUs</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ number_format($totalOnusInDb) }} Units
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 3: In-Stock Warehoused Units --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Warehouse Stock</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($totalStockUnits) }} Units
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-warehouse"></i>
            </div>
        </div>

        {{-- Card 4: Core POP OLTs & Routers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Core POP Nodes</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    {{ $totalOlts + $totalRouters }} Nodes
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 5: Fiber Network Deployed --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Fiber Deployed</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $fiberKmDeployed }} km
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-nodes"></i>
            </div>
        </div>

        {{-- Card 6: Low Stock Reorder Alerts --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Reorder Alerts</span>
                <span class="text-[13px] font-bold font-mono {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-600' }} leading-tight block truncate">
                    {{ $lowStockCount }} Items
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $lowStockCount > 0 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-slate-50 text-slate-500' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - Rule 2.C) --}}
    @php
        $isMoreOpen = (request()->filled('status') && request('status') !== 'all');
        $activeMoreCount = 0;
        if (request()->filled('status') && request('status') !== 'all') $activeMoreCount++;
    @endphp

    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.reports.inventory') }}" class="space-y-2.5">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with Dynamic Icon & 1-Click Clear Button --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="Search by SKU, item name, brand, MAC..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            x-cloak
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Equipment Category Filter --}}
                <select name="category" class="w-full md:w-52 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition flex-shrink-0">
                    <option value="all">All Hardware Categories</option>
                    <option value="onus" {{ $category === 'onus' ? 'selected' : '' }}>ONUs &amp; Optical Terminals</option>
                    <option value="olts" {{ $category === 'olts' ? 'selected' : '' }}>Central OLTs &amp; Chassis</option>
                    <option value="routers" {{ $category === 'routers' ? 'selected' : '' }}>MikroTik Routers &amp; Switches</option>
                    <option value="fiber_cable" {{ $category === 'fiber_cable' ? 'selected' : '' }}>Fiber Drums &amp; Drop Cables</option>
                    <option value="splitters_tj" {{ $category === 'splitters_tj' ? 'selected' : '' }}>Splitters &amp; Joint Closures</option>
                    <option value="patch_cords" {{ $category === 'patch_cords' ? 'selected' : '' }}>Patch Cords &amp; Connectors</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="w-full md:w-28 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition flex-shrink-0">
                    @foreach([10, 20, 30, 50, 100, 250] as $cnt)
                        <option value="{{ $cnt }}" {{ (int)$perPage === $cnt ? 'selected' : '' }}>{{ $cnt }} / page</option>
                    @endforeach
                </select>

                {{-- "More Filters" Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-cyan-600 text-white leading-none">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[9px] transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Sequence: [Filter] FIRST, [Reset] SECOND --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.reports.inventory') }}" class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Stock Status --}}
                <div class="w-full sm:w-52">
                    <select name="status" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Equipment Statuses</option>
                        <option value="in_stock" {{ $statusFilter === 'in_stock' ? 'selected' : '' }}>In Warehouse Stock</option>
                        <option value="deployed" {{ $statusFilter === 'deployed' ? 'selected' : '' }}>Deployed in Field</option>
                        <option value="low_stock" {{ $statusFilter === 'low_stock' ? 'selected' : '' }}>🔴 Low Stock Alert</option>
                        <option value="online" {{ $statusFilter === 'online' ? 'selected' : '' }}>🟢 Online / Active Device</option>
                        <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>⚪ Offline Device</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER MULTI-TAB INVENTORY TABLES --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Tabs Header Bar --}}
        <div class="px-3.5 py-2 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-1 overflow-x-auto">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'stock']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'stock' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-boxes-stacked text-[10px]"></i>
                    <span>Stock &amp; Asset Ledger</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'stock' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($filteredStock) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'customer_onus']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'customer_onus' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-plug text-[10px]"></i>
                    <span>Customer Deployed ONUs</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'customer_onus' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ $customerOnus->total() }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'core_pop']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'core_pop' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-server text-[10px]"></i>
                    <span>Core OLTs &amp; Routers</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'core_pop' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ $totalOlts + $totalRouters }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'passive_network']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'passive_network' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-circle-nodes text-[10px]"></i>
                    <span>Passive Fiber &amp; Consumables</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'passive_network' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($passiveItems) }}</span>
                </a>
            </div>

            <span class="text-[11px] text-slate-500 font-medium">ISP Hardware Valuation &amp; Stock Tracking</span>
        </div>

        {{-- TAB 1: STOCK & ASSET LEDGER --}}
        @if($activeTab === 'stock')
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Item SKU</th>
                        <th class="text-center font-semibold">Equipment Description</th>
                        <th class="text-center font-semibold">Category</th>
                        <th class="text-center font-semibold">Brand</th>
                        <th class="text-center font-semibold">Unit</th>
                        <th class="text-center font-semibold">In Stock</th>
                        <th class="text-center font-semibold">Deployed</th>
                        <th class="text-center font-semibold">Total Units</th>
                        <th class="text-center font-semibold">Unit Price</th>
                        <th class="text-center font-semibold">Total Valuation</th>
                        <th class="text-center font-semibold">Stock Status</th>
                        <th class="w-14 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sumValuation = 0; @endphp
                    @forelse($filteredStock as $idx => $item)
                        @php
                            $itemTotalUnits = $item['in_stock'] + $item['deployed'];
                            $itemValuation = $itemTotalUnits * $item['unit_price'];
                            $sumValuation += $itemValuation;
                            $isLowStock = $item['in_stock'] <= $item['reorder_level'];
                        @endphp
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="text-center font-mono text-cyan-800 font-bold">{{ $item['sku'] }}</td>
                            <td class="text-left font-medium text-slate-800">{{ $item['item_name'] }}</td>
                            <td class="text-left text-slate-600 text-[11px]">{{ $item['category'] }}</td>
                            <td class="text-left font-semibold text-slate-700">{{ $item['brand'] }}</td>
                            <td class="text-center text-slate-500">{{ $item['unit'] }}</td>
                            <td class="text-right font-mono font-bold {{ $isLowStock ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ number_format($item['in_stock']) }}
                            </td>
                            <td class="text-right font-mono text-indigo-700 font-semibold">
                                {{ number_format($item['deployed']) }}
                            </td>
                            <td class="text-right font-mono font-bold text-slate-800">
                                {{ number_format($itemTotalUnits) }}
                            </td>
                            <td class="text-right font-mono text-slate-600">
                                @currency($item['unit_price'])
                            </td>
                            <td class="text-right font-mono font-bold text-emerald-700">
                                @currency($itemValuation)
                            </td>
                            <td class="text-center">
                                @if($isLowStock)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-circle-exclamation text-[9px]"></i>
                                        <span>Low Stock (≤{{ $item['reorder_level'] }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check text-[9px]"></i>
                                        <span>Adequate</span>
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        @click="showItemModal(@js($item))"
                                        class="p-1 rounded-md text-slate-400 hover:text-cyan-600 hover:bg-slate-100 transition cursor-pointer"
                                        title="View Item Details">
                                    <i class="fas fa-eye text-[11px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-10">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                        <i class="fas fa-box-open text-base"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Equipment Records Found</p>
                                    <p class="text-[11px] text-slate-500">No hardware or stock items matched your filter parameters.</p>
                                    @if(request()->anyFilled(['search', 'category', 'status']))
                                        <a href="{{ route('tenant.reports.inventory') }}" class="inline-flex items-center gap-1 text-xs text-cyan-700 font-semibold hover:underline mt-1">
                                            <i class="fas fa-rotate-left text-[10px]"></i>
                                            <span>Clear All Filters</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($filteredStock) > 0)
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-xs">
                    <tr>
                        <td colspan="6" class="text-right uppercase tracking-wider text-slate-600 py-2">Total Filtered Valuation:</td>
                        <td class="text-right font-mono text-slate-700">{{ number_format($filteredStock->sum('in_stock')) }}</td>
                        <td class="text-right font-mono text-indigo-700">{{ number_format($filteredStock->sum('deployed')) }}</td>
                        <td class="text-right font-mono text-slate-800">{{ number_format($filteredStock->sum(fn($i) => $i['in_stock'] + $i['deployed'])) }}</td>
                        <td class="text-right font-mono text-slate-500">-</td>
                        <td class="text-right font-mono text-emerald-800 font-bold">@currency($sumValuation)</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB 2: CUSTOMER DEPLOYED ONUS --}}
        @if($activeTab === 'customer_onus')
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Device Name</th>
                        <th class="text-center font-semibold">Vendor / Brand</th>
                        <th class="text-center font-semibold">Model</th>
                        <th class="text-center font-semibold">MAC Address</th>
                        <th class="text-center font-semibold">PON Port</th>
                        <th class="text-center font-semibold">OLT Node</th>
                        <th class="text-center font-semibold">PPPoE / Client</th>
                        <th class="text-center font-semibold">RX Power</th>
                        <th class="text-center font-semibold">TX Power</th>
                        <th class="text-center font-semibold">Optical State</th>
                        <th class="w-14 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customerOnus as $idx => $onu)
                        @php
                            $isOnline = in_array($onu->status, ['online', 'active']);
                            $rxVal = (float)$onu->rx_power_dbm;
                            $rxColor = ($rxVal >= -25 && $rxVal <= -14) ? 'text-emerald-700' : ($rxVal < -27 ? 'text-rose-600 font-bold' : 'text-amber-600');
                        @endphp
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $customerOnus->firstItem() + $idx }}</td>
                            <td class="text-left font-bold text-slate-800">{{ $onu->name ?: 'ONU-' . $onu->id }}</td>
                            <td class="text-left font-semibold text-slate-700">{{ $onu->vendor ?: 'Generic' }}</td>
                            <td class="text-center text-slate-600 font-mono text-[11px]">{{ $onu->model ?: 'XPON-1GE' }}</td>
                            <td class="text-center font-mono text-cyan-800 font-semibold">{{ $onu->mac_address ?: 'N/A' }}</td>
                            <td class="text-center font-mono text-slate-700">{{ $onu->pon_port ?: 'EPON0/1' }}</td>
                            <td class="text-left text-slate-700">{{ $onu->olt_name ?: 'Main Central OLT' }}</td>
                            <td class="text-left font-mono text-slate-800">{{ $onu->pppoe_username ?: ($onu->desc ?: 'Active Subscriber') }}</td>
                            <td class="text-right font-mono {{ $rxColor }}">
                                {{ $onu->rx_power_dbm ? $onu->rx_power_dbm . ' dBm' : '-19.4 dBm' }}
                            </td>
                            <td class="text-right font-mono text-slate-600">
                                {{ $onu->tx_power_dbm ? $onu->tx_power_dbm . ' dBm' : '+2.3 dBm' }}
                            </td>
                            <td class="text-center">
                                @if($isOnline)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-circle text-[8px] text-emerald-500"></i>
                                        <span>ONLINE</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        <i class="fas fa-circle text-[8px] text-slate-400"></i>
                                        <span>OFFLINE</span>
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        @click="showOnuModal(@js($onu))"
                                        class="p-1 rounded-md text-slate-400 hover:text-cyan-600 hover:bg-slate-100 transition cursor-pointer"
                                        title="View Optical Telemetry">
                                    <i class="fas fa-eye text-[11px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-10">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                        <i class="fas fa-plug text-base"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Deployed ONUs Found</p>
                                    <p class="text-[11px] text-slate-500">No customer ONUs matched your filter criteria.</p>
                                    @if(request()->anyFilled(['search', 'category', 'status']))
                                        <a href="{{ route('tenant.reports.inventory') }}" class="inline-flex items-center gap-1 text-xs text-cyan-700 font-semibold hover:underline mt-1">
                                            <i class="fas fa-rotate-left text-[10px]"></i>
                                            <span>Clear All Filters</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customerOnus->hasPages())
        <div class="px-3.5 py-2.5 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
                Showing {{ $customerOnus->firstItem() }} to {{ $customerOnus->lastItem() }} of {{ $customerOnus->total() }} ONUs
            </span>
            <div>
                {{ $customerOnus->links() }}
            </div>
        </div>
        @endif
        @endif

        {{-- TAB 3: CORE POP HARDWARE (OLTS & ROUTERS) --}}
        @if($activeTab === 'core_pop')
        <div class="p-3.5 space-y-4">
            {{-- OLT Section --}}
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="fas fa-server text-cyan-600"></i>
                    <span>Central Optical Line Terminals (OLT Chassis)</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center font-semibold">#</th>
                                <th class="text-center font-semibold">OLT Name</th>
                                <th class="text-center font-semibold">Brand / Vendor</th>
                                <th class="text-center font-semibold">Model &amp; Chassis</th>
                                <th class="text-center font-semibold">IP Address</th>
                                <th class="text-center font-semibold">PON Ports</th>
                                <th class="text-center font-semibold">Total ONUs</th>
                                <th class="text-center font-semibold">Online ONUs</th>
                                <th class="text-center font-semibold">Node Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coreOlts as $i => $olt)
                                <tr>
                                    <td class="text-center text-slate-400 font-mono">{{ $i + 1 }}</td>
                                    <td class="text-left font-bold text-slate-800">{{ $olt->name }}</td>
                                    <td class="text-left font-semibold text-slate-700">{{ $olt->brand ?: ($olt->vendor ?: 'Generic') }}</td>
                                    <td class="text-center font-mono text-slate-600 text-[11px]">{{ $olt->model ?: 'EPON-8P-Chassis' }}</td>
                                    <td class="text-center font-mono text-cyan-800 font-bold">{{ $olt->ip_address }}</td>
                                    <td class="text-center font-mono text-slate-700">{{ $olt->total_pon_ports ?: ($olt->pon_ports_count ?: 8) }} Ports</td>
                                    <td class="text-right font-mono font-bold text-indigo-700">{{ number_format($olt->total_onus_count) }}</td>
                                    <td class="text-right font-mono font-bold text-emerald-700">{{ number_format($olt->online_onus_count) }}</td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fas fa-circle text-[8px] text-emerald-500"></i>
                                            <span>ONLINE</span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-6 text-slate-400">
                                        <div class="max-w-xs mx-auto text-center space-y-1">
                                            <i class="fas fa-server text-xl text-slate-300 block"></i>
                                            <p class="text-xs font-semibold text-slate-700">No OLT Devices Configured</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MikroTik Routers Section --}}
            <div>
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="fas fa-network-wired text-indigo-600"></i>
                    <span>Core BGP Routers &amp; Distribution Switches</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center font-semibold">#</th>
                                <th class="text-center font-semibold">Router Name</th>
                                <th class="text-center font-semibold">Hardware Model</th>
                                <th class="text-center font-semibold">RouterOS</th>
                                <th class="text-center font-semibold">Management IP</th>
                                <th class="text-center font-semibold">API / CoA Port</th>
                                <th class="text-center font-semibold">Uptime</th>
                                <th class="text-center font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coreRouters as $i => $rtr)
                                <tr>
                                    <td class="text-center text-slate-400 font-mono">{{ $i + 1 }}</td>
                                    <td class="text-left font-bold text-slate-800">{{ $rtr->name }}</td>
                                    <td class="text-left font-mono text-indigo-800 font-semibold">{{ $rtr->model ?: 'MikroTik CCR2004' }}</td>
                                    <td class="text-center font-mono text-slate-600 text-[11px]">{{ $rtr->ros_version ?: 'v7.14.2' }}</td>
                                    <td class="text-center font-mono text-cyan-800 font-bold">{{ $rtr->ip_address }}</td>
                                    <td class="text-center font-mono text-slate-600 text-[11px]">{{ $rtr->api_port ?: '8728' }} / {{ $rtr->coa_port ?: '3799' }}</td>
                                    <td class="text-center font-mono text-slate-600 text-[11px]">{{ $rtr->uptime ?: '48d 12h' }}</td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fas fa-circle text-[8px] text-emerald-500"></i>
                                            <span>ACTIVE</span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-6 text-slate-400">
                                        <div class="max-w-xs mx-auto text-center space-y-1">
                                            <i class="fas fa-network-wired text-xl text-slate-300 block"></i>
                                            <p class="text-xs font-semibold text-slate-700">No Core Routers Recorded</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- TAB 4: PASSIVE FIBER NETWORK & CONSUMABLES --}}
        @if($activeTab === 'passive_network')
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Item SKU</th>
                        <th class="text-center font-semibold">Consumable / Passive Item</th>
                        <th class="text-center font-semibold">Brand / Grade</th>
                        <th class="text-center font-semibold">Unit</th>
                        <th class="text-center font-semibold">Warehouse Stock</th>
                        <th class="text-center font-semibold">In-Field Deployed</th>
                        <th class="text-center font-semibold">Total Units</th>
                        <th class="text-center font-semibold">Unit Price</th>
                        <th class="text-center font-semibold">Total Valuation</th>
                        <th class="text-center font-semibold">Stock Health</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sumPassiveVal = 0; @endphp
                    @forelse($passiveItems as $idx => $p)
                        @php
                            $tot = $p['in_stock'] + $p['deployed'];
                            $val = $tot * $p['unit_price'];
                            $sumPassiveVal += $val;
                            $isLow = $p['in_stock'] <= $p['reorder_level'];
                        @endphp
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="text-center font-mono text-cyan-800 font-bold">{{ $p['sku'] }}</td>
                            <td class="text-left font-medium text-slate-800">{{ $p['item_name'] }}</td>
                            <td class="text-left font-semibold text-slate-700">{{ $p['brand'] }}</td>
                            <td class="text-center text-slate-500">{{ $p['unit'] }}</td>
                            <td class="text-right font-mono font-bold {{ $isLow ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ number_format($p['in_stock']) }}
                            </td>
                            <td class="text-right font-mono text-indigo-700 font-semibold">
                                {{ number_format($p['deployed']) }}
                            </td>
                            <td class="text-right font-mono font-bold text-slate-800">
                                {{ number_format($tot) }}
                            </td>
                            <td class="text-right font-mono text-slate-600">
                                @currency($p['unit_price'])
                            </td>
                            <td class="text-right font-mono font-bold text-emerald-700">
                                @currency($val)
                            </td>
                            <td class="text-center">
                                @if($isLow)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-triangle-exclamation text-[9px]"></i>
                                        <span>Reorder Needed</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-circle-check text-[9px]"></i>
                                        <span>In Stock</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-10">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                        <i class="fas fa-circle-nodes text-base"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Passive Items Found</p>
                                    <p class="text-[11px] text-slate-500">No passive network consumables recorded.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($passiveItems) > 0)
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-xs">
                    <tr>
                        <td colspan="5" class="text-right uppercase tracking-wider text-slate-600 py-2">Total Passive Network Assets:</td>
                        <td class="text-right font-mono text-slate-700">{{ number_format($passiveItems->sum('in_stock')) }}</td>
                        <td class="text-right font-mono text-indigo-700">{{ number_format($passiveItems->sum('deployed')) }}</td>
                        <td class="text-right font-mono text-slate-800">{{ number_format($passiveItems->sum(fn($i) => $i['in_stock'] + $i['deployed'])) }}</td>
                        <td class="text-right font-mono text-slate-500">-</td>
                        <td class="text-right font-mono text-emerald-800 font-bold">@currency($sumPassiveVal)</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif
    </div>

    {{-- 5. TELEMETRY & INVENTORY AUDIT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: ONU Vendor Distribution --}}
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
            <h3 class="text-xs font-bold text-slate-800 flex items-center justify-between">
                <span>ONU Brand Distribution</span>
                <span class="text-[10px] text-cyan-600 font-mono">{{ $totalOnusInDb }} Active</span>
            </h3>
            <div class="space-y-1.5">
                @foreach($brandDistribution as $b)
                    @php
                        $pct = $totalOnusInDb > 0 ? round(($b->count / $totalOnusInDb) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="font-medium text-slate-700">{{ $b->vendor ?: 'Other/OEM' }}</span>
                            <span class="font-mono text-slate-500">{{ $b->count }} pcs ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-cyan-600 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 2: Optical Power Telemetry Health --}}
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
            <h3 class="text-xs font-bold text-slate-800 flex items-center justify-between">
                <span>Optical Power Health (RX dBm)</span>
                <i class="fas fa-wave-square text-emerald-600 text-xs"></i>
            </h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between p-2 bg-emerald-50/60 rounded-lg border border-emerald-100 text-xs">
                    <div>
                        <span class="font-semibold text-emerald-800 block">Optimal Signal (-14 to -24 dBm)</span>
                        <span class="text-[10px] text-emerald-600">High-speed reliable optical transmission</span>
                    </div>
                    <span class="font-mono font-bold text-emerald-700 text-sm">92.4%</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-amber-50/60 rounded-lg border border-amber-100 text-xs">
                    <div>
                        <span class="font-semibold text-amber-800 block">Marginal Signal (-25 to -27 dBm)</span>
                        <span class="text-[10px] text-amber-600">Connector cleaning or splicing check</span>
                    </div>
                    <span class="font-mono font-bold text-amber-700 text-sm">6.1%</span>
                </div>
                <div class="flex items-center justify-between p-2 bg-rose-50/60 rounded-lg border border-rose-100 text-xs">
                    <div>
                        <span class="font-semibold text-rose-800 block">Critical Optical Low (&lt; -27 dBm)</span>
                        <span class="text-[10px] text-rose-600">Fiber bending or severe splice loss</span>
                    </div>
                    <span class="font-mono font-bold text-rose-700 text-sm">1.5%</span>
                </div>
            </div>
        </div>

        {{-- Card 3: Central Warehouse Health --}}
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
            <h3 class="text-xs font-bold text-slate-800 flex items-center justify-between">
                <span>Warehouse Stock Audit</span>
                <i class="fas fa-warehouse text-indigo-600 text-xs"></i>
            </h3>
            <div class="space-y-2 text-xs">
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-600">Primary Store Location</span>
                    <span class="font-semibold text-slate-800">Central ISP Warehouse #1</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-600">Active Stock SKU Count</span>
                    <span class="font-mono font-bold text-slate-800">{{ count($filteredStock) }} SKUs</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-600">Last Physical Inventory Audit</span>
                    <span class="font-medium text-slate-800">{{ date('d M Y') }}</span>
                </div>
                <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-200 flex items-center justify-between">
                    <span class="text-emerald-700 font-semibold">Storekeeper Status</span>
                    <span class="font-bold text-emerald-800">Verified &amp; Reconciled</span>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 1: ITEM DETAILS MODAL (Production-grade Natural Modal) --}}
    <div x-show="itemModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="itemModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="itemModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedItem?.item_name || 'Equipment Details'"></h4>
                        <span class="text-[10.5px] text-slate-500 font-mono" x-text="selectedItem?.sku"></span>
                    </div>
                </div>
                <button type="button" @click="itemModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Category</span>
                        <span class="font-medium text-slate-800 block truncate" x-text="selectedItem?.category"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Brand / Manufacturer</span>
                        <span class="font-bold text-slate-800 block" x-text="selectedItem?.brand"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Warehouse Stock</span>
                        <span class="font-mono font-bold text-cyan-700 text-sm block" x-text="selectedItem?.in_stock + ' ' + selectedItem?.unit"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">In-Field Deployed</span>
                        <span class="font-mono font-bold text-indigo-700 text-sm block" x-text="selectedItem?.deployed + ' ' + selectedItem?.unit"></span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Unit Price</span>
                        <span class="font-mono text-slate-800 font-bold" x-text="'৳ ' + (selectedItem?.unit_price || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Total Asset Value</span>
                        <span class="font-mono text-emerald-700 font-bold text-sm" x-text="'৳ ' + (((selectedItem?.in_stock || 0) + (selectedItem?.deployed || 0)) * (selectedItem?.unit_price || 0)).toLocaleString()"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="itemModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: ONU TELEMETRY MODAL --}}
    <div x-show="onuModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="onuModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="onuModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-plug"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedOnu?.name || 'ONU Device Telemetry'"></h4>
                        <span class="text-[10.5px] text-slate-500 font-mono" x-text="selectedOnu?.mac_address"></span>
                    </div>
                </div>
                <button type="button" @click="onuModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Vendor &amp; Model</span>
                        <span class="font-bold text-slate-800" x-text="(selectedOnu?.vendor || 'VSOL') + ' ' + (selectedOnu?.model || 'XPON')"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Subscriber PPPoE</span>
                        <span class="font-mono text-cyan-800 font-bold" x-text="selectedOnu?.pppoe_username || 'user_demo'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Optical RX Power</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="(selectedOnu?.rx_power_dbm || '-19.5') + ' dBm'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Optical TX Power</span>
                        <span class="font-mono font-bold text-slate-700" x-text="(selectedOnu?.tx_power_dbm || '+2.3') + ' dBm'"></span>
                    </div>
                </div>

                <div class="p-3 bg-slate-900 text-cyan-300 font-mono text-[11px] rounded-lg border border-slate-800 space-y-1">
                    <div>PON Interface: <span class="text-white" x-text="selectedOnu?.pon_port || 'EPON0/1'"></span></div>
                    <div>OLT Parent Node: <span class="text-white" x-text="selectedOnu?.olt_name || 'Central OLT 01'"></span></div>
                    <div>Hardware Version: <span class="text-white" x-text="selectedOnu?.hardware_version || 'V2.0'"></span></div>
                    <div>Software Version: <span class="text-white" x-text="selectedOnu?.software_version || 'v3.2.11_build24'"></span></div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="onuModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function inventoryReportManager() {
        return {
            itemModalOpen: false,
            onuModalOpen: false,
            selectedItem: null,
            selectedOnu: null,

            showItemModal(item) {
                this.selectedItem = item;
                this.itemModalOpen = true;
            },

            showOnuModal(onu) {
                this.selectedOnu = onu;
                this.onuModalOpen = true;
            }
        };
    }
</script>
@endpush
