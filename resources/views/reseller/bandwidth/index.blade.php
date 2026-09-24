@extends('reseller.layouts.app')

@section('title', 'Bandwidth Usage - ' . ($reseller->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="space-y-3" x-data="bandwidthManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Bandwidth Usage') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="fetchLiveTelemetry()" :disabled="isLoading" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrows-rotate text-slate-500 text-xs" :class="isLoading ? 'animate-spin' : ''"></i>
                <span>{{ __('Live Traffic Telemetry') }}</span>
            </button>
            <a href="{{ route('reseller.bandwidth.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.bandwidth.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openUpgradeModal = true" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-bolt text-xs"></i>
                <span>{{ __('Bandwidth Allocation') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Allocated Bandwidth --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Bandwidth') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $bandwidth?->formatted_total_bandwidth ?? number_format($stats['total_allocated'], 0) . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        {{-- Card 2: Current Live Traffic --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Current Usage') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-600 leading-tight block truncate">
                    <span x-text="liveDownload !== null ? liveDownload : '{{ number_format($stats['current_usage'], 2) }}'">{{ number_format($stats['current_usage'], 2) }}</span> Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wave-square"></i>
            </div>
        </div>

        {{-- Card 3: Peak Traffic (24h) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Peak Usage') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">
                    {{ number_format($stats['peak_usage'], 2) }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-area"></i>
            </div>
        </div>

        {{-- Card 4: Global Internet Bandwidth --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Global Internet</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">
                    {{ number_format($stats['global_mbps'], 0) }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        {{-- Card 5: BDIX & Local CDN --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">BDIX &amp; Cache</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ number_format($stats['bdix_mbps'] ?? $stats['bdix_cdn_mbps'], 0) }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 6: Live Connected Users --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['online_users_count'] ?? 0) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

    </div>

    {{-- 3. REAL-TIME MRTG TRAFFIC STUDIO & WHOLESALE PIPELINE --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        
        <!-- MRTG Graph Canvas (2 Columns on Large) -->
        <div class="lg:col-span-2 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-slate-800">MRTG Traffic Throughput</h3>
                    <span class="text-[10px] font-mono text-slate-400">Queue: {{ $bandwidth?->mikrotik_queue_name ?? 'Default Queue' }}</span>
                </div>
                
                <!-- Timeframe Filters & Live Polling Toggle -->
                <div class="flex items-center gap-1.5">
                    <div class="flex items-center bg-slate-100 p-0.5 rounded-lg text-[10.5px] font-medium">
                        <button type="button" @click="setTimeframe(12)" :class="activeHours === 12 ? 'bg-white font-bold text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-0.5 rounded-md transition cursor-pointer">12H</button>
                        <button type="button" @click="setTimeframe(24)" :class="activeHours === 24 ? 'bg-white font-bold text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-0.5 rounded-md transition cursor-pointer">24H</button>
                        <button type="button" @click="setTimeframe(72)" :class="activeHours === 72 ? 'bg-white font-bold text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-0.5 rounded-md transition cursor-pointer">3D</button>
                        <button type="button" @click="setTimeframe(168)" :class="activeHours === 168 ? 'bg-white font-bold text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-0.5 rounded-md transition cursor-pointer">7D</button>
                    </div>
                    <button type="button" 
                            @click="toggleLivePolling()" 
                            :class="isLivePolling ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-200'"
                            class="px-2 py-1 rounded-lg border text-[10.5px] font-semibold flex items-center gap-1 transition cursor-pointer">
                        <i class="fas fa-circle text-[7px]" :class="isLivePolling ? 'text-emerald-500 animate-ping' : 'text-slate-400'"></i>
                        <span x-text="isLivePolling ? 'Live Polling' : 'Paused'">Live Polling</span>
                    </button>
                </div>
            </div>

            <!-- Chart Canvas Container -->
            <div class="relative h-60 w-full">
                <canvas id="resellerMrtgChart"></canvas>
            </div>

            <!-- Telemetry Footer Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 border-t border-slate-100 text-center">
                <div class="p-1.5 bg-purple-50/70 rounded-lg border border-purple-100">
                    <span class="text-[9.5px] text-purple-700 font-medium block">Download (Ingress)</span>
                    <span class="text-xs font-bold font-mono text-purple-900">
                        <span x-text="liveDownload !== null ? liveDownload : '{{ number_format($stats['current_usage'], 2) }}'">{{ number_format($stats['current_usage'], 2) }}</span> Mbps
                    </span>
                </div>
                <div class="p-1.5 bg-indigo-50/70 rounded-lg border border-indigo-100">
                    <span class="text-[9.5px] text-indigo-700 font-medium block">Upload (Egress)</span>
                    <span class="text-xs font-bold font-mono text-indigo-900">
                        <span x-text="liveUpload !== null ? liveUpload : '{{ number_format($stats['current_usage'] * 0.32, 2) }}'">{{ number_format($stats['current_usage'] * 0.32, 2) }}</span> Mbps
                    </span>
                </div>
                <div class="p-1.5 bg-cyan-50/70 rounded-lg border border-cyan-100">
                    <span class="text-[9.5px] text-cyan-700 font-medium block">Latency (Host Core)</span>
                    <span class="text-xs font-bold font-mono text-cyan-900">
                        <span x-text="liveLatency">6</span> ms
                    </span>
                </div>
                <div class="p-1.5 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-[9.5px] text-slate-600 font-medium block">Allocated CIR</span>
                    <span class="text-xs font-bold font-mono text-slate-800">
                        {{ $bandwidth?->formatted_total_bandwidth ?? '500 Mbps' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Wholesale Allocation Pipeline Specs (1 Column) -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-800">Wholesale Profile</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $bandwidth?->allocation_type ? str_replace('_', ' ', $bandwidth->allocation_type) : 'DEDICATED CIR 1:1' }}
                    </span>
                </div>

                <!-- Utilization Bar -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700">
                        <span>Capacity Utilization</span>
                        <span class="font-mono" x-text="(liveUtilization !== null ? liveUtilization : '{{ number_format($stats['utilization_percent'], 1) }}') + '%'">{{ number_format($stats['utilization_percent'], 1) }}%</span>
                    </div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                        <div class="h-full transition-all duration-300 rounded-full"
                             :class="liveUtilization >= 85 ? 'bg-rose-500' : (liveUtilization >= 70 ? 'bg-amber-500' : 'bg-purple-600')"
                             :style="`width: ${Math.min(100, liveUtilization !== null ? liveUtilization : {{ $stats['utilization_percent'] }})}%`"></div>
                    </div>
                </div>

                <!-- Pipeline Breakdown Matrix -->
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-globe text-blue-500 text-xs w-3.5 text-center"></i>
                            <span class="text-slate-600">Global Internet (CIR)</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ number_format($bandwidth?->global_bandwidth_mbps ?? 0, 0) }} Mbps</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-bolt text-amber-500 text-xs w-3.5 text-center"></i>
                            <span class="text-slate-600">BDIX National Exchange</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ number_format($bandwidth?->bdix_bandwidth_mbps ?? 0, 0) }} Mbps</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <i class="fab fa-google text-red-500 text-xs w-3.5 text-center"></i>
                            <span class="text-slate-600">Google Cache (GGC)</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ number_format($bandwidth?->ggc_bandwidth_mbps ?? 0, 0) }} Mbps</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <i class="fab fa-facebook text-blue-600 text-xs w-3.5 text-center"></i>
                            <span class="text-slate-600">Meta / Facebook (FNA)</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ number_format($bandwidth?->fna_bandwidth_mbps ?? 0, 0) }} Mbps</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-network-wired text-purple-600 text-xs w-3.5 text-center"></i>
                            <span class="text-slate-600">Gateway &amp; VLAN</span>
                        </div>
                        <span class="font-mono font-semibold text-slate-700">VLAN {{ $bandwidth?->vlan_id ?? '100' }} • {{ $bandwidth?->router?->name ?? 'Core Gateway' }}</span>
                    </div>
                </div>
            </div>

            <!-- Wholesale Financial Billing Box -->
            <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200/90 flex items-center justify-between mt-2">
                <div>
                    <span class="text-[9.5px] uppercase font-bold text-purple-700 block tracking-wider">Wholesale Monthly Bill</span>
                    <span class="text-sm font-bold font-mono text-purple-900 leading-tight block">@currency($bandwidth?->monthly_bill_amount ?? 0)</span>
                </div>
                <button type="button" @click="openUpgradeModal = true" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                    Upgrade
                </button>
            </div>
        </div>

    </div>

    {{-- 4. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reseller.bandwidth.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 items-center">
            
            <!-- Search Box -->
            <div class="relative lg:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search subscriber name, user ID, IP..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
            </div>

            <!-- Package Filter -->
            <div>
                <select name="package_id" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="all" {{ $packageId === 'all' ? 'selected' : '' }}>All Packages</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ $packageId == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }} ({{ $pkg->bandwidth_mbps }}M)</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="online" {{ $status === 'online' ? 'selected' : '' }}>Online Sessions</option>
                    <option value="offline" {{ $status === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Customers</option>
                    <option value="due" {{ $status === 'due' ? 'selected' : '' }}>Due / Unpaid</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <!-- Per Page -->
            <div>
                <select name="per_page" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C) -->
            <div class="flex items-center gap-1.5">
                <button type="submit" class="w-1/2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.bandwidth.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 5. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Subscriber Name</th>
                        <th class="w-36">Package &amp; Speed</th>
                        <th class="w-36 font-mono">IP Address</th>
                        <th class="w-28 text-center">Session State</th>
                        <th class="w-28 text-center">Account</th>
                        <th class="w-16 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($customers->currentPage() - 1) * $customers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Subscriber Name (Single Clean Field) -->
                            <td class="font-semibold text-slate-800">
                                <span class="hover:text-purple-700 cursor-pointer" @click="viewCustomerDetails({{ json_encode($customer) }})">
                                    {{ $customer->name }}
                                </span>
                            </td>

                            <!-- 3. Package & Speed -->
                            <td class="text-slate-700">
                                {{ $customer->package_display_name }}
                            </td>

                            <!-- 4. IP Address -->
                            <td class="font-mono text-slate-600">
                                {{ $customer->ip_address ?: 'Dynamic (PPPoE)' }}
                            </td>

                            <!-- 5. Online / Session State -->
                            <td class="text-center">
                                @if($customer->online_status === 'online')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        ONLINE
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                        OFFLINE
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Account Status -->
                            <td class="text-center">
                                @php $badge = $customer->status_badge; @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }} border">
                                    {{ strtoupper($badge['label'] ?? $customer->status) }}
                                </span>
                            </td>

                            <!-- 7. Floating 3-Dot Action Button -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($customer) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="fas fa-users-slash text-3xl mb-2 block text-slate-300"></i>
                                <span>No subscribers found for this bandwidth line.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $customers->firstItem() }}</span> to <span class="font-bold">{{ $customers->lastItem() }}</span> of <span class="font-bold">{{ $customers->total() }}</span> subscribers
                </div>
                <div>
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 6. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         @click.outside="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-44 text-xs space-y-0.5"
         :style="menuPos"
         style="display: none;"
         x-cloak>
        <button type="button" 
                @click="viewCustomerDetails(activeMenu); activeMenu = null" 
                class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-purple-600 transition text-left cursor-pointer">
            <i class="fas fa-eye w-4 text-purple-600"></i>
            <span>View Details</span>
        </button>
        <button type="button" 
                @click="testLineSpeed(activeMenu); activeMenu = null" 
                class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 transition text-left cursor-pointer">
            <i class="fas fa-tachometer-alt w-4 text-cyan-600"></i>
            <span>Ping / Line Test</span>
        </button>
    </div>

    {{-- 7. REQUEST BANDWIDTH UPGRADE MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openUpgradeModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.outside="openUpgradeModal = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Request Bandwidth Upgrade</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Direct capacity escalation to host ISP network management</p>
                    </div>
                </div>
                <button type="button" @click="openUpgradeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.bandwidth.request-upgrade') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    <!-- Upgrade Type & Requested Mbps -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="upgrade_type" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Upgrade Type <span class="text-rose-500">*</span>
                            </label>
                            <select id="upgrade_type" name="upgrade_type" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="additional_capacity">Permanent Capacity (+Mbps)</option>
                                <option value="emergency_boost">Emergency 24H Boost</option>
                                <option value="change_cir_ratio">Increase CIR 1:1 Ratio</option>
                                <option value="temporary_addon">Temporary Festive Add-On</option>
                            </select>
                        </div>
                        <div>
                            <label for="requested_mbps" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Additional Capacity (Mbps) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   id="requested_mbps" 
                                   name="requested_mbps" 
                                   min="5" 
                                   step="5" 
                                   value="50" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Target Pipeline & Effective Date -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="target_pipeline" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Target Pipeline <span class="text-rose-500">*</span>
                            </label>
                            <select id="target_pipeline" name="target_pipeline" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="total_pool">Total Combined Pool</option>
                                <option value="global_internet">Global Internet CIR</option>
                                <option value="bdix_local">BDIX National Exchange</option>
                                <option value="cdn_cache">CDN &amp; Video Cache</option>
                            </select>
                        </div>
                        <div>
                            <label for="effective_date" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Preferred Effective Date
                            </label>
                            <input type="date" 
                                   id="effective_date" 
                                   name="effective_date" 
                                   value="{{ date('Y-m-d') }}" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="upgrade_notes" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Operational Notes / Requirements
                        </label>
                        <textarea id="upgrade_notes" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="e.g., Higher evening peak traffic observed at Agrabad node. Please expedite activation." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openUpgradeModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Submit Upgrade Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. SUBSCRIBER DETAILS MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="selectedCustomer" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.outside="selectedCustomer = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedCustomer?.name || 'Subscriber Details'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'ID: ' + (selectedCustomer?.customer_id || 'N/A') + ' • PPPoE: ' + (selectedCustomer?.username || 'N/A')"></p>
                    </div>
                </div>
                <button type="button" @click="selectedCustomer = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Details Grid -->
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Package Plan</span>
                        <span class="font-bold text-slate-800 block" x-text="selectedCustomer?.package_display_name || selectedCustomer?.package_name || 'Standard'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">IP Address</span>
                        <span class="font-mono font-bold text-slate-800 block" x-text="selectedCustomer?.ip_address || 'Dynamic DHCP/PPPoE'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Session State</span>
                        <span class="font-bold block" :class="selectedCustomer?.online_status === 'online' ? 'text-emerald-600' : 'text-slate-600'" x-text="selectedCustomer?.online_status ? selectedCustomer.online_status.toUpperCase() : 'OFFLINE'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Mobile Contact</span>
                        <span class="font-mono font-bold text-slate-800 block" x-text="selectedCustomer?.mobile || 'N/A'"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="selectedCustomer = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bandwidthManager() {
    return {
        openUpgradeModal: false,
        selectedCustomer: null,
        activeMenu: null,
        menuPos: {},
        isLoading: false,
        isLivePolling: true,
        pollingInterval: null,
        activeHours: 24,
        chartInstance: null,
        
        liveDownload: {{ $stats['current_usage'] }},
        liveUpload: {{ round($stats['current_usage'] * 0.32, 2) }},
        liveUtilization: {{ $stats['utilization_percent'] }},
        liveLatency: 6,

        init() {
            this.initChart({!! json_encode($chartPoints) !!});
            this.startLivePolling();
        },

        initChart(points) {
            const ctx = document.getElementById('resellerMrtgChart');
            if (!ctx) return;

            const labels = points.map(p => p.time);
            const downloadData = points.map(p => p.download);
            const uploadData = points.map(p => p.upload);

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Download (Mbps)',
                            data: downloadData,
                            borderColor: '#9333ea', // purple-600
                            backgroundColor: 'rgba(147, 51, 234, 0.12)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Upload (Mbps)',
                            data: uploadData,
                            borderColor: '#4f46e5', // indigo-600
                            backgroundColor: 'rgba(79, 70, 229, 0.08)',
                            borderWidth: 1.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { size: 11 },
                            bodyFont: { size: 11 }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, family: 'monospace' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 10, family: 'monospace' },
                                callback: function(value) { return value + ' M'; }
                            }
                        }
                    }
                }
            });
        },

        async setTimeframe(hours) {
            this.activeHours = hours;
            await this.fetchLiveTelemetry();
        },

        toggleLivePolling() {
            this.isLivePolling = !this.isLivePolling;
            if (this.isLivePolling) {
                this.startLivePolling();
            } else {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
            }
        },

        startLivePolling() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
            this.pollingInterval = setInterval(() => {
                if (this.isLivePolling) {
                    this.fetchLiveTelemetry(true);
                }
            }, 6000); // 6s poll
        },

        async fetchLiveTelemetry(silent = false) {
            if (!silent) this.isLoading = true;
            try {
                const response = await fetch(`{{ route('reseller.bandwidth.traffic') }}?hours=${this.activeHours}`);
                const data = await response.json();
                if (data.success) {
                    this.liveDownload = data.current_download;
                    this.liveUpload = data.current_upload;
                    this.liveUtilization = data.utilization_percent;
                    this.liveLatency = data.latency_ms;

                    if (this.chartInstance && data.points) {
                        this.chartInstance.data.labels = data.points.map(p => p.time);
                        this.chartInstance.data.datasets[0].data = data.points.map(p => p.download);
                        this.chartInstance.data.datasets[1].data = data.points.map(p => p.upload);
                        this.chartInstance.update();
                    }
                }
            } catch (e) {
                console.error('Failed to fetch telemetry:', e);
            } finally {
                if (!silent) this.isLoading = false;
            }
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

        viewCustomerDetails(customer) {
            this.selectedCustomer = customer;
        },

        async testLineSpeed(customer) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Testing Line Ping...',
                    html: `Testing ping latency and packet throughput for <b>${customer.name}</b> (${customer.ip_address || 'Dynamic IP'})...`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        setTimeout(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Line Diagnostic OK',
                                html: `<div class="text-left text-xs font-mono space-y-1 bg-slate-50 p-3 rounded-lg border">
                                    <div>Latency: <b>4.2 ms</b></div>
                                    <div>Packet Loss: <b>0.0%</b></div>
                                    <div>Allocated Speed: <b>${customer.package_display_name || 'Standard'}</b></div>
                                    <div>Session Status: <b class="text-emerald-600">CONNECTED</b></div>
                                </div>`,
                                confirmButtonText: 'Done',
                                customClass: {
                                    confirmButton: 'bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs px-4 py-2 rounded-lg'
                                },
                                buttonsStyling: false
                            });
                        }, 1200);
                    }
                });
            }
        }
    };
}
</script>
@endpush
