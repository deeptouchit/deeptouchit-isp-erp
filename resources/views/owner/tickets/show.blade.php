@extends('owner.layouts.app')

@section('page-title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="space-y-4" x-data="{
    isInternalNote: false,
    replyMessage: '',
}">


    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('owner.tickets.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Back to Tickets">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono font-bold text-blue-600 text-xs sm:text-sm">#{{ $ticket->ticket_number }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ticket->priority_badge_color }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ticket->status_badge_color }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-slate-900 mt-0.5 tracking-tight">{{ $ticket->subject }}</h2>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400">Department: <strong class="text-slate-700">{{ $ticket->department_name }}</strong></span>
            <span class="text-slate-300">|</span>
            <span class="text-[11px] text-slate-400">Created: <strong class="text-slate-700">{{ $ticket->created_at->format('d M Y, h:i A') }}</strong></span>
        </div>
    </div>

    <!-- Main Content 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Left 2 Columns: Conversation Thread & Reply Form -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Conversation Message Cards -->
            <div class="space-y-3">
                @foreach($ticket->messages as $msg)
                    @if($msg->is_internal_note)
                        <!-- Internal Note Card (Highlighted Amber) -->
                        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-amber-200/60 pb-2 mb-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-amber-200 text-amber-900 font-bold text-[10px] flex items-center justify-center">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-amber-900">{{ $msg->user?->name ?? 'Staff Admin' }}</span>
                                        <span class="px-1.5 py-0.2 rounded bg-amber-200 text-amber-800 text-[9.5px] font-bold ml-1 uppercase">Internal Private Note</span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-amber-700 font-medium">{{ $msg->created_at->format('d M Y, h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                            </div>
                            <div class="text-xs text-amber-950 whitespace-pre-wrap leading-relaxed">
                                {!! nl2br(e($msg->message)) !!}
                            </div>
                        </div>
                    @else
                        <!-- Public Message Card -->
                        <div class="bg-white border {{ $msg->sender_type === 'owner' ? 'border-blue-200 bg-blue-50/10' : 'border-slate-200' }} rounded-xl p-3.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2.5">
                                <div class="flex items-center gap-2">
                                    @if($msg->sender_type === 'owner')
                                        <div class="w-6 h-6 rounded-md bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center">
                                            <i class="fas fa-shield-alt text-[9px]"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-xs text-blue-900">{{ $msg->user?->name ?? 'Platform Support' }}</span>
                                            <span class="px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 text-[9px] font-bold ml-1">Platform Admin</span>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 rounded-md bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">
                                            {{ strtoupper(substr($ticket->tenant?->company_name ?? 'T', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-xs text-slate-800">{{ $ticket->tenant?->company_name }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1">({{ $msg->user?->name ?? 'Tenant User' }})</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('d M Y, h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                            </div>

                            <!-- Message Body -->
                            <div class="text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {!! nl2br(e($msg->message)) !!}
                            </div>

                            <!-- Attachments Preview if any -->
                            @if($msg->attachments->isNotEmpty())
                                <div class="mt-3 pt-2.5 border-t border-slate-100 space-y-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Files:</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($msg->attachments as $att)
                                            <a href="{{ $att->url }}" target="_blank" class="flex items-center gap-2 p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white hover:border-blue-400 transition text-[11px] text-slate-700 max-w-xs">
                                                @if($att->isImage())
                                                    <i class="fas fa-file-image text-blue-500"></i>
                                                @else
                                                    <i class="fas fa-file text-slate-500"></i>
                                                @endif
                                                <span class="truncate font-medium">{{ $att->file_name }}</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono">({{ $att->formatted_size }})</span>
                                                <i class="fas fa-download text-[9px] text-slate-400"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Reply Box Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-reply text-blue-600"></i>
                        <span x-text="isInternalNote ? 'Write Internal Staff Note' : 'Send Reply to Tenant'"></span>
                    </h3>

                    <!-- Toggle Button between Public Reply and Internal Note -->
                    <button type="button" 
                            @click="isInternalNote = !isInternalNote"
                            :class="isInternalNote ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-slate-100 text-slate-700 border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[10.5px] font-bold border transition flex items-center gap-1.5">
                        <i :class="isInternalNote ? 'fas fa-lock' : 'fas fa-globe'"></i>
                        <span x-text="isInternalNote ? 'Switch to Public Reply' : 'Switch to Internal Note'"></span>
                    </button>
                </div>

                <form action="{{ route('owner.tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="hidden" name="is_internal_note" :value="isInternalNote ? '1' : '0'">

                    <!-- Note banner if internal -->
                    <div x-show="isInternalNote" class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[10.5px] flex items-center gap-1.5 font-medium">
                        <i class="fas fa-info-circle text-amber-600"></i>
                        <span>This note will ONLY be visible to Platform Super Admins and Staff. The tenant cannot see this.</span>
                    </div>

                    <!-- Message Textarea -->
                    <div>
                        <textarea name="message" 
                                  rows="5" 
                                  required
                                  placeholder="Type your message or internal notes here..." 
                                  :class="isInternalNote ? 'border-amber-300 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/20' : 'border-slate-200 focus:ring-blue-500 focus:border-blue-500 bg-white'"
                                  class="w-full rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-1 transition"></textarea>
                    </div>

                    <!-- Attachments Input -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-500 mb-1">Upload Attachments (Images, PDF, Logs - Max 10MB each)</label>
                        <input type="file" 
                               name="attachments[]" 
                               multiple
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-[10.5px] text-slate-400">
                            Replying will update ticket status to <strong class="text-slate-700">Answered</strong>.
                        </span>

                        <button type="submit" 
                                :class="isInternalNote ? 'bg-amber-600 hover:bg-amber-700' : 'bg-blue-600 hover:bg-blue-700'"
                                class="px-4 py-2 rounded-lg text-white font-bold text-xs transition shadow-xs flex items-center gap-2">
                            <i :class="isInternalNote ? 'fas fa-save' : 'fas fa-paper-plane'"></i>
                            <span x-text="isInternalNote ? 'Save Private Note' : 'Send Official Reply'"></span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Right 1 Column: Tenant Intel & Ticket Controls -->
        <div class="space-y-4">
            
            <!-- 1. Ticket Control Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <h3 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2 flex items-center gap-2">
                    <i class="fas fa-sliders text-blue-600"></i>
                    <span>Ticket Management</span>
                </h3>

                <!-- Status Update Form -->
                <form action="{{ route('owner.tickets.status', $ticket->id) }}" method="POST" class="space-y-2">
                    @csrf
                    <label class="block text-[11px] font-semibold text-slate-600">Change Status</label>
                    <div class="flex items-center gap-2">
                        <select name="status" class="w-full text-xs rounded-lg border border-slate-200 bg-slate-50 p-1.5 focus:bg-white focus:ring-1 focus:ring-blue-500">
                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="answered" {{ $ticket->status === 'answered' ? 'selected' : '' }}>Answered</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition">
                            Save
                        </button>
                    </div>
                </form>

                <!-- Priority Update Form -->
                <form action="{{ route('owner.tickets.priority', $ticket->id) }}" method="POST" class="space-y-2 pt-2 border-t border-slate-100">
                    @csrf
                    <label class="block text-[11px] font-semibold text-slate-600">Change Priority</label>
                    <div class="flex items-center gap-2">
                        <select name="priority" class="w-full text-xs rounded-lg border border-slate-200 bg-slate-50 p-1.5 focus:bg-white focus:ring-1 focus:ring-blue-500">
                            <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>🔴 Urgent</option>
                            <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>🟠 High</option>
                            <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>🔵 Medium</option>
                            <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>⚪ Low</option>
                        </select>
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition">
                            Save
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. Tenant Information Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-building text-blue-600"></i>
                        <span>ISP Tenant Profile</span>
                    </h3>
                    <a href="{{ route('owner.tenants.show', $ticket->tenant_id) }}" class="text-[10px] font-bold text-blue-600 hover:underline">
                        View Full Profile
                    </a>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <span class="text-slate-400 text-[10.5px] block">Company Name</span>
                        <span class="font-bold text-slate-800 text-sm">{{ $ticket->tenant?->company_name ?? 'N/A' }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-400 text-[10.5px] block">Domain / Host</span>
                            <span class="font-mono text-slate-700 text-[11px] truncate">{{ $ticket->tenant?->domain ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] block">Tenant Status</span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $ticket->tenant?->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ ucfirst($ticket->tenant?->status ?? 'Active') }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 text-[10.5px] block">Current SaaS Plan</span>
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="font-bold text-blue-700">{{ $ticket->tenant?->plan?->name ?? 'Custom Plan' }}</span>
                            <span class="font-mono text-slate-600 text-[11px]">৳{{ number_format($ticket->tenant?->plan?->price_monthly ?? 0) }}/mo</span>
                        </div>
                    </div>

                    @php
                        $activeSub = $ticket->tenant?->activeSubscription;
                    @endphp
                    @if($activeSub)
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-[10.5px] space-y-1">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Subscription End:</span>
                                <span class="font-bold text-slate-700">{{ $activeSub->ends_at ? $activeSub->ends_at->format('d M Y') : 'Lifetime' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Sub Status:</span>
                                <span class="font-bold text-emerald-600">{{ ucfirst($activeSub->status) }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Impersonate Action -->
                    <div class="pt-2">
                        <a href="{{ route('owner.tenants.impersonate', $ticket->tenant_id) }}" 
                           class="w-full py-1.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center gap-1.5 transition">
                            <i class="fas fa-right-to-bracket text-blue-600"></i>
                            <span>Login As This Tenant (Impersonate)</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. Ticket Meta Details -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-2 text-xs">
                <h3 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2">
                    Ticket Metadata
                </h3>
                <div class="space-y-1.5 text-[11px] text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Replies:</span>
                        <span class="font-bold">{{ $ticket->messages->count() }} messages</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Last Reply By:</span>
                        <span class="font-bold {{ $ticket->last_reply_by === 'tenant' ? 'text-amber-600' : 'text-blue-600' }}">
                            {{ ucfirst($ticket->last_reply_by) }}
                        </span>
                    </div>
                    @if($ticket->resolved_at)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Resolved Date:</span>
                            <span class="font-bold text-emerald-600">{{ $ticket->resolved_at->format('d M Y, h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
