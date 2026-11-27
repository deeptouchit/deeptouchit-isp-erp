@extends('owner.layouts.app')

@section('page-title', 'SMS Delivery & Cost Logs')

@section('content')
<div class="space-y-3" x-data="{ showMoreFilters: {{ request()->hasAny(['sms_type', 'tenant_id']) ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }">

    <!-- Page-specific SMS log actions; page title is provided by the global Owner header. -->
    <div class="flex justify-end items-center gap-2 flex-wrap">
            <a href="{{ route('owner.sms-gateways.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-sliders-h text-[10px]"></i>
                <span>SMS Gateway Settings</span>
            </a>
            <a href="{{ route('owner.sms-logs.index') }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Refresh Logs</span>
            </a>
    </div>

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Total Dispatched" :value="number_format($totalSmsCount) . ' SMS'" icon="fa-paper-plane" theme="blue" />
        <x-owner.kpi-card title="Providers" value="Live" icon="fa-tower-broadcast" theme="cyan" />
        <x-owner.kpi-card title="Ledger" value="Active" icon="fa-shield-halved" theme="slate" />
        <x-owner.kpi-card title="Total Incurred Cost" :value="currency($totalSmsCost)" icon="fa-coins" theme="emerald" />
        <x-owner.kpi-card title="Delivered Rate" :value="number_format($deliveredSmsCount)" icon="fa-check-double" theme="emerald" />
        <x-owner.kpi-card title="Failed / Errors" :value="number_format($failedSmsCount)" icon="fa-circle-exclamation" theme="rose" />
    </div>

    <!-- Filters Strip -->
    <x-owner.filter-toolbar action="{{ route('owner.sms-logs.index') }}" reset-url="{{ route('owner.sms-logs.index') }}" form-id="owner-sms-logs-filter" :show-more-open="request()->hasAny(['sms_type', 'tenant_id'])" :active-more-count="request()->hasAny(['sms_type', 'tenant_id']) ? 2 : 0" :search-value="request('search', '')">
        <x-slot:primary>
            <div class="relative flex-1 min-w-[200px]"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input x-model="searchQuery" type="search" name="search" value="{{ request('search') }}" placeholder="Search phone, gateway..." class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden"></div>
            <select name="per_page" class="w-full md:w-28 h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="25">25 / page</option><option value="50" @selected(request('per_page') == 50)>50 / page</option><option value="100" @selected(request('per_page') == 100)>100 / page</option></select>
        </x-slot:primary>
        <x-slot:secondary>
                <select name="tenant_id" class="w-full sm:w-auto sm:min-w-[180px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="w-full sm:w-auto sm:min-w-[160px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
        </x-slot:secondary>
    </x-owner.filter-toolbar>

    <!-- Logs Table -->
    <x-owner.table-shell
        :has-rows="$logs->isNotEmpty()"
        empty-title="No SMS logs found"
        empty-message="No SMS transmission logs match the current filters."
        empty-icon="fa-comment-sms"
    >
        <div class="p-3.5 flex items-center justify-between bg-slate-50/50">
            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <i class="fas fa-list-check text-emerald-600"></i>
                SMS Transmission History Ledger
            </span>
            <span class="text-[10px] text-slate-400 font-mono">{{ $logs->total() }} Records Found</span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <x-slot:head>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="text-center">ID</th><th class="text-center">Sender</th><th class="text-center">Recipient</th><th class="text-left">Message</th><th class="text-center">Send Time</th><th class="text-center">SMS Count</th><th class="text-center">SMS Rate</th><th class="text-center">Status</th><th class="text-center">Source</th><th class="text-center">Date Time</th><th class="text-center">IP Address</th>
                    </tr>
                </x-slot:head>
                <x-slot:body>
                    @foreach($logs as $log)
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
                    @endforeach
                </x-slot:body>
            </table>
        </div>

        <x-slot:pagination>
            @if($logs->hasPages())
                {{ $logs->links() }}
            @endif
        </x-slot:pagination>
    </x-owner.table-shell>

</div>
@endsection
