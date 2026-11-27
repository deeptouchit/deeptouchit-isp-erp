@extends('owner.layouts.app')

@section('page-title', 'SMS Delivery & Cost Logs')

@section('content')
<div class="space-y-3" x-data="{ showMoreFilters: {{ request()->hasAny(['sms_type', 'tenant_id']) ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }">

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
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Dispatched</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">{{ number_format($totalSmsCount) }} SMS</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                <i class="fas fa-paper-plane"></i>
            </div>
        </div>

        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between"><div><span class="text-[9px] font-medium uppercase tracking-wider block">Providers</span><span class="text-[13px] font-bold font-mono">Live</span></div><span class="w-6 h-6 rounded-md text-[10px] border flex items-center justify-center bg-cyan-50 text-cyan-600 border-cyan-100"><i class="fas fa-tower-broadcast"></i></span></div>
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between"><div><span class="text-[9px] font-medium uppercase tracking-wider block">Ledger</span><span class="text-[13px] font-bold font-mono">Active</span></div><span class="w-6 h-6 rounded-md text-[10px] border flex items-center justify-center bg-slate-50 text-slate-600 border-slate-100"><i class="fas fa-shield-halved"></i></span></div>

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
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form action="{{ route('owner.sms-logs.index') }}" method="GET" class="space-y-2.5">
            <!-- Search -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2"><div class="relative flex-1 min-w-[200px]"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i><input x-model="searchQuery" type="search" name="search" value="{{ request('search') }}" placeholder="Search phone, gateway..." class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-emerald-500 focus:outline-hidden"></div><select name="per_page" class="w-full md:w-28 h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="25">25 / page</option><option value="50" @selected(request('per_page') == 50)>50 / page</option><option value="100" @selected(request('per_page') == 100)>100 / page</option></select><button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold text-xs flex items-center gap-1.5 whitespace-nowrap"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up text-emerald-600' : 'fa-chevron-down text-slate-400'"></i><span>More Filters</span><span class="px-1 rounded bg-emerald-50 text-emerald-700">{{ request()->hasAny(['sms_type', 'tenant_id']) ? 2 : 0 }}</span></button><div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto"><button type="submit" class="h-[34px] bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs flex items-center gap-1.5"><i class="fas fa-filter text-[10px]"></i><span>Filter</span></button><a href="{{ route('owner.sms-logs.index') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 flex items-center gap-1"><i class="fas fa-rotate-left text-[10px]"></i><span>Reset</span></a></div></div>

            <!-- Tenant Filter -->
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <select name="tenant_id" class="w-full sm:w-auto sm:min-w-[180px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            <!-- Status -->
            <div>
                <select name="status" class="w-full sm:w-auto sm:min-w-[160px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            </div>

            <!-- Filter Buttons -->
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
            <table class="saas-table">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="text-center">ID</th><th class="text-center">Sender</th><th class="text-center">Recipient</th><th class="text-left">Message</th><th class="text-center">Send Time</th><th class="text-center">SMS Count</th><th class="text-center">SMS Rate</th><th class="text-center">Status</th><th class="text-center">Source</th><th class="text-center">Date Time</th><th class="text-center">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-mono">
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-center">{{ $log->id }}</td>
                        <td class="text-center font-sans font-semibold">{{ $log->sender_id ?: $log->gateway_name ?: '—' }}</td>
                        <td class="text-center font-bold text-slate-900">{{ $log->recipient_phone }}</td>
                        <td class="text-left font-sans max-w-[260px] truncate" title="{{ $log->message_body }}">{{ $log->message_body }}</td>
                        <td class="text-center">{{ $log->created_at?->format('h:i A') ?: '—' }}</td>
                        <td class="text-center">{{ number_format((int) ($log->parts_count ?: 1)) }}</td>
                        <td class="text-right">@currency($log->cost_per_part)</td>
                        <td class="text-center">
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
                        <td class="text-center text-slate-400">
                            {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="text-center">{{ str_replace('_', ' ', $log->source ?: $log->sms_type) }}</td>
                        <td class="text-center text-slate-400">{{ $log->created_at?->format('d M Y, h:i A') ?: '—' }}</td>
                        <td class="text-center text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-8 text-center text-slate-400 font-sans text-xs">
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
