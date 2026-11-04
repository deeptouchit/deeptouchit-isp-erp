@extends('tenant.layouts.app')

@section('title', 'কালেক্টর আদায় বিস্তারিত: ' . $collectorName . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printableDetailsArea, #printableDetailsArea * {
            visibility: visible;
        }
        #printableDetailsArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 15px;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="collectorDetailsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            {{-- Back to Collector Collections Summary --}}
            <a href="{{ route('tenant.finance.collector-collections', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'partner_id' => $resellerId]) }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs cursor-pointer"
               title="Back to Collector Collections Summary">
                <i class="fas fa-arrow-left"></i>
            </a>

            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span>কালেক্টর আদায় বিস্তারিত: {{ $collectorName }}</span>
            </h1>

            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                <i class="fas fa-calendar-alt text-cyan-600 text-[9px]"></i>
                <span>{{ $periodLabel }}</span>
            </span>

            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fas fa-handshake text-slate-500 text-[9px]"></i>
                <span>{{ $partnerName }}</span>
            </span>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            {{-- Print Details List --}}
            <button type="button" 
                    @click="window.print()" 
                    class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-3 rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Print Itemized Collection Sheet">
                <i class="fas fa-print text-cyan-600 text-xs"></i>
                <span class="hidden sm:inline">Print</span>
            </button>

            {{-- Export CSV --}}
            <a href="{{ route('tenant.finance.payments.export', array_merge(request()->query(), ['collected_by' => $collectorId, 'reseller_id' => $resellerId])) }}" 
               class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3.5 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer"
               title="Export Receipts as CSV">
                <i class="fas fa-file-csv text-xs"></i>
                <span class="hidden sm:inline">Export</span> CSV
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div id="detailsKpiStrip" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Collected --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">@currency($totalAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 2: Cash Collected --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-700 block truncate">Cash Collection</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">@currency($cashAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 3: Digital / Online --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-cyan-700 block truncate">Digital &amp; Online</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">@currency($digitalAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 4: Total Receipts Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Receipts</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">{{ number_format($totalCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 5: Unique Customers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Customers</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">{{ number_format($uniqueCustomersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 6: Average Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Average Bill</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">@currency($avgAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Architecture & Uniform h-[34px] - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('tenant.finance.collector-collections.show', $collectorId) }}" id="detailsFilterForm" @submit.prevent="submitSearchNow()" class="space-y-2.5">
            {{-- Hidden scope parameters to maintain session context --}}
            <input type="hidden" name="reseller_id" value="{{ $resellerId }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">

            {{-- Tier 1: Primary Filter Bar (Strictly 1 Single Row on md+ screens - AGENTS.md Rule 2.C) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                
                {{-- Intelligent Live Search Box (Client-Side & Server Filter) --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-spinner fa-spin absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-cyan-600 pointer-events-none"
                       x-show="isSearching" x-cloak></i>
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"
                       x-show="!isSearching"></i>
                    <input type="text" 
                           name="search" 
                           id="detailsSearchInput"
                           x-model="searchQuery"
                           @input="handleLiveSearch($event.target.value)"
                           @keydown.enter.prevent="submitSearchNow()"
                           placeholder="Search Receipt No, Customer ID, Name, Phone..." 
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

                {{-- Payment Method Filter --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="payment_method" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="all">All Payment Methods</option>
                        <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>💵 Cash Only</option>
                        <option value="bkash" {{ $paymentMethod === 'bkash' ? 'selected' : '' }}>📱 bKash</option>
                        <option value="nagad" {{ $paymentMethod === 'nagad' ? 'selected' : '' }}>📱 Nagad</option>
                        <option value="rocket" {{ $paymentMethod === 'rocket' ? 'selected' : '' }}>📱 Rocket</option>
                        <option value="bank" {{ $paymentMethod === 'bank' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                    </select>
                </div>

                {{-- Pagination Per-Page Selector --}}
                <div class="w-full md:w-24 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700 cursor-pointer">
                        <option value="15" {{ request('per_page', $perPage) == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ request('per_page', $perPage) == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ request('per_page', $perPage) == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ request('per_page', $perPage) == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </div>

                {{-- Strict Filter & Reset Sequence (Strict AGENTS.md Rule 2.C - Far Right Aligned on md+) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.collector-collections.show', array_merge(['collector' => $collectorId], request()->only(['reseller_id', 'period', 'month', 'year', 'date_from', 'date_to']))) }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System - AGENTS.md Rule 2.D) --}}
    <div id="printableDetailsArea" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center no-sort">#</th>
                        <th class="text-left font-semibold">রিসিট নং (Receipt No)</th>
                        <th class="text-left font-semibold">গ্রাহকের আইডি ও নাম (Customer)</th>
                        <th class="text-left font-semibold">মোবাইল নম্বর (Phone)</th>
                        <th class="text-left font-semibold">প্যাকেজ (Package)</th>
                        <th class="text-right font-semibold">আদায়ের পরিমাণ (Amount)</th>
                        <th class="text-center font-semibold">পেমেন্ট মাধ্যম (Method)</th>
                        <th class="text-left font-semibold">আদায়ের তারিখ ও সময় (Paid At)</th>
                        <th class="w-24 text-center no-sort">একশন (Action)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="searchQuery && totalMatchingRows === 0" x-cloak class="no-record-row">
                        <td colspan="9" class="text-center py-6 text-slate-500 bg-slate-50/50">
                            <i class="fas fa-spinner fa-spin text-cyan-600 mr-1.5" x-show="isSearching"></i>
                            <i class="fas fa-search text-slate-400 mr-1.5" x-show="!isSearching"></i>
                            <span class="text-xs font-medium">No matching receipts found for this search.</span>
                        </td>
                    </tr>
                    @forelse($payments as $index => $p)
                        @php
                            $rowNum = $payments->firstItem() ? ($payments->firstItem() + $index) : ($index + 1);
                            $cleanReceiptNo = $p->invoice_no ?: ('REC-' . str_pad($p->id, 5, '0', STR_PAD_LEFT));
                            $searchPayload = strtolower($cleanReceiptNo . ' ' . ($p->customer?->customer_id ?? '') . ' ' . ($p->customer?->username ?? '') . ' ' . ($p->customer?->name ?? '') . ' ' . ($p->customer?->phone ?? ''));
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors" data-search-text="{{ $searchPayload }}">
                            {{-- 1. Index --}}
                            <td class="w-10 text-center font-mono text-xs text-slate-500">
                                {{ $rowNum }}
                            </td>

                            {{-- 2. Receipt No --}}
                            <td class="whitespace-nowrap font-mono font-bold text-cyan-700 text-xs">
                                <a href="{{ route('tenant.finance.payments.show', $p->id) }}" target="_blank" class="hover:underline inline-flex items-center gap-1" title="View Full Money Receipt">
                                    <i class="fas fa-receipt text-[10px]"></i>
                                    <span>{{ $cleanReceiptNo }}</span>
                                </a>
                            </td>

                            {{-- 3. Customer ID & Name --}}
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-900">{{ $p->customer?->name ?? 'Unknown' }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">({{ $p->customer?->customer_id ?: ($p->customer?->username ?: 'N/A') }})</span>
                                </div>
                            </td>

                            {{-- 4. Mobile Phone --}}
                            <td class="whitespace-nowrap font-mono text-xs text-slate-600">
                                {{ $p->customer?->phone ?: '—' }}
                            </td>

                            {{-- 5. Package Profile --}}
                            <td class="whitespace-nowrap text-xs font-medium text-slate-700">
                                {{ $p->customer?->package?->name ?: 'Standard Plan' }}
                            </td>

                            {{-- 6. Amount Collected --}}
                            <td class="text-right whitespace-nowrap font-mono font-bold text-xs text-emerald-600">
                                @currency($p->amount)
                            </td>

                            {{-- 7. Payment Method --}}
                            <td class="text-center whitespace-nowrap font-mono text-[10px]">
                                @php
                                    $method = strtolower($p->payment_method ?? 'cash');
                                    $isCash = $method === 'cash';
                                @endphp
                                <span class="px-2 py-0.5 rounded font-bold uppercase {{ $isCash ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $p->payment_method ?: 'CASH' }}
                                </span>
                            </td>

                            {{-- 8. Paid Date & Time --}}
                            <td class="whitespace-nowrap font-mono text-[11px] text-slate-500">
                                {{ $p->paid_at ? $p->paid_at->format('d M Y, h:i A') : '—' }}
                            </td>

                            {{-- 9. Action: Print Receipt Voucher --}}
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.finance.payments.show', $p->id) }}" 
                                   target="_blank"
                                   class="h-[28px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                                   title="Open / Print Money Receipt Voucher">
                                    <i class="fas fa-print text-cyan-600 text-[10px]"></i>
                                    <span>Receipt</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-record-row">
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                <i class="fas fa-inbox text-2xl mb-1.5 block text-slate-300"></i>
                                <span class="font-medium text-slate-600 text-xs">No payment receipts found for this collector in this period.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($payments->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/60 no-print">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function collectorDetailsManager() {
    return {
        searchQuery: @json($search ?? ''),
        searchDebounceTimer: null,
        totalMatchingRows: null,
        isSearching: false,

        handleLiveSearch(query) {
            this.searchQuery = query;
            const q = query.trim().toLowerCase();

            // Client-side instant filter on table rows
            const rows = document.querySelectorAll('tbody tr[data-search-text]');
            let matched = 0;

            rows.forEach(row => {
                const text = row.getAttribute('data-search-text') || '';
                if (!q || text.includes(q)) {
                    row.style.display = '';
                    matched++;
                } else {
                    row.style.display = 'none';
                }
            });

            this.totalMatchingRows = matched;

            // Debounced server-side submit for pagination accuracy
            clearTimeout(this.searchDebounceTimer);
            this.isSearching = true;
            this.searchDebounceTimer = setTimeout(() => {
                this.isSearching = false;
                if (q.length === 0 || q.length >= 2) {
                    const form = document.getElementById('detailsFilterForm');
                    if (form) form.submit();
                }
            }, 600);
        },

        submitSearchNow() {
            clearTimeout(this.searchDebounceTimer);
            this.isSearching = false;
            const form = document.getElementById('detailsFilterForm');
            if (form) form.submit();
        },

        clearLiveSearch() {
            this.searchQuery = '';
            const input = document.getElementById('detailsSearchInput');
            if (input) input.value = '';
            
            const rows = document.querySelectorAll('tbody tr[data-search-text]');
            rows.forEach(row => row.style.display = '');
            this.totalMatchingRows = null;

            this.$nextTick(() => {
                const form = document.getElementById('detailsFilterForm');
                if (form) form.submit();
            });
        }
    };
}
</script>
@endpush
