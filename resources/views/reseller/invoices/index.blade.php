@extends('reseller.layouts.app')

@section('title', 'Wholesale Invoices - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="invoiceManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Wholesale Invoices') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <a href="{{ route('reseller.invoices.print-report', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.invoices.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Invoiced --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Billed') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    @currency($stats['total_invoiced'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 2: Total Paid --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Paid') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['total_paid'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Total Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Due') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    @currency($stats['total_due'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: This Month Billed --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Monthly Billed') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($stats['this_month_billed'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        {{-- Card 5: Paid Invoices Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Paid Invoices') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    {{ number_format($stats['paid_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        {{-- Card 6: Due Invoices Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Unpaid Invoices') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">
                    {{ number_format($stats['unpaid_count'] ?? ($stats['due_count'] ?? 0)) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.invoices.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Box (MD: 4 Cols) -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ $search }}" 
                       autocomplete="off"
                       placeholder="Search invoice #, remarks..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Payment Status Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="PAID" {{ $status === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="PARTIAL" {{ $status === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                    <option value="UNPAID" {{ $status === 'UNPAID' ? 'selected' : '' }}>Unpaid / Due</option>
                    <option value="CANCELLED" {{ $status === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Billing Month Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="month" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $month === 'all' ? 'selected' : '' }}>All Months</option>
                    @foreach($availableMonths as $m)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($m . '-01')->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Type Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="type" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="BANDWIDTH_WHOLESALE" {{ $type === 'BANDWIDTH_WHOLESALE' ? 'selected' : '' }}>Bandwidth Wholesale</option>
                    <option value="PANEL_SUBSCRIPTION" {{ $type === 'PANEL_SUBSCRIPTION' ? 'selected' : '' }}>Panel Subscription</option>
                    <option value="MANUAL_CHARGE" {{ $type === 'MANUAL_CHARGE' ? 'selected' : '' }}>Manual Charge</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C - MD: 2 Cols) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.invoices.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-32 font-mono">Invoice #</th>
                        <th class="w-24 text-center font-mono">Month</th>
                        <th>Type / Service</th>
                        <th class="w-28 text-right">Billed</th>
                        <th class="w-28 text-right">Paid</th>
                        <th class="w-28 text-right">Due</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $inv)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($invoices->currentPage() - 1) * $invoices->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Invoice # (Single Clean Field) -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-mono" @click="fetchInvoiceDetails({{ $inv->id }})">
                                    {{ $inv->invoice_no }}
                                </button>
                            </td>

                            <!-- 3. Billing Month -->
                            <td class="text-center font-mono text-xs font-semibold text-slate-700">
                                {{ $inv->billing_month ? $inv->billing_month->format('M Y') : '—' }}
                            </td>

                            <!-- 4. Type / Service -->
                            <td class="font-semibold text-slate-800">
                                <button type="button" class="hover:text-cyan-700 cursor-pointer text-left" @click="fetchInvoiceDetails({{ $inv->id }})">
                                    {{ $inv->type_label }}
                                </button>
                            </td>

                            <!-- 5. Billed Amount -->
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($inv->amount)
                            </td>

                            <!-- 6. Paid Amount -->
                            <td class="text-right font-mono font-semibold text-emerald-600">
                                @currency($inv->paid_amount)
                            </td>

                            <!-- 7. Due Amount -->
                            <td class="text-right font-mono font-bold {{ $inv->due_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                @currency($inv->due_amount)
                            </td>

                            <!-- 8. Status Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $inv->status_badge['class'] }}">
                                    <i class="fas {{ $inv->status_badge['icon'] }} text-[8px]"></i>
                                    <span>{{ $inv->status_badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 9. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from([
                                            'id' => $inv->id,
                                            'no' => $inv->invoice_no,
                                            'due' => (float)$inv->due_amount,
                                            'status' => $inv->payment_status
                                        ]) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <i class="fas fa-file-invoice text-3xl text-slate-300 mb-2 block"></i>
                                <span>No wholesale invoices found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($invoices->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $invoices->firstItem() }}</span> to <span class="font-bold">{{ $invoices->lastItem() }}</span> of <span class="font-bold">{{ $invoices->total() }}</span> wholesale invoices
                </div>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-52 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="fetchInvoiceDetails(activeMenuItem.id); activeMenu = null" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-file-invoice text-cyan-600 w-4"></i>
                <span>View Invoice Details</span>
            </button>

            <a :href="'/reseller/invoices/' + activeMenuItem?.id + '/print'" 
               target="_blank" 
               @click="activeMenu = null"
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-slate-900 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-print text-slate-500 w-4"></i>
                <span>Print Tax Invoice</span>
            </a>
        </div>

        <template x-if="activeMenuItem && activeMenuItem.due > 0">
            <div class="py-0.5">
                <button type="button" 
                        @click="payInvoiceWithWallet(activeMenuItem.id, activeMenuItem.due, activeMenuItem.no); activeMenu = null" 
                        class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 text-emerald-700 font-bold flex items-center gap-2 cursor-pointer transition">
                    <i class="fas fa-wallet text-emerald-600 w-4"></i>
                    <span>Pay from Wallet</span>
                </button>
            </div>
        </template>
    </div>

    {{-- 6. MODAL: NATURAL SOFT INVOICE PREVIEW MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showInvoiceModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showInvoiceModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden my-6">
            
            {{-- Soft Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Wholesale Invoice Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="invoiceData?.invoice_no || 'Invoice'"></p>
                    </div>
                </div>
                <button type="button" @click="showInvoiceModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-4" x-show="invoiceData">
                
                <!-- Financial Banner -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <span class="text-[10px] uppercase font-medium text-slate-500 block">Total Billed</span>
                        <span class="text-sm font-bold font-mono text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-200 text-center">
                        <span class="text-[10px] uppercase font-medium text-emerald-700 block">Total Paid</span>
                        <span class="text-sm font-bold font-mono text-emerald-700" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.paid_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="p-2.5 rounded-lg border text-center" :class="(invoiceData?.due_amount || 0) > 0 ? 'bg-rose-50/60 border-rose-200 text-rose-700' : 'bg-slate-50 border-slate-200 text-slate-600'">
                        <span class="text-[10px] uppercase font-medium block">Balance Due</span>
                        <span class="text-sm font-bold font-mono" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.due_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

                <!-- Invoice Meta Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Billing Month</span>
                        <span class="font-bold font-mono text-slate-800" x-text="invoiceData?.billing_month"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Status</span>
                        <span class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-bold border" :class="invoiceData?.status_badge?.class" x-text="invoiceData?.status_badge?.label"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Due Date</span>
                        <span class="font-mono text-slate-800" x-text="invoiceData?.due_date || 'N/A'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Settled Method</span>
                        <span class="font-semibold text-slate-800" x-text="invoiceData?.payment_method || 'Unsettled'"></span>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-1.5 px-3 text-left">Item &amp; Description</th>
                                <th class="py-1.5 px-2 text-center w-20">Qty</th>
                                <th class="py-1.5 px-2 text-right w-24">Rate</th>
                                <th class="py-1.5 px-3 text-right w-28">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, idx) in invoiceData?.items" :key="idx">
                                <tr class="hover:bg-slate-50/60">
                                    <td class="py-2 px-3">
                                        <div class="font-semibold text-slate-800" x-text="item.item"></div>
                                        <div class="text-[11px] text-slate-500" x-text="item.description"></div>
                                    </td>
                                    <td class="py-2 px-2 text-center font-mono text-slate-700" x-text="item.qty + (item.unit ? ' ' + item.unit : '')"></td>
                                    <td class="py-2 px-2 text-right font-mono text-slate-700" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(item.rate).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(item.total).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold text-xs">
                            <template x-if="invoiceData?.discount > 0">
                                <tr>
                                    <td colspan="3" class="py-1 px-3 text-right text-slate-600">Special Discount:</td>
                                    <td class="py-1 px-3 text-right font-mono text-amber-700" x-text="'-{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.discount).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                            <tr>
                                <td colspan="3" class="py-1.5 px-3 text-right text-slate-800 font-bold">Net Total Amount:</td>
                                <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Wallet Pay Callout if Due -->
                <template x-if="invoiceData?.due_amount > 0">
                    <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-emerald-900">Instant Wallet Settlement</div>
                            <div class="text-[11px] text-emerald-700">Available Wallet Balance: <span class="font-mono font-bold" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(invoiceData?.total_available || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></div>
                        </div>
                        <button type="button" 
                                @click="payInvoiceWithWallet(invoiceData.id, invoiceData.due_amount, invoiceData.invoice_no)" 
                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-wallet text-[10px]"></i>
                            <span>Pay ৳<span x-text="Number(invoiceData.due_amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></span>
                        </button>
                    </div>
                </template>

            </div>

            {{-- Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <a :href="'/reseller/invoices/' + invoiceData?.id + '/print'" 
                   target="_blank" 
                   class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print text-slate-500 text-xs"></i>
                    <span>Print Tax Invoice</span>
                </a>
                <button type="button" 
                        @click="showInvoiceModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
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
        activeMenuItem: null,
        menuPos: {},
        showInvoiceModal: false,
        invoiceData: null,

        toggleMenu(item, event) {
            if (this.activeMenu === item.id) {
                this.activeMenu = null;
                this.activeMenuItem = null;
                return;
            }
            this.activeMenu = item.id;
            this.activeMenuItem = item;

            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 140;
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

        async fetchInvoiceDetails(id) {
            try {
                const res = await fetch(`/reseller/invoices/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.invoiceData = data.data;
                    this.showInvoiceModal = true;
                }
            } catch (err) {
                console.error("Error fetching invoice:", err);
            }
        },

        async payInvoiceWithWallet(id, dueAmount, invoiceNo) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Settle Invoice with Wallet?',
                    html: `<div class="text-xs text-slate-600">Are you sure you want to settle invoice <b>#${invoiceNo}</b> of <b>{{ $currencySymbol ?? '৳' }}${Number(dueAmount).toLocaleString(undefined, {minimumFractionDigits: 2})}</b> using your prepaid wallet balance?</div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Pay from Wallet',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to pay ৳${Number(dueAmount).toLocaleString()} for invoice #${invoiceNo} from your wallet balance?`)) {
                    return;
                }
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/reseller/invoices/${id}/pay-wallet`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Paid Successfully!',
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
                            title: 'Payment Failed',
                            text: data.message || 'Payment could not be completed.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(data.message || 'Payment failed.');
                    }
                }
            } catch (err) {
                console.error("Payment error:", err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An error occurred while processing payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    alert("An error occurred while processing payment.");
                }
            }
        }
    }
}
</script>
@endpush
