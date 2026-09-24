@extends('tenant.layouts.app')

@section('title', 'VLAN Management - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="vlanManager()" @scroll.window="activeMenuVlan = null" @resize.window="activeMenuVlan = null">
    
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
                <i class="fas fa-sitemap"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Virtual LANs (VLAN Management)</h1>
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

            <!-- Create VLAN Button -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Create VLAN</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total VLANs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total VLANs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">{{ number_format($totalVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>

        <!-- Card 2: Service VLANs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Service (PPPoE)</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">{{ number_format($serviceVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Card 3: Management VLANs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">Management</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">{{ number_format($mgmtVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 4: Corporate Trunk -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">Corporate Trunk</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700">{{ number_format($corporateVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Card 5: Active Status -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Active Status</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($activeVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 6: MikroTik Synced -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-600">MikroTik Synced</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-700">{{ number_format($syncedVlans) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-arrows-rotate"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.vlans') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Name, VLAN ID, Interface, Subnet..." 
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

            <!-- Type Filter -->
            <div>
                <select name="type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Types</option>
                    <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service (PPPoE)</option>
                    <option value="management" {{ request('type') === 'management' ? 'selected' : '' }}>Management (OLT/SW)</option>
                    <option value="corporate" {{ request('type') === 'corporate' ? 'selected' : '' }}>Corporate Trunk</option>
                    <option value="cgnat" {{ request('type') === 'cgnat' ? 'selected' : '' }}>CGNAT Trunk</option>
                    <option value="voice" {{ request('type') === 'voice' ? 'selected' : '' }}>VoIP Voice</option>
                    <option value="tr069" {{ request('type') === 'tr069' ? 'selected' : '' }}>TR-069 ACS</option>
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
                <a href="{{ route('tenant.network.vlans') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Pure CSS System Compliant) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Master VLAN Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $vlans->total() }} VLANs
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>Router</th>
                        <th class="text-center">VLAN ID</th>
                        <th>Name</th>
                        <th>Parent Interface</th>
                        <th>Type</th>
                        <th>Subnet / IP</th>
                        <th>Comment</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($vlans as $index => $vlan)
                        @php
                            $vlanPayload = [
                                'id' => $vlan->id,
                                'vlan_id' => $vlan->vlan_id,
                                'name' => $vlan->name,
                                'interface' => $vlan->interface,
                                'type' => $vlan->type,
                                'subnet' => $vlan->subnet,
                                'gateway' => $vlan->gateway,
                                'dhcp_enabled' => (bool)$vlan->dhcp_enabled,
                                'mtu' => $vlan->mtu,
                                'router_id' => $vlan->router_id,
                                'olt_id' => $vlan->olt_id,
                                'status' => $vlan->status,
                                'description' => $vlan->description,
                                'is_sync_mikrotik' => (bool)$vlan->is_sync_mikrotik,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $vlans->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if($vlan->status === 'active')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
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
                                @if($vlan->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $vlan->router_id) }}" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-[10px] font-mono transition" title="{{ $vlan->router->ip_address }}">
                                        <i class="fas fa-server text-[8px] text-indigo-500"></i>
                                        <span class="truncate max-w-[110px]">{{ $vlan->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono text-[10px]">Global</span>
                                @endif
                            </td>

                            <!-- 4. VLAN ID -->
                            <td class="text-center font-mono font-bold text-slate-900">
                                <span class="bg-slate-100 text-slate-800 border border-slate-200 px-2 py-0.5 rounded text-[10px]">
                                    {{ $vlan->vlan_id }}
                                </span>
                            </td>

                            <!-- 5. Name -->
                            <td class="text-cyan-800">
                                <button type="button" 
                                        @click="openEditModal({{ Js::from($vlanPayload) }})" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $vlan->name }}
                                </button>
                            </td>

                            <!-- 6. Parent Interface -->
                            <td class="font-mono text-[10px]">
                                <span class="text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">
                                    {{ $vlan->interface }}
                                </span>
                            </td>

                            <!-- 7. Type -->
                            <td class="font-mono text-[10px]">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $vlan->type_badge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ $vlan->type_badge['label'] ?? ucfirst($vlan->type) }}
                                </span>
                            </td>

                            <!-- 8. Subnet / IP -->
                            <td class="font-mono text-slate-800">
                                @if($vlan->subnet || $vlan->gateway)
                                    <span class="inline-flex items-center gap-1">
                                        <span>{{ $vlan->subnet ?: $vlan->gateway }}</span>
                                        <button type="button" 
                                                @click="copyToClipboard('{{ $vlan->subnet ?: $vlan->gateway }}', 'Subnet')" 
                                                class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                                title="Copy Subnet">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 9. Comment -->
                            <td class="text-slate-600 max-w-[140px] truncate" title="{{ $vlan->description }}">
                                {{ $vlan->description ?: '--' }}
                            </td>

                            <!-- 10. Actions (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($vlanPayload) }}, $event)" 
                                        class="vlan-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
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
                                        <i class="fas fa-sitemap"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No VLANs Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Create 802.1Q VLANs or sync existing VLAN interfaces directly from your MikroTik core router.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openCreateModal()" 
                                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Create VLAN</span>
                                        </button>
                                        <button type="button" 
                                                @click="openImportModal()" 
                                                class="border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-rotate text-purple-600 text-xs"></i>
                                            <span>Sync from MikroTik</span>
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
        @if($vlans->hasPages() || $vlans->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $vlans->firstItem() ?? 0 }} to {{ $vlans->lastItem() ?? 0 }} of {{ $vlans->total() }} results
                </div>
                <div>
                    {{ $vlans->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenuVlan !== null" 
         @click.away="activeMenuVlan = null"
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
        
        <!-- Group 1: Configuration & Management -->
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
                <i class="fas w-3.5 text-center text-[10px]" :class="activeMenuVlan?.status === 'active' ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500'"></i>
                <span x-text="activeMenuVlan?.status === 'active' ? 'Disable VLAN' : 'Enable VLAN'"></span>
            </button>
        </div>

        <!-- Group 2: Destructive Action -->
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete VLAN</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Create & Edit VLAN Modal -->
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
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="modal.isEdit ? 'Edit VLAN' : 'Create New VLAN'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Configure 802.1Q VLAN interface properties and router assignment</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <div class="p-4 space-y-3.5 max-h-[70vh] overflow-y-auto text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Router Selector -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Target MikroTik Router</label>
                        <select x-model="modal.form.router_id" 
                                @change="fetchRouterInterfaces()"
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="">None / Standalone VLAN</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Associated OLT (Optional) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Associated OLT (Optional)</label>
                        <select x-model="modal.form.olt_id" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="">None / All OLTs</option>
                            @foreach($olts as $o)
                                <option value="{{ $o->id }}">{{ $o->name }} ({{ $o->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- VLAN ID -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            VLAN ID (1–4094) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               x-model="modal.form.vlan_id" 
                               placeholder="e.g. 100" 
                               min="1" max="4094"
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- VLAN Name -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            VLAN Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="modal.form.name" 
                               placeholder="e.g. vlan100-pppoe" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Parent Interface -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[11px] font-semibold text-slate-700">
                                Parent Interface <span class="text-rose-500">*</span>
                            </label>
                            <span x-show="modal.loadingInterfaces" class="text-[10px] text-cyan-600 font-mono flex items-center gap-1">
                                <i class="fas fa-spinner fa-spin text-[9px]"></i> Loading...
                            </span>
                        </div>
                        <select x-model="modal.form.interface" 
                                :disabled="modal.loadingInterfaces"
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <template x-for="iface in modal.availableInterfaces" :key="iface">
                                <option :value="iface" x-text="iface" :selected="modal.form.interface === iface"></option>
                            </template>
                            <template x-if="modal.availableInterfaces.length === 0">
                                <option value="ether1">ether1 (Default)</option>
                            </template>
                        </select>
                    </div>

                    <!-- VLAN Service Type -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            VLAN Service Type <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="modal.form.type" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="service">Service (PPPoE / Client Access)</option>
                            <option value="management">Management (OLT / Switch Mgmt)</option>
                            <option value="corporate">Corporate Leased Line / Trunk</option>
                            <option value="cgnat">CGNAT Trunk Block</option>
                            <option value="voice">VoIP Voice SIP</option>
                            <option value="tr069">TR-069 ACS Auto-Config</option>
                        </select>
                    </div>

                    <!-- Subnet (Optional) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">IP Subnet (Optional)</label>
                        <input type="text" 
                               x-model="modal.form.subnet" 
                               placeholder="e.g. 10.10.100.0/24" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Gateway IP (Optional) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Gateway IP (Optional)</label>
                        <input type="text" 
                               x-model="modal.form.gateway" 
                               placeholder="e.g. 10.10.100.1" 
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- MTU Size -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">MTU Size</label>
                        <input type="number" 
                               x-model="modal.form.mtu" 
                               placeholder="1500" 
                               min="576" max="9216"
                               class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Administrative Status</label>
                        <select x-model="modal.form.status" 
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                            <option value="active">Active / Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>

                    <!-- Comment / Description -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Comment (MikroTik Interface Comment)</label>
                        <textarea x-model="modal.form.description" 
                                  rows="2"
                                  placeholder="e.g. PPPoE VLAN for Core Switch POP-1" 
                                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal"></textarea>
                    </div>

                    <!-- MikroTik Sync Checkbox -->
                    <div class="sm:col-span-2 pt-1 border-t border-slate-100 flex items-center gap-2">
                        <input type="checkbox" 
                               id="is_sync_mikrotik" 
                               x-model="modal.form.is_sync_mikrotik" 
                               class="rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                        <label for="is_sync_mikrotik" class="text-xs text-slate-700 cursor-pointer select-none">
                            Automatically push & sync this VLAN interface to the selected MikroTik Router (<code class="text-cyan-700 font-mono text-[10.5px]">/interface/vlan</code>)
                        </label>
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
                        @click="saveVlan()" 
                        :disabled="modal.saving"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': modal.saving }"></i>
                    <span x-text="modal.saving ? 'Saving...' : (modal.isEdit ? 'Save Changes' : 'Create VLAN')"></span>
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
                        <h3 class="text-xs font-semibold text-slate-800">Sync VLANs from MikroTik</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Import /interface/vlan configurations</p>
                    </div>
                </div>
                <button type="button" @click="closeImportModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs">
                <p class="text-slate-600 leading-relaxed font-normal">
                    Select a MikroTik router below. All configured VLAN interfaces (<code class="bg-slate-100 px-1 py-0.5 rounded text-purple-700 font-mono text-[11px]">/interface/vlan</code>) will be imported and synchronized directly with your database.
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
                        <li>Imports VLAN IDs, interface bindings, and comments.</li>
                        <li>Links with associated parent interfaces automatically.</li>
                        <li>Updates matching VLANs without duplicating records.</li>
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
function vlanManager() {
    return {
        activeMenuVlan: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        modal: {
            open: false,
            isEdit: false,
            saving: false,
            loadingInterfaces: false,
            availableInterfaces: ['ether1', 'ether2', 'ether3', 'ether4', 'ether5', 'bridge', 'sfp-sfpplus1'],
            form: {
                id: null,
                vlan_id: '',
                name: '',
                interface: 'ether1',
                type: 'service',
                subnet: '',
                gateway: '',
                dhcp_enabled: false,
                mtu: 1500,
                router_id: '{{ $routers->first()?->id ?? "" }}',
                olt_id: '',
                status: 'active',
                description: '',
                is_sync_mikrotik: true
            }
        },

        importModal: {
            open: false,
            router_id: '{{ $routers->first()?->id ?? "" }}',
            syncing: false
        },

        toggleMenu(vlan, event) {
            if (this.activeMenuVlan && this.activeMenuVlan.id === vlan.id) {
                this.activeMenuVlan = null;
                return;
            }
            this.activeMenuVlan = vlan;

            let targetEl = event.currentTarget || event.target.closest('button');
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
        },

        editFromMenu() {
            if (!this.activeMenuVlan) return;
            const vlan = this.activeMenuVlan;
            this.activeMenuVlan = null;
            this.openEditModal(vlan);
        },

        syncFromMenu() {
            if (!this.activeMenuVlan) return;
            const vlanId = this.activeMenuVlan.id;
            this.activeMenuVlan = null;
            this.syncMikrotik(vlanId);
        },

        async toggleStatusFromMenu() {
            if (!this.activeMenuVlan) return;
            const vlan = this.activeMenuVlan;
            this.activeMenuVlan = null;
            try {
                const response = await fetch(`/admin/network/vlans/${vlan.id}/toggle-status`, {
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
                this.showToast('Network error while updating VLAN status.', 'error');
            }
        },

        deleteFromMenu() {
            if (!this.activeMenuVlan) return;
            const vlan = this.activeMenuVlan;
            this.activeMenuVlan = null;
            this.confirmDelete(vlan.id, `${vlan.name} (VLAN ID: ${vlan.vlan_id})`);
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
                vlan_id: '',
                name: '',
                interface: 'ether1',
                type: 'service',
                subnet: '',
                gateway: '',
                dhcp_enabled: false,
                mtu: 1500,
                router_id: '{{ $routers->first()?->id ?? "" }}',
                olt_id: '',
                status: 'active',
                description: '',
                is_sync_mikrotik: true
            };
            this.modal.open = true;
            if (this.modal.form.router_id) {
                this.fetchRouterInterfaces();
            }
        },

        openEditModal(vlanData) {
            this.modal.isEdit = true;
            const currentIface = vlanData.interface || 'ether1';
            this.modal.form = {
                id: vlanData.id,
                vlan_id: vlanData.vlan_id,
                name: vlanData.name,
                interface: currentIface,
                type: vlanData.type || 'service',
                subnet: vlanData.subnet || '',
                gateway: vlanData.gateway || '',
                dhcp_enabled: Boolean(vlanData.dhcp_enabled),
                mtu: vlanData.mtu || 1500,
                router_id: vlanData.router_id ? String(vlanData.router_id) : '',
                olt_id: vlanData.olt_id ? String(vlanData.olt_id) : '',
                status: vlanData.status || 'active',
                description: vlanData.description || '',
                is_sync_mikrotik: Boolean(vlanData.is_sync_mikrotik)
            };
            if (!this.modal.availableInterfaces.includes(currentIface)) {
                this.modal.availableInterfaces.push(currentIface);
            }
            this.modal.open = true;
            if (this.modal.form.router_id) {
                this.fetchRouterInterfaces(currentIface);
            }
        },

        closeModal() {
            this.modal.open = false;
        },

        async fetchRouterInterfaces(preferredInterface = null) {
            if (!this.modal.form.router_id) {
                this.modal.availableInterfaces = ['ether1', 'ether2', 'ether3', 'ether4', 'ether5', 'bridge', 'sfp-sfpplus1'];
                return;
            }
            this.modal.loadingInterfaces = true;
            try {
                const response = await fetch(`/admin/network/vlans/router-interfaces/${this.modal.form.router_id}`);
                const data = await response.json();
                if (data.success && Array.isArray(data.interfaces) && data.interfaces.length > 0) {
                    this.modal.availableInterfaces = data.interfaces;
                    const target = preferredInterface || this.modal.form.interface;
                    if (target && this.modal.availableInterfaces.includes(target)) {
                        this.modal.form.interface = target;
                    } else if (this.modal.availableInterfaces.length > 0) {
                        this.modal.form.interface = this.modal.availableInterfaces[0];
                    }
                }
            } catch (e) {
                // Keep default fallback list
            } finally {
                this.modal.loadingInterfaces = false;
            }
        },

        async saveVlan() {
            if (!this.modal.form.vlan_id || !this.modal.form.name || !this.modal.form.interface) {
                this.showToast('Please fill in VLAN ID, Name, and Interface.', 'error');
                return;
            }

            this.modal.saving = true;
            const url = this.modal.isEdit 
                ? `/admin/network/vlans/${this.modal.form.id}` 
                : '/admin/network/vlans';
            const method = this.modal.isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.modal.form)
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.closeModal();
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    this.showToast(data.message || 'Validation error while saving VLAN.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while saving VLAN.', 'error');
            } finally {
                this.modal.saving = false;
            }
        },

        confirmDelete(id, name) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete VLAN?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete VLAN <strong class="text-slate-900 font-semibold">${name}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">If synced with MikroTik, it will also be removed from the router.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete VLAN',
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
            } else if (confirm(`Are you sure you want to delete VLAN '${name}'?`)) {
                this.executeDelete(id);
            }
        },

        async executeDelete(id) {
            try {
                const response = await fetch(`/admin/network/vlans/${id}`, {
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
                    this.showToast(data.message || 'Failed to delete VLAN.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while deleting VLAN.', 'error');
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
                const response = await fetch('/admin/network/vlans/import-mikrotik', {
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
                const response = await fetch(`/admin/network/vlans/${id}/sync`, {
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
                    this.showToast(data.message || 'Failed to sync VLAN with MikroTik.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while syncing with MikroTik.', 'error');
            }
        }
    };
}
</script>
@endpush
