@extends('tenant.layouts.app')

@section('title', ($olt->name ?? 'OLT Chassis') . ' - 16-PON Linear Carrier Panel')

@section('content')
<div class="space-y-4" x-data="oltShowManager()">

    <!-- Toast Notification Overlay -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl border text-xs font-semibold"
         :class="toast.type === 'success' ? 'bg-emerald-900/90 border-emerald-700 text-white' : 'bg-rose-900/90 border-rose-700 text-white'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-triangle text-rose-400'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- Top Action & Navigation Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.network.olt') }}" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Back to OLT Directory">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200/60 flex items-center justify-center text-blue-600 shadow-2xs">
                <i class="fas fa-network-wired text-lg"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-extrabold text-slate-900 tracking-tight">{{ $olt->name }}</h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-extrabold {{ $olt->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $olt->status === 'online' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                        <span>{{ strtoupper($olt->status) }}</span>
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $olt->vendor ?? 'DN OPTIC' }} {{ $olt->model ?? 'DN08EP-EX-4S+' }}
                    </span>
                    @if($olt->router)
                        <a href="{{ route('tenant.network.mikrotik.show', $olt->router->id) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10.5px] font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition" title="Parent MikroTik Router">
                            <i class="fas fa-server text-[9px] text-indigo-500"></i>
                            <span>Router: {{ $olt->router->name }} ({{ $olt->router->ip_address }})</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- All Client ONUs Link -->
            <a href="{{ route('tenant.network.onu', ['olt_id' => $olt->id]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-satellite-dish text-slate-500 text-xs"></i>
                <span>All Client ONUs</span>
            </a>

            <!-- Sync Button -->
            <button type="button" 
                    @click="triggerSync({{ $olt->id }})" 
                    :disabled="syncing" 
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="fas fa-sync-alt text-[10px]" :class="{ 'fa-spin': syncing }"></i>
                <span x-text="syncing ? 'Syncing...' : 'Sync OLT'"></span>
            </button>

            <!-- Edit OLT Button -->
            <a href="{{ route('tenant.network.olt.edit', $olt->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition flex items-center gap-1.5">
                <i class="fas fa-cog text-[10px]"></i>
                <span>Configure OLT</span>
            </a>
        </div>
    </div>

    <!-- ================= DYNAMIC CARRIER OLT FRONT PANEL ================= -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Panel Header -->
        <div class="px-4 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900">{{ $olt->name }}</span>
            </div>

            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                    Total Ports: <strong class="font-mono text-slate-900">{{ $totalPorts }}</strong>
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Online: <strong class="font-mono text-emerald-800">{{ $onlinePortsCount }}</strong></span>
                </span>
            </div>
        </div>

        <!-- Chassis Front Bezel Body (Single Horizontal Line for PONs) -->
        <div class="p-4 sm:p-6 bg-slate-50/50 flex flex-col items-center justify-center overflow-x-auto">
            <div class="w-full max-w-6xl {{ $totalPorts > 8 ? 'min-w-[980px]' : 'min-w-[580px]' }} bg-white border border-slate-200 rounded-2xl p-6 shadow-xs relative">
                
                <!-- PON Ports in Single Horizontal Line -->
                <div class="flex items-center justify-between gap-2 py-1 px-2">
                    @foreach($ponList as $p)
                        @php
                            $isOnline = $p['is_online'];
                            $hasOnus = $p['has_onus'];
                        @endphp
                        <a href="{{ $hasOnus ? route('tenant.network.onu', ['olt_id' => $olt->id, 'pon_port' => $p['full_port']]) : '#' }}" 
                           class="group flex flex-col items-center text-center p-1.5 rounded-xl transition duration-150 flex-1 min-w-[54px] {{ $isOnline ? 'hover:bg-emerald-50/50 cursor-pointer hover:-translate-y-0.5' : ($hasOnus ? 'hover:bg-slate-50 cursor-pointer hover:-translate-y-0.5' : 'opacity-40 cursor-default') }}"
                           title="{{ $p['title'] }} ({{ $p['full_port'] }}): {{ $p['online'] }} Online / {{ $p['offline'] }} Offline - Click to view ONUs">
                            
                            <!-- SFP Port Icon (Concentric rounded box matching chassis hardware) -->
                            <div class="w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all bg-white shadow-2xs {{ $isOnline ? 'border-emerald-500 text-emerald-600 shadow-emerald-50 group-hover:ring-2 group-hover:ring-emerald-300' : 'border-slate-300 text-slate-300' }}">
                                @if($isOnline)
                                    <div class="w-6 h-6 rounded-lg border border-emerald-500 flex items-center justify-center bg-emerald-50/30">
                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-700 ring-2 ring-emerald-300"></div>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-lg border border-slate-300/80 flex items-center justify-center bg-slate-50/50">
                                        <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                                    </div>
                                @endif
                            </div>

                            <!-- Port Name e.g. PON 01 -->
                            <span class="text-[10px] font-mono font-extrabold mt-1.5 whitespace-nowrap {{ $isOnline ? 'text-slate-800' : 'text-slate-400' }}">
                                {{ $p['title'] }}
                            </span>

                            <!-- Online (Green) / Offline (Red) Counter -->
                            <div class="text-[10px] font-mono font-bold mt-0.5 leading-tight whitespace-nowrap">
                                @if($hasOnus)
                                    <span class="text-emerald-600 font-extrabold">{{ $p['online'] }}</span><span class="text-slate-300">/</span><span class="{{ $p['offline'] > 0 ? 'text-rose-600 font-extrabold' : 'text-slate-400' }}">{{ $p['offline'] }}</span>
                                @else
                                    <span class="text-slate-300 font-normal">0/0</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- ================= 2-COLUMN LOWER ROW (5 COLUMNS TELEMETRY + 7 COLUMNS DEVICE SPECS) ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Left 5 Columns: Live CPU, Memory & Temperature Telemetry Directly from Device -->
        <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Device Live Telemetry</span>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live</span>
                </span>
            </div>

            @php
                $cpuVal = $olt->cpu_load ? (int)$olt->cpu_load : 0;
                $memVal = $olt->memory_usage ? (int)$olt->memory_usage : 0;
                $tempVal = $olt->temperature ? (number_format($olt->temperature, 1) . '°C') : 'Normal';
            @endphp

            <div class="grid grid-cols-3 divide-x divide-slate-100 pt-3.5 pb-1 items-center">
                <!-- 1. CPU Gauge -->
                <div class="flex flex-col items-center text-center px-1">
                    <div class="relative w-14 h-14 flex items-center justify-center">
                        <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-blue-500" stroke-dasharray="{{ $cpuVal > 0 ? $cpuVal : 2 }}, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="absolute text-xs font-extrabold text-slate-800 font-mono">{{ $cpuVal > 0 ? $cpuVal . '%' : 'N/A' }}</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 mt-2 block">CPU Load</span>
                </div>

                <!-- 2. Memory Gauge -->
                <div class="flex flex-col items-center text-center px-1">
                    <div class="relative w-14 h-14 flex items-center justify-center">
                        <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-indigo-500" stroke-dasharray="{{ $memVal > 0 ? $memVal : 2 }}, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <span class="absolute text-xs font-extrabold text-slate-800 font-mono">{{ $memVal > 0 ? $memVal . '%' : 'N/A' }}</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 mt-2 block">Memory</span>
                </div>

                <!-- 3. Temperature -->
                <div class="flex flex-col items-center text-center px-1">
                    <div class="w-14 h-14 flex items-center justify-center text-emerald-500">
                        <i class="fas fa-temperature-half text-3xl"></i>
                    </div>
                    <div class="text-sm font-extrabold text-slate-800 font-mono tracking-tight mt-0.5">
                        {{ $tempVal }}
                    </div>
                    <span class="text-xs font-semibold text-slate-500 block mt-0.5">Temperature</span>
                </div>
            </div>
        </div>

        <!-- Right 7 Columns: Real Device Hardware Specifications & System Profile -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Device System Specifications</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10.5px] font-bold {{ $olt->status === 'online' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ strtoupper($olt->status) }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Device Model</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->model ?? 'EPON OLT' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Vendor / Brand</span>
                    <span class="font-bold text-slate-800">{{ $olt->vendor ?? 'Carrier OLT' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Serial Number (SN)</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->serial_number ?? 'AF2802-2604000011' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Hardware Version</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->hardware_version ?? 'V1.1' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Firmware Version</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->firmware_version ?? 'V3.4.66' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">MAC Address</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->mac_address ?? '48:35:2E:1C:7F:13' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">IP Address</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->ip_address }}:{{ $olt->web_port ?? 80 }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">System Uptime</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $olt->uptime ?? '1d 9h 21m' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">License Limit</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Time limited</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">License Time Remaining</span>
                    <span class="font-bold text-amber-700 font-mono text-xs">02 days 18 hours</span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function oltShowManager() {
    return {
        syncing: false,
        toast: { show: false, message: '', type: 'success' },

        showToast: function(message, type) {
            this.toast.message = message;
            this.toast.type = type || 'success';
            this.toast.show = true;
            var self = this;
            setTimeout(function() {
                self.toast.show = false;
            }, 4000);
        },

        triggerSync: async function(oltId) {
            this.syncing = true;
            var self = this;
            try {
                var response = await fetch('/admin/network/olt/' + oltId + '/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                var data = await response.json();
                if (data.success) {
                    self.showToast(data.message || 'OLT synchronized successfully!', 'success');
                    setTimeout(function() { window.location.reload(); }, 800);
                } else {
                    self.showToast(data.message || 'Failed to sync OLT.', 'error');
                }
            } catch (e) {
                self.showToast('Network error while syncing OLT.', 'error');
            } finally {
                self.syncing = false;
            }
        }
    };
}
</script>
@endsection
