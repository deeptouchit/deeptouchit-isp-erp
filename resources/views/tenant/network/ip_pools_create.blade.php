@extends('tenant.layouts.app')

@section('title', __('Create IP Pool') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ipPoolCreateManager()">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">{{ __('Create IP Pool & Subnet Block') }}</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                    {{ __('IPv4 / IPv6 Subnets') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.network.ip-pools') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>{{ __('Back to IP Pools') }}</span>
            </a>
        </div>
    </div>

    <!-- Error Summary Card (If Validation Fails) -->
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800">
            <div class="flex items-center gap-2 font-semibold mb-1 text-rose-900">
                <i class="fas fa-triangle-exclamation text-rose-600"></i>
                <span>{{ __('Please correct the errors below:') }}</span>
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
        <form action="{{ route('tenant.network.ip-pools.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf

            <!-- 1. Pool Identity & Gateway Association -->
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        {{ __('1. Pool Identity & Gateway Association') }}
                    </span>
                    <span class="text-[10.5px] text-slate-400">{{ __('Core identifier and router association') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Pool Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Pool Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               placeholder="{{ __('e.g. PPPoE-Pool-Zone1 / CGNAT-100.64-Pool') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Associated Router -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Associated Router') }}
                        </label>
                        <select name="router_id" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">{{ __('-- Global / All Routers --') }}</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}" {{ old('router_id') == $r->id ? 'selected' : '' }}>
                                    {{ $r->name }} ({{ $r->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Status') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                            <option value="exhausted" {{ old('status') === 'exhausted' ? 'selected' : '' }}>{{ __('Exhausted') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Pool Type -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Pool Type') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="pool_type" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="pppoe" {{ old('pool_type', 'pppoe') === 'pppoe' ? 'selected' : '' }}>{{ __('PPPoE Dynamic Client Pool') }}</option>
                            <option value="cgnat" {{ old('pool_type') === 'cgnat' ? 'selected' : '' }}>{{ __('CGNAT RFC6598 (100.64.0.0/10)') }}</option>
                            <option value="static_public" {{ old('pool_type') === 'static_public' ? 'selected' : '' }}>{{ __('Static Public IPv4 Block') }}</option>
                            <option value="dhcp" {{ old('pool_type') === 'dhcp' ? 'selected' : '' }}>{{ __('DHCP / IPoE Hotspot Pool') }}</option>
                            <option value="ipv6" {{ old('pool_type') === 'ipv6' ? 'selected' : '' }}>{{ __('IPv6 Prefix Pool') }}</option>
                            <option value="vpn" {{ old('pool_type') === 'vpn' ? 'selected' : '' }}>{{ __('VPN Client Pool') }}</option>
                        </select>
                    </div>

                    <!-- IP Version -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('IP Version') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="ip_version" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="ipv4" {{ old('ip_version', 'ipv4') === 'ipv4' ? 'selected' : '' }}>{{ __('IPv4 (32-bit)') }}</option>
                            <option value="ipv6" {{ old('ip_version') === 'ipv6' ? 'selected' : '' }}>{{ __('IPv6 (128-bit)') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. IP Addressing & Subnet Calculation -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        {{ __('2. IP Addressing & Subnet Calculation') }}
                    </span>
                    <span class="text-[10.5px] text-slate-400 font-mono">
                        {{ __('Addresses Range format: 10.10.0.2-10.10.3.254 or 192.168.1.0/24') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <!-- Addresses / IP Range -->
                    <div class="sm:col-span-2 md:col-span-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-medium text-slate-700">
                                {{ __('Addresses (IP Range / CIDR Subnet)') }} <span class="text-rose-500">*</span>
                            </label>
                            <template x-if="calculatedCount > 0">
                                <span class="text-[11px] font-mono font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    <i class="fas fa-check-circle text-[10px] mr-1"></i>
                                    {{ __('Approx.') }} <span x-text="calculatedCount.toLocaleString()"></span> {{ __('Usable Host IPs') }}
                                </span>
                            </template>
                        </div>
                        <input type="text" 
                               name="ranges" 
                               x-model="rangesInput" 
                               @input="calculateIps()" 
                               required 
                               placeholder="{{ __('e.g. 10.10.0.2-10.10.3.254 or 192.168.10.0/24') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <span class="text-[10.5px] text-slate-400 mt-1 block">
                            {!! __('Direct MikroTik compatible format: IP Range (:range) or Subnet (:subnet).', [
                                'range' => '<code class="font-mono text-cyan-700 bg-cyan-50 px-1 py-0.5 rounded">10.10.0.2-10.10.3.254</code>',
                                'subnet' => '<code class="font-mono text-cyan-700 bg-cyan-50 px-1 py-0.5 rounded">192.168.1.0/24</code>',
                            ]) !!}
                        </span>
                    </div>

                    <!-- CIDR Subnet -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('CIDR Subnet (Optional)') }}
                        </label>
                        <input type="text" 
                               name="cidr_subnet" 
                               value="{{ old('cidr_subnet') }}" 
                               placeholder="{{ __('e.g. 10.10.10.0/24') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Gateway IP -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Default Gateway IP (Optional)') }}
                        </label>
                        <input type="text" 
                               name="gateway" 
                               value="{{ old('gateway') }}" 
                               placeholder="{{ __('e.g. 10.10.10.1') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- VLAN ID -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('VLAN ID (Optional)') }}
                        </label>
                        <input type="number" 
                               name="vlan_id" 
                               value="{{ old('vlan_id') }}" 
                               min="1" 
                               max="4094" 
                               placeholder="{{ __('e.g. 100') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Primary DNS -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Primary DNS Server') }}
                        </label>
                        <input type="text" 
                               name="dns_primary" 
                               value="{{ old('dns_primary', '8.8.8.8') }}" 
                               placeholder="8.8.8.8" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Secondary DNS -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Secondary DNS Server') }}
                        </label>
                        <input type="text" 
                               name="dns_secondary" 
                               value="{{ old('dns_secondary', '1.1.1.1') }}" 
                               placeholder="1.1.1.1" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Next Pool (Overflow) -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Next Pool (Overflow Chain)') }}
                        </label>
                        <input type="text" 
                               name="next_pool" 
                               value="{{ old('next_pool') }}" 
                               list="existingPoolsList" 
                               placeholder="{{ __('e.g. Pool-Backup-Zone1') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <datalist id="existingPoolsList">
                            @foreach($existingPools as $pName)
                                <option value="{{ $pName }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
            </div>

            <!-- 3. Network Integration & Advanced Options -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        {{ __('3. Network Integration & Metadata') }}
                    </span>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        {{ __('Comment / Description (MikroTik Pool Comment)') }}
                    </label>
                    <input type="text" 
                           name="description" 
                           value="{{ old('description') }}" 
                           placeholder="{{ __('e.g. Primary PPPoE pool serving residential customers in Zone A') }}" 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Sync to MikroTik Switch -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <label for="is_sync_mikrotik" class="text-xs font-semibold text-slate-800 cursor-pointer block">
                            {{ __('Push IP Pool to Associated MikroTik Router') }}
                        </label>
                        <p class="text-[11px] text-slate-500">
                            {!! __('When enabled and a router is selected, this pool will immediately be provisioned under :code via RouterOS API.', ['code' => '<code class="font-mono text-purple-700 bg-purple-50 px-1 py-0.5 rounded">/ip/pool</code>']) !!}
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_sync_mikrotik" 
                               name="is_sync_mikrotik" 
                               value="1" 
                               {{ old('is_sync_mikrotik') ? 'checked' : '' }} 
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Form Footer Actions (AGENTS.md Uniform Standard) -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <a href="{{ route('tenant.network.ip-pools') }}" 
                   class="h-[34px] px-4 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition flex items-center justify-center cursor-pointer">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>{{ __('Create IP Pool') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ipPoolCreateManager() {
    return {
        rangesInput: @js(old('ranges', '')),
        calculatedCount: 0,

        init() {
            if (this.rangesInput) {
                this.calculateIps();
            }
        },

        calculateIps() {
            const val = (this.rangesInput || '').trim();
            if (!val) {
                this.calculatedCount = 0;
                return;
            }

            // Check CIDR format: 192.168.1.0/24
            const cidrMatch = val.match(/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]{1,2})$/);
            if (cidrMatch) {
                const prefix = parseInt(cidrMatch[1], 10);
                if (prefix >= 0 && prefix <= 32) {
                    this.calculatedCount = prefix >= 31 ? (prefix === 31 ? 2 : 1) : Math.pow(2, 32 - prefix) - 2;
                    return;
                }
            }

            // Check Range format: 10.10.0.2-10.10.3.254
            const rangeMatch = val.split('-');
            if (rangeMatch.length === 2) {
                const startIp = rangeMatch[0].trim();
                const endIp = rangeMatch[1].trim();
                const startLong = this.ipToLong(startIp);
                const endLong = this.ipToLong(endIp);
                if (startLong !== null && endLong !== null && endLong >= startLong) {
                    this.calculatedCount = (endLong - startLong) + 1;
                    return;
                }
            }

            this.calculatedCount = 0;
        },

        ipToLong(ip) {
            const parts = ip.split('.');
            if (parts.length !== 4) return null;
            let res = 0;
            for (let i = 0; i < 4; i++) {
                const octet = parseInt(parts[i], 10);
                if (isNaN(octet) || octet < 0 || octet > 255) return null;
                res = (res << 8) + octet;
            }
            return (res >>> 0);
        }
    };
}
</script>
@endpush
