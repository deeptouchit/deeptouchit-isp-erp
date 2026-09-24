@extends('tenant.layouts.app')

@section('title', 'Edit OLT Device - ' . $olt->name . ' - ' . ($tenant->company_name ?? $tenant->name))

@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="oltEditForm()">

    <!-- Top Header Bar -->
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.network.olt') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition cursor-pointer" title="Back to Fleet">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-sm font-bold text-slate-900 tracking-tight">Edit {{ $olt->name }} Device</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="testConnection()" 
                    :disabled="testing"
                    class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="fas fa-bolt text-amber-400 text-xs" :class="{ 'fa-spin': testing }"></i>
                <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
            </button>
        </div>
    </div>

    <!-- Live Connection Test Alert -->
    <div x-show="testResult" 
         x-transition
         class="p-3.5 rounded-xl border text-xs flex items-center justify-between gap-3 shadow-xs"
         :class="testResult?.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'"
         style="display: none;">
        <div class="flex items-center gap-2.5">
            <i :class="testResult?.success ? 'fas fa-check-circle text-emerald-600 text-sm' : 'fas fa-exclamation-circle text-rose-600 text-sm'"></i>
            <span class="font-medium" x-text="testResult?.message"></span>
            <span x-show="testResult?.latency_ms" class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/70 font-bold" x-text="testResult?.latency_ms + ' ms'"></span>
        </div>
        <button type="button" @click="testResult = null" class="text-slate-400 hover:text-slate-600 text-xs">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Main Edit Form -->
    <form action="{{ route('tenant.network.olt.update', $olt->id) }}" method="POST" novalidate class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Card 1: Device Information -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-2.5 flex items-center gap-2">
                <i class="fas fa-server text-blue-600 text-xs"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Device Information</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <!-- Name -->
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Device Name / Identifier <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $olt->name) }}"
                           placeholder="e.g. Core POP - VSOL OLT" 
                           class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('name') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('name')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Parent MikroTik Router -->
                <div class="sm:col-span-2 space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Parent MikroTik Router</label>
                    <select name="router_id" 
                            class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('router_id') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">None (Standalone OLT)</option>
                        @if(isset($routers))
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ (string)old('router_id', $olt->router_id) === (string)$router->id ? 'selected' : '' }}>
                                    {{ $router->name }} ({{ $router->ip_address }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('router_id')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Vendor -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Hardware Vendor <span class="text-rose-500">*</span></label>
                    <select name="vendor" 
                            x-model="form.vendor" 
                            @change="applyVendorPreset()"
                            class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('vendor') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="EPON OLT">EPON OLT (Standard FastCGI / C-Data)</option>
                        <option value="VSOL">VSOL (V1600 Series / HTTPS Web API)</option>
                        <option value="HSGQ">HSGQ (JSON-RPC Web API)</option>
                        <option value="Core Link">Core Link (HA7302V / UniMars Socket)</option>
                        <option value="BDCOM">BDCOM</option>
                        <option value="Huawei">Huawei</option>
                        <option value="ZTE">ZTE</option>
                    </select>
                    @error('vendor')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Model -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Hardware Model</label>
                    <input type="text" 
                           name="model" 
                           x-model="form.model"
                           placeholder="e.g. V1600D4 / DN08EP / HA7302V" 
                           class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('model') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('model')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Total PON Ports -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Total PON Ports <span class="text-rose-500">*</span></label>
                    <select name="total_pon_ports" 
                            x-model="form.total_pon_ports"
                            class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('total_pon_ports') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="2">2 PON Ports</option>
                        <option value="4">4 PON Ports</option>
                        <option value="8">8 PON Ports</option>
                        <option value="16">16 PON Ports</option>
                    </select>
                    @error('total_pon_ports')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Connection Type -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Sync Protocol <span class="text-rose-500">*</span></label>
                    <select name="connection_type" 
                            x-model="form.connection_type"
                            class="w-full px-3 py-2 rounded-lg text-xs transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('connection_type') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="web_api">REST Web API / Socket (FastCGI, JSON, HTTPS)</option>
                        <option value="snmp">SNMP Only</option>
                        <option value="hybrid">Hybrid (Web API + SNMP)</option>
                    </select>
                    @error('connection_type')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Card 2: Web API Credentials -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-2.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-lock text-purple-600 text-xs"></i>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Web API & Credentials</h2>
                </div>
                <span class="text-[11px] text-slate-400">Encrypted in database</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <!-- IP Address -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">IP Address / Host <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="ip_address" 
                           x-model="form.ip_address"
                           placeholder="e.g. 103.59.177.137" 
                           class="w-full px-3 py-2 rounded-lg text-xs font-mono transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('ip_address') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('ip_address')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Web Port -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Web Port <span class="text-rose-500">*</span></label>
                    <input type="number" 
                           name="web_port" 
                           x-model="form.web_port"
                           placeholder="80, 8081, 8082, 8083, 8084" 
                           class="w-full px-3 py-2 rounded-lg text-xs font-mono transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('web_port') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('web_port')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Web Username -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Web Username <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="web_username" 
                           x-model="form.web_username"
                           placeholder="admin or root" 
                           class="w-full px-3 py-2 rounded-lg text-xs font-mono transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('web_username') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('web_username')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Web Password -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Web Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="web_password" 
                               x-model="form.web_password"
                               placeholder="Leave blank to keep unchanged" 
                               class="w-full pl-3 pr-8 py-2 rounded-lg text-xs font-mono transition focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white @error('web_password') border border-rose-500 ring-2 ring-rose-100 bg-rose-50/40 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <button type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition text-xs">
                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    @error('web_password')
                        <p class="text-[11px] text-rose-600 font-semibold flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Card 3: Optional Settings (SNMP & Notes) -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-2.5 flex items-center gap-2">
                <i class="fas fa-sliders-h text-emerald-600 text-xs"></i>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">SNMP & Notes (Optional)</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                <div class="sm:col-span-3 space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">SNMP Read Community</label>
                    <input type="text" 
                           name="snmp_community" 
                           value="{{ old('snmp_community', $olt->snmp_community ?: 'public') }}"
                           placeholder="public" 
                           class="w-full px-3 py-2 rounded-lg bg-slate-50 border text-xs font-mono focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition @error('snmp_community') border-rose-400 bg-rose-50/30 @else border-slate-200 text-slate-800 @enderror">
                    @error('snmp_community')
                        <p class="text-[11px] text-rose-600 font-medium flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="sm:col-span-1 space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">SNMP Port</label>
                    <input type="number" 
                           name="snmp_port" 
                           value="{{ old('snmp_port', $olt->snmp_port ?: '161') }}"
                           placeholder="161" 
                           class="w-full px-3 py-2 rounded-lg bg-slate-50 border text-xs font-mono focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition @error('snmp_port') border-rose-400 @else border-slate-200 text-slate-800 @enderror">
                    @error('snmp_port')
                        <p class="text-[11px] text-rose-600 font-medium flex items-center gap-1 mt-1">
                            <i class="fas fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="sm:col-span-4 space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Operational Notes</label>
                    <textarea name="notes" 
                              rows="2" 
                              placeholder="Rack position, location, uplink details..." 
                              class="w-full px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition resize-none">{{ old('notes', $olt->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-center pt-2">
            <button type="submit" 
                    class="px-8 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs shadow-sm hover:shadow transition transform active:scale-95 flex items-center gap-2 cursor-pointer">
                <i class="fas fa-check text-xs"></i>
                <span>Save Changes</span>
            </button>
        </div>

    </form>

</div>

<script>
function oltEditForm() {
    return {
        showPassword: false,
        testing: false,
        testResult: null,
        form: {
            vendor: '{{ old('vendor', $olt->vendor ?: 'EPON OLT') }}',
            model: '{{ old('model', $olt->model ?: '') }}',
            total_pon_ports: '{{ old('total_pon_ports', $olt->total_pon_ports ?: '4') }}',
            connection_type: '{{ old('connection_type', $olt->connection_type ?: 'web_api') }}',
            ip_address: '{{ old('ip_address', $olt->ip_address) }}',
            web_port: '{{ old('web_port', $olt->web_port ?: '80') }}',
            web_username: '{{ old('web_username', $olt->web_username ?: 'admin') }}',
            web_password: '{{ old('web_password', $olt->decrypted_web_password ?: '') }}',
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
                    message: 'Network error or probe failed to reach the device.'
                };
            } finally {
                this.testing = false;
            }
        }
    }
}
</script>
@endsection
