@extends('tenant.layouts.app')

@section('title', __('Edit OLT Device') . ' - ' . $olt->name . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="oltEditForm()">

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Device Badge + Actions ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">{{ __('Edit OLT Device') }}</h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 font-mono">
                    {{ $olt->name }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Test Connection Button -->
            <button type="button" 
                    @click="testConnection()" 
                    :disabled="testing"
                    class="h-[34px] px-3.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="fas fa-bolt text-amber-400 text-xs" :class="{ 'fa-spin': testing }"></i>
                <span x-text="testing ? i18n.testing : i18n.testConnection"></span>
            </button>

            <!-- Back to Fleet Button -->
            <a href="{{ route('tenant.network.olt') }}"
               class="h-[34px] px-3.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>{{ __('Back to Fleet') }}</span>
            </a>
        </div>
    </div>

    <!-- Live Connection Test Alert -->
    <div x-show="testResult" 
         x-cloak
         x-transition
         class="p-3 rounded-xl border text-xs flex items-center justify-between gap-3 shadow-xs"
         :class="testResult?.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'"
         style="display: none;">
        <div class="flex items-center gap-2.5">
            <i :class="testResult?.success ? 'fas fa-check-circle text-emerald-600 text-sm' : 'fas fa-exclamation-circle text-rose-600 text-sm'"></i>
            <span class="font-medium" x-text="testResult?.message"></span>
            <span x-show="testResult?.latency_ms" class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/70 font-bold" x-text="testResult?.latency_ms + ' ' + i18n.ms"></span>
        </div>
        <button type="button" @click="testResult = null" class="text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- 2. Main Full-Width Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <form action="{{ route('tenant.network.olt.update', $olt->id) }}" method="POST" novalidate class="p-4 space-y-4">
            @csrf
            @method('PUT')

            <!-- Section 1: Device Identification & Fleet Routing -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-xs font-bold">1</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ __('Device Identification & Fleet Routing') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <!-- Device Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Device Name / Identifier') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name', $olt->name) }}"
                               required
                               placeholder="{{ __('e.g. Core POP - VSOL OLT') }}" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:border-cyan-500 font-medium" />
                        @error('name')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent MikroTik Router -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Parent MikroTik Router') }}
                        </label>
                        <select name="router_id" 
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500">
                            <option value="">{{ __('None (Standalone OLT)') }}</option>
                            @if(isset($routers))
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}" {{ (string)old('router_id', $olt->router_id) === (string)$router->id ? 'selected' : '' }}>
                                        {{ $router->name }} ({{ $router->ip_address }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('router_id')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Network Connectivity & Management API -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">2</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ __('Network Connectivity & Management API') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <!-- Host IP Address -->
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Host IP Address') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="ip_address" 
                               x-model="form.ip_address"
                               required
                               placeholder="{{ __('e.g. 192.168.8.100') }}" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-semibold text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('ip_address')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Web Management Port -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Web API Port') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="web_port" 
                               x-model="form.web_port"
                               required
                               placeholder="80" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-semibold text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('web_port')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Connection Type -->
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Connection Protocol') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="connection_type" 
                                x-model="form.connection_type"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500 font-semibold">
                            <option value="web_api">{{ __('Web Management API (HTTP REST/JSON)') }}</option>
                            <option value="snmp">{{ __('SNMP (Simple Network Mgmt Protocol)') }}</option>
                            <option value="telnet">{{ __('Telnet / CLI Terminal') }}</option>
                            <option value="hybrid">{{ __('Hybrid (Web API + SNMP Polling)') }}</option>
                        </select>
                        @error('connection_type')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Web Username -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Management Username') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="web_username" 
                               x-model="form.web_username"
                               required
                               placeholder="{{ __('e.g. admin') }}" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('web_username')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Web Password -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Management Password') }} <span class="text-slate-400 font-normal">({{ __('Leave blank to keep unchanged') }})</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   name="web_password" 
                                   x-model="form.web_password"
                                   placeholder="••••••••" 
                                   class="w-full h-[34px] pl-3 pr-8 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                            <button type="button" 
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <i class="fas text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('web_password')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Hardware Specifications & PON Capacity -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">3</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ __('Hardware Specifications & PON Capacity') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Hardware Vendor -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Hardware Vendor') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="vendor" 
                                x-model="form.vendor"
                                @change="applyVendorPreset()"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500 font-semibold">
                            <option value="EPON OLT">{{ __('EPON OLT (Standard Chinese EPON)') }}</option>
                            <option value="VSOL">{{ __('VSOL (V1600 Series EPON/GPON)') }}</option>
                            <option value="BDCOM">{{ __('BDCOM (P3600 / GP3600)') }}</option>
                            <option value="HSGQ">{{ __('HSGQ (G008 / E008 Series)') }}</option>
                            <option value="Core Link">{{ __('Core Link (HA7302 Series)') }}</option>
                            <option value="Huawei">{{ __('Huawei (SmartAX MA5608T/MA5800)') }}</option>
                            <option value="ZTE">{{ __('ZTE (C300 / C320 Series)') }}</option>
                            <option value="FiberHome">{{ __('FiberHome (AN5516 Series)') }}</option>
                        </select>
                        @error('vendor')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Model Name -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Model Identifier') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="model" 
                               x-model="form.model"
                               required
                               placeholder="{{ __('e.g. DN08EP-EX-4S+') }}" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-medium text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('model')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total PON Ports -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Total PON Ports') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="total_pon_ports" 
                               x-model="form.total_pon_ports"
                               min="1" 
                               max="64"
                               required
                               placeholder="8" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-purple-700 focus:outline-hidden focus:border-cyan-500" />
                        @error('total_pon_ports')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: SNMP Telemetry & Operational Notes -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">4</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">{{ __('SNMP Telemetry & Operational Notes') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- SNMP Port -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('SNMP UDP Port') }} <span class="text-slate-400 font-normal">({{ __('Default: 161') }})</span>
                        </label>
                        <input type="number" 
                               name="snmp_port" 
                               value="{{ old('snmp_port', $olt->snmp_port ?: '161') }}" 
                               placeholder="161" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>

                    <!-- SNMP Read Community -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('SNMP Read Community') }} <span class="text-slate-400 font-normal">({{ __('Default: public') }})</span>
                        </label>
                        <input type="text" 
                               name="snmp_community" 
                               value="{{ old('snmp_community', $olt->snmp_community ?: 'public') }}" 
                               placeholder="public" 
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>
                </div>

                <!-- Operational Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Operational Notes & Physical Location') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                    </label>
                    <input type="text" 
                           name="notes" 
                           value="{{ old('notes', $olt->notes) }}"
                           placeholder="{{ __('e.g. Core POP Rack #2, Unit 4U, Primary fiber trunk to East Hub') }}" 
                           class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                </div>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('tenant.network.olt') }}"
                   class="h-[34px] px-4 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-[10px]"></i>
                    <span>{{ __('Update OLT Gateway') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function oltEditForm() {
    return {
        showPassword: false,
        testing: false,
        testResult: null,
        i18n: {
            testing: @js(__('Testing...')),
            testConnection: @js(__('Test Connection')),
            ms: @js(__('ms')),
            enterHostIp: @js(__('Please enter Host IP Address to test connection.')),
            probeNetworkError: @js(__('Network error or probe failed to reach the device.')),
        },
        form: {
            vendor: @js(old('vendor', $olt->vendor ?: 'EPON OLT')),
            model: @js(old('model', $olt->model ?: '')),
            total_pon_ports: @js(old('total_pon_ports', $olt->total_pon_ports ?: '4')),
            connection_type: @js(old('connection_type', $olt->connection_type ?: 'web_api')),
            ip_address: @js(old('ip_address', $olt->ip_address)),
            web_port: @js(old('web_port', $olt->web_port ?: '80')),
            web_username: @js(old('web_username', $olt->web_username ?: 'admin')),
            web_password: @js(old('web_password', $olt->decrypted_web_password ?: '')),
        },
        applyVendorPreset() {
            if (this.form.vendor === 'VSOL') {
                this.form.web_port = '8082';
                this.form.total_pon_ports = '4';
                if (!this.form.model) this.form.model = 'V1600D4';
            } else if (this.form.vendor === 'HSGQ') {
                this.form.web_port = '8083';
                this.form.total_pon_ports = '4';
                if (!this.form.model) this.form.model = 'EPON/GPON OLT';
            } else if (this.form.vendor === 'Core Link') {
                this.form.web_port = '8084';
                this.form.total_pon_ports = '2';
                if (!this.form.model) this.form.model = 'HA7302V';
            } else if (this.form.vendor === 'EPON OLT') {
                this.form.web_port = '8081';
                this.form.total_pon_ports = '8';
                if (!this.form.model) this.form.model = 'DN08EP-EX-4S+';
            }
        },
        async testConnection() {
            if (!this.form.ip_address) {
                alert(this.i18n.enterHostIp);
                return;
            }
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch('{{ route('tenant.network.olt.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        olt_id: {{ $olt->id }},
                        ip_address: this.form.ip_address,
                        web_port: parseInt(this.form.web_port) || 80,
                        web_username: this.form.web_username,
                        web_password: this.form.web_password,
                    })
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = {
                    success: false,
                    message: this.i18n.probeNetworkError
                };
            } finally {
                this.testing = false;
            }
        }
    };
}
</script>
@endpush
