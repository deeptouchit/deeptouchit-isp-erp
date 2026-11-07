@extends('tenant.layouts.app')

@section('title', 'Assigned Field Jobs & Dispatch Roster - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="fieldJobPageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-person-digging"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Assigned Field Jobs &amp; Dispatch Roster</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.tickets.field-jobs.export', request()->all()) }}" 
               class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>

            {{-- Dispatch New Job Modal Trigger --}}
            <button type="button" 
                    @click="dispatchModalOpen = true" 
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>Dispatch Field Job</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Field Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Tasks</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>

        {{-- Card 2: Pending Dispatch --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Pending Dispatch</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    {{ number_format($stats['pending']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 3: Dispatched En-Route --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Dispatched</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ number_format($stats['dispatched']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-truck-fast"></i>
            </div>
        </div>

        {{-- Card 4: In Progress / Splicing --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">In Progress</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ number_format($stats['in_progress']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
        </div>

        {{-- Card 5: Urgent Escalations --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Urgent / High</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['urgent'] > 0 ? 'text-rose-600' : 'text-slate-700' }} leading-tight block">
                    {{ number_format($stats['urgent']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['urgent'] > 0 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-fire"></i>
            </div>
        </div>

        {{-- Card 6: Resolved & Completed --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Completed</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ number_format($stats['completed']) }}
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
        <form method="GET" action="{{ route('tenant.tickets.field-jobs') }}" class="space-y-2.5">
            {{-- Tier 1: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Input --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="Search by Job #, customer, phone, address, technician..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                </div>

                {{-- Status Filter --}}
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">All Statuses</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Job Type Filter --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="job_type" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">All Job Types</option>
                        <option value="fiber_splicing" {{ $jobType === 'fiber_splicing' ? 'selected' : '' }}>Fiber Splicing</option>
                        <option value="onu_replacement" {{ $jobType === 'onu_replacement' ? 'selected' : '' }}>ONU Replacement</option>
                        <option value="cable_repair" {{ $jobType === 'cable_repair' ? 'selected' : '' }}>Cable Repair</option>
                        <option value="home_visit" {{ $jobType === 'home_visit' ? 'selected' : '' }}>Home Visit</option>
                        <option value="pop_maintenance" {{ $jobType === 'pop_maintenance' ? 'selected' : '' }}>POP Maintenance</option>
                        <option value="new_connection" {{ $jobType === 'new_connection' ? 'selected' : '' }}>New Line Installation</option>
                    </select>
                </div>

                {{-- Per Page Selector --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ $perPage == 250 ? 'selected' : '' }}>250 / page</option>
                        <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500 / page</option>
                    </select>
                </div>

                {{-- More Filters Accordion Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200/90 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center justify-between gap-1.5 transition flex-shrink-0 cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <i class="fas fa-sliders-h text-[11px] text-slate-500"></i>
                        <span>More Filters</span>
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
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.tickets.field-jobs') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Technician Filter --}}
                <div class="w-full sm:w-48">
                    <select name="technician_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">All Technicians</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ (string)$technicianId === (string)$tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Priority Filter --}}
                <div class="w-full sm:w-40">
                    <select name="priority" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>All Priorities</option>
                        <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>🔥 Urgent</option>
                        <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>

                {{-- Coverage Zone Filter --}}
                <div class="w-full sm:w-44">
                    <select name="zone_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">All Coverage Zones</option>
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                        @endforeach
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
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="w-28 text-center font-semibold">Job ID</th>
                        <th class="text-center font-semibold">Job Classification</th>
                        <th class="text-center font-semibold">Subscriber / Site Location</th>
                        <th class="text-center font-semibold">Field Technician</th>
                        <th class="w-28 text-center font-semibold">Status</th>
                        <th class="w-14 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $idx => $j)
                        <tr>
                            {{-- # --}}
                            <td class="text-center text-slate-400 font-mono">{{ $jobs->firstItem() + $idx }}</td>
                            
                            {{-- Job ID --}}
                            <td class="text-center font-mono font-bold text-cyan-800 whitespace-nowrap">
                                <button type="button" @click="showJobModal(@js($j))" class="hover:underline cursor-pointer">
                                    {{ $j->job_number }}
                                </button>
                            </td>

                            {{-- Job Classification --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <span>{{ $j->job_type_name }}</span>
                                    @if($j->priority === 'urgent')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Urgent Priority">
                                            <i class="fas fa-fire text-rose-600 text-[8px]"></i>
                                            <span>Urgent</span>
                                        </span>
                                    @elseif($j->priority === 'high')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="High Priority">
                                            <span>High</span>
                                        </span>
                                    @endif
                                </span>
                            </td>

                            {{-- Subscriber / Site Location --}}
                            <td class="text-left text-slate-700 font-medium whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-800">{{ $j->customer_name ?: 'Central POP Site' }}</span>
                                    @if($j->address)
                                        <span class="text-slate-400 text-[11px]">({{ \Illuminate\Support\Str::limit($j->address, 32) }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Assigned Technician --}}
                            <td class="text-left text-slate-700 whitespace-nowrap">
                                @if($j->assigned_technician_name)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700">
                                        <i class="fas fa-user-gear text-indigo-500 text-[10px]"></i>
                                        <span>{{ $j->assigned_technician_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs font-mono">Unassigned</span>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $j->status_badge_color }}">
                                    <span>{{ strtoupper(str_replace('_', ' ', $j->status)) }}</span>
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu(@js($j), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-person-digging"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-semibold text-slate-600 block">No field jobs found</span>
                                        <span class="text-[11px] text-slate-400 block">Dispatch a new field job to assign technicians or resolve subscriber issues.</span>
                                    </div>
                                    <button type="button" 
                                            @click="dispatchModalOpen = true" 
                                            class="h-[30px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer mt-1">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        <span>Dispatch Field Job</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
        <div class="px-3.5 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
                Showing {{ $jobs->firstItem() }} to {{ $jobs->lastItem() }} of {{ $jobs->total() }} Field Tasks
            </span>
            <div>
                {{ $jobs->links() }}
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
        
        {{-- Group 1: Telemetry & Print --}}
        <div class="py-1">
            <button type="button" 
                    @click="showJobModal(activeMenu); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-blue-600 w-4 text-center text-xs"></i>
                <span>View Job Details</span>
            </button>
            <a :href="`/admin/support/field-jobs/${activeMenu?.id}/print`" 
               target="_blank" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition cursor-pointer">
                <i class="fas fa-print text-cyan-600 w-4 text-center text-xs"></i>
                <span>Print Job Sheet (A4)</span>
            </a>
            <button type="button" 
                    @click="copyJobNumber(activeMenu?.job_number); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>Copy Job #</span>
            </button>
        </div>

        {{-- Group 2: Status Dispatch Actions --}}
        <div class="py-1">
            <template x-if="activeMenu?.status === 'pending'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'dispatched'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                    <i class="fas fa-truck-fast text-blue-600 w-4 text-center text-xs"></i>
                    <span>Mark as Dispatched</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'in_progress' && activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'in_progress'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer">
                    <i class="fas fa-screwdriver-wrench text-indigo-600 w-4 text-center text-xs"></i>
                    <span>Mark as In Progress</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="openCompleteModal(activeMenu); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-medium transition cursor-pointer">
                    <i class="fas fa-circle-check text-emerald-600 w-4 text-center text-xs"></i>
                    <span>Mark as Completed</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'cancelled'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-rose-600 font-medium transition cursor-pointer">
                    <i class="fas fa-ban text-rose-500 w-4 text-center text-xs"></i>
                    <span>Cancel Job</span>
                </button>
            </template>
        </div>

        {{-- Group 3: Delete Action (Super Admin / ISP Admin only) --}}
        @if(auth()->user() && auth()->user()->canDeleteTickets())
        <div class="py-1">
            <button type="button" 
                    @click="deleteJobAction(activeMenu); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 font-medium transition cursor-pointer">
                <i class="fas fa-trash-alt text-rose-500 w-4 text-center text-xs"></i>
                <span>Delete Job</span>
            </button>
        </div>
        @endif
    </div>

    {{-- MODAL 1: DISPATCH NEW FIELD JOB MODAL (Production-Grade Natural Modal) --}}
    <div x-show="dispatchModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="dispatchModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="dispatchModalOpen = false">
            <form method="POST" action="{{ route('tenant.tickets.field-jobs.store') }}">
                @csrf
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-person-digging"></i>
                        </div>
                        <h4 class="text-xs font-semibold text-slate-800">Dispatch Field Technician</h4>
                    </div>
                    <button type="button" @click="dispatchModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs" x-data="{ selectedCustomerId: '', onCustomerChange() {
                    if (this.selectedCustomerId) {
                        const customers = @js($customers);
                        const c = customers.find(x => x.id == this.selectedCustomerId);
                        if (c) {
                            $refs.custName.value = c.name;
                            $refs.custPhone.value = c.phone || '';
                            $refs.custAddr.value = c.address || '';
                            if (c.zone_id && $refs.zoneSelect) {
                                $refs.zoneSelect.value = c.zone_id;
                            }
                        }
                    }
                } }">
                    {{-- Quick Select Customer (Optional) --}}
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Link Registered Subscriber (Optional)</label>
                        <select name="customer_id" 
                                x-model="selectedCustomerId" 
                                @change="onCustomerChange()"
                                class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">-- Choose Existing Subscriber or type manual below --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Job Classification *</label>
                            <select name="job_type" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="fiber_splicing">Fiber Splicing &amp; Link</option>
                                <option value="onu_replacement">ONU Replacement</option>
                                <option value="cable_repair">Drop Cable Repair</option>
                                <option value="home_visit">Home Visit &amp; WiFi Setup</option>
                                <option value="pop_maintenance">POP Node Maintenance</option>
                                <option value="new_connection">New Physical Line Installation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Priority Level *</label>
                            <select name="priority" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="medium">Medium</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">🔥 Urgent Escalation</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Subscriber / Site Name *</label>
                            <input type="text" 
                                   name="customer_name" 
                                   x-ref="custName" 
                                   required 
                                   placeholder="e.g. Anisur Rahman" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Contact Phone</label>
                            <input type="text" 
                                   name="customer_phone" 
                                   x-ref="custPhone" 
                                   placeholder="e.g. 01711223344" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Premise Address / Splicing Pole Location *</label>
                        <input type="text" 
                               name="address" 
                               x-ref="custAddr" 
                               required 
                               placeholder="House 42, Road 11, Block D, Banani, Dhaka" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Assign Technician</label>
                            <select name="assigned_to" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">Select Technician</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Coverage Zone</label>
                            <select name="zone_id" x-ref="zoneSelect" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">Select Zone</option>
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}">{{ $z->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Scheduled Date &amp; Time</label>
                            <input type="datetime-local" 
                                   name="scheduled_at" 
                                   value="{{ date('Y-m-d\TH:i', strtotime('+1 hour')) }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Initial Optical RX (dBm)</label>
                            <input type="text" 
                                   name="optical_rx_before" 
                                   placeholder="e.g. -27.5 dBm" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Issue Description &amp; Technical Notes *</label>
                        <textarea name="issue_description" 
                                  required 
                                  rows="2" 
                                  placeholder="Explain the fault symptoms, optical loss, or replacement reason..." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="dispatchModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        Dispatch Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: JOB DETAILS & OPTICAL TELEMETRY MODAL --}}
    <div x-show="jobModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="jobModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="jobModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-xs text-blue-700" x-text="selectedJob?.job_number"></span>
                        <span class="text-slate-300">|</span>
                        <span class="text-xs font-semibold text-slate-800" x-text="selectedJob?.job_type_name"></span>
                    </div>
                </div>
                <button type="button" @click="jobModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">Subscriber &amp; Site Address</span>
                    <p class="font-bold text-slate-900" x-text="selectedJob?.customer_name"></p>
                    <p class="text-slate-600 text-[11px]" x-text="selectedJob?.address"></p>
                    <p class="text-indigo-700 font-mono text-[11px] mt-0.5" x-text="'Contact: ' + (selectedJob?.customer_phone || 'N/A')"></p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Assigned Technician</span>
                        <span class="font-bold text-slate-800" x-text="selectedJob?.assigned_technician_name || 'Unassigned'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Job Status</span>
                        <span class="font-bold uppercase text-blue-700" x-text="selectedJob?.status"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Optical RX Before</span>
                        <span class="font-mono font-bold text-rose-600" x-text="selectedJob?.optical_rx_before || 'Not Recorded'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Optical RX After</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="selectedJob?.optical_rx_after || 'Pending'"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">Issue Description</span>
                    <p class="text-slate-700" x-text="selectedJob?.issue_description"></p>
                </div>

                <template x-if="selectedJob?.resolution_notes">
                    <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200/80">
                        <span class="text-[10px] text-emerald-800 font-semibold uppercase block mb-0.5">Resolution Notes</span>
                        <p class="text-emerald-900" x-text="selectedJob?.resolution_notes"></p>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="jobModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                    Close
                </button>
                <a :href="`/admin/support/field-jobs/${selectedJob?.id}/print`" target="_blank" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs inline-flex items-center gap-1.5 cursor-pointer transition">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Job Sheet (A4)</span>
                </a>
            </div>
        </div>
    </div>

    {{-- MODAL 3: MARK COMPLETED MODAL --}}
    <div x-show="completeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="completeModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150" 
             @click.away="completeModalOpen = false">
            <form :action="`/admin/support/field-jobs/${selectedJob?.id}/status`" method="POST">
                @csrf
                <input type="hidden" name="status" value="completed">
                
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-semibold text-slate-800">Complete Field Task</h4>
                            <span class="text-[10.5px] text-slate-500 font-mono" x-text="selectedJob?.job_number"></span>
                        </div>
                    </div>
                    <button type="button" @click="completeModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Final Optical RX Power (dBm)</label>
                        <input type="text" 
                               name="optical_rx_after" 
                               placeholder="e.g. -19.2 dBm" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Resolution Summary / Work Done</label>
                        <textarea name="resolution_notes" 
                                  rows="3" 
                                  placeholder="Splice completed with 0.02 dB loss. Optical link restored." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="completeModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        Confirm Completed
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function fieldJobPageManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            dispatchModalOpen: false,
            jobModalOpen: false,
            completeModalOpen: false,
            selectedJob: null,

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

            showJobModal(job) {
                this.selectedJob = job;
                this.jobModalOpen = true;
            },

            openCompleteModal(job) {
                this.selectedJob = job;
                this.completeModalOpen = true;
            },

            copyJobNumber(jobNo) {
                if (navigator.clipboard && jobNo) {
                    navigator.clipboard.writeText(jobNo);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Job # Copied',
                            text: jobNo + ' copied to clipboard',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            },

            async updateJobStatusAction(id, status) {
                if (!id) return;
                try {
                    const response = await fetch(`/admin/support/field-jobs/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ status: status })
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error updating job status:', e);
                    location.reload();
                }
            },

            deleteJobAction(job) {
                if (!job || !job.id) return;
                if (window.Swal) {
                    Swal.fire({
                        title: 'Delete Field Job?',
                        text: `Are you sure you want to permanently delete job #${job.job_number}? This action cannot be undone.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Delete Job',
                        cancelButtonText: 'Cancel'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`/admin/support/field-jobs/${job.id}`, {
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
                                        title: 'Deleted!',
                                        text: data.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire('Error', data.message || 'Could not delete job.', 'error');
                                }
                            } catch (err) {
                                console.error('Delete error:', err);
                                Swal.fire('Error', 'An unexpected error occurred while deleting the job.', 'error');
                            }
                        }
                    });
                } else if (confirm(`Are you sure you want to delete job #${job.job_number}?`)) {
                    fetch(`/admin/support/field-jobs/${job.id}`, {
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
