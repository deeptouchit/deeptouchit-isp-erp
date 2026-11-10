@extends('tenant.layouts.app')

@section('title', 'Reseller Recharge History - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerRechargeManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Reseller Recharge History</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                    {{ number_format($recharges->total()) }} Records
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.resellers.wallets') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wallet text-slate-500 text-xs"></i>
                <span>Wallets &amp; Credit</span>
            </a>
            <a href="{{ route('tenant.resellers.ledger') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-slate-500 text-xs"></i>
                <span>Adjustment Ledger</span>
            </a>
            <button type="button" 
                    @click="openNewRechargeModal()"
                    class="h-[34px] py-0 px-3.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                <span>+ Submit Recharge Entry</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Recharged -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Recharged</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">@currency($totalRechargedEver)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Card 2: Recharged This Month -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">This Month</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">@currency($totalRechargedThisMonth)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <!-- Card 3: Today's Recharge -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Today</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalRechargedToday)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-dollar-to-slot"></i>
            </div>
        </div>

        <!-- Card 4: Pending Approvals -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Pending Approval</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $pendingCount > 0 ? 'text-amber-600' : 'text-slate-700' }} truncate">
                    {{ number_format($pendingCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 5: Online Gateway -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-pink-600">Online PGW</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-pink-700 truncate">{{ number_format($onlinePgwCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-pink-50 text-pink-600 border-pink-100 flex items-center justify-center">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        <!-- Card 6: Bank & Cash -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Bank / Cash</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">{{ number_format($bankAndCashCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        
        <form method="GET" action="{{ route('tenant.resellers.recharge') }}" class="space-y-2.5">
            {{-- Tier 1: Primary Filter Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-purple-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search Recharge #, TrxID, Reseller Partner, Notes..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
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
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 cursor-pointer">
                        <option value="">All Resellers</option>
                        @foreach($allResellers as $res)
                            <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Dropdown --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                        <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                {{-- Pagination Selector (per_page) --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 cursor-pointer">
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
                        :class="showMoreFilters ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'">
                    <i class="fas fa-sliders text-[10px]"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-purple-600 text-white text-[9px] font-bold flex items-center justify-center">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-purple-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Filter & Reset Action Buttons (Strict Sequence: Filter then Reset) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.resellers.recharge') }}" 
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
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 cursor-pointer">
                        <option value="">All Payment Methods</option>
                        <option value="BKASH" {{ request('payment_method') === 'BKASH' ? 'selected' : '' }}>bKash</option>
                        <option value="NAGAD" {{ request('payment_method') === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                        <option value="ROCKET" {{ request('payment_method') === 'ROCKET' ? 'selected' : '' }}>Rocket</option>
                        <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                        <option value="CHEQUE" {{ request('payment_method') === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                        <option value="ONLINE_GATEWAY" {{ request('payment_method') === 'ONLINE_GATEWAY' ? 'selected' : '' }}>Online Gateway</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div class="w-full sm:w-44 flex items-center gap-1">
                    <span class="text-[11px] text-slate-500 whitespace-nowrap">From:</span>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}" 
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-purple-500 cursor-pointer">
                </div>

                {{-- Date To --}}
                <div class="w-full sm:w-44 flex items-center gap-1">
                    <span class="text-[11px] text-slate-500 whitespace-nowrap">To:</span>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}" 
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-purple-500 cursor-pointer">
                </div>

                @if($activeMoreCount > 0)
                    <a href="{{ route('tenant.resellers.recharge', request()->except(['payment_method', 'date_from', 'date_to'])) }}" 
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
                        <th class="text-center font-semibold">Recharge #</th>
                        <th class="text-center font-semibold">Date &amp; Time</th>
                        <th class="text-center font-semibold">Reseller Partner</th>
                        <th class="text-center font-semibold">Amount</th>
                        <th class="text-center font-semibold">Bonus</th>
                        <th class="text-center font-semibold">Total Credited</th>
                        <th class="text-center font-semibold">Method</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recharges as $index => $r)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $recharges->firstItem() + $index }}
                            </td>

                            <!-- 2. Recharge # -->
                            <td class="text-center font-mono text-purple-700 font-semibold whitespace-nowrap">
                                <button type="button" 
                                        @click="viewReceipt({{ $r->id }})" 
                                        class="hover:underline cursor-pointer"
                                        title="View Voucher">
                                    {{ $r->recharge_no }}
                                </button>
                            </td>

                            <!-- 3. Date & Time -->
                            <td class="text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $r->created_at->format('d M Y, h:i A') }}
                            </td>

                            <!-- 4. Reseller Partner -->
                            <td class="text-left font-medium text-slate-900 whitespace-nowrap">
                                {{ $r->reseller?->name ?? 'N/A' }}
                            </td>

                            <!-- 5. Amount -->
                            <td class="text-right font-mono font-medium text-slate-800 whitespace-nowrap">
                                @currency($r->amount)
                            </td>

                            <!-- 6. Bonus -->
                            <td class="text-right font-mono text-emerald-600 whitespace-nowrap">
                                @if($r->bonus_amount > 0)+@currency($r->bonus_amount)@else--@endif
                            </td>

                            <!-- 7. Total Credited -->
                            <td class="text-right font-mono font-bold text-purple-700 whitespace-nowrap">
                                @currency($r->total_credited)
                            </td>

                            <!-- 8. Method -->
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $r->payment_method_badge['label'] ?? $r->payment_method }}
                                </span>
                            </td>

                            <!-- 9. Status -->
                            <td class="text-center whitespace-nowrap">
                                @if($r->status === 'APPROVED')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[8px]"></i>
                                        <span>Approved</span>
                                    </span>
                                @elseif($r->status === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>Pending</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-times-circle text-[8px]"></i>
                                        <span>Rejected</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Action (3-Dot) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($r) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-purple-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-10 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-base border border-purple-100 shadow-2xs">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No Recharge Records Found</h3>
                                    <p class="text-[11px] text-slate-500">Submit a manual recharge entry or await online gateway payments from resellers.</p>
                                    <div class="pt-1">
                                        <button type="button" 
                                                @click="openNewRechargeModal()" 
                                                class="h-[34px] py-0 px-3.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                                            <span>+ Submit Recharge Entry</span>
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
        @if($recharges->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $recharges->firstItem() }} to {{ $recharges->lastItem() }} of {{ $recharges->total() }} entries
                </div>
                <div>
                    {{ $recharges->links() }}
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
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; viewReceipt(r.id)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-file-invoice text-purple-600 w-3.5 text-[11px]"></i>
                <span>View Recharge Voucher</span>
            </button>
            <a :href="`/admin/resellers/${activeMenu?.reseller_id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-user-gear text-slate-500 w-3.5 text-[11px]"></i>
                <span>Reseller Profile</span>
            </a>
            <a href="{{ route('tenant.resellers.wallets') }}" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-wallet text-purple-600 w-3.5 text-[11px]"></i>
                <span>Wallets &amp; Credit</span>
            </a>
        </div>

        <template x-if="activeMenu?.status === 'PENDING'">
            <div class="py-0.5">
                <button type="button" 
                        @click="const r = activeMenu; activeMenu = null; approveRecharge(r.id)" 
                        class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 text-emerald-700 flex items-center gap-2 transition font-medium cursor-pointer">
                    <i class="fas fa-check-circle text-emerald-600 w-3.5 text-[11px]"></i>
                    <span>Approve Recharge</span>
                </button>
                <button type="button" 
                        @click="const r = activeMenu; activeMenu = null; openRejectModal(r)" 
                        class="w-full px-3 py-1.5 text-left hover:bg-rose-50 text-rose-700 flex items-center gap-2 transition font-medium cursor-pointer">
                    <i class="fas fa-times-circle text-rose-600 w-3.5 text-[11px]"></i>
                    <span>Reject Request</span>
                </button>
            </div>
        </template>
    </div>

    {{-- 6. Production-Grade Natural Modals (AGENTS.md Rule 3) --}}
    {{-- Modal 1: Submit Recharge Entry Modal --}}
    <div x-show="showCreateModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="showCreateModal = false">
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Submit Reseller Recharge Entry</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record direct cash, bank deposit or gateway top-up</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="submitRecharge()" class="p-4 space-y-3 text-xs">
                <!-- Reseller Picker -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select Reseller Partner <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="form.reseller_id" 
                            required 
                            class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-purple-500">
                        <option value="">-- Choose Reseller --</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }}) — Balance: ৳{{ number_format($r->wallet_balance, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount & Bonus -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Recharge Amount (৳) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               x-model="form.amount" 
                               required 
                               placeholder="e.g. 10000" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Bonus Amount (৳)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="form.bonus_amount" 
                               placeholder="0.00" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-purple-500">
                    </div>
                </div>

                <!-- Payment Method & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.payment_method" 
                                required 
                                class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-purple-500">
                            <option value="CASH">Cash Deposit</option>
                            <option value="BANK_TRANSFER">Bank Transfer / EFT</option>
                            <option value="BKASH">bKash Merchant / Personal</option>
                            <option value="NAGAD">Nagad Direct</option>
                            <option value="ROCKET">Rocket Direct</option>
                            <option value="CHEQUE">Cheque / Demand Draft</option>
                            <option value="ONLINE_GATEWAY">Online Gateway</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Initial Status <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.status" 
                                required 
                                class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-purple-500">
                            <option value="APPROVED">Direct Approved (Credit Wallet Now)</option>
                            <option value="PENDING">Pending Approval (Review Later)</option>
                        </select>
                    </div>
                </div>

                <!-- Gateway Trx ID & Bank Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Gateway Trx ID / Deposit Slip
                        </label>
                        <input type="text" 
                               x-model="form.gateway_trx_id" 
                               placeholder="e.g. 9J87K654" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Deposit Date
                        </label>
                        <input type="date" 
                               x-model="form.deposit_date" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 focus:bg-white focus:border-purple-500">
                    </div>
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Operational Notes
                    </label>
                    <input type="text" 
                           x-model="form.notes" 
                           placeholder="Optional notes or remarks..." 
                           class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-purple-500">
                </div>

                <!-- Summary Preview -->
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px] flex items-center justify-between">
                    <span class="text-slate-600 font-medium">Total Credited to Wallet:</span>
                    <span class="font-mono font-bold text-purple-700 text-xs" x-text="calculateTotalCredited()"></span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="h-[34px] py-0 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Saving...' : 'Save Recharge'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 2: Printable Receipt / Voucher Modal --}}
    <div x-show="receiptModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="receiptModal.open = false">
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Recharge Receipt Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="receiptModal.data?.recharge_no"></p>
                    </div>
                </div>
                <button type="button" @click="receiptModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Printable Voucher Body -->
            <div id="printableReceipt" class="p-4 space-y-3.5 text-xs">
                <!-- Amount Highlight Card -->
                <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/60 text-center space-y-1">
                    <span class="text-[10px] uppercase font-semibold block tracking-wider text-purple-700">Total Credited Amount</span>
                    <span class="text-xl font-bold font-mono block leading-tight text-purple-900" x-text="'৳' + parseFloat(receiptModal.data?.total_credited || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })"></span>
                </div>

                <!-- Details Table -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Partner:</span>
                        <span class="font-semibold text-slate-800" x-text="receiptModal.data?.reseller_name"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Code:</span>
                        <span class="font-mono text-slate-800" x-text="receiptModal.data?.reseller_code"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Base Amount:</span>
                        <span class="font-mono text-slate-800" x-text="'৳' + parseFloat(receiptModal.data?.amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200" x-show="receiptModal.data?.bonus_amount > 0">
                        <span class="text-slate-500">Bonus Added:</span>
                        <span class="font-mono text-emerald-600" x-text="'+৳' + parseFloat(receiptModal.data?.bonus_amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Payment Method:</span>
                        <span class="font-medium text-slate-800" x-text="receiptModal.data?.payment_method"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200" x-show="receiptModal.data?.gateway_trx_id">
                        <span class="text-slate-500">Gateway Trx ID:</span>
                        <span class="font-mono text-slate-800" x-text="receiptModal.data?.gateway_trx_id"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Status:</span>
                        <span class="font-semibold" :class="receiptModal.data?.status === 'APPROVED' ? 'text-emerald-700' : (receiptModal.data?.status === 'PENDING' ? 'text-amber-700' : 'text-rose-700')" x-text="receiptModal.data?.status"></span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500">Date:</span>
                        <span class="font-mono text-slate-700" x-text="receiptModal.data?.date"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="receiptModal.open = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" @click="printReceipt()" class="h-[34px] py-0 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal 3: Reject Reason Modal --}}
    <div x-show="rejectModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="rejectModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reject Recharge Request</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="rejectModal.data?.recharge_no"></p>
                    </div>
                </div>
                <button type="button" @click="rejectModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="confirmReject()" class="p-4 space-y-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Reason for Rejection <span class="text-rose-500">*</span>
                    </label>
                    <textarea x-model="rejectModal.reason" 
                              required 
                              rows="3" 
                              placeholder="e.g. Invalid transaction ID or payment not received in bank account..." 
                              class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-rose-500 resize-none"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="rejectModal.open = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="h-[34px] py-0 px-4 bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-ban text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Rejecting...' : 'Confirm Rejection'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerRechargeManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        submitting: false,

        showCreateModal: false,
        form: {
            reseller_id: '',
            amount: '',
            bonus_amount: '',
            payment_method: 'CASH',
            gateway_trx_id: '',
            deposit_date: new Date().toISOString().split('T')[0],
            status: 'APPROVED',
            notes: ''
        },

        receiptModal: {
            open: false,
            data: null
        },

        rejectModal: {
            open: false,
            data: null,
            reason: ''
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 160;
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

        openNewRechargeModal() {
            this.form.reseller_id = '{{ $allResellers->first()?->id ?? "" }}';
            this.form.amount = '';
            this.form.bonus_amount = '';
            this.form.payment_method = 'CASH';
            this.form.gateway_trx_id = '';
            this.form.deposit_date = new Date().toISOString().split('T')[0];
            this.form.status = 'APPROVED';
            this.form.notes = '';
            this.showCreateModal = true;
        },

        calculateTotalCredited() {
            const amt = parseFloat(this.form.amount) || 0;
            const bns = parseFloat(this.form.bonus_amount) || 0;
            return '৳' + (amt + bns).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async submitRecharge() {
            if (!this.form.reseller_id || !this.form.amount) return;
            this.submitting = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.recharge.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    this.showCreateModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to submit recharge.'
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

        async viewReceipt(id) {
            try {
                const response = await fetch(`/admin/resellers/recharge/${id}/receipt`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.receiptModal.data = data.receipt;
                    this.receiptModal.open = true;
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load recharge receipt.' });
            }
        },

        async approveRecharge(id) {
            const result = await Swal.fire({
                title: 'Approve Recharge?',
                text: 'This will instantly credit the reseller wallet and record a credit ledger transaction.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Approve & Credit'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/admin/resellers/recharge/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Approved!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Approval Failed', text: data.message });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network request failed.' });
            }
        },

        openRejectModal(item) {
            this.rejectModal.data = item;
            this.rejectModal.reason = '';
            this.rejectModal.open = true;
        },

        async confirmReject() {
            if (!this.rejectModal.reason) return;
            this.submitting = true;
            try {
                const response = await fetch(`/admin/resellers/recharge/${this.rejectModal.data.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: this.rejectModal.reason })
                });
                const data = await response.json();
                if (data.success) {
                    this.rejectModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Action Failed', text: data.message });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network request failed.' });
            } finally {
                this.submitting = false;
            }
        },

        printReceipt() {
            const printContent = document.getElementById('printableReceipt').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Recharge Voucher - ${this.receiptModal.data?.recharge_no || ''}</title>
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
                    <p class="text-center" style="font-size: 12px; color: #64748b; margin-top: 0;">Reseller Official Recharge Voucher</p>
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
        }
    }
}
</script>
@endpush
