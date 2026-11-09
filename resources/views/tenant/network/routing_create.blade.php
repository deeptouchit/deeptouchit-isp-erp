@extends('tenant.layouts.app')

@section('title', 'Create Route - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="routeCreateManager()">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-route"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Create Route (Routing Table)</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                    IPv4 / Policy Routing
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('tenant.network.routing') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Back to Routing</span>
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
        <form action="{{ route('tenant.network.routing.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf

            <!-- 1. Destination & Gateway Addressing -->
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        1. Target &amp; Gateway Addressing
                    </span>
                    <span class="text-[10.5px] text-slate-400">Destination network prefix, next-hop IP or interface gateway</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Dst. Address -->
                    <div class="sm:col-span-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-medium text-slate-700">
                                Dst. Address (Network Prefix) <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" 
                                    @click="dstAddress = '0.0.0.0/0'" 
                                    class="text-[10px] text-cyan-600 hover:text-cyan-800 font-medium cursor-pointer">
                                Set Default (0.0.0.0/0)
                            </button>
                        </div>
                        <input type="text" 
                               name="dst_address" 
                               x-model="dstAddress" 
                               required 
                               placeholder="e.g. 0.0.0.0/0 or 10.50.0.0/16" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Next-Hop Gateway -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Gateway (IP or Interface) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="gateway" 
                               value="{{ old('gateway') }}" 
                               required 
                               placeholder="e.g. 192.168.1.1, 10.0.0.1 or sfp-sfpplus1" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Distance -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Distance (Metric) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="distance" 
                               value="{{ old('distance', 1) }}" 
                               min="1" 
                               max="255" 
                               required 
                               placeholder="1" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Route Type -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Route Type <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="static" {{ old('type', 'static') === 'static' ? 'selected' : '' }}>Static Gateway</option>
                            <option value="bgp" {{ old('type') === 'bgp' ? 'selected' : '' }}>BGP Dynamic</option>
                            <option value="ospf" {{ old('type') === 'ospf' ? 'selected' : '' }}>OSPF Internal</option>
                            <option value="connected" {{ old('type') === 'connected' ? 'selected' : '' }}>Connected (DAC)</option>
                            <option value="blackhole" {{ old('type') === 'blackhole' ? 'selected' : '' }}>Blackhole (Null0)</option>
                        </select>
                    </div>

                    <!-- Routing Table -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Routing Table <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="routing_table" 
                               value="{{ old('routing_table', 'main') }}" 
                               required 
                               placeholder="main" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Router Gateway & Scope -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        2. Router Association &amp; Scope
                    </span>
                    <span class="text-[10.5px] text-slate-400">Target core hardware router in fleet</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Associated Router -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Associated Router
                        </label>
                        <select name="router_id" 
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">-- Standalone (No Router Link) --</option>
                            @foreach($routers as $r)
                                <option value="{{ $r->id }}" {{ old('router_id') == $r->id ? 'selected' : '' }}>
                                    {{ $r->name }} ({{ $r->ip_address }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Comment / Description -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Comment / Description (MikroTik Route Comment)
                        </label>
                        <input type="text" 
                               name="description" 
                               value="{{ old('description') }}" 
                               placeholder="e.g. Primary default gateway route via Telco upstream" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>
            </div>

            <!-- 3. MikroTik Sync & Metadata -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">
                        3. MikroTik Router Synchronization
                    </span>
                </div>

                <!-- Sync to MikroTik Switch -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <label for="is_sync_mikrotik" class="text-xs font-semibold text-slate-800 cursor-pointer block">
                            Push Route to Associated MikroTik Router
                        </label>
                        <p class="text-[11px] text-slate-500">
                            When enabled and a router is selected, this routing record will immediately be provisioned under <code class="font-mono text-purple-700 bg-purple-50 px-1 py-0.5 rounded">/ip/route</code> via RouterOS API.
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
                <a href="{{ route('tenant.network.routing') }}" 
                   class="h-[34px] px-4 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition flex items-center justify-center cursor-pointer">
                    Cancel
                </a>
                <button type="submit" 
                        class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>Create Route</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function routeCreateManager() {
    return {
        dstAddress: '{{ old('dst_address', '') }}'
    };
}
</script>
@endpush
