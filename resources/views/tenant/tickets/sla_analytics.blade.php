@extends('tenant.layouts.app')

@section('title', 'Staff Performance & SLA Analytics - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="pageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Staff Performance &amp; SLA Analytics</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.tickets.sla-analytics.print', ['period' => $period]) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Report</span>
            </a>
            <a href="{{ route('tenant.tickets.sla-analytics.export') }}" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-excel text-xs"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: On-Time Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">On-Time Rate</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                    {{ $stats['compliance_rate'] }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-check"></i>
            </div>
        </div>

        {{-- Card 2: Avg Response Time --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Avg Response Time</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['avg_frt'] }} Mins
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 3: Avg Solve Time --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Avg Solve Time</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ $stats['avg_mttr'] }} Hours
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-stopwatch"></i>
            </div>
        </div>

        {{-- Card 4: Overdue Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Overdue Tasks</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['breached_count'] > 0 ? 'text-amber-600' : 'text-slate-700' }} leading-tight block">
                    {{ number_format($stats['breached_count']) }} Tasks
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['breached_count'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 5: Resolved Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Resolved Tasks</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ number_format($stats['total_resolved']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        {{-- Card 6: First Call Solved --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">First Call Solved</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['fcr_rate'] }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bullseye"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.tickets.sla-analytics') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            {{-- Filter 1: Period Window --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Time Period</label>
                <select name="period" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90_days" {{ $period === '90_days' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>

            {{-- Filter 2: Staff / Engineer --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Staff Member</label>
                <select name="engineer_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Staff &amp; Engineers</option>
                    @foreach($engineers as $eng)
                        <option value="{{ $eng->id }}" {{ (string)$engineerId === (string)$eng->id ? 'selected' : '' }}>{{ $eng->name }} ({{ ucwords(str_replace('_', ' ', $eng->role ?? 'Staff')) }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter 3: Channel / Department --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Support Channel</label>
                <select name="channel" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all" {{ $channel === 'all' ? 'selected' : '' }}>All Channels</option>
                    <option value="tickets" {{ $channel === 'tickets' ? 'selected' : '' }}>Support Tickets</option>
                    <option value="field_jobs" {{ $channel === 'field_jobs' ? 'selected' : '' }}>Field Jobs</option>
                    <option value="escalations" {{ $channel === 'escalations' ? 'selected' : '' }}>Reseller Escalations</option>
                </select>
            </div>

            {{-- Filter 4: Universal Search --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Staff</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, role..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>
            </div>

            {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.tickets.sla-analytics') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. CATEGORY SLA ATTAINMENT MATRIX (Compact Overview Strip) --}}
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Problem Categories &amp; Solve Time</h3>
            </div>
            <span class="text-[10.5px] text-slate-500 font-medium">Target Time vs Actual Solve Time</span>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($categoryBreakdown as $cat)
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <div class="min-w-0 pr-2">
                        <span class="text-xs font-bold text-slate-800 block truncate">{{ $cat['name'] }}</span>
                        <div class="flex items-center gap-2 mt-0.5 text-[10.5px] text-slate-500 font-mono">
                            <span>Target: {{ $cat['target'] }}</span>
                            <span>•</span>
                            <span class="text-indigo-700 font-semibold">Actual: {{ $cat['actual'] }}</span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs font-mono font-bold text-emerald-600 block">{{ $cat['sla_pct'] }}%</span>
                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $cat['status'] === 'Good' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                            {{ $cat['status'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 5. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">SL</th>
                        <th>Staff Name</th>
                        <th>Role / Position</th>
                        <th class="text-center">Assigned</th>
                        <th class="text-center">Resolved</th>
                        <th class="text-center">Avg Solve Time</th>
                        <th class="text-center">On-Time Rate</th>
                        <th class="text-center">Rating</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedScorecards as $idx => $sc)
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $paginatedScorecards->firstItem() + $idx }}</td>
                            
                            {{-- Engineer Name (Single data, clickable) --}}
                            <td class="font-semibold text-slate-800">
                                <a href="javascript:void(0)" @click="showScorecardDetails(@js($sc))" class="hover:text-cyan-700 hover:underline">
                                    {{ $sc->name }}
                                </a>
                            </td>

                            {{-- Role (Single data) --}}
                            <td class="text-slate-600 text-xs">
                                {{ $sc->role }}
                            </td>

                            {{-- Assigned Tasks (Single data) --}}
                            <td class="text-center font-mono font-bold text-slate-700">
                                {{ $sc->assigned_count }}
                            </td>

                            {{-- Resolved Tasks (Single data) --}}
                            <td class="text-center font-mono font-bold text-indigo-700">
                                {{ $sc->resolved_count }}
                            </td>

                            {{-- Avg Resolution Time (Single data) --}}
                            <td class="text-center font-mono font-bold text-slate-800">
                                {{ $sc->avg_resolution_hours }}h
                            </td>

                            {{-- SLA Compliance % (Single data) --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sc->compliance_rate >= 95 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($sc->compliance_rate >= 90 ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                                    <span>{{ $sc->compliance_rate }}%</span>
                                </span>
                            </td>

                            {{-- Rating Grade Badge --}}
                            <td class="text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold font-mono border {{ $sc->grade === 'A+' ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                    ★ {{ $sc->grade }}
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu(@js($sc), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-user-gear"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500">No staff performance records found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($paginatedScorecards->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Showing {{ $paginatedScorecards->firstItem() }} to {{ $paginatedScorecards->lastItem() }} of {{ $paginatedScorecards->total() }} staff members
                </span>
                <div>
                    {{ $paginatedScorecards->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 6. GLOBAL FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 w-48 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        {{-- Group 1: Performance Audit --}}
        <div class="py-1">
            <button type="button" 
                    @click="showScorecardDetails(activeMenu); activeMenu = null"
                    class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-chart-line text-slate-400 w-4"></i>
                <span>View Performance Details</span>
            </button>
            <a :href="`{{ route('tenant.tickets.sla-analytics.print') }}?engineer_id=${activeMenu?.id}&period={{ $period }}`" 
               target="_blank"
               @click="activeMenu = null"
               class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-print text-slate-400 w-4"></i>
                <span>Print Staff Report</span>
            </a>
        </div>

        {{-- Group 2: Quick Actions --}}
        <div class="py-1">
            <button type="button" 
                    @click="copyStaffName(activeMenu); activeMenu = null"
                    class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-copy text-slate-400 w-4"></i>
                <span>Copy Staff Name</span>
            </button>
        </div>
    </div>

    {{-- MODAL: STAFF PERFORMANCE DETAILS MODAL --}}
    <div x-show="detailsModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="detailsModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="detailsModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedScorecard?.name"></h4>
                        <span class="text-[10.5px] text-slate-500 font-normal" x-text="selectedScorecard?.role"></span>
                    </div>
                </div>
                <button type="button" @click="detailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                {{-- Scorecard KPI Strip --}}
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[9px] text-slate-500 font-bold uppercase block">Tasks Assigned</span>
                        <span class="text-base font-bold font-mono text-slate-800" x-text="selectedScorecard?.assigned_count"></span>
                    </div>
                    <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200 text-center">
                        <span class="text-[9px] text-emerald-700 font-bold uppercase block">Resolved On-Time</span>
                        <span class="text-base font-bold font-mono text-emerald-800" x-text="selectedScorecard?.resolved_count"></span>
                    </div>
                    <div class="p-2.5 bg-indigo-50 rounded-lg border border-indigo-200 text-center">
                        <span class="text-[9px] text-indigo-700 font-bold uppercase block">On-Time Rate</span>
                        <span class="text-base font-bold font-mono text-indigo-800" x-text="selectedScorecard?.compliance_rate + '%'"></span>
                    </div>
                </div>

                {{-- Detailed Metrics Grid --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Avg Solve Time</span>
                        <span class="font-bold text-slate-800 font-mono text-xs" x-text="selectedScorecard?.avg_resolution_hours + ' Hours'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Overdue Tasks</span>
                        <span class="font-mono font-bold text-amber-600 text-xs" x-text="selectedScorecard?.breached_count + ' Tasks'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Customer Rating</span>
                        <span class="font-bold text-purple-700 font-mono text-xs" x-text="selectedScorecard?.csat_score + ' / 5.0 (Excellent)'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Performance Grade</span>
                        <span class="font-bold text-emerald-700 text-xs" x-text="selectedScorecard?.grade + ' (Top Performer)'"></span>
                    </div>
                </div>

                {{-- Operational Assessment --}}
                <div class="p-3 bg-slate-900 rounded-lg text-cyan-300 font-mono text-[11px] leading-relaxed border border-slate-800">
                    <div class="text-slate-400 font-bold uppercase text-[10px] mb-1 flex items-center gap-1.5">
                        <i class="fas fa-check-circle text-cyan-400"></i>
                        <span>Performance Summary Note:</span>
                    </div>
                    <div>
                        Staff consistently meets resolution targets with fast turnaround on fiber splicing, network diagnostics, and customer setup.
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
                <a :href="`{{ route('tenant.tickets.sla-analytics.print') }}?engineer_id=${selectedScorecard?.id}&period={{ $period }}`" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs inline-flex items-center gap-1.5">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Staff Report</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function pageManager() {
        return {
            activeMenu: null,
            menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },
            detailsModalOpen: false,
            selectedScorecard: null,

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

            showScorecardDetails(sc) {
                this.selectedScorecard = sc;
                this.detailsModalOpen = true;
            },

            copyStaffName(sc) {
                if (!sc?.name) return;
                navigator.clipboard.writeText(sc.name).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Copied: ${sc.name}`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        };
    }
</script>
@endpush
