@extends('tenant.layouts.app')

@section('title', 'Online Gateway Transactions - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="gatewayTransactionsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strict Rule: Title + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-1 border-b border-slate-200/80">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center font-bold text-sm shadow-2xs">
                <i class="fas fa-network-wired"></i>
            </div>
            <h1 class="text-base font-bold text-slate-800 tracking-tight">Online Gateway Transactions</h1>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <div class="hidden sm:flex items-center gap-1 px-2.5 py-1 bg-slate-100 rounded-lg border border-slate-200 text-[11px] text-slate-600 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>bKash, Nagad, SSLCommerz PGWs Active</span>
            </div>

            <a href="{{ route('tenant.finance.gateway-transactions.export', request()->all()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold shadow-2xs transition">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Success Volume --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Success Volume</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($totalSuccessVolume)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 2: Cleared Transactions Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Cleared Count</span>
                <span class="text-[13px] font-bold font-mono text-teal-700 leading-tight block truncate">{{ number_format($successCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 3: Gateway Fees --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">PGW Fees</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">@currency($totalGatewayFees)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        {{-- Card 4: Net Revenue Settled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Net Settled</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">@currency($netSettledRevenue)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 border border-cyan-100 text-cyan-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 5: In-Flight / Pending --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">In-Flight / Pending</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">{{ number_format($pendingCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 6: Failed / Dropped --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Failed / Dropped</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">{{ number_format($failedCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.finance.gateway-transactions') }}" class="space-y-2">
            <!-- Row 1: Search Box, Period Preset & Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2">
                <!-- Search Box -->
                <div class="md:col-span-4 relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="TrxID, Gateway Ref, Payer Phone/Name..." 
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

            <!-- Row 2: Gateway, Purpose, Status, Month, Per Page & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 pt-1 border-t border-slate-100">
                <!-- Gateway Channel Filter -->
                <div class="md:col-span-2">
                    <select name="gateway" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all">All Gateways</option>
                        <option value="bkash" {{ $selectedGateway === 'bkash' ? 'selected' : '' }}>bKash PGW</option>
                        <option value="nagad" {{ $selectedGateway === 'nagad' ? 'selected' : '' }}>Nagad Direct</option>
                        <option value="rocket" {{ $selectedGateway === 'rocket' ? 'selected' : '' }}>Rocket (DBBL)</option>
                        <option value="sslcommerz" {{ $selectedGateway === 'sslcommerz' ? 'selected' : '' }}>SSLCommerz</option>
                        <option value="shurjopay" {{ $selectedGateway === 'shurjopay' ? 'selected' : '' }}>ShurjoPay</option>
                        <option value="stripe" {{ $selectedGateway === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    </select>
                </div>

                <!-- Purpose / Category Filter -->
                <div class="md:col-span-3">
                    <select name="purpose" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedPurpose === 'all' ? 'selected' : '' }}>All Categories</option>
                        <option value="CUSTOMER_BILL" {{ $selectedPurpose === 'CUSTOMER_BILL' ? 'selected' : '' }}>Subscriber Monthly Bill</option>
                        <option value="CUSTOMER_RECHARGE" {{ $selectedPurpose === 'CUSTOMER_RECHARGE' ? 'selected' : '' }}>Prepaid Recharge</option>
                        <option value="RESELLER_TOPUP" {{ $selectedPurpose === 'RESELLER_TOPUP' ? 'selected' : '' }}>Sub-ISP Wallet Recharge</option>
                        <option value="RESELLER_INVOICE" {{ $selectedPurpose === 'RESELLER_INVOICE' ? 'selected' : '' }}>Wholesale Bandwidth Invoice</option>
                        <option value="MANUAL" {{ $selectedPurpose === 'MANUAL' ? 'selected' : '' }}>Manual Web Payment</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="md:col-span-2">
                    <select name="status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="SUCCESS" {{ $selectedStatus === 'SUCCESS' ? 'selected' : '' }}>Success / Cleared</option>
                        <option value="PENDING" {{ $selectedStatus === 'PENDING' ? 'selected' : '' }}>Pending / Processing</option>
                        <option value="FAILED" {{ $selectedStatus === 'FAILED' ? 'selected' : '' }}>Failed</option>
                        <option value="CANCELLED" {{ $selectedStatus === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                        <option value="REFUNDED" {{ $selectedStatus === 'REFUNDED' ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>

                <!-- Month Filter -->
                <div class="md:col-span-2">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth !== 'all' ? $selectedMonth : '' }}" 
                           title="Billing Month"
                           class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                </div>

                <!-- Per Page Pagination -->
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
                    <a href="{{ route('tenant.finance.gateway-transactions') }}" 
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
                        <th class="w-40">Transaction ID</th>
                        <th class="w-36">Channel</th>
                        <th>Subscriber / Reseller</th>
                        <th class="text-right w-28">Amount</th>
                        <th class="text-center w-28">Status</th>
                        <th class="w-36">Date &amp; Time</th>
                        <th class="no-sort w-12 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                        <tr>
                            {{-- Row index --}}
                            <td class="text-center font-mono text-slate-500">{{ $transactions->firstItem() + $index }}</td>

                            {{-- Transaction ID (Clickable to open View Details Modal) --}}
                            <td>
                                <button type="button" @click="viewDetails({{ $trx->id }})" class="font-mono font-bold text-cyan-800 hover:text-cyan-600 hover:underline cursor-pointer">
                                    {{ $trx->transaction_id }}
                                </button>
                            </td>

                            {{-- Gateway Channel Badge --}}
                            <td>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-semibold border {{ $trx->gateway_badge['class'] }}">
                                    <i class="fas {{ $trx->gateway_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $trx->gateway_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Subscriber / Reseller Party --}}
                            <td>
                                @if($trx->customer)
                                    <span class="font-semibold text-slate-800">{{ $trx->customer->name }}</span>
                                @elseif($trx->reseller)
                                    <span class="font-semibold text-purple-800">{{ $trx->reseller->name }}</span>
                                @else
                                    <span class="text-slate-500">Direct Web User</span>
                                @endif
                            </td>

                            {{-- Gross Amount --}}
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($trx->amount)
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $trx->status_badge['class'] }}">
                                    <i class="fas {{ $trx->status_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $trx->status_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Date & Time --}}
                            <td class="font-mono text-slate-600 text-[11px]">
                                {{ $trx->created_at->format('d M, Y h:i A') }}
                            </td>

                            {{-- Action 3-Dot Button --}}
                            <td class="text-center">
                                <button type="button" @click.stop="toggleMenu({{ $trx->toJson() }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-700 inline-flex items-center justify-center transition cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                <i class="fas fa-network-wired text-3xl mb-2 text-slate-300 block"></i>
                                <span class="text-xs font-medium">No online gateway transactions recorded yet.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Pagination --}}
        @if($transactions->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/60 flex items-center justify-between">
                <div class="text-[11px] text-slate-500">
                    Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->links() }}
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
        
        {{-- Group 1: Diagnostics & Audit --}}
        <div class="py-1">
            <button type="button" @click="viewDetails(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-eye w-3.5 text-cyan-600 text-[11px]"></i>
                <span>View Full Details</span>
            </button>
            <button type="button" @click="copyTrxId(activeMenu.transaction_id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-copy w-3.5 text-slate-500 text-[11px]"></i>
                <span>Copy Internal Trx ID</span>
            </button>
            <template x-if="activeMenu && activeMenu.gateway_trx_id">
                <button type="button" @click="copyTrxId(activeMenu.gateway_trx_id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                    <i class="fas fa-receipt w-3.5 text-indigo-500 text-[11px]"></i>
                    <span>Copy Gateway TrxID</span>
                </button>
            </template>
        </div>

        {{-- Group 2: Verification & Reconciliation --}}
        <div class="py-1">
            <template x-if="activeMenu && (activeMenu.status === 'PENDING' || activeMenu.status === 'FAILED')">
                <button type="button" @click="verifyTransaction(activeMenu.id); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 flex items-center gap-2 text-emerald-700 font-semibold transition cursor-pointer">
                    <i class="fas fa-rotate w-3.5 text-emerald-600 text-[11px]"></i>
                    <span>Verify &amp; Re-query PGW</span>
                </button>
            </template>

            <template x-if="activeMenu && activeMenu.status === 'SUCCESS'">
                <button type="button" @click="openRefundModal(activeMenu); activeMenu = null;" class="w-full px-3 py-1.5 text-left hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
                    <i class="fas fa-rotate-left w-3.5 text-rose-500 text-[11px]"></i>
                    <span>Process Refund / Reversal</span>
                </button>
            </template>
        </div>
    </div>

    {{-- MODAL 1: Full Transaction Telemetry & Detailed Audit Modal --}}
    <div x-show="showDetailModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showDetailModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-waveform"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Online Gateway Transaction Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Real-time gateway payload, fee settlement &amp; party audit</p>
                    </div>
                </div>
                <button type="button" @click="showDetailModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 text-xs overflow-y-auto flex-1">
                <template x-if="detailData">
                    <div class="space-y-4">
                        {{-- 3-Column Financial Metric Cards --}}
                        <div class="grid grid-cols-3 gap-2.5">
                            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-slate-500 block">Gross Amount</span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="formatCurrency(detailData.transaction.amount)"></span>
                            </div>
                            <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-amber-700 block">Gateway Fee</span>
                                <span class="text-sm font-bold font-mono text-amber-800 block" x-text="formatCurrency(detailData.transaction.fee_amount)"></span>
                            </div>
                            <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-200/80 text-center">
                                <span class="text-[10px] font-medium uppercase text-emerald-700 block">Net Settlement</span>
                                <span class="text-sm font-bold font-mono text-emerald-800 block" x-text="formatCurrency(detailData.transaction.net_amount)"></span>
                            </div>
                        </div>

                        {{-- Metadata Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Internal Trx ID:</span>
                                    <span class="font-mono font-bold text-slate-800" x-text="detailData.transaction.transaction_id"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Gateway Trx ID:</span>
                                    <span class="font-mono font-semibold text-slate-800" x-text="detailData.transaction.gateway_trx_id"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Gateway Channel:</span>
                                    <span class="font-semibold text-slate-800 uppercase" x-text="detailData.transaction.gateway"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Category / Purpose:</span>
                                    <span class="text-slate-800 font-medium" x-text="detailData.transaction.purpose_label"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Reference / Invoice:</span>
                                    <span class="font-mono font-semibold text-slate-700" x-text="detailData.transaction.reference_id"></span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Payment Status:</span>
                                    <span class="font-bold uppercase" :class="detailData.transaction.status === 'SUCCESS' ? 'text-emerald-700' : (detailData.transaction.status === 'PENDING' ? 'text-amber-700' : 'text-rose-700')" x-text="detailData.transaction.status"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Payer Account / Phone:</span>
                                    <span class="font-mono text-slate-800 font-semibold" x-text="detailData.transaction.payer_account"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">IP Address:</span>
                                    <span class="font-mono text-slate-700" x-text="detailData.transaction.ip_address"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Timestamp:</span>
                                    <span class="font-mono text-slate-700" x-text="detailData.transaction.created_at"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Gateway Message:</span>
                                    <span class="text-slate-700 truncate max-w-[180px]" x-text="detailData.transaction.status_message" :title="detailData.transaction.status_message"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Linked Subscriber / Reseller Card --}}
                        <template x-if="detailData.transaction.customer">
                            <div class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-200/80">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-800 block mb-1">Subscriber Details:</span>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs" x-text="detailData.transaction.customer.name"></p>
                                        <p class="text-[11px] text-slate-600">ID: <span class="font-mono font-semibold" x-text="detailData.transaction.customer.customer_id"></span> | Phone: <span x-text="detailData.transaction.customer.phone"></span></p>
                                        <p class="text-[11px] text-slate-500" x-text="detailData.transaction.customer.address"></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-100 text-cyan-800">Direct Subscriber</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="detailData.transaction.reseller">
                            <div class="p-3 bg-purple-50/50 rounded-lg border border-purple-200/80">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-800 block mb-1">Sub-ISP Partner Details:</span>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs" x-text="detailData.transaction.reseller.name"></p>
                                        <p class="text-[11px] text-slate-600">Code: <span class="font-mono font-semibold" x-text="detailData.transaction.reseller.code"></span> | Phone: <span x-text="detailData.transaction.reseller.mobile"></span></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-800">Sub-ISP Partner</span>
                                </div>
                            </div>
                        </template>

                        {{-- Raw Gateway JSON Log --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Gateway API Payload &amp; Webhook Telemetry:</span>
                                <button type="button" @click="copyPayload()" class="text-cyan-600 hover:text-cyan-700 text-[10.5px] font-semibold inline-flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-copy"></i> Copy JSON
                                </button>
                            </div>
                            <pre class="bg-slate-900 text-cyan-300 font-mono text-[11px] p-3.5 rounded-lg border border-slate-800 overflow-x-auto max-h-48 whitespace-pre-wrap select-all" x-text="JSON.stringify(detailData.transaction.gateway_payload || { status: detailData.transaction.status, message: detailData.transaction.status_message, channel: detailData.transaction.gateway, trx_id: detailData.transaction.gateway_trx_id }, null, 2)"></pre>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                <template x-if="detailData && (detailData.transaction.status === 'PENDING' || detailData.transaction.status === 'FAILED')">
                    <button type="button" @click="verifyTransaction(detailData.transaction.id); showDetailModal = false;" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-rotate text-[10px]"></i>
                        <span>Re-query Gateway API</span>
                    </button>
                </template>

                <template x-if="detailData && detailData.transaction.status === 'SUCCESS'">
                    <button type="button" @click="openRefundModal(detailData.transaction); showDetailModal = false;" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-medium text-xs px-3.5 py-1.5 rounded-lg transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Refund Transaction</span>
                    </button>
                </template>

                <button type="button" @click="showDetailModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Process Refund / Reversal Modal --}}
    <div x-show="showRefundModal" x-cloak class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.outside="showRefundModal = false" class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Process Transaction Refund</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Reverse cleared transaction and record audit justification</p>
                    </div>
                </div>
                <button type="button" @click="showRefundModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitRefund()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-3 bg-rose-50/60 rounded-lg border border-rose-200/80 space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-rose-700">Transaction ID:</span>
                            <span class="font-mono font-bold text-slate-900" x-text="refundForm.transaction_id"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-rose-700">Refund Amount:</span>
                            <span class="font-mono font-bold text-rose-800" x-text="formatCurrency(refundForm.amount)"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Reason for Refund / Reversal <span class="text-rose-500">*</span></label>
                        <textarea x-model="refundForm.reason" rows="3" required placeholder="e.g. Duplicate customer payment charged via bKash gateway..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showRefundModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-circle-notch fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Confirm Refund</span>
                    </button>
                </div>
            </form>
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

    function gatewayTransactionsManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showDetailModal: false,
            showRefundModal: false,
            isSubmitting: false,
            detailData: null,

            refundForm: {
                id: null,
                transaction_id: '',
                amount: 0,
                reason: ''
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

            async viewDetails(id) {
                try {
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + id;
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.detailData = data;
                        this.showDetailModal = true;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not fetch transaction details.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error: ' + e.message
                    });
                }
            },

            async verifyTransaction(id) {
                const result = await Swal.fire({
                    title: 'Verify Transaction Status?',
                    text: 'Re-query authoritative Payment Gateway for live clearance status?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Verify Now',
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
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + id + "/verify";
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
                            title: 'Status Verified',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Verification Result',
                            text: data.message || 'Transaction could not be verified.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error verifying transaction: ' + e.message
                    });
                }
            },

            openRefundModal(trx) {
                this.refundForm = {
                    id: trx.id,
                    transaction_id: trx.transaction_id,
                    amount: parseFloat(trx.amount) || 0,
                    reason: ''
                };
                this.showRefundModal = true;
            },

            async submitRefund() {
                this.isSubmitting = true;
                try {
                    const url = "{{ url('admin/finance/gateway-transactions') }}/" + this.refundForm.id + "/refund";
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reason: this.refundForm.reason })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showRefundModal = false;
                        await Swal.fire({
                            icon: 'success',
                            title: 'Refund Processed',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Refund Failed',
                            text: data.message || 'Refund processing failed.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error processing refund: ' + e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            copyTrxId(id) {
                navigator.clipboard.writeText(id);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Transaction ID copied: ' + id,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            },

            copyPayload() {
                if (this.detailData?.transaction?.gateway_payload) {
                    navigator.clipboard.writeText(JSON.stringify(this.detailData.transaction.gateway_payload, null, 2));
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Gateway JSON payload copied to clipboard!',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'rounded-xl text-xs font-sans shadow-lg'
                        }
                    });
                }
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
