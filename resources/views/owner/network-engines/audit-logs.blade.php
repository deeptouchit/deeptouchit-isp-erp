@extends('owner.layouts.app')

@section('page-title', 'Immutable Network Audit Trail')

@section('content')
<div class="space-y-4" x-data="{ 
    payloadModal: false, 
    selectedPayload: null,
    copiedText: false,
    copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        this.copiedText = true;
        setTimeout(() => this.copiedText = false, 2000);
    }
}">

    <!-- Page Header (Project Standard Sequence) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('owner.network-engines.index') }}" class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition flex-shrink-0">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-base font-bold text-slate-800 leading-tight">Immutable Network Audit Trail</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                        Tamper-Proof Ledger
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Forensic audit logs for Router reboots, OLT commands, FreeRADIUS CoA disconnections & Vault changes</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-xs shadow-xs transition">
                <i class="fas fa-file-csv text-slate-500 text-[11px]"></i>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('owner.network-engines.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-xs shadow-xs transition">
                <i class="fas fa-network-wired text-slate-500 text-[10px]"></i>
                <span>Engines Hub</span>
            </a>
            <a href="{{ route('owner.network-engines.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition">
                <i class="fas fa-sliders text-[10px]"></i>
                <span>Global Settings</span>
            </a>
        </div>
    </div>

    <!-- Top 4-Card Summary Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Metric 1: Total Events Recorded -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-slate-500 uppercase tracking-wider block">Total Ledger Entries</span>
                <span class="text-lg font-extrabold text-slate-900 tracking-tight font-mono">{{ number_format($totalCount) }}</span>
                <span class="text-[10px] text-slate-400 block">Immutable log stream</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Metric 2: Successful Executions -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-emerald-700 uppercase tracking-wider block">Success Operations</span>
                <span class="text-lg font-extrabold text-emerald-700 tracking-tight font-mono">{{ number_format($successCount) }}</span>
                <span class="text-[10px] text-emerald-600 block font-semibold">
                    {{ $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 100 }}% Pass Rate
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 3: Failed / Refused Actions -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-rose-700 uppercase tracking-wider block">Failed / Exceptions</span>
                <span class="text-lg font-extrabold text-rose-700 tracking-tight font-mono">{{ number_format($failedCount) }}</span>
                <span class="text-[10px] text-slate-400 block">Action errors & refusals</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm border border-rose-100 flex-shrink-0">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>

        <!-- Metric 4: Distinct Devices Interacted -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-purple-700 uppercase tracking-wider block">Targeted Hardware</span>
                <span class="text-lg font-extrabold text-purple-700 tracking-tight font-mono">{{ number_format($distinctDevicesCount) }}</span>
                <span class="text-[10px] text-slate-400 block">Routers, OLTs & Hubs</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-100 flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Forensic Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('owner.network-engines.audit-logs') }}" method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-2.5">
                <!-- Search Input -->
                <div class="relative lg:col-span-2">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search device, action, IP, command..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-blue-500 shadow-2xs">
                </div>

                <!-- Device Type -->
                <div>
                    <select name="device_type" onchange="this.form.submit()" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="">All Device Types</option>
                        <option value="router" {{ request('device_type') == 'router' ? 'selected' : '' }}>MikroTik Router</option>
                        <option value="olt" {{ request('device_type') == 'olt' ? 'selected' : '' }}>OLT PON Device</option>
                        <option value="onu" {{ request('device_type') == 'onu' ? 'selected' : '' }}>Client ONU</option>
                        <option value="radius" {{ request('device_type') == 'radius' ? 'selected' : '' }}>FreeRADIUS AAA</option>
                        <option value="wireguard" {{ request('device_type') == 'wireguard' ? 'selected' : '' }}>WireGuard Gateway</option>
                        <option value="system" {{ request('device_type') == 'system' ? 'selected' : '' }}>Platform Core</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <select name="status" onchange="this.form.submit()" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <!-- Tenant Filter -->
                <div>
                    <select name="tenant_id" onchange="this.form.submit()" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="">All Tenants</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->company_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Second Row: Date Ranges & Reset -->
            <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1 border-t border-slate-100 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold text-slate-500">Date Range:</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="py-1 px-2 rounded-lg border border-slate-200 text-xs text-slate-700 bg-white shadow-2xs">
                    <span class="text-slate-400">to</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="py-1 px-2 rounded-lg border border-slate-200 text-xs text-slate-700 bg-white shadow-2xs">
                    
                    @if(!empty($actionTypes))
                        <select name="action_filter" onchange="this.form.submit()" class="py-1 px-2 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 shadow-2xs">
                            <option value="">All Action Types</option>
                            @foreach($actionTypes as $act)
                                <option value="{{ $act }}" {{ request('action_filter') == $act ? 'selected' : '' }}>{{ $act }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button type="submit" class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-2xs cursor-pointer">
                        Filter
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <div class="text-[11px] text-slate-500 font-mono">
                        Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} events
                    </div>
                    @if(request()->hasAny(['search', 'device_type', 'status', 'tenant_id', 'date_from', 'date_to', 'action_filter']))
                        <a href="{{ route('owner.network-engines.audit-logs') }}" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition border border-slate-200 flex items-center gap-1">
                            <i class="fas fa-times text-[10px]"></i>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Logs Ledger Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-50 text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-3.5 py-2.5">Date & Time</th>
                        <th class="px-3.5 py-2.5">Target & Scope</th>
                        <th class="px-3.5 py-2.5">Action Executed</th>
                        <th class="px-3.5 py-2.5">Status</th>
                        <th class="px-3.5 py-2.5">Operator / Origin</th>
                        <th class="px-3.5 py-2.5">Response / Output</th>
                        <th class="px-3.5 py-2.5 text-right">Forensic Trace</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Timestamp -->
                            <td class="px-3.5 py-2.5 whitespace-nowrap">
                                <div class="font-mono text-[11px] font-bold text-slate-800">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Target & Scope -->
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[9.5px] font-bold uppercase font-mono
                                        {{ $log->device_type === 'router' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                        {{ $log->device_type === 'olt' ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' }}
                                        {{ $log->device_type === 'onu' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                        {{ $log->device_type === 'radius' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                        {{ $log->device_type === 'wireguard' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                        {{ $log->device_type === 'system' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}">
                                        {{ $log->device_type }}
                                    </span>
                                    <span class="font-bold text-slate-900">{{ $log->device_name ?? 'Central Platform' }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                    <i class="fas fa-building text-[9px] text-slate-300"></i>
                                    <span>{{ $log->tenant ? $log->tenant->company_name : 'Platform Core System' }}</span>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-3.5 py-2.5">
                                <span class="font-mono font-bold text-blue-700 text-[11px] block">{{ $log->action }}</span>
                                @if($log->command_executed)
                                    <span class="text-[10px] font-mono text-slate-400 truncate max-w-[200px] block" title="{{ $log->command_executed }}">
                                        {{ $log->command_executed }}
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-3.5 py-2.5 whitespace-nowrap">
                                @if($log->status === 'success')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check text-[8px]"></i> SUCCESS
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-xmark text-[8px]"></i> FAILED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-clock text-[8px]"></i> {{ strtoupper($log->status) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Operator / IP -->
                            <td class="px-3.5 py-2.5 whitespace-nowrap">
                                <div class="text-slate-800 font-medium text-xs">{{ $log->user->name ?? 'System Daemon' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                            </td>

                            <!-- Response Summary -->
                            <td class="px-3.5 py-2.5 text-[11px] text-slate-600 max-w-xs truncate" title="{{ $log->response_summary }}">
                                {{ $log->response_summary ?? 'Executed with zero error status codes' }}
                            </td>

                            <!-- Trace Details -->
                            <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                <button type="button" @click="selectedPayload = {{ json_encode([
                                    'id' => $log->id,
                                    'action' => $log->action,
                                    'device_type' => $log->device_type,
                                    'device_name' => $log->device_name,
                                    'tenant' => $log->tenant?->company_name ?? 'Global Platform',
                                    'operator' => $log->user?->name ?? 'System Daemon',
                                    'ip' => $log->ip_address ?? '127.0.0.1',
                                    'status' => $log->status,
                                    'command' => $log->command_executed,
                                    'summary' => $log->response_summary,
                                    'metadata' => $log->metadata,
                                    'timestamp' => $log->created_at->format('Y-m-d H:i:s') . ' (' . $log->created_at->diffForHumans() . ')'
                                ]) }}; payloadModal = true;" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-semibold transition border border-slate-200 flex items-center gap-1 ml-auto cursor-pointer">
                                    <i class="fas fa-file-code text-[10px] text-slate-500"></i>
                                    <span>Details</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-xs">
                                <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-2 text-slate-300 border border-slate-100">
                                    <i class="fas fa-shield-halved text-xl"></i>
                                </div>
                                <div class="font-semibold text-slate-600 text-xs">No Audit Events Recorded</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">Network audit ledger is clean. Device reboots, CoA disconnections and config changes will appear here automatically.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Forensic Trace & Metadata Inspector Modal (Alpine.js) -->
    <div x-show="payloadModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
         style="display: none;"
         x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full p-4 sm:p-5 space-y-3.5" @click.outside="payloadModal = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">
                        <i class="fas fa-shield-halved text-xs"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Forensic Audit Ledger Trace</h3>
                        <div class="text-[10px] text-slate-400 font-mono" x-text="'Trace ID: #' + (selectedPayload ? selectedPayload.id : '')"></div>
                    </div>
                </div>
                <button type="button" @click="payloadModal = false" class="p-1 text-slate-400 hover:text-slate-700 rounded transition cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <template x-if="selectedPayload">
                <div class="space-y-3 text-xs">
                    <!-- Scope Cards -->
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Action Executed</span>
                            <span class="font-mono font-bold text-blue-700 text-xs" x-text="selectedPayload.action"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Execution Status</span>
                            <span class="font-bold text-xs font-mono uppercase" 
                                  :class="selectedPayload.status === 'success' ? 'text-emerald-700' : 'text-rose-700'"
                                  x-text="selectedPayload.status"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Target Device</span>
                            <span class="font-bold text-slate-800" x-text="(selectedPayload.device_name || 'System Core') + ' (' + selectedPayload.device_type + ')'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">ISP Tenant</span>
                            <span class="font-bold text-slate-800" x-text="selectedPayload.tenant"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Operator / Daemon</span>
                            <span class="font-mono text-slate-700" x-text="selectedPayload.operator + ' [' + selectedPayload.ip + ']'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Timestamp</span>
                            <span class="font-mono text-slate-700" x-text="selectedPayload.timestamp"></span>
                        </div>
                    </div>

                    <!-- Dispatched Command -->
                    <div x-show="selectedPayload.command">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10.5px] font-bold text-slate-700">Dispatched Network Command:</span>
                            <button type="button" @click="copyToClipboard(selectedPayload.command)" class="text-[10px] text-blue-600 hover:text-blue-700 font-semibold cursor-pointer flex items-center gap-1">
                                <i class="fas fa-copy"></i>
                                <span x-text="copiedText ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <pre class="p-2.5 bg-slate-900 text-amber-400 font-mono text-[10.5px] rounded-lg overflow-x-auto leading-relaxed select-all" x-text="selectedPayload.command"></pre>
                    </div>

                    <!-- Response Summary -->
                    <div x-show="selectedPayload.summary">
                        <span class="text-[10.5px] font-bold text-slate-700 block mb-1">Response Summary:</span>
                        <div class="p-2.5 bg-slate-100 rounded-lg text-slate-800 text-[11px]" x-text="selectedPayload.summary"></div>
                    </div>

                    <!-- JSON Metadata Attributes -->
                    <div x-show="selectedPayload.metadata && Object.keys(selectedPayload.metadata).length > 0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10.5px] font-bold text-slate-700">JSON Payload & Metadata:</span>
                            <button type="button" @click="copyToClipboard(JSON.stringify(selectedPayload.metadata, null, 2))" class="text-[10px] text-blue-600 hover:text-blue-700 font-semibold cursor-pointer flex items-center gap-1">
                                <i class="fas fa-copy"></i>
                                <span>Copy JSON</span>
                            </button>
                        </div>
                        <pre class="p-2.5 bg-slate-900 text-emerald-400 font-mono text-[10.5px] rounded-lg overflow-x-auto leading-relaxed max-h-48" x-text="JSON.stringify(selectedPayload.metadata, null, 2)"></pre>
                    </div>
                </div>
            </template>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button type="button" @click="payloadModal = false" class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
