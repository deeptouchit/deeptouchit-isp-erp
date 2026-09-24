@extends('tenant.layouts.app')

@section('title', 'Onboard New Customer - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-3 sm:space-y-4 px-1 sm:px-0" x-data="customerCreateManager()">
    
    <!-- 1. Top Header Bar (Strictly Back Arrow + Title + Info Badges ONLY - No Subtitles/Descriptions) -->
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <a href="{{ route('tenant.customers.index') }}" 
               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition cursor-pointer flex-shrink-0"
               title="Back to Customers Table">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight truncate">Onboard New Customer</h1>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 ml-auto sm:ml-0">
            <!-- Suggested ID Badge -->
            <div class="inline-flex items-center gap-1.5 bg-cyan-50 text-cyan-800 border border-cyan-200 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-[11px] sm:text-xs font-mono font-semibold shadow-2xs">
                <span class="text-[9px] sm:text-[10px] text-cyan-500 font-sans uppercase font-medium">Auto ID:</span>
                <span>{{ $autoCustomerId }}</span>
            </div>

            <!-- Provisioning Mode Indicator -->
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ring-2 ring-emerald-300 animate-pulse"></span>
                <span>MikroTik Live Sync Ready</span>
            </span>
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

    <!-- Main Create Form (No HTML5 required attributes - Handled via smooth validation engine) -->
    <form action="{{ route('tenant.customers.store') }}" 
          method="POST" 
          class="space-y-3 sm:space-y-4" 
          novalidate 
          @submit.prevent="submitForm($event)">
        @csrf

        <!-- SECTION 1: Subscriber Profile & Partner Affiliation -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-2 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs border border-cyan-100 flex-shrink-0">
                        <i class="fas fa-user-gear"></i>
                    </div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Subscriber Profile &amp; Affiliation</h2>
                </div>
                <span class="text-[10px] sm:text-[10.5px] text-slate-400 font-medium">Fields marked with <span class="text-rose-500 font-bold">*</span> are required</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-3.5">
                <!-- Scope / Reseller Affiliation -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Affiliation Scope / Partner</label>
                    <select name="reseller_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('reseller_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">ISP Direct Retail (Headquarters)</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}" {{ old('reseller_id') == $r->id ? 'selected' : '' }}>
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Customer ID <span class="text-slate-400 font-normal">(Leave blank for Auto-ID)</span></label>
                    <div class="relative">
                        <input type="text" 
                               name="customer_id" 
                               value="{{ old('customer_id', $autoCustomerId) }}"
                               placeholder="e.g. {{ $autoCustomerId }}" 
                               class="w-full pl-3 pr-8 py-1.5 rounded-lg text-xs font-mono uppercase font-semibold transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('customer_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <span class="absolute right-2.5 top-2 text-slate-400 text-xs" title="Unique Master ID">
                            <i class="fas fa-fingerprint text-[11px]"></i>
                        </span>
                    </div>
                    @error('customer_id')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Subscriber Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           x-model="fullName"
                           @input="clearError('name'); suggestUsername()"
                           value="{{ old('name') }}"
                           placeholder="e.g. Rahim Ahmed" 
                           :class="errors.name ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-medium border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.name" x-text="errors.name" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('name')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Primary Mobile -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Primary Mobile Phone <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" 
                               name="phone" 
                               x-model="mobilePhone"
                               @input="clearError('phone'); suggestUsername()"
                               value="{{ old('phone') }}"
                               placeholder="01711-XXXXXX" 
                               :class="errors.phone ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                               class="w-full pl-3 pr-8 py-1.5 rounded-lg text-xs font-mono border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <span class="absolute right-2.5 top-2 text-slate-400 text-xs">
                            <i class="fas fa-phone text-[11px]"></i>
                        </span>
                    </div>
                    <p x-show="errors.phone" x-text="errors.phone" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('phone')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Alt Phone -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Alternate / Landline Phone</label>
                    <input type="text" 
                           name="alt_phone" 
                           value="{{ old('alt_phone') }}"
                           placeholder="Optional second number" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('alt_phone') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Email Address</label>
                    <div class="relative">
                        <input type="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               placeholder="client@example.com" 
                               class="w-full pl-3 pr-8 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('email') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <span class="absolute right-2.5 top-2 text-slate-400 text-xs">
                            <i class="fas fa-envelope text-[11px]"></i>
                        </span>
                    </div>
                </div>

                <!-- National ID -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">National ID (NID / Smart Card)</label>
                    <input type="text" 
                           name="national_id" 
                           value="{{ old('national_id') }}"
                           placeholder="10, 13 or 17 digit NID" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('national_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Father / Guardian Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Father's / Guardian's Name</label>
                    <input type="text" 
                           name="father_name" 
                           value="{{ old('father_name') }}"
                           placeholder="Guardian name" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('father_name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Zone / Area Selection -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Coverage Zone / Area</label>
                    <select name="zone_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('zone_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- Select Coverage Zone --</option>
                        @foreach($coverageZones ?? [] as $cz)
                            <option value="{{ $cz->id }}" {{ old('zone_id') == $cz->id ? 'selected' : '' }}>
                                {{ $cz->code }} - {{ $cz->name }}{{ $cz->city_upazila ? " ({$cz->city_upazila})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Installation Address -->
            <div>
                <label class="block text-[11px] font-medium text-slate-700 mb-1">Physical Installation Address</label>
                <input type="text" 
                       name="address" 
                       value="{{ old('address') }}"
                       placeholder="House, Road, Block, Flat / Floor details..." 
                       class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
            </div>
        </div>

        <!-- SECTION 2: Technical & MikroTik Provisioning -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-2 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs border border-indigo-100 flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">2. Technical &amp; MikroTik Provisioning</h2>
                </div>
                <div class="text-[10.5px] font-mono text-slate-500">
                    Target: <span class="font-semibold text-indigo-700" x-text="selectedRouterName || 'Auto Router'"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-3.5">
                <!-- Connection Protocol -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Connection Protocol <span class="text-rose-500">*</span></label>
                    <select name="connection_type" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('connection_type') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="pppoe" {{ old('connection_type', 'pppoe') === 'pppoe' ? 'selected' : '' }}>PPPoE (Point-to-Point Protocol)</option>
                        <option value="static_ip" {{ old('connection_type') === 'static_ip' ? 'selected' : '' }}>Static IP Routing</option>
                        <option value="dhcp" {{ old('connection_type') === 'dhcp' ? 'selected' : '' }}>DHCP Auto-bind</option>
                        <option value="hotspot" {{ old('connection_type') === 'hotspot' ? 'selected' : '' }}>Hotspot Voucher</option>
                    </select>
                </div>

                <!-- PPPoE Username -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">PPPoE Username <span class="text-rose-500">*</span></label>
                        <button type="button" 
                                @click="suggestUsername(true)" 
                                title="Auto-generate clean username from Name"
                                class="text-[10px] text-cyan-600 hover:text-cyan-800 font-semibold cursor-pointer inline-flex items-center gap-1">
                            <i class="fas fa-bolt text-[9px]"></i>
                            <span>Auto</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input type="text" 
                               name="username" 
                               x-model="pppoeUsername"
                               @input="clearError('username')"
                               value="{{ old('username') }}"
                               placeholder="unique_username" 
                               :class="errors.username ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-cyan-900'"
                               class="w-full pl-3 pr-8 py-1.5 rounded-lg text-xs font-mono font-semibold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <span class="absolute right-2.5 top-2 text-slate-400 text-xs">
                            <i class="fas fa-user-lock text-[11px]"></i>
                        </span>
                    </div>
                    <p x-show="errors.username" x-text="errors.username" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('username')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- PPPoE Password -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">PPPoE Password / Secret <span class="text-rose-500">*</span></label>
                        <button type="button" 
                                @click="generatePassword()" 
                                title="Generate random secure password"
                                class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer inline-flex items-center gap-1">
                            <i class="fas fa-key text-[9px]"></i>
                            <span>Random</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" 
                               x-model="pppoePassword"
                               @input="clearError('password')"
                               value="{{ old('password', '123456') }}"
                               placeholder="Secret Key" 
                               :class="errors.password ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                               class="w-full pl-3 pr-8 py-1.5 rounded-lg text-xs font-mono font-semibold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <button type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="fas" :class="showPassword ? 'fa-eye-slash text-[11px]' : 'fa-eye text-[11px]'"></i>
                        </button>
                    </div>
                    <p x-show="errors.password" x-text="errors.password" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('password')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Gateway MikroTik Router -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Gateway MikroTik Router</label>
                    <select name="router_id" 
                            x-model="selectedRouterId"
                            @change="onRouterChange($event)"
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('router_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- Choose Gateway Router --</option>
                        @foreach($allRouters as $rtr)
                            <option value="{{ $rtr->id }}" 
                                    data-name="{{ $rtr->name }}"
                                    data-ip="{{ $rtr->ip_address }}"
                                    {{ old('router_id') == $rtr->id ? 'selected' : '' }}>
                                {{ $rtr->name }} ({{ $rtr->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Framed IP Address -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Framed IP Address <span class="text-slate-400 font-normal">(Optional Static IP)</span></label>
                    <input type="text" 
                           name="ip_address" 
                           value="{{ old('ip_address') }}"
                           placeholder="172.16.XX.XX" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('ip_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Router / MAC Bind -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Caller-ID / MAC Bind</label>
                    <input type="text" 
                           name="mac_address" 
                           value="{{ old('mac_address') }}"
                           placeholder="AA:BB:CC:DD:EE:FF" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('mac_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- OLT Device -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">OLT Device (Optional Bind)</label>
                    <select name="olt_id" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('olt_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- No OLT Binding --</option>
                        @foreach($allOlts as $olt)
                            <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>
                                {{ $olt->name }} ({{ $olt->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- ONU MAC / Serial -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">ONU MAC / Serial Number</label>
                    <input type="text" 
                           name="onu_mac_sn" 
                           value="{{ old('onu_mac_sn') }}"
                           placeholder="HWTC-XXXXXXXX" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('onu_mac_sn') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Fiber Core / Route Info -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Fiber Core &amp; TJ Box Route</label>
                    <input type="text" 
                           name="fiber_route_info" 
                           value="{{ old('fiber_route_info') }}"
                           placeholder="TJ-4, Core 2, Splitter 1:8" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('fiber_route_info') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- SECTION 3: Package, Billing & Expiry Cycle -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-2 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">3. Internet Package &amp; Billing Lifecycle</h2>
                </div>
                <span class="text-[10.5px] font-mono text-emerald-700 font-semibold" x-text="monthlyBill ? 'Selected Rate: {{ $currencySymbol ?? '৳' }}' + monthlyBill + '/mo' : ''"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Package Selector -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Internet Package</label>
                    <select name="package_id" 
                            x-model="selectedPackageId"
                            @change="onPackageChange($event); clearError('monthly_bill')"
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('package_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="" data-price="0">-- Select Package --</option>
                        @foreach($allPackages as $pkg)
                            <option value="{{ $pkg->id }}" 
                                    data-price="{{ $pkg->price }}" 
                                    data-profile="{{ $pkg->mikrotik_profile ?: $pkg->name }}"
                                    {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->package_name ?: $pkg->name }} (@currency($pkg->price))
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Monthly Rate (Customizable) -->
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
                        <option value="prepaid" {{ old('billing_type', 'prepaid') === 'prepaid' ? 'selected' : '' }}>Prepaid (Auto-cutoff)</option>
                        <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid (Monthly Invoice)</option>
                    </select>
                </div>

                <!-- Expiry Date with Smart Quick Presets -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Initial Line Expiry Date</label>
                    <input type="date" 
                           name="expiry_date" 
                           x-model="expiryDate"
                           value="{{ old('expiry_date', now()->addDays(30)->format('Y-m-d')) }}"
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-semibold transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('expiry_date') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>

            <!-- Expiry Quick Buttons (Wrap gracefully on mobile) -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 pt-1">
                <span class="text-[10px] font-semibold text-slate-400 uppercase mr-0.5 sm:mr-1">Quick Presets:</span>
                <button type="button" 
                        @click="setExpiry(30)" 
                        class="px-2 py-1 rounded text-[10.5px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    +30 Days
                </button>
                <button type="button" 
                        @click="setExpiry('next_month_same_day')" 
                        class="px-2 py-1 rounded text-[10.5px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    +1 Month
                </button>
                <button type="button" 
                        @click="setExpiry('first_of_next_month')" 
                        class="px-2 py-1 rounded text-[10.5px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    1st of Next Month
                </button>
                <button type="button" 
                        @click="setExpiry(90)" 
                        class="px-2 py-1 rounded text-[10.5px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    Quarterly (+90d)
                </button>
            </div>
        </div>

        <!-- SECTION 4: Initial Status & Administrative Remarks -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs border border-amber-100 flex-shrink-0">
                    <i class="fas fa-sliders"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">4. Account Status &amp; Admin Notes</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                <!-- Initial Account Status -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Initial Account Status</label>
                    <select name="status" 
                            class="w-full px-3 py-1.5 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('status') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>🟢 Active Line (Provisioned)</option>
                        <option value="due" {{ old('status') === 'due' ? 'selected' : '' }}>🟡 Bill Due</option>
                        <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>🔴 Expired / Cutoff</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>⚪ Suspended</option>
                        <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>⚫ Disabled</option>
                    </select>
                </div>

                <!-- Internal Remarks -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Administrative Remarks / Internal Notes</label>
                    <input type="text" 
                           name="remarks" 
                           value="{{ old('remarks') }}"
                           placeholder="VIP corporate subscriber, special fiber route instructions, etc." 
                           class="w-full px-3 py-1.5 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('remarks') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- 5. Submit Bar (Centralized Clean Action Bar with Responsive Mobile Stack) -->
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
                <span x-text="isSubmitting ? 'Saving &amp; Provisioning to MikroTik...' : 'Save &amp; Provision Subscriber'"></span>
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function customerCreateManager() {
    return {
        fullName: '{{ old('name', '') }}',
        pppoeUsername: '{{ old('username', '') }}',
        pppoePassword: '{{ old('password', '123456') }}',
        mobilePhone: '{{ old('phone', '') }}',
        monthlyBill: '{{ old('monthly_bill', '') }}',
        selectedPackageId: '{{ old('package_id', '') }}',
        selectedRouterId: '{{ old('router_id', '') }}',
        selectedRouterName: '',
        expiryDate: '{{ old('expiry_date', now()->addDays(30)->format('Y-m-d')) }}',
        showPassword: false,
        isSubmitting: false,
        errors: {},

        init() {
            // Check initial router selection name
            const routerSelect = document.querySelector('select[name="router_id"]');
            if (routerSelect && routerSelect.selectedIndex > 0) {
                const opt = routerSelect.options[routerSelect.selectedIndex];
                this.selectedRouterName = opt.dataset.name || '';
            }
        },

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
                this.errors.phone = 'প্রাইমারি মোবাইল নম্বর প্রদান করুন (Phone number is required)';
            } else if (this.mobilePhone.trim().replace(/[^0-9]/g, '').length < 8) {
                this.errors.phone = 'সঠিক মোবাইল নম্বর প্রদান করুন (Valid phone number required)';
            }

            // 3. Validate PPPoE Username
            if (!this.pppoeUsername || !this.pppoeUsername.trim()) {
                this.errors.username = 'PPPoE ইউজারনেম প্রদান করুন (Username is required)';
            }

            // 4. Validate PPPoE Password
            if (!this.pppoePassword || !this.pppoePassword.trim()) {
                this.errors.password = 'পাসওয়ার্ড প্রদান করুন (Password is required)';
            } else if (this.pppoePassword.trim().length < 4) {
                this.errors.password = 'পাসওয়ার্ড কমপক্ষে ৪ অক্ষরের হতে হবে (Minimum 4 characters)';
            }

            // 5. Validate Monthly Bill
            if (this.monthlyBill === '' || this.monthlyBill === null || isNaN(this.monthlyBill) || Number(this.monthlyBill) < 0) {
                this.errors.monthly_bill = 'মাসিক বিল অ্যামাউন্ট উল্লেখ করুন (Monthly bill is required)';
            }

            return Object.keys(this.errors).length === 0;
        },

        submitForm(event) {
            if (!this.validateForm()) {
                // Focus & smoothly scroll to first error field
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

        suggestUsername(force = false) {
            if (!force && this.pppoeUsername !== '') return;
            if (!this.fullName) return;

            // Generate clean lowercase alphanumeric username with suffix
            const clean = this.fullName
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');

            const phoneSuffix = this.mobilePhone ? this.mobilePhone.replace(/[^0-9]/g, '').slice(-4) : Math.floor(100 + Math.random() * 900);
            this.pppoeUsername = (clean ? clean.split('_')[0] : 'user') + '_' + phoneSuffix;
            this.clearError('username');
        },

        generatePassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
            let pass = '';
            for (let i = 0; i < 6; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.pppoePassword = pass;
            this.showPassword = true;
            this.clearError('password');
        },

        onPackageChange(event) {
            const selectEl = event.target;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                this.monthlyBill = selectedOption.dataset.price;
            }
        },

        onRouterChange(event) {
            const selectEl = event.target;
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (selectedOption && selectedOption.dataset.name) {
                this.selectedRouterName = selectedOption.dataset.name;
            } else {
                this.selectedRouterName = '';
            }
        },

        setExpiry(preset) {
            const now = new Date();
            let targetDate = new Date();

            if (preset === 'next_month_same_day') {
                targetDate.setMonth(targetDate.getMonth() + 1);
            } else if (preset === 'first_of_next_month') {
                targetDate = new Date(now.getFullYear(), now.getMonth() + 1, 1);
            } else if (typeof preset === 'number') {
                targetDate.setDate(targetDate.getDate() + preset);
            }

            const yyyy = targetDate.getFullYear();
            const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
            const dd = String(targetDate.getDate()).padStart(2, '0');
            this.expiryDate = `${yyyy}-${mm}-${dd}`;
        }
    };
}
</script>
@endpush

