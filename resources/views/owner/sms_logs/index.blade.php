@extends('owner.layouts.app')

@section('page-title', 'SMS Delivery & Cost Logs')

@section('content')
<div class="space-y-4">

    <!-- Top Banner & Summary Header -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white text-base font-bold shadow-xs flex-shrink-0">
                <i class="fas fa-comment-dots text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Platform-Wide SMS Delivery & Cost Consumption Ledger</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                        {{ number_format($totalSmsCount) }} Total SMS
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Real-time SMS dispatch audit trail, character count, part calculation, carrier billing rates, and delivery statuses.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.sms-gateways.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-sliders-h text-[10px]"></i>
                <span>SMS Gateway Settings</span>
            </a>
            <a href="{{ route('owner.sms-logs.index') }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Refresh Logs</span>
            </a>
        </div>
    </div>

    <!-- Analytics Metric Cards Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Dispatched</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">{{ number_format($totalSmsCount) }} SMS</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                <i class="fas fa-paper-plane"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Total Incurred Cost</span>
                <span class="text-base font-extrabold text-emerald-700 font-mono">@currency($totalSmsCost)</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Delivered Rate</span>
                <span class="text-base font-extrabold text-emerald-600 font-mono">{{ number_format($deliveredSmsCount) }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Failed / Errors</span>
                <span class="text-base font-extrabold text-rose-600 font-mono">{{ number_format($failedSmsCount) }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                <i class="fas fa-circle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-3">
        <form action="{{ route('owner.sms-logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-2.5 text-xs">
            <!-- Search -->
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone, message text, gateway..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500">
            </div>

            <!-- Tenant Filter -->
            <div>
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Apply Filter</span>
                </button>
                @if(request()->hasAny(['search', 'tenant_id', 'status', 'sms_type']))
                    <a href="{{ route('owner.sms-logs.index') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
        <div class="p-3.5 flex items-center justify-between bg-slate-50/50">
            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <i class="fas fa-list-check text-emerald-600"></i>
                SMS Transmission History Ledger
            </span>
            <span class="text-[10px] text-slate-400 font-mono">{{ $logs->total() }} Records Found</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-2.5 px-4">Recipient Phone</th>
                        <th class="py-2.5 px-4">ISP Tenant</th>
                        <th class="py-2.5 px-4">Event Type & Message Body</th>
                        <th class="py-2.5 px-4">Gateway</th>
                        <th class="py-2.5 px-4">Parts & Rate</th>
                        <th class="py-2.5 px-4">Total Cost</th>
                        <th class="py-2.5 px-4">Status</th>
                        <th class="py-2.5 px-4 text-right">Dispatched At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-mono">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition text-[11px]">
                        <td class="py-2.5 px-4 font-bold text-slate-900">
                            {{ $log->recipient_phone }}
                        </td>
                        <td class="py-2.5 px-4 font-sans font-semibold text-slate-800">
                            @if($log->tenant)
                                <a href="{{ route('owner.tenants.show', $log->tenant) }}" class="text-blue-600 hover:underline">
                                    {{ $log->tenant->name }}
                                </a>
                            @else
                                <span class="text-slate-400">System Direct</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 font-sans max-w-sm">
                            <span class="font-bold text-slate-800 text-[10px] uppercase block">
                                {{ str_replace('_', ' ', $log->sms_type) }}
                            </span>
                            <span class="text-[10.5px] text-slate-600 truncate block font-mono" title="{{ $log->message_body }}">
                                {{ Str::limit($log->message_body, 50) }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-[10.5px] text-slate-600">
                            {{ $log->gateway_name }}
                        </td>
                        <td class="py-2.5 px-4 text-[10.5px]">
                            <span class="text-slate-800">{{ $log->parts_count }} Part(s)</span>
                            <span class="text-slate-400 text-[10px] block">({{ $log->character_count }} chars)</span>
                        </td>
                        <td class="py-2.5 px-4 font-bold text-emerald-700">
                            @currency($log->total_cost)
                        </td>
                        <td class="py-2.5 px-4">
                            @if($log->status === 'delivered')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fas fa-check-circle text-[8px] mr-1"></i> Delivered
                                </span>
                            @elseif($log->status === 'sent')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    <i class="fas fa-paper-plane text-[8px] mr-1"></i> Sent
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <i class="fas fa-times-circle text-[8px] mr-1"></i> Failed
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right text-[10.5px] text-slate-400">
                            {{ $log->created_at ? $log->created_at->format('d M, h:i A') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400 font-sans text-xs">
                            No SMS transmission logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
