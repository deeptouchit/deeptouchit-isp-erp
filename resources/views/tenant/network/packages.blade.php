@extends('tenant.layouts.app')

@section('title', 'Internet Packages & Plans - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $routersJson = $routers->map(function($r) {
        return ['id' => $r->id, 'name' => $r->name, 'ip' => $r->ip_address];
    })->values();

    $poolsJson = $ipPools->map(function($p) {
        return [
            'id' => $p->id,
            'router_id' => $p->router_id,
            'name' => $p->name,
            'type' => $p->pool_type,
            'range' => $p->addresses_display ?? ''
        ];
    })->values();
@endphp

<div class="space-y-3" 
     x-data="internetPackagesPage()"
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

    <!-- 1. Top Compact Header Bar (Icon + Title + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Internet Packages &amp; Bandwidth Plans</h2>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <!-- Import from MikroTik Button -->
            <button type="button" 
                    @click="showImportModal = true"
                    class="px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-cloud-arrow-down text-purple-600 text-[11px]"></i>
                <span>Import from MikroTik</span>
            </button>

            <!-- Add Package Button -->
            <button type="button" 
                    @click="openCreateModal()"
                    class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add Package</span>
            </button>
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

        <!-- Metric 3: PPPoE -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">PPPoE</span>
                <span class="text-[13px] font-bold text-blue-700 tracking-tight font-mono leading-tight block">{{ number_format($pppoePackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Metric 4: Hotspot -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Hotspot</span>
                <span class="text-[13px] font-bold text-amber-700 tracking-tight font-mono leading-tight block">{{ number_format($hotspotPackages) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-wifi"></i>
            </div>
        </div>

        <!-- Metric 5: Avg Price -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">Avg Price</span>
                <span class="text-[13px] font-bold text-purple-700 tracking-tight font-mono leading-tight block">@currency($avgPrice)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-tag"></i>
            </div>
        </div>

        <!-- Metric 6: Router Synced -->
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

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.packages') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2.5">
            
            <!-- Search Keyword (col-span-2) -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search package, profile, notes..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- Service Type Filter -->
            <div>
                <select name="service_type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Services</option>
                    <option value="pppoe" {{ request('service_type') === 'pppoe' ? 'selected' : '' }}>PPPoE Profile</option>
                    <option value="hotspot" {{ request('service_type') === 'hotspot' ? 'selected' : '' }}>Hotspot Profile</option>
                    <option value="static" {{ request('service_type') === 'static' ? 'selected' : '' }}>Static IP</option>
                </select>
            </div>

            <!-- Fleet Router Filter -->
            <div>
                <select name="router_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Routers</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                            {{ $r->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Active State Filter -->
            <div>
                <select name="active" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All States</option>
                    <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active Only</option>
                    <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Disabled Only</option>
                </select>
            </div>

            <!-- Per Page Pagination Limit -->
            <div>
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100 / page</option>
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
                <a href="{{ route('tenant.network.packages') }}" 
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
                        <th class="w-10">#</th>
                        <th>Router</th>
                        <th>Package Name</th>
                        <th class="text-center">Price</th>
                        <th>MikroTik Profile</th>
                        <th class="text-center">Local Address</th>
                        <th class="text-center">Remote Address</th>
                        <th class="text-center">Only One</th>
                        <th>Comment</th>
                        <th class="text-center">State</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($packages as $index => $pkg)
                        @php
                            $onlyOne = $pkg->only_one ?: 'default';
                            $onlyOneClass = match($onlyOne) {
                                'yes' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'no' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="{{ !$pkg->is_active ? 'opacity-70' : '' }}">
                            
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $packages->firstItem() + $index }}
                            </td>

                            <!-- 2. Router -->
                            <td>
                                @if($pkg->router)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fas fa-server text-[8px]"></i>
                                        <span>{{ $pkg->router->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">All Routers</span>
                                @endif
                            </td>

                            <!-- 3. Package Name -->
                            <td class="text-cyan-800">
                                <button type="button" 
                                        @click="openEditModal({{ Js::from($pkg) }})"
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $pkg->package_name ?: $pkg->name }}
                                </button>
                            </td>

                            <!-- 4. Price -->
                            <td class="text-right font-mono font-semibold text-slate-800">
                                @currency($pkg->price)
                            </td>

                            <!-- 5. Profile Name -->
                            <td class="font-mono text-indigo-900">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-indigo-50 border border-indigo-200">
                                    {{ $pkg->name ?: $pkg->mikrotik_profile }}
                                </span>
                            </td>

                            <!-- 6. Local Address -->
                            <td class="text-center font-mono text-slate-700">
                                {{ $pkg->local_address ?: '--' }}
                            </td>

                            <!-- 7. Remote Address -->
                            <td class="text-center font-mono text-purple-700">
                                @if($pkg->remote_address || $pkg->ipPool)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-purple-50 border border-purple-200">
                                        {{ $pkg->remote_address ?: $pkg->ipPool?->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">--</span>
                                @endif
                            </td>

                            <!-- 8. Only One -->
                            <td class="text-center">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase border {{ $onlyOneClass }}">
                                    {{ $onlyOne }}
                                </span>
                            </td>

                            <!-- 9. Comment -->
                            <td class="text-slate-600 max-w-[140px] truncate" title="{{ $pkg->comment }}">
                                {{ $pkg->comment ?: '--' }}
                            </td>

                            <!-- 10. State -->
                            <td class="text-center">
                                @if($pkg->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 11. Actions (3-Dot Action Button triggering Floating Dropdown) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($pkg) }}, $event)" 
                                        class="w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-boxes-stacked"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No Internet Packages Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Create your first bandwidth package or import existing profiles directly from your MikroTik fleet.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openCreateModal()" 
                                                class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Create Package</span>
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
        
        <!-- Group 1: Telemetry & Actions -->
        <div class="py-0.5">
            <!-- Sync to Router -->
            <button type="button" 
                    @click="syncFromMenu()"
                    :disabled="!activeMenuPkg?.router_id || syncingPkgId === activeMenuPkg?.id"
                    class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed text-[11px]">
                <i class="fas" :class="syncingPkgId === activeMenuPkg?.id ? 'fa-spinner fa-spin text-cyan-600' : 'fa-arrows-rotate text-cyan-600 w-3.5 text-center text-[10px]'"></i>
                <span>Sync to Router</span>
            </button>

            <!-- Copy Profile Name -->
            <button type="button" 
                    @click="copyProfileFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-copy text-blue-600 w-3.5 text-center text-[10px]"></i>
                <span>Copy Profile Name</span>
            </button>
        </div>

        <!-- Group 2: Configuration & Management -->
        <div class="py-0.5">
            <!-- Toggle Status -->
            <button type="button" 
                    @click="toggleFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-slate-50 text-slate-700 hover:text-indigo-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-power-off text-indigo-600 w-3.5 text-center text-[10px]"></i>
                <span x-text="activeMenuPkg?.is_active ? 'Disable Package' : 'Enable Package'"></span>
            </button>

            <!-- Edit Package -->
            <button type="button" 
                    @click="editFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-600 w-3.5 text-center text-[10px]"></i>
                <span>Edit Package</span>
            </button>

            <!-- Delete Package -->
            <button type="button" 
                    @click="deleteFromMenu()"
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-trash-can text-rose-600 w-3.5 text-center text-[10px]"></i>
                <span>Delete Package</span>
            </button>
        </div>
    </div>

    <!-- MODAL 1: ADD PACKAGE MODAL -->
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
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Create Internet Package</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Configure commercial pricing &amp; MikroTik profile policies</p>
                    </div>
                </div>
                <button type="button" @click="showAddModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('tenant.network.packages.store') }}" method="POST" class="p-4 space-y-3.5 text-xs">
                @csrf

                <!-- SECTION 1: Commercial Pricing & Speeds -->
                <div class="space-y-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 uppercase block font-semibold">1. Pricing &amp; Bandwidth Speeds</span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Package Display Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="package_name" 
                                   x-model="addForm.package_name"
                                   required 
                                   placeholder="e.g. 10 Mbps Home Starter" 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Price ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="price" 
                                   x-model="addForm.price" 
                                   required 
                                   class="w-full px-3 py-1.5 text-xs font-mono font-semibold bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>
                    </div>

                    <!-- Speeds Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-2.5 bg-white rounded-lg border border-slate-200">
                        <div>
                            <label class="block text-[10px] font-semibold text-cyan-800 mb-0.5">Download (M) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="download_speed" 
                                   x-model="addForm.download_speed" 
                                   required 
                                   placeholder="10" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-purple-800 mb-0.5">Upload (M) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="upload_speed" 
                                   x-model="addForm.upload_speed" 
                                   required 
                                   placeholder="10" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">Facebook (M)</label>
                            <input type="text" 
                                   name="facebook_speed" 
                                   x-model="addForm.facebook_speed" 
                                   placeholder="0" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">YouTube (M)</label>
                            <input type="text" 
                                   name="youtube_speed" 
                                   x-model="addForm.youtube_speed" 
                                   placeholder="0" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">BDIX (M)</label>
                            <input type="text" 
                                   name="bdix_speed" 
                                   x-model="addForm.bdix_speed" 
                                   placeholder="0" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Validity (Days) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="validity_days" 
                                   x-model="addForm.validity_days" 
                                   required 
                                   placeholder="30" 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Description / Notes</label>
                            <input type="text" 
                                   name="description" 
                                   x-model="addForm.description" 
                                   placeholder="Commercial details for portal" 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: MikroTik Profile Policies -->
                <div class="space-y-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 uppercase block font-semibold">2. MikroTik RouterOS Profile Policies</span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Target Router</label>
                            <select name="router_id" 
                                    x-model="addForm.router_id" 
                                    @change="handleRouterChange('add')"
                                    class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="">All Routers (Global)</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Profile Name (RouterOS) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="addForm.name" 
                                   required 
                                   placeholder="e.g. 10Mbps" 
                                   class="w-full px-3 py-1.5 text-xs font-mono font-medium text-indigo-900 bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Service Type <span class="text-rose-500">*</span></label>
                            <select name="service_type" x-model="addForm.service_type" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="pppoe">PPPoE Profile</option>
                                <option value="hotspot">Hotspot Profile</option>
                                <option value="static">Static IP</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Local Address</label>
                            <input type="text" 
                                   name="local_address" 
                                   x-model="addForm.local_address" 
                                   placeholder="e.g. 10.10.10.1" 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Remote Address / Pool</label>
                            <select name="ip_pool_id" 
                                    x-model="addForm.ip_pool_id" 
                                    class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-mono">
                                <option value="">Default Pool</option>
                                @foreach($ipPools as $p)
                                    <option value="{{ $p->id }}" 
                                            x-show="!addForm.router_id || '{{ $p->router_id }}' === String(addForm.router_id) || '{{ $p->router_id }}' === ''"
                                            :disabled="addForm.router_id && '{{ $p->router_id }}' !== '' && '{{ $p->router_id }}' !== String(addForm.router_id)">
                                        {{ $p->name }} {{ $p->addresses_display && $p->addresses_display !== '-' ? '('.$p->addresses_display.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Only One</label>
                            <select name="only_one" x-model="addForm.only_one" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="default">default</option>
                                <option value="yes">yes</option>
                                <option value="no">no</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Address List</label>
                            <input type="text" 
                                   name="address_list" 
                                   x-model="addForm.address_list" 
                                   placeholder="e.g. ALLOWED_USERS" 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Comment (RouterOS)</label>
                            <input type="text" 
                                   name="comment" 
                                   x-model="addForm.comment" 
                                   placeholder="e.g. 10M-HOME-PROFILE" 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>
                    </div>
                </div>

                <!-- Active & Sync Checkboxes -->
                <div class="flex items-center gap-4 pt-1">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" name="sync_now" value="1" x-model="addForm.sync_now" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-xs text-slate-700 font-medium">Push Profile to RouterOS</span>
                    </label>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" x-model="addForm.is_active" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-xs text-slate-700 font-medium">Active Status</span>
                    </label>
                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 -mx-4 -mb-4 flex items-center justify-end gap-2">
                    <button type="button" @click="showAddModal = false" class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Save Package</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT PACKAGE MODAL -->
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
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit Internet Package</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Editing: ${editPackage.package_name || editPackage.name}`"></p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="'/admin/network/packages/' + editPackage.id" method="POST" class="p-4 space-y-3.5 text-xs">
                @csrf
                @method('PUT')

                <!-- SECTION 1: Commercial Pricing & Speeds -->
                <div class="space-y-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 uppercase block font-semibold">1. Pricing &amp; Bandwidth Speeds</span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Package Display Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="package_name" 
                                   x-model="editPackage.package_name"
                                   required 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Price ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="price" 
                                   x-model="editPackage.price" 
                                   required 
                                   class="w-full px-3 py-1.5 text-xs font-mono font-semibold bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>
                    </div>

                    <!-- Speeds Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-2.5 bg-white rounded-lg border border-slate-200">
                        <div>
                            <label class="block text-[10px] font-semibold text-cyan-800 mb-0.5">Download (M) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="download_speed" 
                                   x-model="editPackage.download_speed" 
                                   required 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-purple-800 mb-0.5">Upload (M) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="upload_speed" 
                                   x-model="editPackage.upload_speed" 
                                   required 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">Facebook (M)</label>
                            <input type="text" 
                                   name="facebook_speed" 
                                   x-model="editPackage.facebook_speed" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">YouTube (M)</label>
                            <input type="text" 
                                   name="youtube_speed" 
                                   x-model="editPackage.youtube_speed" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-600 mb-0.5">BDIX (M)</label>
                            <input type="text" 
                                   name="bdix_speed" 
                                   x-model="editPackage.bdix_speed" 
                                   class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-200 rounded focus:border-cyan-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Validity (Days) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="validity_days" 
                                   x-model="editPackage.validity_days" 
                                   required 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Description / Notes</label>
                            <input type="text" 
                                   name="description" 
                                   x-model="editPackage.description" 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: MikroTik Profile Policies -->
                <div class="space-y-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 uppercase block font-semibold">2. MikroTik RouterOS Profile Policies</span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Target Router</label>
                            <select name="router_id" 
                                    x-model="editPackage.router_id" 
                                    @change="handleRouterChange('edit')"
                                    class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="">All Routers (Global)</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Profile Name (RouterOS) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="editPackage.name" 
                                   required 
                                   class="w-full px-3 py-1.5 text-xs font-mono font-medium text-indigo-900 bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Service Type <span class="text-rose-500">*</span></label>
                            <select name="service_type" x-model="editPackage.service_type" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="pppoe">PPPoE Profile</option>
                                <option value="hotspot">Hotspot Profile</option>
                                <option value="static">Static IP</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Local Address</label>
                            <input type="text" 
                                   name="local_address" 
                                   x-model="editPackage.local_address" 
                                   placeholder="e.g. 10.10.10.1" 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Remote Address / Pool</label>
                            <select name="ip_pool_id" 
                                    x-model="editPackage.ip_pool_id" 
                                    class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-mono">
                                <option value="">Default Pool</option>
                                @foreach($ipPools as $p)
                                    <option value="{{ $p->id }}" 
                                            x-show="!editPackage.router_id || '{{ $p->router_id }}' === String(editPackage.router_id) || '{{ $p->router_id }}' === ''"
                                            :disabled="editPackage.router_id && '{{ $p->router_id }}' !== '' && '{{ $p->router_id }}' !== String(editPackage.router_id)">
                                        {{ $p->name }} {{ $p->addresses_display && $p->addresses_display !== '-' ? '('.$p->addresses_display.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Only One</label>
                            <select name="only_one" x-model="editPackage.only_one" class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                                <option value="default">default</option>
                                <option value="yes">yes</option>
                                <option value="no">no</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Address List</label>
                            <input type="text" 
                                   name="address_list" 
                                   x-model="editPackage.address_list" 
                                   placeholder="e.g. ALLOWED_USERS" 
                                   class="w-full px-3 py-1.5 text-xs font-mono bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Comment (RouterOS)</label>
                            <input type="text" 
                                   name="comment" 
                                   x-model="editPackage.comment" 
                                   placeholder="e.g. 10M-HOME-PROFILE" 
                                   class="w-full px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 font-normal">
                        </div>
                    </div>
                </div>

                <!-- Active & Sync Checkboxes -->
                <div class="flex items-center gap-4 pt-1">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" name="sync_now" value="1" x-model="editPackage.sync_now" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-xs text-slate-700 font-medium">Push Profile to RouterOS</span>
                    </label>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" x-model="editPackage.is_active" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="text-xs text-slate-700 font-medium">Active Status</span>
                    </label>
                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 -mx-4 -mb-4 flex items-center justify-end gap-2">
                    <button type="button" @click="showEditModal = false" class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Update Package</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: IMPORT FROM MIKROTIK MODAL -->
    <div x-show="showImportModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.away="showImportModal = false" 
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
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Import Profiles from MikroTik</h3>
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Select MikroTik Router <span class="text-rose-500">*</span>
                    </label>
                    <select name="router_id" required class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 focus:bg-white transition font-normal">
                        <option value="">Select a router...</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Profiles to Import <span class="text-rose-500">*</span>
                    </label>
                    <select name="import_type" required class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:border-cyan-500 focus:bg-white transition font-normal">
                        <option value="both">Both PPPoE &amp; Hotspot Profiles</option>
                        <option value="pppoe">PPPoE Profiles (/ppp/profile) Only</option>
                        <option value="hotspot">Hotspot Profiles (/ip/hotspot/user/profile) Only</option>
                    </select>
                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 -mx-4 -mb-4 flex items-center justify-end gap-2">
                    <button type="button" @click="showImportModal = false" class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
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
function internetPackagesPage() {
    return {
        showAddModal: false,
        showEditModal: false,
        showImportModal: false,
        activeMenuPkg: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        syncingPkgId: null,
        editPackage: {},
        routers: @json($routersJson),
        pools: @json($poolsJson),
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

        editFromMenu() {
            if (!this.activeMenuPkg) return;
            const pkg = this.activeMenuPkg;
            this.activeMenuPkg = null;
            this.openEditModal(pkg);
        },

        deleteFromMenu() {
            if (!this.activeMenuPkg) return;
            const pkg = this.activeMenuPkg;
            this.activeMenuPkg = null;
            this.confirmDeletePackage(pkg.id, pkg.name || pkg.package_name);
        },

        addForm: {
            package_name: '',
            name: '',
            code: '',
            service_type: 'pppoe',
            router_id: '',
            local_address: '',
            remote_address: '',
            ip_pool_id: '',
            download_speed: '10',
            upload_speed: '10',
            facebook_speed: '',
            youtube_speed: '',
            bdix_speed: '',
            price: 500,
            validity_days: '30',
            validity_unit: 'days',
            only_one: 'default',
            address_list: '',
            comment: '',
            description: '',
            is_active: true,
            sync_now: true
        },

        openCreateModal() {
            this.addForm = {
                package_name: '',
                name: '',
                code: '',
                service_type: 'pppoe',
                router_id: '',
                local_address: '',
                remote_address: '',
                ip_pool_id: '',
                download_speed: '10',
                upload_speed: '10',
                facebook_speed: '',
                youtube_speed: '',
                bdix_speed: '',
                price: 500,
                validity_days: '30',
                validity_unit: 'days',
                only_one: 'default',
                address_list: '',
                comment: '',
                description: '',
                is_active: true,
                sync_now: true
            };
            this.showAddModal = true;
        },

        openEditModal(pkg) {
            this.editPackage = {
                id: pkg.id,
                package_name: pkg.package_name || pkg.name || '',
                name: pkg.name || pkg.mikrotik_profile || '',
                code: pkg.code || '',
                service_type: pkg.service_type || 'pppoe',
                router_id: pkg.router_id || '',
                local_address: pkg.local_address || '',
                remote_address: pkg.remote_address || (pkg.ip_pool ? pkg.ip_pool.name : ''),
                ip_pool_id: pkg.ip_pool_id || '',
                download_speed: pkg.download_speed || '10',
                upload_speed: pkg.upload_speed || '10',
                facebook_speed: pkg.facebook_speed || '',
                youtube_speed: pkg.youtube_speed || '',
                bdix_speed: pkg.bdix_speed || '',
                price: pkg.price || 0,
                validity_days: pkg.validity_days || '30',
                validity_unit: pkg.validity_unit || 'days',
                only_one: pkg.only_one || 'default',
                address_list: pkg.address_list || '',
                comment: pkg.comment || '',
                description: pkg.description || '',
                is_active: Boolean(pkg.is_active),
                sync_now: true
            };
            this.showEditModal = true;
        },

        getFilteredPools(routerId) {
            if (!routerId) {
                return this.pools;
            }
            return this.pools.filter(p => !p.router_id || String(p.router_id) === String(routerId));
        },

        async handleRouterChange(mode) {
            const routerId = (mode === 'add') ? this.addForm.router_id : this.editPackage.router_id;
            const currentPoolId = (mode === 'add') ? this.addForm.ip_pool_id : this.editPackage.ip_pool_id;
            
            const available = this.getFilteredPools(routerId);
            if (currentPoolId && !available.some(p => String(p.id) === String(currentPoolId))) {
                if (mode === 'add') this.addForm.ip_pool_id = '';
                else this.editPackage.ip_pool_id = '';
            }

            if (!routerId) return;

            try {
                const res = await fetch(`/admin/network/packages/router-pools/${routerId}`);
                const data = await res.json();
                if (data.success && Array.isArray(data.pools)) {
                    data.pools.forEach(newP => {
                        const idx = this.pools.findIndex(p => p.id === newP.id);
                        if (idx !== -1) {
                            this.pools[idx] = newP;
                        } else {
                            this.pools.push(newP);
                        }
                    });
                }
            } catch (e) {
                console.error('Failed to fetch router pools:', e);
            }
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
