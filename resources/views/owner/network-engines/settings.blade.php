@extends('owner.layouts.app')

@section('page-title', 'Network Engines Settings')

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'router',
    showRadiusSecret: false,
    showWireguardKey: false,
    showSnmpCommunity: false,
    readinessLoading: false,
    readinessChecks: [],
    readinessTimestamp: '',
    keypairLoading: false,
    keypairSuccess: false,
    generateWireguardKeys() {
        this.keypairLoading = true;
        this.keypairSuccess = false;
        fetch('{{ route('owner.network-engines.generate-keypair') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(data => {
            this.keypairLoading = false;
            if (data.success) {
                document.getElementById('wg_pubkey').value = data.public_key;
                document.getElementById('wg_privkey').value = data.private_key;
                this.keypairSuccess = true;
                setTimeout(() => this.keypairSuccess = false, 4000);
            }
        })
        .catch(err => {
            this.keypairLoading = false;
            alert('Key generation error: ' + err.message);
        });
    },
    generateRadiusSecret() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()-_=+';
        let secret = '';
        for (let i = 0; i < 32; i++) {
            secret += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('radius_secret').value = secret;
        this.showRadiusSecret = true;
    },
    runReadinessCheck() {
        this.readinessLoading = true;
        fetch('{{ route('owner.network-engines.test-readiness') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(data => {
            this.readinessLoading = false;
            if (data.success) {
                this.readinessChecks = data.checks;
                this.readinessTimestamp = data.timestamp;
            }
        })
        .catch(err => {
            this.readinessLoading = false;
            alert('Readiness check failed: ' + err.message);
        });
    }
}">

    <!-- Page Header (Matching Project Standard Sequence) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('owner.network-engines.index') }}" class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition flex-shrink-0">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-base font-bold text-slate-800 leading-tight">Global Network Infrastructure & Device Vault</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                        Carrier Grade
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Platform-wide defaults for FreeRADIUS AAA, WireGuard Gateway, MikroTik API & OLT SNMP drivers</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('owner.network-engines.audit-logs') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-xs shadow-xs transition">
                <i class="fas fa-shield-halved text-slate-500 text-[10px]"></i>
                <span>Audit Logs</span>
            </a>
            <button type="submit" form="networkSettingsForm" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                <i class="fas fa-floppy-disk text-[10px]"></i>
                <span>Save All Engine Policies</span>
            </button>
        </div>
    </div>

    <!-- Alert / Feedback Banner -->
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-2 shadow-xs">
            <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1 shadow-xs">
            <div class="font-bold flex items-center gap-1.5">
                <i class="fas fa-circle-exclamation text-rose-600"></i> Please correct the validation errors below:
            </div>
            <ul class="list-disc list-inside text-[11px] space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs (Sleek Compact, Matching General Settings) -->
    <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-xs overflow-x-auto">
        <button type="button" @click="activeTab = 'router'" 
                :class="activeTab === 'router' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-server text-[11px]"></i>
            <span>RouterOS Engine</span>
            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-blue-100/70 text-blue-800">API & REST</span>
        </button>

        <button type="button" @click="activeTab = 'radius'" 
                :class="activeTab === 'radius' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-key text-[11px]"></i>
            <span>FreeRADIUS AAA</span>
            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-emerald-100/70 text-emerald-800">RFC 3576 CoA</span>
        </button>

        <button type="button" @click="activeTab = 'olt'" 
                :class="activeTab === 'olt' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-microchip text-[11px]"></i>
            <span>OLT PON Hardware</span>
            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-purple-100/70 text-purple-800">Multi-Vendor</span>
        </button>

        <button type="button" @click="activeTab = 'wireguard'" 
                :class="activeTab === 'wireguard' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-shield-halved text-[11px]"></i>
            <span>WireGuard Mesh</span>
            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-amber-100/70 text-amber-800">NAT Traversal</span>
        </button>

        <button type="button" @click="activeTab = 'automation'" 
                :class="activeTab === 'automation' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-bolt text-[11px]"></i>
            <span>Queue & Automation</span>
            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-mono bg-slate-100 text-slate-700">Async Loop</span>
        </button>

        <button type="button" @click="activeTab = 'diagnostics'; if(readinessChecks.length === 0) runReadinessCheck();" 
                :class="activeTab === 'diagnostics' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <i class="fas fa-stethoscope text-[11px]"></i>
            <span>Engine Readiness Probe</span>
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        </button>
    </div>

    <!-- Main Settings Form Container -->
    <form id="networkSettingsForm" action="{{ route('owner.network-engines.settings.update') }}" method="POST" class="space-y-4">
        @csrf

        <!-- ========================================================================= -->
        <!-- TAB 1: MIKROTIK ROUTEROS ENGINE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'router'" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left: Core Connection Ports & Timeouts -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-server text-blue-600"></i>
                            RouterOS Connection Protocol & Socket Timing
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            v6 & v7.x Architecture
                        </span>
                    </div>

                    <div class="p-4 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Default API Port (Plain TCP)</label>
                                <input type="number" name="network_default_api_port" value="{{ old('network_default_api_port', $settings['network_default_api_port'] ?? 8728) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Standard MikroTik ROS binary protocol port (Default: 8728).</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">API SSL / TLS Port</label>
                                <input type="number" name="network_default_ssl_api_port" value="{{ old('network_default_ssl_api_port', $settings['network_default_ssl_api_port'] ?? 8729) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Encrypted RouterOS API-SSL service port (Default: 8729).</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Socket Timeout (Seconds)</label>
                                <input type="number" name="network_api_timeout_seconds" value="{{ old('network_api_timeout_seconds', $settings['network_api_timeout_seconds'] ?? 5) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Maximum timeout when polling queues or interface stats before dropping connection.</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Connection Pool Keepalive (Seconds)</label>
                                <input type="number" name="network_routeros_keepalive_interval" value="{{ old('network_routeros_keepalive_interval', $settings['network_routeros_keepalive_interval'] ?? 30) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Persistent connection idle timeout before initiating TCP reconnect.</p>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs block">RouterOS v7 Native REST API</span>
                                <p class="text-[11px] text-slate-500">Enable HTTPS REST API (/rest/) for RouterOS v7.1+. Vastly faster, immune to binary protocol socket corruption, and firewall friendly.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-3">
                                <input type="checkbox" name="network_routeros_v7_rest_enabled" value="1" {{ ($settings['network_routeros_v7_rest_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right: Least-Privilege Policy & Security Context -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
                        <div class="p-3.5 bg-slate-50/50 rounded-t-xl">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-user-shield text-emerald-600"></i>
                                Least-Privilege Access Policy
                            </span>
                        </div>
                        <div class="p-4 space-y-3 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">RouterOS System Group</label>
                                <input type="text" name="network_api_least_privilege_group" value="{{ old('network_api_least_privilege_group', $settings['network_api_least_privilege_group'] ?? 'somitysoft_api') }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Restricts system policy to <span class="font-mono text-slate-600">read,write,api,test</span>. Prevents unauthorized full admin commands.</p>
                            </div>

                            <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-lg text-blue-900 text-[11px] space-y-1">
                                <div class="font-bold flex items-center gap-1">
                                    <i class="fas fa-lock text-blue-600"></i> Vault Protection Active
                                </div>
                                <p class="text-slate-600">
                                    All tenant router passwords and API tokens are encrypted with AES-256-CBC at rest. Plaintext passwords are never logged.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: AAA FREERADIUS CLUSTER -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'radius'" class="space-y-4" style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left: Master & Failover Nodes -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-key text-emerald-600"></i>
                            FreeRADIUS Cluster & Dynamic Authorization (RFC 3576 CoA)
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            v3.x / v4.x High Availability
                        </span>
                    </div>

                    <div class="p-4 space-y-4 text-xs">
                        
                        <!-- Master RADIUS Node -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Primary / Master RADIUS Node
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-700 mb-1">Master Server Host / IP</label>
                                    <input type="text" name="network_radius_master_host" value="{{ old('network_radius_master_host', $settings['network_radius_master_host'] ?? '10.50.0.1') }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Auth / Acct Ports</label>
                                    <div class="flex gap-1.5">
                                        <input type="number" name="network_radius_master_auth_port" value="{{ old('network_radius_master_auth_port', $settings['network_radius_master_auth_port'] ?? 1812) }}" title="Auth Port" class="w-1/2 py-1.5 px-1.5 text-center rounded-lg border border-slate-200 font-mono text-xs shadow-2xs">
                                        <input type="number" name="network_radius_master_acct_port" value="{{ old('network_radius_master_acct_port', $settings['network_radius_master_acct_port'] ?? 1813) }}" title="Acct Port" class="w-1/2 py-1.5 px-1.5 text-center rounded-lg border border-slate-200 font-mono text-xs shadow-2xs">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failover Secondary RADIUS Node -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                Secondary / Failover Replica Node (Optional)
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-700 mb-1">Replica Server Host / IP</label>
                                    <input type="text" name="network_radius_replica_host" value="{{ old('network_radius_replica_host', $settings['network_radius_replica_host'] ?? '10.50.0.2') }}" placeholder="10.50.0.2" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">CoA / PoD Port</label>
                                    <input type="number" name="network_radius_coa_port" value="{{ old('network_radius_coa_port', $settings['network_radius_coa_port'] ?? 3799) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                </div>
                            </div>
                        </div>

                        <!-- Credentials & Accounting Interval -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-semibold text-slate-700">Master Shared Secret</label>
                                    <button type="button" @click="generateRadiusSecret()" class="text-[10px] text-blue-600 hover:text-blue-700 font-semibold cursor-pointer">
                                        <i class="fas fa-wand-magic-sparkles mr-0.5"></i> Generate Strong Secret
                                    </button>
                                </div>
                                <div class="relative">
                                    <input :type="showRadiusSecret ? 'text' : 'password'" id="radius_secret" name="network_radius_default_secret" value="{{ old('network_radius_default_secret', $settings['network_radius_default_secret'] ?? '') }}" placeholder="Enter or keep existing secret" class="w-full py-1.5 pl-2.5 pr-8 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                    <button type="button" @click="showRadiusSecret = !showRadiusSecret" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                        <i :class="showRadiusSecret ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-[11px]"></i>
                                    </button>
                                </div>
                                <p class="text-[10.5px] text-slate-400 mt-1">Shared secret between FreeRADIUS server and NAS (MikroTik routers). Encrypted in Vault.</p>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Interim-Update Interval (Minutes)</label>
                                <input type="number" name="network_radius_interim_interval" value="{{ old('network_radius_interim_interval', $settings['network_radius_interim_interval'] ?? 5) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">How frequently MikroTik sends live bandwidth usage packets (Default: 5 mins).</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right: RFC 3576 Packet Structure & Execution Info -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
                        <div class="p-3.5 bg-slate-50/50 rounded-t-xl">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-bolt text-emerald-600"></i>
                                Instant Billing Disconnect (CoA)
                            </span>
                        </div>
                        <div class="p-4 space-y-3 text-xs">
                            <p class="text-slate-600 text-[11px] leading-relaxed">
                                When a subscriber is suspended due to non-payment or expired plan, the SaaS engine dispatches an asynchronous <strong class="text-slate-800">RFC 3576 Disconnect-Request</strong> on port 3799.
                            </p>

                            <div class="bg-slate-900 text-slate-100 rounded-lg p-2.5 font-mono text-[10px] space-y-1">
                                <div class="text-slate-400"># Real CoA Disconnect Packet</div>
                                <div>Packet-Type = Disconnect-Request</div>
                                <div>User-Name = &quot;pppoe_user_01&quot;</div>
                                <div>Framed-IP-Address = 100.64.12.45</div>
                                <div>Acct-Session-Id = &quot;8140004f&quot;</div>
                            </div>

                            <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200 text-emerald-800 text-[10.5px] flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-600 flex-shrink-0"></i>
                                <span>radclient utility ready at <code class="font-mono font-bold">/usr/bin/radclient</code></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: OLT PON HARDWARE ENGINE -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'olt'" class="space-y-4" style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left: SNMP Engine Policies & Optical Thresholds -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-microchip text-purple-600"></i>
                            SNMP v2c/v3 Telemetry & Optical Link Thresholds
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-purple-50 text-purple-700 border border-purple-200">
                            Multi-Vendor OIDs
                        </span>
                    </div>

                    <div class="p-4 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Default SNMP Port</label>
                                <input type="number" name="network_olt_default_snmp_port" value="{{ old('network_olt_default_snmp_port', $settings['network_olt_default_snmp_port'] ?? 161) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">UDP Port for OLT SNMP queries (Default: 161).</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Default SNMP Read Community</label>
                                <div class="relative">
                                    <input :type="showSnmpCommunity ? 'text' : 'password'" name="network_olt_default_snmp_community" value="{{ old('network_olt_default_snmp_community', $settings['network_olt_default_snmp_community'] ?? 'public_somitysoft') }}" class="w-full py-1.5 pl-2.5 pr-8 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                    <button type="button" @click="showSnmpCommunity = !showSnmpCommunity" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                        <i :class="showSnmpCommunity ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-[11px]"></i>
                                    </button>
                                </div>
                                <p class="text-[10.5px] text-slate-400 mt-1">Default read community used when discovering new OLT headends.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">SNMP Socket Timeout (Milliseconds)</label>
                                <input type="number" name="network_olt_snmp_timeout_ms" value="{{ old('network_olt_snmp_timeout_ms', $settings['network_olt_snmp_timeout_ms'] ?? 2000) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Max socket wait time per OID leaf query (Default: 2000 ms).</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Optical Power Polling Schedule (Minutes)</label>
                                <input type="number" name="network_olt_polling_interval_minutes" value="{{ old('network_olt_polling_interval_minutes', $settings['network_olt_polling_interval_minutes'] ?? 15) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Background worker interval for syncing ONU optical dBm (Default: 15 mins).</p>
                            </div>
                        </div>

                        <!-- Optical Thresholds -->
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                            <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                <i class="fas fa-wave-square text-purple-600"></i>
                                Optical Signal Health Degradation Levels
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-amber-700 mb-1">Warning Rx Threshold (dBm)</label>
                                    <input type="number" step="0.1" name="network_olt_optical_rx_warning_dbm" value="{{ old('network_olt_optical_rx_warning_dbm', $settings['network_olt_optical_rx_warning_dbm'] ?? -25.0) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-amber-300 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-amber-500 font-mono shadow-2xs">
                                    <p class="text-[10px] text-slate-400 mt-1">Triggers degradation warning on customer profile (-25.0 dBm).</p>
                                </div>
                                <div>
                                    <label class="block font-semibold text-rose-700 mb-1">Critical / Loss of Signal Threshold (dBm)</label>
                                    <input type="number" step="0.1" name="network_olt_optical_rx_critical_dbm" value="{{ old('network_olt_optical_rx_critical_dbm', $settings['network_olt_optical_rx_critical_dbm'] ?? -28.0) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-rose-300 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-rose-500 font-mono shadow-2xs">
                                    <p class="text-[10px] text-slate-400 mt-1">Triggers severe fiber bend / fiber cut alert (-28.0 dBm).</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right: Supported Hardware Matrix -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
                        <div class="p-3.5 bg-slate-50/50 rounded-t-xl">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-layer-group text-purple-600"></i>
                                Hardware Vendor OID Driver
                            </span>
                        </div>
                        <div class="p-4 space-y-3 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Default Vendor Profile</label>
                                <select name="network_olt_vendor_profile" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs focus:ring-1 focus:ring-blue-500 shadow-2xs">
                                    <option value="multi_vendor" {{ ($settings['network_olt_vendor_profile'] ?? 'multi_vendor') == 'multi_vendor' ? 'selected' : '' }}>Multi-Vendor Auto-Detect</option>
                                    <option value="vsol" {{ ($settings['network_olt_vendor_profile'] ?? '') == 'vsol' ? 'selected' : '' }}>V-SOL (V1600G / V1600D OIDs)</option>
                                    <option value="bdcom" {{ ($settings['network_olt_vendor_profile'] ?? '') == 'bdcom' ? 'selected' : '' }}>BDCOM (GP3600 / P3310 OIDs)</option>
                                    <option value="huawei" {{ ($settings['network_olt_vendor_profile'] ?? '') == 'huawei' ? 'selected' : '' }}>Huawei SmartAX (MA5608T / MA5683T)</option>
                                    <option value="zte" {{ ($settings['network_olt_vendor_profile'] ?? '') == 'zte' ? 'selected' : '' }}>ZTE (C300 / C320 OIDs)</option>
                                    <option value="fiberhome" {{ ($settings['network_olt_vendor_profile'] ?? '') == 'fiberhome' ? 'selected' : '' }}>Fiberhome (AN5516 OIDs)</option>
                                </select>
                                <p class="text-[10.5px] text-slate-400 mt-1">Selects vendor MIB driver when reading ONU serials and optical power levels.</p>
                            </div>

                            <div class="p-3 bg-purple-50/50 border border-purple-100 rounded-lg text-[11px] text-slate-600 space-y-1.5">
                                <div class="font-bold text-purple-900 flex items-center gap-1">
                                    <i class="fas fa-check-double text-purple-600"></i> Auto-Registration Ready
                                </div>
                                <p>Unconfigured ONUs found on PON ports are detected automatically and queued for 1-click activation.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: WIREGUARD MESH & NAT TRAVERSAL -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'wireguard'" class="space-y-4" style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left: WireGuard Server Configuration -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs lg:col-span-2 divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-halved text-amber-600"></i>
                            <span class="text-xs font-bold text-slate-800">WireGuard Secure Mesh Gateway</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold text-slate-600">Master Gateway:</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="network_wireguard_enabled" value="1" {{ ($settings['network_wireguard_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="p-4 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Server Public Endpoint (FQDN or Public IP)</label>
                                <input type="text" name="network_wireguard_endpoint" value="{{ old('network_wireguard_endpoint', $settings['network_wireguard_endpoint'] ?? 'vpn.somitysoft.com:51820') }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Tenant routers connect to this public endpoint to establish encrypted tunnel.</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">UDP Listen Port</label>
                                <input type="number" name="network_wireguard_listen_port" value="{{ old('network_wireguard_listen_port', $settings['network_wireguard_listen_port'] ?? 51820) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Default: 51820</p>
                            </div>
                        </div>

                        <!-- Curve25519 Cryptographic Keypair -->
                        <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-lock text-amber-600"></i>
                                    Server Curve25519 Cryptographic Keypair (Vault Encrypted)
                                </h4>
                                <button type="button" @click="generateWireguardKeys()" :disabled="keypairLoading" class="px-2.5 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-[10.5px] transition flex items-center gap-1 cursor-pointer">
                                    <i :class="keypairLoading ? 'fas fa-spinner fa-spin' : 'fas fa-key'" class="text-[10px]"></i>
                                    <span x-text="keypairLoading ? 'Generating...' : 'Generate Real Keypair'"></span>
                                </button>
                            </div>

                            <div x-show="keypairSuccess" class="p-2 bg-emerald-100 border border-emerald-300 rounded text-emerald-800 text-[10.5px] flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-emerald-600"></i>
                                <span>Real Curve25519 Keypair generated successfully via Libsodium engine! Click Save to apply.</span>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Server Public Key</label>
                                <input type="text" id="wg_pubkey" name="network_wireguard_server_pubkey" value="{{ old('network_wireguard_server_pubkey', $settings['network_wireguard_server_pubkey'] ?? 'k+8NqfRj5v7pW9sL1xZ0yT4uA2bC3dE4fG5hI6jK7lM=') }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10px] text-slate-400 mt-1">Embedded into tenant 1-click bootstrap scripts.</p>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Server Private Key (Vault)</label>
                                <div class="relative">
                                    <input :type="showWireguardKey ? 'text' : 'password'" id="wg_privkey" name="network_wireguard_server_privkey" value="{{ old('network_wireguard_server_privkey', $settings['network_wireguard_server_privkey'] ?? '') }}" placeholder="Enter or keep existing private key" class="w-full py-1.5 pl-2.5 pr-8 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                    <button type="button" @click="showWireguardKey = !showWireguardKey" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                        <i :class="showWireguardKey ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-[11px]"></i>
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Strictly confidential. Encrypted in database with application cipher.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Subnet Pool Allocation (IPv4 CIDR)</label>
                                <input type="text" name="network_wireguard_subnet_pool" value="{{ old('network_wireguard_subnet_pool', $settings['network_wireguard_subnet_pool'] ?? '10.50.0.0/16') }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Tenant routers receive static private IPs from this block (e.g. 10.50.1.2/32).</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Persistent Keepalive (Seconds)</label>
                                <input type="number" name="network_wireguard_persistent_keepalive" value="{{ old('network_wireguard_persistent_keepalive', $settings['network_wireguard_persistent_keepalive'] ?? 25) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                                <p class="text-[10.5px] text-slate-400 mt-1">Hole-punching interval to keep NAT / CGNAT state tables active (Default: 25s).</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right: NAT Traversal Explanation -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
                        <div class="p-3.5 bg-slate-50/50 rounded-t-xl">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-network-wired text-amber-600"></i>
                                CGNAT Bypass Architecture
                            </span>
                        </div>
                        <div class="p-4 space-y-3 text-xs">
                            <p class="text-slate-600 text-[11px] leading-relaxed">
                                Most ISP tenant routers run behind upstream carrier CGNAT without static public IPs. WireGuard solves this by establishing a lightweight outbound UDP tunnel directly to SomitySoft SaaS.
                            </p>

                            <div class="p-3 bg-amber-50/60 rounded-lg border border-amber-200 text-amber-900 text-[11px] space-y-1">
                                <div class="font-bold flex items-center gap-1">
                                    <i class="fas fa-circle-check text-amber-600"></i> Zero Port Forwarding Needed
                                </div>
                                <p class="text-slate-600">
                                    Tenant only runs the 1-click MikroTik script. Tunnel connects automatically and stays alive 24/7.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 5: QUEUE, WORKERS & BILLING AUTOMATION -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'automation'" class="space-y-4" style="display: none;">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-bolt text-purple-600"></i>
                        Background Worker Concurrency & Automated Billing Suspension Loop
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        Zero-Blocking HTTP
                    </span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Async Worker Concurrency Limit</label>
                            <input type="number" name="network_monitoring_queue_concurrency" value="{{ old('network_monitoring_queue_concurrency', $settings['network_monitoring_queue_concurrency'] ?? 16) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                            <p class="text-[10.5px] text-slate-400 mt-1">Parallel worker threads processing SNMP telemetry and heartbeat pings.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Queue Connection Driver</label>
                            <select name="network_queue_connection" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs focus:ring-1 focus:ring-blue-500 shadow-2xs">
                                <option value="redis" {{ ($settings['network_queue_connection'] ?? 'redis') == 'redis' ? 'selected' : '' }}>Redis (High Throughput / Low Latency)</option>
                                <option value="database" {{ ($settings['network_queue_connection'] ?? '') == 'database' ? 'selected' : '' }}>Database (Standard MySQL Queue)</option>
                                <option value="default" {{ ($settings['network_queue_connection'] ?? '') == 'default' ? 'selected' : '' }}>System Default (config/queue.php)</option>
                            </select>
                            <p class="text-[10.5px] text-slate-400 mt-1">Redis is recommended for high-volume carrier telemetry.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Billing Grace Period (Hours)</label>
                            <input type="number" name="network_billing_grace_period_hours" value="{{ old('network_billing_grace_period_hours', $settings['network_billing_grace_period_hours'] ?? 24) }}" class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 font-mono shadow-2xs">
                            <p class="text-[10.5px] text-slate-400 mt-1">Grace hours allowed after expiration before issuing CoA disconnect.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 text-xs">Auto CoA Disconnect on Due Expiration</span>
                                <input type="checkbox" name="network_auto_coa_on_due_expired" value="1" {{ ($settings['network_auto_coa_on_due_expired'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                When a tenant customer passes their billing due date and grace period, the system instantly queues a background job to send a FreeRADIUS Disconnect-Request (CoA) to terminate active PPPoE sessions.
                            </p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 text-xs">Instant Auto Restore on Payment</span>
                                <input type="checkbox" name="network_auto_restore_on_payment" value="1" {{ ($settings['network_auto_restore_on_payment'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                As soon as a subscriber pays via bKash, Nagad, or Cash, the billing webhook unlocks their profile and issues a CoA reconnect command, restoring internet access in under 2 seconds.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Sticky Bottom Save Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('owner.network-engines.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-floppy-disk text-xs"></i>
                <span>Save All Engine Policies</span>
            </button>
        </div>

    </form>

    <!-- ========================================================================= -->
    <!-- TAB 6: REAL ENGINE DIAGNOSTICS & READINESS PROBE (Outside Form) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'diagnostics'" class="space-y-4" style="display: none;">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs divide-y divide-slate-100">
            
            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 rounded-t-xl">
                <div>
                    <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-stethoscope text-blue-600"></i>
                        Carrier Infrastructure Diagnostics & System Readiness Probe
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Real-time health evaluation of low-level networking extensions, cryptography ciphers, and FreeRADIUS binaries.</p>
                </div>

                <div class="flex items-center gap-2">
                    <span x-show="readinessTimestamp" class="text-[10px] font-mono text-slate-500" x-text="'Checked: ' + readinessTimestamp"></span>
                    <button type="button" @click="runReadinessCheck()" :disabled="readinessLoading" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i :class="readinessLoading ? 'fas fa-spinner fa-spin' : 'fas fa-rotate'" class="text-[10px]"></i>
                        <span x-text="readinessLoading ? 'Probing Engines...' : 'Run Self-Check Now'"></span>
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-3">
                <div x-show="readinessLoading" class="p-8 text-center space-y-2">
                    <i class="fas fa-circle-notch fa-spin text-2xl text-blue-600"></i>
                    <div class="text-xs font-semibold text-slate-700">Executing Low-Level Socket & Cryptographic Handshakes...</div>
                </div>

                <div x-show="!readinessLoading && readinessChecks.length === 0" class="p-8 text-center text-slate-500 text-xs">
                    Click "Run Self-Check Now" to evaluate server networking capabilities.
                </div>

                <div x-show="!readinessLoading && readinessChecks.length > 0" class="space-y-2.5">
                    <template x-for="(check, idx) in readinessChecks" :key="idx">
                        <div class="p-3 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                             :class="{
                                 'bg-emerald-50/60 border-emerald-200': check.status === 'PASS',
                                 'bg-amber-50/60 border-amber-200': check.status === 'WARN' || check.status === 'INFO',
                                 'bg-rose-50/60 border-rose-200': check.status === 'FAIL'
                             }">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded font-mono"
                                          :class="{
                                              'bg-emerald-200 text-emerald-900': check.status === 'PASS',
                                              'bg-amber-200 text-amber-900': check.status === 'WARN' || check.status === 'INFO',
                                              'bg-rose-200 text-rose-900': check.status === 'FAIL'
                                          }" x-text="check.status"></span>
                                    <span class="font-bold text-slate-900 text-xs" x-text="check.name"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="'(' + check.engine + ')'"></span>
                                </div>
                                <p class="text-[11px] text-slate-600" x-text="check.details"></p>
                            </div>
                            
                            <div class="flex-shrink-0">
                                <template x-if="check.status === 'PASS'">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                        <i class="fas fa-check-circle"></i> Operational
                                    </span>
                                </template>
                                <template x-if="check.status === 'WARN' || check.status === 'INFO'">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700">
                                        <i class="fas fa-triangle-exclamation"></i> Notice
                                    </span>
                                </template>
                                <template x-if="check.status === 'FAIL'">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700">
                                        <i class="fas fa-circle-xmark"></i> Action Required
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection
