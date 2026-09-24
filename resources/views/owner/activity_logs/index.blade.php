@extends('owner.layouts.app')

@section('page-title', 'Tenant Activity & Audit Logs')

@section('content')
<div class="space-y-4">

    <!-- Top Banner & Summary Header -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-700 flex items-center justify-center text-white text-base font-bold shadow-xs flex-shrink-0">
                <i class="fas fa-history text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Platform-Wide Tenant Activity & Audit Trail</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                        {{ number_format($totalLogsCount) }} Total Events
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Real-time security auditing, impersonations, wallet adjustments, and automated lifecycle state transitions.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.activity-logs.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Refresh Logs</span>
            </a>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-3">
        <form action="{{ route('owner.activity-logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 text-xs">
            <!-- Search -->
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description, actor, IP..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-purple-500">
            </div>

            <!-- Tenant Filter -->
            <div>
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-purple-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Event Type -->
            <div>
                <select name="event_type" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-purple-500">
                    <option value="">All Event Types</option>
                    <option value="tenant_created" {{ request('event_type') == 'tenant_created' ? 'selected' : '' }}>Tenant Created</option>
                    <option value="tenant_updated" {{ request('event_type') == 'tenant_updated' ? 'selected' : '' }}>Tenant Updated</option>
                    <option value="status_toggled" {{ request('event_type') == 'status_toggled' ? 'selected' : '' }}>Status Toggled</option>
                    <option value="subscription_extended" {{ request('event_type') == 'subscription_extended' ? 'selected' : '' }}>Subscription Extended</option>
                    <option value="impersonated" {{ request('event_type') == 'impersonated' ? 'selected' : '' }}>Super Admin Impersonation</option>
                    <option value="wallet_adjusted" {{ request('event_type') == 'wallet_adjusted' ? 'selected' : '' }}>Wallet Adjusted</option>
                    <option value="service_suspended" {{ request('event_type') == 'service_suspended' ? 'selected' : '' }}>Service Suspended</option>
                    <option value="service_restored" {{ request('event_type') == 'service_restored' ? 'selected' : '' }}>Service Restored</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Apply Filter</span>
                </button>
                @if(request()->hasAny(['search', 'tenant_id', 'event_type']))
                    <a href="{{ route('owner.activity-logs.index') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-2.5 px-4">Event & Description</th>
                        <th class="py-2.5 px-4">ISP Tenant</th>
                        <th class="py-2.5 px-4">Actor</th>
                        <th class="py-2.5 px-4">IP & Client</th>
                        <th class="py-2.5 px-4 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-2.5 px-4">
                            <div class="flex items-start gap-2.5">
                                <div class="w-6 h-6 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0 text-[10px] mt-0.5 border border-purple-100">
                                    @if(str_contains($log->event_type, 'created'))
                                        <i class="fas fa-plus"></i>
                                    @elseif(str_contains($log->event_type, 'wallet'))
                                        <i class="fas fa-wallet"></i>
                                    @elseif(str_contains($log->event_type, 'impersonate'))
                                        <i class="fas fa-user-secret"></i>
                                    @elseif(str_contains($log->event_type, 'suspend'))
                                        <i class="fas fa-lock text-rose-600"></i>
                                    @elseif(str_contains($log->event_type, 'extend') || str_contains($log->event_type, 'renew'))
                                        <i class="fas fa-calendar-check text-emerald-600"></i>
                                    @else
                                        <i class="fas fa-bolt"></i>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block text-xs">{{ $log->description }}</span>
                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-mono text-[9.5px] uppercase font-bold">
                                        {{ str_replace('_', ' ', $log->event_type) }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 px-4 font-sans font-semibold text-slate-800">
                            @if($log->tenant)
                                <a href="{{ route('owner.tenants.show', $log->tenant) }}" class="text-blue-600 hover:underline">
                                    {{ $log->tenant->name }}
                                </a>
                            @else
                                <span class="text-slate-400">System Platform</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4">
                            <span class="font-bold text-slate-800 block text-[11px]">{{ $log->actor_name ?: 'System' }}</span>
                            <span class="text-[9.5px] text-slate-400 uppercase font-mono">{{ $log->actor_type }}</span>
                        </td>
                        <td class="py-2.5 px-4 font-mono text-[10.5px] text-slate-600">
                            <span class="block text-slate-800">{{ $log->ip_address ?: '127.0.0.1' }}</span>
                            <span class="text-[9px] text-slate-400 block truncate max-w-xs font-sans">{{ Str::limit($log->user_agent, 30) }}</span>
                        </td>
                        <td class="py-2.5 px-4 text-right text-[10.5px] text-slate-400 font-mono">
                            {{ $log->created_at ? $log->created_at->format('d M, h:i A') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 font-sans text-xs">
                            No tenant activity logs found matching the filter criteria.
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
