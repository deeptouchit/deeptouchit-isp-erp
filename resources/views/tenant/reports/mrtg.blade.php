@extends('tenant.layouts.app')

@section('title', 'Bandwidth & MRTG Graph - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    .progress-bar-fill {
        transition: width 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="mrtgReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-chart-area"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Bandwidth Utilization &amp; MRTG Graph Analytics</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Live Pulse Indicator Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>MRTG Engine: Live</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.mrtg.print', request()->all()) }}" 
               target="_blank" 
               class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>Print Statement</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.mrtg.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Peak Inflow (Download / RX) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Peak Inflow (RX)</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ $peakDownloadMbps >= 1000 ? round($peakDownloadMbps / 1000, 2) . ' Gbps' : $peakDownloadMbps . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>

        {{-- Card 2: Peak Outflow (Upload / TX) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Peak Outflow (TX)</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $peakUploadMbps >= 1000 ? round($peakUploadMbps / 1000, 2) . ' Gbps' : $peakUploadMbps . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>

        {{-- Card 3: Allocated Subscribed Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Allocated CIR</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $totalSubscribedBandwidthMbps >= 1000 ? round($totalSubscribedBandwidthMbps / 1000, 2) . ' Gbps' : $totalSubscribedBandwidthMbps . ' Mbps' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 4: Upstream Trunk Utilization --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Trunk Load</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    {{ $upstreamUtilizationPct }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        {{-- Card 5: BDIX Domestic Peering Peak --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">BDIX Peering</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $bdixPeakMbps }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shuffle"></i>
            </div>
        </div>

        {{-- Card 6: Monitored Interface Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Interfaces</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ $activeInterfacesCount }} Ports
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ethernet"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.reports.mrtg') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            {{-- Filter 1: Router Node Selector --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Router / POP Node</label>
                <select name="router_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Routers Combined</option>
                    @foreach($allRouters as $r)
                        <option value="{{ $r->id }}" {{ (string)$routerId === (string)$r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->ip_address }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter 2: Timescale Range Selector --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">MRTG Timeframe</label>
                <select name="timescale" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="daily" {{ $timescale === 'daily' ? 'selected' : '' }}>Daily (24 Hours Graph)</option>
                    <option value="weekly" {{ $timescale === 'weekly' ? 'selected' : '' }}>Weekly (7 Days Graph)</option>
                    <option value="monthly" {{ $timescale === 'monthly' ? 'selected' : '' }}>Monthly (30 Days Graph)</option>
                    <option value="yearly" {{ $timescale === 'yearly' ? 'selected' : '' }}>Yearly (12 Months Continuous)</option>
                    <option value="live" {{ $timescale === 'live' ? 'selected' : '' }}>Real-time Live Stream (10s Polling)</option>
                </select>
            </div>

            {{-- Filter 3: Interface Classification --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Interface Type</label>
                <select name="interface_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all" {{ $interfaceType === 'all' ? 'selected' : '' }}>All Trunk &amp; Client Ports</option>
                    <option value="wan" {{ $interfaceType === 'wan' ? 'selected' : '' }}>Upstream IIG / ITC Only</option>
                    <option value="bdix" {{ $interfaceType === 'bdix' ? 'selected' : '' }}>BDIX Domestic Peering Only</option>
                    <option value="trunk" {{ $interfaceType === 'trunk' ? 'selected' : '' }}>OLT &amp; Reseller Trunks</option>
                    <option value="lan" {{ $interfaceType === 'lan' ? 'selected' : '' }}>Retail PPPoE LAN Distribution</option>
                </select>
            </div>

            {{-- Filter 4: View Mode --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Presentation Mode</label>
                <select name="view_mode" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="both" {{ $viewMode === 'both' ? 'selected' : '' }}>MRTG Graphs + Data Matrix</option>
                    <option value="graph" {{ $viewMode === 'graph' ? 'selected' : '' }}>MRTG Graphs Only</option>
                    <option value="table" {{ $viewMode === 'table' ? 'selected' : '' }}>Interface Data Table Only</option>
                </select>
            </div>

            {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.reports.mrtg') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MRTG INTERACTIVE REAL-TIME GRAPH CANVAS --}}
    @if($viewMode !== 'table')
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
            {{-- Graph Header with MRTG Legend & Live Controls --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line text-cyan-600 text-sm"></i>
                    <div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                            {{ ucfirst($timescale) }} MRTG Bandwidth Utilization Curve
                        </h2>
                        <span class="text-[10.5px] text-slate-500 font-mono">Target: {{ $selectedRouter ? $selectedRouter->name : 'Consolidated Aggregate Backbone' }}</span>
                    </div>
                </div>

                {{-- MRTG Legend Tags --}}
                <div class="flex items-center gap-3 text-[11px] font-semibold flex-wrap">
                    <span class="inline-flex items-center gap-1 text-emerald-700">
                        <span class="w-3 h-3 rounded bg-emerald-500/80 inline-block"></span>
                        <span>Incoming / Download (RX)</span>
                    </span>
                    <span class="inline-flex items-center gap-1 text-cyan-700">
                        <span class="w-3 h-3 rounded bg-cyan-500/80 inline-block"></span>
                        <span>Outgoing / Upload (TX)</span>
                    </span>
                    <span class="inline-flex items-center gap-1 text-purple-700">
                        <span class="w-3 h-3 rounded bg-purple-500/80 inline-block"></span>
                        <span>BDIX Domestic Peering</span>
                    </span>
                </div>
            </div>

            {{-- Graph Canvas --}}
            <div class="w-full h-72 sm:h-80 relative">
                <canvas id="mrtgCanvas"></canvas>
            </div>

            {{-- MRTG Formal Telemetry Statistics Strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 pt-2 border-t border-slate-100 text-xs text-center font-mono">
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Current In (RX)</span>
                    <span class="font-bold text-emerald-700 block mt-0.5" id="statCurRx">{{ $graphData['stats']['current_rx'] }} Mbps</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Current Out (TX)</span>
                    <span class="font-bold text-cyan-700 block mt-0.5" id="statCurTx">{{ $graphData['stats']['current_tx'] }} Mbps</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Average In</span>
                    <span class="font-bold text-slate-800 block mt-0.5">{{ $graphData['stats']['average_rx'] }} Mbps</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Average Out</span>
                    <span class="font-bold text-slate-800 block mt-0.5">{{ $graphData['stats']['average_tx'] }} Mbps</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Maximum Peak In</span>
                    <span class="font-bold text-emerald-800 block mt-0.5">{{ $graphData['stats']['maximum_rx'] }} Mbps</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block">Maximum Peak Out</span>
                    <span class="font-bold text-cyan-800 block mt-0.5">{{ $graphData['stats']['maximum_tx'] }} Mbps</span>
                </div>
            </div>
        </div>
    @endif

    {{-- 5. MASTER INTERFACE & TRUNK TELEMETRY TABLE (<table class="saas-table">) --}}
    @if($viewMode !== 'graph')
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table-columns text-slate-500 text-xs"></i>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Interface &amp; Trunk Live Telemetry Matrix</h2>
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Real-time Port Bandwidth, Capacity &amp; Utilization Logs</span>
            </div>

            <div class="overflow-x-auto min-h-[220px]">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Interface Port</th>
                            <th>Router Node</th>
                            <th>Classification</th>
                            <th class="w-28">Link Capacity</th>
                            <th class="text-right w-24 text-emerald-700 font-bold">RX (In)</th>
                            <th class="text-right w-24 text-cyan-700 font-bold">TX (Out)</th>
                            <th class="text-right w-24">Peak RX</th>
                            <th class="text-right w-24">Peak TX</th>
                            <th class="text-center w-20">Load %</th>
                            <th class="text-center w-16">Status</th>
                            <th class="no-sort w-14 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($interfacesMatrix as $idx => $iface)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-bold text-slate-800 font-mono">{{ $iface['name'] }}</td>
                                <td class="text-slate-600 font-medium">{{ $iface['router_name'] }}</td>
                                <td>
                                    <span class="px-1.5 py-0.5 rounded text-[10.5px] font-semibold {{ $iface['type'] === 'wan' ? 'bg-indigo-100 text-indigo-800' : ($iface['type'] === 'bdix' ? 'bg-purple-100 text-purple-800' : ($iface['type'] === 'trunk' ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-700')) }}">
                                        {{ $iface['type_label'] }}
                                    </span>
                                </td>
                                <td class="font-mono text-slate-700 text-xs">{{ $iface['capacity_label'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">{{ $iface['rx_mbps'] }} M</td>
                                <td class="text-right font-mono font-bold text-cyan-700 bg-cyan-50/20">{{ $iface['tx_mbps'] }} M</td>
                                <td class="text-right font-mono text-slate-800">{{ $iface['peak_rx_mbps'] }} M</td>
                                <td class="text-right font-mono text-slate-800">{{ $iface['peak_tx_mbps'] }} M</td>
                                <td class="text-center font-mono font-bold">
                                    <span class="px-1.5 py-0.5 rounded text-[10.5px] {{ $iface['load_pct'] >= 80 ? 'bg-rose-100 text-rose-800' : ($iface['load_pct'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                                        {{ $iface['load_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">
                                        {{ $iface['status'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            @click="openInterfaceModal({{ json_encode($iface) }})"
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition cursor-pointer" 
                                            title="View Port Details">
                                        <i class="fas fa-circle-info text-[11px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-10 text-slate-400">
                                    <i class="fas fa-ethernet text-2xl mb-2 text-slate-300 block"></i>
                                    <span class="text-xs font-medium">No interface telemetry recorded.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- 6. EXECUTIVE DEEP-DIVE ANALYTICS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: Traffic Distribution Breakdown --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-pie-chart text-indigo-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Traffic Distribution Mix</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Share %</span>
            </div>

            <div class="space-y-2.5">
                @foreach($trafficDistribution as $td)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800 truncate pr-2">{{ $td['label'] }}</span>
                            <span class="font-mono font-bold text-slate-900">{{ $td['bandwidth_mbps'] }} Mbps</span>
                        </div>
                        <div class="flex items-center justify-between text-[10.5px] text-slate-500">
                            <span>Traffic Share</span>
                            <span class="font-mono font-semibold text-indigo-700">{{ $td['share_pct'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-{{ $td['color'] }}-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ $td['share_pct'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 2: Top Wholesale Sub-ISP Bandwidth Users --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-tower-broadcast text-cyan-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Sub-ISP Trunk Consumption</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Allocated</span>
            </div>

            <div class="space-y-2">
                @forelse($topWholesaleBandwidth as $res)
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-800 block truncate">{{ $res['name'] }}</span>
                            <span class="text-[10.5px] text-slate-500 font-mono">{{ $res['current_mbps'] }} M Active &bull; {{ $res['utilization_pct'] }}% Load</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold text-cyan-700 block">{{ $res['allocated_mbps'] }} Mbps</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">CIR Dedicated</span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-4">No wholesale sub-ISPs mapped.</p>
                @endforelse
            </div>
        </div>

        {{-- Card 3: Router Node Telemetry & POP Health --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-microchip text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Router Node Telemetry</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-emerald-700">Healthy</span>
            </div>

            <div class="space-y-2 text-xs">
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Hardware Model:</span>
                    <span class="font-mono font-bold text-slate-800 truncate pl-2">{{ $hardwareTelemetry['model'] }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">CPU Core Utilization:</span>
                    <span class="font-mono font-bold {{ $hardwareTelemetry['cpu_load'] > 75 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $hardwareTelemetry['cpu_load'] }}%</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Free System RAM:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $hardwareTelemetry['free_memory_mb'] }} MB / {{ $hardwareTelemetry['total_memory_mb'] }} MB</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Router Uptime:</span>
                    <span class="font-mono font-semibold text-slate-700">{{ $hardwareTelemetry['uptime'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 7. INTERFACE QUICK AUDIT MODAL --}}
    <div x-show="selectedInterfaceModal !== null" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="selectedInterfaceModal = null" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-ethernet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Interface Port Audit</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="selectedInterfaceModal?.name"></p>
                    </div>
                </div>
                <button type="button" @click="selectedInterfaceModal = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs" x-show="selectedInterfaceModal">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Download (RX Rate)</span>
                        <span class="font-bold font-mono text-emerald-700 text-sm mt-0.5 block" x-text="selectedInterfaceModal?.rx_mbps + ' Mbps'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Upload (TX Rate)</span>
                        <span class="font-bold font-mono text-cyan-700 text-sm mt-0.5 block" x-text="selectedInterfaceModal?.tx_mbps + ' Mbps'"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Router Node:</span>
                        <span class="font-semibold text-slate-800" x-text="selectedInterfaceModal?.router_name"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Port Capacity:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedInterfaceModal?.capacity_label"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Peak Download RX:</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="selectedInterfaceModal?.peak_rx_mbps + ' Mbps'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Peak Upload TX:</span>
                        <span class="font-mono font-bold text-cyan-700" x-text="selectedInterfaceModal?.peak_tx_mbps + ' Mbps'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5 border-t border-slate-200 pt-1">
                        <span class="text-slate-600 font-semibold">Port Load Utilization:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="selectedInterfaceModal?.load_pct + '%'"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="selectedInterfaceModal = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close Audit
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
let mrtgChartInstance = null;

function mrtgReportManager() {
    return {
        selectedInterfaceModal: null,

        init() {
            this.renderChart();
            @if($timescale === 'live')
                this.startLiveTicker();
            @endif
        },

        openInterfaceModal(iface) {
            this.selectedInterfaceModal = iface;
        },

        renderChart() {
            const ctx = document.getElementById('mrtgCanvas');
            if (!ctx) return;

            const graphData = @json($graphData);

            if (mrtgChartInstance) {
                mrtgChartInstance.destroy();
            }

            mrtgChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: graphData.labels,
                    datasets: [
                        {
                            label: 'Incoming (Download RX)',
                            data: graphData.rx_series,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.15)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2,
                        },
                        {
                            label: 'Outgoing (Upload TX)',
                            data: graphData.tx_series,
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.12)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 2,
                        },
                        {
                            label: 'BDIX Peering',
                            data: graphData.bdix_series,
                            borderColor: '#a855f7',
                            backgroundColor: 'rgba(168, 85, 247, 0.08)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 1.5,
                            borderDash: [4, 4],
                            pointRadius: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#e2e8f0',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 8,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + ' Mbps';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: '#f1f5f9',
                            },
                            ticks: {
                                font: { size: 10, family: 'monospace' },
                                color: '#64748b'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e2e8f0',
                            },
                            ticks: {
                                font: { size: 10, family: 'monospace' },
                                color: '#64748b',
                                callback: function(value) {
                                    return value >= 1000 ? (value / 1000).toFixed(1) + ' Gbps' : value + ' Mbps';
                                }
                            }
                        }
                    }
                }
            });
        },

        startLiveTicker() {
            setInterval(async () => {
                try {
                    const res = await fetch('{{ route("tenant.reports.mrtg.live") }}');
                    const data = await res.json();
                    if (data.success && mrtgChartInstance) {
                        mrtgChartInstance.data.labels.shift();
                        mrtgChartInstance.data.labels.push(data.timestamp);

                        mrtgChartInstance.data.datasets[0].data.shift();
                        mrtgChartInstance.data.datasets[0].data.push(data.rx_mbps);

                        mrtgChartInstance.data.datasets[1].data.shift();
                        mrtgChartInstance.data.datasets[1].data.push(data.tx_mbps);

                        mrtgChartInstance.data.datasets[2].data.shift();
                        mrtgChartInstance.data.datasets[2].data.push(data.bdix_mbps);

                        mrtgChartInstance.update('none');

                        const curRxElem = document.getElementById('statCurRx');
                        const curTxElem = document.getElementById('statCurTx');
                        if (curRxElem) curRxElem.textContent = data.rx_formatted;
                        if (curTxElem) curTxElem.textContent = data.tx_formatted;
                    }
                } catch (e) {
                    console.error('MRTG Live Poll Error:', e);
                }
            }, 5000);
        }
    };
}
</script>
@endpush
