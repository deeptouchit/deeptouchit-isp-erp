@extends('tenant.layouts.app')

@section('title', ($reseller->name ?? 'Reseller Details') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerShowManager({{ Js::from($reseller) }})" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- Toast Notification Popup -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="toast.type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'error' ? 'fa-circle-exclamation text-rose-600' : 'fa-circle-check text-emerald-600'"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - No Subtitles or <p> tags) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight">{{ $reseller->name }}</h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $reseller->code }}
                </span>
                @if($reseller->status === 'active')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>{{ __('Active') }}</span>
                    </span>
                @elseif($reseller->status === 'suspended')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>{{ __('Suspended') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        <span>{{ __('Inactive') }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('tenant.resellers.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>{{ __('Back to Resellers') }}</span>
            </a>

            <button type="button" 
                    @click="openRechargeModal()" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wallet text-[10px]"></i>
                <span>{{ __('Recharge Wallet') }}</span>
            </button>

            <button type="button" 
                    @click="openEditModal()" 
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-pen-to-square text-[10px]"></i>
                <span>{{ __('Edit Reseller') }}</span>
            </button>

            <button type="button" 
                    @click="toggleStatus()" 
                    :disabled="statusToggling"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition inline-flex items-center gap-1.5 cursor-pointer"
                    :class="reseller.status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'">
                <i class="fas" :class="statusToggling ? 'fa-spinner fa-spin' : (reseller.status === 'active' ? 'fa-ban' : 'fa-check')"></i>
                <span x-text="reseller.status === 'active' ? '{{ __('Suspend') }}' : '{{ __('Activate') }}'"></span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Subscribers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Total Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">{{ number_format($totalCustomers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Active Subscribers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Active Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">{{ number_format($activeCustomers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Metric 3: Online Sessions -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">{{ __('Online Sessions') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-800 leading-tight block">{{ number_format($onlineCustomers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Metric 4: Wallet Balance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('Wallet Balance') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-900 leading-tight block">
                    @currency($reseller->wallet_balance)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Metric 5: Allocated Bandwidth -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Allocated CIR') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-800 leading-tight block truncate">
                    {{ $totalBandwidthMbps > 0 ? $totalBandwidthMbps . ' Mbps' : '--' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        <!-- Metric 6: Commission Rate -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-teal-700 uppercase tracking-wider block truncate">{{ __('Commission Rate') }}</span>
                <span class="text-[13px] font-bold font-mono text-teal-800 leading-tight block">
                    {{ number_format($reseller->commission_rate, 1) }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-percent"></i>
            </div>
        </div>
    </div>

    <!-- 3. Main Content Grid (Profile & Operations) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">
        
        <!-- Left Column: Profile, Commercial & Terms (4/12) -->
        <div class="lg:col-span-4 space-y-3">
            
            <!-- Card 1: Reseller Profile & Contacts -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-id-card text-purple-600 text-[11px]"></i>
                        <span>{{ __('Reseller Information') }}</span>
                    </span>
                    <span class="text-[10px] font-mono font-medium text-slate-500">ID: #{{ $reseller->id }}</span>
                </div>

                <div class="p-3.5 space-y-2.5 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Reseller Code') }}</span>
                        <span class="font-mono font-bold text-cyan-800">{{ $reseller->code }}</span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Username Prefix') }}</span>
                        <span class="font-mono font-semibold text-purple-700">
                            {{ $reseller->prefix ? $reseller->prefix . '_*' : __('None') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Contact Person') }}</span>
                        <span class="font-semibold text-slate-900">{{ $reseller->contact_person }}</span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Mobile Phone') }}</span>
                        <a href="tel:{{ $reseller->mobile }}" class="font-mono text-cyan-700 hover:underline">
                            {{ $reseller->mobile }}
                        </a>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Email Address') }}</span>
                        @if($reseller->email)
                            <a href="mailto:{{ $reseller->email }}" class="font-mono text-cyan-700 hover:underline truncate max-w-[180px]">
                                {{ $reseller->email }}
                            </a>
                        @else
                            <span class="text-slate-400">--</span>
                        @endif
                    </div>

                    <div class="flex justify-between items-start py-1 border-b border-slate-100">
                        <span class="text-slate-500 flex-shrink-0">{{ __('Office Address') }}</span>
                        <span class="text-slate-800 text-right max-w-[200px]">
                            {{ $reseller->address ?: __('Not specified') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-500">{{ __('Onboarded Date') }}</span>
                        <span class="font-mono text-slate-700">{{ $reseller->created_at?->format('d M, Y') }}</span>
                    </div>

                    @if($reseller->notes)
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-[10.5px] font-medium text-slate-400 block mb-1">{{ __('Notes / Remarks') }}</span>
                            <div class="p-2 bg-slate-50 rounded-lg text-slate-700 text-[11px] leading-relaxed">
                                {{ $reseller->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card 2: Commercial & Billing Configuration -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-file-invoice-dollar text-emerald-600 text-[11px]"></i>
                        <span>{{ __('Billing & Commercial Terms') }}</span>
                    </span>
                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono border {{ $reseller->billing_badge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                        {{ $reseller->billing_badge['label'] ?? $reseller->billing_type }}
                    </span>
                </div>

                <div class="p-3.5 space-y-2.5 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Wallet Balance') }}</span>
                        <span class="font-mono font-bold text-emerald-700">
                            @currency($reseller->wallet_balance)
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Credit Limit') }}</span>
                        <span class="font-mono font-semibold text-slate-800">
                            @currency($reseller->credit_limit)
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Available Balance') }}</span>
                        <span class="font-mono font-extrabold text-purple-900">
                            @currency($reseller->total_available_balance)
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Commission Rate') }}</span>
                        <span class="font-mono font-bold text-teal-700">{{ number_format($reseller->commission_rate, 1) }}%</span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Monthly Software Fee') }}</span>
                        <span class="font-mono font-semibold text-slate-800">
                            @currency($reseller->monthly_panel_charge)
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">{{ __('Panel Expiry Date') }}</span>
                        <span class="font-mono text-slate-800">
                            {{ $reseller->panel_expiry_date ? $reseller->panel_expiry_date->format('d M, Y') : __('Unlimited') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-500">{{ __('Panel Status') }}</span>
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono border {{ $reseller->panel_badge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                            {{ $reseller->panel_badge['label'] ?? $reseller->panel_billing_status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Assigned Coverage Zones -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-map-location-dot text-cyan-600 text-[11px]"></i>
                        <span>{{ __('Assigned Coverage Zones') }}</span>
                    </span>
                    <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                        {{ $coverageZones->count() }}
                    </span>
                </div>

                <div class="p-3 text-xs">
                    @if($coverageZones->count() > 0)
                        <div class="space-y-1.5">
                            @foreach($coverageZones as $zone)
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                                    <div class="min-w-0">
                                        <span class="font-semibold text-slate-900 block truncate">{{ $zone->name }}</span>
                                        <span class="text-[10.5px] text-slate-500 block truncate">
                                            {{ $zone->district ?? '' }} {{ $zone->thana ? '• ' . $zone->thana : '' }}
                                        </span>
                                    </div>
                                    <span class="font-mono text-[10px] font-semibold text-cyan-700 bg-cyan-50 px-1.5 py-0.5 rounded border border-cyan-100 flex-shrink-0">
                                        {{ $zone->subscribers_count }} {{ __('Subs') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-3 text-center text-slate-400">
                            <i class="fas fa-location-dot text-slate-300 text-base mb-1 block"></i>
                            <span class="text-[11px]">{{ __('No specific zones assigned to this reseller.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Right Column: Operations, Bandwidth, Subscribers & Invoices (8/12) -->
        <div class="lg:col-span-8 space-y-3">
            
            <!-- Section 1: Bandwidth Allocation Profile -->
            @if($bandwidth)
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-gauge-high text-amber-600 text-[11px]"></i>
                            <span>{{ __('Wholesale Bandwidth Allocation') }}</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                {{ __('Total CIR') }}: {{ $bandwidth->total_bandwidth_mbps ?? 0 }} Mbps
                            </span>
                            <a href="{{ route('tenant.resellers.bandwidth') }}" class="text-[11px] text-cyan-700 font-semibold hover:underline">
                                {{ __('Manage Bandwidth') }} &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="p-3.5">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[9.5px] uppercase font-semibold text-slate-500 block">{{ __('Global Transit') }}</span>
                                <span class="text-xs font-mono font-bold text-slate-800 block mt-0.5">{{ $bandwidth->global_bandwidth_mbps ?? 0 }} Mbps</span>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[9.5px] uppercase font-semibold text-slate-500 block">{{ __('BDIX Peering') }}</span>
                                <span class="text-xs font-mono font-bold text-slate-800 block mt-0.5">{{ $bandwidth->bdix_bandwidth_mbps ?? 0 }} Mbps</span>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[9.5px] uppercase font-semibold text-slate-500 block">{{ __('CDN / Cache') }}</span>
                                <span class="text-xs font-mono font-bold text-slate-800 block mt-0.5">{{ $bandwidth->cdn_bandwidth_mbps ?? 0 }} Mbps</span>
                            </div>
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="text-[9.5px] uppercase font-semibold text-slate-500 block">{{ __('Monthly Charge') }}</span>
                                <span class="text-xs font-mono font-bold text-emerald-700 block mt-0.5">
                                    @currency($bandwidth->monthly_bill_amount)
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-600 bg-slate-50/60 p-2.5 rounded-lg border border-slate-100">
                            <div>
                                <span class="text-slate-400 block">{{ __('Interface / Port') }}:</span>
                                <span class="font-mono font-medium text-slate-800">{{ $bandwidth->interface_name ?: '--' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">{{ __('VLAN Tag') }}:</span>
                                <span class="font-mono font-medium text-purple-700">{{ $bandwidth->vlan_id ?: '--' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">{{ __('MikroTik Queue') }}:</span>
                                <span class="font-mono font-medium text-slate-800 truncate block">{{ $bandwidth->mikrotik_queue_name ?: '--' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section 2: Recent Subscribers Preview -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-users text-cyan-600 text-[11px]"></i>
                            <span>{{ __('Subscribers Directory Preview') }}</span>
                        </span>
                        <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                            {{ $totalCustomers }} {{ __('Total') }}
                        </span>
                    </div>

                    <a href="{{ route('tenant.customers.index', ['reseller_id' => $reseller->id]) }}" 
                       class="text-[11px] text-cyan-700 font-semibold hover:underline inline-flex items-center gap-1">
                        <span>{{ __('View All Customers') }}</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-8 text-center">#</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Username / PPPoE') }}</th>
                                <th>{{ __('Package') }}</th>
                                <th class="text-right">{{ __('Monthly Bill') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCustomers as $idx => $cust)
                                <tr>
                                    <td class="text-center font-mono text-slate-400 text-[10px]">{{ $idx + 1 }}</td>
                                    <td class="font-medium text-slate-900">
                                        <a href="{{ route('tenant.customers.show', $cust->id) }}" class="hover:underline text-cyan-800">
                                            {{ $cust->name }}
                                        </a>
                                    </td>
                                    <td class="font-mono text-slate-700">{{ $cust->username ?: '--' }}</td>
                                    <td>
                                        <span class="font-mono text-[10.5px] text-purple-800 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100">
                                            {{ $cust->package_display_name }}
                                        </span>
                                    </td>
                                    <td class="text-right font-mono font-semibold text-slate-800">
                                        @currency($cust->monthly_bill)
                                    </td>
                                    <td class="text-center">
                                        @if($cust->status === 'active')
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>{{ __('Active') }}</span>
                                            </span>
                                        @elseif($cust->status === 'suspended')
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>{{ __('Suspended') }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                <span>{{ ucfirst($cust->status) }}</span>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400 text-xs">
                                        <i class="fas fa-user-slash text-slate-300 text-base mb-1 block"></i>
                                        <span>{{ __('No subscribers found under this reseller yet.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Reseller Staff & Technicians -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-user-tie text-blue-600 text-[11px]"></i>
                            <span>{{ __('Reseller Operators & Staff') }}</span>
                        </span>
                        <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                            {{ $staffCount }}
                        </span>
                    </div>

                    <a href="{{ route('tenant.staff.resellers') }}" class="text-[11px] text-cyan-700 font-semibold hover:underline">
                        {{ __('View Staff Roster') }} &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-8 text-center">#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email / Username') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Mobile') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffList as $idx => $st)
                                <tr>
                                    <td class="text-center font-mono text-slate-400 text-[10px]">{{ $idx + 1 }}</td>
                                    <td class="font-medium text-slate-900">{{ $st->name }}</td>
                                    <td class="font-mono text-slate-700">{{ $st->email }}</td>
                                    <td class="font-mono text-[10px]">
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ ucfirst($st->role ?? 'Staff') }}
                                        </span>
                                    </td>
                                    <td class="font-mono text-slate-700">{{ $st->phone ?: '--' }}</td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $st->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $st->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            <span>{{ ucfirst($st->status ?? 'active') }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400 text-xs">
                                        <i class="fas fa-users-slash text-slate-300 text-base mb-1 block"></i>
                                        <span>{{ __('No portal staff users registered for this reseller.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: Recent Invoices & Billing -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-file-invoice text-emerald-600 text-[11px]"></i>
                        <span>{{ __('Recent Wholesale Invoices') }}</span>
                    </span>

                    <a href="{{ route('tenant.resellers.invoices') }}" class="text-[11px] text-cyan-700 font-semibold hover:underline">
                        {{ __('View All Invoices') }} &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice #') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Billing Period') }}</th>
                                <th class="text-right">{{ __('Total Amount') }}</th>
                                <th class="text-right">{{ __('Paid') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td class="font-mono font-semibold text-cyan-800">
                                        {{ $inv->invoice_number ?? ('INV-' . $inv->id) }}
                                    </td>
                                    <td class="font-mono text-[10px]">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-700 border border-slate-200">
                                            {{ str_replace('_', ' ', $inv->type ?? 'INVOICE') }}
                                        </span>
                                    </td>
                                    <td class="font-mono text-slate-700">
                                        {{ $inv->billing_period ?? ($inv->created_at?->format('M Y')) }}
                                    </td>
                                    <td class="text-right font-mono font-bold text-slate-900">
                                        @currency($inv->total_amount ?? $inv->amount)
                                    </td>
                                    <td class="text-right font-mono text-emerald-700">
                                        @currency($inv->paid_amount)
                                    </td>
                                    <td class="text-center">
                                        @if(($inv->status ?? '') === 'PAID')
                                            <span class="inline-flex px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium">
                                                {{ __('Paid') }}
                                            </span>
                                        @elseif(($inv->status ?? '') === 'UNPAID')
                                            <span class="inline-flex px-1.5 py-0.2 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200 font-medium">
                                                {{ __('Unpaid') }}
                                            </span>
                                        @else
                                            <span class="inline-flex px-1.5 py-0.2 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200 font-medium">
                                                {{ ucfirst(strtolower($inv->status ?? 'Draft')) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400 text-xs">
                                        <i class="fas fa-receipt text-slate-300 text-base mb-1 block"></i>
                                        <span>{{ __('No invoices generated for this reseller yet.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- 4. Production-Grade Natural Modal: Recharge Wallet Modal -->
    <div x-show="rechargeModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeRechargeModal()" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Wallet Top-up & Adjustment') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ $reseller->name }} ({{ $reseller->code }})</p>
                    </div>
                </div>
                <button type="button" @click="closeRechargeModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitRecharge()" class="p-4 space-y-3 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                    <span class="text-slate-500">{{ __('Current Balance') }}:</span>
                    <span class="font-mono font-bold text-emerald-700 text-sm">
                        <span x-text="currencySymbol"></span>&nbsp;<span x-text="parseFloat(reseller.wallet_balance).toFixed(2)"></span>
                    </span>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">{{ __('Transaction Type') }} <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer transition"
                               :class="rechargeModal.form.type === 'credit' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-white border-slate-200 text-slate-700'">
                            <input type="radio" value="credit" x-model="rechargeModal.form.type" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="font-semibold">{{ __('Add Balance (+)') }}</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer transition"
                               :class="rechargeModal.form.type === 'debit' ? 'bg-rose-50 border-rose-300 text-rose-900' : 'bg-white border-slate-200 text-slate-700'">
                            <input type="radio" value="debit" x-model="rechargeModal.form.type" class="text-rose-600 focus:ring-rose-500">
                            <span class="font-semibold">{{ __('Deduct Balance (-)') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">{{ __('Amount') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span></label>
                    <input type="number" 
                           step="0.01" 
                           min="1" 
                           required 
                           x-model="rechargeModal.form.amount" 
                           placeholder="0.00" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">{{ __('Remarks / Note') }}</label>
                    <textarea x-model="rechargeModal.form.notes" 
                              rows="2" 
                              placeholder="{{ __('Optional transaction remarks...') }}" 
                              class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="closeRechargeModal()" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="rechargeModal.submitting" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas" :class="rechargeModal.submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="rechargeModal.submitting ? '{{ __('Processing...') }}' : '{{ __('Submit') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. Production-Grade Natural Modal: Edit Reseller Modal -->
    <div x-show="editModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="closeEditModal()" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Edit Reseller Configuration') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Update contact info, billing model and license terms') }}</p>
                    </div>
                </div>
                <button type="button" @click="closeEditModal()" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitEdit()" class="p-4 space-y-3 text-xs max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Reseller / Brand Name') }} <span class="text-rose-500">*</span></label>
                        <input type="text" required x-model="editModal.form.name" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Reseller Code') }} <span class="text-rose-500">*</span></label>
                        <input type="text" required x-model="editModal.form.code" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Username Prefix') }}</label>
                        <input type="text" x-model="editModal.form.prefix" placeholder="e.g. SN" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Contact Person') }} <span class="text-rose-500">*</span></label>
                        <input type="text" required x-model="editModal.form.contact_person" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Mobile Phone') }} <span class="text-rose-500">*</span></label>
                        <input type="text" required x-model="editModal.form.mobile" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Email Address') }}</label>
                        <input type="email" x-model="editModal.form.email" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Office Address') }}</label>
                        <input type="text" x-model="editModal.form.address" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Billing Model') }} <span class="text-rose-500">*</span></label>
                        <select x-model="editModal.form.billing_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                            <option value="PREPAID_WALLET">{{ __('Prepaid Wallet') }}</option>
                            <option value="POSTPAID_MONTHLY">{{ __('Postpaid Monthly') }}</option>
                            <option value="BANDWIDTH_WHOLESALE">{{ __('Bandwidth Wholesale') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Commission Rate (%)') }}</label>
                        <input type="number" step="0.1" min="0" max="100" x-model="editModal.form.commission_rate" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Credit Limit') }} ({{ $currencySymbol ?? '৳' }})</label>
                        <input type="number" step="0.01" min="0" x-model="editModal.form.credit_limit" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Monthly Panel Fee') }}</label>
                        <input type="number" step="0.01" min="0" x-model="editModal.form.monthly_panel_charge" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Panel Expiry Date') }}</label>
                        <input type="date" x-model="editModal.form.panel_expiry_date" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">{{ __('Operational Status') }} <span class="text-rose-500">*</span></label>
                        <select x-model="editModal.form.status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none">
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="suspended">{{ __('Suspended') }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">{{ __('Remarks / Notes') }}</label>
                    <textarea x-model="editModal.form.notes" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="closeEditModal()" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="editModal.saving" 
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas" :class="editModal.saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="editModal.saving ? '{{ __('Saving...') }}' : '{{ __('Save Changes') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerShowManager(initialReseller) {
    return {
        reseller: initialReseller,
        currencySymbol: '{{ $currencySymbol ?? '৳' }}',
        statusToggling: false,
        toast: { show: false, message: '', type: 'success' },

        rechargeModal: {
            open: false,
            submitting: false,
            form: {
                type: 'credit',
                amount: '',
                notes: ''
            }
        },

        editModal: {
            open: false,
            saving: false,
            form: {}
        },

        init() {
            // Initial setup
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        openRechargeModal() {
            this.rechargeModal.form = {
                type: 'credit',
                amount: '',
                notes: ''
            };
            this.rechargeModal.open = true;
        },

        closeRechargeModal() {
            this.rechargeModal.open = false;
        },

        async submitRecharge() {
            if (!this.rechargeModal.form.amount || parseFloat(this.rechargeModal.form.amount) <= 0) {
                this.showToast('Please enter a valid amount.', 'error');
                return;
            }

            this.rechargeModal.submitting = true;
            try {
                const res = await fetch(`/admin/resellers/${this.reseller.id}/adjust-wallet`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.rechargeModal.form)
                });

                const data = await res.json();
                if (data.success) {
                    this.reseller.wallet_balance = data.new_balance;
                    this.showToast(data.message || 'Balance updated successfully!', 'success');
                    this.closeRechargeModal();
                } else {
                    this.showToast(data.message || 'Failed to update balance.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while updating balance.', 'error');
            } finally {
                this.rechargeModal.submitting = false;
            }
        },

        openEditModal() {
            this.editModal.form = {
                id: this.reseller.id,
                name: this.reseller.name || '',
                code: this.reseller.code || '',
                prefix: this.reseller.prefix || '',
                contact_person: this.reseller.contact_person || '',
                mobile: this.reseller.mobile || '',
                email: this.reseller.email || '',
                address: this.reseller.address || '',
                billing_type: this.reseller.billing_type || 'PREPAID_WALLET',
                commission_rate: this.reseller.commission_rate || 0,
                credit_limit: this.reseller.credit_limit || 0,
                monthly_panel_charge: this.reseller.monthly_panel_charge || 0,
                panel_expiry_date: this.reseller.panel_expiry_date ? this.reseller.panel_expiry_date.substring(0, 10) : '',
                status: this.reseller.status || 'active',
                notes: this.reseller.notes || ''
            };
            this.editModal.open = true;
        },

        closeEditModal() {
            this.editModal.open = false;
        },

        async submitEdit() {
            this.editModal.saving = true;
            try {
                const res = await fetch(`/admin/resellers/${this.reseller.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(this.editModal.form)
                });

                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message || 'Reseller updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    this.showToast(data.message || 'Failed to update reseller.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while updating reseller.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        async toggleStatus() {
            this.statusToggling = true;
            try {
                const res = await fetch(`/admin/resellers/${this.reseller.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({})
                });

                const data = await res.json();
                if (data.success) {
                    this.reseller.status = data.new_status;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to toggle status.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while toggling status.', 'error');
            } finally {
                this.statusToggling = false;
            }
        }
    };
}
</script>
@endpush

