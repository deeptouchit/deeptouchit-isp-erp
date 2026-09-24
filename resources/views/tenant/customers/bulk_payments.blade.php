@extends('tenant.layouts.app')

@section('title', 'Bulk Bill & Due Payments - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Bulk payment dedicated page tweaks */
    .saas-table tbody tr.is-selected {
        background-color: #f0fdf4 !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="bulkPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-md border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 leading-none">Bulk Bill &amp; Due Payments</h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Print Statement / Demand Sheet -->
            <button type="button" 
                    @click="printSelectedOrAll()" 
                    class="px-3 py-1.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Print Bulk Bill Demand & Due Statement">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print Sheet</span>
            </button>

            <!-- Back to Due & Expired Customers -->
            <a href="{{ route('tenant.customers.due') }}" 
               class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Due Customers</span>
            </a>

            <!-- Customer Master -->
            <a href="{{ route('tenant.customers.index') }}" 
               class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>All Customers</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Processing Mode -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Scope Mode</span>
                <span class="text-[13px] font-bold text-slate-800 font-mono leading-tight block truncate">
                    {{ $mode === 'reseller' ? ($selectedReseller ? $selectedReseller->code ?: $selectedReseller->name : 'Reseller Scope') : 'ISP Direct Retail' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas {{ $mode === 'reseller' ? 'fa-handshake' : 'fa-building' }}"></i>
            </div>
        </div>

        <!-- Metric 2: Filtered Subscribers -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-cyan-700 uppercase tracking-wider block truncate">Scope Subscribers</span>
                <span class="text-[13px] font-bold text-cyan-700 font-mono leading-tight block">{{ number_format($kpis['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 3: Total Gross Bill -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Total Gross Bill</span>
                <span class="text-[13px] font-bold text-amber-700 font-mono leading-tight block">@currency($kpis['gross_bill'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Metric 4: Admin Share (Payable) -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Admin Share</span>
                <span class="text-[13px] font-bold text-emerald-700 font-mono leading-tight block">@currency($kpis['admin_share'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Metric 5: Reseller Commission -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Reseller Profit</span>
                <span class="text-[13px] font-bold text-indigo-700 font-mono leading-tight block">@currency($kpis['reseller_commission'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        <!-- Metric 6: Reseller Wallet -->
        <div class="px-2.5 py-1.5 rounded-md bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-600 uppercase tracking-wider block truncate">Reseller Wallet</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $kpis['reseller_available'] >= $kpis['admin_share'] ? 'text-slate-800' : 'text-rose-600' }}">
                    @currency($kpis['reseller_wallet'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-50 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- 3. Scope Mode Switcher & Filter Toolbar -->
    <div class="bg-white p-3 rounded-md border border-slate-200 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('tenant.customers.bulk-payments') }}" class="space-y-2.5">
            
            <!-- Scope Mode Buttons & Quick Presets -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-md">
                    <button type="button" 
                            @click="setMode('reseller')"
                            :class="mode === 'reseller' ? 'bg-white text-purple-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1 rounded text-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fas fa-handshake text-[10px]"></i>
                        <span>Reseller / Sub-ISP Wise</span>
                    </button>
                    <button type="button" 
                            @click="setMode('direct')"
                            :class="mode === 'direct' ? 'bg-white text-cyan-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1 rounded text-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fas fa-building text-[10px]"></i>
                        <span>ISP Direct / Custom Scope</span>
                    </button>
                </div>
                <input type="hidden" name="mode" x-model="mode">

                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-500 font-medium">Auto-Selection:</span>
                    <button type="button" 
                            @click="selectAllRows()" 
                            class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-semibold border border-emerald-200 cursor-pointer">
                        Select All (<span x-text="tableRows.length"></span>)
                    </button>
                    <button type="button" 
                            @click="clearAllSelections()" 
                            class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-semibold cursor-pointer">
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Filter Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 lg:grid-cols-12 gap-2">
                
                <!-- Reseller Select (Highlighted when Reseller Mode) -->
                <div class="col-span-2 sm:col-span-3 lg:col-span-3" x-show="mode === 'reseller'">
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Reseller / Franchise <span class="text-rose-500">*</span></label>
                    <select name="reseller_id" 
                            x-model="selectedResellerId"
                            @change="onResellerChange($event)"
                            class="w-full px-2.5 py-1.5 bg-purple-50/50 border border-purple-200 rounded-md text-xs font-semibold text-purple-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                        <option value="">-- Choose Reseller --</option>
                        @foreach($resellers as $res)
                            <option value="{{ $res->id }}" 
                                    data-commission="{{ (float)$res->commission_rate }}"
                                    data-wallet="{{ (float)$res->wallet_balance }}"
                                    data-credit="{{ (float)$res->credit_limit }}"
                                    {{ (string)$resellerId === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }}) - Comm: {{ (float)$res->commission_rate }}%
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Zone Select -->
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Area / Zone</label>
                    <select name="zone" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Zones</option>
                        @foreach($zones as $z)
                            <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Select -->
                <div class="col-span-1 sm:col-span-2 lg:col-span-2">
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Package Plan</label>
                    <select name="package_id" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Packages</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>
                                {{ $pkg->mikrotik_profile ?: ($pkg->name ?: $pkg->package_name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Select -->
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Status Filter</label>
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all_due" {{ $status === 'all_due' ? 'selected' : '' }}>Pending Dues &amp; Expired</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>🟢 Active Only</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>🔴 Expired Only</option>
                        <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>⛔ Suspended Only</option>
                        <option value="due" {{ $status === 'due' ? 'selected' : '' }}>🟡 Due Only</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-span-2 sm:col-span-2 lg:col-span-2">
                    <label class="block text-[10px] font-semibold uppercase text-slate-600 mb-0.5">Search User / Phone</label>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="ID / Username / Phone..."
                           class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Filter & Reset Buttons (Strict Universal Standard) -->
                <div class="col-span-2 sm:col-span-1 lg:col-span-1 flex items-end gap-1.5 flex-shrink-0">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.customers.bulk-payments') }}" 
                       class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Reseller Information Banner (when Reseller is selected) -->
        @if($mode === 'reseller' && $selectedReseller)
            <div class="p-3 bg-purple-50/70 border border-purple-200 rounded-md flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs flex-shrink-0">
                        {{ substr($selectedReseller->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-purple-950 flex items-center gap-1.5">
                            <span>{{ $selectedReseller->name }}</span>
                            <span class="px-1.5 py-0.2 rounded bg-purple-200 text-purple-800 text-[10px] font-mono">{{ $selectedReseller->code ?: 'RES-' . $selectedReseller->id }}</span>
                        </div>
                        <div class="text-[11px] text-purple-700">
                            <span>📞 {{ $selectedReseller->mobile ?: 'N/A' }}</span>
                            <span class="mx-1.5">|</span>
                            <span>Billing Type: <strong>{{ ucfirst($selectedReseller->billing_type ?? 'Prepaid') }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap sm:ml-auto">
                    <!-- Commission Rate -->
                    <div class="px-2.5 py-1 bg-white rounded border border-purple-200 text-center">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">Commission Rate</span>
                        <span class="text-xs font-bold text-purple-700 font-mono">{{ (float)$selectedReseller->commission_rate }}%</span>
                    </div>

                    <!-- Current Wallet Balance -->
                    <div class="px-2.5 py-1 bg-white rounded border border-purple-200 text-center">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">Wallet Balance</span>
                        <span class="text-xs font-bold text-slate-900 font-mono">@currency($selectedReseller->wallet_balance)</span>
                    </div>

                    <!-- Credit Limit -->
                    <div class="px-2.5 py-1 bg-white rounded border border-purple-200 text-center">
                        <span class="text-[9px] text-slate-500 uppercase font-semibold block">Credit Limit</span>
                        <span class="text-xs font-bold text-slate-700 font-mono">@currency($selectedReseller->credit_limit)</span>
                    </div>

                    <!-- Total Available -->
                    <div class="px-2.5 py-1 bg-emerald-50 rounded border border-emerald-200 text-center">
                        <span class="text-[9px] text-emerald-800 uppercase font-semibold block">Total Available</span>
                        <span class="text-xs font-bold text-emerald-700 font-mono">@currency($selectedReseller->total_available_balance)</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
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
                            $packageName = $c->mikrotik_profile_name ?: ($c->package ? ($c->package->mikrotik_profile ?: ($c->package->name ?: $c->package->package_name)) : ($c->package_name ?: 'default'));
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
                                <a href="{{ route('tenant.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
                                    {{ $c->customer_id }}
                                </a>
                            </td>

                            <!-- 2. Name -->
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('tenant.customers.show', $c->id) }}" target="_blank" class="hover:underline hover:text-cyan-600">
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
                            Reseller Profit (<span x-text="resellerCommissionRate"></span>%):
                        </td>
                        <td class="text-right py-1.5 px-2.5 font-mono font-bold text-indigo-700">
                            {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                        </td>
                    </tr>

                    <!-- 3. Admin Share -->
                    <tr class="bg-emerald-50/50">
                        <td colspan="11" class="text-right py-1.5 px-3 font-bold text-emerald-900">
                            Admin Share:
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
    <div class="bg-white rounded-md border border-slate-200 shadow-sm p-4 space-y-4">
        
        <!-- Live Financial Calculation Cards (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Card 1: Gross Total Bill -->
            <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-md">
                <span class="text-[10px] font-semibold text-amber-800 uppercase tracking-wider block">1. Gross Bill</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-amber-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedGrossBill.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-amber-700">
                        (<span x-text="selectedIds.length"></span> Users)
                    </span>
                </div>
            </div>

            <!-- Card 2: Admin Share -->
            <div class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-md">
                <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider block">2. Admin Share</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-emerald-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-emerald-700">
                        Net Payable
                    </span>
                </div>
            </div>

            <!-- Card 3: Reseller Profit -->
            <div class="p-3 bg-indigo-50/80 border border-indigo-200 rounded-md">
                <span class="text-[10px] font-semibold text-indigo-800 uppercase tracking-wider block">3. Reseller Profit</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono text-indigo-900">
                        {{ $currencySymbol ?? '৳' }} <span x-text="selectedResellerCommission.toFixed(2)"></span>
                    </span>
                    <span class="text-[10.5px] font-medium text-indigo-700 font-mono">
                        <span x-text="resellerCommissionRate"></span>% Profit
                    </span>
                </div>
            </div>

            <!-- Card 4: Reseller Wallet -->
            <div class="p-3 bg-purple-50/80 border border-purple-200 rounded-md" :class="{'ring-2 ring-rose-300 bg-rose-50/50': isWalletInsufficient && paymentMethod === 'reseller_wallet'}">
                <span class="text-[10px] font-semibold text-purple-800 uppercase tracking-wider block">4. Reseller Wallet</span>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-base font-bold font-mono" :class="isWalletInsufficient ? 'text-rose-700' : 'text-purple-900'">
                        {{ $currencySymbol ?? '৳' }} <span x-text="resellerWalletBalance.toFixed(2)"></span>
                    </span>
                    <span class="text-[10px] font-medium" :class="isWalletInsufficient ? 'text-rose-600 font-bold' : 'text-purple-700'">
                        <span x-show="mode === 'reseller'">Avail: {{ $currencySymbol ?? '৳' }} <span x-text="resellerTotalAvailable.toFixed(2)"></span></span>
                        <span x-show="mode !== 'reseller'">ISP HQ</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Warning Alert if Wallet Insufficient -->
        <div x-show="mode === 'reseller' && paymentMethod === 'reseller_wallet' && isWalletInsufficient" 
             x-transition 
             class="p-2.5 bg-rose-50 border border-rose-200 rounded-md text-xs text-rose-800 flex items-center gap-2">
            <i class="fas fa-triangle-exclamation text-rose-600"></i>
            <span>Warning: Reseller available balance ({{ $currencySymbol ?? '৳' }} <span x-text="resellerTotalAvailable.toFixed(2)"></span>) is less than Admin Share ({{ $currencySymbol ?? '৳' }} <span x-text="selectedAdminShare.toFixed(2)"></span>). Please top up reseller wallet or choose Cash/Online payment method.</span>
        </div>

        <!-- Payment Method Selection & Execution Form -->
        <form @submit.prevent="confirmAndExecuteBulkPayment()" class="pt-3 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                
                <!-- Payment Method Select -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        Payment Method <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="paymentMethod" 
                            required
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs font-semibold focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <template x-if="mode === 'reseller'">
                            <option value="reseller_wallet">💼 Reseller Wallet (Auto-Debit Admin Share)</option>
                        </template>
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
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="e.g. {{ date('F Y') }}">
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Batch Remarks / Notes</label>
                    <input type="text" 
                           x-model="notes" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"
                           placeholder="Optional batch notes...">
                </div>

                <!-- Submit Execution Button -->
                <div>
                    <button type="submit" 
                            :disabled="loading || selectedIds.length === 0 || (paymentMethod === 'reseller_wallet' && isWalletInsufficient)"
                            class="w-full px-4 py-2 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md transition inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
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

    <!-- 6. Toast Notification Floating Container -->
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
function bulkPaymentManager() {
    const rawRows = @json($subscribers->map(fn($c) => [
        'id' => $c->id,
        'due' => (float)($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0))
    ]));

    return {
        mode: '{{ $mode }}',
        selectedResellerId: '{{ $resellerId ?? "" }}',
        resellerCommissionRate: {{ (float)($kpis['commission_rate'] ?? 0) }},
        resellerWalletBalance: {{ (float)($kpis['reseller_wallet'] ?? 0) }},
        resellerCreditLimit: {{ (float)($kpis['reseller_credit'] ?? 0) }},
        resellerTotalAvailable: {{ (float)($kpis['reseller_available'] ?? 0) }},
        
        tableRows: rawRows,
        selectedIds: rawRows.map(r => r.id), // Auto-select rows by default for fast bulk payment
        selectedDueMap: rawRows.reduce((acc, cur) => { acc[cur.id] = cur.due; return acc; }, {}),
        selectAll: true,

        paymentMethod: '{{ $mode === "reseller" && $selectedReseller ? "reseller_wallet" : "cash" }}',
        billingMonth: '{{ date("F Y") }}',
        notes: '',
        extendValidity: true,
        reactivateLine: true,
        loading: false,

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
            this.toast.timeout = setTimeout(() => { this.toast.show = false; }, 4000);
        },

        get selectedGrossBill() {
            let sum = 0;
            for (const id of this.selectedIds) {
                sum += (this.selectedDueMap[id] || 0);
            }
            return sum;
        },

        get selectedResellerCommission() {
            if (this.mode === 'reseller' && this.resellerCommissionRate > 0) {
                return (this.selectedGrossBill * this.resellerCommissionRate) / 100;
            }
            return 0.00;
        },

        get selectedAdminShare() {
            return Math.max(0, this.selectedGrossBill - this.selectedResellerCommission);
        },

        get isWalletInsufficient() {
            if (this.mode === 'reseller' && this.paymentMethod === 'reseller_wallet') {
                return this.resellerTotalAvailable < this.selectedAdminShare;
            }
            return false;
        },

        setMode(newMode) {
            this.mode = newMode;
            if (newMode === 'direct') {
                this.selectedResellerId = '';
                this.paymentMethod = 'cash';
                this.resellerCommissionRate = 0;
            }
            // Auto submit form with new mode
            const url = new URL(window.location.href);
            url.searchParams.set('mode', newMode);
            if (newMode === 'direct') {
                url.searchParams.delete('reseller_id');
            }
            window.location.href = url.toString();
        },

        onResellerChange(e) {
            const selectEl = e.target;
            const opt = selectEl.options[selectEl.selectedIndex];
            if (opt && opt.dataset) {
                this.resellerCommissionRate = parseFloat(opt.dataset.commission || 0);
                this.resellerWalletBalance = parseFloat(opt.dataset.wallet || 0);
                this.resellerCreditLimit = parseFloat(opt.dataset.credit || 0);
                this.resellerTotalAvailable = this.resellerWalletBalance + this.resellerCreditLimit;
            }
            selectEl.form.submit();
        },

        toggleSelectAll(e) {
            this.selectAll = e.target.checked;
            if (this.selectAll) {
                this.selectedIds = this.tableRows.map(r => r.id);
            } else {
                this.selectedIds = [];
            }
        },

        selectAllRows() {
            this.selectedIds = this.tableRows.map(r => r.id);
            this.selectAll = true;
        },

        clearAllSelections() {
            this.selectedIds = [];
            this.selectAll = false;
        },

        toggleRow(id, due) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
                this.selectAll = false;
            } else {
                this.selectedIds.push(id);
                if (this.selectedIds.length === this.tableRows.length) {
                    this.selectAll = true;
                }
            }
        },

        printSelectedOrAll() {
            let url = new URL("{{ route('tenant.customers.bulk-payments.print') }}", window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.forEach((val, key) => url.searchParams.set(key, val));
            if (this.selectedIds.length > 0 && this.selectedIds.length < this.tableRows.length) {
                url.searchParams.set('ids', this.selectedIds.join(','));
            }
            window.open(url.toString(), '_blank');
        },

        async confirmAndExecuteBulkPayment() {
            if (this.selectedIds.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Subscribers Selected',
                        text: 'Please select at least one subscriber from the table for bulk payment.',
                        confirmButtonColor: '#0891b2'
                    });
                } else {
                    this.showToast('Please select at least one subscriber for bulk payment.', 'error');
                }
                return;
            }

            if (this.paymentMethod === 'reseller_wallet' && this.isWalletInsufficient) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Insufficient Reseller Wallet',
                        text: 'Reseller available balance is insufficient for admin share payment.',
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    this.showToast('Reseller wallet balance is insufficient for admin share payment.', 'error');
                }
                return;
            }

            const count = this.selectedIds.length;
            const grossBill = this.selectedGrossBill.toFixed(2);
            const adminShare = this.selectedAdminShare.toFixed(2);
            const resellerComm = this.selectedResellerCommission.toFixed(2);
            const methodLabel = this.paymentMethod.replace(/_/g, ' ').toUpperCase();
            const curr = '{{ $currencySymbol ?? '৳' }}';

            let htmlContent = `
                <div class="text-left text-xs space-y-2.5 p-1">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-700 space-y-1.5 font-mono">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Total Selected:</span>
                            <strong class="text-slate-900 font-bold">${count} Subscribers</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Gross Total Bill:</span>
                            <strong class="text-slate-900">${curr} ${grossBill}</strong>
                        </div>`;

            if (this.isResellerMode) {
                htmlContent += `
                        <div class="flex justify-between border-t border-slate-200 pt-1 text-cyan-700 font-bold">
                            <span class="font-sans">Admin Share (Due):</span>
                            <span>${curr} ${adminShare}</span>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <span class="font-sans">Reseller Profit:</span>
                            <span>${curr} ${resellerComm}</span>
                        </div>`;
            }

            htmlContent += `
                        <div class="flex justify-between border-t border-slate-200 pt-1">
                            <span class="text-slate-500 font-sans">Payment Method:</span>
                            <span class="font-bold text-slate-800">${methodLabel}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Billing Month:</span>
                            <span class="font-bold text-slate-800">${this.billingMonth}</span>
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-500 space-y-0.5">
                        <p class="text-emerald-700 font-medium">✓ Auto extend validity (+1 month)</p>
                        <p class="text-emerald-700 font-medium">✓ Auto reactivate in MikroTik & FreeRADIUS</p>
                    </div>
                </div>
            `;

            let isConfirmed = false;
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Confirm Bulk Payment?',
                    html: htmlContent,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-check-double mr-1"></i> Yes, Confirm & Process',
                    cancelButtonText: 'Cancel'
                });
                isConfirmed = result.isConfirmed;
            } else {
                const confirmMsg = `Process bulk payment for ${count} subscribers?\nGross Total: ${curr}${grossBill}\nAdmin Share: ${curr}${adminShare}\nMethod: ${methodLabel}`;
                isConfirmed = confirm(confirmMsg);
            }

            if (!isConfirmed) {
                return;
            }

            this.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.customers.bulk-payments.process') }}`, {
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
                        reactivate_line: this.reactivateLine,
                        reseller_id: this.selectedResellerId || null
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Batch Processed Successfully!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        this.showToast(data.message, 'success');
                    }
                    if (data.reseller_wallet_balance !== null) {
                        this.resellerWalletBalance = parseFloat(data.reseller_wallet_balance);
                        this.resellerTotalAvailable = this.resellerWalletBalance + this.resellerCreditLimit;
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Processing Failed',
                            text: data.message || 'Bulk payment execution failed.',
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        this.showToast(data.message || 'Bulk payment execution failed.', 'error');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An unexpected network error occurred during bulk payment processing.',
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    this.showToast('Network error during bulk payment processing.', 'error');
                }
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
