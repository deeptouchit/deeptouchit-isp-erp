@extends('tenant.layouts.app')

@section('title', __('Reseller Wallets & Credit') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerWalletManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">{{ __('Reseller Wallets & Credit') }}</h1>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">
                {{ __(':count Resellers', ['count' => $resellers->total()]) }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.resellers.recharge') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-clock-rotate-left text-[11px] text-slate-600"></i>
                <span>{{ __('Recharge History') }}</span>
            </a>
            <a href="{{ route('tenant.resellers.ledger') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-[11px] text-slate-600"></i>
                <span>{{ __('Adjustment Ledger') }}</span>
            </a>
            <button type="button" 
                    @click="openTopupModal()"
                    class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                <span>{{ __('Adjust / Recharge') }}</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Liquidity -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Liquidity') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">@currency($totalWalletLiquidity)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        <!-- Card 2: Credit Limits -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Credit Limits') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($totalCreditLimits)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Card 3: Purchasing Power -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Purchasing Power') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalPurchasingPower)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Card 4: Prepaid Resellers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Prepaid Resellers') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">{{ number_format($prepaidResellersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Card 5: Low Balance Alerts -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Low Balance (<1k)') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">{{ number_format($lowBalanceCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: Recharged This Month -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Recharged (Month)') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($totalRechargedThisMonth)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-circle-dollar-to-slot"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.resellers.wallets') }}" class="space-y-2.5">
            
            <!-- Tier 1: Primary Filter Bar (Uniform h-[34px] Row) -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Box -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors pointer-events-none"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="search" 
                           name="search" 
                           x-model="searchQuery"
                           autocomplete="off"
                           placeholder="{{ __('Search reseller name, code, prefix, contact...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 text-xs cursor-pointer p-0.5">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <!-- Primary Dropdown: Reseller Partner Filter -->
                <select name="reseller_id" 
                        onchange="this.form.submit()" 
                        class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-48 flex-shrink-0">
                    <option value="">{{ __('All Resellers') }}</option>
                    @foreach($allResellers as $res)
                        <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                            {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }})
                        </option>
                    @endforeach
                </select>

                <!-- Primary Dropdown: Balance Status Filter -->
                <select name="balance_status" 
                        onchange="this.form.submit()" 
                        class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-36 flex-shrink-0">
                    <option value="">{{ __('All Balances') }}</option>
                    <option value="healthy" {{ request('balance_status') === 'healthy' ? 'selected' : '' }}>{{ __('Healthy (≥ :currency:amount)', ['currency' => $currencySymbol ?? '৳', 'amount' => '5k']) }}</option>
                    <option value="low" {{ request('balance_status') === 'low' ? 'selected' : '' }}>{{ __('Low (< :currency:amount)', ['currency' => $currencySymbol ?? '৳', 'amount' => '1k']) }}</option>
                    <option value="negative" {{ request('balance_status') === 'negative' ? 'selected' : '' }}>{{ __('Overdrawn (< :currency:amount)', ['currency' => $currencySymbol ?? '৳', 'amount' => '0']) }}</option>
                </select>

                <!-- Pagination Selector (per_page) -->
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs w-full md:w-28 flex-shrink-0">
                    <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                    <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                    <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                </select>

                <!-- Accordion Toggle Button for Tier-2 Secondary Filters -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <i class="fas fa-sliders text-slate-500 text-[11px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] ml-0.5 transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Strict Filter & Reset Buttons Sequence (AGENTS.md Rule 2.C) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.resellers.wallets') }}" 
                       class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filter Drawer (Expandable) -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- Secondary Filter: Billing Type -->
                <div class="w-full sm:w-auto min-w-[200px]">
                    <select name="billing_type" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                        <option value="">{{ __('All Billing Types') }}</option>
                        <option value="PREPAID_WALLET" {{ request('billing_type') === 'PREPAID_WALLET' ? 'selected' : '' }}>{{ __('Prepaid Wallet') }}</option>
                        <option value="POSTPAID_INVOICE" {{ request('billing_type') === 'POSTPAID_INVOICE' ? 'selected' : '' }}>{{ __('Postpaid Monthly Invoice') }}</option>
                        <option value="REVENUE_SHARE" {{ request('billing_type') === 'REVENUE_SHARE' ? 'selected' : '' }}>{{ __('Revenue Share') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="w-24 text-center font-semibold">{{ __('Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Reseller Partner') }}</th>
                        <th class="w-24 text-center font-semibold">{{ __('Billing Type') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Current Balance') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Credit Limit') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Purchasing Power') }}</th>
                        <th class="w-10 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $index => $res)
                        @php
                            $bal = (float)$res->wallet_balance;
                            $limit = (float)$res->credit_limit;
                            $power = $bal + $limit;
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ ($resellers->currentPage() - 1) * $resellers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Health Status (Center Align, Single-Line Badge) -->
                            <td class="text-center whitespace-nowrap">
                                @if($bal < 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-circle-exclamation text-[8px]"></i>
                                        <span>{{ __('Overdrawn') }}</span>
                                    </span>
                                @elseif($bal < 1000)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-triangle-exclamation text-[8px]"></i>
                                        <span>{{ __('Low Balance') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-circle-check text-[8px]"></i>
                                        <span>{{ __('Healthy') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Reseller Partner (Left Align) -->
                            <td class="text-left font-semibold text-cyan-800 whitespace-nowrap">
                                <button type="button" 
                                        @click="viewDetails({{ Js::from($res) }})" 
                                        class="hover:underline text-left cursor-pointer truncate max-w-[220px] inline-block align-middle font-semibold">
                                    {{ $res->name }}
                                </button>
                            </td>

                            <!-- 4. Billing Type (Center Align) -->
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $res->billing_type === 'PREPAID_WALLET' ? __('Prepaid Wallet') : ($res->billing_type === 'REVENUE_SHARE' ? __('Revenue Share') : __('Postpaid')) }}
                                </span>
                            </td>

                            <!-- 5. Current Balance (Right Align, Mono) -->
                            <td class="text-right font-mono font-bold whitespace-nowrap">
                                @if($bal < 0)
                                    <span class="text-rose-600 font-bold">@currency($bal)</span>
                                @elseif($bal < 1000)
                                    <span class="text-amber-600 font-bold">@currency($bal)</span>
                                @else
                                    <span class="text-emerald-700 font-bold">@currency($bal)</span>
                                @endif
                            </td>

                            <!-- 6. Credit Limit (Right Align, Mono) -->
                            <td class="text-right font-mono font-bold text-blue-700 whitespace-nowrap">
                                @currency($limit)
                            </td>

                            <!-- 7. Purchasing Power (Right Align, Mono) -->
                            <td class="text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                @currency($power)
                            </td>

                            <!-- 8. Action (3-Dot Action Menu ONLY - No Duplicate Inline Buttons) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($res) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-cyan-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">{{ __('No Reseller Wallets Found') }}</h3>
                                    <p class="text-[11px] text-slate-500">{{ __('Resellers created under Reseller Directory will appear here with active balances.') }}</p>
                                    <div class="pt-1">
                                        <button type="button" 
                                                @click="openTopupModal()"
                                                class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-xs"></i>
                                            <span>{{ __('Recharge / Adjust Balance') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($resellers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    {!! __('Showing :first to :last of :total entries', [
                        'first' => '<span class="font-bold text-slate-800">' . $resellers->firstItem() . '</span>',
                        'last' => '<span class="font-bold text-slate-800">' . $resellers->lastItem() . '</span>',
                        'total' => '<span class="font-bold text-slate-800">' . $resellers->total() . '</span>',
                    ]) !!}
                </div>
                <div>
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; viewDetails(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-3.5 text-[11px]"></i>
                <span>{{ __('View Wallet Details') }}</span>
            </button>
            <a :href="`/admin/resellers/${activeMenu?.id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-user-gear text-slate-500 w-3.5 text-[11px]"></i>
                <span>{{ __('Reseller Profile') }}</span>
            </a>
        </div>

        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; openTopupModal(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-emerald-600 w-3.5 text-[11px]"></i>
                <span>{{ __('Recharge / Adjust Balance') }}</span>
            </button>
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; openCreditLimitModal(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-hand-holding-dollar text-blue-600 w-3.5 text-[11px]"></i>
                <span>{{ __('Set Overdraft Limit') }}</span>
            </button>
            <a :href="`/admin/resellers/ledger?reseller_id=${activeMenu?.id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-receipt text-cyan-600 w-3.5 text-[11px]"></i>
                <span>{{ __('View Transaction Ledger') }}</span>
            </a>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modals (AGENTS.md Rule 3) -->
    
    <!-- Modal 1: View Full Wallet Details -->
    <div x-show="detailsModal.open" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="detailsModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.data?.name"></h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-100">{{ __('Wallet Details') }}</span>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3 text-xs">
                <!-- 3 Metric Cards Grid -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ __('Balance') }}</span>
                        <span class="text-xs font-bold font-mono text-cyan-800 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.wallet_balance)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ __('Credit Limit') }}</span>
                        <span class="text-xs font-bold font-mono text-blue-700 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.credit_limit)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ __('Purchasing Power') }}</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 mt-0.5 block" x-text="formatCurrency(parseFloat(detailsModal.data?.wallet_balance || 0) + parseFloat(detailsModal.data?.credit_limit || 0))"></span>
                    </div>
                </div>

                <!-- Technical Details Table -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">{{ __('Reseller Code:') }}</span>
                        <span class="font-mono text-slate-800 font-semibold" x-text="detailsModal.data?.code || i18n.na"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">{{ __('Username Prefix:') }}</span>
                        <span class="font-mono text-cyan-800 font-semibold" x-text="detailsModal.data?.prefix ? (detailsModal.data.prefix + '_*') : i18n.none"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">{{ __('Billing Type:') }}</span>
                        <span class="text-slate-800 font-semibold" x-text="formatBillingType(detailsModal.data?.billing_type)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">{{ __('Contact Person:') }}</span>
                        <span class="text-slate-800" x-text="detailsModal.data?.contact_person || i18n.na"></span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500 font-normal">{{ __('Mobile / Phone:') }}</span>
                        <span class="font-mono text-slate-800" x-text="detailsModal.data?.mobile || detailsModal.data?.phone || i18n.na"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
                <button type="button" @click="const r = detailsModal.data; detailsModal.open = false; openTopupModal(r)" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-circle-dollar-to-slot text-[10px]"></i>
                    <span>{{ __('Recharge / Adjust') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Wallet Top-Up / Adjustment Modal -->
    <div x-show="showTopupModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="if (!submitting) showTopupModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Wallet Top-up & Balance Adjustment') }}</h3>
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
                        {{ __('Select Reseller Partner') }} <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="topupForm.reseller_id" 
                            @change="onResellerSelect()"
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 transition shadow-2xs">
                        <option value="">{{ __('-- Choose Reseller --') }}</option>
                        <template x-for="r in allResellersList" :key="r.id">
                            <option :value="r.id" x-text="formatResellerOption(r)"></option>
                        </template>
                    </select>
                </div>

                <!-- Transaction Type Toggle (3 Tabs: Credit, Debit, Set Exact) -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Adjustment Type') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" 
                                @click="topupForm.type = 'CREDIT'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'CREDIT' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-down text-emerald-600 text-[10px]"></i>
                            <span>{{ __('Credit (+ Add)') }}</span>
                        </button>
                        <button type="button" 
                                @click="topupForm.type = 'DEBIT'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'DEBIT' ? 'bg-rose-50 border-rose-300 text-rose-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-up text-rose-600 text-[10px]"></i>
                            <span>{{ __('Debit (- Deduct)') }}</span>
                        </button>
                        <button type="button" 
                                @click="topupForm.type = 'SET'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'SET' ? 'bg-cyan-50 border-cyan-300 text-cyan-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-sliders text-cyan-600 text-[10px]"></i>
                            <span>{{ __('Set (= Exact)') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Amount & Payment Method -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            <span x-show="topupForm.type === 'SET'">{{ __('Target Balance (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span></span>
                            <span x-show="topupForm.type !== 'SET'">{{ __('Amount (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span></span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="topupForm.amount" 
                               required 
                               :placeholder="topupForm.type === 'SET' ? i18n.placeholder_target_balance : i18n.placeholder_amount" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="topupForm.payment_method" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            <option value="Cash">{{ __('Cash Deposit') }}</option>
                            <option value="Bank Transfer">{{ __('Bank Transfer / EFT') }}</option>
                            <option value="bKash">{{ __('bKash Merchant') }}</option>
                            <option value="Nagad">{{ __('Nagad Direct') }}</option>
                            <option value="Rocket">{{ __('Rocket Direct') }}</option>
                            <option value="Cheque">{{ __('Cheque') }}</option>
                            <option value="System Adjustment">{{ __('System Adjustment') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Reference & Notes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Reference No / Trx Voucher') }}
                        </label>
                        <input type="text" 
                               x-model="topupForm.reference_no" 
                               placeholder="{{ __('e.g. BK-987654 / Bank Slip') }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Remarks / Description') }}
                        </label>
                        <input type="text" 
                               x-model="topupForm.description" 
                               placeholder="{{ __('e.g. Balance recharge / correction') }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Balance Preview -->
                <div x-show="selectedReseller" class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px] space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('Current Balance:') }}</span>
                        <span class="font-mono text-slate-700" x-text="formatCurrency(selectedReseller?.wallet_balance)"></span>
                    </div>
                    <div class="flex items-center justify-between font-semibold">
                        <span class="text-slate-700">{{ __('Projected New Balance:') }}</span>
                        <span class="font-mono text-cyan-800 font-bold" x-text="calculateProjectedBalance()"></span>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showTopupModal = false" :disabled="submitting" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="submitting || !topupForm.reseller_id"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? i18n.processing : i18n.confirm_transaction"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Set Overdraft / Credit Limit Modal -->
    <div x-show="showCreditLimitModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden my-6"
             @click.outside="if (!submitting) showCreditLimitModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Set Overdraft & Credit Limit') }}</h3>
                    </div>
                </div>
                <button type="button" @click="showCreditLimitModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitCreditLimit()" class="p-4 space-y-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Reseller Partner:') }}
                    </label>
                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800" x-text="creditLimitForm.reseller_name"></div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Allowed Credit Limit (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" 
                           min="0" 
                           x-model="creditLimitForm.credit_limit" 
                           required 
                           placeholder="0.00" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    <span class="text-[10.5px] text-slate-500 mt-1 block">
                        {{ __('Reseller can continue purchasing bandwidth and creating subscribers even if wallet balance drops down to negative up to this amount.') }}
                    </span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreditLimitModal = false" :disabled="submitting" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? i18n.updating : i18n.save_credit_limit"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Floating Toast Notification Container -->
    <div x-show="toast.show" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 max-w-sm p-3 rounded-xl shadow-xl text-xs font-medium border flex items-center gap-2"
         :class="toast.type === 'success' ? 'bg-emerald-900 text-white border-emerald-700' : 'bg-rose-900 text-white border-rose-700'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-triangle-exclamation text-rose-400'"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerWalletManager() {
    return {
        currencySymbol: @json($currencySymbol ?? '৳'),
        i18n: {
            na: @json(__('N/A')),
            none: @json(__('None')),
            prepaid: @json(__('Prepaid')),
            prepaid_wallet: @json(__('Prepaid Wallet')),
            postpaid_invoice: @json(__('Postpaid Monthly Invoice')),
            revenue_share: @json(__('Revenue Share')),
            reseller_option: @json(__(':name (:code) — Balance: :amount')),
            placeholder_target_balance: @json(__('e.g. 10000')),
            placeholder_amount: @json(__('e.g. 5000')),
            processing: @json(__('Processing...')),
            confirm_transaction: @json(__('Confirm Transaction')),
            updating: @json(__('Updating...')),
            save_credit_limit: @json(__('Save Credit Limit')),
            select_reseller_amount: @json(__('Please enter an amount and select a reseller.')),
            error_adjust_balance: @json(__('Error executing balance adjustment.')),
            error_credit_limit: @json(__('Error updating credit limit.')),
            network_error: @json(__('Network error or server unreachable.'))
        },
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        allResellersList: {{ Js::from($allResellers) }},
        selectedReseller: null,
        submitting: false,

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => { this.toast.show = false; }, 3500);
        },

        // Modals
        detailsModal: {
            open: false,
            data: null
        },
        showTopupModal: false,
        showCreditLimitModal: false,

        topupForm: {
            reseller_id: '',
            type: 'CREDIT',
            amount: '',
            payment_method: 'Cash',
            reference_no: '',
            description: ''
        },

        creditLimitForm: {
            reseller_id: '',
            reseller_name: '',
            credit_limit: 0
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

        viewDetails(item) {
            this.detailsModal.data = item;
            this.detailsModal.open = true;
        },

        formatBillingType(type) {
            if (type === 'PREPAID_WALLET') return this.i18n.prepaid_wallet;
            if (type === 'POSTPAID_INVOICE') return this.i18n.postpaid_invoice;
            if (type === 'REVENUE_SHARE') return this.i18n.revenue_share;
            return type || this.i18n.prepaid;
        },

        formatResellerOption(r) {
            const code = r.code || ('RES-' + r.id);
            return this.i18n.reseller_option
                .replace(':name', r.name)
                .replace(':code', code)
                .replace(':amount', this.formatCurrency(r.wallet_balance));
        },

        openTopupModal(item = null) {
            if (item) {
                this.topupForm.reseller_id = item.id;
                this.selectedReseller = item;
            } else if (this.allResellersList.length > 0) {
                this.topupForm.reseller_id = this.allResellersList[0].id;
                this.selectedReseller = this.allResellersList[0];
            }
            this.topupForm.type = 'CREDIT';
            this.topupForm.amount = '';
            this.topupForm.payment_method = 'Cash';
            this.topupForm.reference_no = '';
            this.topupForm.description = '';
            this.showTopupModal = true;
        },

        onResellerSelect() {
            this.selectedReseller = this.allResellersList.find(r => String(r.id) === String(this.topupForm.reseller_id)) || null;
        },

        calculateProjectedBalance() {
            if (!this.selectedReseller) return this.formatCurrency(0);
            const cur = parseFloat(this.selectedReseller.wallet_balance || 0);
            const amt = parseFloat(this.topupForm.amount || 0);
            let res = cur;
            if (this.topupForm.type === 'CREDIT') {
                res = cur + amt;
            } else if (this.topupForm.type === 'DEBIT') {
                res = cur - amt;
            } else {
                res = amt;
            }
            return this.formatCurrency(res);
        },

        openCreditLimitModal(item) {
            this.creditLimitForm.reseller_id = item.id;
            this.creditLimitForm.reseller_name = item.name + ' (' + (item.code || 'RES-' + item.id) + ')';
            this.creditLimitForm.credit_limit = parseFloat(item.credit_limit || 0);
            this.showCreditLimitModal = true;
        },

        async submitTopup() {
            if (!this.topupForm.reseller_id || this.topupForm.amount === '') {
                this.showToast(this.i18n.select_reseller_amount, 'error');
                return;
            }
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
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.showTopupModal = false;
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showToast(data.message || this.i18n.error_adjust_balance, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.network_error, 'error');
            } finally {
                this.submitting = false;
            }
        },

        async submitCreditLimit() {
            this.submitting = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.wallets.credit-limit') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reseller_id: this.creditLimitForm.reseller_id,
                        credit_limit: this.creditLimitForm.credit_limit
                    })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.showCreditLimitModal = false;
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showToast(data.message || this.i18n.error_credit_limit, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.network_error, 'error');
            } finally {
                this.submitting = false;
            }
        },

        formatCurrency(val) {
            const n = parseFloat(val || 0);
            return this.currencySymbol + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
