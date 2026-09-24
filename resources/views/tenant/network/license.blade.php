@extends('tenant.layouts.app')

@section('title', 'OLT License Governance & Lifecycle - ' . ($tenant->company_name ?? $tenant->name ?? 'SomitySoft'))

@section('content')
<div class="space-y-3.5" x-data="oltLicenseManager()" @click.away="activeMenuOlt = null">

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

    <!-- 1. Header Bar -->
    <div class="flex flex-wrap items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">OLT License Governance &amp; Lifecycle</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    <i class="fas fa-shield-alt text-[9px]"></i>
                    <span>Hardware Lock Management</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.network.olt') }}" class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-xs border border-slate-200 shadow-2xs transition flex items-center gap-1.5">
                <i class="fas fa-network-wired text-slate-400"></i>
                <span>OLT Fleet</span>
            </a>
            <button type="button" @click="window.location.reload()" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Refresh">
                <i class="fas fa-rotate-right text-xs"></i>
            </button>
        </div>
    </div>

    <!-- 2. Metric Cards Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
        <!-- Card 1: Total OLTs -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider block">Managed OLTs</span>
                <span class="text-lg font-bold font-mono text-slate-800">{{ $stats['total_olts'] ?? 0 }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm">
                <i class="fas fa-server"></i>
            </div>
        </div>

        <!-- Card 2: Unlimited / Permanent -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider block">Unlimited / Permanent</span>
                <span class="text-lg font-bold font-mono text-emerald-600">{{ $stats['unlimited_licenses'] ?? 0 }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm">
                <i class="fas fa-infinity"></i>
            </div>
        </div>

        <!-- Card 3: Expiring Soon / Warnings -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider block">Expiring (&le; 7 Days)</span>
                <span class="text-lg font-bold font-mono {{ ($stats['warning_licenses'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-700' }}">
                    {{ $stats['warning_licenses'] ?? 0 }}
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-sm">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <!-- Card 4: Active Time Limited -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider block">Active Time-Limited</span>
                <span class="text-lg font-bold font-mono text-purple-600">{{ $stats['active_limited'] ?? 0 }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <!-- 3. Filter Toolbar -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <form action="{{ route('tenant.network.license') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <div class="flex-1 min-w-[200px] relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search OLT name, IP, model or vendor..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
            </div>

            <select name="status" class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50/50 text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">All License Modes</option>
                <option value="unlimited" {{ request('status') === 'unlimited' ? 'selected' : '' }}>Unlimited / Permanent</option>
                <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>Expiring Soon (&le; 7 Days)</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Time-Limited</option>
            </select>

            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition shadow-2xs">
                Filter
            </button>

            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('tenant.network.license') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 4. Data Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600">
                        <th class="py-2 px-3 border-r border-slate-200 w-12 text-center">#</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-left">OLT Gateway / Model</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-left">Management IP &amp; Port</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-left">Vendor &amp; Firmware</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-center">License Limit Mode</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-center">Remaining Duration</th>
                        <th class="py-2 px-3 border-r border-slate-200 text-center">Auto-Renew Policy</th>
                        <th class="py-2 px-3 text-center w-36">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-normal">
                    @forelse($olts as $index => $olt)
                        <tr class="hover:bg-blue-50/30 transition">
                            <td class="py-2 px-3 border-r border-slate-100 text-center font-mono text-[11px] text-slate-400">
                                {{ $olts->firstItem() + $index }}
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-left">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center font-bold text-xs shrink-0">
                                        <i class="fas fa-network-wired text-[10px]"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('tenant.network.olt.show', $olt->id) }}" class="font-bold text-slate-900 hover:text-blue-600 block">
                                            {{ $olt->name }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ $olt->model ?? 'EPON OLT' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-left font-mono text-[11px] text-slate-700">
                                <span>{{ $olt->ip_address }}:{{ $olt->web_port ?? 80 }}</span>
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-left">
                                <span class="font-medium text-slate-800 block">{{ $olt->vendor ?? 'DN OPTIC' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block">{{ $olt->firmware_version ?? 'V3.4.66' }}</span>
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-center">
                                @if((int)$olt->license_limit === 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[9px]"></i> Unlimited
                                    </span>
                                @elseif($olt->is_license_warning)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                        <i class="fas fa-triangle-exclamation text-[9px]"></i> Expiring Soon
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fas fa-clock text-[9px]"></i> Time Limited
                                    </span>
                                @endif
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-center font-mono text-[11px]">
                                @if((int)$olt->license_limit === 0)
                                    <span class="text-emerald-700 font-bold">&infin; Permanent</span>
                                @elseif($olt->is_license_warning)
                                    <span class="text-amber-700 font-bold font-mono">{{ $olt->license_formatted }}</span>
                                @else
                                    <span class="text-slate-700 font-medium font-mono">{{ $olt->license_formatted }}</span>
                                @endif
                            </td>

                            <td class="py-2 px-3 border-r border-slate-100 text-center">
                                @if($olt->license_auto_renew)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Auto-unlocks when remaining hours &le; {{ $olt->license_renew_threshold_hours }}h">
                                        <i class="fas fa-bolt text-[9px]"></i> Auto (&le; {{ $olt->license_renew_threshold_hours }}h)
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10.5px]">Manual</span>
                                @endif
                            </td>

                            <td class="py-2 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Unlock / Update Action -->
                                    <button 
                                        type="button" 
                                        @click="openUnlockModal({{ json_encode($olt) }})"
                                        class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded text-xs shadow-2xs transition flex items-center gap-1 cursor-pointer"
                                        title="Unlock / Update License">
                                        <i class="fas fa-key text-[10px]"></i>
                                        <span>Unlock / Extend</span>
                                    </button>

                                    <!-- Live Sync Action -->
                                    <button 
                                        type="button" 
                                        @click="syncLicense({{ $olt->id }})"
                                        :disabled="syncingOltId === {{ $olt->id }}"
                                        class="p-1 bg-white hover:bg-slate-50 text-slate-700 rounded border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                                        title="Sync Live Telemetry">
                                        <i class="fas fa-rotate-right text-xs" :class="{ 'fa-spin text-blue-600': syncingOltId === {{ $olt->id }} }"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                <i class="fas fa-key text-slate-300 text-2xl mb-1.5 block"></i>
                                No OLT gateways found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($olts->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-xs">
                {{ $olts->links() }}
            </div>
        @endif
    </div>

    <!-- UNLOCK & LICENSE CONFIGURATION MODAL -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden" @click.away="showModal = false">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center font-bold text-xs">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            OLT License Unlock: <span x-text="selectedOlt?.name"></span>
                        </h3>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="w-6 h-6 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitLicenseUpdate" class="p-4 space-y-3.5 text-xs">
                <!-- Auth Password Input -->
                <div class="space-y-1">
                    <label class="block font-semibold text-slate-700">
                        OLT Security Auth Password <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="password" 
                        x-model="form.auth_password" 
                        required 
                        placeholder="Enter OLT authentication password (e.g. admin)" 
                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" 
                    />
                    <span class="text-[10px] text-slate-400 block">Required by the OLT firmware for security verification.</span>
                </div>

                <!-- Duration / Days Limit -->
                <div class="space-y-1.5">
                    <label class="block font-semibold text-slate-700">License Duration Limit (Days)</label>
                    <input 
                        type="number" 
                        x-model.number="form.limit_days" 
                        min="0" 
                        max="3650" 
                        placeholder="0 for Unlimited / Permanent" 
                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" 
                    />

                    <!-- Quick Presets -->
                    <div class="flex items-center gap-1.5 pt-1">
                        <span class="text-[10.5px] text-slate-400 font-medium">Presets:</span>
                        <button type="button" @click="form.limit_days = 0" class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10.5px] font-bold hover:bg-emerald-100 transition cursor-pointer">
                            &infin; Unlimited (0)
                        </button>
                        <button type="button" @click="form.limit_days = 365" class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-medium transition cursor-pointer">
                            1 Year (365d)
                        </button>
                        <button type="button" @click="form.limit_days = 3650" class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-medium transition cursor-pointer">
                            10 Years (3650d)
                        </button>
                    </div>
                </div>

                <!-- Auto Renew Checkbox -->
                <div class="p-2.5 rounded-lg bg-blue-50/60 border border-blue-100 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.auto_renew" class="rounded text-blue-600 focus:ring-blue-500 w-3.5 h-3.5" />
                        <span class="font-semibold text-slate-800 text-xs">Enable Automated Auto-Unlock / Renewal</span>
                    </label>
                    <p class="text-[10.5px] text-slate-500 leading-relaxed pl-5">
                        If enabled, SomitySoft will automatically push the unlock payload whenever the remaining timer falls below 7 days.
                    </p>
                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button 
                        type="button" 
                        @click="showModal = false" 
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg border border-slate-200 shadow-2xs transition">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        :disabled="isSubmitting" 
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg shadow-2xs transition flex items-center gap-1.5 disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span x-text="isSubmitting ? 'Applying to OLT...' : 'Push License to OLT'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function oltLicenseManager() {
    return {
        showModal: false,
        isSubmitting: false,
        syncingOltId: null,
        selectedOlt: null,
        activeMenuOlt: null,
        form: {
            auth_password: 'admin',
            limit_days: 0,
            auto_renew: true,
            renew_threshold_hours: 168
        },
        toast: { show: false, message: '', type: 'success' },

        showToast: function(message, type) {
            this.toast.message = message;
            this.toast.type = type || 'success';
            this.toast.show = true;
            var self = this;
            setTimeout(function() {
                self.toast.show = false;
            }, 4000);
        },

        openUnlockModal: function(olt) {
            this.selectedOlt = olt;
            this.form.limit_days = olt.license_limit === 0 ? 0 : 0;
            this.form.auto_renew = olt.license_auto_renew ? true : true;
            this.showModal = true;
        },

        submitLicenseUpdate: async function() {
            if (!this.selectedOlt) return;
            this.isSubmitting = true;
            var self = this;

            try {
                var response = await fetch('/admin/network/license/' + this.selectedOlt.id + '/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                var data = await response.json();
                if (data.success) {
                    self.showToast(data.message, 'success');
                    self.showModal = false;
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    self.showToast(data.message || 'Failed to update license.', 'error');
                }
            } catch (e) {
                self.showToast('Network error communicating with server.', 'error');
            } finally {
                self.isSubmitting = false;
            }
        },

        syncLicense: async function(oltId) {
            this.syncingOltId = oltId;
            var self = this;

            try {
                var response = await fetch('/admin/network/license/' + oltId + '/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                var data = await response.json();
                if (data.success) {
                    self.showToast('Live license telemetry updated: ' + data.license_formatted, 'success');
                    setTimeout(function() { window.location.reload(); }, 800);
                } else {
                    self.showToast('Failed to fetch live license telemetry.', 'error');
                }
            } catch (e) {
                self.showToast('Network error syncing license telemetry.', 'error');
            } finally {
                self.syncingOltId = null;
            }
        }
    };
}
</script>
@endsection
