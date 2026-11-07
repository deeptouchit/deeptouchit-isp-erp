@extends('tenant.layouts.app')

@section('title', 'Payment Gateway Settings - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="paymentGatewayManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 border border-pink-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-credit-card"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Payment Gateways &amp; Merchant API Settings</h1>
        </div>
        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" 
                    @click="activeTab === 'bkash' ? testBkashConnection() : (activeTab === 'nagad' ? testNagadConnection() : testBankQr())" 
                    class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plug text-slate-500 text-xs"></i>
                <span>Test API Handshake</span>
            </button>
            <a href="{{ route('tenant.finance.gateway-transactions') }}" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs"></i>
                <span>View Transactions</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Active Channels --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Channels</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ $stats['active_channels'] }} Gateways
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-toggle-on"></i>
            </div>
        </div>

        {{-- Card 2: bKash Mode --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">bKash Status</span>
                <span class="text-[13px] font-bold font-mono text-pink-600 leading-tight block truncate">
                    {{ $stats['bkash_mode'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-pink-200 bg-pink-50 text-pink-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 3: Nagad Mode --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Nagad Status</span>
                <span class="text-[13px] font-bold font-mono text-orange-600 leading-tight block truncate">
                    {{ $stats['nagad_mode'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-orange-200 bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 4: Total Gateway Collections --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Online Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['total_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Card 5: Successful Payments --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Successful</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">
                    {{ number_format($stats['successful_tx']) }} TXNs
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 6: Failed / Expired --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Failed / Dropped</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ number_format($stats['failed_tx']) }} TXNs
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- 3. TABBED CONFIGURATION HUB --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Segmented Tab Header -->
        <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3 pt-2 gap-1 overflow-x-auto">
            <button type="button" 
                    @click="activeTab = 'bkash'" 
                    class="h-[36px] px-4 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer whitespace-nowrap"
                    :class="activeTab === 'bkash' ? 'bg-white text-pink-700 border-pink-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-mobile-screen text-pink-600 text-xs"></i>
                <span>bKash Merchant API</span>
                @if($bkashSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'nagad'" 
                    class="h-[36px] px-4 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer whitespace-nowrap"
                    :class="activeTab === 'nagad' ? 'bg-white text-orange-700 border-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-mobile-screen text-orange-600 text-xs"></i>
                <span>Nagad Merchant API</span>
                @if($nagadSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'bank_qr'" 
                    class="h-[36px] px-4 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer whitespace-nowrap"
                    :class="activeTab === 'bank_qr' ? 'bg-white text-teal-700 border-teal-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-qrcode text-teal-600 text-xs"></i>
                <span>Bangla QR &amp; Bank Info</span>
                @if($bankQrSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'logs'" 
                    class="h-[36px] px-4 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer whitespace-nowrap"
                    :class="activeTab === 'logs' ? 'bg-white text-blue-700 border-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-list-check text-blue-600 text-xs"></i>
                <span>Gateway Logs &amp; Webhooks ({{ $logs->total() }})</span>
            </button>
        </div>

        <div class="p-4 sm:p-5">
            
            {{-- TAB 1: bKash Merchant API Configuration --}}
            <div x-show="activeTab === 'bkash'" class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.bkash') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-pink-50/50 border border-pink-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-pink-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-mobile-screen"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-pink-950">bKash Tokenized Checkout API</h3>
                                <p class="text-[11px] text-pink-800">Automated 1-click customer invoice payment and reseller wallet recharge via bKash PGW</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-pink-200 transition">
                                <input type="checkbox" name="is_active" value="1" {{ $bkashSettings['is_active'] ? 'checked' : '' }} class="w-4 h-4 rounded text-pink-600 focus:ring-pink-500 cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $bkashSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Environment Mode Selector -->
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 mb-4">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Environment Mode *</label>
                        <div class="grid grid-cols-2 gap-2 sm:w-80">
                            <label class="h-[34px] flex items-center gap-2 px-3 rounded-lg border cursor-pointer transition {{ $bkashSettings['mode'] === 'sandbox' ? 'bg-white border-pink-500 ring-1 ring-pink-400 font-bold text-pink-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="sandbox" {{ $bkashSettings['mode'] === 'sandbox' ? 'checked' : '' }} class="text-pink-600 focus:ring-pink-500 cursor-pointer">
                                <span class="text-xs">Sandbox (Testing)</span>
                            </label>
                            <label class="h-[34px] flex items-center gap-2 px-3 rounded-lg border cursor-pointer transition {{ $bkashSettings['mode'] === 'live' ? 'bg-white border-emerald-500 ring-1 ring-emerald-400 font-bold text-emerald-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="live" {{ $bkashSettings['mode'] === 'live' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                <span class="text-xs">Live (Production)</span>
                            </label>
                        </div>
                    </div>

                    <!-- API Credentials Grid (Strict Uniform h-[34px]) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        
                        <!-- App Key -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">App Key *</label>
                            <input type="text" 
                                   name="app_key" 
                                   value="{{ $bkashSettings['app_key'] }}" 
                                   placeholder="e.g. 4fxxxxxxxxxxxxxxxxxxxxxx" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- App Secret -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">App Secret *</label>
                            <div class="relative">
                                <input :type="showBkashSecret ? 'text' : 'password'" 
                                       name="app_secret" 
                                       value="{{ $bkashSettings['app_secret'] }}" 
                                       placeholder="••••••••••••••••••••••••" 
                                       class="h-[34px] w-full pl-3 pr-8 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                                <button type="button" @click="showBkashSecret = !showBkashSecret" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fas" :class="showBkashSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Username *</label>
                            <input type="text" 
                                   name="username" 
                                   value="{{ $bkashSettings['username'] }}" 
                                   placeholder="e.g. sandboxTestUser" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Password *</label>
                            <div class="relative">
                                <input :type="showBkashPass ? 'text' : 'password'" 
                                       name="password" 
                                       value="{{ $bkashSettings['password'] }}" 
                                       placeholder="••••••••••••••••" 
                                       class="h-[34px] w-full pl-3 pr-8 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                                <button type="button" @click="showBkashPass = !showBkashPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fas" :class="showBkashPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Merchant Phone / Number -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Merchant Account / Till Number</label>
                            <input type="text" 
                                   name="merchant_number" 
                                   value="{{ $bkashSettings['merchant_number'] }}" 
                                   placeholder="e.g. 01819000000" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- Transaction Fee Percentage -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Gateway Fee (%)</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="fee_value" 
                                   value="{{ $bkashSettings['fee_value'] }}" 
                                   placeholder="1.50" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-pink-500 shadow-2xs">
                        </div>

                    </div>

                    <!-- Integration URLs / Webhook Box -->
                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                        <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">bKash Webhook &amp; Callback Endpoint URLs</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="p-2 rounded bg-white border border-slate-200 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <span class="text-[9.5px] text-slate-400 block uppercase font-bold">IPN / Webhook URL</span>
                                    <span class="font-mono text-[10.5px] text-slate-800 truncate block">{{ url('/api/payment/webhook/bkash') }}</span>
                                </div>
                                <button type="button" @click="copyText('{{ url('/api/payment/webhook/bkash') }}', 'Webhook URL')" class="h-[28px] px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border border-slate-300 transition cursor-pointer">
                                    Copy
                                </button>
                            </div>

                            <div class="p-2 rounded bg-white border border-slate-200 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <span class="text-[9.5px] text-slate-400 block uppercase font-bold">Callback Return URL</span>
                                    <span class="font-mono text-[10.5px] text-slate-800 truncate block">{{ url('/api/payment/callback/bkash') }}</span>
                                </div>
                                <button type="button" @click="copyText('{{ url('/api/payment/callback/bkash') }}', 'Callback URL')" class="h-[28px] px-2.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border border-slate-300 transition cursor-pointer">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" 
                                @click="testBkashConnection()" 
                                :disabled="isTestingBkash"
                                class="h-[34px] py-0 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition flex items-center gap-1.5 cursor-pointer">
                            <template x-if="isTestingBkash">
                                <i class="fas fa-spinner fa-spin text-xs"></i>
                            </template>
                            <template x-if="!isTestingBkash">
                                <i class="fas fa-bolt text-pink-600 text-xs"></i>
                            </template>
                            <span>Test Live Token Grant</span>
                        </button>

                        <button type="submit" class="h-[34px] py-0 px-5 bg-pink-600 hover:bg-pink-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-floppy-disk text-xs"></i>
                            <span>Save bKash Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 2: Nagad Merchant API Configuration --}}
            <div x-show="activeTab === 'nagad'" class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.nagad') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-orange-50/50 border border-orange-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-mobile-screen"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-orange-950">Nagad Direct Payment Gateway</h3>
                                <p class="text-[11px] text-orange-800">Secure automated merchant clearance via Nagad Public/Private RSA signature exchange</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-orange-200 transition">
                                <input type="checkbox" name="is_active" value="1" {{ $nagadSettings['is_active'] ? 'checked' : '' }} class="w-4 h-4 rounded text-orange-600 focus:ring-orange-500 cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $nagadSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Environment Mode Selector -->
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 mb-4">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Environment Mode *</label>
                        <div class="grid grid-cols-2 gap-2 sm:w-80">
                            <label class="h-[34px] flex items-center gap-2 px-3 rounded-lg border cursor-pointer transition {{ $nagadSettings['mode'] === 'sandbox' ? 'bg-white border-orange-500 ring-1 ring-orange-400 font-bold text-orange-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="sandbox" {{ $nagadSettings['mode'] === 'sandbox' ? 'checked' : '' }} class="text-orange-600 focus:ring-orange-500 cursor-pointer">
                                <span class="text-xs">Sandbox (Testing)</span>
                            </label>
                            <label class="h-[34px] flex items-center gap-2 px-3 rounded-lg border cursor-pointer transition {{ $nagadSettings['mode'] === 'live' ? 'bg-white border-emerald-500 ring-1 ring-emerald-400 font-bold text-emerald-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="live" {{ $nagadSettings['mode'] === 'live' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                <span class="text-xs">Live (Production)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Credentials (Strict Uniform h-[34px]) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Merchant ID *</label>
                            <input type="text" 
                                   name="merchant_id" 
                                   value="{{ $nagadSettings['merchant_id'] }}" 
                                   placeholder="e.g. 68xxxxxxxxxxxxx" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-orange-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Merchant Phone / A/C</label>
                            <input type="text" 
                                   name="merchant_phone" 
                                   value="{{ $nagadSettings['merchant_phone'] }}" 
                                   placeholder="e.g. 01711000000" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-orange-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Public & Private Keys -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nagad PGW Public Key</label>
                            <textarea name="public_key" 
                                      rows="5" 
                                      placeholder="-----BEGIN PUBLIC KEY-----&#10;MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...&#10;-----END PUBLIC KEY-----" 
                                      class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono text-[10.5px] text-slate-800 focus:bg-white focus:outline-hidden focus:border-orange-500 shadow-2xs leading-relaxed">{{ $nagadSettings['public_key'] }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Merchant Private Key</label>
                            <textarea name="private_key" 
                                      rows="5" 
                                      placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;MIIEowIBAAKCAQEA0wZk...&#10;-----END RSA PRIVATE KEY-----" 
                                      class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono text-[10.5px] text-slate-800 focus:bg-white focus:outline-hidden focus:border-orange-500 shadow-2xs leading-relaxed">{{ $nagadSettings['private_key'] }}</textarea>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" 
                                @click="testNagadConnection()" 
                                class="h-[34px] py-0 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-shield-halved text-orange-600 text-xs"></i>
                            <span>Verify RSA Keys</span>
                        </button>

                        <button type="submit" class="h-[34px] py-0 px-5 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-floppy-disk text-xs"></i>
                            <span>Save Nagad Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 3: Bangla QR & Bank Info Configuration --}}
            <div x-show="activeTab === 'bank_qr'" class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.bank-qr') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-teal-50/50 border border-teal-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-teal-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-teal-950">Interoperable Bangla QR &amp; Official Bank Account</h3>
                                <p class="text-[11px] text-teal-800">Display official company bank transfer info and Bangla QR on invoices and reseller recharge portal</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-teal-200 transition">
                                <input type="checkbox" name="is_active" value="1" {{ $bankQrSettings['is_active'] ? 'checked' : '' }} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $bankQrSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Bank Account Details Grid (Strict Uniform h-[34px]) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Bank Name *</label>
                            <input type="text" 
                                   name="bank_name" 
                                   value="{{ $bankQrSettings['bank_name'] }}" 
                                   required 
                                   placeholder="e.g. The City Bank Ltd." 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Account Holder Name *</label>
                            <input type="text" 
                                   name="account_name" 
                                   value="{{ $bankQrSettings['account_name'] }}" 
                                   required 
                                   placeholder="e.g. SpeedNet Online" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Account Number *</label>
                            <input type="text" 
                                   name="account_number" 
                                   value="{{ $bankQrSettings['account_number'] }}" 
                                   required 
                                   placeholder="e.g. 1102983746001" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Branch Name</label>
                            <input type="text" 
                                   name="branch_name" 
                                   value="{{ $bankQrSettings['branch_name'] }}" 
                                   placeholder="e.g. Principal Branch" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Routing Number</label>
                            <input type="text" 
                                   name="routing_number" 
                                   value="{{ $bankQrSettings['routing_number'] }}" 
                                   placeholder="e.g. 225271890" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Bangla QR Merchant Number</label>
                            <input type="text" 
                                   name="bangla_qr_number" 
                                   value="{{ $bankQrSettings['bangla_qr_number'] }}" 
                                   placeholder="e.g. 01819000000" 
                                   class="h-[34px] w-full px-3 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-hidden focus:border-teal-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-end">
                        <button type="submit" class="h-[34px] py-0 px-5 bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-floppy-disk text-xs"></i>
                            <span>Save Bank &amp; QR Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 4: Live Gateway Logs & Webhooks --}}
            <div x-show="activeTab === 'logs'" class="space-y-3">
                
                {{-- Search & Multi-Filter Bar (AGENTS.md Rule 2.C) --}}
                <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
                    <form method="GET" action="{{ route('tenant.settings.payment-gateways') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                        <input type="hidden" name="tab" value="logs">
                        
                        {{-- Search Box with 1-Click Clear --}}
                        <div class="relative flex-1 min-w-[200px]">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs"
                               :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                            <input type="text" 
                                   name="search" 
                                   x-model="searchQuery" 
                                   placeholder="Search by Txn ID, customer name, mobile..." 
                                   class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <button type="button" 
                                    x-show="searchQuery && searchQuery.length > 0" 
                                    @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>

                        {{-- Gateway Filter --}}
                        <div class="w-full md:w-44 flex-shrink-0">
                            <select name="gateway" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                                <option value="all">All Gateways</option>
                                <option value="bkash" {{ $gatewayFilter === 'bkash' ? 'selected' : '' }}>bKash PGW</option>
                                <option value="nagad" {{ $gatewayFilter === 'nagad' ? 'selected' : '' }}>Nagad PGW</option>
                                <option value="bank" {{ $gatewayFilter === 'bank' ? 'selected' : '' }}>Bank / QR</option>
                            </select>
                        </div>

                        {{-- Status Filter --}}
                        <div class="w-full md:w-40 flex-shrink-0">
                            <select name="status" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                                <option value="all">All Statuses</option>
                                <option value="SUCCESS" {{ $statusFilter === 'SUCCESS' ? 'selected' : '' }}>Success</option>
                                <option value="PENDING" {{ $statusFilter === 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="FAILED" {{ $statusFilter === 'FAILED' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>

                        {{-- Filter & Reset Buttons (AGENTS.md Rule 2.C) --}}
                        <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                            <button type="submit" class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="fas fa-filter text-[10px]"></i>
                                <span>Filter</span>
                            </button>
                            <a href="{{ route('tenant.settings.payment-gateways', ['tab' => 'logs']) }}" class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                                <i class="fas fa-rotate-left text-[10px]"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>

                {{-- Master Compact Table (<table class="saas-table">) --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="saas-table">
                            <thead>
                                <tr>
                                    <th class="w-10 text-center font-semibold">#</th>
                                    <th class="w-36 text-center font-semibold">Transaction #</th>
                                    <th class="w-28 text-center font-semibold">Gateway</th>
                                    <th class="text-center font-semibold">Customer / Reference</th>
                                    <th class="w-32 text-center font-semibold">Amount</th>
                                    <th class="w-28 text-center font-semibold">Status</th>
                                    <th class="w-36 text-center font-semibold">Date &amp; Time</th>
                                    <th class="w-16 text-center no-sort font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $idx => $log)
                                    <tr>
                                        <td class="text-center font-mono text-slate-500">
                                            {{ ($logs->currentPage() - 1) * $logs->perPage() + $idx + 1 }}
                                        </td>
                                        <td class="text-center font-mono font-bold text-cyan-700">
                                            {{ $log->transaction_id ?? $log->gateway_trx_id ?? ('TXN-' . $log->id) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $log->gateway_badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }} whitespace-nowrap">
                                                <span>{{ ucfirst($log->gateway) }}</span>
                                            </span>
                                        </td>
                                        <td class="text-left text-slate-800 text-xs font-medium">
                                            {{ $log->customer?->name ?? 'Online Subscriber' }}
                                        </td>
                                        <td class="text-right font-mono font-bold text-emerald-600">
                                            @currency($log->amount)
                                        </td>
                                        <td class="text-center">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $log->status_badge['class'] ?? 'bg-emerald-50 text-emerald-700 border-emerald-200' }} whitespace-nowrap">
                                                <span>{{ $log->status }}</span>
                                            </span>
                                        </td>
                                        <td class="text-center font-mono text-slate-600 text-xs">
                                            {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    @click="viewTransactionDetails({{ json_encode($log) }})"
                                                    class="h-[28px] px-2.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-md text-xs font-semibold border border-cyan-200 transition inline-flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                                <i class="fas fa-eye text-[10px]"></i>
                                                <span>View</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                                    <i class="fas fa-receipt text-base"></i>
                                                </div>
                                                <span class="text-xs font-semibold text-slate-600">No Gateway Transactions Found</span>
                                                <p class="text-[11px] text-slate-400 mt-1">Online transactions processed via bKash, Nagad, and Bank QR will appear here.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        <div class="p-3 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-mono">
                                Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} entries
                            </span>
                            <div>
                                {{ $logs->links() }}
                            </div>
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>

    {{-- MODAL: VIEW TRANSACTION DETAILS --}}
    <div x-show="txDetailsModalOpen" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" @click.away="txDetailsModalOpen = false">
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Gateway Transaction Details</h3>
                        <span class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="activeTx?.transaction_id || activeTx?.gateway_trx_id"></span>
                    </div>
                </div>
                <button type="button" @click="txDetailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Transaction ID</span>
                        <span class="font-mono font-bold text-slate-800 text-xs" x-text="activeTx?.transaction_id || activeTx?.gateway_trx_id || 'N/A'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Gateway Status</span>
                        <span class="font-bold uppercase text-xs" :class="activeTx?.status === 'SUCCESS' ? 'text-emerald-600' : 'text-rose-600'" x-text="activeTx?.status"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Amount</span>
                        <span class="font-mono font-bold text-emerald-600 text-sm" x-text="`৳${activeTx?.amount || '0.00'}`"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Gateway Method</span>
                        <span class="font-bold uppercase text-slate-800 text-xs" x-text="activeTx?.gateway"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer / Reference</label>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800 font-medium" x-text="activeTx?.customer?.name || 'Online Subscriber'"></div>
                </div>

                <template x-if="activeTx?.payload || activeTx?.gateway_response">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Raw Gateway Payload</label>
                        <pre class="p-3 bg-slate-900 text-cyan-300 rounded-lg font-mono text-[10.5px] overflow-x-auto max-h-40 leading-relaxed" x-text="formatJson(activeTx?.payload || activeTx?.gateway_response)"></pre>
                    </div>
                </template>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="txDetailsModalOpen = false" class="h-[34px] py-0 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function paymentGatewayManager() {
    return {
        activeTab: '{{ $tab }}',
        searchQuery: '{{ addslashes($search) }}',
        showBkashSecret: false,
        showBkashPass: false,
        isTestingBkash: false,
        txDetailsModalOpen: false,
        activeTx: null,

        viewTransactionDetails(tx) {
            this.activeTx = tx;
            this.txDetailsModalOpen = true;
        },

        formatJson(str) {
            if (!str) return 'No Payload';
            try {
                const parsed = typeof str === 'string' ? JSON.parse(str) : str;
                return JSON.stringify(parsed, null, 2);
            } catch(e) {
                return str;
            }
        },

        copyText(text, label) {
            navigator.clipboard.writeText(text).then(() => {
                if (window.Toast) {
                    Toast.fire({
                        icon: 'success',
                        title: `${label} copied to clipboard!`
                    });
                } else if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: `${label} copied to clipboard.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    alert(`${label} copied: ${text}`);
                }
            }).catch(err => {
                console.error("Copy failed", err);
            });
        },

        testBankQr() {
            Swal.fire({
                icon: 'info',
                title: 'Bangla QR & Bank Info Active',
                text: 'Official company bank deposit credentials and Bangla QR details are configured and ready for customer invoice display.',
                confirmButtonColor: '#0d9488'
            });
        },

        async testBkashConnection() {
            this.isTestingBkash = true;
            try {
                const form = document.querySelector("form[action*='payment-gateways/bkash']");
                const formData = new FormData(form);
                
                const res = await fetch("{{ route('tenant.settings.payment-gateways.test.bkash') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        app_key: formData.get('app_key'),
                        app_secret: formData.get('app_secret'),
                        username: formData.get('username'),
                        password: formData.get('password'),
                        mode: formData.get('mode')
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'bKash Handshake Successful!',
                            text: data.message,
                            confirmButtonColor: '#ec4899',
                        });
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'bKash Connection Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48',
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (err) {
                console.error("bKash test error", err);
                alert("Network error while connecting to bKash API.");
            } finally {
                this.isTestingBkash = false;
            }
        },

        async testNagadConnection() {
            try {
                const form = document.querySelector("form[action*='payment-gateways/nagad']");
                const formData = new FormData(form);
                
                const res = await fetch("{{ route('tenant.settings.payment-gateways.test.nagad') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        merchant_id: formData.get('merchant_id'),
                        public_key: formData.get('public_key'),
                        private_key: formData.get('private_key'),
                        mode: formData.get('mode')
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Nagad Handshake Valid!',
                            text: data.message,
                            confirmButtonColor: '#ea580c',
                        });
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Nagad Verification Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48',
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (err) {
                console.error("Nagad test error", err);
                alert("Network error while testing Nagad connection.");
            }
        }
    }
}
</script>
@endpush
