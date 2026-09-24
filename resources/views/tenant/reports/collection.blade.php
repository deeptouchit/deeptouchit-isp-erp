@extends('tenant.layouts.app')

@section('title', 'Collection & Due Report - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .progress-bar-fill {
        transition: width 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="collectionReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Collection &amp; Due Breakdown Analytics</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Quick Filter Indicator Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fas fa-calendar-days text-slate-500 text-[10px]"></i>
                <span>{{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.collection.print', request()->all()) }}" 
               target="_blank" 
               class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>Print Statement</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.collection.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Billed Amount --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Billed</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    @currency($totalBilled)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 2: Total Collected (Received) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    @currency($totalCollected)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        {{-- Card 3: Total Outstanding Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block">
                    @currency($totalDue)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 4: Collection Efficiency % --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Recovery Rate</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $collectionEfficiency }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>

        {{-- Card 5: Due Subscribers Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Due Subscribers</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    {{ number_format($dueSubscribersCount) }} Users
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-slash"></i>
            </div>
        </div>

        {{-- Card 6: Discounts / Waivers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Discounts</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    @currency($totalDiscount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-tags"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (2-Row Day-to-Day Layout) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.reports.collection') }}" class="space-y-2.5">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- Row 1: Period Preset, Date From, Date To, Revenue Stream Scope --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                {{-- Filter 1: Period Preset --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Period Preset</label>
                    <select name="preset" x-model="selectedPreset" @change="handlePeriodChange($event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="today" {{ $preset === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $preset === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $preset === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ $preset === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ $preset === 'this_year' ? 'selected' : '' }}>This Year ({{ date('Y') }})</option>
                        <option value="last_12_months" {{ $preset === 'last_12_months' ? 'selected' : '' }}>Last 12 Months</option>
                        <option value="all" {{ $preset === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="custom" {{ $preset === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                    </select>
                </div>

                {{-- Filter 2: Date From --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Date From</label>
                    <input type="date" name="date_from" x-model="dateFrom" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                </div>

                {{-- Filter 3: Date To --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Date To</label>
                    <input type="date" name="date_to" x-model="dateTo" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                </div>

                {{-- Filter 4: Business Stream Scope --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Collection Scope</label>
                    <select name="stream" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all" {{ ($stream ?? 'all') === 'all' ? 'selected' : '' }}>All Streams (Retail + Wholesale + Direct)</option>
                        <option value="retail" {{ ($stream ?? 'all') === 'retail' ? 'selected' : '' }}>Retail Subscribers Collection Only</option>
                        <option value="wholesale" {{ ($stream ?? 'all') === 'wholesale' ? 'selected' : '' }}>Sub-ISP Wholesale Billing &amp; Recharges</option>
                        <option value="gateway" {{ ($stream ?? 'all') === 'gateway' ? 'selected' : '' }}>Digital Payment Gateways (bKash/Nagad/SSL)</option>
                        <option value="direct" {{ ($stream ?? 'all') === 'direct' ? 'selected' : '' }}>Direct &amp; Other Incomes Only</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Zone, Collector, Package, Status, Action Buttons --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                {{-- Filter 5: Zone Selector --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Coverage Zone</label>
                    <select name="zone_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Coverage Zones</option>
                        @foreach($allZones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 6: Collector Staff Selector --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Billing Collector</label>
                    <select name="collector_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Collectors</option>
                        @foreach($allCollectors as $col)
                            <option value="{{ $col->id }}" {{ (string)$collectorId === (string)$col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 7: Package Selector --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Internet Package</label>
                    <select name="package_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Packages</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>{{ $pkg->name ?: $pkg->package_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 8: Due Status --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Account Status</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Accounts</option>
                        <option value="due" {{ $statusFilter === 'due' ? 'selected' : '' }}>🔴 With Dues Only</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>🟢 Active Subscribers</option>
                        <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>🟡 Suspended Accounts</option>
                        <option value="disconnected" {{ $statusFilter === 'disconnected' ? 'selected' : '' }}>⚪ Disconnected</option>
                    </select>
                </div>

                {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
                <div class="flex items-end gap-1.5">
                    <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.reports.collection') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER MULTI-DIMENSIONAL BREAKDOWN TABS & TABLES --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Tabs Header Bar --}}
        <div class="px-3.5 py-2 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-1 overflow-x-auto">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'zones']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'zones' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-location-dot text-[10px]"></i>
                    <span>Zones</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'zones' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($zoneMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'collectors']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'collectors' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-user-tie text-[10px]"></i>
                    <span>Collectors</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'collectors' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($collectorMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'packages']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'packages' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-cubes text-[10px]"></i>
                    <span>Packages</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'packages' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($packageMatrix) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'resellers']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'resellers' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-network-wired text-[10px]"></i>
                    <span>Resellers</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'resellers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($wholesaleMatrix ?? []) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'gateways']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'gateways' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-credit-card text-[10px]"></i>
                    <span>Gateways</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'gateways' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($gatewayMatrix ?? []) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'customers']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'customers' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-users text-[10px]"></i>
                    <span>Subscribers Ledger</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'customers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ $customerRecords->total() }}</span>
                </a>
            </div>

            @if($activeTab === 'customers')
                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('tenant.reports.collection') }}" class="flex items-center gap-1.5">
                        <input type="hidden" name="tab" value="customers">
                        <input type="hidden" name="preset" value="{{ $preset }}">
                        <input type="hidden" name="date_from" value="{{ request('date_from', $from->toDateString()) }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', $to->toDateString()) }}">
                        <input type="hidden" name="zone_id" value="{{ $zoneId }}">
                        <input type="hidden" name="collector_id" value="{{ $collectorId }}">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <div class="relative">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search subscriber..." class="pl-7 pr-2.5 py-1 text-xs bg-white border border-slate-200 rounded-lg focus:border-cyan-500 focus:outline-none transition w-44">
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- TAB CONTENT 1: ZONE BREAKDOWN --}}
        @if($activeTab === 'zones')
            <div class="overflow-x-auto min-h-[280px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Zone Name</th>
                            <th class="w-24 text-center">Subscribers</th>
                            <th class="w-24 text-center">Active</th>
                            <th class="text-right w-28">Total Billed</th>
                            <th class="text-right w-28 text-emerald-700 font-bold">Collected</th>
                            <th class="text-right w-28 text-rose-700 font-bold">Total Due</th>
                            <th class="text-center w-24">Recovery %</th>
                            <th class="no-sort w-16 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zoneMatrix as $idx => $z)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">{{ $z['name'] }}</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $z['subscribers_count'] }}</td>
                                <td class="text-center font-mono font-semibold text-emerald-700">{{ $z['active_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($z['total_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($z['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($z['total_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $z['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($z['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $z['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'zone_id' => $z['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="View Zone Customers">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-location-dot text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No zone breakdown data recorded in this workspace.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 2: COLLECTOR PERFORMANCE --}}
        @if($activeTab === 'collectors')
            <div class="overflow-x-auto min-h-[280px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Collector Staff</th>
                            <th class="w-28">Designation</th>
                            <th class="w-24 text-center">Assigned Users</th>
                            <th class="text-right w-28">Target Billed</th>
                            <th class="text-right w-28 text-emerald-700 font-bold">Collected</th>
                            <th class="text-right w-28 text-rose-700 font-bold">Pending Due</th>
                            <th class="text-center w-24">Recovery %</th>
                            <th class="no-sort w-16 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collectorMatrix as $idx => $col)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">{{ $col['name'] }}</td>
                                <td class="text-slate-600 text-xs">{{ $col['role'] }}</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $col['assigned_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($col['target_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($col['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($col['outstanding_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $col['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($col['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $col['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'collector_id' => $col['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="View Assigned Customers">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-user-tie text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No billing collector staff found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 3: PACKAGE BREAKDOWN --}}
        @if($activeTab === 'packages')
            <div class="overflow-x-auto min-h-[280px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Package Name</th>
                            <th class="w-20 text-center">Speed</th>
                            <th class="text-right w-24">Price</th>
                            <th class="w-24 text-center">Subscribers</th>
                            <th class="text-right w-28">Monthly Bill</th>
                            <th class="text-right w-28 text-emerald-700 font-bold">Collected</th>
                            <th class="text-right w-28 text-rose-700 font-bold">Total Due</th>
                            <th class="text-center w-24">Recovery %</th>
                            <th class="no-sort w-16 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packageMatrix as $idx => $pkg)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">{{ $pkg['name'] }}</td>
                                <td class="text-center font-mono text-cyan-700 font-semibold">{{ $pkg['speed'] }}</td>
                                <td class="text-right font-mono text-slate-700">@currency($pkg['price'])</td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ $pkg['subscribers_count'] }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($pkg['total_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($pkg['total_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($pkg['total_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $pkg['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($pkg['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $pkg['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.reports.collection', ['tab' => 'customers', 'package_id' => $pkg['id']]) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="View Package Subscribers">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-cubes text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No package analytics found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 4: SUB-ISP WHOLESALE RESELLERS --}}
        @if($activeTab === 'resellers')
            <div class="overflow-x-auto min-h-[280px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Reseller / Sub-ISP</th>
                            <th class="w-20 text-center">Type</th>
                            <th class="text-right w-24">Wallet</th>
                            <th class="text-right w-28">Period Billed</th>
                            <th class="text-right w-28 text-emerald-700 font-bold">Collected</th>
                            <th class="text-right w-28 text-rose-700 font-bold">Total Due</th>
                            <th class="text-center w-24">Recovery %</th>
                            <th class="no-sort w-16 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wholesaleMatrix ?? [] as $idx => $res)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">
                                    <a href="{{ url('admin/resellers/' . $res['id']) }}" class="text-cyan-800 hover:underline">
                                        {{ $res['name'] }}
                                    </a>
                                    <span class="text-[10px] text-slate-400 block font-mono">{{ $res['code'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $res['billing_type'] === 'POSTPAID' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $res['billing_type'] }}
                                    </span>
                                </td>
                                <td class="text-right font-mono text-slate-700">@currency($res['wallet_balance'])</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($res['period_billed'])</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($res['period_collected'])</td>
                                <td class="text-right font-mono font-bold text-rose-700 bg-rose-50/20">@currency($res['outstanding_due'])</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $res['efficiency_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($res['efficiency_pct'] >= 50 ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $res['efficiency_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ url('admin/finance/wholesale-billing?search=' . urlencode($res['name'])) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="View Wholesale Billing">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-network-wired text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No Sub-ISP reseller accounts found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 5: PAYMENT GATEWAYS BREAKDOWN --}}
        @if($activeTab === 'gateways')
            <div class="overflow-x-auto min-h-[280px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Payment Channel / Gateway</th>
                            <th class="w-24 text-center">Category</th>
                            <th class="text-center w-28">Transactions</th>
                            <th class="text-right w-36 text-emerald-700 font-bold">Total Inflow</th>
                            <th class="text-center w-28">Share %</th>
                            <th class="no-sort w-16 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gatewayMatrix ?? [] as $idx => $gw)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-[11px] text-slate-600">
                                            @if($gw['method_key'] === 'cash')
                                                <i class="fas fa-money-bill text-emerald-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'bkash'))
                                                <i class="fas fa-mobile-screen text-pink-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'nagad'))
                                                <i class="fas fa-mobile-screen text-orange-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'rocket'))
                                                <i class="fas fa-mobile-screen text-purple-600"></i>
                                            @elseif(str_contains($gw['method_key'], 'ssl') || str_contains($gw['method_key'], 'shurjopay'))
                                                <i class="fas fa-credit-card text-blue-600"></i>
                                            @else
                                                <i class="fas fa-wallet text-slate-600"></i>
                                            @endif
                                        </div>
                                        <span>{{ $gw['channel_name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $gw['is_gateway'] ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $gw['is_gateway'] ? 'DIGITAL PGW' : 'MANUAL / CASH' }}
                                    </span>
                                </td>
                                <td class="text-center font-mono font-semibold text-slate-700">{{ number_format($gw['txn_count']) }}</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">@currency($gw['total_amount'])</td>
                                <td class="text-center font-mono font-bold text-slate-700">
                                    <span class="px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-800">
                                        {{ $gw['share_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ url('admin/finance/gateway-transactions?gateway=' . urlencode($gw['method_key'])) }}" 
                                       class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition" 
                                       title="View Transactions">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-credit-card text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No payment inflow records logged in this period.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 6: SUBSCRIBERS DUE LEDGER --}}
        @if($activeTab === 'customers')
            <div class="overflow-x-auto min-h-[300px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-24">Customer ID</th>
                            <th>Subscriber Name</th>
                            <th>PPPoE User</th>
                            <th>Zone</th>
                            <th>Package</th>
                            <th class="text-right w-24">Monthly Bill</th>
                            <th class="text-right w-24 text-rose-700 font-bold">Due Amount</th>
                            <th class="w-24 text-center">Status</th>
                            <th class="w-28">Collector</th>
                            <th class="no-sort w-14 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerRecords as $cust)
                            <tr>
                                <td class="font-mono font-bold text-slate-800">
                                    <a href="{{ route('tenant.customers.show', $cust->id) }}" class="text-cyan-700 hover:underline">
                                        {{ $cust->customer_id ?? ('CUST-' . $cust->id) }}
                                    </a>
                                </td>
                                <td class="font-semibold text-slate-800">{{ $cust->name }}</td>
                                <td class="font-mono text-slate-600">{{ $cust->username }}</td>
                                <td class="text-slate-600">{{ $cust->coverageZone?->name ?? 'Default Area' }}</td>
                                <td class="text-slate-600">{{ $cust->package?->name ?? $cust->package_name }}</td>
                                <td class="text-right font-mono font-medium text-slate-700">@currency($cust->monthly_bill)</td>
                                <td class="text-right font-mono font-bold {{ $cust->due_amount > 0 ? 'text-rose-700 bg-rose-50/30' : 'text-slate-400' }}">
                                    @currency($cust->due_amount)
                                </td>
                                <td class="text-center">
                                    @if(in_array($cust->status, ['active', 'online']))
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">Active</span>
                                    @elseif($cust->status === 'due')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase">Due</span>
                                    @elseif($cust->status === 'suspended')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase">Suspended</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">{{ $cust->status }}</span>
                                    @endif
                                </td>
                                <td class="text-slate-600 truncate">{{ $cust->collector?->name ?? 'Unassigned' }}</td>
                                <td class="text-center">
                                    <button type="button" 
                                            @click="toggleMenu({{ json_encode($cust) }}, $event)"
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 inline-flex items-center justify-center transition cursor-pointer">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-users-slash text-3xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No subscriber records found matching current filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customerRecords->hasPages())
                <div class="px-3.5 py-2.5 border-t border-slate-200 bg-slate-50/50">
                    {{ $customerRecords->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- 5. EXECUTIVE REVENUE & DUE ANALYTICS GRIDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: Aging Dues Analysis --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-hourglass-half text-amber-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Aging Dues Analysis</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Aging Risk</span>
            </div>

            <div class="space-y-2.5">
                @foreach($agingAnalysis as $bracket)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">{{ $bracket['label'] }}</span>
                            <span class="font-mono font-bold text-slate-900">@currency($bracket['amount'])</span>
                        </div>
                        <div class="flex items-center justify-between text-[10.5px] text-slate-500">
                            <span>{{ $bracket['count'] }} Subscribers</span>
                            <span class="font-mono font-semibold">{{ $bracket['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-{{ $bracket['color'] }}-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ min(100, $bracket['percentage']) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 2: Top 5 Highest Due Defaulters --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-rose-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Top Due Defaulters</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-rose-600">Urgent Followup</span>
            </div>

            <div class="space-y-2">
                @forelse($topDefaulters as $def)
                    <div class="p-2 bg-rose-50/40 rounded-lg border border-rose-200/60 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <a href="{{ route('tenant.customers.show', $def->id) }}" class="font-bold text-slate-800 hover:text-cyan-700 block truncate">
                                {{ $def->name }}
                            </a>
                            <span class="text-[10.5px] text-slate-500 block truncate font-mono">
                                {{ $def->phone }} &bull; {{ $def->coverageZone?->name ?? 'Default Area' }}
                            </span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono font-bold text-rose-700 block">@currency($def->due_amount)</span>
                            <button type="button" 
                                    @click="sendPaymentReminder({{ json_encode($def) }})"
                                    class="text-[10px] text-cyan-700 hover:underline font-semibold cursor-pointer">
                                <i class="fas fa-bell text-[9px]"></i> Remind
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No subscribers with overdue balances.</p>
                @endforelse
            </div>
        </div>

        {{-- Card 3: Collection Inflow Channels --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-wallet text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Collection Inflow Channels</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Total Yield</span>
            </div>

            <div class="space-y-2">
                @forelse($paymentChannels as $ch)
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-white border border-slate-200 flex items-center justify-center text-[10px] text-slate-600">
                                @if(strtoupper($ch->payment_method) === 'CASH')
                                    <i class="fas fa-money-bill text-emerald-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'BKASH')
                                    <i class="fas fa-mobile-screen text-pink-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'NAGAD')
                                    <i class="fas fa-mobile-screen text-orange-600"></i>
                                @elseif(strtoupper($ch->payment_method) === 'BANK')
                                    <i class="fas fa-building-columns text-blue-600"></i>
                                @else
                                    <i class="fas fa-credit-card text-slate-600"></i>
                                @endif
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 uppercase block">{{ $ch->payment_method }}</span>
                                <span class="text-[10px] text-slate-500">{{ $ch->count }} Payments</span>
                            </div>
                        </div>
                        <span class="font-mono font-bold text-emerald-700">@currency($ch->total)</span>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No collection logs in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 6. GLOBAL FLOATING 3-DOT ACTION MENU --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        <div class="py-1">
            <a :href="activeMenu ? '{{ url('admin/customers') }}/' + activeMenu.id : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-user-circle text-cyan-600 text-[11px] w-4"></i>
                <span>View Profile</span>
            </a>
            <a :href="activeMenu ? '{{ url('admin/finance/invoices') }}?search=' + activeMenu.username : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-file-invoice text-indigo-600 text-[11px] w-4"></i>
                <span>View Invoices</span>
            </a>
            <a :href="activeMenu ? '{{ url('admin/finance/payments') }}?search=' + activeMenu.username : '#'" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-receipt text-emerald-600 text-[11px] w-4"></i>
                <span>Payment History</span>
            </a>
        </div>

        <div class="py-1">
            <button type="button" 
                    @click="sendPaymentReminder(activeMenu); activeMenu = null;"
                    class="w-full text-left flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                <i class="fas fa-paper-plane text-amber-600 text-[11px] w-4"></i>
                <span>Send SMS Reminder</span>
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function collectionReportManager() {
    return {
        selectedPreset: '{{ $preset }}',
        dateFrom: '{{ request("date_from", request("start_date", $from->toDateString())) }}',
        dateTo: '{{ request("date_to", request("end_date", $to->toDateString())) }}',
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },

        handlePeriodChange(val) {
            const now = new Date();
            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (val === 'today') {
                this.dateFrom = formatDate(now);
                this.dateTo = formatDate(now);
            } else if (val === 'yesterday') {
                const y = new Date(now);
                y.setDate(y.getDate() - 1);
                this.dateFrom = formatDate(y);
                this.dateTo = formatDate(y);
            } else if (val === 'this_week') {
                const day = now.getDay();
                const diffToSat = (day + 1) % 7; 
                const start = new Date(now);
                start.setDate(start.getDate() - diffToSat);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(now);
            } else if (val === 'this_month') {
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'last_month') {
                const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const end = new Date(now.getFullYear(), now.getMonth(), 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'this_quarter') {
                const qMonth = Math.floor(now.getMonth() / 3) * 3;
                const start = new Date(now.getFullYear(), qMonth, 1);
                const end = new Date(now.getFullYear(), qMonth + 3, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'this_year') {
                const start = new Date(now.getFullYear(), 0, 1);
                const end = new Date(now.getFullYear(), 11, 31);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'last_12_months') {
                const start = new Date(now.getFullYear() - 1, now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.dateFrom = formatDate(start);
                this.dateTo = formatDate(end);
            } else if (val === 'all') {
                this.dateFrom = '2020-01-01';
                this.dateTo = formatDate(now);
            }
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

        sendPaymentReminder(cust) {
            if (!cust) return;
            const sym = '{{ $currencySymbol ?? "৳" }}';
            const dueText = sym + ' ' + (parseFloat(cust.due_amount) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
            
            Swal.fire({
                title: 'Send Payment Reminder?',
                html: `<div class="text-left text-xs text-slate-600 space-y-2">
                        <p>Send an instant SMS &amp; WhatsApp billing reminder to subscriber <strong>${cust.name}</strong> (${cust.phone || 'No phone'})?</p>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Outstanding Balance</span>
                            <span class="font-mono font-bold text-rose-700 text-sm block">${dueText}</span>
                        </div>
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-paper-plane mr-1"></i> Send Reminder',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Payment reminder queued for ${cust.name}`,
                        showConfirmButton: false,
                        timer: 2500
                    });
                }
            });
        }
    };
}
</script>
@endpush
