@extends('tenant.layouts.app')

@section('title', 'Reseller Bandwidth Allocation - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerBandwidthManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Reseller Bandwidth Allocation</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="generateBills()"
                    :disabled="generatingBills"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="fas fa-file-invoice-dollar text-[11px] text-cyan-700" :class="{ 'fa-spin fa-spinner': generatingBills }"></i>
                <span x-text="generatingBills ? 'Generating...' : 'Generate Monthly Bills'"></span>
            </button>
            <button type="button" 
                    @click="openAllocationModal()"
                    class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Allocate Bandwidth</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Allocation -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Allocation</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">
                    {{ $totalAllocatedMbps >= 1000 ? round($totalAllocatedMbps/1000, 2) . ' Gbps' : round($totalAllocatedMbps, 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Global Internet -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Global Internet</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">
                    {{ $totalGlobalMbps >= 1000 ? round($totalGlobalMbps/1000, 2) . ' Gbps' : round($totalGlobalMbps, 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Card 3: Peering & Cache -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Peering &amp; Cache</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">
                    {{ $totalBdixCdnMbps >= 1000 ? round($totalBdixCdnMbps/1000, 2) . ' Gbps' : round($totalBdixCdnMbps, 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <!-- Card 4: Avg Network Load -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Avg Load</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ $avgUtilization }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Card 5: High Load Resellers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">High Load (&gt;85%)</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $congestedCount > 0 ? 'text-rose-600' : 'text-slate-700' }} truncate">
                    {{ number_format($congestedCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: Wholesale MRR -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Wholesale MRR</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($totalBandwidthMRR)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('tenant.resellers.bandwidth') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Input (MD: 3 Cols) -->
            <div class="relative md:col-span-3">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ request('search') }}" 
                       autocomplete="off"
                       placeholder="Search reseller, prefix, queue..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Router Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="router_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="">All Routers</option>
                    @foreach($routers as $rtr)
                        <option value="{{ $rtr->id }}" {{ (string)request('router_id') === (string)$rtr->id ? 'selected' : '' }}>
                            {{ $rtr->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Rate Plan Filter (MD: 3 Cols) -->
            <div class="md:col-span-3">
                <select name="bandwidth_plan_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="">All Rate Slabs</option>
                    @foreach($bandwidthPlans as $bp)
                        <option value="{{ $bp->id }}" {{ (string)request('bandwidth_plan_id') === (string)$bp->id ? 'selected' : '' }}>
                            {{ $bp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="">All Status</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="THROTTLED" {{ request('status') === 'THROTTLED' ? 'selected' : '' }}>Throttled</option>
                    <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C - MD: 2 Cols) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" 
                        class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.bandwidth') }}" 
                   class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) (AGENTS.md Rule 2.D - Minimal 7 Columns) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Reseller Partner</th>
                        <th>Core Router</th>
                        <th class="w-28 text-right">Total Bandwidth</th>
                        <th class="w-32 text-center">Rate Slab Plan</th>
                        <th class="w-36 text-right">Monthly Bill &amp; Due</th>
                        <th class="w-28 text-center no-sort">Status &amp; Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $index => $res)
                        @php
                            $bw = $res->bandwidthAllocation;
                            $latestInv = $res->latestBandwidthInvoice;
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($resellers->currentPage() - 1) * $resellers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Reseller Partner (Single-Line Pure Name) -->
                            <td class="font-semibold text-cyan-800">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($res) }})" 
                                        class="hover:underline cursor-pointer text-left font-semibold">
                                    {{ $res->name }}
                                </button>
                            </td>

                            <!-- 3. Core Router -->
                            <td class="text-slate-700">
                                {{ $bw?->router?->name ?? 'Standalone' }}
                            </td>

                            <!-- 4. Total Bandwidth -->
                            <td class="text-right font-mono font-bold text-cyan-800">
                                {{ $bw ? $bw->formatted_total_bandwidth : '—' }}
                            </td>

                            <!-- 5. Rate Slab Plan -->
                            <td class="text-center">
                                @if($bw && $bw->bandwidthPlan)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        {{ $bw->bandwidthPlan->name }}
                                    </span>
                                @elseif($bw)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        Custom Rates
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            <!-- 6. Monthly Bill & Due Status -->
                            <td class="text-right font-mono">
                                @if($bw && $bw->monthly_bill_amount > 0)
                                    <div class="font-bold text-slate-900">
                                        @currency($bw->monthly_bill_amount)
                                    </div>
                                    @if($latestInv)
                                        @if($latestInv->payment_status === 'PAID')
                                            <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">
                                                <i class="fas fa-check-circle text-[8px]"></i> PAID
                                            </span>
                                        @elseif($latestInv->payment_status === 'PARTIAL')
                                            <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                                                <i class="fas fa-circle-half-stroke text-[8px]"></i> DUE: @currency($latestInv->due_amount)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                                                <i class="fas fa-clock text-[8px]"></i> DUE: @currency($latestInv->due_amount)
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[9.5px] font-medium text-slate-400">UNBILLED</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 font-normal">—</span>
                                @endif
                            </td>

                            <!-- 7. Status & 3-Dot Action -->
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($bw)
                                        @if($bw->status === 'ACTIVE')
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fas fa-circle text-[5px]"></i> ACTIVE
                                            </span>
                                        @elseif($bw->status === 'THROTTLED')
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fas fa-gauge text-[7px]"></i> THROTTLED
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200">
                                                <i class="fas fa-pause text-[5px]"></i> SUSPENDED
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-medium font-mono bg-slate-100 text-slate-500 border border-slate-200">
                                            UNSET
                                        </span>
                                    @endif

                                    <button type="button" 
                                            @click.stop="toggleMenu({{ Js::from($res) }}, $event)" 
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center cursor-pointer"
                                            title="Actions">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400 text-xs">
                                <i class="fas fa-network-wired text-3xl mb-2 block text-slate-300"></i>
                                <span>No bandwidth allocations found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($resellers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $resellers->firstItem() }}</span> to <span class="font-bold">{{ $resellers->lastItem() }}</span> of <span class="font-bold">{{ $resellers->total() }}</span> entries
                </div>
                <div>
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="const r = activeMenu; activeMenu = null; openDetailsModal(r)" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-circle-info text-cyan-600 w-4"></i>
            <span>View Details</span>
        </button>

        <template x-if="activeMenu?.latest_bandwidth_invoice && activeMenu?.latest_bandwidth_invoice?.payment_status !== 'PAID'">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; openPaymentModal(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 hover:text-emerald-700 flex items-center gap-2 cursor-pointer transition font-semibold text-emerald-700">
                <i class="fas fa-money-bill-wave text-emerald-600 w-4"></i>
                <span>Collect Payment</span>
            </button>
        </template>

        <template x-if="activeMenu?.latest_bandwidth_invoice">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; printReceipt(r.latest_bandwidth_invoice.id)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-receipt text-cyan-600 w-4"></i>
                <span>Print Money Receipt</span>
            </button>
        </template>

        <template x-if="activeMenu?.bandwidth_allocation">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; showTrafficStats(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-chart-area text-blue-600 w-4"></i>
                <span>Live Traffic MRTG</span>
            </button>
        </template>

        <button type="button" 
                @click="const r = activeMenu; activeMenu = null; openAllocationModal(r)" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition border-t border-slate-100">
            <i class="fas fa-sliders text-cyan-600 w-4"></i>
            <span>Configure Bandwidth</span>
        </button>

        <template x-if="activeMenu?.bandwidth_allocation">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; syncQueue(r.bandwidth_allocation.id)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-emerald-600 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-rotate text-emerald-600 w-4"></i>
                <span>Sync MikroTik Queue</span>
            </button>
        </template>

        <template x-if="activeMenu?.bandwidth_allocation">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; deleteAllocation(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-rose-50 text-rose-600 flex items-center gap-2 cursor-pointer transition border-t border-slate-100">
                <i class="fas fa-trash-alt text-rose-600 w-4"></i>
                <span>Delete Allocation</span>
            </button>
        </template>
    </div>

    <!-- 6. Production-Grade Natural Modals (AGENTS.md Rule 3) -->
    
    <!-- Modal 1: Bandwidth Allocation Profile Modal -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="showModal = false">
            
            <!-- Soft Natural Header (Rule 3 & Rule 2.A: No subtitles/p-tags) -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3 class="text-xs font-semibold text-slate-800">Configure Bandwidth Allocation</h3>
                </div>
                <button type="button" @click="showModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="saveAllocation()" class="p-4 space-y-3.5 text-xs">
                
                <!-- Section 1: Partner & Rate Plan (2-Col) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Reseller Partner <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.reseller_id" 
                                @change="onResellerSelect()"
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="" disabled>-- Select Reseller --</option>
                            @foreach($allResellers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Bandwidth Rate Plan <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.bandwidth_plan_id" 
                                @change="onPlanSelect()" 
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="" disabled>-- Select Rate Plan --</option>
                            <template x-for="p in plans" :key="p.id">
                                <option :value="p.id" x-text="formatPlanPrice(p)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Router & VLAN (2-Col) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Core Gateway Router
                        </label>
                        <select x-model="form.router_id" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="">None (Standalone / Unrouted)</option>
                            @foreach($routers as $rtr)
                                <option value="{{ $rtr->id }}">{{ $rtr->name }} ({{ $rtr->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            VLAN IDs / Tags
                        </label>
                        <input type="text" 
                               x-model="form.vlan_id" 
                                placeholder="e.g. 101, 102, 205" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Section 3: Bandwidth Components (6 Categories with Integrated Tariff Badges) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[11px] font-semibold text-slate-700">
                            Bandwidth Allocation (Mbps)
                        </label>
                        <span class="text-[10px] text-slate-400 font-normal">Tariff rates auto-applied</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <!-- 1. Global Internet -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">Global Internet</span>
                                <span class="text-[9.5px] font-mono font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100" x-text="formatRate(form.global_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.global_bandwidth_mbps" required placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>

                        <!-- 2. CDN Cache -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">CDN Cache</span>
                                <span class="text-[9.5px] font-mono font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100" x-text="formatRate(form.cdn_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.cdn_bandwidth_mbps" placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>

                        <!-- 3. BDIX Peering -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">BDIX Peering</span>
                                <span class="text-[9.5px] font-mono font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100" x-text="formatRate(form.bdix_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.bdix_bandwidth_mbps" placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>

                        <!-- 4. GGC Google -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">GGC Google</span>
                                <span class="text-[9.5px] font-mono font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100" x-text="formatRate(form.ggc_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.ggc_bandwidth_mbps" placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>

                        <!-- 5. FNA Facebook -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">FNA Facebook</span>
                                <span class="text-[9.5px] font-mono font-semibold text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-100" x-text="formatRate(form.fna_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.fna_bandwidth_mbps" placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>

                        <!-- 6. Others Cache -->
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10.5px] font-medium text-slate-700">Other Cache</span>
                                <span class="text-[9.5px] font-mono font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100" x-text="formatRate(form.others_rate_per_mbps)"></span>
                            </div>
                            <div class="relative">
                                <input type="number" min="0" x-model="form.other_bandwidth_mbps" placeholder="0" class="w-full bg-white border border-slate-200 rounded-md text-xs font-mono px-2.5 py-1 pr-10 focus:border-cyan-500 focus:outline-none shadow-2xs">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Mbps</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Queue & Status (2-Col) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            MikroTik Simple Queue Name
                        </label>
                        <input type="text" 
                               x-model="form.mikrotik_queue_name" 
                               placeholder="Auto-generated if blank" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Line Status
                        </label>
                        <select x-model="form.status" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="ACTIVE">Active (Normal Operation)</option>
                            <option value="THROTTLED">Throttled (Restricted Rate)</option>
                            <option value="SUSPENDED">Suspended (Disabled Queue)</option>
                        </select>
                    </div>
                </div>

                <!-- Section 5: Clean Summary Strip -->
                <div class="px-3 py-2 bg-slate-100/70 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-medium">Total Bandwidth:</span>
                        <span class="font-mono font-bold text-slate-800" x-text="calculateTotalMbps() + ' Mbps'"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-medium">Est. Monthly Bill:</span>
                        <span class="font-mono font-bold text-cyan-800" x-text="calculateEstimatedBill()"></span>
                    </div>
                </div>

                <!-- Section 6: Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Saving...' : 'Save Bandwidth Profile'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Collect Wholesale Payment Modal -->
    <div x-show="paymentModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden my-6"
             @click.outside="paymentModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Collect Bandwidth Bill Payment</h3>
                    </div>
                </div>
                <button type="button" @click="paymentModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Payment Form -->
            <form @submit.prevent="submitPayment()" class="p-4 space-y-3.5 text-xs">
                
                <!-- Partner & Bill Summary Box -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Reseller Partner:</span>
                        <span class="font-bold text-slate-800" x-text="paymentModal.reseller?.name"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Invoice Number:</span>
                        <span class="font-mono font-bold text-cyan-800" x-text="paymentModal.invoice?.invoice_no"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Current Due Balance:</span>
                        <span class="font-mono font-bold text-rose-600" x-text="formatCurrency(paymentModal.invoice?.due_amount)"></span>
                    </div>
                </div>

                <!-- Input: Paid Amount -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Received Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" 
                           min="0.01" 
                           x-model="paymentModal.form.paid_amount" 
                           required 
                           placeholder="0.00" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- 2-Col: Payment Method & Discount -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="paymentModal.form.payment_method" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="CASH">Cash</option>
                            <option value="BANK_TRANSFER">Bank Transfer</option>
                            <option value="BKASH">bKash</option>
                            <option value="NAGAD">Nagad</option>
                            <option value="ROCKET">Rocket</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Discount / Waiver ({{ $currencySymbol ?? '৳' }})
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="paymentModal.form.discount" 
                               placeholder="0.00" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Input: Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Transaction Ref / Notes
                    </label>
                    <input type="text" 
                           x-model="paymentModal.form.notes" 
                           placeholder="e.g. Bank Trx ID, Cheque number..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="paymentModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="paymentModal.submitting"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': paymentModal.submitting }"></i>
                        <span x-text="paymentModal.submitting ? 'Processing...' : 'Confirm Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Real-time Traffic MRTG Preview Modal -->
    <div x-show="trafficModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="trafficModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-chart-area"></i>
                    </div>
                    <h3 class="text-xs font-semibold text-slate-800" x-text="trafficModal.data?.partner_name + ' - Traffic Graph'"></h3>
                </div>
                <button type="button" @click="trafficModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- 3 Metric Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Total Capacity</span>
                        <span class="text-xs font-bold font-mono text-cyan-800 mt-0.5 block" x-text="trafficModal.data?.total_bandwidth"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Current Usage</span>
                        <span class="text-xs font-bold font-mono text-blue-700 mt-0.5 block" x-text="trafficModal.data?.current_usage"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Peak Rate</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 mt-0.5 block" x-text="trafficModal.data?.peak_usage"></span>
                    </div>
                </div>

                <!-- Simulation MRTG Bar Chart -->
                <div class="p-3.5 bg-slate-900 rounded-xl border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-300 font-mono">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></span> Download (Rx)
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-blue-400"></span> Upload (Tx)
                        </span>
                    </div>
                    <div class="h-32 flex items-end gap-1.5 pt-4">
                        <template x-for="(pt, idx) in trafficModal.data?.points || []" :key="idx">
                            <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                <div class="w-full flex flex-col justify-end gap-0.5 h-24">
                                    <div class="w-full bg-emerald-400/80 rounded-t-xs transition-all duration-300" :style="`height: ${Math.min(100, (pt.download / 100) * 100)}%`"></div>
                                    <div class="w-full bg-blue-400/80 rounded-t-xs transition-all duration-300" :style="`height: ${Math.min(100, (pt.upload / 100) * 100)}%`"></div>
                                </div>
                                <span class="text-[9px] font-mono text-slate-500" x-text="pt.time"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="trafficModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 4: View Details Modal -->
    <div x-show="detailsModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="detailsModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.reseller?.name + ' - Bandwidth Details'"></h3>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- 6 Bandwidth Breakdown Cards -->
                <div>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Bandwidth Breakdown (6 Types)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <div class="p-2 bg-blue-50/50 rounded-lg border border-blue-100">
                            <span class="text-[10px] font-medium text-blue-600 uppercase block">Global Internet</span>
                            <span class="text-xs font-bold font-mono text-blue-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.global_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.global_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-indigo-50/50 rounded-lg border border-indigo-100">
                            <span class="text-[10px] font-medium text-indigo-600 uppercase block">BDIX Peering</span>
                            <span class="text-xs font-bold font-mono text-indigo-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.bdix_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.bdix_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-emerald-50/50 rounded-lg border border-emerald-100">
                            <span class="text-[10px] font-medium text-emerald-600 uppercase block">GGC (Google)</span>
                            <span class="text-xs font-bold font-mono text-emerald-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.ggc_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.ggc_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-sky-50/50 rounded-lg border border-sky-100">
                            <span class="text-[10px] font-medium text-sky-600 uppercase block">FNA (Facebook)</span>
                            <span class="text-xs font-bold font-mono text-sky-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.fna_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.fna_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-purple-50/50 rounded-lg border border-purple-100">
                            <span class="text-[10px] font-medium text-purple-600 uppercase block">CDN Cache</span>
                            <span class="text-xs font-bold font-mono text-purple-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.cdn_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.cdn_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-[10px] font-medium text-slate-600 uppercase block">Other Cache</span>
                            <span class="text-xs font-bold font-mono text-slate-800 mt-0.5 block" x-text="(detailsModal.reseller?.bandwidth_allocation?.other_bandwidth_mbps ? parseFloat(detailsModal.reseller.bandwidth_allocation.other_bandwidth_mbps).toFixed(0) : 0) + ' Mbps'"></span>
                        </div>
                    </div>
                </div>

                <!-- Metrics Overview Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Total Capacity</span>
                        <span class="text-xs font-bold font-mono text-cyan-800 mt-0.5 block" x-text="detailsModal.reseller?.bandwidth_allocation?.formatted_total_bandwidth || 'Not Configured'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Rate Plan</span>
                        <span class="text-xs font-semibold text-cyan-800 mt-0.5 block truncate" x-text="detailsModal.reseller?.bandwidth_allocation?.bandwidth_plan?.name || 'Custom Rate'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Monthly Bill</span>
                        <span class="text-xs font-mono font-bold text-slate-900 mt-0.5 block" x-text="formatCurrency(detailsModal.reseller?.bandwidth_allocation?.monthly_bill_amount)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">VLAN Tags</span>
                        <span class="text-[11px] font-mono font-semibold text-slate-800 mt-0.5 block truncate" x-text="detailsModal.reseller?.bandwidth_allocation?.vlan_id || 'Untagged'"></span>
                    </div>
                </div>

                <!-- Applied Rate Breakdown -->
                <template x-if="detailsModal.reseller?.bandwidth_allocation">
                    <div>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Applied Rate Breakdown ({{ $currencySymbol ?? '৳' }}/Mbps)</span>
                        <div class="p-2.5 bg-cyan-50/30 rounded-lg border border-cyan-100 grid grid-cols-2 sm:grid-cols-3 gap-2 text-center text-[11px]">
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">Global Rate</span>
                                <span class="font-mono font-bold text-blue-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.global_rate_per_mbps)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">CDN Rate</span>
                                <span class="font-mono font-bold text-purple-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.cdn_rate_per_mbps)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">BDIX Rate</span>
                                <span class="font-mono font-bold text-indigo-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.bdix_rate_per_mbps)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">GGC Rate</span>
                                <span class="font-mono font-bold text-amber-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.ggc_rate_per_mbps)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">FNA Rate</span>
                                <span class="font-mono font-bold text-sky-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.fna_rate_per_mbps)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 text-[10px] uppercase block">Others Rate</span>
                                <span class="font-mono font-bold text-emerald-700" x-text="formatRate(detailsModal.reseller.bandwidth_allocation.others_rate_per_mbps)"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Network & Routing Info -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div class="grid grid-cols-2 gap-3 text-[11px]">
                        <div>
                            <span class="text-slate-500 block">Core Gateway Router:</span>
                            <span class="font-medium text-slate-800" x-text="detailsModal.reseller?.bandwidth_allocation?.router?.name || 'Standalone / None'"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">VLAN ID / Tag:</span>
                            <span class="font-mono text-slate-800" x-text="detailsModal.reseller?.bandwidth_allocation?.vlan_id || 'Untagged'"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">MikroTik Queue Name:</span>
                            <span class="font-mono text-cyan-800 font-medium" x-text="detailsModal.reseller?.bandwidth_allocation?.mikrotik_queue_name || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Line Status:</span>
                            <span class="font-semibold" :class="detailsModal.reseller?.bandwidth_allocation?.status === 'ACTIVE' ? 'text-emerald-600' : 'text-rose-600'" x-text="detailsModal.reseller?.bandwidth_allocation?.status || 'Unassigned'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" 
                        @click="const r = detailsModal.reseller; detailsModal.open = false; openAllocationModal(r)" 
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-sliders text-xs"></i>
                    <span>Configure Bandwidth</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerBandwidthManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        submitting: false,
        generatingBills: false,
        plans: {{ Js::from($bandwidthPlans) }},
        currencySymbol: '{{ $currencySymbol ?? "৳" }}',

        showModal: false,
        detailsModal: {
            open: false,
            reseller: null
        },
        trafficModal: {
            open: false,
            data: null
        },
        paymentModal: {
            open: false,
            submitting: false,
            reseller: null,
            invoice: null,
            form: {
                paid_amount: '',
                payment_method: 'CASH',
                discount: '',
                notes: ''
            }
        },

        form: {
            reseller_id: '{{ $allResellers->first()?->id ?? "" }}',
            bandwidth_plan_id: '',
            router_id: '',
            interface_name: '',
            vlan_id: '',
            global_bandwidth_mbps: '',
            bdix_bandwidth_mbps: 0,
            cdn_bandwidth_mbps: 0,
            ggc_bandwidth_mbps: 0,
            fna_bandwidth_mbps: 0,
            other_bandwidth_mbps: 0,
            allocation_type: 'DEDICATED_CIR',
            rate_per_mbps: 0,
            global_rate_per_mbps: 0,
            cdn_rate_per_mbps: 0,
            bdix_rate_per_mbps: 0,
            ggc_rate_per_mbps: 0,
            fna_rate_per_mbps: 0,
            others_rate_per_mbps: 0,
            mikrotik_queue_name: '',
            status: 'ACTIVE',
            notes: ''
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

        openDetailsModal(res) {
            this.detailsModal.reseller = res;
            this.detailsModal.open = true;
        },

        openPaymentModal(res) {
            if (!res.latest_bandwidth_invoice) return;
            this.paymentModal.reseller = res;
            this.paymentModal.invoice = res.latest_bandwidth_invoice;
            this.paymentModal.form.paid_amount = res.latest_bandwidth_invoice.due_amount || res.latest_bandwidth_invoice.amount;
            this.paymentModal.form.payment_method = 'CASH';
            this.paymentModal.form.discount = '';
            this.paymentModal.form.notes = '';
            this.paymentModal.open = true;
        },

        printReceipt(invoiceId) {
            window.open(`/admin/resellers/bandwidth/invoices/${invoiceId}/receipt`, '_blank');
        },

        openAllocationModal(res = null) {
            if (res) {
                this.form.reseller_id = res.id;
                const bw = res.bandwidth_allocation;
                if (bw) {
                    this.form.bandwidth_plan_id = bw.bandwidth_plan_id || '';
                    this.form.router_id = bw.router_id || '';
                    this.form.vlan_id = bw.vlan_id || '';
                    this.form.global_bandwidth_mbps = bw.global_bandwidth_mbps || 0;
                    this.form.cdn_bandwidth_mbps = bw.cdn_bandwidth_mbps || 0;
                    this.form.bdix_bandwidth_mbps = bw.bdix_bandwidth_mbps || 0;
                    this.form.ggc_bandwidth_mbps = bw.ggc_bandwidth_mbps || 0;
                    this.form.fna_bandwidth_mbps = bw.fna_bandwidth_mbps || 0;
                    this.form.other_bandwidth_mbps = bw.other_bandwidth_mbps || 0;
                    this.form.allocation_type = bw.allocation_type || 'DEDICATED_CIR';
                    this.form.rate_per_mbps = bw.rate_per_mbps || 0;
                    this.form.global_rate_per_mbps = bw.global_rate_per_mbps || 0;
                    this.form.cdn_rate_per_mbps = bw.cdn_rate_per_mbps || 0;
                    this.form.bdix_rate_per_mbps = bw.bdix_rate_per_mbps || 0;
                    this.form.ggc_rate_per_mbps = bw.ggc_rate_per_mbps || 0;
                    this.form.fna_rate_per_mbps = bw.fna_rate_per_mbps || 0;
                    this.form.others_rate_per_mbps = bw.others_rate_per_mbps || 0;
                    this.form.mikrotik_queue_name = bw.mikrotik_queue_name || '';
                    this.form.status = bw.status || 'ACTIVE';
                    if (this.form.bandwidth_plan_id) {
                        this.onPlanSelect();
                    }
                } else {
                    this.resetFormFields();
                }
            } else {
                this.form.reseller_id = '{{ $allResellers->first()?->id ?? "" }}';
                this.resetFormFields();
            }
            this.showModal = true;
        },

        resetFormFields() {
            this.form.bandwidth_plan_id = this.plans.length > 0 ? this.plans[0].id : '';
            this.form.router_id = '';
            this.form.vlan_id = '';
            this.form.global_bandwidth_mbps = '';
            this.form.cdn_bandwidth_mbps = 0;
            this.form.bdix_bandwidth_mbps = 0;
            this.form.ggc_bandwidth_mbps = 0;
            this.form.fna_bandwidth_mbps = 0;
            this.form.other_bandwidth_mbps = 0;
            this.form.allocation_type = 'DEDICATED_CIR';
            this.form.rate_per_mbps = 0;
            this.form.global_rate_per_mbps = 0;
            this.form.cdn_rate_per_mbps = 0;
            this.form.bdix_rate_per_mbps = 0;
            this.form.ggc_rate_per_mbps = 0;
            this.form.fna_rate_per_mbps = 0;
            this.form.others_rate_per_mbps = 0;
            this.form.mikrotik_queue_name = '';
            this.form.status = 'ACTIVE';
            if (this.form.bandwidth_plan_id) {
                this.onPlanSelect();
            }
        },

        onResellerSelect() {
            // Optional callback
        },

        onPlanSelect() {
            if (!this.form.bandwidth_plan_id) return;
            const plan = this.plans.find(p => String(p.id) === String(this.form.bandwidth_plan_id));
            if (!plan) return;

            this.form.global_rate_per_mbps = parseFloat(plan.global_rate_per_mbps || 0);
            this.form.cdn_rate_per_mbps = parseFloat(plan.cdn_rate_per_mbps || 0);
            this.form.bdix_rate_per_mbps = parseFloat(plan.bdix_rate_per_mbps || 0);
            this.form.ggc_rate_per_mbps = parseFloat(plan.ggc_rate_per_mbps || 0);
            this.form.fna_rate_per_mbps = parseFloat(plan.fna_rate_per_mbps || 0);
            this.form.others_rate_per_mbps = parseFloat(plan.others_rate_per_mbps || 0);
            this.form.rate_per_mbps = 0;
        },

        formatPlanPrice(p) {
            if (p.formatted_slab_range) {
                return p.name + ' (' + p.formatted_slab_range + ')';
            }
            return p.name;
        },

        calculateTotalMbps() {
            const g = parseFloat(this.form.global_bandwidth_mbps || 0);
            const cdn = parseFloat(this.form.cdn_bandwidth_mbps || 0);
            const bdix = parseFloat(this.form.bdix_bandwidth_mbps || 0);
            const ggc = parseFloat(this.form.ggc_bandwidth_mbps || 0);
            const fna = parseFloat(this.form.fna_bandwidth_mbps || 0);
            const other = parseFloat(this.form.other_bandwidth_mbps || 0);
            return g + cdn + bdix + ggc + fna + other;
        },

        formatCurrency(val, decimals = 2) {
            const num = parseFloat(val || 0);
            return `${this.currencySymbol} ${num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}`;
        },

        formatRate(val) {
            const num = parseFloat(val || 0);
            return `${this.currencySymbol} ${num.toFixed(0)}/M`;
        },

        calculateEstimatedBill() {
            const g = parseFloat(this.form.global_bandwidth_mbps || 0);
            const cdn = parseFloat(this.form.cdn_bandwidth_mbps || 0);
            const bdix = parseFloat(this.form.bdix_bandwidth_mbps || 0);
            const ggc = parseFloat(this.form.ggc_bandwidth_mbps || 0);
            const fna = parseFloat(this.form.fna_bandwidth_mbps || 0);
            const other = parseFloat(this.form.other_bandwidth_mbps || 0);
            
            const gRate = parseFloat(this.form.global_rate_per_mbps || 0);
            const cdnRate = parseFloat(this.form.cdn_rate_per_mbps || 0);
            const bdixRate = parseFloat(this.form.bdix_rate_per_mbps || 0);
            const ggcRate = parseFloat(this.form.ggc_rate_per_mbps || 0);
            const fnaRate = parseFloat(this.form.fna_rate_per_mbps || 0);
            const otherRate = parseFloat(this.form.others_rate_per_mbps || 0);

            const totalBill = (g * gRate) + (cdn * cdnRate) + (bdix * bdixRate) + (ggc * ggcRate) + (fna * fnaRate) + (other * otherRate);
            return this.formatCurrency(totalBill, 2);
        },

        async generateBills() {
            if (typeof Swal !== 'undefined') {
                const confirmResult = await Swal.fire({
                    title: 'Generate Wholesale Invoices?',
                    text: 'This will generate monthly bandwidth invoices for all active Sub-ISPs for current billing month.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Generate Invoices',
                    cancelButtonText: 'Cancel'
                });
                if (!confirmResult.isConfirmed) return;
            }

            this.generatingBills = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.bandwidth.generate-bills') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bills Generated',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Generation Failed',
                            text: data.message || 'Error generating invoices.'
                        });
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error or server unreachable.'
                    });
                }
            } finally {
                this.generatingBills = false;
            }
        },

        async submitPayment() {
            if (!this.paymentModal.invoice?.id || !this.paymentModal.form.paid_amount) return;
            this.paymentModal.submitting = true;
            try {
                const response = await fetch(`/admin/resellers/bandwidth/invoices/${this.paymentModal.invoice.id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.paymentModal.form)
                });
                const data = await response.json();
                if (data.success) {
                    this.paymentModal.open = false;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Received',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Failed',
                            text: data.message || 'Error recording payment.'
                        });
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error or server unreachable.'
                    });
                }
            } finally {
                this.paymentModal.submitting = false;
            }
        },

        async saveAllocation() {
            if (!this.form.reseller_id || !this.form.global_bandwidth_mbps) return;
            this.submitting = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.bandwidth.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    this.showModal = false;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Allocation Saved',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Error saving bandwidth profile.'
                        });
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error or server unreachable.'
                    });
                }
            } finally {
                this.submitting = false;
            }
        },

        async showTrafficStats(item) {
            if (!item.bandwidth_allocation?.id) return;
            try {
                const res = await fetch(`/admin/resellers/bandwidth/${item.bandwidth_allocation.id}/traffic`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.trafficModal.data = data;
                    this.trafficModal.open = true;
                }
            } catch (err) {
                console.error(err);
            }
        },

        async syncQueue(id) {
            try {
                const res = await fetch(`/admin/resellers/bandwidth/${id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'MikroTik Synced',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Error',
                        text: 'Unable to communicate with core router.'
                    });
                }
            }
        },

        async deleteAllocation(item) {
            if (!item?.bandwidth_allocation?.id) return;
            const resName = item.name || 'Reseller';
            
            if (typeof Swal !== 'undefined') {
                const confirmResult = await Swal.fire({
                    title: 'Delete Bandwidth Profile?',
                    text: `Are you sure you want to delete the bandwidth allocation profile for "${resName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                });

                if (!confirmResult.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to delete bandwidth allocation for "${resName}"?`)) return;
            }

            try {
                const res = await fetch(`/admin/resellers/bandwidth/${item.bandwidth_allocation.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: data.message || 'Unable to delete bandwidth profile.'
                        });
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Unable to reach server.'
                    });
                }
            }
        }
    };
}
</script>
@endpush
