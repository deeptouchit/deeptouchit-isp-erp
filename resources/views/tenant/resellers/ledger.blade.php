@extends('tenant.layouts.app')

@section('title', 'Reseller Adjustment Ledger - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerLedgerManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Reseller Adjustment Ledger</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-100 text-cyan-800 border border-cyan-200">
                    {{ number_format($transactions->total()) }} Transactions
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.resellers.wallets') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wallet text-slate-500 text-xs"></i>
                <span>Wallets &amp; Credit</span>
            </a>
            <a href="{{ route('tenant.resellers.recharge') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-clock-rotate-left text-slate-500 text-xs"></i>
                <span>Recharge History</span>
            </a>
            <button type="button" 
                    @click="openTopupModal()"
                    class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                <span>+ Adjust Balance</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Recharges -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Recharges</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalCreditRecharges)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-arrow-down-left"></i>
            </div>
        </div>

        <!-- Card 2: Total Deductions -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Deductions</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600 truncate">@currency($totalDebits)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-arrow-up-right"></i>
            </div>
        </div>

        <!-- Card 3: Month Volume -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Month Volume</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($thisMonthVolume)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <!-- Card 4: Total Trx Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Trx Count</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalTransactionsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

        <!-- Card 5: Credit Transactions Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Credit Count</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($creditCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-plus"></i>
            </div>
        </div>

        <!-- Card 6: Debit Transactions Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Debit Count</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600 truncate">{{ number_format($debitCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-minus"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        
        <form method="GET" action="{{ route('tenant.resellers.ledger') }}" class="space-y-2.5">
            {{-- Tier 1: Primary Filter Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search Trx ID, Reference, Reseller Partner, Notes..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Reseller Dropdown --}}
                <div class="w-full md:w-52 flex-shrink-0">
                    <select name="reseller_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Resellers</option>
                        @foreach($allResellers as $res)
                            <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Dropdown --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Types</option>
                        <option value="CREDIT" {{ request('type') === 'CREDIT' ? 'selected' : '' }}>Credit (+Top-up)</option>
                        <option value="DEBIT" {{ request('type') === 'DEBIT' ? 'selected' : '' }}>Debit (-Deduction)</option>
                    </select>
                </div>

                {{-- Pagination Selector (per_page) --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        @foreach([15, 20, 30, 50, 100, 250, 500] as $size)
                            <option value="{{ $size }}" {{ (int)request('per_page', $perPage) === $size ? 'selected' : '' }}>
                                {{ $size }} / page
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'">
                    <i class="fas fa-sliders text-[10px]"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Filter & Reset Action Buttons (Strict Sequence: Filter then Reset) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.resellers.ledger') }}" 
                       class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Payment Method Filter --}}
                <div class="w-full sm:w-48">
                    <select name="payment_method" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Payment Methods</option>
                        @foreach($availableMethods as $method)
                            <option value="{{ $method }}" {{ request('payment_method') === $method ? 'selected' : '' }}>
                                {{ $method }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="w-full sm:w-44 flex items-center gap-1">
                    <span class="text-[11px] text-slate-500 whitespace-nowrap">From:</span>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}" 
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 cursor-pointer">
                </div>

                {{-- Date To --}}
                <div class="w-full sm:w-44 flex items-center gap-1">
                    <span class="text-[11px] text-slate-500 whitespace-nowrap">To:</span>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}" 
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 cursor-pointer">
                </div>

                @if($activeMoreCount > 0)
                    <a href="{{ route('tenant.resellers.ledger', request()->except(['payment_method', 'date_from', 'date_to'])) }}" 
                       class="h-[34px] py-0 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg text-xs transition inline-flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-times text-[10px]"></i>
                        <span>Clear Advanced</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 4. Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Trx ID</th>
                        <th class="text-center font-semibold">Date &amp; Time</th>
                        <th class="text-center font-semibold">Reseller Partner</th>
                        <th class="text-center font-semibold">Type</th>
                        <th class="text-center font-semibold">Amount</th>
                        <th class="text-center font-semibold">Balance After</th>
                        <th class="text-center font-semibold">Method</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $transactions->firstItem() + $index }}
                            </td>

                            <!-- 2. Trx ID -->
                            <td class="text-center font-mono text-cyan-700 font-semibold whitespace-nowrap">
                                <button type="button" 
                                        @click="viewVoucher({{ Js::from($trx) }})" 
                                        class="hover:underline cursor-pointer"
                                        title="View Voucher">
                                    {{ $trx->trx_id }}
                                </button>
                            </td>

                            <!-- 3. Date & Time -->
                            <td class="text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $trx->created_at->format('d M Y, h:i A') }}
                            </td>

                            <!-- 4. Reseller Partner -->
                            <td class="text-left font-medium text-slate-900 whitespace-nowrap">
                                {{ $trx->reseller?->name ?? 'N/A' }}
                            </td>

                            <!-- 5. Type -->
                            <td class="text-center whitespace-nowrap">
                                @if($trx->type === 'CREDIT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-arrow-down-left text-[8px]"></i>
                                        <span>Credit</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-arrow-up-right text-[8px]"></i>
                                        <span>Debit</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Amount -->
                            <td class="text-right font-mono font-bold whitespace-nowrap">
                                <span class="{{ $trx->type === 'CREDIT' ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $trx->type === 'CREDIT' ? '+' : '-' }}@currency($trx->amount)
                                </span>
                            </td>

                            <!-- 7. Balance After -->
                            <td class="text-right font-mono font-semibold text-slate-800 whitespace-nowrap">
                                @currency($trx->balance_after)
                            </td>

                            <!-- 8. Method -->
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $trx->payment_method }}
                                </span>
                            </td>

                            <!-- 9. Action (3-Dot) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($trx) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-cyan-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-2xs">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No Ledger Transactions Found</h3>
                                    <p class="text-[11px] text-slate-500">All top-ups, recharge requests, and balance adjustments will be recorded in this ledger.</p>
                                    <div class="pt-1">
                                        <button type="button" 
                                                @click="openTopupModal()" 
                                                class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                                            <span>+ Adjust Balance</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         :style="menuPos"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const trx = activeMenu; activeMenu = null; viewVoucher(trx)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-file-invoice text-cyan-600 w-3.5 text-[11px]"></i>
                <span>View Trx Voucher</span>
            </button>
            <a :href="`/admin/resellers/${activeMenu?.reseller_id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-user-gear text-slate-500 w-3.5 text-[11px]"></i>
                <span>Reseller Profile</span>
            </a>
            <a href="{{ route('tenant.resellers.wallets') }}" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-wallet text-cyan-600 w-3.5 text-[11px]"></i>
                <span>Wallet Balance</span>
            </a>
        </div>
    </div>

    {{-- 6. Production-Grade Natural Modals (AGENTS.md Rule 3) --}}
    {{-- Modal 1: Trx Voucher / Receipt Modal --}}
    <div x-show="voucherModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="voucherModal.open = false">
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Transaction Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="voucherModal.data?.trx_id"></p>
                    </div>
                </div>
                <button type="button" @click="voucherModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Printable Voucher Body -->
            <div id="printableVoucher" class="p-4 space-y-3.5 text-xs">
                <!-- Amount Highlight Card -->
                <div class="p-3.5 rounded-xl border text-center space-y-1"
                     :class="voucherModal.data?.type === 'CREDIT' ? 'bg-emerald-50/70 border-emerald-200' : 'bg-rose-50/70 border-rose-200'">
                    <span class="text-[10px] uppercase font-semibold block tracking-wider"
                          :class="voucherModal.data?.type === 'CREDIT' ? 'text-emerald-700' : 'text-rose-700'"
                          x-text="voucherModal.data?.type === 'CREDIT' ? 'Wallet Credit (+Topup)' : 'Wallet Debit (-Deduction)'"></span>
                    <span class="text-xl font-bold font-mono block leading-tight"
                          :class="voucherModal.data?.type === 'CREDIT' ? 'text-emerald-800' : 'text-rose-800'"
                          x-text="(voucherModal.data?.type === 'CREDIT' ? '+' : '-') + formatCurrency(voucherModal.data?.amount)"></span>
                </div>

                <!-- Voucher Meta Details -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Partner:</span>
                        <span class="font-semibold text-slate-800" x-text="voucherModal.data?.reseller?.name || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Code:</span>
                        <span class="font-mono text-slate-800" x-text="voucherModal.data?.reseller?.code || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Payment Method:</span>
                        <span class="font-medium text-slate-800" x-text="voucherModal.data?.payment_method || 'Cash'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reference No:</span>
                        <span class="font-mono text-slate-800" x-text="voucherModal.data?.reference_no || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Balance Before:</span>
                        <span class="font-mono text-slate-600" x-text="formatCurrency(voucherModal.data?.balance_before)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Balance After:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatCurrency(voucherModal.data?.balance_after)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Transaction Date:</span>
                        <span class="font-mono text-slate-700" x-text="formatDate(voucherModal.data?.created_at)"></span>
                    </div>
                    <div class="py-1">
                        <span class="text-slate-500 block mb-0.5">Remarks:</span>
                        <span class="text-slate-700 italic" x-text="voucherModal.data?.description || 'N/A'"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="voucherModal.open = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" @click="printVoucher()" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Voucher</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal 2: Quick Top-Up / Adjustment Modal --}}
    <div x-show="showTopupModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="showTopupModal = false">
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Wallet Top-up &amp; Balance Adjustment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Recharge or debit reseller balance</p>
                    </div>
                </div>
                <button type="button" @click="showTopupModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="submitTopup()" class="p-4 space-y-3 text-xs">
                <!-- Reseller Picker -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select Reseller Partner <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="topupForm.reseller_id" 
                            required 
                            class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500">
                        <option value="">-- Choose Reseller --</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }}) — Balance: {{ $currencySymbol ?? '৳' }} {{ number_format($r->wallet_balance, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Transaction Type Toggle -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Adjustment Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" 
                                @click="topupForm.type = 'CREDIT'"
                                class="h-[34px] py-0 px-3 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer"
                                :class="topupForm.type === 'CREDIT' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-down-left text-emerald-600 text-[10px]"></i>
                            <span>Credit (Add / Top-up)</span>
                        </button>
                        <button type="button" 
                                @click="topupForm.type = 'DEBIT'"
                                class="h-[34px] py-0 px-3 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer"
                                :class="topupForm.type === 'DEBIT' ? 'bg-rose-50 border-rose-300 text-rose-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-up-right text-rose-600 text-[10px]"></i>
                            <span>Debit (Deduct / Correction)</span>
                        </button>
                    </div>
                </div>

                <!-- Amount & Payment Method -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               x-model="topupForm.amount" 
                               required 
                               placeholder="e.g. 5000" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="topupForm.payment_method" 
                                required 
                                class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500">
                            <option value="Cash">Cash Deposit</option>
                            <option value="Bank Transfer">Bank Transfer / EFT</option>
                            <option value="bKash">bKash Merchant</option>
                            <option value="Nagad">Nagad Direct</option>
                            <option value="Rocket">Rocket Direct</option>
                            <option value="Cheque">Cheque</option>
                            <option value="System Adjustment">System Adjustment</option>
                        </select>
                    </div>
                </div>

                <!-- Reference & Notes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Reference No / Trx Voucher
                        </label>
                        <input type="text" 
                               x-model="topupForm.reference_no" 
                               placeholder="e.g. BK-987654 / Bank Slip" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Remarks / Description
                        </label>
                        <input type="text" 
                               x-model="topupForm.description" 
                               placeholder="e.g. Month initial top-up" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500">
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showTopupModal = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Processing...' : 'Confirm Transaction'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerLedgerManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        submitting: false,

        voucherModal: {
            open: false,
            data: null
        },
        showTopupModal: false,

        topupForm: {
            reseller_id: '',
            type: 'CREDIT',
            amount: '',
            payment_method: 'Cash',
            reference_no: '',
            description: ''
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 120;
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

        viewVoucher(item) {
            this.voucherModal.data = item;
            this.voucherModal.open = true;
        },

        openTopupModal() {
            this.topupForm.reseller_id = '{{ $allResellers->first()?->id ?? "" }}';
            this.topupForm.type = 'CREDIT';
            this.topupForm.amount = '';
            this.topupForm.reference_no = '';
            this.topupForm.description = '';
            this.showTopupModal = true;
        },

        async submitTopup() {
            if (!this.topupForm.reseller_id || !this.topupForm.amount) return;
            this.submitting = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.wallets.adjust') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.topupForm)
                });
                const data = await response.json();
                if (data.success) {
                    this.showTopupModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Transaction Successful',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Transaction Failed',
                        text: data.message || 'Error executing balance adjustment.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error or server unreachable.'
                });
            } finally {
                this.submitting = false;
            }
        },

        printVoucher() {
            const printContent = document.getElementById('printableVoucher').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Transaction Voucher - ${this.voucherModal.data?.trx_id || ''}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #1e293b; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .font-mono { font-family: monospace; }
                        .border { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
                        .flex { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
                    </style>
                </head>
                <body>
                    <h2 class="text-center" style="margin-bottom: 4px;">{{ $tenant->company_name ?? $tenant->name }}</h2>
                    <p class="text-center" style="font-size: 12px; color: #64748b; margin-top: 0;">Reseller Wallet Transaction Voucher</p>
                    <div style="max-width: 400px; margin: 20px auto;">
                        ${printContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        },

        formatCurrency(val) {
            const n = Number(val || 0);
            return '{{ $currencySymbol ?? "৳" }} ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatDate(val) {
            if (!val) return 'N/A';
            const d = new Date(val);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
        }
    }
}
</script>
@endpush
