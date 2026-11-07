@extends('tenant.layouts.app')

@section('title', 'Create Internet Package - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $routersJson = $routers->map(function($r) {
        return ['id' => $r->id, 'name' => $r->name, 'ip' => $r->ip_address];
    })->values();

    $poolsJson = $ipPools->map(function($p) {
        return [
            'id' => $p->id,
            'router_id' => $p->router_id,
            'name' => $p->name,
            'type' => $p->pool_type,
            'range' => $p->addresses_display ?? ''
        ];
    })->values();
@endphp

<div class="space-y-3" x-data="packageCreateManager()">

    <!-- 1. Top Header Bar (Icon + Title + Back Button ONLY) -->
    <div class="flex items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Create Internet Package</h2>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('tenant.network.packages') }}"
               class="h-[34px] px-3.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Back to Packages</span>
            </a>
        </div>
    </div>

    <!-- 2. Main Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <form action="{{ route('tenant.network.packages.store') }}" method="POST" class="p-4 space-y-4">
            @csrf

            <!-- Section 1: Commercial Pricing & Validity -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-xs font-bold">1</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Commercial Pricing &amp; Validity</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Package Display Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Package Display Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="package_name"
                               x-model="form.package_name"
                               @input="autoGenerateProfileName()"
                               required
                               placeholder="e.g. 10 Mbps Home Starter"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:border-cyan-500 font-medium" />
                        @error('package_name')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Price (৳) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="price"
                               x-model="form.price"
                               required
                               placeholder="500.00"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-semibold text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('price')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Validity Days -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Validity (Days) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="validity_days"
                               x-model="form.validity_days"
                               required
                               placeholder="30"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('validity_days')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Package Code (Optional) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Package Code <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text"
                               name="code"
                               x-model="form.code"
                               placeholder="Auto-generated if blank (e.g. PKG-A1B2)"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                        @error('code')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description / Notes -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Description / Commercial Notes <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text"
                               name="description"
                               x-model="form.description"
                               placeholder="Commercial details for customer portal & billing invoices"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                        @error('description')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Bandwidth & Speeds Allocation -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">2</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Bandwidth &amp; Speeds Allocation</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <!-- Download Speed -->
                    <div>
                        <label class="block text-[11px] font-semibold text-cyan-800 mb-1">
                            Download (Mbps) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="download_speed"
                               x-model="form.download_speed"
                               required
                               placeholder="10"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-cyan-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('download_speed')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Upload Speed -->
                    <div>
                        <label class="block text-[11px] font-semibold text-purple-800 mb-1">
                            Upload (Mbps) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="upload_speed"
                               x-model="form.upload_speed"
                               required
                               placeholder="10"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-purple-800 focus:outline-hidden focus:border-cyan-500" />
                        @error('upload_speed')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Facebook Speed -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            Facebook (M)
                        </label>
                        <input type="text"
                               name="facebook_speed"
                               x-model="form.facebook_speed"
                               placeholder="0"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>

                    <!-- YouTube Speed -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            YouTube (M)
                        </label>
                        <input type="text"
                               name="youtube_speed"
                               x-model="form.youtube_speed"
                               placeholder="0"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>

                    <!-- BDIX Speed -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">
                            BDIX (M)
                        </label>
                        <input type="text"
                               name="bdix_speed"
                               x-model="form.bdix_speed"
                               placeholder="0"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>
                </div>
            </div>

            <!-- Section 3: MikroTik RouterOS & RADIUS Profile Policy -->
            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60 text-slate-800">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">3</span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">MikroTik RouterOS &amp; RADIUS Profile Policies</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Target Router -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Target Router
                        </label>
                        <select name="router_id"
                                x-model="form.router_id"
                                @change="onRouterChange()"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500">
                            <option value="">All Routers (Global Fleet)</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                            @endforeach
                        </select>
                        @error('router_id')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Profile Name (RouterOS) -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Profile Name (RouterOS) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               x-model="form.name"
                               required
                               placeholder="e.g. 10Mbps"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono font-semibold text-indigo-900 focus:outline-hidden focus:border-cyan-500" />
                        @error('name')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Service Type -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Service Type <span class="text-rose-500">*</span>
                        </label>
                        <select name="service_type"
                                x-model="form.service_type"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500 font-semibold">
                            <option value="pppoe">PPPoE Profile</option>
                            <option value="hotspot">Hotspot Profile</option>
                            <option value="static">Static IP</option>
                        </select>
                        @error('service_type')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Local Address -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Local Address <span class="text-slate-400 font-normal">(Gateway IP)</span>
                        </label>
                        <input type="text"
                               name="local_address"
                               x-model="form.local_address"
                               placeholder="e.g. 10.10.10.1"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                        @error('local_address')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remote Address / IP Pool -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Remote Address / IP Pool
                        </label>
                        <select name="ip_pool_id"
                                x-model="form.ip_pool_id"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500">
                            <option value="">Default Pool</option>
                            @foreach($ipPools as $p)
                                <option value="{{ $p->id }}"
                                        x-show="!form.router_id || '{{ $p->router_id }}' === String(form.router_id) || '{{ $p->router_id }}' === ''"
                                        :disabled="form.router_id && '{{ $p->router_id }}' !== '' && '{{ $p->router_id }}' !== String(form.router_id)">
                                    {{ $p->name }} {{ $p->addresses_display && $p->addresses_display !== '-' ? '('.$p->addresses_display.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('ip_pool_id')
                            <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Only One -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Only One Session
                        </label>
                        <select name="only_one"
                                x-model="form.only_one"
                                class="w-full h-[34px] px-2.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500">
                            <option value="default">default</option>
                            <option value="yes">yes (Single Session)</option>
                            <option value="no">no (Multiple Sessions)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Address List -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Address List <span class="text-slate-400 font-normal">(RouterOS Firewall)</span>
                        </label>
                        <input type="text"
                               name="address_list"
                               x-model="form.address_list"
                               placeholder="e.g. ALLOWED_USERS"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            RouterOS Comment <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text"
                               name="comment"
                               x-model="form.comment"
                               placeholder="e.g. 10M-HOME-PROFILE"
                               class="w-full h-[34px] px-3 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-hidden focus:border-cyan-500" />
                    </div>
                </div>
            </div>

            <!-- Section 4: Automation & Activation Flags -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-wrap items-center gap-6">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox"
                           name="sync_now"
                           value="1"
                           x-model="form.sync_now"
                           class="w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                    <span class="text-xs font-semibold text-slate-700">Push Profile to RouterOS Immediately</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           x-model="form.is_active"
                           class="w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                    <span class="text-xs font-semibold text-slate-700">Active Status (Enabled for Subscriptions)</span>
                </label>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <a href="{{ route('tenant.network.packages') }}"
                   class="h-[34px] px-4 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs shadow-2xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    Cancel
                </a>
                <button type="submit"
                        class="h-[34px] px-5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-[10px]"></i>
                    <span>Save Internet Package</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function packageCreateManager() {
    return {
        form: {
            package_name: '{{ old('package_name', '') }}',
            name: '{{ old('name', '') }}',
            code: '{{ old('code', '') }}',
            price: '{{ old('price', '500') }}',
            validity_days: '{{ old('validity_days', '30') }}',
            download_speed: '{{ old('download_speed', '10') }}',
            upload_speed: '{{ old('upload_speed', '10') }}',
            facebook_speed: '{{ old('facebook_speed', '') }}',
            youtube_speed: '{{ old('youtube_speed', '') }}',
            bdix_speed: '{{ old('bdix_speed', '') }}',
            service_type: '{{ old('service_type', 'pppoe') }}',
            router_id: '{{ old('router_id', '') }}',
            local_address: '{{ old('local_address', '') }}',
            ip_pool_id: '{{ old('ip_pool_id', '') }}',
            only_one: '{{ old('only_one', 'default') }}',
            address_list: '{{ old('address_list', '') }}',
            comment: '{{ old('comment', '') }}',
            description: '{{ old('description', '') }}',
            sync_now: true,
            is_active: true
        },
        autoGenerateProfileName() {
            if (!this.form.name || this.form.name === this.lastAutoName) {
                const clean = this.form.package_name
                    .trim()
                    .replace(/[^a-zA-Z0-9_-]/g, '_')
                    .toLowerCase();
                this.form.name = clean;
                this.lastAutoName = clean;
            }
        },
        lastAutoName: '',
        onRouterChange() {
            // Optional router change hook
        }
    };
}
</script>
@endpush
