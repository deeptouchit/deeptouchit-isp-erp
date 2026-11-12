@extends('tenant.layouts.app')

@section('title', __('Staff Performance & SLA Analytics - :company', ['company' => $tenant->company_name ?? $tenant->name]))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="pageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strict Rule 2.A: Title + Icon + Action Buttons ONLY. No subtitles/p tags) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Staff Performance & SLA Analytics') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.tickets.sla-analytics.print', ['period' => $period]) }}" target="_blank" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Audit Report') }}</span>
            </a>
            <a href="{{ route('tenant.tickets.sla-analytics.export') }}" class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-excel text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: On-Time Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('On-Time Rate') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Avg Response Time') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ __(':mins Mins', ['mins' => $stats['avg_frt']]) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 3: Avg Solve Time --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Avg Solve Time') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ __(':hours Hours', ['hours' => $stats['avg_mttr']]) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-stopwatch"></i>
            </div>
        </div>

        {{-- Card 4: Overdue Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Overdue Tasks') }}</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['breached_count'] > 0 ? 'text-amber-600' : 'text-slate-700' }} leading-tight block">
                    {{ __(':count Tasks', ['count' => number_format($stats['breached_count'])]) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['breached_count'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        {{-- Card 5: Resolved Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Resolved Tasks') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('First Call Solved') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['fcr_rate'] }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bullseye"></i>
            </div>
        </div>
    </div>

    {{-- 3. CUSTOMER-STANDARD 2-TIER FILTER SYSTEM (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
             searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.tickets.sla-analytics') }}" class="space-y-2.5">
            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="{{ __('Search by staff name, role, phone...') }}"
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Time Period --}}
                <select name="period" class="w-full md:w-36 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>{{ __('Last 7 Days') }}</option>
                    <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>{{ __('Last 30 Days') }}</option>
                    <option value="90_days" {{ $period === '90_days' ? 'selected' : '' }}>{{ __('Last 90 Days') }}</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('All Time') }}</option>
                </select>

                {{-- Primary Filter: Support Channel --}}
                <select name="channel" class="w-full md:w-40 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all" {{ $channel === 'all' ? 'selected' : '' }}>{{ __('All Channels') }}</option>
                    <option value="tickets" {{ $channel === 'tickets' ? 'selected' : '' }}>{{ __('Support Tickets') }}</option>
                    <option value="field_jobs" {{ $channel === 'field_jobs' ? 'selected' : '' }}>{{ __('Field Jobs') }}</option>
                    <option value="escalations" {{ $channel === 'escalations' ? 'selected' : '' }}>{{ __('Reseller Escalations') }}</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="w-full md:w-28 flex-shrink-0 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 10]) }}</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                    <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 30]) }}</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                </select>

                {{-- "More Filters" Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount > 0 ? 'true' : 'false' }} ? 'bg-cyan-50 border-cyan-300 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                    <i class="fas fa-sliders text-xs"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Buttons Sequence --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.tickets.sla-analytics') }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Staff / Engineer Filter --}}
                <div class="w-full sm:w-60">
                    <select name="engineer_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Staff & Engineers') }}</option>
                        @foreach($engineers as $eng)
                            <option value="{{ $eng->id }}" {{ (string)$engineerId === (string)$eng->id ? 'selected' : '' }}>
                                {{ $eng->name }} ({{ $eng->role ? __(ucwords(str_replace('_', ' ', $eng->role))) : __('Staff') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Performance Grade Filter --}}
                <div class="w-full sm:w-44">
                    <select name="grade" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $grade === 'all' ? 'selected' : '' }}>{{ __('All Performance Grades') }}</option>
                        <option value="A+" {{ $grade === 'A+' ? 'selected' : '' }}>{{ __('Grade A+ (On-Time >= 95%)') }}</option>
                        <option value="A" {{ $grade === 'A' ? 'selected' : '' }}>{{ __('Grade A (On-Time 90 - 94%)') }}</option>
                        <option value="B+" {{ $grade === 'B+' ? 'selected' : '' }}>{{ __('Grade B+ (On-Time < 90%)') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. CATEGORY SLA ATTAINMENT MATRIX (Compact Overview Strip) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Problem Categories & Resolution Benchmarks') }}</h3>
            </div>
            <span class="text-[10px] text-slate-400 font-medium font-mono">{{ __('Target SLA vs Actual Average Performance') }}</span>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($categoryBreakdown as $cat)
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <div class="min-w-0 pr-2">
                        <span class="text-xs font-semibold text-slate-800 block truncate">{{ $cat['name'] }}</span>
                        <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-500 font-mono">
                            <span>{{ __('Target: :target', ['target' => $cat['target']]) }}</span>
                            <span>•</span>
                            <span class="text-indigo-700 font-semibold">{{ __('Actual: :actual', ['actual' => $cat['actual']]) }}</span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs font-mono font-bold text-emerald-600 block">{{ $cat['sla_pct'] }}%</span>
                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $cat['status'] === 'Good' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                            {{ $cat['status_label'] ?? __($cat['status']) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 5. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Anti-min-h void rule: Natural container height without forced min-h --}}
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('Staff Name') }}</th>
                        <th class="text-center font-semibold">{{ __('Role / Position') }}</th>
                        <th class="text-center font-semibold">{{ __('Assigned') }}</th>
                        <th class="text-center font-semibold">{{ __('Resolved') }}</th>
                        <th class="text-center font-semibold">{{ __('Avg Solve Time') }}</th>
                        <th class="text-center font-semibold">{{ __('On-Time Rate') }}</th>
                        <th class="text-center font-semibold">{{ __('Rating') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedScorecards as $idx => $sc)
                        <tr>
                            {{-- SL Index (Center) --}}
                            <td class="text-center text-slate-400 font-mono whitespace-nowrap">{{ $paginatedScorecards->firstItem() + $idx }}</td>
                            
                            {{-- Staff Name (Left-aligned, Single-line) --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                <a href="javascript:void(0)" @click="showScorecardDetails(@js($sc))" class="text-slate-800 hover:text-cyan-700 hover:underline inline-flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                                        {{ strtoupper(substr($sc->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $sc->name }}</span>
                                </a>
                            </td>

                            {{-- Role / Position (Left-aligned, Single-line) --}}
                            <td class="text-left text-slate-600 text-xs whitespace-nowrap">
                                {{ $sc->role }}
                            </td>

                            {{-- Assigned Tasks (Center / Number) --}}
                            <td class="text-center font-mono font-bold text-slate-700 whitespace-nowrap">
                                {{ $sc->assigned_count }}
                            </td>

                            {{-- Resolved Tasks (Center / Number) --}}
                            <td class="text-center font-mono font-bold text-indigo-700 whitespace-nowrap">
                                {{ $sc->resolved_count }}
                            </td>

                            {{-- Avg Resolution Time (Center / Number) --}}
                            <td class="text-center font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ __(':hours h', ['hours' => $sc->avg_resolution_hours]) }}
                            </td>

                            {{-- SLA Compliance % (Center / Badge) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sc->compliance_rate >= 95 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($sc->compliance_rate >= 90 ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                                    <span>{{ $sc->compliance_rate }}%</span>
                                </span>
                            </td>

                            {{-- Rating Grade Badge (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold font-mono border {{ $sc->grade === 'A+' ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                    ★ {{ $sc->grade }}
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu(@js($sc), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-user-gear"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-600">{{ __('No staff performance records found.') }}</span>
                                    <a href="{{ route('tenant.tickets.sla-analytics') }}" class="mt-1 inline-flex items-center gap-1 text-xs text-cyan-600 font-semibold hover:underline">
                                        <i class="fas fa-rotate-left text-[10px]"></i>
                                        <span>{{ __('Reset All Filters') }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($paginatedScorecards->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500">
                    {{ __('Showing :from to :to of :total staff members', ['from' => $paginatedScorecards->firstItem(), 'to' => $paginatedScorecards->lastItem(), 'total' => $paginatedScorecards->total()]) }}
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
                <span>{{ __('View Performance Details') }}</span>
            </button>
            <a :href="`{{ route('tenant.tickets.sla-analytics.print') }}?engineer_id=${activeMenu?.id}&period={{ $period }}`" 
               target="_blank"
               @click="activeMenu = null"
               class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-print text-slate-400 w-4"></i>
                <span>{{ __('Print Staff Report') }}</span>
            </a>
        </div>

        {{-- Group 2: Quick Actions --}}
        <div class="py-1">
            <button type="button" 
                    @click="copyStaffName(activeMenu); activeMenu = null"
                    class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-copy text-slate-400 w-4"></i>
                <span>{{ __('Copy Staff Name') }}</span>
            </button>
        </div>
    </div>

    {{-- 7. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}
    <div x-show="detailsModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="detailsModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="detailsModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
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
                        <span class="text-[9px] text-slate-500 font-bold uppercase block">{{ __('Tasks Assigned') }}</span>
                        <span class="text-base font-bold font-mono text-slate-800" x-text="selectedScorecard?.assigned_count"></span>
                    </div>
                    <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200 text-center">
                        <span class="text-[9px] text-emerald-700 font-bold uppercase block">{{ __('Resolved On-Time') }}</span>
                        <span class="text-base font-bold font-mono text-emerald-800" x-text="selectedScorecard?.resolved_count"></span>
                    </div>
                    <div class="p-2.5 bg-indigo-50 rounded-lg border border-indigo-200 text-center">
                        <span class="text-[9px] text-indigo-700 font-bold uppercase block">{{ __('On-Time Rate') }}</span>
                        <span class="text-base font-bold font-mono text-indigo-800" x-text="selectedScorecard?.compliance_rate + '%'"></span>
                    </div>
                </div>

                {{-- Detailed Metrics Grid --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Avg Solve Time') }}</span>
                        <span class="font-bold text-slate-800 font-mono text-xs" x-text="(selectedScorecard?.avg_resolution_hours ?? '') + ' ' + @js(__('Hours'))"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Overdue Tasks') }}</span>
                        <span class="font-mono font-bold text-amber-600 text-xs" x-text="(selectedScorecard?.breached_count ?? '') + ' ' + @js(__('Tasks'))"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Customer Rating') }}</span>
                        <span class="font-bold text-purple-700 font-mono text-xs" x-text="(selectedScorecard?.csat_score ?? '') + ' / 5.0 (' + @js(__('Excellent')) + ')'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Performance Grade') }}</span>
                        <span class="font-bold text-emerald-700 text-xs" x-text="(selectedScorecard?.grade ?? '') + ' (' + @js(__('Top Performer')) + ')'"></span>
                    </div>
                </div>

                {{-- Operational Assessment --}}
                <div class="p-3 bg-slate-900 rounded-lg text-cyan-300 font-mono text-[11px] leading-relaxed border border-slate-800">
                    <div class="text-slate-400 font-bold uppercase text-[10px] mb-1 flex items-center gap-1.5">
                        <i class="fas fa-check-circle text-cyan-400"></i>
                        <span>{{ __('Performance Summary Note:') }}</span>
                    </div>
                    <div>
                        {{ __('Staff consistently meets resolution targets with fast turnaround on fiber splicing, network diagnostics, and customer setup.') }}
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModalOpen = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 rounded-lg cursor-pointer transition">
                    {{ __('Close') }}
                </button>
                <a :href="`{{ route('tenant.tickets.sla-analytics.print') }}?engineer_id=${selectedScorecard?.id}&period={{ $period }}`" target="_blank" class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 rounded-lg shadow-xs inline-flex items-center gap-1.5 transition">
                    <i class="fas fa-print text-xs"></i>
                    <span>{{ __('Print Staff Report') }}</span>
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
                        title: @js(__('Copied: :name', ['name' => '__NAME__'])).replace('__NAME__', sc.name),
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            }
        };
    }
</script>
@endpush
