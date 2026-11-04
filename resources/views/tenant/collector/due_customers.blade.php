@extends('tenant.layouts.app')

@section('title', 'Area Due Customers - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Management'))

@push('styles')
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
@endpush

@section('content')
<div class="space-y-3" x-data="dueCustomersManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.collector.dashboard') }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs cursor-pointer"
               title="Back to Collector Hub">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Area Due Customers</h1>
        </div>
        
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            {{-- Print Field Due Sheet Button --}}
            <a href="{{ Route::has('tenant.collector.due-customers.print') ? route('tenant.collector.due-customers.print', request()->query()) : url('/admin/collector/due-customers/print?' . http_build_query(request()->query())) }}" 
               target="_blank"
               class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
               title="Print Offline Field Collection Sheet">
                <i class="fas fa-print text-cyan-600 text-[11px]"></i>
                <span>Print Due Sheet</span>
            </a>

            {{-- Daily Cash Handover Quick Access --}}
            <a href="{{ route('tenant.collector.handover') }}" 
               class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold border border-amber-200/90 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
               title="Submit Cash in Hand to Office">
                <i class="fas fa-hand-holding-dollar text-amber-600 text-[11px]"></i>
                <span>Handover Cash</span>
            </a>

            {{-- Hub Shortcut --}}
            <a href="{{ route('tenant.collector.dashboard') }}" 
               class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-gauge-high text-[11px]"></i>
                <span>Collector Hub</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Due Customers Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Due Subscribers</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['total_due_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-slash"></i>
            </div>
        </div>

        {{-- Card 2: Total Due Volume --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Due Amount</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">@currency($stats['total_due_amount'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        {{-- Card 3: Today Collected --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">Today Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">@currency($stats['today_collected'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 4: Cash in Hand --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-700 block truncate">Cash In Hand</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">@currency($stats['cash_in_hand'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0" title="Unhanded collected cash in pocket">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 5: Expired Lines --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Expired / Cut</span>
                <span class="text-[13px] font-bold font-mono text-slate-700 leading-tight block truncate">{{ number_format($stats['expired_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-plug-circle-xmark"></i>
            </div>
        </div>

        {{-- Card 6: Active Lines with Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-cyan-700 block truncate">Active (With Due)</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">{{ number_format($stats['active_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Architecture & Uniform h-[34px] - AGENTS.md Rule 2.C) --}}
    @php
        $activeMoreCount = collect([$packageFilter !== 'all' ? $packageFilter : null, ($collectorFilter ?? 'all') !== 'all' ? $collectorFilter : null])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search) }}' }">
        <form method="GET" action="{{ route('tenant.collector.due-customers') }}" id="dueFilterForm" class="space-y-2.5">
            
            {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] & Responsive Wrapping) --}}
            <div class="flex flex-wrap items-center gap-2">
                
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           id="dueSearchInput"
                           x-model="searchQuery"
                           placeholder="Search Username, Name, Phone, ID, Address..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400"
                           autocomplete="off">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-xs transition cursor-pointer"
                            title="Clear Search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Zone / Area Filter --}}
                <div class="w-full sm:w-40 flex-shrink-0">
                    <select name="zone_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all">All Zones / Areas</option>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneFilter === (string)$z->id ? 'selected' : '' }}>
                                {{ $z->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Due Status Filter --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="due_status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all_due" {{ $dueStatusFilter === 'all_due' ? 'selected' : '' }}>All Dues & Expired</option>
                        <option value="due_only" {{ $dueStatusFilter === 'due_only' ? 'selected' : '' }}>🟢 Active Lines (Due)</option>
                        <option value="expired_only" {{ $dueStatusFilter === 'expired_only' ? 'selected' : '' }}>🔴 Expired Lines</option>
                        <option value="high_due" {{ $dueStatusFilter === 'high_due' ? 'selected' : '' }}>⚠️ High Due (Tk 1000+)</option>
                    </select>
                </div>

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full sm:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="15" {{ request('per_page', $perPage) == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ request('per_page', $perPage) == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ request('per_page', $perPage) == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ request('per_page', $perPage) == 250 ? 'selected' : '' }}>250 / page</option>
                        <option value="all" {{ request('per_page', $perPage) === 'all' ? 'selected' : '' }}>All Subscribers</option>
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="Toggle Package & Collector Filters">
                    <i class="fas fa-sliders-h text-[10px]"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-cyan-600 text-white leading-none">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Filter & Reset Sequence (Strict AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 sm:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.collector.due-customers') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Package Profile Filter --}}
                <div class="w-full sm:w-48 flex-shrink-0">
                    <select name="package_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all">All Internet Packages</option>
                        @foreach($packages as $p)
                            <option value="{{ $p->id }}" {{ (string)$packageFilter === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->name }} (@currency($p->price))
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Collector Scoping (Admin / Manager Only) --}}
                @if(!$isCollector && count($collectors) > 0)
                <div class="w-full sm:w-52 flex-shrink-0">
                    <select name="collector_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all">All Field Collectors</option>
                        @foreach($collectors as $c)
                            <option value="{{ $c->id }}" {{ (string)($collectorFilter ?? '') === (string)$c->id ? 'selected' : '' }}>
                                <i class="fas fa-user"></i> {{ $c->name }} ({{ $c->phone ?: 'Staff' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-left font-semibold">Customer ID</th>
                        <th class="text-left font-semibold">Customer Name</th>
                        <th class="text-left font-semibold">Guardian Name</th>
                        <th class="text-left font-semibold">Mobile Number</th>
                        <th class="text-left font-semibold">Area</th>
                        <th class="text-left font-semibold">Package</th>
                        <th class="text-right font-semibold">Monthly Bill</th>
                        <th class="text-right font-semibold">Due Amount</th>
                        <th class="w-24 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        @php
                            $dueAmt = (float)($customer->due_amount ?? 0);
                            $cleanPhone = preg_replace('/[^0-9]/', '', $customer->phone ?? '');
                            if (str_starts_with($cleanPhone, '0')) {
                                $intlPhone = '88' . $cleanPhone;
                            } else {
                                $intlPhone = $cleanPhone;
                            }
                            $waMessage = rawurlencode("Dear Customer ({$customer->username}), your internet bill due is Tk " . number_format($dueAmt, 2) . ". Please pay your bill to keep your internet connection active. Thank you - " . ($tenant->company_name ?? 'ISP'));
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-500">{{ $customers->firstItem() + $index }}</td>

                            {{-- 2. Customer ID --}}
                            <td class="font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                {{ $customer->customer_id ?: $customer->username }}
                            </td>

                            {{-- 3. Customer Name (Clean - No email, code or extra tags) --}}
                            <td class="whitespace-nowrap font-medium text-slate-900 text-xs">
                                {{ $customer->name ?: $customer->username }}
                            </td>

                            {{-- 4. Guardian Name --}}
                            <td class="whitespace-nowrap font-sans text-slate-700 text-xs">
                                {{ $customer->father_name ?: '—' }}
                            </td>

                            {{-- 5. Mobile Number --}}
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                                    @if(!empty($customer->phone))
                                        <a href="tel:{{ $customer->phone }}" 
                                           class="inline-flex items-center gap-1 font-mono text-slate-700 hover:text-cyan-700 font-semibold text-xs"
                                           title="Call Now">
                                            <i class="fas fa-phone text-emerald-600 text-[10px]"></i>
                                            <span>{{ $customer->phone }}</span>
                                        </a>

                                        {{-- WhatsApp Quick Reminder Link --}}
                                        <a href="https://wa.me/{{ $intlPhone }}?text={{ $waMessage }}" 
                                           target="_blank" 
                                           class="w-5 h-5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 inline-flex items-center justify-center text-[10px] transition shadow-2xs"
                                           title="Send WhatsApp Due Reminder">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    @else
                                        <span class="text-slate-400 font-mono">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 6. Area (Properly resolve string attribute, relation or address) --}}
                            <td class="whitespace-nowrap font-medium text-slate-700 text-xs">
                                {{ (is_object($customer->zone) ? ($customer->zone->name ?? '') : $customer->zone) ?: ($customer->coverageZone?->name ?: ($customer->address ?: '—')) }}
                            </td>

                            {{-- 7. Package --}}
                            <td class="whitespace-nowrap font-medium text-slate-800 text-xs">
                                {{ $customer->package_display_name }}
                            </td>

                            {{-- 8. Monthly Bill / Price --}}
                            <td class="text-right whitespace-nowrap font-mono font-semibold text-xs text-slate-700">
                                @currency($customer->monthly_bill ?? ($customer->package?->price ?? 0))
                            </td>

                            {{-- 9. Due Amount --}}
                            <td class="text-right whitespace-nowrap font-mono font-bold text-xs text-rose-600">
                                @currency($dueAmt)
                            </td>

                            {{-- 10. Action --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="openCollectModal({
                                            id: {{ $customer->id }},
                                            username: '{{ addslashes($customer->username) }}',
                                            customer_id: '{{ addslashes($customer->customer_id ?? $customer->username) }}',
                                            name: '{{ addslashes($customer->name ?? $customer->username) }}',
                                            phone: '{{ addslashes($customer->phone ?? '') }}',
                                            package_name: '{{ addslashes($customer->package_display_name) }}',
                                            monthly_bill: '{{ (float)($customer->monthly_bill ?? ($customer->package?->price ?? 0)) }}',
                                            due_amount: {{ $dueAmt }}
                                        })" 
                                        class="h-[28px] px-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer"
                                        title="Collect Due Bill">
                                    <i class="fas fa-money-bill-wave text-[10px]"></i>
                                    <span>Collect</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                <i class="fas fa-circle-check text-2xl mb-1.5 block text-emerald-500"></i>
                                <span class="font-medium text-slate-600 text-xs">No due customers found in the selected scope.</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">All assigned subscribers in this area have zero outstanding dues.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">All assigned subscribers in this area have zero outstanding dues.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- 5. COLLECT BILL SOFT NATURAL MODAL (Strict AGENTS.md Rule 3 Standard) --}}
    <div x-show="showCollectModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.outside="if(!isSubmitting) showCollectModal = false">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">
                            Collect Customer Bill
                        </h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">
                            Receive field payment, update ledger, and auto-renew subscriber line
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="showCollectModal = false" 
                        :disabled="isSubmitting"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body Form --}}
            <form @submit.prevent="submitBillCollection()" class="p-4 space-y-3">
                
                {{-- Subscriber Summary Banner --}}
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                    <div>
                        <div class="flex items-center gap-1.5 font-semibold text-slate-900 font-mono">
                            <span x-text="selectedCustomer?.username"></span>
                            <span class="text-[10px] text-slate-400 font-normal" x-text="'(' + selectedCustomer?.customer_id + ')'"></span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                            <span x-text="selectedCustomer?.package_name"></span>
                            <span>•</span>
                            <span class="font-mono text-slate-700" x-text="'Fee: Tk ' + selectedCustomer?.monthly_bill"></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] uppercase font-semibold text-slate-500 block">Total Due</span>
                        <span class="font-bold font-mono text-sm text-rose-600" x-text="'Tk ' + Number(selectedCustomer?.due_amount || 0).toFixed(2)"></span>
                    </div>
                </div>

                {{-- Amount Field with Quick Presets --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-semibold text-slate-700">
                            Collection Amount (Tk) <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-1">
                            <button type="button" 
                                    @click="setPreset('full')" 
                                    class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer">
                                Full Due
                            </button>
                            <button type="button" 
                                    @click="setPreset('monthly')" 
                                    class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 transition cursor-pointer">
                                1 Month
                            </button>
                        </div>
                    </div>
                    <input type="number" 
                           x-model="collectAmount" 
                           step="0.01" 
                           min="1" 
                           required 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold font-mono px-3 py-1.5 text-slate-900 focus:bg-white focus:border-cyan-500 shadow-2xs">
                </div>

                {{-- Payment Method & Discount Row --}}
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="paymentMethod" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 font-medium text-slate-800 shadow-2xs">
                            <option value="cash">Cash in Hand</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="bank">Bank Deposit</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Discount / Waiver (Tk)
                        </label>
                        <input type="number" 
                               x-model="collectDiscount" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                {{-- Online Transaction ID (Shown if non-cash) --}}
                <div x-show="paymentMethod !== 'cash'" x-cloak>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Transaction / TrxID
                    </label>
                    <input type="text" 
                           x-model="trxId" 
                           placeholder="e.g. 9J83KL41N" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                </div>

                {{-- Collector Note --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Collection Note (Optional)
                    </label>
                    <input type="text" 
                           x-model="notes" 
                           placeholder="e.g. Received at customer doorstep" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                </div>

                {{-- Modal Footer --}}
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" 
                            @click="showCollectModal = false" 
                            :disabled="isSubmitting"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    
                    <button type="submit" 
                            :disabled="isSubmitting || collectAmount <= 0"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" x-cloak></i>
                        <i class="fas fa-check" x-show="!isSubmitting"></i>
                        <span>Confirm Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 6. PAYMENT SUCCESS & RECEIPT PROMPT MODAL --}}
    <div x-show="showReceiptModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden p-4 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center text-xl shadow-xs">
                <i class="fas fa-check"></i>
            </div>
            
            <div>
                <h3 class="text-sm font-bold text-slate-800">Payment Collected Successfully!</h3>
                <p class="text-xs text-slate-500 mt-1" x-text="receiptMessage"></p>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1 text-left font-mono">
                <div class="flex justify-between">
                    <span class="text-slate-500">Subscriber:</span>
                    <span class="font-bold text-slate-800" x-text="receiptData?.username"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Amount Paid:</span>
                    <span class="font-bold text-emerald-600" x-text="'Tk ' + Number(receiptData?.amount || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Method:</span>
                    <span class="font-bold text-slate-700 uppercase" x-text="receiptData?.payment_method"></span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-1">
                    <span class="text-slate-500">Remaining Due:</span>
                    <span class="font-bold text-rose-600" x-text="'Tk ' + Number(receiptData?.remaining_due || 0).toFixed(2)"></span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-center gap-2">
                <button type="button" 
                        @click="showReceiptModal = false; window.location.reload();" 
                        class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200 transition cursor-pointer">
                    Close
                </button>
                <button type="button" 
                        @click="printReceipt(receiptData)" 
                        class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>Print Thermal Slip</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dueCustomersManager() {
    return {
        showCollectModal: false,
        showReceiptModal: false,
        selectedCustomer: null,
        collectAmount: 0,
        collectDiscount: 0,
        paymentMethod: 'cash',
        trxId: '',
        notes: '',
        isSubmitting: false,
        receiptMessage: '',
        receiptData: null,

        openCollectModal(customer) {
            this.selectedCustomer = customer;
            this.collectAmount = Number(customer.due_amount || 0);
            this.collectDiscount = 0;
            this.paymentMethod = 'cash';
            this.trxId = '';
            this.notes = '';
            this.showCollectModal = true;
        },

        setPreset(type) {
            if (!this.selectedCustomer) return;
            if (type === 'full') {
                this.collectAmount = Number(this.selectedCustomer.due_amount || 0);
            } else if (type === 'monthly') {
                this.collectAmount = Number(this.selectedCustomer.monthly_bill || this.selectedCustomer.due_amount || 0);
            }
        },

        async submitBillCollection() {
            if (this.isSubmitting || !this.selectedCustomer) return;
            this.isSubmitting = true;

            const payload = {
                _token: '{{ csrf_token() }}',
                customer_id: this.selectedCustomer.id,
                amount: this.collectAmount,
                discount: this.collectDiscount || 0,
                payment_method: this.paymentMethod,
                trx_id: this.trxId || null,
                notes: this.notes || ''
            };

            try {
                const response = await fetch('{{ route("tenant.collector.collect-bill") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showCollectModal = false;
                    this.receiptMessage = data.message;
                    this.receiptData = {
                        username: this.selectedCustomer.username,
                        customer_id: this.selectedCustomer.customer_id,
                        amount: this.collectAmount,
                        payment_method: this.paymentMethod,
                        remaining_due: Math.max(0, (this.selectedCustomer.due_amount - this.collectAmount - (this.collectDiscount || 0))),
                        payment_id: data.payment?.id || Date.now(),
                        date: new Date().toLocaleString()
                    };
                    this.showReceiptModal = true;
                } else {
                    alert(data.message || 'Payment collection failed. Please verify the amount.');
                }
            } catch (err) {
                console.error(err);
                alert('An unexpected network error occurred while submitting collection.');
            } finally {
                this.isSubmitting = false;
            }
        },

        printReceipt(data) {
            if (!data) return;
            const companyName = '{{ addslashes($tenant->company_name ?? $tenant->name ?? "ISP Network") }}';
            const companyPhone = '{{ addslashes($tenant->phone ?? "") }}';
            const printWindow = window.open('', '_blank', 'width=350,height=500');
            
            const html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payment Receipt #${data.payment_id}</title>
                    <style>
                        body { font-family: 'Courier New', monospace; font-size: 12px; margin: 0; padding: 10px; line-height: 1.3; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .bold { font-weight: bold; }
                        .divider { border-top: 1px dashed #000; margin: 8px 0; }
                        .row { display: flex; justify-content: space-between; }
                    </style>
                </head>
                <body>
                    <div class="text-center bold" style="font-size: 14px;">${companyName}</div>
                    ${companyPhone ? `<div class="text-center">${companyPhone}</div>` : ''}
                    <div class="text-center">MONEY RECEIPT (CUSTOMER COPY)</div>
                    <div class="divider"></div>
                    <div class="row"><span>Date:</span><span>${data.date}</span></div>
                    <div class="row"><span>Receipt #:</span><span>REC-${data.payment_id}</span></div>
                    <div class="row"><span>Subscriber:</span><span class="bold">${data.username}</span></div>
                    <div class="row"><span>Customer ID:</span><span>${data.customer_id}</span></div>
                    <div class="divider"></div>
                    <div class="row bold" style="font-size: 13px;"><span>Paid Amount:</span><span>Tk ${Number(data.amount).toFixed(2)}</span></div>
                    <div class="row"><span>Method:</span><span>${data.payment_method.toUpperCase()}</span></div>
                    <div class="row"><span>Remaining Due:</span><span>Tk ${Number(data.remaining_due).toFixed(2)}</span></div>
                    <div class="divider"></div>
                    <div class="text-center" style="font-size: 10px; margin-top: 10px;">
                        Thank you for using our internet service!<br>
                        Powered by SomitySoft Cloud
                    </div>
                </body>
                </html>
            `;

            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 300);
        }
    };
}
</script>
@endpush
