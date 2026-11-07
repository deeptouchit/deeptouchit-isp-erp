@extends('tenant.layouts.app')

@section('title', 'Upstream Bandwidth & Carrier Accounting - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (Strictly Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-tower-broadcast"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Upstream Bandwidth &amp; Carrier Accounting</h1>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    Total {{ number_format($providers->total()) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <button type="button" 
                    @click="openCreateModal()"
                    class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Add Carrier</span>
            </button>
            <button type="button" 
                    @click="openInvoiceModal()"
                    class="h-[34px] px-3 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice-dollar text-xs"></i>
                <span>Record Bill</span>
            </button>
            <button type="button" 
                    @click="openPaymentModal()"
                    class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-money-bill-transfer text-xs"></i>
                <span>Disburse Payment</span>
            </button>
            <a href="{{ route('tenant.upstream.invoices') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice text-xs text-blue-600"></i>
                <span>Carrier Bills</span>
            </a>
            <a href="{{ route('tenant.upstream.payments') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs text-emerald-600"></i>
                <span>Payment Vouchers</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Upstream Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Upstream</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">
                    {{ $totalUpstreamMbps >= 1000 ? round($totalUpstreamMbps / 1000, 2) . ' Gbps' : number_format($totalUpstreamMbps) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        {{-- Card 2: Global Internet Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Global Internet</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">
                    {{ $globalMbps >= 1000 ? round($globalMbps / 1000, 2) . ' Gbps' : number_format($globalMbps) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        {{-- Card 3: Peering & Cache Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Peering &amp; Cache</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">
                    {{ $peeringCacheMbps >= 1000 ? round($peeringCacheMbps / 1000, 2) . ' Gbps' : number_format($peeringCacheMbps) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-diagram-project"></i>
            </div>
        </div>

        {{-- Card 4: Monthly Carrier Cost --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Monthly Est Cost</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700 truncate">@currency($monthlyCarrierCost)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 5: Carrier Total Due Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Carrier Total Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">@currency($carrierTotalDue)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 6: Gross Bandwidth Margin --}}
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

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    @php
        $isMoreOpen = (!empty(request('carrier_type')));
        $activeMoreCount = (!empty(request('carrier_type')) ? 1 : 0);
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.upstream.index') }}" class="space-y-2.5">
            
            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with 1-Click Clear --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           value="{{ request('search') }}" 
                           placeholder="Search carrier, contact, phone, bank account..." 
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Core Router --}}
                <select name="router_id" class="h-[34px] w-full md:w-48 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="">All Core Routers</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->ip_address }})</option>
                    @endforeach
                </select>

                {{-- Primary Filter: Status --}}
                <select name="status" class="h-[34px] w-full md:w-32 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="h-[34px] w-full md:w-28 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono cursor-pointer transition">
                    <option value="15" {{ request('per_page', '20') == '15' ? 'selected' : '' }}>15 / page</option>
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 / page</option>
                </select>

                {{-- More Filters Toggle Accordion Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] px-2.5 py-0 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'border-cyan-400 bg-cyan-50/50 text-cyan-800' : ''">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Action Buttons Sequence (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.upstream.index') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset All Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Carrier Type Filter --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">Carrier Category / Type</label>
                    <select name="carrier_type" class="h-[34px] w-full sm:w-56 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="">All Carrier Types</option>
                        <option value="Upstream Carrier" {{ request('carrier_type') == 'Upstream Carrier' ? 'selected' : '' }}>Upstream Carrier / IIG</option>
                        <option value="ITC Carrier" {{ request('carrier_type') == 'ITC Carrier' ? 'selected' : '' }}>ITC Carrier</option>
                        <option value="NTTN Transmission" {{ request('carrier_type') == 'NTTN Transmission' ? 'selected' : '' }}>NTTN Transmission Provider</option>
                        <option value="BDIX Peering" {{ request('carrier_type') == 'BDIX Peering' ? 'selected' : '' }}>BDIX / Local Peering</option>
                        <option value="CDN / Cache Partner" {{ request('carrier_type') == 'CDN / Cache Partner' ? 'selected' : '' }}>CDN / Cache Partner</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> - AGENTS.md Rule 2.D & Updated Columns Policy) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Carrier Provider</th>
                        <th class="text-center font-semibold">Type</th>
                        <th class="text-center font-semibold">Core Router &amp; Port</th>
                        <th class="text-center font-semibold">Total Capacity</th>
                        <th class="text-center font-semibold">Monthly Bill</th>
                        <th class="text-center font-semibold">Due Balance</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $index => $provider)
                        @php
                            $primaryLink = $provider->links->first();
                            $totCapacity = (float) $provider->links->sum('total_mbps');
                            $transCost = (float) $provider->links->sum('monthly_transmission_cost');
                            $estBill = (float) $provider->links->sum('est_monthly_bill');
                            $dueBal = (float) $provider->invoices->sum('due_amount');
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 text-xs w-10">
                                {{ $providers->firstItem() + $index }}
                            </td>

                            {{-- 2. Carrier Provider (text-left) --}}
                            <td class="text-left font-bold text-slate-800 text-xs whitespace-nowrap">
                                <span class="cursor-pointer hover:text-cyan-700 hover:underline" 
                                      @click="viewDetails({{ json_encode([
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
                                      ]) }})">
                                    {{ $provider->name }}
                                </span>
                            </td>

                            {{-- 3. Carrier Type (text-center badge) --}}
                            <td class="text-center whitespace-nowrap">
                                @php
                                    $t = strtolower($provider->carrier_type ?? '');
                                    $badgeClass = 'border-slate-200 bg-slate-50 text-slate-700';
                                    if (str_contains($t, 'iig') || str_contains($t, 'upstream')) {
                                        $badgeClass = 'border-cyan-200 bg-cyan-50 text-cyan-700';
                                    } elseif (str_contains($t, 'itc')) {
                                        $badgeClass = 'border-blue-200 bg-blue-50 text-blue-700';
                                    } elseif (str_contains($t, 'nttn') || str_contains($t, 'transmission')) {
                                        $badgeClass = 'border-purple-200 bg-purple-50 text-purple-700';
                                    } elseif (str_contains($t, 'bdix') || str_contains($t, 'peering')) {
                                        $badgeClass = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                    } elseif (str_contains($t, 'cdn') || str_contains($t, 'cache')) {
                                        $badgeClass = 'border-amber-200 bg-amber-50 text-amber-700';
                                    }
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $badgeClass }}">
                                    {{ $provider->carrier_type ?: 'Carrier' }}
                                </span>
                            </td>

                            {{-- 4. Core Router & Port (text-left) --}}
                            <td class="text-left text-slate-700 text-xs whitespace-nowrap">
                                @if($primaryLink && $primaryLink->router)
                                    <span>{{ $primaryLink->router->name }}</span>
                                    @if($primaryLink->interface_port)
                                        <span class="ml-1 px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-slate-100 text-slate-600 border border-slate-200">{{ $primaryLink->interface_port }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">Direct Delivery</span>
                                @endif
                            </td>

                            {{-- 5. Total Capacity (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                {{ $totCapacity >= 1000 ? round($totCapacity / 1000, 2) . ' Gbps' : number_format($totCapacity) . ' Mbps' }}
                            </td>

                            {{-- 6. Monthly Bill (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-slate-900 text-xs whitespace-nowrap">
                                @currency($estBill)
                            </td>

                            {{-- 7. Due Balance (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-xs whitespace-nowrap {{ $dueBal > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                @currency($dueBal)
                            </td>

                            {{-- 8. Status (text-center badge) --}}
                            <td class="text-center whitespace-nowrap">
                                @if($provider->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-rose-200 bg-rose-50 text-rose-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Inactive</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 9. Action (3-Dot Floating Trigger) --}}
                            <td class="text-center whitespace-nowrap">
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
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-sm border border-cyan-100">
                                        <i class="fas fa-tower-broadcast"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Upstream Carriers Registered</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">Register your upstream IIG, ITC, and NTTN transmission links to monitor bandwidth and carrier billing.</p>
                                    <button type="button" @click="openCreateModal()" class="mt-2 h-[30px] px-3 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-xs font-semibold border border-cyan-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        <span>Add Carrier Now</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($providers->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    Showing {{ $providers->firstItem() }} to {{ $providers->lastItem() }} of {{ $providers->total() }} upstream carriers
                </span>
                <div>
                    {{ $providers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. GLOBAL FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null" 
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-52 text-xs font-medium text-slate-700 space-y-0.5 divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        {{-- Group 1: Diagnostics & Info --}}
        <div class="py-1">
            <button type="button" 
                    @click="viewDetails(activeMenu)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-circle-info w-4 text-cyan-600 text-[11px]"></i>
                <span>View Capacity Details</span>
            </button>
            <button type="button" 
                    @click="openInvoiceModal(activeMenu)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-file-invoice-dollar w-4 text-slate-600 text-[11px]"></i>
                <span>Record Monthly Bill</span>
            </button>
            <button type="button" 
                    @click="openPaymentModal(activeMenu)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-money-bill-transfer w-4 text-emerald-600 text-[11px]"></i>
                <span>Disburse Payment</span>
            </button>
        </div>

        {{-- Group 2: Configuration & Status --}}
        <div class="py-1">
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
        </div>

        {{-- Group 3: Danger --}}
        <div class="py-1">
            <button type="button" 
                    @click="deleteCarrier(activeMenu)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
                <span>Delete Carrier</span>
            </button>
        </div>
    </div>

    {{-- 6. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3 & Strict Uniform Height h-[34px]) --}}

    {{-- Modal 1: Add / Edit Carrier Provider & Link Modal --}}
    <div x-show="showProviderModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-3xl overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showProviderModal = false">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-tower-broadcast"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="isEditMode ? 'Edit Upstream Carrier & Capacity' : 'Add Upstream Carrier Provider'"></h3>
                </div>
                <button type="button" @click="showProviderModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Form Body --}}
            <form :action="isEditMode ? `/admin/upstream/providers/${currentProvider.id}` : '{{ route('tenant.upstream.provider.store') }}'" method="POST" class="p-4 space-y-4 max-h-[76vh] overflow-y-auto text-xs">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!isEditMode">
                <input type="hidden" name="link_id" :value="currentProvider?.primary_link?.id || ''" x-bind:disabled="!isEditMode || !currentProvider?.primary_link">

                {{-- Section 1: Carrier Profile --}}
                <div class="space-y-2.5">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-100">
                        <i class="fas fa-building text-[10px] text-cyan-600"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">1. Carrier Company Information</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                        <div class="md:col-span-2">
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Carrier / Provider Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   x-model="formData.name" 
                                   required 
                                   placeholder="e.g. Summit Communications Ltd." 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Carrier Type / Category <span class="text-rose-500">*</span>
                            </label>
                            <select name="carrier_type" 
                                    x-model="formData.carrier_type" 
                                    required
                                    class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                                <option value="Upstream Carrier">Upstream Carrier / IIG</option>
                                <option value="ITC Carrier">ITC Carrier</option>
                                <option value="NTTN Transmission">NTTN Transmission</option>
                                <option value="BDIX Peering">BDIX Peering</option>
                                <option value="CDN / Cache Partner">CDN / Cache Partner</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Contact Person Name
                            </label>
                            <input type="text" 
                                   name="contact_person" 
                                   x-model="formData.contact_person" 
                                   placeholder="e.g. Md. Rafiqul Islam" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Contact Phone Number
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   x-model="formData.phone" 
                                   placeholder="e.g. 017xxxxxxxx" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Contact Email Address
                            </label>
                            <input type="email" 
                                   name="email" 
                                   x-model="formData.email" 
                                   placeholder="e.g. noc@carrier.com" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Bank Name &amp; Branch
                            </label>
                            <input type="text" 
                                   name="bank_name" 
                                   x-model="formData.bank_name" 
                                   placeholder="e.g. City Bank - Gulshan" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Bank Account Number
                            </label>
                            <input type="text" 
                                   name="bank_account_no" 
                                   x-model="formData.bank_account_no" 
                                   placeholder="e.g. 1102938475001" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Routing Number
                            </label>
                            <input type="text" 
                                   name="routing_no" 
                                   x-model="formData.routing_no" 
                                   placeholder="e.g. 225272635" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>
                </div>

                {{-- Section 2: Link & Core Router Port --}}
                <div class="space-y-2.5 pt-1">
                    <div class="flex items-center gap-1.5 pb-1 border-b border-slate-100">
                        <i class="fas fa-network-wired text-[10px] text-cyan-600"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">2. Core Router &amp; Delivery Link</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Connected Core Router
                            </label>
                            <select name="router_id" 
                                    x-model="formData.router_id" 
                                    class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                                <option value="">Select Core Router</option>
                                @foreach($routers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Interface Port / SFP
                            </label>
                            <input type="text" 
                                   name="interface_port" 
                                   x-model="formData.interface_port" 
                                   placeholder="e.g. sfp-sfpplus1" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Circuit ID / Tag
                            </label>
                            <input type="text" 
                                   name="circuit_id" 
                                   x-model="formData.circuit_id" 
                                   placeholder="e.g. CKT-DH-901" 
                                   class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>
                </div>

                {{-- Section 3: Traffic Capacities & Unit Rates Matrix --}}
                <div class="space-y-2.5 pt-1">
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-chart-pie text-[10px] text-cyan-600"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">3. Purchased Capacity (Mbps) &amp; Unit Rates ({{ $currencySymbol }}/Mbps)</span>
                        </div>
                        <span class="text-[10.5px] font-mono font-bold text-cyan-700" x-text="`Total Bandwidth: ${totalMbps()} Mbps`"></span>
                    </div>

                    {{-- Structured Capacity Breakdown Table --}}
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
                                {{-- 1. Global Internet --}}
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-blue-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-globe text-[11px] w-4 text-blue-500"></i>
                                            <span>Global Internet (IP Transit)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="global_mbps" x-model.number="formData.global_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="global_rate" x-model.number="formData.global_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.global_mbps || 0) * Number(formData.global_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                {{-- 2. BDIX Peering --}}
                                <tr class="hover:bg-emerald-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-emerald-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-network-wired text-[11px] w-4 text-emerald-500"></i>
                                            <span>BDIX Peering</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="bdix_mbps" x-model.number="formData.bdix_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="bdix_rate" x-model.number="formData.bdix_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.bdix_mbps || 0) * Number(formData.bdix_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                {{-- 3. CDN Cache --}}
                                <tr class="hover:bg-purple-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-purple-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-bolt text-[11px] w-4 text-purple-500"></i>
                                            <span>CDN Cache / Akamai</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="cdn_mbps" x-model.number="formData.cdn_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="cdn_rate" x-model.number="formData.cdn_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.cdn_mbps || 0) * Number(formData.cdn_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                {{-- 4. Google GGC --}}
                                <tr class="hover:bg-amber-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-amber-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fab fa-google text-[11px] w-4 text-amber-500"></i>
                                            <span>Google Cache (GGC)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="ggc_mbps" x-model.number="formData.ggc_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="ggc_rate" x-model.number="formData.ggc_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.ggc_mbps || 0) * Number(formData.ggc_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                {{-- 5. Facebook FNA --}}
                                <tr class="hover:bg-indigo-50/30 transition">
                                    <td class="py-1.5 px-3 font-semibold text-indigo-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fab fa-facebook text-[11px] w-4 text-indigo-500"></i>
                                            <span>Facebook Cache (FNA)</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="fna_mbps" x-model.number="formData.fna_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="fna_rate" x-model.number="formData.fna_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.fna_mbps || 0) * Number(formData.fna_rate || 0))).toFixed(2)}`"></td>
                                </tr>

                                {{-- 6. Other / Peering --}}
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-1.5 px-3 font-semibold text-slate-700">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-server text-[11px] w-4 text-slate-400"></i>
                                            <span>Other / Private Peering</span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="other_mbps" x-model.number="formData.other_mbps" placeholder="0" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1 px-2">
                                        <input type="number" step="0.01" min="0" name="other_rate" x-model.number="formData.other_rate" placeholder="0.00" class="h-[30px] w-full text-right bg-slate-50 focus:bg-white border border-slate-200 rounded px-2.5 font-mono text-xs focus:border-cyan-500 focus:outline-hidden transition">
                                    </td>
                                    <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-800" x-text="`{{ $currencySymbol }} ${((Number(formData.other_mbps || 0) * Number(formData.other_rate || 0))).toFixed(2)}`"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Transmission Charge & Est Bill Box --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                        <div>
                            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
                                Monthly Transmission Charge ({{ $currencySymbol }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="monthly_transmission_cost" 
                                   x-model.number="formData.monthly_transmission_cost" 
                                   placeholder="0.00" 
                                   class="h-[34px] w-full bg-white border border-slate-200 rounded-lg text-xs px-3 font-mono focus:border-cyan-500 focus:outline-hidden transition">
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

                {{-- Modal Footer --}}
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" @click="showProviderModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 rounded-lg shadow-xs transition cursor-pointer">
                        <span x-text="isEditMode ? 'Update Carrier Profile' : 'Save Upstream Carrier'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 2: Record Carrier Invoice / Monthly Bill Modal --}}
    <div x-show="showInvoiceModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showInvoiceModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Record Carrier Monthly Bill</h3>
                </div>
                <button type="button" @click="showInvoiceModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.invoice.store') }}" method="POST" class="p-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="invoiceData.provider_id" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->carrier_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Invoice / Bill No <span class="text-rose-500">*</span></label>
                        <input type="text" name="invoice_no" x-model="invoiceData.invoice_no" required placeholder="e.g. SMT-2026-09" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Billing Month <span class="text-rose-500">*</span></label>
                        <input type="date" name="billing_month" x-model="invoiceData.billing_month" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Bandwidth Cost <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="bandwidth_cost" x-model.number="invoiceData.bandwidth_cost" required placeholder="0.00" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono text-right focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">NTTN / Trans.</label>
                        <input type="number" step="0.01" min="0" name="transmission_cost" x-model.number="invoiceData.transmission_cost" placeholder="0.00" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono text-right focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">VAT / Tax</label>
                        <input type="number" step="0.01" min="0" name="vat_tax" x-model.number="invoiceData.vat_tax" placeholder="0.00" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono text-right focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between font-mono text-xs">
                    <span class="font-bold text-slate-600">Total Billed Amount:</span>
                    <span class="font-bold text-cyan-800 text-sm" x-text="`{{ $currencySymbol }} ${(Number(invoiceData.bandwidth_cost || 0) + Number(invoiceData.transmission_cost || 0) + Number(invoiceData.vat_tax || 0)).toFixed(2)}`"></span>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" @click="showInvoiceModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-4 rounded-lg shadow-xs transition cursor-pointer">
                        Record Carrier Bill
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 3: Disburse Payment & Generate Voucher Modal --}}
    <div x-show="showPaymentModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showPaymentModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Disburse Payment to Carrier</h3>
                </div>
                <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.payment.store') }}" method="POST" class="p-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="paymentData.provider_id" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (Due: @currency($p->invoices->sum('due_amount')))</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Paid Amount ({{ $currencySymbol }}) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" x-model.number="paymentData.amount" required placeholder="0.00" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono font-bold text-emerald-700 text-right focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select name="payment_method" x-model="paymentData.payment_method" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
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
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Bank Name / Branch</label>
                        <input type="text" name="bank_name" x-model="paymentData.bank_name" placeholder="e.g. City Bank" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Cheque / Trx Reference</label>
                        <input type="text" name="transaction_ref" x-model="paymentData.transaction_ref" placeholder="Ref No / Cheque No" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="paid_at" x-model="paymentData.paid_at" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" @click="showPaymentModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 rounded-lg shadow-xs transition cursor-pointer">
                        Disburse &amp; Generate Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 4: View Details & Cost Breakdown Modal --}}
    <div x-show="showDetailsModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150"
             @click.away="showDetailsModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="detailsData?.name + ' - Capacity & Rates'"></h3>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 max-h-[75vh] overflow-y-auto text-xs">
                {{-- 2-Column Summary Cards --}}
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

                {{-- Link & Rate Breakdown --}}
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

                {{-- Bank Info --}}
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Bank Account Details</span>
                    <div class="text-slate-800 font-medium" x-text="`Bank: ${detailsData?.bank_name || 'N/A'}`"></div>
                    <div class="text-slate-600 font-mono" x-text="`A/C: ${detailsData?.bank_account_no || 'N/A'} (Branch: ${detailsData?.bank_branch || 'N/A'})`"></div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end flex-shrink-0">
                <button type="button" @click="showDetailsModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-4 rounded-lg transition cursor-pointer">
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
                carrier_type: 'Upstream Carrier',
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
                    carrier_type: 'Upstream Carrier',
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
                    carrier_type: item.carrier_type || 'Upstream Carrier',
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
