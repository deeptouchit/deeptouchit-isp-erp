@extends('tenant.layouts.app')

@section('title', 'Reseller Wallets & Balances - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerWalletManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 leading-none">Reseller Wallets &amp; Balances</h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" 
                    @click="openTopupModal()"
                    class="px-3 py-1.5 rounded-md bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                <span>Recharge / Adjust Balance</span>
            </button>
            <a href="{{ route('tenant.resellers.index') }}" 
               class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>Reseller Directory</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Liquidity -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Liquidity</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">@currency($totalWalletLiquidity)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        <!-- Card 2: Credit Limits -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Credit Limits</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">@currency($totalCreditLimits)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Card 3: Purchasing Power -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Purchasing Power</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">@currency($totalPurchasingPower)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- Card 4: Prepaid Resellers -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">Prepaid Resellers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700">{{ number_format($prepaidResellersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Card 5: Low Balance Alerts -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Low Balance (&lt;1k)</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700">{{ number_format($lowBalanceCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: Recharged This Month -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">Recharged (Month)</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">@currency($totalRechargedThisMonth)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-circle-dollar-to-slot"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="bg-white p-3 rounded-md border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.resellers.wallets') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2 relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search reseller name, code, prefix, contact..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-md border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500 transition">
            </div>

            <!-- Reseller Filter -->
            <div>
                <select name="reseller_id" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-md border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    <option value="">All Resellers</option>
                    @foreach($allResellers as $res)
                        <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                            {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Balance Status Filter -->
            <div>
                <select name="balance_status" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-md border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    <option value="">All Balances</option>
                    <option value="healthy" {{ request('balance_status') === 'healthy' ? 'selected' : '' }}>Healthy (≥ ৳5k)</option>
                    <option value="low" {{ request('balance_status') === 'low' ? 'selected' : '' }}>Low (&lt; ৳1k)</option>
                    <option value="negative" {{ request('balance_status') === 'negative' ? 'selected' : '' }}>Overdrawn (&lt; ৳0)</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div>
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-md border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int)request('per_page', $perPage) === $size ? 'selected' : '' }}>
                            {{ $size }} per page
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.wallets') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Reseller Name</th>
                        <th>Code</th>
                        <th>Prefix</th>
                        <th>Contact Person</th>
                        <th class="text-right">Current Balance</th>
                        <th class="text-right">Credit Limit</th>
                        <th class="text-right">Purchasing Power</th>
                        <th class="text-center">Health Status</th>
                        <th class="text-center">Billing Type</th>
                        <th class="w-16 text-center no-sort">Action</th>
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
                            <td class="text-center font-mono text-slate-400">
                                {{ $resellers->firstItem() + $index }}
                            </td>

                            <!-- 2. Reseller Name -->
                            <td class="font-medium text-slate-900">
                                <button type="button" 
                                        @click="viewDetails({{ Js::from($res) }})" 
                                        class="hover:underline text-left cursor-pointer text-purple-700 font-semibold">
                                    {{ $res->name }}
                                </button>
                            </td>

                            <!-- 3. Code -->
                            <td class="font-mono text-slate-700">
                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded border border-slate-200 text-[10px]">
                                    {{ $res->code ?: 'RES-' . $res->id }}
                                </span>
                            </td>

                            <!-- 4. Prefix -->
                            <td class="font-mono text-purple-700">
                                {{ $res->prefix ? $res->prefix . '_*' : '--' }}
                            </td>

                            <!-- 5. Contact Person -->
                            <td class="text-slate-700">
                                {{ $res->contact_person ?: '--' }}
                            </td>

                            <!-- 6. Current Balance -->
                            <td class="text-right font-mono font-bold">
                                @if($bal < 0)
                                    <span class="text-rose-600">@currency($bal)</span>
                                @elseif($bal < 1000)
                                    <span class="text-amber-600">@currency($bal)</span>
                                @else
                                    <span class="text-emerald-700">@currency($bal)</span>
                                @endif
                            </td>

                            <!-- 7. Credit Limit -->
                            <td class="text-right font-mono text-blue-700">
                                @currency($limit)
                            </td>

                            <!-- 8. Purchasing Power -->
                            <td class="text-right font-mono font-semibold text-emerald-700">
                                @currency($power)
                            </td>

                            <!-- 9. Health Status -->
                            <td class="text-center">
                                @if($bal < 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Overdrawn</span>
                                    </span>
                                @elseif($bal < 1000)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Low Balance</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Healthy</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Billing Type -->
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $res->billing_type === 'PREPAID_WALLET' ? 'Prepaid' : 'Postpaid' }}
                                </span>
                            </td>

                            <!-- 11. Action (Quick Adjust & 3-Dot) -->
                            <td class="text-center">
                                <div class="inline-flex items-center justify-center gap-1">
                                    <button type="button" 
                                            @click="openTopupModal({{ Js::from($res) }})" 
                                            class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 text-[10px] font-semibold border border-purple-200 shadow-2xs transition cursor-pointer"
                                            title="Quick Recharge / Adjust">
                                        <i class="fas fa-coins text-[9px]"></i>
                                        <span>Adjust</span>
                                    </button>
                                    <button type="button" 
                                            @click.stop="toggleMenu({{ Js::from($res) }}, $event)" 
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                            title="Actions">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-md bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-base border border-purple-100">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No Reseller Wallets Found</h3>
                                    <p class="text-[11px] text-slate-500">Resellers created under Reseller Directory will appear here with active balances.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($resellers->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $resellers->firstItem() }} to {{ $resellers->lastItem() }} of {{ $resellers->total() }} entries
                </div>
                <div>
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu -->
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
                    @click="const r = activeMenu; activeMenu = null; viewDetails(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-eye text-purple-600 w-3.5 text-[11px]"></i>
                <span>View Wallet Details</span>
            </button>
            <a :href="`/admin/resellers/${activeMenu?.id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-user-gear text-slate-500 w-3.5 text-[11px]"></i>
                <span>Reseller Profile</span>
            </a>
        </div>

        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; openTopupModal(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-emerald-600 w-3.5 text-[11px]"></i>
                <span>Recharge / Adjust Balance</span>
            </button>
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; openCreditLimitModal(r)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-hand-holding-dollar text-blue-600 w-3.5 text-[11px]"></i>
                <span>Set Overdraft Limit</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Modals -->
    
    <!-- Modal 1: View Full Wallet Details -->
    <div x-show="detailsModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="detailsModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.data?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Reseller Wallet &amp; Financial Profile</p>
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
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Balance</span>
                        <span class="text-xs font-bold font-mono text-purple-700 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.wallet_balance)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Credit Limit</span>
                        <span class="text-xs font-bold font-mono text-blue-700 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.credit_limit)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Purchasing Power</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 mt-0.5 block" x-text="formatCurrency(parseFloat(detailsModal.data?.wallet_balance || 0) + parseFloat(detailsModal.data?.credit_limit || 0))"></span>
                    </div>
                </div>

                <!-- Technical Details Table -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">Reseller Code:</span>
                        <span class="font-mono text-slate-800 font-semibold" x-text="detailsModal.data?.code || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">Username Prefix:</span>
                        <span class="font-mono text-purple-700 font-semibold" x-text="detailsModal.data?.prefix ? (detailsModal.data.prefix + '_*') : 'None'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">Billing Type:</span>
                        <span class="text-slate-800 font-semibold" x-text="detailsModal.data?.billing_type || 'PREPAID'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-normal">Contact Person:</span>
                        <span class="text-slate-800" x-text="detailsModal.data?.contact_person || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500 font-normal">Mobile / Phone:</span>
                        <span class="font-mono text-slate-800" x-text="detailsModal.data?.mobile || detailsModal.data?.phone || 'N/A'"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" @click="const r = detailsModal.data; detailsModal.open = false; openTopupModal(r)" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-circle-dollar-to-slot text-[10px]"></i>
                    <span>Recharge / Adjust</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Wallet Top-Up / Adjustment Modal -->
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
             @click.away="if (!submitting) showTopupModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Wallet Top-up &amp; Balance Adjustment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Recharge, debit, or set exact reseller balance</p>
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
                            @change="onResellerSelect()"
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 transition">
                        <option value="">-- Choose Reseller --</option>
                        <template x-for="r in allResellersList" :key="r.id">
                            <option :value="r.id" x-text="`${r.name} (${r.code || 'RES-' + r.id}) — Balance: ৳${parseFloat(r.wallet_balance || 0).toFixed(2)}`"></option>
                        </template>
                    </select>
                </div>

                <!-- Transaction Type Toggle (3 Tabs: Credit, Debit, Set Exact) -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Adjustment Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" 
                                @click="topupForm.type = 'CREDIT'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'CREDIT' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-down text-emerald-600 text-[10px]"></i>
                            <span>Credit (+ Add)</span>
                        </button>
                        <button type="button" 
                                @click="topupForm.type = 'DEBIT'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'DEBIT' ? 'bg-rose-50 border-rose-300 text-rose-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-arrow-up text-rose-600 text-[10px]"></i>
                            <span>Debit (- Deduct)</span>
                        </button>
                        <button type="button" 
                                @click="topupForm.type = 'SET'"
                                class="py-1.5 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1 transition cursor-pointer"
                                :class="topupForm.type === 'SET' ? 'bg-purple-50 border-purple-300 text-purple-800 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white'">
                            <i class="fas fa-sliders text-purple-600 text-[10px]"></i>
                            <span>Set (= Exact)</span>
                        </button>
                    </div>
                </div>

                <!-- Amount & Payment Method -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            <span x-show="topupForm.type === 'SET'">Target Balance (৳) <span class="text-rose-500">*</span></span>
                            <span x-show="topupForm.type !== 'SET'">Amount (৳) <span class="text-rose-500">*</span></span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="topupForm.amount" 
                               required 
                               :placeholder="topupForm.type === 'SET' ? 'e.g. 10000' : 'e.g. 5000'" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="topupForm.payment_method" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
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
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Remarks / Description
                        </label>
                        <input type="text" 
                               x-model="topupForm.description" 
                               placeholder="e.g. Balance recharge / correction" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                </div>

                <!-- Balance Preview -->
                <div x-show="selectedReseller" class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px] space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Current Balance:</span>
                        <span class="font-mono text-slate-700" x-text="formatCurrency(selectedReseller?.wallet_balance)"></span>
                    </div>
                    <div class="flex items-center justify-between font-semibold">
                        <span class="text-slate-700">Projected New Balance:</span>
                        <span class="font-mono text-purple-700 font-bold" x-text="calculateProjectedBalance()"></span>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showTopupModal = false" :disabled="submitting" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting || !topupForm.reseller_id"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="submitting" style="display: none;"></i>
                        <i class="fas fa-check text-xs" x-show="!submitting"></i>
                        <span x-text="submitting ? 'Processing...' : 'Confirm Transaction'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Set Overdraft / Credit Limit Modal -->
    <div x-show="showCreditLimitModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="if (!submitting) showCreditLimitModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Set Overdraft &amp; Credit Limit</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="creditLimitForm.reseller_name"></p>
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
                        Allowed Credit Limit (৳) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" 
                           min="0" 
                           x-model="creditLimitForm.credit_limit" 
                           required 
                           placeholder="0.00" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-blue-500">
                    <p class="text-[10.5px] text-slate-500 mt-1">
                        Reseller can continue purchasing bandwidth and creating subscribers even if wallet balance drops down to negative up to this amount.
                    </p>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreditLimitModal = false" :disabled="submitting" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="submitting" style="display: none;"></i>
                        <i class="fas fa-check text-xs" x-show="!submitting"></i>
                        <span x-text="submitting ? 'Updating...' : 'Save Credit Limit'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Floating Toast Notification Container -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 max-w-sm p-3 rounded-md shadow-xl text-xs font-medium border flex items-center gap-2"
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
            if (!this.selectedReseller) return '৳0.00';
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
            return '৳' + res.toFixed(2);
        },

        openCreditLimitModal(item) {
            this.creditLimitForm.reseller_id = item.id;
            this.creditLimitForm.reseller_name = item.name + ' (' + (item.code || 'RES-' + item.id) + ')';
            this.creditLimitForm.credit_limit = parseFloat(item.credit_limit || 0);
            this.showCreditLimitModal = true;
        },

        async submitTopup() {
            if (!this.topupForm.reseller_id || this.topupForm.amount === '') {
                this.showToast('Please enter an amount and select a reseller.', 'error');
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
                    this.showToast(data.message || 'Error executing balance adjustment.', 'error');
                }
            } catch (err) {
                this.showToast('Network error or server unreachable.', 'error');
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
                    this.showToast(data.message || 'Error updating credit limit.', 'error');
                }
            } catch (err) {
                this.showToast('Network error or server unreachable.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        formatCurrency(val) {
            const n = parseFloat(val || 0);
            return '৳' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
