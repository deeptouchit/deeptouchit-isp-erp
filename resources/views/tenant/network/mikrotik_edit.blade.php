@extends('tenant.layouts.app')

@section('title', __('Edit MikroTik Router') . ': ' . $router->name . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="mikrotikEditManager()">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">{{ __('Edit MikroTik Gateway') }}: {{ $router->name }}</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $router->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                    {{ $router->is_active ? __('Active') : __('Disabled') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.network.mikrotik.show', $router->id) }}" 
               class="h-[34px] px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-chart-line text-[11px]"></i>
                <span>{{ __('Live Telemetry') }}</span>
            </a>

            <a href="{{ route('tenant.network.mikrotik') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>{{ __('Back to Routers') }}</span>
            </a>
        </div>
    </div>

    <!-- Main Form Container -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <form action="{{ route('tenant.network.mikrotik.update', $router->id) }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf
            @method('PUT')

            <!-- 1. Connection Mode Selector (Segmented Cards) -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    {{ __('Gateway Architecture Mode') }} <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <!-- FreeRADIUS Option -->
                    <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="formData.connection_type === 'radius' ? 'bg-emerald-50/70 border-emerald-300 ring-1 ring-emerald-400' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                        <input type="radio" name="connection_type" value="radius" x-model="formData.connection_type" class="sr-only">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="formData.connection_type === 'radius' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-500'">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-bold block" :class="formData.connection_type === 'radius' ? 'text-emerald-900' : 'text-slate-800'">{{ __('FreeRADIUS AAA') }}</span>
                            <span class="text-[10.5px] text-slate-500 block">{{ __('PPPoE & Hotspot Subscriber Auth') }}</span>
                        </div>
                    </label>

                    <!-- RouterOS API Option -->
                    <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="formData.connection_type === 'api' ? 'bg-cyan-50/70 border-cyan-300 ring-1 ring-cyan-400' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                        <input type="radio" name="connection_type" value="api" x-model="formData.connection_type" class="sr-only">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="formData.connection_type === 'api' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-500'">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-bold block" :class="formData.connection_type === 'api' ? 'text-cyan-900' : 'text-slate-800'">{{ __('RouterOS API') }}</span>
                            <span class="text-[10.5px] text-slate-500 block">{{ __('Live Telemetry & Simple Queues') }}</span>
                        </div>
                    </label>

                    <!-- Hybrid Option -->
                    <label class="relative flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="formData.connection_type === 'hybrid' ? 'bg-purple-50/70 border-purple-300 ring-1 ring-purple-400' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                        <input type="radio" name="connection_type" value="hybrid" x-model="formData.connection_type" class="sr-only">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0"
                             :class="formData.connection_type === 'hybrid' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-500'">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-bold block" :class="formData.connection_type === 'hybrid' ? 'text-purple-900' : 'text-slate-800'">{{ __('Hybrid (Both)') }}</span>
                            <span class="text-[10.5px] text-slate-500 block">{{ __('Full RADIUS AAA + Live API Sync') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="border-t border-slate-100"></div>

            <!-- 2. Core Identity & Hardware Details -->
            <div class="space-y-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('1. Hardware & Host Identity') }}</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Router Name / Identity') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               x-model="formData.name" 
                               required 
                               placeholder="{{ __('e.g. Core-CCR2004-DHAKA') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Router IP Address') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="ip_address" 
                               x-model="formData.ip_address" 
                               required 
                               placeholder="{{ __('e.g. 103.59.177.10') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Hardware Model') }}
                        </label>
                        <input type="text" 
                               name="model" 
                               x-model="formData.model" 
                               placeholder="{{ __('e.g. CCR2004-16G-2S+ or RB4011') }}" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>
            </div>

            <!-- 3. FreeRADIUS Settings (Shown if radius or hybrid) -->
            <div x-show="formData.connection_type === 'radius' || formData.connection_type === 'hybrid'" class="space-y-3 pt-2">
                <div class="border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">{{ __('2. FreeRADIUS AAA Settings') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('RADIUS Shared Secret') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showRadiusSecret ? 'text' : 'password'" 
                                   name="radius_secret" 
                                   x-model="formData.radius_secret" 
                                   required 
                                   placeholder="radius@123" 
                                   class="w-full h-[34px] pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <button type="button" 
                                    @click="showRadiusSecret = !showRadiusSecret" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fas text-xs" :class="showRadiusSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('CoA / Disconnect Port') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="coa_port" 
                               x-model="formData.coa_port" 
                               required 
                               placeholder="3799" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <!-- RouterOS Quick Script Snippet -->
                <div class="rounded-lg overflow-hidden border border-slate-800 bg-slate-900 mt-2">
                    <div class="px-3 py-1.5 bg-slate-800/80 border-b border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>{{ __('RouterOS FreeRADIUS Setup Script') }}</span>
                        <button type="button" 
                                @click="copyToClipboard(getRadiusScript(), i18n.radiusScript)" 
                                class="text-cyan-400 hover:text-cyan-300 font-sans font-medium flex items-center gap-1 transition cursor-pointer">
                            <i class="fas fa-copy text-[10px]"></i>
                            <span>{{ __('Copy Script') }}</span>
                        </button>
                    </div>
                    <pre class="p-3 text-cyan-300 font-mono text-[11px] overflow-x-auto leading-relaxed max-h-36 selection:bg-cyan-900" x-text="getRadiusScript()"></pre>
                </div>
            </div>

            <!-- 4. RouterOS API Settings (Shown if api or hybrid) -->
            <div x-show="formData.connection_type === 'api' || formData.connection_type === 'hybrid'" class="space-y-3 pt-2">
                <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-700 block">
                        <span x-text="formData.connection_type === 'hybrid' ? i18n.apiCredentialsStep3 : i18n.apiCredentialsStep2"></span>
                    </span>
                    <button type="button" 
                            @click="testApiSocket()" 
                            :disabled="testingSocket"
                            class="h-[28px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-[11px] font-semibold transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-cyan-600" x-show="testingSocket"></i>
                        <i class="fas fa-bolt text-emerald-600" x-show="!testingSocket"></i>
                        <span>{{ __('Test Socket Now') }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('RouterOS API Port') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="api_port" 
                               x-model="formData.api_port" 
                               placeholder="8728" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('API Username') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="username" 
                               x-model="formData.username" 
                               placeholder="admin" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('API Password') }} <span class="text-slate-400 font-normal">({{ __('Leave blank to keep unchanged') }})</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   name="password" 
                                   x-model="formData.password" 
                                   placeholder="••••••••" 
                                   class="w-full h-[34px] pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <button type="button" 
                                    @click="showPassword = !showPassword" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fas text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" 
                           id="use_ssl" 
                           name="use_ssl" 
                           value="1" 
                           x-model="formData.use_ssl" 
                           class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                    <label for="use_ssl" class="text-xs text-slate-700 font-medium cursor-pointer">
                        {{ __('Use SSL / TLS Encryption (Port 8729 / API-SSL)') }}
                    </label>
                </div>
            </div>

            <!-- 5. Status & Options -->
            <div class="border-t border-slate-100 pt-3">
                <div class="flex items-center gap-2">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1" 
                           x-model="formData.is_active" 
                           class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                    <label for="is_active" class="text-xs text-slate-700 font-medium cursor-pointer">
                        {{ __('Enable this gateway for active authentication & telemetry') }}
                    </label>
                </div>
            </div>

            <!-- Form Footer Actions -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <a href="{{ route('tenant.network.mikrotik') }}" 
                   class="h-[34px] px-4 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition flex items-center justify-center cursor-pointer">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>{{ __('Update Gateway Configuration') }}</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function mikrotikEditManager() {
    return {
        serverIp: '{{ $serverIp ?? "103.59.177.136" }}',
        showRadiusSecret: false,
        showPassword: false,
        testingSocket: false,

        i18n: {
            radiusScript: @js(__('RADIUS Script')),
            apiCredentialsStep2: @js(__('2. RouterOS API Socket Credentials')),
            apiCredentialsStep3: @js(__('3. RouterOS API Socket Credentials')),
            copiedDefault: @js(__('Copied')),
            copiedExclaim: @js(__('Copied!')),
            copyFailed: @js(__('Failed to copy text.')),
            enterIpFirst: @js(__('Please enter Router IP Address first.')),
            connectionSuccess: @js(__('Connection Successful!')),
            connectionFailed: @js(__('Connection Failed')),
            routerOsLabel: @js(__('RouterOS')),
            socketNetworkError: @js(__('Network communication error while testing socket.')),
        },

        formData: {
            connection_type: '{{ $router->connection_type ?: "radius" }}',
            name: '{{ addslashes($router->name) }}',
            ip_address: '{{ addslashes($router->ip_address) }}',
            model: '{{ addslashes($router->model ?? "") }}',
            radius_secret: '{{ addslashes($router->decrypted_radius_secret ?: ($router->decrypted_password ?: "radius@123")) }}',
            coa_port: {{ $router->coa_port ?: 3799 }},
            api_port: {{ $router->api_port ?: 8728 }},
            username: '{{ addslashes($router->username ?: "admin") }}',
            password: '',
            use_ssl: {{ $router->use_ssl ? 'true' : 'false' }},
            is_active: {{ $router->is_active ? 'true' : 'false' }}
        },

        getRadiusScript() {
            const sIp = this.serverIp || '103.59.177.136';
            const sec = this.formData.radius_secret || 'radius@123';
            const coa = this.formData.coa_port || 3799;

            return `# 1. Add FreeRADIUS Server\n/radius add service=ppp,hotspot,login address=${sIp} secret="${sec}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS"\n\n# 2. Enable CoA / Disconnect Requests\n/radius incoming set accept=yes port=${coa}\n\n# 3. Enable PPPoE RADIUS AAA Accounting\n/ppp aaa set use-radius=yes accounting=yes interim-update=5m`;
        },

        copyToClipboard(text, label = null) {
            if (!text) return;
            const lbl = label || this.i18n.copiedDefault;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: `${lbl} ${this.i18n.copiedExclaim}`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    Swal.fire({
                        icon: 'success',
                        title: `${lbl} ${this.i18n.copiedExclaim}`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } catch (e) {
                    alert(this.i18n.copyFailed);
                }
                document.body.removeChild(ta);
            }
        },

        async testApiSocket() {
            if (!this.formData.ip_address) {
                alert(this.i18n.enterIpFirst);
                return;
            }

            this.testingSocket = true;

            try {
                const res = await fetch(`{{ route('tenant.network.mikrotik.test') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        router_id: {{ $router->id }},
                        ip_address: this.formData.ip_address,
                        api_port: this.formData.api_port || 8728,
                        username: this.formData.username || 'admin',
                        password: this.formData.password || '',
                        use_ssl: this.formData.use_ssl
                    })
                });
                const data = await res.json();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? this.i18n.connectionSuccess : this.i18n.connectionFailed,
                        html: `<div class='text-xs text-slate-600 mt-1'>${data.message}` + 
                              (data.ros_version ? `<br><span class='font-mono font-bold text-cyan-800'>${this.i18n.routerOsLabel}: ${data.ros_version} (${data.model || ''})</span>` : '') + 
                              `</div>`,
                        confirmButtonColor: data.success ? '#0891b2' : '#e11d48'
                    });
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert(this.i18n.socketNetworkError);
            } finally {
                this.testingSocket = false;
            }
        }
    };
}
</script>
@endpush

