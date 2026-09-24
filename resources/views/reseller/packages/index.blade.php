@extends('reseller.layouts.app')

@section('title', 'Assigned Packages & Rates - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="packageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Assigned Packages') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <a href="{{ route('reseller.packages.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.packages.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Assigned Plans --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Assigned Packages') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">
                    {{ number_format($stats['total_assigned_plans']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        {{-- Card 2: Commission Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Commission Rate') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ number_format($stats['commission_rate'], 1) }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>

        {{-- Card 3: Active Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['active_subscribers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 4: Total Retail MRR --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Monthly Billed') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    @currency($stats['total_retail_mrr'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-trend-up"></i>
            </div>
        </div>

        {{-- Card 5: Total Monthly Profit --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Profit') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['total_monthly_profit'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Card 6: Wholesale Cost --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Admin Share') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-700 leading-tight block truncate">
                    @currency($stats['total_wholesale_cost'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.packages.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Box (MD: 4 Cols) -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ $search }}" 
                       autocomplete="off"
                       placeholder="Search package name, profile..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Service Type Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="service_type" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $serviceType === 'all' ? 'selected' : '' }}>All Service Types</option>
                    <option value="PPPOE" {{ $serviceType === 'PPPOE' ? 'selected' : '' }}>PPPoE</option>
                    <option value="STATIC_IP" {{ $serviceType === 'STATIC_IP' ? 'selected' : '' }}>Static IP</option>
                    <option value="HOTSPOT" {{ $serviceType === 'HOTSPOT' ? 'selected' : '' }}>Hotspot</option>
                </select>
            </div>

            <!-- Speed Tier Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="speed" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $speedTier === 'all' ? 'selected' : '' }}>All Speeds</option>
                    <option value="10" {{ $speedTier === '10' ? 'selected' : '' }}>Up to 10 Mbps</option>
                    <option value="20" {{ $speedTier === '20' ? 'selected' : '' }}>11 - 20 Mbps</option>
                    <option value="35" {{ $speedTier === '35' ? 'selected' : '' }}>21 - 35 Mbps</option>
                    <option value="50+" {{ $speedTier === '50+' ? 'selected' : '' }}>40+ Mbps</option>
                </select>
            </div>

            <!-- Per Page Selector (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="per_page" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C - MD: 2 Cols) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.packages.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Compact .saas-table with Bandwidth Breakdown) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Package</th>
                        <th class="w-20 text-center font-mono">Download</th>
                        <th class="w-20 text-center font-mono">Upload</th>
                        <th class="w-20 text-center font-mono">Facebook</th>
                        <th class="w-20 text-center font-mono">YouTube</th>
                        <th class="w-20 text-center font-mono">BDIX</th>
                        <th class="w-24 text-right">Retail Rate</th>
                        <th class="w-24 text-right">Wholesale Cost</th>
                        <th class="w-28 text-right">Margin ({{ $commissionRate }}%)</th>
                        <th class="w-20 text-center font-mono">Subscribers</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $index => $pkg)
                        @php
                            $retail = (float) $pkg->price;
                            $margin = ($retail * $commissionRate) / 100;
                            $cost = max(0, $retail - $margin);
                            $subs = $subscriberCounts->get($pkg->id);
                            $activeSubs = $subs ? $subs->active_subscribers : 0;
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($packages->currentPage() - 1) * $packages->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Package (MikroTik Profile Name) -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-mono font-bold" @click="fetchPackageDetails({{ $pkg->id }})">
                                    {{ $pkg->mikrotik_profile ?? $pkg->name }}
                                </button>
                            </td>

                            <!-- 3. Download (M) -->
                            <td class="text-center font-mono font-bold text-blue-700">
                                {{ $pkg->download_speed ? $pkg->download_speed . 'M' : '—' }}
                            </td>

                            <!-- 4. Upload (M) -->
                            <td class="text-center font-mono font-bold text-teal-700">
                                {{ $pkg->upload_speed ? $pkg->upload_speed . 'M' : '—' }}
                            </td>

                            <!-- 5. Facebook (M) -->
                            <td class="text-center font-mono font-bold text-indigo-700">
                                {{ $pkg->facebook_speed ? $pkg->facebook_speed . 'M' : '—' }}
                            </td>

                            <!-- 6. YouTube (M) -->
                            <td class="text-center font-mono font-bold text-rose-700">
                                {{ $pkg->youtube_speed ? $pkg->youtube_speed . 'M' : '—' }}
                            </td>

                            <!-- 7. BDIX (M) -->
                            <td class="text-center font-mono font-bold text-purple-700">
                                {{ $pkg->bdix_speed ? $pkg->bdix_speed . 'M' : '—' }}
                            </td>

                            <!-- 8. Retail Rate -->
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($retail)
                            </td>

                            <!-- 9. Wholesale Cost -->
                            <td class="text-right font-mono font-semibold text-slate-700">
                                @currency($cost)
                            </td>

                            <!-- 10. Margin (Profit) -->
                            <td class="text-right font-mono font-bold text-emerald-600">
                                +@currency($margin)
                            </td>

                            <!-- 11. Subscribers -->
                            <td class="text-center font-mono font-bold {{ $activeSubs > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $activeSubs }}
                            </td>

                            <!-- 12. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from([
                                            'id' => $pkg->id,
                                            'name' => $pkg->mikrotik_profile ?? $pkg->name,
                                            'speed' => $pkg->download_speed
                                        ]) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-400 text-xs">
                                <i class="fas fa-boxes-stacked text-3xl text-slate-300 mb-2 block"></i>
                                <span>No assigned internet packages found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($packages->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $packages->firstItem() }}</span> to <span class="font-bold">{{ $packages->lastItem() }}</span> of <span class="font-bold">{{ $packages->total() }}</span> packages
                </div>
                <div>
                    {{ $packages->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="fetchPackageDetails(activeMenuItem.id); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-circle-info text-cyan-600 w-4"></i>
            <span>View Details</span>
        </button>
    </div>

    {{-- 6. MODAL: NATURAL SOFT PACKAGE DETAILS MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showPkgModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showPkgModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6">
            
            {{-- Soft Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Package Specifications &amp; Rates</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="pkgData?.mikrotik_profile || pkgData?.package_name || 'Package'"></p>
                    </div>
                </div>
                <button type="button" @click="showPkgModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-3" x-show="pkgData">
                
                <!-- Financial Margin Banner -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <span class="text-[10px] uppercase font-medium text-slate-500 block">Retail Rate</span>
                        <span class="text-sm font-bold font-mono text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(pkgData?.price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <span class="text-[10px] uppercase font-medium text-slate-500 block">Wholesale Cost</span>
                        <span class="text-sm font-bold font-mono text-slate-600" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(pkgData?.wholesale_cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200 text-center">
                        <span class="text-[10px] uppercase font-medium text-emerald-700 block">Your Margin</span>
                        <span class="text-sm font-bold font-mono text-emerald-700" x-text="'+{{ $currencySymbol ?? '৳' }}' + Number(pkgData?.profit_margin || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

                <!-- Speed Breakdown -->
                <div class="space-y-1.5">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Bandwidth &amp; Speed Caps</h4>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">Download Speed</span>
                            <span class="font-bold font-mono text-cyan-800" x-text="(pkgData?.download_speed || 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">Upload Speed</span>
                            <span class="font-bold font-mono text-cyan-800" x-text="(pkgData?.upload_speed || 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">BDIX Speed</span>
                            <span class="font-bold font-mono text-purple-700" x-text="(pkgData?.bdix_speed || pkgData?.download_speed || 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">YouTube Cache</span>
                            <span class="font-bold font-mono text-rose-700" x-text="(pkgData?.youtube_speed || pkgData?.download_speed || 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">Facebook CDN</span>
                            <span class="font-bold font-mono text-indigo-700" x-text="(pkgData?.facebook_speed || pkgData?.download_speed || 0) + ' Mbps'"></span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[10px] text-slate-500 block">Validity</span>
                            <span class="font-bold font-mono text-slate-800" x-text="(pkgData?.validity_days || 30) + ' Days'"></span>
                        </div>
                    </div>
                </div>

                <!-- Subscribers & Revenue Projection -->
                <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-100 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-slate-700">Active Subscribers:</span>
                        <span class="font-mono font-bold text-cyan-900" x-text="(pkgData?.active_subscribers || 0) + ' Users'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-slate-700">Total Monthly Revenue:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(pkgData?.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-cyan-100 pt-1">
                        <span class="font-bold text-emerald-800">Your Monthly Profit:</span>
                        <span class="font-mono font-bold text-emerald-700 text-sm" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(pkgData?.total_profit || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="showPkgModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function packageManager() {
    return {
        activeMenu: null,
        activeMenuItem: null,
        menuPos: {},
        showPkgModal: false,
        pkgData: null,

        toggleMenu(item, event) {
            if (this.activeMenu === item.id) {
                this.activeMenu = null;
                this.activeMenuItem = null;
                return;
            }
            this.activeMenu = item.id;
            this.activeMenuItem = item;

            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 80;
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

        async fetchPackageDetails(id) {
            try {
                const res = await fetch(`/reseller/packages/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.pkgData = data.data;
                    this.showPkgModal = true;
                }
            } catch (err) {
                console.error("Error fetching package details:", err);
            }
        }
    };
}
</script>
@endpush
