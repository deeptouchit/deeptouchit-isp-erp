@extends('tenant.layouts.app')

@section('title', __('Onboard New Customer') . ' - ' . ($tenant->company_name ?? $tenant->name))
@section('full_width', true)

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="w-full space-y-3 sm:space-y-4" x-data="customerCreateManager()">
    
    <!-- 1. Top Header Bar (Strictly Back Arrow + Title + Info Badges ONLY - No Subtitles/Descriptions) -->
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <a href="{{ route('tenant.customers.index') }}" 
               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition cursor-pointer flex-shrink-0"
               title="{{ __('Back to Customers Table') }}">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight truncate">{{ __('Onboard New Customer') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 ml-auto sm:ml-0">
            <!-- Suggested ID Badge -->
            <div class="inline-flex items-center gap-1.5 bg-cyan-50 text-cyan-800 border border-cyan-200 px-2.5 py-1 rounded-lg text-xs font-mono font-semibold shadow-2xs">
                <span class="text-[10px] text-cyan-600 font-sans uppercase font-medium">{{ __('Auto ID:') }}</span>
                <span class="font-bold tracking-wider">{{ $autoCustomerId }}</span>
            </div>

            <!-- Provisioning Mode Indicator -->
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ring-2 ring-emerald-300 animate-pulse"></span>
                <span>{{ __('MikroTik Live Sync Ready') }}</span>
            </span>
        </div>
    </div>

    @if(isset($customerQuota) && !($customerQuota['allowed'] ?? true))
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2.5 text-amber-900 text-xs font-medium">
                <i class="fas fa-triangle-exclamation text-amber-600 text-sm flex-shrink-0"></i>
                <span>{{ $customerQuota['message'] }}</span>
            </div>
            <a href="{{ route('tenant.billing.dashboard') }}"
               class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 flex-shrink-0">
                <i class="fas fa-arrow-up-right-dots text-[10px]"></i>
                <span>{{ __('Upgrade Plan') }}</span>
            </a>
        </div>
    @endif

    <!-- Validation Notification Banner (Appears only on form error) -->
    <div x-show="Object.keys(errors).length > 0" 
         x-transition
         class="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between gap-2 shadow-2xs"
         style="display: none;">
        <div class="flex items-center gap-2 text-rose-800 text-xs font-medium min-w-0">
            <i class="fas fa-circle-exclamation text-rose-600 text-sm flex-shrink-0"></i>
            <span class="truncate sm:whitespace-normal">{{ __('Please fill in the highlighted fields below accurately.') }}</span>
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
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('1. Subscriber Profile & Affiliation') }}</h2>
                </div>
                <span class="text-[10px] sm:text-[10.5px] text-slate-400 font-medium">{!! __('Fields marked with :asterisk are required', ['asterisk' => '<span class="text-rose-500 font-bold">*</span>']) !!}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Scope / Reseller Affiliation -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Affiliation Scope / Partner') }}</label>
                    <select name="reseller_id" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('reseller_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">{{ __('ISP Direct Retail (Headquarters)') }}</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}" {{ old('reseller_id') == $r->id ? 'selected' : '' }}>
                                {{ __('Sub-ISP') }}: {{ $r->name }} ({{ $r->code ?: $r->prefix }})
                            </option>
                        @endforeach
                    </select>
                    @error('reseller_id')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Customer ID -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">{{ __('Customer ID') }} <span class="text-cyan-600 font-semibold">({{ __('Auto Generated') }})</span></label>
                        <span class="text-[9.5px] text-slate-400 font-mono">{{ __('DB Sequence') }}</span>
                    </div>
                    <div class="relative">
                        <input type="text" 
                               name="customer_id" 
                               value="{{ old('customer_id', $autoCustomerId) }}"
                               placeholder="{{ __('e.g. :example', ['example' => $autoCustomerId]) }}" 
                               class="w-full h-[36px] pl-3 pr-8 rounded-lg text-xs font-mono font-bold text-cyan-900 tracking-wider transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('customer_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 @enderror">
                        <span class="absolute right-2.5 inset-y-0 flex items-center text-cyan-600 text-xs" title="{{ __('Auto Unique Master ID') }}">
                            <i class="fas fa-fingerprint text-[11px]"></i>
                        </span>
                    </div>
                    @error('customer_id')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Subscriber Full Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           x-model="fullName"
                           @input="clearError('name'); suggestUsername()"
                           value="{{ old('name') }}"
                           placeholder="{{ __('e.g. Rahim Ahmed') }}" 
                           :class="errors.name ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-medium border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.name" x-text="errors.name" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('name')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Primary Mobile -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Primary Mobile Phone') }} <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" 
                               name="phone" 
                               x-model="mobilePhone"
                               @input="clearError('phone'); suggestUsername()"
                               value="{{ old('phone') }}"
                               placeholder="{{ __('01711-XXXXXX') }}" 
                               :class="errors.phone ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                               class="w-full h-[36px] pl-3 pr-8 rounded-lg text-xs font-mono border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <span class="absolute right-2.5 inset-y-0 flex items-center text-slate-400 text-xs">
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Alternate / Landline Phone') }}</label>
                    <input type="text" 
                           name="alt_phone" 
                           value="{{ old('alt_phone') }}"
                           placeholder="{{ __('Optional second number') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('alt_phone') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Email Address') }}</label>
                    <div class="relative">
                        <input type="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               placeholder="{{ __('client@example.com') }}" 
                               class="w-full h-[36px] pl-3 pr-8 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('email') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <span class="absolute right-2.5 inset-y-0 flex items-center text-slate-400 text-xs">
                            <i class="fas fa-envelope text-[11px]"></i>
                        </span>
                    </div>
                </div>

                <!-- National ID -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('National ID (NID / Smart Card)') }}</label>
                    <input type="text" 
                           name="national_id" 
                           value="{{ old('national_id') }}"
                           placeholder="{{ __('10, 13 or 17 digit NID') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('national_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Father / Guardian Name -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __("Father's / Guardian's Name") }}</label>
                    <input type="text" 
                           name="father_name" 
                           value="{{ old('father_name') }}"
                           placeholder="{{ __('Guardian name') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('father_name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Zone / Area Selection -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Coverage Zone / Area') }}</label>
                    <select name="zone_id" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('zone_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- {{ __('Select Coverage Zone') }} --</option>
                        @foreach($coverageZones ?? [] as $cz)
                            <option value="{{ $cz->id }}" {{ old('zone_id') == $cz->id ? 'selected' : '' }}>
                                {{ $cz->code }} - {{ $cz->name }}{{ $cz->city_upazila ? " ({$cz->city_upazila})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Physical Installation Address (Spans remaining columns) -->
                <div class="sm:col-span-2 md:col-span-2 xl:col-span-3">
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Physical Installation Address') }}</label>
                    <input type="text" 
                           name="address" 
                           value="{{ old('address') }}"
                           placeholder="{{ __('House, Road, Block, Flat / Floor details...') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- SECTION 2: Technical & MikroTik Provisioning -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-2 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs border border-indigo-100 flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('2. Technical & MikroTik Provisioning') }}</h2>
                </div>
                <div class="text-[10.5px] font-mono text-slate-500">
                    {{ __('Target:') }} <span class="font-semibold text-indigo-700" x-text="selectedRouterName || i18n.autoRouter"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Connection Protocol -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Connection Protocol') }} <span class="text-rose-500">*</span></label>
                    <select name="connection_type" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('connection_type') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="pppoe" {{ old('connection_type', 'pppoe') === 'pppoe' ? 'selected' : '' }}>{{ __('PPPoE (Point-to-Point Protocol)') }}</option>
                        <option value="static_ip" {{ old('connection_type') === 'static_ip' ? 'selected' : '' }}>{{ __('Static IP Routing') }}</option>
                        <option value="dhcp" {{ old('connection_type') === 'dhcp' ? 'selected' : '' }}>{{ __('DHCP Auto-bind') }}</option>
                        <option value="hotspot" {{ old('connection_type') === 'hotspot' ? 'selected' : '' }}>{{ __('Hotspot Voucher') }}</option>
                    </select>
                </div>

                <!-- PPPoE Username -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-medium text-slate-700">{{ __('PPPoE Username') }} <span class="text-rose-500">*</span></label>
                        <button type="button" 
                                @click="suggestUsername(true)" 
                                title="{{ __('Auto-generate clean username from Name') }}"
                                class="text-[10px] text-cyan-600 hover:text-cyan-800 font-semibold cursor-pointer inline-flex items-center gap-1">
                            <i class="fas fa-bolt text-[9px]"></i>
                            <span>{{ __('Auto') }}</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input type="text" 
                               name="username" 
                               x-model="pppoeUsername"
                               @input="clearError('username')"
                               value="{{ old('username') }}"
                               placeholder="{{ __('unique_username') }}" 
                               :class="errors.username ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-cyan-900'"
                               class="w-full h-[36px] pl-3 pr-8 rounded-lg text-xs font-mono font-semibold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <span class="absolute right-2.5 inset-y-0 flex items-center text-slate-400 text-xs">
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
                        <label class="block text-[11px] font-medium text-slate-700">{{ __('PPPoE Password / Secret') }} <span class="text-rose-500">*</span></label>
                        <button type="button" 
                                @click="generatePassword()" 
                                title="{{ __('Generate random secure password') }}"
                                class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer inline-flex items-center gap-1">
                            <i class="fas fa-key text-[9px]"></i>
                            <span>{{ __('Random') }}</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" 
                               x-model="pppoePassword"
                               @input="clearError('password')"
                               value="{{ old('password', '123456') }}"
                               placeholder="{{ __('Secret Key') }}" 
                               :class="errors.password ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-800'"
                               class="w-full h-[36px] pl-3 pr-8 rounded-lg text-xs font-mono font-semibold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                        <button type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute right-2.5 inset-y-0 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Gateway MikroTik Router') }}</label>
                    <select name="router_id" 
                            x-model="selectedRouterId"
                            @change="onRouterChange($event)"
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('router_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- {{ __('Choose Gateway Router') }} --</option>
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Framed IP Address') }} <span class="text-slate-400 font-normal">({{ __('Optional Static IP') }})</span></label>
                    <input type="text" 
                           name="ip_address" 
                           value="{{ old('ip_address') }}"
                           placeholder="172.16.XX.XX" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('ip_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Router / MAC Bind -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Caller-ID / MAC Bind') }}</label>
                    <input type="text" 
                           name="mac_address" 
                           value="{{ old('mac_address') }}"
                           placeholder="AA:BB:CC:DD:EE:FF" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('mac_address') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- OLT Device -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('OLT Device (Optional Bind)') }}</label>
                    <select name="olt_id" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('olt_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="">-- {{ __('No OLT Binding') }} --</option>
                        @foreach($allOlts as $olt)
                            <option value="{{ $olt->id }}" {{ old('olt_id') == $olt->id ? 'selected' : '' }}>
                                {{ $olt->name }} ({{ $olt->ip_address }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- PON Port / Slot -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('PON Port / Slot') }}</label>
                    <input type="text" 
                           name="pon_port" 
                           list="ponPortListCreate"
                           value="{{ old('pon_port') }}"
                           placeholder="{{ __('e.g. 0/4 or 0/4:47') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('pon_port') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                    <datalist id="ponPortListCreate">
                        @foreach($allPonPorts ?? [] as $port)
                            <option value="{{ $port }}">{{ $port }}</option>
                        @endforeach
                    </datalist>
                </div>

                <!-- ONU MAC / Serial -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('ONU MAC / Serial Number') }}</label>
                    <input type="text" 
                           name="onu_mac_sn" 
                           value="{{ old('onu_mac_sn') }}"
                           placeholder="HWTC-XXXXXXXX" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono uppercase transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('onu_mac_sn') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>

                <!-- Fiber Core / Route Info (Spans 2-4 cols) -->
                <div class="sm:col-span-1 md:col-span-2 xl:col-span-3">
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Fiber Core & TJ Box Route') }}</label>
                    <input type="text" 
                           name="fiber_route_info" 
                           value="{{ old('fiber_route_info') }}"
                           placeholder="{{ __('TJ-4, Core 2, Splitter 1:8') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('fiber_route_info') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
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
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('3. Internet Package & Billing Lifecycle') }}</h2>
                </div>
                <span class="text-[10.5px] font-mono text-emerald-700 font-semibold" x-text="monthlyBill ? i18n.selectedRate.replace(':amount', formatCurrency(monthlyBill)) : ''"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Package Selector -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Internet Package') }}</label>
                    <select name="package_id" 
                            x-model="selectedPackageId"
                            @change="onPackageChange($event); clearError('monthly_bill')"
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('package_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="" data-price="0">-- {{ __('Select Package') }} --</option>
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
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Monthly Bill (:currency)', ['currency' => $currencySymbol ?? '৳']) }} <span class="text-rose-500">*</span></label>
                    <input type="number" 
                           step="0.01" 
                           name="monthly_bill" 
                           x-model="monthlyBill"
                           @input="clearError('monthly_bill')"
                           placeholder="800.00" 
                           :class="errors.monthly_bill ? 'border-rose-400 ring-1 ring-rose-200 bg-rose-50/40 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-900'"
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono font-bold border transition focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <p x-show="errors.monthly_bill" x-text="errors.monthly_bill" class="text-[10.5px] text-rose-600 font-medium mt-1 flex items-center gap-1" style="display: none;"></p>
                    @error('monthly_bill')
                        <span class="text-[10px] text-rose-600 font-medium block mt-0.5">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Billing Type -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Billing Type') }}</label>
                    <select name="billing_type" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('billing_type') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="prepaid" {{ old('billing_type', 'prepaid') === 'prepaid' ? 'selected' : '' }}>{{ __('Prepaid (Auto-cutoff)') }}</option>
                        <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>{{ __('Postpaid (Monthly Invoice)') }}</option>
                    </select>
                </div>

                <!-- Expiry Date with Smart Quick Presets -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Initial Line Expiry Date') }}</label>
                    <input type="date" 
                           name="expiry_date" 
                           x-model="expiryDate"
                           value="{{ old('expiry_date', now()->addDays(30)->format('Y-m-d')) }}"
                           class="w-full h-[36px] px-3 rounded-lg text-xs font-mono font-semibold transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('expiry_date') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>

            <!-- Expiry Quick Buttons (Wrap gracefully on mobile) -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 pt-1 border-t border-slate-50">
                <span class="text-[10px] font-semibold text-slate-400 uppercase mr-0.5 sm:mr-1">{{ __('Quick Presets:') }}</span>
                <button type="button" 
                        @click="setExpiry(30)" 
                        class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    {{ __('+30 Days') }}
                </button>
                <button type="button" 
                        @click="setExpiry('next_month_same_day')" 
                        class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    {{ __('+1 Month') }}
                </button>
                <button type="button" 
                        @click="setExpiry('first_of_next_month')" 
                        class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    {{ __('1st of Next Month') }}
                </button>
                <button type="button" 
                        @click="setExpiry(90)" 
                        class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition cursor-pointer">
                    {{ __('Quarterly (+90d)') }}
                </button>
            </div>
        </div>

        <!-- SECTION 4: Initial Status & Administrative Remarks -->
        <div class="bg-white p-3.5 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-3.5 sm:space-y-4">
            <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs border border-amber-100 flex-shrink-0">
                    <i class="fas fa-sliders"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('4. Account Status & Admin Notes') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-3.5">
                <!-- Initial Account Status -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Initial Account Status') }}</label>
                    <select name="status" 
                            class="w-full h-[36px] px-3 rounded-lg text-xs font-medium transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('status') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>🟢 {{ __('Active Line (Provisioned)') }}</option>
                        <option value="due" {{ old('status') === 'due' ? 'selected' : '' }}>🟡 {{ __('Bill Due') }}</option>
                        <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>🔴 {{ __('Expired / Cutoff') }}</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>⚪ {{ __('Suspended') }}</option>
                        <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>⚫ {{ __('Disabled') }}</option>
                    </select>
                </div>

                <!-- Internal Remarks (Spans 2-3 columns) -->
                <div class="sm:col-span-2 md:col-span-2 xl:col-span-3">
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Administrative Remarks / Internal Notes') }}</label>
                    <input type="text" 
                           name="remarks" 
                           value="{{ old('remarks') }}"
                           placeholder="{{ __('VIP corporate subscriber, special fiber route instructions, etc.') }}" 
                           class="w-full h-[36px] px-3 rounded-lg text-xs transition focus:bg-white focus:border-cyan-500 focus:outline-hidden @error('remarks') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror">
                </div>
            </div>
        </div>

        <!-- 5. Submit Bar (Centralized Clean Action Bar with Responsive Mobile Stack) -->
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5 bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
            <a href="{{ route('tenant.customers.index') }}" 
               class="text-center border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-2 rounded-lg transition cursor-pointer">
                {{ __('Cancel') }}
            </a>

            <button type="submit" 
                    :disabled="isSubmitting"
                    class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-6 py-2.5 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                <i class="fas fa-spinner fa-spin text-xs" x-show="isSubmitting" style="display: none;"></i>
                <i class="fas fa-check text-xs" x-show="!isSubmitting"></i>
                <span x-text="isSubmitting ? i18n.savingProvisioning : i18n.saveProvisionSubscriber"></span>
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function customerCreateManager() {
    return {
        fullName: @json(old('name', '')),
        pppoeUsername: @json(old('username', '')),
        pppoePassword: @json(old('password', '123456')),
        mobilePhone: @json(old('phone', '')),
        monthlyBill: @json(old('monthly_bill', '')),
        selectedPackageId: @json(old('package_id', '')),
        selectedRouterId: @json(old('router_id', '')),
        selectedRouterName: '',
        expiryDate: @json(old('expiry_date', now()->addDays(30)->format('Y-m-d'))),
        showPassword: false,
        isSubmitting: false,
        errors: {},
        currencySymbol: @json($currencySymbol ?? '৳'),
        currencyPosition: @json($currencyPosition ?? 'left'),

        i18n: {
            autoRouter: @json(__('Auto Router')),
            selectedRate: @json(__('Selected Rate: :amount/mo')),
            savingProvisioning: @json(__('Saving & Provisioning to MikroTik...')),
            saveProvisionSubscriber: @json(__('Save & Provision Subscriber')),
            errNameRequired: @json(__('Subscriber full name is required.')),
            errPhoneRequired: @json(__('Primary mobile phone number is required.')),
            errPhoneInvalid: @json(__('Please enter a valid mobile phone number.')),
            errUsernameRequired: @json(__('PPPoE username is required.')),
            errPasswordRequired: @json(__('PPPoE password is required.')),
            errPasswordMin: @json(__('Password must be at least 4 characters.')),
            errMonthlyBillRequired: @json(__('Monthly bill amount is required.'))
        },

        formatCurrency(amount) {
            return this.currencyPosition === 'right'
                ? `${amount} ${this.currencySymbol}`
                : `${this.currencySymbol}${amount}`;
        },

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
                this.errors.name = this.i18n.errNameRequired;
            }

            // 2. Validate Phone
            if (!this.mobilePhone || !this.mobilePhone.trim()) {
                this.errors.phone = this.i18n.errPhoneRequired;
            } else if (this.mobilePhone.trim().replace(/[^0-9]/g, '').length < 8) {
                this.errors.phone = this.i18n.errPhoneInvalid;
            }

            // 3. Validate PPPoE Username
            if (!this.pppoeUsername || !this.pppoeUsername.trim()) {
                this.errors.username = this.i18n.errUsernameRequired;
            }

            // 4. Validate PPPoE Password
            if (!this.pppoePassword || !this.pppoePassword.trim()) {
                this.errors.password = this.i18n.errPasswordRequired;
            } else if (this.pppoePassword.trim().length < 4) {
                this.errors.password = this.i18n.errPasswordMin;
            }

            // 5. Validate Monthly Bill
            if (this.monthlyBill === '' || this.monthlyBill === null || isNaN(this.monthlyBill) || Number(this.monthlyBill) < 0) {
                this.errors.monthly_bill = this.i18n.errMonthlyBillRequired;
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

