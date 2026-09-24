@extends('reseller.layouts.app')

@section('title', 'Bulk Bill & Due Payments - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
<style>
    .saas-table tbody tr.is-selected {
        background-color: #f0fdf4 !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="bulkPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Icon + Title + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 leading-none">{{ __('Bulk Bill & Due Payments') }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Print Statement / Demand Sheet -->
            <button type="button" 
                    @click="printSelectedOrAll()" 
                    class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Print Bulk Bill Demand & Due Statement">
                <i class="fas fa-print text-[11px]"></i>
                <span>{{ __('Print Sheet') }}</span>
            </button>

            <!-- Due Customers List -->
            <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>{{ __('Due Customers') }}</span>
            </a>

            <!-- Customer Master -->
            <a href="{{ route('reseller.customers.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>{{ __('All Customers') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Scope Subscribers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('Subscribers') }}</span>
                <span class="text-[13px] font-bold text-slate-800 font-mono leading-tight block truncate">{{ number_format($kpis['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Total Gross Bill -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Gross Bill') }}</span>
                <span class="text-[13px] font-bold text-amber-700 font-mono leading-tight block truncate">@currency($kpis['gross_bill'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Metric 3: Admin Share Net -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Admin Share') }}</span>
                <span class="text-[13px] font-bold text-emerald-700 font-mono leading-tight block truncate">@currency($kpis['admin_share'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Metric 4: Reseller Profit / Commission -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __('Your Profit') }}</span>
                <span class="text-[13px] font-bold text-indigo-700 font-mono leading-tight block truncate">@currency($kpis['reseller_commission'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        <!-- Metric 5: Wallet Balance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-600 uppercase tracking-wider block truncate">{{ __('Wallet Balance') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate text-slate-800">
                    @currency($kpis['reseller_wallet'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Metric 6: Total Available Balance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Available Balance') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate {{ $kpis['reseller_available'] >= $kpis['admin_share'] ? 'text-emerald-700' : 'text-rose-600' }}">
                    @currency($kpis['reseller_available'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Filter Toolbar & Selection Controls -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
        <form method="GET" action="{{ route('reseller.customers.bulk-payments') }}" class="space-y-2.5">
            
            <!-- Quick Selection Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-check-square text-cyan-600 text-xs"></i>
                        <span>Selection Status:</span>
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                        <span x-text="selectedIds.length"></span> / {{ $subscribers->total() }} Selected
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="selectAllRows()" 
                            class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold border border-emerald-200 cursor-pointer shadow-2xs transition">
                        <i class="fas fa-check-double text-[10px] mr-1"></i>
                        Select All (<span x-text="tableRows.length"></span>)
                    </button>
                    <button type="button" 
                            @click="clearAllSelections()" 
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold border border-slate-200 cursor-pointer shadow-2xs transition">
                        <i class="fas fa-xmark text-[10px] mr-1"></i>
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Filter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
                
                <!-- Zone Select -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Area / Zone</label>
                    <select name="zone" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Zones</option>
                        @foreach($zones as $z)
                            <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Select -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Package</label>
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Packages</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name ?: ($pkg->mikrotik_profile ?: ($pkg->package_name ?: 'Package #' . $pkg->id)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Select -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Status Filter</label>
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all_due" {{ $status === 'all_due' ? 'selected' : '' }}>Pending Dues &amp; Expired</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>🟢 Active Only</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>🔴 Expired Only</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>⛔ Suspended Only</option>
                        <option value="due" {{ $status === 'due' ? 'selected' : '' }}>🟡 Due Only</option>
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Per Page</label>
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500 / page</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Search</label>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="ID / Username / Phone..."
                           class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Strict Filter & Reset Sequence (Rule 2.C) -->
                <div class="flex items-end gap-1.5 flex-shrink-0">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('reseller.customers.bulk-payments') }}" 
                       class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-8 text-center no-sort">
                            <input type="checkbox" 
                                   @change="toggleSelectAll($event)" 
                                   :checked="selectAll"
                                   class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer"
                                   title="Select All Subscribers on this page">
                        </th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Zone</th>
                        <th>Package</th>
                        <th class="text-center">Status</th>
                        <th>Expiry</th>
                        <th class="text-right">Bill</th>
                        <th class="text-right">Due</th>
                        <th class="text-right">Payable</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $c)
                        @php
                            $statusBadge = $c->status_badge;
                            $packageName = $c->package_display_name;
                            $dueVal = (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
                        @endphp
                        <tr :class="{'is-selected': selectedIds.includes({{ $c->id }})}">
                            <!-- 0. Selection Checkbox -->
                            <td class="text-center">
                                <input type="checkbox" 
                                       :value="{{ $c->id }}" 
                                       :checked="selectedIds.includes({{ $c->id }})"
                                       @change="toggleRow({{ $c->id }}, {{ $dueVal }})"
                                       class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                            </td>

                            <!-- 1. Customer ID -->
                            <td class="font-mono text-cyan-800 font-semibold">
                                <a href="{{ route('reseller.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
                                    {{ $c->customer_id }}
                                </a>
                            </td>

                            <!-- 2. Name -->
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('reseller.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
                                    {{ $c->name }}
                                </a>
                            </td>

                            <!-- 3. Username -->
                            <td class="font-mono text-slate-700">
                                {{ $c->username }}
                            </td>

                            <!-- 4. Phone -->
                            <td class="font-mono text-slate-600">
                                {{ $c->phone ?: '--' }}
                            </td>

                            <!-- 5. Zone -->
                            <td class="text-slate-600">
                                {{ $c->zone ?: '--' }}
                            </td>

                            <!-- 6. Package -->
                            <td class="font-medium text-slate-800">
                                {{ $packageName }}
                            </td>

                            <!-- 7. Status Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $statusBadge['class'] }} shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 8. Expiry Date -->
                            <td class="font-mono text-slate-600">
                                {{ $c->expiry_date ? $c->expiry_date->format('d M Y') : '--' }}
                            </td>

                            <!-- 9. Monthly Bill -->
                            <td class="font-mono text-right text-slate-700">
                                @currency($c->monthly_bill)
                            </td>

                            <!-- 10. Due Amount -->
                            <td class="font-mono text-right font-semibold {{ $c->due_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                @currency($c->due_amount)
                            </td>

                            <!-- 11. Total Payable Amount -->
                            <td class="font-mono text-right font-bold text-emerald-700 bg-emerald-50/30">
                                @currency($dueVal)
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-users-slash text-2xl text-slate-300 mb-2 block"></i>
                                No subscribers found matching your bulk filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50/80 border-t-2 border-slate-200 divide-y divide-slate-200/80 text-xs">
                    <!-- 1. Gross Bill -->
                    <tr>
                        <td colspan="11" class="text-right py-1.5 px-3 font-semibold text-slate-700">
                            Gross Bill (<span x-text="selectedIds.length"></span> Users):
                        </td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-amber-900">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedGrossBill.toFixed(2)"></span>
                        </td>
                    </tr>

                    <!-- 2. Reseller Profit -->
                    <tr>
                        <td colspan="11" class="text-right py-1.5 px-3 font-semibold text-indigo-800">
                            Your Profit (<span x-text="resellerCommissionRate"></span>%):
                        </td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-indigo-700">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                        </td>
                    </tr>

                    <!-- 3. Admin Share -->
                    <tr class="bg-emerald-50/50">
                        <td colspan="11" class="text-right py-1.5 px-3 font-bold text-emerald-900">
                            Admin Share (Net Payable):
                        </td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-emerald-800">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($subscribers->hasPages())
            <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-200">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>

    <!-- 5. TABLE BOTTOM LIVE FINANCIAL STRIP & SUBMISSION BOX -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-4">
        
        <!-- Live Financial Calculation Cards (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Card 1: Gross Total Bill -->
            <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-lg">
                <span class="text-[10px] font-semibold text-amber-800 uppercase tracking-wider block">1. Gross Total Bill</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-amber-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedGrossBill.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-amber-700">
                        (<span x-text="selectedIds.length"></span> Subscribers)
                    </span>
                </div>
            </div>

            <!-- Card 2: Admin Share -->
            <div class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-lg">
                <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider block">2. Admin Share (Net)</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-emerald-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-emerald-700">
                        To Deduct
                    </span>
                </div>
            </div>

            <!-- Card 3: Reseller Profit -->
            <div class="p-3 bg-indigo-50/80 border border-indigo-200 rounded-lg">
                <span class="text-[10px] font-semibold text-indigo-800 uppercase tracking-wider block">3. Your Profit</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-indigo-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-indigo-700 font-mono">
                        <span x-text="resellerCommissionRate"></span>% Margin
                    </span>
                </div>
            </div>

            <!-- Card 4: Reseller Wallet -->
            <div class="p-3 bg-purple-50/80 border border-purple-200 rounded-lg" :class="{'ring-2 ring-rose-300 bg-rose-50/50': isWalletInsufficient && paymentMethod === 'reseller_wallet'}">
                <span class="text-[10px] font-semibold text-purple-800 uppercase tracking-wider block">4. Your Wallet</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono" :class="isWalletInsufficient ? 'text-rose-700' : 'text-purple-900'">
                        {{ $currencySymbol ?? '৳' }} <span x-text="resellerWalletBalance.toFixed(2)"></span>
                    </span>
                    <span class="text-[10px] font-medium" :class="isWalletInsufficient ? 'text-rose-600 font-bold' : 'text-purple-700'">
                        Avail: {{ $currencySymbol ?? '৳' }} <span x-text="resellerTotalAvailable.toFixed(2)"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Warning Alert if Wallet Insufficient -->
        <div x-show="paymentMethod === 'reseller_wallet' && isWalletInsufficient" 
             x-transition 
             class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800 flex items-center gap-2"
             style="display: none;">
            <i class="fas fa-triangle-exclamation text-rose-600 flex-shrink-0"></i>
            <span>Warning: Your available wallet balance ({{ $currencySymbol ?? '৳' }} <span x-text="resellerTotalAvailable.toFixed(2)"></span>) is less than Admin Share ({{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>). Please recharge your wallet or choose another payment method.</span>
        </div>

        <!-- Payment Execution Form -->
        <form @submit.prevent="confirmAndExecuteBulkPayment()" class="pt-3 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                
                <!-- Payment Method Select -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        Payment Method <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="paymentMethod" 
                            required
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="reseller_wallet">💼 Reseller Wallet (Auto-Debit Admin Share)</option>
                        <option value="cash">💵 Cash In Hand</option>
                        <option value="bkash">📱 bKash</option>
                        <option value="nagad">📱 Nagad</option>
                        <option value="rocket">📱 Rocket</option>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
                        <option value="pos">💳 Card / POS</option>
                        <option value="online">🌐 Online Gateway</option>
                        <option value="other">⚙️ Other</option>
                    </select>
                </div>

                <!-- Billing Month -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Billing Month</label>
                    <input type="text" 
                           x-model="billingMonth" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="e.g. {{ date('F Y') }}">
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Batch Remarks / Notes</label>
                    <input type="text" 
                           x-model="notes" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="Optional batch notes...">
                </div>

                <!-- Submit Execution Button -->
                <div>
                    <button type="submit" 
                            :disabled="loading || selectedIds.length === 0 || (paymentMethod === 'reseller_wallet' && isWalletInsufficient)"
                            class="w-full px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-xs transition inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="loading" style="display: none;"></i>
                        <i class="fas fa-check-double text-xs" x-show="!loading"></i>
                        <span>Confirm &amp; Process (<span x-text="selectedIds.length"></span>)</span>
                    </button>
                </div>
            </div>

            <!-- Automations Checkboxes -->
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" 
                           x-model="extendValidity" 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                    <span>Auto Extend Validity (+1 Month from expiry date)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" 
                           x-model="reactivateLine" 
                           class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 text-xs cursor-pointer">
                    <span>Auto Re-activate in MikroTik Router &amp; FreeRADIUS</span>
                </label>
            </div>
        </form>
    </div>

    <!-- 6. Toast Notification Container -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 px-4 py-2.5 rounded-xl shadow-lg text-xs font-semibold flex items-center gap-2 border"
         :class="{
             'bg-emerald-600 text-white border-emerald-700': toast.type === 'success',
             'bg-rose-600 text-white border-rose-700': toast.type === 'error',
             'bg-blue-600 text-white border-blue-700': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas" :class="{
            'fa-circle-check': toast.type === 'success',
            'fa-circle-xmark': toast.type === 'error',
            'fa-info-circle': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bulkPaymentManager() {
    return {
        tableRows: @js($subscribers->map(function($c) {
            return [
                'id' => $c->id,
                'due' => (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0)),
            ];
        })),
        selectedIds: [],
        selectAll: false,
        paymentMethod: 'reseller_wallet',
        billingMonth: '{{ date('F Y') }}',
        notes: '',
        extendValidity: true,
        reactivateLine: true,
        loading: false,

        // Financial metrics from server
        resellerCommissionRate: {{ (float)($kpis['commission_rate'] ?? 0) }},
        resellerWalletBalance: {{ (float)($kpis['reseller_wallet'] ?? 0) }},
        resellerCreditLimit: {{ (float)($kpis['reseller_credit'] ?? 0) }},
        resellerTotalAvailable: {{ (float)($kpis['reseller_available'] ?? 0) }},
        currencySymbol: '{{ $currencySymbol ?? '৳' }}',

        toast: {
            show: false,
            message: '',
            type: 'info'
        },

        init() {
            // By default auto-select all subscribers on the page
            this.selectAllRows();
        },

        showToast(message, type = 'info') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        // Toggle individual row
        toggleRow(id, due) {
            const index = this.selectedIds.indexOf(id);
            if (index > -1) {
                this.selectedIds.splice(index, 1);
            } else {
                this.selectedIds.push(id);
            }
            this.selectAll = (this.selectedIds.length === this.tableRows.length && this.tableRows.length > 0);
        },

        // Toggle select all checkbox in table header
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectAllRows();
            } else {
                this.clearAllSelections();
            }
        },

        selectAllRows() {
            this.selectedIds = this.tableRows.map(r => r.id);
            this.selectAll = this.tableRows.length > 0;
        },

        clearAllSelections() {
            this.selectedIds = [];
            this.selectAll = false;
        },

        // Computed Selected Gross Bill
        get selectedGrossBill() {
            return this.tableRows
                .filter(r => this.selectedIds.includes(r.id))
                .reduce((sum, r) => sum + (parseFloat(r.due) || 0), 0);
        },

        // Computed Selected Reseller Commission
        get selectedResellerCommission() {
            const gross = this.selectedGrossBill;
            return (gross * this.resellerCommissionRate) / 100;
        },

        // Computed Selected Admin Share Net
        get selectedAdminShare() {
            const gross = this.selectedGrossBill;
            const comm = this.selectedResellerCommission;
            return Math.max(0, gross - comm);
        },

        // Computed Check if Reseller Wallet is Insufficient for Admin Share
        get isWalletInsufficient() {
            if (this.paymentMethod === 'reseller_wallet') {
                return this.resellerTotalAvailable < this.selectedAdminShare;
            }
            return false;
        },

        printSelectedOrAll() {
            let url = new URL("{{ route('reseller.customers.bulk-payments.print') }}", window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((val, key) => url.searchParams.set(key, val));
            if (this.selectedIds.length > 0 && this.selectedIds.length < this.tableRows.length) {
                url.searchParams.set('ids', this.selectedIds.join(','));
            }
            window.open(url.toString(), '_blank');
        },

        // Confirm & Execute Bulk Payment
        async confirmAndExecuteBulkPayment() {
            if (this.selectedIds.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Subscribers Selected',
                        text: 'Please select at least one subscriber before processing bulk payments.',
                        confirmButtonColor: '#0891b2'
                    });
                } else {
                    alert('Please select at least one subscriber.');
                }
                return;
            }

            if (this.paymentMethod === 'reseller_wallet' && this.isWalletInsufficient) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Insufficient Wallet Balance',
                        html: `Your total available balance is <b>${this.currencySymbol} ${this.resellerTotalAvailable.toFixed(2)}</b>, but required Admin Share is <b>${this.currencySymbol} ${this.selectedAdminShare.toFixed(2)}</b>.<br><br>Please recharge your wallet or choose a different payment method.`,
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    alert('Insufficient Wallet Balance.');
                }
                return;
            }

            const count = this.selectedIds.length;
            const grossFormatted = `${this.currencySymbol} ${this.selectedGrossBill.toFixed(2)}`;
            const adminShareFormatted = `${this.currencySymbol} ${this.selectedAdminShare.toFixed(2)}`;
            const profitFormatted = `${this.currencySymbol} ${this.selectedResellerCommission.toFixed(2)}`;

            let confirmHtml = `
                <div class="text-left text-xs space-y-2">
                    <p>Are you sure you want to process bulk payments for <b>${count}</b> selected subscribers?</p>
                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg space-y-1 font-mono text-xs">
                        <div class="flex justify-between"><span>Gross Total Bill:</span> <b>${grossFormatted}</b></div>
                        <div class="flex justify-between text-indigo-700"><span>Your Profit (${this.resellerCommissionRate}%):</span> <b>+${profitFormatted}</b></div>
                        <div class="flex justify-between text-emerald-700 border-t border-slate-200 pt-1 font-bold"><span>Admin Share Net:</span> <b>${adminShareFormatted}</b></div>
                        <div class="flex justify-between text-slate-700 pt-1"><span>Payment Method:</span> <b>${this.paymentMethod.toUpperCase()}</b></div>
                    </div>
                </div>
            `;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Confirm Bulk Payment',
                    html: confirmHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: `<i class="fas fa-check-double mr-1"></i> Yes, Process ${count} Subscribers`,
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b'
                });

                if (!result.isConfirmed) {
                    return;
                }
            } else {
                if (!confirm(`Are you sure you want to process bulk payments for ${count} subscribers?`)) {
                    return;
                }
            }

            this.loading = true;

            try {
                const res = await fetch(`{{ route('reseller.customers.bulk-payments.process') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_ids: this.selectedIds,
                        payment_method: this.paymentMethod,
                        billing_month: this.billingMonth,
                        notes: this.notes,
                        extend_validity: this.extendValidity,
                        reactivate_line: this.reactivateLine
                    })
                });

                const data = await res.json();

                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Bulk Processing Complete!',
                            text: data.message || `Successfully processed payments for ${data.processed_count} subscribers.`,
                            confirmButtonColor: '#0891b2'
                        });
                    } else {
                        alert(data.message);
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Bulk Processing Failed',
                            text: data.message || 'An error occurred during bulk payment processing.',
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                }
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An unexpected connection error occurred. Please try again.',
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    alert('Network error occurred.');
                }
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
