@extends('owner.layouts.app')

@section('page-title', 'Tenant Activity & Audit Logs')

@section('content')
<div class="space-y-3" x-data="{ showMoreFilters: {{ request()->hasAny(['event_type', 'tenant_id']) ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }">

    <!-- Page-specific activity actions; page title is provided by the global Owner header. -->
    <div class="flex justify-end items-center gap-2 flex-wrap">
            <form method="POST" action="{{ route('owner.activity-logs.clear') }}" onsubmit="return confirmActivityLogClear(event, this)">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-semibold transition shadow-xs flex items-center gap-1.5">
                    <i class="fas fa-trash-can text-[10px]"></i><span>Clear Logs</span>
                </button>
            </form>
            <a href="{{ route('owner.activity-logs.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Refresh Logs</span>
            </a>
    </div>

    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Events" :value="number_format($totalLogsCount)" icon="fa-list-check" theme="purple" />
        <x-owner.kpi-card title="Today" :value="number_format($logs->total())" icon="fa-calendar-day" theme="blue" />
        <x-owner.kpi-card title="Filtered" :value="$logs->hasPages() ? 'Paged' : 'All'" icon="fa-filter" theme="cyan" />
        <x-owner.kpi-card title="Tenants" :value="number_format($tenants->count())" icon="fa-building" theme="emerald" />
        <x-owner.kpi-card title="Audit" value="Active" icon="fa-shield-halved" theme="amber" />
        <x-owner.kpi-card title="Access" value="Owner" icon="fa-user-shield" theme="slate" />
    </div>

    <!-- Filters Strip -->
    <x-owner.filter-toolbar action="{{ route('owner.activity-logs.index') }}" reset-url="{{ route('owner.activity-logs.index') }}" form-id="owner-activity-filter" :show-more-open="request()->hasAny(['event_type', 'tenant_id'])" :active-more-count="request()->hasAny(['event_type', 'tenant_id']) ? 2 : 0" :search-value="request('search', '')">
        <x-slot:primary>
            <div class="relative flex-1 min-w-[200px]"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input x-model="searchQuery" type="search" name="search" value="{{ request('search') }}" placeholder="Search description, actor, IP..." class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden"></div>
            <select name="per_page" class="w-full md:w-28 h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="20">20 / page</option><option value="30" @selected(request('per_page') == 30)>30 / page</option><option value="50" @selected(request('per_page') == 50)>50 / page</option><option value="100" @selected(request('per_page') == 100)>100 / page</option></select>
        </x-slot:primary>
        <x-slot:secondary>
                <select name="tenant_id" class="w-full sm:w-auto sm:min-w-[180px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-purple-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            <!-- Event Type -->
            <div>
                <select name="event_type" class="w-full sm:w-auto sm:min-w-[180px] h-[34px] px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:ring-1 focus:ring-purple-500">
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
            </div>

        </x-slot:secondary>
    </x-owner.filter-toolbar>

    <!-- Logs Table -->
    <x-owner.table-shell
        :has-rows="$logs->isNotEmpty()"
        empty-title="No activity logs found"
        empty-message="No tenant activity logs match the current filters."
        empty-icon="fa-clock-rotate-left"
    >
        <div class="overflow-x-auto">
            <table class="saas-table">
                <x-slot:head>
                    <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="text-center">Event</th><th class="text-center">ISP Tenant</th><th class="text-center">Actor</th><th class="text-center">IP Address</th><th class="text-center">Timestamp</th>
                    </tr>
                </x-slot:head>
                <x-slot:body>
                    @foreach($logs as $log)
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

@push('scripts')
<script>
function confirmActivityLogClear(event, form) {
    event.preventDefault();
    const submit = () => form.submit();
    if (window.Swal) {
        Swal.fire({
            title: 'Clear all activity logs?',
            text: 'This will permanently remove the complete activity history.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Yes, clear logs',
            cancelButtonText: 'Cancel'
        }).then(result => { if (result.isConfirmed) submit(); });
    } else if (window.confirm('Clear all activity logs permanently?')) {
        submit();
    }
    return false;
}
</script>
@endpush
