@extends('owner.layouts.app')

@section('page-title', 'Carrier Network Engines Hub')

@section('content')
<div class="space-y-4" x-data="{ 
    scriptModal: false, 
    probeModal: false,
    activeTab: 'routers', 
    generatedScript: '', 
    loadingScript: false,
    probeData: {
        ip: '10.50.1.2',
        port: 8728,
        type: 'router',
        name: '',
        loading: false,
        result: null
    },
    runProbe() {
        this.probeData.loading = true;
        this.probeData.result = null;
        fetch('{{ route('owner.network-engines.probe-device') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                target_ip: this.probeData.ip,
                target_port: this.probeData.port,
                device_type: this.probeData.type,
                device_name: this.probeData.name
            })
        })
        .then(r => r.json())
        .then(data => {
            this.probeData.loading = false;
            this.probeData.result = data;
        })
        .catch(err => {
            this.probeData.loading = false;
            this.probeData.result = { success: false, status: 'ERROR', message: 'Probe failed: ' + err.message };
        });
    },
    quickProbe(ip, port, type, name) {
        this.probeData.ip = ip;
        this.probeData.port = port;
        this.probeData.type = type;
        this.probeData.name = name;
        this.probeModal = true;
        this.runProbe();
    }
}">

    <!-- Top Header Bar (Project Standard Sequence) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-base font-bold shadow-xs flex-shrink-0">
                <i class="fas fa-network-wired text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Carrier Network Engines Hub</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                        5-Engines Core
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Manage MikroTik routers, OLT PON access, FreeRADIUS AAA & WireGuard mesh</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('owner.network-engines.settings') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-sliders text-slate-500 text-[10px]"></i>
                <span>Global Settings</span>
            </a>
            <button type="button" @click="scriptModal = true" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-terminal text-[10px]"></i>
                <span>1-Click MikroTik Script</span>
            </button>
        </div>
    </div>

    <!-- Alert / Banner Feedback -->
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-2 shadow-xs">
            <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Top Statistics 4-Card Strip (Matching Tenants/Automation Standard Sequence) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        
        <!-- Metric 1: MikroTik Router Fleet -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-slate-500 uppercase tracking-wider block">MikroTik Routers</span>
                <span class="text-lg font-extrabold text-slate-900 tracking-tight font-mono">{{ number_format($totalRouters) }}</span>
                <span class="text-[10px] text-slate-400 block">
                    {{ $onlineRouters }} Online • {{ $offlineRouters }} Offline
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Metric 2: OLT Hardware & PON Capacity -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-purple-700 uppercase tracking-wider block">OLT PON Hardware</span>
                <span class="text-lg font-extrabold text-purple-700 tracking-tight font-mono">{{ number_format($totalOlts) }}</span>
                <span class="text-[10px] text-slate-400 block">
                    {{ number_format($totalOnus) }} ONUs • {{ $totalPonPorts }} PON Ports
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-100 flex-shrink-0">
                <i class="fas fa-microchip"></i>
            </div>
        </div>

        <!-- Metric 3: AAA FreeRADIUS Cluster -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-emerald-700 uppercase tracking-wider block">FreeRADIUS AAA</span>
                <span class="text-lg font-extrabold text-emerald-700 tracking-tight font-mono">Port 3799</span>
                <span class="text-[10px] text-slate-400 block font-mono">
                    {{ $settings['network_radius_master_host'] ?? '10.50.0.1' }}
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100 flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
        </div>

        <!-- Metric 4: WireGuard VPN Mesh -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-amber-700 uppercase tracking-wider block">WireGuard Tunnel</span>
                <span class="text-lg font-extrabold text-amber-700 tracking-tight font-mono">10.50.0.0/16</span>
                <span class="text-[10px] text-slate-400 block">
                    NAT/CGNAT Mesh Traversal
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm border border-amber-100 flex-shrink-0">
                <i class="fas fa-shield-virus"></i>
            </div>
        </div>

    </div>

    <!-- Main Navigation Tabs: Routers, OLTs, Architecture Flow, Audit Stream -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Tab Navigation Bar (Standard Sequence) -->
        <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button" @click="activeTab = 'routers'" :class="activeTab === 'routers' ? 'bg-white text-blue-600 border-slate-200 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent font-medium'" class="px-3 py-1.5 rounded-lg border text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-server text-[11px]"></i>
                    <span>MikroTik Routers</span>
                    <span class="px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-700 text-[10px] font-mono">{{ $totalRouters }}</span>
                </button>

                <button type="button" @click="activeTab = 'olts'" :class="activeTab === 'olts' ? 'bg-white text-blue-600 border-slate-200 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent font-medium'" class="px-3 py-1.5 rounded-lg border text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-microchip text-[11px]"></i>
                    <span>OLT Hardware</span>
                    <span class="px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-700 text-[10px] font-mono">{{ $totalOlts }}</span>
                </button>

                <button type="button" @click="activeTab = 'topology'" :class="activeTab === 'topology' ? 'bg-white text-blue-600 border-slate-200 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent font-medium'" class="px-3 py-1.5 rounded-lg border text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-diagram-project text-[11px]"></i>
                    <span>5-Engine Flow Topology</span>
                </button>

                <button type="button" @click="activeTab = 'audit'" :class="activeTab === 'audit' ? 'bg-white text-blue-600 border-slate-200 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent font-medium'" class="px-3 py-1.5 rounded-lg border text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-clock-rotate-left text-[11px]"></i>
                    <span>Audit Stream</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                </button>
            </div>

            <div class="text-[11px] text-slate-500 flex items-center gap-1.5 font-mono">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Telemetry Daemon Ready</span>
            </div>
        </div>

        <!-- TAB 1: MIKROTIK ROUTERS FLEET -->
        <div x-show="activeTab === 'routers'" class="p-4 space-y-3">
            <!-- Search & Filter Controls -->
            <form action="{{ route('owner.network-engines.index') }}" method="GET" class="flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[260px]">
                    <div class="relative flex-1 max-w-sm">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="router_search" value="{{ request('router_search') }}" placeholder="Search router name, IP, model, tenant..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-blue-500 shadow-xs">
                    </div>
                    <select name="router_status" onchange="this.form.submit()" class="py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 shadow-xs">
                        <option value="">All Statuses</option>
                        <option value="online" {{ request('router_status') == 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ request('router_status') == 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="error" {{ request('router_status') == 'error' ? 'selected' : '' }}>Error</option>
                        <option value="pending" {{ request('router_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="probeModal = true" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-200 flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-satellite-dish text-slate-500 text-[10px]"></i>
                        <span>TCP Probe</span>
                    </button>
                    <div class="text-[11px] text-slate-500 font-mono">
                        {{ $routers->count() }} of {{ $routers->total() }} Routers
                    </div>
                </div>
            </form>

            <!-- Routers Table -->
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-3.5 py-2.5">Router Name / Tenant</th>
                            <th class="px-3.5 py-2.5">Hardware & ROS</th>
                            <th class="px-3.5 py-2.5">Connection Mode</th>
                            <th class="px-3.5 py-2.5">Management IP & Port</th>
                            <th class="px-3.5 py-2.5">CPU Load & RAM</th>
                            <th class="px-3.5 py-2.5">Status & Handshake</th>
                            <th class="px-3.5 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($routers as $router)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-slate-900">{{ $router->name }}</div>
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="fas fa-building text-slate-400 text-[9px]"></i>
                                        <span>{{ $router->tenant->company_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5 font-mono">
                                    <div class="font-semibold text-slate-800">{{ $router->model ?? 'RouterBOARD' }}</div>
                                    <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 text-[9.5px]">
                                        {{ $router->ros_version ?? 'v7.x' }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($router->connection_type === 'hybrid')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            <i class="fas fa-bolt text-[8px]"></i> Hybrid (API + AAA)
                                        </span>
                                    @elseif($router->connection_type === 'radius')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fas fa-key text-[8px]"></i> RADIUS AAA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="fas fa-code text-[8px]"></i> RouterOS API
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 font-mono text-[11px]">
                                    <div class="font-bold text-slate-900">{{ $router->ip_address }}</div>
                                    <div class="text-[10px] text-slate-400">Port {{ $router->api_port }} {{ $router->use_ssl ? '(SSL)' : '' }}</div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if(!is_null($router->cpu_load))
                                        <div class="flex items-center gap-2">
                                            <div class="w-14 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full {{ $router->cpu_load > 80 ? 'bg-rose-500' : ($router->cpu_load > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $router->cpu_load) }}%"></div>
                                            </div>
                                            <span class="font-mono text-[10.5px] font-bold text-slate-700">{{ $router->cpu_load }}%</span>
                                        </div>
                                        <div class="text-[9.5px] text-slate-400 font-mono">RAM: {{ round(($router->free_memory ?? 0) / 1048576, 0) }} MB free</div>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Awaiting Telemetry</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($router->status === 'online')
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online
                                        </span>
                                    @elseif($router->status === 'offline')
                                        <span class="inline-flex items-center gap-1 text-rose-600 font-bold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Offline
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-amber-600 font-semibold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ ucfirst($router->status) }}
                                        </span>
                                    @endif
                                    <div class="text-[9.5px] text-slate-400 font-mono">{{ $router->last_ping_at ? $router->last_ping_at->diffForHumans() : 'Never' }}</div>
                                </td>
                                <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="quickProbe('{{ $router->ip_address }}', {{ $router->api_port }}, 'router', '{{ $router->name }}')" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition cursor-pointer" title="Test Live TCP Handshake">
                                            <i class="fas fa-satellite-dish"></i> Probe
                                        </button>
                                        <button type="button" @click="
                                            $refs.scriptTenant.value = '{{ $router->tenant->slug ?? '' }}';
                                            $refs.scriptRouterName.value = '{{ $router->name }}';
                                            $refs.scriptPort.value = '{{ $router->api_port }}';
                                            scriptModal = true;
                                        " class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition cursor-pointer" title="Get Bootstrap Script">
                                            <i class="fas fa-terminal"></i> Script
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 text-xs">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5 text-lg">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div class="font-bold text-slate-800 text-sm">No MikroTik Routers Connected</div>
                                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-1">
                                        When ISP tenants register their core routers or run the 1-Click Bootstrap Script, their live telemetry and connection status will appear here.
                                    </p>
                                    <button type="button" @click="scriptModal = true" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                                        <i class="fas fa-terminal text-[10px]"></i>
                                        <span>Generate 1-Click Bootstrap Script</span>
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($routers->hasPages())
                <div class="pt-2">
                    {{ $routers->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 2: OLT PON HARDWARE FLEET -->
        <div x-show="activeTab === 'olts'" class="p-4 space-y-3" style="display: none;">
            <!-- Search & Filter Controls -->
            <form action="{{ route('owner.network-engines.index') }}" method="GET" class="flex flex-wrap items-center justify-between gap-2.5">
                <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[260px]">
                    <div class="relative flex-1 max-w-sm">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="olt_search" value="{{ request('olt_search') }}" placeholder="Search OLT name, IP, brand, tenant..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-blue-500 shadow-xs">
                    </div>
                    <select name="olt_brand" onchange="this.form.submit()" class="py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 focus:ring-1 focus:ring-blue-500 shadow-xs">
                        <option value="">All Brands</option>
                        <option value="vsol" {{ request('olt_brand') == 'vsol' ? 'selected' : '' }}>V-SOL</option>
                        <option value="bdcom" {{ request('olt_brand') == 'bdcom' ? 'selected' : '' }}>BDCOM</option>
                        <option value="huawei" {{ request('olt_brand') == 'huawei' ? 'selected' : '' }}>Huawei</option>
                        <option value="zte" {{ request('olt_brand') == 'zte' ? 'selected' : '' }}>ZTE</option>
                        <option value="fiberhome" {{ request('olt_brand') == 'fiberhome' ? 'selected' : '' }}>Fiberhome</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="probeModal = true; probeData.type = 'olt'; probeData.port = 23;" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-200 flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-satellite-dish text-slate-500 text-[10px]"></i>
                        <span>Probe Port</span>
                    </button>
                    <div class="text-[11px] text-slate-500 font-mono">
                        {{ $olts->count() }} of {{ $olts->total() }} OLTs
                    </div>
                </div>
            </form>

            <!-- OLT Table -->
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-3.5 py-2.5">OLT Name / Tenant</th>
                            <th class="px-3.5 py-2.5">Brand & Model</th>
                            <th class="px-3.5 py-2.5">Management IP & SNMP</th>
                            <th class="px-3.5 py-2.5">PON Ports</th>
                            <th class="px-3.5 py-2.5">ONU Capacity & Health</th>
                            <th class="px-3.5 py-2.5">Status & Sync</th>
                            <th class="px-3.5 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($olts as $olt)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-slate-900">{{ $olt->name }}</div>
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="fas fa-building text-slate-400 text-[9px]"></i>
                                        <span>{{ $olt->tenant->company_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="inline-block font-bold text-slate-800 uppercase px-1.5 py-0.5 rounded bg-slate-100 text-[10.5px]">
                                        {{ strtoupper($olt->brand) }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $olt->model ?? 'Chassis/Box' }}</div>
                                </td>
                                <td class="px-3.5 py-2.5 font-mono text-[11px]">
                                    <div class="font-bold text-slate-900">{{ $olt->ip_address }}</div>
                                    <div class="text-[10px] text-slate-400">SNMP Port: {{ $olt->snmp_port }} ({{ $olt->snmp_version }})</div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-mono font-bold text-slate-800">{{ $olt->pon_ports_count }} Ports</span>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <div class="flex items-center gap-2 font-mono text-[11px]">
                                        <span class="text-slate-900 font-bold">{{ $olt->total_onus_count }} Total</span>
                                        <span class="text-emerald-600 font-semibold">({{ $olt->online_onus_count }} Online)</span>
                                    </div>
                                    @if($olt->los_onus_count > 0)
                                        <div class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 bg-rose-50 px-1 py-0.2 rounded mt-0.5">
                                            <i class="fas fa-link-slash text-[8px]"></i> {{ $olt->los_onus_count }} LOS Cut
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($olt->status === 'online')
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online
                                        </span>
                                    @elseif($olt->status === 'offline')
                                        <span class="inline-flex items-center gap-1 text-rose-600 font-bold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Offline
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-amber-600 font-semibold text-[10.5px]">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ ucfirst($olt->status) }}
                                        </span>
                                    @endif
                                    <div class="text-[9.5px] text-slate-400 font-mono">{{ $olt->last_sync_at ? $olt->last_sync_at->diffForHumans() : 'Never synced' }}</div>
                                </td>
                                <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                    <button type="button" @click="quickProbe('{{ $olt->ip_address }}', {{ $olt->telnet_port ?: 23 }}, 'olt', '{{ $olt->name }}')" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold transition cursor-pointer">
                                        <i class="fas fa-satellite-dish"></i> Probe
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 text-xs">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5 text-lg">
                                        <i class="fas fa-microchip"></i>
                                    </div>
                                    <div class="font-bold text-slate-800 text-sm">No OLT Devices Configured</div>
                                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-1">
                                        V-SOL, BDCOM, Huawei and ZTE OLTs configured by tenant ISPs will appear here with real-time optical power and LOS telemetry.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($olts->hasPages())
                <div class="pt-2">
                    {{ $olts->links() }}
                </div>
            @endif
        </div>

        <!-- TAB 3: 5-ENGINE FLOW TOPOLOGY -->
        <div x-show="activeTab === 'topology'" class="p-5 space-y-4" style="display: none;">
            <div class="max-w-3xl mx-auto space-y-4">
                <div class="border-b border-slate-200 pb-2">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Enterprise 5-Engine Connectivity Topology</h3>
                    <p class="text-[11px] text-slate-500">Overview of how the Cloud SaaS Engine connects securely with client ISP private networks</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    
                    <!-- Engine 1 & 2 -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <div class="flex items-center gap-2 text-blue-700 font-bold text-xs">
                            <span class="w-5 h-5 rounded-md bg-blue-100 flex items-center justify-center text-[10px]">1</span>
                            <span>RouterOS Core Engine</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Communicates with MikroTik via API socket (Port 8728) or SSL (Port 8729). Periodically checks CPU load, memory utilization, and synchronizes subscriber queues.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs">
                            <span class="w-5 h-5 rounded-md bg-emerald-100 flex items-center justify-center text-[10px]">2</span>
                            <span>FreeRADIUS AAA Engine</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Authenticates PPPoE subscribers directly from the cloud database. Receives 5-minute interim accounting and transmits RFC 3576 CoA/PoD packets (Port 3799) to disconnect or rate-limit overdue users.
                        </p>
                    </div>

                    <!-- Engine 3 & 4 -->
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <div class="flex items-center gap-2 text-purple-700 font-bold text-xs">
                            <span class="w-5 h-5 rounded-md bg-purple-100 flex items-center justify-center text-[10px]">3</span>
                            <span>OLT PON Access Engine</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Interrogates V-SOL, BDCOM, Huawei, and ZTE hardware via SNMP v2c (Port 161). Automatically checks Optical Power (dBm) and triggers alerts if fiber degradation or Loss of Signal (LOS) occurs.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <div class="flex items-center gap-2 text-amber-700 font-bold text-xs">
                            <span class="w-5 h-5 rounded-md bg-amber-100 flex items-center justify-center text-[10px]">4</span>
                            <span>WireGuard NAT Mesh Engine</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Provides point-to-point encrypted tunnels (UDP 51820) so routers behind private NAT/CGNAT can communicate with the SaaS platform without requiring dedicated public real IPs.
                        </p>
                    </div>

                </div>

                <!-- Engine 5: Automation -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-rose-700 font-bold text-xs">
                            <span class="w-5 h-5 rounded-md bg-rose-100 flex items-center justify-center text-[10px]">5</span>
                            <span>Monitoring & Automation Engine (Async Queues)</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">
                            Heavy background scans (SNMP Walk, bulk ping, CoA triggers) execute asynchronously in dedicated Laravel Queue workers with zero web latency.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 4: NOC AUDIT STREAM -->
        <div x-show="activeTab === 'audit'" class="p-4 space-y-3" style="display: none;">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-800">Immutable Network Audit Trail (Latest Events)</h3>
                <a href="{{ route('owner.network-engines.audit-logs') }}" class="text-blue-600 hover:text-blue-700 text-xs font-semibold">
                    View Full Audit Hub <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2">Timestamp</th>
                            <th class="px-3 py-2">Device Type / Target</th>
                            <th class="px-3 py-2">Action Executed</th>
                            <th class="px-3 py-2">Operator / IP</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Response Summary</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($recentAuditLogs as $log)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-3 py-2 font-mono text-[10.5px] text-slate-500">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 text-[10px] font-semibold uppercase text-slate-700 mr-1">
                                        {{ $log->device_type }}
                                    </span>
                                    <span class="font-bold text-slate-800">{{ $log->device_name ?? 'System' }}</span>
                                </td>
                                <td class="px-3 py-2 font-mono font-bold text-blue-700 text-[11px]">
                                    {{ $log->action }}
                                </td>
                                <td class="px-3 py-2 text-[11px]">
                                    <div class="text-slate-800 font-medium">{{ $log->user->name ?? 'Platform Daemon' }}</div>
                                    <div class="text-[9.5px] text-slate-400 font-mono">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    @if($log->status === 'success')
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            SUCCESS
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            FAILED
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            {{ strtoupper($log->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-600 text-[11px] truncate max-w-xs">
                                    {{ $log->response_summary ?? 'Operation logged cleanly' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                    No audit actions recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 1. LIVE DIAGNOSTIC PROBE MODAL -->
    <div x-show="probeModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
         x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-4 sm:p-5 space-y-3.5" @click.outside="probeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Network Diagnostic Probe</h3>
                        <p class="text-[10px] text-slate-500">Live TCP socket handshake & latency measurement</p>
                    </div>
                </div>
                <button type="button" @click="probeModal = false" class="p-1 text-slate-400 hover:text-slate-700 rounded transition cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Target IP / Host</label>
                        <input type="text" x-model="probeData.ip" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Port</label>
                        <input type="number" x-model="probeData.port" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div class="text-[10.5px] text-slate-500 font-mono">
                        Target: <span class="font-bold text-slate-800" x-text="probeData.name || probeData.ip"></span>
                    </div>
                    <button type="button" @click="runProbe()" :disabled="probeData.loading" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <i class="fas fa-paper-plane text-[10px]" :class="probeData.loading ? 'animate-spin' : ''"></i>
                        <span x-text="probeData.loading ? 'Probing...' : 'Send Probe'"></span>
                    </button>
                </div>

                <!-- Probe Result Display -->
                <template x-if="probeData.result">
                    <div class="mt-3 p-3 rounded-xl border text-xs space-y-1.5"
                         :class="probeData.result.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
                        <div class="flex items-center justify-between font-bold">
                            <span class="flex items-center gap-1.5">
                                <i class="fas" :class="probeData.result.success ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600'"></i>
                                <span x-text="probeData.result.status"></span>
                            </span>
                            <span class="font-mono text-[11px]" x-show="probeData.result.latency_ms" x-text="probeData.result.latency_ms + ' ms'"></span>
                        </div>
                        <p class="text-[11px] leading-relaxed" x-text="probeData.result.message"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- 2. 1-CLICK MIKROTIK CLI GENERATOR MODAL -->
    <div x-show="scriptModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
         x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full p-4 sm:p-6 space-y-4" @click.outside="scriptModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-terminal text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">1-Click MikroTik CLI Bootstrap Generator</h3>
                        <p class="text-[11px] text-slate-500">Generates least-privilege security group, API service, RADIUS AAA & WireGuard</p>
                    </div>
                </div>
                <button type="button" @click="scriptModal = false" class="p-1 text-slate-400 hover:text-slate-700 rounded transition cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Generator Form -->
            <form @submit.prevent="
                loadingScript = true;
                fetch('{{ route('owner.network-engines.generate-script') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        tenant_slug: $refs.scriptTenant.value,
                        router_name: $refs.scriptRouterName.value,
                        username: $refs.scriptUser.value,
                        password: $refs.scriptPass.value,
                        api_port: $refs.scriptPort.value,
                        radius_secret: $refs.scriptSecret.value,
                        wireguard_ip: $refs.scriptWgIp.value,
                        wireguard_key: $refs.scriptWgKey.value,
                        ros_version: $refs.scriptRosVer.value
                    })
                })
                .then(r => r.json())
                .then(data => {
                    generatedScript = data.script;
                    loadingScript = false;
                })
                .catch(() => { loadingScript = false; alert('Error generating script'); })
            " class="space-y-3">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Target Tenant</label>
                        <select x-ref="scriptTenant" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                            @foreach($tenants as $t)
                                <option value="{{ $t->slug }}">{{ $t->company_name }} ({{ $t->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Router Name</label>
                        <input type="text" x-ref="scriptRouterName" value="Core-Router-01" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">API Username</label>
                        <input type="text" x-ref="scriptUser" value="somitysoft_api" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">API Password</label>
                        <input type="text" x-ref="scriptPass" value="SecureApiPass{{ rand(1000, 9999) }}!" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">API Port / ROS Version</label>
                        <div class="flex gap-2">
                            <input type="number" x-ref="scriptPort" value="8728" class="w-24 py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            <select x-ref="scriptRosVer" class="flex-1 py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="v7">RouterOS v7 (WireGuard Native)</option>
                                <option value="v6">RouterOS v6</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">RADIUS Shared Secret</label>
                        <input type="text" x-ref="scriptSecret" value="somitysoft_radius_secret" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">WireGuard Tunnel IP</label>
                        <input type="text" x-ref="scriptWgIp" value="10.50.1.2" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">WireGuard Client Private Key</label>
                        <input type="text" x-ref="scriptWgKey" value="CLIENT_PRIVATE_KEY_HERE" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="submit" :disabled="loadingScript" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                        <span x-text="loadingScript ? 'Generating...' : 'Generate CLI Script'"></span>
                    </button>
                </div>
            </form>

            <!-- Script Output Block -->
            <div x-show="generatedScript" class="space-y-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Generated RouterOS CLI Commands:</span>
                    <button type="button" @click="navigator.clipboard.writeText(generatedScript); alert('Copied to clipboard!');" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-copy"></i> Copy Script
                    </button>
                </div>
                <pre class="p-3 bg-slate-900 text-emerald-400 font-mono text-[10.5px] rounded-lg overflow-x-auto max-h-56 leading-relaxed select-all" x-text="generatedScript"></pre>
                <p class="text-[10px] text-slate-500 italic">Copy and paste directly into MikroTik WinBox Terminal.</p>
            </div>

        </div>
    </div>

</div>
@endsection
