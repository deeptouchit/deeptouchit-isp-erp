@extends('tenant.layouts.app')

@section('title', 'Helpdesk & Support Inquiries - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ticketPageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Helpdesk &amp; Technical Support Tickets</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Refresh Button --}}
            <a href="{{ route('tenant.tickets.index') }}" 
               class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-rotate text-slate-500 text-[11px]"></i>
                <span>Refresh</span>
            </a>

            {{-- Create Ticket Primary Button --}}
            <a href="{{ route('tenant.tickets.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>Open New Ticket</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Support Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Tickets</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        {{-- Card 2: Open / Unresolved --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Open / New</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ number_format($stats['open']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        {{-- Card 3: In Progress / NOC --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">In Progress</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ number_format($stats['in_progress']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-spinner"></i>
            </div>
        </div>

        {{-- Card 4: Answered / Awaiting --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Answered</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ number_format($stats['answered']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-comments"></i>
            </div>
        </div>

        {{-- Card 5: High & Urgent Escalations --}}
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

        {{-- Card 6: Resolved & Closed --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Resolved / Closed</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ number_format($stats['closed']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
            {{-- Filter 1: Universal Search --}}
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Ticket / Subject</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by ticket #, issue subject..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>
            </div>

            {{-- Filter 2: Department --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Department</label>
                <select name="department" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Departments</option>
                    <option value="technical" {{ $department === 'technical' ? 'selected' : '' }}>Technical &amp; NOC</option>
                    <option value="billing" {{ $department === 'billing' ? 'selected' : '' }}>Billing &amp; Accounts</option>
                    <option value="sms_gateway" {{ $department === 'sms_gateway' ? 'selected' : '' }}>SMS Gateway API</option>
                    <option value="payment_gateway" {{ $department === 'payment_gateway' ? 'selected' : '' }}>Payment Gateway</option>
                    <option value="general" {{ $department === 'general' ? 'selected' : '' }}>General Support</option>
                </select>
            </div>

            {{-- Filter 3: Priority --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Priority</label>
                <select name="priority" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Priorities</option>
                    <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>🔥 Urgent Escalation</option>
                    <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High Priority</option>
                    <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>

            {{-- Filter 4: Status --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Active / Open</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="answered" {{ $status === 'answered' ? 'selected' : '' }}>Answered</option>
                    <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.tickets.index') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MASTER TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">SL</th>
                        <th>Ticket #</th>
                        <th>Subject &amp; Issue Summary</th>
                        <th>Department</th>
                        <th class="text-center">Priority</th>
                        <th class="text-center">Status</th>
                        <th>Last Activity</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $idx => $t)
                        @php
                            $isClosed = in_array($t->status, ['resolved', 'closed']);
                        @endphp
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $tickets->firstItem() + $idx }}</td>
                            
                            {{-- Ticket Number (Single data) --}}
                            <td class="font-mono font-bold text-blue-700">
                                <a href="{{ route('tenant.tickets.show', $t->id) }}" class="hover:underline">
                                    {{ $t->ticket_number }}
                                </a>
                            </td>

                            {{-- Subject (Single data) --}}
                            <td class="font-semibold text-slate-800">
                                <a href="{{ route('tenant.tickets.show', $t->id) }}" class="hover:text-blue-600 transition">
                                    {{ $t->subject }}
                                </a>
                            </td>

                            {{-- Department (Single data) --}}
                            <td class="text-slate-600 text-[11px]">
                                {{ $t->department_name }}
                            </td>

                            {{-- Priority Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->priority_badge_color }}">
                                    @if($t->priority === 'urgent')
                                        <i class="fas fa-fire text-rose-600 text-[9px]"></i>
                                    @endif
                                    <span>{{ ucfirst($t->priority) }}</span>
                                </span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->status_badge_color }}">
                                    <span>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span>
                                </span>
                            </td>

                            {{-- Last Activity --}}
                            <td class="text-slate-500 font-mono text-[11px]">
                                {{ ($t->last_reply_at ?? $t->updated_at)->locale('en')->diffForHumans() }}
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu(@js($t), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500">No support tickets found matching your filters.</span>
                                    <a href="{{ route('tenant.tickets.create') }}" class="mt-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition">
                                        Open New Ticket
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div class="px-3.5 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
                Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} of {{ $tickets->total() }} Tickets
            </span>
            <div>
                {{ $tickets->links() }}
            </div>
        </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        {{-- Group 1: Primary Actions --}}
        <div class="py-1">
            <a :href="`/admin/support/${activeMenu?.id}`" class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition">
                <i class="fas fa-comments text-blue-600 w-4 text-center text-xs"></i>
                <span>View &amp; Reply Ticket</span>
            </a>
            <button type="button" @click="showQuickModal(activeMenu); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-4 text-center text-xs"></i>
                <span>Quick Ticket Details</span>
            </button>
            <button type="button" @click="copyTicketNumber(activeMenu?.ticket_number); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>Copy Ticket #</span>
            </button>
        </div>

        {{-- Group 2: Status Management --}}
        <div class="py-1">
            <template x-if="activeMenu?.status !== 'closed' && activeMenu?.status !== 'resolved'">
                <button type="button" 
                        @click="closeTicketAction(activeMenu?.id); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-medium transition cursor-pointer">
                    <i class="fas fa-check-double text-emerald-600 w-4 text-center text-xs"></i>
                    <span>Mark as Closed</span>
                </button>
            </template>
            <template x-if="activeMenu?.status === 'closed' || activeMenu?.status === 'resolved'">
                <button type="button" 
                        @click="reopenTicketAction(activeMenu?.id); activeMenu = null" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                    <i class="fas fa-rotate-left text-blue-600 w-4 text-center text-xs"></i>
                    <span>Reopen Ticket</span>
                </button>
            </template>
        </div>
    </div>

    {{-- 6. QUICK TICKET DETAILS MODAL (Production-Grade Natural Modal) --}}
    <div x-show="quickModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="quickModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="quickModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-ticket"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedTicket?.ticket_number"></h4>
                        <span class="text-[10.5px] text-slate-500 font-normal">Support Inquiry Overview</span>
                    </div>
                </div>
                <button type="button" @click="quickModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div>
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">Subject</span>
                    <p class="font-bold text-slate-900 text-sm" x-text="selectedTicket?.subject"></p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Department</span>
                        <span class="font-medium text-slate-800" x-text="selectedTicket?.department_name || selectedTicket?.department"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Priority Level</span>
                        <span class="font-bold text-slate-800 uppercase" x-text="selectedTicket?.priority"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Current Status</span>
                        <span class="font-bold uppercase text-blue-700" x-text="selectedTicket?.status"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Last Response</span>
                        <span class="font-mono text-slate-700" x-text="selectedTicket?.last_reply_by === 'owner' ? 'Staff Replied' : 'Awaiting Reply'"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="quickModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
                <a :href="`/admin/support/${selectedTicket?.id}`" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs inline-flex items-center gap-1.5">
                    <i class="fas fa-comments text-xs"></i>
                    <span>Open Full Conversation</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function ticketPageManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            quickModalOpen: false,
            selectedTicket: null,

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

            showQuickModal(ticket) {
                this.selectedTicket = ticket;
                this.quickModalOpen = true;
            },

            copyTicketNumber(ticketNo) {
                if (navigator.clipboard && ticketNo) {
                    navigator.clipboard.writeText(ticketNo);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Ticket # Copied',
                            text: ticketNo + ' copied to clipboard',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            },

            async closeTicketAction(id) {
                if (!id) return;

                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Close Support Ticket?',
                        text: 'Are you sure you want to mark this support ticket as closed?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check-double mr-1.5"></i> Yes, Mark as Closed',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-xs cursor-pointer mr-2',
                            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg border border-slate-200 cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                    if (!result.isConfirmed) return;
                } else {
                    if (!confirm('Are you sure you want to mark this ticket as closed?')) return;
                }

                try {
                    const response = await fetch(`/admin/support/${id}/close`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Ticket Closed',
                                text: data.message || 'Support ticket has been closed.',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error closing ticket:', e);
                    location.reload();
                }
            },

            async reopenTicketAction(id) {
                if (!id) return;

                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Reopen Support Ticket?',
                        text: 'Are you sure you want to reopen this support ticket?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-rotate-left mr-1.5"></i> Yes, Reopen',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-xs cursor-pointer mr-2',
                            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg border border-slate-200 cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                    if (!result.isConfirmed) return;
                }

                try {
                    const response = await fetch(`/admin/support/${id}/reopen`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Ticket Reopened',
                                text: data.message || 'Support ticket has been reopened.',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error reopening ticket:', e);
                    location.reload();
                }
            }
        };
    }
</script>
@endpush
