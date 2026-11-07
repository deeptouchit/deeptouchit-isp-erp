@extends('tenant.layouts.app')

@section('title', 'Add NAS Gateway - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $routersData = $tenantRouters->map(function($r) {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'ip' => $r->ip_address,
            'radius_secret' => $r->decrypted_radius_secret ?: ($r->decrypted_password ?: 'radius@123'),
            'coa_port' => $r->coa_port ?: 3799,
        ];
    })->values();
@endphp

<div class="space-y-3" x-data="nasCreateManager()">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-violet-50 text-violet-600 border border-violet-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Register FreeRADIUS NAS Gateway</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-violet-50 text-violet-700 border border-violet-200">
                    FreeRADIUS 3.0
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.network.nas') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Back to Gateways</span>
            </a>
        </div>
    </div>

    <!-- Main Form Container -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <form action="{{ route('tenant.network.nas.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf

            <!-- Quick Auto-Fill from Fleet Router Card -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                        Quick Import from Fleet MikroTik Router (Optional)
                    </span>
                    <span class="text-[10.5px] text-slate-400">Auto-fills IP, Identity, Secret &amp; CoA Port</span>
                </div>
                <div class="max-w-md">
                    <select @change="onRouterSelect($event.target.value)" 
                            class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">-- Select Fleet Router to Auto-Populate --</option>
                        @foreach($tenantRouters as $r)
                        <option value="{{ $r->id }}" {{ (string)$preselectedRouterId === (string)$r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->ip_address }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 1. Core Identity & Network Addressing -->
            <div class="space-y-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">1. Gateway Identity &amp; Platform</span>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            NAS Shortname / Identifier <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="shortname" 
                               x-model="formData.shortname" 
                               required 
                               placeholder="e.g. CORE-CCR2004-DHAKA" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            NAS IP / Host Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="nasname" 
                               x-model="formData.nasname" 
                               required 
                               placeholder="e.g. 103.59.177.10" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Platform / Vendor <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" 
                                x-model="formData.type" 
                                required 
                                class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="mikrotik">MikroTik RouterOS</option>
                            <option value="cisco">Cisco IOS</option>
                            <option value="juniper">Juniper JunOS</option>
                            <option value="huawei">Huawei VRP</option>
                            <option value="accel-ppp">Accel-PPP BRAS</option>
                            <option value="other">Generic RADIUS Client</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. RADIUS Ports & Secret -->
            <div class="space-y-3 pt-2">
                <div class="border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">2. Authentication, CoA &amp; Shared Secret</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            RADIUS Secret <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showSecret ? 'text' : 'password'" 
                                   name="secret" 
                                   x-model="formData.secret" 
                                   required 
                                   placeholder="radius@123" 
                                   class="w-full h-[34px] pl-3 pr-8 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <button type="button" 
                                    @click="showSecret = !showSecret" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="fas text-xs" :class="showSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Auth / Acct Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="ports" 
                               x-model="formData.ports" 
                               required 
                               placeholder="1812" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            CoA / Disconnect Port <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="coa_port" 
                               x-model="formData.coa_port" 
                               required 
                               placeholder="3799" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>
            </div>

            <!-- 3. Link to Fleet Router & Optional Properties -->
            <div class="space-y-3 pt-2">
                <div class="border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">3. Fleet Router Association &amp; Meta</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Linked Fleet Router
                        </label>
                        <select name="router_id" 
                                x-model="formData.router_id" 
                                class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">-- Standalone (No Fleet Link) --</option>
                            @foreach($tenantRouters as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Virtual Server Tag (Optional)
                        </label>
                        <input type="text" 
                               name="server" 
                               x-model="formData.server" 
                               placeholder="e.g. default" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            SNMP Community (Optional)
                        </label>
                        <input type="text" 
                               name="community" 
                               x-model="formData.community" 
                               placeholder="e.g. public" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Description / Notes (Optional)
                    </label>
                    <input type="text" 
                           name="description" 
                           x-model="formData.description" 
                           placeholder="e.g. Core BRAS handling Zone A subscribers" 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>

            <!-- 4. Active Status Checkbox -->
            <div class="border-t border-slate-100 pt-3">
                <div class="flex items-center gap-2">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1" 
                           x-model="formData.is_active" 
                           class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                    <label for="is_active" class="text-xs text-slate-700 font-medium cursor-pointer">
                        Enable this NAS client immediately in FreeRADIUS configuration
                    </label>
                </div>
            </div>

            <!-- Form Footer Actions -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <a href="{{ route('tenant.network.nas') }}" 
                   class="h-[34px] px-4 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition flex items-center justify-center cursor-pointer">
                    Cancel
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>Register NAS Gateway</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function nasCreateManager() {
    return {
        showSecret: false,
        routers: @json($routersData),

        formData: {
            shortname: '',
            nasname: '',
            type: 'mikrotik',
            ports: 1812,
            coa_port: 3799,
            secret: 'radius@123',
            router_id: '{{ $preselectedRouterId ?? "" }}',
            server: '',
            community: '',
            description: '',
            is_active: true
        },

        init() {
            if (this.formData.router_id) {
                this.onRouterSelect(this.formData.router_id);
            }
        },

        onRouterSelect(routerId) {
            if (!routerId) return;
            const router = this.routers.find(r => r.id == routerId);
            if (router) {
                this.formData.router_id = router.id;
                if (!this.formData.shortname) {
                    this.formData.shortname = router.name;
                }
                if (!this.formData.nasname) {
                    this.formData.nasname = router.ip;
                }
                if (router.radius_secret) {
                    this.formData.secret = router.radius_secret;
                }
                if (router.coa_port) {
                    this.formData.coa_port = router.coa_port;
                }
            }
        }
    };
}
</script>
@endpush
