@extends('tenant.layouts.app')

@section('title', 'Coverage Zones & Areas - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" 
     x-data="zoneManager()" 
     @scroll.window="activeMenu = null" 
     @resize.window="activeMenu = null">

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

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-location-dot"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">Coverage Zones &amp; Operational Areas</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('tenant.customers.zones.import-template') }}" 
               class="px-3 py-1.5 rounded-md bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer"
               title="Download standard Excel / CSV template">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>Download Excel Template</span>
            </a>
            <button type="button" 
                    @click="openImportModal()" 
                    class="px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-import text-[11px]"></i>
                <span>Import Excel / CSV</span>
            </button>
            <button type="button" 
                    @click="openCreateModal()" 
                    class="px-3 py-1.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>+ Add New Zone</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Zones -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Zones</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-map-location-dot"></i>
            </div>
        </div>

        <!-- Metric 2: Active Zones -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Active Zones</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($activeZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 3: Inactive Zones -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Inactive Zones</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($inactiveZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-ban"></i>
            </div>
        </div>

        <!-- Metric 4: Total Assigned Subscribers -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Subscribers</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($totalSubscribersAssigned) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 5: Top Zone Density -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Top Density Zone</span>
                <span class="text-[11px] font-bold text-slate-800 font-mono leading-tight block truncate" title="{{ $topZoneName }}">{{ $topZoneName }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-ranking-star"></i>
            </div>
        </div>

        <!-- Metric 6: Assigned Staff / Tech -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Assigned Tech / Staff</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($assignedStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-user-gear"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Bar -->
    <div class="bg-white p-2.5 rounded-md border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.customers.zones') }}" class="flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex flex-1 items-center gap-2 w-full flex-wrap sm:flex-nowrap">
                <!-- Search Box -->
                <div class="relative flex-1 min-w-[180px] w-full">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-search text-[11px]"></i>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ $search ?? '' }}" 
                           placeholder="Search by zone name, code, staff, phone..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                @if(!$isResellerUser)
                    <!-- Reseller / Scope Filter -->
                    <select name="reseller_id" class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ ($selectedResellerId ?? 'all') === 'all' ? 'selected' : '' }}>All Partners / Direct</option>
                        <option value="isp" {{ ($selectedResellerId ?? '') === 'isp' ? 'selected' : '' }}>HQ Direct Areas</option>
                        @foreach($allResellers ?? [] as $r)
                            <option value="{{ $r->id }}" {{ ($selectedResellerId ?? '') == $r->id ? 'selected' : '' }}>
                                Partner: {{ $r->code ? "[{$r->code}] " : '' }}{{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Status Filter -->
                <select name="status" class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <!-- Items Per Page -->
                <select name="per_page" class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="10" {{ ($perPage ?? 20) == 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="20" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100 / page</option>
                </select>

                <!-- Filter & Reset Buttons (Strict Universal Standard) -->
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.customers.zones') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-16">Code</th>
                        <th>Zone / Area Name</th>
                        <th>Sub Areas / উপ-এলাকা</th>
                        <th>Affiliation / Partner</th>
                        <th>City / Upazila</th>
                        <th>Assigned Staff / Tech</th>
                        <th>Contact Phone</th>
                        <th class="text-center">Subscribers</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($zones as $z)
                        @php
                            $subCount = $z->subscribers_count;
                            $assignedStaffName = $z->assignedStaff?->name ?: ($z->in_charge_name ?: '');
                            $assignedStaffPhone = $z->assignedStaff?->phone ?: ($z->in_charge_phone ?: '');
                            $assignedStaffRole = $z->assignedStaff?->role_badge['label'] ?? '';

                            $subAreasList = $z->sub_areas_list;
                            $zoneData = [
                                'id' => $z->id,
                                'code' => $z->code,
                                'name' => $z->name,
                                'sub_areas' => $subAreasList,
                                'sub_areas_count' => count($subAreasList),
                                'reseller_id' => $z->reseller_id ? (string)$z->reseller_id : '',
                                'reseller_name' => $z->reseller?->name ?? 'HQ Direct',
                                'reseller_code' => $z->reseller?->code ?? '',
                                'staff_id' => $z->staff_id ? (string)$z->staff_id : '',
                                'staff_name' => $assignedStaffName,
                                'staff_phone' => $assignedStaffPhone,
                                'staff_role' => $assignedStaffRole,
                                'city_upazila' => $z->city_upazila ?? '',
                                'in_charge_name' => $z->in_charge_name ?? '',
                                'in_charge_phone' => $z->in_charge_phone ?? '',
                                'description' => $z->description ?? '',
                                'is_active' => (bool)$z->is_active,
                                'subscribers_count' => $subCount,
                                'active_subscribers' => $z->active_subscribers_count,
                                'mrr' => $z->total_mrr,
                                'created_at' => $z->created_at?->format('d M, Y h:i A') ?? '--'
                            ];
                        @endphp
                        <tr class="whitespace-nowrap">
                            <!-- 1. Code -->
                            <td class="font-mono text-cyan-800 font-semibold whitespace-nowrap">
                                {{ $z->code }}
                            </td>

                            <!-- 2. Zone Name -->
                            <td class="font-semibold text-slate-900 whitespace-nowrap">
                                {{ $z->name }}
                            </td>

                            <!-- 3. Sub Areas (Dedicated Column - Strict Single-Line / Nowrap) -->
                            <td class="whitespace-nowrap">
                                @if(!empty($subAreasList))
                                    <div class="flex items-center gap-1 flex-nowrap whitespace-nowrap">
                                        @foreach(array_slice($subAreasList, 0, 2) as $sa)
                                            <span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-medium border border-slate-200/80 whitespace-nowrap">{{ $sa }}</span>
                                        @endforeach
                                        @if(count($subAreasList) > 2)
                                            <button type="button" 
                                                    @click="openSubAreasModal({{ Js::from($zoneData) }})"
                                                    class="text-[10px] font-semibold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 px-1.5 py-0.5 rounded border border-cyan-200/70 transition cursor-pointer whitespace-nowrap" 
                                                    title="{{ implode(', ', array_slice($subAreasList, 2)) }}">
                                                +{{ count($subAreasList) - 2 }} more
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <button type="button" 
                                            @click="openSubAreasModal({{ Js::from($zoneData) }})"
                                            class="text-[10.5px] text-slate-400 hover:text-cyan-700 inline-flex items-center gap-1 transition cursor-pointer whitespace-nowrap">
                                        <i class="fas fa-plus text-[9px]"></i>
                                        <span>Add Sub-Area</span>
                                    </button>
                                @endif
                            </td>

                            <!-- 4. Affiliation / Partner -->
                            <td class="whitespace-nowrap">
                                @if($z->reseller)
                                    <span class="inline-flex items-center gap-1 text-cyan-800 font-semibold whitespace-nowrap" title="Managed by Sub-ISP Reseller: {{ $z->reseller->name }}">
                                        <i class="fas fa-handshake text-cyan-600 text-[10px]"></i>
                                        <span>{{ $z->reseller->code ?: $z->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-purple-700 font-semibold whitespace-nowrap" title="ISP Headquarters Direct Territory">
                                        <i class="fas fa-building text-purple-500 text-[10px]"></i>
                                        <span>HQ Direct</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 5. City / Upazila -->
                            <td class="text-slate-600 whitespace-nowrap">
                                {{ $z->city_upazila ?: '--' }}
                            </td>

                            <!-- 6. Assigned Staff / Tech (Strict single data per cell) -->
                            <td class="text-slate-700 whitespace-nowrap" title="{{ $assignedStaffRole ? ($assignedStaffRole . ' - ' . $assignedStaffName) : $assignedStaffName }}">
                                {{ $assignedStaffName ?: '--' }}
                            </td>

                            <!-- 7. Phone -->
                            <td class="font-mono text-slate-600 whitespace-nowrap">
                                {{ $assignedStaffPhone ?: '--' }}
                            </td>

                            <!-- 8. Subscribers Count -->
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.customers.index', ['zone' => $z->name]) }}" 
                                   class="inline-flex items-center gap-1 font-mono font-bold text-xs whitespace-nowrap {{ $subCount > 0 ? 'text-cyan-700 hover:text-cyan-900 underline' : 'text-slate-400' }}"
                                   title="View all subscribers in {{ $z->name }}">
                                    <span>{{ $subCount }}</span>
                                    <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                            </td>

                            <!-- 9. Status Toggle -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleStatus({{ $z->id }}, '{{ addslashes($z->name) }}')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs whitespace-nowrap {{ $z->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}"
                                        title="Click to toggle active status">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $z->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ $z->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </td>

                            <!-- 10. Action (Floating 3-Dot Menu) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($zoneData) }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-map-location text-2xl text-slate-300 mb-2 block"></i>
                                No coverage zones found. Click "+ Add New Zone" to define operational areas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($zones->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50">
                {{ $zones->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 text-left text-xs divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-1">
            <!-- View Details -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openDetailsModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-3.5 text-center text-[11px]"></i>
                <span>View Details</span>
            </button>

            <!-- Manage Sub Areas (Add, Edit, View, Delete) -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openSubAreasModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-layer-group text-emerald-600 w-3.5 text-center text-[11px]"></i>
                <span>Manage Sub Areas</span>
            </button>

            <!-- Edit Zone -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openEditModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-pen-to-square text-amber-600 w-3.5 text-center text-[11px]"></i>
                <span>Edit Zone Details</span>
            </button>

            <!-- View Subscribers in Zone -->
            <a :href="'{{ url('admin/customers') }}?zone=' + encodeURIComponent(activeMenu ? activeMenu.name : '')" 
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-users text-blue-600 w-3.5 text-center text-[11px]"></i>
                <span>View Subscribers</span>
            </a>
        </div>

        <div class="py-1">
            <!-- Toggle Status -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; toggleStatus(item.id, item.name)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 transition text-left cursor-pointer"
                    :class="activeMenu?.is_active ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800'">
                <i class="fas w-3.5 text-center text-[11px]" :class="activeMenu?.is_active ? 'fa-toggle-off text-rose-500' : 'fa-toggle-on text-emerald-600'"></i>
                <span x-text="activeMenu?.is_active ? 'Disable Zone' : 'Enable Zone'"></span>
            </button>

            <!-- Delete Zone -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; confirmDelete(item.id, item.name, item.subscribers_count)" 
                    class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-3.5 text-center text-[11px]"></i>
                <span>Delete Zone</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Add & Edit Coverage Zone -->
    <div x-show="modal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!modal.loading) modal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="modal.isEdit ? 'Edit Coverage Zone' : 'Create New Coverage Zone'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Define coverage area boundaries, assigned technician &amp; contact info</p>
                    </div>
                </div>
                <button type="button" 
                        @click="modal.open = false" 
                        :disabled="modal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitModal()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Zone Name & Code Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Zone / Area Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.name" 
                                   required 
                                   placeholder="e.g. Dhanmondi, Mirpur-10, Uttara Sec-3"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Zone Code
                            </label>
                            <input type="text" 
                                   x-model="modal.form.code" 
                                   placeholder="e.g. ZN-01"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    @if(!$isResellerUser)
                        <!-- Reseller / Partner Assignment (ISP Admin Only) -->
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Assigned Partner / Reseller Scope
                            </label>
                            <select x-model="modal.form.reseller_id" 
                                    @change="onResellerScopeChange()"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                                <option value="">ISP HQ Direct (Direct Retail Territory)</option>
                                @foreach($allResellers ?? [] as $r)
                                    <option value="{{ $r->id }}">
                                        Partner: {{ $r->code ? "[{$r->code}] " : '' }}{{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- City / Upazila -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            City / Upazila / Thana <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" 
                               x-model="modal.form.city_upazila" 
                               placeholder="e.g. Dhaka North, Gazipur Sadar"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>

                    <!-- Assigned Staff / Field Technician Dropdown (Production Relational Linkage) -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px] flex items-center justify-between">
                            <span>Assigned Field Technician / Staff</span>
                            <span class="text-[10px] text-cyan-600 font-normal">From Staff Management</span>
                        </label>
                        <select x-model="modal.form.staff_id" 
                                @change="onStaffSelected()"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                            <option value="">-- No Assigned Staff (Unassigned) --</option>
                            <template x-for="s in filteredStaffList" :key="s.id">
                                <option :value="s.id" x-text="(s.staff_id ? '[' + s.staff_id + '] ' : '') + s.name + ' (' + s.role_label + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Contact Phone & Custom Contact Person Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Contact Phone Number
                            </label>
                            <input type="text" 
                                   x-model="modal.form.in_charge_phone" 
                                   placeholder="e.g. 01712-345678"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Area In-Charge / Alternate Name <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.in_charge_name" 
                                   placeholder="e.g. Md. Rafiqul Islam"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    <!-- Sub Areas (উপ-এলাকা সমূহ) -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Sub Areas / উপ-এলাকা সমূহ <span class="text-slate-400 font-normal">(কমা দিয়ে একাধিক উপ-এলাকা লিখুন)</span>
                        </label>
                        <textarea x-model="modal.form.description" 
                                  rows="2" 
                                  placeholder="যেমন: পশ্চিমপাড়া, পূর্বপাড়া, গুচ্ছগ্রাম, বাজারপাড়া..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition"></textarea>
                    </div>

                    <!-- Active Switch -->
                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="modal.form.is_active" 
                                   class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                            <span class="text-slate-700 text-[11px] font-medium">Zone is active and selectable for new subscriber onboarding</span>
                        </label>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="modal.open = false" 
                            :disabled="modal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="modal.loading || !modal.form.name"
                            class="bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="modal.loading ? 'fa-spinner fa-spin' : (modal.isEdit ? 'fa-check' : 'fa-plus')"></i>
                        <span x-text="modal.loading ? 'Saving...' : (modal.isEdit ? 'Update Zone' : 'Save Coverage Zone')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: View Details -->
    <div x-show="detailsModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="detailsModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.zone?.name || 'Zone Details'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'Code: ' + (detailsModal.zone?.code || '--')"></p>
                    </div>
                </div>
                <button type="button" 
                        @click="detailsModal.open = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Details Body Grid -->
            <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                
                <!-- Quick Metrics Strip -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[9px] font-medium uppercase text-slate-500 block">Total Users</span>
                        <span class="text-xs font-bold text-slate-900 font-mono" x-text="detailsModal.zone?.subscribers_count ?? 0"></span>
                    </div>
                    <div class="p-2 bg-emerald-50/50 rounded-lg border border-emerald-100 text-center">
                        <span class="text-[9px] font-medium uppercase text-emerald-700 block">Active Users</span>
                        <span class="text-xs font-bold text-emerald-700 font-mono" x-text="detailsModal.zone?.active_subscribers ?? 0"></span>
                    </div>
                    <div class="p-2 bg-cyan-50/50 rounded-lg border border-cyan-100 text-center">
                        <span class="text-[9px] font-medium uppercase text-cyan-700 block">Monthly Revenue</span>
                        <span class="text-xs font-bold text-cyan-800 font-mono" x-text="'৳ ' + Number(detailsModal.zone?.mrr || 0).toLocaleString()"></span>
                    </div>
                </div>

                <!-- 2-Column Info Grid -->
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">Scope / Partner</span>
                        <span class="text-xs font-medium text-slate-800 block" x-text="detailsModal.zone?.reseller_name || 'HQ Direct'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">Status</span>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold" :class="detailsModal.zone?.is_active ? 'text-emerald-700' : 'text-rose-600'">
                            <i class="fas fa-circle text-[7px]"></i>
                            <span x-text="detailsModal.zone?.is_active ? 'Active' : 'Inactive'"></span>
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">Assigned Staff / Tech</span>
                        <span class="text-xs font-semibold text-slate-800 block" x-text="detailsModal.zone?.staff_name || '--'"></span>
                        <span class="text-[10px] text-cyan-700 block" x-show="detailsModal.zone?.staff_role" x-text="detailsModal.zone?.staff_role"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">Contact Phone</span>
                        <span class="text-xs font-mono font-medium text-slate-800 block" x-text="detailsModal.zone?.staff_phone || '--'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5 col-span-2">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">City / Upazila</span>
                        <span class="text-xs font-medium text-slate-800 block" x-text="detailsModal.zone?.city_upazila || '--'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5 col-span-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9.5px] font-semibold text-slate-500 uppercase tracking-wider block">
                                Sub Areas / উপ-এলাকা সমূহ (<span x-text="detailsModal.zone?.sub_areas?.length ?? 0"></span>)
                            </span>
                            <button type="button" 
                                    @click="openSubAreasModal(detailsModal.zone)" 
                                    class="text-[10px] text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                <i class="fas fa-layer-group text-[9px]"></i>
                                <span>Manage Sub Areas</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap" x-show="detailsModal.zone?.sub_areas && detailsModal.zone?.sub_areas?.length > 0">
                            <template x-for="(sa, idx) in (detailsModal.zone?.sub_areas ?? [])" :key="idx">
                                <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md text-[11px] font-medium text-slate-700 shadow-2xs" x-text="sa"></span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-400 italic" x-show="!detailsModal.zone?.sub_areas || detailsModal.zone?.sub_areas?.length === 0">
                            No sub-areas defined for this zone.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="detailsModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Close
                </button>

                <button type="button" 
                        @click="const item = detailsModal.zone; detailsModal.open = false; openEditModal(item)" 
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas fa-pen-to-square text-[11px]"></i>
                    <span>Edit Zone</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: Manage Sub Areas (View, Add, Edit, Delete) -->
    <div x-show="subAreasModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!subAreasModal.loading) subAreasModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">
                            Sub Areas / উপ-এলাকা - <span class="text-cyan-700" x-text="subAreasModal.zone?.name"></span>
                        </h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Add, edit, or delete operational sub-areas in this zone</p>
                    </div>
                </div>
                <button type="button" 
                        @click="subAreasModal.open = false" 
                        :disabled="subAreasModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-4 text-xs">
                <!-- Add New Sub-Area Form Strip -->
                <div class="space-y-1">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        + Add New Sub-Area (নতুন উপ-এলাকা যুক্ত করুন)
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               x-model="subAreasModal.newSubArea" 
                               @keydown.enter.prevent="addSubArea()"
                               placeholder="যেমন: পশ্চিমপাড়া / চারমাথা / বাজার" 
                               class="flex-1 bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <button type="button" 
                                @click="addSubArea()" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-plus text-[10px]"></i>
                            <span>Add</span>
                        </button>
                    </div>
                </div>

                <!-- Sub-Areas List -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-600 font-semibold">
                        <span>Current Sub-Areas (<span x-text="subAreasModal.items.length"></span>)</span>
                        <span class="text-slate-400 font-normal text-[10px]">Click pencil to edit or trash to delete</span>
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 divide-y divide-slate-100 border border-slate-200 rounded-lg p-2 bg-slate-50/50">
                        <template x-for="(item, idx) in subAreasModal.items" :key="idx">
                            <div class="pt-1.5 first:pt-0 flex items-center justify-between gap-2">
                                <!-- View Mode -->
                                <div class="flex items-center gap-2 flex-1 min-w-0" x-show="subAreasModal.editingIndex !== idx">
                                    <span class="w-5 h-5 rounded-full bg-slate-200/80 text-slate-600 flex items-center justify-center text-[10px] font-mono flex-shrink-0" x-text="idx + 1"></span>
                                    <span class="text-xs font-medium text-slate-800 truncate" x-text="item"></span>
                                </div>

                                <!-- Inline Edit Mode -->
                                <div class="flex items-center gap-1.5 flex-1 min-w-0" x-show="subAreasModal.editingIndex === idx">
                                    <input type="text" 
                                           x-model="subAreasModal.editingValue" 
                                           @keydown.enter.prevent="saveEditSubArea(idx)" 
                                           class="w-full bg-white border border-cyan-400 rounded-md text-xs px-2 py-1 text-slate-800 focus:outline-hidden">
                                    <button type="button" 
                                            @click="saveEditSubArea(idx)" 
                                            class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center text-[10px] hover:bg-emerald-700 cursor-pointer" 
                                            title="Save">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" 
                                            @click="subAreasModal.editingIndex = null" 
                                            class="w-6 h-6 rounded bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] hover:bg-slate-300 cursor-pointer" 
                                            title="Cancel">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <!-- Action Buttons (when not editing) -->
                                <div class="flex items-center gap-1 flex-shrink-0" x-show="subAreasModal.editingIndex !== idx">
                                    <button type="button" 
                                            @click="startEditSubArea(idx, item)" 
                                            class="w-6 h-6 rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition flex items-center justify-center text-[10px] cursor-pointer" 
                                            title="Edit Name">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" 
                                            @click="deleteSubArea(idx)" 
                                            class="w-6 h-6 rounded hover:bg-rose-100 text-slate-400 hover:text-rose-600 transition flex items-center justify-center text-[10px] cursor-pointer" 
                                            title="Delete Sub-Area">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="subAreasModal.items.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                            <i class="fas fa-map-pin text-xl text-slate-300 mb-1.5 block"></i>
                            No sub-areas added yet. Type a name above to add.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="subAreasModal.open = false" 
                        :disabled="subAreasModal.loading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Cancel
                </button>

                <button type="button" 
                        @click="saveSubAreasToServer()" 
                        :disabled="subAreasModal.loading"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas" :class="subAreasModal.loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="subAreasModal.loading ? 'Saving...' : 'Save Sub Areas'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: Import Excel / CSV -->
    <div x-show="importModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="if (!importModal.loading) importModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Import Coverage Zones & Areas') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Upload Excel or CSV file to import operational areas & sub-areas') }}</p>
                    </div>
                </div>
                <button type="button" 
                        @click="importModal.open = false" 
                        :disabled="importModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitImport()" class="p-4 space-y-3.5 text-xs">
                <!-- Template Download Alert Strip -->
                <div class="flex items-center justify-between p-2.5 bg-cyan-50/60 border border-cyan-100 rounded-lg text-cyan-800">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-info-circle text-cyan-600"></i>
                        <span class="text-[11px] font-medium">{{ __('Need formatting guidelines? Download standard template.') }}</span>
                    </div>
                    <a href="{{ route('tenant.customers.zones.import-template') }}" 
                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white border border-cyan-200 hover:bg-cyan-50 text-cyan-700 text-[10.5px] font-semibold transition shadow-2xs">
                        <i class="fas fa-download text-[10px]"></i>
                        <span>Template (CSV)</span>
                    </a>
                </div>

                <!-- Drag & Drop Zone -->
                <div class="relative border-2 border-dashed rounded-xl p-5 text-center transition cursor-pointer"
                     :class="importModal.isDragging ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 hover:border-emerald-400 bg-slate-50/50 hover:bg-slate-50'"
                     @dragover.prevent="importModal.isDragging = true"
                     @dragleave.prevent="importModal.isDragging = false"
                     @drop.prevent="handleImportDrop($event)">
                    
                    <input type="file" 
                           id="zone_import_file" 
                           accept=".csv,.xlsx,.xls"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                           @change="handleImportFileSelect($event)">

                    <template x-if="!importModal.fileName">
                        <div class="flex flex-col items-center justify-center space-y-1.5 pointer-events-none">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg shadow-xs">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-800">Click to browse or drag and drop your file here</p>
                            <p class="text-[10.5px] text-slate-400">Supports standard CSV, UTF-8 encoded Excel/CSV up to 10MB</p>
                        </div>
                    </template>

                    <template x-if="importModal.fileName">
                        <div class="flex items-center justify-center gap-3 pointer-events-none">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-slate-800" x-text="importModal.fileName"></p>
                                <p class="text-[10.5px] text-emerald-700 font-mono font-semibold" x-text="importModal.fileSize"></p>
                            </div>
                            <button type="button" 
                                    @click.stop="clearImportFile()" 
                                    class="pointer-events-auto ml-2 w-5 h-5 rounded-full bg-slate-200 hover:bg-rose-100 hover:text-rose-600 text-slate-500 flex items-center justify-center text-[9px] transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Options Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <!-- Duplicate Action -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Duplicate Action</label>
                        <select x-model="importModal.duplicateAction" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="skip">Skip Duplicates</option>
                            <option value="update">Update Existing by Name/Code</option>
                        </select>
                    </div>

                    <!-- Default Reseller Scope -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default Scope / Reseller</label>
                        <select x-model="importModal.defaultResellerId" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="isp">ISP Headquarters (HQ Direct)</option>
                            @foreach($allResellers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code ?: 'RES-' . $r->id }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="importModal.open = false" 
                            :disabled="importModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="importModal.loading || !importModal.file"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="importModal.loading ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                        <span x-text="importModal.loading ? 'Importing...' : 'Upload & Import'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function zoneManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },

        staffList: @json($staffListJson ?? []),

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        modal: {
            open: false,
            isEdit: false,
            loading: false,
            editId: null,
            form: {
                name: '',
                code: '',
                reseller_id: '',
                staff_id: '',
                city_upazila: '',
                in_charge_name: '',
                in_charge_phone: '',
                description: '',
                is_active: true
            }
        },

        detailsModal: {
            open: false,
            zone: null
        },

        subAreasModal: {
            open: false,
            loading: false,
            zone: null,
            items: [],
            newSubArea: '',
            editingIndex: null,
            editingValue: '',
        },

        importModal: {
            open: false,
            loading: false,
            isDragging: false,
            file: null,
            fileName: '',
            fileSize: '',
            duplicateAction: 'skip',
            defaultResellerId: 'isp',
        },

        get filteredStaffList() {
            if (!this.modal.form.reseller_id) {
                // HQ Direct: Show HQ staff (where reseller_id is empty) or all staff
                return this.staffList.filter(s => !s.reseller_id);
            }
            // Reseller Scope: Show this reseller's staff + HQ staff
            return this.staffList.filter(s => s.reseller_id === String(this.modal.form.reseller_id) || !s.reseller_id);
        },

        onResellerScopeChange() {
            // Check if current staff_id is still valid in filtered list
            if (this.modal.form.staff_id) {
                const isValid = this.filteredStaffList.some(s => String(s.id) === String(this.modal.form.staff_id));
                if (!isValid) {
                    this.modal.form.staff_id = '';
                }
            }
        },

        onStaffSelected() {
            if (!this.modal.form.staff_id) return;
            const staff = this.staffList.find(s => String(s.id) === String(this.modal.form.staff_id));
            if (staff) {
                this.modal.form.in_charge_name = staff.name;
                if (staff.phone) {
                    this.modal.form.in_charge_phone = staff.phone;
                }
            }
        },

        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.editId = null;
            this.modal.form = {
                name: '',
                code: '',
                reseller_id: '',
                staff_id: '',
                city_upazila: '',
                in_charge_name: '',
                in_charge_phone: '',
                description: '',
                is_active: true
            };
            this.modal.open = true;
        },

        openEditModal(item) {
            this.modal.isEdit = true;
            this.modal.editId = item.id;
            this.modal.form = {
                name: item.name || '',
                code: item.code || '',
                reseller_id: item.reseller_id || '',
                staff_id: item.staff_id || '',
                city_upazila: item.city_upazila || '',
                in_charge_name: item.in_charge_name || item.staff_name || '',
                in_charge_phone: item.in_charge_phone || item.staff_phone || '',
                description: item.description || '',
                is_active: item.is_active !== undefined ? item.is_active : true
            };
            this.modal.open = true;
        },

        openDetailsModal(item) {
            this.detailsModal.zone = item;
            this.detailsModal.open = true;
        },

        openSubAreasModal(zone) {
            this.subAreasModal.zone = zone;
            this.subAreasModal.items = Array.isArray(zone.sub_areas) ? [...zone.sub_areas] : [];
            this.subAreasModal.newSubArea = '';
            this.subAreasModal.editingIndex = null;
            this.subAreasModal.editingValue = '';
            this.subAreasModal.open = true;
        },

        addSubArea() {
            const val = this.subAreasModal.newSubArea.trim();
            if (!val) return;
            const splitVals = val.split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
            splitVals.forEach(s => {
                if (!this.subAreasModal.items.includes(s)) {
                    this.subAreasModal.items.push(s);
                }
            });
            this.subAreasModal.newSubArea = '';
        },

        startEditSubArea(idx, currentVal) {
            this.subAreasModal.editingIndex = idx;
            this.subAreasModal.editingValue = currentVal;
        },

        saveEditSubArea(idx) {
            const val = this.subAreasModal.editingValue.trim();
            if (val) {
                this.subAreasModal.items[idx] = val;
            }
            this.subAreasModal.editingIndex = null;
            this.subAreasModal.editingValue = '';
        },

        deleteSubArea(idx) {
            this.subAreasModal.items.splice(idx, 1);
            if (this.subAreasModal.editingIndex === idx) {
                this.subAreasModal.editingIndex = null;
            }
        },

        async saveSubAreasToServer() {
            if (!this.subAreasModal.zone) return;
            this.subAreasModal.loading = true;
            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${this.subAreasModal.zone.id}/sub-areas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sub_areas: this.subAreasModal.items
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.subAreasModal.open = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Failed to update sub-areas.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving sub-areas.', 'error');
            } finally {
                this.subAreasModal.loading = false;
            }
        },

        async submitModal() {
            this.modal.loading = true;
            const url = this.modal.isEdit 
                ? `{{ url('admin/customers/zones') }}/${this.modal.editId}` 
                : `{{ route('tenant.customers.zones.store') }}`;
            const method = this.modal.isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.modal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.modal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Operation failed. Please check inputs.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving zone details.', 'error');
            } finally {
                this.modal.loading = false;
            }
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 220;
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

        async toggleStatus(id, name) {
            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Status toggle failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while updating status.', 'error');
            }
        },

        async confirmDelete(id, name, subCount) {
            if (subCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Delete Zone',
                        text: `This zone '${name}' currently has ${subCount} assigned subscribers. Please reassign subscribers first.`,
                        confirmButtonColor: '#0891b2'
                    });
                } else {
                    alert(`Cannot delete zone '${name}' because ${subCount} subscribers are assigned.`);
                }
                return;
            }

            let isConfirmed = false;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: `Delete Zone '${name}'?`,
                    text: 'This operational area will be permanently removed from the database.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Zone',
                    cancelButtonText: 'Cancel'
                });
                isConfirmed = res.isConfirmed;
            } else {
                isConfirmed = confirm(`Are you sure you want to delete zone '${name}'?`);
            }

            if (!isConfirmed) return;

            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        this.showToast(data.message, 'error');
                    }
                }
            } catch (err) {
                this.showToast('Network error while deleting zone.', 'error');
            }
        },

        openImportModal() {
            this.importModal.open = true;
            this.clearImportFile();
        },

        handleImportFileSelect(e) {
            const files = e.target.files;
            if (files && files[0]) {
                this.setImportFile(files[0]);
            }
        },

        handleImportDrop(e) {
            this.importModal.isDragging = false;
            const files = e.dataTransfer.files;
            if (files && files[0]) {
                this.setImportFile(files[0]);
            }
        },

        setImportFile(file) {
            this.importModal.file = file;
            this.importModal.fileName = file.name;
            const sizeKb = (file.size / 1024).toFixed(1);
            this.importModal.fileSize = sizeKb > 1024 ? `${(sizeKb / 1024).toFixed(2)} MB` : `${sizeKb} KB`;
        },

        clearImportFile() {
            this.importModal.file = null;
            this.importModal.fileName = '';
            this.importModal.fileSize = '';
            const input = document.getElementById('zone_import_file');
            if (input) input.value = '';
        },

        async submitImport() {
            if (!this.importModal.file) {
                this.showToast('Please select a CSV or Excel file to import.', 'error');
                return;
            }

            this.importModal.loading = true;
            const formData = new FormData();
            formData.append('file', this.importModal.file);
            formData.append('duplicate_action', this.importModal.duplicateAction);
            formData.append('default_reseller_id', this.importModal.defaultResellerId);

            try {
                const res = await fetch(`{{ route('tenant.customers.zones.import') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.importModal.open = false;
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Import failed. Check file format.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while importing file.', 'error');
            } finally {
                this.importModal.loading = false;
            }
        }
    };
}
</script>
@endpush
