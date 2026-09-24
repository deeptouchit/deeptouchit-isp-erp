@extends('tenant.layouts.app')

@section('title', 'Activity & Audit Trail - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* SaaS Table Overrides for Audit Logs */
    .saas-table th {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .json-key { color: #38bdf8; }
    .json-string { color: #a5f3fc; }
    .json-number { color: #fde047; }
    .json-boolean { color: #f472b6; }
    .json-null { color: #94a3b8; }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="auditTrailManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Title + Icon + Actions ONLY, strictly no <p> tags) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-sm flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Activity &amp; Security Audit Trail</h1>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" 
                    @click="showPurgeModal = true" 
                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                <i class="fas fa-trash-can text-rose-600 text-[10px]"></i>
                <span>Purge Logs</span>
            </button>
            <a href="{{ route('tenant.settings.audit-logs.export', request()->query()) }}" 
               class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-file-csv text-emerald-600 text-[10px]"></i>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('tenant.settings.audit-logs.print', request()->query()) }}" 
               target="_blank" 
               class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-print text-slate-600 text-[10px]"></i>
                <span>Print Trail</span>
            </a>
            <a href="{{ route('tenant.settings.audit-logs') }}" 
               class="px-2.5 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-xs font-semibold border border-cyan-200 transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-rotate text-cyan-600 text-[10px]"></i>
                <span>Live Refresh</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (AGENTS.md Rule 2.B: Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Audit Events --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Events</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ $stats['total_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

        {{-- Card 2: Today's Telemetry --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Today's Logs</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['today_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 3: Security & Auth --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Auth &amp; Security</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ $stats['security_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
        </div>

        {{-- Card 4: Billing & Finance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Billing Events</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ $stats['finance_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 5: Network & MikroTik --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Network Sync</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $stats['network_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 6: System & Admin --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">System &amp; Admin</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block">
                    {{ $stats['system_events'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-sliders"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.settings.audit-logs') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-7 gap-2.5 items-center">
            {{-- Search Box --}}
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search actor, description, IP, event..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none">
            </div>

            {{-- Category Filter --}}
            <div>
                <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $categoryFilter === 'all' || !$categoryFilter ? 'selected' : '' }}>All Categories</option>
                    <option value="security" {{ $categoryFilter === 'security' ? 'selected' : '' }}>Security &amp; Auth</option>
                    <option value="finance" {{ $categoryFilter === 'finance' ? 'selected' : '' }}>Billing &amp; Finance</option>
                    <option value="network" {{ $categoryFilter === 'network' ? 'selected' : '' }}>Network &amp; MikroTik</option>
                    <option value="system" {{ $categoryFilter === 'system' ? 'selected' : '' }}>System &amp; Admin</option>
                </select>
            </div>

            {{-- Actor Type Filter --}}
            <div>
                <select name="actor_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $actorFilter === 'all' || !$actorFilter ? 'selected' : '' }}>All Actor Roles</option>
                    <option value="tenant" {{ $actorFilter === 'tenant' ? 'selected' : '' }}>ISP Admin</option>
                    <option value="owner" {{ $actorFilter === 'owner' ? 'selected' : '' }}>Super Admin</option>
                    <option value="staff" {{ $actorFilter === 'staff' ? 'selected' : '' }}>Staff / Technician</option>
                    <option value="reseller" {{ $actorFilter === 'reseller' ? 'selected' : '' }}>Reseller</option>
                    <option value="customer" {{ $actorFilter === 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="system" {{ $actorFilter === 'system' ? 'selected' : '' }}>System Daemon</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none" title="Filter From Date">
            </div>

            {{-- Per Page Selector --}}
            <div>
                <select name="per_page" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Logs / Page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 Logs / Page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Logs / Page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Logs / Page</option>
                </select>
            </div>

            {{-- Action Buttons: Filter (Cyan) then Reset (Slate) --}}
            <div class="flex items-center gap-1.5">
                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.settings.audit-logs') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-32">Timestamp</th>
                        <th class="w-36">Actor</th>
                        <th class="w-36">Event</th>
                        <th>Activity Summary</th>
                        <th class="w-28 text-center font-mono">IP Address</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        @php
                            $actorBadge = $log->actor_badge;
                            $eventBadge = $log->event_type_badge;
                        @endphp
                        <tr>
                            {{-- Index --}}
                            <td class="text-center font-mono text-slate-500 text-xs">
                                {{ $logs->firstItem() + $index }}
                            </td>

                            {{-- Compact Timestamp --}}
                            <td class="font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ $log->created_at ? $log->created_at->format('d M, h:i A') : 'N/A' }}
                            </td>

                            {{-- Actor Name --}}
                            <td class="font-semibold text-slate-800 whitespace-nowrap">
                                {{ $log->actor_name ?? 'System' }}
                            </td>

                            {{-- Event Type Badge --}}
                            <td class="whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border inline-flex items-center gap-1 {{ $eventBadge['class'] }}">
                                    <i class="fas {{ $eventBadge['icon'] }} text-[8.5px]"></i>
                                    <span>{{ $eventBadge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Activity Summary (Short compact single-line preview) --}}
                            <td class="text-slate-700 text-xs">
                                <span class="truncate block max-w-sm cursor-pointer hover:text-cyan-700 hover:underline" 
                                      @click="viewLogTelemetry({{ $log->id }})" 
                                      title="{{ $log->description }}">
                                    {{ $log->description }}
                                </span>
                            </td>

                            {{-- IP Address --}}
                            <td class="text-center font-mono text-slate-600 text-xs whitespace-nowrap">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>

                            {{-- Action 3-Dot Menu --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($log) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="fas fa-shield-halved text-3xl mb-2 text-slate-300 block"></i>
                                <span class="text-xs font-semibold">No audit logs found matching your filter criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1.5 text-xs animate-in fade-in zoom-in-95 duration-100"
         style="display: none;">
        
        <div class="px-3 py-1.5 border-b border-slate-100 bg-slate-50/70">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Audit Record</span>
            <span class="text-[11px] font-bold text-slate-700 truncate block font-mono" x-text="`Log #${activeMenu?.id}`"></span>
        </div>

        <div class="p-1 space-y-0.5">
            {{-- View Details --}}
            <button type="button" 
                    @click="viewLogTelemetry(activeMenu.id); activeMenu = null;" 
                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-cyan-50 hover:text-cyan-700 text-slate-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-circle-info text-cyan-600 text-[11px] w-4 text-center"></i>
                <span>View Telemetry Details</span>
            </button>

            {{-- Filter by this Actor --}}
            <a :href="`{{ route('tenant.settings.audit-logs') }}?search=${encodeURIComponent(activeMenu?.actor_name || '')}`" 
               class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 hover:text-slate-900 text-slate-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-user-filter text-indigo-500 text-[11px] w-4 text-center"></i>
                <span>Filter by this Actor</span>
            </a>

            {{-- Filter by Event Type --}}
            <a :href="`{{ route('tenant.settings.audit-logs') }}?event_type=${encodeURIComponent(activeMenu?.event_type || '')}`" 
               class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 hover:text-slate-900 text-slate-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-filter text-amber-500 text-[11px] w-4 text-center"></i>
                <span>Filter by Event Type</span>
            </a>

            {{-- Trace IP --}}
            <a :href="`{{ route('tenant.settings.audit-logs') }}?search=${encodeURIComponent(activeMenu?.ip_address || '')}`" 
               class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-50 hover:text-slate-900 text-slate-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-network-wired text-purple-500 text-[11px] w-4 text-center"></i>
                <span>Trace IP Address</span>
            </a>

            <div class="border-t border-slate-100 my-1"></div>

            {{-- Delete Log Entry --}}
            <button type="button" 
                    @click="deleteLogAction(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-rose-50 text-rose-600 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 text-[11px] w-4 text-center"></i>
                <span>Delete Log Entry</span>
            </button>
        </div>
    </div>

    {{-- 6. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}

    {{-- MODAL 1: VIEW TELEMETRY & METADATA DETAILS --}}
    <div x-show="showDetailsModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showDetailsModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Security Audit Telemetry &amp; Metadata</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Record ID: #${selectedLog?.id} • ${selectedLog?.created_at}`"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto" x-show="selectedLog">
                {{-- Key Attributes Grid --}}
                <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Actor / User</span>
                        <span class="font-semibold text-slate-800 text-xs" x-text="selectedLog?.actor_name"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Role Scope</span>
                        <span class="font-mono text-xs text-indigo-700 font-bold" x-text="selectedLog?.actor_type"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Event Identifier</span>
                        <span class="font-mono text-xs text-cyan-800 font-bold" x-text="selectedLog?.event_type"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Relative Timestamp</span>
                        <span class="font-mono text-xs text-slate-700" x-text="selectedLog?.relative_time"></span>
                    </div>
                </div>

                {{-- Full Activity Description --}}
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Activity Log Description</label>
                    <p class="text-slate-700 text-xs p-3 bg-slate-50 rounded-lg border border-slate-200/80 leading-relaxed" x-text="selectedLog?.description"></p>
                </div>

                {{-- IP & User-Agent Telemetry --}}
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Client IP Address:</span>
                        <span class="font-mono text-xs font-bold text-slate-800" x-text="selectedLog?.ip_address"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Client Platform:</span>
                        <span class="text-xs text-slate-700" x-text="selectedLog?.device?.platform"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Browser / Agent:</span>
                        <span class="text-xs text-slate-700" x-text="selectedLog?.device?.browser"></span>
                    </div>
                    <div class="pt-1 border-t border-slate-200/80">
                        <span class="text-[9.5px] font-mono text-slate-500 break-all" x-text="selectedLog?.user_agent"></span>
                    </div>
                </div>

                {{-- Metadata JSON Payload --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[10px] uppercase font-bold text-slate-500">Parsed Metadata Payload (JSON)</label>
                        <button type="button" @click="copyJsonPayload()" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700 flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-copy text-[10px]"></i>
                            <span>Copy JSON</span>
                        </button>
                    </div>
                    <pre class="bg-slate-900 text-cyan-300 font-mono text-[11px] p-3.5 rounded-lg border border-slate-800 overflow-x-auto max-h-48" x-html="formatJsonOutput(selectedLog?.metadata)"></pre>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" @click="showDetailsModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg cursor-pointer transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: PURGE OLD LOGS --}}
    <div x-show="showPurgeModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showPurgeModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-trash-can"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Purge Legacy Audit Logs</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Free up database space and optimize indexes</p>
                    </div>
                </div>
                <button type="button" @click="showPurgeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <form @submit.prevent="executePurgeLogs()">
                <div class="p-5 space-y-3.5 text-xs">
                    <div class="p-3 bg-rose-50/50 rounded-lg border border-rose-200 text-rose-800 space-y-1">
                        <div class="font-bold flex items-center gap-1 text-[11.5px]">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span>Irreversible Log Deletion</span>
                        </div>
                        <p class="text-[10.5px] leading-relaxed text-rose-700">
                            Purging historical audit entries permanently removes them from the database. A summary record of this action will be kept in the audit stream.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Retention Cutoff Period</label>
                        <select x-model="purgeDays" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none">
                            <option value="30">Delete logs older than 30 Days</option>
                            <option value="60">Delete logs older than 60 Days</option>
                            <option value="90" selected>Delete logs older than 90 Days (Recommended)</option>
                            <option value="180">Delete logs older than 180 Days (6 Months)</option>
                            <option value="365">Delete logs older than 365 Days (1 Year)</option>
                        </select>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" @click="showPurgeModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs cursor-pointer transition flex items-center gap-1.5">
                        <i class="fas fa-trash-can text-xs"></i>
                        <span>Confirm Purge</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function auditTrailManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        showDetailsModal: false,
        showPurgeModal: false,
        selectedLog: null,
        purgeDays: 90,

        // Toggle 3-Dot Menu with strict 2px offset & boundary checking (AGENTS.md Rule 2.E)
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

        // Fetch single log telemetry via AJAX
        async viewLogTelemetry(logId) {
            try {
                const response = await fetch(`/admin/settings/audit-logs/${logId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    this.selectedLog = result.log;
                    this.showDetailsModal = true;
                }
            } catch (err) {
                console.error(err);
            }
        },

        // Delete Single Log Entry
        deleteLogAction(log) {
            Swal.fire({
                title: 'Delete Audit Record?',
                text: `Permanently delete log entry #${log.id} (${log.event_type})?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/admin/settings/audit-logs/${log.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const res = await response.json();
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    } catch (err) {
                        Swal.fire('Error', 'Failed to delete record.', 'error');
                    }
                }
            });
        },

        // Execute Purge Logs
        async executePurgeLogs() {
            Swal.fire({
                title: 'Purging Legacy Logs...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('/admin/settings/audit-logs/purge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ days: this.purgeDays })
                });
                const result = await response.json();
                this.showPurgeModal = false;

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Purge Complete',
                        text: result.message,
                        confirmButtonColor: '#0891b2'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to purge legacy logs.', 'error');
            }
        },

        // Format JSON syntax highlighting
        formatJsonOutput(obj) {
            if (!obj || Object.keys(obj).length === 0) {
                return '<span class="text-slate-500">// No additional metadata attached</span>';
            }
            const json = JSON.stringify(obj, null, 2);
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        },

        // Copy raw JSON to clipboard
        copyJsonPayload() {
            if (!this.selectedLog || !this.selectedLog.metadata) return;
            navigator.clipboard.writeText(JSON.stringify(this.selectedLog.metadata, null, 2));
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Metadata payload copied to clipboard',
                timer: 1200,
                showConfirmButton: false
            });
        }
    };
}
</script>
@endpush
