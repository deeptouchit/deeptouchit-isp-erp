@extends('tenant.layouts.app')

@section('title', 'IP Pools & Subnet Management - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ipPoolsManager()" @scroll.window="activeMenuPool = null" @resize.window="activeMenuPool = null">
    
    <!-- Floating Global Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-xl border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">IP Pools &amp; Subnet Management</h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Sync from MikroTik Button -->
            <button type="button" 
                    @click="openImportModal()" 
                    class="px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-rotate text-purple-600 text-xs" :class="{ 'fa-spin': importModal.syncing }"></i>
                <span>Sync from MikroTik</span>
            </button>

            <!-- Link to MikroTik Routers -->
            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-server text-slate-400 text-xs"></i>
                <span>View Routers</span>
            </a>

            <!-- Create IP Pool Button -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Create IP Pool</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Configured Pools -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Pools</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">{{ number_format($totalPools) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        <!-- Card 2: Total IP Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Total Capacity</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">{{ number_format($totalIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 3: Available Free IPs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Available Free</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($freeIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 4: Assigned / Leased -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">Assigned / Used</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700">{{ number_format($usedIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Card 5: Static Public / CGNAT -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">Public / CGNAT</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">{{ number_format($staticPublicIps + $cgnatIps) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Card 6: Pool Utilization -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-600">Pool Utilization</span>
                <span class="text-[13px] font-bold font-mono leading-tight block"
                      :class="{
                          'text-emerald-700': {{ $utilizationPercent }} < 70,
                          'text-amber-700': {{ $utilizationPercent }} >= 70 && {{ $utilizationPercent }} < 90,
                          'text-rose-700': {{ $utilizationPercent }} >= 90
                      }">
                    {{ $utilizationPercent }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.ip-pools') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Name, IP Range, Subnet, Gateway..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- Router Filter -->
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

            <!-- Pool Type Filter -->
            <div>
                <select name="pool_type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Types</option>
                    <option value="pppoe" {{ request('pool_type') === 'pppoe' ? 'selected' : '' }}>PPPoE Dynamic</option>
                    <option value="cgnat" {{ request('pool_type') === 'cgnat' ? 'selected' : '' }}>CGNAT Block</option>
                    <option value="static_public" {{ request('pool_type') === 'static_public' ? 'selected' : '' }}>Static Public</option>
                    <option value="dhcp" {{ request('pool_type') === 'dhcp' ? 'selected' : '' }}>DHCP Hotspot</option>
                    <option value="ipv6" {{ request('pool_type') === 'ipv6' ? 'selected' : '' }}>IPv6 Prefix</option>
                    <option value="vpn" {{ request('pool_type') === 'vpn' ? 'selected' : '' }}>VPN Pool</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    <option value="exhausted" {{ request('status') === 'exhausted' ? 'selected' : '' }}>Exhausted</option>
                </select>
            </div>

            <!-- Per Page Filter -->
            <div>
                @php $perPageVal = (int)request('per_page', 30); @endphp
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="15" {{ $perPageVal === 15 ? 'selected' : '' }}>15 / page</option>
                    <option value="30" {{ $perPageVal === 30 ? 'selected' : '' }}>30 / page</option>
                    <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
                    <option value="200" {{ $perPageVal === 200 ? 'selected' : '' }}>200 / page</option>
                </select>
            </div>

            <!-- Filter & Reset Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.network.ip-pools') }}" 
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
                <span>Master IP Pool &amp; Subnet Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $pools->total() }} Pools
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>Router</th>
                        <th>Pool Name</th>
                        <th>Type</th>
                        <th>Addresses / Range</th>
                        <th class="text-center">Total IPs</th>
                        <th class="text-center">Next Pool</th>
                        <th>Comment</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($pools as $index => $pool)
                        @php
                            $poolPayload = [
                                'id' => $pool->id,
                                'name' => $pool->name,
                                'pool_type' => $pool->pool_type,
                                'ip_version' => $pool->ip_version,
                                'range_start' => $pool->range_start,
                                'range_end' => $pool->range_end,
                                'cidr_subnet' => $pool->cidr_subnet,
                                'gateway' => $pool->gateway,
                                'dns_primary' => $pool->dns_primary,
                                'dns_secondary' => $pool->dns_secondary,
                                'next_pool' => $pool->next_pool,
                                'router_id' => $pool->router_id,
                                'vlan_id' => $pool->vlan_id,
                                'status' => $pool->status,
                                'description' => $pool->description,
                                'is_sync_mikrotik' => (bool)$pool->is_sync_mikrotik,
                                'total_ips' => $pool->total_ips,
                                'used_ips' => $pool->used_ips,
                                'free_ips' => $pool->free_ips,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $pools->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if($pool->status === 'active')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @elseif($pool->status === 'exhausted')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Exhausted</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Router -->
                            <td>
                                @if($pool->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $pool->router_id) }}" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-[10px] font-mono transition" title="{{ $pool->router->ip_address }}">
                                        <i class="fas fa-server text-[8px] text-indigo-500"></i>
                                        <span class="truncate max-w-[110px]">{{ $pool->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono text-[10px]">Global</span>
                                @endif
                            </td>

                            <!-- 4. Pool Name -->
                            <td class="text-cyan-800">
                                <button type="button" 
                                        @click="openEditModal({{ Js::from($poolPayload) }})" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $pool->name }}
                                </button>
                            </td>

                            <!-- 5. Type -->
                            <td class="font-mono text-[10px]">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $pool->type_badge['badge'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ $pool->type_badge['label'] ?? ucfirst($pool->pool_type) }}
                                </span>
                            </td>

                            <!-- 6. Addresses / Range -->
                            <td class="font-mono text-slate-800">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $pool->addresses_display }}</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $pool->addresses_display }}', 'Addresses')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy Range">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 7. Total IPs -->
                            <td class="text-center font-mono text-slate-700">
                                <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px]">
                                    {{ number_format($pool->total_ips) }} IPs
                                </span>
                            </td>

                            <!-- 8. Next Pool -->
                            <td class="text-center font-mono text-slate-700">
                                @if($pool->next_pool)
                                    <span class="text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 text-[10px]">{{ $pool->next_pool }}</span>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 9. Comment -->
                            <td class="text-slate-600 max-w-[140px] truncate" title="{{ $pool->description }}">
                                {{ $pool->description ?: '--' }}
                            </td>

                            <!-- 10. Actions (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($poolPayload) }}, $event)" 
                                        class="pool-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
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
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No IP Pools Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Create your first IP pool for PPPoE dynamic assignment, CGNAT subnets, or static public blocks.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openCreateModal()" 
                                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Create IP Pool</span>
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
        @if($pools->hasPages() || $pools->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $pools->firstItem() ?? 0 }} to {{ $pools->lastItem() ?? 0 }} of {{ $pools->total() }} results
                </div>
                <div>
                    {{ $pools->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenuPool !== null" 
         @click.away="activeMenuPool = null"
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
        
        <!-- Group 1: Configuration & Sync -->
        <div class="py-0.5">
            <button type="button" 
                    @click="editFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-500 w-3.5 text-center text-[10px]"></i>
                <span>Edit Configuration</span>
            </button>

            <button type="button" 
                    @click="syncFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-rotate text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>Push to MikroTik</span>
            </button>

            <button type="button" 
                    @click="toggleStatusFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas w-3.5 text-center text-[10px]" :class="activeMenuPool?.status === 'active' ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500'"></i>
                <span x-text="activeMenuPool?.status === 'active' ? 'Disable IP Pool' : 'Enable IP Pool'"></span>
            </button>
        </div>

        <!-- Group 2: Destructive Action -->
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete IP Pool</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Create & Edit IP Pool Modal -->
    <div x-show="modal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="modal.isEdit ? 'Edit IP Pool' : 'Create New IP Pool'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Define subnet block, IP range boundaries, and gateway routing</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <div class="p-4 space-y-3.5 max-h-[70vh] overflow-y-auto text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Pool Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Pool Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="modal.form.name" 
                               placeholder="e.g. PPPoE-Pool-Zone1 / CGNAT-100.64-Pool" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Pool Type -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Pool Type <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="modal.form.pool_type" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="pppoe">PPPoE Dynamic Client Pool</option>
                            <option value="cgnat">CGNAT RFC6598 (100.64.0.0/10)</option>
                            <option value="static_public">Static Public IPv4 Block</option>
                            <option value="dhcp">DHCP / IPoE Hotspot Pool</option>
                            <option value="ipv6">IPv6 Prefix Pool</option>
                            <option value="vpn">VPN Client Pool</option>
                        </select>
                    </div>

                    <!-- Associated Router -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Associated Router</label>
                        <select x-model="modal.form.router_id" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="">Global / All Routers</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Addresses / IP Range -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Addresses (IP Range / Subnet) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="modal.form.ranges" 
                               placeholder="e.g. 10.10.0.2-10.10.3.254 or 192.168.1.0/24" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                        <span class="text-[10px] text-slate-400 mt-1 block">MikroTik format: IP range (<code class="font-mono text-cyan-700 bg-cyan-50 px-1 py-0.5 rounded">10.10.0.2-10.10.3.254</code>) or CIDR block (<code class="font-mono text-cyan-700 bg-cyan-50 px-1 py-0.5 rounded">192.168.1.0/24</code>).</span>
                    </div>

                    <!-- CIDR Subnet -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">CIDR Subnet (Optional)</label>
                        <input type="text" 
                               x-model="modal.form.cidr_subnet" 
                               placeholder="e.g. 10.10.10.0/24" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Gateway -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Gateway IP</label>
                        <input type="text" 
                               x-model="modal.form.gateway" 
                               placeholder="e.g. 10.10.10.1" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Primary DNS -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Primary DNS</label>
                        <input type="text" 
                               x-model="modal.form.dns_primary" 
                               placeholder="8.8.8.8" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Secondary DNS -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Secondary DNS</label>
                        <input type="text" 
                               x-model="modal.form.dns_secondary" 
                               placeholder="1.1.1.1" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Next Pool -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Next Pool (Overflow)</label>
                        <input type="text" 
                               x-model="modal.form.next_pool" 
                               placeholder="e.g. Pool2 (optional)" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- VLAN ID -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">VLAN ID (Optional)</label>
                        <input type="number" 
                               x-model="modal.form.vlan_id" 
                               placeholder="e.g. 100" 
                               min="1" max="4094"
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Comment / Description -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Comment (MikroTik Pool Comment)</label>
                        <textarea x-model="modal.form.description" 
                                  rows="2"
                                  placeholder="e.g. PPPoE Client Pool for Core Router" 
                                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal"></textarea>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeModal()" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="savePool()" 
                        :disabled="modal.saving"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': modal.saving }"></i>
                    <span x-text="modal.saving ? 'Saving...' : (modal.isEdit ? 'Save Changes' : 'Create IP Pool')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: Sync / Import from MikroTik Modal -->
    <div x-show="importModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeImportModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-rotate"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Sync from MikroTik Router</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Pull and synchronize /ip/pool configurations</p>
                    </div>
                </div>
                <button type="button" @click="closeImportModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <p class="text-slate-600 leading-relaxed font-normal">
                    Select a MikroTik router below to pull all configured IP Pools (<code class="bg-slate-100 px-1 py-0.5 rounded text-purple-700 font-mono text-[11px]">/ip/pool</code>) and live active client allocations directly into the database.
                </p>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Select MikroTik Router <span class="text-rose-500">*</span></label>
                    <select x-model="importModal.router_id" 
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-purple-50/60 border border-purple-100 rounded-lg text-purple-900 space-y-1">
                    <span class="font-semibold block text-[11px]">✨ Automatic Sync Capabilities:</span>
                    <ul class="list-disc list-inside text-[10.5px] space-y-0.5 text-purple-800/90 font-normal">
                        <li>Imports IP Pool names, ranges, and comments.</li>
                        <li>Reads live allocated IP count (<code class="font-mono">/ip/pool/used</code>).</li>
                        <li>Updates matching pools without duplicates.</li>
                    </ul>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeImportModal()" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="syncFromMikrotik()" 
                        :disabled="importModal.syncing"
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-rotate text-[10px]" :class="{ 'fa-spin': importModal.syncing }"></i>
                    <span x-text="importModal.syncing ? 'Pulling from Router...' : 'Start Sync'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ipPoolsManager() {
    return {
        activeMenuPool: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        modal: {
            open: false,
            isEdit: false,
            saving: false,
            form: {
                id: null,
                name: '',
                pool_type: 'pppoe',
                ip_version: 'ipv4',
                ranges: '',
                cidr_subnet: '',
                gateway: '',
                dns_primary: '8.8.8.8',
                dns_secondary: '1.1.1.1',
                next_pool: '',
                router_id: '',
                vlan_id: '',
                status: 'active',
                description: '',
                is_sync_mikrotik: false
            }
        },

        importModal: {
            open: false,
            router_id: '{{ $routers->first()?->id ?? "" }}',
            syncing: false
        },

        toggleMenu(pool, event) {
            if (this.activeMenuPool && this.activeMenuPool.id === pool.id) {
                this.activeMenuPool = null;
                return;
            }
            this.activeMenuPool = pool;

            let targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
            const dropdownHeight = 140;
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
        },

        editFromMenu() {
            if (!this.activeMenuPool) return;
            const pool = this.activeMenuPool;
            this.activeMenuPool = null;
            this.openEditModal(pool);
        },

        syncFromMenu() {
            if (!this.activeMenuPool) return;
            const poolId = this.activeMenuPool.id;
            this.activeMenuPool = null;
            this.syncMikrotik(poolId);
        },

        async toggleStatusFromMenu() {
            if (!this.activeMenuPool) return;
            const pool = this.activeMenuPool;
            this.activeMenuPool = null;
            try {
                const response = await fetch(`/admin/network/ip-pools/${pool.id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Failed to update status.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while updating pool status.', 'error');
            }
        },

        deleteFromMenu() {
            if (!this.activeMenuPool) return;
            const pool = this.activeMenuPool;
            this.activeMenuPool = null;
            this.confirmDelete(pool.id, pool.name);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        copyToClipboard(text, label = 'Text') {
            if (!text) return;
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

        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.form = {
                id: null,
                name: '',
                pool_type: 'pppoe',
                ip_version: 'ipv4',
                ranges: '',
                cidr_subnet: '',
                gateway: '',
                dns_primary: '8.8.8.8',
                dns_secondary: '1.1.1.1',
                next_pool: '',
                router_id: '',
                vlan_id: '',
                status: 'active',
                description: '',
                is_sync_mikrotik: false
            };
            this.modal.open = true;
        },

        openEditModal(poolData) {
            this.modal.isEdit = true;
            const currentRange = poolData.range_start 
                ? (poolData.range_end && poolData.range_end !== poolData.range_start ? `${poolData.range_start}-${poolData.range_end}` : poolData.range_start) 
                : '';

            this.modal.form = {
                id: poolData.id,
                name: poolData.name || '',
                pool_type: poolData.pool_type || 'pppoe',
                ip_version: poolData.ip_version || 'ipv4',
                ranges: currentRange,
                cidr_subnet: poolData.cidr_subnet || '',
                gateway: poolData.gateway || '',
                dns_primary: poolData.dns_primary || '8.8.8.8',
                dns_secondary: poolData.dns_secondary || '1.1.1.1',
                next_pool: poolData.next_pool || '',
                router_id: poolData.router_id ? String(poolData.router_id) : '',
                vlan_id: poolData.vlan_id || '',
                status: poolData.status || 'active',
                description: poolData.description || poolData.comment || '',
                is_sync_mikrotik: Boolean(poolData.is_sync_mikrotik)
            };
            this.modal.open = true;
        },

        closeModal() {
            this.modal.open = false;
        },

        async savePool() {
            if (!this.modal.form.name || !this.modal.form.ranges) {
                this.showToast('Please fill all required fields (Pool Name, Addresses / Range).', 'error');
                return;
            }

            this.modal.saving = true;
            const url = this.modal.isEdit 
                ? `/admin/network/ip-pools/${this.modal.form.id}` 
                : `/admin/network/ip-pools`;
            const method = this.modal.isEdit ? 'PUT' : 'POST';

            const payload = {
                ...this.modal.form,
                description: this.modal.form.description,
                comment: this.modal.form.description
            };

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.closeModal();
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Failed to save IP pool.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while saving IP pool.', 'error');
            } finally {
                this.modal.saving = false;
            }
        },

        confirmDelete(id, name) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete IP Pool?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete IP pool <strong class="text-slate-900 font-semibold">${name}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">Assigned IP allocations and dynamic bindings will be removed.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete Pool',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeDelete(id);
                    }
                });
            } else if (confirm(`Are you sure you want to delete IP pool '${name}'?`)) {
                this.executeDelete(id);
            }
        },

        async executeDelete(id) {
            try {
                const response = await fetch(`/admin/network/ip-pools/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Failed to delete IP pool.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while deleting IP pool.', 'error');
            }
        },

        openImportModal() {
            this.importModal.open = true;
        },

        closeImportModal() {
            this.importModal.open = false;
        },

        async syncFromMikrotik() {
            if (!this.importModal.router_id) {
                this.showToast('Please select a MikroTik router first.', 'error');
                return;
            }
            this.importModal.syncing = true;
            try {
                const response = await fetch('/admin/network/ip-pools/import-mikrotik', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ router_id: this.importModal.router_id })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.closeImportModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Failed to sync from MikroTik.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while communicating with MikroTik.', 'error');
            } finally {
                this.importModal.syncing = false;
            }
        },

        async syncMikrotik(id) {
            try {
                const response = await fetch(`/admin/network/ip-pools/${id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to sync with MikroTik.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while syncing with MikroTik.', 'error');
            }
        }
    };
}
</script>
@endpush
