@extends('tenant.layouts.app')

@section('title', 'Subscription Invoices - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="invoicesManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY. No subtitle/p tag) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Subscription Invoices Registry</h1>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    Total {{ number_format($totalInvoicesCount) }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            @if($totalDueAmount > 0 && $latestDueInvoice)
                <a href="{{ route('tenant.billing.invoice.show', $latestDueInvoice) }}" 
                   class="h-[34px] px-3 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-xs"></i>
                    <span>Pay Due (@currency($totalDueAmount))</span>
                </a>
            @endif

            <a href="{{ route('tenant.billing.dashboard') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-xs text-slate-500"></i>
                <span>Plan Overview</span>
            </a>

            <a href="{{ route('tenant.billing.payments') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs text-emerald-600"></i>
                <span>Payment History</span>
            </a>
        </div>
    </div>

    {{-- 1.1 OUTSTANDING DUE ALERT BANNER (If due invoice exists) --}}
    @if($totalDueAmount > 0 && $latestDueInvoice)
        <div class="p-3 rounded-xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs flex-shrink-0 border border-amber-200">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <span class="font-bold text-amber-900 block text-xs">Outstanding Due: @currency($totalDueAmount)</span>
                    <span class="text-[11px] text-amber-800 font-normal">
                        Invoice #{{ $latestDueInvoice->invoice_no }} • Due Date: <span class="font-mono font-medium">{{ $latestDueInvoice->due_date ? \Carbon\Carbon::parse($latestDueInvoice->due_date)->format('d M Y') : 'Immediate' }}</span>
                    </span>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.billing.invoice.show', $latestDueInvoice) }}" 
                   class="h-[32px] px-3.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-[11px]"></i>
                    <span>Pay Online (bKash / Nagad)</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Invoices --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Invoices</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($totalInvoicesCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>

        {{-- Card 2: Settled Invoices --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">Settled Invoices</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($paidInvoicesCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Unpaid Invoices --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-rose-700 block truncate">Unpaid Invoices</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    {{ number_format($unpaidInvoicesCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 4: Total Invoiced --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Invoiced</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($totalInvoicedAmount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 5: Total Settled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">Total Settled</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($totalPaidAmount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 6: Outstanding Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-700 block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold font-mono {{ $totalDueAmount > 0 ? 'text-amber-600' : 'text-slate-800' }} leading-tight block truncate">
                    @currency($totalDueAmount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    @php
        $isMoreOpen = (!empty($dateFrom) || !empty($dateTo));
        $activeMoreCount = (!empty($dateFrom) ? 1 : 0) + (!empty($dateTo) ? 1 : 0);
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search ?? '') }}' }">
        <form action="{{ route('tenant.billing.invoices') }}" method="GET" class="space-y-2.5">
            
            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with 1-Click Clear --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           value="{{ $search }}" 
                           placeholder="Search invoice number or plan..." 
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Status --}}
                <select name="status" class="h-[34px] w-full md:w-44 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid Invoices</option>
                    <option value="unpaid" {{ in_array($status, ['unpaid', 'pending']) ? 'selected' : '' }}>Unpaid Invoices</option>
                    <option value="partially_paid" {{ $status === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="h-[34px] w-full md:w-28 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono cursor-pointer transition">
                    <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / page</option>
                    <option value="20" {{ ($perPage ?? 15) == 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100 / page</option>
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
                    <a href="{{ route('tenant.billing.invoices') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset All Filters">
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
                
                {{-- Date From --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">Issue Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="h-[34px] w-full sm:w-40 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Date To --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">Issue Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="h-[34px] w-full sm:w-40 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Invoice No</th>
                        <th class="text-center font-semibold">Plan / Description</th>
                        <th class="text-center font-semibold">Issue Date</th>
                        <th class="text-center font-semibold">Due Date</th>
                        <th class="text-center font-semibold">Total Amount</th>
                        <th class="text-center font-semibold">Paid Amount</th>
                        <th class="text-center font-semibold">Due</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-24 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $inv)
                        @php
                            $calculatedDue = (float) $inv->amount - (float) $inv->paid_amount;
                            if ($calculatedDue < 0) $calculatedDue = 0;
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 text-xs w-10">
                                {{ $invoices->firstItem() + $index }}
                            </td>

                            {{-- 2. Invoice No (text-center font-mono) --}}
                            <td class="text-center font-mono font-bold text-cyan-800 text-xs">
                                <a href="{{ route('tenant.billing.invoice.show', $inv) }}" class="hover:underline">
                                    {{ $inv->invoice_no }}
                                </a>
                            </td>

                            {{-- 3. Plan / Description (text-left) --}}
                            <td class="text-left font-semibold text-slate-800 text-xs">
                                {{ $inv->plan->name ?? 'SaaS Subscription Plan' }}
                            </td>

                            {{-- 4. Issue Date (text-center font-mono) --}}
                            <td class="text-center font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $inv->created_at ? $inv->created_at->format('d M Y') : 'N/A' }}
                            </td>

                            {{-- 5. Due Date (text-center font-mono) --}}
                            <td class="text-center font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : 'Immediate' }}
                            </td>

                            {{-- 6. Total Amount (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                @currency($inv->amount)
                            </td>

                            {{-- 7. Paid Amount (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-emerald-700 text-xs whitespace-nowrap">
                                @currency($inv->paid_amount)
                            </td>

                            {{-- 8. Remaining Due (text-right font-mono) --}}
                            <td class="text-right font-mono font-bold text-xs whitespace-nowrap {{ $calculatedDue > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                @currency($calculatedDue)
                            </td>

                            {{-- 9. Status (text-center badge) --}}
                            <td class="text-center">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700 whitespace-nowrap">
                                        <i class="fas fa-check text-[8px]"></i>
                                        <span>Paid</span>
                                    </span>
                                @elseif($inv->status === 'partially_paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-amber-200 bg-amber-50 text-amber-700 whitespace-nowrap">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>Partial</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-rose-200 bg-rose-50 text-rose-700 whitespace-nowrap">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>Unpaid</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 10. Action (text-center) --}}
                            <td class="text-center whitespace-nowrap">
                                <a href="{{ route('tenant.billing.invoice.show', $inv) }}" 
                                   class="h-[28px] px-2.5 rounded-lg text-xs font-semibold transition inline-flex items-center justify-center gap-1 cursor-pointer {{ $calculatedDue > 0 ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80' }}">
                                    <i class="fas {{ $calculatedDue > 0 ? 'fa-credit-card' : 'fa-eye' }} text-[10px]"></i>
                                    <span>{{ $calculatedDue > 0 ? 'Pay Online' : 'View' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-sm">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Invoices Found</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">No subscription invoices match the selected filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($invoices->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} subscription invoices
                </span>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoicesManager() {
    return {};
}
</script>
@endpush
