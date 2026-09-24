@extends('tenant.layouts.app')

@section('title', 'ONU & Optical Terminals - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="onuManager()" @scroll.window="activeMenuOnu = null" @resize.window="activeMenuOnu = null">
    
    <!-- Floating Global Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-xl border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-satellite-dish"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">ONU &amp; Optical Terminals Directory</h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Link to OLT Gateways -->
            <a href="{{ route('tenant.network.olt') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-network-wired text-slate-400 text-xs"></i>
                <span>View OLTs</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total ONUs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">{{ number_format($totalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-boxes"></i>
            </div>
        </div>

        <!-- Card 2: Online Active -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Online Active</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($onlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Offline -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">Offline</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700">{{ number_format($offlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>

        <!-- Card 4: Critical Signal -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">Critical Optical</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-600">{{ number_format($criticalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-radiation"></i>
            </div>
        </div>

        <!-- Card 5: Warning Signal -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Warning Signal</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700">{{ number_format($warningOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: Online Ratio -->
        @php
            $onlineRatio = ($totalOnus > 0) ? round(($onlineOnus / $totalOnus) * 100, 1) : 0;
        @endphp
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-600">Online Ratio</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-700">{{ $onlineRatio }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.onu') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Name, MAC, Model, Port..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- OLT Filter -->
            <div>
                <select name="olt_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All OLTs</option>
                    @foreach($olts as $o)
                        <option value="{{ $o->id }}" {{ (string)request('olt_id') === (string)$o->id ? 'selected' : '' }}>
                            {{ $o->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Type Filter (EPON / GPON / XPON) -->
            <div>
                <select name="onu_type" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Types</option>
                    <option value="EPON" {{ request('onu_type') === 'EPON' ? 'selected' : '' }}>EPON</option>
                    <option value="GPON" {{ request('onu_type') === 'GPON' ? 'selected' : '' }}>GPON</option>
                    <option value="XPON" {{ request('onu_type') === 'XPON' ? 'selected' : '' }}>XPON</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Statuses</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>🟢 Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>🔴 Offline</option>
                    <option value="good" {{ request('status') === 'good' ? 'selected' : '' }}>Good (0 to -20 dBm)</option>
                    <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>Warning (-21 to -26 dBm)</option>
                    <option value="critical" {{ request('status') === 'critical' ? 'selected' : '' }}>Critical (-27 to -32 dBm)</option>
                </select>
            </div>

            <!-- Per Page Filter -->
            <div>
                @php $perPageVal = (int)request('per_page', 30); @endphp
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="15" {{ $perPageVal === 15 ? 'selected' : '' }}>15 / page</option>
                    <option value="30" {{ $perPageVal === 30 ? 'selected' : '' }}>30 / page</option>
                    <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
                    <option value="200" {{ $perPageVal === 200 ? 'selected' : '' }}>200 / page</option>
                </select>
            </div>

            <!-- Filter & Reset Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.network.onu') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strictly UI_DESIGN.md Compliant - Single Line, NO BOLD TEXT) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>Master ONU / ONT Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $onus->total() }} Terminals
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>OLT Name</th>
                        <th class="text-center">PON:ONU ID</th>
                        <th>Client / ONU Name</th>
                        <th>MAC Address</th>
                        <th class="text-center">Type</th>
                        <th>Model</th>
                        <th class="text-center">Optical Power</th>
                        <th class="text-center">Distance</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($onus as $index => $onu)
                        @php
                            $sig = $onu->signal_quality;
                            $onuType = strtoupper($onu->onu_type ?: 'EPON');
                            $typeBadge = match($onuType) {
                                'XPON' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'GPON' => 'bg-blue-50 text-blue-700 border-blue-200',
                                default => 'bg-cyan-50 text-cyan-700 border-cyan-200'
                            };
                            $onuPayload = [
                                'id' => $onu->id,
                                'olt_id' => $onu->olt_id,
                                'olt_name' => $onu->olt->name ?? 'OLT',
                                'olt_ip' => $onu->olt->ip_address ?? '',
                                'pon_port' => $onu->pon_port,
                                'onu_id' => $onu->onu_id,
                                'name' => $onu->name,
                                'desc' => $onu->desc,
                                'mac_address' => $onu->mac_address,
                                'vendor' => $onu->vendor ?: 'VSOL',
                                'model' => $onu->model ?: '1GE ONU',
                                'onu_type' => $onuType,
                                'vlan_id' => $onu->vlan_id,
                                'vlan_mode' => $onu->vlan_mode ?: 'tag',
                                'service_mode' => $onu->service_mode ?: 'bridge',
                                'pppoe_username' => $onu->pppoe_username,
                                'pppoe_password' => $onu->pppoe_password,
                                'lan1_state' => $onu->lan1_state ?: 'enable',
                                'wifi_ssid' => $onu->wifi_ssid,
                                'wifi_password' => $onu->wifi_password,
                                'catv_state' => $onu->catv_state ?: 'enable',
                                'bandwidth_profile' => $onu->bandwidth_profile,
                                'status' => $onu->status,
                                'rx_power_dbm' => $onu->rx_power_dbm,
                                'tx_power_dbm' => $onu->tx_power_dbm,
                                'distance_m' => $onu->distance_m,
                                'signal_label' => $sig['label'],
                                'signal_badge' => $sig['badge'],
                                'updated_at' => $onu->updated_at ? $onu->updated_at->diffForHumans() : 'Recently'
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $onus->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if($onu->status === 'online')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Offline</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. OLT Name -->
                            <td class="text-cyan-800">
                                <a href="{{ route('tenant.network.olt.show', ['olt' => $onu->olt_id, 'pon_port' => $onu->pon_port]) }}" class="hover:underline">
                                    {{ $onu->olt->name ?? 'OLT' }}
                                </a>
                            </td>

                            <!-- 4. PON:ONU ID -->
                            <td class="text-center font-mono text-purple-700">
                                <span class="inline-flex px-1.5 py-0.5 rounded bg-purple-50 border border-purple-200 text-[10px]">
                                    {{ $onu->pon_port }}:#{{ $onu->onu_id }}
                                </span>
                            </td>

                            <!-- 5. Client / ONU Name -->
                            <td class="text-slate-800 max-w-[150px] truncate" id="onu-name-{{ $onu->id }}" title="{{ $onu->name }}">
                                <button type="button" 
                                        @click="openManageModal({{ Js::from($onuPayload) }}, 'config')" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $onu->name ?: 'Unnamed ONU' }}
                                </button>
                            </td>

                            <!-- 6. MAC Address -->
                            <td class="font-mono text-slate-700">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $onu->mac_address ?: '--' }}</span>
                                    @if($onu->mac_address)
                                        <button type="button" 
                                                @click="copyToClipboard('{{ $onu->mac_address }}', 'MAC Address')" 
                                                class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                                title="Copy MAC">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    @endif
                                </span>
                            </td>

                            <!-- 7. Type -->
                            <td class="text-center font-mono">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] border {{ $typeBadge }}">
                                    {{ $onuType }}
                                </span>
                            </td>

                            <!-- 8. Model -->
                            <td class="font-mono text-slate-700">
                                {{ $onu->model ?: '1GE ONU' }}
                            </td>

                            <!-- 9. Optical Power -->
                            <td class="text-center font-mono">
                                @if($onu->rx_power_dbm !== null)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] border {{ $sig['badge'] }}">
                                        {{ $onu->rx_power_dbm }} dBm
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px]">--</span>
                                @endif
                            </td>

                            <!-- 10. Distance -->
                            <td class="text-center font-mono text-slate-700">
                                {{ $onu->distance_m !== null ? $onu->distance_m . 'm' : '--' }}
                            </td>

                            <!-- 11. Actions (3-Dot Action Button triggering Floating Dropdown) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($onuPayload) }}, $event)" 
                                        class="onu-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-satellite-dish"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No ONUs Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Adjust search filters or synchronize your OLT gateways to discover and link client ONUs.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.network.olt') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-network-wired text-[10px]"></i>
                                            <span>View OLT Gateways</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($onus->hasPages() || $onus->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $onus->firstItem() ?? 0 }} to {{ $onus->lastItem() ?? 0 }} of {{ $onus->total() }} results
                </div>
                <div>
                    {{ $onus->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenuOnu !== null" 
         @click.away="activeMenuOnu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-48 bg-white rounded-lg shadow-xl border border-slate-200 py-0.5 text-xs divide-y divide-slate-100 font-normal"
         style="display: none;">
        
        <!-- Group 1: Configuration & Telemetry -->
        <div class="py-0.5">
            <button type="button" 
                    @click="configureFromMenu('config')" 
                    class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-sliders-h text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>Configure Settings</span>
            </button>

            <button type="button" 
                    @click="configureFromMenu('optical')" 
                    class="w-full px-2.5 py-1 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-signal text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>Optical Telemetry</span>
            </button>
        </div>

        <!-- Group 2: Remote Operations -->
        <div class="py-0.5">
            <button type="button" 
                    @click="rebootFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-amber-50 text-amber-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-power-off text-amber-600 w-3.5 text-center text-[10px]"></i>
                <span>Reboot Terminal</span>
            </button>

            <button type="button" 
                    @click="factoryResetFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="fas fa-rotate-left text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Factory Reset ONU</span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: ONU Configuration Modal -->
    <div x-show="manageModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeManageModal()" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <template x-if="manageModal.onu">
                <div>
                    <!-- Soft Natural Header -->
                    <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                                <i class="fas fa-satellite-dish"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-semibold text-slate-800">Configure ONU Terminal</h3>
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono uppercase bg-slate-100 text-slate-700 border border-slate-200"
                                          x-text="manageModal.onu.onu_type"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-500 font-normal">
                                    <span x-text="manageModal.onu.olt_name"></span> &bull; 
                                    Port <span class="font-mono text-cyan-700" x-text="manageModal.onu.pon_port + ':#' + manageModal.onu.onu_id"></span> &bull;
                                    MAC <span class="font-mono text-slate-600" x-text="manageModal.onu.mac_address"></span>
                                </p>
                            </div>
                        </div>
                        
                        <button type="button" 
                                @click="closeManageModal()" 
                                class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Minimal Tab Bar -->
                    <div class="flex items-center gap-2 px-4 pt-2 bg-slate-50 border-b border-slate-200">
                        <button type="button" 
                                @click="manageModal.activeTab = 'config'" 
                                class="px-3 py-1.5 text-xs font-medium border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'config' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-sliders-h text-[10px] mr-1"></i>
                            <span>Configuration</span>
                        </button>

                        <button type="button" 
                                @click="manageModal.activeTab = 'subscriber'" 
                                class="px-3 py-1.5 text-xs font-medium border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'subscriber' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-user text-[10px] mr-1"></i>
                            <span>Subscriber Info</span>
                        </button>

                        <button type="button" 
                                @click="manageModal.activeTab = 'optical'" 
                                class="px-3 py-1.5 text-xs font-medium border-b-2 transition cursor-pointer"
                                :class="manageModal.activeTab === 'optical' ? 'border-cyan-600 text-cyan-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800'">
                            <i class="fas fa-signal text-[10px] mr-1"></i>
                            <span>Optical Status</span>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-4 max-h-[60vh] overflow-y-auto text-xs space-y-3">

                        <!-- TAB 1: Configuration -->
                        <div x-show="manageModal.activeTab === 'config'" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Service Mode -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Service / WAN Mode</label>
                                    <select x-model="manageModal.form.service_mode" 
                                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                        <option value="bridge">Bridge Mode</option>
                                        <option value="pppoe">PPPoE Router Mode</option>
                                        <option value="dhcp">DHCP Client Mode</option>
                                        <option value="static">Static IP Mode</option>
                                    </select>
                                </div>

                                <!-- VLAN ID -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">VLAN ID</label>
                                    <input type="number" 
                                           x-model="manageModal.form.vlan_id" 
                                           placeholder="e.g. 100" 
                                           min="1" max="4094"
                                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                                </div>

                                <!-- VLAN Mode -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">VLAN Mode</label>
                                    <select x-model="manageModal.form.vlan_mode" 
                                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                        <option value="tag">Tagged</option>
                                        <option value="untag">Untagged</option>
                                        <option value="transparent">Transparent</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>

                                <!-- LAN Port 1 -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">LAN Port 1 State</label>
                                    <select x-model="manageModal.form.lan1_state" 
                                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                        <option value="enable">Enabled</option>
                                        <option value="disable">Disabled</option>
                                    </select>
                                </div>

                                <!-- Bandwidth Profile -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Bandwidth Profile</label>
                                    <select x-model="manageModal.form.bandwidth_profile" 
                                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                        <option value="">Default (Unlimited)</option>
                                        <option value="10M">10 Mbps</option>
                                        <option value="20M">20 Mbps</option>
                                        <option value="30M">30 Mbps</option>
                                        <option value="50M">50 Mbps</option>
                                        <option value="100M">100 Mbps</option>
                                    </select>
                                </div>

                                <!-- CATV Control -->
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">CATV RF Port</label>
                                    <select x-model="manageModal.form.catv_state" 
                                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal">
                                        <option value="enable">Enabled</option>
                                        <option value="disable">Disabled</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Optional PPPoE Credentials -->
                            <div class="border-t border-slate-100 pt-3 space-y-2">
                                <span class="text-[11px] font-semibold text-slate-700 block">PPPoE Credentials (Optional)</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Username</label>
                                        <input type="text" 
                                               x-model="manageModal.form.pppoe_username" 
                                               placeholder="username" 
                                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Password</label>
                                        <input type="password" 
                                               x-model="manageModal.form.pppoe_password" 
                                               placeholder="••••••••" 
                                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Subscriber Details -->
                        <div x-show="manageModal.activeTab === 'subscriber'" class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Subscriber / Client Name</label>
                                <input type="text" 
                                       x-model="manageModal.form.name" 
                                       placeholder="e.g. John Doe / Customer #1024" 
                                       class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal" />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Description / Installation Address</label>
                                <textarea x-model="manageModal.form.desc" 
                                          rows="3"
                                          placeholder="e.g. House 14, Road 5, Block B" 
                                          class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 outline-hidden transition font-normal"></textarea>
                            </div>
                        </div>

                        <!-- TAB 3: Optical Status -->
                        <div x-show="manageModal.activeTab === 'optical'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-slate-700">Telemetry Readings</span>
                                <button type="button" 
                                        @click="refreshOptical()" 
                                        :disabled="manageModal.refreshingOptical"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-medium transition flex items-center gap-1 cursor-pointer disabled:opacity-50">
                                    <i class="fas fa-rotate text-[10px]" :class="{ 'fa-spin text-cyan-600': manageModal.refreshingOptical }"></i>
                                    <span x-text="manageModal.refreshingOptical ? 'Checking...' : 'Refresh Telemetry'"></span>
                                </button>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center space-y-1">
                                    <span class="text-[10px] text-slate-500 font-medium uppercase block">Rx Power</span>
                                    <span class="font-mono text-xs font-bold block"
                                          :class="{
                                              'text-emerald-600': manageModal.onu.rx_power_dbm !== null && manageModal.onu.rx_power_dbm >= -20,
                                              'text-amber-600': manageModal.onu.rx_power_dbm !== null && manageModal.onu.rx_power_dbm >= -26 && manageModal.onu.rx_power_dbm < -20,
                                              'text-rose-600': manageModal.onu.rx_power_dbm !== null && manageModal.onu.rx_power_dbm < -26,
                                              'text-slate-400': manageModal.onu.rx_power_dbm === null
                                          }"
                                          x-text="manageModal.onu.rx_power_dbm !== null ? manageModal.onu.rx_power_dbm + ' dBm' : 'N/A'"></span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9.5px] border"
                                          :class="manageModal.onu.signal_badge || 'bg-slate-100 text-slate-600 border-slate-200'"
                                          x-text="manageModal.onu.signal_label || 'Signal'"></span>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center space-y-1">
                                    <span class="text-[10px] text-slate-500 font-medium uppercase block">Tx Power</span>
                                    <span class="font-mono text-xs font-bold text-slate-800 block"
                                          x-text="manageModal.onu.tx_power_dbm !== null && manageModal.onu.tx_power_dbm !== undefined ? manageModal.onu.tx_power_dbm + ' dBm' : 'N/A'"></span>
                                    <span class="text-[10px] text-slate-400">Transmit Power</span>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-center space-y-1">
                                    <span class="text-[10px] text-slate-500 font-medium uppercase block">Distance</span>
                                    <span class="font-mono text-xs font-bold text-slate-800 block"
                                          x-text="manageModal.onu.distance_m !== null && manageModal.onu.distance_m !== undefined ? manageModal.onu.distance_m + 'm' : 'N/A'"></span>
                                    <span class="text-[10px] text-slate-400">Fiber Distance</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block">Model</span>
                                    <span class="font-mono text-[11px] text-slate-800 truncate block" x-text="manageModal.onu.model || '1GE ONU'"></span>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block">Vendor</span>
                                    <span class="text-[11px] text-slate-800 truncate block" x-text="manageModal.onu.vendor || 'VSOL'"></span>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block">Status</span>
                                    <span class="text-[11px] font-semibold uppercase" :class="manageModal.onu.status === 'online' ? 'text-emerald-600' : 'text-slate-600'" x-text="manageModal.onu.status"></span>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <span class="text-[10px] text-slate-400 block">Last Seen</span>
                                    <span class="text-[11px] text-slate-600 truncate block" x-text="manageModal.onu.updated_at"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer Action Bar -->
                    <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                        <div>
                            <a :href="`/admin/network/olt/${manageModal.onu.olt_id}?pon_port=${manageModal.onu.pon_port}`" 
                               class="text-[11px] text-slate-500 hover:text-cyan-700 transition flex items-center gap-1 font-medium">
                                <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                <span>Open in OLT</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="closeManageModal()" 
                                    class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="button" 
                                    @click="saveOnuConfig()" 
                                    :disabled="manageModal.saving"
                                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                                <i class="fas fa-check text-[10px]" :class="{ 'fa-spin fa-spinner': manageModal.saving }"></i>
                                <span x-text="manageModal.saving ? 'Saving...' : 'Save & Apply'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function onuManager() {
    return {
        activeMenuOnu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        manageModal: {
            open: false,
            activeTab: 'config',
            onu: null,
            saving: false,
            refreshingOptical: false,
            form: {
                name: '',
                desc: '',
                vlan_id: '',
                vlan_mode: 'tag',
                service_mode: 'bridge',
                pppoe_username: '',
                pppoe_password: '',
                lan1_state: 'enable',
                wifi_ssid: '',
                wifi_password: '',
                catv_state: 'enable',
                bandwidth_profile: ''
            }
        },

        toggleMenu(onu, event) {
            if (this.activeMenuOnu && this.activeMenuOnu.id === onu.id) {
                this.activeMenuOnu = null;
                return;
            }
            this.activeMenuOnu = onu;

            let targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
            const dropdownHeight = 160;
            const spaceBelow = window.innerHeight - rect.bottom;
            const right = Math.max(10, window.innerWidth - rect.right);

            if (spaceBelow >= dropdownHeight) {
                this.menuPos = {
                    top: `${Math.round(rect.bottom) + 2}px`,
                    bottom: 'auto',
                    right: `${right}px`,
                    left: 'auto'
                };
            } else {
                this.menuPos = {
                    top: 'auto',
                    bottom: `${Math.round(window.innerHeight - rect.top) + 2}px`,
                    right: `${right}px`,
                    left: 'auto'
                };
            }
        },

        configureFromMenu(tab = 'config') {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;
            this.openManageModal(onu, tab);
        },

        rebootFromMenu() {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Reboot ONU Terminal?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to reboot ONU <strong class="text-slate-900 font-semibold">${onu.name || onu.mac_address}</strong> on Port <span class="font-mono text-purple-700 font-semibold">${onu.pon_port}:#${onu.onu_id}</span>?</div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Yes, Reboot',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeReboot(onu.id);
                    }
                });
            } else if (confirm(`Reboot ONU ${onu.name}?`)) {
                this.executeReboot(onu.id);
            }
        },

        factoryResetFromMenu() {
            if (!this.activeMenuOnu) return;
            const onu = this.activeMenuOnu;
            this.activeMenuOnu = null;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Factory Reset ONU?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to reset ONU <strong class="text-slate-900 font-semibold">${onu.name || onu.mac_address}</strong> to factory defaults?<br><span class="text-[11px] text-rose-500 mt-1 block">All custom VLAN, WAN, and WiFi configurations will be erased.</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-rotate-left mr-1"></i> Yes, Reset to Defaults',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.executeFactoryReset(onu.id);
                    }
                });
            } else if (confirm(`Reset ONU ${onu.name} to factory defaults?`)) {
                this.executeFactoryReset(onu.id);
            }
        },

        async executeReboot(onuId) {
            try {
                const response = await fetch(`/admin/network/onu/${onuId}/reboot`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || 'ONU reboot command sent successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to reboot ONU.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while sending reboot command.', 'error');
            }
        },

        async executeFactoryReset(onuId) {
            try {
                const response = await fetch(`/admin/network/onu/${onuId}/factory-reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || 'Factory reset command sent successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to send factory reset command.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while sending factory reset.', 'error');
            }
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        copyToClipboard(text, label = 'MAC address') {
            if (!text) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(`${label} copied to clipboard!`, 'success');
                }).catch(() => {
                    this.showToast('Could not copy to clipboard.', 'error');
                });
            } else {
                this.showToast(`${label} copied!`, 'success');
            }
        },

        openManageModal(onuData, defaultTab = 'config') {
            this.manageModal.onu = { ...onuData };
            this.manageModal.activeTab = defaultTab;
            this.manageModal.form.name = onuData.name || '';
            this.manageModal.form.desc = onuData.desc || '';
            this.manageModal.form.vlan_id = onuData.vlan_id || '';
            this.manageModal.form.vlan_mode = onuData.vlan_mode || 'tag';
            this.manageModal.form.service_mode = onuData.service_mode || 'bridge';
            this.manageModal.form.pppoe_username = onuData.pppoe_username || '';
            this.manageModal.form.pppoe_password = onuData.pppoe_password || '';
            this.manageModal.form.lan1_state = onuData.lan1_state || 'enable';
            this.manageModal.form.wifi_ssid = onuData.wifi_ssid || '';
            this.manageModal.form.wifi_password = onuData.wifi_password || '';
            this.manageModal.form.catv_state = onuData.catv_state || 'enable';
            this.manageModal.form.bandwidth_profile = onuData.bandwidth_profile || '';
            this.manageModal.open = true;
        },

        closeManageModal() {
            this.manageModal.open = false;
        },

        async saveOnuConfig() {
            if (!this.manageModal.onu) return;
            this.manageModal.saving = true;
            try {
                const response = await fetch(`/admin/network/onu/${this.manageModal.onu.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.manageModal.form.name,
                        desc: this.manageModal.form.desc,
                        vlan_id: this.manageModal.form.vlan_id || null,
                        vlan_mode: this.manageModal.form.vlan_mode,
                        service_mode: this.manageModal.form.service_mode,
                        pppoe_username: this.manageModal.form.pppoe_username,
                        pppoe_password: this.manageModal.form.pppoe_password,
                        lan1_state: this.manageModal.form.lan1_state,
                        wifi_ssid: this.manageModal.form.wifi_ssid,
                        wifi_password: this.manageModal.form.wifi_password,
                        catv_state: this.manageModal.form.catv_state,
                        bandwidth_profile: this.manageModal.form.bandwidth_profile
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.manageModal.onu = { ...this.manageModal.onu, ...this.manageModal.form };
                    this.showToast(data.message || 'Configuration saved successfully.', 'success');
                    
                    const nameEl = document.getElementById(`onu-name-${this.manageModal.onu.id}`);
                    if (nameEl) nameEl.textContent = this.manageModal.form.name;

                    this.closeManageModal();
                } else {
                    this.showToast(data.message || 'Failed to save configuration.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while saving configuration.', 'error');
            } finally {
                this.manageModal.saving = false;
            }
        },

        async refreshOptical() {
            if (!this.manageModal.onu) return;
            this.manageModal.refreshingOptical = true;
            try {
                const response = await fetch(`/admin/network/onu/${this.manageModal.onu.id}/optical`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success && data.telemetry) {
                    if (data.telemetry.rx_power_dbm !== undefined) {
                        this.manageModal.onu.rx_power_dbm = data.telemetry.rx_power_dbm;
                    }
                    if (data.telemetry.tx_power_dbm !== undefined) {
                        this.manageModal.onu.tx_power_dbm = data.telemetry.tx_power_dbm;
                    }
                    if (data.telemetry.distance_m !== undefined) {
                        this.manageModal.onu.distance_m = data.telemetry.distance_m;
                    }

                    const rx = this.manageModal.onu.rx_power_dbm;
                    if (rx !== null && rx !== undefined) {
                        if (rx >= -20.0) {
                            this.manageModal.onu.signal_label = 'Good';
                            this.manageModal.onu.signal_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        } else if (rx >= -26.0 && rx < -20.0) {
                            this.manageModal.onu.signal_label = 'Warning';
                            this.manageModal.onu.signal_badge = 'bg-amber-50 text-amber-700 border-amber-200';
                        } else {
                            this.manageModal.onu.signal_label = 'Critical';
                            this.manageModal.onu.signal_badge = 'bg-rose-50 text-rose-700 border-rose-200';
                        }
                    }

                    this.showToast('Live telemetry updated.', 'success');
                } else {
                    this.showToast(data.message || 'Could not fetch live telemetry from OLT.', 'info');
                }
            } catch (e) {
                this.showToast('Network error while refreshing telemetry.', 'error');
            } finally {
                this.manageModal.refreshingOptical = false;
            }
        }
    };
}
</script>
@endpush
