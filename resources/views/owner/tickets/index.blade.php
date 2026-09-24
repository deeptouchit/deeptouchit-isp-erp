@extends('owner.layouts.app')

@section('page-title', 'Support Tickets & Helpdesk')

@section('content')
<div class="space-y-4" x-data="{
    statusModalOpen: false,
    selectedTicket: null,
    newStatus: 'open',
    
    openStatusModal(ticketId, currentStatus) {
        this.selectedTicket = ticketId;
        this.newStatus = currentStatus;
        this.statusModalOpen = true;
    }
}">


    <!-- KPI Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- 1. Total Tickets -->
        <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tickets</span>
                <h3 class="text-lg font-bold text-slate-800 mt-0.5">{{ number_format($stats['total']) }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 flex items-center justify-center text-xs">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        <!-- 2. Open / In Progress -->
        <div class="p-3 rounded-xl bg-white border border-blue-200 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-blue-50/40">
            <div>
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Active Open</span>
                <h3 class="text-lg font-bold text-blue-800 mt-0.5">{{ number_format($stats['open']) }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        <!-- 3. Awaiting Reply -->
        <div class="p-3 rounded-xl bg-white border border-amber-200 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-amber-50/40">
            <div>
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Pending Reply</span>
                <h3 class="text-lg font-bold text-amber-800 mt-0.5">{{ number_format($stats['awaiting_reply']) }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs">
                <i class="fas fa-reply-all"></i>
            </div>
        </div>

        <!-- 4. Urgent Priority -->
        <div class="p-3 rounded-xl bg-white border border-rose-200 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-rose-50/40">
            <div>
                <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Urgent Attention</span>
                <h3 class="text-lg font-bold text-rose-800 mt-0.5">{{ number_format($stats['urgent']) }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xs">
                <i class="fas fa-fire"></i>
            </div>
        </div>

        <!-- 5. Resolved / Closed -->
        <div class="p-3 rounded-xl bg-white border border-emerald-200 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/40 col-span-2 sm:col-span-1">
            <div>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Resolved</span>
                <h3 class="text-lg font-bold text-emerald-800 mt-0.5">{{ number_format($stats['resolved']) }}</h3>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('owner.tickets.index') }}" method="GET" class="flex flex-col lg:flex-row gap-2.5 items-stretch lg:items-center justify-between">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative lg:col-span-2">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Ticket ID, Subject, Tenant..." 
                           class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Tenant Filter -->
                <select name="tenant_id" class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->company_name }}
                        </option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select name="status" class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>All Active (Open/Answered)</option>
                    <option value="pending_reply" {{ request('status') == 'pending_reply' ? 'selected' : '' }}>Pending Owner Reply</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="answered" {{ request('status') == 'answered' ? 'selected' : '' }}>Answered</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>

                <!-- Priority Filter -->
                <select name="priority" class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Priorities</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>🔴 Urgent</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>🟠 High</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>🔵 Medium</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>⚪ Low</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-xs flex items-center gap-1.5">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Apply Filter</span>
                </button>

                @if(request()->anyFilled(['search', 'tenant_id', 'status', 'priority', 'department']))
                    <a href="{{ route('owner.tickets.index') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition" title="Clear Filters">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tickets Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="py-2.5 px-3">Ticket ID</th>
                        <th class="py-2.5 px-3">ISP Tenant</th>
                        <th class="py-2.5 px-3">Subject & Category</th>
                        <th class="py-2.5 px-3">Priority</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Last Activity</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-slate-50/60 transition {{ $t->isUrgent() && $t->isOpen() ? 'bg-rose-50/20' : '' }}">
                            <!-- Ticket Number -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <a href="{{ route('owner.tickets.show', $t->id) }}" class="font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                    <i class="fas fa-hashtag text-[9px] text-slate-400"></i>
                                    <span>{{ $t->ticket_number }}</span>
                                </a>
                            </td>

                            <!-- Tenant Info -->
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                        {{ strtoupper(substr($t->tenant?->company_name ?? 'T', 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col leading-tight overflow-hidden">
                                        <a href="{{ route('owner.tenants.show', $t->tenant_id) }}" class="font-bold text-slate-800 hover:text-blue-600 truncate max-w-[150px]">
                                            {{ $t->tenant?->company_name ?? 'N/A' }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 truncate">{{ $t->tenant?->domain ?? '' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Subject & Department -->
                            <td class="py-3 px-3">
                                <div class="flex flex-col">
                                    <a href="{{ route('owner.tickets.show', $t->id) }}" class="font-semibold text-slate-900 hover:text-blue-600 line-clamp-1">
                                        {{ $t->subject }}
                                    </a>
                                    <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-400">
                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium">
                                            {{ $t->department_name }}
                                        </span>
                                        <span>•</span>
                                        <span>Opened by {{ $t->user?->name ?? 'Admin' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Priority Badge -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->priority_badge_color }} flex items-center gap-1 w-fit">
                                    @if($t->priority === 'urgent')
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                    @endif
                                    <span>{{ ucfirst($t->priority) }}</span>
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <button type="button" 
                                        @click="openStatusModal({{ $t->id }}, '{{ $t->status }}')"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->status_badge_color }} hover:opacity-80 transition cursor-pointer flex items-center gap-1"
                                        title="Click to quickly change status">
                                    <span>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span>
                                    <i class="fas fa-chevron-down text-[8px] opacity-60"></i>
                                </button>
                            </td>

                            <!-- Last Activity -->
                            <td class="py-3 px-3 whitespace-nowrap text-[11px] text-slate-500">
                                <div class="flex flex-col">
                                    <span>{{ ($t->last_reply_at ?? $t->updated_at)->diffForHumans() }}</span>
                                    <span class="text-[9.5px] font-semibold {{ $t->last_reply_by === 'tenant' ? 'text-amber-600' : 'text-blue-600' }}">
                                        {{ $t->last_reply_by === 'tenant' ? 'Awaiting Reply' : 'Staff Replied' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-3 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('owner.tickets.show', $t->id) }}" 
                                       class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-semibold text-[11px] transition shadow-2xs flex items-center gap-1">
                                        <i class="fas fa-comments text-[9px]"></i>
                                        <span>Reply</span>
                                    </a>

                                    <form action="{{ route('owner.tickets.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Delete Ticket">
                                            <i class="fas fa-trash-alt text-[11px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-inbox"></i>
                                    </div>
                                    <p class="font-medium text-xs text-slate-500">No support tickets found matching your criteria</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-3 border-t border-slate-100 bg-slate-50/50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Status Change Modal -->
    <div x-show="statusModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-cloak>
        <div @click.away="statusModalOpen = false" class="bg-white rounded-xl border border-slate-200 shadow-xl max-w-sm w-full p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <h3 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-sliders text-blue-600"></i>
                    <span>Quick Change Ticket Status</span>
                </h3>
                <button type="button" @click="statusModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <template x-if="selectedTicket">
                <form :action="'/owner/tickets/' + selectedTicket + '/status'" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">New Status</label>
                        <select name="status" x-model="newStatus" class="w-full text-xs rounded-lg border border-slate-200 p-2 focus:ring-1 focus:ring-blue-500">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="answered">Answered</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="statusModalOpen = false" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs">
                            Cancel
                        </button>
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs">
                            Update Status
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
