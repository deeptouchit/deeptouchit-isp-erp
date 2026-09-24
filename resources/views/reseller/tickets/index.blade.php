@extends('reseller.layouts.app')

@section('title', 'Support Tickets - ' . ($reseller->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ticketManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Support Tickets') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reseller.tickets.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.tickets.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openCreateModal = true" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>{{ __('Create Ticket') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('All Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        {{-- Card 2: Open Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Open') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                    {{ number_format($stats['open']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        {{-- Card 3: In Progress --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('In Progress') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block">
                    {{ number_format($stats['in_progress']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-spinner"></i>
            </div>
        </div>

        {{-- Card 4: Answered --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Answered') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ number_format($stats['answered']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-reply-all"></i>
            </div>
        </div>

        {{-- Card 5: Urgent Issues --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Urgent') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block">
                    {{ number_format($stats['urgent']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: Closed Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Closed') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-500 leading-tight block">
                    {{ number_format($stats['closed']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-500 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reseller.tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 items-center">
            
            <!-- Search Box -->
            <div class="relative lg:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search ticket # or subject..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
            </div>

            <!-- Department Filter -->
            <div>
                <select name="department" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="all" {{ $department === 'all' ? 'selected' : '' }}>All Departments</option>
                    <option value="technical" {{ $department === 'technical' ? 'selected' : '' }}>Technical / NOC</option>
                    <option value="billing" {{ $department === 'billing' ? 'selected' : '' }}>Billing &amp; Wallet</option>
                    <option value="network" {{ $department === 'network' ? 'selected' : '' }}>Network &amp; Router</option>
                    <option value="package" {{ $department === 'package' ? 'selected' : '' }}>Package &amp; Speed</option>
                    <option value="general" {{ $department === 'general' ? 'selected' : '' }}>General Support</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <select name="priority" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>All Priorities</option>
                    <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-purple-500 shadow-2xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="answered" {{ $status === 'answered' ? 'selected' : '' }}>Answered</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C) -->
            <div class="flex items-center gap-1.5">
                <button type="submit" class="w-1/2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.tickets.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-28">Ticket ID</th>
                        <th>Subject</th>
                        <th class="w-32">Department</th>
                        <th class="w-24 text-center">Priority</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-16 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $index => $ticket)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($tickets->currentPage() - 1) * $tickets->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Ticket ID -->
                            <td class="font-mono font-bold text-purple-700">
                                <a href="{{ route('reseller.tickets.show', $ticket->id) }}" class="hover:underline">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>

                            <!-- 3. Subject (Single data) -->
                            <td class="font-semibold text-slate-800">
                                <a href="{{ route('reseller.tickets.show', $ticket->id) }}" class="hover:text-purple-700 transition">
                                    {{ $ticket->subject }}
                                </a>
                            </td>

                            <!-- 4. Department -->
                            <td class="capitalize text-slate-600">
                                {{ $ticket->department ?? 'General' }}
                            </td>

                            <!-- 5. Priority -->
                            <td class="text-center">
                                @if($ticket->priority === 'urgent')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200">URGENT</span>
                                @elseif($ticket->priority === 'high')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200">HIGH</span>
                                @elseif($ticket->priority === 'medium')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-blue-50 text-blue-700 border border-blue-200">MEDIUM</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-50 text-slate-600 border border-slate-200">LOW</span>
                                @endif
                            </td>

                            <!-- 6. Status -->
                            <td class="text-center">
                                @if($ticket->status === 'open')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">OPEN</span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">IN PROGRESS</span>
                                @elseif($ticket->status === 'answered')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">ANSWERED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-50 text-slate-600 border border-slate-200">CLOSED</span>
                                @endif
                            </td>

                            <!-- 7. Action: Global Floating 3-Dot Menu (AGENTS.md Rule 2.E) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($ticket) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="fas fa-headset text-3xl mb-2 block text-slate-300"></i>
                                <span>No support tickets found matching your query.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $tickets->firstItem() }}</span> to <span class="font-bold">{{ $tickets->lastItem() }}</span> of <span class="font-bold">{{ $tickets->total() }}</span> tickets
                </div>
                <div>
                    {{ $tickets->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         @click.outside="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-44 text-xs space-y-0.5"
         :style="menuPos"
         style="display: none;"
         x-cloak>
        <a :href="`/reseller/tickets/${activeMenu?.id}`" 
           class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-purple-600 transition">
            <i class="fas fa-comments w-4 text-purple-600"></i>
            <span>View Thread</span>
        </a>
        <button type="button" 
                @click="closeTicketAction(activeMenu?.id)" 
                x-show="activeMenu?.status !== 'closed'"
                class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50 transition cursor-pointer text-left">
            <i class="fas fa-check-circle w-4"></i>
            <span>Close Ticket</span>
        </button>
    </div>

    {{-- 6. CREATE NEW TICKET MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openCreateModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.outside="openCreateModal = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Open Support Ticket</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Direct escalation to host ISP network &amp; NOC team</p>
                    </div>
                </div>
                <button type="button" @click="openCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.tickets.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-4 space-y-3">
                    <!-- Subject -->
                    <div>
                        <label for="subject" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Subject <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="subject" 
                               name="subject" 
                               required 
                               placeholder="e.g. Bandwidth latency issue at Agrabad POP" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                    </div>

                    <!-- Department & Priority -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="modal_department" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Department <span class="text-rose-500">*</span>
                            </label>
                            <select id="modal_department" name="department" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="technical">Technical / NOC</option>
                                <option value="billing">Billing &amp; Wallet</option>
                                <option value="network">Network &amp; Router</option>
                                <option value="package">Package &amp; Speed</option>
                                <option value="general">General Support</option>
                            </select>
                        </div>
                        <div>
                            <label for="modal_priority" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Priority <span class="text-rose-500">*</span>
                            </label>
                            <select id="modal_priority" name="priority" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>

                    <!-- Detailed Message -->
                    <div>
                        <label for="modal_message" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Issue Description <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="modal_message" 
                                  name="message" 
                                  rows="4" 
                                  required 
                                  placeholder="Describe the technical or billing problem in detail..." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs"></textarea>
                    </div>

                    <!-- File Attachment -->
                    <div>
                        <label for="modal_attachment" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Attachment (Screenshot / Log)
                        </label>
                        <input type="file" 
                               id="modal_attachment" 
                               name="attachment" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-purple-100 file:text-purple-700">
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ticketManager() {
    return {
        openCreateModal: false,
        activeMenu: null,
        menuPos: {},

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 120;
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
                if (!confirm('Are you sure you want to close this ticket?')) return;
            }

            try {
                const response = await fetch(`/reseller/tickets/${id}/close`, {
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
                console.error('Network error closing ticket:', e);
                location.reload();
            }
        }
    };
}
</script>
@endpush
