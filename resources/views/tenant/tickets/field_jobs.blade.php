@extends('tenant.layouts.app')

@section('title', __('Assigned Field Jobs & Dispatch Roster') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page specific CSS --}}
@endpush

@section('content')
@php
    $jobTypeLabels = [
        'fiber_splicing' => __('Fiber Splicing & Optical Link'),
        'onu_replacement' => __('ONU Replacement / Upgrade'),
        'home_visit' => __('Home Visit & Diagnostics'),
        'cable_repair' => __('Drop Cable Re-pulling / Repair'),
        'pop_maintenance' => __('POP Node & Splitter Maintenance'),
        'new_connection' => __('New Physical Line Installation'),
    ];
    $statusLabels = [
        'pending' => __('Pending'),
        'dispatched' => __('Dispatched'),
        'in_progress' => __('In Progress'),
        'completed' => __('Completed'),
        'cancelled' => __('Cancelled'),
    ];
@endphp
<div class="space-y-3" x-data="fieldJobPageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-person-digging"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Assigned Field Jobs & Dispatch Roster') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.tickets.field-jobs.export', request()->all()) }}" 
               class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>

            {{-- Dispatch New Job Modal Trigger --}}
            <button type="button" 
                    @click="dispatchModalOpen = true" 
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>{{ __('Dispatch Field Job') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Field Tasks --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Tasks') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Pending Dispatch') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Dispatched') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('In Progress') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Urgent / High') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Completed') }}</span>
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
                           placeholder="{{ __('Search by Job #, customer, phone, address, technician...') }}" 
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
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>{{ __('Dispatched') }}</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>

                {{-- Job Type Filter --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="job_type" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Job Types') }}</option>
                        <option value="fiber_splicing" {{ $jobType === 'fiber_splicing' ? 'selected' : '' }}>{{ __('Fiber Splicing') }}</option>
                        <option value="onu_replacement" {{ $jobType === 'onu_replacement' ? 'selected' : '' }}>{{ __('ONU Replacement') }}</option>
                        <option value="cable_repair" {{ $jobType === 'cable_repair' ? 'selected' : '' }}>{{ __('Cable Repair') }}</option>
                        <option value="home_visit" {{ $jobType === 'home_visit' ? 'selected' : '' }}>{{ __('Home Visit') }}</option>
                        <option value="pop_maintenance" {{ $jobType === 'pop_maintenance' ? 'selected' : '' }}>{{ __('POP Maintenance') }}</option>
                        <option value="new_connection" {{ $jobType === 'new_connection' ? 'selected' : '' }}>{{ __('New Line Installation') }}</option>
                    </select>
                </div>

                {{-- Per Page Selector --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        @foreach([15, 20, 30, 50, 100, 250, 500] as $pp)
                            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ __(':count / page', ['count' => $pp]) }}</option>
                        @endforeach
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
                    <a href="{{ route('tenant.tickets.field-jobs') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Technician Filter --}}
                <div class="w-full sm:w-48">
                    <select name="technician_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Technicians') }}</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ (string)$technicianId === (string)$tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Priority Filter --}}
                <div class="w-full sm:w-40">
                    <select name="priority" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>{{ __('All Priorities') }}</option>
                        <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>🔥 {{ __('Urgent') }}</option>
                        <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                        <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                        <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                    </select>
                </div>

                {{-- Coverage Zone Filter --}}
                <div class="w-full sm:w-44">
                    <select name="zone_id" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all">{{ __('All Coverage Zones') }}</option>
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
                        <th class="w-10 text-center font-semibold">{{ __('#') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Job ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Job Classification') }}</th>
                        <th class="text-center font-semibold">{{ __('Subscriber / Site Location') }}</th>
                        <th class="text-center font-semibold">{{ __('Field Technician') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
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
                                    <span>{{ $jobTypeLabels[$j->job_type] ?? __($j->job_type_name) }}</span>
                                    @if($j->priority === 'urgent')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ __('Urgent Priority') }}">
                                            <i class="fas fa-fire text-rose-600 text-[8px]"></i>
                                            <span>{{ __('Urgent') }}</span>
                                        </span>
                                    @elseif($j->priority === 'high')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="{{ __('High Priority') }}">
                                            <span>{{ __('High') }}</span>
                                        </span>
                                    @endif
                                </span>
                            </td>

                            {{-- Subscriber / Site Location --}}
                            <td class="text-left text-slate-700 font-medium whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-800">{{ $j->customer_name ?: __('Central POP Site') }}</span>
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
                                    <span class="text-slate-400 text-xs font-mono">{{ __('Unassigned') }}</span>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $j->status_badge_color }}">
                                    <span>{{ $statusLabels[$j->status] ?? strtoupper(str_replace('_', ' ', $j->status)) }}</span>
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu(@js($j), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="{{ __('Actions') }}">
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
                                        <span class="text-xs font-semibold text-slate-600 block">{{ __('No field jobs found') }}</span>
                                        <span class="text-[11px] text-slate-400 block">{{ __('Dispatch a new field job to assign technicians or resolve subscriber issues.') }}</span>
                                    </div>
                                    <button type="button" 
                                            @click="dispatchModalOpen = true" 
                                            class="h-[30px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 py-0 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer mt-1">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        <span>{{ __('Dispatch Field Job') }}</span>
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
                {{ __('Showing :from to :to of :total Field Tasks', ['from' => $jobs->firstItem(), 'to' => $jobs->lastItem(), 'total' => $jobs->total()]) }}
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
                <span>{{ __('View Job Details') }}</span>
            </button>
            <a :href="`/admin/support/field-jobs/${activeMenu?.id}/print`" 
               target="_blank" 
               class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition cursor-pointer">
                <i class="fas fa-print text-cyan-600 w-4 text-center text-xs"></i>
                <span>{{ __('Print Job Sheet (A4)') }}</span>
            </a>
            <button type="button" 
                    @click="copyJobNumber(activeMenu?.job_number); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>{{ __('Copy Job #') }}</span>
            </button>
        </div>

        {{-- Group 2: Status Dispatch Actions --}}
        <div class="py-1">
            <template x-if="activeMenu?.status === 'pending'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'dispatched'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                    <i class="fas fa-truck-fast text-blue-600 w-4 text-center text-xs"></i>
                    <span>{{ __('Mark as Dispatched') }}</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'in_progress' && activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'in_progress'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer">
                    <i class="fas fa-screwdriver-wrench text-indigo-600 w-4 text-center text-xs"></i>
                    <span>{{ __('Mark as In Progress') }}</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="openCompleteModal(activeMenu); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-medium transition cursor-pointer">
                    <i class="fas fa-circle-check text-emerald-600 w-4 text-center text-xs"></i>
                    <span>{{ __('Mark as Completed') }}</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'completed' && activeMenu?.status !== 'cancelled'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'cancelled'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-rose-600 font-medium transition cursor-pointer">
                    <i class="fas fa-ban text-rose-500 w-4 text-center text-xs"></i>
                    <span>{{ __('Cancel Job') }}</span>
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
                <span>{{ __('Delete Job') }}</span>
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
                        <h4 class="text-xs font-semibold text-slate-800">{{ __('Dispatch Field Technician') }}</h4>
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
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Link Registered Subscriber (Optional)') }}</label>
                        <select name="customer_id" 
                                x-model="selectedCustomerId" 
                                @change="onCustomerChange()"
                                class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">{{ __('-- Choose Existing Subscriber or type manual below --') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Job Classification *') }}</label>
                            <select name="job_type" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="fiber_splicing">{{ __('Fiber Splicing & Link') }}</option>
                                <option value="onu_replacement">{{ __('ONU Replacement') }}</option>
                                <option value="cable_repair">{{ __('Drop Cable Repair') }}</option>
                                <option value="home_visit">{{ __('Home Visit & WiFi Setup') }}</option>
                                <option value="pop_maintenance">{{ __('POP Node Maintenance') }}</option>
                                <option value="new_connection">{{ __('New Physical Line Installation') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Priority Level *') }}</label>
                            <select name="priority" required class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="medium">{{ __('Medium') }}</option>
                                <option value="high">{{ __('High Priority') }}</option>
                                <option value="urgent">🔥 {{ __('Urgent Escalation') }}</option>
                                <option value="low">{{ __('Low') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Subscriber / Site Name *') }}</label>
                            <input type="text" 
                                   name="customer_name" 
                                   x-ref="custName" 
                                   required 
                                   placeholder="{{ __('e.g. Anisur Rahman') }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Contact Phone') }}</label>
                            <input type="text" 
                                   name="customer_phone" 
                                   x-ref="custPhone" 
                                   placeholder="{{ __('e.g. 01711223344') }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Premise Address / Splicing Pole Location *') }}</label>
                        <input type="text" 
                               name="address" 
                               x-ref="custAddr" 
                               required 
                               placeholder="{{ __('House 42, Road 11, Block D, Banani, Dhaka') }}" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Assign Technician') }}</label>
                            <select name="assigned_to" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">{{ __('Select Technician') }}</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Coverage Zone') }}</label>
                            <select name="zone_id" x-ref="zoneSelect" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="">{{ __('Select Zone') }}</option>
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}">{{ $z->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Scheduled Date & Time') }}</label>
                            <input type="datetime-local" 
                                   name="scheduled_at" 
                                   value="{{ date('Y-m-d\TH:i', strtotime('+1 hour')) }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Initial Optical RX (dBm)') }}</label>
                            <input type="text" 
                                   name="optical_rx_before" 
                                   placeholder="{{ __('e.g. -27.5 dBm') }}" 
                                   class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Issue Description & Technical Notes *') }}</label>
                        <textarea name="issue_description" 
                                  required 
                                  rows="2" 
                                  placeholder="{{ __('Explain the fault symptoms, optical loss, or replacement reason...') }}" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="dispatchModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        {{ __('Dispatch Task') }}
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
                        <span class="text-xs font-semibold text-slate-800" x-text="getJobTypeLabel(selectedJob)"></span>
                    </div>
                </div>
                <button type="button" @click="jobModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">{{ __('Subscriber & Site Address') }}</span>
                    <p class="font-bold text-slate-900" x-text="selectedJob?.customer_name"></p>
                    <p class="text-slate-600 text-[11px]" x-text="selectedJob?.address"></p>
                    <p class="text-indigo-700 font-mono text-[11px] mt-0.5" x-text="formatContactPhone(selectedJob?.customer_phone)"></p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Assigned Technician') }}</span>
                        <span class="font-bold text-slate-800" x-text="selectedJob?.assigned_technician_name || @js(__('Unassigned'))"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Job Status') }}</span>
                        <span class="font-bold uppercase text-blue-700" x-text="getStatusLabel(selectedJob?.status)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Optical RX Before') }}</span>
                        <span class="font-mono font-bold text-rose-600" x-text="selectedJob?.optical_rx_before || @js(__('Not Recorded'))"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">{{ __('Optical RX After') }}</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="selectedJob?.optical_rx_after || @js(__('Pending'))"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">{{ __('Issue Description') }}</span>
                    <p class="text-slate-700" x-text="selectedJob?.issue_description"></p>
                </div>

                <template x-if="selectedJob?.resolution_notes">
                    <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200/80">
                        <span class="text-[10px] text-emerald-800 font-semibold uppercase block mb-0.5">{{ __('Resolution Notes') }}</span>
                        <p class="text-emerald-900" x-text="selectedJob?.resolution_notes"></p>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="jobModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                    {{ __('Close') }}
                </button>
                <a :href="`/admin/support/field-jobs/${selectedJob?.id}/print`" target="_blank" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs inline-flex items-center gap-1.5 cursor-pointer transition">
                    <i class="fas fa-print text-xs"></i>
                    <span>{{ __('Print Job Sheet (A4)') }}</span>
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
                            <h4 class="text-xs font-semibold text-slate-800">{{ __('Complete Field Task') }}</h4>
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
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Final Optical RX Power (dBm)') }}</label>
                        <input type="text" 
                               name="optical_rx_after" 
                               placeholder="{{ __('e.g. -19.2 dBm') }}" 
                               class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('Resolution Summary / Work Done') }}</label>
                        <textarea name="resolution_notes" 
                                  rows="3" 
                                  placeholder="{{ __('Splice completed with 0.02 dB loss. Optical link restored.') }}" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="completeModalOpen = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 py-0 rounded-lg cursor-pointer transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-0 rounded-lg shadow-xs cursor-pointer transition">
                        {{ __('Confirm Completed') }}
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
        const i18n = {
            jobTypeLabels: @js($jobTypeLabels),
            statusLabels: @js($statusLabels),
            na: @js(__('N/A')),
            contactTemplate: @js(__('Contact: :phone')),
            copiedTitle: @js(__('Job # Copied')),
            copiedTextTemplate: @js(__(':jobNumber copied to clipboard')),
            deleteTitle: @js(__('Delete Field Job?')),
            deleteTextTemplate: @js(__('Are you sure you want to permanently delete job #:jobNumber? This action cannot be undone.')),
            confirmDeleteText: @js(__('Yes, Delete Job')),
            cancelText: @js(__('Cancel')),
            deletedTitle: @js(__('Deleted!')),
            errorTitle: @js(__('Error')),
            couldNotDeleteText: @js(__('Could not delete job.')),
            unexpectedDeleteError: @js(__('An unexpected error occurred while deleting the job.')),
            confirmFallbackTemplate: @js(__('Are you sure you want to delete job #:jobNumber?'))
        };

        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            dispatchModalOpen: false,
            jobModalOpen: false,
            completeModalOpen: false,
            selectedJob: null,

            getJobTypeLabel(job) {
                if (!job) return '';
                if (job.job_type && i18n.jobTypeLabels[job.job_type]) {
                    return i18n.jobTypeLabels[job.job_type];
                }
                return job.job_type_name || (job.job_type ? job.job_type.replace(/_/g, ' ') : '');
            },

            getStatusLabel(status) {
                if (!status) return '';
                return i18n.statusLabels[status] || status.replace(/_/g, ' ').toUpperCase();
            },

            formatContactPhone(phone) {
                return i18n.contactTemplate.replace(':phone', phone || i18n.na);
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
                            title: i18n.copiedTitle,
                            text: i18n.copiedTextTemplate.replace(':jobNumber', jobNo),
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
                        title: i18n.deleteTitle,
                        text: i18n.deleteTextTemplate.replace(':jobNumber', job.job_number),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: i18n.confirmDeleteText,
                        cancelButtonText: i18n.cancelText
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
                                        title: i18n.deletedTitle,
                                        text: data.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire(i18n.errorTitle, data.message || i18n.couldNotDeleteText, 'error');
                                }
                            } catch (err) {
                                console.error('Delete error:', err);
                                Swal.fire(i18n.errorTitle, i18n.unexpectedDeleteError, 'error');
                            }
                        }
                    });
                } else if (confirm(i18n.confirmFallbackTemplate.replace(':jobNumber', job.job_number))) {
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
