@extends('reseller.layouts.app')

@section('title', __('Ticket') . ' #' . $ticket->ticket_number . ' - ' . ($reseller->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-3">
    
    {{-- Top Header Bar (AGENTS.md Rule 4: Back Arrow + Clear Title ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('reseller.tickets.index') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition cursor-pointer" title="{{ __('Back to Tickets') }}">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight font-mono">
                        {{ $ticket->ticket_number }}
                    </h1>
                    @if($ticket->status === 'open')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">{{ __('OPEN') }}</span>
                    @elseif($ticket->status === 'in_progress')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-indigo-50 text-indigo-700 border border-indigo-200">{{ __('IN PROGRESS') }}</span>
                    @elseif($ticket->status === 'answered')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">{{ __('ANSWERED') }}</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-50 text-slate-600 border border-slate-200">{{ __('CLOSED') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($ticket->status !== 'closed')
                <form action="{{ route('reseller.tickets.close', $ticket->id) }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('{{ __('Mark this support ticket as closed?') }}')" class="px-3 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-check-circle text-xs"></i>
                        <span>{{ __('Close Ticket') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Ticket Overview Card --}}
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <h2 class="text-sm font-bold text-slate-900 leading-snug">
            {{ $ticket->subject }}
        </h2>
        
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 pt-1 border-t border-slate-100 font-mono">
            <div>
                <span class="text-slate-400">{{ __('Department') }}:</span> 
                <strong class="text-slate-700 capitalize font-sans">{{ $ticket->department }}</strong>
            </div>
            <div>
                <span class="text-slate-400">{{ __('Priority') }}:</span> 
                <strong class="text-slate-700 capitalize font-sans">{{ $ticket->priority }}</strong>
            </div>
            <div>
                <span class="text-slate-400">{{ __('Created') }}:</span> 
                <strong class="text-slate-700">{{ $ticket->created_at->format('d M Y, h:i A') }}</strong>
            </div>
            @if($ticket->last_reply_at)
                <div>
                    <span class="text-slate-400">{{ __('Last Reply') }}:</span> 
                    <strong class="text-purple-700">{{ $ticket->last_reply_at->diffForHumans() }}</strong>
                </div>
            @endif
        </div>
    </div>

    {{-- Conversation Thread --}}
    <div class="space-y-3">
        @foreach($ticket->messages as $msg)
            @php
                $isReseller = ($msg->sender_type === 'tenant' && $msg->user_id === $user->id) || $msg->sender_type === 'reseller';
            @endphp
            <div class="bg-white rounded-xl border {{ $isReseller ? 'border-purple-200 bg-purple-50/20' : 'border-slate-200' }} shadow-xs p-4 space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md {{ $isReseller ? 'bg-purple-600' : 'bg-slate-700' }} text-white flex items-center justify-center text-[10px] font-bold">
                            {{ strtoupper(substr($msg->user->name ?? ($isReseller ? 'R' : 'ISP'), 0, 1)) }}
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-800">
                                {{ $msg->user->name ?? ($isReseller ? 'Reseller Admin' : 'Host ISP Support') }}
                            </span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded font-mono font-semibold {{ $isReseller ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }} ml-1">
                                {{ $isReseller ? __('Reseller') : __('ISP Support') }}
                            </span>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">
                        {{ $msg->created_at->format('d M, Y h:i A') }}
                    </span>
                </div>

                <!-- Message Body -->
                <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                    {{ $msg->message }}
                </div>

                <!-- Attachments if any -->
                @if($msg->attachments && $msg->attachments->count() > 0)
                    <div class="pt-2 border-t border-slate-100 flex flex-wrap gap-2">
                        @foreach($msg->attachments as $att)
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-purple-50 hover:text-purple-700 text-slate-600 text-[11px] font-medium border border-slate-200 transition flex items-center gap-1.5">
                                <i class="fas fa-paperclip text-[10px]"></i>
                                <span class="truncate max-w-xs">{{ $att->file_name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Reply Box --}}
    @if($ticket->status !== 'closed')
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <h3 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <i class="fas fa-reply text-purple-600"></i>
                <span>{{ __('Send Reply') }}</span>
            </h3>

            <form action="{{ route('reseller.tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <textarea name="message" 
                              rows="3" 
                              required 
                              placeholder="{{ __('Write your response to the ISP technical team...') }}" 
                              class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-purple-500 shadow-2xs"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1">
                    <div>
                        <input type="file" 
                               name="attachment" 
                               class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>{{ __('Send Reply') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="p-4 rounded-xl bg-slate-100 text-center text-xs text-slate-500 border border-slate-200">
            <i class="fas fa-lock text-slate-400 mr-1"></i>
            {{ __('This support ticket is marked as closed. Open a new ticket if you have another issue.') }}
        </div>
    @endif

</div>
@endsection
