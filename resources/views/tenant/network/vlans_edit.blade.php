@extends('tenant.layouts.app')

@section('title', 'Edit VLAN: ' . $vlan->name . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="vlanEditManager()">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Edit VLAN: {{ $vlan->name }}</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                    VLAN ID: {{ $vlan->vlan_id }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold {{ $vlan->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $vlan->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    <span>{{ ucfirst($vlan->status) }}</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.network.vlans') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Back to VLANs</span>
            </a>
        </div>
    </div>

    <!-- Error Summary Card (If Validation Fails) -->
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800">
            <div class="flex items-center gap-2 font-semibold mb-1 text-rose-900">
                <i class="fas fa-triangle-exclamation text-rose-600"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Full-Width Form Container -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <form action="{{ route('tenant.network.vlans.update', $vlan->id) }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf
            @method('PUT')

            <!-- 1. VLAN Identity & 802.1Q Tag -->
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        1. VLAN Identity &amp; 802.1Q Tag
                    </span>
                    <span class="text-[10.5px] text-slate-400">Standard 802.1Q Tag identifier &amp; Service Classification</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- VLAN ID -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            VLAN ID (Tag) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="vlan_id" 
                               value="{{ old('vlan_id', $vlan->vlan_id) }}" 
                               required 
                               min="1" 
                               max="4094" 
                               placeholder="e.g. 100" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- VLAN Name -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            VLAN Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name', $vlan->name) }}" 
                               required 
                               placeholder="e.g. vlan100-pppoe-core" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- VLAN Type -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            VLAN Type <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="service" {{ old('type', $vlan->type) === 'service' ? 'selected' : '' }}>Service (PPPoE / Client)</option>
                            <option value="management" {{ old('type', $vlan->type) === 'management' ? 'selected' : '' }}>Management (OLT / Switch)</option>
                            <option value="corporate" {{ old('type', $vlan->type) === 'corporate' ? 'selected' : '' }}>Corporate Leased Line</option>
                            <option value="cgnat" {{ old('type', $vlan->type) === 'cgnat' ? 'selected' : '' }}>CGNAT Trunk</option>
                            <option value="voice" {{ old('type', $vlan->type) === 'voice' ? 'selected' : '' }}>VoIP Voice</option>
                            <option value="tr069" {{ old('type', $vlan->type) === 'tr069' ? 'selected' : '' }}>TR-069 ACS</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="active" {{ old('status', $vlan->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="disabled" {{ old('status', $vlan->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Router & Interface Attachment -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        2. Router &amp; Interface Attachment
                    </span>
                    <span class="text-[10.5px] text-slate-400">Parent interface on MikroTik hardware and optional OLT mapping</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Associated Router -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Associated Router
                        </label>
                        <select name="router_id" 
                                x-model="selectedRouterId" 
                                @change="onRouterChange()" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">-- Standalone (No Router Link) --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}" {{ (string)old('router_id', $vlan->router_id) === (string)$r->id ? 'selected' : '' }}>
                                    {{ $r->name }} ({{ $r->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Parent Interface -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-medium text-slate-700">
                                Parent Interface <span class="text-rose-500">*</span>
                            </label>
                            <span x-show="loadingInterfaces" class="text-[10px] text-cyan-600 flex items-center gap-1">
                                <i class="fas fa-spinner fa-spin text-[9px]"></i> Loading...
                            </span>
                        </div>
                        <input type="text" 
                               name="interface" 
                               x-model="interfaceInput" 
                               list="routerInterfacesList" 
                               required 
                               placeholder="e.g. ether1, sfp-sfpplus1, bridge" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <datalist id="routerInterfacesList">
                            <template x-for="iface in interfaceOptions" :key="iface">
                                <option :value="iface"></option>
                            </template>
                            <option value="ether1"></option>
                            <option value="ether2"></option>
                            <option value="ether3"></option>
                            <option value="ether4"></option>
                            <option value="ether5"></option>
                            <option value="sfp-sfpplus1"></option>
                            <option value="bridge"></option>
                        </datalist>
                    </div>

                    <!-- Associated OLT -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Associated OLT (Optional)
                        </label>
                        <select name="olt_id" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">-- None / Core Router Only --</option>
                            @foreach($olts as $o)
                                <option value="{{ $o->id }}" {{ (string)old('olt_id', $vlan->olt_id) === (string)$o->id ? 'selected' : '' }}>
                                    {{ $o->name }} ({{ $o->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. IP Addressing & Subnet Configuration -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        3. IP Addressing &amp; Subnet Configuration
                    </span>
                    <span class="text-[10.5px] text-slate-400">Layer-3 Gateway, Subnet &amp; DHCP Settings</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Subnet CIDR -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Subnet CIDR (Optional)
                        </label>
                        <input type="text" 
                               name="subnet" 
                               value="{{ old('subnet', $vlan->subnet) }}" 
                               placeholder="e.g. 10.100.0.0/24" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Gateway IP -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Gateway IP Address (Optional)
                        </label>
                        <input type="text" 
                               name="gateway" 
                               value="{{ old('gateway', $vlan->gateway) }}" 
                               placeholder="e.g. 10.100.0.1" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- MTU -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            MTU (Bytes)
                        </label>
                        <input type="number" 
                               name="mtu" 
                               value="{{ old('mtu', $vlan->mtu ?: 1500) }}" 
                               min="576" 
                               max="9216" 
                               placeholder="1500" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <!-- DHCP Enabled Checkbox -->
                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" 
                               name="dhcp_enabled" 
                               value="1" 
                               {{ old('dhcp_enabled', $vlan->dhcp_enabled) ? 'checked' : '' }} 
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                        <span class="text-xs font-medium text-slate-700">
                            Enable DHCP Server on this VLAN interface (Assign dynamic IP pool)
                        </span>
                    </label>
                </div>
            </div>

            <!-- 4. MikroTik Sync & Metadata -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        4. MikroTik Sync &amp; Metadata
                    </span>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Comment / Description (MikroTik Interface Comment)
                    </label>
                    <input type="text" 
                           name="description" 
                           value="{{ old('description', $vlan->description) }}" 
                           placeholder="e.g. PPPoE Service VLAN for Residential Zone A" 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Sync to MikroTik Switch -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <label for="is_sync_mikrotik" class="text-xs font-semibold text-slate-800 cursor-pointer block">
                            Push VLAN to Associated MikroTik Router
                        </label>
                        <p class="text-[11px] text-slate-500">
                            When enabled and a router is selected, changes to this VLAN will immediately be synced to <code class="font-mono text-purple-700 bg-purple-50 px-1 py-0.5 rounded">/interface/vlan</code> via RouterOS API.
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_sync_mikrotik" 
                               name="is_sync_mikrotik" 
                               value="1" 
                               {{ old('is_sync_mikrotik', $vlan->is_sync_mikrotik) ? 'checked' : '' }} 
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Form Footer Actions (AGENTS.md Uniform Standard) -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <a href="{{ route('tenant.network.vlans') }}" 
                   class="h-[34px] px-4 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition flex items-center justify-center cursor-pointer">
                    Cancel
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-[10px]"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vlanEditManager() {
    return {
        selectedRouterId: '{{ old('router_id', $vlan->router_id) }}',
        interfaceInput: '{{ old('interface', $vlan->interface) }}',
        interfaceOptions: ['ether1', 'ether2', 'ether3', 'ether4', 'ether5', 'bridge'],
        loadingInterfaces: false,

        init() {
            if (this.selectedRouterId) {
                this.fetchRouterInterfaces(this.selectedRouterId);
            }
        },

        onRouterChange() {
            if (this.selectedRouterId) {
                this.fetchRouterInterfaces(this.selectedRouterId);
            }
        },

        async fetchRouterInterfaces(routerId) {
            this.loadingInterfaces = true;
            try {
                const response = await fetch(`/admin/network/vlans/router-interfaces/${routerId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && Array.isArray(data.interfaces) && data.interfaces.length > 0) {
                    this.interfaceOptions = data.interfaces;
                }
            } catch (e) {
                console.error('Failed to load router interfaces', e);
            } finally {
                this.loadingInterfaces = false;
            }
        }
    };
}
</script>
@endpush
