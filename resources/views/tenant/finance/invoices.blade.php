@extends('tenant.layouts.app')

@section('title', 'Customer Invoices & Bills - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableInvoiceArea, #printableInvoiceArea * {
        visibility: visible !important;
    }
    #printableInvoiceArea {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        background: #fff !important;
        z-index: 999999 !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="space-y-3" 
     x-data="invoiceManager()" 
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
            <div class="w-7 h-7 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">
                @if(($statusFilter ?? '') === 'due')
                    Due Invoices &amp; Unpaid Bills
                @elseif(($statusFilter ?? '') === 'paid')
                    Paid Invoices &amp; Billing Ledger
                @else
                    Customer Invoices &amp; Billing Ledger
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            @if(isset($uninvoicedQueueCount) && $uninvoicedQueueCount > 0)
            <!-- Billing Catch-Up Queue Quick Link -->
            <a href="{{ route('tenant.finance.billing-catchup') }}" 
               class="px-2.5 py-1.5 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-semibold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer"
               title="{{ $uninvoicedQueueCount }} uninvoiced subscribers in catch-up queue">
                <i class="fas fa-bolt text-amber-600 text-[11px]"></i>
                <span>Catch-Up Queue</span>
                <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-600 text-white font-mono leading-none">{{ $uninvoicedQueueCount }}</span>
            </a>
            @endif

            <!-- Create Custom Invoice Button -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="px-3 py-1.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>+ Custom Invoice</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div id="invoicesKpiStrip" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Invoices -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Invoices</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Metric 2: Total Billed Amount -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Billed Amount</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block truncate">@currency($totalBilledAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>

        <!-- Metric 3: Total Collected Amount -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Collected Paid</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block truncate">@currency($totalPaidAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Metric 4: Outstanding Due -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block truncate">@currency($totalDueAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Unpaid & Overdue Count -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Unpaid Invoices</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($unpaidOverdueCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Metric 6: Collection Efficiency -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Collection Rate</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ $collectionRate }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C - Customer-Standard 2-Tier Architecture & Uniform h-[34px]) -->
    @php
        $secondaryFilters = [
            $dateFrom ? $dateFrom : null,
            $dateTo ? $dateTo : null,
            ($selectedMonth ?? 'all') !== 'all' ? $selectedMonth : null,
            ($selectedPackageId ?? 'all') !== 'all' ? $selectedPackageId : null,
            (!$isResellerUser && ($selectedResellerId ?? 'all') !== 'all') ? $selectedResellerId : null,
            ($collectorFilter ?? 'all') !== 'all' ? $collectorFilter : null,
        ];
        $activeMoreCount = collect($secondaryFilters)->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('tenant.finance.invoices') }}" id="invoiceFilterForm" @submit.prevent="submitSearchNow()" class="space-y-2.5">
            
            {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] & Responsive Wrapping) --}}
            <div class="flex flex-wrap items-center gap-2">
                
                {{-- Intelligent Live Search Box (Instant Client-Side & Debounced Server Search) --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-spinner fa-spin absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-cyan-600 pointer-events-none"
                       x-show="isSearching" x-cloak></i>
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"
                       x-show="!isSearching"></i>
                    <input type="text" 
                           name="search" 
                           id="invoiceSearchInput"
                           x-model="searchQuery"
                           @input="handleLiveSearch($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="Search Invoice #, Customer ID, Name, Phone, Address..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400 font-medium"
                           autocomplete="off">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="clearLiveSearch()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-xs transition cursor-pointer"
                            title="Clear Search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Quick Period Preset --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="period" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all" {{ ($period ?? '') === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="due" {{ ($statusFilter ?? '') === 'due' ? 'selected' : '' }}>Due (Unpaid & Partial)</option>
                        <option value="paid" {{ ($statusFilter ?? '') === 'paid' ? 'selected' : '' }}>Paid Only</option>
                        <option value="unpaid" {{ ($statusFilter ?? '') === 'unpaid' ? 'selected' : '' }}>Unpaid Only</option>
                        <option value="partially_paid" {{ ($statusFilter ?? '') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="overdue" {{ ($statusFilter ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Collector / Collected By Filter --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="collector_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all">All Collectors</option>
                        @foreach($allCollectors ?? [] as $col)
                            <option value="{{ $col->id }}" {{ (string)($collectorFilter ?? 'all') === (string)$col->id ? 'selected' : '' }}>
                                {{ $col->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full sm:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="15" {{ ($perPage ?? 20) == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ ($perPage ?? 20) == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ ($perPage ?? 20) == 250 ? 'selected' : '' }}>250 / page</option>
                        <option value="500" {{ ($perPage ?? 20) == 500 ? 'selected' : '' }}>500 / page</option>
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="Toggle Date Range, Month, Partner & Package Filters">
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

                {{-- Strict Filter & Reset Buttons Sequence (Strict AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 sm:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ ($statusFilter ?? '') === 'due' ? route('tenant.finance.invoices', ['status' => 'due']) : (($statusFilter ?? '') === 'paid' ? route('tenant.finance.invoices', ['status' => 'paid']) : route('tenant.finance.invoices')) }}" 
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
                
                {{-- Date From --}}
                <div class="relative w-full sm:w-36 flex-shrink-0">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9.5px] uppercase font-bold text-slate-400 pointer-events-none">From</span>
                    <input type="date" 
                           name="date_from" 
                           value="{{ $dateFrom ?? '' }}" 
                           class="w-full h-[34px] pl-11 pr-2 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Date To --}}
                <div class="relative w-full sm:w-36 flex-shrink-0">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9.5px] uppercase font-bold text-slate-400 pointer-events-none">To</span>
                    <input type="date" 
                           name="date_to" 
                           value="{{ $dateTo ?? '' }}" 
                           class="w-full h-[34px] pl-8 pr-2 text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Billing Month Selector --}}
                <div class="w-full sm:w-40 flex-shrink-0">
                    <select name="month" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all" {{ ($selectedMonth ?? '') === 'all' ? 'selected' : '' }}>All Months</option>
                        @foreach($availableMonths as $m)
                            <option value="{{ $m }}" {{ ($selectedMonth ?? '') === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Partner / Reseller Filter --}}
                @if(!$isResellerUser)
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="reseller_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all" {{ ($selectedResellerId ?? 'all') === 'all' ? 'selected' : '' }}>All Partners</option>
                        <option value="isp" {{ ($selectedResellerId ?? '') === 'isp' ? 'selected' : '' }}>Direct Retail (HQ)</option>
                        @foreach($allResellers ?? [] as $r)
                            <option value="{{ $r->id }}" {{ ($selectedResellerId ?? '') == $r->id ? 'selected' : '' }}>
                                {{ $r->code ?: $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Package Profile Filter --}}
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        <option value="all">All Packages</option>
                        @foreach($allPackages ?? [] as $pkg)
                            <option value="{{ $pkg->id }}" {{ ($selectedPackageId ?? '') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div id="invoicesTableCard" class="bg-white rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-center font-semibold">Invoice #</th>
                        <th class="text-center font-semibold">Cust ID</th>
                        <th class="text-center font-semibold">Customer</th>
                        <th class="text-center font-semibold">Guardian</th>
                        <th class="text-center font-semibold">Area</th>
                        <th class="text-center font-semibold">Partner</th>
                        <th class="text-center font-semibold">Package</th>
                        <th class="text-center font-semibold">Bill (Tk)</th>
                        <th class="text-center font-semibold">Paid (Tk)</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="text-center font-semibold">Paid At</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="searchQuery && totalMatchingRows === 0" x-cloak class="no-record-row">
                        <td colspan="13" class="text-center py-6 text-slate-500 bg-slate-50/50">
                            <i class="fas fa-spinner fa-spin text-cyan-600 mr-1.5" x-show="isSearching"></i>
                            <i class="fas fa-search text-slate-400 mr-1.5" x-show="!isSearching"></i>
                            <span class="text-xs font-medium">No matching invoices found on this page. Searching entire database...</span>
                        </td>
                    </tr>
                    @forelse($invoices as $inv)
                        @php
                            $statusBadge = $inv->status_badge;
                            $invData = [
                                'id' => $inv->id,
                                'invoice_no' => $inv->invoice_no,
                                'billing_month' => $inv->billing_month,
                                'formatted_month' => $inv->formatted_month,
                                'customer_id' => $inv->customer_id,
                                'customer_name' => $inv->customer?->name ?? 'Unknown',
                                'customer_username' => $inv->customer?->username ?? '--',
                                'customer_phone' => $inv->customer?->phone ?? '--',
                                'package_name' => $inv->mikrotik_package_name,
                                'commercial_package' => $inv->package_name ?: ($inv->package?->package_name ?? $inv->mikrotik_package_name),
                                'total_payable' => (float) $inv->total_payable,
                                'paid_amount' => (float) $inv->paid_amount,
                                'due_amount' => (float) $inv->due_amount,
                                'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : '',
                                'status' => $inv->status,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500 whitespace-nowrap">
                                {{ $invoices->firstItem() + $loop->index }}
                            </td>

                            <!-- 2. Invoice # (Clickable) -->
                            <td class="text-center font-mono text-cyan-800 font-bold whitespace-nowrap">
                                <button type="button" 
                                        @click="openViewModal({{ $inv->id }})" 
                                        class="hover:underline hover:text-cyan-600 cursor-pointer inline-flex items-center gap-1"
                                        title="Click to view & print invoice">
                                    <i class="fas fa-file-invoice text-[10px] text-cyan-600"></i>
                                    <span>{{ $inv->invoice_no }}</span>
                                </button>
                            </td>

                            <!-- 3. Cust ID -->
                            <td class="text-center font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                {{ $inv->customer?->customer_id ?: ($inv->customer?->username ?? '—') }}
                            </td>

                            <!-- 4. Customer -->
                            <td class="font-medium text-slate-900 whitespace-nowrap text-xs">
                                @if($inv->customer)
                                    <a href="{{ route('tenant.customers.show', $inv->customer->id) }}" class="hover:underline hover:text-cyan-700">
                                        {{ $inv->customer->name ?: $inv->customer->username }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-sans">Deleted Subscriber</span>
                                @endif
                            </td>

                            <!-- 5. Guardian -->
                            <td class="text-slate-700 whitespace-nowrap text-xs">
                                {{ $inv->customer?->father_name ?: '—' }}
                            </td>

                            <!-- 6. Area -->
                            <td class="text-slate-700 whitespace-nowrap text-xs">
                                {{ (is_object($inv->customer?->zone) ? ($inv->customer->zone->name ?? '') : $inv->customer?->zone) ?: ($inv->customer?->coverageZone?->name ?: ($inv->customer?->address ?: '—')) }}
                            </td>

                            <!-- 7. Partner -->
                            <td class="text-center font-mono whitespace-nowrap text-xs">
                                @if($inv->reseller)
                                    <span class="inline-flex items-center gap-1 text-cyan-800 font-semibold" title="{{ $inv->reseller->name }}">
                                        <i class="fas fa-handshake text-cyan-600 text-[10px]"></i>
                                        <span>{{ $inv->reseller->code ?: $inv->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-medium" title="HQ Direct Retail">
                                        <i class="fas fa-building text-slate-400 text-[10px]"></i>
                                        <span>Direct</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Package -->
                            <td class="font-medium text-slate-800 whitespace-nowrap text-xs">
                                {{ $inv->mikrotik_package_name ?: ($inv->package?->name ?: ($inv->package_name ?: '—')) }}
                            </td>

                            <!-- 9. Bill (Tk) -->
                            <td class="text-right font-mono font-semibold text-slate-800 whitespace-nowrap text-xs">
                                @currency($inv->total_payable)
                            </td>

                            <!-- 10. Paid (Tk) -->
                            <td class="text-right font-mono font-semibold text-emerald-600 whitespace-nowrap text-xs">
                                @currency($inv->paid_amount)
                            </td>

                            <!-- 11. Status -->
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $statusBadge['class'] }} shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 12. Paid At -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap text-xs">
                                @if($inv->paid_at)
                                    <span class="inline-flex items-center gap-1 text-slate-700">
                                        <i class="fas fa-clock text-emerald-600 text-[10px]"></i>
                                        <span>{{ \Carbon\Carbon::parse($inv->paid_at)->format('d M Y, h:i A') }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-sans">—</span>
                                @endif
                            </td>

                            <!-- 13. Action -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($invData) }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="13" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-file-invoice text-2xl text-slate-300 mb-2 block"></i>
                                No customer invoices found for the selected scope.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($invoices->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50">
                {{ $invoices->links() }}
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
            <!-- View & Print Invoice -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openViewModal(item.id)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-print text-cyan-600 w-3.5 text-center text-[11px]"></i>
                <span>View &amp; Print Bill</span>
            </button>

            <!-- Receive Payment (if due exists) -->
            <template x-if="activeMenu && activeMenu.due_amount > 0">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; openPayModal(item)" 
                        class="w-full px-3 py-1.5 hover:bg-slate-50 text-emerald-700 font-semibold flex items-center gap-2 transition text-left cursor-pointer">
                    <i class="fas fa-hand-holding-dollar text-emerald-600 w-3.5 text-center text-[11px]"></i>
                    <span>Receive Payment</span>
                </button>
            </template>
        </div>

        <div class="py-1">
            <!-- Cancel Invoice -->
            <template x-if="activeMenu && activeMenu.status !== 'cancelled' && activeMenu.paid_amount == 0">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; cancelInvoice(item.id, item.invoice_no)" 
                        class="w-full px-3 py-1.5 hover:bg-slate-50 text-amber-700 flex items-center gap-2 transition text-left cursor-pointer">
                    <i class="fas fa-ban text-amber-500 w-3.5 text-center text-[11px]"></i>
                    <span>Cancel Invoice</span>
                </button>
            </template>

            <!-- Delete Invoice (Unpaid Only) -->
            <template x-if="activeMenu && activeMenu.paid_amount == 0">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; deleteInvoice(item.id, item.invoice_no)" 
                        class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
                    <i class="fas fa-trash-can text-rose-500 w-3.5 text-center text-[11px]"></i>
                    <span>Delete Invoice</span>
                </button>
            </template>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Create Custom Invoice -->
    <div x-show="createModal.open" 
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
             @click.away="if (!createModal.loading) createModal.open = false"
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
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Create Custom Invoice</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Generate one-off or custom bill for a subscriber</p>
                    </div>
                </div>
                <button type="button" 
                        @click="createModal.open = false" 
                        :disabled="createModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitCreateInvoice()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Subscriber / Customer Select2 Searchable Combobox -->
                    <div class="space-y-1 relative" @click.away="createModal.customerDropdownOpen = false">
                        <div class="flex items-center justify-between">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Subscriber / Customer <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-mono" x-show="createModal.selectedCustomer">
                                ID: <span class="text-cyan-700 font-bold" x-text="createModal.selectedCustomer?.customer_id"></span>
                            </span>
                        </div>

                        <!-- Selected Customer Pill Display (if selected) -->
                        <template x-if="createModal.selectedCustomer">
                            <div class="flex items-center justify-between p-2 bg-cyan-50/70 border border-cyan-200 rounded-lg">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-md bg-cyan-600 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                        <i class="fas fa-user text-[9px]"></i>
                                    </div>
                                    <div class="truncate">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-slate-900 text-xs truncate" x-text="createModal.selectedCustomer.name"></span>
                                            <span class="text-[10px] font-mono text-cyan-800 font-bold" x-text="'(' + createModal.selectedCustomer.username + ')'"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-2">
                                            <span class="font-mono text-slate-600" x-text="'Pkg: ' + createModal.selectedCustomer.package_name"></span>
                                            <span>•</span>
                                            <span class="font-mono font-semibold text-cyan-700" x-text="'Bill: {{ $currencySymbol ?? '৳' }}' + parseFloat(createModal.selectedCustomer.monthly_bill).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="clearCustomer()" 
                                        class="w-5 h-5 rounded hover:bg-cyan-200/80 text-cyan-700 flex items-center justify-center transition cursor-pointer"
                                        title="Change Subscriber">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        </template>

                        <!-- Select2 Search Input Box (when no customer selected or searching) -->
                        <div x-show="!createModal.selectedCustomer" class="relative">
                            <div class="relative flex items-center">
                                <span class="absolute left-2.5 text-slate-400 text-[11px] pointer-events-none">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" 
                                       x-model="createModal.customerSearch" 
                                       @focus="createModal.customerDropdownOpen = true"
                                       @input="createModal.customerDropdownOpen = true"
                                       placeholder="Type to search subscriber by Name, ID (SO..), Username or Phone..." 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-8 py-1.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                                <button type="button" 
                                        x-show="createModal.customerSearch" 
                                        @click="createModal.customerSearch = ''; createModal.customerDropdownOpen = true"
                                        class="absolute right-2.5 text-slate-400 hover:text-slate-600 text-xs">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>

                            <!-- Search Results Dropdown Panel -->
                            <div x-show="createModal.customerDropdownOpen" 
                                 x-cloak
                                 class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-56 overflow-y-auto z-50 divide-y divide-slate-100"
                                 style="display: none;">
                                
                                <template x-for="c in filteredCustomers" :key="c.id">
                                    <div @click="selectCustomer(c)" 
                                         class="p-2 hover:bg-cyan-50/80 transition cursor-pointer flex items-center justify-between text-xs">
                                        <div class="min-w-0 pr-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900 truncate" x-text="c.name"></span>
                                                <span class="text-[10px] font-mono text-cyan-700 font-semibold" x-text="'@' + c.username"></span>
                                                <span class="text-[9px] px-1 py-0.2 bg-slate-100 text-slate-600 rounded font-mono" x-text="c.customer_id"></span>
                                            </div>
                                            <div class="text-[10.5px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span x-show="c.phone && c.phone !== '--'" class="flex items-center gap-1 text-[10px]">
                                                    <i class="fas fa-phone text-[8.5px] text-slate-400"></i>
                                                    <span x-text="c.phone"></span>
                                                </span>
                                                <span class="text-cyan-800 font-mono text-[10px] bg-cyan-50 px-1 rounded" x-text="c.package_name"></span>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="font-mono font-bold text-slate-800 text-[11px]" x-text="'{{ $currencySymbol ?? '৳' }}' + parseFloat(c.monthly_bill).toFixed(2)"></span>
                                            <span class="block text-[9px] text-slate-400 uppercase">Monthly</span>
                                        </div>
                                    </div>
                                </template>

                                <!-- No Results State -->
                                <div x-show="filteredCustomers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                    <i class="fas fa-user-slash text-slate-300 text-base mb-1 block"></i>
                                    <span>No subscribers matching "<span class="font-semibold text-slate-600" x-text="createModal.customerSearch"></span>"</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Form Value for Required Validation -->
                        <input type="hidden" name="customer_id" x-model="createModal.form.customer_id" required>
                    </div>

                    <!-- Month & Due Date Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Billing Month (YYYY-MM) <span class="text-rose-500">*</span>
                            </label>
                            <input type="month" 
                                   x-model="createModal.form.billing_month" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Payment Due Date <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" 
                                   x-model="createModal.form.due_date" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    <!-- Bill Amounts Grid -->
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Bill Fee ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="createModal.form.amount" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Discount ({{ $currencySymbol ?? '৳' }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="createModal.form.discount" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                VAT / Tax ({{ $currencySymbol ?? '৳' }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="createModal.form.vat_tax" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    <!-- Calculated Total Payable Display -->
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-slate-600">Total Net Payable:</span>
                        <span class="text-xs font-bold font-mono text-cyan-800" 
                              x-text="'{{ $currencySymbol ?? '৳' }} ' + Math.max(0, (Number(createModal.form.amount || 0) + Number(createModal.form.vat_tax || 0)) - Number(createModal.form.discount || 0)).toFixed(2)">
                        </span>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Remarks / Notes <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <textarea x-model="createModal.form.notes" 
                                  rows="2" 
                                  placeholder="e.g. Fiber shifting bill, Static IP fee, Advance payment note" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition"></textarea>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="createModal.open = false" 
                            :disabled="createModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="createModal.loading || !createModal.form.customer_id || !createModal.form.amount"
                            class="bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="createModal.loading ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                        <span x-text="createModal.loading ? 'Creating...' : 'Create Invoice'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: Receive Payment -->
    <div x-show="payModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="if (!payModal.loading) payModal.open = false"
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
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Receive Bill Payment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'Invoice: #' + (payModal.invoice?.invoice_no || '')"></p>
                    </div>
                </div>
                <button type="button" 
                        @click="payModal.open = false" 
                        :disabled="payModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitRecordPayment()">
                <div class="p-4 space-y-3.5 text-xs">
                    
                    <!-- Summary Strip -->
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 grid grid-cols-2 gap-2 text-slate-800">
                        <div>
                            <span class="text-[9.5px] uppercase font-semibold text-slate-400 block">Subscriber</span>
                            <span class="text-xs font-semibold block truncate" x-text="payModal.invoice?.customer_name"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[9.5px] uppercase font-semibold text-slate-400 block">Current Due</span>
                            <span class="text-xs font-bold font-mono text-amber-600 block" x-text="'{{ $currencySymbol ?? '৳' }} ' + Number(payModal.invoice?.due_amount || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Payment Amount & Discount Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Amount Paying ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   x-model="payModal.form.amount_paying" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono font-bold focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Waiver / Discount ({{ $currencySymbol ?? '৳' }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="payModal.form.discount" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Payment Channel / Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="payModal.form.payment_method" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                            <option value="cash">Cash in Hand</option>
                            <option value="bkash">bKash (MFS Merchant/Personal)</option>
                            <option value="nagad">Nagad (MFS)</option>
                            <option value="rocket">Rocket (DBBL MFS)</option>
                            <option value="pos">POS Terminal / Card</option>
                            <option value="bank">Bank Transfer / Cheque</option>
                            <option value="online">Online Gateway</option>
                        </select>
                    </div>

                    <!-- Transaction ID / Ref -->
                    <div class="space-y-1" x-show="payModal.form.payment_method !== 'cash'">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Transaction ID / MFS TrxID <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" 
                               x-model="payModal.form.transaction_id" 
                               placeholder="e.g. 9K28X91LKQ" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Collection Remarks <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" 
                               x-model="payModal.form.notes" 
                               placeholder="e.g. Paid in full via bKash by subscriber" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="payModal.open = false" 
                            :disabled="payModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="payModal.loading || !payModal.form.amount_paying"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="payModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="payModal.loading ? 'Recording...' : 'Confirm Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 9. Production-Grade Natural Modal: Full Printable Invoice & POS Receipt Modal -->
    <div x-show="viewModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden"
             @click.away="viewModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between no-print">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Invoice: #' + (viewModal.invoice?.invoice_no || '')"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="viewModal.invoice?.formatted_month || ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="window.print()" 
                            class="px-2.5 py-1 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white text-[11px] font-medium shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-print text-[10px]"></i>
                        <span>Print Invoice</span>
                    </button>
                    <button type="button" 
                            @click="viewModal.open = false" 
                            class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Invoice Printable Document Area -->
            <div id="printableInvoiceArea" class="p-6 text-xs max-h-[75vh] overflow-y-auto space-y-4 bg-white">
                
                <!-- Invoice Top Branding & Meta -->
                <div class="flex justify-between items-start border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight" x-text="viewModal.invoice?.company_name"></h2>
                        <p class="text-[11px] text-slate-500" x-text="viewModal.invoice?.company_address || 'Internet Service Provider'"></p>
                        <p class="text-[11px] text-slate-500" x-text="'Hotline: ' + (viewModal.invoice?.company_phone || '--')"></p>
                    </div>
                    <div class="text-right space-y-0.5">
                        <span class="inline-block px-2.5 py-0.5 rounded text-[10.5px] font-bold uppercase tracking-wider mb-1 border"
                              :class="viewModal.invoice?.status_badge?.class" 
                              x-text="viewModal.invoice?.status_badge?.label">
                        </span>
                        <p class="font-mono text-xs font-bold text-slate-900" x-text="'INV: ' + (viewModal.invoice?.invoice_no || '')"></p>
                        <p class="text-[11px] text-slate-500" x-text="'Issue Date: ' + (viewModal.invoice?.issue_date || '--')"></p>
                        <p class="text-[11px] text-slate-500" x-text="'Due Date: ' + (viewModal.invoice?.due_date || '--')"></p>
                    </div>
                </div>

                <!-- Billed To Grid -->
                <div class="grid grid-cols-2 gap-4 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="space-y-0.5">
                        <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Billed To (Subscriber)</span>
                        <p class="text-xs font-bold text-slate-900" x-text="viewModal.invoice?.customer_name"></p>
                        <p class="font-mono text-[11px] text-slate-700" x-text="'User ID: ' + (viewModal.invoice?.customer_code || '') + ' (' + (viewModal.invoice?.customer_username || '') + ')'"></p>
                        <p class="font-mono text-[11px] text-slate-600" x-text="'Mobile: ' + (viewModal.invoice?.customer_phone || '--')"></p>
                    </div>

                    <div class="space-y-0.5 text-right">
                        <span class="text-[9.5px] uppercase font-bold text-slate-400 block tracking-wider">Service Scope &amp; Area</span>
                        <p class="text-xs font-semibold text-slate-800" x-text="viewModal.invoice?.reseller_name"></p>
                        <p class="text-[11px] text-slate-600" x-text="'Zone: ' + (viewModal.invoice?.customer_zone || '--')"></p>
                        <p class="text-[11px] text-slate-500" x-text="viewModal.invoice?.customer_address || ''"></p>
                    </div>
                </div>

                <!-- Invoice Line Items Table -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700">
                            <tr>
                                <th class="p-2.5 font-semibold">Service Description</th>
                                <th class="p-2.5 font-semibold text-center">Billing Period</th>
                                <th class="p-2.5 font-semibold text-right">Amount ({{ $currencySymbol ?? '৳' }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr>
                                <td class="p-2.5">
                                    <span class="font-semibold text-slate-900 block" x-text="viewModal.invoice?.package_name"></span>
                                    <span class="text-[10.5px] text-slate-500 block">Monthly Broadband Internet Subscription Fee</span>
                                </td>
                                <td class="p-2.5 text-center font-mono" x-text="viewModal.invoice?.formatted_month"></td>
                                <td class="p-2.5 text-right font-mono font-semibold" x-text="Number(viewModal.invoice?.amount || 0).toFixed(2)"></td>
                            </tr>
                            <template x-if="Number(viewModal.invoice?.vat_tax || 0) > 0">
                                <tr>
                                    <td colspan="2" class="p-2 text-right text-slate-600">VAT / Tax:</td>
                                    <td class="p-2 text-right font-mono text-slate-700" x-text="'+ ' + Number(viewModal.invoice?.vat_tax || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                            <template x-if="Number(viewModal.invoice?.discount || 0) > 0">
                                <tr>
                                    <td colspan="2" class="p-2 text-right text-slate-600">Special Discount / Waiver:</td>
                                    <td class="p-2 text-right font-mono text-emerald-600" x-text="'- ' + Number(viewModal.invoice?.discount || 0).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50/70 font-semibold text-slate-900">
                            <tr>
                                <td colspan="2" class="p-2.5 text-right uppercase tracking-wider text-[10.5px]">Total Net Payable:</td>
                                <td class="p-2.5 text-right font-mono text-sm font-bold text-cyan-900" x-text="'{{ $currencySymbol ?? '৳' }} ' + Number(viewModal.invoice?.total_payable || 0).toFixed(2)"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="p-2 text-right text-emerald-700">Total Paid:</td>
                                <td class="p-2 text-right font-mono text-emerald-700" x-text="'{{ $currencySymbol ?? '৳' }} ' + Number(viewModal.invoice?.paid_amount || 0).toFixed(2)"></td>
                            </tr>
                            <tr class="bg-amber-50/50">
                                <td colspan="2" class="p-2.5 text-right text-amber-900 font-bold uppercase tracking-wider text-[10.5px]">Remaining Due Balance:</td>
                                <td class="p-2.5 text-right font-mono text-sm font-bold text-amber-700" x-text="'{{ $currencySymbol ?? '৳' }} ' + Number(viewModal.invoice?.due_amount || 0).toFixed(2)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Details (if paid) -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 text-[11px] text-slate-600 space-y-1" x-show="viewModal.invoice?.paid_amount > 0">
                    <p><span class="font-semibold text-slate-800">Payment Channel:</span> <span x-text="viewModal.invoice?.payment_method || 'Cash'"></span></p>
                    <p><span class="font-semibold text-slate-800">Payment Recorded:</span> <span x-text="viewModal.invoice?.paid_at || '--'"></span></p>
                </div>

                <!-- Footer Signatures -->
                <div class="pt-6 flex justify-between items-end text-slate-400 text-[10.5px]">
                    <div>
                        <div class="w-32 border-b border-slate-300 mb-1"></div>
                        <p>Customer Signature</p>
                    </div>
                    <div class="text-right">
                        <div class="w-36 border-b border-slate-300 mb-1"></div>
                        <p>Authorized Officer Signature</p>
                    </div>
                </div>

            </div>

            <!-- Modal Footer (No Print) -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between no-print">
                <button type="button" 
                        @click="viewModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Close
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="window.print()" 
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas fa-print text-[11px]"></i>
                        <span>Print Bill</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoiceManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },

        showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }},
        searchQuery: @json($search ?? ''),
        searchDebounceTimer: null,
        totalMatchingRows: null,
        isSearching: false,

        init() {
            if (this.searchQuery) {
                this.$nextTick(() => {
                    this.filterRowsOnly(this.searchQuery);
                    const input = document.getElementById('invoiceSearchInput');
                    if (input) {
                        input.focus();
                        input.setSelectionRange(input.value.length, input.value.length);
                    }
                });
            }
        },

        handleLiveSearch(val) {
            this.searchQuery = val;
            this.filterRowsOnly(val);

            // Debounced full database search via AJAX without page reload or focus loss
            clearTimeout(this.searchDebounceTimer);
            this.isSearching = true;
            this.searchDebounceTimer = setTimeout(() => {
                this.fetchData();
            }, 350);
        },

        filterRowsOnly(val) {
            const term = (val || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.saas-table tbody tr:not(.no-record-row)');
            let matchCount = 0;
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const matches = !term || text.includes(term);
                row.style.display = matches ? '' : 'none';
                if (matches) matchCount++;
            });
            this.totalMatchingRows = term ? matchCount : null;
        },

        async fetchData() {
            const form = document.getElementById('invoiceFilterForm');
            if (!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const url = `${form.action}?${params.toString()}`;

            this.isSearching = true;
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Smoothly replace Table Card without page reload
                    const newTable = doc.querySelector('#invoicesTableCard');
                    const curTable = document.querySelector('#invoicesTableCard');
                    if (newTable && curTable) {
                        curTable.innerHTML = newTable.innerHTML;
                    }

                    // Smoothly update KPI Stats Strip
                    const newKpi = doc.querySelector('#invoicesKpiStrip');
                    const curKpi = document.querySelector('#invoicesKpiStrip');
                    if (newKpi && curKpi) {
                        curKpi.innerHTML = newKpi.innerHTML;
                    }

                    this.filterRowsOnly(this.searchQuery);
                    window.history.replaceState({}, '', url);
                }
            } catch (err) {
                console.error('Invoice live search error:', err);
            } finally {
                this.isSearching = false;
            }
        },

        clearLiveSearch() {
            this.searchQuery = '';
            clearTimeout(this.searchDebounceTimer);
            const input = document.getElementById('invoiceSearchInput');
            if (input) input.value = '';
            this.filterRowsOnly('');
            this.fetchData();
        },

        submitSearchNow() {
            clearTimeout(this.searchDebounceTimer);
            this.fetchData();
        },

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(message, type = 'success') {
            const iconType = (type === 'error' || type === 'danger') ? 'error' : (type === 'warning' ? 'warning' : 'success');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: iconType,
                    title: message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            } else {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                if (this.toast.timeout) clearTimeout(this.toast.timeout);
                this.toast.timeout = setTimeout(() => {
                    this.toast.show = false;
                }, 3500);
            }
        },

        customersList: @json($allCustomers ?? []),

        createModal: {
            open: false,
            loading: false,
            customerSearch: '',
            customerDropdownOpen: false,
            selectedCustomer: null,
            form: {
                customer_id: '',
                billing_month: '{{ Carbon\Carbon::now()->format('Y-m') }}',
                due_date: '{{ Carbon\Carbon::now()->endOfMonth()->toDateString() }}',
                amount: '',
                discount: 0,
                vat_tax: 0,
                notes: ''
            }
        },

        payModal: {
            open: false,
            loading: false,
            invoice: null,
            form: {
                amount_paying: '',
                discount: 0,
                payment_method: 'cash',
                transaction_id: '',
                notes: ''
            }
        },

        viewModal: {
            open: false,
            loading: false,
            invoice: null
        },

        get filteredCustomers() {
            if (!this.createModal.customerSearch || !this.createModal.customerSearch.trim()) {
                return (this.customersList || []).slice(0, 50);
            }
            const q = this.createModal.customerSearch.toLowerCase().trim();
            return (this.customersList || []).filter(c => {
                const name = (c.name || '').toLowerCase();
                const username = (c.username || '').toLowerCase();
                const custId = (c.customer_id || '').toLowerCase();
                const phone = (c.phone || '');
                const pkg = (c.package_name || '').toLowerCase();
                return name.includes(q) || username.includes(q) || custId.includes(q) || phone.includes(q) || pkg.includes(q);
            }).slice(0, 50);
        },

        selectCustomer(c) {
            this.createModal.selectedCustomer = c;
            this.createModal.form.customer_id = c.id;
            this.createModal.customerSearch = '';
            this.createModal.customerDropdownOpen = false;
            if (c.monthly_bill && (!this.createModal.form.amount || this.createModal.form.amount === '0' || this.createModal.form.amount === '0.00')) {
                this.createModal.form.amount = parseFloat(c.monthly_bill).toFixed(2);
            }
        },

        clearCustomer() {
            this.createModal.selectedCustomer = null;
            this.createModal.form.customer_id = '';
            this.createModal.customerSearch = '';
            this.createModal.customerDropdownOpen = false;
        },

        openCreateModal() {
            this.createModal.selectedCustomer = null;
            this.createModal.customerSearch = '';
            this.createModal.customerDropdownOpen = false;
            this.createModal.form = {
                customer_id: '',
                billing_month: '{{ Carbon\Carbon::now()->format('Y-m') }}',
                due_date: '{{ Carbon\Carbon::now()->endOfMonth()->toDateString() }}',
                amount: '',
                discount: 0,
                vat_tax: 0,
                notes: ''
            };
            this.createModal.open = true;
        },

        async submitCreateInvoice() {
            if (!this.createModal.form.customer_id) {
                this.showToast('Please search and select a subscriber first.', 'error');
                return;
            }
            this.createModal.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.finance.invoices.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.createModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.createModal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Failed to create invoice.', 'error');
                }
            } catch (err) {
                this.showToast('Network error creating invoice.', 'error');
            } finally {
                this.createModal.loading = false;
            }
        },

        openPayModal(inv) {
            this.payModal.invoice = inv;
            this.payModal.form = {
                amount_paying: inv.due_amount,
                discount: 0,
                payment_method: 'cash',
                transaction_id: '',
                notes: ''
            };
            this.payModal.open = true;
        },

        async submitRecordPayment() {
            if (!this.payModal.invoice) return;
            this.payModal.loading = true;
            try {
                const res = await fetch(`{{ url('admin/finance/invoices') }}/${this.payModal.invoice.id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.payModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.payModal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Payment collection failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error recording payment.', 'error');
            } finally {
                this.payModal.loading = false;
            }
        },

        async openViewModal(id) {
            this.viewModal.loading = true;
            try {
                const res = await fetch(`{{ url('admin/finance/invoices') }}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.viewModal.invoice = data.invoice;
                    this.viewModal.open = true;
                } else {
                    this.showToast('Failed to load invoice details.', 'error');
                }
            } catch (err) {
                this.showToast('Network error loading invoice.', 'error');
            } finally {
                this.viewModal.loading = false;
            }
        },

        async cancelInvoice(id, invNo) {
            const result = await Swal.fire({
                title: 'Cancel Invoice?',
                text: `Are you sure you want to cancel invoice #${invNo}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Cancel Invoice',
                cancelButtonText: 'No, Keep',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-2xl text-xs font-sans shadow-2xl border border-slate-200',
                    title: 'text-sm font-bold text-slate-800',
                    confirmButton: 'px-4 py-2 text-xs font-medium rounded-lg shadow-xs cursor-pointer',
                    cancelButton: 'px-4 py-2 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer'
                }
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`{{ url('admin/finance/invoices') }}/${id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Invoice Cancelled',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Failed to cancel invoice.', 'error');
                }
            } catch (err) {
                this.showToast('Network error cancelling invoice.', 'error');
            }
        },

        async deleteInvoice(id, invNo) {
            const result = await Swal.fire({
                title: 'Delete Invoice?',
                text: `Are you sure you want to permanently delete invoice #${invNo}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete Permanently',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-2xl text-xs font-sans shadow-2xl border border-slate-200',
                    title: 'text-sm font-bold text-slate-800',
                    confirmButton: 'px-4 py-2 text-xs font-medium rounded-lg shadow-xs cursor-pointer',
                    cancelButton: 'px-4 py-2 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer'
                }
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`{{ url('admin/finance/invoices') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Invoice Deleted',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Failed to delete invoice.', 'error');
                }
            } catch (err) {
                this.showToast('Network error deleting invoice.', 'error');
            }
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
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
        }
    };
}
</script>
@endpush
