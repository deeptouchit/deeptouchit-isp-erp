@extends('tenant.layouts.app')

@section('title', 'Payment Collections & Receipts - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Specialized POS & Thermal 80mm Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printableReceiptArea, #printableReceiptArea * {
            visibility: visible;
        }
        #printableReceiptArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $authUser = auth()->user();
    $isCollector = $isCollector ?? ($authUser && $authUser->isCollector());
    $isResellerUser = $isResellerUser ?? ($authUser && ($authUser->isResellerUser() || !empty($authUser->reseller_id)));
@endphp
<div class="space-y-3" x-data="paymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">
                Collection Report &amp; Money Receipts
            </h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Export CSV -->
            <a href="{{ route('tenant.finance.payments.export', request()->query()) }}" 
               class="h-[34px] border border-slate-200/90 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3 rounded-lg transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                <span class="hidden sm:inline">Export</span> CSV
            </a>

            <!-- Collect Payment Button -->
            <button type="button" 
                    @click="openCollectModal()" 
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>+ Collect Payment</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Collections -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($totalCollections)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        <!-- Card 2: Today's Collection -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Today's Total</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($todayCollections)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        <!-- Card 3: This Month Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">This Month</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    @currency($thisMonthCollections)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <!-- Card 4: Total Receipts Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Money Receipts</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ number_format($totalReceiptsCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>

        <!-- Card 5: Cash Collection -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Cash in Hand</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    @currency($cashCollections)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Card 6: Digital & Online Collection -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Digital &amp; Online</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($digitalCollections)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-mobile-screen-button"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Architecture & Uniform h-[34px] - AGENTS.md Rule 2.C) -->
    @php
        $activeMoreCount = collect([
            (!empty($dateFrom) && $period === 'custom') ? $dateFrom : null,
            (!empty($dateTo) && $period === 'custom') ? $dateTo : null,
            ($methodFilter !== 'all' && !empty($methodFilter)) ? $methodFilter : null,
            (!$isResellerUser && !$isCollector && $selectedResellerId !== 'all' && !empty($selectedResellerId)) ? $selectedResellerId : null,
            ($statusFilter !== 'all' && !empty($statusFilter)) ? $statusFilter : null,
            ($selectedMonth !== 'all' && !empty($selectedMonth)) ? $selectedMonth : null,
        ])->filter()->count();
        $isMoreOpen = $activeMoreCount > 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
             searchQuery: '{{ addslashes($search ?? '') }}'
         }">
        <form method="GET" action="{{ route('tenant.finance.payments') }}" id="paymentsFilterForm" class="space-y-2.5">
            
            {{-- Tier 1: Primary Filter Bar (Strictly 1 Single Row on md+ screens - AGENTS.md Rule 2.C) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search Receipt No, Name, Username, Phone, Trx ID..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400 font-medium"
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

                {{-- Primary Dropdown 1: Collector / Staff Filter (Crucial for কালেক্টর-ভিত্তিক আদায়) --}}
                @if(!$isCollector)
                <div class="w-full md:w-48 flex-shrink-0">
                    <select name="collector_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-semibold text-slate-800 cursor-pointer">
                        <option value="all">👤 All Collectors / Staff</option>
                        @foreach($allCollectors ?? [] as $col)
                            <option value="{{ $col->id }}" {{ (string)$collectorFilter === (string)$col->id ? 'selected' : '' }}>
                                {{ $col->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Primary Dropdown 2: Period Preset --}}
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="period" 
                            x-model="filterPeriod"
                            @change="if (filterPeriod === 'custom') { showMoreFilters = true; } else { handlePeriodChange(filterPeriod); $nextTick(() => $el.closest('form').submit()); }"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-semibold text-slate-800 cursor-pointer">
                        <option value="today">📅 Today</option>
                        <option value="yesterday">⏳ Yesterday</option>
                        <option value="this_week">📆 This Week</option>
                        <option value="this_month">🗓️ This Month</option>
                        <option value="last_month">📊 Last Month</option>
                        <option value="custom">📅 Custom Date...</option>
                        <option value="all">🌐 All Time</option>
                    </select>
                </div>

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="Toggle Date Range, Method, Partner & Status Filters">
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

                {{-- Strict Filter & Reset Button Sequence (Rule 2.C - Far Right on md+) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.payments') }}" 
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
                
                {{-- Date From (Custom Range) --}}
                <div class="w-full sm:w-40 flex-shrink-0 relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9px] uppercase font-bold text-slate-400 pointer-events-none">From</span>
                    <input type="date" 
                           name="date_from" 
                           x-model="filterDateFrom" 
                           @input="filterPeriod = 'custom'"
                           class="w-full h-[34px] pl-11 pr-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Date To (Custom Range) --}}
                <div class="w-full sm:w-40 flex-shrink-0 relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9px] uppercase font-bold text-slate-400 pointer-events-none">To</span>
                    <input type="date" 
                           name="date_to" 
                           x-model="filterDateTo" 
                           @input="filterPeriod = 'custom'"
                           class="w-full h-[34px] pl-8 pr-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Payment Method --}}
                <div class="w-full sm:w-40 flex-shrink-0">
                    <select name="method" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">All Payment Methods</option>
                        <option value="cash" {{ $methodFilter === 'cash' ? 'selected' : '' }}>💵 Cash in Hand</option>
                        <option value="bkash" {{ $methodFilter === 'bkash' ? 'selected' : '' }}>📱 bKash</option>
                        <option value="nagad" {{ $methodFilter === 'nagad' ? 'selected' : '' }}>📱 Nagad</option>
                        <option value="rocket" {{ $methodFilter === 'rocket' ? 'selected' : '' }}>📱 Rocket</option>
                        <option value="bangla_qr" {{ $methodFilter === 'bangla_qr' ? 'selected' : '' }}>🔲 Bangla QR</option>
                        <option value="bank_transfer" {{ $methodFilter === 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                        <option value="pos" {{ $methodFilter === 'pos' ? 'selected' : '' }}>💳 Card / POS</option>
                        <option value="online" {{ $methodFilter === 'online' ? 'selected' : '' }}>🌐 Online Gateway</option>
                    </select>
                </div>

                {{-- Billing Month --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="month" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">All Billing Months</option>
                        @foreach($availableMonths as $m)
                            <option value="{{ $m }}" {{ (string)$selectedMonth === (string)$m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($m . '-01')->format('M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="w-full sm:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="paid" {{ ($statusFilter ?? '') === 'paid' ? 'selected' : '' }}>Paid / Settled</option>
                        <option value="pending" {{ ($statusFilter ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="void" {{ ($statusFilter ?? '') === 'void' ? 'selected' : '' }}>Voided</option>
                    </select>
                </div>

                {{-- Reseller Scope Filter (if HQ Admin) --}}
                @if(!$isResellerUser && !$isCollector)
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="reseller_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all" {{ $selectedResellerId === 'all' ? 'selected' : '' }}>All Network Scope</option>
                        <option value="isp" {{ $selectedResellerId === 'isp' ? 'selected' : '' }}>Direct HQ</option>
                        @foreach($allResellers ?? [] as $r)
                            <option value="{{ $r->id }}" {{ (string)$selectedResellerId === (string)$r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->code ?: 'Partner' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

        </form>
    </div>

    <!-- 4. Master Compact Table (.saas-table Pure CSS System - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-8 text-center no-sort">#</th>
                        <th class="w-36">Receipt / MR No</th>
                        <th>Subscriber</th>
                        <th class="w-36">Collected By</th>
                        <th class="w-24 text-center">Billing Month</th>
                        <th class="w-32">Payment Method</th>
                        <th class="text-right w-28">Collected Amount</th>
                        <th class="text-center w-24">Status</th>
                        <th class="no-sort text-center w-8">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $p)
                        @php
                            $badge = $p->status_badge;
                            $paymentJson = [
                                'id' => $p->id,
                                'invoice_no' => $p->invoice_no,
                                'customer_id' => $p->customer_id,
                                'customer_name' => $p->customer?->name ?? 'Unknown',
                                'customer_username' => $p->customer?->username ?? '--',
                                'customer_phone' => $p->customer?->phone ?? '--',
                                'amount' => (float) $p->amount,
                                'discount' => (float) $p->discount,
                                'billing_month' => $p->billing_month,
                                'payment_method' => $p->payment_method,
                                'payment_method_name' => $p->payment_method_name,
                                'status' => $p->status,
                                'sms_sent' => (bool) $p->sms_sent,
                            ];
                        @endphp
                        <tr>
                            <!-- 0. Row Index -->
                            <td class="text-center font-mono text-slate-500 whitespace-nowrap">{{ $payments->firstItem() + $index }}</td>

                            <!-- 1. Receipt / MR No (Click to open Print / Detail Modal) -->
                            <td class="font-mono text-cyan-800 font-bold whitespace-nowrap">
                                <button type="button" 
                                        @click="openReceiptModal({{ $p->id }})" 
                                        class="hover:underline hover:text-cyan-600 cursor-pointer flex items-center gap-1.5"
                                        title="Click to view & print Money Receipt">
                                    <i class="fas fa-file-invoice text-[10px] text-cyan-600"></i>
                                    <span>{{ $p->invoice_no }}</span>
                                </button>
                            </td>

                            <!-- 2. Subscriber Name (Strict Single Data per Cell) -->
                            <td class="font-medium text-slate-900 whitespace-nowrap">
                                @if($p->customer)
                                    <a href="{{ route('tenant.customers.show', $p->customer->id) }}" class="hover:underline hover:text-cyan-600">
                                        {{ $p->customer->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Deleted Subscriber</span>
                                @endif
                            </td>

                            <!-- 3. Collected By (Crucial for কালেক্টর-ভিত্তিক আদায় রিপোর্ট) -->
                            <td class="whitespace-nowrap text-slate-700">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user-check text-[10px] text-slate-400"></i>
                                    <span class="font-medium text-xs">{{ $p->collector?->name ?? 'Direct / System' }}</span>
                                </div>
                            </td>

                            <!-- 4. Billing Month -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $p->formatted_month }}
                            </td>

                            <!-- 5. Payment Method -->
                            <td class="whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 text-slate-700 text-xs">
                                    <i class="{{ $p->method_icon }} text-[11px] text-slate-500"></i>
                                    <span>{{ $p->payment_method_name }}</span>
                                </span>
                            </td>

                            <!-- 6. Collected Amount -->
                            <td class="text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                @currency($p->amount)
                            </td>

                            <!-- 7. Status -->
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $badge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 8. 3-Dot Action Button (Strictly Single Button in Row) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu(@js($paymentJson), $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-base">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <p class="text-xs font-medium text-slate-600">No payment collections found matching your criteria.</p>
                                    <button type="button" 
                                            @click="openCollectModal()" 
                                            class="text-xs text-cyan-600 font-semibold hover:underline cursor-pointer">
                                        + Collect New Payment
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($payments->hasPages())
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200/90 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing <span class="font-semibold">{{ $payments->firstItem() ?? 0 }}</span> to <span class="font-semibold">{{ $payments->lastItem() ?? 0 }}</span> of <span class="font-semibold">{{ $payments->total() }}</span> payments
                </div>
                <div>
                    {{ $payments->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white border border-slate-200 rounded-xl shadow-xl w-52 py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         style="display: none;">
        
        <!-- Group 0: Approval & Rejection for Pending Payments (Bangla QR etc.) -->
        <template x-if="activeMenu?.status === 'pending'">
            <div class="py-1 bg-amber-50/50">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; approvePayment(item)" 
                        class="w-full text-left px-3 py-1.5 hover:bg-emerald-100 text-emerald-700 flex items-center gap-2 transition cursor-pointer font-bold">
                    <i class="fas fa-check-circle w-4 text-center text-emerald-600 text-[11px]"></i>
                    <span>Approve &amp; Activate</span>
                </button>
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; rejectPayment(item)" 
                        class="w-full text-left px-3 py-1.5 hover:bg-rose-100 text-rose-700 flex items-center gap-2 transition cursor-pointer font-semibold">
                    <i class="fas fa-times-circle w-4 text-center text-rose-600 text-[11px]"></i>
                    <span>Reject Payment</span>
                </button>
            </div>
        </template>

        <!-- Group 1: Money Receipt & Print -->
        <div class="py-1">
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openReceiptModal(item.id)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-amber-50/80 hover:text-amber-800 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-print w-4 text-center text-amber-600 text-[11px]"></i>
                <span class="font-medium">View &amp; Print Receipt</span>
            </button>

            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openSmsModal(item)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-cyan-50/80 hover:text-cyan-800 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-comment-sms w-4 text-center text-cyan-600 text-[11px]"></i>
                <span class="font-medium">Send / Resend SMS</span>
            </button>
        </div>

        <!-- Group 2: Subscriber & Reversal -->
        <div class="py-1">
            <template x-if="activeMenu?.customer_id">
                <a :href="`{{ url('/admin/customers') }}/${activeMenu.customer_id}`" 
                   class="w-full text-left px-3 py-1.5 hover:bg-slate-100 text-slate-700 flex items-center gap-2 transition">
                    <i class="fas fa-user-circle w-4 text-center text-slate-500 text-[11px]"></i>
                    <span>Subscriber Profile</span>
                </a>
            </template>

            <template x-if="activeMenu?.status !== 'void'">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; openVoidModal(item)" 
                        class="w-full text-left px-3 py-1.5 hover:bg-rose-50/80 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-ban w-4 text-center text-rose-500 text-[11px]"></i>
                    <span class="font-semibold">Void / Refund Payment</span>
                </button>
            </template>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Instant Collect Payment -->
    <div x-show="collectModal.open" 
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
             @click.away="if (!collectModal.loading) collectModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Collect Customer Payment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record bill collection &amp; generate money receipt</p>
                    </div>
                </div>
                <button type="button" 
                        @click="collectModal.open = false" 
                        :disabled="collectModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitCollectPayment()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Subscriber Select2 Searchable Combobox -->
                    <div class="space-y-1 relative" @click.away="collectModal.customerDropdownOpen = false">
                        <div class="flex items-center justify-between">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Subscriber / Customer <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-mono" x-show="collectModal.selectedCustomer">
                                ID: <span class="text-amber-700 font-bold" x-text="collectModal.selectedCustomer?.customer_id"></span>
                            </span>
                        </div>

                        <!-- Selected Customer Pill -->
                        <template x-if="collectModal.selectedCustomer">
                            <div class="flex items-center justify-between p-2 bg-amber-50/70 border border-amber-200 rounded-lg">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-md bg-amber-600 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                        <i class="fas fa-user text-[9px]"></i>
                                    </div>
                                    <div class="truncate">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-slate-900 text-xs truncate" x-text="collectModal.selectedCustomer.name"></span>
                                            <span class="text-[10px] font-mono text-amber-800 font-bold" x-text="'(' + collectModal.selectedCustomer.username + ')'"></span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-2">
                                            <span class="font-mono text-slate-600" x-text="'Pkg: ' + collectModal.selectedCustomer.package_name"></span>
                                            <span>•</span>
                                            <span class="font-mono font-semibold text-rose-700" x-text="'Due: {{ $currencySymbol ?? '৳' }}' + parseFloat(collectModal.selectedCustomer.due_amount).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="clearCustomer()" 
                                        class="w-5 h-5 rounded hover:bg-amber-200/80 text-amber-800 flex items-center justify-center transition cursor-pointer"
                                        title="Change Subscriber">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        </template>

                        <!-- Search Input Box -->
                        <div x-show="!collectModal.selectedCustomer" class="relative">
                            <div class="relative flex items-center">
                                <span class="absolute left-2.5 text-slate-400 text-[11px] pointer-events-none">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" 
                                       x-model="collectModal.customerSearch" 
                                       @focus="collectModal.customerDropdownOpen = true"
                                       @input="collectModal.customerDropdownOpen = true"
                                       placeholder="Type to search subscriber by Name, ID, Username or Phone..." 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-8 py-1.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition">
                                <button type="button" 
                                        x-show="collectModal.customerSearch" 
                                        @click="collectModal.customerSearch = ''; collectModal.customerDropdownOpen = true"
                                        class="absolute right-2.5 text-slate-400 hover:text-slate-600 text-xs">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div x-show="collectModal.customerDropdownOpen" 
                                 x-cloak
                                 class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-56 overflow-y-auto z-50 divide-y divide-slate-100"
                                 style="display: none;">
                                
                                <template x-for="c in filteredCustomers" :key="c.id">
                                    <div @click="selectCustomer(c)" 
                                         class="p-2 hover:bg-amber-50/80 transition cursor-pointer flex items-center justify-between text-xs">
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
                                                <span class="text-amber-800 font-mono text-[10px] bg-amber-50 px-1 rounded" x-text="c.package_name"></span>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="font-mono font-bold text-slate-800 text-[11px]" x-text="'{{ $currencySymbol ?? '৳' }}' + parseFloat(c.monthly_bill).toFixed(2)"></span>
                                            <span class="block text-[9px] text-rose-600 font-mono" x-show="c.due_amount > 0" x-text="'Due: {{ $currencySymbol ?? '৳' }}' + parseFloat(c.due_amount).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </template>

                                <!-- No Results State -->
                                <div x-show="filteredCustomers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                    <i class="fas fa-user-slash text-slate-300 text-base mb-1 block"></i>
                                    <span>No subscribers matching "<span class="font-semibold text-slate-600" x-text="collectModal.customerSearch"></span>"</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden Required Input -->
                        <input type="hidden" name="customer_id" x-model="collectModal.form.customer_id" required>
                    </div>

                    <!-- Month & Method Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Billing Month (YYYY-MM) <span class="text-rose-500">*</span>
                            </label>
                            <input type="month" 
                                   x-model="collectModal.form.billing_month" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Payment Method <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="collectModal.form.payment_method" 
                                    required 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                                <option value="cash">Cash in Hand</option>
                                <option value="bkash">bKash (MFS)</option>
                                <option value="nagad">Nagad (MFS)</option>
                                <option value="rocket">Rocket (MFS)</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="pos">Card / POS Terminal</option>
                                <option value="online">Online Payment Gateway</option>
                                <option value="other">Other Method</option>
                            </select>
                        </div>
                    </div>

                    <!-- Amounts Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Collected Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   x-model="collectModal.form.amount" 
                                   required 
                                   placeholder="0.00" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono font-bold focus:bg-white focus:border-amber-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Discount / Concession ({{ $currencySymbol ?? '৳' }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="collectModal.form.discount" 
                                   placeholder="0.00" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-amber-500 transition">
                        </div>
                    </div>

                    <!-- Transaction ID & Paid Date Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Transaction Reference / ID
                            </label>
                            <input type="text" 
                                   x-model="collectModal.form.transaction_id" 
                                   placeholder="Auto-generated if blank" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-amber-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Collection Date &amp; Time
                            </label>
                            <input type="datetime-local" 
                                   x-model="collectModal.form.paid_at" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-amber-500 transition">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Remarks / Notes
                        </label>
                        <input type="text" 
                               x-model="collectModal.form.notes" 
                               placeholder="e.g. Monthly bill clearance, Advance payment, Received by Collector..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                    </div>

                    <!-- SMS Confirmation Checkbox -->
                    <div class="p-2.5 bg-cyan-50/70 border border-cyan-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" 
                                   id="collect_send_sms" 
                                   x-model="collectModal.form.send_sms" 
                                   class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-4 h-4 cursor-pointer">
                            <label for="collect_send_sms" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Send SMS Confirmation to Subscriber
                            </label>
                        </div>
                        <i class="fas fa-comment-sms text-cyan-600 text-sm"></i>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="collectModal.open = false" 
                            :disabled="collectModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="collectModal.loading"
                            class="bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-semibold text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="collectModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="collectModal.loading ? 'Recording Payment...' : 'Confirm &amp; Issue Receipt'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: View / Print Money Receipt (Dual POS Thermal & A4) -->
    <div x-show="receiptModal.open" 
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
             @click.away="receiptModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between no-print">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Money Receipt Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Official collection confirmation receipt</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" 
                            @click="printReceipt()" 
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-xs cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>Print</span>
                    </button>

                    <button type="button" 
                            @click="receiptModal.open = false" 
                            class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Receipt Content (Printable Area) -->
            <div id="printableReceiptArea" class="p-5 text-xs text-slate-800 font-sans max-h-[75vh] overflow-y-auto">
                <template x-if="receiptModal.payment">
                    <div class="space-y-4">
                        
                        <!-- Header / Company Branded -->
                        <div class="text-center border-b border-dashed border-slate-300 pb-3">
                            <h2 class="text-base font-bold text-slate-900 tracking-tight" x-text="receiptModal.payment.company.name"></h2>
                            <p class="text-[10.5px] text-slate-500 mt-0.5" x-text="receiptModal.payment.company.address"></p>
                            <p class="text-[10.5px] text-slate-600 font-mono mt-0.5 flex items-center justify-center gap-2">
                                <span x-text="'Helpline: ' + receiptModal.payment.company.phone"></span>
                            </p>
                            <div class="mt-2 inline-block px-2.5 py-0.5 bg-slate-900 text-amber-400 font-mono font-bold text-[11px] rounded tracking-wider uppercase">
                                Money Receipt
                            </div>
                        </div>

                        <!-- Meta Info Grid -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] font-mono border-b border-dashed border-slate-200 pb-2.5">
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase">Receipt No</span>
                                <span class="font-bold text-slate-900" x-text="receiptModal.payment.receipt_no"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block text-[9px] uppercase">Date &amp; Time</span>
                                <span class="text-slate-800" x-text="receiptModal.payment.paid_at"></span>
                            </div>
                        </div>

                        <!-- Subscriber Info Card -->
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-800" x-text="receiptModal.payment.customer.name"></span>
                                <span class="font-mono text-cyan-800 font-bold" x-text="receiptModal.payment.customer.customer_code"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[10.5px] font-mono">
                                <span x-text="'PPPoE: ' + receiptModal.payment.customer.username"></span>
                                <span x-text="'Phone: ' + receiptModal.payment.customer.phone"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 text-[10.5px]">
                                <span x-text="'Package: ' + receiptModal.payment.customer.package_name"></span>
                                <span x-text="'Month: ' + receiptModal.payment.billing_month"></span>
                            </div>
                        </div>

                        <!-- Financial Breakdown -->
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600">Bill Fee Paid:</span>
                                <span class="font-mono font-bold text-slate-900" x-text="receiptModal.payment.amount_formatted"></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100" x-show="receiptModal.payment.discount > 0">
                                <span class="text-slate-600">Discount Concession:</span>
                                <span class="font-mono text-rose-600 font-semibold" x-text="receiptModal.payment.discount_formatted"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b-2 border-slate-800 font-bold">
                                <span class="text-slate-900">Total Received:</span>
                                <span class="font-mono text-emerald-700 text-sm" x-text="receiptModal.payment.total_received_formatted"></span>
                            </div>
                            <div class="flex justify-between py-1 text-slate-500 text-[11px] font-mono">
                                <span>Payment Method:</span>
                                <span class="font-semibold text-slate-800" x-text="receiptModal.payment.payment_method_name"></span>
                            </div>
                            <div class="flex justify-between py-1 text-slate-500 text-[11px] font-mono" x-show="receiptModal.payment.transaction_id && receiptModal.payment.transaction_id !== 'N/A'">
                                <span>Trx ID:</span>
                                <span class="text-slate-700" x-text="receiptModal.payment.transaction_id"></span>
                            </div>
                            <div class="flex justify-between py-1 text-slate-500 text-[11px]">
                                <span>Remaining Due:</span>
                                <span class="font-mono font-bold" :class="receiptModal.payment.customer.current_due > 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="receiptModal.payment.customer.current_due_formatted"></span>
                            </div>
                        </div>

                        <!-- Footer Signatures & Note -->
                        <div class="pt-4 border-t border-dashed border-slate-300 space-y-4 text-center">
                            <div class="flex justify-between items-end text-[10px] text-slate-500 pt-3">
                                <div>
                                    <div class="w-20 border-b border-slate-300 mb-1"></div>
                                    <span>Subscriber Sign</span>
                                </div>
                                <div>
                                    <div class="w-20 border-b border-slate-300 mb-1"></div>
                                    <span x-text="receiptModal.payment.collector_name"></span>
                                </div>
                            </div>

                            <p class="text-[9.5px] text-slate-400 font-mono">
                                Thank you for your payment! Please preserve this receipt for future reference.
                            </p>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 no-print">
                <button type="button" 
                        @click="receiptModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Close
                </button>
                <button type="button" 
                        @click="printReceipt()" 
                        class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: Send / Resend SMS Confirmation -->
    <div x-show="smsModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden"
             @click.away="if (!smsModal.loading) smsModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-comment-sms"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Dispatch SMS Confirmation</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Send instant SMS receipt to customer</p>
                    </div>
                </div>
                <button type="button" 
                        @click="smsModal.open = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Recipient Phone:</span>
                        <span class="font-mono font-bold text-slate-800" x-text="smsModal.payment?.customer_phone"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Receipt No:</span>
                        <span class="font-mono text-amber-800 font-bold" x-text="smsModal.payment?.invoice_no"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Amount Paid:</span>
                        <span class="font-mono text-emerald-700 font-bold" x-text="'{{ $currencySymbol ?? '৳' }}' + parseFloat(smsModal.payment?.amount || 0).toFixed(2)"></span>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-700 font-semibold text-[11px]">SMS Message Template</label>
                    <div class="bg-slate-900 text-cyan-300 font-mono text-[10.5px] p-2.5 rounded-lg border border-slate-800 leading-relaxed">
                        Dear <span x-text="smsModal.payment?.customer_name"></span>, payment of {{ $currencySymbol ?? '৳' }}<span x-text="parseFloat(smsModal.payment?.amount || 0).toFixed(2)"></span> received successfully (Receipt: <span x-text="smsModal.payment?.invoice_no"></span>). Thank you for being with us!
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="smsModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitSendSms()" 
                        :disabled="smsModal.loading" 
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas" :class="smsModal.loading ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="smsModal.loading ? 'Sending SMS...' : 'Dispatch SMS'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 9. Production-Grade Natural Modal: Void / Cancel Payment -->
    <div x-show="voidModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden"
             @click.away="if (!voidModal.loading) voidModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Void / Reverse Payment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Revert collection &amp; restore subscriber due</p>
                    </div>
                </div>
                <button type="button" 
                        @click="voidModal.open = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitVoidPayment()">
                <div class="p-4 space-y-3 text-xs">
                    <div class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-xs">
                            <i class="fas fa-triangle-exclamation text-rose-600"></i>
                            <span>Warning: Action cannot be undone</span>
                        </div>
                        <p class="text-[11px] leading-tight">
                            Voiding this receipt will deduct <span class="font-bold font-mono">{{ $currencySymbol ?? '৳' }}<span x-text="parseFloat(voidModal.payment?.amount || 0).toFixed(2)"></span></span> from collections and re-add the due amount back to the subscriber's balance.
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">Reason for Voiding <span class="text-rose-500">*</span></label>
                        <textarea x-model="voidModal.reason" 
                                  required 
                                  rows="2" 
                                  placeholder="e.g. Mistaken entry, Cheque bounced, Wrong subscriber selected..." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2 text-slate-800 focus:bg-white focus:border-rose-500 transition"></textarea>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="voidModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="voidModal.loading" 
                            class="bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="voidModal.loading ? 'fa-spinner fa-spin' : 'fa-ban'"></i>
                        <span x-text="voidModal.loading ? 'Voiding...' : 'Confirm Void'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 10. Global Toast Alert -->
    <div x-show="toast.show" 
         x-cloak 
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-2xl border text-xs font-medium transition-all"
         :class="toast.type === 'success' ? 'bg-emerald-900 text-emerald-100 border-emerald-700' : 'bg-rose-900 text-rose-100 border-rose-700'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle text-rose-400'"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function paymentManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

        filterPeriod: '{{ $period ?? 'this_month' }}',
        filterDateFrom: '{{ $dateFrom ?? '' }}',
        filterDateTo: '{{ $dateTo ?? '' }}',

        handlePeriodChange(val) {
            const now = new Date();
            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (val === 'today') {
                const todayStr = formatDate(now);
                this.filterDateFrom = todayStr;
                this.filterDateTo = todayStr;
            } else if (val === 'yesterday') {
                const y = new Date(now);
                y.setDate(y.getDate() - 1);
                const yStr = formatDate(y);
                this.filterDateFrom = yStr;
                this.filterDateTo = yStr;
            } else if (val === 'this_week') {
                const currentDay = now.getDay();
                const diffToMonday = (currentDay === 0 ? -6 : 1) - currentDay;
                const monday = new Date(now);
                monday.setDate(now.getDate() + diffToMonday);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                this.filterDateFrom = formatDate(monday);
                this.filterDateTo = formatDate(sunday);
            } else if (val === 'this_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.filterDateFrom = formatDate(firstDay);
                this.filterDateTo = formatDate(lastDay);
            } else if (val === 'last_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
                this.filterDateFrom = formatDate(firstDay);
                this.filterDateTo = formatDate(lastDay);
            } else if (val === 'all') {
                this.filterDateFrom = '';
                this.filterDateTo = '';
            }
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

        collectModal: {
            open: false,
            loading: false,
            customerSearch: '',
            customerDropdownOpen: false,
            selectedCustomer: null,
            form: {
                customer_id: '',
                billing_month: '{{ Carbon\Carbon::now()->format('Y-m') }}',
                payment_method: 'cash',
                amount: '',
                discount: 0,
                transaction_id: '',
                paid_at: '{{ Carbon\Carbon::now()->format('Y-m-d\TH:i') }}',
                notes: '',
                send_sms: true,
            }
        },

        receiptModal: {
            open: false,
            loading: false,
            payment: null
        },

        smsModal: {
            open: false,
            loading: false,
            payment: null
        },

        voidModal: {
            open: false,
            loading: false,
            payment: null,
            reason: ''
        },

        get filteredCustomers() {
            if (!this.collectModal.customerSearch || !this.collectModal.customerSearch.trim()) {
                return (this.customersList || []).slice(0, 50);
            }
            const q = this.collectModal.customerSearch.toLowerCase().trim();
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
            this.collectModal.selectedCustomer = c;
            this.collectModal.form.customer_id = c.id;
            this.collectModal.customerSearch = '';
            this.collectModal.customerDropdownOpen = false;
            
            // Auto populate amount with due or monthly bill
            const targetAmount = c.due_amount > 0 ? c.due_amount : c.monthly_bill;
            if (targetAmount && (!this.collectModal.form.amount || this.collectModal.form.amount === '0' || this.collectModal.form.amount === '0.00')) {
                this.collectModal.form.amount = parseFloat(targetAmount).toFixed(2);
            }
        },

        clearCustomer() {
            this.collectModal.selectedCustomer = null;
            this.collectModal.form.customer_id = '';
            this.collectModal.customerSearch = '';
            this.collectModal.customerDropdownOpen = false;
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
        },

        openCollectModal() {
            this.collectModal.selectedCustomer = null;
            this.collectModal.customerSearch = '';
            this.collectModal.customerDropdownOpen = false;
            this.collectModal.form = {
                customer_id: '',
                billing_month: '{{ Carbon\Carbon::now()->format('Y-m') }}',
                payment_method: 'cash',
                amount: '',
                discount: 0,
                transaction_id: '',
                paid_at: '{{ Carbon\Carbon::now()->format('Y-m-d\TH:i') }}',
                notes: '',
                send_sms: true,
            };
            this.collectModal.open = true;
        },

        async submitCollectPayment() {
            if (!this.collectModal.form.customer_id) {
                this.showToast('Please search and select a subscriber first.', 'error');
                return;
            }
            this.collectModal.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.finance.payments.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.collectModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.collectModal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Failed to record payment.', 'error');
                }
            } catch (err) {
                this.showToast('Network error recording payment.', 'error');
            } finally {
                this.collectModal.loading = false;
            }
        },

        async openReceiptModal(id) {
            this.receiptModal.loading = true;
            this.receiptModal.payment = null;
            this.receiptModal.open = true;

            try {
                const res = await fetch(`{{ url('/admin/finance/payments') }}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.receiptModal.payment = data.payment;
                } else {
                    this.showToast('Failed to load receipt voucher details.', 'error');
                    this.receiptModal.open = false;
                }
            } catch (err) {
                this.showToast('Network error fetching money receipt.', 'error');
                this.receiptModal.open = false;
            } finally {
                this.receiptModal.loading = false;
            }
        },

        printReceipt() {
            window.print();
        },

        openSmsModal(payment) {
            this.smsModal.payment = payment;
            this.smsModal.open = true;
        },

        async submitSendSms() {
            if (!this.smsModal.payment?.id) return;
            this.smsModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/finance/payments') }}/${this.smsModal.payment.id}/sms`, {
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
                    this.smsModal.open = false;
                } else {
                    this.showToast(data.message || 'SMS dispatch failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error sending SMS.', 'error');
            } finally {
                this.smsModal.loading = false;
            }
        },

        openVoidModal(payment) {
            this.voidModal.payment = payment;
            this.voidModal.reason = '';
            this.voidModal.open = true;
        },

        async submitVoidPayment() {
            if (!this.voidModal.payment?.id) return;
            this.voidModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/finance/payments') }}/${this.voidModal.payment.id}/void`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: this.voidModal.reason })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.voidModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Voided',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Failed to void payment.', 'error');
                }
            } catch (err) {
                this.showToast('Network error voiding payment.', 'error');
            } finally {
                this.voidModal.loading = false;
            }
        },

        async approvePayment(item) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Approve Payment & Activate Line?',
                    html: `<div class="text-xs text-slate-600">Are you sure you want to approve receipt <b>${item.invoice_no}</b> (${item.amount_formatted})?<br><span class="text-slate-400 mt-1 block">Subscriber line will be activated immediately on MikroTik and validity extended.</span></div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve & Activate',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to approve payment '${item.invoice_no}' (${item.amount_formatted}) and activate customer line in MikroTik & FreeRADIUS?`)) {
                    return;
                }
            }

            try {
                const res = await fetch(`{{ url('/admin/finance/payments') }}/${item.id}/approve`, {
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
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Approved!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Approval Failed',
                            text: data.message || 'Failed to approve payment.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        this.showToast(data.message || 'Failed to approve payment.', 'error');
                    }
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not communicate with the server to approve payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    this.showToast('Network error approving payment.', 'error');
                }
            }
        },

        async rejectPayment(item) {
            let reason = 'Invalid TrxID / Unverified Payment';
            if (typeof Swal !== 'undefined') {
                const { value: text, isConfirmed } = await Swal.fire({
                    title: 'Reject Pending Payment',
                    input: 'textarea',
                    inputLabel: `Rejection reason for receipt ${item.invoice_no}`,
                    inputValue: reason,
                    inputPlaceholder: 'Type rejection reason here...',
                    inputAttributes: {
                        'aria-label': 'Type rejection reason here'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Rejection',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer',
                        input: 'text-xs text-slate-700'
                    },
                    buttonsStyling: false
                });
                if (!isConfirmed) return;
                reason = text || reason;
            } else {
                let prompted = prompt(`Please enter rejection reason for payment '${item.invoice_no}':`, reason);
                if (prompted === null) return;
                reason = prompted;
            }

            try {
                const res = await fetch(`{{ url('/admin/finance/payments') }}/${item.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'warning',
                            title: 'Payment Rejected',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Rejection Failed',
                            text: data.message || 'Failed to reject payment.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        this.showToast(data.message || 'Failed to reject payment.', 'error');
                    }
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not communicate with the server to reject payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    this.showToast('Network error rejecting payment.', 'error');
                }
            }
        }
    };
}
</script>
@endpush
