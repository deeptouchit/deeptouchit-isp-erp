@extends('tenant.layouts.app')

@section('title', ($router->name ?? 'MikroTik Gateway') . ' - NOC Monitor')

@section('content')
<div class="space-y-3" x-data="mikrotikShowManager()" x-init="init()">

    <!-- Toast Notification Overlay -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-3.5 py-2 rounded-lg shadow-lg border text-xs font-semibold"
         :class="toast.type === 'success' ? 'bg-slate-900 text-emerald-400 border-slate-700' : 'bg-slate-900 text-rose-400 border-slate-700'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
        <span x-text="toast.message" class="text-white"></span>
    </div>

    <!-- 1. Top Header Bar with Live Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition cursor-pointer" 
               title="Back to MikroTik Directory">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shadow-2xs flex-shrink-0"
                 :class="status === 'online' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200'">
                <i class="fas fa-server"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight">{{ $router->name }}</h1>
                
                <!-- Live Health Status Badge -->
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border transition-colors duration-300"
                      :class="status === 'online' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (status === 'disabled' ? 'bg-slate-100 text-slate-600 border-slate-200' : 'bg-rose-50 text-rose-700 border-rose-200')">
                    <span class="w-1.5 h-1.5 rounded-full" :class="status === 'online' ? 'bg-emerald-500 animate-pulse' : (status === 'disabled' ? 'bg-slate-400' : 'bg-rose-500')"></span>
                    <span x-text="status.toUpperCase()"></span>
                </span>

                <!-- Architecture Badge -->
                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $router->connection_badge['class'] }}">
                    <i class="fas {{ $router->connection_badge['icon'] }} text-[8.5px] mr-1"></i>
                    <span>{{ $router->connection_badge['label'] }}</span>
                </span>

                <!-- Host IP Badge -->
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $router->ip_address }}
                </span>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2">
            
            <!-- Real-Time Auto-Refresh Stream Toggle -->
            <button type="button" 
                    @click="toggleLiveStream()" 
                    class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold shadow-2xs transition flex items-center gap-1.5 cursor-pointer"
                    :class="autoRefresh ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-500'"
                    :title="autoRefresh ? 'Real-Time Auto Stream Active (Every 4s)' : 'Real-Time Stream Paused'">
                <span class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                <span x-text="autoRefresh ? 'Live Stream Active' : 'Live Stream Paused'"></span>
            </button>

            <!-- Sync Telemetry Now -->
            <button type="button" 
                    @click="pollTelemetry(true)" 
                    :disabled="isPolling || !{{ $router->is_active ? 'true' : 'false' }}"
                    class="px-2.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="fas fa-sync-alt text-[10px]" :class="{ 'fa-spin': isPolling }"></i>
                <span x-text="isPolling ? 'Probing...' : 'Probe Live'"></span>
            </button>

            <!-- Configure Button -->
            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-server text-[10px]"></i>
                <span>Routers Fleet</span>
            </a>

            <!-- Reboot Button (SweetAlert2) -->
            <button type="button" 
                    @click="confirmReboot()"
                    class="p-1.5 rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs transition cursor-pointer" 
                    title="Reboot Router Gateway">
                <i class="fas fa-power-off text-xs"></i>
            </button>

            <!-- Hidden Reboot Form -->
            <form id="rebootForm" action="{{ route('tenant.network.mikrotik.reboot', $router->id) }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    <!-- 2. Real-Time 5-Card Metric Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
        
        <!-- Metric 1: Health State & Latency -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block truncate">Status / Latency</span>
                <span class="text-sm font-extrabold tracking-tight font-mono leading-none block"
                      :class="status === 'online' ? 'text-emerald-700' : (status === 'disabled' ? 'text-slate-600' : 'text-rose-700')"
                      x-text="status === 'online' ? (latency + ' ms RTT') : (status === 'disabled' ? 'DISABLED' : 'OFFLINE')">
                </span>
            </div>
            <div class="w-7 h-7 rounded-md flex items-center justify-center text-xs border flex-shrink-0"
                 :class="status === 'online' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : (status === 'disabled' ? 'bg-slate-100 text-slate-500 border-slate-200' : 'bg-rose-50 text-rose-600 border-rose-100')">
                <i class="fas" :class="status === 'online' ? 'fa-bolt' : 'fa-triangle-exclamation'"></i>
            </div>
        </div>

        <!-- Metric 2: Live CPU Load -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-blue-700 uppercase tracking-wider block truncate">CPU Load</span>
                <span class="text-sm font-extrabold text-blue-700 tracking-tight font-mono leading-none block" 
                      x-text="status === 'online' && cpuLoad !== null ? (cpuLoad + '%') : '--'"></span>
            </div>
            <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-microchip"></i>
            </div>
        </div>

        <!-- Metric 3: RAM Available -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-emerald-700 uppercase tracking-wider block truncate">RAM Free</span>
                <span class="text-sm font-extrabold text-emerald-700 tracking-tight font-mono leading-none block" 
                      x-text="status === 'online' && freeMemory > 0 ? formatBytes(freeMemory) : '--'"></span>
            </div>
            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-memory"></i>
            </div>
        </div>

        <!-- Metric 4: Real-Time Live Uptime -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-purple-700 uppercase tracking-wider block truncate">Live Uptime</span>
                <span class="text-xs font-extrabold text-purple-700 tracking-tight font-mono leading-none block truncate" 
                      x-text="status === 'online' && uptimeStr ? formatUptime(uptimeStr) : (status === 'disabled' ? 'Disabled' : 'Offline')"></span>
            </div>
            <div class="w-7 h-7 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Connected OLTs -->
        <div class="px-3 py-2 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[10px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Connected OLTs</span>
                <span class="text-sm font-extrabold text-indigo-700 tracking-tight font-mono leading-none block">{{ $router->olts->count() }} Chassis</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs border border-indigo-100 flex-shrink-0">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>

    </div>

    <!-- 3. Real-Time Telemetry & FreeRADIUS Setup Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        
        <!-- Left 6 Columns: Hardware Telemetry Monitor -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800">Hardware Telemetry</span>
                </div>
                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-mono">
                    <span>Sampled:</span>
                    <strong class="text-slate-700" x-text="lastSyncAt"></strong>
                </div>
            </div>

            <!-- Offline Diagnostic State -->
            <template x-if="status !== 'online'">
                <div class="p-3.5 rounded-lg bg-rose-50/70 border border-rose-200 space-y-2">
                    <div class="flex items-center gap-2 text-rose-800 font-semibold text-xs">
                        <i class="fas fa-circle-exclamation text-rose-600"></i>
                        <span>Router Unreachable / Telemetry Unavailable</span>
                    </div>
                    <p class="text-[11px] text-rose-700 leading-relaxed font-mono break-all" 
                       x-text="lastError || 'Cannot establish socket connection to {{ $router->ip_address }}:{{ $router->api_port ?: 8728 }}. Please verify router power, IP reachability, and API service (/ip service enable api).'"></p>
                    <div class="pt-1 flex items-center gap-2 flex-wrap">
                        <button type="button" 
                                @click="pollTelemetry(true)" 
                                :disabled="isPolling"
                                class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-md text-[11px] font-semibold transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                            <i class="fas fa-rotate" :class="{ 'fa-spin': isPolling }"></i>
                            <span x-text="isPolling ? 'Probing...' : 'Probe Live Connection'"></span>
                        </button>
                        <a href="{{ route('tenant.network.mikrotik') }}" class="text-[11px] font-semibold text-rose-700 hover:underline">
                            Router Settings &rarr;
                        </a>
                    </div>
                </div>
            </template>

            <!-- Online Live Telemetry Stream -->
            <template x-if="status === 'online'">
                <div class="space-y-3">
                    <!-- CPU Real-Time Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-600 flex items-center gap-1.5">
                                <i class="fas fa-microchip text-[11px] text-blue-500"></i>
                                <span>CPU Utilization</span>
                            </span>
                            <span class="font-bold font-mono text-blue-700" x-text="cpuLoad !== null ? (cpuLoad + '%') : '0%'"></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500" 
                                 :class="cpuLoad > 80 ? 'bg-rose-500' : (cpuLoad > 50 ? 'bg-amber-500' : 'bg-blue-600')"
                                 :style="'width: ' + (cpuLoad || 0) + '%'"></div>
                        </div>
                    </div>

                    <!-- RAM Memory Real-Time Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-600 flex items-center gap-1.5">
                                <i class="fas fa-memory text-[11px] text-emerald-500"></i>
                                <span>RAM Memory Usage</span>
                            </span>
                            <span class="font-bold font-mono text-emerald-700" x-text="formatBytes(totalMemory - freeMemory) + ' / ' + formatBytes(totalMemory) + ' (' + calcUsedMemPercent() + '%)'"></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" 
                                 :style="'width: ' + calcUsedMemPercent() + '%'"></div>
                        </div>
                    </div>

                    <!-- HDD / Storage Progress Bar -->
                    <div class="space-y-1" x-show="totalHdd > 0">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-600 flex items-center gap-1.5">
                                <i class="fas fa-hard-drive text-[11px] text-purple-500"></i>
                                <span>Storage (NAND / Disk)</span>
                            </span>
                            <span class="font-bold font-mono text-purple-700" x-text="formatBytes(totalHdd - freeHdd) + ' / ' + formatBytes(totalHdd)"></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full bg-purple-500 transition-all duration-500" 
                                 :style="'width: ' + calcUsedHddPercent() + '%'"></div>
                        </div>
                    </div>

                    <!-- Telemetry Details Grid -->
                    <div class="grid grid-cols-3 gap-2 pt-1 border-t border-slate-100 text-xs">
                        <div class="p-2 rounded-lg bg-slate-50">
                            <span class="text-[9.5px] text-slate-400 uppercase font-semibold block">Board Model</span>
                            <span class="font-bold text-slate-800 font-mono truncate block text-[11px]" x-text="model || 'RouterBOARD'"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50">
                            <span class="text-[9.5px] text-slate-400 uppercase font-semibold block">RouterOS</span>
                            <span class="font-bold text-blue-600 font-mono text-[11px] block" x-text="rosVersion ? ('v' + rosVersion) : '--'"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50">
                            <span class="text-[9.5px] text-slate-400 uppercase font-semibold block">Architecture</span>
                            <span class="font-bold text-slate-800 font-mono text-[11px] block truncate" x-text="archName ? (cpuCount + ' CPU (' + archName + ')') : '--'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Right 6 Columns: FreeRADIUS Setup Script & CoA Config -->
        <div class="lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-terminal text-cyan-600 text-xs"></i>
                    <span>FreeRADIUS Terminal Setup Script</span>
                </span>
                <button type="button" 
                        @click="copyToClipboard(radiusScript, 'FreeRADIUS Terminal Script')"
                        class="px-2 py-0.5 rounded-md bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-[10.5px] shadow-2xs transition flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-copy text-[9px]"></i>
                    <span>Copy</span>
                </button>
            </div>

            <div class="p-2.5 bg-slate-900 flex-1">
                <pre class="text-[11px] font-mono text-cyan-200 whitespace-pre-wrap leading-tight overflow-y-auto max-h-[145px] p-2 bg-slate-950 rounded border border-slate-800" x-text="radiusScript"></pre>
            </div>

            <div class="px-3.5 py-1.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[10.5px] text-slate-500 font-mono">
                <span>AAA Service: <strong>PPP & Hotspot</strong></span>
                <span>CoA Listen: <strong class="text-emerald-700">Port 3799</strong></span>
            </div>
        </div>

    </div>

    <!-- 4. Lower Tables: Connected OLTs & IP Pools -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        
        <!-- Connected OLT Chassis Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-sitemap text-indigo-600 text-xs"></i>
                    <span>Connected OLT Chassis ({{ $router->olts->count() }})</span>
                </span>
                <a href="{{ route('tenant.network.olt.create') }}" class="text-[11px] font-bold text-cyan-600 hover:text-cyan-800">
                    + Add OLT
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th>OLT Name</th>
                            <th class="text-center">IP Address</th>
                            <th class="text-center">Status</th>
                            <th class="w-16 text-center no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700">
                        @forelse($router->olts as $olt)
                            <tr>
                                <td class="font-mono text-slate-400">{{ $loop->iteration }}</td>
                                <td class="font-semibold text-slate-900">
                                    <a href="{{ route('tenant.network.olt.show', $olt->id) }}" class="text-cyan-800 hover:underline">
                                        {{ $olt->name }}
                                    </a>
                                </td>
                                <td class="text-center font-mono text-slate-700">{{ $olt->ip_address }}</td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold {{ $olt->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ strtoupper($olt->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.network.olt.show', $olt->id) }}" class="px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10.5px] font-semibold hover:bg-cyan-100 border border-cyan-200">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-3 text-center text-slate-400 text-xs">No OLT chassis linked to this router gateway yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Allocated IP Pools Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-network-wired text-purple-600 text-xs"></i>
                    <span>Allocated IP Pools ({{ $ipPools->count() }})</span>
                </span>
                <a href="{{ route('tenant.network.ip-pools') }}" class="text-[11px] font-bold text-cyan-600 hover:text-cyan-800">
                    Manage Pools
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th>Pool Name</th>
                            <th class="text-center">Subnet CIDR / Range</th>
                            <th class="text-center">Type</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700">
                        @forelse($ipPools as $pool)
                            <tr>
                                <td class="font-mono text-slate-400">{{ $loop->iteration }}</td>
                                <td class="font-semibold text-slate-900">{{ $pool->name }}</td>
                                <td class="text-center font-mono text-slate-700">{{ $pool->subnet_cidr ?: $pool->range_start . ' - ' . $pool->range_end }}</td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ strtoupper($pool->pool_type) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-3 text-center text-slate-400 text-xs">No IP Pools allocated to this router yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
function mikrotikShowManager() {
    return {
        status: '{{ !$router->is_active ? "disabled" : ($router->status ?: "pending") }}',
        cpuLoad: {{ $router->status === 'online' && $router->cpu_load !== null ? (int)$router->cpu_load : 'null' }},
        freeMemory: {{ $router->status === 'online' && $router->free_memory ? (int)$router->free_memory : 0 }},
        totalMemory: {{ $router->status === 'online' && $router->total_memory ? (int)$router->total_memory : 0 }},
        rosVersion: '{{ $router->status === "online" ? ($router->ros_version ?: "") : "" }}',
        model: '{{ $router->status === "online" ? ($router->model ?: "") : "" }}',
        cpuCount: 1,
        archName: null,
        freeHdd: 0,
        totalHdd: 0,
        latency: 0,
        uptimeStr: '{{ $router->status === "online" ? ($router->uptime ?: "") : "" }}',
        lastSyncAt: '{{ $router->last_sync_at ? $router->last_sync_at->diffForHumans() : "Never synced" }}',
        lastError: @json($router->last_error ?: ''),
        isPolling: false,
        autoRefresh: {{ $router->status === 'online' ? 'true' : 'false' }},
        pollInterval: null,
        radiusScript: @json($radiusCliScript),
        toast: { show: false, message: '', type: 'success' },

        init: function() {
            var self = this;
            if (self.autoRefresh) {
                self.startStream();
            }
        },

        startStream: function() {
            var self = this;
            if (self.pollInterval) clearInterval(self.pollInterval);
            self.pollInterval = setInterval(function() {
                if (self.autoRefresh) {
                    self.pollTelemetry(false);
                }
            }, 5000);
        },

        toggleLiveStream: function() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.showToast('Real-time live monitoring started.', 'success');
                this.pollTelemetry(false);
                this.startStream();
            } else {
                if (this.pollInterval) clearInterval(this.pollInterval);
                this.showToast('Real-time live stream paused.', 'info');
            }
        },

        calcUsedMemPercent: function() {
            if (this.totalMemory > 0 && this.freeMemory >= 0) {
                var used = this.totalMemory - this.freeMemory;
                return Math.min(100, Math.max(0, Math.round((used / this.totalMemory) * 100)));
            }
            return 0;
        },

        calcUsedHddPercent: function() {
            if (this.totalHdd > 0 && this.freeHdd >= 0) {
                var used = this.totalHdd - this.freeHdd;
                return Math.min(100, Math.max(0, Math.round((used / this.totalHdd) * 100)));
            }
            return 0;
        },

        formatBytes: function(bytes) {
            if (!bytes || bytes <= 0) return '0 MB';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        formatUptime: function(raw) {
            if (!raw || raw === 'Live' || raw === 'null') return 'Online';
            var str = String(raw).trim();

            var w = (str.match(/(\d+)\s*w/i) || [])[1];
            var d = (str.match(/(\d+)\s*d/i) || [])[1];
            var h = (str.match(/(\d+)\s*h/i) || [])[1];
            var m = (str.match(/(\d+)\s*m(?!s)/i) || [])[1];
            var s = (str.match(/(\d+)\s*s/i) || [])[1];

            if (!h && !m && str.includes(':')) {
                var colonParts = str.split(':');
                if (colonParts.length === 3) {
                    h = colonParts[0].replace(/\D/g, '');
                    m = colonParts[1];
                    s = colonParts[2];
                }
            }

            var parts = [];
            if (w && parseInt(w) > 0) parts.push(w + (parseInt(w) > 1 ? ' Weeks' : ' Week'));
            if (d && parseInt(d) > 0) parts.push(d + (parseInt(d) > 1 ? ' Days' : ' Day'));
            if (h && parseInt(h) > 0) parts.push(h + (parseInt(h) > 1 ? ' Hours' : ' Hour'));
            if (m && parseInt(m) > 0) parts.push(m + ' Mins');
            if (s && parts.length < 2 && parseInt(s) > 0) parts.push(s + 's');

            if (parts.length > 0) {
                return parts.join(', ');
            }

            return str;
        },

        showToast: function(message, type) {
            this.toast.message = message;
            this.toast.type = type || 'success';
            this.toast.show = true;
            var self = this;
            setTimeout(function() {
                self.toast.show = false;
            }, 3000);
        },

        copyToClipboard: function(text, label) {
            if (!text) return;
            var self = this;
            navigator.clipboard.writeText(text).then(function() {
                self.showToast((label || 'Script') + ' copied to clipboard!', 'success');
            }).catch(function() {
                self.showToast('Could not copy to clipboard.', 'error');
            });
        },

        confirmReboot: function() {
            var self = this;
            var routerName = @json($router->name);

            var proceedReboot = async function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Sending Reboot Signal...',
                        html: "<div class='text-xs text-slate-600 mt-1'>Connecting to MikroTik RouterOS API and initiating system reboot...</div>",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function() {
                            Swal.showLoading();
                        },
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-slate-200 p-5'
                        }
                    });
                }

                try {
                    var response = await fetch('{{ route("tenant.network.mikrotik.reboot", $router->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    var data = await response.json();
                    if (data.success) {
                        self.status = 'offline';
                        self.uptimeStr = 'Rebooting...';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reboot Initiated!',
                                html: "<div class='text-xs text-slate-600 mt-1'>" + (data.message || 'Router is restarting.') + "</div>",
                                timer: 4500,
                                confirmButtonColor: '#0891b2',
                                customClass: {
                                    popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                                    confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                                }
                            });
                        } else {
                            self.showToast(data.message || 'Router reboot initiated.', 'success');
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Reboot Failed',
                                text: data.message || 'Could not send reboot signal to MikroTik.',
                                confirmButtonColor: '#e11d48',
                                customClass: {
                                    popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                                    confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                                }
                            });
                        } else {
                            self.showToast(data.message || 'Reboot failed.', 'error');
                        }
                    }
                } catch (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Failed to communicate with the server.',
                            confirmButtonColor: '#e11d48',
                            customClass: {
                                popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                                confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                            }
                        });
                    } else {
                        self.showToast('Network error while rebooting router.', 'error');
                    }
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reboot MikroTik Router?',
                    html: "<div class='text-xs text-slate-600 mt-1.5 leading-relaxed'>Are you sure you want to reboot router <strong class='text-slate-900 font-semibold'>" + routerName + "</strong>?<br><span class='text-rose-600 text-[11.5px] font-medium'>All active subscriber sessions (PPP & Hotspot) will momentarily disconnect.</span></div>",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Yes, Reboot Gateway',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        proceedReboot();
                    }
                });
            } else if (confirm('WARNING: Are you sure you want to reboot router ' + routerName + '? All active sessions will momentarily disconnect.')) {
                proceedReboot();
            }
        },

        pollTelemetry: async function(manual) {
            var self = this;
            if (self.isPolling) return;
            self.isPolling = true;

            try {
                var response = await fetch('{{ route("tenant.network.mikrotik.telemetry", $router->id) }}', {
                    headers: { 'Accept': 'application/json' }
                });
                var data = await response.json();
                if (data.success) {
                    self.status = 'online';
                    if (data.cpu_load !== null && data.cpu_load !== undefined) self.cpuLoad = data.cpu_load;
                    if (data.free_memory) self.freeMemory = data.free_memory;
                    if (data.total_memory) self.totalMemory = data.total_memory;
                    if (data.ros_version) self.rosVersion = data.ros_version;
                    if (data.model) self.model = data.model;
                    if (data.cpu_count) self.cpuCount = data.cpu_count;
                    if (data.architecture) self.archName = data.architecture;
                    if (data.free_hdd) self.freeHdd = data.free_hdd;
                    if (data.total_hdd) self.totalHdd = data.total_hdd;
                    if (data.latency_ms !== undefined) self.latency = data.latency_ms;
                    if (data.uptime) self.uptimeStr = data.uptime;
                    if (data.last_sync_at) self.lastSyncAt = data.last_sync_at;
                    self.lastError = '';

                    if (manual) {
                        self.showToast('Live telemetry synchronized successfully!', 'success');
                    }
                } else {
                    self.status = data.status || 'offline';
                    self.lastError = data.message || 'Router unreachable via API.';
                    if (data.last_sync_at) self.lastSyncAt = data.last_sync_at;

                    if (manual) {
                        self.showToast(data.message || 'Router probe failed.', 'error');
                    }
                }
            } catch (e) {
                self.status = 'offline';
                self.lastError = 'Network communication error with server.';
                console.error('Telemetry sampling error:', e);
            } finally {
                self.isPolling = false;
            }
        }
    };
}
</script>
@endsection
