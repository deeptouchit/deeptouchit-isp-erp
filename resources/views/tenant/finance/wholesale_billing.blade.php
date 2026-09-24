@extends('tenant.layouts.app')

@section('title', 'Reseller Wholesale Billing - ' . ($tenant->company_name ?? $tenant->name))

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
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            padding: 20px !important;
            background: #ffffff !important;
            color: #000000 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="wholesaleBillingManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strict Rule: Title + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-1 border-b border-slate-200/80">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-sm shadow-2xs">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-base font-bold text-slate-800 tracking-tight">Reseller Wholesale Billing</h1>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" @click="openBatchGenerateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                <i class="fas fa-bolt text-[11px]"></i>
                <span>Generate Monthly Bills</span>
            </button>

            <button type="button" @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>Create Custom Invoice</span>
            </button>

            <a href="{{ route('tenant.finance.wholesale-billing.export', request()->all()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Wholesale Invoiced --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Invoiced</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">@currency($totalInvoicedAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 2: Total Collected / Paid --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($totalCollectedAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 3: Outstanding Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">@currency($totalDueAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
        </div>

        {{-- Card 4: Paid Invoices Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Paid Invoices</span>
                <span class="text-[13px] font-bold font-mono text-teal-700 leading-tight block truncate">{{ number_format($paidCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 5: Unpaid / Due Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Unpaid / Due</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">{{ number_format($unpaidCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 6: Total Sub-ISPs --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Sub-ISPs / Resellers</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">{{ number_format($resellersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 border border-cyan-100 text-cyan-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.finance.wholesale-billing') }}" class="space-y-2">
            <!-- Row 1: Search, Period Preset & Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2">
                <!-- Search Box -->
                <div class="md:col-span-4 relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Invoice #, Reseller, Notes..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Quick Period Preset -->
                <div class="md:col-span-2">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value)"
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition font-medium">
                        <option value="all" {{ ($period ?? '') === 'all' || empty($period) ? 'selected' : '' }}>All Dates</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-day"></i>
                        </span>
                        <input type="date" 
                               name="date_from" 
                               id="dateFromInput"
                               value="{{ $dateFrom }}" 
                               title="From Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>

                <!-- Date To -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-check"></i>
                        </span>
                        <input type="date" 
                               name="date_to" 
                               id="dateToInput"
                               value="{{ $dateTo }}" 
                               title="To Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>
            </div>

            <!-- Row 2: Reseller, Month, Type, Status, Per Page & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 pt-1 border-t border-slate-100">
                <!-- Sub-ISP Reseller Filter -->
                <div class="md:col-span-3">
                    @if(!$isResellerUser)
                    <select name="reseller_id" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all">All Sub-ISPs</option>
                        @foreach($allResellers as $res)
                            <option value="{{ $res->id }}" {{ $selectedResellerId == (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code }})
                            </option>
                        @endforeach
                    </select>
                    @else
                    <div class="px-2.5 py-1.5 text-xs bg-slate-100 border border-slate-200 rounded-lg text-slate-600 truncate">
                        {{ $tenant->company_name ?? 'My Invoices' }}
                    </div>
                    @endif
                </div>

                <!-- Month Filter -->
                <div class="md:col-span-2">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth !== 'all' ? $selectedMonth : '' }}" 
                           title="Billing Month"
                           class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                </div>

                <!-- Invoice Type Filter -->
                <div class="md:col-span-2">
                    <select name="type" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedType === 'all' ? 'selected' : '' }}>All Invoice Types</option>
                        <option value="BANDWIDTH_WHOLESALE" {{ $selectedType === 'BANDWIDTH_WHOLESALE' ? 'selected' : '' }}>Bandwidth Wholesale</option>
                        <option value="PANEL_SOFTWARE_FEE" {{ $selectedType === 'PANEL_SOFTWARE_FEE' ? 'selected' : '' }}>Software / Panel Fee</option>
                        <option value="MANUAL_CHARGE" {{ $selectedType === 'MANUAL_CHARGE' ? 'selected' : '' }}>Manual Custom Charge</option>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="md:col-span-2">
                    <select name="status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="PAID" {{ $selectedStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                        <option value="UNPAID" {{ $selectedStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                        <option value="PARTIAL" {{ $selectedStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                        <option value="CANCELLED" {{ $selectedStatus === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="md:col-span-1">
                    <select name="per_page" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <!-- Strict Action Buttons: [Filter] FIRST, [Reset] SECOND (AGENTS.md Rule 2.C) -->
                <div class="md:col-span-2 flex items-center justify-end gap-1.5">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.finance.wholesale-billing') }}" 
                       class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1 cursor-pointer flex-shrink-0" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Master Compact Table (<table class="saas-table"> - Clean Single-Line Core Columns) --}}
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto min-h-[300px]">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-36">Invoice No</th>
                        <th>Sub-ISP / Reseller</th>
                        <th class="w-28">Billing Month</th>
                        <th class="text-right w-28">Total Amount</th>
                        <th class="text-right w-28">Due Amount</th>
                        <th class="text-center w-28">Payment Status</th>
                        <th class="no-sort w-12 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $inv)
                        <tr>
                            {{-- Row index --}}
                            <td class="text-center font-mono text-slate-500">{{ $invoices->firstItem() + $index }}</td>

                            {{-- Invoice No (Clickable to open View Details / Print Modal) --}}
                            <td>
                                <button type="button" @click="viewInvoice({{ $inv->id }})" class="font-mono font-bold text-cyan-800 hover:text-cyan-600 hover:underline cursor-pointer">
                                    {{ $inv->invoice_no }}
                                </button>
                            </td>

                            {{-- Sub-ISP / Reseller Name --}}
                            <td>
                                <span class="font-semibold text-slate-800">{{ $inv->reseller?->name ?? 'Unknown Reseller' }}</span>
                            </td>

                            {{-- Billing Month --}}
                            <td class="font-mono text-slate-700">
                                {{ $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month)->format('M Y') : '--' }}
                            </td>

                            {{-- Total Amount --}}
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($inv->amount)
                            </td>

                            {{-- Due Amount --}}
                            <td class="text-right font-mono font-bold {{ $inv->due_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                @currency($inv->due_amount)
                            </td>

                            {{-- Payment Status Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $inv->status_badge['class'] }}">
                                    <i class="fas {{ $inv->status_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $inv->status_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Action 3-Dot Button --}}
                            <td class="text-center">
                                <button type="button" @click.stop="toggleMenu({{ $inv->toJson() }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-700 inline-flex items-center justify-center transition cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                <i class="fas fa-file-invoice text-3xl mb-2 text-slate-300 block"></i>
                                <span class="text-xs font-medium">No wholesale invoices found for the selected criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Pagination --}}
        @if($invoices->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/60 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} invoices
                </div>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Implementation) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 divide-y divide-slate-100 text-xs transition duration-100">
        
        {{-- Group 1: Diagnostics & View --}}
        <div class="py-1">
            <button type="button" @click="viewInvoice(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-print w-3.5 text-cyan-600 text-[11px]"></i>
                <span>View &amp; Print Invoice</span>
            </button>
            <button type="button" @click="copyInvoiceNo(activeMenu.invoice_no); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-copy w-3.5 text-slate-500 text-[11px]"></i>
                <span>Copy Invoice Number</span>
            </button>
        </div>

        {{-- Group 2: Payment & Management --}}
        <div class="py-1">
            <template x-if="activeMenu && activeMenu.payment_status !== 'PAID' && activeMenu.payment_status !== 'CANCELLED'">
                <button type="button" @click="openPaymentModal(activeMenu); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-emerald-50/80 flex items-center gap-2 text-emerald-700 font-semibold transition cursor-pointer">
                    <i class="fas fa-hand-holding-dollar w-3.5 text-emerald-600 text-[11px]"></i>
                    <span>Record Payment</span>
                </button>
            </template>

            <template x-if="activeMenu && activeMenu.paid_amount == 0 && activeMenu.payment_status !== 'CANCELLED'">
                <button type="button" @click="cancelInvoice(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-amber-50 flex items-center gap-2 text-amber-700 transition cursor-pointer">
                    <i class="fas fa-ban w-3.5 text-amber-600 text-[11px]"></i>
                    <span>Cancel Invoice</span>
                </button>
            </template>

            <template x-if="activeMenu && (activeMenu.payment_status === 'CANCELLED' || (activeMenu.paid_amount == 0 && activeMenu.payment_status === 'UNPAID'))">
                <button type="button" @click="deleteInvoice(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                    <i class="fas fa-trash-can w-3.5 text-rose-500 text-[11px]"></i>
                    <span>Delete Invoice</span>
                </button>
            </template>
        </div>
    </div>

    {{-- MODAL 1: Batch Generate Monthly Wholesale Bills --}}
    <div x-show="showBatchModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showBatchModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Generate Monthly Wholesale Bills</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Auto calculate bandwidth trunk &amp; SaaS panel fees for Sub-ISPs</p>
                    </div>
                </div>
                <button type="button" @click="showBatchModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitBatchGenerate()">
                <div class="p-4 space-y-3.5 text-xs">
                    {{-- Billing Month --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Billing Month <span class="text-rose-500">*</span></label>
                        <input type="month" x-model="batchForm.billing_month" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    {{-- Target Reseller --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Target Sub-ISP Resellers <span class="text-rose-500">*</span></label>
                        <select x-model="batchForm.reseller_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="all">All Active Sub-ISPs (Batch)</option>
                            @foreach($allResellers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }}) - Bandwidth: {{ $r->bandwidthAllocation?->formatted_total_bandwidth ?? '0 Mbps' }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Payment Due Date --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Due Date</label>
                        <input type="date" x-model="batchForm.due_date" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    {{-- Auto Debit Prepaid Wallet Option --}}
                    <div class="p-3 bg-cyan-50/60 rounded-lg border border-cyan-200/80 space-y-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="batchForm.auto_deduct_wallet" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-xs font-semibold text-cyan-900">Auto-Debit from Reseller Prepaid Wallet</span>
                        </label>
                        <p class="text-[10px] text-cyan-700 pl-5">If enabled, the system will automatically deduct invoice amounts from Sub-ISPs with sufficient prepaid balances and mark them as Paid.</p>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showBatchModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Generate Invoices</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Create Custom Wholesale Invoice --}}
    <div x-show="showCreateModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showCreateModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-slate-800 text-cyan-400 border border-slate-700 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Create Custom Wholesale Invoice</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Issue custom charges, burst bandwidth, fiber maintenance, or ONU purchase bills</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitCreateInvoice()" class="flex flex-col flex-1 overflow-hidden">
                <div class="p-4 space-y-3.5 text-xs overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Select Reseller --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Sub-ISP Reseller <span class="text-rose-500">*</span></label>
                            <select x-model="createForm.reseller_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="">Select Reseller</option>
                                @foreach($allResellers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Invoice Type --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Invoice Category <span class="text-rose-500">*</span></label>
                            <select x-model="createForm.type" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="BANDWIDTH_WHOLESALE">Bandwidth Wholesale</option>
                                <option value="PANEL_SOFTWARE_FEE">Panel Software Fee</option>
                                <option value="MANUAL_CHARGE">Manual / Custom Surcharge</option>
                            </select>
                        </div>

                        {{-- Billing Month --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Billing Month <span class="text-rose-500">*</span></label>
                            <input type="month" x-model="createForm.billing_month" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>

                    {{-- Dynamic Line Items --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Itemized Breakdown</span>
                            <button type="button" @click="addLineItem()" class="text-cyan-600 hover:text-cyan-700 text-[11px] font-semibold inline-flex items-center gap-1 cursor-pointer">
                                <i class="fas fa-plus-circle"></i> Add Item
                            </button>
                        </div>

                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="p-2 text-left">Description / Service</th>
                                        <th class="p-2 w-20 text-right">Qty</th>
                                        <th class="p-2 w-20 text-left">Unit</th>
                                        <th class="p-2 w-24 text-right">Unit Rate ({{ $currencySymbol ?? '৳' }})</th>
                                        <th class="p-2 w-24 text-right">Total ({{ $currencySymbol ?? '৳' }})</th>
                                        <th class="p-2 w-10 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="(item, idx) in createForm.items" :key="idx">
                                        <tr class="bg-white">
                                            <td class="p-1.5">
                                                <input type="text" x-model="item.description" placeholder="e.g. 50 Mbps CIR Bandwidth Burst" required class="w-full bg-slate-50 border border-slate-200 rounded px-2 py-1 text-xs focus:bg-white focus:border-cyan-500 focus:outline-none">
                                            </td>
                                            <td class="p-1.5">
                                                <input type="number" step="0.01" min="0.01" x-model="item.qty" required class="w-full bg-slate-50 border border-slate-200 rounded px-2 py-1 text-xs text-right font-mono focus:bg-white focus:border-cyan-500 focus:outline-none">
                                            </td>
                                            <td class="p-1.5">
                                                <input type="text" x-model="item.unit" placeholder="Mbps/Unit" class="w-full bg-slate-50 border border-slate-200 rounded px-2 py-1 text-xs focus:bg-white focus:border-cyan-500 focus:outline-none">
                                            </td>
                                            <td class="p-1.5">
                                                <input type="number" step="0.01" min="0" x-model="item.unit_price" required class="w-full bg-slate-50 border border-slate-200 rounded px-2 py-1 text-xs text-right font-mono focus:bg-white focus:border-cyan-500 focus:outline-none">
                                            </td>
                                            <td class="p-1.5 text-right font-mono font-bold text-slate-800" x-text="formatNumber(item.qty * item.unit_price)">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <button type="button" @click="removeLineItem(idx)" :disabled="createForm.items.length <= 1" class="text-slate-400 hover:text-rose-600 disabled:opacity-30 cursor-pointer">
                                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Financial Calculation Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Discount ({{ $currencySymbol ?? '৳' }})</label>
                            <input type="number" step="0.01" min="0" x-model="createForm.discount" class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono text-right focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">VAT / Tax ({{ $currencySymbol ?? '৳' }})</label>
                            <input type="number" step="0.01" min="0" x-model="createForm.vat_tax" class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono text-right focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Grand Total ({{ $currencySymbol ?? '৳' }})</label>
                            <div class="px-2.5 py-1.5 bg-slate-200 text-slate-900 font-bold font-mono text-xs rounded-lg text-right" x-text="calculateGrandTotal()"></div>
                        </div>
                    </div>

                    {{-- Payment Action & Due Date --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Action <span class="text-rose-500">*</span></label>
                            <select x-model="createForm.payment_action" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="DUE">Mark as Unpaid / Due</option>
                                <option value="WALLET_DEDUCT">Debit from Sub-ISP Prepaid Wallet</option>
                                <option value="CASH">Paid via Cash</option>
                                <option value="BANK">Paid via Bank Wire / Transfer</option>
                                <option value="BKASH">Paid via bKash</option>
                                <option value="NAGAD">Paid via Nagad</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Due Date</label>
                            <input type="date" x-model="createForm.due_date" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Invoice Notes / Remarks</label>
                        <textarea x-model="createForm.notes" rows="2" placeholder="e.g. Core fiber line maintenance charges for north segment..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" @click="showCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Issue Wholesale Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: Record Payment Modal --}}
    <div x-show="showPaymentModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showPaymentModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Record Wholesale Payment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Settle invoice via Prepaid Wallet, Bank, or MFS</p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitPayment()">
                <div class="p-4 space-y-3.5 text-xs">
                    {{-- Invoice & Sub-ISP Wallet Snapshot --}}
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Invoice:</span>
                            <span class="font-mono font-bold text-slate-800" x-text="paymentForm.invoice_no"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Sub-ISP:</span>
                            <span class="font-semibold text-slate-800" x-text="paymentForm.reseller_name"></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-200/60 pt-1">
                            <span class="text-slate-500">Total Outstanding Due:</span>
                            <span class="font-mono font-bold text-rose-600" x-text="formatCurrency(paymentForm.due_amount)"></span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-200/60 pt-1 text-[11px]">
                            <span class="text-slate-500">Prepaid Wallet Balance:</span>
                            <span class="font-mono font-bold" 
                                  :class="paymentForm.available_wallet >= paymentForm.amount ? 'text-emerald-700' : 'text-amber-700'" 
                                  x-text="formatCurrency(paymentForm.available_wallet)"></span>
                        </div>
                    </div>

                    {{-- Wallet Balance Warning if WALLET_DEDUCT is selected and insufficient --}}
                    <div x-show="paymentForm.payment_method === 'WALLET_DEDUCT' && paymentForm.amount > paymentForm.available_wallet" 
                         x-cloak 
                         class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] space-y-1.5">
                        <div class="flex items-center gap-1.5 font-semibold text-amber-800">
                            <i class="fas fa-triangle-exclamation text-amber-600"></i>
                            <span>Insufficient Sub-ISP Wallet Balance</span>
                        </div>
                        <p class="text-slate-600 leading-tight">
                            Available in wallet: <strong class="font-mono text-slate-800" x-text="formatCurrency(paymentForm.available_wallet)"></strong>. Required: <strong class="font-mono text-slate-800" x-text="formatCurrency(paymentForm.amount)"></strong>.
                        </p>
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" 
                                    @click="paymentForm.amount = paymentForm.available_wallet" 
                                    x-show="paymentForm.available_wallet > 0"
                                    class="px-2 py-1 rounded bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-[10px] transition cursor-pointer">
                                Pay Available (<span x-text="formatCurrency(paymentForm.available_wallet)"></span>)
                            </button>
                            <button type="button" 
                                    @click="paymentForm.payment_method = 'CASH'" 
                                    class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[10px] transition cursor-pointer">
                                Switch to Cash / Direct Collection
                            </button>
                        </div>
                    </div>

                    {{-- Payment Amount --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="1" :max="paymentForm.due_amount" x-model="paymentForm.amount" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono font-bold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select x-model="paymentForm.payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="CASH">Cash Collection (Direct External Settlement)</option>
                            <option value="BANK">Bank Wire / Deposit</option>
                            <option value="BKASH">bKash (MFS Transfer)</option>
                            <option value="NAGAD">Nagad (MFS Transfer)</option>
                            <option value="CHEQUE">Bank Cheque</option>
                            <option value="WALLET_DEDUCT">Debit from Sub-ISP Prepaid Wallet</option>
                        </select>
                    </div>

                    {{-- Transaction Ref --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction Ref / Slip No</label>
                        <input type="text" x-model="paymentForm.transaction_ref" placeholder="e.g. TXN-8938493 or Cheque #004" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Remarks</label>
                        <input type="text" x-model="paymentForm.notes" placeholder="Optional payment notes" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Confirm Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: Official Wholesale Invoice Printable Modal --}}
    <div x-show="showPrintModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showPrintModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-3xl max-h-[95vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0 no-print">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Official Wholesale Invoice Preview</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Ready for corporate print or PDF dispatch</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="printInvoiceArea()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-[11px]"></i>
                        <span>Print Invoice</span>
                    </button>
                    <button type="button" @click="showPrintModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Printable Invoice Body --}}
            <div class="p-6 overflow-y-auto flex-1 bg-slate-50/30" id="printableInvoiceArea">
                <template x-if="viewData">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-6 text-slate-800">
                        {{-- Top Corporate Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                            <div>
                                <h2 class="text-lg font-black text-cyan-900 tracking-tight" x-text="viewData.tenant.name"></h2>
                                <p class="text-xs text-slate-600" x-text="viewData.tenant.address"></p>
                                <p class="text-xs text-slate-600">Phone: <span x-text="viewData.tenant.phone"></span> | Email: <span x-text="viewData.tenant.email"></span></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400 block">WHOLESALE INVOICE</span>
                                <span class="text-base font-black font-mono text-cyan-800 block" x-text="viewData.invoice.invoice_no"></span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border mt-1" :class="viewData.invoice.status_badge.class">
                                    <span x-text="viewData.invoice.status_badge.label"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Bill-To & Invoice Metadata --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Billed To (Sub-ISP Reseller):</span>
                                <p class="font-bold text-slate-800 text-sm" x-text="viewData.invoice.reseller.name"></p>
                                <p class="text-slate-600">Code: <span class="font-mono font-semibold" x-text="viewData.invoice.reseller.code"></span> | Prefix: <span class="font-mono" x-text="viewData.invoice.reseller.prefix"></span></p>
                                <p class="text-slate-600">Attn: <span x-text="viewData.invoice.reseller.contact_person"></span> (<span x-text="viewData.invoice.reseller.mobile"></span>)</p>
                                <p class="text-slate-600" x-text="viewData.invoice.reseller.address"></p>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Invoice Date:</span>
                                    <span class="font-mono font-semibold text-slate-700" x-text="viewData.invoice.created_at"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Billing Month:</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="viewData.invoice.billing_month"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Billing Period:</span>
                                    <span class="font-mono text-slate-700"><span x-text="viewData.invoice.period_start"></span> to <span x-text="viewData.invoice.period_end"></span></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Payment Due Date:</span>
                                    <span class="font-mono font-bold text-rose-600" x-text="viewData.invoice.due_date"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Itemized Table --}}
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-bold">
                                    <tr>
                                        <th class="p-2.5 text-center w-10">#</th>
                                        <th class="p-2.5 text-left">Service Description / Line Item</th>
                                        <th class="p-2.5 text-right w-20">Quantity</th>
                                        <th class="p-2.5 text-left w-20">Unit</th>
                                        <th class="p-2.5 text-right w-28">Rate ({{ $currencySymbol ?? '৳' }})</th>
                                        <th class="p-2.5 text-right w-32">Total ({{ $currencySymbol ?? '৳' }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="(item, idx) in viewData.invoice.item_details" :key="idx">
                                        <tr>
                                            <td class="p-2.5 text-center font-mono text-slate-500" x-text="idx + 1"></td>
                                            <td class="p-2.5 font-medium text-slate-800" x-text="item.description"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-700" x-text="item.qty"></td>
                                            <td class="p-2.5 text-slate-600" x-text="item.unit || 'Unit'"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-700" x-text="formatNumber(item.unit_price)"></td>
                                            <td class="p-2.5 text-right font-mono font-bold text-slate-900" x-text="formatNumber(item.total)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals Summary --}}
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="w-full sm:w-1/2 p-3 bg-slate-50 rounded-lg border border-slate-200 text-[11px] space-y-1">
                                <span class="font-bold text-slate-700 block">Bank Transfer Details &amp; Instructions:</span>
                                <p class="text-slate-600">Please settle invoice dues via Bank Wire or Sub-ISP Wallet before the due date.</p>
                                <p class="text-slate-600 font-mono">Invoice Notes: <span x-text="viewData.invoice.notes"></span></p>
                            </div>

                            <div class="w-full sm:w-1/2 max-w-xs space-y-1.5 text-xs">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal:</span>
                                    <span class="font-mono font-bold" x-text="formatCurrency(viewData.invoice.subtotal)"></span>
                                </div>
                                <div class="flex justify-between text-slate-600" x-show="viewData.invoice.discount > 0">
                                    <span>Discount:</span>
                                    <span class="font-mono text-emerald-600">- <span x-text="formatCurrency(viewData.invoice.discount)"></span></span>
                                </div>
                                <div class="flex justify-between text-slate-600" x-show="viewData.invoice.vat_tax > 0">
                                    <span>VAT / Tax:</span>
                                    <span class="font-mono" x-text="formatCurrency(viewData.invoice.vat_tax)"></span>
                                </div>
                                <div class="flex justify-between text-slate-900 font-bold border-t border-slate-200 pt-1.5 text-sm">
                                    <span>Grand Total:</span>
                                    <span class="font-mono text-cyan-900" x-text="formatCurrency(viewData.invoice.amount)"></span>
                                </div>
                                <div class="flex justify-between text-emerald-700 font-semibold">
                                    <span>Paid Amount:</span>
                                    <span class="font-mono" x-text="formatCurrency(viewData.invoice.paid_amount)"></span>
                                </div>
                                <div class="flex justify-between text-rose-700 font-bold border-t border-slate-200/80 pt-1">
                                    <span>Balance Due:</span>
                                    <span class="font-mono" x-text="formatCurrency(viewData.invoice.due_amount)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Signatures --}}
                        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs text-slate-500">
                            <div>
                                <div class="border-b border-slate-300 w-36 mx-auto mb-1"></div>
                                <span>Prepared By (<span x-text="viewData.invoice.creator"></span>)</span>
                            </div>
                            <div>
                                <div class="border-b border-slate-300 w-36 mx-auto mb-1"></div>
                                <span>Authorized Signature &amp; Stamp</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function handlePeriodChange(val) {
        const fromInput = document.getElementById('dateFromInput');
        const toInput = document.getElementById('dateToInput');
        if (!fromInput || !toInput) return;

        const today = new Date();
        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        if (val === 'today') {
            fromInput.value = formatDate(today);
            toInput.value = formatDate(today);
        } else if (val === 'yesterday') {
            const y = new Date();
            y.setDate(y.getDate() - 1);
            fromInput.value = formatDate(y);
            toInput.value = formatDate(y);
        } else if (val === 'this_week') {
            const start = new Date(today);
            const day = start.getDay();
            const diff = start.getDate() - day + (day === 0 ? -6 : 1);
            start.setDate(diff);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'this_month') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'last_month') {
            const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const end = new Date(today.getFullYear(), today.getMonth(), 0);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (val === 'all') {
            fromInput.value = '';
            toInput.value = '';
        }
    }

    function wholesaleBillingManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showBatchModal: false,
            showCreateModal: false,
            showPaymentModal: false,
            showPrintModal: false,
            isSubmitting: false,
            viewData: null,

            batchForm: {
                billing_month: '{{ date("Y-m") }}',
                reseller_id: 'all',
                due_date: '{{ date("Y-m-10") }}',
                auto_deduct_wallet: true
            },

            createForm: {
                reseller_id: '',
                type: 'BANDWIDTH_WHOLESALE',
                billing_month: '{{ date("Y-m") }}',
                due_date: '{{ date("Y-m-10") }}',
                discount: 0,
                vat_tax: 0,
                payment_action: 'DUE',
                notes: '',
                items: [
                    { description: 'Dedicated Bandwidth Trunk (CIR 1:1)', qty: 50, unit: 'Mbps', unit_price: 120 }
                ]
            },

            paymentForm: {
                invoice_id: null,
                invoice_no: '',
                reseller_name: '',
                due_amount: 0,
                amount: 0,
                payment_method: 'WALLET_DEDUCT',
                transaction_ref: '',
                notes: ''
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 200;
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

            openBatchGenerateModal() {
                this.showBatchModal = true;
            },

            openCreateModal() {
                this.showCreateModal = true;
            },

            addLineItem() {
                this.createForm.items.push({
                    description: '',
                    qty: 1,
                    unit: 'Unit',
                    unit_price: 0
                });
            },

            removeLineItem(index) {
                if (this.createForm.items.length > 1) {
                    this.createForm.items.splice(index, 1);
                }
            },

            calculateGrandTotal() {
                let sub = 0;
                this.createForm.items.forEach(i => {
                    sub += (parseFloat(i.qty) || 0) * (parseFloat(i.unit_price) || 0);
                });
                let disc = parseFloat(this.createForm.discount) || 0;
                let vat = parseFloat(this.createForm.vat_tax) || 0;
                let grand = Math.max(0, sub - disc + vat);
                return this.formatNumber(grand);
            },

            openPaymentModal(inv) {
                const wallet = parseFloat(inv.reseller?.wallet_balance || 0);
                const credit = parseFloat(inv.reseller?.credit_limit || 0);
                const available = wallet + credit;
                const due = parseFloat(inv.due_amount || 0);
                const defaultMethod = (available >= due && due > 0) ? 'WALLET_DEDUCT' : 'CASH';

                this.paymentForm = {
                    invoice_id: inv.id,
                    invoice_no: inv.invoice_no,
                    reseller_name: inv.reseller?.name || 'Sub-ISP',
                    wallet_balance: wallet,
                    credit_limit: credit,
                    available_wallet: available,
                    due_amount: due,
                    amount: due,
                    payment_method: defaultMethod,
                    transaction_ref: '',
                    notes: ''
                };
                this.showPaymentModal = true;
            },

            async submitBatchGenerate() {
                this.isSubmitting = true;
                try {
                    const res = await fetch("{{ route('tenant.finance.wholesale-billing.generate-monthly') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.batchForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showBatchModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Batch Invoices Generated',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Generation Failed',
                            text: data.message || 'Batch billing failed.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error generating bills: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitCreateInvoice() {
                this.isSubmitting = true;
                try {
                    const res = await fetch("{{ route('tenant.finance.wholesale-billing.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.createForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showCreateModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Invoice Created',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Creation Failed',
                            text: data.message || 'Invoice creation failed.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error creating invoice: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitPayment() {
                this.isSubmitting = true;
                try {
                    const url = "{{ url('admin/finance/wholesale-billing') }}/" + this.paymentForm.invoice_id + "/pay";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.paymentForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showPaymentModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Recorded',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Failed',
                            text: data.message || 'Payment recording failed.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error recording payment: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async viewInvoice(id) {
                try {
                    const url = "{{ url('admin/finance/wholesale-billing') }}/" + id;
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.viewData = data;
                        this.showPrintModal = true;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not load invoice data.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error fetching invoice details: ' + e.message
                    });
                }
            },

            async cancelInvoice(id) {
                const result = await Swal.fire({
                    title: 'Cancel Wholesale Invoice?',
                    text: 'Are you sure you want to cancel this wholesale invoice?',
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
                    const url = "{{ url('admin/finance/wholesale-billing') }}/" + id + "/cancel";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Invoice Cancelled',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to cancel invoice.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error cancelling invoice: ' + e.message
                    });
                }
            },

            async deleteInvoice(id) {
                const result = await Swal.fire({
                    title: 'Delete Wholesale Invoice?',
                    text: 'Are you sure you want to permanently delete this invoice? This action cannot be undone.',
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
                    const url = "{{ url('admin/finance/wholesale-billing') }}/" + id;
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Invoice Deleted',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to delete invoice.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error deleting invoice: ' + e.message
                    });
                }
            },

            copyInvoiceNo(no) {
                navigator.clipboard.writeText(no);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Invoice number copied: ' + no,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            },

            printInvoiceArea() {
                window.print();
            },

            formatNumber(num) {
                return (parseFloat(num) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            formatCurrency(amount) {
                const sym = '{{ $currencySymbol ?? "৳" }}';
                return sym + ' ' + this.formatNumber(amount);
            }
        };
    }
</script>
@endpush
