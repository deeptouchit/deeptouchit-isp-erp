@extends('tenant.layouts.app')

@section('title', 'Reseller Directory - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerDirectoryManager()" @scroll.window="activeMenuRes = null" @resize.window="activeMenuRes = null">
    
    {{-- Floating Global Toast Notification --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-3.5 py-2 rounded-xl shadow-lg border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Actions ONLY - No Subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs shrink-0">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight whitespace-nowrap">Resellers</h1>
                {{-- Plan Quota Badge --}}
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border {{ $isQuotaReached ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-purple-50 text-purple-700 border-purple-200' }}"
                      title="Reseller quota allowed in your ISP plan">
                    <i class="fas fa-layer-group text-[9px]"></i>
                    <span>Plan: {{ $plan->name ?? 'Standard' }} ({{ $totalResellers }}/{{ $resellerQuota > 0 ? $resellerQuota : '∞' }})</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap sm:flex-nowrap shrink-0">
            {{-- Wholesale Bandwidth Link --}}
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5"
               title="Manage Dedicated & Wholesale Bandwidth Allocation">
                <i class="fas fa-network-wired text-cyan-600 text-[10px]"></i>
                <span class="hidden md:inline">Wholesale Bandwidth</span>
            </a>

            {{-- Wallets Link --}}
            <a href="{{ route('tenant.resellers.wallets') }}" 
               class="h-[34px] bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5">
                <i class="fas fa-wallet text-slate-500 text-[10px]"></i>
                <span class="hidden sm:inline">Wallets</span>
            </a>

            {{-- Import Resellers (Excel / CSV) --}}
            <a href="{{ route('tenant.resellers.import') }}" 
               class="h-[34px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-emerald-200/80 shadow-2xs transition flex items-center justify-center gap-1.5"
               title="Bulk Import Resellers from Excel / CSV">
                <i class="fas fa-file-excel text-emerald-600 text-[10px]"></i>
                <span>Import</span>
            </a>

            {{-- Add New Reseller Button --}}
            <button type="button" 
                    @click="openCreateModal()" 
                    class="h-[34px] font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer text-white {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-cyan-600 hover:bg-cyan-700' }}">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Onboard Reseller</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strict AGENTS.md Rule 2.B: Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Resellers / Quota --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Plan Quota</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate {{ $isQuotaReached ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $totalResellers }} / {{ $resellerQuota > 0 ? $resellerQuota : '∞' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 {{ $isQuotaReached ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-cyan-50 text-cyan-600 border-cyan-100' }} flex items-center justify-center">
                <i class="fas fa-handshake"></i>
            </div>
        </div>

        {{-- Card 2: Active Resellers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Active Resellers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-emerald-700">{{ number_format($activeResellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Prepaid Model --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">Prepaid Model</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-purple-700">{{ number_format($prepaidResellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 4: Postpaid / Trunk --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Postpaid / Trunk</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-blue-700">{{ number_format($postpaidResellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 5: Total Wallet Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Total Wallets</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-emerald-700">@currency($totalWalletBalance)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        {{-- Card 6: Monthly Software Fees --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">Monthly Software</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-indigo-700">@currency($totalMonthlySoftwareFees)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-laptop-code"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Strict Customer-Standard 2-Tier Filter System) --}}
    <form method="GET" action="{{ route('tenant.resellers.index') }}" 
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
          x-data="{ 
              showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
              searchQuery: '{{ addslashes(request('search', '')) }}' 
          }">
        
        {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] controls) --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            {{-- Intelligent Live Search Box --}}
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors duration-200"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery" 
                       placeholder="Search Name, Code, Prefix, Contact, Mobile, Email..." 
                       value="{{ request('search') }}"
                       autocomplete="off"
                       class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                <button type="button" 
                        x-show="searchQuery && searchQuery.length > 0" 
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                        style="display: none;">
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            </div>

            {{-- Primary Filter: Billing Model --}}
            <select name="billing_type" 
                    class="h-[34px] w-full md:w-44 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="">All Billing Models</option>
                <option value="PREPAID_WALLET" {{ request('billing_type') === 'PREPAID_WALLET' ? 'selected' : '' }}>Prepaid Wallet</option>
                <option value="POSTPAID_MONTHLY" {{ request('billing_type') === 'POSTPAID_MONTHLY' ? 'selected' : '' }}>Postpaid Monthly</option>
                <option value="BANDWIDTH_WHOLESALE" {{ request('billing_type') === 'BANDWIDTH_WHOLESALE' ? 'selected' : '' }}>Bandwidth Wholesale</option>
            </select>

            {{-- Primary Filter: Status --}}
            <select name="status" 
                    class="h-[34px] w-full md:w-36 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>

            {{-- Pagination Selector (per_page) --}}
            @php $perPageVal = (int)request('per_page', 20); @endphp
            <select name="per_page" 
                    class="h-[34px] w-full md:w-28 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="15" {{ $perPageVal === 15 ? 'selected' : '' }}>15 / page</option>
                <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / page</option>
                <option value="30" {{ $perPageVal === 30 ? 'selected' : '' }}>30 / page</option>
                <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
            </select>

            {{-- More Filters Accordion Toggle Button --}}
            <button type="button" 
                    @click="showMoreFilters = !showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                    :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50/70 border-cyan-200 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                <span>More Filters</span>
                @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                @endif
                <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
            </button>

            {{-- Strict Filter & Reset Button Sequence (Filter First, Reset Second - Always Visible) --}}
            <div class="flex items-center gap-1.5 shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('tenant.resellers.index') }}" 
                   class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </div>

        {{-- Tier 2: Secondary Filters Drawer --}}
        <div x-show="showMoreFilters" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            
            {{-- Wallet Balance Filter --}}
            <div class="w-full sm:w-auto">
                <select name="wallet_state" 
                        class="h-[34px] w-full sm:w-48 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">All Wallet Balances</option>
                    <option value="positive" {{ request('wallet_state') === 'positive' ? 'selected' : '' }}>Positive Balance (&gt; 0)</option>
                    <option value="zero_negative" {{ request('wallet_state') === 'zero_negative' ? 'selected' : '' }}>Zero or Negative (&le; 0)</option>
                    <option value="credit_exceeded" {{ request('wallet_state') === 'credit_exceeded' ? 'selected' : '' }}>Exceeded Credit Limit</option>
                </select>
            </div>

            {{-- PPPoE Prefix Filter --}}
            <div class="w-full sm:w-auto">
                <select name="has_prefix" 
                        class="h-[34px] w-full sm:w-44 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">All Reseller Prefixes</option>
                    <option value="yes" {{ request('has_prefix') === 'yes' ? 'selected' : '' }}>Has PPPoE Prefix</option>
                    <option value="no" {{ request('has_prefix') === 'no' ? 'selected' : '' }}>No Prefix Assigned</option>
                </select>
            </div>
        </div>
    </form>

    {{-- 4. Master Compact Table (Pure CSS System Compliant - Strict AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Reseller Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $resellers->total() }} Resellers
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table w-full">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="text-center font-semibold">Code</th>
                        <th class="text-center font-semibold">Reseller Name</th>
                        <th class="text-center font-semibold">Prefix</th>
                        <th class="text-center font-semibold">Contact Person</th>
                        <th class="text-center font-semibold">Mobile</th>
                        <th class="text-center font-semibold">Billing Model</th>
                        <th class="text-center font-semibold">Commission</th>
                        <th class="text-center font-semibold">Wallet Balance</th>
                        <th class="text-center font-semibold">Monthly Fee</th>
                        <th class="w-10 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $index => $res)
                        @php
                            $billingBadge = $res->billing_badge;
                            $statusBadge = $res->status_badge;
                            $resPayload = [
                                'id' => $res->id,
                                'name' => $res->name,
                                'code' => $res->code,
                                'prefix' => $res->prefix,
                                'contact_person' => $res->contact_person,
                                'mobile' => $res->mobile,
                                'email' => $res->email,
                                'address' => $res->address,
                                'billing_type' => $res->billing_type,
                                'wallet_balance' => (float)$res->wallet_balance,
                                'credit_limit' => (float)$res->credit_limit,
                                'commission_rate' => (float)$res->commission_rate,
                                'monthly_panel_charge' => (float)$res->monthly_panel_charge,
                                'panel_expiry_date' => $res->panel_expiry_date ? substr((string)$res->panel_expiry_date, 0, 10) : null,
                                'status' => $res->status,
                                'notes' => $res->notes,
                            ];
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $resellers->firstItem() + $index }}
                            </td>

                            {{-- 2. Status --}}
                            <td class="text-center whitespace-nowrap">
                                @if($res->status === 'active')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Active</span>
                                    </span>
                                @elseif($res->status === 'suspended')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Suspended</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Inactive</span>
                                    </span>
                                @endif
                            </td>

                            {{-- 3. Code --}}
                            <td class="text-center font-mono text-cyan-800 font-semibold whitespace-nowrap">
                                <a href="{{ route('tenant.resellers.show', $res->id) }}" 
                                   class="hover:underline cursor-pointer">
                                    {{ $res->code }}
                                </a>
                            </td>

                            {{-- 4. Reseller Name --}}
                            <td class="text-left text-slate-800 font-medium whitespace-nowrap">
                                <a href="{{ route('tenant.resellers.show', $res->id) }}" 
                                   class="hover:underline cursor-pointer">
                                    {{ $res->name }}
                                </a>
                            </td>

                            {{-- 5. Prefix --}}
                            <td class="text-center font-mono text-purple-700 whitespace-nowrap">
                                {{ $res->prefix ? $res->prefix . '_*' : '--' }}
                            </td>

                            {{-- 6. Contact Person --}}
                            <td class="text-left text-slate-800 whitespace-nowrap">
                                {{ $res->contact_person }}
                            </td>

                            {{-- 7. Mobile --}}
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $res->mobile }}
                            </td>

                            {{-- 8. Billing Model --}}
                            <td class="text-center font-mono text-[10px] whitespace-nowrap">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $billingBadge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ $billingBadge['label'] ?? $res->billing_type }}
                                </span>
                            </td>

                            {{-- 9. Commission --}}
                            <td class="text-center font-mono text-purple-700 whitespace-nowrap">
                                {{ number_format($res->commission_rate, 1) }}%
                            </td>

                            {{-- 10. Wallet Balance --}}
                            <td class="text-right font-mono text-emerald-700 font-bold whitespace-nowrap">
                                @currency($res->wallet_balance)
                            </td>

                            {{-- 11. Panel Fee --}}
                            <td class="text-right font-mono text-slate-700 whitespace-nowrap">
                                @currency($res->monthly_panel_charge)
                            </td>

                            {{-- 12. Actions (3-Dot Action Button) --}}
                            <td class="w-10 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($resPayload) }}, $event)" 
                                        class="res-action-btn w-6 h-6 mx-auto rounded-md hover:bg-slate-100 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-2xs">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800">No Resellers Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Onboard your first reseller to start managing clients and wallet balances.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openCreateModal()" 
                                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Onboard Reseller</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if($resellers->hasPages() || $resellers->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $resellers->firstItem() ?? 0 }} to {{ $resellers->lastItem() ?? 0 }} of {{ $resellers->total() }} results
                </div>
                <div>
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Global Floating 3-Dot Action Menu (Strict AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenuRes !== null" 
         @click.away="activeMenuRes = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-48 bg-white rounded-lg shadow-xl border border-slate-200 py-0.5 text-xs divide-y divide-slate-100 font-normal"
         style="display: none;">
        
        {{-- Group 1: Profile & Wallet Recharge --}}
        <div class="py-0.5">
            <a :href="'{{ url('admin/resellers') }}/' + activeMenuRes?.id" 
               class="w-full px-2.5 py-1.5 hover:bg-slate-50 text-slate-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-id-card text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>{{ __('View Details') }}</span>
            </a>

            <button type="button" 
                    @click="rechargeFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-emerald-50 text-emerald-800 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-wallet text-emerald-600 w-3.5 text-center text-[10px]"></i>
                <span>Quick Wallet Top-up</span>
            </button>
        </div>

        {{-- Group 2: Configuration & Status --}}
        <div class="py-0.5">
            <button type="button" 
                    @click="editFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-pen-to-square text-amber-500 w-3.5 text-center text-[10px]"></i>
                <span>Edit Configuration</span>
            </button>

            <button type="button" 
                    @click="toggleStatusFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas w-3.5 text-center text-[10px]" :class="activeMenuRes?.status === 'active' ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500'"></i>
                <span x-text="activeMenuRes?.status === 'active' ? 'Suspend Reseller' : 'Activate Reseller'"></span>
            </button>
        </div>

        {{-- Group 3: Destructive Action --}}
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1.5 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete Reseller</span>
            </button>
        </div>
    </div>

    {{-- 6. Production-Grade Natural Modal: Create & Edit Reseller Modal --}}
    <div x-show="modal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas" :class="modal.isEdit ? 'fa-pen-to-square' : 'fa-handshake'"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800" x-text="modal.isEdit ? 'Edit Reseller' : 'Onboard New Reseller'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Configure business identity, username prefix, billing terms, and portal credentials</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Modal Form Body --}}
            <div class="p-4 space-y-3.5 max-h-[70vh] overflow-y-auto text-xs">
                
                {{-- Plan Quota Notification Banner --}}
                @if($isQuotaReached)
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-2.5 text-rose-800 text-xs" x-show="!modal.isEdit">
                        <i class="fas fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i>
                        <div>
                            <strong class="font-semibold block">Plan Reseller Quota Limit Reached ({{ $totalResellers }}/{{ $resellerQuota }})</strong>
                            <span class="text-[11px] text-rose-600">Your ISP subscription plan (<strong>{{ $plan->name ?? 'Standard' }}</strong>) allows up to {{ $resellerQuota }} resellers. To add more resellers, please upgrade your subscription plan.</span>
                        </div>
                    </div>
                @else
                    <div class="p-2.5 bg-purple-50/60 border border-purple-100 rounded-lg flex items-center justify-between text-xs" x-show="!modal.isEdit">
                        <div class="flex items-center gap-1.5 text-purple-800">
                            <i class="fas fa-layer-group text-purple-600"></i>
                            <span><strong>Plan Quota:</strong> {{ $totalResellers }} of {{ $resellerQuota > 0 ? $resellerQuota : 'Unlimited' }} resellers used ({{ $plan->name ?? 'Current Plan' }})</span>
                        </div>
                        <span class="text-[10.5px] font-semibold text-purple-700 bg-white px-2 py-0.5 rounded border border-purple-200">
                            {{ $quotaRemaining }} remaining
                        </span>
                    </div>
                @endif

                {{-- SECTION 1: Business Identity & Contact Info --}}
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2.5">
                    <span class="text-[11px] font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-building text-cyan-600"></i>
                        <span>1. Business Identity &amp; Contact</span>
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Company Name --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Company / Reseller Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.name" 
                                   placeholder="e.g. SpeedNet Chittagong" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Reseller Code --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Reseller Code <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.code" 
                                   placeholder="e.g. RES-101" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Username Prefix --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Username Prefix (PPPoE)
                            </label>
                            <input type="text" 
                                   x-model="modal.form.prefix" 
                                   placeholder="e.g. spd" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-purple-700 focus:border-cyan-500 outline-hidden transition font-normal" />
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">Format: prefix_* (e.g. spd_user1)</span>
                        </div>

                        {{-- Contact Person --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Contact Person <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.contact_person" 
                                   placeholder="e.g. Md. Rafiqul Islam" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Mobile Number --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Mobile Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="modal.form.mobile" 
                                   placeholder="e.g. 01819000000" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Email --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Email Address</label>
                            <input type="email" 
                                   name="reseller_email_nobrowser_fill"
                                   autocomplete="off"
                                   x-model="modal.form.email" 
                                   placeholder="partner@speednet.com" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Territory / Office Address</label>
                            <input type="text" 
                                   x-model="modal.form.address" 
                                   placeholder="e.g. Agrabad C/A, Chattogram" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Billing Model & Financial Terms --}}
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2.5">
                    <span class="text-[11px] font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-wallet text-cyan-600"></i>
                        <span>2. Billing Model &amp; Financials</span>
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        {{-- Billing Type --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Billing Model <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="modal.form.billing_type" 
                                    class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal">
                                <option value="PREPAID_WALLET">Prepaid Wallet</option>
                                <option value="POSTPAID_MONTHLY">Postpaid Monthly</option>
                                <option value="BANDWIDTH_WHOLESALE">Bandwidth Wholesale</option>
                            </select>
                        </div>

                        {{-- Commission Rate % --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Commission Rate (%) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.1" min="0" max="100" 
                                   x-model="modal.form.commission_rate" 
                                   placeholder="30.0" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Initial Balance (only for new) --}}
                        <div x-show="!modal.isEdit">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Initial Wallet (<span x-text="currencySymbol"></span>)
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   x-model="modal.form.wallet_balance" 
                                   placeholder="0.00" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Credit Limit --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Credit Limit (<span x-text="currencySymbol"></span>)
                            </label>
                            <input type="number" 
                                   step="0.01" min="0" 
                                   x-model="modal.form.credit_limit" 
                                   placeholder="0.00" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: Software / Panel Subscription --}}
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2.5">
                    <span class="text-[11px] font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-laptop-code text-cyan-600"></i>
                        <span>3. Software Subscription &amp; Status</span>
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Monthly Charge --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Monthly Panel Fee (<span x-text="currencySymbol"></span>)
                            </label>
                            <input type="number" 
                                   step="0.01" min="0" 
                                   x-model="modal.form.monthly_panel_charge" 
                                   placeholder="e.g. 500.00" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Panel Expiry Date --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Panel Expiry Date</label>
                            <input type="date" 
                                   x-model="modal.form.panel_expiry_date" 
                                   class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Account Status <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="modal.form.status" 
                                    class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal">
                                <option value="active">Active (Operational)</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended (Blocked)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SECTION 4: Portal Login Credentials (New only) --}}
                <div x-show="!modal.isEdit" class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-100 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-cyan-900 flex items-center gap-1.5">
                            <i class="fas fa-shield-halved text-cyan-600"></i>
                            <span>4. Reseller Admin Portal Access</span>
                        </span>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                            <input type="checkbox" x-model="modal.form.create_portal_user" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-xs text-slate-700 font-semibold">Create Portal Login</span>
                        </label>
                    </div>

                    <div x-show="modal.form.create_portal_user" class="pt-1">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Initial Portal Password</label>
                        <input type="password" 
                               name="reseller_pwd_nobrowser_fill"
                               autocomplete="new-password"
                               x-model="modal.form.portal_password" 
                               placeholder="Default: 12345678" 
                               class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:border-cyan-500 outline-hidden transition font-normal" />
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Login email will be the reseller contact email provided above.</span>
                    </div>
                </div>

                {{-- SECTION 5: Notes / Agreement --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Contract Details</label>
                    <textarea rows="2" 
                              x-model="modal.form.notes" 
                              placeholder="Contract agreement terms, territory coverage, special discount notes..." 
                              class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal"></textarea>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeModal()" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="saveReseller()" 
                        :disabled="modal.saving || (!modal.isEdit && {{ $isQuotaReached ? 'true' : 'false' }})"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': modal.saving }"></i>
                    <span x-text="modal.saving ? 'Saving...' : (modal.isEdit ? 'Save Changes' : 'Onboard Reseller')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- 7. Production-Grade Natural Modal: Quick Wallet Recharge Modal --}}
    <div x-show="rechargeModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeRechargeModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            {{-- Soft Natural Header --}}
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800">Quick Wallet Recharge</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono font-normal" x-text="rechargeModal.resellerName"></p>
                    </div>
                </div>
                <button type="button" @click="closeRechargeModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                {{-- Current Balance Display --}}
                <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-100 flex items-center justify-between">
                    <div>
                        <span class="text-[9.5px] text-emerald-800 uppercase font-semibold block tracking-wider">Available Balance</span>
                        <span class="text-sm font-extrabold text-emerald-700 font-mono block leading-none mt-0.5">
                            <span x-text="currencySymbol"></span>&nbsp;<span x-text="rechargeModal.currentBalance"></span>
                        </span>
                    </div>
                    <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                        <i class="fas fa-vault"></i>
                    </div>
                </div>

                {{-- Transaction Type Toggle --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction Type <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" 
                                @click="rechargeModal.form.type = 'credit'"
                                :class="rechargeModal.form.type === 'credit' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                class="py-1.5 px-3 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer">
                            <i class="fas fa-circle-plus text-[10px]"></i>
                            <span>Add (Credit)</span>
                        </button>
                        <button type="button" 
                                @click="rechargeModal.form.type = 'debit'"
                                :class="rechargeModal.form.type === 'debit' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                class="py-1.5 px-3 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition cursor-pointer">
                            <i class="fas fa-circle-minus text-[10px]"></i>
                            <span>Deduct (Debit)</span>
                        </button>
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-800 mb-1">
                        Amount (<span x-text="currencySymbol"></span>) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 font-mono font-bold text-xs" x-text="currencySymbol"></span>
                        <input type="number" 
                               step="0.01" min="1" 
                               x-model="rechargeModal.form.amount" 
                               placeholder="e.g. 5000.00" 
                               class="w-full pl-8 pr-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 text-xs font-mono font-bold focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                    </div>
                </div>

                {{-- Reference / Note --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Reference / Note</label>
                    <input type="text" 
                           x-model="rechargeModal.form.notes" 
                           placeholder="e.g. Bank deposit / bKash TxnID #987654" 
                           class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="closeRechargeModal()" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitRecharge()" 
                        :disabled="rechargeModal.saving"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': rechargeModal.saving }"></i>
                    <span x-text="rechargeModal.saving ? 'Processing...' : 'Submit Transaction'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function resellerDirectoryManager() {
    return {
        currencySymbol: '{{ $currencySymbol ?? '৳' }}',
        nextCode: '{{ $nextCode ?? 'RES-001' }}',
        activeMenuRes: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        modal: {
            open: false,
            isEdit: false,
            saving: false,
            form: {
                id: null,
                name: '',
                code: '',
                prefix: '',
                contact_person: '',
                mobile: '',
                email: '',
                address: '',
                billing_type: 'PREPAID_WALLET',
                wallet_balance: 0,
                credit_limit: 0,
                commission_rate: 30,
                monthly_panel_charge: 0,
                panel_expiry_date: '',
                status: 'active',
                notes: '',
                create_portal_user: true,
                portal_password: ''
            }
        },

        rechargeModal: {
            open: false,
            saving: false,
            resellerId: null,
            resellerName: '',
            currentBalance: '0.00',
            form: {
                type: 'credit',
                amount: '',
                notes: ''
            }
        },

        toggleMenu(res, event) {
            if (this.activeMenuRes && this.activeMenuRes.id === res.id) {
                this.activeMenuRes = null;
                return;
            }
            this.activeMenuRes = res;

            let targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
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

        rechargeFromMenu() {
            if (!this.activeMenuRes) return;
            const res = this.activeMenuRes;
            this.activeMenuRes = null;
            this.openRechargeModal(res);
        },

        editFromMenu() {
            if (!this.activeMenuRes) return;
            const res = this.activeMenuRes;
            this.activeMenuRes = null;
            this.openEditModal(res);
        },

        toggleStatusFromMenu() {
            if (!this.activeMenuRes) return;
            const res = this.activeMenuRes;
            this.activeMenuRes = null;
            this.toggleStatus(res.id, res.status === 'active' ? 'suspended' : 'active');
        },

        deleteFromMenu() {
            if (!this.activeMenuRes) return;
            const res = this.activeMenuRes;
            this.activeMenuRes = null;
            this.deleteReseller(res.id, res.name);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.form = {
                id: null,
                name: '',
                code: this.nextCode,
                prefix: '',
                contact_person: '',
                mobile: '',
                email: '',
                address: '',
                billing_type: 'PREPAID_WALLET',
                wallet_balance: 0,
                credit_limit: 0,
                commission_rate: 30,
                monthly_panel_charge: 0,
                panel_expiry_date: '',
                status: 'active',
                notes: '',
                create_portal_user: true,
                portal_password: ''
            };
            this.modal.open = true;
        },

        openEditModal(res) {
            this.modal.isEdit = true;
            this.modal.form = {
                id: res.id,
                name: res.name || '',
                code: res.code || '',
                prefix: res.prefix || '',
                contact_person: res.contact_person || '',
                mobile: res.mobile || '',
                email: res.email || '',
                address: res.address || '',
                billing_type: res.billing_type || 'PREPAID_WALLET',
                wallet_balance: res.wallet_balance || 0,
                credit_limit: res.credit_limit || 0,
                commission_rate: res.commission_rate ?? 30,
                monthly_panel_charge: res.monthly_panel_charge || 0,
                panel_expiry_date: res.panel_expiry_date ? String(res.panel_expiry_date).substring(0, 10) : '',
                status: res.status || 'active',
                notes: res.notes || '',
                create_portal_user: false,
                portal_password: ''
            };
            this.modal.open = true;
        },

        closeModal() {
            this.modal.open = false;
        },

        openRechargeModal(res) {
            this.rechargeModal.resellerId = res.id;
            this.rechargeModal.resellerName = res.name + ' (' + res.code + ')';
            this.rechargeModal.currentBalance = parseFloat(res.wallet_balance || 0).toFixed(2);
            this.rechargeModal.form = {
                type: 'credit',
                amount: '',
                notes: ''
            };
            this.rechargeModal.open = true;
        },

        closeRechargeModal() {
            this.rechargeModal.open = false;
        },

        async saveReseller() {
            if (!this.modal.form.name || !this.modal.form.code || !this.modal.form.contact_person || !this.modal.form.mobile) {
                this.showToast('Please fill all required fields (Name, Code, Contact Person, Mobile).', 'error');
                return;
            }

            this.modal.saving = true;
            const url = this.modal.isEdit 
                ? `/admin/resellers/${this.modal.form.id}`
                : `/admin/resellers`;
            const method = this.modal.isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.modal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || 'Reseller saved successfully!', 'success');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Validation error while saving reseller.', 'error');
                }
            } catch (err) {
                this.showToast('Network error saving reseller.', 'error');
            } finally {
                this.modal.saving = false;
            }
        },

        async submitRecharge() {
            if (!this.rechargeModal.form.amount || parseFloat(this.rechargeModal.form.amount) <= 0) {
                this.showToast('Please enter a valid amount.', 'error');
                return;
            }
            this.rechargeModal.saving = true;
            try {
                const res = await fetch(`/admin/resellers/${this.rechargeModal.resellerId}/adjust-wallet`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.rechargeModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || 'Wallet balance updated!', 'success');
                    this.closeRechargeModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Failed to update wallet.', 'error');
                }
            } catch (e) {
                this.showToast('Error adjusting wallet', 'error');
            } finally {
                this.rechargeModal.saving = false;
            }
        },

        async toggleStatus(id, newStatus) {
            try {
                const res = await fetch(`/admin/resellers/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || 'Status updated!', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(data.message || 'Failed to update status.', 'error');
                }
            } catch (e) {
                this.showToast('Error toggling status', 'error');
            }
        },

        deleteReseller(id, name) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Reseller?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete <strong class="text-slate-900 font-semibold">${name}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">Associated reseller records and permissions will be removed.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete Reseller',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeDelete(id);
                    }
                });
            } else if (confirm(`Are you sure you want to delete reseller '${name}'?`)) {
                this.executeDelete(id);
            }
        },

        async executeDelete(id) {
            try {
                const res = await fetch(`/admin/resellers/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message || 'Reseller deleted.', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(data.message || 'Failed to delete reseller.', 'error');
                }
            } catch (e) {
                this.showToast('Error deleting reseller', 'error');
            }
        }
    };
}
</script>
@endpush
