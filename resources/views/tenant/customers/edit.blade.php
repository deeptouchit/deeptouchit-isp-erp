@extends('tenant.layouts.app')

@section('title', 'Edit Customer: ' . $customer->name . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-3 sm:space-y-4 px-1 sm:px-0" x-data="customerEditManager()">
    
    <!-- Top Header (Strictly Back Arrow + Title ONLY - No Subtitle) -->
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <a href="{{ route('tenant.customers.index') }}" 
               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition cursor-pointer flex-shrink-0"
               title="Back to Customer List">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight truncate">Edit Customer: {{ $customer->name }}</h1>
        </div>
        <div class="text-[11px] font-mono text-cyan-700 font-semibold bg-cyan-50 px-2.5 py-1 rounded-lg border border-cyan-100 flex-shrink-0 ml-auto sm:ml-0">
            ID: {{ $customer->customer_id }}
        </div>
    </div>

    <!-- Validation Notification Banner (Appears only on form error) -->
    <div x-show="Object.keys(errors).length > 0" 
         x-transition
         class="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between gap-2 shadow-2xs"
         style="display: none;">
        <div class="flex items-center gap-2 text-rose-800 text-xs font-medium min-w-0">
            <i class="fas fa-circle-exclamation text-rose-600 text-sm flex-shrink-0"></i>
            <span class="truncate sm:whitespace-normal">অনুগ্রহ করে নিচের লাল চিহ্নিত তথ্যগুলো সঠিকভাবে পূরণ করুন।</span>
        </div>
        <button type="button" @click="errors = {}" class="text-rose-400 hover:text-rose-700 text-xs cursor-pointer flex-shrink-0">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Main Edit Form -->
    <form action="{{ route('tenant.customers.update', $customer->id) }}" 
          method="POST" 
          class="space-y-3 sm:space-y-4"
          novalidate
          @submit.prevent="submitForm($event)">
        @csrf
        @method('PUT')

        <!-- Section 1: Subscriber Profile & Partner Affiliation -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Subscriber Profile &amp; Partner Scope</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-3.5">
                <!-- Scope / Reseller -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Scope / Partner</label>
                    <select name="reseller_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('reseller_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">ISP Direct Retail (Headquarters)</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}" {{ old('reseller_id', $customer->reseller_id) == $r->id ? 'selected' : '' }}>
                                Sub-ISP: {{ $r->name }} ({{ $r->code ?: $r->prefix }})
                            </option>
                        @endforeach
                    </select>
                    @error('reseller_id')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Customer ID -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Customer ID</label>
                    <input type="text" 
                           name="customer_id" 
                           value="{{ old('customer_id', $customer->customer_id) }}"
                           placeholder="e.g. {{ $customer->customer_id }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('customer_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('customer_id')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           x-model="fullName"
                           @input="clearError('name')"
                           value="{{ old('name', $customer->name) }}"
                           placeholder="Subscriber Full Name" 
                           :class="errors.name ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-medium border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.name" x-text="errors.name" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('name')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Primary Mobile -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Mobile Phone <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="phone" 
                           x-model="mobilePhone"
                           @input="clearError('phone')"
                           value="{{ old('phone', $customer->phone) }}"
                           placeholder="017XX-XXXXXX" 
                           :class="errors.phone ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.phone" x-text="errors.phone" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('phone')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Alt Phone -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Alternate Phone</label>
                    <input type="text" 
                           name="alt_phone" 
                           value="{{ old('alt_phone', $customer->alt_phone) }}"
                           placeholder="Optional second phone" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('alt_phone') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Email Address</label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $customer->email) }}"
                           placeholder="subscriber@example.com" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('email') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- National ID -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">National ID (NID / Smart Card)</label>
                    <input type="text" 
                           name="national_id" 
                           value="{{ old('national_id', $customer->national_id) }}"
                           placeholder="10 or 17 digit NID" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('national_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Father Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Father's / Guardian's Name</label>
                    <input type="text" 
                           name="father_name" 
                           value="{{ old('father_name', $customer->father_name) }}"
                           placeholder="Father or Husband name" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('father_name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Zone / Area -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Coverage Zone / Area</label>
                    <select name="zone_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('zone_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- Select Coverage Zone --</option>
                        @foreach($coverageZones ?? [] as $cz)
                            <option value="{{ $cz->id }}" {{ (old('zone_id', $customer->zone_id) == $cz->id || old('zone', $customer->zone) == $cz->name) ? 'selected' : '' }}>
                                {{ $cz->code }} - {{ $cz->name }}{{ $cz->city_upazila ? " ({$cz->city_upazila})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Installation Address -->
            <div>
                <label class="block text-[11px] font-medium text-slate-700 mb-1">Installation Address</label>
                <input type="text" 
                       name="address" 
                       value="{{ old('address', $customer->address) }}"
                       placeholder="House, Road, Block, Floor / Flat details..." 
                       class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
            </div>
        </div>

        <!-- Section 2: Technical & MikroTik Provisioning -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">2. Technical &amp; MikroTik Provisioning</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-3.5">
                <!-- Connection Type -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Connection Protocol <span class="text-rose-500">*</span></label>
                    <select name="connection_type" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('connection_type') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="pppoe" {{ old('connection_type', $customer->connection_type) === 'pppoe' ? 'selected' : '' }}>PPPoE (Point-to-Point Protocol)</option>
                        <option value="static_ip" {{ old('connection_type', $customer->connection_type) === 'static_ip' ? 'selected' : '' }}>Static IP Assignment</option>
                        <option value="dhcp" {{ old('connection_type', $customer->connection_type) === 'dhcp' ? 'selected' : '' }}>DHCP Auto-bind</option>
                        <option value="hotspot" {{ old('connection_type', $customer->connection_type) === 'hotspot' ? 'selected' : '' }}>Hotspot Voucher</option>
                    </select>
                </div>

                <!-- PPPoE Username -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">PPPoE Username <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="username" 
                           x-model="pppoeUsername"
                           @input="clearError('username')"
                           value="{{ old('username', $customer->username) }}"
                           placeholder="unique_username" 
                           :class="errors.username ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-semibold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.username" x-text="errors.username" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('username')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- PPPoE Password -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">PPPoE Password <span class="text-slate-400 font-normal">(Leave blank to keep unchanged)</span></label>
                    <input type="text" 
                           name="password" 
                           value="{{ old('password') }}"
                           placeholder="Enter new password if changing" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('password') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    @error('password')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Router Gateway -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Gateway MikroTik Router</label>
                    <select name="router_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('router_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- Choose Gateway Router --</option>
                        @foreach($allRouters as $rtr)
                            <option value="{{ $rtr->id }}" {{ old('router_id', $customer->router_id) == $rtr->id ? 'selected' : '' }}>
                                {{ $rtr->name }} ({{ $rtr->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Framed IP Address -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Framed / Static IP Address</label>
                    <input type="text" 
                           name="ip_address" 
                           value="{{ old('ip_address', $customer->ip_address) }}"
                           placeholder="172.16.XX.XX" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('ip_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- MAC Address Bind -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Router / MAC Bind</label>
                    <input type="text" 
                           name="mac_address" 
                           value="{{ old('mac_address', $customer->mac_address) }}"
                           placeholder="AA:BB:CC:DD:EE:FF" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('mac_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- OLT Device -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">OLT Device</label>
                    <select name="olt_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('olt_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- Optional OLT Bind --</option>
                        @foreach($allOlts as $olt)
                            <option value="{{ $olt->id }}" {{ old('olt_id', $customer->olt_id) == $olt->id ? 'selected' : '' }}>
                                {{ $olt->name }} ({{ $olt->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ONU MAC / Serial -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">ONU SN / MAC</label>
                    <input type="text" 
                           name="onu_mac_sn" 
                           value="{{ old('onu_mac_sn', $customer->onu_mac_sn) }}"
                           placeholder="HWTC-XXXXXXXX" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('onu_mac_sn') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Fiber Core Route Info -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Fiber Core / TJ Box Info</label>
                    <input type="text" 
                           name="fiber_route_info" 
                           value="{{ old('fiber_route_info', $customer->fiber_route_info) }}"
                           placeholder="TJ-4, Core 2, Splitter 1:8" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('fiber_route_info') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- Section 3: Package, Billing & Expiry Cycle -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">3. Internet Package, Billing &amp; Expiry Cycle</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Internet Package -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Internet Package</label>
                    <select name="package_id" 
                            x-ref="packageSelect"
                            @change="onPackageChange($event); clearError('monthly_bill')"
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('package_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="" data-price="{{ $customer->monthly_bill }}">-- Select Package --</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}" 
                                    data-price="{{ $pkg->price }}" 
                                    {{ old('package_id', $customer->package_id) == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->package_name ?: $pkg->name }} (@currency($pkg->price))
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Monthly Rate -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Monthly Bill ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span></label>
                    <input type="number" 
                           step="0.01" 
                           name="monthly_bill" 
                           x-model="monthlyBill"
                           @input="clearError('monthly_bill')"
                           placeholder="800.00" 
                           :class="errors.monthly_bill ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-900'"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-bold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.monthly_bill" x-text="errors.monthly_bill" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('monthly_bill')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Billing Type -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Billing Type</label>
                    <select name="billing_type" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('billing_type') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="prepaid" {{ old('billing_type', $customer->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid (Auto-cutoff)</option>
                        <option value="postpaid" {{ old('billing_type', $customer->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid (Monthly Invoicing)</option>
                    </select>
                </div>

                <!-- Expiry Date -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Expiry Date</label>
                    <input type="date" 
                           name="expiry_date" 
                           value="{{ old('expiry_date', $customer->expiry_date ? $customer->expiry_date->format('Y-m-d') : '') }}"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('expiry_date') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- Section 4: Initial Status & Administrative Remarks -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-sliders"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">4. Account Status &amp; Admin Notes</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                <!-- Status -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Account Status</label>
                    <select name="status" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('status') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active Line</option>
                        <option value="due" {{ old('status', $customer->status) === 'due' ? 'selected' : '' }}>Bill Due</option>
                        <option value="expired" {{ old('status', $customer->status) === 'expired' ? 'selected' : '' }}>Expired / Auto-cut</option>
                        <option value="suspended" {{ old('status', $customer->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="disabled" {{ old('status', $customer->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        <option value="disconnected" {{ old('status', $customer->status) === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Remarks / Internal Notes</label>
                    <input type="text" 
                           name="remarks" 
                           value="{{ old('remarks', $customer->remarks) }}"
                           placeholder="VIP client, fiber route special instructions, etc." 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('remarks') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- Submit Bar (Centralized Clean Action Bar with Responsive Mobile Stack) -->
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5 bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
            <a href="{{ route('tenant.customers.index') }}" 
               class="text-center border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-2 rounded-lg transition cursor-pointer">
                Cancel
            </a>
            <button type="submit" 
                    :disabled="isSubmitting"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-6 py-2.5 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                <i class="fas fa-spinner fa-spin text-xs" x-show="isSubmitting" style="display: none;"></i>
                <i class="fas fa-check text-xs" x-show="!isSubmitting"></i>
                <span x-text="isSubmitting ? 'Updating &amp; Syncing...' : 'Update Customer &amp; Sync'"></span>
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function customerEditManager() {
    return {
        fullName: '{{ old('name', $customer->name) }}',
        mobilePhone: '{{ old('phone', $customer->phone) }}',
        pppoeUsername: '{{ old('username', $customer->username) }}',
        monthlyBill: '{{ old('monthly_bill', $customer->monthly_bill) }}',
        isSubmitting: false,
        errors: {},

        clearError(field) {
            if (this.errors[field]) {
                delete this.errors[field];
            }
        },

        validateForm() {
            this.errors = {};

            // 1. Validate Name
            if (!this.fullName || !this.fullName.trim()) {
                this.errors.name = 'গ্রাহকের পুরো নাম প্রদান করুন (Full name is required)';
            }

            // 2. Validate Phone
            if (!this.mobilePhone || !this.mobilePhone.trim()) {
                this.errors.phone = 'মোবাইল নম্বর প্রদান করুন (Phone number is required)';
            } else if (this.mobilePhone.trim().replace(/[^0-9]/g, '').length < 8) {
                this.errors.phone = 'সঠিক মোবাইল নম্বর প্রদান করুন (Valid phone number required)';
            }

            // 3. Validate PPPoE Username
            if (!this.pppoeUsername || !this.pppoeUsername.trim()) {
                this.errors.username = 'PPPoE ইউজারনেম প্রদান করুন (Username is required)';
            }

            // 4. Validate Monthly Bill
            if (this.monthlyBill === '' || this.monthlyBill === null || isNaN(this.monthlyBill) || Number(this.monthlyBill) < 0) {
                this.errors.monthly_bill = 'মাসিক বিল অ্যামাউন্ট উল্লেখ করুন (Monthly bill is required)';
            }

            return Object.keys(this.errors).length === 0;
        },

        submitForm(event) {
            if (!this.validateForm()) {
                const firstErrKey = Object.keys(this.errors)[0];
                const inputEl = document.querySelector(`[name="${firstErrKey}"]`);
                if (inputEl) {
                    inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => inputEl.focus(), 300);
                }
                return false;
            }

            this.isSubmitting = true;
            event.target.submit();
        },

        onPackageChange(event) {
            const selectEl = event.target;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                this.monthlyBill = selectedOption.dataset.price;
            }
        }
    };
}
</script>
@endpush
