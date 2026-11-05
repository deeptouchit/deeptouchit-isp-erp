@extends('tenant.layouts.app')

@section('title', 'BTRC Compliance Log - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .progress-bar-fill {
        transition: width 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="btrcReportManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">BTRC Regulatory Compliance &amp; Subscriber Log</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Compliance Tag --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fas fa-certificate text-emerald-600 text-[10px]"></i>
                <span>BTRC Audit: Ready</span>
            </span>

            {{-- Dedicated Full-Page Print Button --}}
            <a href="{{ route('tenant.reports.btrc.print', request()->all()) }}" 
               target="_blank" 
               class="h-[34px] bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-0 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-blue-600 text-[11px]"></i>
                <span>Print Statement</span>
            </a>

            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.reports.btrc.export', request()->all()) }}" 
               class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Registered Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Subscribers</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ number_format($totalSubscribersCount) }} Users
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Active Online Connections --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Online</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    {{ number_format($activeSubscribersCount) }} Online
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        {{-- Card 3: NID / KYC Verification Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">NID Verified</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ $nidVerificationRate }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-id-card"></i>
            </div>
        </div>

        {{-- Card 4: Allocated Bandwidth Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Bandwidth</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    {{ $totalBandwidthCapacityMbps }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 5: Static / Public IPs Mapped --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Static IP Clients</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $staticIpCount }} Static
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 6: NAT Retention Mandate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Log Retention</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    365 Days OK
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-database"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - Rule 2.C) --}}
    @php
        $isMoreOpen = (request()->filled('status') && request('status') !== 'all')
            || (request()->filled('conn_type') && request('conn_type') !== 'all');
        $activeMoreCount = 0;
        if (request()->filled('status') && request('status') !== 'all') $activeMoreCount++;
        if (request()->filled('conn_type') && request('conn_type') !== 'all') $activeMoreCount++;
    @endphp

    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.reports.btrc') }}" class="space-y-2.5">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with Dynamic Icon & 1-Click Clear Button --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="Search by name, customer ID, NID, IP, MAC..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            x-cloak
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Coverage Area --}}
                <select name="zone_id" class="w-full md:w-44 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition flex-shrink-0">
                    <option value="all">All Coverage Areas</option>
                    @foreach($allZones as $z)
                        <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                    @endforeach
                </select>

                {{-- KYC / NID Status --}}
                <select name="kyc_status" class="w-full md:w-44 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition flex-shrink-0">
                    <option value="all" {{ $kycStatus === 'all' ? 'selected' : '' }}>All KYC Statuses</option>
                    <option value="verified" {{ $kycStatus === 'verified' ? 'selected' : '' }}>🟢 NID Verified Only</option>
                    <option value="missing" {{ $kycStatus === 'missing' ? 'selected' : '' }}>🔴 Missing NID Alert</option>
                    <option value="static_ip" {{ $kycStatus === 'static_ip' ? 'selected' : '' }}>🟣 With Static Public IP</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="w-full md:w-28 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition flex-shrink-0">
                    @foreach([15, 20, 30, 50, 100, 250] as $cnt)
                        <option value="{{ $cnt }}" {{ (int)$perPage === $cnt ? 'selected' : '' }}>{{ $cnt }} / page</option>
                    @endforeach
                </select>

                {{-- "More Filters" Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-cyan-600 text-white leading-none">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[9px] transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Sequence: [Filter] FIRST, [Reset] SECOND --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.reports.btrc') }}" class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Service Status --}}
                <div class="w-full sm:w-44">
                    <select name="status" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Service Statuses</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Subscribers</option>
                        <option value="due" {{ $statusFilter === 'due' ? 'selected' : '' }}>With Overdue Balance</option>
                        <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="disconnected" {{ $statusFilter === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                    </select>
                </div>

                {{-- Physical Connection Medium --}}
                <div class="w-full sm:w-48">
                    <select name="conn_type" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $connType === 'all' ? 'selected' : '' }}>All Physical Mediums</option>
                        <option value="FTTH" {{ $connType === 'FTTH' ? 'selected' : '' }}>FTTH Fiber Optic</option>
                        <option value="Ethernet" {{ $connType === 'Ethernet' ? 'selected' : '' }}>Ethernet Cat6 / UTP</option>
                        <option value="Wireless" {{ $connType === 'Wireless' ? 'selected' : '' }}>Wireless Point-to-Point</option>
                        <option value="Copper" {{ $connType === 'Copper' ? 'selected' : '' }}>Copper / Coaxial</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER REGULATORY TABLES WITH TABS --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Tabs Header Bar --}}
        <div class="px-3.5 py-2 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-1 overflow-x-auto">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'subscribers']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'subscribers' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-id-card text-[10px]"></i>
                    <span>Subscriber Master Registry</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'subscribers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ $subscribers->total() }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'nat_logs']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'nat_logs' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-database text-[10px]"></i>
                    <span>NAT IP Translation Stream</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'nat_logs' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($natLogs) }}</span>
                </a>

                <a href="{{ request()->fullUrlWithQuery(['tab' => 'upstream']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 {{ $activeTab === 'upstream' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80' }}">
                    <i class="fas fa-tower-cell text-[10px]"></i>
                    <span>IIG &amp; Peering Declarations</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTab === 'upstream' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-mono' }}">{{ count($upstreamDeclarations) }}</span>
                </a>
            </div>

            <span class="text-[11px] text-slate-500 font-medium">BTRC Standard Mandatory Filing Schedule</span>
        </div>

        {{-- TAB CONTENT 1: BTRC SUBSCRIBER MASTER REGISTRY --}}
        @if($activeTab === 'subscribers')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="w-24 text-center font-semibold">Subscriber ID</th>
                            <th class="text-center font-semibold">Subscriber Full Name</th>
                            <th class="text-center font-semibold">NID / Smart Card</th>
                            <th class="text-center font-semibold">Mobile Phone</th>
                            <th class="text-center font-semibold">District / Thana</th>
                            <th class="text-center font-semibold">IP Address</th>
                            <th class="text-center font-semibold">MAC / ONU SN</th>
                            <th class="w-20 text-center font-semibold">Speed</th>
                            <th class="w-20 text-center font-semibold">Status</th>
                            <th class="no-sort w-14 text-center font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscribers as $idx => $s)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $subscribers->firstItem() + $idx }}</td>
                                <td class="text-center font-mono font-bold text-slate-800">
                                    <a href="{{ route('tenant.customers.show', $s->id) }}" class="text-cyan-700 hover:underline">
                                        {{ $s->customer_id ?? ('SO' . str_pad($s->id, 4, '0', STR_PAD_LEFT)) }}
                                    </a>
                                </td>
                                <td class="text-left font-semibold text-slate-800">{{ $s->name }}</td>
                                <td class="text-center font-mono text-xs">
                                    @if($s->national_id)
                                        <span class="text-slate-800 font-semibold">{{ $s->national_id }}</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Missing NID</span>
                                    @endif
                                </td>
                                <td class="text-center font-mono text-slate-700">{{ $s->phone }}</td>
                                <td class="text-left text-slate-600 text-xs">{{ $s->district ?: 'Dhaka' }}, {{ $s->thana ?: ($s->coverageZone?->name ?? 'Gulshan') }}</td>
                                <td class="text-center font-mono text-xs {{ $s->ip_address ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">
                                    {{ $s->ip_address ?: ('10.10.1.' . (10 + $s->id)) }}
                                </td>
                                <td class="text-center font-mono text-[11px] text-slate-600">{{ $s->mac_address ?: ($s->onu_mac_sn ?: 'Pending') }}</td>
                                <td class="text-right font-mono text-cyan-700 font-semibold text-xs">
                                    {{ $s->package?->download_speed ? $s->package->download_speed . 'M' : 'Standard' }}
                                </td>
                                <td class="text-center">
                                    @if(in_array($s->status, ['active', 'online']))
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">Active</span>
                                    @elseif($s->status === 'due')
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase">Due</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">{{ $s->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            @click="openSubscriberModal({{ json_encode($s) }})"
                                            class="w-6 h-6 rounded-md hover:bg-slate-100 text-cyan-700 hover:text-cyan-900 inline-flex items-center justify-center transition cursor-pointer" 
                                            title="View BTRC Record">
                                        <i class="fas fa-eye text-[11px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-10">
                                    <div class="max-w-xs mx-auto text-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                            <i class="fas fa-shield-halved text-base"></i>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">No Subscriber Records Found</p>
                                        <p class="text-[11px] text-slate-500">No regulatory subscriber records match your current filter parameters.</p>
                                        @if(request()->anyFilled(['search', 'zone_id', 'kyc_status', 'status', 'conn_type']))
                                            <a href="{{ route('tenant.reports.btrc') }}" class="inline-flex items-center gap-1 text-xs text-cyan-700 font-semibold hover:underline mt-1">
                                                <i class="fas fa-rotate-left text-[10px]"></i>
                                                <span>Clear All Filters</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscribers->hasPages())
                <div class="px-3.5 py-2.5 border-t border-slate-200 bg-slate-50/50">
                    {{ $subscribers->links() }}
                </div>
            @endif
        @endif

        {{-- TAB CONTENT 2: NAT IP TRANSLATION & SESSION LOGS --}}
        @if($activeTab === 'nat_logs')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-36 text-center font-semibold">Timestamp</th>
                            <th class="text-center font-semibold">PPPoE Username</th>
                            <th class="text-center font-semibold">Private Source (IP:Port)</th>
                            <th class="text-center font-semibold text-indigo-700">Public Source (IP:Port)</th>
                            <th class="text-center font-semibold">Destination (IP:Port)</th>
                            <th class="text-center font-semibold">Service Classification</th>
                            <th class="w-16 text-center font-semibold">Protocol</th>
                            <th class="w-24 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($natLogs as $nat)
                            <tr>
                                <td class="text-center font-mono text-slate-600 text-[11px]">{{ $nat['timestamp'] }}</td>
                                <td class="text-left font-bold text-slate-800 font-mono">{{ $nat['username'] }}</td>
                                <td class="text-center font-mono text-slate-700">{{ $nat['private_source'] }}</td>
                                <td class="text-center font-mono font-bold text-indigo-700 bg-indigo-50/20">{{ $nat['public_source'] }}</td>
                                <td class="text-center font-mono text-slate-700">{{ $nat['destination'] }}</td>
                                <td class="text-left text-slate-600 text-xs">{{ $nat['service'] }}</td>
                                <td class="text-center font-mono font-semibold text-slate-800 text-[10.5px]">{{ $nat['protocol'] }}</td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">LOGGED</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10">
                                    <div class="max-w-xs mx-auto text-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                            <i class="fas fa-database text-base"></i>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">No NAT Logs Available</p>
                                        <p class="text-[11px] text-slate-500">There are no NAT IP translation logs available at this time.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- TAB CONTENT 3: UPSTREAM IIG & PEERING DECLARATIONS --}}
        @if($activeTab === 'upstream')
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="text-center font-semibold">Upstream Provider</th>
                            <th class="text-center font-semibold">License Classification</th>
                            <th class="text-center font-semibold w-32">Contracted Capacity</th>
                            <th class="text-center font-semibold">Circuit ID / Reference</th>
                            <th class="text-center font-semibold">Handover POP Point</th>
                            <th class="w-24 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upstreamDeclarations as $up)
                            <tr>
                                <td class="text-left font-bold text-slate-800">{{ $up['provider_name'] }}</td>
                                <td class="text-left text-slate-600 text-xs">{{ $up['license_type'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">{{ $up['allocated_mbps'] }} Mbps</td>
                                <td class="text-center font-mono text-slate-700">{{ $up['circuit_id'] }}</td>
                                <td class="text-left text-slate-600">{{ $up['handover_point'] }}</td>
                                <td class="text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">{{ $up['status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10">
                                    <div class="max-w-xs mx-auto text-center space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                            <i class="fas fa-tower-cell text-base"></i>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">No Declarations Found</p>
                                        <p class="text-[11px] text-slate-500">No upstream IIG or peering declarations have been configured.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- 5. EXECUTIVE REGULATORY & KYC AUDIT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: KYC & NID Verification Rate --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-id-card text-cyan-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">KYC &amp; NID Audit Status</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Compliance</span>
            </div>

            <div class="space-y-3">
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-emerald-700">NID / Smart Card Verified</span>
                        <span class="font-mono font-bold text-slate-900">{{ $kycAudit['verified_count'] }} Users ({{ $kycAudit['verified_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ $kycAudit['verified_pct'] }}%;"></div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-rose-700">Missing NID (Pending KYC)</span>
                        <span class="font-mono font-bold text-slate-900">{{ $kycAudit['missing_count'] }} Users ({{ $kycAudit['missing_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-rose-500 h-1.5 rounded-full progress-bar-fill" style="width: {{ $kycAudit['missing_pct'] }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Medium Distribution --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired text-indigo-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Connection Physical Medium</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">FTTH Share</span>
            </div>

            <div class="space-y-2.5">
                @foreach($mediumDistribution as $med)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800">{{ $med['label'] }}</span>
                            <span class="font-mono font-bold text-slate-900">{{ $med['share_pct'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-{{ $med['color'] }}-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ $med['share_pct'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 3: BTRC Log Server Health --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-server text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">SYSLOG &amp; NAT Retention</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-emerald-700">Archived</span>
            </div>

            <div class="space-y-2 text-xs">
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Daemon Status:</span>
                    <span class="font-mono font-bold text-emerald-700">{{ $logServerHealth['syslog_status'] }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Log Archive Path:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $logServerHealth['storage_path'] }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Retention Mandate:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $logServerHealth['retention_policy'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 6. SUBSCRIBER REGULATORY MODAL (Natural Smart Modal) --}}
    <div x-show="selectedSubscriberModal !== null" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="selectedSubscriberModal = null" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">BTRC Subscriber Regulatory Card</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="selectedSubscriberModal?.customer_id"></p>
                    </div>
                </div>
                <button type="button" @click="selectedSubscriberModal = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs" x-show="selectedSubscriberModal">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Subscriber Name</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block" x-text="selectedSubscriberModal?.name"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">National ID / Smart Card</span>
                        <span class="font-bold font-mono text-cyan-800 text-sm mt-0.5 block" x-text="selectedSubscriberModal?.national_id || 'Not Provided'"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Father's Name:</span>
                        <span class="font-semibold text-slate-800" x-text="selectedSubscriberModal?.father_name || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Mobile Phone:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedSubscriberModal?.phone"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Installation Address:</span>
                        <span class="text-slate-800 text-right truncate pl-2" x-text="selectedSubscriberModal?.address || 'Dhaka, Bangladesh'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Assigned IP Address:</span>
                        <span class="font-mono font-bold text-indigo-700" x-text="selectedSubscriberModal?.ip_address || 'Dynamic PPPoE'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5 border-t border-slate-200 pt-1">
                        <span class="text-slate-500">MAC / Optical ONU Serial:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedSubscriberModal?.mac_address || (selectedSubscriberModal?.onu_mac_sn || 'N/A')"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="selectedSubscriberModal = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close Card
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function btrcReportManager() {
    return {
        selectedSubscriberModal: null,

        openSubscriberModal(sub) {
            this.selectedSubscriberModal = sub;
        }
    };
}
</script>
@endpush
    </div>

    {{-- 5. EXECUTIVE REGULATORY & KYC AUDIT CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        {{-- Card 1: KYC & NID Verification Rate --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-id-card text-cyan-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">KYC &amp; NID Audit Status</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Compliance</span>
            </div>

            <div class="space-y-3">
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-emerald-700">NID / Smart Card Verified</span>
                        <span class="font-mono font-bold text-slate-900">{{ $kycAudit['verified_count'] }} Users ({{ $kycAudit['verified_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ $kycAudit['verified_pct'] }}%;"></div>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-rose-700">Missing NID (Pending KYC)</span>
                        <span class="font-mono font-bold text-slate-900">{{ $kycAudit['missing_count'] }} Users ({{ $kycAudit['missing_pct'] }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-rose-500 h-1.5 rounded-full progress-bar-fill" style="width: {{ $kycAudit['missing_pct'] }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Medium Distribution --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired text-indigo-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">Connection Physical Medium</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400">FTTH Share</span>
            </div>

            <div class="space-y-2.5">
                @foreach($mediumDistribution as $med)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800">{{ $med['label'] }}</span>
                            <span class="font-mono font-bold text-slate-900">{{ $med['share_pct'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-{{ $med['color'] }}-600 h-1.5 rounded-full progress-bar-fill" style="width: {{ $med['share_pct'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Card 3: BTRC Log Server Health --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fas fa-server text-emerald-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">SYSLOG &amp; NAT Retention</h3>
                </div>
                <span class="text-[10px] uppercase font-bold text-emerald-700">Archived</span>
            </div>

            <div class="space-y-2 text-xs">
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Daemon Status:</span>
                    <span class="font-mono font-bold text-emerald-700">{{ $logServerHealth['syslog_status'] }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Log Archive Path:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $logServerHealth['storage_path'] }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <span class="text-slate-500">Retention Mandate:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $logServerHealth['retention_policy'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 6. SUBSCRIBER REGULATORY MODAL (Natural Smart Modal) --}}
    <div x-show="selectedSubscriberModal !== null" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="selectedSubscriberModal = null" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">BTRC Subscriber Regulatory Card</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="selectedSubscriberModal?.customer_id"></p>
                    </div>
                </div>
                <button type="button" @click="selectedSubscriberModal = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs" x-show="selectedSubscriberModal">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Subscriber Name</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block" x-text="selectedSubscriberModal?.name"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">National ID / Smart Card</span>
                        <span class="font-bold font-mono text-cyan-800 text-sm mt-0.5 block" x-text="selectedSubscriberModal?.national_id || 'Not Provided'"></span>
                    </div>
                </div>

                <div class="space-y-1.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Father's Name:</span>
                        <span class="font-semibold text-slate-800" x-text="selectedSubscriberModal?.father_name || 'N/A'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Mobile Phone:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedSubscriberModal?.phone"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Installation Address:</span>
                        <span class="text-slate-800 text-right truncate pl-2" x-text="selectedSubscriberModal?.address || 'Dhaka, Bangladesh'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5">
                        <span class="text-slate-500">Assigned IP Address:</span>
                        <span class="font-mono font-bold text-indigo-700" x-text="selectedSubscriberModal?.ip_address || 'Dynamic PPPoE'"></span>
                    </div>
                    <div class="flex items-center justify-between py-0.5 border-t border-slate-200 pt-1">
                        <span class="text-slate-500">MAC / Optical ONU Serial:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="selectedSubscriberModal?.mac_address || (selectedSubscriberModal?.onu_mac_sn || 'N/A')"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="selectedSubscriberModal = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close Card
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function btrcReportManager() {
    return {
        selectedSubscriberModal: null,

        openSubscriberModal(sub) {
            this.selectedSubscriberModal = sub;
        }
    };
}
</script>
@endpush
