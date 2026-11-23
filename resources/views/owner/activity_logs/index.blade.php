@extends('owner.layouts.app')

@section('page-title', 'Tenant Activity & Audit Logs')

@section('content')
<div class="space-y-3" x-data="{ showMoreFilters: {{ request()->hasAny(['event_type', 'tenant_id']) ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }">

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
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.activity-logs.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Refresh Logs</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        @foreach([
            ['Events', number_format($totalLogsCount), 'fa-list-check', 'purple'],
            ['Today', number_format($logs->total()), 'fa-calendar-day', 'blue'],
            ['Filtered', $logs->hasPages() ? 'Paged' : 'All', 'fa-filter', 'cyan'],
            ['Tenants', number_format($tenants->count()), 'fa-building', 'emerald'],
            ['Audit', 'Active', 'fa-shield-halved', 'amber'],
            ['Access', 'Owner', 'fa-user-shield', 'slate'],
        ] as [$label, $value, $icon, $color])
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center gap-2">
                <span class="w-6 h-6 rounded-md text-[10px] border flex items-center justify-center flex-shrink-0 bg-{{ $color }}-50 text-{{ $color }}-600 border-{{ $color }}-100"><i class="fas {{ $icon }}"></i></span>
                <span class="min-w-0"><span class="text-[9px] font-medium uppercase tracking-wider block truncate">{{ $label }}</span><span class="text-[13px] font-bold font-mono leading-tight block truncate">{{ $value }}</span></span>
            </div>
        @endforeach
    </div>

    <!-- Filters Strip -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form action="{{ route('owner.activity-logs.index') }}" method="GET" class="space-y-2.5">
            <!-- Search -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <div class="relative flex-1 min-w-[200px]"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i><input x-model="searchQuery" type="search" name="search" value="{{ request('search') }}" placeholder="Search description, actor, IP..." class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-purple-500 focus:outline-hidden"></div>
                <select name="per_page" class="w-full md:w-28 flex-shrink-0 h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="20">20 / page</option><option value="30" @selected(request('per_page') == 30)>30 / page</option><option value="50" @selected(request('per_page') == 50)>50 / page</option><option value="100" @selected(request('per_page') == 100)>100 / page</option></select>
                <button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-3 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold text-xs flex items-center gap-1.5 whitespace-nowrap"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up text-purple-600' : 'fa-chevron-down text-slate-400'"></i><span>More Filters</span><span class="px-1 rounded bg-purple-50 text-purple-700">{{ request()->hasAny(['event_type', 'tenant_id']) ? 2 : 0 }}</span></button>
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto"><button type="submit" class="h-[34px] bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs flex items-center gap-1.5"><i class="fas fa-filter text-[10px]"></i><span>Filter</span></button><a href="{{ route('owner.activity-logs.index') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 flex items-center gap-1"><i class="fas fa-rotate-left text-[10px]"></i><span>Reset</span></a></div>
            </div>

            <!-- Tenant Filter -->
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
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
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="text-center">Event</th><th class="text-center">ISP Tenant</th><th class="text-center">Actor</th><th class="text-center">IP Address</th><th class="text-center">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="text-left">
                            <div class="flex items-center gap-2 flex-nowrap whitespace-nowrap">
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
                                <span class="font-bold text-slate-900 truncate">{{ $log->event_type }}</span>
                            </div>
                        </td>
                        <td class="text-left font-sans font-semibold text-slate-800">
                            @if($log->tenant)
                                <a href="{{ route('owner.tenants.show', $log->tenant) }}" class="text-blue-600 hover:underline">
                                    {{ $log->tenant->name }}
                                </a>
                            @else
                                <span class="text-slate-400">System Platform</span>
                            @endif
                        </td>
                        <td class="text-left">
                            <span class="font-bold text-slate-800">{{ $log->actor_name ?: 'System' }}</span>
                        </td>
                        <td class="text-center font-mono text-slate-600">{{ $log->ip_address ?: 'Unknown' }}</td>
                        <td class="text-center text-slate-400 font-mono">
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
