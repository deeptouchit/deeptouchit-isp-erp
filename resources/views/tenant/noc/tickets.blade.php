@extends('tenant.layouts.app')

@section('title', 'Support Tickets - ' . ($tenant->company_name ?? 'ISP Management'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ticketsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.noc.dashboard') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Field Support Tickets') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.noc.dashboard') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-gauge-high text-xs text-slate-500"></i>
                <span>{{ __('NOC Hub') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Open Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['open_tickets']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-exclamation"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('In Progress') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">{{ number_format($stats['in_progress_tickets']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Resolved Today') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($stats['resolved_today']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Critical High') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">{{ number_format($stats['critical_priority']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-fire"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Solved') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">{{ number_format($stats['total_resolved']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clipboard-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('All Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">{{ number_format($stats['total_tickets']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <form method="GET" action="{{ route('tenant.noc.tickets') }}" class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 flex-1 min-w-[240px]">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search Ticket Number, Subject...') }}" class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 shadow-2xs">
            </div>
            <select name="status" class="bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-700 focus:bg-white focus:border-cyan-500">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ $statusFilter === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="resolved" {{ $statusFilter === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
            <select name="per_page" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-700 focus:bg-white focus:border-cyan-500">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-filter text-[10px]"></i>
                <span>{{ __('Filter') }}</span>
            </button>
            <a href="{{ route('tenant.noc.tickets') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                <i class="fas fa-rotate-left text-[10px]"></i>
                <span>{{ __('Reset') }}</span>
            </a>
        </div>
    </form>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>{{ __('Ticket #') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="w-24 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $index => $ticket)
                        <tr>
                            <td class="text-center font-mono text-slate-500">{{ $tickets->firstItem() + $index }}</td>
                            <td class="font-mono font-bold text-slate-800">#{{ $ticket->ticket_number }}</td>
                            <td class="font-semibold text-slate-700">{{ $ticket->customer?->username ?? ($ticket->user?->name ?? 'General') }}</td>
                            <td class="text-slate-800 font-medium">{{ $ticket->subject }}</td>
                            <td>
                                @if($ticket->priority === 'critical' || $ticket->priority === 'high')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200 uppercase">{{ $ticket->priority }}</span>
                                @elseif($ticket->priority === 'medium')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200 uppercase">{{ $ticket->priority }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-100 text-slate-600 border border-slate-200 uppercase">{{ $ticket->priority ?? 'LOW' }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($ticket->status === 'resolved' || $ticket->status === 'closed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">{{ __('Resolved') }}</span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200">{{ __('In Progress') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200">{{ __('Open') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        @click="openStatusModal({
                                            id: {{ $ticket->id }},
                                            number: '{{ $ticket->ticket_number }}',
                                            subject: '{{ addslashes($ticket->subject) }}',
                                            status: '{{ $ticket->status }}'
                                        })"
                                        class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-[11px] font-semibold transition cursor-pointer inline-flex items-center gap-1 shadow-2xs">
                                    <i class="fas fa-check-to-slot text-[10px]"></i>
                                    <span>{{ __('Update') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400">
                                <i class="fas fa-clipboard-check text-2xl mb-1.5 block text-emerald-400"></i>
                                <span>{{ __('No support tickets found in this queue.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50/50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

    {{-- 5. UPDATE TICKET STATUS SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showStatusModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.outside="showStatusModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-ticket"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Update Ticket #' + selectedTicket?.number"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Change resolution status & field diagnosis') }}</p>
                    </div>
                </div>
                <button type="button" @click="showStatusModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form :action="'/admin/noc/tickets/' + selectedTicket?.id + '/status'" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Status') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="status" :value="selectedTicket?.status" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-amber-500 shadow-2xs">
                        <option value="in_progress">{{ __('In Progress') }}</option>
                        <option value="resolved">{{ __('Resolved') }}</option>
                        <option value="closed">{{ __('Closed') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Resolution Notes') }}
                    </label>
                    <textarea name="notes" rows="3" placeholder="e.g. Fiber splice re-done, red light solved." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2.5 focus:bg-white focus:border-amber-500 shadow-2xs"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="showStatusModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Update Status') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ticketsManager() {
    return {
        showStatusModal: false,
        selectedTicket: null,

        openStatusModal(ticket) {
            this.selectedTicket = ticket;
            this.showStatusModal = true;
        }
    };
}
</script>
@endpush
