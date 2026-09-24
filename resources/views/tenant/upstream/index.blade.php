@extends('tenant.layouts.app')

@section('title', 'Upstream Bandwidth & Carrier Accounting - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-tower-broadcast"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Upstream Bandwidth &amp; Carrier Accounting</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="openCreateModal()"
                    class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Add Carrier</span>
            </button>
            <button type="button" 
                    @click="openInvoiceModal()"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice-dollar text-xs"></i>
                <span>Record Bill</span>
            </button>
            <button type="button" 
                    @click="openPaymentModal()"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-money-bill-transfer text-xs"></i>
                <span>Disburse Payment</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B & Rule 6) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Upstream Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Upstream</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">
                    {{ $totalUpstreamMbps >= 1000 ? round($totalUpstreamMbps / 1000, 2) . ' Gbps' : number_format($totalUpstreamMbps) . ' M' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        <!-- Card 2: Global Internet Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Global Internet</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">
                    {{ $globalMbps >= 1000 ? round($globalMbps / 1000, 2) . ' Gbps' : number_format($globalMbps) . ' M' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Card 3: Peering & Cache Capacity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Peering &amp; Cache</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">
                    {{ $peeringCacheMbps >= 1000 ? round($peeringCacheMbps / 1000, 2) . ' Gbps' : number_format($peeringCacheMbps) . ' M' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-diagram-project"></i>
            </div>
        </div>

        <!-- Card 4: Monthly Carrier Cost -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Monthly Est Cost</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700 truncate">@currency($monthlyCarrierCost)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Card 5: Carrier Total Due Balance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Carrier Total Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">@currency($carrierTotalDue)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 6: Gross Bandwidth Margin -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Est Gross Margin</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $grossMargin >= 0 ? 'text-emerald-700' : 'text-rose-700' }} truncate">@currency($grossMargin)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Strictly Rule 2.C - Filter then Reset Sequence) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.upstream.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Input -->
            <div class="relative md:col-span-5">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Carrier, Contact, Phone, Bank A/C..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
            </div>

            <!-- Core Router Filter -->
            <div class="md:col-span-3">
                <select name="router_id" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Core Routers</option>
                    @foreach($routers as $r)
                    <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="md:col-span-2">
                <select name="status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div class="md:col-span-1">
                <select name="per_page" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <!-- Filter & Reset Button Sequence (Strictly Filter Cyan first, Reset Slate second, Always visible) -->
            <div class="md:col-span-1 flex items-center justify-end gap-1.5">
                <button type="submit" 
                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('tenant.upstream.index') }}" 
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                   title="Reset All Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (Strictly .saas-table pure CSS system - Max 7 Core Columns - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Carrier Provider</th>
                        <th>Core Router</th>
                        <th class="text-right">Capacity</th>
                        <th class="text-right">Transmission Fee</th>
                        <th class="text-right">Monthly Bill</th>
                        <th class="w-12 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $index => $provider)
                    @php
                        $primaryLink = $provider->links->first();
                        $totCapacity = $provider->links->sum('total_mbps');
                        $transCost = $provider->links->sum('monthly_transmission_cost');
                        $estBill = $provider->links->sum('est_monthly_bill');
                        $dueBal = $provider->invoices->sum('due_amount');
                    @endphp
                    <tr>
                        <td class="text-center font-mono text-slate-500">
                            {{ $providers->firstItem() + $index }}
                        </td>

                        <!-- Carrier Provider (Strictly Single Data - No Badges or Concat) -->
                        <td class="font-bold text-slate-800">
                            {{ $provider->name }}
                        </td>

                        <!-- Core Router (Strictly Single Data - No Port in Parens) -->
                        <td class="text-slate-700">
                            {{ $primaryLink->router->name ?? '-' }}
                        </td>

                        <!-- Capacity (Single Line Metric) -->
                        <td class="text-right font-mono font-bold text-slate-800">
                            {{ $totCapacity >= 1000 ? round($totCapacity / 1000, 2) . ' Gbps' : number_format($totCapacity) . ' Mbps' }}
                        </td>

                        <!-- Transmission Fee (Single Value) -->
                        <td class="text-right font-mono text-slate-700">
                            @currency($transCost)
                        </td>

                        <!-- Monthly Bill (Strictly Single Line - No Stacked Sub-text) -->
                        <td class="text-right font-mono font-bold text-slate-900">
                            @currency($estBill)
                        </td>

                        <!-- Action (3-Dot Floating Trigger) -->
                        <td class="text-center">
                            <button type="button" 
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $provider->id,
                                        'name' => $provider->name,
                                        'carrier_type' => $provider->carrier_type,
                                        'contact_person' => $provider->contact_person,
                                        'phone' => $provider->phone,
                                        'email' => $provider->email,
                                        'address' => $provider->address,
                                        'bank_name' => $provider->bank_name,
                                        'bank_account_no' => $provider->bank_account_no,
                                        'bank_branch' => $provider->bank_branch,
                                        'routing_no' => $provider->routing_no,
                                        'is_active' => $provider->is_active,
                                        'notes' => $provider->notes,
                                        'primary_link' => $primaryLink,
                                        'tot_capacity' => $totCapacity,
                                        'trans_cost' => $transCost,
                                        'est_bill' => $estBill,
                                        'due_balance' => $dueBal,
                                        'invoices_count' => $provider->invoices->count(),
                                        'payments_count' => $provider->payments->count(),
                                    ]) }}, $event)"
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer">
                                <i class="fas fa-ellipsis-v text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <i class="fas fa-tower-broadcast text-2xl text-slate-300"></i>
                                <span class="text-xs font-semibold text-slate-600">No upstream carriers found</span>
                                <span class="text-[11px] text-slate-400">Click '+ Add Carrier' above to register your upstream IIG / ITC / NTTN links.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($providers->hasPages())
        <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="text-[11px] text-slate-500">
                Showing <span class="font-semibold text-slate-700">{{ $providers->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $providers->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $providers->total() }}</span> carriers
            </div>
            <div>
                {{ $providers->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null" 
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-52 text-xs font-medium text-slate-700 space-y-0.5"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        <!-- Group 1: Diagnostics & Info -->
        <button type="button" 
                @click="viewDetails(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-circle-info w-4 text-slate-400 text-[11px]"></i>
            <span>View Capacity Details</span>
        </button>

        <button type="button" 
                @click="openInvoiceModal(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-file-invoice-dollar w-4 text-cyan-600 text-[11px]"></i>
            <span>Record Monthly Bill</span>
        </button>

        <button type="button" 
                @click="openPaymentModal(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-money-bill-transfer w-4 text-emerald-600 text-[11px]"></i>
            <span>Disburse Payment</span>
        </button>

        <div class="border-t border-slate-100 my-0.5"></div>

        <!-- Group 2: Configuration & Status -->
        <button type="button" 
                @click="openEditModal(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-pen-to-square w-4 text-blue-500 text-[11px]"></i>
            <span>Edit Carrier &amp; Rates</span>
        </button>

        <button type="button" 
                @click="toggleCarrierStatus(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-power-off w-4 text-amber-500 text-[11px]"></i>
            <span x-text="activeMenu?.is_active ? 'Disable Carrier' : 'Enable Carrier'"></span>
        </button>

        <div class="border-t border-slate-100 my-0.5"></div>

        <!-- Group 3: Delete Action -->
        <button type="button" 
                @click="deleteCarrier(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Carrier</span>
        </button>
    </div>

    <!-- 6. Production-Grade Natural Modals (AGENTS.md Rule 3) -->

    <!-- Modal 1: Add / Edit Carrier Provider & Link Modal -->
    <div x-show="showProviderModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-3xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showProviderModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-tower-broadcast"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="isEditMode ? 'Edit Upstream Carrier & Capacity' : 'Add Upstream Carrier Provider'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Configure carrier profile, connection link, traffic streams, and pricing.</p>
                    </div>
                </div>
                <button type="button" @click="showProviderModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form :action="isEditMode ? `/admin/upstream/providers/${currentProvider.id}` : '{{ route('tenant.upstream.provider.store') }}'" method="POST" class="p-4 space-y-4 max-h-[78vh] overflow-y-auto">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!isEditMode">
                <input type="hidden" name="link_id" :value="currentProvider?.primary_link?.id || ''" x-bind:disabled="!isEditMode || !currentProvider?.primary_link">
                <input type="hidden" name="carrier_type" x-model="formData.carrier_type">

                <!-- Section 1: Carrier Profile -->
                <div class="space-y-2">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-100">
                        <i class="fas fa-building text-[10px] text-cyan-600"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">1. Carrier Company Information</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Carrier / Provider Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="formData.name" 
                                   required 
                                   placeholder="e.g. Summit Communications Ltd." 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Contact Person Name
                            </label>
                            <input type="text" 
                                   name="contact_person" 
                                   x-model="formData.contact_person" 
                                   placeholder="e.g. Md. Rafiqul Islam" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Contact Phone Number
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   x-model="formData.phone" 
                                   placeholder="e.g. 017xxxxxxxx" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Contact Email Address
                            </label>
                            <input type="email" 
                                   name="email" 
                                   x-model="formData.email" 
                                   placeholder="e.g. noc@carrier.com" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Bank Name &amp; Branch
                            </label>
                            <input type="text" 
                                   name="bank_name" 
                                   x-model="formData.bank_name" 
                                   placeholder="e.g. City Bank - Gulshan Branch" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Bank Account Number / Routing
                            </label>
                            <input type="text" 
                                   name="bank_account_no" 
                                   x-model="formData.bank_account_no" 
                                   placeholder="e.g. 1102938475001" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Link & Core Router Port -->
                <div class="space-y-2 pt-1">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-100">
                        <i class="fas fa-network-wired text-[10px] text-cyan-600"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">2. Core Router &amp; Delivery Link</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Connected Core Router
                            </label>
                            <select name="router_id" 
                                    x-model="formData.router_id" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="">Select Core Router</option>
                                @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Interface Port / SFP
                            </label>
                            <input type="text" 
                                   name="interface_port" 
                                   x-model="formData.interface_port" 
                                   placeholder="e.g. sfp-sfpplus1" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Circuit ID / Tag
                            </label>
                            <input type="text" 
                                   name="circuit_id" 
                                   x-model="formData.circuit_id" 
                                   placeholder="e.g. CKT-DH-901" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Traffic Capacities & Unit Rates Matrix -->
                <div class="space-y-2 pt-1">
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-chart-pie text-[10px] text-cyan-600"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">3. Purchased Capacity (Mbps) &amp; Unit Rates ({{ $currencySymbol }}/Mbps)</span>
                        </div>
                        <span class="text-[10.5px] font-mono font-bold text-cyan-700" x-text="`Total Bandwidth: ${totalMbps()} Mbps`"></span>
                    </div>

                    <!-- Clean Structured Capacity Breakdown Table -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100/90 text-[10px] uppercase font-bold text-slate-600 border-b border-slate-200 tracking-wider">
                                <tr>
                                    <th class="py-2 px-3">Traffic Stream</th>
                                    <th class="py-2 px-3 w-32 text-right">Capacity (Mbps)</th>
                                    <th class="py-2 px-3 w-36 text-right">Unit Rate ({{ $currencySymbol }}/Mbps)</th>
                                    <th class="py-2 px-3 w-32 text-right">Monthly Sub-total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <!-- 1. Global Internet -->
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-blue-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-globe text-[11px] w-4 text-blue-500"></i>
                                            <span>Global Internet (IP Transit)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="global_mbps" x-model.number="formData.global_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="global_rate" x-model.number="formData.global_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.global_mbps || 0) * Number(formData.global_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                <!-- 2. BDIX Peering -->
                                <tr class="hover:bg-emerald-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-emerald-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-network-wired text-[11px] w-4 text-emerald-500"></i>
                                            <span>BDIX Peering</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="bdix_mbps" x-model.number="formData.bdix_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="bdix_rate" x-model.number="formData.bdix_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.bdix_mbps || 0) * Number(formData.bdix_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                <!-- 3. CDN Cache -->
                                <tr class="hover:bg-purple-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-purple-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-bolt text-[11px] w-4 text-purple-500"></i>
                                            <span>CDN Cache / Akamai</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="cdn_mbps" x-model.number="formData.cdn_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="cdn_rate" x-model.number="formData.cdn_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.cdn_mbps || 0) * Number(formData.cdn_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                <!-- 4. Google GGC -->
                                <tr class="hover:bg-amber-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-amber-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fab fa-google text-[11px] w-4 text-amber-500"></i>
                                            <span>Google Cache (GGC)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="ggc_mbps" x-model.number="formData.ggc_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="ggc_rate" x-model.number="formData.ggc_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.ggc_mbps || 0) * Number(formData.ggc_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                <!-- 5. Facebook FNA -->
                                <tr class="hover:bg-indigo-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-indigo-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fab fa-facebook text-[11px] w-4 text-indigo-500"></i>
                                            <span>Facebook Cache (FNA)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="fna_mbps" x-model.number="formData.fna_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="fna_rate" x-model.number="formData.fna_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.fna_mbps || 0) * Number(formData.fna_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                <!-- 6. Other / Peering -->
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-1.5 px-3 font-semibold text-slate-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-server text-[11px] w-4 text-slate-400"></i>
                                            <span>Other / Private Peering</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="other_mbps" x-model.number="formData.other_mbps" placeholder="0" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="other_rate" x-model.number="formData.other_rate" placeholder="0.00" class="w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 py-1 font-mono text-xs focus:border-cyan-500 focus:outline-none transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.other_mbps || 0) * Number(formData.other_rate || 0))).toFixed(2)}`"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Transmission Cost & Real-Time Bill Calculation Box -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Monthly NTTN / Fiber Transmission Charge ({{ $currencySymbol }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="monthly_transmission_cost" 
                                   x-model.number="formData.monthly_transmission_cost" 
                                   placeholder="0.00" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono focus:border-cyan-500 focus:outline-none transition">
                        </div>

                        <div class="flex flex-col justify-center bg-white p-2.5 rounded-lg border border-slate-200">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-500 font-medium">Total Bandwidth:</span>
                                <span class="font-bold font-mono text-slate-800" x-text="`${totalMbps()} Mbps`"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs border-t border-slate-100 pt-1">
                                <span class="text-slate-700 font-semibold">Est. Monthly Total Bill:</span>
                                <span class="font-bold font-mono text-cyan-700 text-sm" x-text="`{{ $currencySymbol }} ${estMonthlyCost()}`"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showProviderModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        <span x-text="isEditMode ? 'Update Carrier Profile' : 'Save Upstream Carrier'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Record Carrier Invoice / Bill Modal -->
    <div x-show="showInvoiceModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showInvoiceModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Record Carrier Invoice / Monthly Bill</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Enter carrier billed amount, transmission cost and VAT.</p>
                    </div>
                </div>
                <button type="button" @click="showInvoiceModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.invoice.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="invoiceData.provider_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->carrier_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Invoice / Bill No <span class="text-rose-500">*</span></label>
                        <input type="text" name="invoice_no" x-model="invoiceData.invoice_no" required placeholder="e.g. SMT-2026-09" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Billing Month <span class="text-rose-500">*</span></label>
                        <input type="date" name="billing_month" x-model="invoiceData.billing_month" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10.5px] font-medium text-slate-700 mb-1">Bandwidth Cost <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="bandwidth_cost" x-model.number="invoiceData.bandwidth_cost" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-medium text-slate-700 mb-1">NTTN / Trans.</label>
                        <input type="number" step="0.01" min="0" name="transmission_cost" x-model.number="invoiceData.transmission_cost" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-medium text-slate-700 mb-1">VAT / Tax</label>
                        <input type="number" step="0.01" min="0" name="vat_tax" x-model.number="invoiceData.vat_tax" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono">
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between font-mono text-xs">
                    <span class="font-bold text-slate-600">Total Billed Amount:</span>
                    <span class="font-bold text-cyan-800 text-sm" x-text="`{{ $currencySymbol }} ${(Number(invoiceData.bandwidth_cost || 0) + Number(invoiceData.transmission_cost || 0) + Number(invoiceData.vat_tax || 0)).toFixed(2)}`"></span>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showInvoiceModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Record Carrier Bill
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Disburse Payment & Generate Voucher Modal -->
    <div x-show="showPaymentModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showPaymentModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Disburse Payment to Carrier</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record bank transfer/cheque and generate printable voucher.</p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.payment.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="paymentData.provider_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (Due: @currency($p->invoices->sum('due_amount')))</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Paid Amount ({{ $currencySymbol }}) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" x-model.number="paymentData.amount" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono font-bold text-emerald-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select name="payment_method" x-model="paymentData.payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="BANK_TRANSFER">Bank Transfer / BEFTN</option>
                            <option value="CHEQUE">Bank Cheque</option>
                            <option value="RTGS">RTGS Instant</option>
                            <option value="CASH">Cash</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Bank Name / Branch</label>
                        <input type="text" name="bank_name" x-model="paymentData.bank_name" placeholder="e.g. City Bank" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Cheque / Trx Reference</label>
                        <input type="text" name="transaction_ref" x-model="paymentData.transaction_ref" placeholder="Ref No / Cheque No" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="paid_at" x-model="paymentData.paid_at" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Disburse &amp; Generate Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: View Details & Cost Breakdown Modal -->
    <div x-show="showDetailsModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showDetailsModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="detailsData?.name + ' - Carrier Details'"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Carrier Profile &amp; Bandwidth Capacity</p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- 2-Column Summary Cards -->
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 block font-bold">Total Bandwidth</span>
                        <span class="text-sm font-bold font-mono text-cyan-800" x-text="`${detailsData?.tot_capacity || 0} Mbps`"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 block font-bold">Monthly Bill / Due</span>
                        <span class="text-sm font-bold font-mono text-rose-700" x-text="`{{ $currencySymbol }}${detailsData?.est_bill || 0}`"></span>
                    </div>
                </div>

                <!-- Link & Rate Breakdown -->
                <div class="space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block border-b border-slate-100 pb-1">Capacity by Traffic Type</span>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-blue-700 font-semibold block">Global Internet</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`${detailsData?.primary_link?.global_mbps || 0}M @ {{ $currencySymbol }}${detailsData?.primary_link?.global_rate || 0}`"></span>
                        </div>
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-emerald-700 font-semibold block">BDIX Peering</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`${detailsData?.primary_link?.bdix_mbps || 0}M @ {{ $currencySymbol }}${detailsData?.primary_link?.bdix_rate || 0}`"></span>
                        </div>
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-purple-700 font-semibold block">CDN Cache</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`${detailsData?.primary_link?.cdn_mbps || 0}M @ {{ $currencySymbol }}${detailsData?.primary_link?.cdn_rate || 0}`"></span>
                        </div>
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-amber-700 font-semibold block">Google GGC</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`${detailsData?.primary_link?.ggc_mbps || 0}M @ {{ $currencySymbol }}${detailsData?.primary_link?.ggc_rate || 0}`"></span>
                        </div>
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-indigo-700 font-semibold block">Facebook FNA</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`${detailsData?.primary_link?.fna_mbps || 0}M @ {{ $currencySymbol }}${detailsData?.primary_link?.fna_rate || 0}`"></span>
                        </div>
                        <div class="p-2 rounded bg-slate-50 border border-slate-200">
                            <span class="text-[10px] text-slate-600 font-semibold block">Transmission Fee</span>
                            <span class="font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }}${detailsData?.primary_link?.monthly_transmission_cost || 0}`"></span>
                        </div>
                    </div>
                </div>

                <!-- Bank Info -->
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Bank Account Details</span>
                    <div class="text-slate-800 font-medium" x-text="`Bank: ${detailsData?.bank_name || 'N/A'}`"></div>
                    <div class="text-slate-600 font-mono" x-text="`A/C: ${detailsData?.bank_account_no || 'N/A'} (Branch: ${detailsData?.bank_branch || 'N/A'})`"></div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="showDetailsModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function upstreamManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showProviderModal: false,
            showInvoiceModal: false,
            showPaymentModal: false,
            showDetailsModal: false,
            isEditMode: false,
            currentProvider: {},
            detailsData: {},

            formData: {
                name: '',
                carrier_type: 'IIG',
                contact_person: '',
                phone: '',
                email: '',
                address: '',
                bank_name: '',
                bank_account_no: '',
                bank_branch: '',
                routing_no: '',
                notes: '',
                router_id: '',
                interface_port: '',
                circuit_id: '',
                global_mbps: 0,
                bdix_mbps: 0,
                cdn_mbps: 0,
                ggc_mbps: 0,
                fna_mbps: 0,
                other_mbps: 0,
                global_rate: 0,
                bdix_rate: 0,
                cdn_rate: 0,
                ggc_rate: 0,
                fna_rate: 0,
                other_rate: 0,
                monthly_transmission_cost: 0
            },

            invoiceData: {
                provider_id: '',
                invoice_no: '',
                billing_month: '{{ date('Y-m-01') }}',
                bandwidth_cost: 0,
                transmission_cost: 0,
                vat_tax: 0
            },

            paymentData: {
                provider_id: '',
                amount: 0,
                payment_method: 'BANK_TRANSFER',
                bank_name: '',
                transaction_ref: '',
                paid_at: '{{ date('Y-m-d\TH:i') }}'
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

            totalMbps() {
                return (
                    Number(this.formData.global_mbps || 0) +
                    Number(this.formData.bdix_mbps || 0) +
                    Number(this.formData.cdn_mbps || 0) +
                    Number(this.formData.ggc_mbps || 0) +
                    Number(this.formData.fna_mbps || 0) +
                    Number(this.formData.other_mbps || 0)
                ).toFixed(2);
            },

            estMonthlyCost() {
                const bw = (Number(this.formData.global_mbps || 0) * Number(this.formData.global_rate || 0)) +
                           (Number(this.formData.bdix_mbps || 0) * Number(this.formData.bdix_rate || 0)) +
                           (Number(this.formData.cdn_mbps || 0) * Number(this.formData.cdn_rate || 0)) +
                           (Number(this.formData.ggc_mbps || 0) * Number(this.formData.ggc_rate || 0)) +
                           (Number(this.formData.fna_mbps || 0) * Number(this.formData.fna_rate || 0)) +
                           (Number(this.formData.other_mbps || 0) * Number(this.formData.other_rate || 0));
                const total = bw + Number(this.formData.monthly_transmission_cost || 0);
                return total.toFixed(2);
            },

            openCreateModal() {
                this.isEditMode = false;
                this.currentProvider = {};
                this.formData = {
                    name: '',
                    carrier_type: 'IIG',
                    contact_person: '',
                    phone: '',
                    email: '',
                    address: '',
                    bank_name: '',
                    bank_account_no: '',
                    bank_branch: '',
                    routing_no: '',
                    notes: '',
                    router_id: '',
                    interface_port: '',
                    circuit_id: '',
                    global_mbps: 0,
                    bdix_mbps: 0,
                    cdn_mbps: 0,
                    ggc_mbps: 0,
                    fna_mbps: 0,
                    other_mbps: 0,
                    global_rate: 0,
                    bdix_rate: 0,
                    cdn_rate: 0,
                    ggc_rate: 0,
                    fna_rate: 0,
                    other_rate: 0,
                    monthly_transmission_cost: 0
                };
                this.showProviderModal = true;
            },

            openEditModal(item) {
                this.activeMenu = null;
                this.isEditMode = true;
                this.currentProvider = item;
                const link = item.primary_link || {};
                this.formData = {
                    name: item.name || '',
                    carrier_type: item.carrier_type || 'IIG',
                    contact_person: item.contact_person || '',
                    phone: item.phone || '',
                    email: item.email || '',
                    address: item.address || '',
                    bank_name: item.bank_name || '',
                    bank_account_no: item.bank_account_no || '',
                    bank_branch: item.bank_branch || '',
                    routing_no: item.routing_no || '',
                    notes: item.notes || '',
                    router_id: link.router_id || '',
                    interface_port: link.interface_port || '',
                    circuit_id: link.circuit_id || '',
                    global_mbps: link.global_mbps || 0,
                    bdix_mbps: link.bdix_mbps || 0,
                    cdn_mbps: link.cdn_mbps || 0,
                    ggc_mbps: link.ggc_mbps || 0,
                    fna_mbps: link.fna_mbps || 0,
                    other_mbps: link.other_mbps || 0,
                    global_rate: link.global_rate || 0,
                    bdix_rate: link.bdix_rate || 0,
                    cdn_rate: link.cdn_rate || 0,
                    ggc_rate: link.ggc_rate || 0,
                    fna_rate: link.fna_rate || 0,
                    other_rate: link.other_rate || 0,
                    monthly_transmission_cost: link.monthly_transmission_cost || 0
                };
                this.showProviderModal = true;
            },

            openInvoiceModal(item = null) {
                this.activeMenu = null;
                this.invoiceData = {
                    provider_id: item?.id || '',
                    invoice_no: `INV-${Date.now().toString().slice(-6)}`,
                    billing_month: '{{ date('Y-m-01') }}',
                    bandwidth_cost: item?.est_bill ? (item.est_bill - (item.trans_cost || 0)) : 0,
                    transmission_cost: item?.trans_cost || 0,
                    vat_tax: 0
                };
                this.showInvoiceModal = true;
            },

            openPaymentModal(item = null) {
                this.activeMenu = null;
                this.paymentData = {
                    provider_id: item?.id || '',
                    amount: item?.due_balance || item?.est_bill || 0,
                    payment_method: 'BANK_TRANSFER',
                    bank_name: item?.bank_name || '',
                    transaction_ref: '',
                    paid_at: '{{ date('Y-m-d\TH:i') }}'
                };
                this.showPaymentModal = true;
            },

            viewDetails(item) {
                this.activeMenu = null;
                this.detailsData = item;
                this.showDetailsModal = true;
            },

            async toggleCarrierStatus(item) {
                this.activeMenu = null;
                try {
                    const response = await fetch(`/admin/upstream/providers/${item.id}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const res = await response.json();
                    if (res.success) {
                        item.is_active = res.is_active;
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update carrier status.'
                    });
                }
            },

            async deleteCarrier(item) {
                this.activeMenu = null;
                const result = await Swal.fire({
                    title: 'Delete Upstream Carrier?',
                    text: `Are you sure you want to delete '${item.name}'? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Carrier',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/admin/upstream/providers/${item.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Carrier Deleted',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cannot Delete',
                                text: data.message
                            });
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting the carrier.'
                        });
                    }
                }
            }
        };
    }

    // Trigger SweetAlert2 for server-side flash sessions
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: "{{ session('error') }}"
            });
        @endif
    });
</script>
@endpush
