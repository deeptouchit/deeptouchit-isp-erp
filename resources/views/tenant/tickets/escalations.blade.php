@extends('tenant.layouts.app')

@section('title', __('Sub-ISP & Reseller Escalations') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="escalationPageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Sub-ISP & Reseller Technical Escalations') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.tickets.escalations.export', request()->all()) }}" 
               class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>

            {{-- Log Escalation Primary Button --}}
            <button type="button" 
                    @click="newEscalationModalOpen = true" 
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>{{ __('Log Technical Escalation') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Escalations --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Incidents') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        {{-- Card 2: Open / Triage --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Open / Triage') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    {{ number_format($stats['open']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-inbox"></i>
            </div>
        </div>

        {{-- Card 3: Tier-2 Investigating --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Tier-2 Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ number_format($stats['tier2']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        {{-- Card 4: Tier-3 NOC Escalated --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Tier-3 NOC') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ number_format($stats['tier3']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-tower-cell"></i>
            </div>
        </div>

        {{-- Card 5: Critical Outages --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Critical Outages') }}</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['critical'] > 0 ? 'text-rose-600' : 'text-slate-700' }} leading-tight block">
                    {{ number_format($stats['critical']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['critical'] > 0 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 6: Resolved & Closed --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Resolved') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ number_format($stats['resolved']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.tickets.escalations') }}" class="space-y-2.5">
            {{-- Tier 1: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Input --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="{{ __('Search by ESC #, reseller name, subject, VLAN...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                </div>

                {{-- Category Filter --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="category" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Categories') }}</option>
                        <option value="trunk_congestion" {{ $category === 'trunk_congestion' ? 'selected' : '' }}>{{ __('Trunk Congestion') }}</option>
                        <option value="bgp_routing" {{ $category === 'bgp_routing' ? 'selected' : '' }}>{{ __('BGP Peering / Flap') }}</option>
                        <option value="radius_sync" {{ $category === 'radius_sync' ? 'selected' : '' }}>{{ __('FreeRADIUS AAA / CoA') }}</option>
                        <option value="vlan_allocation" {{ $category === 'vlan_allocation' ? 'selected' : '' }}>{{ __('VLAN & IP Pool') }}</option>
                        <option value="wholesale_billing" {{ $category === 'wholesale_billing' ? 'selected' : '' }}>{{ __('Wholesale Billing') }}</option>
                        <option value="olt_uplink_loss" {{ $category === 'olt_uplink_loss' ? 'selected' : '' }}>{{ __('OLT Uplink Loss') }}</option>
                    </select>
                </div>

                {{-- Impact Level Filter --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="impact" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Impact Levels') }}</option>
                        <option value="critical_outage" {{ $impact === 'critical_outage' ? 'selected' : '' }}>🔴 {{ __('Critical Outage') }}</option>
                        <option value="high_degraded" {{ $impact === 'high_degraded' ? 'selected' : '' }}>🟠 {{ __('High Degraded') }}</option>
                        <option value="medium_packet_loss" {{ $impact === 'medium_packet_loss' ? 'selected' : '' }}>🔵 {{ __('Medium Loss') }}</option>
                        <option value="low_inquiry" {{ $impact === 'low_inquiry' ? 'selected' : '' }}>⚪ {{ __('Low Inquiry') }}</option>
                    </select>
                </div>

                {{-- Per Page Selector --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                        <option value="250" {{ $perPage == 250 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 250]) }}</option>
                        <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 500]) }}</option>
                    </select>
                </div>

                {{-- More Filters Accordion Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200/90 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center justify-between gap-1.5 transition flex-shrink-0 cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-sliders-h text-[11px] text-slate-500"></i>
                        <span>{{ __('More Filters') }}</span>
                        @if($activeMoreCount > 0)
                            <span class="px-1.5 py-0.2 bg-cyan-600 text-white rounded-full text-[10px] font-bold">{{ $activeMoreCount }}</span>
                        @endif
                    </span>
                    <i class="fas text-[9px] transition-transform duration-150" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Buttons Sequence --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.tickets.escalations') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Reseller Filter --}}
                <div class="w-full sm:w-48">
                    <select name="reseller_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Sub-ISPs / Resellers') }}</option>
                        @foreach($resellers as $r)
                            <option value="{{ $r->id }}" {{ (string)$resellerId === (string)$r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lead NOC Engineer Filter --}}
                <div class="w-full sm:w-48">
                    <select name="engineer_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Lead Engineers') }}</option>
                        @foreach($engineers as $eng)
                            <option value="{{ $eng->id }}" {{ (string)$engineerId === (string)$eng->id ? 'selected' : '' }}>{{ $eng->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Incident Status Filter --}}
                <div class="w-full sm:w-44">
                    <select name="status" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('All Statuses') }}</option>
                        <option value="open" {{ $status === 'open' ? 'selected' : '' }}>{{ __('Open / Triage') }}</option>
                        <option value="tier2_investigating" {{ $status === 'tier2_investigating' ? 'selected' : '' }}>{{ __('Tier-2 Investigating') }}</option>
                        <option value="tier3_noc_escalated" {{ $status === 'tier3_noc_escalated' ? 'selected' : '' }}>{{ __('Tier-3 NOC Escalated') }}</option>
                        <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                        <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">{{ __('#') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Escalation ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Sub-ISP / Reseller') }}</th>
                        <th class="text-center font-semibold">{{ __('Incident Subject & Category') }}</th>
                        <th class="text-center font-semibold">{{ __('Lead NOC Engineer') }}</th>
                        <th class="text-center font-semibold">{{ __('Severity') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($escalations as $idx => $e)
                        @php
                            $categoryLabel = match ($e->category) {
                                'trunk_congestion' => __('CIR Trunk Congestion & Latency'),
                                'bgp_routing' => __('BGP Peering / BDIX Prefix Flap'),
                                'radius_sync' => __('FreeRADIUS AAA Disconnect / CoA'),
                                'vlan_allocation' => __('Sub-ISP VLAN & IP Pool Routing'),
                                'wholesale_billing' => __('Wholesale Bandwidth Billing Dispute'),
                                'olt_uplink_loss' => __('Distribution OLT 10G Uplink Loss'),
                                default => __($e->category_name),
                            };
                            $impactLabel = match ($e->impact_level) {
                                'critical_outage' => __('Critical Outage'),
                                'high_degraded' => __('High Degraded'),
                                'medium_packet_loss' => __('Medium Packet Loss'),
                                'low_inquiry' => __('Low Inquiry'),
                                default => strtoupper(str_replace('_', ' ', $e->impact_level)),
                            };
                            $statusLabel = match ($e->status) {
                                'open' => __('Open / Triage'),
                                'tier2_investigating' => __('Tier-2 Investigating'),
                                'tier3_noc_escalated' => __('Tier-3 NOC Escalated'),
                                'resolved' => __('Resolved'),
                                'closed' => __('Closed'),
                                default => strtoupper(str_replace('_', ' ', $e->status)),
                            };
                        @endphp
                        <tr>
                            {{-- # --}}
                            <td class="text-center text-slate-400 font-mono">{{ $escalations->firstItem() + $idx }}</td>
                            
                            {{-- Escalation ID --}}
                            <td class="text-center font-mono font-bold text-cyan-800 whitespace-nowrap">
                                <button type="button" @click="showEscalationDetails(@js($e))" class="hover:underline cursor-pointer">
                                    {{ $e->escalation_number }}
                                </button>
                            </td>

                            {{-- Sub-ISP / Reseller --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $e->reseller_name }}</span>
                                    @if($e->affected_circuits)
                                        <span class="text-slate-400 text-[11px] font-mono">({{ \Illuminate\Support\Str::limit($e->affected_circuits, 25) }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Incident Subject & Category --}}
                            <td class="text-left text-slate-800 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium">{{ \Illuminate\Support\Str::limit($e->subject, 45) }}</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $categoryLabel }}
                                    </span>
                                </div>
                            </td>

                            {{-- Lead NOC Engineer --}}
                            <td class="text-left text-indigo-700 whitespace-nowrap">
                                @if($e->assigned_engineer_name)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold">
                                        <i class="fas fa-user-shield text-indigo-500 text-[10px]"></i>
                                        <span>{{ $e->assigned_engineer_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs font-mono">{{ __('Unassigned') }}</span>
                                @endif
                            </td>

                            {{-- Severity Badge --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $e->impact_badge_color }}">
                                    @if($e->impact_level === 'critical_outage')
                                        <i class="fas fa-triangle-exclamation text-rose-600 text-[9px]"></i>
                                    @endif
                                    <span>{{ $impactLabel }}</span>
                                </span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $e->status_badge_color }}">
                                    <span>{{ $statusLabel }}</span>
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu(@js($e), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-network-wired"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-semibold text-slate-600 block">{{ __('No technical escalations found') }}</span>
                                        <span class="text-[11px] text-slate-400 block">{{ __('Log a new technical escalation for wholesale trunk issues, BGP peering flaps, or AAA outages.') }}</span>
                                    </div>
                                    <button type="button" 
                                            @click="newEscalationModalOpen = true" 
                                            class="h-[30px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer mt-1">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        <span>{{ __('Log Technical Escalation') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($escalations->hasPages())
        <div class="px-3.5 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
                {{ __('Showing :from to :to of :total Escalation Incidents', ['from' => $escalations->firstItem(), 'to' => $escalations->lastItem(), 'total' => $escalations->total()]) }}
            </span>
            <div>
                {{ $escalations->links() }}
            </div>
        </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        {{-- Group 1: Telemetry & RCA Statements --}}
        <div class="py-1">
            <button type="button" 
                    @click="showEscalationDetails(activeMenu); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-blue-600 w-4 text-center text-xs"></i>
                <span>{{ __('View Incident Details') }}</span>
            </button>
            <a :href="`/admin/support/escalations/${activeMenu?.id}/print`" 
               target="_blank" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition cursor-pointer">
                <i class="fas fa-print text-cyan-600 w-4 text-center text-xs"></i>
                <span>{{ __('Print RCA Statement (A4)') }}</span>
            </a>
            <button type="button" 
                    @click="copyEscalationNumber(activeMenu?.escalation_number); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>{{ __('Copy Incident #') }}</span>
            </button>
        </div>

        {{-- Group 2: NOC Progression & Resolution --}}
        <div class="py-1">
            <template x-if="activeMenu?.status !== 'tier3_noc_escalated' && activeMenu?.status !== 'resolved' && activeMenu?.status !== 'closed'">
                <button type="button" 
                        @click="escalateToNocAction(activeMenu?.id); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-purple-600 font-medium transition cursor-pointer">
                    <i class="fas fa-tower-cell text-purple-600 w-4 text-center text-xs"></i>
                    <span>{{ __('Escalate to Tier-3 NOC') }}</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'resolved' && activeMenu?.status !== 'closed'">
                <button type="button" 
                        @click="openResolveModal(activeMenu); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-medium transition cursor-pointer">
                    <i class="fas fa-circle-check text-emerald-600 w-4 text-center text-xs"></i>
                    <span>{{ __('Resolve & Submit RCA') }}</span>
                </button>
            </template>
            <template x-if="activeMenu?.status === 'resolved'">
                <button type="button" 
                        @click="closeIncidentAction(activeMenu?.id); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-medium transition cursor-pointer">
                    <i class="fas fa-lock text-slate-500 w-4 text-center text-xs"></i>
                    <span>{{ __('Close Incident') }}</span>
                </button>
            </template>
        </div>

        {{-- Group 3: Delete Action (Super Admin / ISP Admin only) --}}
        @if(auth()->user() && auth()->user()->canDeleteTickets())
        <div class="py-1">
            <button type="button" 
                    @click="deleteEscalationAction(activeMenu); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 font-medium transition cursor-pointer">
                <i class="fas fa-trash-alt text-rose-500 w-4 text-center text-xs"></i>
                <span>{{ __('Delete Incident') }}</span>
            </button>
        </div>
        @endif
    </div>

    {{-- MODAL 1: LOG NEW ESCALATION MODAL (Production-Grade Natural Modal) --}}
    <div x-show="newEscalationModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="newEscalationModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="newEscalationModalOpen = false">
            <form method="POST" action="{{ route('tenant.tickets.escalations.store') }}">
                @csrf
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h4 class="text-xs font-semibold text-slate-800">{{ __('Log Sub-ISP Technical Escalation') }}</h4>
                    </div>
                    <button type="button" @click="newEscalationModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Sub-ISP / Reseller *') }}</label>
                            <select name="reseller_id" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">{{ __('Select Reseller') }}</option>
                                @foreach($resellers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Technical Category *') }}</label>
                            <select name="category" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="trunk_congestion">{{ __('CIR Trunk Congestion & Latency') }}</option>
                                <option value="bgp_routing">{{ __('BGP Peering / BDIX Prefix Flap') }}</option>
                                <option value="radius_sync">{{ __('FreeRADIUS AAA / Dynamic CoA') }}</option>
                                <option value="vlan_allocation">{{ __('Sub-ISP VLAN & IP Pool') }}</option>
                                <option value="wholesale_billing">{{ __('Wholesale Bandwidth Billing Dispute') }}</option>
                                <option value="olt_uplink_loss">{{ __('OLT 10G Uplink Loss') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Impact Severity *') }}</label>
                            <select name="impact_level" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="high_degraded">{{ __('High Degraded Link') }}</option>
                                <option value="critical_outage">🔴 {{ __('Critical Wholesale Outage') }}</option>
                                <option value="medium_packet_loss">{{ __('Medium Packet Loss') }}</option>
                                <option value="low_inquiry">{{ __('Low Technical Inquiry') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Affected Trunk / Circuit') }}</label>
                            <input type="text" 
                                   name="affected_circuits" 
                                   placeholder="{{ __('e.g. Trunk VLAN 204 (SFP-02)') }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Incident Subject *') }}</label>
                        <input type="text" 
                               name="subject" 
                               required 
                               placeholder="{{ __('e.g. 100 Mbps CIR Wholesale Trunk Saturation during Peak Hours') }}" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Assign Lead NOC Engineer') }}</label>
                            <select name="assigned_engineer_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">{{ __('Select Engineer') }}</option>
                                @foreach($engineers as $eng)
                                    <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Technical Symptoms & Telemetry Details *') }}</label>
                        <textarea name="issue_description" 
                                  required 
                                  rows="3" 
                                  placeholder="{{ __('Describe ping drops, MRTG peak queue load, BGP log messages or AAA error...') }}" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="newEscalationModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        {{ __('Submit Escalation') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: INCIDENT DETAILS & TELEMETRY MODAL --}}
    <div x-show="detailsModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="detailsModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="detailsModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-xs text-indigo-700" x-text="selectedEscalation?.escalation_number"></span>
                        <span class="text-slate-300">|</span>
                        <span class="text-xs font-semibold text-slate-800" x-text="categoryLabel(selectedEscalation?.category, selectedEscalation?.category_name)"></span>
                    </div>
                </div>
                <button type="button" @click="detailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div>
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">{{ __('Subject & Outage Scope') }}</span>
                    <p class="font-bold text-slate-900 text-sm" x-text="selectedEscalation?.subject"></p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Sub-ISP Organization') }}</span>
                        <span class="font-bold text-slate-800" x-text="selectedEscalation?.reseller_name"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Affected Circuit / Trunk') }}</span>
                        <span class="font-mono font-bold text-indigo-700" x-text="selectedEscalation?.affected_circuits || @js(__('Main Trunk Link'))"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Assigned NOC Lead') }}</span>
                        <span class="font-bold text-slate-800" x-text="selectedEscalation?.assigned_engineer_name || @js(__('Unassigned'))"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Incident Status') }}</span>
                        <span class="font-bold uppercase text-indigo-700" x-text="statusLabel(selectedEscalation?.status)"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">{{ __('Issue Description') }}</span>
                    <p class="text-slate-700 leading-relaxed" x-text="selectedEscalation?.issue_description"></p>
                </div>

                <template x-if="selectedEscalation?.resolution_summary">
                    <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200">
                        <span class="text-[10px] text-emerald-800 font-semibold uppercase block mb-0.5">{{ __('Root Cause Analysis (RCA) & Resolution') }}</span>
                        <p class="text-emerald-900 leading-relaxed" x-text="selectedEscalation?.resolution_summary"></p>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                    {{ __('Close') }}
                </button>
                <a :href="`/admin/support/escalations/${selectedEscalation?.id}/print`" target="_blank" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs inline-flex items-center gap-1.5 cursor-pointer transition">
                    <i class="fas fa-print text-xs"></i>
                    <span>{{ __('Print RCA Statement (A4)') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- MODAL 3: RESOLUTION & RCA SUBMISSION MODAL --}}
    <div x-show="resolveModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="resolveModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="resolveModalOpen = false">
            <form :action="`/admin/support/escalations/${selectedEscalation?.id}/status`" method="POST">
                @csrf
                <input type="hidden" name="status" value="resolved">
                
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-semibold text-slate-800">{{ __('Resolve Escalation & Submit RCA') }}</h4>
                            <span class="text-[10.5px] text-slate-500 font-mono" x-text="selectedEscalation?.escalation_number"></span>
                        </div>
                    </div>
                    <button type="button" @click="resolveModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Root Cause Analysis (RCA) & Corrective Action *') }}</label>
                        <textarea name="resolution_summary" 
                                  required 
                                  rows="3" 
                                  placeholder="{{ __('Explain the root cause (e.g. BGP peering flap, queue adjustment) and fix implemented...') }}" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="resolveModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        {{ __('Resolve Incident') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function escalationPageManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            newEscalationModalOpen: false,
            detailsModalOpen: false,
            resolveModalOpen: false,
            selectedEscalation: null,

            categoryMap: {
                trunk_congestion: @js(__('CIR Trunk Congestion & Latency')),
                bgp_routing: @js(__('BGP Peering / BDIX Prefix Flap')),
                radius_sync: @js(__('FreeRADIUS AAA Disconnect / CoA')),
                vlan_allocation: @js(__('Sub-ISP VLAN & IP Pool Routing')),
                wholesale_billing: @js(__('Wholesale Bandwidth Billing Dispute')),
                olt_uplink_loss: @js(__('Distribution OLT 10G Uplink Loss')),
            },

            statusMap: {
                open: @js(__('Open / Triage')),
                tier2_investigating: @js(__('Tier-2 Investigating')),
                tier3_noc_escalated: @js(__('Tier-3 NOC Escalated')),
                resolved: @js(__('Resolved')),
                closed: @js(__('Closed')),
            },

            categoryLabel(cat, fallback) {
                return this.categoryMap[cat] || fallback || cat || '';
            },

            statusLabel(st) {
                return this.statusMap[st] || st || '';
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 220;
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

            showEscalationDetails(item) {
                this.selectedEscalation = item;
                this.detailsModalOpen = true;
            },

            openResolveModal(item) {
                this.selectedEscalation = item;
                this.resolveModalOpen = true;
            },

            copyEscalationNumber(escNo) {
                if (navigator.clipboard && escNo) {
                    navigator.clipboard.writeText(escNo);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: @js(__('Incident # Copied')),
                            text: @js(__(':number copied to clipboard', ['number' => '__ESC__'])).replace('__ESC__', escNo),
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            },

            async escalateToNocAction(id) {
                if (!id) return;
                try {
                    const response = await fetch(`/admin/support/escalations/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ status: 'tier3_noc_escalated' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error escalating incident:', e);
                    location.reload();
                }
            },

            async closeIncidentAction(id) {
                if (!id) return;
                try {
                    const response = await fetch(`/admin/support/escalations/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ status: 'closed' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error closing incident:', e);
                    location.reload();
                }
            },

            deleteEscalationAction(item) {
                if (!item || !item.id) return;
                if (window.Swal) {
                    Swal.fire({
                        title: @js(__('Delete Escalation Incident?')),
                        text: @js(__('Are you sure you want to permanently delete incident #:number? This action cannot be undone.', ['number' => '__ESC__'])).replace('__ESC__', item.escalation_number),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: @js(__('Yes, Delete Incident')),
                        cancelButtonText: @js(__('Cancel'))
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`/admin/support/escalations/${item.id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });
                                const data = await response.json();
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: @js(__('Deleted!')),
                                        text: data.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire(@js(__('Error')), data.message || @js(__('Could not delete technical escalation.')), 'error');
                                }
                            } catch (err) {
                                console.error('Delete error:', err);
                                Swal.fire(@js(__('Error')), @js(__('An unexpected error occurred while deleting the escalation.')), 'error');
                            }
                        }
                    });
                } else if (confirm(@js(__('Are you sure you want to delete incident #:number?', ['number' => '__ESC__'])).replace('__ESC__', item.escalation_number))) {
                    fetch(`/admin/support/escalations/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).then(() => location.reload());
                }
            }
        };
    }
</script>
@endpush
