@extends('tenant.layouts.app')

@section('title', 'OLT Gateways & Optical Terminals - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="oltManager()" @scroll.window="activeMenuOlt = null" @resize.window="activeMenuOlt = null">
    
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
                <i class="fas fa-network-wired"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">OLT Gateways &amp; Optical Terminals</h1>
                <!-- Plan Quota Badge -->
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold border {{ $isQuotaReached ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-purple-50 text-purple-700 border-purple-200' }}"
                      title="OLT quota allowed in your ISP plan">
                    <i class="fas fa-layer-group text-[9px]"></i>
                    <span>Plan: {{ $plan->name ?? 'Standard' }} ({{ $totalOlts }}/{{ $oltQuota > 0 ? $oltQuota : '∞' }})</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Link to All Client ONUs -->
            <a href="{{ route('tenant.network.onu') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-satellite-dish text-slate-400 text-xs"></i>
                <span>Client ONUs</span>
            </a>

            <!-- Add OLT Button -->
            <a href="{{ route('tenant.network.olt.create') }}" 
               class="px-3.5 py-1.5 rounded-lg text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer {{ $isQuotaReached ? 'bg-slate-700 hover:bg-slate-800' : 'bg-cyan-600 hover:bg-cyan-700' }}">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add OLT Device</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Plan Quota -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Plan Quota</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $isQuotaReached ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $totalOlts }} / {{ $oltQuota > 0 ? $oltQuota : '∞' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 {{ $isQuotaReached ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-purple-50 text-purple-600 border-purple-100' }} flex items-center justify-center">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Card 2: Online OLTs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Online OLT</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($onlineOlts) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Card 3: Total ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Total ONU</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">{{ number_format($totalOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-satellite-dish"></i>
            </div>
        </div>

        <!-- Card 4: Online ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Online ONU</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($onlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Card 5: Offline ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">Offline ONU</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700">{{ number_format($offlineOnus) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-times-circle"></i>
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
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.network.olt') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search OLT name, IP, model..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal" />
                </div>
            </div>

            <!-- Router Filter -->
            <div>
                <select name="router_id" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Routers</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" {{ (string)request('router_id') === (string)$r->id ? 'selected' : '' }}>
                            {{ $r->name }}
                        </option>
                    @endforeach
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
                </select>
            </div>

            <!-- Vendor Filter -->
            <div>
                <select name="vendor" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="">All Vendors</option>
                    <option value="EPON OLT" {{ request('vendor') === 'EPON OLT' ? 'selected' : '' }}>EPON OLT</option>
                    <option value="VSOL" {{ request('vendor') === 'VSOL' ? 'selected' : '' }}>VSOL</option>
                    <option value="BDCOM" {{ request('vendor') === 'BDCOM' ? 'selected' : '' }}>BDCOM</option>
                    <option value="Huawei" {{ request('vendor') === 'Huawei' ? 'selected' : '' }}>Huawei</option>
                    <option value="ZTE" {{ request('vendor') === 'ZTE' ? 'selected' : '' }}>ZTE</option>
                </select>
            </div>

            <!-- Per Page Filter -->
            <div>
                @php $perPageVal = (int)request('per_page', 20); @endphp
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition font-normal">
                    <option value="10" {{ $perPageVal === 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / page</option>
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
                <a href="{{ route('tenant.network.olt') }}" 
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
                <span>Master OLT Directory</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $olts->total() }} Devices
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>Router</th>
                        <th>OLT Device Name</th>
                        <th>Host IP</th>
                        <th>Model / Vendor</th>
                        <th class="text-center">PON Ports</th>
                        <th class="text-center">Total ONU</th>
                        <th class="text-center">Online ONU</th>
                        <th class="text-center">Offline ONU</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($olts as $index => $olt)
                        @php
                            $totalCount = max((int)($olt->total_onus_count ?? 0), (int)($olt->onus_count ?? 0));
                            $onlineCount = (int)($olt->online_onus_count ?? 0);
                            $offlineCount = isset($olt->offline_onus_count) ? (int)$olt->offline_onus_count : max(0, $totalCount - $onlineCount);
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $olts->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if($olt->status === 'online')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Online</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200" title="{{ $olt->last_error }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Offline</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Router -->
                            <td>
                                @if($olt->router)
                                    <a href="{{ route('tenant.network.mikrotik.show', $olt->router->id) }}" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-[10px] font-mono transition" title="{{ $olt->router->ip_address }}">
                                        <i class="fas fa-server text-[8px] text-indigo-500"></i>
                                        <span class="truncate max-w-[110px]">{{ $olt->router->name }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono text-[10px]">Standalone</span>
                                @endif
                            </td>

                            <!-- 4. Device Name -->
                            <td class="text-cyan-800">
                                <a href="{{ route('tenant.network.olt.show', $olt->id) }}" class="hover:underline">
                                    {{ $olt->name }}
                                </a>
                            </td>

                            <!-- 5. Host IP -->
                            <td class="font-mono text-slate-800">
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $olt->ip_address }}:{{ $olt->web_port ?: 80 }}</span>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $olt->ip_address }}', 'OLT IP')" 
                                            class="text-slate-400 hover:text-cyan-600 text-[10px] cursor-pointer" 
                                            title="Copy IP">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>

                            <!-- 6. Model / Vendor -->
                            <td class="font-mono text-slate-700">
                                {{ $olt->model ?: ($olt->vendor ?: 'EPON OLT') }}
                            </td>

                            <!-- 7. PON Ports -->
                            <td class="text-center font-mono text-purple-700">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-50 border border-purple-200 text-[10px]">
                                    <i class="fas fa-plug text-[8px]"></i>
                                    <span>{{ $olt->total_pon_ports ?: 8 }} P</span>
                                </span>
                            </td>

                            <!-- 8. Total ONU -->
                            <td class="text-center font-mono">
                                <a href="{{ route('tenant.network.onu', ['olt_id' => $olt->id]) }}" 
                                   class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] hover:bg-blue-100 transition"
                                   title="View all ONUs">
                                    <i class="fas fa-satellite-dish text-[8px]"></i>
                                    <span>{{ number_format($totalCount) }}</span>
                                </a>
                            </td>

                            <!-- 9. Online ONU -->
                            <td class="text-center font-mono">
                                <a href="{{ route('tenant.network.onu', ['olt_id' => $olt->id, 'status' => 'online']) }}" 
                                   class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] hover:bg-emerald-100 transition"
                                   title="View Online ONUs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ number_format($onlineCount) }}</span>
                                </a>
                            </td>

                            <!-- 10. Offline ONU -->
                            <td class="text-center font-mono">
                                <a href="{{ route('tenant.network.onu', ['olt_id' => $olt->id, 'status' => 'offline']) }}" 
                                   class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded {{ $offlineCount > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' : 'bg-slate-50 text-slate-400 border border-slate-200' }} text-[10px] transition"
                                   title="View Offline ONUs">
                                    <i class="fas fa-times-circle text-[8px]"></i>
                                    <span>{{ number_format($offlineCount) }}</span>
                                </a>
                            </td>

                            <!-- 11. Actions (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($olt) }}, $event)" 
                                        class="olt-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition cursor-pointer flex items-center justify-center text-[10px]"
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
                                        <i class="fas fa-network-wired"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No OLT Gateways Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Connect your EPON or GPON Optical Line Terminal to synchronize PON port status and customer ONUs.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.network.olt.create') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>Add OLT Device</span>
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
        @if($olts->hasPages() || $olts->total() > 0)
            <div class="p-3 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $olts->firstItem() ?? 0 }} to {{ $olts->lastItem() ?? 0 }} of {{ $olts->total() }} results
                </div>
                <div>
                    {{ $olts->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenuOlt !== null" 
         @click.away="activeMenuOlt = null"
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
        
        <!-- Group 1: Diagnostics & Telemetry -->
        <div class="py-0.5">
            <a :href="'/admin/network/olt/' + activeMenuOlt?.id" 
               class="w-full px-2.5 py-1 hover:bg-cyan-50 text-slate-700 hover:text-cyan-700 font-normal flex items-center gap-2 transition text-left text-[11px]">
                <i class="fas fa-desktop text-cyan-600 w-3.5 text-center text-[10px]"></i>
                <span>Chassis &amp; PON Panel</span>
            </a>

            <a :href="'/admin/network/onu?olt_id=' + activeMenuOlt?.id" 
               class="w-full px-2.5 py-1 hover:bg-purple-50 text-slate-700 hover:text-purple-700 font-normal flex items-center gap-2 transition text-left text-[11px]">
                <i class="fas fa-satellite-dish text-purple-600 w-3.5 text-center text-[10px]"></i>
                <span>View Client ONUs</span>
            </a>
        </div>

        <!-- Group 2: Configuration & Sync -->
        <div class="py-0.5">
            <button type="button" 
                    @click="syncFromMenu()" 
                    :disabled="syncingId !== null"
                    class="w-full px-2.5 py-1 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px] disabled:opacity-50">
                <i class="fas fa-rotate text-emerald-600 w-3.5 text-center text-[10px]" :class="{ 'fa-spin': syncingId === activeMenuOlt?.id }"></i>
                <span x-text="syncingId === activeMenuOlt?.id ? 'Syncing...' : 'Sync with OLT Device'"></span>
            </button>

            <a :href="'/admin/network/olt/' + activeMenuOlt?.id + '/edit'" 
               class="w-full px-2.5 py-1 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-normal flex items-center gap-2 transition text-left text-[11px]">
                <i class="fas fa-pen-to-square text-amber-500 w-3.5 text-center text-[10px]"></i>
                <span>Edit OLT Settings</span>
            </a>
        </div>

        <!-- Group 3: Destructive Action -->
        <div class="py-0.5">
            <button type="button" 
                    @click="deleteFromMenu()" 
                    class="w-full px-2.5 py-1 hover:bg-rose-50 text-rose-600 font-normal flex items-center gap-2 transition text-left cursor-pointer text-[11px]">
                <i class="far fa-trash-alt text-rose-500 w-3.5 text-center text-[10px]"></i>
                <span>Delete OLT Gateway</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function oltManager() {
    return {
        syncingId: null,
        activeMenuOlt: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
        toast: { show: false, message: '', type: 'success' },

        toggleMenu(olt, event) {
            if (this.activeMenuOlt && this.activeMenuOlt.id === olt.id) {
                this.activeMenuOlt = null;
                return;
            }
            this.activeMenuOlt = olt;

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

        syncFromMenu() {
            if (!this.activeMenuOlt) return;
            const id = this.activeMenuOlt.id;
            this.activeMenuOlt = null;
            this.triggerSync(id);
        },

        deleteFromMenu() {
            if (!this.activeMenuOlt) return;
            const id = this.activeMenuOlt.id;
            const name = this.activeMenuOlt.name;
            this.activeMenuOlt = null;
            this.confirmDeleteOlt(id, name);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        async triggerSync(oltId) {
            this.syncingId = oltId;
            try {
                const response = await fetch(`/admin/network/olt/${oltId}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || 'OLT synchronized successfully!', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Failed to sync OLT.', 'error');
                }
            } catch (e) {
                this.showToast('Network error while syncing OLT.', 'error');
            } finally {
                this.syncingId = null;
            }
        },

        confirmDeleteOlt(id, name) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete OLT Gateway?',
                    html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete OLT <strong class="text-slate-900 font-semibold">${name}</strong> and all associated ONUs?</div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete OLT',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/admin/network/olt/${id}`;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete OLT '${name}'?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/network/olt/${id}`;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },

        copyToClipboard(text, label = 'Copied') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(`${label} copied to clipboard!`, 'success');
                }).catch(() => {
                    this.showToast('Could not copy to clipboard.', 'error');
                });
            } else {
                this.showToast(`${label} copied!`, 'success');
            }
        }
    };
}
</script>
@endpush