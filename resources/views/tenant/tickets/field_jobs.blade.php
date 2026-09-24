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

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.tickets.field-jobs.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>

            {{-- Dispatch New Job Modal Trigger --}}
            <button type="button" 
                    @click="dispatchModalOpen = true" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>Dispatch Field Job</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
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

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.tickets.field-jobs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
            {{-- Filter 1: Universal Search --}}
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Job / Client / Address</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by job #, customer, address, tech..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>
            </div>

            {{-- Filter 2: Job Type --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Job Classification</label>
                <select name="job_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Job Types</option>
                    <option value="fiber_splicing" {{ $jobType === 'fiber_splicing' ? 'selected' : '' }}>Fiber Splicing</option>
                    <option value="onu_replacement" {{ $jobType === 'onu_replacement' ? 'selected' : '' }}>ONU Replacement</option>
                    <option value="cable_repair" {{ $jobType === 'cable_repair' ? 'selected' : '' }}>Cable Repair / Pulling</option>
                    <option value="home_visit" {{ $jobType === 'home_visit' ? 'selected' : '' }}>Home Visit &amp; WiFi</option>
                    <option value="pop_maintenance" {{ $jobType === 'pop_maintenance' ? 'selected' : '' }}>POP Maintenance</option>
                    <option value="new_connection" {{ $jobType === 'new_connection' ? 'selected' : '' }}>New Line Installation</option>
                </select>
            </div>

            {{-- Filter 3: Assigned Technician --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Field Technician</label>
                <select name="technician_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Technicians</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ (string)$technicianId === (string)$tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter 4: Status --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Job Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Dispatch</option>
                    <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.tickets.field-jobs') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">SL</th>
                        <th>Job #</th>
                        <th>Job Type</th>
                        <th>Subscriber / Site Location</th>
                        <th>Assigned Technician</th>
                        <th>Scheduled Date</th>
                        <th class="text-center">Priority</th>
                        <th class="text-center">Job Status</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $idx => $j)
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $jobs->firstItem() + $idx }}</td>
                            
                            {{-- Job Number (Single data) --}}
                            <td class="font-mono font-bold text-cyan-800">
                                <a href="javascript:void(0)" @click="showJobModal(@js($j))" class="hover:underline">
                                    {{ $j->job_number }}
                                </a>
                            </td>

                            {{-- Job Type (Single data) --}}
                            <td class="font-semibold text-slate-800">
                                {{ $j->job_type_name }}
                            </td>

                            {{-- Customer / Site (Single data) --}}
                            <td class="text-slate-700 font-medium">
                                {{ $j->customer_name ?: 'Central POP Site' }}
                            </td>

                            {{-- Assigned Technician (Single data) --}}
                            <td class="text-slate-700">
                                @if($j->assigned_technician_name)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700">
                                        <i class="fas fa-user-gear text-indigo-500 text-[10px]"></i>
                                        <span>{{ $j->assigned_technician_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs font-mono">Unassigned</span>
                                @endif
                            </td>

                            {{-- Scheduled Date --}}
                            <td class="text-slate-500 font-mono text-[11px]">
                                {{ $j->scheduled_at ? $j->scheduled_at->format('d M Y, h:i A') : 'Immediate' }}
                            </td>

                            {{-- Priority Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $j->priority_badge_color }}">
                                    @if($j->priority === 'urgent')
                                        <i class="fas fa-fire text-rose-600 text-[9px]"></i>
                                    @endif
                                    <span>{{ ucfirst($j->priority) }}</span>
                                </span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $j->status_badge_color }}">
                                    <span>{{ strtoupper(str_replace('_', ' ', $j->status)) }}</span>
                                </span>
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center">
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
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-person-digging"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500">No field jobs found matching your filters.</span>
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
            <button type="button" @click="showJobModal(activeMenu); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-blue-600 w-4 text-center text-xs"></i>
                <span>View Job Details</span>
            </button>
            <a :href="`/admin/support/field-jobs/${activeMenu?.id}/print`" target="_blank" class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition">
                <i class="fas fa-print text-cyan-600 w-4 text-center text-xs"></i>
                <span>Print Job Sheet (A4)</span>
            </a>
            <button type="button" @click="copyJobNumber(activeMenu?.job_number); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>Copy Job #</span>
            </button>
        </div>

        {{-- Group 2: Status Dispatch Actions --}}
        <div class="py-1">
            <template x-if="activeMenu?.status !== 'in_progress' && activeMenu?.status !== 'completed'">
                <button type="button" 
                        @click="updateJobStatusAction(activeMenu?.id, 'in_progress'); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition cursor-pointer">
                    <i class="fas fa-person-running text-indigo-600 w-4 text-center text-xs"></i>
                    <span>Mark as In Progress</span>
                </button>
            </template>
            <template x-if="activeMenu?.status !== 'completed'">
                <button type="button" @click="openCompleteModal(activeMenu); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-medium transition cursor-pointer">
                    <i class="fas fa-circle-check text-emerald-600 w-4 text-center text-xs"></i>
                    <span>Mark as Completed</span>
                </button>
            </template>
        </div>
    </div>

    {{-- MODAL 1: DISPATCH NEW FIELD JOB MODAL --}}
    <div x-show="dispatchModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="dispatchModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="dispatchModalOpen = false">
            <form method="POST" action="{{ route('tenant.tickets.field-jobs.store') }}">
                @csrf
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-person-digging"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-slate-800">Dispatch Field Technician</h4>
                            <span class="text-[10.5px] text-slate-500 font-normal">Assign onsite hardware or optical repair job</span>
                        </div>
                    </div>
                    <button type="button" @click="dispatchModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Job Classification *</label>
                            <select name="job_type" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                <option value="fiber_splicing">Fiber Splicing &amp; Link</option>
                                <option value="onu_replacement">ONU Replacement</option>
                                <option value="cable_repair">Drop Cable Repair</option>
                                <option value="home_visit">Home Visit &amp; WiFi Setup</option>
                                <option value="pop_maintenance">POP Node Maintenance</option>
                                <option value="new_connection">New Physical Line Installation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Priority Level *</label>
                            <select name="priority" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                <option value="medium">Medium</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">🔥 Urgent Escalation</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Customer / Site Name *</label>
                            <input type="text" name="customer_name" required placeholder="e.g. Anisur Rahman" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Contact Phone</label>
                            <input type="text" name="customer_phone" placeholder="e.g. 01711223344" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Premise Address / Splicing Pole Location *</label>
                        <input type="text" name="address" required placeholder="House 42, Road 11, Block D, Banani, Dhaka" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Assign Technician</label>
                            <select name="assigned_to" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                <option value="">Select Technician</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Scheduled Date &amp; Time</label>
                            <input type="datetime-local" name="scheduled_at" value="{{ date('Y-m-d\TH:i', strtotime('+1 hour')) }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Issue Description &amp; Technical Notes *</label>
                        <textarea name="issue_description" required rows="2" placeholder="Explain the fault symptoms, optical loss, or replacement reason..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="dispatchModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs cursor-pointer">
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
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="jobModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedJob?.job_number"></h4>
                        <span class="text-[10.5px] text-slate-500 font-normal" x-text="selectedJob?.job_type_name"></span>
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
                    <p class="text-indigo-700 font-mono text-[11px]" x-text="'Phone: ' + (selectedJob?.customer_phone || 'N/A')"></p>
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
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="jobModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
                <a :href="`/admin/support/field-jobs/${selectedJob?.id}/print`" target="_blank" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs inline-flex items-center gap-1.5">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Job Sheet</span>
                </a>
            </div>
        </div>
    </div>

    {{-- MODAL 3: MARK COMPLETED MODAL --}}
    <div x-show="completeModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="completeModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
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
                        <div>
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
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Final Optical RX Power (dBm)</label>
                        <input type="text" name="optical_rx_after" placeholder="e.g. -19.2 dBm" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Resolution Summary / Work Done</label>
                        <textarea name="resolution_notes" rows="2" placeholder="Splice completed with 0.02 dB loss. Link restored." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="completeModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs cursor-pointer">
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
                const dropdownHeight = 180;
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
            }
        };
    }
</script>
@endpush
