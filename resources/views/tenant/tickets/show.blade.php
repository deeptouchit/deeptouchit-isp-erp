@extends('tenant.layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
        

        <!-- 2 Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- Left 2 Cols: Thread and Reply -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Conversation Thread -->
                <div class="space-y-3">
                    @foreach($ticket->publicMessages as $msg)
                        <div class="bg-white border {{ $msg->sender_type === 'owner' ? 'border-blue-200 bg-blue-50/10' : 'border-slate-200' }} rounded-xl p-3.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2.5">
                                <div class="flex items-center gap-2">
                                    @if($msg->sender_type === 'owner')
                                        <div class="w-6 h-6 rounded-md bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center">
                                            <i class="fas fa-headset text-[9px]"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-xs text-blue-900">Platform Support Staff</span>
                                            <span class="px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 text-[9px] font-bold ml-1">Official Response</span>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 rounded-md bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center">
                                            {{ strtoupper(substr($msg->user?->name ?? 'You', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-xs text-slate-800">{{ $msg->user?->name ?? 'You' }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1">(Your Team)</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('d M Y, h:i A') }} ({{ $msg->created_at->locale('en')->diffForHumans() }})</span>
                            </div>

                            <div class="text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {!! nl2br(e($msg->message)) !!}
                            </div>

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
                    @endforeach
                </div>

                <!-- Tenant Reply Form -->
                @if($ticket->status === 'closed')
                    <div class="p-4 rounded-xl bg-slate-100 border border-slate-200 text-center text-slate-500 text-xs">
                        <i class="fas fa-lock mr-1 text-slate-400"></i> This ticket has been closed. To continue discussing this issue, you can reply below to reopen it.
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2">
                        <i class="fas fa-reply text-blue-600"></i>
                        <span>Submit a Reply</span>
                    </h3>

                    <form action="{{ route('tenant.tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <textarea name="message" 
                                      rows="4" 
                                      required 
                                      placeholder="Type your message, questions or follow-up details..." 
                                      class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-slate-50 focus:bg-white transition leading-relaxed"></textarea>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-500 mb-1">Attach Files (Screenshots, Logs - Max 10MB)</label>
                            <input type="file" 
                                   name="attachments[]" 
                                   multiple
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                        </div>

                        <div class="flex justify-end pt-2 border-t border-slate-100">
                            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-2">
                                <i class="fas fa-paper-plane text-xs"></i>
                                <span>Send Reply</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Right 1 Col: Meta & Helpdesk Info -->
            <div class="space-y-4">
                
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fas fa-circle-info text-blue-600"></i>
                        <span>Ticket Information</span>
                    </h3>

                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="text-slate-400 text-[10.5px] block">Department</span>
                            <span class="font-bold text-slate-800">{{ $ticket->department_name }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 text-[10.5px] block">Opened By</span>
                            <span class="font-bold text-slate-800">{{ $ticket->user?->name ?? 'Admin' }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 text-[10.5px] block">Created Date</span>
                            <span class="text-slate-700 font-mono">{{ $ticket->created_at->format('d M Y, h:i A') }}</span>
                        </div>

                        @if($ticket->resolved_at)
                            <div>
                                <span class="text-slate-400 text-[10.5px] block">Resolved Date</span>
                                <span class="text-emerald-700 font-semibold">{{ $ticket->resolved_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Support SLA Advice Box -->
                <div class="bg-blue-50/70 rounded-xl border border-blue-200 p-4 text-blue-950 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-xs text-blue-900">
                        <i class="fas fa-headset text-blue-600"></i>
                        <span>SomitySoft Support SLA</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-blue-800">
                        Our technical and billing operations team monitors incoming support tickets 24/7. Urgent issues receive prioritized responses within 15–30 minutes.
                    </p>
                </div>

            </div>

        </div>
@endsection
